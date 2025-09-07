<?php
session_start();
require_once 'config/database.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $nis = trim($_POST['nis'] ?? '');
    $kelas = $_POST['kelas'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    
    // Validation
    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($nis) || empty($kelas) || empty($jenis_kelamin) || empty($tanggal_lahir)) {
        $error_message = 'Semua field harus diisi.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Password dan konfirmasi password tidak sama.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password minimal 6 karakter.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if username or NIS already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR nis = ?");
            $stmt->execute([$username, $nis]);
            
            if ($stmt->fetch()) {
                $error_message = 'Username atau NIS sudah digunakan. Silakan pilih yang lain.';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nis, kelas, jenis_kelamin, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $nama_lengkap, $nis, $kelas, $jenis_kelamin, $tanggal_lahir]);
                
                $success_message = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                
                // Clear form data
                $username = $nama_lengkap = $nis = $kelas = $jenis_kelamin = $tanggal_lahir = '';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Siswa - AKM Online Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h2 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Registrasi Siswa
                </h2>
                <p class="mb-0 mt-2 opacity-75">Daftar untuk mengikuti AKM Online Test</p>
            </div>
            
            <div class="register-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i>
                            Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($username ?? '') ?>" 
                               placeholder="Masukkan username" required>
                        <small class="text-muted">Username akan digunakan untuk login</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">
                            <i class="fas fa-id-card me-1"></i>
                            Nama Lengkap
                        </label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                               value="<?= htmlspecialchars($nama_lengkap ?? '') ?>" 
                               placeholder="Masukkan nama lengkap" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nis" class="form-label">
                            <i class="fas fa-id-badge me-1"></i>
                            NIS (Nomor Induk Siswa)
                        </label>
                        <input type="text" class="form-control" id="nis" name="nis" 
                               value="<?= htmlspecialchars($nis ?? '') ?>" 
                               placeholder="Masukkan NIS" required>
                        <small class="text-muted">Nomor Induk Siswa sesuai dengan data sekolah</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kelas" class="form-label">
                                <i class="fas fa-school me-1"></i>
                                Kelas
                            </label>
                            <select class="form-control" id="kelas" name="kelas" required>
                                <option value="">Pilih Kelas</option>
                                <option value="SMP/MTs/PAKET B" <?= ($kelas ?? '') === 'SMP/MTs/PAKET B' ? 'selected' : '' ?>>SMP/MTs/PAKET B</option>
                                <option value="SMA/MA/PAKET C" <?= ($kelas ?? '') === 'SMA/MA/PAKET C' ? 'selected' : '' ?>>SMA/MA/PAKET C</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="jenis_kelamin" class="form-label">
                                <i class="fas fa-venus-mars me-1"></i>
                                Jenis Kelamin
                            </label>
                            <select class="form-control" id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki" <?= ($jenis_kelamin ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($jenis_kelamin ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tanggal_lahir" class="form-label">
                            <i class="fas fa-calendar me-1"></i>
                            Tanggal Lahir
                        </label>
                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" 
                               value="<?= htmlspecialchars($tanggal_lahir ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Masukkan password" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Konfirmasi Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Ulangi password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-register btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus me-2"></i>
                        Daftar Sekarang
                    </button>
                </form>
                
                <div class="text-center">
                    <p class="mb-2">Sudah punya akun? <a href="login.php" class="back-link">Login di sini</a></p>
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left me-1"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>