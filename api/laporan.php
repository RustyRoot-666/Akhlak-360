<?php
require_once __DIR__ . '/../includes/functions.php';
$action = input('action', ''); startSession();
switch ($action) {
case 'divisi':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT u.divisi, COUNT(DISTINCT u.id) as jumlah_karyawan, AVG(r.nilai_final) as avg_score FROM users u LEFT JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi ORDER BY avg_score DESC"); $stmt->execute([$periodeId]); $divisions = $stmt->fetchAll();
    success(['divisions'=>$divisions,'periode'=>$periode]); break;
case 'matrix':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT u.divisi, d.kode, d.nama as dimensi_nama, AVG(r.nilai_final) as avg_nilai FROM users u JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi, d.id ORDER BY u.divisi, d.urutan"); $stmt->execute([$periodeId]); $rows = $stmt->fetchAll();
    $matrix = []; $dimensions = []; foreach ($rows as $row) { $div = $row['divisi']; $dim = $row['dimensi_nama']; if (!isset($matrix[$div])) $matrix[$div] = []; $matrix[$div][$dim] = round($row['avg_nilai'], 2); if (!in_array($dim, $dimensions)) $dimensions[] = $dim; }
    success(['matrix'=>$matrix,'dimensions'=>$dimensions,'periode'=>$periode]); break;
case 'detail_karyawan':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $divisi = input('divisi', ''); $db = getDB();
    $sql = "SELECT u.id, u.nik, u.nama, u.divisi, u.jabatan, d.kode, d.nama as dimensi_nama, r.nilai_final, r.nilai_self, r.nilai_peer, r.nilai_atasan, r.nilai_bawahan FROM users u LEFT JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? LEFT JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE u.role = 'karyawan' AND u.status = 'aktif'"; $params = [$periodeId];
    if ($divisi) { $sql .= " AND u.divisi = ?"; $params[] = $divisi; } $sql .= " ORDER BY u.divisi, u.nama, d.urutan";
    $stmt = $db->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll(); $employees = [];
    foreach ($rows as $row) { $id = $row['id']; if (!isset($employees[$id])) $employees[$id] = ['id'=>$id,'nik'=>$row['nik'],'nama'=>$row['nama'],'divisi'=>$row['divisi'],'jabatan'=>$row['jabatan'],'scores'=>[]]; if ($row['kode']) $employees[$id]['scores'][$row['kode']] = round($row['nilai_final'], 2); }
    success(['employees'=>array_values($employees),'periode'=>$periode]); break;
case 'top_performers':
    requireRole(['adminhrd','manager']); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $limit = (int)input('limit', 10); $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.nama, u.divisi, u.jabatan, AVG(r.nilai_final) as avg_score FROM users u JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.id HAVING avg_score IS NOT NULL ORDER BY avg_score DESC LIMIT ?"); $stmt->execute([$periodeId, $limit]);
    success(['performers'=>$stmt->fetchAll(),'periode'=>$periode]); break;
case 'progress':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT u.divisi, COUNT(DISTINCT am.karyawan_id) as total_karyawan, SUM(CASE WHEN am.status = 'submitted' THEN 1 ELSE 0 END) as completed, COUNT(*) as total_mapping FROM users u LEFT JOIN assessor_mapping am ON u.id = am.karyawan_id AND am.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi ORDER BY u.divisi"); $stmt->execute([$periodeId]); $progress = $stmt->fetchAll();
    success(['progress'=>$progress,'periode'=>$periode]); break;
default: error('Unknown action', 400);
}
