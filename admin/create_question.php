<?php
session_start();
require_once '../config/database.php';

// Initialize database connection
$pdo = getDBConnection();

// Check if user is logged in as admin/teacher
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe_soal = $_POST['tipe_soal'] ?? '';
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $tingkat_kesulitan = $_POST['tingkat_kesulitan'] ?? 'sedang';
    $gambar = '';
    
    // Handle image upload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/questions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                $gambar = 'uploads/questions/' . $filename;
            }
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Prepare question data based on type
        $jawaban_benar = '';
        $pilihan_jawaban = '';
        $penjelasan = trim($_POST['penjelasan'] ?? '');
        
        switch ($tipe_soal) {
            case 'pilihan_ganda':
                $jawaban_benar = $_POST['jawaban_benar'] ?? '';
                $pilihan = [];
                for ($i = 1; $i <= 5; $i++) {
                    if (!empty($_POST["pilihan_$i"])) {
                        $pilihan[] = trim($_POST["pilihan_$i"]);
                    }
                }
                $pilihan_jawaban = json_encode($pilihan);
                break;
                
            case 'benar_salah':
                $jawaban_benar = $_POST['jawaban_benar'] ?? 'benar';
                $pilihan_jawaban = json_encode(['Benar', 'Salah']);
                break;
                
            case 'pilihan_ganda_kompleks':
                $jawaban_benar = json_encode($_POST['jawaban_benar'] ?? []);
                $pilihan = [];
                for ($i = 1; $i <= 6; $i++) {
                    if (!empty($_POST["pilihan_$i"])) {
                        $pilihan[] = trim($_POST["pilihan_$i"]);
                    }
                }
                $pilihan_jawaban = json_encode($pilihan);
                break;
                
            case 'isian_singkat':
                $jawaban_benar = trim($_POST['jawaban_benar'] ?? '');
                $alternatif_jawaban = [];
                for ($i = 1; $i <= 3; $i++) {
                    if (!empty($_POST["alternatif_$i"])) {
                        $alternatif_jawaban[] = trim($_POST["alternatif_$i"]);
                    }
                }
                if (!empty($alternatif_jawaban)) {
                    $pilihan_jawaban = json_encode($alternatif_jawaban);
                }
                break;
                
            case 'drag_drop':
                $items = [];
                $targets = [];
                for ($i = 1; $i <= 5; $i++) {
                    if (!empty($_POST["item_$i"]) && !empty($_POST["target_$i"])) {
                        $items[] = trim($_POST["item_$i"]);
                        $targets[] = trim($_POST["target_$i"]);
                    }
                }
                $pilihan_jawaban = json_encode(['items' => $items, 'targets' => $targets]);
                $jawaban_benar = json_encode(array_combine($items, $targets));
                break;
                
            case 'urutan':
                $urutan_items = [];
                for ($i = 1; $i <= 6; $i++) {
                    if (!empty($_POST["urutan_$i"])) {
                        $urutan_items[] = trim($_POST["urutan_$i"]);
                    }
                }
                $pilihan_jawaban = json_encode($urutan_items);
                $jawaban_benar = json_encode(range(1, count($urutan_items)));
                break;
                
            case 'essay':
                $poin_kunci = trim($_POST['poin_kunci'] ?? '');
                $skor_maksimal = intval($_POST['skor_maksimal'] ?? 10);
                $rubrik_penilaian = trim($_POST['rubrik_penilaian'] ?? '');
                
                $jawaban_benar = $poin_kunci;
                $pilihan_jawaban = json_encode([
                    'skor_maksimal' => $skor_maksimal,
                    'rubrik_penilaian' => $rubrik_penilaian
                ]);
                break;
        }
        
        // Insert question
        $stmt = $pdo->prepare("
            INSERT INTO questions (tipe_soal, pertanyaan, pilihan_jawaban, jawaban_benar, 
                                 penjelasan, gambar, category_id, tingkat_kesulitan, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $tipe_soal, $pertanyaan, $pilihan_jawaban, $jawaban_benar,
            $penjelasan, $gambar, $category_id ?: null, $tingkat_kesulitan, $_SESSION['admin_id']
        ]);
        
        $question_id = $pdo->lastInsertId();
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description) VALUES (?, 'create_question', ?)");
        $stmt->execute([$_SESSION['admin_id'], "Membuat soal baru: $pertanyaan"]);
        
        $pdo->commit();
        $success_message = 'Soal berhasil dibuat!';
        
        // Reset form
        $_POST = [];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = 'Gagal membuat soal: ' . $e->getMessage();
    }
}

