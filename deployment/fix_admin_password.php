<?php
// Script untuk mengupdate password admin dari 'password' ke 'admin123'
require_once 'config/database.php';
require_once 'admin/auth_config.php';

echo "<h2>Fix Admin Password</h2>";
echo "<p>Script untuk mengupdate password admin dari 'password' ke 'admin123'</p>";

try {
    $pdo = getDBConnection();
    
    // Cek password admin saat ini
    echo "<h3>1. Cek Password Admin Saat Ini</h3>";
    $stmt = $pdo->prepare("SELECT username, password FROM teachers WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✅ Admin user ditemukan: <strong>{$user['username']}</strong></p>";
        echo "<p>Hash saat ini: <code>{$user['password']}</code></p>";
        
        // Test password lama
        echo "<h3>2. Test Password Lama</h3>";
        $test_passwords = ['password', 'admin123', 'admin'];
        
        foreach ($test_passwords as $test_pass) {
            $result = password_verify($test_pass, $user['password']);
            $status = $result ? '✅ MATCH' : '❌ NO MATCH';
            echo "<p>Testing '{$test_pass}': <strong>{$status}</strong></p>";
        }
        
        // Generate hash baru untuk 'admin123'
        echo "<h3>3. Generate Hash Baru</h3>";
        $new_password = 'admin123';
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        echo "<p>Password baru: <strong>{$new_password}</strong></p>";
        echo "<p>Hash baru: <code>{$new_hash}</code></p>";
        
        // Verify hash baru
        $verify_result = password_verify($new_password, $new_hash);
        echo "<p>Verifikasi hash baru: " . ($verify_result ? '✅ OK' : '❌ ERROR') . "</p>";
        
        if ($verify_result) {
            // Update password di database
            echo "<h3>4. Update Password di Database</h3>";
            $update_stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE username = 'admin'");
            $update_result = $update_stmt->execute([$new_hash]);
            
            if ($update_result) {
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>✅ PASSWORD BERHASIL DIUPDATE!</h4>";
                echo "<p><strong>Username:</strong> admin</p>";
                echo "<p><strong>Password baru:</strong> admin123</p>";
                echo "<p>Sekarang Anda bisa login dengan kredensial ini.</p>";
                echo "</div>";
                
                // Verify update berhasil
                echo "<h3>5. Verifikasi Update</h3>";
                $verify_stmt = $pdo->prepare("SELECT password FROM teachers WHERE username = 'admin'");
                $verify_stmt->execute();
                $updated_user = $verify_stmt->fetch();
                
                $final_test = password_verify('admin123', $updated_user['password']);
                echo "<p>Test login dengan 'admin123': " . ($final_test ? '✅ BERHASIL' : '❌ GAGAL') . "</p>";
                
                if ($final_test) {
                    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h4>🎉 SELESAI!</h4>";
                    echo "<p>Password admin telah berhasil diupdate.</p>";
                    echo "<p><a href='admin/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login Admin</a></p>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
                echo "<h4>❌ GAGAL UPDATE PASSWORD</h4>";
                echo "<p>Terjadi error saat mengupdate password di database.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "<h4>❌ ERROR HASH</h4>";
            echo "<p>Hash yang digenerate tidak valid.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h4>❌ ADMIN USER TIDAK DITEMUKAN</h4>";
        echo "<p>User admin tidak ditemukan di tabel teachers.</p>";
        echo "<p>Pastikan tabel teachers sudah dibuat dan data admin sudah diinsert.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4>❌ ERROR</h4>";
    echo "<p>Terjadi error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Debug Database</a> | <a href='check_config.php'>Check Config</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>