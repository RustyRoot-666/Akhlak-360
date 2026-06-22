<?php
/**
 * AKHLAK360 — Database Connection Test Page
 *
 * Halaman diagnostik untuk memverifikasi koneksi MySQL sebelum login.
 * Akses: http://localhost/akhlak360-php/dbtest.php
 *
 * Setelah koneksi berhasil, halaman ini bisa dihapus atau diproteksi
 * untuk menghindari exposure informasi di produksi.
 */
require_once __DIR__ . '/includes/config.php';

$results = [];
$ok = true;

// Step 1: PHP version check
$results[] = [
    'step' => 'PHP Version',
    'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'fail',
    'message' => 'PHP ' . PHP_VERSION . ' (minimal 7.4, rekomendasi 8.0+)',
];
if (version_compare(PHP_VERSION, '7.4.0', '<')) $ok = false;

// Step 2: Required PHP extensions
$requiredExt = ['pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($requiredExt as $ext) {
    $loaded = extension_loaded($ext);
    $results[] = [
        'step' => "Extension: $ext",
        'status' => $loaded ? 'ok' : 'fail',
        'message' => $loaded ? 'Loaded' : 'NOT loaded — install dengan: apt-get install php-' . $ext,
    ];
    if (!$loaded) $ok = false;
}

// Step 3: Display current config
$results[] = [
    'step' => 'Config',
    'status' => 'info',
    'message' => 'Host=' . DB_HOST . ' | Port=' . DB_PORT . ' | DB=' . DB_NAME . ' | User=' . DB_USER,
];

// Step 4: TCP connection check (host:port reachable)
$hostReachable = @fsockopen(DB_HOST, (int)DB_PORT, $errno, $errstr, 3);
$results[] = [
    'step' => 'TCP ' . DB_HOST . ':' . DB_PORT,
    'status' => $hostReachable ? 'ok' : 'fail',
    'message' => $hostReachable ? 'Port terbuka dan bisa diakses' : "Tidak bisa terhubung — $errstr ($errno). Pastikan MySQL berjalan di port " . DB_PORT,
];
if ($hostReachable) fclose($hostReachable);
else $ok = false;

// Step 5: PDO connection test
$pdoOk = false;
$pdoErr = '';
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    $results[] = [
        'step' => 'PDO MySQL',
        'status' => 'ok',
        'message' => 'Connected — MySQL version: ' . $version,
    ];
    $pdoOk = true;
} catch (PDOException $e) {
    $results[] = [
        'step' => 'PDO MySQL',
        'status' => 'fail',
        'message' => $e->getMessage(),
    ];
    $pdoErr = $e->getMessage();
    $ok = false;
}

// Step 6: Schema check — apakah tabel utama sudah di-import?
if ($pdoOk) {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = ['users', 'periode', 'dimensi_akhlak', 'pertanyaan', 'assessor_mapping', 'hasil_penilaian', 'rekap_nilai', 'catatan_manager', 'activity_log', 'system_config', 'notifikasi', 'security_alerts'];
        $missing = array_diff($requiredTables, $tables);
        if (empty($missing)) {
            $results[] = [
                'step' => 'Schema',
                'status' => 'ok',
                'message' => count($tables) . ' tabel ditemukan, semua tabel utama ada',
            ];
        } else {
            $results[] = [
                'step' => 'Schema',
                'status' => 'fail',
                'message' => 'Tabel belum lengkap. Import akhlak360.sql terlebih dahulu. Missing: ' . implode(', ', $missing),
            ];
            $ok = false;
        }

        // Step 7: Seed data check
        $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $periodeCount = (int)$pdo->query("SELECT COUNT(*) FROM periode")->fetchColumn();
        if ($userCount > 0) {
            $results[] = [
                'step' => 'Seed Data',
                'status' => $userCount >= 4 ? 'ok' : 'warn',
                'message' => "$userCount user terdaftar, $periodeCount periode. " . ($userCount < 4 ? 'Import seed_data.sql untuk demo login.' : 'Siap untuk login demo.'),
            ];
        } else {
            $results[] = [
                'step' => 'Seed Data',
                'status' => 'fail',
                'message' => 'Belum ada user. Import seed_data.sql agar bisa login demo.',
            ];
            $ok = false;
        }
    } catch (Exception $e) {
        $results[] = [
            'step' => 'Schema',
            'status' => 'fail',
            'message' => 'Gagal query schema: ' . $e->getMessage(),
        ];
        $ok = false;
    }
}

