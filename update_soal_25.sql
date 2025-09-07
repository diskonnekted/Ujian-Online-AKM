USE akm_online_test;

-- Update soal nomor 25 untuk menambahkan pilihan jawaban
UPDATE questions SET 
    pilihan_jawaban = '["Merkurius, Venus, Bumi, Mars, Jupiter, Saturnus, Uranus, Neptunus", "Venus, Merkurius, Bumi, Mars, Jupiter, Saturnus, Uranus, Neptunus", "Bumi, Venus, Merkurius, Mars, Jupiter, Saturnus, Uranus, Neptunus", "Mars, Bumi, Venus, Merkurius, Jupiter, Saturnus, Uranus, Neptunus"]',
    jawaban_benar = 'A'
WHERE id = 26;

-- Verifikasi hasil update
SELECT id, nomor_soal, tipe_soal, pertanyaan, pilihan_jawaban, jawaban_benar 
FROM questions WHERE id = 26;