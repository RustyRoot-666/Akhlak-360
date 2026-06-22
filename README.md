# AKHLAK360 - Sistem Penilaian 360° (PHP Backend)

> **v1.1** — Port MySQL default **3307**. Lihat [INSTALL.md](INSTALL.md) untuk panduan instalasi lengkap dan [PANDUAN_PENGGUNAAN.md](PANDUAN_PENGGUNAAN.md) untuk panduan end-user.

## Informasi
- **Nama**: AKHLAK360
- **Versi**: 1.1 (PHP Backend, MySQL Port 3307)
- **Perusahaan**: PT Energi Nusantara
- **Tahun**: 2025

## Fitur

### 1. Authentication & Authorization
- Login dengan 4 role (Karyawan, Manager, Admin HRD, Admin IT)
- Session management dengan timeout (default 30 menit)
- Logout otomatis jika idle
- Forgot / Reset Password (token-based, 30 menit expiry)
- Logging keamanan untuk percobaan login gagal

### 2. Karyawan
- Dashboard dengan statistik penilaian
- Daftar penilaian tertunda
- Form pengisian penilaian 360° (18 pertanyaan, 6 dimensi AKHLAK)
- Auto-save jawaban saat radio button dipilih
- Lihat nilai AKHLAK sendiri dengan radar chart + breakdown per assessor type

### 3. Manager
- Dashboard tim dengan progress anggota + radar profil tim
- Nilai karyawan tim (tabel + export XLS)
- Tab filter berdasarkan status (Semua / Selesai / Berjalan / Tertunda)
- Detail skor per karyawan (breakdown 6 dimensi × 4 assessor type)
- Dashboard performa tim dengan coaching priority list
- Buat Coaching Plan + Kirim Feedback ke karyawan (lewat notifikasi in-app)

### 4. Admin HRD
- Dashboard monitoring per divisi (real progress dari assessor_mapping)
- Data karyawan (CRUD + filter divisi/status/search + export XLS + Export IDP)
- Tentukan assessor (auto-assign, manual, validasi konflik, kirim undangan)
- Pantau progress per divisi + daftar keterlambatan
- Laporan: Executive Summary, Detail Karyawan, Matrix AKHLAK, Audit Trail
- Generate Pack (4 sheet XLSX sekaligus)

### 5. Admin IT
- Dashboard system health (events 24h, active admins, open alerts, backup success)
- Database manager (inventory, table list, size, test connection)
- Database audit (slow query, schema changes, audit events, export CSV)
- Backup center (job schedule, manual snapshot, retention, history, CSV export)
- Restore center (restore points, dry run, validate, guarded recovery, rollback)
- Monitoring center (service health, uptime, CPU/memory, probes)
- Monitor detail (worker probe detail, queue depth, restart, scale, incidents)
- Anomaly review (security alerts queue, severity triage, resolve, block IP)
- Security center (policies, MFA, IP blocklist, session timeout, security scan)
- System access (access matrix per role, invite admin, revoke access, timeline)
- Activity log (filterable audit trail, CSV export, highlights)
- Report center (6 templates: uptime/backup/security/access/database/anomaly, CSV/XLSX/PDF)

### 6. Umum (semua role)
- Profil Saya (edit nama, ganti avatar color, ubah password)
- Notifikasi (bell icon dengan unread count, list, mark as read, mark all as read)
- Aktivitas terakhir user (di halaman profil)

## Instalasi Cepat

### 1. Database (MySQL port 3307)
```bash
mysql -u root -p --port=3307 -e "CREATE DATABASE akhlak360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p --port=3307 akhlak360 < akhlak360.sql
mysql -u root -p --port=3307 akhlak360 < seed_data.sql
```

### 2. Konfigurasi
Edit `includes/config.php` jika kredensial DB Anda berbeda:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');        // ← Port MySQL (default 3307 di v1.1)
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'akhlak360');
```

### 3. Verifikasi koneksi
Buka browser: `http://localhost/akhlak360-php/dbtest.php`
Semua check harus hijau sebelum lanjut.

