<?php
session_start();
require_once 'config/database.php';
// require_once 'vendor/autoload.php'; // Commented out - using alternative PDF solution

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

// Generate HTML report that can be printed as PDF
// Using HTML/CSS instead of TCPDF for better compatibility

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Add CSS for print styling
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Analisis AKM - ' . htmlspecialchars($user['username']) . '</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
        }
        .header {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .signature-table {
            border: none;
        }
        .signature-table td {
            border: none;
            text-align: center;
        }
        .print-button {
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #007cba;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>';

// Add print button
echo '<div class="no-print">
    <button class="print-button" onclick="window.print()">Cetak/Save sebagai PDF</button>
    <p><strong>Petunjuk:</strong> Klik tombol "Cetak/Save sebagai PDF" di atas, lalu pilih "Save as PDF" atau "Microsoft Print to PDF" sebagai printer untuk menyimpan sebagai file PDF.</p>
</div>';

// Add header
echo '<div class="header">Laporan Hasil Analisis AKM</div>';

// Informasi Sekolah dan Siswa
echo '
<table>
    <tr>
        <td width="20%"><strong>Sekolah</strong></td>
        <td width="5%">:</td>
        <td width="75%">CLASNET ACADEMY</td>
    </tr>
    <tr>
        <td><strong>Nama Siswa</strong></td>
        <td>:</td>
        <td>' . htmlspecialchars($user['username']) . '</td>
    </tr>
    <tr>
        <td><strong>Judul</strong></td>
        <td>:</td>
        <td>Analisis Hasil Asesmen Kompetensi Minimum (AKM) Siswa</td>
    </tr>
    <tr>
        <td><strong>Tanggal Laporan</strong></td>
        <td>:</td>
        <td>' . date('d F Y') . '</td>
    </tr>
</table>
<br>

<h3>Ringkasan Hasil Tes</h3>
<table>
    <tr>
        <th>Total Tes Diikuti</th>
        <td>' . $total_tes . '</td>
    </tr>
    <tr>
        <th>Rata-rata Skor</th>
        <td>' . $rata_rata_keseluruhan . '</td>
    </tr>
    <tr>
        <th>Skor Tertinggi</th>
        <td>' . $skor_tertinggi_keseluruhan . '</td>
    </tr>
    <tr>
        <th>Skor Terendah</th>
        <td>' . $skor_terendah_keseluruhan . '</td>
    </tr>
</table>
<br>

<h3>Detail Hasil per Mata Pelajaran</h3>
<table>
    <tr>
        <th>Mata Pelajaran</th>
        <th>Jumlah Tes</th>
        <th>Rata-rata</th>
        <th>Skor Tertinggi</th>
        <th>Skor Terendah</th>
        <th>Level Kompetensi</th>
    </tr>';

foreach ($subject_stats as $stat) {
    $competency = getCompetencyLevel($stat['rata_rata']);
    echo '<tr>
        <td>' . htmlspecialchars($stat['nama']) . '</td>
        <td>' . $stat['total_tes'] . '</td>
        <td>' . $stat['rata_rata'] . '</td>
        <td>' . $stat['skor_tertinggi'] . '</td>
        <td>' . $stat['skor_terendah'] . '</td>
        <td style="color: ' . $competency['color'] . ';">' . $competency['level'] . '</td>
    </tr>';
}

echo '</table>
<br>

<h3>Rekomendasi</h3>
<ul>
    <li><strong>Literasi Membaca:</strong> Perbanyak kegiatan membaca, gunakan berbagai jenis teks, latih keterampilan berpikir kritis</li>
    <li><strong>Numerasi:</strong> Perkuat pemahaman konsep matematika, gunakan media pembelajaran yang menarik, berikan soal-soal yang bervariasi</li>
    <li><strong>Konsistensi:</strong> Pertahankan rutinitas belajar yang teratur dan evaluasi berkala</li>
</ul>

<h3>Kesimpulan</h3>
<p>Berdasarkan hasil analisis, siswa menunjukkan perkembangan yang baik dalam mengikuti tes AKM. Perlu dilakukan perbaikan berkelanjutan untuk meningkatkan kualitas pembelajaran.</p>

<br><br>

<table class="signature-table">
    <tr>
        <td width="50%"></td>
        <td width="50%">
            PINRANG, ' . date('d F Y') . '<br><br><br>
            MENGETAHUI<br>
            KEPALA SEKOLAH<br><br><br><br><br>
            KETUA GOMBEL
        </td>
    </tr>
</table>

</body>
</html>';
?>