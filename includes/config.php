<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 1800);

// Database configuration — override via env vars if needed (e.g. for Docker/cloud)
// Default uses port 3307 (sesuai request user). Jika MySQL Anda pakai port 3306,
// ubah konstanta DB_PORT di bawah atau set env var DB_PORT=3306.
// Railway & cloud deployment: reads from environment variables automatically
if (!defined('DB_HOST')) define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
if (!defined('DB_USER')) define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'akhlak360');
if (!defined('APP_NAME')) define('APP_NAME', 'AKHLAK360');
if (!defined('COMPANY_NAME')) define('COMPANY_NAME', 'PT Energi Nusantara');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0');
if (!defined('BASE_URL')) define('BASE_URL', '');
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 30 * 60);

$AKHLAK_DIMENSIONS = [
    'am' => ['nama' => 'Amanah', 'warna' => '#1565C0', 'bg' => '#E3F2FD'],
    'ko' => ['nama' => 'Kompeten', 'warna' => '#E65100', 'bg' => '#FFF3E0'],
    'ha' => ['nama' => 'Harmonis', 'warna' => '#2E7D32', 'bg' => '#E8F5E9'],
    'lo' => ['nama' => 'Loyal', 'warna' => '#C62828', 'bg' => '#FFEBEE'],
    'ad' => ['nama' => 'Adaptif', 'warna' => '#4527A0', 'bg' => '#EDE7F6'],
    'kol' => ['nama' => 'Kolaboratif', 'warna' => '#00695C', 'bg' => '#E0F2F1'],
];

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            http_response_code(500);
            // For API endpoints, return JSON; for pages, return HTML
            $isApi = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
            if ($isApi) {
                header('Content-Type: application/json');
                die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]));
            } else {
                die('<h2>Database Connection Error</h2><p>Pastikan MySQL berjalan dan kredensial di <code>includes/config.php</code> benar.</p><p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p><p><strong>Config:</strong> host=' . DB_HOST . ', port=' . DB_PORT . ', db=' . DB_NAME . ', user=' . DB_USER . '</p>');
            }
        }
    }
    return $pdo;
}
