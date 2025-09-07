<?php
// Script untuk debug masalah login admin
require_once 'config/database.php';
require_once 'admin/auth_config.php';

echo "<h2>Debug Login Admin</h2>";
echo "<p>Script untuk mengecek masalah login admin yang gagal</p>";

try {
    $pdo = getDBConnection();
    
    // 1. Cek apakah tabel teachers ada
    echo "<h3>1. Cek Tabel Teachers</h3>";
    $tables_stmt = $pdo->query("SHOW TABLES LIKE 'teachers'");
    $table_exists = $tables_stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p>✅ Tabel 'teachers' ditemukan</p>";
        
        // 2. Cek struktur tabel
        echo "<h3>2. Struktur Tabel Teachers</h3>";
        $structure_stmt = $pdo->query("DESCRIBE teachers");
        $columns = $structure_stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th style='background: #f8f9fa; padding: 8px;'>Field</th><th style='background: #f8f9fa; padding: 8px;'>Type</th><th style='background: #f8f9fa; padding: 8px;'>Null</th><th style='background: #f8f9fa; padding: 8px;'>Key</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$column['Field']}</td>";
            echo "<td style='padding: 8px;'>{$column['Type']}</td>";
            echo "<td style='padding: 8px;'>{$column['Null']}</td>";
            echo "<td style='padding: 8px;'>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 3. Cek data admin
        echo "<h3>3. Data Admin di Tabel Teachers</h3>";
        $admin_stmt = $pdo->query("SELECT * FROM teachers");
        $admins = $admin_stmt->fetchAll();
        
        if (count($admins) > 0) {
            echo "<p>✅ Ditemukan " . count($admins) . " record di tabel teachers</p>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th style='background: #f8f9fa; padding: 8px;'>ID</th><th style='background: #f8f9fa; padding: 8px;'>Username</th><th style='background: #f8f9fa; padding: 8px;'>Nama</th><th style='background: #f8f9fa; padding: 8px;'>Role</th><th style='background: #f8f9fa; padding: 8px;'>Status</th><th style='background: #f8f9fa; padding: 8px;'>Password Hash</th></tr>";
            
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td style='padding: 8px;'>{$admin['id']}</td>";
                echo "<td style='padding: 8px;'><strong>{$admin['username']}</strong></td>";
                echo "<td style='padding: 8px;'>{$admin['nama_lengkap']}</td>";
                echo "<td style='padding: 8px;'>{$admin['role']}</td>";
                echo "<td style='padding: 8px;'>" . ($admin['status'] ?? 'active') . "</td>";
                echo "<td style='padding: 8px; font-size: 10px; word-break: break-all;'>" . substr($admin['password'], 0, 50) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 4. Test password untuk user admin
            echo "<h3>4. Test Password untuk User Admin</h3>";
            $admin_user = null;
            foreach ($admins as $admin) {
                if ($admin['username'] === 'admin') {
                    $admin_user = $admin;
                    break;
                }
            }
            
            if ($admin_user) {
                echo "<p>✅ User 'admin' ditemukan</p>";
                echo "<p><strong>Status:</strong> " . ($admin_user['status'] ?? 'active') . "</p>";
                echo "<p><strong>Role:</strong> {$admin_user['role']}</p>";
                
                // Test berbagai password
                $test_passwords = ['password', 'admin123', 'admin', '123456', 'Password123', 'admin@123'];
                
                echo "<h4>Test Password:</h4>";
                $found_password = false;
                
                foreach ($test_passwords as $test_pass) {
                    $result = password_verify($test_pass, $admin_user['password']);
                    $status = $result ? '✅ MATCH' : '❌ NO MATCH';
                    $color = $result ? 'green' : 'red';
                    echo "<p style='color: {$color};'>Testing '{$test_pass}': <strong>{$status}</strong></p>";
                    
                    if ($result) {
                        $found_password = $test_pass;
                    }
                }
                
                if ($found_password) {
                    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h4>✅ PASSWORD DITEMUKAN!</h4>";
                    echo "<p><strong>Username:</strong> admin</p>";
                    echo "<p><strong>Password:</strong> {$found_password}</p>";
                    echo "<p>Gunakan kredensial ini untuk login.</p>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h4>❌ TIDAK ADA PASSWORD YANG COCOK</h4>";
                    echo "<p>Semua password yang ditest tidak cocok dengan hash yang tersimpan.</p>";
                    echo "<p><strong>Solusi:</strong></p>";
                    echo "<ol>";
                    echo "<li>Jalankan <a href='fix_admin_password.php'>fix_admin_password.php</a> untuk reset password</li>";
                    echo "<li>Atau import ulang database dengan data admin yang benar</li>";
                    echo "</ol>";
                    echo "</div>";
                }
                
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
                echo "<h4>❌ USER ADMIN TIDAK DITEMUKAN</h4>";
                echo "<p>Tidak ada user dengan username 'admin' di tabel teachers.</p>";
                echo "<p><strong>Solusi:</strong> Import file admin_dashboard_structure.sql untuk membuat data admin default.</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "<h4>❌ TABEL TEACHERS KOSONG</h4>";
            echo "<p>Tabel teachers ada tapi tidak berisi data.</p>";
            echo "<p><strong>Solusi:</strong> Import file admin_dashboard_structure.sql untuk membuat data admin default.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h4>❌ TABEL TEACHERS TIDAK ADA</h4>";
        echo "<p>Tabel 'teachers' tidak ditemukan di database.</p>";
        echo "<p><strong>Solusi:</strong> Import file admin_dashboard_structure.sql untuk membuat tabel dan data admin.</p>";
        echo "</div>";
    }
    
    // 5. Cek tabel admins (backup)
    echo "<h3>5. Cek Tabel Admins (Backup)</h3>";
    $admins_table_stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    $admins_table_exists = $admins_table_stmt->rowCount() > 0;
    
    if ($admins_table_exists) {
        echo "<p>✅ Tabel 'admins' juga ditemukan</p>";
        
        $admins_data_stmt = $pdo->query("SELECT * FROM admins");
        $admins_data = $admins_data_stmt->fetchAll();
        
        if (count($admins_data) > 0) {
            echo "<p>Ditemukan " . count($admins_data) . " record di tabel admins:</p>";
            
            foreach ($admins_data as $admin) {
                echo "<p>- Username: <strong>{$admin['username']}</strong>, Role: {$admin['role']}</p>";
            }
        } else {
            echo "<p>Tabel admins kosong</p>";
        }
    } else {
        echo "<p>ℹ️ Tabel 'admins' tidak ada (normal, menggunakan tabel teachers)</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4>❌ ERROR DATABASE</h4>";
    echo "<p>Terjadi error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Pastikan database sudah diimport dengan benar.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='fix_admin_password.php'>Fix Admin Password</a> | <a href='debug_database.php'>Debug Database</a> | <a href='admin/login.php'>Login Admin</a></p>";
?>