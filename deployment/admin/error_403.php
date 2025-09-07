<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - AKM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 30px;
        }
        .error-code {
            font-size: 4rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 1.5rem;
            color: #495057;
            margin-bottom: 20px;
        }
        .error-description {
            color: #6c757d;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: white;
        }
        .permission-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            border-left: 4px solid #dc3545;
        }
        .permission-list {
            text-align: left;
            margin-top: 15px;
        }
        .permission-list li {
            margin-bottom: 5px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-ban"></i>
        </div>
        
        <div class="error-code">403</div>
        
        <h1 class="error-title">Akses Ditolak</h1>
        
        <p class="error-description">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. 
            Silakan hubungi administrator sistem jika Anda merasa ini adalah kesalahan.
        </p>
        
        <div class="permission-info">
            <h6 class="mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Informasi Akses
            </h6>
            <p class="mb-2"><strong>User:</strong> <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Unknown') ?></p>
            <p class="mb-2"><strong>Role:</strong> <?= htmlspecialchars($_SESSION['admin_role'] ?? 'Unknown') ?></p>
            <p class="mb-3"><strong>Waktu:</strong> <?= date('d/m/Y H:i:s') ?></p>
            
            <?php if (isset($_SESSION['admin_role'])): ?>
                <?php 
                require_once 'auth_config.php';
                $permissions = getUserPermissions();
                ?>
                <?php if (!empty($permissions)): ?>
                    <h6 class="mb-2">Izin yang Anda miliki:</h6>
                    <ul class="permission-list">
                        <?php foreach ($permissions as $permission): ?>
                            <li><i class="fas fa-check text-success me-2"></i><?= ucwords(str_replace('_', ' ', $permission)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="d-flex gap-3 justify-content-center">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                Jika Anda memerlukan akses tambahan, silakan hubungi administrator sistem.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>