-- ============================================================
-- AKHLAK360 - Sistem Penilaian 360° Database Schema
-- PT Energi Nusantara
-- ============================================================

CREATE DATABASE IF NOT EXISTS akhlak360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE akhlak360;

-- 1. USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('karyawan','manager','adminhrd','adminit') NOT NULL DEFAULT 'karyawan',
    divisi ENUM('Operations','Finance','IT','HR','Marketing','Legal','Procurement') NOT NULL DEFAULT 'Operations',
    jabatan VARCHAR(50) NOT NULL DEFAULT 'Staff',
    avatar_color VARCHAR(50) DEFAULT '#1565C0',
    status ENUM('aktif','nonaktif','cuti') NOT NULL DEFAULT 'aktif',
    manager_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_role (role), INDEX idx_divisi (divisi), INDEX idx_status (status)
) ENGINE=InnoDB;

-- 2. PERIODE
CREATE TABLE periode (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL, semester ENUM('I','II') NOT NULL, tahun YEAR NOT NULL,
    tanggal_mulai DATE NOT NULL, tanggal_selesai DATE NOT NULL, deadline DATE NOT NULL,
    status ENUM('aktif','ditutup','draft') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status), INDEX idx_tahun (tahun)
) ENGINE=InnoDB;

-- 3. DIMENSI AKHLAK
CREATE TABLE dimensi_akhlak (
    id INT AUTO_INCREMENT PRIMARY KEY, kode VARCHAR(5) UNIQUE NOT NULL,
    nama VARCHAR(20) NOT NULL, deskripsi TEXT, warna VARCHAR(20) DEFAULT '#1B2A4A', urutan INT DEFAULT 0
) ENGINE=InnoDB;

-- 4. PERTANYAAN
CREATE TABLE pertanyaan (
    id INT AUTO_INCREMENT PRIMARY KEY, dimensi_id INT NOT NULL, teks TEXT NOT NULL,
    urutan INT DEFAULT 0, status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    FOREIGN KEY (dimensi_id) REFERENCES dimensi_akhlak(id) ON DELETE CASCADE,
    INDEX idx_dimensi (dimensi_id)
) ENGINE=InnoDB;

-- 5. ASSESSOR MAPPING
CREATE TABLE assessor_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY, periode_id INT NOT NULL, karyawan_id INT NOT NULL,
    assessor_id INT NOT NULL, tipe_assessor ENUM('atasan','peer','bawahan','diri') NOT NULL,
    status ENUM('assigned','draft','submitted','validated') DEFAULT 'assigned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (periode_id) REFERENCES periode(id) ON DELETE CASCADE,
    FOREIGN KEY (karyawan_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assessor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (periode_id, karyawan_id, assessor_id, tipe_assessor),
    INDEX idx_periode (periode_id), INDEX idx_karyawan (karyawan_id), INDEX idx_assessor (assessor_id)
) ENGINE=InnoDB;

-- 6. HASIL PENILAIAN
CREATE TABLE hasil_penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY, mapping_id INT NOT NULL, pertanyaan_id INT NOT NULL,
    nilai INT NOT NULL CHECK (nilai BETWEEN 1 AND 5), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mapping_id) REFERENCES assessor_mapping(id) ON DELETE CASCADE,
    FOREIGN KEY (pertanyaan_id) REFERENCES pertanyaan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_jawaban (mapping_id, pertanyaan_id), INDEX idx_mapping (mapping_id)
) ENGINE=InnoDB;

