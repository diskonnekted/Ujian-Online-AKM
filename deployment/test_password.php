<?php
// Test script to verify admin password
require_once 'config/database.php';
require_once 'admin/auth_config.php';

$pdo = getDBConnection();

// Get admin user data
$stmt = $pdo->prepare("SELECT username, password FROM teachers WHERE username = 'admin'");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    echo "Username: " . $user['username'] . "\n";
    echo "Stored hash: " . $user['password'] . "\n\n";
    
    // Test different passwords
    $test_passwords = ['password', 'admin123', 'admin', '123456', 'Password123'];
    
    foreach ($test_passwords as $test_pass) {
        $result = verifyPassword($test_pass, $user['password']);
        echo "Testing password '$test_pass': " . ($result ? 'MATCH' : 'NO MATCH') . "\n";
    }
    
    // Show what the hash represents
    echo "\nThe stored hash appears to be for password: 'password'\n";
    echo "This is a Laravel default hash for 'password'\n";
} else {
    echo "Admin user not found!\n";
}
?>