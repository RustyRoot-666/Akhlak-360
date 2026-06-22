# AKHLAK360 — Panduan Penggunaan Lengkap (End-User)

> Dokumen ini menjelaskan langkah-langkah penggunaan aplikasi AKHLAK360 dari sisi end-user. Cocok untuk Karyawan, Manager, Admin HRD, dan Admin IT yang baru pertama kali menggunakan sistem.

---

## Daftar Isi

1. [Login & Logout](#1-login--logout)
2. [Role Karyawan](#2-role-karyawan)
3. [Role Manager](#3-role-manager)
4. [Role Admin HRD](#4-role-admin-hrd)
5. [Role Admin IT](#5-role-admin-it)
6. [Fitur Umum (Semua Role)](#6-fitur-umum-semua-role)
7. [Reset Password](#7-reset-password)
8. [FAQ](#8-faq)

---

## 1. Login & Logout

### Cara Login

1. Buka browser ke alamat aplikasi:
   - Lokal: `http://localhost/akhlak360-php/`
   - Production: `https://akhlak360.yourcompany.com/`
2. Pada halaman login, pilih **tab role** sesuai akun Anda:
   - **Karyawan**, **Manager**, **Admin HRD**, atau **Admin IT**
   - Email demo akan terisi otomatis sesuai tab yang dipilih
3. Masukkan email Anda (atau biarkan terisi otomatis).
4. Masukkan password.
5. Klik tombol **Masuk →** atau tekan **Enter**.

> **Penting:** Role yang dipilih harus cocok dengan email. Jika Anda login sebagai Karyawan tapi memilih tab Manager, sistem akan menolak meskipun password benar.

### Cara Logout

1. Di sidebar kiri, scroll ke paling bawah.
2. Klik tombol **Keluar** (icon panah keluar).
3. Anda akan diarahkan kembali ke halaman login.

### Session Timeout

- Sesi Anda otomatis berakhir setelah **30 menit** tidak ada aktivitas (default).
- Jika session expired, Anda perlu login ulang.
- Admin IT bisa mengubah timeout ini via menu **Security → Edit Policy**.

---

## 2. Role Karyawan

### 2.1 Dashboard Karyawan

Setelah login, halaman pertama yang muncul adalah Dashboard Karyawan.

**Yang akan Anda lihat:**
- **4 Stat Card**:
  - **Menunggu Dinilai** — jumlah penilaian yang belum Anda kerjakan
  - **Sudah Selesai** — jumlah penilaian yang sudah Anda submit
  - **Nilai Saya** — rata-rata skor AKHLAK Anda (skala 5.00)
  - **Status Periode** — periode penilaian aktif + deadline
- **Penilaian Tertunda** — 5 penilaian teratas yang harus diselesaikan, dengan tombol **Nilai →**
- **Radar Chart Nilai AKHLAK** — visualisasi 6 dimensi skor Anda

### 2.2 Mengisi Penilaian 360°

Ini adalah fitur utama untuk role Karyawan. Ikuti langkah-langkah berikut:

1. Di sidebar, klik **Penilaian Saya**.
2. Anda akan melihat daftar penugasan penilaian:
   - Setiap baris menampilkan: nama karyawan yang akan dinilai, jabatan, tipe assessor (Self/Atasan/Peer/Bawahan), deadline, dan status.
3. Cari baris dengan status **Tertunda** atau **Draft**.
4. Klik tombol **Isi Penilaian →** di sisi kanan baris tersebut.
5. Anda akan masuk ke halaman form penilaian:

   **Struktur Form:**
   - **Header**: Nama karyawan yang dinilai + tipe assessor (contoh: "Penilaian: Ahmad Fauzi · Peer")
   - **Progress bar**: Menunjukkan berapa persen pertanyaan sudah dijawab
   - **6 Tab dimensi AKHLAK**: Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif
   - **3 Pertanyaan per dimensi** (total 18 pertanyaan)
   - **Radio button 1–5** untuk setiap pertanyaan (1 = Sangat Kurang, 5 = Sangat Baik)
   - **Tombol navigasi**: Sebelumnya ← / Berikutnya → / Kirim Penilaian

6. **Cara menjawab**: Klik angka 1–5 di samping setiap pertanyaan. Jawaban akan **otomatis tersimpan** ke database (auto-save) — Anda tidak perlu khawatir kehilangan progress.
7. **Berpindah dimensi**: Klik tab dimensi atau gunakan tombol **Berikutnya →**.
8. Setelah semua 18 pertanyaan dijawab, tombol **Kirim Penilaian** akan muncul di tab Kolaboratif (dimensi terakhir).
9. Klik **Kirim Penilaian**.
10. Anda akan diarahkan ke halaman sukses: "Penilaian Terkirim!".

> **Catatan penting:**
> - Setelah submit, jawaban **tidak bisa diubah**. Pastikan semua sudah benar sebelum submit.
> - Jika Anda sudah submit, status mapping akan berubah menjadi `submitted`.
> - Sistem akan otomatis menghitung ulang nilai final karyawan yang Anda nilai (kombinasi dari self + atasan + peer + bawahan).

### 2.3 Melihat Nilai AKHLAK Sendiri

1. Klik menu **Nilai AKHLAK** di sidebar.
2. Halaman menampilkan:
   - **5 Summary Card**: Nilai Akhir, Atasan (40%), Bawahan (30%), Rekan (20%), Mandiri (10%)
   - **Radar Chart Profil AKHLAK** — 6 dimensi
   - **Bar per Dimensi** — skor per dimensi dengan warna khas
   - **Rekomendasi IDP** — area pengembangan utama berdasarkan dimensi terendah

> **Bobot Penilaian 360°:**
> - Atasan: 40% (paling berbobot)
> - Bawahan: 30%
> - Rekan Sejawat (Peer): 20%
> - Penilaian Diri (Self): 10%

---

## 3. Role Manager

### 3.1 Dashboard Manager

Setelah login sebagai Manager, Anda akan melihat:
- **4 Stat Card**: Tim Saya, Sudah Dinilai, Menunggu Nilai, Nilai Rata-rata
- **Tabel Progress Tim** dengan progress bar per anggota
- **Radar Chart Profil Tim** — rata-rata skor 6 dimensi dari seluruh anggota tim

### 3.2 Melihat Nilai Karyawan Tim

1. Klik **Nilai Karyawan** di sidebar.
2. Tampil tabel anggota tim dengan kolom:
   - Nama, Jabatan, Progress (bar), Nilai Akhir, Status, Detail
3. **Filter tab** di atas tabel: Semua / Selesai / Berjalan / Tertunda.
4. **Export XLS**: Klik tombol **Export XLS** di kanan atas untuk download spreadsheet.

### 3.3 Detail Skor Karyawan + Coaching Plan

1. Di tabel Nilai Karyawan, klik **Lihat →** pada baris karyawan.
2. Halaman detail menampilkan:
   - Card karyawan + coaching status (Need Coaching / On Track / Excellent)
   - Total Score + Self Score
   - **Analisis Otomatis**: Area terkuat + area pengembangan
   - **Breakdown AKHLAK** tabel per dimensi (Self, Peer, Manager, Final)
   - **Tindak Lanjut**: Tombol Buat Coaching Plan, Kirim Feedback, Export PDF
   - **Catatan Manager**: Catatan coaching yang sudah dibuat (jika ada)

#### Membuat Coaching Plan

1. Klik tombol **Buat Coaching Plan** (atau **Kirim Feedback**).
2. Modal **Coaching Plan** akan muncul:
   - Pilih **Status Coaching**: On Track / Need Coaching / Excellent
   - Isi **Catatan** untuk karyawan (area yang perlu diperbaiki, target 30 hari, dll.)
   - Isi **Rekomendasi** tindak lanjut
3. Klik **Simpan Coaching Plan**.
4. Catatan akan tersimpan dan tampil di halaman detail karyawan.

#### Mengirim Feedback ke Karyawan

1. Klik tombol **Kirim Feedback**.
2. Modal **Feedback** akan muncul.
3. Tulis pesan feedback (minimal 10 karakter).
4. Klik **Kirim Feedback**.
5. Pesan akan dikirim sebagai **notifikasi in-app** ke karyawan. Mereka akan melihatnya di halaman Profil Saya mereka.

### 3.4 Dashboard Performa Tim

1. Klik **Dashboard Performa** di sidebar.
2. Tampilan:
   - 4 Stat Card: Avg AKHLAK, Top Value (dimensi tertinggi), Focus Area (dimensi terendah), At Risk (jumlah anggota butuh coaching)
   - **Distribusi Nilai Tim** per dimensi (bar chart)
   - **Radar AKHLAK**
   - **Coaching Priority List** — tabel anggota tim diurutkan dari skor terendah, dengan kolom: Nama, Area Fokus, Nilai, Priority, Rekomendasi otomatis

> Coaching Priority List sangat berguna untuk memutuskan siapa yang perlu di-coaching lebih dulu. Anggota dengan skor < 3.5 otomatis ditandai **Need Coaching** (merah).

---

## 4. Role Admin HRD

### 4.1 Dashboard Admin HRD

- **4 Stat Card**: Total Karyawan, Pengisian Selesai (%), Belum Mulai, Rata-rata Nilai
- **Progress Pengisian per Divisi** dengan bar progress real-time
- **Daftar Karyawan** teratas (6) dengan progress masing-masing

### 4.2 Data Karyawan (CRUD)

#### Melihat Daftar Karyawan

1. Klik **Data Karyawan** di sidebar.
2. Tampil tabel karyawan dengan kolom: Nama, Divisi, Jabatan, Manager, Progress, Nilai Akhir, Status, Aksi.
3. **Filter**: Klik tombol **Filter** di kanan atas untuk menampilkan form filter (Divisi, Status, Search).

#### Tambah Karyawan Baru

1. Klik tombol **+ Tambah Karyawan** di kanan atas.
2. Modal **Tambah Karyawan** muncul:
   - NIK (wajib, unique)
   - Nama Lengkap (wajib)
   - Email (wajib, unique)
   - Password (wajib, min 6 karakter)
   - Divisi (pilih dari dropdown: Operations, Finance, IT, HR, Marketing, Legal, Procurement)
   - Jabatan (wajib, contoh: "Staff Operations")
   - Manager (pilih dari dropdown manager aktif, atau "Tanpa manager")
   - Status (Aktif / Nonaktif / Cuti)
3. Klik **Simpan**.
4. Jika sukses, tabel akan auto-refresh dan menampilkan karyawan baru di urutan alfabetis.

#### Edit Karyawan

1. Klik **Edit** pada baris karyawan.
2. Modal akan terbuka dengan data karyawan terisi.
3. Ubah field yang perlu diubah.
4. Jika tidak ingin mengubah password, kosongkan field Password.
5. Klik **Simpan**.

#### Hapus (Nonaktifkan) Karyawan

1. Klik **Hapus** pada baris karyawan.
2. Konfirmasi: "Nonaktifkan karyawan '[nama]'?"
3. Klik OK.
4. Status karyawan berubah menjadi `nonaktif` — **tidak bisa dihapus permanen** (soft delete untuk audit trail).

#### Export Data Karyawan

- **Export XLS**: Download tabel karyawan yang sedang ditampilkan (termasuk filter) sebagai Excel.
- **Export IDP**: Download Individual Development Plan — Excel dengan kolom 6 dimensi AKHLAK + avg + area pengembangan + rekomendasi per karyawan.

### 4.3 Tentukan Assessor

Halaman ini untuk mengatur siapa menilai siapa pada periode aktif.

#### Auto Assign (Cepat)

1. Klik tombol **Auto Assign Semua** di kanan atas.
2. Konfirmasi proses akan auto-assign untuk semua karyawan yang belum punya assessor.
3. Sistem akan otomatis membuat mapping:
   - **Self** (karyawan menilai diri sendiri)
   - **Atasan** (manager langsung karyawan)
   - **2 Peer acak** dari divisi yang sama

#### Auto Assign untuk 1 Karyawan

1. Scroll ke bawah ke bagian "Karyawan Belum Punya Assessor".
2. Klik **Auto Assign** pada baris karyawan.
3. Sistem akan auto-assign hanya untuk karyawan tersebut.

#### Tambah Peer Manual (Tidak ada UI dialog khusus)

Untuk demo, tombol **Tambah Peer** hanya menampilkan notifikasi. Untuk menambah peer manual, gunakan API endpoint:
```
POST api/assessor.php?action=create
Body: {"periode_id": 1, "karyawan_id": 5, "assessor_id": 10, "tipe_assessor": "peer"}
```

#### Validasi Konflik

- Sistem otomatis mendeteksi konflik (karyawan dengan >1 atasan).
- Stat card **Konflik** menampilkan jumlah konflik yang perlu di-review.

### 4.4 Pantau Progress

1. Klik **Pantau Progress** di sidebar.
2. Tampil:
   - 4 Stat Card: Completion (%), Belum Mulai, Draft, Selesai
   - Progress per Divisi (bar chart)
   - **Daftar Keterlambatan** — tabel karyawan yang belum menyelesaikan semua penilaian
3. **Kirim Reminder**: Klik tombol untuk broadcast notifikasi ke semua karyawan yang belum selesai.
4. **Follow-up Draft**: Broadcast ke karyawan dengan status draft.
5. **Lock Periode**: Kirim request ke Admin IT untuk lock periode (setelah deadline).
6. **Export XLS**: Download tabel keterlambatan.

### 4.5 Laporan

Klik **Laporan** di sidebar untuk membuka pusat laporan.

#### 4.5.1 Generate Pack (XLSX 4 sheet)

Klik **Generate Pack (ZIP)** → otomatis download file XLSX dengan 4 sheet:
- Ringkasan Divisi
- Detail Karyawan
- Matrix AKHLAK
- Audit Log

#### 4.5.2 Executive Summary (PDF)

1. Klik **Executive Summary**.
2. Halaman preview tampil dengan daftar isi laporan.
3. Klik **Download PDF** → browser print dialog muncul.
4. Pilih "Save as PDF" sebagai destination.

#### 4.5.3 Detail Karyawan (XLS)

1. Klik **Detail Karyawan**.
2. Preview data karyawan tampil.
3. Klik **Download XLS**.

#### 4.5.4 Matrix AKHLAK (CSV)

1. Klik **Matrix AKHLAK**.
2. Preview matrix (divisi × 6 dimensi) tampil.
3. Klik **Download CSV**.

#### 4.5.5 Audit Trail (XLSX)

1. Klik **Audit Trail**.
2. Riwayat aktivitas terbaru tampil.
3. Klik **Download Pack (XLSX)** → 3 sheet: Activity Log, Audit Events, Backup Log.

---

## 5. Role Admin IT

### 5.1 Dashboard Admin IT

- 4 Stat Card: Database Status, Backup Success %, Open Alerts, Access Events (24h)
- **Monitoring Layanan** — 6 service dengan health, latency, status
- **IT Controls** — quick link ke Backup, Restore, Security, Export Report
- **Security & Anomaly** — 5 alert terbaru
- **Activity Log** — 10 aktivitas terbaru

### 5.2 Database Manager

1. Klik **Database** di sidebar.
2. Tampil:
   - 4 Stat Card: Primary, Replica, Storage %, Tables count
   - **Database Inventory** — 4 database (Primary, Replica, Audit, Archive) dengan status
   - **Tables in akhlak360** — grid semua tabel dengan row count
   - **Database Actions**: Add Connection, Test Connection, View Schema, Open Audit DB
3. **Test Connection**: Klik tombol **Test Connection** di kanan atas → sistem akan ping DB dan tampilkan latency + versi MySQL.

### 5.3 DB Audit

- 4 Stat Card: Slow Query, Changes, Locks, Audit (Live)
- **Database Audit Events** — tabel aktivitas admin/system dari `activity_log`
- Tombol **Export Audit** — download CSV

### 5.4 Backup Center

1. Klik **Backup** di sidebar.
2. Tampil:
   - 4 Stat Card: Last Backup, Success %, Retention, Queue
   - **Backup Queue** — 4 job (Daily full, Hourly incremental, Audit log, Archive)
   - **Backup Controls**: Run Backup Now, Schedule Job, Set Retention, Download Log
3. **Run Backup Now**: Klik untuk trigger backup manual → success toast muncul.
4. **Schedule Job**: Klik → prompt HH:MM → update `backup_schedule` di `system_config`.
5. **Set Retention**: Klik → prompt jumlah hari → simpan ke policy (demo only).
6. **Download Log**: Download CSV berisi aktivitas backup.

### 5.5 Restore Center

- 4 Stat Card: Restore Points, Last Test (Passed), RTO (12 min), Risk (Low)
- **Restore Points** — 4 restore point (RP-YYYY-MM-DD)
- **Restore Flow**: Validate Point, Start Restore, Dry Run, Rollback
- **Restore Activity** — log aktivitas restore

> **Penting:** Semua aksi restore akan dicatat di `activity_log` dengan tipe `system` untuk audit.

### 5.6 Monitoring Center

1. Klik **Monitoring** di sidebar.
2. Tampil:
   - 4 Stat Card: Uptime %, Services (healthy/total), CPU %, Memory %
   - **Service Health** — 6 service dengan health bar + latency real (DB-related service akan di-ping sungguhan)
   - **Monitoring Views**: Open Dashboard, Set Threshold, Mute Alert, Refresh Health
3. **Run Probes**: Klik untuk trigger ping ke semua service.

### 5.7 Monitor Detail (Worker)

- 4 Stat Card: Service, Status, Latency, Errors
- **Worker Probe Detail** — 4 probe terbaru
- **Queue depth** — jumlah assessment yang masih pending (dari `assessor_mapping` status assigned/draft)
- **Monitor Actions**: Restart Worker, Open Logs, Scale Worker, Create Incident

### 5.8 Anomaly Review

1. Klik **Anomaly** di sidebar.
2. Tampil:
   - 4 Stat Card: Open, High, Medium, Resolved
   - **Anomaly Queue** — tabel `security_alerts` diurutkan by severity
   - **Anomaly Actions**: Assign Review, Block Source, False Positive, Create Report
3. **Resolve Anomaly**: Klik link di kolom Action → status berubah jadi `resolved`.
4. **Block Source**: Prompt IP → tambahkan security alert tipe "IP blocked by Admin IT".
5. **Create Report**: Download audit log CSV.

### 5.9 Security Center

1. Klik **Security** di sidebar.
2. Tampil:
   - 4 Stat Card: Policies, Blocked IP, Sessions (active users), Risk
   - **Security Controls** — 4 policy (MFA, IP allowlist, Session timeout, Password policy)
   - **Security Actions**: Edit Policy, Block IP, Force Logout, Run Scan
3. **Edit Policy**: Klik → modal muncul → pilih policy → isi nilai baru → Update. Akan update `system_config`.
4. **Block IP**: Klik → prompt IP → tambahkan ke security_alerts.
5. **Force Logout**: Konfirmasi → trigger log "Force logout all sessions".
6. **Run Scan**: Klik → scan security_alerts + admin nonaktif → tampilkan hasil.

### 5.10 System Access

- 4 Stat Card: Users, Roles, Pending, Revoked
- **Access Matrix** — 4 row (per role + module)
- **Access Actions**: Invite Admin, Create Role, Approve Access, Revoke Access
- **Invite Admin**: Klik → modal form → isi data user → Simpan. Sama seperti Tambah Karyawan di HRD.
- **Revoke Access**: Klik → prompt pilih user aktif → set status `nonaktif`.

### 5.11 Activity Log

1. Klik **Activity Log** di sidebar.
2. Tampil:
   - 4 Stat Card: Events (24h), Admins, Today, Flagged
   - **Filter form**: Type, Actor, Search
   - **Activity Events** — tabel 200 log terbaru
3. **Export CSV**: Klik tombol **Export CSV** → download dengan filter aktif.

### 5.12 Report Center

1. Klik **Report** di sidebar.
2. Tampil:
   - 4 Stat Card: Templates, Exports, Scheduled, Failed
   - **Report Templates** — 6 kategori (System uptime, Backup summary, Security audit, Access activity, Database inventory, Anomaly review)
3. **Download/Export/Generate**: Klik link di kolom Action pada template yang dipilih.
4. **Generate Report (Custom)**: Klik tombol → prompt kategori + format → download.
5. **Export Pack**: Klik → download 6 sheet XLSX (semua kategori).
6. **Share Link**: Klik → generate random token → prompt tampilkan URL share.

---

## 6. Fitur Umum (Semua Role)

### 6.1 Profil Saya

1. Klik **Profil Saya** di paling bawah sidebar.
2. Halaman menampilkan:
   - **Card Informasi Akun**: Avatar, nama, role, divisi, jabatan, NIK, email, status
   - **Edit Profil**: Ubah nama + warna avatar (10 pilihan warna)
   - **Ubah Password**: Password lama + password baru + konfirmasi
   - **Notifikasi Saya** (20 terbaru, dengan tombol "Tandai dibaca")
   - **Aktivitas Terakhir** (10 log aktivitas user sendiri)

### 6.2 Notifikasi

Notifikasi muncul di 2 tempat:
1. **Bell icon badge** di sidebar (jumlah unread)
2. **List lengkap** di halaman Profil Saya

**Aksi yang tersedia:**
- **Tandai dibaca** — klik tombol di samping notifikasi
- **Mark all as read** — tandai semua sebagai sudah dibaca (via API)
- **Delete** — hapus notifikasi (via API)

Notifikasi otomatis dibuat oleh sistem ketika:
- Penilaian baru di-assign ke Anda
- Self assessment Anda selesai
- Deadline mendekat
- Manager mengirim feedback
- Admin IT/HRD melakukan broadcast
- Security alert terdeteksi

### 6.3 Ganti Password

1. Buka **Profil Saya**.
2. Scroll ke section **Ubah Password**.
3. Isi: Password Lama, Password Baru (min 6 karakter), Konfirmasi Password Baru.
4. Klik **Ubah Password**.
5. Password langsung di-hash (bcrypt) dan disimpan.
6. Logout dan login ulang dengan password baru untuk verifikasi.

---

## 7. Reset Password (Lupa Password)

Jika Anda lupa password, ikuti langkah berikut:

### Step 1: Minta Reset Token

1. Di halaman login, klik **Lupa Password?**.
2. Masukkan email Anda.
3. Klik **Kirim Link Reset**.
4. **DEMO MODE**: Token akan ditampilkan langsung di halaman (karena tidak ada email server di demo). Salin token tersebut.
   - **Produksi**: Token akan dikirim ke email Anda.

### Step 2: Reset Password

1. Klik tombol **Buka Form Reset Password** (di mode demo), atau buka link dari email.
2. Form reset muncul dengan email + token sudah terisi.
3. Masukkan Password Baru (min 6 karakter).
4. Masukkan Konfirmasi Password.
5. Klik **Reset Password**.
6. Jika sukses, halaman menampilkan "Reset Berhasil".
7. Klik **Kembali ke Login** dan login dengan password baru.

> **Catatan:**
> - Token berlaku **30 menit** sejak di-generate.
> - Token hanya bisa dipakai **sekali**.
> - Jika token expired, ulangi Step 1.

---

## 8. FAQ

### Q: Bagaimana cara mengganti periode aktif?

**A:** Saat ini, periode aktif ditentukan oleh field `status = 'aktif'` di tabel `periode`. Hanya boleh ada 1 periode aktif pada satu waktu. Untuk mengubah, jalankan SQL:
```sql
UPDATE periode SET status = 'ditutup' WHERE status = 'aktif';
UPDATE periode SET status = 'aktif' WHERE id = [id periode baru];
```
Atau minta Admin IT untuk menambahkan UI periode management di versi mendatang.

### Q: Bisakah karyawan melihat nilai dari peer-nya?

**A: Tidak.** Karyawan hanya bisa melihat **nilai final per dimensi** (gabungan dari semua assessor). Skor per assessor type (atasan/peer/bawahan) bisa dilihat di card summary di halaman Nilai AKHLAK, tapi tidak mengidentifikasi peer individu (anonim).

### Q: Apakah jawaban penilaian bisa diubah setelah submit?

**A: Tidak.** Setelah klik "Kirim Penilaian", jawaban dikunci. Jika ada kesalahan, hubungi Admin HRD untuk reset mapping (ubah status dari `submitted` ke `draft`).

### Q: Bagaimana cara menambah dimensi atau pertanyaan baru?

**A:** Hubungi Admin IT untuk:
1. Insert ke tabel `dimensi_akhlak` (jika menambah dimensi baru — tapi akan memengaruhi 6 nilai AKHLAK BUMN).
2. Insert ke tabel `pertanyaan` dengan `dimensi_id` yang sesuai.
3. Update form penilaian di `pages/karyawan-form.php` jika perlu (sekarang auto-generate dari DB, jadi tidak perlu).

### Q: Apakah data bisa di-export ke format lain (selain XLS/CSV)?

**A:** Untuk PDF, gunakan fitur **Export PDF** di halaman Detail Skor (Manager) atau Executive Summary (HRD) → akan memunculkan print dialog browser → Save as PDF. Format XLSX dan CSV tersedia langsung di berbagai tombol Export.

### Q: Berapa sering backup dilakukan?

**A:** Backup manual bisa dipicu kapan saja oleh Admin IT via menu **Backup → Run Backup Now**. Backup otomatis butuh cron job (lihat INSTALL.md → Production Hardening).

### Q: Apakah aplikasi mendukung multi-bahasa?

**A:** Saat ini hanya **Bahasa Indonesia**. UI label dan message semuanya dalam Bahasa Indonesia. Untuk mendukung multi-bahasa, perlu implementasi i18n (misal dengan gettext atau array language file).

### Q: Bagaimana cara melihat log aktivitas saya sendiri?

**A:** Buka **Profil Saya** → scroll ke section **Aktivitas Terakhir**. Tampil 10 aktivitas terakhir Anda (login, submit penilaian, dll).

### Q: Bagaimana jika saya kena lock karena salah password berkali-kali?

**A:** Saat ini **tidak ada lockout otomatis**. Setiap percobaan login gagal dicatat sebagai security alert (Admin IT bisa lihat di menu Anomaly). Jika salah password 3x, sebaiknya gunakan fitur **Lupa Password** untuk reset.

### Q: Apakah ada batasan ukuran file upload?

**A:** Tidak ada fitur upload file di aplikasi ini (kecuali via phpMyAdmin untuk import SQL). Default PHP `upload_max_filesize` di-set ke 10MB via `.htaccess` (untuk antisipasi).

---

## Bantuan Lebih Lanjut

- **Install issue**: Baca [INSTALL.md](INSTALL.md) → Troubleshooting section
- **API documentation**: Baca [README.md](README.md) → API Endpoints section
- **Bug report**: Hubungi Admin IT internal dengan screenshot + langkah reproduksi

---

© 2025 PT Energi Nusantara. AKHLAK360 v1.1
