# AKHLAK360 — Panduan Instalasi Lengkap

> **Versi 1.1** — MySQL port default **3307** (sesuai request). Aplikasi siap pakai setelah 6 langkah singkat.

---

## Prasyarat Sistem

| Komponen | Minimum | Rekomendasi |
|----------|---------|-------------|
| PHP | 7.4 | 8.1+ |
| MySQL / MariaDB | 5.7 / 10.3 | 8.0 / 10.10+ |
| Ekstensi PHP | pdo_mysql, mbstring, json, session | + openssl, curl |
| Web Server | Apache 2.4 (mod_rewrite) atau Nginx + PHP-FPM | Apache 2.4 |
| Browser | Chrome 100+, Firefox 100+, Edge 100+ | Latest |

**Bundle yang direkomendasikan (sudah termasuk semua di atas):**
- **XAMPP** (Windows): https://www.apachefriends.org/
- **Laragon** (Windows, lebih ringan): https://laragon.org/
- **MAMP** (macOS): https://www.mamp.info/
- **Docker LAMP** (Linux): https://hub.docker.com/_/mysql

---

## Langkah 1 — Copy Project ke Web Root

Extract file `akhlak360-php-completed.zip`. Anda akan mendapatkan folder `akhlak360-php/`. Copy/pindahkan folder tersebut ke web root sesuai stack Anda:

| Stack | Lokasi Web Root |
|-------|-----------------|
| **XAMPP (Windows)** | `C:\xampp\htdocs\akhlak360-php\` |
| **Laragon (Windows)** | `C:\laragon\www\akhlak360-php\` |
| **MAMP (macOS)** | `/Applications/MAMP/htdocs/akhlak360-php/` |
| **Linux Apache** | `/var/www/html/akhlak360-php/` |
| **Nginx + PHP-FPM** | `/usr/share/nginx/html/akhlak360-php/` |

> **Catatan:** Nama folder `akhlak360-php` bisa diubah, tapi pastikan URL di browser menyesuaikan.

---

## Langkah 2 — Buat Database MySQL

### Opsi A: Via phpMyAdmin (paling mudah untuk pemula)

1. Buka phpMyAdmin di browser:
   - **XAMPP**: http://localhost/phpmyadmin
   - **Laragon**: http://localhost/phpmyadmin (jika diaktifkan)
   - **MAMP**: http://localhost:8888/phpMyAdmin/
2. Klik tab **Import** di atas.
3. Pilih file `akhlak360.sql` dari folder project → klik **Go**.
   (File ini akan otomatis membuat database `akhlak360` + 12 tabel + seed data master.)
4. Ulangi langkah 2–3 untuk file `seed_data.sql` (berisi 16 user demo + mapping assessor + notifikasi).

### Opsi B: Via MySQL CLI (lebih cepat)

```bash
# Masuk ke MySQL (ganti password sesuai konfigurasi MySQL Anda)
mysql -u root -p

