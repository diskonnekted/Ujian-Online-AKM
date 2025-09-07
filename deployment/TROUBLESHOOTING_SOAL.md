# Troubleshooting: Soal Tidak Muncul di Hosting

## Masalah
Soal muncul lengkap di localhost tapi tidak muncul sama sekali di server hosting.

## Langkah Troubleshooting

### 1. Cek Koneksi Database
- Akses: `https://akm.orbitdev.id/check_config.php`
- File ini akan menampilkan:
  - Status koneksi database
  - Jumlah records di setiap tabel
  - Konfigurasi database yang digunakan

### 2. Debug Database Lengkap
- Akses: `https://akm.orbitdev.id/debug_database.php`
- File ini akan menampilkan:
  - Semua data di tabel subjects, tests, questions, users
  - Test query yang sama dengan test_page.php
  - Informasi PHP dan PDO

### 3. Test Halaman dengan Debug
- File `test_page.php` sudah ditambahkan debug code
- Jika soal tidak ditemukan, akan muncul informasi debug:
  - Test ID yang digunakan
  - Jumlah soal yang ditemukan
  - Total soal di database
  - Daftar soal untuk test_id tertentu

## Kemungkinan Penyebab

### A. Database Belum Diimport dengan Benar
**Solusi:**
1. Login ke cPanel hosting
2. Buka phpMyAdmin
3. Pilih database `orb44008_akm`
4. Import file `orb44008_akm.sql`
5. Pastikan semua tabel ter-create dengan data

### B. Kredensial Database Salah
**Cek di file:** `config/database.php`
```php
$host = 'localhost';
$db_name = 'orb44008_akm';
$username = 'orb44008_akm';
$password = 'Dirumah@5474';
```

### C. Struktur Tabel Berbeda
**Kemungkinan:**
- Tabel `questions` tidak ada
- Kolom di tabel `questions` berbeda
- Foreign key constraint error

### D. Data Tidak Ter-insert
**Cek:**
- Apakah tabel `tests` memiliki data?
- Apakah tabel `questions` memiliki data?
- Apakah `test_id` di tabel `questions` sesuai dengan `id` di tabel `tests`?

### E. PHP Error atau Warning
**Solusi:**
- Enable error reporting di hosting
- Cek error log hosting
- Pastikan PHP version compatible

## Langkah Perbaikan

### 1. Jika Database Kosong
```sql
-- Re-import database
DROP DATABASE IF EXISTS orb44008_akm;
CREATE DATABASE orb44008_akm;
USE orb44008_akm;
-- Import orb44008_akm.sql
```

### 2. Jika Koneksi Gagal
- Cek kredensial database di cPanel
- Update file `config/database.php`
- Test koneksi dengan `check_config.php`

### 3. Jika Tabel Ada tapi Data Kosong
```sql
-- Cek data di tabel
SELECT COUNT(*) FROM questions;
SELECT COUNT(*) FROM tests;
SELECT * FROM tests LIMIT 5;
SELECT * FROM questions LIMIT 5;
```

### 4. Jika Foreign Key Error
```sql
-- Cek foreign key constraints
SHOW CREATE TABLE questions;
SELECT * FROM tests WHERE id = 1;
SELECT * FROM questions WHERE test_id = 1;
```

## File Debug yang Tersedia

1. **check_config.php** - Cek konfigurasi dan koneksi database
2. **debug_database.php** - Debug lengkap semua tabel dan data
3. **test_page.php** - Sudah ada debug code untuk troubleshooting soal
4. **php_version_check.php** - Cek versi PHP dan kompatibilitas
5. **check_php_compatibility.php** - Test kompatibilitas PHP lengkap
6. **test_questions_simple.php** - Test sederhana pengambilan soal
7. **quick_fix.php** - Tool perbaikan cepat database

## 5. Kemungkinan Penyebab Lain

- **File gambar tidak terupload** ke hosting
- **Path gambar salah** di hosting
- **Permission file/folder** tidak sesuai
- **PHP version** berbeda antara localhost dan hosting
- **MySQL version** berbeda
- **Konfigurasi server** berbeda (mod_rewrite, dll)

## 6. Masalah Versi PHP

### Cek Versi PHP
1. Akses `php_version_check.php` di browser
2. Bandingkan versi PHP hosting dengan localhost
3. Pastikan versi PHP minimal 7.4.0

### Gejala Masalah Versi PHP:
- Soal tidak muncul sama sekali
- Error "syntax error" atau "unexpected token"
- Fungsi PHP tidak bekerja dengan benar
- Database connection gagal

### Solusi Versi PHP:
1. **Jika PHP < 7.4:**
   - Hubungi provider hosting untuk upgrade PHP
   - Atau pindah ke hosting dengan PHP modern
   - Cek panel kontrol hosting untuk opsi PHP version

2. **Jika PHP sudah 7.4+:**
   - Masalah bukan di versi PHP
   - Lanjut ke troubleshooting database dan konfigurasi
   - Cek `check_php_compatibility.php` untuk test lengkap

### Test Kompatibilitas:
- Jalankan `check_php_compatibility.php`
- Jalankan `test_questions_simple.php`
- Bandingkan hasil dengan localhost

## Kontak Support

Jika masalah masih berlanjut:
1. Screenshot hasil dari `check_config.php`
2. Screenshot hasil dari `debug_database.php`
3. Screenshot error yang muncul di `test_page.php`
4. Kirim ke support hosting atau developer

## Checklist Verifikasi

- [ ] Database `orb44008_akm` sudah dibuat
- [ ] File `orb44008_akm.sql` sudah diimport
- [ ] Kredensial database di `config/database.php` benar
- [ ] Koneksi database berhasil (cek via `check_config.php`)
- [ ] Tabel `questions` ada dan berisi data
- [ ] Tabel `tests` ada dan berisi data
- [ ] Foreign key `test_id` di tabel `questions` sesuai dengan `id` di tabel `tests`
- [ ] PHP error reporting enabled untuk debugging
- [ ] File gambar soal sudah diupload ke folder `images/`

---

**Catatan:** File troubleshooting ini dibuat untuk membantu mendiagnosis masalah soal tidak muncul di hosting. Ikuti langkah-langkah secara berurutan untuk menemukan akar masalah.