// Step 8: Writable check (untuk session & upload)
$sessionPath = session_save_path() ?: sys_get_temp_dir();
$results[] = [
    'step' => 'Session Path',
    'status' => is_writable($sessionPath) ? 'ok' : 'warn',
    'message' => $sessionPath . ' — ' . (is_writable($sessionPath) ? 'Writable' : 'Not writable'),
];

?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DB Test — AKHLAK360</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #F5F5F0; color: #1B2A4A; padding: 40px 20px; min-height: 100vh; }
    .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
    .header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #E2E8F0; }
    .header img { height: 36px; }
    .header h1 { font-size: 22px; font-weight: 700; }
    .header .ver { color: #64748B; font-size: 13px; margin-left: auto; }
    .summary { padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 15px; font-weight: 600; }
    .summary.ok { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
    .summary.fail { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
    .results { display: flex; flex-direction: column; gap: 8px; }
    .result { display: flex; gap: 12px; padding: 12px 16px; border-radius: 6px; background: #F8FAFC; align-items: flex-start; }
    .result .step { font-weight: 600; min-width: 180px; color: #1B2A4A; font-size: 13px; }
    .result .msg { flex: 1; color: #64748B; font-size: 13px; word-break: break-word; }
    .result .icon { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .icon.ok { background: #2E7D32; }
    .icon.fail { background: #C62828; }
    .icon.warn { background: #E65100; }
    .icon.info { background: #1565C0; }
    .next-steps { margin-top: 24px; padding: 16px 20px; background: #E3F2FD; border-radius: 8px; border-left: 4px solid #1565C0; }
    .next-steps h3 { color: #1565C0; font-size: 14px; margin-bottom: 8px; }
    .next-steps ol { padding-left: 20px; font-size: 13px; color: #1B2A4A; line-height: 1.7; }
    .next-steps a { color: #1565C0; }
    .footer { margin-top: 24px; text-align: center; font-size: 12px; color: #64748B; }
    .footer a { color: #1565C0; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="assets/logo.png" alt="AKHLAK360">
      <h1>Database Connection Test</h1>
      <span class="ver">AKHLAK360 v<?= APP_VERSION ?></span>
    </div>

    <div class="summary <?= $ok ? 'ok' : 'fail' ?>">
      <?= $ok ? '✓ Semua check berhasil — siap digunakan!' : '✕ Beberapa check gagal — perbaiki dulu sebelum login.' ?>
    </div>

    <div class="results">
      <?php foreach ($results as $r): ?>
        <div class="result">
          <div class="icon <?= $r['status'] ?>"><?= $r['status'] === 'ok' ? '✓' : ($r['status'] === 'fail' ? '✕' : ($r['status'] === 'warn' ? '!' : 'i')) ?></div>
          <div class="step"><?= htmlspecialchars($r['step']) ?></div>
          <div class="msg"><?= htmlspecialchars($r['message']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="next-steps">
      <h3>Langkah Berikutnya:</h3>
      <ol>
        <li>Pastikan semua check di atas berwarna <strong>hijau</strong>.</li>
        <li>Jika MySQL port salah, edit <code>includes/config.php</code> bagian <code>DB_PORT</code>.</li>
        <li>Jika schema belum di-import, jalankan: <code>mysql -u root -p akhlak360 &lt; akhlak360.sql</code></li>
        <li>Jika belum ada user demo, jalankan: <code>mysql -u root -p akhlak360 &lt; seed_data.sql</code></li>
        <li>Buka <a href="index.php">halaman login</a> dan coba kredensial demo (lihat INSTALL.md).</li>
        <li>Setelah berhasil, <strong>hapus file dbtest.php ini</strong> dari server produksi demi keamanan.</li>
      </ol>
    </div>

    <div class="footer">
      <p>AKHLAK360 &copy; <?= date('Y') ?> PT Energi Nusantara &middot; <a href="index.php">← Kembali ke Login</a></p>
    </div>
  </div>
</body>
</html>