# Di dalam MySQL prompt:
CREATE DATABASE akhlak360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema + seed (jalankan dari dalam folder project)
mysql -u root -p akhlak360 < akhlak360.sql
mysql -u root -p akhlak360 < seed_data.sql
```

> **Jika MySQL di port 3307**, tambahkan flag `--port=3307`:
> ```bash
> mysql -u root -p --port=3307 akhlak360 < akhlak360.sql
> mysql -u root -p --port=3307 akhlak360 < seed_data.sql
> ```

---

## Langkah 3 — Konfigurasi Koneksi Database

Edit file `includes/config.php` (sudah di-set port **3307** sebagai default). Sesuaikan jika berbeda:

```php
// File: includes/config.php (baris 12-16)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3307');  // ← Port MySQL Anda
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');   // ← Username MySQL
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');       // ← Password MySQL
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'akhlak360');
```

### Aturan umum kredensial default per stack:

| Stack | User | Password |
|-------|------|----------|
| XAMPP | `root` | *(kosong)* |
| Laragon | `root` | *(kosong)* |
| MAMP | `root` | `root` |
| Linux default | `root` | *(kosong / diatur saat install)* |
| MariaDB default | `root` | *(kosong / diatur saat install)* |

### Alternatif: Override via environment variable (untuk Docker / production)

```bash
# Di Apache vhost atau .env:
SetEnv DB_HOST localhost
SetEnv DB_PORT 3307
SetEnv DB_USER root
SetEnv DB_PASS rahasia
SetEnv DB_NAME akhlak360
```

---

## Langkah 4 — Verifikasi Koneksi (PENTING!)

Sebelum login, pastikan koneksi database berhasil. Buka browser ke:

```
http://localhost/akhlak360-php/dbtest.php
```

Anda akan melihat halaman diagnostik dengan beberapa check:

| Check | Status yang diharapkan | Solusi jika gagal |
|-------|------------------------|-------------------|
| PHP Version | ✓ ok (≥ 7.4) | Upgrade PHP |
| Extension: pdo_mysql | ✓ ok | `apt install php-mysql` / aktifkan di php.ini |
| Extension: mbstring | ✓ ok | `apt install php-mbstring` |
| Config | info — port=3307, db=akhlak360 | — |
| TCP localhost:3307 | ✓ ok | Mulai MySQL service, atau cek port |
| PDO MySQL | ✓ ok | Cek kredensial di config.php |
| Schema | ✓ ok (12 tabel) | Import `akhlak360.sql` |
| Seed Data | ✓ ok (16 user) | Import `seed_data.sql` |

**Jika semua check berwarna hijau**, lanjut ke Langkah 5.
**Jika ada yang merah**, perbaiki dulu sesuai kolom "Solusi".

> **Keamanan:** Setelah verifikasi berhasil, **hapus file `dbtest.php`** dari server produksi karena berisi informasi sensitif.

---

## Langkah 5 — Aplikasi Siap Diakses

Buka browser ke:

```
http://localhost/akhlak360-php/
```

Anda akan diarahkan ke halaman login AKHLAK360.

### Login Demo (password sama untuk semua: `password123`)

| Role | Email | Password | Akses |
|------|-------|----------|-------|
| Karyawan | `ahmad.fauzi@energinusantara.co.id` | `password123` | Dashboard, Penilaian, Nilai AKHLAK |
| Manager | `hendra@energinusantara.co.id` | `password123` | Dashboard Tim, Nilai Karyawan, Performa, Coaching |
| Admin HRD | `admin.hrd@energinusantara.co.id` | `password123` | Dashboard HRD, Data Karyawan, Assessor, Laporan |
| Admin IT | `admin.it@energinusantara.co.id` | `password123` | Dashboard IT, DB, Backup, Security, Activity Log |

> **Catatan:** Pilih **role** yang sesuai dengan tab di form login — email + role harus cocok dengan data di DB.

---

## Langkah 6 — Verifikasi Fitur Utama

Setelah login, lakukan checklist berikut untuk memastikan semua fitur berfungsi:

### Sebagai Karyawan
- [ ] Dashboard menampilkan 4 stat card (Menunggu, Selesai, Nilai Saya, Status Periode)
- [ ] Klik **Penilaian Saya** → muncul daftar penugasan
- [ ] Klik **Isi Penilaian** → form 18 pertanyaan (6 dimensi × 3 pertanyaan) tampil
- [ ] Pilih radio button 1–5 di setiap pertanyaan → progress bar bergerak
- [ ] Klik **Kirim Penilaian** → toast sukses muncul → redirect ke halaman success
- [ ] Klik **Nilai AKHLAK** → radar chart 6 dimensi tampil (jika sudah ada nilai)

### Sebagai Manager
- [ ] Dashboard menampilkan daftar tim + radar profil tim
- [ ] Klik **Nilai Karyawan** → tabel tim dengan tab filter (Semua/Selesai/Berjalan/Tertunda)
- [ ] Klik **Detail** karyawan → breakdown 6 dimensi + tombol "Buat Coaching Plan"
- [ ] Buka modal Coaching Plan → isi catatan + rekomendasi → Simpan → data tersimpan
- [ ] Klik **Dashboard Performa** → tabel coaching priority berurutan dari skor terendah

### Sebagai Admin HRD
- [ ] Dashboard menampilkan progress per divisi
- [ ] Klik **Data Karyawan** → klik **+ Tambah Karyawan** → isi form → Simpan → data tersimpan
- [ ] Klik **Tentukan Assessor** → tombol **Auto Assign** berfungsi
- [ ] Klik **Pantau Progress** → tabel keterlambatan tampil
- [ ] Klik **Laporan** → 4 opsi laporan (Executive, Detail, Matrix, Audit) dapat diakses

### Sebagai Admin IT
- [ ] Dashboard menampilkan service health + recent activity
- [ ] Klik **Database** → inventory tabel tampil + klik **Test Connection** → sukses
- [ ] Klik **Backup** → klik **Run Backup Now** → success toast
- [ ] Klik **Activity Log** → list aktivitas tampil + tombol Export CSV berfungsi
- [ ] Klik **Security** → klik **Run Scan** → hasil scan tampil
- [ ] Klik **Anomaly** → daftar security_alerts tampil + tombol resolve berfungsi

---

## Troubleshooting

### "Database connection failed"

**Gejala:** Halaman menampilkan error merah atau blank page.

**Solusi:**
1. Cek MySQL service berjalan:
   - **Windows (XAMPP)**: Buka XAMPP Control Panel → klik **Start** pada MySQL
   - **Linux**: `sudo systemctl status mysql` atau `sudo systemctl status mariadb`
   - **macOS (MAMP)**: Buka MAMP app → klik **Start Servers**
2. Cek port MySQL:
   ```bash
   # Cek port yang dipakai MySQL
   netstat -an | grep 3306   # Windows
   ss -tlnp | grep mysql     # Linux
   ```
3. Jika MySQL Anda di port **3306** (bukan 3307), edit `includes/config.php`:
   ```php
   define('DB_PORT', '3306');
   ```
4. Test koneksi manual via CLI:
   ```bash
   mysql -u root -p --port=3307   # sesuaikan port
   ```
5. Akses `dbtest.php` (Langkah 4) untuk diagnosa otomatis.

### "Table doesn't exist"

**Gejala:** Login berhasil tapi halaman internal error "table users not found" atau sejenisnya.

**Solusi:**
1. Pastikan `akhlak360.sql` sudah di-import (bukan hanya `seed_data.sql`).
2. Cek tabel yang ada:
   ```sql
   USE akhlak360;
   SHOW TABLES;
   ```
   Harusnya muncul 12 tabel: `users, periode, dimensi_akhlak, pertanyaan, assessor_mapping, hasil_penilaian, rekap_nilai, catatan_manager, activity_log, system_config, notifikasi, security_alerts`.
3. Jika kurang, import ulang `akhlak360.sql`.

### "Email atau password salah" saat login demo

**Gejala:** Login gagal walaupun menggunakan email + password demo yang benar.

**Solusi:**
1. Pastikan `seed_data.sql` sudah di-import (berisi 16 user dengan hash bcrypt).
2. Cek di DB:
   ```sql
   SELECT email, role, status, password_hash FROM users WHERE email = 'ahmad.fauzi@energinusantara.co.id';
   ```
   - `status` harus `aktif`
   - `password_hash` harus diawali `$2y$10$wZU432lbx98MEGFRcs4sReItFdxX2BSNuaBSMj/9DqNB0nZoLnsfu` (cocok dengan `password123`)
3. Pastikan **role** yang dipilih di form login cocok dengan email:
   - Ahmad Fauzi → Karyawan
   - Hendra → Manager
   - admin.hrd → Admin HRD
   - admin.it → Admin IT
4. Jika password dirasa corrupt, re-import `seed_data.sql`:
   ```bash
   mysql -u root -p --port=3307 akhlak360 < seed_data.sql
   ```

### "403 Forbidden" di Apache

**Solusi:**
1. Aktifkan mod_rewrite:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
2. Pastikan `AllowOverride All` di vhost Apache:
   ```apache
   <Directory /var/www/html/akhlak360-php>
       AllowOverride All
       Require all granted
   </Directory>
   ```

### Charts (radar / bar) tidak muncul

**Solusi:**
1. Pastikan ada koneksi internet (Chart.js, XLSX, jsPDF dimuat via CDN).
2. Buka **DevTools (F12) → Console** untuk lihat error JavaScript.
3. Jika offline / intranet tertutup, download library berikut dan host secara lokal:
   - Chart.js: https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
   - XLSX: https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js
   - jsPDF: https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js
4. Update `includes/template.php` bagian `<script src="...">` ke path lokal.

### Tab "Karyawan" di login tidak bisa diubah

**Penjelasan:** Tab role di login form HANYA pre-fill email demo. Tombol role wajib diklik sesuai akun yang ingin dituju.

**Solusi:** Klik salah satu tab (Karyawan/Manager/Admin HRD/Admin IT), lalu klik **Masuk**. Email akan terisi otomatis.

### Login error " Failed login attempt" di security alerts

**Penjelasan:** Setiap percobaan login gagal akan otomatis menambahkan security alert (by design untuk audit). Ini **bukan bug** — fitur keamanan.

**Solusi:** Untuk membersihkan, login sebagai Admin IT → buka **Anomaly** → klik **Resolve** pada alert yang ingin ditutup.

---

## Production Hardening (Opsional tapi Direkomendasikan)

Untuk deployment produksi, lakukan hardening berikut:

### 1. Matikan error display
Edit `includes/config.php`:
```php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/akhlak360-php-error.log');
```

### 2. Aktifkan HTTPS
```bash
# Dapatkan sertifikat gratis dari Let's Encrypt
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d akhlak360.yourdomain.com
```

### 3. Set session cookie secure
Tambahkan di `includes/config.php`:
```php
ini_set('session.cookie_secure', 1);     // HTTPS only
ini_set('session.cookie_httponly', 1);   // Tidak bisa diakses via JS
ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
```

### 4. Hapus file demo
```bash
rm dbtest.php           # mengandung info sensitif
# Pertimbangkan untuk membatasi akses INSTALL.md, README.md via .htaccess
```

### 5. Setup backup otomatis via cron
```bash
# Edit crontab
crontab -e

