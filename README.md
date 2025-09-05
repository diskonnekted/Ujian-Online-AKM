# Ujian Online AKM (Asesmen Kompetensi Minimum)

Sistem ujian online berbasis web untuk Asesmen Kompetensi Minimum (AKM) yang dikembangkan menggunakan PHP dan MySQL.

## 🚀 Fitur Utama

### Tipe Soal yang Didukung
- **Pilihan Ganda** - Soal dengan satu jawaban benar
- **Benar/Salah** - Soal dengan validasi true/false
- **Pilihan Ganda Kompleks** - Soal dengan multiple jawaban benar
- **Isian Singkat** - Soal dengan input teks dan validasi
- **Drag and Drop** - Soal matching dengan drag and drop interface
- **Urutan/Sequence** - Soal pengurutan dengan validasi sequence

### Fitur Sistem
- ✅ Manajemen sesi ujian
- ✅ Sistem scoring otomatis
- ✅ Laporan kompetensi
- ✅ Interface responsif
- ✅ Validasi JSON untuk jawaban kompleks
- ✅ Dashboard admin
- ✅ Konfirmasi peserta

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache/Nginx
- **Environment**: XAMPP/LAMP/WAMP

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.3+
- Apache/Nginx web server
- Browser modern (Chrome, Firefox, Safari, Edge)

## 🔧 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/diskonnekted/Ujian-Online-AKM.git
cd Ujian-Online-AKM
```

### 2. Setup Database
1. Buat database baru di MySQL:
```sql
CREATE DATABASE akm_online_test;
```

2. Import struktur database:
```bash
mysql -u root -p akm_online_test < database.sql
```

3. (Opsional) Tambahkan data sample:
```bash
mysql -u root -p akm_online_test < add_sample_questions.sql
```

### 3. Konfigurasi Database
Edit file `config/database.php` sesuai dengan pengaturan database Anda:
```php
<?php
$host = 'localhost';
$dbname = 'akm_online_test';
$username = 'root';
$password = '';
?>
```

### 4. Jalankan Aplikasi
1. Pastikan web server berjalan
2. Akses aplikasi melalui browser:
   - Development: `http://localhost/akm`
   - Production: `http://yourdomain.com`

## 📁 Struktur Proyek

```
akm/
├── config/
│   └── database.php          # Konfigurasi database
├── images/
│   └── nenek_ani.jpg         # Asset gambar untuk soal
├── index.php                 # Halaman utama
├── login.php                 # Halaman login
├── dashboard.php             # Dashboard admin
├── test_page.php             # Halaman ujian utama
├── test_confirmation.php     # Konfirmasi sebelum ujian
├── test_result.php           # Hasil ujian
├── test_finish.php           # Halaman selesai ujian
├── participant_confirmation.php # Konfirmasi peserta
├── competency_report.php     # Laporan kompetensi
├── logout.php                # Logout
├── database.sql              # Struktur database
├── add_sample_questions.sql  # Data sample soal
├── fix_questions_table.sql   # Perbaikan tabel
└── update_database_structure.sql # Update struktur DB
```

## 🎯 Cara Penggunaan

### Untuk Administrator
1. Login ke dashboard admin
2. Kelola soal-soal ujian
3. Monitor sesi ujian
4. Lihat laporan hasil ujian

### Untuk Peserta
1. Akses halaman utama
2. Masukkan informasi peserta
3. Konfirmasi data dan mulai ujian
4. Jawab soal sesuai tipe yang diberikan
5. Lihat hasil ujian setelah selesai

## 🔍 Tipe Soal Detail

### Pilihan Ganda
- Soal dengan 4-5 opsi jawaban
- Hanya satu jawaban yang benar
- Scoring: 1 poin untuk jawaban benar

### Benar/Salah
- Soal dengan dua opsi: Benar atau Salah
- Validasi boolean
- Scoring: 1 poin untuk jawaban benar

### Pilihan Ganda Kompleks
- Soal dengan multiple jawaban benar
- Peserta dapat memilih lebih dari satu opsi
- Scoring: Proporsional berdasarkan jawaban benar

### Isian Singkat
- Input teks bebas
- Validasi berdasarkan kata kunci
- Case-insensitive matching

### Drag and Drop
- Interface drag and drop untuk matching
- Cocokkan item dengan pasangannya
- Validasi JSON untuk jawaban

### Urutan/Sequence
- Urutkan item sesuai dengan urutan yang benar
- Validasi sequence
- Scoring berdasarkan ketepatan urutan

## 🛡️ Keamanan

- Validasi input untuk mencegah SQL injection
- Sanitasi data sebelum disimpan ke database
- Validasi JSON untuk jawaban kompleks
- Session management untuk keamanan ujian

## 🐛 Troubleshooting

### Error Database Connection
- Pastikan MySQL service berjalan
- Periksa konfigurasi di `config/database.php`
- Pastikan database sudah dibuat

### Error JSON Validation
- Pastikan kolom `jawaban_kompleks` memiliki constraint JSON valid
- Jalankan `update_database_structure.sql` jika diperlukan

### Error Permission
- Pastikan folder memiliki permission yang tepat
- Set permission 755 untuk folder, 644 untuk file

## 🤝 Kontribusi

1. Fork repository ini
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 Changelog

### v1.0.0
- ✅ Implementasi 6 tipe soal (Pilihan Ganda, Benar/Salah, Pilihan Ganda Kompleks, Isian Singkat, Drag & Drop, Urutan)
- ✅ Sistem scoring otomatis
- ✅ Validasi JSON untuk jawaban kompleks
- ✅ Interface responsif
- ✅ Dashboard admin
- ✅ Laporan kompetensi

## 📄 Lisensi

Proyek ini dilisensikan di bawah MIT License - lihat file [LICENSE](LICENSE) untuk detail.

## 👥 Tim Pengembang

- **Developer**: [diskonnekted](https://github.com/diskonnekted)
- **Repository**: [Ujian-Online-AKM](https://github.com/diskonnekted/Ujian-Online-AKM)

## 📞 Dukungan

Jika Anda mengalami masalah atau memiliki pertanyaan:
1. Buka [Issues](https://github.com/diskonnekted/Ujian-Online-AKM/issues) di GitHub
2. Berikan deskripsi detail tentang masalah yang dialami
3. Sertakan informasi environment (PHP version, MySQL version, OS)

---

**Dibuat dengan ❤️ untuk pendidikan Indonesia**