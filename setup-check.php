<?php
require_once __DIR__ . '/includes/config.php';

$checks = [];
$checks[] = ['PHP Version', version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'Perlu PHP 7.4+', PHP_VERSION];
$checks[] = ['PDO MySQL', extension_loaded('pdo_mysql') ? 'OK' : 'Belum aktif', extension_loaded('pdo_mysql') ? 'Tersedia' : 'Aktifkan ekstensi pdo_mysql'];
$checks[] = ['Session', function_exists('session_start') ? 'OK' : 'Belum aktif', function_exists('session_start') ? 'Tersedia' : 'Aktifkan session'];

$dbStatus = 'Belum tersambung';
$dbDetail = DB_HOST . ':' . DB_PORT . '/' . DB_NAME;
try {
    $hostParts = explode(':', DB_HOST, 2);
    $host = $hostParts[0];
    $ports = array_values(array_unique([$hostParts[1] ?? DB_PORT, '3307', '3306']));
    $pdo = null; $usedPort = DB_PORT; $lastException = null;
    foreach ($ports as $port) {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $usedPort = $port;
            break;
        } catch (Throwable $e) {
            $lastException = $e;
        }
    }
    if (!$pdo) throw $lastException;
    $exists = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote(DB_NAME))->fetchColumn();
    if ($exists) {
        $pdo->exec("USE `" . str_replace('`', '``', DB_NAME) . "`");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dbStatus = count($tables) >= 12 ? 'OK' : 'Database ada, tabel belum lengkap';
        $dbDetail = count($tables) . ' tabel ditemukan di port ' . $usedPort;
    } else {
        $dbStatus = 'Database belum dibuat';
        $dbDetail = 'Server MySQL aktif di port ' . $usedPort . ', tetapi database ' . DB_NAME . ' belum ada';
    }
} catch (Throwable $e) {
    $dbDetail = $e->getMessage();
}
$checks[] = ['Database', $dbStatus, $dbDetail];
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup Check - AKHLAK360</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f8fafc;color:#1f2937;margin:0;padding:32px}
    .wrap{max-width:900px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:24px;box-shadow:0 12px 30px rgba(15,23,42,.08)}
    table{width:100%;border-collapse:collapse;margin:18px 0}th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left}
    .ok{color:#166534;font-weight:700}.bad{color:#b91c1c;font-weight:700}code{background:#f1f5f9;padding:2px 6px;border-radius:4px}
  </style>
</head>
<body><div class="wrap">
  <h1>AKHLAK360 Setup Check</h1>
  <p>Gunakan halaman ini setelah memindahkan folder <code>PHP</code> ke web server lokal seperti XAMPP atau Laragon.</p>
  <table><thead><tr><th>Komponen</th><th>Status</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($checks as $c): $ok = $c[1] === 'OK'; ?>
      <tr><td><?= htmlspecialchars($c[0]) ?></td><td class="<?= $ok ? 'ok' : 'bad' ?>"><?= htmlspecialchars($c[1]) ?></td><td><?= htmlspecialchars($c[2]) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <h2>Langkah database</h2>
  <p>Import file berikut berurutan lewat phpMyAdmin atau terminal MySQL:</p>
  <p><code>akhlak360.sql</code> lalu <code>seed_data.sql</code></p>
  <p>Login demo tetap: <code>password123</code> untuk semua role.</p>
  <p><a href="index.php">Kembali ke Login</a></p>
</div></body></html>