// Get categories
try {
    $stmt = $pdo->query("SELECT DISTINCT id, nama_kategori, deskripsi, created_at FROM question_categories GROUP BY nama_kategori ORDER BY nama_kategori");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Soal - AKM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .question-type-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .question-type-card:hover {
            border-color: #007bff;
            box-shadow: 0 5px 15px rgba(0,123,255,0.1);
        }
        .question-type-card.active {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .dynamic-form {
            display: none;
        }
        .dynamic-form.active {
            display: block;
        }
        .option-input {
            margin-bottom: 10px;
        }
        .drag-drop-pair {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4">
                        <h4 class="text-white mb-4">
                            <i class="fas fa-graduation-cap me-2"></i>
                            AKM Admin
                        </h4>
                        <div class="text-white-50 mb-4">
                            Selamat datang, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                        </div>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-users me-2"></i> Data Siswa
                        </a>
                        <a class="nav-link" href="teachers.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Data Guru
                        </a>
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i> Bank Soal
                        </a>
                        <a class="nav-link active" href="create_question.php">
                            <i class="fas fa-plus-circle me-2"></i> Buat Soal
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Laporan
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i> Pengaturan
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Buat Soal Baru</h2>
                        <a href="questions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Bank Soal
                        </a>
                    </div>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Question Type Selection -->
                        <div class="form-card mb-4">
                            <h5 class="mb-3">1. Pilih Tipe Soal</h5>
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="pilihan_ganda">
                                        <div class="text-center">
                                            <i class="fas fa-list-ul fa-2x text-primary mb-2"></i>
                                            <h6>Pilihan Ganda</h6>
                                            <small class="text-muted">Satu jawaban benar dari beberapa pilihan</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="benar_salah">
                                        <div class="text-center">
                                            <i class="fas fa-check-double fa-2x text-success mb-2"></i>
                                            <h6>Benar/Salah</h6>
                                            <small class="text-muted">Pilih benar atau salah</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="pilihan_ganda_kompleks">
                                        <div class="text-center">
                                            <i class="fas fa-tasks fa-2x text-info mb-2"></i>
                                            <h6>Pilihan Ganda Kompleks</h6>
                                            <small class="text-muted">Beberapa jawaban benar</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="isian_singkat">
                                        <div class="text-center">
                                            <i class="fas fa-edit fa-2x text-warning mb-2"></i>
                                            <h6>Isian Singkat</h6>
                                            <small class="text-muted">Jawaban berupa teks singkat</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="drag_drop">
                                        <div class="text-center">
                                            <i class="fas fa-arrows-alt fa-2x text-danger mb-2"></i>
                                            <h6>Drag & Drop</h6>
                                            <small class="text-muted">Seret dan lepas item</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="urutan">
                                        <div class="text-center">
                                            <i class="fas fa-sort-numeric-down fa-2x text-secondary mb-2"></i>
                                            <h6>Urutan</h6>
                                            <small class="text-muted">Susun urutan yang benar</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="question-type-card" data-type="essay">
                                        <div class="text-center">
                                            <i class="fas fa-file-alt fa-2x text-dark mb-2"></i>
                                            <h6>Essay/Uraian</h6>
                                            <small class="text-muted">Jawaban berupa uraian panjang</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="tipe_soal" id="tipe_soal" required>
                        </div>
                        
                        <!-- Basic Question Info -->
                        <div class="form-card mb-4">
                            <h5 class="mb-3">2. Informasi Dasar Soal</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Kategori</label>
                                        <select class="form-select" name="category_id">
                                            <option value="">Pilih Kategori (Opsional)</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['nama_kategori']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tingkat_kesulitan" class="form-label">Tingkat Kesulitan</label>
                                        <select class="form-select" name="tingkat_kesulitan" required>
                                            <option value="mudah">Mudah</option>
                                            <option value="sedang" selected>Sedang</option>
                                            <option value="sulit">Sulit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pertanyaan" class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="pertanyaan" id="pertanyaan" rows="4" 
                                          placeholder="Masukkan pertanyaan soal..." required><?= htmlspecialchars($_POST['pertanyaan'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gambar" class="form-label">Gambar Pendukung (Opsional)</label>
                                <input type="file" class="form-control" name="gambar" id="gambar" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif">
                                <div class="form-text">Format yang didukung: JPG, PNG, GIF. Maksimal 2MB.</div>
                            </div>
                        </div>
                        
                        <!-- Dynamic Forms for Each Question Type -->
                        
                        <!-- Pilihan Ganda -->
                        <div class="form-card mb-4 dynamic-form" id="form_pilihan_ganda">
                            <h5 class="mb-3">3. Pilihan Jawaban</h5>
                            <div id="pilihan_ganda_options">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="option-input">
                                        <div class="input-group">
                                            <span class="input-group-text"><?= chr(64 + $i) ?></span>
                                            <input type="text" class="form-control" name="pilihan_<?= $i ?>" 
                                                   placeholder="Pilihan <?= chr(64 + $i) ?>" <?= $i <= 2 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Jawaban Benar</label>
                                <select class="form-select" name="jawaban_benar">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Benar/Salah -->
                        <div class="form-card mb-4 dynamic-form" id="form_benar_salah">
                            <h5 class="mb-3">3. Jawaban</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jawaban_benar" value="benar" id="benar" checked>
                                <label class="form-check-label" for="benar">
                                    <i class="fas fa-check text-success me-2"></i>Benar
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jawaban_benar" value="salah" id="salah">
                                <label class="form-check-label" for="salah">
                                    <i class="fas fa-times text-danger me-2"></i>Salah
                                </label>
                            </div>
                        </div>
                        
                        <!-- Pilihan Ganda Kompleks -->
                        <div class="form-card mb-4 dynamic-form" id="form_pilihan_ganda_kompleks">
                            <h5 class="mb-3">3. Pilihan Jawaban</h5>
                            <div id="pilihan_kompleks_options">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <div class="option-input">
                                        <div class="input-group">
                                            <div class="input-group-text">
                                                <input class="form-check-input" type="checkbox" name="jawaban_benar[]" value="<?= chr(64 + $i) ?>">
                                            </div>
                                            <span class="input-group-text"><?= chr(64 + $i) ?></span>
                                            <input type="text" class="form-control" name="pilihan_<?= $i ?>" 
                                                   placeholder="Pilihan <?= chr(64 + $i) ?>" <?= $i <= 3 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="form-text">Centang pilihan yang benar (bisa lebih dari satu)</div>
                        </div>
                        
                        <!-- Isian Singkat -->
                        <div class="form-card mb-4 dynamic-form" id="form_isian_singkat">
                            <h5 class="mb-3">3. Jawaban</h5>
                            <div class="mb-3">
                                <label class="form-label">Jawaban Utama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="jawaban_benar" 
                                       placeholder="Jawaban yang benar" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alternatif Jawaban (Opsional)</label>
                                <input type="text" class="form-control mb-2" name="alternatif_1" 
                                       placeholder="Alternatif jawaban 1">
                                <input type="text" class="form-control mb-2" name="alternatif_2" 
                                       placeholder="Alternatif jawaban 2">
                                <input type="text" class="form-control" name="alternatif_3" 
                                       placeholder="Alternatif jawaban 3">
                                <div class="form-text">Jawaban alternatif yang juga dianggap benar</div>
                            </div>
                        </div>
                        
                        <!-- Drag & Drop -->
                        <div class="form-card mb-4 dynamic-form" id="form_drag_drop">
                            <h5 class="mb-3">3. Item dan Target</h5>
                            <div id="drag_drop_pairs">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="drag-drop-pair">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Item <?= $i ?></label>
                                                <input type="text" class="form-control" name="item_<?= $i ?>" 
                                                       placeholder="Item yang akan diseret" <?= $i <= 3 ? 'required' : '' ?>>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Target <?= $i ?></label>
                                                <input type="text" class="form-control" name="target_<?= $i ?>" 
                                                       placeholder="Target tempat item diletakkan" <?= $i <= 3 ? 'required' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Urutan -->
                        <div class="form-card mb-4 dynamic-form" id="form_urutan">
                            <h5 class="mb-3">3. Item Urutan</h5>
                            <div class="mb-3">
                                <div class="form-text mb-3">Masukkan item dalam urutan yang benar (dari atas ke bawah)</div>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><?= $i ?></span>
                                        <input type="text" class="form-control" name="urutan_<?= $i ?>" 
                                               placeholder="Item urutan ke-<?= $i ?>" <?= $i <= 3 ? 'required' : '' ?>>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Essay/Uraian -->
                        <div class="form-card mb-4 dynamic-form" id="form_essay">
                            <h5 class="mb-3">3. Kriteria Penilaian</h5>
                            <div class="mb-3">
                                <label class="form-label">Poin Kunci Jawaban <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="poin_kunci" rows="4" 
                                          placeholder="Masukkan poin-poin kunci yang harus ada dalam jawaban siswa..." required></textarea>
                                <div class="form-text">Jelaskan poin-poin utama yang harus ada dalam jawaban siswa</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Skor Maksimal</label>
                                <input type="number" class="form-control" name="skor_maksimal" 
                                       value="10" min="1" max="100" placeholder="10">
                                <div class="form-text">Skor maksimal untuk soal ini (1-100)</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rubrik Penilaian (Opsional)</label>
                                <textarea class="form-control" name="rubrik_penilaian" rows="3" 
                                          placeholder="Contoh: Skor 8-10: Jawaban lengkap dan tepat, Skor 6-7: Jawaban cukup baik, dll..."></textarea>
                                <div class="form-text">Panduan penilaian untuk membantu koreksi manual</div>
                            </div>
                        </div>
                        
                        <!-- Explanation -->
                        <div class="form-card mb-4">
                            <h5 class="mb-3">4. Penjelasan (Opsional)</h5>
                            <textarea class="form-control" name="penjelasan" rows="3" 
                                      placeholder="Penjelasan jawaban atau pembahasan soal..."><?= htmlspecialchars($_POST['penjelasan'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Simpan Soal
                            </button>
                            <a href="questions.php" class="btn btn-outline-secondary btn-lg px-5 ms-3">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeCards = document.querySelectorAll('.question-type-card');
            const dynamicForms = document.querySelectorAll('.dynamic-form');
            const typeInput = document.getElementById('tipe_soal');
            
            typeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const type = this.dataset.type;
                    
                    // Remove active class from all cards
                    typeCards.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked card
                    this.classList.add('active');
                    
                    // Hide all dynamic forms
                    dynamicForms.forEach(form => form.classList.remove('active'));
                    
                    // Show selected form
                    const selectedForm = document.getElementById(`form_${type}`);
                    if (selectedForm) {
                        selectedForm.classList.add('active');
                    }
                    
                    // Set hidden input value
                    typeInput.value = type;
                });
            });
            
            // File upload validation
            const fileInput = document.getElementById('gambar');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        if (file.size > 2 * 1024 * 1024) { // 2MB
                            alert('Ukuran file terlalu besar. Maksimal 2MB.');
                            this.value = '';
                        }
                    }
                });
            }
            
            // Form submission validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const tipeInput = document.getElementById('tipe_soal');
                    const pertanyaanInput = document.querySelector('textarea[name="pertanyaan"]');
                    
                    // Check if question type is selected
                    if (!tipeInput.value) {
                        e.preventDefault();
                        alert('Silakan pilih tipe soal terlebih dahulu!');
                        return false;
                    }
                    
                    // Check if question text is filled
                    if (!pertanyaanInput.value.trim()) {
                        e.preventDefault();
                        alert('Silakan isi pertanyaan soal!');
                        pertanyaanInput.focus();
                        return false;
                    }
                    
                    // Additional validation based on question type
                    const activeForm = document.querySelector('.dynamic-form.active');
                    if (activeForm) {
                        const requiredInputs = activeForm.querySelectorAll('input[required], select[required]');
                        for (let input of requiredInputs) {
                            if (!input.value.trim()) {
                                e.preventDefault();
                                alert('Silakan lengkapi semua field yang wajib diisi!');
                                input.focus();
                                return false;
                            }
                        }
                        
                        // Special validation for isian singkat
                        if (tipeInput.value === 'isian_singkat') {
                            const jawabanBenar = activeForm.querySelector('input[name="jawaban_benar"]');
                            if (!jawabanBenar || !jawabanBenar.value.trim()) {
                                e.preventDefault();
                                alert('Silakan isi jawaban yang benar untuk soal isian singkat!');
                                if (jawabanBenar) jawabanBenar.focus();
                                return false;
                            }
                        }
                    }
                    
                    console.log('Form validation passed, submitting...');
                });
            }
        });
    </script>
</body>
</html>