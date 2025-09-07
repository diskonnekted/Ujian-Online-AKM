<?php
// File untuk mengecek versi PHP dan kompatibilitas
echo "<h2>PHP Version & Compatibility Check</h2>";
echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7;'>";
echo "<strong>Tujuan:</strong> Mengecek apakah versi PHP hosting berbeda dengan localhost dan menyebabkan masalah soal tidak muncul.";
echo "</p>";

// Informasi dasar PHP
echo "<h3>📋 Informasi PHP Server</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='background: #f8f9fa; padding: 8px;'>Property</th><th style='background: #f8f9fa; padding: 8px;'>Value</th></tr>";

$php_info = [
    'PHP Version' => phpversion(),
    'PHP Major Version' => PHP_MAJOR_VERSION,
    'PHP Minor Version' => PHP_MINOR_VERSION,
    'PHP Release Version' => PHP_RELEASE_VERSION,
    'PHP SAPI' => php_sapi_name(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Operating System' => PHP_OS,
    'Architecture' => php_uname('m'),
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
];

foreach ($php_info as $key => $value) {
    echo "<tr><td style='padding: 8px;'><strong>{$key}</strong></td><td style='padding: 8px;'>{$value}</td></tr>";
}
echo "</table>";

// Cek kompatibilitas versi
echo "<h3>🔍 Analisis Kompatibilitas</h3>";
$current_version = phpversion();
$min_required = '7.4.0';
$recommended = '8.0.0';

echo "<div style='padding: 15px; margin: 10px 0;'>";
if (version_compare($current_version, $min_required, '<')) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4>❌ MASALAH VERSI PHP</h4>";
    echo "<p><strong>Versi saat ini:</strong> {$current_version}</p>";
    echo "<p><strong>Minimum required:</strong> {$min_required}</p>";
    echo "<p><strong>Status:</strong> Versi PHP terlalu lama!</p>";
    echo "<p><strong>Dampak:</strong> Aplikasi mungkin tidak berfungsi dengan benar.</p>";
    echo "</div>";
} elseif (version_compare($current_version, $recommended, '<')) {
    echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
    echo "<h4>⚠️ PERINGATAN VERSI PHP</h4>";
    echo "<p><strong>Versi saat ini:</strong> {$current_version}</p>";
    echo "<p><strong>Recommended:</strong> {$recommended}+</p>";
    echo "<p><strong>Status:</strong> Masih didukung, tapi disarankan upgrade.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4>✅ VERSI PHP KOMPATIBEL</h4>";
    echo "<p><strong>Versi saat ini:</strong> {$current_version}</p>";
    echo "<p><strong>Status:</strong> Versi PHP sudah modern dan kompatibel.</p>";
    echo "</div>";
}
echo "</div>";

// Test fitur PHP yang digunakan aplikasi
echo "<h3>🧪 Test Fitur PHP yang Digunakan</h3>";
$features = [];

