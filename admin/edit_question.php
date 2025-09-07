<?php
session_start();
require_once '../config/database.php';
require_once 'auth_config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($question_id <= 0) {
    header('Location: questions.php');
    exit();
}

// Ambil data pertanyaan
$query = "SELECT q.*, t.nama_tes, s.nama_subject 
          FROM questions q 
          JOIN tests t ON q.test_id = t.id 
          JOIN subjects s ON t.subject_id = s.id 
          WHERE q.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $question_id);
$stmt->execute();
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    echo "<script>alert('Pertanyaan tidak ditemukan!'); window.location.href='questions.php';</script>";
    exit();
}

// Ambil daftar tes untuk dropdown
$query = "SELECT t.*, s.nama_subject FROM tests t JOIN subjects s ON t.subject_id = s.id ORDER BY s.nama_subject, t.nama_tes";
$stmt = $conn->prepare($query);
$stmt->execute();
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses update pertanyaan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = $_POST['test_id'];
    $pertanyaan = $_POST['pertanyaan'];
    $tipe_soal = $_POST['tipe_soal'];
    $bobot = $_POST['bobot'];
    
    // Validasi input
    if (empty($pertanyaan) || empty($tipe_soal)) {
        $error = "Pertanyaan dan tipe soal harus diisi!";
    } else {
        try {
            $conn->beginTransaction();
            
            if ($tipe_soal == 'pilihan_ganda' || $tipe_soal == 'benar_salah') {
                $pilihan_a = $_POST['pilihan_a'];
                $pilihan_b = $_POST['pilihan_b'];
                $pilihan_c = $_POST['pilihan_c'] ?? '';
                $pilihan_d = $_POST['pilihan_d'] ?? '';
                $jawaban_benar = $_POST['jawaban_benar'];
                
                $query = "UPDATE questions SET 
                         test_id = :test_id,
                         pertanyaan = :pertanyaan,
                         tipe_soal = :tipe_soal,
                         pilihan_a = :pilihan_a,
                         pilihan_b = :pilihan_b,
                         pilihan_c = :pilihan_c,
                         pilihan_d = :pilihan_d,
                         jawaban_benar = :jawaban_benar,
                         bobot = :bobot,
                         pilihan_jawaban = NULL
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':test_id', $test_id);
                $stmt->bindParam(':pertanyaan', $pertanyaan);
                $stmt->bindParam(':tipe_soal', $tipe_soal);
                $stmt->bindParam(':pilihan_a', $pilihan_a);
                $stmt->bindParam(':pilihan_b', $pilihan_b);
                $stmt->bindParam(':pilihan_c', $pilihan_c);
                $stmt->bindParam(':pilihan_d', $pilihan_d);
                $stmt->bindParam(':jawaban_benar', $jawaban_benar);
                $stmt->bindParam(':bobot', $bobot);
                $stmt->bindParam(':id', $question_id);
                
            } elseif ($tipe_soal == 'pilihan_ganda_kompleks') {
                $kompleks_a = $_POST['kompleks_a'];
                $kompleks_b = $_POST['kompleks_b'];
                $kompleks_c = $_POST['kompleks_c'];
                $kompleks_d = $_POST['kompleks_d'];
                $kompleks_e = $_POST['kompleks_e'];
                $jawaban_kompleks_array = $_POST['jawaban_kompleks'] ?? [];
                
                // Validasi minimal satu jawaban benar dipilih
                if (empty($jawaban_kompleks_array)) {
                    throw new Exception("Minimal satu jawaban benar harus dipilih!");
                }
                
                // Buat JSON untuk pilihan jawaban
                $pilihan_jawaban = json_encode([
                    'A' => $kompleks_a,
                    'B' => $kompleks_b,
                    'C' => $kompleks_c,
                    'D' => $kompleks_d,
                    'E' => $kompleks_e
                ]);
                
                // Gabungkan jawaban benar menjadi string (misal: "A,C,E" untuk multiple answers)
                $jawaban_benar_str = implode(',', $jawaban_kompleks_array);
                
                $query = "UPDATE questions SET 
                         test_id = :test_id,
                         pertanyaan = :pertanyaan,
                         tipe_soal = :tipe_soal,
                         pilihan_jawaban = :pilihan_jawaban,
                         jawaban_benar = :jawaban_benar,
                         bobot = :bobot,
                         pilihan_a = NULL,
                         pilihan_b = NULL,
                         pilihan_c = NULL,
                         pilihan_d = NULL
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':test_id', $test_id);
                $stmt->bindParam(':pertanyaan', $pertanyaan);
                $stmt->bindParam(':tipe_soal', $tipe_soal);
                $stmt->bindParam(':pilihan_jawaban', $pilihan_jawaban);
                $stmt->bindParam(':jawaban_benar', $jawaban_benar_str);
                $stmt->bindParam(':bobot', $bobot);
                $stmt->bindParam(':id', $question_id);
                
            } elseif ($tipe_soal == 'isian_singkat') {
                $jawaban_isian = $_POST['jawaban_isian'];
                $case_sensitive = isset($_POST['case_sensitive']) ? 1 : 0;
                
                $query = "UPDATE questions SET 
                         test_id = :test_id,
                         pertanyaan = :pertanyaan,
                         tipe_soal = :tipe_soal,
                         jawaban_benar = :jawaban_benar,
                         is_case_sensitive = :case_sensitive,
                         bobot = :bobot,
                         pilihan_a = NULL,
                         pilihan_b = NULL,
                         pilihan_c = NULL,
                         pilihan_d = NULL
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':test_id', $test_id);
                $stmt->bindParam(':pertanyaan', $pertanyaan);
                $stmt->bindParam(':tipe_soal', $tipe_soal);
                $stmt->bindParam(':jawaban_benar', $jawaban_isian);
                $stmt->bindParam(':case_sensitive', $case_sensitive);
                $stmt->bindParam(':bobot', $bobot);
                $stmt->bindParam(':id', $question_id);
                
            } elseif ($tipe_soal == 'essay') {
                $query = "UPDATE questions SET 
                         test_id = :test_id,
                         pertanyaan = :pertanyaan,
                         tipe_soal = :tipe_soal,
                         bobot = :bobot,
                         pilihan_a = NULL,
                         pilihan_b = NULL,
                         pilihan_c = NULL,
                         pilihan_d = NULL,
                         jawaban_benar = NULL
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':test_id', $test_id);
                $stmt->bindParam(':pertanyaan', $pertanyaan);
                $stmt->bindParam(':tipe_soal', $tipe_soal);
                $stmt->bindParam(':bobot', $bobot);
                $stmt->bindParam(':id', $question_id);
            }
            
            $stmt->execute();
            $conn->commit();
            
            echo "<script>alert('Pertanyaan berhasil diupdate!'); window.location.href='questions.php';</script>";
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pertanyaan - Admin AKM</title>
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
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .title {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #718096;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #4299e1;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .options-container {
            display: none;
        }
        
        .options-container.show {
            display: block;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(66, 153, 225, 0.4);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .question-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .question-info h3 {
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .question-info p {
            color: #4a5568;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Edit Pertanyaan</h1>
            <p class="subtitle">Ubah data pertanyaan yang sudah ada</p>
        </div>
        
        <div class="question-info">
            <h3>Informasi Pertanyaan</h3>
            <p><strong>ID:</strong> <?php echo $question['id']; ?></p>
            <p><strong>Tes:</strong> <?php echo htmlspecialchars($question['nama_tes']); ?></p>
            <p><strong>Mata Pelajaran:</strong> <?php echo htmlspecialchars($question['nama_subject']); ?></p>
            <p><strong>Tipe Soal:</strong> <?php echo ucfirst(str_replace('_', ' ', $question['tipe_soal'])); ?></p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Pilih Tes:</label>
                <select name="test_id" class="form-select" required>
                    <option value="">-- Pilih Tes --</option>
                    <?php foreach ($tests as $test): ?>
                        <option value="<?php echo $test['id']; ?>" <?php echo ($test['id'] == $question['test_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($test['nama_subject'] . ' - ' . $test['nama_tes']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipe Soal:</label>
                <select name="tipe_soal" id="tipe_soal" class="form-select" required>
                    <option value="pilihan_ganda" <?php echo ($question['tipe_soal'] == 'pilihan_ganda') ? 'selected' : ''; ?>>Pilihan Ganda</option>
                    <option value="pilihan_ganda_kompleks" <?php echo ($question['tipe_soal'] == 'pilihan_ganda_kompleks') ? 'selected' : ''; ?>>Pilihan Ganda Kompleks</option>
                    <option value="benar_salah" <?php echo ($question['tipe_soal'] == 'benar_salah') ? 'selected' : ''; ?>>Benar/Salah</option>
                    <option value="isian_singkat" <?php echo ($question['tipe_soal'] == 'isian_singkat') ? 'selected' : ''; ?>>Isian Singkat</option>
                    <option value="essay" <?php echo ($question['tipe_soal'] == 'essay') ? 'selected' : ''; ?>>Essay</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Pertanyaan:</label>
                <textarea name="pertanyaan" class="form-textarea" required><?php echo htmlspecialchars($question['pertanyaan']); ?></textarea>
            </div>
            
            <div id="options_container" class="options-container">
                <div class="form-group">
                    <label class="form-label">Pilihan A:</label>
                    <input type="text" name="pilihan_a" class="form-input" value="<?php echo htmlspecialchars($question['pilihan_a'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilihan B:</label>
                    <input type="text" name="pilihan_b" class="form-input" value="<?php echo htmlspecialchars($question['pilihan_b'] ?? ''); ?>">
                </div>
                
                <div id="pilihan_cd" class="pilihan-cd">
                    <div class="form-group">
                        <label class="form-label">Pilihan C:</label>
                        <input type="text" name="pilihan_c" class="form-input" value="<?php echo htmlspecialchars($question['pilihan_c'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Pilihan D:</label>
                        <input type="text" name="pilihan_d" class="form-input" value="<?php echo htmlspecialchars($question['pilihan_d'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jawaban Benar:</label>
                    <select name="jawaban_benar" id="jawaban_benar" class="form-select">
                        <option value="A" <?php echo ($question['jawaban_benar'] == 'A') ? 'selected' : ''; ?>>A</option>
                        <option value="B" <?php echo ($question['jawaban_benar'] == 'B') ? 'selected' : ''; ?>>B</option>
                        <option value="C" <?php echo ($question['jawaban_benar'] == 'C') ? 'selected' : ''; ?>>C</option>
                        <option value="D" <?php echo ($question['jawaban_benar'] == 'D') ? 'selected' : ''; ?>>D</option>
                    </select>
                </div>
            </div>
            
            <div id="isian_container" class="isian-container" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Jawaban yang Benar:</label>
                    <input type="text" name="jawaban_isian" class="form-input" value="<?php echo htmlspecialchars($question['jawaban_benar'] ?? ''); ?>" placeholder="Masukkan jawaban yang benar">
                    <small class="text-muted">Untuk soal isian singkat, masukkan jawaban yang tepat</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="case_sensitive" value="1" <?php echo (isset($question['is_case_sensitive']) && $question['is_case_sensitive']) ? 'checked' : ''; ?>>
                        Case Sensitive (Membedakan huruf besar/kecil)
                    </label>
                </div>
            </div>
            
            <div id="kompleks_container" class="kompleks-container" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Pilihan A:</label>
                    <input type="text" name="kompleks_a" class="form-input" value="" placeholder="Masukkan pilihan A">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilihan B:</label>
                    <input type="text" name="kompleks_b" class="form-input" value="" placeholder="Masukkan pilihan B">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilihan C:</label>
                    <input type="text" name="kompleks_c" class="form-input" value="" placeholder="Masukkan pilihan C">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilihan D:</label>
                    <input type="text" name="kompleks_d" class="form-input" value="" placeholder="Masukkan pilihan D">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilihan E:</label>
                    <input type="text" name="kompleks_e" class="form-input" value="" placeholder="Masukkan pilihan E">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jawaban Benar (dapat memilih lebih dari satu):</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="jawaban_kompleks[]" value="A" id="jawaban_a">
                            <span>A</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="jawaban_kompleks[]" value="B" id="jawaban_b">
                            <span>B</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="jawaban_kompleks[]" value="C" id="jawaban_c">
                            <span>C</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="jawaban_kompleks[]" value="D" id="jawaban_d">
                            <span>D</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="jawaban_kompleks[]" value="E" id="jawaban_e">
                            <span>E</span>
                        </label>
                    </div>
                    <small class="text-muted">Centang semua pilihan yang merupakan jawaban benar</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Bobot Nilai:</label>
                <input type="number" name="bobot" class="form-input" min="1" max="100" value="<?php echo $question['bobot'] ?: 10; ?>" required>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Update Pertanyaan</button>
                <a href="questions.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    
    <script>
        function toggleOptions() {
            const tipeSelect = document.getElementById('tipe_soal');
            const optionsContainer = document.getElementById('options_container');
            const isianContainer = document.getElementById('isian_container');
            const kompleksContainer = document.getElementById('kompleks_container');
            const pilihanCD = document.getElementById('pilihan_cd');
            const jawabanSelect = document.getElementById('jawaban_benar');
            
            // Hide all containers first
            optionsContainer.classList.remove('show');
            isianContainer.style.display = 'none';
            kompleksContainer.style.display = 'none';
            
            if (tipeSelect.value === 'essay') {
                // Essay type - no options needed
                optionsContainer.classList.remove('show');
            } else if (tipeSelect.value === 'isian_singkat') {
                // Isian singkat type - show isian container
                isianContainer.style.display = 'block';
            } else if (tipeSelect.value === 'pilihan_ganda_kompleks') {
                // Pilihan ganda kompleks - show kompleks container
                kompleksContainer.style.display = 'block';
            } else {
                // Multiple choice types - show options container
                optionsContainer.classList.add('show');
                
                if (tipeSelect.value === 'benar_salah') {
                    pilihanCD.style.display = 'none';
                    // Update jawaban options untuk benar/salah
                    jawabanSelect.innerHTML = '<option value="A">A (Benar)</option><option value="B">B (Salah)</option>';
                    // Set pilihan A dan B untuk benar/salah
                    document.querySelector('input[name="pilihan_a"]').value = 'Benar';
                    document.querySelector('input[name="pilihan_b"]').value = 'Salah';
                } else {
                    pilihanCD.style.display = 'block';
                    // Update jawaban options untuk pilihan ganda
                    jawabanSelect.innerHTML = '<option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>';
                }
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleOptions();
            document.getElementById('tipe_soal').addEventListener('change', toggleOptions);
            
            // Set the correct jawaban_benar value based on current question type
            const currentType = document.getElementById('tipe_soal').value;
            const jawabanSelect = document.getElementById('jawaban_benar');
            const currentAnswer = '<?php echo $question['jawaban_benar']; ?>';
            
            if (currentType === 'benar_salah') {
                // For benar_salah, map the current answer
                if (currentAnswer === 'benar' || currentAnswer === 'A') {
                    jawabanSelect.value = 'A';
                } else if (currentAnswer === 'salah' || currentAnswer === 'B') {
                    jawabanSelect.value = 'B';
                }
            } else if (currentType === 'pilihan_ganda') {
                jawabanSelect.value = currentAnswer;
                
                // Load data from JSON if pilihan_jawaban exists and individual pilihan fields are empty
                const pilihanJawaban = <?php echo json_encode($question['pilihan_jawaban'] ?? '{}'); ?>;
                if (pilihanJawaban && typeof pilihanJawaban === 'string') {
                    try {
                        const pilihan = JSON.parse(pilihanJawaban);
                        // Only load from JSON if individual fields are empty
                        if (!document.querySelector('input[name="pilihan_a"]').value && pilihan.A) {
                            document.querySelector('input[name="pilihan_a"]').value = pilihan.A || '';
                        }
                        if (!document.querySelector('input[name="pilihan_b"]').value && pilihan.B) {
                            document.querySelector('input[name="pilihan_b"]').value = pilihan.B || '';
                        }
                        if (!document.querySelector('input[name="pilihan_c"]').value && pilihan.C) {
                            document.querySelector('input[name="pilihan_c"]').value = pilihan.C || '';
                        }
                        if (!document.querySelector('input[name="pilihan_d"]').value && pilihan.D) {
                            document.querySelector('input[name="pilihan_d"]').value = pilihan.D || '';
                        }
                    } catch (e) {
                        console.error('Error parsing pilihan jawaban for pilihan_ganda:', e);
                    }
                } else if (pilihanJawaban && typeof pilihanJawaban === 'object') {
                    // Data sudah dalam bentuk object
                    if (!document.querySelector('input[name="pilihan_a"]').value && pilihanJawaban.A) {
                        document.querySelector('input[name="pilihan_a"]').value = pilihanJawaban.A || '';
                    }
                    if (!document.querySelector('input[name="pilihan_b"]').value && pilihanJawaban.B) {
                        document.querySelector('input[name="pilihan_b"]').value = pilihanJawaban.B || '';
                    }
                    if (!document.querySelector('input[name="pilihan_c"]').value && pilihanJawaban.C) {
                        document.querySelector('input[name="pilihan_c"]').value = pilihanJawaban.C || '';
                    }
                    if (!document.querySelector('input[name="pilihan_d"]').value && pilihanJawaban.D) {
                        document.querySelector('input[name="pilihan_d"]').value = pilihanJawaban.D || '';
                    }
                }
            } else if (currentType === 'pilihan_ganda_kompleks') {
                // Load data for pilihan ganda kompleks
                const pilihanJawaban = <?php echo json_encode($question['pilihan_jawaban'] ?? '{}'); ?>;
                if (pilihanJawaban && typeof pilihanJawaban === 'string') {
                    try {
                        const pilihan = JSON.parse(pilihanJawaban);
                        document.querySelector('input[name="kompleks_a"]').value = pilihan.A || '';
                        document.querySelector('input[name="kompleks_b"]').value = pilihan.B || '';
                        document.querySelector('input[name="kompleks_c"]').value = pilihan.C || '';
                        document.querySelector('input[name="kompleks_d"]').value = pilihan.D || '';
                        document.querySelector('input[name="kompleks_e"]').value = pilihan.E || '';
                        
                        // Set jawaban benar (multiple selection)
                        if (currentAnswer) {
                            const jawabanArray = currentAnswer.split(',');
                            jawabanArray.forEach(jawaban => {
                                const checkbox = document.getElementById('jawaban_' + jawaban.toLowerCase());
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing pilihan jawaban:', e);
                    }
                } else if (pilihanJawaban && typeof pilihanJawaban === 'object') {
                    // Data sudah dalam bentuk object
                    document.querySelector('input[name="kompleks_a"]').value = pilihanJawaban.A || '';
                    document.querySelector('input[name="kompleks_b"]').value = pilihanJawaban.B || '';
                    document.querySelector('input[name="kompleks_c"]').value = pilihanJawaban.C || '';
                    document.querySelector('input[name="kompleks_d"]').value = pilihanJawaban.D || '';
                    document.querySelector('input[name="kompleks_e"]').value = pilihanJawaban.E || '';
                    
                    // Set jawaban benar (multiple selection)
                    if (currentAnswer) {
                        const jawabanArray = currentAnswer.split(',');
                        jawabanArray.forEach(jawaban => {
                            const checkbox = document.getElementById('jawaban_' + jawaban.toLowerCase());
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                }
            }
        });
    </script>
</body>
</html>