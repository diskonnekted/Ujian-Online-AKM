# Panduan Deployment Aplikasi AKM Online Test

## Informasi Hosting
- **Domain**: akm.orbitdev.id
- **Database Name**: orb44008_akm
- **Database User**: orb44008_akm
- **Database Password**: Dirumah@5474

## Langkah-langkah Deployment

### 1. Upload File ke Hosting

1. **Compress folder deployment**
   - Zip semua file dalam folder `deployment` (kecuali file README ini)
   - Atau upload file satu per satu via File Manager cPanel

2. **Upload ke public_html**
   - Login ke cPanel hosting
   - Buka File Manager
   - Masuk ke folder `public_html`
   - Upload dan extract file zip
   - Pastikan struktur folder seperti ini:
     ```
     public_html/
     ├── index.php
     ├── login.php
     ├── dashboard.php
     ├── admin/
     │   ├── index.php
     │   ├── login.php
     │   └── ...
     ├── config/
     │   └── database.php
     ├── images/
     │   └── nenek_ani.jpg
     └── ...
     ```

### 2. Import Database

1. **Login ke phpMyAdmin**
   - Buka phpMyAdmin dari cPanel
   - Login dengan kredensial database

2. **Buat Database (jika belum ada)**
   - Klik "Databases"
   - Buat database dengan nama: `orb44008_akm`

3. **Import SQL File**
   - Pilih database `orb44008_akm`
   - Klik tab "Import"
   - Choose file: `orb44008_akm.sql`
   - Klik "Go" untuk import

### 3. Konfigurasi File Permissions

Pastikan permission file/folder sesuai:
- **Folder**: 755
- **File PHP**: 644
- **File gambar**: 644

### 4. Testing Aplikasi

1. **Akses Website**
   - Buka: https://akm.orbitdev.id
   - Pastikan halaman utama muncul

2. **Test Login Siswa**
   - Username: `P130100230`
   - Password: `password123`
   - Atau Username: `agus`, Password: `agus123`

3. **Test Login Admin**
   - Buka: https://akm.orbitdev.id/admin/
   - Username: `admin`
   - Password: `admin123`
   - Atau Username: `teacher1`, Password: `teacher123`

4. **File Debug & Troubleshooting**
   - **PHP Version Check**: `https://akm.orbitdev.id/php_version_check.php` (CEK INI DULU!)
   - **PHP Compatibility**: `https://akm.orbitdev.id/check_php_compatibility.php`
   - **Test Questions Simple**: `https://akm.orbitdev.id/test_questions_simple.php`
   - **Check Admin Login**: `https://akm.orbitdev.id/check_admin_login.php`
   - **Fix Admin Password**: `https://akm.orbitdev.id/fix_admin_password.php`
   - **Check Config**: `https://akm.orbitdev.id/check_config.php`
   - **Debug Database**: `https://akm.orbitdev.id/debug_database.php`
   - **Quick Fix**: `https://akm.orbitdev.id/quick_fix.php`
   - **Troubleshooting Guide**: Baca file `TROUBLESHOOTING_SOAL.md`

### 5. Konfigurasi Tambahan (Opsional)

1. **SSL Certificate**
   - Aktifkan SSL di cPanel untuk HTTPS
   - Update URL di konfigurasi jika diperlukan

2. **Error Reporting**
   - Untuk production, set `display_errors = Off` di php.ini
   - Atau tambahkan di .htaccess:
     ```
     php_flag display_errors Off
     ```

3. **Security**
   - Ganti password default admin setelah login pertama
   - Backup database secara berkala

## Struktur Database

### Tabel Utama:
- **users**: Data siswa/peserta
- **admins**: Data admin/guru
- **subjects**: Mata pelajaran
- **tests**: Data tes/ujian
- **questions**: Soal-soal
- **test_sessions**: Sesi ujian siswa
- **user_answers**: Jawaban siswa

### Data Default:
- **Subjects**: Literasi Membaca, Literasi Matematika
- **Admin**: admin/admin123, teacher1/teacher123
- **Siswa**: P130100230/password123, agus/agus123
- **Test**: Tes Baru (11 soal)

## Troubleshooting

### Error Database Connection
- Periksa kredensial database di `config/database.php`
- Pastikan database sudah dibuat dan diimport
- Cek apakah user database memiliki privilege yang cukup

### Error 500 Internal Server Error
- Periksa error log di cPanel
- Pastikan file permission sudah benar
- Cek syntax error di file PHP

### Gambar Tidak Muncul
- Pastikan folder `images/` sudah terupload
- Periksa permission folder images (755)
- Pastikan file `nenek_ani.jpg` ada

## Kontak Support

Jika ada masalah deployment, hubungi:
- Developer: [Your Contact]
- Hosting Support: [Hosting Provider Support]

---
**Catatan**: Simpan file ini sebagai referensi dan jangan upload ke hosting untuk keamanan.