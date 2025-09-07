<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();

// Ambil data sesi tes
$query = "SELECT ts.*, t.nama_tes, t.durasi_menit, s.nama_subject 
          FROM test_sessions ts 
          JOIN tests t ON ts.test_id = t.id 
          JOIN subjects s ON t.subject_id = s.id 
          WHERE ts.token = :token AND ts.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo "<script>alert('Sesi tes tidak valid!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Update status sesi menjadi completed
if ($session['status'] == 'ongoing') {
    $query = "UPDATE test_sessions SET status = 'completed', waktu_selesai = CURRENT_TIMESTAMP WHERE id = :session_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':session_id', $session['id']);
    $stmt->execute();
}

// Hitung skor
$query = "SELECT COUNT(*) as total_questions FROM questions WHERE test_id = :test_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':test_id', $session['test_id']);
$stmt->execute();
$total_questions = $stmt->fetch(PDO::FETCH_ASSOC)['total_questions'];

$query = "SELECT COUNT(*) as answered FROM user_answers WHERE session_id = :session_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':session_id', $session['id']);
$stmt->execute();
$answered_questions = $stmt->fetch(PDO::FETCH_ASSOC)['answered'];

// Hitung jawaban benar dengan logika berbeda untuk setiap tipe soal
$query = "SELECT ua.*, q.tipe_soal, q.jawaban_benar, q.jawaban_benar_kompleks, q.jawaban_benar_text, q.bobot, q.is_case_sensitive
          FROM user_answers ua 
          JOIN questions q ON ua.question_id = q.id 
          WHERE ua.session_id = :session_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':session_id', $session['id']);
$stmt->execute();
$user_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_score = 0;
$max_possible_score = 0;
$correct_answers = 0;

foreach ($user_answers as $answer) {
    $is_correct = false;
    $question_score = $answer['bobot'] ?: 10; // Default bobot 10 jika null
    $max_possible_score += $question_score;
    
    switch ($answer['tipe_soal']) {
        case 'pilihan_ganda':
        case 'benar_salah':
            if ($answer['jawaban'] == $answer['jawaban_benar']) {
                $is_correct = true;
                $total_score += $question_score;
            }
            break;
            
        case 'pilihan_ganda_kompleks':
            $user_answers_array = json_decode($answer['jawaban_kompleks'], true) ?: [];
            $correct_answers_array = json_decode($answer['jawaban_benar_kompleks'], true) ?: [];
            
            // Hitung partial scoring untuk pilihan ganda kompleks
            if (!empty($user_answers_array) && !empty($correct_answers_array)) {
                $correct_selected = array_intersect($user_answers_array, $correct_answers_array);
                $incorrect_selected = array_diff($user_answers_array, $correct_answers_array);
                $missed_correct = array_diff($correct_answers_array, $user_answers_array);
                
                $partial_score = (count($correct_selected) - count($incorrect_selected)) / count($correct_answers_array);
                $partial_score = max(0, $partial_score); // Tidak boleh negatif
                
                if ($partial_score > 0) {
                    $total_score += $question_score * $partial_score;
                    if ($partial_score == 1) $is_correct = true;
                }
            }
            break;
            
        case 'isian_singkat':
            $user_answer = trim($answer['jawaban']);
            $correct_answer = trim($answer['jawaban_benar_text'] ?: $answer['jawaban_benar']);
            
            if ($answer['is_case_sensitive']) {
                $is_correct = ($user_answer === $correct_answer);
            } else {
                $is_correct = (strtolower($user_answer) === strtolower($correct_answer));
            }
            
            if ($is_correct) {
                $total_score += $question_score;
            }
            break;
            
        case 'drag_drop':
            $user_answer = trim($answer['jawaban_kompleks'] ?: $answer['jawaban']);
            $correct_answer = trim($answer['jawaban_benar']);
            
            if (strtolower($user_answer) === strtolower($correct_answer)) {
                $is_correct = true;
                $total_score += $question_score;
            }
            break;
            
        case 'urutan':
            $user_sequence = json_decode($answer['jawaban_kompleks'], true) ?: [];
            $correct_sequence = json_decode($answer['jawaban_benar_kompleks'], true) ?: [];
            
            if ($user_sequence === $correct_sequence) {
                $is_correct = true;
                $total_score += $question_score;
            } else {
                // Partial scoring untuk urutan yang hampir benar
                $correct_positions = 0;
                $min_length = min(count($user_sequence), count($correct_sequence));
                
                for ($i = 0; $i < $min_length; $i++) {
                    if (isset($user_sequence[$i]) && isset($correct_sequence[$i]) && 
                        $user_sequence[$i] === $correct_sequence[$i]) {
                        $correct_positions++;
                    }
                }
                
                if ($correct_positions > 0 && count($correct_sequence) > 0) {
                    $partial_score = $correct_positions / count($correct_sequence);
                    $total_score += $question_score * $partial_score;
                }
            }
            break;
            
        case 'essay':
            // Essay tidak bisa dinilai otomatis, perlu penilaian manual
            // Untuk sementara, berikan skor 0 dan tandai sebagai perlu review
            // Skor akan diupdate manual oleh admin/guru
            $total_score += 0; // Skor 0 sampai dinilai manual
            $is_correct = false; // Tidak dihitung sebagai benar/salah
            break;
    }
    
    if ($is_correct) {
        $correct_answers++;
    }
}

