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

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_teacher') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
        $role = $_POST['role'] ?? 'teacher';
        
        if (empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
            $error_message = 'Username, password, nama lengkap, dan email harus diisi.';
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM teachers WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error_message = 'Username atau email sudah digunakan.';
                } else {
                    // Insert new teacher
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO teachers (username, password, nama_lengkap, email, nip, mata_pelajaran, role) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $hashed_password, $nama_lengkap, $email, $nip, $mata_pelajaran, $role]);
                    $success_message = 'Guru berhasil ditambahkan.';
                }
            } catch (PDOException $e) {
                $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update_status') {
        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        
        try {
            $stmt = $pdo->prepare("UPDATE teachers SET status = ? WHERE id = ?");
            $stmt->execute([$status, $teacher_id]);
            $success_message = 'Status guru berhasil diperbarui.';
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get teachers data
try {
    $search = $_GET['search'] ?? '';
    $filter_role = $_GET['role'] ?? '';
    $filter_status = $_GET['status'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(username LIKE ? OR nama_lengkap LIKE ? OR email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($filter_role)) {
        $where_conditions[] = "role = ?";
        $params[] = $filter_role;
    }
    
    if (!empty($filter_status)) {
        $where_conditions[] = "status = ?";
        $params[] = $filter_status;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "
        SELECT 
            t.*,
            COUNT(q.id) as total_questions,
            COUNT(CASE WHEN q.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as questions_last_month
        FROM teachers t
        LEFT JOIN questions q ON t.id = q.created_by
        $where_clause
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - AKM Admin</title>
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
                        <h2 class="mb-0">Data Guru</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="fas fa-plus me-2"></i>Tambah Guru
                        </button>
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
                                               placeholder="Cari nama, username, atau email...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="role">
                                        <option value="">Semua Role</option>
                                        <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="teacher" <?= $filter_role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <a href="teachers.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Teachers Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>NIP</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Total Soal</th>
                                        <th>Soal Bulan Ini</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($teachers)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Tidak ada data guru ditemukan</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($teachers as $index => $teacher): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($teacher['username']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($teacher['nama_lengkap']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($teacher['email'] ?? '-') ?></small>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($teacher['nip'] ?? '-') ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($teacher['mata_pelajaran'] ?? '-') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $teacher['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                        <?= ucfirst($teacher['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $teacher['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $teacher['status'] === 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= number_format($teacher['total_questions']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">
                                                        <?= number_format($teacher['questions_last_month']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-warning" 
                                                                onclick="toggleStatus(<?= $teacher['id'] ?>, '<?= $teacher['status'] === 'active' ? 'inactive' : 'active' ?>')" 
                                                                title="<?= $teacher['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                            <i class="fas <?= $teacher['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                        </button>
                                                        <button class="btn btn-outline-info" 
                                                                onclick="viewTeacher(<?= $teacher['id'] ?>)" 
                                                                title="Lihat Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
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
    
    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Tambah Guru Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_teacher">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nip" class="form-label">NIP</label>
                                    <input type="text" class="form-control" id="nip" name="nip">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mata_pelajaran" class="form-label">Mata Pelajaran</label>
                                    <input type="text" class="form-control" id="mata_pelajaran" name="mata_pelajaran">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleStatus(teacherId, newStatus) {
            if (confirm('Apakah Anda yakin ingin mengubah status guru ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="teacher_id" value="${teacherId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewTeacher(teacherId) {
            window.location.href = 'teacher_detail.php?id=' + teacherId;
        }
    </script>
</body>
</html>