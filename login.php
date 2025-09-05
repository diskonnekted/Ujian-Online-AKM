<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, redirect ke halaman utama
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $conn = getDBConnection();
        
        $query = "SELECT id, username, nama_lengkap, kelas FROM users WHERE username = :username AND password = MD5(:password)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['kelas'] = $user['kelas'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = 'Username atau password salah!';
        }
    } else {
        $error_message = 'Silakan isi username dan password!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CLASNET ACADEMY AKM</title>
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
            z-index: 1000;
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
        
        .logo img {
            width: 30px;
            height: 30px;
        }
        
        .header-text {
            font-size: 18px;
            font-weight: bold;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin-top: 80px;
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-text h2 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .welcome-text p {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .password-field {
            position: relative;
        }
        
        .password-dots {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 20px;
        }
        
        .login-btn {
            width: 100%;
            background: #667eea;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-btn:hover {
            background: #5a67d8;
        }
        
        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .demo-info {
            margin-top: 20px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            font-size: 12px;
            color: #4a5568;
        }
        
        .demo-info strong {
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="header">
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
    
    <div class="login-container">
        <div class="welcome-text">
            <h2>Selamat Datang</h2>
            <p>Silakan login dengan menggunakan username dan password yang anda miliki</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="username" placeholder="P130100230" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'P130100230'; ?>" required>
            </div>
            
            <div class="form-group">
                <div class="password-field">
                    <input type="password" name="password" placeholder="••••••" required>
                    <span class="password-dots">••••</span>
                </div>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="demo-info">
            <strong>Demo Account:</strong><br>
            Username: P130100230<br>
            Password: password123
        </div>
    </div>
</body>
</html>