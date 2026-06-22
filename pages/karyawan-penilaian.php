<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('karyawan');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stmt = $db->prepare("SELECT am.id as mapping_id, am.tipe_assessor, am.status, u.id as target_id, u.nama as target_nama, u.divisi as target_divisi, u.jabatan as target_jabatan FROM assessor_mapping am JOIN users u ON am.karyawan_id = u.id WHERE am.assessor_id = ? AND am.periode_id = ? ORDER BY FIELD(am.status,'draft','assigned','submitted'), u.nama");
$stmt->execute([$user['id'], $periodeId]); $assessments = $stmt->fetchAll();
$colors = ['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C'];
$total = count($assessments); $completed = 0; foreach ($assessments as $a) if ($a['status'] === 'submitted') $completed++;
renderPageStart('Penilaian Saya'); renderSidebar('karyawan', 'karyawan-penilaian', $user);
?><main class="main-content">
<div class="page-header"><h1>Penilaian Saya</h1><p class="subtitle"><?= $total ?> penugasan &middot; <?= $completed ?> selesai &middot; <?= $total - $completed ?> tertunda</p></div>
<div class="info-bar"><div class="info-bar-title">Bobot penilaian 360°</div><div class="info-bar-sub">Atasan 40% &middot; Bawahan 30% &middot; Rekan Sejawat 20% &middot; Mandiri 10%</div></div>
<?php if (empty($assessments)): ?><div class="card"><p style="color:#64748B;padding:20px;">Belum ada penugasan penilaian untuk periode ini.</p></div>
<?php else: foreach ($assessments as $a): $initials = getInitials($a['target_nama']); $color = $colors[($a['target_id'] % count($colors))]; $isDone = $a['status'] === 'submitted'; ?>
<div class="eval-item"><div class="eval-item-left"><div class="avatar avatar-sm" style="background:<?= $color ?>;"><?= $initials ?></div><div><div class="eval-item-name"><?= htmlspecialchars($a['target_nama']) ?></div><div class="eval-item-dept"><?= htmlspecialchars($a['target_jabatan']) ?> &middot; Batas: <?= $periode ? date('d M Y', strtotime($periode['deadline'])) : '-' ?></div></div></div><div class="eval-item-right"><?php renderBadge($a['tipe_assessor'] === 'diri' ? 'mandiri' : $a['tipe_assessor']); ?><?php if ($isDone): ?><span style="color:#2E7D32;font-weight:600;font-size:13px;">✓ Selesai</span><?php else: ?><a href="karyawan-form.php?mapping_id=<?= $a['mapping_id'] ?>" class="btn btn-primary btn-sm">Isi Penilaian &rarr;</a><?php endif; ?></div></div>
<?php endforeach; endif; ?></main><?php renderPageEnd(); ?>