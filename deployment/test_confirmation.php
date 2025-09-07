<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$subject_id = isset($_GET['subject']) ? (int)$_GET['subject'] : 1;

$conn = getDBConnection();

// Ambil data subject
$query = "SELECT * FROM subjects WHERE id = :subject_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':subject_id', $subject_id);
$stmt->execute();
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data tes untuk subject ini
$query = "SELECT * FROM tests WHERE subject_id = :subject_id AND status = 'active' LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':subject_id', $subject_id);
$stmt->execute();
$test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test) {
    echo "<script>alert('Tes tidak ditemukan!'); window.location.href='dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Tes - CLASNET ACADEMY AKM</title>
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
        
        .confirmation-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            margin-top: 80px;
            text-align: center;
        }
        
        .confirmation-title {
            color: #2d3748;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .test-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .info-label {
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }
        
        .info-value {
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }
        
        .start-btn {
            background: #667eea;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .start-btn:hover {
            background: #5a67d8;
        }
        
        .back-btn {
            background: #e2e8f0;
            color: #4a5568;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-right: 15px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background: #cbd5e0;
        }
        
        .button-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
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
        
        /* Custom Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 90%;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .modal-overlay.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .modal-message {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .modal-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .modal-btn-cancel {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .modal-btn-cancel:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }
        
        .modal-btn-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .warning-text {
            background: #fef5e7;
            border: 1px solid #f6ad55;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #c05621;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
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
    
    <div class="confirmation-container">
        <h1 class="confirmation-title">Konfirmasi Tes</h1>
        
        <div class="test-info">
            <div class="info-row">
                <span class="info-label">Nama Tes:</span>
                <span class="info-value"><?php echo htmlspecialchars($subject['nama_subject']); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Status Tes:</span>
                <span class="info-value"><?php echo htmlspecialchars($test['nama_tes']); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Waktu Tes:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i'); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Alokasi Waktu Tes:</span>
                <span class="info-value"><?php echo $test['durasi_menit']; ?> Menit</span>
            </div>
        </div>
        
        <div class="button-group">
            <a href="dashboard.php" class="back-btn">Kembali</a>
            <button class="start-btn" onclick="startTest()">Mulai</button>
        </div>
    </div>
    
    <!-- Custom Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content">
            <div class="modal-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 class="modal-title">Konfirmasi Memulai Tes</h3>
            <p class="modal-message">
                Apakah Anda yakin ingin memulai tes <strong><?php echo htmlspecialchars($subject['nama_subject']); ?></strong>?
            </p>
            <div class="warning-text">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Setelah dimulai, waktu akan berjalan dan tidak dapat dihentikan!</span>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Batal</button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmStart()">Ya, Mulai Tes</button>
            </div>
        </div>
    </div>
    
    <script>
        function startTest() {
            document.getElementById('confirmModal').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('confirmModal').classList.add('show');
            }, 10);
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('show');
            setTimeout(() => {
                document.getElementById('confirmModal').style.display = 'none';
            }, 300);
        }
        
        function confirmStart() {
            // Add loading state
            const confirmBtn = document.querySelector('.modal-btn-confirm');
            confirmBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.3"/><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"/></svg> Memulai...';
            confirmBtn.disabled = true;
            
            setTimeout(() => {
                window.location.href = 'participant_confirmation.php?test_id=<?php echo $test['id']; ?>';
            }, 1000);
        }
        
        // Close modal when clicking outside
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Add spin animation for loading
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>