-- 7. REKAP NILAI
CREATE TABLE rekap_nilai (
    id INT AUTO_INCREMENT PRIMARY KEY, periode_id INT NOT NULL, karyawan_id INT NOT NULL, dimensi_id INT NOT NULL,
    nilai_self DECIMAL(4,2) DEFAULT NULL, nilai_peer DECIMAL(4,2) DEFAULT NULL,
    nilai_atasan DECIMAL(4,2) DEFAULT NULL, nilai_bawahan DECIMAL(4,2) DEFAULT NULL,
    nilai_final DECIMAL(4,2) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (periode_id) REFERENCES periode(id) ON DELETE CASCADE,
    FOREIGN KEY (karyawan_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dimensi_id) REFERENCES dimensi_akhlak(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rekap (periode_id, karyawan_id, dimensi_id),
    INDEX idx_periode (periode_id), INDEX idx_karyawan (karyawan_id)
) ENGINE=InnoDB;

-- 8. CATATAN MANAGER
CREATE TABLE catatan_manager (
    id INT AUTO_INCREMENT PRIMARY KEY, periode_id INT NOT NULL, manager_id INT NOT NULL, karyawan_id INT NOT NULL,
    catatan TEXT, rekomendasi TEXT, status_coaching ENUM('need_coaching','on_track','excellent') DEFAULT 'on_track',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (periode_id) REFERENCES periode(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (karyawan_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_catatan (periode_id, manager_id, karyawan_id), INDEX idx_karyawan (karyawan_id)
) ENGINE=InnoDB;

-- 9. ACTIVITY LOG
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, aksi VARCHAR(100) NOT NULL,
    detail TEXT, tipe ENUM('login','penilaian','admin','system','security') DEFAULT 'system',
    ip_address VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id), INDEX idx_tipe (tipe), INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- 10. SYSTEM CONFIG
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY, config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT, deskripsi VARCHAR(255), updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 11. NOTIFIKASI
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL, tipe ENUM('info','peringatan','success','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0, link VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id), INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- 12. SECURITY ALERTS
CREATE TABLE security_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL, deskripsi TEXT,
    severity ENUM('low','medium','high','critical') DEFAULT 'medium', status ENUM('open','resolved','ignored') DEFAULT 'open',
    source VARCHAR(100), ip_address VARCHAR(45), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, resolved_at TIMESTAMP NULL,
    INDEX idx_severity (severity), INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================
INSERT INTO dimensi_akhlak (kode, nama, deskripsi, warna, urutan) VALUES
('am', 'Amanah', 'Menjalankan tugas dengan penuh tanggung jawab, konsisten, dan menjaga kepercayaan', '#1565C0', 1),
('ko', 'Kompeten', 'Menguasai bidang pekerjaan, terus mengembangkan diri, memberikan solusi', '#E65100', 2),
('ha', 'Harmonis', 'Menjaga hubungan baik, menghargai perbedaan, suasana kerja kondusif', '#2E7D32', 3),
('lo', 'Loyal', 'Menjaga rahasia, menjunjung kepentingan perusahaan, menjadi panutan', '#C62828', 4),
('ad', 'Adaptif', 'Cepat beradaptasi, terbuka terhadap ide baru, mengelola perubahan positif', '#4527A0', 5),
('kol', 'Kolaboratif', 'Aktif berkontribusi, berbagi pengetahuan, membangun sinergi', '#00695C', 6);

INSERT INTO pertanyaan (dimensi_id, teks, urutan) VALUES
(1, 'Menjalankan tugas dengan penuh tanggung jawab', 1),
(1, 'Konsisten antara ucapan dan tindakan', 2),
(1, 'Menjaga kepercayaan perusahaan dan rekan', 3),
(2, 'Menguasai bidang pekerjaan dengan baik', 1),
(2, 'Terus mengembangkan kompetensi diri', 2),
(2, 'Memberikan solusi atas permasalahan', 3),
(3, 'Menjaga hubungan baik dengan rekan kerja', 1),
(3, 'Menghargai perbedaan dan keberagaman', 2),
(3, 'Menjaga suasana kerja yang kondusif', 3),
(4, 'Menjaga rahasia perusahaan', 1),
(4, 'Menjunjung tinggi kepentingan perusahaan', 2),
(4, 'Menjadi panutan dalam bertindak', 3),
(5, 'Cepat beradaptasi dengan perubahan', 1),
(5, 'Terbuka terhadap ide dan inovasi baru', 2),
(5, 'Mampu mengelola perubahan dengan positif', 3),
(6, 'Aktif berkontribusi dalam tim', 1),
(6, 'Berbagi pengetahuan dengan rekan', 2),
(6, 'Membangun sinergi antar divisi', 3);

INSERT INTO periode (nama, semester, tahun, tanggal_mulai, tanggal_selesai, deadline, status) VALUES
('Semester I 2025', 'I', 2025, '2025-01-01', '2025-06-30', '2025-06-20', 'aktif'),
('Semester II 2024', 'II', 2024, '2024-07-01', '2024-12-31', '2024-12-20', 'ditutup');

INSERT INTO system_config (config_key, config_value, deskripsi) VALUES
('company_name', 'PT Energi Nusantara', 'Nama perusahaan'),
('app_version', '1.0', 'Versi aplikasi'),
('session_timeout', '30', 'Timeout sesi dalam menit'),
('mfa_required', 'false', 'Apakah MFA wajib'),
('password_policy', 'strong', 'Kebijakan password'),
('backup_schedule', '02:00', 'Jadwal backup harian'),
('alert_email', 'admin@energinusantara.co.id', 'Email untuk notifikasi alert');
