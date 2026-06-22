<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('manager');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();

// Team dimension averages (real)
$stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, AVG(r.nilai_final) as avg_score FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id JOIN users u ON r.karyawan_id = u.id WHERE u.manager_id = ? AND r.periode_id = ? GROUP BY d.id ORDER BY d.urutan");
$stmt->execute([$user['id'], $periodeId]); $teamScores = $stmt->fetchAll();
$teamScoreData = []; foreach ($teamScores as $s) $teamScoreData[$s['kode']] = round($s['avg_score'], 2);

// Real coaching priority list: each team member with avg score, weakest dimension, recommendation
$stmt = $db->prepare("SELECT u.id, u.nama, u.divisi, u.jabatan, AVG(r.nilai_final) as avg_score FROM users u LEFT JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? WHERE u.manager_id = ? AND u.status = 'aktif' GROUP BY u.id HAVING avg_score IS NOT NULL ORDER BY avg_score ASC");
$stmt->execute([$periodeId, $user['id']]); $teamMembers = $stmt->fetchAll();

// For each member, find their weakest dimension
$coachingList = [];
foreach ($teamMembers as $m) {
    $stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY r.nilai_final ASC LIMIT 1");
    $stmt->execute([$m['id'], $periodeId]); $weakest = $stmt->fetch();
    $avg = round((float)$m['avg_score'], 2);

    // Determine coaching status based on avg score
    if ($avg < 3.5) { $status = 'need_coaching'; $priority = 'High'; }
    elseif ($avg < 4.0) { $status = 'on_track'; $priority = 'Medium'; }
    else { $status = 'excellent'; $priority = 'Low'; }

    // Recommendation rules
    $recommendations = [
        'am' => 'Review komitmen target dan konsistensi kerja',
        'ko' => 'Upskill technical dan pengembangan kompetensi',
        'ha' => 'Tim building dan conflict resolution training',
        'lo' => 'Sesi engagement dan alignment nilai perusahaan',
        'ad' => 'Mentoring perubahan proses dan change management',
        'kol' => 'Cross-functional project untuk kolaborasi',
    ];
    $recKey = $weakest['kode'] ?? 'ko';
    $recommendation = $recommendations[$recKey] ?? 'Pengembangan umum';

    $coachingList[] = [
        'nama' => $m['nama'],
        'divisi' => $m['divisi'],
        'weakness' => $weakest['nama'] ?? 'Kompeten',
        'weakness_kode' => $weakest['kode'] ?? 'ko',
        'weakness_warna' => $weakest['warna'] ?? '#1B2A4A',
        'avg_score' => $avg,
        'status' => $status,
        'priority' => $priority,
        'recommendation' => $recommendation,
    ];
}

// Calculate real stats
$avgTeamScore = $teamScoreData ? round(array_sum($teamScoreData) / count($teamScoreData), 2) : 0;
$topDim = !empty($teamScores) ? array_reduce($teamScores, fn($a, $b) => (!$a || $b['avg_score'] > $a['avg_score']) ? $b : $a) : null;
$focusDim = !empty($teamScores) ? array_reduce($teamScores, fn($a, $b) => (!$a || $b['avg_score'] < $a['avg_score']) ? $b : $a) : null;
$atRiskCount = count(array_filter($coachingList, fn($c) => $c['status'] === 'need_coaching'));

