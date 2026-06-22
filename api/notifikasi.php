<?php
require_once __DIR__ . '/../includes/functions.php';
$action = input('action', '');
startSession();
requireAuth();
$db = getDB();
$userId = $_SESSION['user_id'];

switch ($action) {

case 'list':
    $stmt = $db->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    $unread = (int)$db->query("SELECT COUNT(*) FROM notifikasi WHERE user_id = {$userId} AND is_read = 0")->fetchColumn();
    success(['notifications' => $notifications, 'unread_count' => $unread]);
    break;

case 'unread_count':
    $unread = (int)$db->query("SELECT COUNT(*) FROM notifikasi WHERE user_id = {$userId} AND is_read = 0")->fetchColumn();
    success(['unread_count' => $unread]);
    break;

case 'mark_read':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $id = (int)input('id', 0);
    if (!$id) error('ID diperlukan');
    $stmt = $db->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if ($stmt->rowCount() === 0) error('Notifikasi tidak ditemukan', 404);
    success([], 'Notifikasi ditandai dibaca');
    break;

case 'mark_all_read':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $db->prepare("UPDATE notifikasi SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$userId]);
    success([], 'Semua notifikasi ditandai dibaca');
    break;

case 'delete':
    $id = (int)input('id', 0);
    if (!$id) error('ID diperlukan');
    $db->prepare("DELETE FROM notifikasi WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
    success([], 'Notifikasi dihapus');
    break;

case 'create':
    // Only admin can create notification for other users
    requireRole(['adminhrd', 'adminit']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $data = inputJSON();
    $targetUserId = (int)($data['user_id'] ?? 0);
    $judul = trim($data['judul'] ?? '');
    $pesan = trim($data['pesan'] ?? '');
    $tipe = $data['tipe'] ?? 'info';
    $link = $data['link'] ?? null;
    if (!$targetUserId || !$judul || !$pesan) error('user_id, judul, pesan wajib diisi');
    if (!in_array($tipe, ['info', 'peringatan', 'success', 'danger'])) $tipe = 'info';
    $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan, tipe, link) VALUES (?, ?, ?, ?, ?)")
       ->execute([$targetUserId, $judul, $pesan, $tipe, $link]);
    logActivity('Notification sent', "To user: {$targetUserId}, Title: {$judul}", 'admin', $_SESSION['user_id']);
    success(['id' => $db->lastInsertId()], 'Notifikasi terkirim');
    break;

case 'broadcast':
    // Only admin can broadcast
    requireRole(['adminhrd', 'adminit']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $data = inputJSON();
    $judul = trim($data['judul'] ?? '');
    $pesan = trim($data['pesan'] ?? '');
    $tipe = $data['tipe'] ?? 'info';
    $role = $data['role'] ?? null; // optional: only to specific role
    if (!$judul || !$pesan) error('judul, pesan wajib diisi');
    // Get target users
    if ($role) {
        $users = $db->prepare("SELECT id FROM users WHERE role = ? AND status = 'aktif'");
        $users->execute([$role]);
    } else {
        $users = $db->query("SELECT id FROM users WHERE status = 'aktif'");
    }
    $count = 0;
    $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES (?, ?, ?, ?)");
    foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        $stmt->execute([$uid, $judul, $pesan, $tipe]);
        $count++;
    }
    logActivity('Broadcast sent', "To {$count} users, Title: {$judul}", 'admin', $_SESSION['user_id']);
    success(['sent_count' => $count], "Notifikasi terkirim ke {$count} user");
    break;

default:
    error('Unknown action', 400);
}
