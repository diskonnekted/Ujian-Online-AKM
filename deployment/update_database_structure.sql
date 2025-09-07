-- Script untuk update struktur database mendukung berbagai tipe soal

-- Modifikasi tabel questions untuk mendukung berbagai tipe soal
ALTER TABLE questions 
MODIFY COLUMN tipe_soal ENUM(
    'pilihan_ganda', 
    'benar_salah', 
    'pilihan_ganda_kompleks', 
    'isian_singkat',
    'drag_drop',
    'urutan',
    'essay'
) DEFAULT 'pilihan_ganda';

-- Tambah kolom untuk mendukung berbagai tipe soal
ALTER TABLE questions ADD COLUMN pilihan_e TEXT AFTER pilihan_d;
ALTER TABLE questions ADD COLUMN jawaban_benar_kompleks JSON AFTER jawaban_benar;
ALTER TABLE questions ADD COLUMN jawaban_benar_text TEXT AFTER jawaban_benar_kompleks;
ALTER TABLE questions ADD COLUMN is_case_sensitive BOOLEAN DEFAULT FALSE AFTER jawaban_benar_text;
ALTER TABLE questions ADD COLUMN drag_drop_pairs JSON AFTER is_case_sensitive;
ALTER TABLE questions ADD COLUMN sequence_items JSON AFTER drag_drop_pairs;
ALTER TABLE questions ADD COLUMN correct_sequence JSON AFTER sequence_items;

-- Modifikasi tabel user_answers untuk mendukung berbagai tipe jawaban
ALTER TABLE user_answers ADD COLUMN jawaban_kompleks JSON AFTER jawaban;
ALTER TABLE user_answers ADD COLUMN waktu_jawab INT AFTER jawaban_kompleks;

SELECT 'Database structure updated successfully' as status;