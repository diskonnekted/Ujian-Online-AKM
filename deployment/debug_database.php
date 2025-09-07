<?php
// File debug untuk memeriksa koneksi database dan data di hosting
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    echo "<h2>Debug Database Connection</h2>";
    echo "<p style='color: green;'>✓ Koneksi database berhasil!</p>";
    
    // Cek tabel subjects
    echo "<h3>1. Cek Tabel Subjects:</h3>";
    $query = "SELECT * FROM subjects";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Jumlah subjects: " . count($subjects) . "</p>";
    if (!empty($subjects)) {
        echo "<ul>";
        foreach ($subjects as $subject) {
            echo "<li>ID: {$subject['id']}, Nama: {$subject['nama_subject']}</li>";
        }
        echo "</ul>";
    }
    
    // Cek tabel tests
    echo "<h3>2. Cek Tabel Tests:</h3>";
    $query = "SELECT * FROM tests";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Jumlah tests: " . count($tests) . "</p>";
    if (!empty($tests)) {
        echo "<ul>";
        foreach ($tests as $test) {
            echo "<li>ID: {$test['id']}, Nama: {$test['nama_tes']}, Subject ID: {$test['subject_id']}</li>";
        }
        echo "</ul>";
    }
    
    // Cek tabel questions
    echo "<h3>3. Cek Tabel Questions:</h3>";
    $query = "SELECT * FROM questions";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Jumlah questions: " . count($questions) . "</p>";
    if (!empty($questions)) {
        echo "<ul>";
        foreach ($questions as $question) {
            echo "<li>ID: {$question['id']}, Test ID: {$question['test_id']}, Nomor: {$question['nomor_soal']}, Pertanyaan: " . substr($question['pertanyaan'], 0, 50) . "...</li>";
        }
        echo "</ul>";
    }
    
    // Cek tabel users
    echo "<h3>4. Cek Tabel Users:</h3>";
    $query = "SELECT id, username, nama_lengkap FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Jumlah users: " . count($users) . "</p>";
    if (!empty($users)) {
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>ID: {$user['id']}, Username: {$user['username']}, Nama: {$user['nama_lengkap']}</li>";
        }
        echo "</ul>";
    }
    
    // Cek tabel test_sessions
    echo "<h3>5. Cek Tabel Test Sessions:</h3>";
    $query = "SELECT * FROM test_sessions";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Jumlah test sessions: " . count($sessions) . "</p>";
    if (!empty($sessions)) {
        echo "<ul>";
        foreach ($sessions as $session) {
            echo "<li>ID: {$session['id']}, User ID: {$session['user_id']}, Test ID: {$session['test_id']}, Status: {$session['status']}</li>";
        }
        echo "</ul>";
    }
    
    // Test query yang sama dengan test_page.php
    echo "<h3>6. Test Query dari test_page.php:</h3>";
    if (!empty($tests)) {
        $test_id = $tests[0]['id']; // Ambil test_id pertama
        echo "<p>Testing dengan test_id: {$test_id}</p>";
        
        $query = "SELECT * FROM questions WHERE test_id = :test_id ORDER BY nomor_soal";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();
        $test_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Soal ditemukan untuk test_id {$test_id}: " . count($test_questions) . "</p>";
        if (!empty($test_questions)) {
            echo "<ul>";
            foreach ($test_questions as $tq) {
                echo "<li>Nomor: {$tq['nomor_soal']}, Pertanyaan: " . substr($tq['pertanyaan'], 0, 100) . "...</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><strong>Informasi PHP:</strong></p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</p>";
echo "<p>PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "</p>";

echo "<hr>";
echo "<p><a href='dashboard.php'>Kembali ke Dashboard</a></p>";
?>