### 4. Login Demo
Buka: `http://localhost/akhlak360-php/`

| Role | Email | Password |
|------|-------|----------|
| Karyawan | ahmad.fauzi@energinusantara.co.id | password123 |
| Manager | hendra@energinusantara.co.id | password123 |
| Admin HRD | admin.hrd@energinusantara.co.id | password123 |
| Admin IT | admin.it@energinusantara.co.id | password123 |

## Struktur Direktori
```
akhlak360-php/
├── akhlak360.sql              # Database schema (12 tabel + seed data master)
├── seed_data.sql              # Seed data (16 user demo + mapping + notifikasi)
├── .htaccess                  # Apache config (mod_rewrite + security headers)
├── dbtest.php                 # Diagnostik koneksi MySQL (HAPUS di produksi)
├── index.php                  # Login page
├── INSTALL.md                 # Panduan instalasi lengkap
├── PANDUAN_PENGGUNAAN.md      # Panduan end-user langkah demi langkah
├── README.md                  # Dokumen ini
├── api/                       # API endpoints (JSON)
│   ├── auth.php               # Authentication (login, logout, forgot/reset/change password)
│   ├── dashboard.php          # Dashboard data per role
│   ├── penilaian.php          # Assessment CRUD + coaching + feedback
│   ├── karyawan.php           # Employee CRUD
│   ├── assessor.php           # Assessor management
│   ├── laporan.php            # Reports
│   ├── adminit.php            # Admin IT actions (backup, restore, monitoring, security)
│   └── notifikasi.php         # Notifications (list, mark read, broadcast)
├── includes/                  # Core files
│   ├── config.php             # Configuration (DB_PORT=3307 default)
│   ├── functions.php          # Helper functions (auth, db, cfgGet/cfgSet, export CSV)
│   └── template.php           # UI template (sidebar w/ notification badge, page start/end)
├── pages/                     # Page views (32 halaman)
│   ├── karyawan-*.php         # 6 halaman Karyawan
│   ├── manager-*.php          # 4 halaman Manager
│   ├── hrd-*.php              # 10 halaman Admin HRD
│   ├── adminit-*.php          # 12 halaman Admin IT
│   ├── profile.php            # Profile + change password (all roles)
│   └── reset-password.php     # Forgot/reset password flow (demo mode)
├── css/
│   └── style.css              # ~1500 baris, semua komponen
├── js/
│   ├── script.js              # Shared utility (counter animation, progress bar, dll)
│   └── export.js              # Export XLS/CSV/PDF utilities
└── assets/
    └── logo.png               # Logo AKHLAK360
```

## API Endpoints

### Authentication (`api/auth.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `?action=login` | Login (email + password + role) |
| POST | `?action=logout` | Logout |
| GET | `?action=me` | Data user yang sedang login |
| GET | `?action=check` | Cek session masih aktif |
| POST | `?action=forgot_password` | Minta reset token (demo: token di-return di response) |
| POST | `?action=reset_password` | Reset password pakai token |
| POST | `?action=change_password` | Ganti password (auth required) |

### Dashboard (`api/dashboard.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `?action=karyawan` | Data dashboard Karyawan |
| GET | `?action=manager` | Data dashboard Manager |
| GET | `?action=hrd` | Data dashboard Admin HRD |
| GET | `?action=adminit` | Data dashboard Admin IT |

### Penilaian (`api/penilaian.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `?action=pending` | Daftar penilaian tertunda untuk user |
| GET | `?action=form&mapping_id=X` | Data form penilaian (18 pertanyaan) |
| POST | `?action=save_answer` | Auto-save jawaban radio |
| POST | `?action=submit` | Submit penilaian + trigger calculateFinalScore |
| GET | `?action=my_scores` | Skor AKHLAK user sendiri |
| GET | `?action=employee_scores&karyawan_id=X` | Skor karyawan (Manager/HRD) |
| POST | `?action=save_coaching` | Simpan coaching plan (Manager/HRD) |
| POST | `?action=send_feedback` | Kirim feedback ke karyawan (Manager/HRD) |

