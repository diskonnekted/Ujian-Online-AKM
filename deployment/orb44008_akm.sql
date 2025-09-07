-- Database untuk Sistem Ujian Online AKM
-- Untuk hosting akm.orbitdev.id
-- Database: orb44008_akm

-- Tabel untuk menyimpan data pengguna/siswa
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    kelas VARCHAR(20) NOT NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan data admin/guru
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'teacher') DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan mata pelajaran
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_subject VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan tes/ujian
CREATE TABLE tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    nama_tes VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    durasi_menit INT NOT NULL DEFAULT 60,
    jumlah_soal INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

-- Tabel untuk menyimpan soal-soal
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    nomor_soal INT NOT NULL,
    tipe_soal ENUM('pilihan_ganda', 'essay') DEFAULT 'pilihan_ganda',
    pertanyaan TEXT NOT NULL,
    gambar VARCHAR(255),
    pilihan_a TEXT,
    pilihan_b TEXT,
    pilihan_c TEXT,
    pilihan_d TEXT,
    jawaban_benar CHAR(1),
    poin INT DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id),
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

-- Tabel untuk menyimpan sesi ujian siswa
CREATE TABLE test_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    token VARCHAR(100) UNIQUE NOT NULL,
    waktu_mulai TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_selesai TIMESTAMP NULL,
    status ENUM('ongoing', 'completed', 'expired') DEFAULT 'ongoing',
    total_skor INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (test_id) REFERENCES tests(id)
);

-- Tabel untuk menyimpan jawaban siswa
CREATE TABLE user_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    jawaban TEXT,
    is_correct BOOLEAN DEFAULT FALSE,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES test_sessions(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- Insert data mata pelajaran default
INSERT INTO subjects (nama_subject, deskripsi, icon) VALUES 
('Literasi Membaca', 'Asesmen Kompetensi Minimum - Literasi Membaca', 'book-icon'),
('Literasi Matematika', 'Asesmen Kompetensi Minimum - Numerasi', 'math-icon');

-- Insert admin default
INSERT INTO admins (username, password, nama_lengkap, email, role) VALUES 
('admin', MD5('admin123'), 'Administrator', 'admin@akm.orbitdev.id', 'admin'),
('teacher1', MD5('teacher123'), 'Guru Pertama', 'teacher1@akm.orbitdev.id', 'teacher');

-- Insert contoh user untuk testing
INSERT INTO users (username, password, nama_lengkap, kelas, jenis_kelamin, tanggal_lahir) VALUES 
('P130100230', MD5('password123'), 'Peserta 01', 'SMP/MTs/PAKET B', 'Laki-laki', '2008-01-01'),
('agus', MD5('agus123'), 'Agus Setiawan', 'SMP/MTs/PAKET B', 'Laki-laki', '2008-05-15');

-- Insert contoh tes
INSERT INTO tests (subject_id, nama_tes, deskripsi, durasi_menit, jumlah_soal) VALUES 
(1, 'Tes Baru', 'Tes Literasi Membaca AKM', 60, 11);

-- Insert contoh soal berdasarkan gambar
INSERT INTO questions (test_id, nomor_soal, pertanyaan, gambar, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, created_by) VALUES 
(1, 1, 'Nenek menggunakan tetes-tetes cairan coklat kental pada secarik kain putih. Dari mana tetes-tetes cairan itu berasal?', 'nenek_ani.jpg', 'Dari teh yang diminum nenek', 'Dari obat yang diteteskan nenek', 'Dari cairan pembersih', 'Dari tinta yang tumpah', 'B', 1),
(1, 2, 'Berdasarkan teks, apa yang paling mungkin terjadi setelah nenek meneteskan cairan coklat pada kain?', NULL, 'Kain menjadi bersih dan putih kembali', 'Kain berubah warna menjadi coklat', 'Kain robek karena cairan', 'Kain menjadi lebih tebal', 'B', 1),
(1, 3, 'Mengapa nenek menggunakan kain putih untuk aktivitas tersebut?', NULL, 'Karena kain putih lebih murah', 'Karena kain putih mudah dibersihkan', 'Karena perubahan warna lebih terlihat jelas', 'Karena kain putih lebih kuat', 'C', 1),
(1, 4, 'Dari konteks cerita, aktivitas nenek kemungkinan besar adalah...', NULL, 'Membersihkan rumah', 'Merawat kesehatan', 'Memasak makanan', 'Mencuci pakaian', 'B', 1),
(1, 5, 'Kata "tetes-tetes" dalam kalimat menunjukkan bahwa cairan tersebut...', NULL, 'Sangat banyak jumlahnya', 'Diberikan dalam jumlah sedikit dan hati-hati', 'Tidak sengaja tumpah', 'Sudah kering dan mengeras', 'B', 1),
(1, 6, 'Jika 3x + 5 = 20, maka nilai x adalah...', NULL, '3', '5', '7', '15', 'B', 1),
(1, 7, 'Sebuah persegi panjang memiliki panjang 12 cm dan lebar 8 cm. Luas persegi panjang tersebut adalah...', NULL, '20 cm²', '40 cm²', '96 cm²', '160 cm²', 'C', 1),
(1, 8, 'Hasil dari 15% dari 200 adalah...', NULL, '15', '20', '30', '35', 'C', 1),
(1, 9, 'Jika sebuah segitiga memiliki alas 10 cm dan tinggi 6 cm, maka luasnya adalah...', NULL, '16 cm²', '30 cm²', '60 cm²', '120 cm²', 'B', 1),
(1, 10, 'Manakah dari bilangan berikut yang merupakan bilangan prima?', NULL, '15', '21', '23', '27', 'C', 1),
(1, 11, 'Rata-rata dari angka 8, 12, 15, dan 9 adalah...', NULL, '10', '11', '12', '13', 'B', 1);