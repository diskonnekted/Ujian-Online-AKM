<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKM Online Test - Asesmen Kompetensi Minimum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }
        .hero-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-custom {
            border-radius: 50px;
            padding: 15px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            margin: 10px;
        }
        .btn-login {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }
        .btn-login:hover {
            background: transparent;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .btn-register {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        .btn-register:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ffd700;
        }
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 15px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .admin-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row hero-section">
            <div class="col-12">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="hero-card">
                                <h1 class="display-4 mb-4">
                                    <i class="fas fa-graduation-cap me-3"></i>
                                    AKM Online Test
                                </h1>
                                <p class="lead mb-4">
                                    Asesmen Kompetensi Minimum - Platform ujian online untuk mengukur kemampuan literasi dan numerasi siswa
                                </p>
                                
                                <div class="mb-5">
                                    <a href="login.php" class="btn btn-login btn-custom">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Masuk
                                    </a>
                                    <a href="register.php" class="btn btn-register btn-custom">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Daftar
                                    </a>
                                </div>
                                
                                <div class="row mt-5">
                                    <div class="col-md-4">
                                        <div class="feature-card">
                                            <div class="feature-icon">
                                                <i class="fas fa-book-open"></i>
                                            </div>
                                            <h5>Literasi Membaca</h5>
                                            <p>Tes kemampuan memahami, menggunakan, dan merefleksikan berbagai jenis teks</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="feature-card">
                                            <div class="feature-icon">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                            <h5>Numerasi</h5>
                                            <p>Tes kemampuan berpikir menggunakan konsep, prosedur, dan alat matematika</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="feature-card">
                                            <div class="feature-icon">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <h5>Laporan Hasil</h5>
                                            <p>Analisis mendalam tentang kemampuan dan rekomendasi pembelajaran</p>
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
    
    <!-- Admin Access Link -->
    <a href="admin/login.php" class="admin-link" title="Admin Login">
        <i class="fas fa-cog me-1"></i>
        Admin
    </a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>