### Karyawan (`api/karyawan.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `?action=list` | List karyawan dengan filter |
| GET | `?action=get&id=X` | Detail karyawan |
| POST | `?action=create` | Tambah user baru |
| POST | `?action=update` | Update user |
| GET | `?action=delete&id=X` | Soft delete (set status=nonaktif) |
| GET | `?action=managers` | List manager aktif |
| GET | `?action=divisions` | List divisi |

### Assessor (`api/assessor.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `?action=list` | List mapping assessor |
| POST | `?action=create` | Tambah mapping manual |
| GET | `?action=delete&id=X` | Hapus mapping |
| POST | `?action=auto_assign` | Auto-assign (self + atasan + 2 peer) |
| GET | `?action=stats` | Statistik mapping |

### Laporan (`api/laporan.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `?action=divisi` | Ringkasan per divisi |
| GET | `?action=matrix` | Matrix AKHLAK (divisi × dimensi) |
| GET | `?action=detail_karyawan` | Detail nilai semua karyawan |
| GET | `?action=top_performers` | Top performers |
| GET | `?action=progress` | Progress per divisi |

### Admin IT (`api/adminit.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `?action=run_backup` | Trigger manual backup |
| GET | `?action=backup_log_csv` | Download backup log CSV |
| POST | `?action=validate_restore` | Validate restore point |
| POST | `?action=start_restore` | Start guarded restore |
| POST | `?action=dry_run` | Simulate restore |
| POST | `?action=run_probes` | Run service probes |
| POST | `?action=restart_worker` | Restart worker service |
| POST | `?action=scale_worker` | Scale worker capacity |
| POST | `?action=resolve_anomaly` | Resolve security alert |
| POST | `?action=block_ip` | Block suspicious IP |
| POST | `?action=security_scan` | Run security scan |
| POST | `?action=update_policy` | Update security policy (config_key/value) |
| POST | `?action=force_logout` | Force logout all sessions |
| POST | `?action=test_connection` | Test DB connection |
| GET | `?action=db_stats` | Database stats (tables, rows, size) |
| GET | `?action=activity_log_csv` | Export activity log CSV |
| GET | `?action=audit_log_csv` | Export DB audit log CSV |
| POST | `?action=generate_report` | Generate report (category, format) |

### Notifikasi (`api/notifikasi.php`)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `?action=list` | List user notifications |
| GET | `?action=unread_count` | Get unread count |
| POST | `?action=mark_read` | Mark single notification as read |
| POST | `?action=mark_all_read` | Mark all as read |
| GET | `?action=delete&id=X` | Delete notification |
| POST | `?action=create` | Send to one user (admin only) |
| POST | `?action=broadcast` | Broadcast to all/role (admin only) |

## Perubahan v1.1 (dari v1.0)

| Item | v1.0 | v1.1 |
|------|------|------|
| MySQL Port default | 3306 | **3307** |
| Bug: `$db` undefined di `penilaian.php?action=save_coaching` | Fatal error | Fixed |
| Bug: `$db` undefined di `penilaian.php?action=send_feedback` | Fatal error | Fixed |
| Demo reset-password flow | Token `DEMO` (tidak bisa dipakai) | Token asli ditampilkan + link aktif |
| Share link di `adminit-report.php` | Path hardcoded broken | Fixed (relative URL) |
| `.htaccess` | Tidak ada | Ditambahkan (mod_rewrite + security headers) |
| `dbtest.php` | Tidak ada | Ditambahkan (diagnostik koneksi + schema + seed) |
| INSTALL.md | Standar | Diperluas (6 langkah + troubleshooting + production hardening) |
| PANDUAN_PENGGUNAAN.md | Tidak ada | Ditambahkan (panduan end-user) |
| Hash compare reset token | `===` (timing-unsafe) | `hash_equals` (timing-safe) |

## Lisensi
© 2025 PT Energi Nusantara. All rights reserved.
