<?php
// Quick Fix untuk masalah soal tidak muncul
// HANYA JALANKAN JIKA ADA MASALAH!

require_once 'config/database.php';

// Cek parameter action
$action = isset($_GET['action']) ? $_GET['action'] : '';

echo "<h2>Quick Fix - Soal Tidak Muncul</h2>";
echo "<p style='color: red;'><strong>PERINGATAN:</strong> Hanya jalankan jika ada masalah dengan database!</p>";

if (empty($action)) {
    echo "<h3>Pilih Action:</h3>";
    echo "<ul>";
    echo "<li><a href='?action=check'>1. Check Database Status</a></li>";
    echo "<li><a href='?action=recreate_questions'>2. Re-create Tabel Questions</a></li>";
    echo "<li><a href='?action=insert_sample_data'>3. Insert Sample Data</a></li>";
    echo "<li><a href='?action=fix_foreign_keys'>4. Fix Foreign Keys</a></li>";
    echo "<li><a href='?action=reset_all'>5. Reset All Data (HATI-HATI!)</a></li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p><a href='check_config.php'>Check Config</a> | <a href='debug_database.php'>Debug Database</a></p>";
    exit();
}

try {
    $conn = getDBConnection();
    
    switch ($action) {
        case 'check':
            echo "<h3>Database Status Check</h3>";
            
            // Cek setiap tabel
            $tables = ['subjects', 'tests', 'questions', 'users', 'admins'];
            foreach ($tables as $table) {
                try {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM {$table}");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p>✓ Tabel {$table}: {$result['count']} records</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Tabel {$table}: " . $e->getMessage() . "</p>";
                }
            }
            
            // Cek relasi
            try {
                $stmt = $conn->query("SELECT q.id, q.test_id, t.nama_tes FROM questions q LEFT JOIN tests t ON q.test_id = t.id LIMIT 5");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<h4>Sample Questions with Tests:</h4>";
                foreach ($results as $row) {
                    echo "<p>Question ID: {$row['id']}, Test ID: {$row['test_id']}, Test Name: {$row['nama_tes']}</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error checking relations: " . $e->getMessage() . "</p>";
            }
            break;
            
        case 'recreate_questions':
            echo "<h3>Re-creating Questions Table</h3>";
            
            // Backup existing data
            try {
                $stmt = $conn->query("SELECT * FROM questions");
                $backup_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p>Backup " . count($backup_data) . " existing questions...</p>";
            } catch (Exception $e) {
                $backup_data = [];
                echo "<p>No existing questions to backup.</p>";
            }
            
            // Drop and recreate table
            $conn->exec("DROP TABLE IF EXISTS questions");
            
            $create_sql = "
            CREATE TABLE questions (
                id int(11) NOT NULL AUTO_INCREMENT,
                test_id int(11) NOT NULL,
                nomor_soal int(11) NOT NULL,
                pertanyaan text NOT NULL,
                gambar varchar(255) DEFAULT NULL,
                pilihan_a text,
                pilihan_b text,
                pilihan_c text,
                pilihan_d text,
                jawaban_benar varchar(10) DEFAULT NULL,
                tipe_soal enum('pilihan_ganda','benar_salah','pilihan_ganda_kompleks','isian_singkat','drag_drop','urutan','essay') DEFAULT 'pilihan_ganda',
                jawaban_kompleks text DEFAULT NULL,
                created_by int(11) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY test_id (test_id),
                CONSTRAINT questions_ibfk_1 FOREIGN KEY (test_id) REFERENCES tests (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";
            
            $conn->exec($create_sql);
            echo "<p>✓ Questions table recreated successfully!</p>";
            
            // Restore backup data if any
            if (!empty($backup_data)) {
                foreach ($backup_data as $question) {
                    $insert_sql = "INSERT INTO questions (test_id, nomor_soal, pertanyaan, gambar, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, tipe_soal, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->execute([
                        $question['test_id'],
                        $question['nomor_soal'],
                        $question['pertanyaan'],
                        $question['gambar'],
                        $question['pilihan_a'],
                        $question['pilihan_b'],
                        $question['pilihan_c'],
                        $question['pilihan_d'],
                        $question['jawaban_benar'],
                        $question['tipe_soal'] ?? 'pilihan_ganda',
                        $question['created_by']
                    ]);
                }
                echo "<p>✓ Restored " . count($backup_data) . " questions!</p>";
            }
            break;
            
        case 'insert_sample_data':
            echo "<h3>Inserting Sample Data</h3>";
            
            // Insert sample questions
            $sample_questions = [
                [1, 1, 'Nenek menggunakan tetes-tetes cairan coklat kental pada secarik kain putih. Dari mana tetes-tetes cairan itu berasal?', 'nenek_ani.jpg', 'Dari teh yang diminum nenek', 'Dari obat yang diteteskan nenek', 'Dari cairan pembersih', 'Dari tinta yang tumpah', 'B'],
                [1, 2, 'Berdasarkan teks, apa yang paling mungkin terjadi setelah nenek meneteskan cairan coklat pada kain?', NULL, 'Kain menjadi bersih dan putih kembali', 'Kain berubah warna menjadi coklat', 'Kain robek karena cairan', 'Kain menjadi lebih tebal', 'B'],
                [1, 3, 'Mengapa nenek menggunakan kain putih untuk aktivitas tersebut?', NULL, 'Karena kain putih lebih murah', 'Karena kain putih mudah dibersihkan', 'Karena perubahan warna lebih terlihat jelas', 'Karena kain putih lebih kuat', 'C']
            ];
            
            foreach ($sample_questions as $q) {
                try {
                    $stmt = $conn->prepare("INSERT INTO questions (test_id, nomor_soal, pertanyaan, gambar, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute($q);
                    echo "<p>✓ Inserted question {$q[1]}</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Error inserting question {$q[1]}: " . $e->getMessage() . "</p>";
                }
            }
            break;
            
        case 'fix_foreign_keys':
            echo "<h3>Fixing Foreign Keys</h3>";
            
            // Check and fix foreign key issues
            try {
                // Disable foreign key checks
                $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Remove invalid questions (questions without valid test_id)
                $stmt = $conn->exec("DELETE q FROM questions q LEFT JOIN tests t ON q.test_id = t.id WHERE t.id IS NULL");
                echo "<p>Removed {$stmt} invalid questions</p>";
                
                // Re-enable foreign key checks
                $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo "<p>✓ Foreign keys fixed!</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error fixing foreign keys: " . $e->getMessage() . "</p>";
            }
            break;
            
        case 'reset_all':
            echo "<h3>RESET ALL DATA - HATI-HATI!</h3>";
            
            if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
                echo "<p style='color: red;'>Apakah Anda yakin ingin reset semua data?</p>";
                echo "<p><a href='?action=reset_all&confirm=yes' style='color: red; font-weight: bold;'>YA, RESET SEMUA DATA</a></p>";
                echo "<p><a href='quick_fix.php'>Batal</a></p>";
                break;
            }
            
            // Reset all data
            try {
                $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
                $conn->exec("TRUNCATE TABLE user_answers");
                $conn->exec("TRUNCATE TABLE test_sessions");
                $conn->exec("TRUNCATE TABLE questions");
                $conn->exec("TRUNCATE TABLE tests");
                $conn->exec("TRUNCATE TABLE subjects");
                $conn->exec("TRUNCATE TABLE users");
                $conn->exec("TRUNCATE TABLE admins");
                $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo "<p style='color: red;'>✓ All data reset!</p>";
                echo "<p>Sekarang import ulang file orb44008_akm.sql melalui phpMyAdmin</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error resetting data: " . $e->getMessage() . "</p>";
            }
            break;
            
        default:
            echo "<p>Invalid action!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='quick_fix.php'>Back to Menu</a> | <a href='check_config.php'>Check Config</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>