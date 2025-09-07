<?php
// Authentication configuration for admin panel

// Session configuration - only set if session not started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
}

// Session timeout (in seconds) - 2 hours
define('SESSION_TIMEOUT', 7200);

// Admin roles and permissions
define('ADMIN_ROLES', [
    'super_admin' => [
        'name' => 'Super Administrator',
        'permissions' => [
            'manage_users',
            'manage_teachers', 
            'manage_students',
            'manage_questions',
            'create_questions',
            'edit_questions',
            'delete_questions',
            'view_reports',
            'manage_categories',
            'system_settings',
            'view_activities'
        ]
    ],
    'admin' => [
        'name' => 'Administrator',
        'permissions' => [
            'manage_teachers',
            'manage_students', 
            'manage_questions',
            'create_questions',
            'edit_questions',
            'delete_questions',
            'view_reports',
            'manage_categories',
            'view_activities'
        ]
    ],
    'teacher' => [
        'name' => 'Guru/Pengajar',
        'permissions' => [
            'manage_questions',
            'create_questions',
            'edit_questions',
            'view_reports',
            'manage_categories'
        ]
    ]
]);

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Check if session is valid (not expired)
 */
function isSessionValid() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['admin_role'] ?? 'teacher';
    $roles = ADMIN_ROLES;
    
    if (!isset($roles[$userRole])) {
        return false;
    }
    
    return in_array($permission, $roles[$userRole]['permissions']);
}

/**
 * Get user permissions
 */
function getUserPermissions() {
    if (!isAuthenticated()) {
        return [];
    }
    
    $userRole = $_SESSION['admin_role'] ?? 'teacher';
    $roles = ADMIN_ROLES;
    
    return $roles[$userRole]['permissions'] ?? [];
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated() || !isSessionValid()) {
        // Clear invalid session
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

/**
 * Require specific permission - show 403 error if not authorized
 */
function requirePermission($permission) {
    requireAuth();
    
    if (!hasPermission($permission)) {
        http_response_code(403);
        include 'error_403.php';
        exit();
    }
}

/**
 * Log admin activity
 */
function logActivity($pdo, $activity_type, $description, $target_id = null) {
    if (!isAuthenticated()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_activities 
            (teacher_id, activity_type, description, target_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['admin_id'],
            $activity_type,
            $description,
            $target_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Check password strength
 */
function checkPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    
    // Length check
    if (strlen($password) >= 8) {
        $score += 1;
    } else {
        $feedback[] = 'Password minimal 8 karakter';
    }
    
    // Uppercase check
    if (preg_match('/[A-Z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Gunakan huruf besar';
    }
    
    // Lowercase check
    if (preg_match('/[a-z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Gunakan huruf kecil';
    }
    
    // Number check
    if (preg_match('/[0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Gunakan angka';
    }
    
    // Special character check
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Gunakan karakter khusus';
    }
    
    $strength = 'Sangat Lemah';
    if ($score >= 4) $strength = 'Kuat';
    elseif ($score >= 3) $strength = 'Sedang';
    elseif ($score >= 2) $strength = 'Lemah';
    
    return [
        'score' => $score,
        'strength' => $strength,
        'feedback' => $feedback
    ];
}

/**
 * Rate limiting for login attempts
 */
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 900) { // 15 minutes
    $cache_key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $data = $_SESSION[$cache_key];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$cache_key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
        return true;
    }
    
    return $data['attempts'] < $max_attempts;
}

/**
 * Record login attempt
 */
function recordLoginAttempt($identifier, $success = false) {
    $cache_key = 'login_attempts_' . md5($identifier);
    
    if ($success) {
        // Clear attempts on successful login
        unset($_SESSION[$cache_key]);
    } else {
        // Increment failed attempts
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        $_SESSION[$cache_key]['attempts']++;
    }
}

/**
 * Get remaining lockout time
 */
function getRemainingLockoutTime($identifier, $time_window = 900) {
    $cache_key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$cache_key])) {
        return 0;
    }
    
    $data = $_SESSION[$cache_key];
    $elapsed = time() - $data['first_attempt'];
    
    return max(0, $time_window - $elapsed);
}

/**
 * Format time duration
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . ' detik';
    } elseif ($seconds < 3600) {
        return ceil($seconds / 60) . ' menit';
    } else {
        return ceil($seconds / 3600) . ' jam';
    }
}
?>