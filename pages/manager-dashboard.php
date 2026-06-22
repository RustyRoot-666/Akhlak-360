<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('manager');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stats = getManagerStats($user['id'], $periodeId);
$stmt = $db->prepare("SELECT u.*, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = ?) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ? AND status = 'submitted') as completed, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ?) as total FROM users u WHERE u.manager_id = ? AND u.status = 'aktif' ORDER BY u.nama");
$stmt->execute([$periodeId, $periodeId, $periodeId, $user['id']]); $team = $stmt->fetchAll();
$stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, AVG(r.nilai_final) as avg_score FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id JOIN users u ON r.karyawan_id = u.id WHERE u.manager_id = ? AND r.periode_id = ? GROUP BY d.id ORDER BY d.urutan");
$stmt->execute([$user['id'], $periodeId]); $teamScores = $stmt->fetchAll();
$teamScoreData = []; foreach ($teamScores as $s) $teamScoreData[$s['kode']] = round($s['avg_score'], 2);
$colors = ['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C','#AD1457','#FF8F00'];
renderPageStart('Dashboard Manajer'); renderSidebar('manager', 'manager-dashboard', $user);
?><main class="main-content">
<div class="page-header"><h1>Dashboard Manajer</h1><p class="subtitle"><?= htmlspecialchars($user['nama']) ?> &middot; <?= htmlspecialchars($user['divisi']) ?> &middot; <?= $periode['nama'] ?? '-' ?></p></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Tim Saya</div><div class="value" data-count="<?= $stats['total_team'] ?>">0</div></div><div class="sub">Dalam grup Anda</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Sudah Dinilai</div><div class="value" data-count="<?= $stats['completed'] ?>">0</div></div><div class="sub">Selesai dari tim</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Menunggu Nilai</div><div class="value" data-count="<?= $stats['pending'] ?>">0</div></div><div class="sub">Perlu diselesaikan</div></div>
  <div class="stat-card"><div class="accent" style="background:#1B2A4A;"></div><div><div class="label">Nilai Rata-rata</div><div class="value" data-count="<?= $stats['avg_score'] ?? 0 ?>" data-decimals="2">0</div></div><div class="sub">Tim <?= htmlspecialchars($user['divisi']) ?></div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Progress Tim Saya</h3></div>
    <table class="data-table"><thead><tr><th>Nama</th><th>Progress</th><th>Nilai</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($team as $i => $member): $pct = ($member['total'] ?? 0) > 0 ? round((($member['completed'] ?? 0) / $member['total']) * 100) : 0; $initials = getInitials($member['nama']); $color = $colors[$i % count($colors)]; ?>
    <tr><td style="display:flex;align-items:center;gap:8px;"><div class="avatar avatar-sm" style="background:<?= $color ?>;"><?= $initials ?></div><?= htmlspecialchars($member['nama']) ?></td><td><div class="progress-track"><div class="progress-fill" data-progress="<?= $pct ?>" style="width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#2E7D32' : ($pct >= 50 ? '#1565C0' : '#E65100') ?>;"></div></div></td><td><?= $member['avg_score'] ? round($member['avg_score'], 2) : '&mdash;' ?></td><td><?php renderBadge($pct >= 100 ? 'selesai' : ($pct > 0 ? 'berjalan' : 'tertunda')); ?></td></tr>
    <?php endforeach; ?></tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Profil Tim</h3></div><div style="max-width:280px;margin:0 auto;"><canvas id="radar-team"></canvas></div>
    <?php foreach ($teamScores as $s): $pct = $s['avg_score'] ? round(($s['avg_score'] / 5) * 100) : 0; ?>
    <div class="score-bar-row"><div class="score-bar-dot" style="background:<?= $s['warna'] ?>;"></div><div class="score-bar-label"><?= $s['nama'] ?></div><div class="score-bar-track"><div class="score-bar-fill" style="width:<?= $pct ?>%;background:<?= $s['warna'] ?>;"></div></div><div class="score-bar-value"><?= $s['avg_score'] ? round($s['avg_score'], 2) : '-' ?></div></div>
    <?php endforeach; ?>
  </div>
</div></main>
<script>
new Chart(document.getElementById('radar-team').getContext('2d'),{type:'radar',data:{labels:['Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif'],datasets:[{label:'Rata-rata Tim',data:[<?= $teamScoreData['am']??0 ?>,<?= $teamScoreData['ko']??0 ?>,<?= $teamScoreData['ha']??0 ?>,<?= $teamScoreData['lo']??0 ?>,<?= $teamScoreData['ad']??0 ?>,<?= $teamScoreData['kol']??0 ?>],backgroundColor:'rgba(27,42,74,0.2)',borderColor:'#1B2A4A',borderWidth:2,pointBackgroundColor:'#1B2A4A',pointRadius:4}]},options:{responsive:true,scales:{r:{beginAtZero:true,max:5,ticks:{stepSize:1,font:{size:10}},pointLabels:{font:{size:11}}}},plugins:{legend:{display:false}}}});
</script>
<?php renderPageEnd(); ?>