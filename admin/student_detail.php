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

$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header('Location: students.php');
    exit();
}

// Handle form submission for updating student data
if ($_POST['action'] ?? '' === 'update_student') {
    try {
        $stmt = $pdo->prepare("
            UPDATE users SET 
                nama_lengkap = ?,
                email = ?,
                nis = ?,
                kelas = ?,
                sekolah = ?,
                tahun_ajaran = ?,
                jenis_kelamin = ?,
                tanggal_lahir = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $_POST['nama_lengkap'],
            $_POST['email'],
            $_POST['nis'],
            $_POST['kelas'],
            $_POST['sekolah'],
            $_POST['tahun_ajaran'],
            $_POST['jenis_kelamin'],
            $_POST['tanggal_lahir'],
            $student_id
        ]);
        
        if ($result) {
            $success_message = "Data siswa berhasil diperbarui!";
        } else {
            $error_message = "Gagal memperbarui data siswa.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

try {
    // Get student data
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            COUNT(ts.id) as total_tests,
            AVG(ts.total_skor) as avg_score,
            MAX(ts.waktu_mulai) as last_test,
            MIN(ts.waktu_mulai) as first_test
        FROM users u
        LEFT JOIN test_sessions ts ON u.id = ts.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: students.php');
        exit();
    }
    
    // Get test history
    $stmt = $pdo->prepare("
        SELECT 
            ts.*,
            t.nama_tes
        FROM test_sessions ts
        LEFT JOIN tests t ON ts.test_id = t.id
        WHERE ts.user_id = ?
        ORDER BY ts.waktu_mulai DESC
        LIMIT 10
    ");
    $stmt->execute([$student_id]);
    $test_history = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Generate year options from 1972 to current year
$current_year = date('Y');
$years = [];
for ($year = $current_year; $year >= 1972; $year--) {
    $years[] = $year;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Siswa - <?= htmlspecialchars($student['nama_lengkap'] ?? 'Unknown') ?> - AKM Admin</title>
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
        .detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .badge-score {
            font-size: 0.9em;
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
                        <a class="nav-link active" href="students.php">
                            <i class="fas fa-users me-2"></i> Data Siswa
                        </a>
                        <a class="nav-link" href="teachers.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Data Guru
                        </a>
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i> Bank Soal
                        </a>
                        <a class="nav-link" href="create_question.php">
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
                        <div>
                            <h2 class="mb-0">Detail Siswa</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="students.php">Data Siswa</a></li>
                                    <li class="breadcrumb-item active"><?= htmlspecialchars($student['nama_lengkap']) ?></li>
                                </ol>
                            </nav>
                        </div>
                        <a href="students.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Profile Card -->
                        <div class="col-lg-4">
                            <div class="detail-card mb-4">
                                <div class="profile-header">
                                    <div class="profile-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h4 class="mb-1"><?= htmlspecialchars($student['nama_lengkap']) ?></h4>
                                    <p class="mb-0 opacity-75"><?= htmlspecialchars($student['username']) ?></p>
                                    <?php if ($student['kelas']): ?>
                                        <span class="badge bg-light text-dark mt-2"><?= htmlspecialchars($student['kelas']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="p-4">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="stats-number"><?= number_format($student['total_tests']) ?></div>
                                            <small class="text-muted">Total Ujian</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="stats-number">
                                                <?php if ($student['avg_score']): ?>
                                                    <?= round($student['avg_score'], 1) ?>%
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">Rata-rata</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="stats-number">
                                                <?php 
                                                if ($student['first_test'] && $student['last_test']) {
                                                    $days = (strtotime($student['last_test']) - strtotime($student['first_test'])) / (60 * 60 * 24);
                                                    echo max(1, round($days));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted">Hari Aktif</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detail Form -->
                        <div class="col-lg-8">
                            <div class="detail-card">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0">Informasi Siswa</h5>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="toggleEdit()">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                    </div>
                                    
                                    <form method="POST" id="studentForm">
                                        <input type="hidden" name="action" value="update_student">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($student['username']) ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">NIS</label>
                                                <input type="text" class="form-control" name="nis" value="<?= htmlspecialchars($student['nis'] ?? '') ?>" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Nama Lengkap *</label>
                                                <input type="text" class="form-control" name="nama_lengkap" value="<?= htmlspecialchars($student['nama_lengkap']) ?>" required readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Kelas *</label>
                                                <select class="form-select" name="kelas" required disabled>
                                                    <option value="">Pilih Kelas</option>
                                                    <option value="SD/MI/PAKET A" <?= $student['kelas'] === 'SD/MI/PAKET A' ? 'selected' : '' ?>>SD/MI/PAKET A</option>
                                                    <option value="SMP/MTs/PAKET B" <?= $student['kelas'] === 'SMP/MTs/PAKET B' ? 'selected' : '' ?>>SMP/MTs/PAKET B</option>
                                                    <option value="SMA/MA/SMK/PAKET C" <?= $student['kelas'] === 'SMA/MA/SMK/PAKET C' ? 'selected' : '' ?>>SMA/MA/SMK/PAKET C</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Jenis Kelamin *</label>
                                                <select class="form-select" name="jenis_kelamin" required disabled>
                                                    <option value="Laki-laki" <?= $student['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                                    <option value="Perempuan" <?= $student['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Sekolah</label>
                                                <input type="text" class="form-control" name="sekolah" value="<?= htmlspecialchars($student['sekolah'] ?? '') ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Tahun Ajaran</label>
                                                <input type="text" class="form-control" name="tahun_ajaran" value="<?= htmlspecialchars($student['tahun_ajaran'] ?? '') ?>" placeholder="2024/2025" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Lahir *</label>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <select class="form-select" name="birth_day" required disabled>
                                                        <option value="">Tanggal</option>
                                                        <?php for ($day = 1; $day <= 31; $day++): ?>
                                                            <?php $selected = (date('j', strtotime($student['tanggal_lahir'])) == $day) ? 'selected' : ''; ?>
                                                            <option value="<?= sprintf('%02d', $day) ?>" <?= $selected ?>><?= $day ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <select class="form-select" name="birth_month" required disabled>
                                                        <option value="">Bulan</option>
                                                        <?php 
                                                        $months = [
                                                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                                            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                                            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                                            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                                        ];
                                                        foreach ($months as $num => $name):
                                                            $selected = (date('m', strtotime($student['tanggal_lahir'])) == $num) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?= $num ?>" <?= $selected ?>><?= $name ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <select class="form-select" name="birth_year" required disabled>
                                                        <option value="">Tahun</option>
                                                        <?php foreach ($years as $year): ?>
                                                            <?php $selected = (date('Y', strtotime($student['tanggal_lahir'])) == $year) ? 'selected' : ''; ?>
                                                            <option value="<?= $year ?>" <?= $selected ?>><?= $year ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <input type="hidden" name="tanggal_lahir" value="<?= $student['tanggal_lahir'] ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Terdaftar</label>
                                                <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($student['created_at'])) ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Login Terakhir</label>
                                                <input type="text" class="form-control" value="<?= $student['last_login'] ? date('d/m/Y H:i', strtotime($student['last_login'])) : 'Belum pernah login' ?>" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="d-none" id="editButtons">
                                            <hr>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                                                    <i class="fas fa-times me-1"></i> Batal
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test History -->
                    <?php if (!empty($test_history)): ?>
                        <div class="detail-card mt-4">
                            <div class="p-4">
                                <h5 class="mb-4">Riwayat Ujian (10 Terakhir)</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Ujian</th>
                                                <th>Waktu Mulai</th>
                                                <th>Waktu Selesai</th>
                                                <th>Durasi</th>
                                                <th>Skor</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($test_history as $index => $test): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($test['nama_tes'] ?? 'Ujian #' . $test['test_id']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($test['waktu_mulai'])) ?></td>
                                                    <td>
                                                        <?= $test['waktu_selesai'] ? date('d/m/Y H:i', strtotime($test['waktu_selesai'])) : '-' ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($test['waktu_selesai']): ?>
                                                            <?php 
                                                            $duration = strtotime($test['waktu_selesai']) - strtotime($test['waktu_mulai']);
                                                            $minutes = floor($duration / 60);
                                                            $seconds = $duration % 60;
                                                            echo sprintf('%d:%02d', $minutes, $seconds);
                                                            ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($test['total_skor'] !== null): ?>
                                                            <?php 
                                                            $score = round($test['total_skor'], 1);
                                                            $badge_class = $score >= 80 ? 'bg-success' : ($score >= 60 ? 'bg-warning' : 'bg-danger');
                                                            ?>
                                                            <span class="badge <?= $badge_class ?> badge-score"><?= $score ?>%</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($test['waktu_selesai']): ?>
                                                            <span class="badge bg-success">Selesai</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Belum Selesai</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleEdit() {
            const form = document.getElementById('studentForm');
            const inputs = form.querySelectorAll('input[readonly], select[disabled]');
            const editButtons = document.getElementById('editButtons');
            
            inputs.forEach(input => {
                if (input.name !== 'username' && input.name !== 'created_at' && input.name !== 'last_login') {
                    if (input.hasAttribute('readonly')) {
                        input.removeAttribute('readonly');
                    }
                    if (input.hasAttribute('disabled')) {
                        input.removeAttribute('disabled');
                    }
                }
            });
            
            editButtons.classList.remove('d-none');
        }
        
        function cancelEdit() {
            location.reload();
        }
        
        // Update hidden tanggal_lahir field when date selects change
        document.addEventListener('change', function(e) {
            if (e.target.name === 'birth_day' || e.target.name === 'birth_month' || e.target.name === 'birth_year') {
                const day = document.querySelector('[name="birth_day"]').value;
                const month = document.querySelector('[name="birth_month"]').value;
                const year = document.querySelector('[name="birth_year"]').value;
                
                if (day && month && year) {
                    document.querySelector('[name="tanggal_lahir"]').value = year + '-' + month + '-' + day;
                }
            }
        });
    </script>
</body>
</html>