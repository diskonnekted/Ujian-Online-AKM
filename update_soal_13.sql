USE akm_online_test;

UPDATE questions SET 
    pilihan_jawaban = '["Asia", "Afrika", "Amerika Utara", "Amerika Selatan", "Antartika", "Eropa", "Australia"]',
    sequence_items = '["Asia", "Afrika", "Amerika Utara", "Amerika Selatan", "Antartika", "Eropa", "Australia"]',
    correct_sequence = '["Asia", "Afrika", "Amerika Utara", "Amerika Selatan", "Eropa", "Australia", "Antartika"]'
WHERE id = 16;

SELECT id, nomor_soal, tipe_soal, pertanyaan, pilihan_jawaban FROM questions WHERE id = 16;