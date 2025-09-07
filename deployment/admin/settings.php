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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_profile':
                $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $no_telepon = trim($_POST['no_telepon'] ?? '');
                
                if (empty($nama_lengkap) || empty($email)) {
                    throw new Exception('Nama lengkap dan email harus diisi.');
                }
                
                // Check if email already exists for other users
                $stmt = $pdo->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['admin_id']]);
                if ($stmt->fetch()) {
                    throw new Exception('Email sudah digunakan oleh pengguna lain.');
                }
                
                $stmt = $pdo->prepare("
                    UPDATE teachers 
                    SET nama_lengkap = ?, email = ?, no_telepon = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nama_lengkap, $email, $no_telepon, $_SESSION['admin_id']]);
                
                $_SESSION['admin_name'] = $nama_lengkap;
                $success_message = 'Profil berhasil diperbarui.';
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description) VALUES (?, 'update_profile', 'Memperbarui profil')");
                $stmt->execute([$_SESSION['admin_id']]);
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception('Semua field password harus diisi.');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('Konfirmasi password tidak cocok.');
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception('Password baru minimal 6 karakter.');
                }
                
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM teachers WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($current_password, $user['password'])) {
                    throw new Exception('Password saat ini tidak benar.');
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE teachers SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                
                $success_message = 'Password berhasil diubah.';
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description) VALUES (?, 'change_password', 'Mengubah password')");
                $stmt->execute([$_SESSION['admin_id']]);
                break;
                
            case 'add_category':
                $nama_kategori = trim($_POST['nama_kategori'] ?? '');
                $deskripsi = trim($_POST['deskripsi'] ?? '');
                
                if (empty($nama_kategori)) {
                    throw new Exception('Nama kategori harus diisi.');
                }
                
                // Check if category already exists
                $stmt = $pdo->prepare("SELECT id FROM question_categories WHERE nama_kategori = ?");
                $stmt->execute([$nama_kategori]);
                if ($stmt->fetch()) {
                    throw new Exception('Kategori sudah ada.');
                }
                
                $stmt = $pdo->prepare("INSERT INTO question_categories (nama_kategori, deskripsi) VALUES (?, ?)");
                $stmt->execute([$nama_kategori, $deskripsi]);
                
                $success_message = 'Kategori berhasil ditambahkan.';
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description) VALUES (?, 'add_category', ?)");
                $stmt->execute([$_SESSION['admin_id'], "Menambah kategori: $nama_kategori"]);
                break;
                
            case 'delete_category':
                $category_id = intval($_POST['category_id'] ?? 0);
                
                // Check if category is being used
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $usage_count = $stmt->fetch()['count'];
                
                if ($usage_count > 0) {
                    throw new Exception("Kategori tidak dapat dihapus karena masih digunakan oleh $usage_count soal.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM question_categories WHERE id = ?");
                $stmt->execute([$category_id]);
                
                $success_message = 'Kategori berhasil dihapus.';
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description) VALUES (?, 'delete_category', ?)");
                $stmt->execute([$_SESSION['admin_id'], "Menghapus kategori ID: $category_id"]);
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $current_user = $stmt->fetch();
    
    // Get categories
    $stmt = $pdo->query("SELECT DISTINCT id, nama_kategori, deskripsi, created_at FROM question_categories GROUP BY nama_kategori ORDER BY nama_kategori");
    $categories = $stmt->fetchAll();
    
    // Get recent activities
    $stmt = $pdo->prepare("
        SELECT * FROM admin_activities 
        WHERE teacher_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    $recent_activities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - AKM Admin</title>
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
        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        .settings-content {
            padding: 25px;
        }
        .nav-pills .nav-link {
            border-radius: 10px;
            margin-bottom: 5px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
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
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Laporan
                        </a>
                        <a class="nav-link active" href="settings.php">
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
                        <h2 class="mb-0">Pengaturan</h2>
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
                    
                    <div class="row">
                        <!-- Settings Navigation -->
                        <div class="col-lg-3">
                            <div class="settings-card">
                                <div class="settings-content">
                                    <ul class="nav nav-pills flex-column" id="settingsTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active w-100 text-start" id="profile-tab" 
                                                    data-bs-toggle="pill" data-bs-target="#profile" type="button">
                                                <i class="fas fa-user me-2"></i>Profil
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link w-100 text-start" id="password-tab" 
                                                    data-bs-toggle="pill" data-bs-target="#password" type="button">
                                                <i class="fas fa-lock me-2"></i>Password
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link w-100 text-start" id="categories-tab" 
                                                    data-bs-toggle="pill" data-bs-target="#categories" type="button">
                                                <i class="fas fa-tags me-2"></i>Kategori Soal
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link w-100 text-start" id="activities-tab" 
                                                    data-bs-toggle="pill" data-bs-target="#activities" type="button">
                                                <i class="fas fa-history me-2"></i>Aktivitas
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Settings Content -->
                        <div class="col-lg-9">
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Profile Tab -->
                                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                    <div class="settings-card">
                                        <div class="settings-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-user me-2"></i>Profil Pengguna
                                            </h5>
                                        </div>
                                        <div class="settings-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_profile">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="nama_lengkap" 
                                                                   value="<?= htmlspecialchars($current_user['nama_lengkap'] ?? '') ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                                            <input type="email" class="form-control" name="email" 
                                                                   value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">No. Telepon</label>
                                                            <input type="text" class="form-control" name="no_telepon" 
                                                                   value="<?= htmlspecialchars($current_user['no_telepon'] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Role</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?= htmlspecialchars($current_user['role'] ?? 'teacher') ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Password Tab -->
                                <div class="tab-pane fade" id="password" role="tabpanel">
                                    <div class="settings-card">
                                        <div class="settings-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-lock me-2"></i>Ubah Password
                                            </h5>
                                        </div>
                                        <div class="settings-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="change_password">
                                                <div class="mb-3">
                                                    <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control" name="current_password" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control" name="new_password" 
                                                           minlength="6" required>
                                                    <div class="form-text">Minimal 6 karakter</div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control" name="confirm_password" 
                                                           minlength="6" required>
                                                </div>
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-key me-2"></i>Ubah Password
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Categories Tab -->
                                <div class="tab-pane fade" id="categories" role="tabpanel">
                                    <div class="settings-card">
                                        <div class="settings-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-tags me-2"></i>Kategori Soal
                                            </h5>
                                        </div>
                                        <div class="settings-content">
                                            <!-- Add Category Form -->
                                            <form method="POST" class="mb-4">
                                                <input type="hidden" name="action" value="add_category">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="nama_kategori" 
                                                               placeholder="Nama kategori" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control" name="deskripsi" 
                                                               placeholder="Deskripsi (opsional)">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" class="btn btn-primary w-100">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                            <!-- Categories List -->
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Nama Kategori</th>
                                                            <th>Deskripsi</th>
                                                            <th>Jumlah Soal</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($categories)): ?>
                                                            <tr>
                                                                <td colspan="4" class="text-center py-3 text-muted">
                                                                    Belum ada kategori
                                                                </td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <?php foreach ($categories as $category): ?>
                                                                <?php
                                                                // Get question count for this category
                                                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE category_id = ?");
                                                                $stmt->execute([$category['id']]);
                                                                $question_count = $stmt->fetch()['count'];
                                                                ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($category['nama_kategori']) ?></td>
                                                                    <td><?= htmlspecialchars($category['deskripsi'] ?? '-') ?></td>
                                                                    <td>
                                                                        <span class="badge bg-info">
                                                                            <?= number_format($question_count) ?> soal
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($question_count == 0): ?>
                                                                            <form method="POST" class="d-inline" 
                                                                                  onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                                                                <input type="hidden" name="action" value="delete_category">
                                                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                                    <i class="fas fa-trash"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">Tidak dapat dihapus</span>
                                                                        <?php endif; ?>
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
                                
                                <!-- Activities Tab -->
                                <div class="tab-pane fade" id="activities" role="tabpanel">
                                    <div class="settings-card">
                                        <div class="settings-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-history me-2"></i>Aktivitas Terbaru
                                            </h5>
                                        </div>
                                        <div class="settings-content">
                                            <?php if (empty($recent_activities)): ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="fas fa-history fa-3x mb-3"></i>
                                                    <p>Belum ada aktivitas</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($recent_activities as $activity): ?>
                                                    <div class="activity-item">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="mb-1"><?= ucfirst(str_replace('_', ' ', $activity['activity_type'])) ?></h6>
                                                                <p class="mb-1 text-muted"><?= htmlspecialchars($activity['description']) ?></p>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
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
    <script>
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.querySelector('input[name="new_password"]');
            const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
            
            if (newPasswordInput && confirmPasswordInput) {
                function validatePassword() {
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity('Password tidak cocok');
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
                
                newPasswordInput.addEventListener('input', validatePassword);
                confirmPasswordInput.addEventListener('input', validatePassword);
            }
        });
    </script>
</body>
</html>