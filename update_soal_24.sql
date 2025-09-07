USE akm_online_test;

-- Update soal nomor 24 untuk tipe urutan
UPDATE questions SET 
    pertanyaan = 'Urutkan tahapan metamorfosis kupu-kupu dari awal hingga akhir:',
    pilihan_jawaban = '["Telur", "Larva (Ulat)", "Pupa (Kepompong)", "Kupu-kupu dewasa"]',
    sequence_items = '["Telur", "Larva (Ulat)", "Pupa (Kepompong)", "Kupu-kupu dewasa"]',
    correct_sequence = '["Telur", "Larva (Ulat)", "Pupa (Kepompong)", "Kupu-kupu dewasa"]'
WHERE id = 25;

-- Verifikasi hasil update
SELECT id, nomor_soal, tipe_soal, pertanyaan, pilihan_jawaban, sequence_items, correct_sequence 
FROM questions WHERE id = 25;