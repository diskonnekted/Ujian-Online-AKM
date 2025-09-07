<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Ambil data mata pelajaran
$query = "SELECT * FROM subjects ORDER BY id";
$stmt = $conn->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CLASNET ACADEMY AKM</title>
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
        
        .dashboard-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            margin-top: 80px;
            text-align: center;
        }
        
        .welcome-title {
            color: #2d3748;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .subject-card {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .subject-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        
        .subject-card.literasi {
            background: #48bb78;
            color: white;
        }
        
        .subject-card.matematika {
            background: #ed8936;
            color: white;
        }
        
        .subject-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .subject-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .subject-desc {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .grade-section {
            margin-bottom: 20px;
        }
        
        .grade-label {
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .grade-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            color: #2d3748;
        }
        
        .start-btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        .start-btn:hover {
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
    
    <div class="dashboard-container">
        <h1 class="welcome-title">Materi :</h1>
        
        <div class="subjects-grid">
            <a href="test_confirmation.php?subject=1" class="subject-card literasi">
                <span class="subject-icon">📚</span>
                <div class="subject-name">Literasi Membaca</div>
            </a>
            
            <a href="test_confirmation.php?subject=2" class="subject-card matematika">
                <span class="subject-icon">🔢</span>
                <div class="subject-name">Literasi Matematika</div>
            </a>
        </div>
        
        <div class="grade-section">
            <div class="grade-label">Jenjang :</div>
            <select class="grade-select" id="jenjang">
                <option value="SMP/MTs/PAKET B">SMP/MTs/PAKET B</option>
                <option value="SMA/MA/PAKET C">SMA/MA/PAKET C</option>
            </select>
        </div>
        
        <button class="start-btn" onclick="startTest()">Ok, Lanjutkan !</button>
    </div>
    
    <script>
        function startTest() {
            // Untuk saat ini, arahkan ke literasi membaca sebagai default
            window.location.href = 'test_confirmation.php?subject=1';
        }
        
        // Update jenjang di session jika diperlukan
        document.getElementById('jenjang').addEventListener('change', function() {
            // Bisa ditambahkan AJAX call untuk update jenjang di session
        });
    </script>
</body>
</html>