// Test PDO
try {
    if (class_exists('PDO')) {
        $features['PDO'] = ['status' => 'OK', 'message' => 'PDO class available'];
    } else {
        $features['PDO'] = ['status' => 'ERROR', 'message' => 'PDO class not found'];
    }
} catch (Exception $e) {
    $features['PDO'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// Test PDO MySQL
try {
    if (extension_loaded('pdo_mysql')) {
        $features['PDO MySQL'] = ['status' => 'OK', 'message' => 'PDO MySQL extension loaded'];
    } else {
        $features['PDO MySQL'] = ['status' => 'ERROR', 'message' => 'PDO MySQL extension not loaded'];
    }
} catch (Exception $e) {
    $features['PDO MySQL'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// Test Session
try {
    if (function_exists('session_start')) {
        $features['Session'] = ['status' => 'OK', 'message' => 'Session functions available'];
    } else {
        $features['Session'] = ['status' => 'ERROR', 'message' => 'Session functions not available'];
    }
} catch (Exception $e) {
    $features['Session'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// Test JSON
try {
    if (function_exists('json_encode') && function_exists('json_decode')) {
        $features['JSON'] = ['status' => 'OK', 'message' => 'JSON functions available'];
    } else {
        $features['JSON'] = ['status' => 'ERROR', 'message' => 'JSON functions not available'];
    }
} catch (Exception $e) {
    $features['JSON'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// Test Array functions
try {
    $test_array = [1, 2, 3];
    $count_result = count($test_array);
    $empty_result = empty([]);
    if ($count_result === 3 && $empty_result === true) {
        $features['Array Functions'] = ['status' => 'OK', 'message' => 'Array functions working correctly'];
    } else {
        $features['Array Functions'] = ['status' => 'ERROR', 'message' => 'Array functions not working correctly'];
    }
} catch (Exception $e) {
    $features['Array Functions'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// Tampilkan hasil test
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='background: #f8f9fa; padding: 8px;'>Feature</th><th style='background: #f8f9fa; padding: 8px;'>Status</th><th style='background: #f8f9fa; padding: 8px;'>Message</th></tr>";

foreach ($features as $feature => $result) {
    $color = $result['status'] === 'OK' ? 'green' : 'red';
    $icon = $result['status'] === 'OK' ? '✅' : '❌';
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>{$feature}</strong></td>";
    echo "<td style='padding: 8px; color: {$color};'>{$icon} {$result['status']}</td>";
    echo "<td style='padding: 8px;'>{$result['message']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test syntax PHP modern
echo "<h3>🔧 Test Syntax PHP Modern</h3>";
$syntax_tests = [];

try {
    // Null coalescing operator (PHP 7.0+)
    $test = null ?? 'default';
    $syntax_tests['Null Coalescing (??)'] = 'OK - PHP 7.0+ syntax supported';
} catch (ParseError $e) {
    $syntax_tests['Null Coalescing (??)'] = 'ERROR - ' . $e->getMessage();
}

try {
    // Arrow functions (PHP 7.4+)
    if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
        $syntax_tests['Arrow Functions'] = 'OK - PHP 7.4+ syntax supported';
    } else {
        $syntax_tests['Arrow Functions'] = 'SKIP - PHP version < 7.4';
    }
} catch (ParseError $e) {
    $syntax_tests['Arrow Functions'] = 'ERROR - ' . $e->getMessage();
}

echo "<ul>";
foreach ($syntax_tests as $test => $result) {
    $color = strpos($result, 'OK') === 0 ? 'green' : (strpos($result, 'ERROR') === 0 ? 'red' : 'orange');
    echo "<li style='color: {$color};'><strong>{$test}:</strong> {$result}</li>";
}
echo "</ul>";

// Rekomendasi berdasarkan hasil
echo "<h3>💡 Rekomendasi & Solusi</h3>";

if (version_compare($current_version, '7.4.0', '<')) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🚨 URGENT: Upgrade PHP Required</h4>";
    echo "<p><strong>Masalah:</strong> Versi PHP Anda ({$current_version}) terlalu lama.</p>";
    echo "<p><strong>Solusi:</strong></p>";
    echo "<ol>";
    echo "<li>Hubungi provider hosting untuk upgrade PHP ke versi 7.4 atau 8.0+</li>";
    echo "<li>Atau pindah ke hosting yang mendukung PHP modern</li>";
    echo "<li>Jika menggunakan shared hosting, cek panel kontrol untuk opsi PHP version</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✅ PHP Version OK</h4>";
    echo "<p>Versi PHP Anda kompatibel. Masalah soal tidak muncul kemungkinan bukan karena versi PHP.</p>";
    echo "<p><strong>Langkah selanjutnya:</strong></p>";
    echo "<ol>";
    echo "<li>Cek <a href='test_questions_simple.php'>test_questions_simple.php</a> untuk test pengambilan soal</li>";
    echo "<li>Cek <a href='check_php_compatibility.php'>check_php_compatibility.php</a> untuk test kompatibilitas lengkap</li>";
    echo "<li>Periksa error log hosting untuk error yang tidak terlihat</li>";
    echo "<li>Cek apakah ada perbedaan konfigurasi antara localhost dan hosting</li>";
    echo "</ol>";
    echo "</div>";
}

// Perbandingan dengan localhost
echo "<h3>🏠 Perbandingan dengan Localhost</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Catatan:</strong> Untuk membandingkan dengan localhost, jalankan file ini di localhost juga.</p>";
echo "<p><strong>Localhost biasanya menggunakan:</strong></p>";
echo "<ul>";
echo "<li>XAMPP: PHP 7.4.x atau 8.0.x</li>";
echo "<li>WAMP: PHP 7.4.x atau 8.0.x</li>";
echo "<li>MAMP: PHP 7.4.x atau 8.0.x</li>";
echo "</ul>";
echo "<p><strong>Hosting saat ini:</strong> PHP {$current_version}</p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='test_questions_simple.php'>Test Questions Simple</a> | <a href='check_php_compatibility.php'>Check PHP Compatibility</a> | <a href='debug_database.php'>Debug Database</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>