<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('karyawan');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final, r.nilai_self, r.nilai_peer, r.nilai_atasan, r.nilai_bawahan FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY d.urutan");
$stmt->execute([$user['id'], $periodeId]); $scores = $stmt->fetchAll(); $scoreData = [];
foreach ($scores as $s) $scoreData[$s['kode']] = round($s['nilai_final'], 2);
$avgScore = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_final')) / count($scores), 2) : null;
$selfAvg = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_self')) / count($scores), 2) : null;
$peerAvg = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_peer')) / count($scores), 2) : null;
$atasanAvg = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_atasan')) / count($scores), 2) : null;
$bawahanAvg = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_bawahan')) / count($scores), 2) : null;
renderPageStart('Nilai AKHLAK'); renderSidebar('karyawan', 'karyawan-nilai', $user);
?><main class="main-content">
<div class="page-header"><h1>Nilai AKHLAK Saya</h1><p class="subtitle"><?= htmlspecialchars($user['nama']) ?> &middot; Periode <?= $periode['nama'] ?? '-' ?></p></div>
<div class="score-summary">
  <div class="score-sum-seg"><div class="top-bar" style="background:#E65100;"></div><div class="label">Nilai Akhir</div><div class="val" data-count="<?= $avgScore ?? 0 ?>" data-decimals="2">0</div><div class="sub">dari 5.00</div></div>
  <div class="score-sum-seg"><div class="top-bar" style="background:#1565C0;"></div><div class="label">Atasan (40%)</div><div class="val" data-count="<?= $atasanAvg ?? 0 ?>" data-decimals="1">0</div><div class="sub">Dari atasan</div></div>
  <div class="score-sum-seg"><div class="top-bar" style="background:#2E7D32;"></div><div class="label">Bawahan (30%)</div><div class="val" data-count="<?= $bawahanAvg ?? 0 ?>" data-decimals="1">0</div><div class="sub">Dari bawahan</div></div>
  <div class="score-sum-seg"><div class="top-bar" style="background:#E65100;"></div><div class="label">Rekan (20%)</div><div class="val" data-count="<?= $peerAvg ?? 0 ?>" data-decimals="1">0</div><div class="sub">Dari rekan</div></div>
  <div class="score-sum-seg"><div class="top-bar" style="background:#4527A0;"></div><div class="label">Mandiri (10%)</div><div class="val" data-count="<?= $selfAvg ?? 0 ?>" data-decimals="1">0</div><div class="sub">Penilaian diri</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Profil AKHLAK</h3></div><div style="max-width:320px;margin:0 auto;"><canvas id="radar-nilai"></canvas></div></div>
  <div class="card"><div class="card-header"><h3>Nilai per Dimensi</h3></div>
    <?php foreach ($scores as $s): $pct = $s['nilai_final'] ? round(($s['nilai_final'] / 5) * 100) : 0; ?>
    <div class="score-bar-row"><div class="score-bar-dot" style="background:<?= $s['warna'] ?>;"></div><div class="score-bar-label"><?= $s['nama'] ?></div><div class="score-bar-track"><div class="score-bar-fill" style="width:<?= $pct ?>%;background:<?= $s['warna'] ?>;"></div></div><div class="score-bar-value"><?= $s['nilai_final'] ? round($s['nilai_final'], 2) : '-' ?></div></div>
    <?php endforeach; ?>
    <div class="recommend-box"><div class="title">💡 Rekomendasi IDP</div><p>Fokus pengembangan: <?= !empty($scores) ? $scores[array_search(min(array_column($scores, 'nilai_final')), array_column($scores, 'nilai_final'))]['nama'] : '-' ?> (<?= !empty($scores) ? round(min(array_column($scores, 'nilai_final')), 2) : '-' ?>) perlu ditingkatkan melalui pelatihan.</p></div>
  </div>
</div></main>
<script>
new Chart(document.getElementById('radar-nilai').getContext('2d'),{type:'radar',data:{labels:['Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif'],datasets:[{label:'Nilai AKHLAK',data:[<?= $scoreData['am']??0 ?>,<?= $scoreData['ko']??0 ?>,<?= $scoreData['ha']??0 ?>,<?= $scoreData['lo']??0 ?>,<?= $scoreData['ad']??0 ?>,<?= $scoreData['kol']??0 ?>],backgroundColor:'rgba(27,42,74,0.2)',borderColor:'#1B2A4A',borderWidth:2,pointBackgroundColor:'#1B2A4A',pointRadius:4}]},options:{responsive:true,scales:{r:{beginAtZero:true,max:5,ticks:{stepSize:1,font:{size:10}},pointLabels:{font:{size:11}}}},plugins:{legend:{display:false}}}});
</script>
<?php renderPageEnd(); ?>