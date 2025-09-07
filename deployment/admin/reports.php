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

// Get date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today
$report_type = $_GET['report_type'] ?? 'overview';

// Initialize default values
$daily_activity = [];
$question_type_stats = [];
$overview_stats = [];
$top_students = [];
$error_message = '';

try {
    // Overview Statistics
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM students) as total_students,
            (SELECT COUNT(*) FROM teachers) as total_teachers,
            (SELECT COUNT(*) FROM questions) as total_questions,
            (SELECT COUNT(*) FROM test_sessions WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date') as total_tests_period,
            (SELECT COUNT(*) FROM test_sessions) as total_tests_all,
            (SELECT AVG(total_skor) FROM test_sessions WHERE total_skor IS NOT NULL AND DATE(created_at) BETWEEN '$start_date' AND '$end_date') as avg_score_period
    ");
    $overview_stats = $stmt->fetch();
    
    // Daily test activity for chart
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as test_date,
            COUNT(*) as test_count,
            AVG(total_skor) as avg_score
        FROM test_sessions 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY test_date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_activity = $stmt->fetchAll();
    
    // Top performing students
    $stmt = $pdo->prepare("
        SELECT 
            s.nama_lengkap,
            s.kelas,
            COUNT(ts.id) as total_tests,
            AVG(ts.total_skor) as avg_score,
            MAX(ts.total_skor) as best_score
        FROM students s
        JOIN test_sessions ts ON s.id = ts.student_id
        WHERE DATE(ts.created_at) BETWEEN ? AND ?
        AND ts.total_skor IS NOT NULL
        GROUP BY s.id
        HAVING total_tests >= 1
        ORDER BY avg_score DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_students = $stmt->fetchAll();
    
    // Question usage statistics
    $stmt = $pdo->prepare("
        SELECT 
            q.pertanyaan,
            q.tipe_soal,
            qc.nama_kategori,
            t.nama_lengkap as creator_name,
            COUNT(ts.id) as usage_count
        FROM questions q
        LEFT JOIN question_categories qc ON q.category_id = qc.id
        LEFT JOIN teachers t ON q.created_by = t.id
        LEFT JOIN test_sessions ts ON q.test_id = ts.test_id
        WHERE DATE(ts.created_at) BETWEEN ? AND ?
        GROUP BY q.id
        HAVING usage_count > 0
        ORDER BY usage_count DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $popular_questions = $stmt->fetchAll();
    
    // Class performance
    $stmt = $pdo->prepare("
        SELECT 
            s.kelas,
            COUNT(DISTINCT s.id) as student_count,
            COUNT(ts.id) as total_tests,
            AVG(ts.total_skor) as avg_score,
            MIN(ts.total_skor) as min_score,
            MAX(ts.total_skor) as max_score
        FROM students s
        LEFT JOIN test_sessions ts ON s.id = ts.student_id
        WHERE DATE(ts.created_at) BETWEEN ? AND ?
        AND ts.total_skor IS NOT NULL
        GROUP BY s.kelas
        ORDER BY avg_score DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $class_performance = $stmt->fetchAll();
    
    // Question type analysis
    $stmt = $pdo->prepare("
        SELECT 
            q.tipe_soal,
            COUNT(ts.id) as usage_count,
            AVG(ts.total_skor) as avg_score
        FROM questions q
        JOIN test_sessions ts ON q.test_id = ts.test_id
        WHERE DATE(ts.created_at) BETWEEN ? AND ?
        AND ts.total_skor IS NOT NULL
        GROUP BY q.tipe_soal
        ORDER BY usage_count DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $question_type_stats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

$question_types = [
    'pilihan_ganda' => 'Pilihan Ganda',
    'benar_salah' => 'Benar/Salah',
    'pilihan_ganda_kompleks' => 'Pilihan Ganda Kompleks',
    'isian_singkat' => 'Isian Singkat',
    'drag_drop' => 'Drag & Drop',
    'urutan' => 'Urutan'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Statistik - AKM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
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
                        <a class="nav-link" href="teachers.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Data Guru
                        </a>
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-2"></i> Bank Soal
                        </a>
                        <a class="nav-link" href="create_question.php">
                            <i class="fas fa-plus-circle me-2"></i> Buat Soal
                        </a>
                        <a class="nav-link active" href="reports.php">
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
                        <h2 class="mb-0">Laporan & Statistik</h2>
                        <div>
                            <button class="btn btn-primary" onclick="exportReport()">
                                <i class="fas fa-download me-2"></i>Export PDF
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filter -->
                    <div class="filter-card">
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="start_date" 
                                           value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" name="end_date" 
                                           value="<?= htmlspecialchars($end_date) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Jenis Laporan</label>
                                    <select class="form-select" name="report_type">
                                        <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>Overview</option>
                                        <option value="detailed" <?= $report_type === 'detailed' ? 'selected' : '' ?>>Detail</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Overview Stats -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="metric-icon bg-primary me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($overview_stats['total_students'] ?? 0) ?></h3>
                                        <p class="text-muted mb-0">Total Siswa</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="metric-icon bg-success me-3">
                                        <i class="fas fa-question-circle"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($overview_stats['total_questions'] ?? 0) ?></h3>
                                        <p class="text-muted mb-0">Total Soal</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="metric-icon bg-info me-3">
                                        <i class="fas fa-clipboard-check"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($overview_stats['total_tests_period'] ?? 0) ?></h3>
                                        <p class="text-muted mb-0">Tes Periode Ini</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="metric-icon bg-warning me-3">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($overview_stats['avg_score_period'] ?? 0, 1) ?></h3>
                                        <p class="text-muted mb-0">Rata-rata Skor</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <h5 class="mb-3">Aktivitas Tes Harian</h5>
                                <canvas id="dailyActivityChart" height="100"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="chart-card">
                                <h5 class="mb-3">Distribusi Tipe Soal</h5>
                                <canvas id="questionTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tables Row -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="table-card mb-4">
                                <div class="p-3 border-bottom">
                                    <h5 class="mb-0">Top 10 Siswa Terbaik</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Tes</th>
                                                <th>Rata-rata</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($top_students)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">
                                                        Tidak ada data
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($top_students as $student): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($student['nama_lengkap']) ?></td>
                                                        <td><?= htmlspecialchars($student['kelas'] ?? '-') ?></td>
                                                        <td><?= number_format($student['total_tests']) ?></td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <?= number_format($student['avg_score'] ?? 0, 1) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="table-card mb-4">
                                <div class="p-3 border-bottom">
                                    <h5 class="mb-0">Performa per Kelas</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kelas</th>
                                                <th>Siswa</th>
                                                <th>Tes</th>
                                                <th>Rata-rata</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($class_performance)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">
                                                        Tidak ada data
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($class_performance as $class): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($class['kelas'] ?? '-') ?></td>
                                                        <td><?= number_format($class['student_count']) ?></td>
                                                        <td><?= number_format($class['total_tests']) ?></td>
                                                        <td>
                                                            <span class="badge bg-info">
                                                                <?= number_format($class['avg_score'] ?? 0, 1) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Popular Questions -->
                    <div class="table-card">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0">Soal Paling Populer</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pertanyaan</th>
                                        <th>Tipe</th>
                                        <th>Kategori</th>
                                        <th>Pembuat</th>
                                        <th>Digunakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($popular_questions)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">
                                                Tidak ada data
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($popular_questions as $question): ?>
                                            <tr>
                                                <td>
                                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?= htmlspecialchars($question['pertanyaan']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= $question_types[$question['tipe_soal']] ?? $question['tipe_soal'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($question['nama_kategori'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($question['creator_name'] ?? '-') ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= number_format($question['usage_count']) ?>x
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Activity Chart
        const dailyActivityCtx = document.getElementById('dailyActivityChart').getContext('2d');
        const dailyActivityData = <?= json_encode($daily_activity) ?>;
        
        new Chart(dailyActivityCtx, {
            type: 'line',
            data: {
                labels: dailyActivityData.map(item => item.test_date),
                datasets: [{
                    label: 'Jumlah Tes',
                    data: dailyActivityData.map(item => item.test_count),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Rata-rata Skor',
                    data: dailyActivityData.map(item => item.avg_score),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
        
        // Question Type Chart
        const questionTypeCtx = document.getElementById('questionTypeChart').getContext('2d');
        const questionTypeData = <?= json_encode($question_type_stats) ?>;
        const questionTypes = <?= json_encode($question_types) ?>;
        
        new Chart(questionTypeCtx, {
            type: 'doughnut',
            data: {
                labels: questionTypeData.map(item => questionTypes[item.tipe_soal] || item.tipe_soal),
                datasets: [{
                    data: questionTypeData.map(item => item.usage_count),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
        
        function exportReport() {
            alert('Fitur export PDF akan segera tersedia');
        }
    </script>
</body>
</html>