-- ============================================================
-- NEXUS CAMPUS PORTAL - Database Schema
-- Fictional Academic System | PHP + MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS nexus_campus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexus_campus;

-- ------------------------------------------------------------
-- TABEL PENGGUNA (Users)
-- ------------------------------------------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role        ENUM('mahasiswa','dosen','admin') NOT NULL DEFAULT 'mahasiswa',
    avatar      VARCHAR(255) DEFAULT NULL,
    status      ENUM('aktif','nonaktif') DEFAULT 'aktif',
    last_login  DATETIME DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL MAHASISWA (Student Profile)
-- ------------------------------------------------------------
CREATE TABLE mahasiswa (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    nim         VARCHAR(20) UNIQUE NOT NULL,
    prodi       VARCHAR(100) NOT NULL,
    fakultas    VARCHAR(100) NOT NULL,
    angkatan    YEAR NOT NULL,
    semester    TINYINT DEFAULT 1,
    ipk         DECIMAL(3,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL DOSEN (Lecturer Profile)
-- ------------------------------------------------------------
CREATE TABLE dosen (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    nidn        VARCHAR(20) UNIQUE NOT NULL,
    prodi       VARCHAR(100) NOT NULL,
    jabatan     VARCHAR(100) DEFAULT 'Dosen Pengajar',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL MATA KULIAH (Courses)
-- ------------------------------------------------------------
CREATE TABLE mata_kuliah (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(20) UNIQUE NOT NULL,
    nama        VARCHAR(150) NOT NULL,
    sks         TINYINT NOT NULL DEFAULT 3,
    dosen_id    INT NOT NULL,
    semester    TINYINT NOT NULL,
    prodi       VARCHAR(100) NOT NULL,
    deskripsi   TEXT DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL ENROLLMENT
-- ------------------------------------------------------------
CREATE TABLE enrollment (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id    INT NOT NULL,
    matkul_id       INT NOT NULL,
    tahun_ajaran    VARCHAR(10) DEFAULT '2025/2026',
    status          ENUM('aktif','selesai','drop') DEFAULT 'aktif',
    enrolled_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enroll (mahasiswa_id, matkul_id, tahun_ajaran),
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (matkul_id)    REFERENCES mata_kuliah(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL NILAI (Grades)
-- ------------------------------------------------------------
CREATE TABLE nilai (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id   INT NOT NULL UNIQUE,
    nilai_tugas     DECIMAL(5,2) DEFAULT 0,
    nilai_uts       DECIMAL(5,2) DEFAULT 0,
    nilai_uas       DECIMAL(5,2) DEFAULT 0,
    nilai_akhir     DECIMAL(5,2) GENERATED ALWAYS AS (
                        (nilai_tugas * 0.30) + (nilai_uts * 0.30) + (nilai_uas * 0.40)
                    ) STORED,
    grade           CHAR(2) GENERATED ALWAYS AS (
                        CASE
                            WHEN (nilai_tugas * 0.30 + nilai_uts * 0.30 + nilai_uas * 0.40) >= 85 THEN 'A'
                            WHEN (nilai_tugas * 0.30 + nilai_uts * 0.30 + nilai_uas * 0.40) >= 75 THEN 'B'
                            WHEN (nilai_tugas * 0.30 + nilai_uts * 0.30 + nilai_uas * 0.40) >= 65 THEN 'C'
                            WHEN (nilai_tugas * 0.30 + nilai_uts * 0.30 + nilai_uas * 0.40) >= 55 THEN 'D'
                            ELSE 'E'
                        END
                    ) STORED,
    updated_by      INT DEFAULT NULL,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollment(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL TUGAS (Assignments)
-- ------------------------------------------------------------
CREATE TABLE tugas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    matkul_id   INT NOT NULL,
    judul       VARCHAR(200) NOT NULL,
    deskripsi   TEXT,
    deadline    DATETIME NOT NULL,
    max_score   DECIMAL(5,2) DEFAULT 100,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matkul_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL PENGUMPULAN TUGAS (Submissions)
-- ------------------------------------------------------------
CREATE TABLE pengumpulan_tugas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tugas_id        INT NOT NULL,
    mahasiswa_id    INT NOT NULL,
    file_path       VARCHAR(500) DEFAULT NULL,
    catatan         TEXT DEFAULT NULL,
    nilai           DECIMAL(5,2) DEFAULT NULL,
    feedback        TEXT DEFAULT NULL,
    status          ENUM('submitted','graded','late') DEFAULT 'submitted',
    submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    graded_at       DATETIME DEFAULT NULL,
    UNIQUE KEY unique_submit (tugas_id, mahasiswa_id),
    FOREIGN KEY (tugas_id)      REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (mahasiswa_id)  REFERENCES mahasiswa(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL FORUM DISKUSI
-- ------------------------------------------------------------
CREATE TABLE forum (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    matkul_id   INT NOT NULL,
    user_id     INT NOT NULL,
    judul       VARCHAR(200) NOT NULL,
    konten      TEXT NOT NULL,
    views       INT DEFAULT 0,
    pinned      TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matkul_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL BALASAN FORUM
-- ------------------------------------------------------------
CREATE TABLE forum_balasan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    forum_id    INT NOT NULL,
    user_id     INT NOT NULL,
    konten      TEXT NOT NULL,
    is_solution TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id)  REFERENCES forum(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABEL NOTIFIKASI
-- ------------------------------------------------------------
CREATE TABLE notifikasi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    judul       VARCHAR(200) NOT NULL,
    pesan       TEXT NOT NULL,
    type        ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA SINTETIS
-- Password semua akun: "password123" (bcrypt hash)
-- ============================================================

INSERT INTO users (username, email, nama_lengkap, role, password) VALUES
('admin', 'admin@nexuscampus.id', 'Super Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO users (username, email, nama_lengkap, role, password) VALUES
('dr.andi',   'andi.pratama@nexuscampus.id',   'Dr. Andi Pratama, M.Kom',    'dosen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('prof.sari', 'sari.dewi@nexuscampus.id',      'Prof. Sari Dewi, Ph.D',      'dosen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('dr.reza',   'reza.firmansyah@nexuscampus.id','Dr. Reza Firmansyah, M.T',   'dosen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('ir.maya',   'maya.kusuma@nexuscampus.id',    'Ir. Maya Kusuma, M.Sc',      'dosen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO dosen (user_id, nidn, prodi, jabatan) VALUES
(2, '0312078901', 'Teknik Informatika', 'Lektor Kepala'),
(3, '0425069002', 'Sistem Informasi',   'Guru Besar'),
(4, '0508079103', 'Teknik Informatika', 'Lektor'),
(5, '0617088804', 'Teknik Informatika', 'Asisten Ahli');

INSERT INTO users (username, email, nama_lengkap, role, password) VALUES
('budi2022',   'budi.santoso@student.nexus.id',   'Budi Santoso',         'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('citra2022',  'citra.lestari@student.nexus.id',  'Citra Lestari',        'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('dimas2023',  'dimas.rahardjo@student.nexus.id', 'Dimas Rahardjo',       'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('eka2023',    'eka.putri@student.nexus.id',      'Eka Putri Handayani',  'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('fajar2024',  'fajar.nugroho@student.nexus.id',  'Fajar Nugroho',        'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('gita2024',   'gita.ramadhani@student.nexus.id', 'Gita Ramadhani',       'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('hendra2022', 'hendra.wijaya@student.nexus.id',  'Hendra Wijaya',        'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('indira2023', 'indira.sari@student.nexus.id',    'Indira Sari Utami',    'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO mahasiswa (user_id, nim, prodi, fakultas, angkatan, semester) VALUES
(6,  '22TIF001', 'Teknik Informatika', 'Fakultas Teknik', 2022, 7),
(7,  '22TIF002', 'Teknik Informatika', 'Fakultas Teknik', 2022, 7),
(8,  '23TIF001', 'Teknik Informatika', 'Fakultas Teknik', 2023, 5),
(9,  '23SI001',  'Sistem Informasi',   'Fakultas Teknik', 2023, 5),
(10, '24TIF001', 'Teknik Informatika', 'Fakultas Teknik', 2024, 3),
(11, '24TIF002', 'Teknik Informatika', 'Fakultas Teknik', 2024, 3),
(12, '22TIF003', 'Teknik Informatika', 'Fakultas Teknik', 2022, 7),
(13, '23SI002',  'Sistem Informasi',   'Fakultas Teknik', 2023, 5);

INSERT INTO mata_kuliah (kode, nama, sks, dosen_id, semester, prodi, deskripsi) VALUES
('TIF601', 'Kecerdasan Buatan',           3, 1, 6, 'Teknik Informatika', 'Mempelajari algoritma dan teknik AI modern'),
('TIF603', 'Keamanan Siber',              3, 1, 6, 'Teknik Informatika', 'Konsep keamanan jaringan dan ethical hacking'),
('TIF501', 'Rekayasa Perangkat Lunak',    3, 4, 5, 'Teknik Informatika', 'SDLC, design patterns, dan metodologi software'),
('SI501',  'Sistem Basis Data Lanjut',    3, 2, 5, 'Sistem Informasi',   'Advanced SQL, NoSQL, dan database administration'),
('TIF301', 'Algoritma dan Struktur Data', 3, 3, 3, 'Teknik Informatika', 'Kompleksitas algoritma, trees, dan graphs'),
('TIF303', 'Pemrograman Web',             2, 4, 3, 'Teknik Informatika', 'HTML, CSS, JavaScript, PHP, dan framework modern');

INSERT INTO enrollment (mahasiswa_id, matkul_id) VALUES
(1,1),(1,2),(2,1),(2,2),(3,3),(3,5),(4,4),(5,5),(5,6),(6,5),(6,6),(7,1),(8,4);

INSERT INTO nilai (enrollment_id, nilai_tugas, nilai_uts, nilai_uas, updated_by) VALUES
(1,88,82,90,2),(2,75,70,78,2),(3,92,88,95,2),(4,80,76,82,2),
(5,85,79,88,4),(6,70,65,72,3),(7,78,82,80,2),(8,88,90,92,3),
(9,72,68,75,3),(10,80,75,82,4),(11,65,60,70,4),(12,90,85,88,2),(13,76,80,78,2);

INSERT INTO tugas (matkul_id, judul, deskripsi, deadline) VALUES
(1, 'Implementasi Neural Network Sederhana', 'Buat neural network feedforward dari scratch.', '2026-10-25 23:59:00'),
(1, 'Analisis Dataset dengan Machine Learning', 'Lakukan klasifikasi menggunakan 3 algoritma ML.', '2026-11-10 23:59:00'),
(2, 'Penetration Testing Report', 'Lakukan pentest pada environment lab dan buat laporan.', '2026-10-20 23:59:00'),
(3, 'Desain Sistem dengan UML', 'Buat diagram UML lengkap untuk sistem e-commerce.', '2026-10-30 23:59:00'),
(5, 'Implementasi Binary Search Tree', 'Implementasikan BST dengan operasi lengkap.', '2026-10-18 23:59:00'),
(6, 'Aplikasi CRUD dengan PHP', 'Buat aplikasi web CRUD dengan PHP native dan MySQL.', '2026-11-05 23:59:00');

INSERT INTO forum (matkul_id, user_id, judul, konten, views, pinned) VALUES
(1, 6, '[Pinned] Resource Belajar AI 2025', 'Kumpulan link dan buku terbaik untuk belajar AI semester ini...', 245, 1),
(1, 2, 'Bagaimana cara implement backpropagation dari scratch?', 'Masih bingung dengan chain rule saat implementasi backprop.', 87, 0),
(2, 7, 'Tools untuk network scanning yang recommended?', 'Tools apa yang worth it dipelajari selain Nmap?', 134, 0),
(3, 8, 'Perbedaan Waterfall vs Agile di dunia nyata?', 'Dari pengalaman yang sudah kerja, mana yang lebih sering dipakai?', 203, 0),
(5, 9, 'Kompleksitas O(log n) pada Binary Search', 'Tolong jelasin kenapa binary search O(log n).', 156, 0);

INSERT INTO forum_balasan (forum_id, user_id, konten, is_solution) VALUES
(2, 2, 'Chain rule intinya aturan turunan fungsi komposisi. f(g(x)) = f''(g(x)) * g''(x). Terapkan berlayer-layer dari output ke input.', 1),
(2, 6, 'Baca "Neural Networks and Deep Learning" oleh Michael Nielsen, gratis online!', 0),
(3, 2, 'Nmap, Wireshark, Metasploit, dan Burp Suite untuk web security. Semua ada versi free.', 1),
(5, 3, 'Binary search membelah array jadi 2 bagian. Worst case: log2(n) langkah karena tiap step elemen berkurang setengah.', 1);

INSERT INTO notifikasi (user_id, judul, pesan, type) VALUES
(6, 'Nilai Kecerdasan Buatan Diupdate', 'Dosen telah memasukkan nilai tugas Anda.', 'success'),
(6, 'Tugas Baru: Neural Network', 'Dr. Andi menambahkan tugas baru: Implementasi Neural Network.', 'info'),
(7, 'Nilai Diupdate', 'Nilai Kecerdasan Buatan telah diperbarui.', 'success'),
(8, 'Deadline Tugas Besok!', 'Tugas BST deadline besok jam 23:59.', 'warning'),
(2, 'Pertanyaan Forum Terjawab', 'Pertanyaan Anda mendapat jawaban terpilih.', 'info'),
(10, 'Selamat Datang di NexusCampus!', 'Akun Anda berhasil dibuat. Mulai belajar!', 'success');
