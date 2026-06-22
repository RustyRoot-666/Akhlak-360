<?php
require_once __DIR__ . '/../includes/functions.php';
$action = input('action', ''); startSession();
switch ($action) {
case 'list':
    requireRole('adminhrd'); $db = getDB(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0;
    $divisiF = input('divisi', ''); $statusF = input('status', ''); $search = input('search', '');
    $sql = "SELECT u.*, m.nama as manager_nama, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = ?) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ? AND status = 'submitted') as completed_count, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ?) as total_assessors FROM users u LEFT JOIN users m ON u.manager_id = m.id WHERE u.role = 'karyawan'";
    $params = [$periodeId, $periodeId, $periodeId];
    if ($divisiF) { $sql .= " AND u.divisi = ?"; $params[] = $divisiF; }
    if ($statusF) { $sql .= " AND u.status = ?"; $params[] = $statusF; }
    if ($search) { $sql .= " AND (u.nama LIKE ? OR u.email LIKE ? OR u.nik LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
    $sql .= " ORDER BY u.nama"; $stmt = $db->prepare($sql); $stmt->execute($params); $employees = $stmt->fetchAll();
    foreach ($employees as &$emp) { $t = (int)$emp['total_assessors']; $emp['progress'] = $t > 0 ? round(((int)$emp['completed_count'] / $t) * 100) : 0; }
    success(['employees'=>$employees]); break;
case 'get':
    requireRole(['adminhrd','manager']); $id = (int)input('id', 0); if (!$id) error('ID diperlukan'); $db = getDB();
    $stmt = $db->prepare("SELECT u.*, m.nama as manager_nama FROM users u LEFT JOIN users m ON u.manager_id = m.id WHERE u.id = ?"); $stmt->execute([$id]); $emp = $stmt->fetch(); if (!$emp) error('Karyawan tidak ditemukan', 404); success(['employee'=>$emp]); break;
case 'create':
    requireRole(['adminhrd','adminit']); if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405); $data = inputJSON();
    foreach (['nik','nama','email','password','divisi','jabatan'] as $f) if (empty($data[$f])) error("Field {$f} wajib diisi");
    $db = getDB(); $stmt = $db->prepare("SELECT id FROM users WHERE nik = ? OR email = ?"); $stmt->execute([$data['nik'], $data['email']]); if ($stmt->fetch()) error('NIK atau email sudah terdaftar');
    $ac = $data['avatar_color'] ?? '#1565C0';
    $role = $data['role'] ?? 'karyawan';
    if (!in_array($role, ['karyawan','manager','adminhrd','adminit'])) error('Role tidak valid');
    // Only Admin IT can create other Admin IT accounts
    if ($role === 'adminit' && $_SESSION['role'] !== 'adminit') error('Hanya Admin IT yang dapat membuat akun Admin IT', 403);
    $stmt = $db->prepare("INSERT INTO users (nik, nama, email, password_hash, role, divisi, jabatan, avatar_color, manager_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
    $stmt->execute([$data['nik'], $data['nama'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT), $role, $data['divisi'], $data['jabatan'], $ac, $data['manager_id'] ?? null]);
    logActivity('Tambah user', "ID: {$db->lastInsertId()}, Nama: {$data['nama']}, Role: {$role}", 'admin', $_SESSION['user_id']);
    success(['id'=>$db->lastInsertId()], 'User baru berhasil ditambahkan'); break;
case 'update':
    requireRole(['adminhrd','adminit']); if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405); $data = inputJSON(); $id = (int)($data['id'] ?? 0); if (!$id) error('ID diperlukan');
    $fields = []; $params = []; foreach (['nik','nama','email','divisi','jabatan','avatar_color','manager_id','status','role'] as $f) if (isset($data[$f])) { $fields[] = "{$f} = ?"; $params[] = $data[$f]; }
    if (!empty($data['password'])) { $fields[] = "password_hash = ?"; $params[] = password_hash($data['password'], PASSWORD_DEFAULT); }
    if (empty($fields)) error('Tidak ada data yang diupdate'); $params[] = $id;
    getDB()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
    logActivity('Update user', "ID: {$id}", 'admin', $_SESSION['user_id']); success([], 'User berhasil diupdate'); break;
case 'delete':
    requireRole(['adminhrd','adminit']); $id = (int)input('id', 0); if (!$id) error('ID diperlukan'); getDB()->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?")->execute([$id]);
    logActivity('Nonaktifkan user', "ID: {$id}", 'admin', $_SESSION['user_id']); success([], 'User berhasil dinonaktifkan'); break;
case 'managers':
    requireRole('adminhrd'); success(['managers'=>getDB()->query("SELECT id, nik, nama, divisi FROM users WHERE role = 'manager' AND status = 'aktif' ORDER BY nama")->fetchAll()]); break;
case 'divisions':
    success(['divisions'=>['Operations','Finance','IT','HR','Marketing','Legal','Procurement']]); break;
default: error('Unknown action', 400);
}
