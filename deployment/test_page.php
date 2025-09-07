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
          WHERE ts.token = :token AND ts.user_id = :user_id AND ts.status = 'ongoing'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo "<script>alert('Sesi tes tidak valid!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Ambil soal-soal untuk tes ini
$query = "SELECT * FROM questions WHERE test_id = :test_id ORDER BY nomor_soal";
$stmt = $conn->prepare($query);
$stmt->bindParam(':test_id', $session['test_id']);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Cek apakah soal ditemukan
if (empty($questions)) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin: 10px;'>";
    echo "<h3>Debug Info:</h3>";
    echo "<p>Test ID: " . $session['test_id'] . "</p>";
    echo "<p>Jumlah soal ditemukan: " . count($questions) . "</p>";
    
    // Cek apakah ada soal di database
    $debug_query = "SELECT COUNT(*) as total FROM questions";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->execute();
    $total_questions = $debug_stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total soal di database: " . $total_questions['total'] . "</p>";
    
    // Cek soal untuk test_id tertentu
    $debug_query2 = "SELECT id, nomor_soal, pertanyaan FROM questions WHERE test_id = :test_id";
    $debug_stmt2 = $conn->prepare($debug_query2);
    $debug_stmt2->bindParam(':test_id', $session['test_id']);
    $debug_stmt2->execute();
    $debug_questions = $debug_stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Soal untuk test_id " . $session['test_id'] . ": " . count($debug_questions) . "</p>";
    
    if (!empty($debug_questions)) {
        echo "<ul>";
        foreach ($debug_questions as $dq) {
            echo "<li>ID: " . $dq['id'] . ", Nomor: " . $dq['nomor_soal'] . ", Pertanyaan: " . substr($dq['pertanyaan'], 0, 50) . "...</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    echo "<p><a href='dashboard.php'>Kembali ke Dashboard</a></p>";
    exit();
}

// Ambil nomor soal saat ini
$current_question = isset($_GET['q']) ? (int)$_GET['q'] : 1;
$current_question = max(1, min($current_question, count($questions)));

$question = $questions[$current_question - 1];

// Ambil jawaban yang sudah disimpan
$query = "SELECT jawaban FROM user_answers WHERE session_id = :session_id AND question_id = :question_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':session_id', $session['id']);
$stmt->bindParam(':question_id', $question['id']);
$stmt->execute();
$saved_answer = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses penyimpanan jawaban
if ($_POST) {
    $answer = null;
    $answer_kompleks = null;
    
    // Handle berbagai tipe jawaban
    if ($question['tipe_soal'] == 'pilihan_ganda' || $question['tipe_soal'] == 'benar_salah') {
        $answer = isset($_POST['answer']) ? $_POST['answer'] : null;
    } elseif ($question['tipe_soal'] == 'pilihan_ganda_kompleks') {
        $answer_kompleks = isset($_POST['answers']) ? json_encode($_POST['answers']) : null;
    } elseif ($question['tipe_soal'] == 'isian_singkat') {
        $answer = isset($_POST['text_answer']) ? trim($_POST['text_answer']) : null;
    } elseif ($question['tipe_soal'] == 'drag_drop') {
        $drag_answer = isset($_POST['drag_drop_answer']) ? $_POST['drag_drop_answer'] : '';
        $answer_kompleks = json_encode($drag_answer);
    } elseif ($question['tipe_soal'] == 'urutan') {
        $sequence_answer = isset($_POST['sequence_answer']) ? $_POST['sequence_answer'] : '';
        // Pastikan sequence_answer adalah array atau string yang bisa di-decode
        if (is_string($sequence_answer)) {
            $decoded = json_decode($sequence_answer, true);
            $answer_kompleks = $decoded !== null ? $sequence_answer : json_encode($sequence_answer);
        } else {
            $answer_kompleks = json_encode($sequence_answer);
        }
    } elseif ($question['tipe_soal'] == 'essay') {
        $answer = isset($_POST['essay_answer']) ? trim($_POST['essay_answer']) : null;
    }
    
    // Validasi jawaban_kompleks harus JSON valid atau NULL
    if ($answer_kompleks !== null && !empty($answer_kompleks)) {
        $decoded = json_decode($answer_kompleks);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $answer_kompleks = null; // Set ke null jika bukan JSON valid
        }
    }
    
    if ($answer !== null || $answer_kompleks !== null) {
        // Cek apakah sudah ada jawaban
        $query = "SELECT id FROM user_answers WHERE session_id = :session_id AND question_id = :question_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':session_id', $session['id']);
        $stmt->bindParam(':question_id', $question['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update jawaban
            $query = "UPDATE user_answers SET jawaban = :jawaban, jawaban_kompleks = :jawaban_kompleks, answered_at = CURRENT_TIMESTAMP WHERE session_id = :session_id AND question_id = :question_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':jawaban', $answer);
            $stmt->bindParam(':jawaban_kompleks', $answer_kompleks);
            $stmt->bindParam(':session_id', $session['id']);
            $stmt->bindParam(':question_id', $question['id']);
            $stmt->execute();
        } else {
            // Insert jawaban baru
            $query = "INSERT INTO user_answers (session_id, question_id, jawaban, jawaban_kompleks) VALUES (:session_id, :question_id, :jawaban, :jawaban_kompleks)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':session_id', $session['id']);
            $stmt->bindParam(':question_id', $question['id']);
            $stmt->bindParam(':jawaban', $answer);
            $stmt->bindParam(':jawaban_kompleks', $answer_kompleks);
            $stmt->execute();
        }
    }
    
    // Redirect ke soal berikutnya atau halaman selesai
    if ($current_question < count($questions)) {
        header('Location: test_page.php?token=' . $token . '&q=' . ($current_question + 1));
    } else {
        header('Location: test_finish.php?token=' . $token);
    }
    exit();
}

// Hitung waktu tersisa
$start_time = strtotime($session['waktu_mulai']);
$duration_seconds = $session['durasi_menit'] * 60;
$current_time = time();
$elapsed_time = $current_time - $start_time;
$remaining_time = max(0, $duration_seconds - $elapsed_time);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal <?php echo $current_question; ?> - CLASNET ACADEMY AKM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .header {
            background: #4a5568;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
        
        .timer {
            background: #e53e3e;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: bold;
        }
        
        .question-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .question-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .question-number {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: bold;
        }
        
        .subject-info {
            color: #4a5568;
            font-size: 14px;
        }
        
        .font-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .font-btn {
            background: #e2e8f0;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .main-content {
            display: flex;
            min-height: calc(100vh - 140px);
        }
        
        .question-content {
            flex: 1;
            padding: 30px;
            background: white;
            margin: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .question-title {
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 25px;
        }
        
        .question-image {
            width: 300px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .question-text {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .answer-options {
            margin: 30px 0;
        }
        
        .option {
            display: block;
            padding: 0;
            margin: 15px 0;
            border: none;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
        }
        
        .option input[type="radio"] {
            display: none;
        }
        
        .option-text {
            display: block;
            padding: 18px 25px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            line-height: 1.5;
            color: #2d3748;
            background: white;
            transition: all 0.3s;
            position: relative;
        }
        
        .option:hover .option-text {
            border-color: #cbd5e0;
            background: #f7fafc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .option.selected .option-text {
            border-color: #667eea;
            background: #edf2f7;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .option.selected .option-text::before {
            content: '●';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 20px;
            font-weight: bold;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .prev-btn {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .prev-btn:hover {
            background: #cbd5e0;
        }
        
        .next-btn {
            background: #667eea;
            color: white;
        }
        
        .next-btn:hover {
            background: #5a67d8;
        }
        
        .question-nav {
            width: 250px;
            background: white;
            margin: 20px 20px 20px 0;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .nav-title {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .question-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
        }
        
        .question-item {
            width: 35px;
            height: 35px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .question-item.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .question-item.answered {
            background: #48bb78;
            color: white;
            border-color: #48bb78;
        }
        
        /* Styling untuk checkbox (pilihan ganda kompleks) */
        .option-checkbox {
            display: block;
            padding: 0;
            margin: 15px 0;
            border: none;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
        }
        
        .option-checkbox input[type="checkbox"] {
            display: none;
        }
        
        .option-checkbox .option-text {
            display: block;
            padding: 18px 25px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            line-height: 1.5;
            color: #2d3748;
            background: white;
            transition: all 0.3s;
            position: relative;
        }
        
        .option-checkbox:hover .option-text {
            border-color: #cbd5e0;
            background: #f7fafc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .option-checkbox.selected .option-text {
            border-color: #667eea;
            background: #edf2f7;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .option-checkbox.selected .option-text::before {
            content: '✓';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
            font-weight: bold;
        }
        
        /* Styling untuk text input (isian singkat) */
        .text-input-container {
            margin: 20px 0;
        }
        
        .text-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .text-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Styling untuk drag and drop */
        .drag-drop-container {
            margin: 20px 0;
        }
        
        .drag-items {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .drag-item {
            background: #667eea;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: grab;
            user-select: none;
            transition: all 0.3s;
        }
        
        .drag-item:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .drag-item.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }
        
        .drop-zone {
            min-height: 100px;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7fafc;
            transition: all 0.3s;
        }
        
        .drop-zone.drag-over {
            border-color: #667eea;
            background: #edf2f7;
        }
        
        .drop-text {
            color: #a0aec0;
            font-style: italic;
        }
        
        /* Styling untuk sequence/urutan */
        .sequence-container {
            margin: 20px 0;
        }
        
        .sequence-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .sequence-item {
            background: white;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: move;
            user-select: none;
            transition: all 0.3s;
            position: relative;
        }
        
        .sequence-item:hover {
            border-color: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .sequence-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        .sequence-item::before {
            content: "⋮⋮";
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-weight: bold;
        }
        
        .sequence-item .sequence-text {
            margin-left: 20px;
        }
        
        /* Styling untuk essay */
        .essay-container {
            margin: 20px 0;
        }
        
        .essay-instructions {
            background: #f0f8ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .essay-instructions p {
            margin: 0;
            color: #2d3748;
            font-size: 14px;
        }
        
        .essay-input-container {
            margin-bottom: 15px;
        }
        
        .essay-input {
            width: 100%;
            min-height: 200px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        .essay-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .essay-input::placeholder {
            color: #a0aec0;
            font-style: italic;
        }
        
        .essay-info {
            text-align: right;
        }
        
        .essay-info .text-muted {
            color: #718096;
            font-size: 13px;
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
        <div class="timer" id="timer">
            <span id="time-remaining"><?php echo gmdate('H:i:s', $remaining_time); ?></span>
        </div>
    </div>
    
    <div class="question-header">
        <div class="question-info">
            <div class="question-number">Soal nomor <?php echo $current_question; ?></div>
            <div class="subject-info">
                <div>Ujian Tes Soal: <?php echo htmlspecialchars($session['nama_subject']); ?></div>
                <div>Literasi : <?php echo htmlspecialchars($session['nama_tes']); ?></div>
            </div>
        </div>
        <div class="font-controls">
            <span>Ukuran font soal:</span>
            <button class="font-btn" onclick="changeFontSize(-1)">A-</button>
            <button class="font-btn" onclick="changeFontSize(0)">A</button>
            <button class="font-btn" onclick="changeFontSize(1)">A+</button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="question-content">
            <h2 class="question-title"><?php echo htmlspecialchars($question['pertanyaan']); ?></h2>
            
            <?php if ($question['gambar']): ?>
                <img src="images/<?php echo htmlspecialchars($question['gambar']); ?>" alt="Gambar Soal" class="question-image" onerror="this.style.display='none'">
            <?php endif; ?>
            
            <?php if (!empty($question['teks_bacaan'])): ?>
            <div class="question-text">
                <?php echo nl2br(htmlspecialchars($question['teks_bacaan'])); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="answer-options">
                    <?php if ($question['tipe_soal'] == 'pilihan_ganda'): ?>
                        <?php 
                        $options = $question['pilihan_jawaban'] ? json_decode($question['pilihan_jawaban'], true) : null;
                        if ($options):
                            foreach ($options as $key => $option):
                        ?>
                        <label class="option <?php echo ($saved_answer && $saved_answer['jawaban'] == $key) ? 'selected' : ''; ?>">
                            <input type="radio" name="answer" value="<?php echo $key; ?>" <?php echo ($saved_answer && $saved_answer['jawaban'] == $key) ? 'checked' : ''; ?>>
                            <span class="option-text"><?php echo htmlspecialchars($option); ?></span>
                        </label>
                        <?php endforeach; endif; ?>
                    
                    <?php elseif ($question['tipe_soal'] == 'benar_salah'): ?>
                        <label class="option <?php echo ($saved_answer && $saved_answer['jawaban'] == 'benar') ? 'selected' : ''; ?>">
                            <input type="radio" name="answer" value="benar" <?php echo ($saved_answer && $saved_answer['jawaban'] == 'benar') ? 'checked' : ''; ?>>
                            <span class="option-text">Benar</span>
                        </label>
                        <label class="option <?php echo ($saved_answer && $saved_answer['jawaban'] == 'salah') ? 'selected' : ''; ?>">
                            <input type="radio" name="answer" value="salah" <?php echo ($saved_answer && $saved_answer['jawaban'] == 'salah') ? 'checked' : ''; ?>>
                            <span class="option-text">Salah</span>
                        </label>
                    
                    <?php elseif ($question['tipe_soal'] == 'pilihan_ganda_kompleks'): ?>
                        <?php 
                        $options = json_decode($question['pilihan_jawaban'], true);
                        $saved_kompleks = $saved_answer ? json_decode($saved_answer['jawaban_kompleks'], true) : [];
                        if ($options):
                            foreach ($options as $key => $option):
                        ?>
                        <label class="option-checkbox <?php echo (in_array($key, $saved_kompleks ?: [])) ? 'selected' : ''; ?>">
                            <input type="checkbox" name="answers[]" value="<?php echo $key; ?>" <?php echo (in_array($key, $saved_kompleks ?: [])) ? 'checked' : ''; ?>>
                            <span class="option-text"><?php echo htmlspecialchars($option); ?></span>
                        </label>
                        <?php endforeach; endif; ?>
                    
                    <?php elseif ($question['tipe_soal'] == 'isian_singkat'): ?>
                        <div class="text-input-container">
                            <input type="text" name="text_answer" class="text-input" placeholder="Masukkan jawaban Anda..." value="<?php echo $saved_answer ? htmlspecialchars($saved_answer['jawaban']) : ''; ?>">
                        </div>
                    
                    <?php elseif ($question['tipe_soal'] == 'drag_drop'): ?>
                        <div class="drag-drop-container">
                            <div class="drag-items">
                                <?php 
                                $drag_items = json_decode($question['pilihan_jawaban'], true);
                                if ($drag_items):
                                    foreach ($drag_items as $item):
                                ?>
                                <div class="drag-item" draggable="true" data-value="<?php echo htmlspecialchars($item); ?>">
                                    <?php echo htmlspecialchars($item); ?>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                            <div class="drop-zone" id="dropZone">
                                <span class="drop-text">Seret jawaban ke sini</span>
                            </div>
                            <input type="hidden" name="drag_drop_answer" id="dragDropAnswer" value="<?php echo $saved_answer ? htmlspecialchars($saved_answer['jawaban_kompleks']) : ''; ?>">
                        </div>
                    
                    <?php elseif ($question['tipe_soal'] == 'urutan'): ?>
                        <div class="sequence-container">
                            <div class="sequence-items" id="sequenceItems">
                                <?php 
                                $sequence_items = json_decode($question['pilihan_jawaban'], true);
                                $saved_sequence = $saved_answer ? json_decode($saved_answer['jawaban_kompleks'], true) : [];
                                if ($sequence_items):
                                    // Jika ada jawaban tersimpan, gunakan urutan tersebut
                                    if (!empty($saved_sequence)) {
                                        foreach ($saved_sequence as $item) {
                                            if (in_array($item, $sequence_items)):
                                    ?>
                                    <div class="sequence-item" data-value="<?php echo htmlspecialchars($item); ?>">
                                        <?php echo htmlspecialchars($item); ?>
                                    </div>
                                    <?php 
                                            endif;
                                        }
                                    } else {
                                        // Tampilkan dalam urutan acak
                                        shuffle($sequence_items);
                                        foreach ($sequence_items as $item):
                                    ?>
                                    <div class="sequence-item" data-value="<?php echo htmlspecialchars($item); ?>">
                                        <?php echo htmlspecialchars($item); ?>
                                    </div>
                                    <?php 
                                        endforeach;
                                    }
                                endif; 
                                ?>
                            </div>
                            <input type="hidden" name="sequence_answer" id="sequenceAnswer" value="<?php echo $saved_answer ? htmlspecialchars($saved_answer['jawaban_kompleks']) : ''; ?>">
                        </div>
                    
                    <?php elseif ($question['tipe_soal'] == 'essay'): ?>
                        <div class="essay-container">
                            <div class="essay-instructions">
                                <p><strong>Petunjuk:</strong> Jawablah pertanyaan berikut dengan uraian yang jelas dan lengkap.</p>
                            </div>
                            <div class="essay-input-container">
                                <textarea name="essay_answer" class="essay-input" placeholder="Tuliskan jawaban Anda di sini..." rows="10"><?php echo $saved_answer ? htmlspecialchars($saved_answer['jawaban']) : ''; ?></textarea>
                            </div>
                            <div class="essay-info">
                                <small class="text-muted">Tip: Pastikan jawaban Anda mencakup poin-poin penting dan disusun dengan baik.</small>
                            </div>
                        </div>
                    
                    <?php endif; ?>
                </div>
                
                <div class="navigation">
                    <?php if ($current_question > 1): ?>
                        <a href="test_page.php?token=<?php echo $token; ?>&q=<?php echo ($current_question - 1); ?>" class="nav-btn prev-btn">← Sebelumnya</a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <button type="submit" class="nav-btn next-btn">
                        <?php echo ($current_question < count($questions)) ? 'Selanjutnya →' : 'Selesai'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="question-nav">
            <div class="nav-title">Navigasi Soal</div>
            <div class="question-grid">
                <?php for ($i = 1; $i <= count($questions); $i++): ?>
                    <?php
                    $class = '';
                    if ($i == $current_question) {
                        $class = 'current';
                    } else {
                        // Cek apakah soal sudah dijawab
                        $q_id = $questions[$i-1]['id'];
                        $query = "SELECT id FROM user_answers WHERE session_id = :session_id AND question_id = :question_id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':session_id', $session['id']);
                        $stmt->bindParam(':question_id', $q_id);
                        $stmt->execute();
                        if ($stmt->rowCount() > 0) {
                            $class = 'answered';
                        }
                    }
                    ?>
                    <a href="test_page.php?token=<?php echo $token; ?>&q=<?php echo $i; ?>" class="question-item <?php echo $class; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Timer countdown
        let remainingTime = <?php echo $remaining_time; ?>;
        
        function updateTimer() {
            if (remainingTime <= 0) {
                alert('Waktu habis! Tes akan diselesaikan.');
                window.location.href = 'test_finish.php?token=<?php echo $token; ?>';
                return;
            }
            
            const hours = Math.floor(remainingTime / 3600);
            const minutes = Math.floor((remainingTime % 3600) / 60);
            const seconds = remainingTime % 60;
            
            document.getElementById('time-remaining').textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
            
            remainingTime--;
        }
        
        setInterval(updateTimer, 1000);
        
        // Font size control
        function changeFontSize(change) {
            const content = document.querySelector('.question-content');
            const currentSize = parseInt(window.getComputedStyle(content).fontSize);
            let newSize = currentSize;
            
            if (change === -1) newSize = Math.max(12, currentSize - 2);
            else if (change === 1) newSize = Math.min(24, currentSize + 2);
            else newSize = 16;
            
            content.style.fontSize = newSize + 'px';
        }
        
        // Option selection visual feedback for radio buttons
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                // Check the hidden radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });
        
        // Checkbox selection visual feedback
        document.querySelectorAll('.option-checkbox').forEach(option => {
            option.addEventListener('click', function(e) {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    
                    if (checkbox.checked) {
                        this.classList.add('selected');
                    } else {
                        this.classList.remove('selected');
                    }
                }
            });
        });
        
        // Drag and Drop functionality
        const dragItems = document.querySelectorAll('.drag-item');
        const dropZone = document.getElementById('dropZone');
        const dragDropAnswer = document.getElementById('dragDropAnswer');
        
        if (dragItems.length > 0 && dropZone) {
            dragItems.forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    this.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', this.dataset.value);
                });
                
                item.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                });
            });
            
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
            
            dropZone.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });
            
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                const draggedValue = e.dataTransfer.getData('text/plain');
                this.innerHTML = '<span class="dropped-item">' + draggedValue + '</span>';
                dragDropAnswer.value = draggedValue;
            });
        }
        
        // Sequence/Urutan functionality
        const sequenceItems = document.getElementById('sequenceItems');
        const sequenceAnswer = document.getElementById('sequenceAnswer');
        
        if (sequenceItems) {
            let draggedElement = null;
            
            // Make sequence items draggable
            Array.from(sequenceItems.children).forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                
                item.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                    draggedElement = null;
                    updateSequenceAnswer();
                });
                
                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                });
                
                item.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    if (draggedElement && draggedElement !== this) {
                        const rect = this.getBoundingClientRect();
                        const midpoint = rect.top + rect.height / 2;
                        
                        if (e.clientY < midpoint) {
                            sequenceItems.insertBefore(draggedElement, this);
                        } else {
                            sequenceItems.insertBefore(draggedElement, this.nextSibling);
                        }
                    }
                });
            });
            
            function updateSequenceAnswer() {
                const sequence = Array.from(sequenceItems.children).map(item => item.dataset.value);
                sequenceAnswer.value = JSON.stringify(sequence);
            }
            
            // Initialize sequence answer if not already set
            if (!sequenceAnswer.value) {
                updateSequenceAnswer();
            }
        }
    </script>
</body>
</html>