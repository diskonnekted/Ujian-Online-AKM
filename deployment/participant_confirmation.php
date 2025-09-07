<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 1;

$conn = getDBConnection();

// Ambil data user
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data test
$query = "SELECT t.*, s.nama_subject FROM tests t JOIN subjects s ON t.subject_id = s.id WHERE t.id = :test_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':test_id', $test_id);
$stmt->execute();
$test = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate token kesiapan otomatis
function generateToken($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $token;
}

// Handle refresh token request
if (isset($_GET['refresh_token']) && $_GET['refresh_token'] == '1') {
    $_SESSION['readiness_token'] = generateToken();
    echo $_SESSION['readiness_token'];
    exit();
}

// Simpan atau ambil token dari session
if (!isset($_SESSION['readiness_token'])) {
    $_SESSION['readiness_token'] = generateToken();
}
$generated_token = $_SESSION['readiness_token'];

if ($_POST) {
    // Validasi input
    $konfirmasi_nama = trim($_POST['konfirmasi_nama']);
    $token_kesiapan = trim($_POST['token_kesiapan']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $hari = $_POST['hari'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    
    // Validasi nama peserta (lebih fleksibel)
    $nama_user_clean = strtolower(trim(preg_replace('/\s+/', ' ', $user['nama_lengkap'])));
    $nama_input_clean = strtolower(trim(preg_replace('/\s+/', ' ', $konfirmasi_nama)));
    
    if ($nama_input_clean !== $nama_user_clean) {
        $error = "Nama yang Anda masukkan tidak sesuai dengan data peserta! Nama yang terdaftar: " . $user['nama_lengkap'];
    }
    // Validasi token kesiapan (harus sesuai dengan token yang di-generate)
    else if (strtoupper($token_kesiapan) !== strtoupper($_SESSION['readiness_token'])) {
        $error = "Token kesiapan tidak sesuai! Gunakan token yang ditampilkan di kiri atas.";
    }
    // Validasi tanggal lahir
    else if (empty($hari) || empty($bulan) || empty($tahun)) {
        $error = "Tanggal lahir harus diisi lengkap!";
    }
    else {
        // Generate token untuk sesi tes
        $token = bin2hex(random_bytes(16));
        
        // Buat sesi tes baru
        $query = "INSERT INTO test_sessions (user_id, test_id, token, status) VALUES (:user_id, :test_id, :token, 'ongoing')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $session_id = $conn->lastInsertId();
        $_SESSION['test_session_id'] = $session_id;
        $_SESSION['test_token'] = $token;
        
        header('Location: test_page.php?token=' . $token);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Data Peserta - CLASNET ACADEMY AKM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #4a5568;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-text {
            font-size: 18px;
            font-weight: bold;
        }
        
        .user-info {
            font-size: 14px;
            color: #e2e8f0;
        }
        
        .main-container {
            display: flex;
            gap: 30px;
            margin-top: 80px;
            width: 100%;
            max-width: 1200px;
            padding: 20px;
        }
        
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 350px;
            height: fit-content;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .search-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .confirmation-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            flex: 1;
            max-width: 500px;
        }
        
        .confirmation-title {
            color: #2d3748;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: #4a5568;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            background: #f7fafc;
            color: #2d3748;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            color: #2d3748;
        }
        
        .date-group {
            display: flex;
            gap: 10px;
        }
        
        .date-group select {
            flex: 1;
        }
        
        .submit-btn {
            background: #667eea;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            background: #5a67d8;
        }
        
        .logout-btn {
            position: absolute;
            top: 15px;
            right: 30px;
            background: #e53e3e;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .logout-btn:hover {
            background: #c53030;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#4a5568" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="#4a5568" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="#4a5568" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <div class="header-text">CLASNET ACADEMY</div>
                <div style="font-size: 12px; opacity: 0.8;">APLIKASI AKM</div>
            </div>
        </div>
        <div class="user-info">
            <?php echo htmlspecialchars($_SESSION['username']); ?> - <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="main-container">
        <div class="search-container">
            <input type="text" class="search-input" id="generated-token" placeholder="Token : Loading..." value="<?php echo $generated_token; ?>" readonly>
            <button class="search-btn" onclick="refreshToken()">Refresh</button>
        </div>
        
        <div class="confirmation-container">
            <h1 class="confirmation-title">Konfirmasi data Peserta</h1>
            
            <?php if (isset($error)): ?>
                <div style="background: #fed7d7; color: #c53030; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #feb2b2;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">NIS (Nomor Induk Siswa):</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['nis'] ?? $user['username']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nama Peserta:</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jenis Kelamin:</label>
                    <select class="form-select" name="jenis_kelamin">
                        <option value="Laki-laki" <?php echo (($user['jenis_kelamin'] ?? '') == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo (($user['jenis_kelamin'] ?? '') == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kelas Ujian:</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($test['nama_subject']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Konfirmasi Nama Peserta:</label>
                    <input type="text" class="form-input" name="konfirmasi_nama" placeholder="Ketik ulang nama Anda untuk konfirmasi" required>
                    <small style="color: #718096; font-size: 12px; margin-top: 5px; display: block;">* Ketik ulang nama: <?php echo htmlspecialchars($user['nama_lengkap']); ?></small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Lahir:</label>
                    <div class="date-group">
                        <select class="form-select" name="hari" required>
                            <option value="">Hari</option>
                            <?php for($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-select" name="bulan" required>
                            <option value="">Bulan</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <select class="form-select" name="tahun" required>
                            <option value="">Tahun</option>
                            <?php for($i = 2000; $i <= 2010; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Token Kesiapan:</label>
                    <input type="text" class="form-input" name="token_kesiapan" placeholder="Masukkan token dari kiri atas" required>
                    <small style="color: #718096; font-size: 12px; margin-top: 5px; display: block;">* Salin token yang ditampilkan di kiri atas halaman</small>
                </div>
                
                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>
    
    <script>
    function refreshToken() {
        // Generate new token via AJAX
        fetch('?refresh_token=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.text())
        .then(data => {
            // Update token display
            document.getElementById('generated-token').value = data;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>