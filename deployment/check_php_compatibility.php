<?php
// File untuk mengecek kompatibilitas PHP dan debugging masalah versi
echo "<h2>PHP Compatibility Check</h2>";

// Informasi PHP
echo "<h3>Informasi PHP:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>PHP SAPI:</strong> " . php_sapi_name() . "</li>";
echo "<li><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "<li><strong>Operating System:</strong> " . PHP_OS . "</li>";
echo "</ul>";

// Cek ekstensi yang diperlukan
echo "<h3>Ekstensi PHP yang Diperlukan:</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring'];
echo "<ul>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    $color = extension_loaded($ext) ? 'green' : 'red';
    echo "<li style='color: {$color};'>{$status} {$ext}</li>";
}
echo "</ul>";

// Cek konfigurasi PHP yang penting
echo "<h3>Konfigurasi PHP:</h3>";
$php_configs = [
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'session.auto_start' => ini_get('session.auto_start'),
    'default_charset' => ini_get('default_charset')
];

echo "<ul>";
foreach ($php_configs as $config => $value) {
    echo "<li><strong>{$config}:</strong> {$value}</li>";
}
echo "</ul>";

// Test error handling
echo "<h3>Test Error Handling:</h3>";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo "<p style='color: green;'>✓ Error reporting enabled</p>";

// Test database connection dengan error handling yang lebih detail
echo "<h3>Test Database Connection (Detailed):</h3>";
try {
    require_once 'config/database.php';
    
    echo "<p>Mencoba koneksi database...</p>";
    $conn = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test query dengan error handling
    echo "<h4>Test Query Questions:</h4>";
    $query = "SELECT COUNT(*) as total FROM questions";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total questions: {$result['total']}</p>";
    
    // Test query yang sama dengan test_page.php
    echo "<h4>Test Query dari test_page.php:</h4>";
    $test_id = 1;
    $query = "SELECT * FROM questions WHERE test_id = :test_id ORDER BY nomor_soal";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':test_id', $test_id, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Questions found for test_id {$test_id}: " . count($questions) . "</p>";
    
    if (empty($questions)) {
        echo "<p style='color: red;'>⚠️ MASALAH: Tidak ada soal ditemukan!</p>";
        
        // Debug lebih lanjut
        echo "<h5>Debug Query:</h5>";
        $debug_query = "SELECT test_id, COUNT(*) as count FROM questions GROUP BY test_id";
        $debug_stmt = $conn->prepare($debug_query);
        $debug_stmt->execute();
        $debug_results = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Questions per test_id:</p>";
        echo "<ul>";
        foreach ($debug_results as $row) {
            echo "<li>Test ID: {$row['test_id']}, Count: {$row['count']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✓ Questions found successfully</p>";
        echo "<ul>";
        foreach (array_slice($questions, 0, 3) as $q) {
            echo "<li>ID: {$q['id']}, Nomor: {$q['nomor_soal']}, Pertanyaan: " . substr($q['pertanyaan'], 0, 50) . "...</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ General Error: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

// Test session functionality
echo "<h3>Test Session:</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

$_SESSION['test_var'] = 'test_value';
if (isset($_SESSION['test_var']) && $_SESSION['test_var'] === 'test_value') {
    echo "<p style='color: green;'>✓ Session working correctly</p>";
    unset($_SESSION['test_var']);
} else {
    echo "<p style='color: red;'>✗ Session not working</p>";
}

// Cek kompatibilitas syntax PHP
echo "<h3>Test PHP Syntax Compatibility:</h3>";
try {
    // Test null coalescing operator (PHP 7.0+)
    $test_var = null;
    $result = $test_var ?? 'default';
    echo "<p style='color: green;'>✓ Null coalescing operator (??) supported</p>";
    
    // Test spaceship operator (PHP 7.0+)
    $comparison = 1 <=> 2;
    echo "<p style='color: green;'>✓ Spaceship operator (<=>) supported</p>";
    
    // Test array destructuring (PHP 7.1+)
    [$a, $b] = [1, 2];
    echo "<p style='color: green;'>✓ Array destructuring supported</p>";
    
} catch (ParseError $e) {
    echo "<p style='color: red;'>✗ PHP Syntax Error: " . $e->getMessage() . "</p>";
    echo "<p>Your PHP version might be too old for this application</p>";
} catch (Error $e) {
    echo "<p style='color: orange;'>⚠️ PHP Feature Error: " . $e->getMessage() . "</p>";
}

// Rekomendasi
echo "<h3>Rekomendasi:</h3>";
$php_version = phpversion();
if (version_compare($php_version, '7.4.0', '<')) {
    echo "<p style='color: red;'>⚠️ PHP version Anda ({$php_version}) mungkin terlalu lama. Disarankan PHP 7.4 atau lebih baru.</p>";
} elseif (version_compare($php_version, '8.0.0', '<')) {
    echo "<p style='color: orange;'>⚠️ PHP version Anda ({$php_version}) masih didukung, tapi disarankan upgrade ke PHP 8.0+</p>";
} else {
    echo "<p style='color: green;'>✓ PHP version Anda ({$php_version}) sudah modern dan kompatibel</p>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Debug Database</a> | <a href='check_config.php'>Check Config</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>