$score = ($max_possible_score > 0) ? round(($total_score / $max_possible_score) * 100, 2) : 0;

// Update skor di database
$query = "UPDATE test_sessions SET total_skor = :skor WHERE id = :session_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':skor', $score);
$stmt->bindParam(':session_id', $session['id']);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tes Selesai - CLASNET ACADEMY AKM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #4a5568;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-text {
            font-size: 18px;
            font-weight: bold;
        }
        
        .user-info {
            font-size: 14px;
            color: #e2e8f0;
        }
        
        .finish-container {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            margin-top: 80px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            color: #48bb78;
            margin-bottom: 20px;
        }
        
        .finish-title {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .finish-subtitle {
            color: #4a5568;
            margin-bottom: 40px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .result-summary {
            background: #f7fafc;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .result-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .result-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
        }
        
        .result-label {
            color: #4a5568;
            font-weight: 500;
        }
        
        .result-value {
            color: #2d3748;
            font-weight: 600;
        }
        
        .score-highlight {
            color: #667eea;
            font-size: 24px;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .logout-btn {
            position: absolute;
            top: 15px;
            right: 30px;
            background: #e53e3e;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .logout-btn:hover {
            background: #c53030;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#4a5568" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="#4a5568" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="#4a5568" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <div class="header-text">CLASNET ACADEMY</div>
                <div style="font-size: 12px; opacity: 0.8;">APLIKASI AKM</div>
            </div>
        </div>
        <div class="user-info">
            <?php echo htmlspecialchars($_SESSION['username']); ?> - <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="finish-container">
        <div class="success-icon">✅</div>
        
        <h1 class="finish-title">Tes Telah Selesai!</h1>
        
        <p class="finish-subtitle">
            Terima kasih telah mengikuti tes <?php echo htmlspecialchars($session['nama_subject']); ?>. 
            Berikut adalah ringkasan hasil tes Anda.
        </p>
        
        <div class="result-summary">
            <div class="result-row">
                <span class="result-label">Mata Pelajaran:</span>
                <span class="result-value"><?php echo htmlspecialchars($session['nama_subject']); ?></span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Nama Tes:</span>
                <span class="result-value"><?php echo htmlspecialchars($session['nama_tes']); ?></span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Waktu Mulai:</span>
                <span class="result-value"><?php echo date('d/m/Y H:i:s', strtotime($session['waktu_mulai'])); ?></span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Waktu Selesai:</span>
                <span class="result-value"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Total Soal:</span>
                <span class="result-value"><?php echo $total_questions; ?> soal</span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Soal Terjawab:</span>
                <span class="result-value"><?php echo $answered_questions; ?> soal</span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Jawaban Benar:</span>
                <span class="result-value"><?php echo $correct_answers; ?> soal</span>
            </div>
            
            <div class="result-row">
                <span class="result-label">Skor Akhir:</span>
                <span class="result-value score-highlight"><?php echo $score; ?>%</span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            <a href="test_result.php?session_id=<?php echo $session['id']; ?>" class="btn btn-primary">Lihat Detail Hasil</a>
            <a href="competency_report.php" class="btn" style="background: linear-gradient(135deg, #38a169 0%, #2f855a 100%); color: white;">Laporan Kompetensi</a>
        </div>
    </div>
</body>
</html>