# Tambahkan baris berikut (backup harian jam 02:00)
0 2 * * * mysqldump -u root -pYOUR_PASSWORD --port=3307 akhlak360 | gzip > /backup/akhlak360-$(date +\%Y\%m\%d).sql.gz

# Retensi: hapus backup lebih dari 30 hari
0 3 * * * find /backup -name "akhlak360-*.sql.gz" -mtime +30 -delete
```

### 6. Logrotate untuk activity_log
Buat file `/etc/logrotate.d/akhlak360`:
```
/var/log/akhlak360-php-error.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
}
```

### 7. Pertimbangkan MFA (Multi-Factor Authentication)
Untuk produksi dengan data sensitif, integrasikan:
- **Google Authenticator** + library PHPGangsta/GoogleAuthenticator
- **SMS OTP** via provider Indonesia (Twilio, Nexmo, atau lokal seperti Mobcel)

### 8. Performance tuning (opsional)
- Aktifkan OPcache di `php.ini`:
  ```ini
  opcache.enable=1
  opcache.memory_consumption=128
  opcache.max_accelerated_files=10000
  ```
- Tambahkan index pada kolom yang sering di-query (cek dengan `EXPLAIN`).
- Untuk beban tinggi, gunakan Redis untuk session storage.

---

## Backup & Restore Manual

### Backup manual
```bash
mysqldump -u root -p --port=3307 akhlak360 > backup-$(date +%Y%m%d).sql
```

### Restore manual
```bash
mysql -u root -p --port=3307 akhlak360 < backup-20250617.sql
```

### Backup via UI (Admin IT)
1. Login sebagai Admin IT (`admin.it@energinusantara.co.id` / `password123`).
2. Buka menu **Backup** → klik **Run Backup Now**.
3. Untuk download log backup: klik **Download Log** (CSV).

---

## Bantuan & Dukungan

- **Baca README.md** untuk dokumentasi API endpoints lengkap.
- **Baca PANDUAN_PENGGUNAAN.md** untuk panduan langkah-demi-langkah menggunakan aplikasi sebagai end-user.
- Jika menemukan bug, dokumentasikan di Issue Tracker internal perusahaan.

---

© 2025 PT Energi Nusantara. AKHLAK360 v1.1
