-- Admin Dashboard Database Structure
-- Tambahan tabel untuk dashboard admin AKM

-- Tabel untuk guru/admin
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `nip` varchar(20) DEFAULT NULL,
  `mata_pelajaran` varchar(50) DEFAULT NULL,
  `role` enum('admin','teacher') DEFAULT 'teacher',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel untuk kategori soal
CREATE TABLE IF NOT EXISTS `question_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel untuk tracking aktivitas admin
CREATE TABLE IF NOT EXISTS `admin_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','create_question','edit_question','delete_question','view_report') NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert data admin default jika belum ada
INSERT IGNORE INTO `teachers` (`username`, `password`, `nama_lengkap`, `email`, `role`, `mata_pelajaran`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@akm.edu', 'admin', 'Semua'),
('guru1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guru Literasi', 'guru1@akm.edu', 'teacher', 'Literasi Membaca'),
('guru2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guru Matematika', 'guru2@akm.edu', 'teacher', 'Matematika');

-- Insert kategori soal default jika belum ada
INSERT IGNORE INTO `question_categories` (`nama_kategori`, `deskripsi`) VALUES
('Literasi Membaca', 'Soal-soal untuk mengukur kemampuan literasi membaca'),
('Numerasi', 'Soal-soal untuk mengukur kemampuan numerasi/matematika'),
('Sains', 'Soal-soal untuk mengukur kemampuan sains'),
('Sosial', 'Soal-soal untuk mengukur kemampuan sosial');

COMMIT;