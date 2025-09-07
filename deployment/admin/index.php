<?php
session_start();
require_once '../config/database.php';
require_once 'auth_config.php';

// Initialize database connection
$pdo = getDBConnection();

// Check authentication
requireAuth();

// Get dashboard statistics
try {
    // Total siswa
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_siswa = $stmt->fetch()['total'];
    
    // Total guru
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM teachers WHERE role = 'teacher'");
    $total_guru = $stmt->fetch()['total'];
    
    // Total soal
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
    $total_soal = $stmt->fetch()['total'];
    
    // Total sesi ujian
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_sessions");
    $total_sesi = $stmt->fetch()['total'];
    
    // Sesi ujian hari ini
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_sessions WHERE DATE(waktu_mulai) = CURDATE()");
    $sesi_hari_ini = $stmt->fetch()['total'];
    
    // Soal per kategori
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN tipe_soal = 'pilihan_ganda' THEN 'Pilihan Ganda'
                WHEN tipe_soal = 'benar_salah' THEN 'Benar/Salah'
                WHEN tipe_soal = 'pilihan_ganda_kompleks' THEN 'Pilihan Ganda Kompleks'
                WHEN tipe_soal = 'isian_singkat' THEN 'Isian Singkat'
                WHEN tipe_soal = 'drag_drop' THEN 'Drag & Drop'
                WHEN tipe_soal = 'urutan' THEN 'Urutan'
                ELSE 'Lainnya'
            END as tipe,
            COUNT(*) as jumlah
        FROM questions 
        GROUP BY tipe_soal
    ");
    $soal_per_tipe = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    // Initialize default values to prevent undefined variable errors
    $total_siswa = 0;
    $total_guru = 0;
    $total_soal = 0;
    $total_sesi = 0;
    $sesi_hari_ini = 0;
    $soal_per_tipe = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - AKM Online Test</title>
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .bg-primary-gradient { background: linear-gradient(45deg, #007bff, #0056b3); }
        .bg-success-gradient { background: linear-gradient(45deg, #28a745, #1e7e34); }
        .bg-warning-gradient { background: linear-gradient(45deg, #ffc107, #e0a800); }
        .bg-info-gradient { background: linear-gradient(45deg, #17a2b8, #138496); }
        .bg-danger-gradient { background: linear-gradient(45deg, #dc3545, #c82333); }
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-users me-2"></i> Data Siswa
                        </a>
                        <a class="nav-link" href="../register.php" target="_blank">
                            <i class="fas fa-user-plus me-2"></i> Registrasi Siswa
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
                        <h2 class="mb-0">Dashboard Overview</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?= date('d F Y') ?>
                        </div>
                    </div>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-primary-gradient me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($total_siswa) ?></h3>
                                        <p class="text-muted mb-0">Total Siswa</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-success-gradient me-3">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($total_guru) ?></h3>
                                        <p class="text-muted mb-0">Total Guru</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-warning-gradient me-3">
                                        <i class="fas fa-question-circle"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($total_soal) ?></h3>
                                        <p class="text-muted mb-0">Total Soal</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-info-gradient me-3">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($total_sesi) ?></h3>
                                        <p class="text-muted mb-0">Total Sesi Ujian</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Activity -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon bg-danger-gradient mx-auto mb-3">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <h3 class="mb-1"><?= number_format($sesi_hari_ini) ?></h3>
                                <p class="text-muted mb-0">Sesi Ujian Hari Ini</p>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="stat-card">
                                <h5 class="mb-3">Distribusi Soal per Tipe</h5>
                                <div class="row">
                                    <?php foreach ($soal_per_tipe as $tipe): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted"><?= htmlspecialchars($tipe['tipe']) ?></span>
                                                <span class="badge bg-primary"><?= number_format($tipe['jumlah']) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="stat-card">
                                <h5 class="mb-3">Aksi Cepat</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="create_question.php" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Buat Soal Baru
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="students.php" class="btn btn-success w-100">
                                            <i class="fas fa-users me-2"></i>Kelola Siswa
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="reports.php" class="btn btn-info w-100">
                                            <i class="fas fa-chart-bar me-2"></i>Lihat Laporan
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="../index.php" class="btn btn-outline-secondary w-100" target="_blank">
                                            <i class="fas fa-external-link-alt me-2"></i>Lihat Situs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>