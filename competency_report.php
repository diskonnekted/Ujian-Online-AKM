<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Ambil data user
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil semua sesi tes yang sudah selesai
$query = "SELECT ts.*, t.nama_tes, t.durasi_menit, s.nama_subject, s.id as subject_id
          FROM test_sessions ts 
          JOIN tests t ON ts.test_id = t.id 
          JOIN subjects s ON t.subject_id = s.id 
          WHERE ts.user_id = :user_id AND ts.status = 'completed'
          ORDER BY ts.waktu_selesai DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik per mata pelajaran
$subject_stats = [];
foreach ($sessions as $session) {
    $subject_id = $session['subject_id'];
    $subject_name = $session['nama_subject'];
    
    if (!isset($subject_stats[$subject_id])) {
        $subject_stats[$subject_id] = [
            'nama' => $subject_name,
            'total_tes' => 0,
            'total_skor' => 0,
            'skor_tertinggi' => 0,
            'skor_terendah' => 100,
            'tes_terakhir' => null
        ];
    }
    
    $subject_stats[$subject_id]['total_tes']++;
    $subject_stats[$subject_id]['total_skor'] += $session['total_skor'];
    
    if ($session['total_skor'] > $subject_stats[$subject_id]['skor_tertinggi']) {
        $subject_stats[$subject_id]['skor_tertinggi'] = $session['total_skor'];
    }
    
    if ($session['total_skor'] < $subject_stats[$subject_id]['skor_terendah']) {
        $subject_stats[$subject_id]['skor_terendah'] = $session['total_skor'];
    }
    
    if (!$subject_stats[$subject_id]['tes_terakhir'] || 
        strtotime($session['waktu_selesai']) > strtotime($subject_stats[$subject_id]['tes_terakhir']['waktu_selesai'])) {
        $subject_stats[$subject_id]['tes_terakhir'] = $session;
    }
}

// Hitung rata-rata per mata pelajaran
foreach ($subject_stats as &$stat) {
    $stat['rata_rata'] = $stat['total_tes'] > 0 ? round($stat['total_skor'] / $stat['total_tes'], 2) : 0;
    if ($stat['skor_terendah'] == 100 && $stat['total_tes'] > 0) {
        $stat['skor_terendah'] = $stat['skor_tertinggi'];
    }
}

// Fungsi untuk menentukan level kompetensi
function getCompetencyLevel($score) {
    if ($score >= 85) return ['level' => 'Mahir', 'color' => '#38a169', 'description' => 'Sangat baik dalam menguasai kompetensi'];
    if ($score >= 70) return ['level' => 'Cakap', 'color' => '#3182ce', 'description' => 'Baik dalam menguasai kompetensi'];
    if ($score >= 55) return ['level' => 'Layak', 'color' => '#ed8936', 'description' => 'Cukup dalam menguasai kompetensi'];
    return ['level' => 'Perlu Intervensi', 'color' => '#e53e3e', 'description' => 'Memerlukan bimbingan tambahan'];
}

