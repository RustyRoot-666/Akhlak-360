<?php
require_once __DIR__ . '/config.php';

function startSession() { if (session_status() === PHP_SESSION_NONE) session_start(); }
function requireAuth() {
    startSession();
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json'); http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'redirect' => 'login']); exit;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy(); header('Content-Type: application/json'); http_response_code(401);
        echo json_encode(['error' => 'Session expired', 'redirect' => 'login']); exit;
    }
    $_SESSION['last_activity'] = time();
}
function requireRole($allowedRoles) {
    requireAuth();
    if (!in_array($_SESSION['role'], (array)$allowedRoles)) {
        http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit;
    }
}
function getCurrentUser() {
    startSession();
    if (!isset($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, nik, nama, email, role, divisi, jabatan, avatar_color, manager_id, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]); return $stmt->fetch();
}
function isLoggedIn() { startSession(); return isset($_SESSION['user_id']); }
function getUserRole() { startSession(); return $_SESSION['role'] ?? null; }
function jsonResponse($data, $status = 200) {
    header('Content-Type: application/json; charset=utf-8'); http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function success($data = [], $message = 'Success') { jsonResponse(['success' => true, 'message' => $message, 'data' => $data]); }
function error($message = 'Error', $status = 400) { jsonResponse(['success' => false, 'error' => $message], $status); }
function input($key, $default = null) { return $_POST[$key] ?? $_GET[$key] ?? $default; }
function inputJSON() { $json = file_get_contents('php://input'); return json_decode($json, true) ?: []; }
function sanitize($str) { return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8'); }
function logActivity($aksi, $detail = '', $tipe = 'system', $userId = null) {
    try { $db = getDB(); $uid = $userId ?? ($_SESSION['user_id'] ?? null); $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, aksi, detail, tipe, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $aksi, $detail, $tipe, $ip]);
    } catch (Exception $e) {}
}
function getInitials($name) {
    $parts = explode(' ', strtoupper(trim($name)));
    return count($parts) >= 2 ? substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1) : strtoupper(substr($name, 0, 2));
}
function getActivePeriode() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM periode WHERE status = 'aktif' ORDER BY tahun DESC, semester DESC LIMIT 1");
    return $stmt->fetch();
}
function getUserStats($userId, $periodeId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM assessor_mapping WHERE assessor_id = ? AND periode_id = ? AND status IN ('assigned','draft')");
    $stmt->execute([$userId, $periodeId]); $pending = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM assessor_mapping WHERE assessor_id = ? AND periode_id = ? AND status = 'submitted'");
    $stmt->execute([$userId, $periodeId]); $completed = $stmt->fetchColumn();
    return ['pending' => (int)$pending, 'completed' => (int)$completed, 'total' => (int)($pending + $completed)];
}
function getManagerStats($managerId, $periodeId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE manager_id = ? AND status = 'aktif'");
    $stmt->execute([$managerId]); $totalTeam = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(DISTINCT karyawan_id) FROM assessor_mapping WHERE periode_id = ? AND karyawan_id IN (SELECT id FROM users WHERE manager_id = ?) AND status = 'submitted'");
    $stmt->execute([$periodeId, $managerId]); $completed = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT AVG(nilai_final) FROM rekap_nilai WHERE periode_id = ? AND karyawan_id IN (SELECT id FROM users WHERE manager_id = ?)");
    $stmt->execute([$periodeId, $managerId]); $avgScore = $stmt->fetchColumn();
    return ['total_team' => (int)$totalTeam, 'completed' => (int)$completed, 'pending' => (int)($totalTeam - $completed), 'avg_score' => $avgScore ? round($avgScore, 2) : null];
}
function getHRDStats($periodeId) {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'karyawan' AND status = 'aktif'"); $totalEmp = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(DISTINCT assessor_id), COUNT(DISTINCT CASE WHEN status = 'submitted' THEN assessor_id END) FROM assessor_mapping WHERE periode_id = ?");
    $stmt->execute([$periodeId]); $row = $stmt->fetch(); $totalA = $row[0] ?? 0; $comp = $row[1] ?? 0;
    $stmt = $db->prepare("SELECT COUNT(DISTINCT karyawan_id) FROM assessor_mapping WHERE periode_id = ? AND status = 'assigned'");
    $stmt->execute([$periodeId]); $notStarted = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT AVG(nilai_final) FROM rekap_nilai WHERE periode_id = ?"); $stmt->execute([$periodeId]); $avgScore = $stmt->fetchColumn();
    return ['total_employees' => (int)$totalEmp, 'completion_rate' => $totalA > 0 ? round(($comp / $totalA) * 100) : 0, 'not_started' => (int)$notStarted, 'avg_score' => $avgScore ? round($avgScore, 2) : null];
}
function calculateFinalScore($karyawanId, $periodeId) {
    $db = getDB();
    $dims = $db->query("SELECT id, kode FROM dimensi_akhlak ORDER BY urutan")->fetchAll();
    $weights = ['diri' => 0.10, 'peer' => 0.20, 'atasan' => 0.40, 'bawahan' => 0.30];
    foreach ($dims as $dim) {
        $stmt = $db->prepare("SELECT am.tipe_assessor, AVG(hp.nilai) as avg_nilai FROM hasil_penilaian hp JOIN assessor_mapping am ON hp.mapping_id = am.id JOIN pertanyaan p ON hp.pertanyaan_id = p.id WHERE am.karyawan_id = ? AND am.periode_id = ? AND p.dimensi_id = ? GROUP BY am.tipe_assessor");
        $stmt->execute([$karyawanId, $periodeId, $dim['id']]);
        $scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $final = 0; $totalWeight = 0;
        foreach ($weights as $type => $weight) {
            if (isset($scores[$type])) { $final += $scores[$type] * $weight; $totalWeight += $weight; }
        }
        if ($totalWeight > 0) {
            $final = round($final / $totalWeight * array_sum($weights), 2);
            $stmt = $db->prepare("INSERT INTO rekap_nilai (periode_id, karyawan_id, dimensi_id, nilai_self, nilai_peer, nilai_atasan, nilai_bawahan, nilai_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nilai_self = VALUES(nilai_self), nilai_peer = VALUES(nilai_peer), nilai_atasan = VALUES(nilai_atasan), nilai_bawahan = VALUES(nilai_bawahan), nilai_final = VALUES(nilai_final)");
            $stmt->execute([$periodeId, $karyawanId, $dim['id'], $scores['diri'] ?? null, $scores['peer'] ?? null, $scores['atasan'] ?? null, $scores['bawahan'] ?? null, $final]);
        }
    }
}
function exportToCSV($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, $headers);
    foreach ($rows as $row) fputcsv($output, $row);
    fclose($output); exit;
}
function cfgGet($key, $default = null) {
    try {
        $stmt = getDB()->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : $default;
    } catch (Exception $e) { return $default; }
}
function cfgSet($key, $value, $desc = '') {
    try {
        $stmt = getDB()->prepare("INSERT INTO system_config (config_key, config_value, deskripsi) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), deskripsi = VALUES(deskripsi)");
        $stmt->execute([$key, $value, $desc]);
        return true;
    } catch (Exception $e) { return false; }
}
