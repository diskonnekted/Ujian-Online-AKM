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

// Handle search and filter
$search = $_GET['search'] ?? '';
$filter_class = $_GET['class'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.nama_lengkap LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filter_class)) {
    $where_conditions[] = "u.kelas = ?";
    $params[] = $filter_class;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM users u $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_students = $stmt->fetch()['total'];
    $total_pages = ceil($total_students / $limit);
    
    // Get students data
    $query = "
        SELECT 
            u.*,
            COUNT(ts.id) as total_tests,
            AVG(ts.total_skor) as avg_score,
            MAX(ts.waktu_mulai) as last_test
        FROM users u
        LEFT JOIN test_sessions ts ON u.id = ts.user_id
        $where_clause
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    // Get unique classes for filter
    $stmt = $pdo->query("SELECT DISTINCT kelas FROM users WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas");
    $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - AKM Admin</title>
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
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .search-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-score {
            font-size: 0.8em;
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
                        <h2 class="mb-0">Data Siswa</h2>
                        <div>
                            <span class="badge bg-primary fs-6"><?= number_format($total_students) ?> Total Siswa</span>
                        </div>
                    </div>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Search and Filter -->
                    <div class="search-card">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" name="search" 
                                               value="<?= htmlspecialchars($search) ?>" 
                                               placeholder="Cari nama, username, atau email...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="class">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= htmlspecialchars($class) ?>" 
                                                    <?= $filter_class === $class ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($class) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i> Cari
                                        </button>
                                        <a href="students.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Students Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Kelas</th>
                                        <th>Sekolah</th>
                                        <th>Total Ujian</th>
                                        <th>Rata-rata Skor</th>
                                        <th>Ujian Terakhir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada data siswa ditemukan</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $index => $student): ?>
                                            <tr>
                                                <td><?= $offset + $index + 1 ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($student['username']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($student['nama_lengkap']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($student['email'] ?? '-') ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($student['kelas']): ?>
                                                        <span class="badge bg-info"><?= htmlspecialchars($student['kelas']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($student['sekolah'] ?? '-') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary badge-score">
                                                        <?= number_format($student['total_tests']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($student['avg_score']): ?>
                                                        <?php 
                                                        $avg = round($student['avg_score'], 1);
                                                        $badge_class = $avg >= 80 ? 'bg-success' : ($avg >= 60 ? 'bg-warning' : 'bg-danger');
                                                        ?>
                                                        <span class="badge <?= $badge_class ?> badge-score">
                                                            <?= $avg ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($student['last_test']): ?>
                                                        <small class="text-muted">
                                                            <?= date('d/m/Y H:i', strtotime($student['last_test'])) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="student_detail.php?id=<?= $student['id'] ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-info" 
                                                onclick="viewHistory(<?= $student['id'] ?>)" 
                                                title="Riwayat Ujian">
                                            <i class="fas fa-history"></i>
                                        </button>
                                    </div>
                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="p-3 border-top">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&class=<?= urlencode($filter_class) ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&class=<?= urlencode($filter_class) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&class=<?= urlencode($filter_class) ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_students) ?> dari <?= number_format($total_students) ?> siswa
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewHistory(studentId) {
            // TODO: Implement test history modal
            alert('Fitur riwayat ujian akan segera tersedia');
        }
    </script>
</body>
</html>