// Hitung statistik keseluruhan
$total_tes = count($sessions);
$total_skor_keseluruhan = array_sum(array_column($sessions, 'total_skor'));
$rata_rata_keseluruhan = $total_tes > 0 ? round($total_skor_keseluruhan / $total_tes, 2) : 0;
$skor_tertinggi_keseluruhan = $total_tes > 0 ? max(array_column($sessions, 'total_skor')) : 0;
$skor_terendah_keseluruhan = $total_tes > 0 ? min(array_column($sessions, 'total_skor')) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kompetensi - CLASNET ACADEMY AKM</title>
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
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: #f7fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-text {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .user-info {
            color: #4a5568;
            font-size: 14px;
        }
        
        .logout-btn {
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .report-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .report-title {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .student-info {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            text-align: left;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #718096;
            font-size: 14px;
        }
        
        .subject-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 22px;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .subject-card {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #4299e1;
        }
        
        .subject-name {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .competency-level {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            margin-bottom: 15px;
        }
        
        .subject-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .subject-stat {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 6px;
        }
        
        .subject-stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .subject-stat-label {
            font-size: 12px;
            color: #718096;
        }
        
        .history-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .history-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        .score-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #4299e1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3182ce;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4299e1, #38a169);
            border-radius: 10px;
            transition: width 0.3s ease;
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
    
    <div class="container">
        <div class="report-header">
            <h1 class="report-title">Laporan Kompetensi Siswa</h1>
            <p style="color: #718096; margin-bottom: 20px;">Analisis Hasil Asesmen Kompetensi Minimum (AKM)</p>
            
            <div class="student-info">
                <div class="info-item">
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Kelas</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['kelas']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Jenis Kelamin</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['jenis_kelamin']); ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($total_tes > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e6fffa; color: #38a169;">📊</div>
                    <div class="stat-value"><?php echo $total_tes; ?></div>
                    <div class="stat-label">Total Tes Dikerjakan</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ebf8ff; color: #3182ce;">📈</div>
                    <div class="stat-value"><?php echo $rata_rata_keseluruhan; ?>%</div>
                    <div class="stat-label">Rata-rata Skor</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f0fff4; color: #38a169;">🏆</div>
                    <div class="stat-value"><?php echo $skor_tertinggi_keseluruhan; ?>%</div>
                    <div class="stat-label">Skor Tertinggi</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef5e7; color: #ed8936;">📉</div>
                    <div class="stat-value"><?php echo $skor_terendah_keseluruhan; ?>%</div>
                    <div class="stat-label">Skor Terendah</div>
                </div>
            </div>
            
            <div class="subject-section">
                <h2 class="section-title">Analisis Per Mata Pelajaran</h2>
                
                <div class="subject-grid">
                    <?php foreach ($subject_stats as $stat): ?>
                        <?php $competency = getCompetencyLevel($stat['rata_rata']); ?>
                        <div class="subject-card">
                            <div class="subject-name"><?php echo htmlspecialchars($stat['nama']); ?></div>
                            
                            <div class="competency-level" style="background: <?php echo $competency['color']; ?>">
                                <?php echo $competency['level']; ?>
                            </div>
                            
                            <div style="font-size: 12px; color: #718096; margin-bottom: 15px;">
                                <?php echo $competency['description']; ?>
                            </div>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $stat['rata_rata']; ?>%;"></div>
                            </div>
                            
                            <div class="subject-stats">
                                <div class="subject-stat">
                                    <div class="subject-stat-value"><?php echo $stat['total_tes']; ?></div>
                                    <div class="subject-stat-label">Tes Dikerjakan</div>
                                </div>
                                <div class="subject-stat">
                                    <div class="subject-stat-value"><?php echo $stat['rata_rata']; ?>%</div>
                                    <div class="subject-stat-label">Rata-rata</div>
                                </div>
                                <div class="subject-stat">
                                    <div class="subject-stat-value"><?php echo $stat['skor_tertinggi']; ?>%</div>
                                    <div class="subject-stat-label">Tertinggi</div>
                                </div>
                                <div class="subject-stat">
                                    <div class="subject-stat-value"><?php echo $stat['skor_terendah']; ?>%</div>
                                    <div class="subject-stat-label">Terendah</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="history-section">
                <h2 class="section-title">Riwayat Tes</h2>
                
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Mata Pelajaran</th>
                            <th>Nama Tes</th>
                            <th>Skor</th>
                            <th>Level Kompetensi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <?php $competency = getCompetencyLevel($session['total_skor']); ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($session['waktu_selesai'])); ?></td>
                                <td><?php echo htmlspecialchars($session['nama_subject']); ?></td>
                                <td><?php echo htmlspecialchars($session['nama_tes']); ?></td>
                                <td>
                                    <span class="score-badge" style="background: <?php echo $competency['color']; ?>">
                                        <?php echo $session['total_skor']; ?>%
                                    </span>
                                </td>
                                <td><?php echo $competency['level']; ?></td>
                                <td>
                                    <a href="test_result.php?session_id=<?php echo $session['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="subject-section">
                <div class="no-data">
                    <h3>Belum Ada Data Tes</h3>
                    <p>Anda belum mengerjakan tes apapun. Silakan kerjakan tes terlebih dahulu untuk melihat laporan kompetensi.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            <?php if ($total_tes > 0): ?>
                <a href="#" onclick="window.print()" class="btn btn-primary">Cetak Laporan</a>
                <a href="#" onclick="generatePDF()" class="btn btn-success">Simpan PDF</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        // Animasi progress bar
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
        
        // Generate PDF function
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            
            // Kop Lembaga Clasnet
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('CLASNET', 105, 20, { align: 'center' });
            
            doc.setFontSize(14);
            doc.setFont('helvetica', 'normal');
            doc.text('Lembaga Pendidikan Digital', 105, 28, { align: 'center' });
            
            doc.setFontSize(12);
            doc.text('Jl. Pendidikan No. 123, Jakarta Selatan', 105, 35, { align: 'center' });
            doc.text('Telp: (021) 1234-5678 | Email: info@clasnet.id', 105, 42, { align: 'center' });
            
            // Garis pemisah
            doc.setLineWidth(0.5);
            doc.line(20, 48, 190, 48);
            
            // Judul Laporan
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('LAPORAN KOMPETENSI SISWA', 105, 58, { align: 'center' });
            doc.text('ASESMEN KOMPETENSI MINIMUM (AKM)', 105, 66, { align: 'center' });
            
            // Data Siswa
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            let yPos = 80;
            
            doc.text('Nama Siswa: <?php echo htmlspecialchars($user["nama_lengkap"]); ?>', 20, yPos);
            yPos += 8;
            doc.text('Username: <?php echo htmlspecialchars($user["username"]); ?>', 20, yPos);
            yPos += 8;
            doc.text('Kelas: <?php echo htmlspecialchars($user["kelas"]); ?>', 20, yPos);
            yPos += 8;
            doc.text('Tanggal Cetak: ' + new Date().toLocaleDateString('id-ID'), 20, yPos);
            yPos += 15;
            
            // Statistik Keseluruhan
            doc.setFont('helvetica', 'bold');
            doc.text('STATISTIK KESELURUHAN', 20, yPos);
            yPos += 10;
            
            doc.setFont('helvetica', 'normal');
            doc.text('Total Tes Dikerjakan: <?php echo $total_tes; ?>', 20, yPos);
            yPos += 6;
            doc.text('Skor Rata-rata: <?php echo $rata_rata_keseluruhan; ?>%', 20, yPos);
            yPos += 6;
            doc.text('Skor Tertinggi: <?php echo $skor_tertinggi_keseluruhan; ?>%', 20, yPos);
            yPos += 6;
            doc.text('Skor Terendah: <?php echo $skor_terendah_keseluruhan; ?>%', 20, yPos);
            yPos += 15;
            
            // Analisis Per Mata Pelajaran
            <?php if (!empty($subject_stats)): ?>
            doc.setFont('helvetica', 'bold');
            doc.text('ANALISIS PER MATA PELAJARAN', 20, yPos);
            yPos += 10;
            
            <?php foreach ($subject_stats as $stat): ?>
            doc.setFont('helvetica', 'bold');
            doc.text('<?php echo htmlspecialchars($stat["nama"]); ?>', 20, yPos);
            yPos += 8;
            
            doc.setFont('helvetica', 'normal');
            doc.text('  • Total Tes: <?php echo $stat["total_tes"]; ?>', 25, yPos);
            yPos += 6;
            doc.text('  • Rata-rata: <?php echo $stat["rata_rata"]; ?>%', 25, yPos);
            yPos += 6;
            doc.text('  • Skor Tertinggi: <?php echo $stat["skor_tertinggi"]; ?>%', 25, yPos);
            yPos += 6;
            doc.text('  • Skor Terendah: <?php echo $stat["skor_terendah"]; ?>%', 25, yPos);
            yPos += 6;
            
            <?php 
            $competency = getCompetencyLevel($stat['rata_rata']);
            ?>
            doc.text('  • Level Kompetensi: <?php echo $competency["level"]; ?>', 25, yPos);
            yPos += 10;
            <?php endforeach; ?>
            <?php endif; ?>
            
            // Riwayat Tes (5 terakhir)
            if (yPos > 200) {
                doc.addPage();
                yPos = 20;
            }
            
            doc.setFont('helvetica', 'bold');
            doc.text('RIWAYAT TES (5 TERAKHIR)', 20, yPos);
            yPos += 10;
            
            <?php 
            $recent_sessions = array_slice($sessions, 0, 5);
            foreach ($recent_sessions as $session): 
            $competency = getCompetencyLevel($session['total_skor']);
            ?>
            doc.setFont('helvetica', 'normal');
            doc.text('<?php echo date("d/m/Y H:i", strtotime($session["waktu_selesai"])); ?> - <?php echo htmlspecialchars($session["nama_subject"]); ?>', 20, yPos);
            yPos += 6;
            doc.text('  Skor: <?php echo $session["total_skor"]; ?>% (<?php echo $competency["level"]; ?>)', 25, yPos);
            yPos += 8;
            <?php endforeach; ?>
            
            // Footer
            doc.setFontSize(10);
            doc.setFont('helvetica', 'italic');
            doc.text('Dokumen ini digenerate secara otomatis oleh sistem AKM Clasnet', 105, 280, { align: 'center' });
            
            // Save PDF
            const fileName = 'Laporan_Kompetensi_<?php echo htmlspecialchars($user["username"]); ?>_' + new Date().toISOString().slice(0,10) + '.pdf';
            doc.save(fileName);
        }
    </script>
</body>
</html>