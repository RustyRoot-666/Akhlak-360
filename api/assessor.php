<?php
require_once __DIR__ . '/../includes/functions.php';
$action = input('action', ''); startSession();
switch ($action) {
case 'list':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT am.id, am.tipe_assessor, am.status, k.id as karyawan_id, k.nama as karyawan_nama, k.divisi, a.id as assessor_id, a.nama as assessor_nama FROM assessor_mapping am JOIN users k ON am.karyawan_id = k.id JOIN users a ON am.assessor_id = a.id WHERE am.periode_id = ? ORDER BY k.nama, am.tipe_assessor"); $stmt->execute([$periodeId]);
    success(['mappings'=>$stmt->fetchAll(),'periode'=>$periode]); break;
case 'create':
    requireRole('adminhrd'); if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405); $data = inputJSON();
    $periodeId = (int)($data['periode_id'] ?? (getActivePeriode()['id'] ?? 0)); $karyawanId = (int)($data['karyawan_id'] ?? 0); $assessorId = (int)($data['assessor_id'] ?? 0); $tipe = $data['tipe_assessor'] ?? '';
    if (!$periodeId || !$karyawanId || !$assessorId || !$tipe) error('Semua field wajib diisi'); if ($karyawanId === $assessorId) error('Tidak boleh diri sendiri'); $db = getDB();
    $stmt = $db->prepare("SELECT id FROM assessor_mapping WHERE periode_id = ? AND karyawan_id = ? AND assessor_id = ? AND tipe_assessor = ?"); $stmt->execute([$periodeId, $karyawanId, $assessorId, $tipe]); if ($stmt->fetch()) error('Mapping sudah ada');
    if ($tipe === 'atasan') { $stmt = $db->prepare("SELECT id FROM assessor_mapping WHERE periode_id = ? AND karyawan_id = ? AND tipe_assessor = 'atasan'"); $stmt->execute([$periodeId, $karyawanId]); if ($stmt->fetch()) error('Karyawan sudah memiliki atasan'); }
    $db->prepare("INSERT INTO assessor_mapping (periode_id, karyawan_id, assessor_id, tipe_assessor, status) VALUES (?, ?, ?, ?, 'assigned')")->execute([$periodeId, $karyawanId, $assessorId, $tipe]);
    logActivity('Tambah assessor', "Karyawan: {$karyawanId}, Assessor: {$assessorId}", 'admin', $_SESSION['user_id']); success(['id'=>$db->lastInsertId()], 'Assessor berhasil ditambahkan'); break;
case 'delete':
    requireRole('adminhrd'); $id = (int)input('id', 0); if (!$id) error('ID diperlukan');
    getDB()->prepare("DELETE FROM assessor_mapping WHERE id = ? AND status IN ('assigned','draft')")->execute([$id]); success([], 'Mapping dihapus'); break;
case 'auto_assign':
    requireRole('adminhrd'); if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405); $data = inputJSON();
    $karyawanId = (int)($data['karyawan_id'] ?? 0); $periodeId = (int)($data['periode_id'] ?? (getActivePeriode()['id'] ?? 0));
    if (!$karyawanId || !$periodeId) error('Data tidak lengkap'); $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'karyawan'"); $stmt->execute([$karyawanId]); $karyawan = $stmt->fetch(); if (!$karyawan) error('Karyawan tidak ditemukan');
    $count = 0;
    if ($karyawan['manager_id']) {
        $stmt = $db->prepare("INSERT IGNORE INTO assessor_mapping (periode_id, karyawan_id, assessor_id, tipe_assessor) VALUES (?, ?, ?, 'diri')"); $stmt->execute([$periodeId, $karyawanId, $karyawanId]); if ($stmt->rowCount() > 0) $count++;
        $stmt = $db->prepare("INSERT IGNORE INTO assessor_mapping (periode_id, karyawan_id, assessor_id, tipe_assessor) VALUES (?, ?, ?, 'atasan')"); $stmt->execute([$periodeId, $karyawanId, $karyawan['manager_id']]); if ($stmt->rowCount() > 0) $count++;
    }
    $stmt = $db->prepare("SELECT id FROM users WHERE id != ? AND divisi = ? AND role = 'karyawan' AND status = 'aktif' ORDER BY RAND() LIMIT 2"); $stmt->execute([$karyawanId, $karyawan['divisi']]); $peers = $stmt->fetchAll();
    foreach ($peers as $peer) { $stmt = $db->prepare("INSERT IGNORE INTO assessor_mapping (periode_id, karyawan_id, assessor_id, tipe_assessor) VALUES (?, ?, ?, 'peer')"); $stmt->execute([$periodeId, $karyawanId, $peer['id']]); if ($stmt->rowCount() > 0) $count++; }
    logActivity('Auto assign', "Karyawan: {$karyawanId}, {$count} mapping", 'admin', $_SESSION['user_id']); success(['assigned'=>$count], "{$count} assessor di-assign"); break;
case 'stats':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE u.role = 'karyawan' AND u.status = 'aktif' AND NOT EXISTS (SELECT 1 FROM assessor_mapping am WHERE am.karyawan_id = u.id AND am.periode_id = ?)"); $stmt->execute([$periodeId]); $belumDiatur = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(DISTINCT karyawan_id) FROM assessor_mapping WHERE periode_id = ?"); $stmt->execute([$periodeId]); $lengkap = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT karyawan_id, COUNT(*) FROM assessor_mapping WHERE periode_id = ? AND tipe_assessor = 'atasan' GROUP BY karyawan_id HAVING COUNT(*) > 1"); $stmt->execute([$periodeId]); $konflik = count($stmt->fetchAll());
    $stmt = $db->prepare("SELECT COUNT(*) FROM assessor_mapping WHERE periode_id = ?"); $stmt->execute([$periodeId]); $aktif = $stmt->fetchColumn();
    success(['belum_diatur'=>(int)$belumDiatur,'lengkap'=>(int)$lengkap,'konflik'=>(int)$konflik,'assessor_aktif'=>(int)$aktif]); break;
default: error('Unknown action', 400);
}
