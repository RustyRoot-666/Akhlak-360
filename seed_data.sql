
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- SEED DATA USERS
-- ============================================================
INSERT INTO users (id, nik, nama, email, password_hash, role, divisi, jabatan, avatar_color, status, manager_id) VALUES
(1, '240188', 'Ahmad Fauzi', 'ahmad.fauzi@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Operations', 'Staff Operations', '#1565C0', 'aktif', 2),
(2, 'M001', 'Drs. Hendra', 'hendra@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'manager', 'Operations', 'Manager Operations', '#6D4C41', 'aktif', NULL),
(3, 'HR001', 'Admin HRD', 'admin.hrd@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'adminhrd', 'HR', 'HR Manager', '#C62828', 'aktif', NULL),
(4, 'IT001', 'Admin IT', 'admin.it@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'adminit', 'IT', 'IT Administrator', '#E65100', 'aktif', NULL),
(5, '240189', 'Siti Aminah', 'siti.aminah@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Finance', 'Staff Finance', '#2E7D32', 'aktif', 2),
(6, '240190', 'Budi Santoso', 'budi.santoso@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'IT', 'Staff IT', '#4527A0', 'aktif', 2),
(7, '240191', 'Dewi Lestari', 'dewi.lestari@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'HR', 'Staff HR', '#C62828', 'aktif', 2),
(8, '240192', 'Eko Prasetyo', 'eko.prasetyo@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Operations', 'Staff Operations', '#2E7D32', 'aktif', 2),
(9, '240193', 'Fatimah Zahra', 'fatimah.zahra@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Finance', 'Staff Finance', '#E65100', 'aktif', 2),
(10, '240194', 'Gunawan H.', 'gunawan.h@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'IT', 'Staff IT', '#1565C0', 'aktif', 2),
(11, '240195', 'Hani Pratiwi', 'hani.pratiwi@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Marketing', 'Staff Marketing', '#C62828', 'aktif', 2),
(12, '240196', 'Irfan Maulana', 'irfan.maulana@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'HR', 'Staff HR', '#E65100', 'aktif', 2),
(13, '240197', 'Joko Widodo', 'joko.widodo@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Operations', 'Staff Operations', '#2E7D32', 'aktif', 2),
(14, '240198', 'Krisna D.', 'krisna.d@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Operations', 'Staff Operations', '#4527A0', 'aktif', 2),
(15, '240199', 'Lina Sari', 'lina.sari@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Operations', 'Staff Operations', '#C62828', 'aktif', 2),
(16, '240200', 'Maya Putri', 'maya.putri@energinusantara.co.id', '$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu', 'karyawan', 'Marketing', 'Staff Marketing', '#E65100', 'aktif', 2);

-- ============================================================
-- SEED DATA ASSESSOR MAPPING (Semester I 2025)
-- ============================================================
INSERT INTO assessor_mapping (periode_id, karyawan_id, assessor_id, tipe_assessor, status) VALUES
-- Ahmad Fauzi (1)
(1, 1, 1, 'diri', 'submitted'),
(1, 1, 2, 'atasan', 'submitted'),
(1, 1, 8, 'peer', 'submitted'),
(1, 1, 13, 'peer', 'submitted'),
-- Siti Aminah (5)
(1, 5, 5, 'diri', 'submitted'),
(1, 5, 2, 'atasan', 'submitted'),
(1, 5, 9, 'peer', 'submitted'),
(1, 5, 1, 'peer', 'submitted'),
-- Budi Santoso (6)
(1, 6, 6, 'diri', 'draft'),
(1, 6, 2, 'atasan', 'assigned'),
(1, 6, 10, 'peer', 'assigned'),
(1, 6, 1, 'peer', 'assigned'),
-- Eko Prasetyo (8)
(1, 8, 8, 'diri', 'submitted'),
(1, 8, 2, 'atasan', 'submitted'),
(1, 8, 1, 'peer', 'submitted'),
(1, 8, 13, 'peer', 'submitted'),
-- Joko Widodo (13)
(1, 13, 13, 'diri', 'submitted'),
(1, 13, 2, 'atasan', 'submitted'),
(1, 13, 1, 'peer', 'submitted'),
(1, 13, 8, 'peer', 'submitted'),
-- Gunawan H. (10)
(1, 10, 10, 'diri', 'draft'),
(1, 10, 2, 'atasan', 'assigned'),
(1, 10, 6, 'peer', 'assigned'),
(1, 10, 1, 'peer', 'assigned'),
-- Krisna D. (14)
(1, 14, 14, 'diri', 'draft'),
(1, 14, 2, 'atasan', 'assigned'),
(1, 14, 1, 'peer', 'assigned'),
(1, 14, 13, 'peer', 'assigned'),
-- Lina Sari (15)
(1, 15, 15, 'diri', 'submitted'),
(1, 15, 2, 'atasan', 'submitted'),
(1, 15, 1, 'peer', 'submitted'),
(1, 15, 8, 'peer', 'submitted'),
-- Maya Putri (16)
(1, 16, 16, 'diri', 'draft'),
(1, 16, 2, 'atasan', 'assigned'),
(1, 16, 11, 'peer', 'assigned'),
(1, 16, 1, 'peer', 'assigned');

-- ============================================================
-- SEED DATA HASIL PENILAIAN
-- ============================================================
-- Ahmad Fauzi - Self (mapping_id 1)
INSERT INTO hasil_penilaian (mapping_id, pertanyaan_id, nilai) VALUES
(1, 1, 4), (1, 2, 4), (1, 3, 5),
(1, 4, 4), (1, 5, 4), (1, 6, 3),
(1, 7, 5), (1, 8, 4), (1, 9, 4),
(1, 10, 5), (1, 11, 4), (1, 12, 4),
(1, 13, 3), (1, 14, 3), (1, 15, 4),
(1, 16, 4), (1, 17, 5), (1, 18, 4);

-- Eko Prasetyo - Self (mapping_id 9)
INSERT INTO hasil_penilaian (mapping_id, pertanyaan_id, nilai) VALUES
(9, 1, 4), (9, 2, 5), (9, 3, 4),
(9, 4, 5), (9, 5, 4), (9, 6, 4),
(9, 7, 4), (9, 8, 5), (9, 9, 4),
(9, 10, 5), (9, 11, 5), (9, 12, 4),
(9, 13, 4), (9, 14, 4), (9, 15, 5),
(9, 16, 5), (9, 17, 4), (9, 18, 5);

-- ============================================================
-- SEED DATA SECURITY ALERTS
-- ============================================================
INSERT INTO security_alerts (judul, deskripsi, severity, status, source, ip_address) VALUES
('Multiple failed login', 'Terdeteksi 5x percobaan login gagal untuk admin_hrd@energinusantara.co.id', 'high', 'open', 'Auth System', '192.168.12.44'),
('New device access', 'Login dari perangkat baru dengan IP 192.168.12.44', 'medium', 'open', 'Auth System', '192.168.12.44'),
('Backup checksum delay', 'Checksum backup db-replica-02 terlambat 15 menit', 'low', 'resolved', 'Backup Service', NULL);

-- ============================================================
-- SEED DATA ACTIVITY LOG
-- ============================================================
INSERT INTO activity_log (user_id, aksi, detail, tipe, ip_address, created_at) VALUES
(4, 'Restore point created', 'Manual backup snapshot oleh Admin IT', 'system', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, 'Access role updated', 'Update permission untuk Manager Operations', 'admin', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(3, 'Report exported', 'Export laporan semester I 2025', 'admin', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(4, 'Anomaly reviewed', 'Review security alert #1 - multiple failed login', 'security', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(1, 'Submit penilaian', 'Submit self assessment', 'penilaian', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(8, 'Submit penilaian', 'Submit self assessment', 'penilaian', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 7 HOUR));

-- ============================================================
-- SEED DATA NOTIFIKASI
-- ============================================================
INSERT INTO notifikasi (user_id, judul, pesan, tipe, is_read, link, created_at) VALUES
(1, 'Penilaian baru menunggu', 'Anda memiliki 3 penilaian yang harus diselesaikan sebelum 20 Juni 2025', 'peringatan', 0, 'karyawan-penilaian.php', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'Self assessment selesai', 'Terima kasih telah menyelesaikan penilaian diri. Hasil akan tersedia setelah seluruh assessor submit.', 'success', 0, 'karyawan-nilai.php', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(1, 'Pengingat deadline', 'Deadline penilaian Semester I 2025: 20 Juni 2025. Segera selesaikan penugasan Anda.', 'info', 1, 'karyawan-penilaian.php', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'Tim Anda butuh perhatian', '2 anggota tim memiliki skor di bawah 3.5 dan memerlukan coaching', 'peringatan', 0, 'manager-performa.php', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'Progress tim 75%', '6 dari 8 anggota tim telah menyelesaikan penilaian. Kirim reminder ke yang belum?', 'info', 0, 'manager-nilai.php', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 'Periode aktif berjalan', 'Semester I 2025 aktif. Completion rate saat ini 73%. Pantau progress per divisi.', 'info', 1, 'hrd-progress.php', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, '3 karyawan belum di-assign assessor', 'Segera tentukan assessor untuk karyawan yang belum memiliki mapping', 'peringatan', 0, 'hrd-assessor.php', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, 'Security alert: Multiple failed login', 'Terdeteksi 5x percobaan login gagal. Review di Security Center', 'danger', 0, 'adminit-security.php', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, 'Backup harian sukses', 'Daily full backup pada 02:00 berhasil. Checksum verified.', 'success', 1, 'adminit-backup.php', DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(4, 'Worker queue warning', 'Worker queue depth di atas threshold (12 jobs). Restart recommended.', 'peringatan', 0, 'adminit-monitor-detail.php', DATE_SUB(NOW(), INTERVAL 30 MINUTE));

SET FOREIGN_KEY_CHECKS = 1;