renderPageStart('Dashboard Performa Tim'); renderSidebar('manager', 'manager-performa', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Dashboard Performa Tim</h1><p class="subtitle">Analitik nilai AKHLAK, distribusi skor, dan gap kompetensi tim</p></div><div class="page-header-actions"><button class="btn btn-secondary btn-sm" onclick="window.print()">Export PDF</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Avg AKHLAK</div><div class="value" data-count="<?= $avgTeamScore ?>" data-decimals="2">0</div></div><div class="sub">Rata-rata tim periode aktif</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Top Value</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($topDim['nama'] ?? '-') ?></div></div><div class="sub"><?= $topDim ? round($topDim['avg_score'], 2) . ' rata-rata' : 'Belum ada data' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Focus Area</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($focusDim['nama'] ?? '-') ?></div></div><div class="sub"><?= $focusDim ? round($focusDim['avg_score'], 2) . ' rata-rata' : 'Belum ada data' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">At Risk</div><div class="value" data-count="<?= $atRiskCount ?>">0</div></div><div class="sub">Butuh coaching</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Distribusi Nilai Tim</h3></div>
    <?php if (empty($teamScores)): ?>
      <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada data nilai untuk tim pada periode ini.</p>
    <?php else: foreach ($teamScores as $s): $pct = $s['avg_score'] ? round(($s['avg_score'] / 5) * 100) : 0; ?>
    <div class="score-bar-row"><div class="score-bar-label" style="min-width:80px;"><?= htmlspecialchars($s['nama']) ?></div><div class="score-bar-track"><div class="score-bar-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($s['warna']) ?>;"></div></div><div class="score-bar-value"><?= $s['avg_score'] ? round($s['avg_score'], 2) : '-' ?></div></div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card"><div class="card-header"><h3>Radar AKHLAK</h3></div><div style="max-width:280px;margin:0 auto;"><canvas id="radar-performa"></canvas></div></div>
</div>
<div class="card" style="margin-bottom:28px;"><div class="card-header"><h3>Coaching Priority (<?= count($coachingList) ?> anggota)</h3><span style="font-size:12px;color:#64748B;">Diurutkan dari skor terendah</span></div>
  <?php if (empty($coachingList)): ?>
    <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada data nilai tim. Tunggu hingga penilaian selesai di-submit.</p>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 2fr;gap:16px;padding:12px 0;font-weight:600;font-size:12px;color:#64748B;text-transform:uppercase;border-bottom:1px solid #F0F0F0;"><div>Nama</div><div>Area Fokus</div><div>Nilai</div><div>Priority</div><div>Rekomendasi</div></div>
  <?php foreach ($coachingList as $c): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 2fr;gap:16px;padding:12px 0;border-bottom:1px solid #F0F0F0;align-items:center;">
      <div style="font-weight:500;"><?= htmlspecialchars($c['nama']) ?><br><span style="font-size:11px;color:#64748B;font-weight:400;"><?= htmlspecialchars($c['divisi']) ?></span></div>
      <div><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= htmlspecialchars($c['weakness_warna']) ?>;margin-right:6px;"></span><?= htmlspecialchars($c['weakness']) ?></div>
      <div><strong style="color:<?= $c['avg_score'] < 3.5 ? '#C62828' : ($c['avg_score'] < 4.0 ? '#E65100' : '#2E7D32') ?>;"><?= $c['avg_score'] ?></strong></div>
      <div><?php renderBadge($c['status']); ?></div>
      <div style="font-size:13px;color:#64748B;"><?= htmlspecialchars($c['recommendation']) ?></div>
    </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div></main>
<script>
new Chart(document.getElementById('radar-performa').getContext('2d'),{type:'radar',data:{labels:['Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif'],datasets:[{label:'Rata-rata Tim',data:[<?= $teamScoreData['am']??0 ?>,<?= $teamScoreData['ko']??0 ?>,<?= $teamScoreData['ha']??0 ?>,<?= $teamScoreData['lo']??0 ?>,<?= $teamScoreData['ad']??0 ?>,<?= $teamScoreData['kol']??0 ?>],backgroundColor:'rgba(27,42,74,0.2)',borderColor:'#1B2A4A',borderWidth:2,pointBackgroundColor:'#1B2A4A',pointRadius:4}]},options:{responsive:true,scales:{r:{beginAtZero:true,max:5,ticks:{stepSize:1,font:{size:10}},pointLabels:{font:{size:11}}}},plugins:{legend:{display:false}}}});
</script>
<?php renderPageEnd(); ?>
