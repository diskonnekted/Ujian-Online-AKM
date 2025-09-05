-- Menambahkan kolom pilihan_jawaban untuk menyimpan JSON options
ALTER TABLE questions ADD COLUMN pilihan_jawaban LONGTEXT AFTER gambar;

-- Menambahkan kolom bobot untuk scoring
ALTER TABLE questions ADD COLUMN bobot INT DEFAULT 10 AFTER correct_sequence;

-- Update existing questions dengan format JSON
UPDATE questions SET 
    pilihan_jawaban = CONCAT('{"A": "', IFNULL(pilihan_a, ''), '", "B": "', IFNULL(pilihan_b, ''), '", "C": "', IFNULL(pilihan_c, ''), '", "D": "', IFNULL(pilihan_d, ''), '"', 
    CASE WHEN pilihan_e IS NOT NULL AND pilihan_e != '' THEN CONCAT(', "E": "', pilihan_e, '"') ELSE '' END, '}')
WHERE tipe_soal = 'pilihan_ganda' AND (pilihan_a IS NOT NULL OR pilihan_b IS NOT NULL);

-- Set default bobot untuk soal yang sudah ada
UPDATE questions SET bobot = 10 WHERE bobot IS NULL;