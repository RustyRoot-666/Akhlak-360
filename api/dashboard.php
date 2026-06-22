<?php
require_once __DIR__ . '/../includes/functions.php';
$action = input('action', '');
startSession();
switch ($action) {
case 'karyawan':
    requireRole('karyawan'); $user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT am.id as mapping_id, am.tipe_assessor, am.status, u.id as target_id, u.nama as target_nama, u.divisi as target_divisi, u.jabatan as target_jabatan FROM assessor_mapping am JOIN users u ON am.karyawan_id = u.id WHERE am.assessor_id = ? AND am.periode_id = ? ORDER BY am.status DESC, u.nama");
    $stmt->execute([$user['id'], $periodeId]); $assessments = $stmt->fetchAll();
    $stats = getUserStats($user['id'], $periodeId);
    $stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY d.urutan");
    $stmt->execute([$user['id'], $periodeId]); $scores = $stmt->fetchAll();
    $avgScore = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_final')) / count($scores), 2) : null;
    success(['user'=>$user,'periode'=>$periode,'stats'=>$stats,'assessments'=>$assessments,'scores'=>$scores,'avg_score'=>$avgScore]); break;
case 'manager':
    requireRole('manager'); $user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT u.*, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = ?) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ? AND status = 'submitted') as completed, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ?) as total FROM users u WHERE u.manager_id = ? AND u.status = 'aktif' ORDER BY u.nama");
    $stmt->execute([$periodeId, $periodeId, $periodeId, $user['id']]); $team = $stmt->fetchAll();
    $stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, AVG(r.nilai_final) as avg_score FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id JOIN users u ON r.karyawan_id = u.id WHERE u.manager_id = ? AND r.periode_id = ? GROUP BY d.id ORDER BY d.urutan");
    $stmt->execute([$user['id'], $periodeId]); $teamScores = $stmt->fetchAll();
    success(['user'=>$user,'periode'=>$periode,'team'=>$team,'team_scores'=>$teamScores,'stats'=>getManagerStats($user['id'], $periodeId)]); break;
case 'hrd':
    requireRole('adminhrd'); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT u.divisi, COUNT(DISTINCT u.id) as total_karyawan, AVG(r.nilai_final) as avg_score FROM users u LEFT JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi ORDER BY u.divisi");
    $stmt->execute([$periodeId]); $divisions = $stmt->fetchAll();
    $stmt = $db->query("SELECT u.*, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = {$periodeId}) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = {$periodeId} AND status = 'submitted') as completed, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = {$periodeId}) as total FROM users u WHERE u.role = 'karyawan' AND u.status = 'aktif' ORDER BY u.nama LIMIT 10");
    $employees = $stmt->fetchAll();
    success(['periode'=>$periode,'stats'=>getHRDStats($periodeId),'divisions'=>$divisions,'employees'=>$employees]); break;
case 'adminit':
    requireRole('adminit'); $db = getDB();
    $events24h = $db->query("SELECT COUNT(*) FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    $activeAdmins = $db->query("SELECT COUNT(DISTINCT user_id) FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND user_id IS NOT NULL")->fetchColumn();
    $flagged = $db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'open'")->fetchColumn();
    $alerts = $db->query("SELECT * FROM security_alerts ORDER BY created_at DESC LIMIT 10")->fetchAll();
    $logs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 20")->fetchAll();
    success(['stats'=>['events_24h'=>(int)$events24h,'active_admins'=>(int)$activeAdmins,'flagged'=>(int)$flagged,'db_status'=>'4/4 online','backup_success'=>98.7],'alerts'=>$alerts,'logs'=>$logs]); break;
default: error('Unknown action', 400);
}
