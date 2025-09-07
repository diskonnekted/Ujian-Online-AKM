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

// Get teacher ID from URL
$teacher_id = intval($_GET['id'] ?? 0);
if ($teacher_id <= 0) {
    header('Location: teachers.php');
    exit();
}

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_teacher') {
        $username = trim($_POST['username'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
        $role = $_POST['role'] ?? 'teacher';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($nama_lengkap) || empty($email)) {
            $error_message = 'Username, nama lengkap, dan email harus diisi.';
        } else {
            try {
                // Check if username or email already exists (excluding current teacher)
                $stmt = $pdo->prepare("SELECT id FROM teachers WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $teacher_id]);
                if ($stmt->fetch()) {
                    $error_message = 'Username atau email sudah digunakan oleh guru lain.';
                } else {
                    // Update teacher data
                    if (!empty($password)) {
                        // Update with new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE teachers SET 
                                username = ?, password = ?, nama_lengkap = ?, email = ?, 
                                nip = ?, mata_pelajaran = ?, role = ?, status = ?, 
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $hashed_password, $nama_lengkap, $email, $nip, $mata_pelajaran, $role, $status, $teacher_id]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("
                            UPDATE teachers SET 
                                username = ?, nama_lengkap = ?, email = ?, 
                                nip = ?, mata_pelajaran = ?, role = ?, status = ?, 
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $nama_lengkap, $email, $nip, $mata_pelajaran, $role, $status, $teacher_id]);
                    }
                    $success_message = 'Data guru berhasil diperbarui.';
                }
            } catch (PDOException $e) {
                $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Get teacher data with statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            COUNT(DISTINCT q.id) as total_questions,
            COUNT(DISTINCT CASE WHEN q.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN q.id END) as questions_last_month,
            COUNT(DISTINCT CASE WHEN q.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN q.id END) as questions_last_week,
            0 as total_tests_created,
            0 as total_test_sessions,
            0 as avg_student_score
        FROM teachers t
        LEFT JOIN questions q ON t.id = q.created_by
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        header('Location: teachers.php');
        exit();
    }
    
    // Get recent questions created by this teacher
    $stmt = $pdo->prepare("
        SELECT q.*
        FROM questions q
        WHERE q.created_by = ?
        ORDER BY q.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$teacher_id]);
    $recent_questions = $stmt->fetchAll();
    
    // Get tests (since tests table doesn't have created_by column, we'll show empty for now)
    $teacher_tests = [];
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Guru - <?= htmlspecialchars($teacher['nama_lengkap'] ?? 'Unknown') ?> - AKM Admin</title>
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
        .info-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
        }
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
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
                        <a class="nav-link active" href="teachers.php">
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
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="teachers.php">Data Guru</a></li>
                                    <li class="breadcrumb-item active">Detail Guru</li>
                                </ol>
                            </nav>
                            <h2 class="mb-0">Detail Guru</h2>
                        </div>
                        <div>
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editTeacherModal">
                                <i class="fas fa-edit me-2"></i>Edit Data
                            </button>
                            <a href="teachers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
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
                    
                    <!-- Profile Header -->
                    <div class="info-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="mb-2"><?= htmlspecialchars($teacher['nama_lengkap']) ?></h3>
                            <p class="mb-2">
                                <span class="badge <?= $teacher['role'] === 'admin' ? 'bg-danger' : 'bg-light text-dark' ?> me-2">
                                    <?= ucfirst($teacher['role']) ?>
                                </span>
                                <span class="badge <?= $teacher['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $teacher['status'] === 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                </span>
                            </p>
                            <p class="mb-0"><?= htmlspecialchars($teacher['mata_pelajaran'] ?? 'Tidak ada mata pelajaran') ?></p>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($teacher['total_questions']) ?></div>
                                <div>Total Soal Dibuat</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($teacher['questions_last_month']) ?></div>
                                <div>Soal Bulan Ini</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($teacher['total_tests_created']) ?></div>
                                <div>Tes Dibuat</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= $teacher['avg_student_score'] ? number_format($teacher['avg_student_score'], 1) : '0' ?></div>
                                <div>Rata-rata Nilai Siswa</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Teacher Information -->
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Informasi Guru
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Username:</strong></td>
                                            <td><?= htmlspecialchars($teacher['username']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Nama Lengkap:</strong></td>
                                            <td><?= htmlspecialchars($teacher['nama_lengkap']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?= htmlspecialchars($teacher['email'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>NIP:</strong></td>
                                            <td><?= htmlspecialchars($teacher['nip'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mata Pelajaran:</strong></td>
                                            <td><?= htmlspecialchars($teacher['mata_pelajaran'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Role:</strong></td>
                                            <td>
                                                <span class="badge <?= $teacher['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                    <?= ucfirst($teacher['role']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge <?= $teacher['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $teacher['status'] === 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Terdaftar:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($teacher['created_at'])) ?></td>
                                        </tr>
                                        <?php if ($teacher['updated_at']): ?>
                                        <tr>
                                            <td><strong>Terakhir Update:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($teacher['updated_at'])) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Summary -->
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Ringkasan Aktivitas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="border rounded p-3">
                                                <h4 class="text-primary mb-1"><?= number_format($teacher['total_questions']) ?></h4>
                                                <small class="text-muted">Total Soal</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="border rounded p-3">
                                                <h4 class="text-warning mb-1"><?= number_format($teacher['questions_last_month']) ?></h4>
                                                <small class="text-muted">Soal Bulan Ini</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="border rounded p-3">
                                                <h4 class="text-info mb-1"><?= number_format($teacher['questions_last_week']) ?></h4>
                                                <small class="text-muted">Soal Minggu Ini</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="border rounded p-3">
                                                <h4 class="text-success mb-1"><?= number_format($teacher['total_tests_created']) ?></h4>
                                                <small class="text-muted">Tes Dibuat</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="border rounded p-3">
                                            <h4 class="text-danger mb-1"><?= number_format($teacher['total_test_sessions']) ?></h4>
                                            <small class="text-muted">Total Sesi Tes Siswa</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Questions -->
                    <div class="info-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i>Soal Terbaru (10 Terakhir)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_questions)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Belum ada soal yang dibuat</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Pertanyaan</th>
                                                <th>Kompetensi</th>
                                                <th>Tingkat</th>
                                                <th>Dibuat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_questions as $index => $question): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 300px;">
                                                            <?= htmlspecialchars(substr($question['question_text'], 0, 100)) ?>
                                                            <?= strlen($question['question_text']) > 100 ? '...' : '' ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= htmlspecialchars($question['competency_name'] ?? 'Tidak ada') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?= ucfirst($question['difficulty_level']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?= date('d/m/Y', strtotime($question['created_at'])) ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Teacher Tests -->
                    <div class="info-card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list me-2"></i>Tes yang Dibuat (10 Terakhir)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teacher_tests)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Belum ada tes yang dibuat</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Tes</th>
                                                <th>Durasi</th>
                                                <th>Total Sesi</th>
                                                <th>Rata-rata Nilai</th>
                                                <th>Dibuat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teacher_tests as $index => $test): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($test['nama_tes']) ?></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?= $test['durasi'] ?> menit
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?= number_format($test['total_sessions']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <?= $test['avg_score'] ? number_format($test['avg_score'], 1) : '0' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?= date('d/m/Y', strtotime($test['created_at'])) ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Data Guru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_teacher">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="edit_username" name="username" 
                                           value="<?= htmlspecialchars($teacher['username']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="edit_password" name="password" 
                                           placeholder="Kosongkan jika tidak ingin mengubah password">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_nama_lengkap" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="edit_nama_lengkap" name="nama_lengkap" 
                                           value="<?= htmlspecialchars($teacher['nama_lengkap']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" 
                                           value="<?= htmlspecialchars($teacher['email']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_nip" class="form-label">NIP</label>
                                    <input type="text" class="form-control" id="edit_nip" name="nip" 
                                           value="<?= htmlspecialchars($teacher['nip'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_mata_pelajaran" class="form-label">Mata Pelajaran</label>
                                    <input type="text" class="form-control" id="edit_mata_pelajaran" name="mata_pelajaran" 
                                           value="<?= htmlspecialchars($teacher['mata_pelajaran'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Role</label>
                                    <select class="form-select" id="edit_role" name="role">
                                        <option value="teacher" <?= $teacher['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                        <option value="admin" <?= $teacher['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_status" name="status">
                                        <option value="active" <?= $teacher['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="inactive" <?= $teacher['status'] === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>