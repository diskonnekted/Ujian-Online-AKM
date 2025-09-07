<?php
// File untuk memeriksa konfigurasi database
echo "<h2>Database Configuration Check</h2>";

// Tampilkan konfigurasi database (tanpa password untuk keamanan)
require_once 'config/database.php';

echo "<h3>Konfigurasi Database:</h3>";
echo "<ul>";
echo "<li>Host: localhost</li>";
echo "<li>Database: orb44008_akm</li>";
echo "<li>Username: orb44008_akm</li>";
echo "<li>Password: [HIDDEN]</li>";
echo "</ul>";

// Test koneksi manual
echo "<h3>Test Koneksi Manual:</h3>";
try {
    $host = 'localhost';
    $db_name = 'orb44008_akm';
    $username = 'orb44008_akm';
    $password = 'Dirumah@5474';
    
    $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Koneksi manual berhasil!</p>";
    
    // Test query sederhana
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Database aktif: " . $result['current_db'] . "</p>";
    
    // Cek tabel yang ada
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tabel yang ada (" . count($tables) . "):</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Koneksi manual gagal: " . $e->getMessage() . "</p>";
}

// Test menggunakan fungsi getDBConnection()
echo "<h3>Test Fungsi getDBConnection():</h3>";
try {
    $conn = getDBConnection();
    echo "<p style='color: green;'>✓ Fungsi getDBConnection() berhasil!</p>";
    
    // Test query count untuk setiap tabel
    $tables_to_check = ['subjects', 'tests', 'questions', 'users', 'admins', 'test_sessions', 'user_answers'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Tabel {$table}: {$result['count']} records</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>Tabel {$table}: Error - " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fungsi getDBConnection() gagal: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Server Info:</strong></p>";
echo "<p>Server: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

echo "<hr>";
echo "<p><a href='debug_database.php'>Debug Database</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>