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

// Handle delete question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $question_id = intval($_POST['question_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $success_message = 'Soal berhasil dihapus.';
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description) VALUES (?, 'delete_question', ?)");
        $stmt->execute([$_SESSION['admin_id'], "Menghapus soal ID: $question_id"]);
    } catch (PDOException $e) {
        $error_message = 'Gagal menghapus soal: ' . $e->getMessage();
    }
}

// Handle search and filter
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_category = $_GET['category'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(q.pertanyaan LIKE ? OR q.jawaban_benar LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filter_type)) {
    $where_conditions[] = "q.tipe_soal = ?";
    $params[] = $filter_type;
}

if (!empty($filter_category)) {
    $where_conditions[] = "qc.nama_kategori = ?";
    $params[] = $filter_category;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Initialize default values
$question_types = [
    'pilihan_ganda' => 'Pilihan Ganda',
    'benar_salah' => 'Benar/Salah',
    'pilihan_ganda_kompleks' => 'Pilihan Ganda Kompleks',
    'isian_singkat' => 'Isian Singkat',
    'drag_drop' => 'Drag & Drop',
    'urutan' => 'Urutan'
];
$categories = [];
$questions = [];
$total_questions = 0;
$total_pages = 1;

try {
    // Get total count
    $count_query = "
        SELECT COUNT(*) as total 
        FROM questions q
        LEFT JOIN question_categories qc ON q.category_id = qc.id
        LEFT JOIN teachers t ON q.created_by = t.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_questions = $stmt->fetch()['total'];
    $total_pages = ceil($total_questions / $limit);
    
    // Get questions data
    $query = "
        SELECT 
            q.*,
            qc.nama_kategori,
            t.nama_lengkap as creator_name,
            COUNT(ts.id) as usage_count
        FROM questions q
        LEFT JOIN question_categories qc ON q.category_id = qc.id
        LEFT JOIN teachers t ON q.created_by = t.id
        LEFT JOIN test_sessions ts ON q.test_id = ts.test_id
        $where_clause
        GROUP BY q.id
        ORDER BY q.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $pdo->query("SELECT DISTINCT nama_kategori FROM question_categories GROUP BY nama_kategori ORDER BY nama_kategori");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal - AKM Admin</title>
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
        .question-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .type-badge {
            font-size: 0.75em;
        }
        
        /* Warna untuk setiap tipe soal */
        .type-pilihan-ganda {
            background-color: #007bff !important; /* Biru */
        }
        .type-benar-salah {
            background-color: #28a745 !important; /* Hijau */
        }
        .type-pilihan-ganda-kompleks {
            background-color: #dc3545 !important; /* Merah */
        }
        .type-isian-singkat {
            background-color: #ffc107 !important; /* Kuning */
            color: #212529 !important;
        }
        .type-drag-drop {
            background-color: #6f42c1 !important; /* Ungu */
        }
        .type-urutan {
            background-color: #fd7e14 !important; /* Orange */
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
                        <a class="nav-link active" href="questions.php">
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
                        <h2 class="mb-0">Bank Soal</h2>
                        <div>
                            <span class="badge bg-primary fs-6 me-2"><?= number_format($total_questions) ?> Total Soal</span>
                            <a href="create_question.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Soal Baru
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
                    
                    <!-- Search and Filter -->
                    <div class="search-card">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" name="search" 
                                               value="<?= htmlspecialchars($search) ?>" 
                                               placeholder="Cari pertanyaan atau jawaban...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="type">
                                        <option value="">Semua Tipe</option>
                                        <?php foreach ($question_types as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= $filter_type === $key ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="category">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>" 
                                                    <?= $filter_category === $category ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <a href="questions.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Questions Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Pertanyaan</th>
                                        <th>Tipe</th>
                                        <th>Kategori</th>
                                        <th>Pembuat</th>
                                        <th>Digunakan</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($questions)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada soal ditemukan</p>
                                                <a href="create_question.php" class="btn btn-primary mt-2">
                                                    <i class="fas fa-plus me-2"></i>Buat Soal Pertama
                                                </a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($questions as $index => $question): ?>
                                            <tr>
                                                <td><?= $offset + $index + 1 ?></td>
                                                <td>
                                                    <div class="question-preview" title="<?= htmlspecialchars($question['pertanyaan']) ?>">
                                                        <?= htmlspecialchars($question['pertanyaan']) ?>
                                                    </div>
                                                    <?php if ($question['gambar']): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-image me-1"></i>Ada gambar
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge type-badge type-<?= str_replace('_', '-', $question['tipe_soal']) ?>">
                                                        <?= $question_types[$question['tipe_soal']] ?? $question['tipe_soal'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($question['nama_kategori']): ?>
                                                        <span class="badge bg-secondary">
                                                            <?= htmlspecialchars($question['nama_kategori']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($question['creator_name'] ?? 'Unknown') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= number_format($question['usage_count']) ?>x
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($question['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-info" 
                                                                onclick="viewQuestion(<?= $question['id'] ?>)" 
                                                                title="Lihat Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning" 
                                                                onclick="editQuestion(<?= $question['id'] ?>)" 
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteQuestion(<?= $question['id'] ?>)" 
                                                                title="Hapus">
                                                            <i class="fas fa-trash"></i>
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
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($filter_type) ?>&category=<?= urlencode($filter_category) ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($filter_type) ?>&category=<?= urlencode($filter_category) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($filter_type) ?>&category=<?= urlencode($filter_category) ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_questions) ?> dari <?= number_format($total_questions) ?> soal
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
        function viewQuestion(questionId) {
            // TODO: Implement question detail modal
            alert('Fitur detail soal akan segera tersedia');
        }
        
        function editQuestion(questionId) {
            window.location.href = `edit_question.php?id=${questionId}`;
        }
        
        function deleteQuestion(questionId) {
            if (confirm('Apakah Anda yakin ingin menghapus soal ini? Tindakan ini tidak dapat dibatalkan.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="question_id" value="${questionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>