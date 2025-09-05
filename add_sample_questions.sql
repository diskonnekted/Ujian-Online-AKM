-- Menambahkan soal-soal contoh dengan berbagai tipe

-- Soal Pilihan Ganda
INSERT INTO questions (test_id, pertanyaan, tipe_soal, pilihan_jawaban, jawaban_benar, bobot) VALUES
(1, 'Apa ibu kota Indonesia?', 'pilihan_ganda', '{"A": "Jakarta", "B": "Bandung", "C": "Surabaya", "D": "Medan"}', 'A', 10),
(1, 'Siapa presiden pertama Indonesia?', 'pilihan_ganda', '{"A": "Soekarno", "B": "Soeharto", "C": "Habibie", "D": "Megawati"}', 'A', 10);

-- Soal Benar/Salah
INSERT INTO questions (test_id, pertanyaan, tipe_soal, jawaban_benar, bobot) VALUES
(1, 'Indonesia merdeka pada tanggal 17 Agustus 1945.', 'benar_salah', 'benar', 5),
(1, 'Benua terbesar di dunia adalah Afrika.', 'benar_salah', 'salah', 5),
(1, 'Air mendidih pada suhu 100 derajat Celsius.', 'benar_salah', 'benar', 5);

-- Soal Pilihan Ganda Kompleks
INSERT INTO questions (test_id, pertanyaan, tipe_soal, pilihan_jawaban, jawaban_benar_kompleks, bobot) VALUES
(1, 'Manakah dari berikut ini yang termasuk planet dalam tata surya? (Pilih semua yang benar)', 'pilihan_ganda_kompleks', '{"A": "Mars", "B": "Bulan", "C": "Venus", "D": "Matahari", "E": "Jupiter"}', '["A", "C", "E"]', 15),
(1, 'Pilih semua bilangan prima dari pilihan berikut:', 'pilihan_ganda_kompleks', '{"A": "2", "B": "4", "C": "7", "D": "9", "E": "11"}', '["A", "C", "E"]', 15);

-- Soal Isian Singkat
INSERT INTO questions (test_id, pertanyaan, tipe_soal, jawaban_benar, bobot) VALUES
(1, 'Berapa hasil dari 15 + 27?', 'isian_singkat', '42', 8),
(1, 'Apa nama mata uang Indonesia?', 'isian_singkat', 'Rupiah', 8),
(1, 'Siapa penemu lampu pijar?', 'isian_singkat', 'Thomas Edison', 8);

-- Soal Drag and Drop
INSERT INTO questions (test_id, pertanyaan, tipe_soal, pilihan_jawaban, jawaban_benar, bobot) VALUES
(1, 'Seret jawaban yang tepat untuk melengkapi kalimat: "Fotosintesis adalah proses pembuatan makanan oleh..."', 'drag_drop', '["tumbuhan", "hewan", "manusia", "bakteri"]', 'tumbuhan', 12),
(1, 'Seret nama benua yang memiliki luas terbesar:', 'drag_drop', '["Asia", "Afrika", "Amerika Utara", "Eropa"]', 'Asia', 12);

-- Soal Urutan/Sequence
INSERT INTO questions (test_id, pertanyaan, tipe_soal, pilihan_jawaban, jawaban_benar_kompleks, bobot) VALUES
(1, 'Urutkan tahapan metamorfosis kupu-kupu dari awal hingga akhir:', 'urutan', '["Telur", "Larva (Ulat)", "Pupa (Kepompong)", "Kupu-kupu dewasa"]', '["Telur", "Larva (Ulat)", "Pupa (Kepompong)", "Kupu-kupu dewasa"]', 20),
(1, 'Urutkan planet-planet berikut berdasarkan jarak dari matahari (terdekat ke terjauh):', 'urutan', '["Mars", "Bumi", "Merkurius", "Venus"]', '["Merkurius", "Venus", "Bumi", "Mars"]', 20);

-- Update soal lama yang sudah ada untuk menggunakan format JSON
UPDATE questions SET 
    pilihan_jawaban = '{"A": "Dari teh yang diminum nenek", "B": "Dari obat yang diteteskan nenek", "C": "Dari cairan pembersih", "D": "Dari tinta yang tumpah"}',
    tipe_soal = 'pilihan_ganda'
WHERE id = 1;

UPDATE questions SET 
    pilihan_jawaban = '{"A": "Sangat setuju", "B": "Setuju", "C": "Tidak setuju", "D": "Sangat tidak setuju"}',
    tipe_soal = 'pilihan_ganda'
WHERE id = 2;

UPDATE questions SET 
    pilihan_jawaban = '{"A": "Pilihan A", "B": "Pilihan B", "C": "Pilihan C", "D": "Pilihan D"}',
    tipe_soal = 'pilihan_ganda'
WHERE id = 3;