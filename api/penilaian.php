<?php
require_once __DIR__ . '/../includes/functions.php';
$action = input('action', ''); startSession();
switch ($action) {
case 'pending':
    requireAuth(); $user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT am.id as mapping_id, am.tipe_assessor, am.status, u.id as karyawan_id, u.nama as karyawan_nama, u.divisi, u.jabatan FROM assessor_mapping am JOIN users u ON am.karyawan_id = u.id WHERE am.assessor_id = ? AND am.periode_id = ? ORDER BY FIELD(am.status,'draft','assigned','submitted'), u.nama");
    $stmt->execute([$user['id'], $periodeId]); success(['assessments'=>$stmt->fetchAll(),'periode'=>$periode]); break;
case 'form':
    requireAuth(); $mappingId = (int)input('mapping_id', 0); if (!$mappingId) error('Mapping ID diperlukan'); $db = getDB();
    $stmt = $db->prepare("SELECT am.*, u.nama as target_nama, u.divisi, u.jabatan FROM assessor_mapping am JOIN users u ON am.karyawan_id = u.id WHERE am.id = ? AND am.assessor_id = ?"); $stmt->execute([$mappingId, $_SESSION['user_id']]); $mapping = $stmt->fetch(); if (!$mapping) error('Penilaian tidak ditemukan', 404);
    $stmt = $db->query("SELECT d.id as dimensi_id, d.kode, d.nama, d.warna, d.deskripsi, p.id as pertanyaan_id, p.teks, p.urutan FROM dimensi_akhlak d JOIN pertanyaan p ON d.id = p.dimensi_id WHERE p.status = 'aktif' ORDER BY d.urutan, p.urutan"); $rows = $stmt->fetchAll();
    $dimensions = []; foreach ($rows as $row) { $k = $row['kode']; if (!isset($dimensions[$k])) $dimensions[$k] = ['id'=>$row['dimensi_id'],'kode'=>$k,'nama'=>$row['nama'],'warna'=>$row['warna'],'deskripsi'=>$row['deskripsi'],'pertanyaan'=>[]]; $dimensions[$k]['pertanyaan'][] = ['id'=>$row['pertanyaan_id'],'teks'=>$row['teks'],'urutan'=>$row['urutan']]; }
    $stmt = $db->prepare("SELECT pertanyaan_id, nilai FROM hasil_penilaian WHERE mapping_id = ?"); $stmt->execute([$mappingId]); $answers = []; foreach ($stmt->fetchAll() as $a) $answers[$a['pertanyaan_id']] = $a['nilai'];
    success(['mapping'=>$mapping,'dimensions'=>array_values($dimensions),'answers'=>$answers]); break;
case 'save_answer':
    requireAuth(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $mappingId = (int)input('mapping_id', 0); $pertanyaanId = (int)input('pertanyaan_id', 0); $nilai = (int)input('nilai', 0);
    if (!$mappingId || !$pertanyaanId || $nilai < 1 || $nilai > 5) error('Data tidak valid'); $db = getDB();
    $stmt = $db->prepare("SELECT id FROM assessor_mapping WHERE id = ? AND assessor_id = ?"); $stmt->execute([$mappingId, $_SESSION['user_id']]); if (!$stmt->fetch()) error('Akses ditolak', 403);
    $db->prepare("INSERT INTO hasil_penilaian (mapping_id, pertanyaan_id, nilai) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)")->execute([$mappingId, $pertanyaanId, $nilai]);
    $db->prepare("UPDATE assessor_mapping SET status = 'draft' WHERE id = ? AND status = 'assigned'")->execute([$mappingId]);
    success(['saved'=>true]); break;
case 'submit':
    requireAuth(); if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $data = inputJSON(); $mappingId = (int)($data['mapping_id'] ?? 0); $answers = $data['answers'] ?? [];
    if (!$mappingId || empty($answers)) error('Data tidak lengkap'); $db = getDB();
    $stmt = $db->prepare("SELECT * FROM assessor_mapping WHERE id = ? AND assessor_id = ?"); $stmt->execute([$mappingId, $_SESSION['user_id']]); $mapping = $stmt->fetch(); if (!$mapping) error('Akses ditolak', 403);
    $db->beginTransaction(); try {
        $stmt = $db->prepare("INSERT INTO hasil_penilaian (mapping_id, pertanyaan_id, nilai) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
        foreach ($answers as $pid => $n) $stmt->execute([$mappingId, (int)$pid, (int)$n]);
        $db->prepare("UPDATE assessor_mapping SET status = 'submitted' WHERE id = ?")->execute([$mappingId]); $db->commit();
        calculateFinalScore($mapping['karyawan_id'], $mapping['periode_id']);
        logActivity('Submit penilaian', "Mapping: {$mappingId}", 'penilaian', $_SESSION['user_id']);
        success(['submitted'=>true], 'Penilaian berhasil dikirim');
    } catch (Exception $e) { $db->rollBack(); error('Gagal: '.$e->getMessage()); } break;
case 'my_scores':
    requireAuth(); $user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final, r.nilai_self, r.nilai_peer, r.nilai_atasan, r.nilai_bawahan FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY d.urutan"); $stmt->execute([$user['id'], $periodeId]); $scores = $stmt->fetchAll();
    $avgScore = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_final')) / count($scores), 2) : null;
    success(['scores'=>$scores,'avg_score'=>$avgScore]); break;
case 'employee_scores':
    requireRole(['manager','adminhrd']); $karyawanId = (int)input('karyawan_id', 0); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
    $stmt = $db->prepare("SELECT id, nik, nama, email, divisi, jabatan, avatar_color, manager_id FROM users WHERE id = ?"); $stmt->execute([$karyawanId]); $employee = $stmt->fetch(); if (!$employee) error('Karyawan tidak ditemukan', 404);
    $stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final, r.nilai_self, r.nilai_peer, r.nilai_atasan, r.nilai_bawahan FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY d.urutan"); $stmt->execute([$karyawanId, $periodeId]); $scores = $stmt->fetchAll();
    $stmt = $db->prepare("SELECT * FROM catatan_manager WHERE karyawan_id = ? AND periode_id = ?"); $stmt->execute([$karyawanId, $periodeId]); $notes = $stmt->fetch();
    $avgScore = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_final')) / count($scores), 2) : null;
    $coachingStatus = 'on_track'; if ($avgScore !== null) { if ($avgScore < 3.5) $coachingStatus = 'need_coaching'; elseif ($avgScore >= 4.3) $coachingStatus = 'excellent'; }
    success(['employee'=>$employee,'scores'=>$scores,'avg_score'=>$avgScore,'notes'=>$notes,'coaching_status'=>$coachingStatus]); break;
case 'save_coaching':
    requireRole(['manager','adminhrd']);
    $db = getDB();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $data = inputJSON();
    $karyawanId = (int)($data['karyawan_id'] ?? 0);
    $catatan = trim($data['catatan'] ?? '');
    $rekomendasi = trim($data['rekomendasi'] ?? '');
    $statusCoaching = $data['status_coaching'] ?? 'on_track';
    $periodeId = (int)($data['periode_id'] ?? (getActivePeriode()['id'] ?? 0));
    $managerId = $_SESSION['user_id'];
    if (!$karyawanId || !$periodeId) error('karyawan_id dan periode_id wajib diisi');
    if (!in_array($statusCoaching, ['need_coaching','on_track','excellent'])) error('status_coaching tidak valid');
    // Verify manager owns this karyawan
    if ($_SESSION['role'] === 'manager') {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND manager_id = ?"); $stmt->execute([$karyawanId, $managerId]);
        if (!$stmt->fetch()) error('Karyawan bukan bawahan Anda', 403);
    }
    $stmt = $db->prepare("INSERT INTO catatan_manager (periode_id, manager_id, karyawan_id, catatan, rekomendasi, status_coaching) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE catatan = VALUES(catatan), rekomendasi = VALUES(rekomendasi), status_coaching = VALUES(status_coaching)");
    $stmt->execute([$periodeId, $managerId, $karyawanId, $catatan, $rekomendasi, $statusCoaching]);
    logActivity('Coaching plan saved', "Karyawan: {$karyawanId}, Status: {$statusCoaching}", 'penilaian', $managerId);
    success(['saved' => true], 'Coaching plan berhasil disimpan');
    break;
case 'send_feedback':
    requireRole(['manager','adminhrd']);
    $db = getDB();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);
    $data = inputJSON();
    $karyawanId = (int)($data['karyawan_id'] ?? 0);
    $feedback = trim($data['feedback'] ?? '');
    $managerId = $_SESSION['user_id'];
    if (!$karyawanId || !$feedback) error('karyawan_id dan feedback wajib diisi');
    // Get manager name
    $stmt = $db->prepare("SELECT nama FROM users WHERE id = ?"); $stmt->execute([$managerId]);
    $managerNama = $stmt->fetchColumn() ?: 'Manager';
    // Send notification to karyawan
    $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan, tipe, link) VALUES (?, ?, ?, 'info', 'karyawan-nilai.php')")
       ->execute([$karyawanId, 'Feedback dari ' . $managerNama, $feedback, 'info']);
    logActivity('Feedback sent', "To karyawan: {$karyawanId}", 'penilaian', $managerId);
    success(['sent' => true], 'Feedback berhasil dikirim ke karyawan');
    break;
default: error('Unknown action', 400);
}
