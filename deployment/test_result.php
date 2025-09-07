<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : '';

if (empty($session_id)) {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();

// Ambil data sesi tes
$query = "SELECT ts.*, t.nama_tes, t.durasi_menit, s.nama_subject, u.nama_lengkap
          FROM test_sessions ts 
          JOIN tests t ON ts.test_id = t.id 
          JOIN subjects s ON t.subject_id = s.id 
          JOIN users u ON ts.user_id = u.id
          WHERE ts.id = :session_id AND ts.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':session_id', $session_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo "<script>alert('Sesi tes tidak valid!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Ambil detail jawaban
$query = "SELECT q.id, q.pertanyaan, q.pilihan_a, q.pilihan_b, q.pilihan_c, q.pilihan_d, 
                 q.jawaban_benar, q.tipe_soal, q.pilihan_jawaban, ua.jawaban as jawaban_user, 
                 ua.jawaban_kompleks, 
                 CASE 
                     WHEN q.tipe_soal = 'essay' THEN 0
                     WHEN ua.jawaban = q.jawaban_benar THEN 1 
                     ELSE 0 
                 END as is_correct
          FROM questions q 
          LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.session_id = :session_id
          WHERE q.test_id = :test_id
          ORDER BY q.id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':session_id', $session_id);
$stmt->bindParam(':test_id', $session['test_id']);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_questions = count($questions);
$answered_questions = 0;
$correct_answers = 0;

foreach ($questions as $question) {
    if (!empty($question['jawaban_user'])) {
        $answered_questions++;
        if ($question['is_correct']) {
            $correct_answers++;
        }
    }
}

$score = ($total_questions > 0) ? round(($correct_answers / $total_questions) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Hasil Tes - CLASNET ACADEMY AKM</title>
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
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: #f7fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-text {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .user-info {
            color: #4a5568;
            font-size: 14px;
        }
        
        .logout-btn {
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
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .result-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .result-title {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .result-subtitle {
            color: #718096;
            font-size: 16px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary-label {
            font-size: 14px;
            color: #718096;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .score-value {
            color: #38a169;
        }
        
        .questions-section {
            margin-top: 30px;
        }
        
        .section-title {
            font-size: 20px;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .question-item {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #cbd5e0;
        }
        
        .question-item.correct {
            border-left-color: #38a169;
            background: #f0fff4;
        }
        
        .question-item.incorrect {
            border-left-color: #e53e3e;
            background: #fff5f5;
        }
        
        .question-item.unanswered {
            border-left-color: #ed8936;
            background: #fffaf0;
        }
        
        .question-number {
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .question-text {
            color: #2d3748;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .options {
            display: grid;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .option {
            padding: 8px 12px;
            border-radius: 6px;
            background: white;
            border: 1px solid #e2e8f0;
        }
        
        .option.correct {
            background: #c6f6d5;
            border-color: #38a169;
            font-weight: bold;
        }
        
        .option.user-answer {
            background: #fed7d7;
            border-color: #e53e3e;
        }
        
        .option.user-answer.correct {
            background: #c6f6d5;
            border-color: #38a169;
        }
        
        .answer-status {
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-correct {
            color: #38a169;
        }
        
        .status-incorrect {
            color: #e53e3e;
        }
        
        .status-unanswered {
            color: #ed8936;
        }
        
        .status-pending {
            color: #3182ce;
        }
        
        .essay-answer {
            margin-bottom: 15px;
        }
        
        .essay-label {
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .essay-content {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            min-height: 80px;
            line-height: 1.6;
            color: #2d3748;
        }
        
        .essay-content em {
            color: #a0aec0;
            font-style: italic;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #4299e1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3182ce;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
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
    
    <div class="container">
        <div class="result-header">
            <h1 class="result-title">Detail Hasil Tes</h1>
            <p class="result-subtitle"><?php echo htmlspecialchars($session['nama_tes']); ?> - <?php echo htmlspecialchars($session['nama_subject']); ?></p>
        </div>
        
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Soal</div>
                <div class="summary-value"><?php echo $total_questions; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Soal Terjawab</div>
                <div class="summary-value"><?php echo $answered_questions; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Jawaban Benar</div>
                <div class="summary-value"><?php echo $correct_answers; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Skor Akhir</div>
                <div class="summary-value score-value"><?php echo $score; ?>%</div>
            </div>
        </div>
        
        <div class="questions-section">
            <h2 class="section-title">Detail Jawaban</h2>
            
            <?php foreach ($questions as $index => $question): ?>
                <?php 
                    $status_class = '';
                    $status_text = '';
                    
                    if (empty($question['jawaban_user'])) {
                        $status_class = 'unanswered';
                        $status_text = 'Tidak Dijawab';
                    } elseif ($question['is_correct']) {
                        $status_class = 'correct';
                        $status_text = 'Benar';
                    } else {
                        $status_class = 'incorrect';
                        $status_text = 'Salah';
                    }
                ?>
                
                <div class="question-item <?php echo $status_class; ?>">
                    <div class="question-number">Soal <?php echo $index + 1; ?> - <?php echo ucfirst(str_replace('_', ' ', $question['tipe_soal'])); ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question['pertanyaan']); ?></div>
                    
                    <?php if ($question['tipe_soal'] == 'essay'): ?>
                        <!-- Tampilan untuk Essay -->
                        <div class="essay-answer">
                            <div class="essay-label">Jawaban Anda:</div>
                            <div class="essay-content">
                                <?php if (!empty($question['jawaban_user'])): ?>
                                    <?php echo nl2br(htmlspecialchars($question['jawaban_user'])); ?>
                                <?php else: ?>
                                    <em>Tidak ada jawaban</em>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="answer-status status-pending">
                            Status: Menunggu Penilaian Manual
                        </div>
                    <?php else: ?>
                        <!-- Tampilan untuk Pilihan Ganda -->
                        <div class="options">
                            <div class="option <?php echo ($question['jawaban_benar'] == 'A') ? 'correct' : ''; ?> <?php echo ($question['jawaban_user'] == 'A') ? 'user-answer' : ''; ?>">
                                A. <?php echo htmlspecialchars($question['pilihan_a']); ?>
                            </div>
                            <div class="option <?php echo ($question['jawaban_benar'] == 'B') ? 'correct' : ''; ?> <?php echo ($question['jawaban_user'] == 'B') ? 'user-answer' : ''; ?>">
                                B. <?php echo htmlspecialchars($question['pilihan_b']); ?>
                            </div>
                            <div class="option <?php echo ($question['jawaban_benar'] == 'C') ? 'correct' : ''; ?> <?php echo ($question['jawaban_user'] == 'C') ? 'user-answer' : ''; ?>">
                                C. <?php echo htmlspecialchars($question['pilihan_c']); ?>
                            </div>
                            <div class="option <?php echo ($question['jawaban_benar'] == 'D') ? 'correct' : ''; ?> <?php echo ($question['jawaban_user'] == 'D') ? 'user-answer' : ''; ?>">
                                D. <?php echo htmlspecialchars($question['pilihan_d']); ?>
                            </div>
                        </div>
                        
                        <div class="answer-status status-<?php echo $status_class; ?>">
                            Status: <?php echo $status_text; ?>
                            <?php if (!empty($question['jawaban_user'])): ?>
                                | Jawaban Anda: <?php echo $question['jawaban_user']; ?>
                            <?php endif; ?>
                            | Jawaban Benar: <?php echo $question['jawaban_benar']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            <a href="test_finish.php?token=<?php echo $session['token']; ?>" class="btn btn-primary">Kembali ke Ringkasan</a>
        </div>
    </div>
</body>
</html>