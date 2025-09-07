<?php
// File test sederhana untuk mengecek pengambilan soal
// Mensimulasikan proses yang sama dengan test_page.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Questions Simple</h2>";
echo "<p>File ini mensimulasikan proses pengambilan soal seperti di test_page.php</p>";

try {
    require_once 'config/database.php';
    
    echo "<h3>1. Test Database Connection</h3>";
    $conn = getDBConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    echo "<h3>2. Simulasi Session (tanpa login)</h3>";
    // Simulasi data session
    $fake_session = [
        'user_id' => 1,
        'test_id' => 1
    ];
    echo "<p>Simulated user_id: {$fake_session['user_id']}</p>";
    echo "<p>Simulated test_id: {$fake_session['test_id']}</p>";
    
    echo "<h3>3. Test Query Ambil Soal (Exact sama dengan test_page.php)</h3>";
    $query = "SELECT * FROM questions WHERE test_id = :test_id ORDER BY nomor_soal";
    echo "<p><strong>Query:</strong> {$query}</p>";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':test_id', $fake_session['test_id']);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Jumlah soal ditemukan:</strong> " . count($questions) . "</p>";
    
    if (empty($questions)) {
        echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'>";
        echo "<h4>❌ MASALAH: Tidak ada soal ditemukan!</h4>";
        
        // Debug query
        echo "<p><strong>Debug Info:</strong></p>";
        echo "<p>Test ID yang dicari: {$fake_session['test_id']}</p>";
        
        // Cek apakah ada soal di database
        $debug_query = "SELECT COUNT(*) as total FROM questions";
        $debug_stmt = $conn->prepare($debug_query);
        $debug_stmt->execute();
        $total_questions = $debug_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total soal di database: {$total_questions['total']}</p>";
        
        // Cek test_id yang ada
        $debug_query2 = "SELECT DISTINCT test_id FROM questions";
        $debug_stmt2 = $conn->prepare($debug_query2);
        $debug_stmt2->execute();
        $test_ids = $debug_stmt2->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Test IDs yang ada: " . implode(', ', $test_ids) . "</p>";
        
        echo "</div>";
    } else {
        echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb;'>";
        echo "<h4>✅ BERHASIL: Soal ditemukan!</h4>";
        echo "</div>";
        
        echo "<h4>4. Test Pengambilan Soal Pertama (seperti di test_page.php)</h4>";
        $current_question = 1; // Simulasi $_GET['q']
        $current_question = max(1, min($current_question, count($questions)));
        
        if (isset($questions[$current_question - 1])) {
            $question = $questions[$current_question - 1];
            
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0;'>";
            echo "<h5>Soal Nomor {$current_question}:</h5>";
            echo "<p><strong>ID:</strong> {$question['id']}</p>";
            echo "<p><strong>Test ID:</strong> {$question['test_id']}</p>";
            echo "<p><strong>Nomor Soal:</strong> {$question['nomor_soal']}</p>";
            echo "<p><strong>Pertanyaan:</strong> {$question['pertanyaan']}</p>";
            
            if (!empty($question['gambar'])) {
                echo "<p><strong>Gambar:</strong> {$question['gambar']}</p>";
                $image_path = "images/{$question['gambar']}";
                if (file_exists($image_path)) {
                    echo "<p style='color: green;'>✓ File gambar ditemukan: {$image_path}</p>";
                } else {
                    echo "<p style='color: red;'>✗ File gambar tidak ditemukan: {$image_path}</p>";
                }
            }
            
            echo "<p><strong>Pilihan A:</strong> {$question['pilihan_a']}</p>";
            echo "<p><strong>Pilihan B:</strong> {$question['pilihan_b']}</p>";
            echo "<p><strong>Pilihan C:</strong> {$question['pilihan_c']}</p>";
            echo "<p><strong>Pilihan D:</strong> {$question['pilihan_d']}</p>";
            echo "<p><strong>Jawaban Benar:</strong> {$question['jawaban_benar']}</p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>✗ Error: Tidak bisa mengambil soal dengan index " . ($current_question - 1) . "</p>";
        }
        
        echo "<h4>5. Test Semua Soal</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>No</th><th>ID</th><th>Test ID</th><th>Pertanyaan (50 char)</th><th>Gambar</th></tr>";
        foreach ($questions as $i => $q) {
            $pertanyaan_short = substr($q['pertanyaan'], 0, 50) . '...';
            $gambar_status = !empty($q['gambar']) ? $q['gambar'] : 'Tidak ada';
            echo "<tr>";
            echo "<td>" . ($i + 1) . "</td>";
            echo "<td>{$q['id']}</td>";
            echo "<td>{$q['test_id']}</td>";
            echo "<td>{$pertanyaan_short}</td>";
            echo "<td>{$gambar_status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>6. Test Variabel dan Tipe Data</h3>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
    echo "<p>Count function result type: " . gettype(count($questions)) . "</p>";
    echo "<p>Questions array type: " . gettype($questions) . "</p>";
    echo "<p>Empty check: " . (empty($questions) ? 'true' : 'false') . "</p>";
    echo "<p>Is array: " . (is_array($questions) ? 'true' : 'false') . "</p>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24;'>";
    echo "<h4>❌ Database Error:</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24;'>";
    echo "<h4>❌ General Error:</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Kesimpulan:</h3>";
echo "<p>Jika file ini menampilkan soal dengan benar, maka masalah bukan di PHP atau database.</p>";
echo "<p>Masalah mungkin di:</p>";
echo "<ul>";
echo "<li>Session management di test_page.php</li>";
echo "<li>Token validation</li>";
echo "<li>User authentication</li>";
echo "<li>JavaScript atau frontend issues</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='check_php_compatibility.php'>Check PHP Compatibility</a> | <a href='debug_database.php'>Debug Database</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>