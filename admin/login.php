<?php
session_start();
require_once '../config/database.php';
require_once 'auth_config.php';

// Initialize database connection
$pdo = getDBConnection();

$error_message = '';
$success_message = '';
$lockout_time = 0;

// Check if already logged in
if (isAuthenticated() && isSessionValid()) {
    header('Location: index.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } elseif (empty($email) || empty($password)) {
        $error_message = 'Email dan password harus diisi.';
    } else {
        // Check rate limiting
        $identifier = $_SERVER['REMOTE_ADDR'] . '_' . $email;
        
        if (!checkRateLimit($identifier)) {
            $lockout_time = getRemainingLockoutTime($identifier);
            $error_message = 'Terlalu banyak percobaan login. Coba lagi dalam ' . formatDuration($lockout_time) . '.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, password, nama_lengkap, email, role FROM teachers WHERE username = ? AND status = 'active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && verifyPassword($password, $user['password'])) {
                    // Login successful
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_name'] = $user['nama_lengkap'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // Record successful login
                    recordLoginAttempt($identifier, true);
                    
                    // Log activity
                    logActivity($pdo, 'login', 'Login ke admin panel');
                    
                    header('Location: index.php');
                    exit();
                } else {
                    // Record failed login
                    recordLoginAttempt($identifier, false);
                    $error_message = 'Email atau password salah.';
                    
                    // Log failed login attempt
                    if ($user) {
                        logActivity($pdo, 'failed_login', 'Percobaan login gagal untuk: ' . $email, $user['id']);
                    }
                }
            } catch (PDOException $e) {
                error_log('Login error: ' . $e->getMessage());
                $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AKM Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        }
                    }
                }
            }
        }
    </script>

</head>
<body class="min-h-screen bg-gradient-to-br from-blue-500 via-purple-500 to-indigo-600 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-10 text-center">
                <div class="text-white">
                    <i class="fas fa-user-shield text-4xl mb-4"></i>
                    <h1 class="text-2xl font-bold mb-2">AKM Admin Panel</h1>
                    <p class="text-blue-100">Masuk ke dashboard admin</p>
                </div>
            </div>
            
            <!-- Body -->
            <div class="px-8 py-8">
                <!-- Error Messages -->
                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($lockout_time > 0): ?>
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Akun terkunci. Coba lagi dalam <span id="countdown" class="font-semibold"><?= formatDuration($lockout_time) ?></span></span>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <!-- Username Field -->
                     <div>
                         <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                         <div class="relative">
                             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                 <i class="fas fa-user text-gray-400"></i>
                             </div>
                             <input type="text" id="email" name="email" 
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors <?= $lockout_time > 0 ? 'bg-gray-100' : '' ?>"
                                    placeholder="Masukkan username"
                                    <?= $lockout_time > 0 ? 'disabled' : '' ?> required>
                         </div>
                     </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password"
                                   class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors <?= $lockout_time > 0 ? 'bg-gray-100' : '' ?>"
                                   placeholder="Masukkan password"
                                   <?= $lockout_time > 0 ? 'disabled' : '' ?> required>
                            <button type="button" id="togglePassword" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    <?= $lockout_time > 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold py-3 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                            <?= $lockout_time > 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        <?= $lockout_time > 0 ? 'Terkunci' : 'Masuk' ?>
                    </button>
                </form>
                
                <!-- Back Link -->
                <div class="text-center mt-6">
                    <a href="../index.php" class="text-gray-500 hover:text-gray-700 text-sm transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Kembali ke halaman utama
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Demo Info -->
        <div class="mt-6 bg-white/90 backdrop-blur-sm rounded-lg p-6 shadow-lg">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                Akun Demo
            </h3>
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span class="font-medium">Admin:</span>
                    <span class="font-mono bg-gray-100 px-2 py-1 rounded">admin / password</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Guru:</span>
                    <span class="font-mono bg-gray-100 px-2 py-1 rounded">guru1 / password</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const icon = this.querySelector('i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }
        
        // Countdown timer for lockout
        <?php if ($lockout_time > 0): ?>
        let countdown = <?= $lockout_time ?>;
        const countdownElement = document.getElementById('countdown');
        
        function formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return minutes > 0 ? `${minutes}m ${remainingSeconds}s` : `${remainingSeconds}s`;
        }
        
        if (countdownElement) {
            const timer = setInterval(function() {
                countdown--;
                countdownElement.textContent = formatDuration(countdown);
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    location.reload();
                }
            }, 1000);
        }
        <?php endif; ?>
        
        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('email');
                 const password = document.getElementById('password');
                 
                 if (!username.value.trim() || !password.value.trim()) {
                     e.preventDefault();
                     alert('Mohon isi semua field yang diperlukan.');
                     return false;
                 }
            });
        }
        
        // Auto focus on username field
         const usernameField = document.getElementById('email');
         if (usernameField) {
             usernameField.focus();
         }
        
        // Auto-refresh CSRF token every 10 minutes
        setInterval(function() {
            fetch('login.php?action=refresh_csrf')
                .then(response => response.json())
                .then(data => {
                    if (data.csrf_token) {
                        const csrfInput = document.querySelector('input[name="csrf_token"]');
                        if (csrfInput) {
                            csrfInput.value = data.csrf_token;
                        }
                    }
                })
                .catch(error => console.log('CSRF refresh failed:', error));
        }, 600000); // 10 minutes
    </script>
</body>
</html>