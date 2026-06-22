<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('karyawan');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stats = getUserStats($user['id'], $periodeId);
$stmt = $db->prepare("SELECT am.id as mapping_id, am.tipe_assessor, am.status, u.id as target_id, u.nama as target_nama, u.divisi as target_divisi, u.jabatan as target_jabatan FROM assessor_mapping am JOIN users u ON am.karyawan_id = u.id WHERE am.assessor_id = ? AND am.periode_id = ? AND am.status IN ('assigned','draft') LIMIT 5");
$stmt->execute([$user['id'], $periodeId]); $pending = $stmt->fetchAll();
$stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY d.urutan");
$stmt->execute([$user['id'], $periodeId]); $scores = $stmt->fetchAll(); $scoreData = [];
foreach ($scores as $s) $scoreData[$s['kode']] = round($s['nilai_final'], 2);
$avgScore = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_final')) / count($scores), 2) : null;
$colors = ['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C'];
renderPageStart('Dashboard'); renderSidebar('karyawan', 'karyawan-dashboard', $user);
?><main class="main-content">
<div class="page-header"><h1>Selamat datang, <?= htmlspecialchars($user['nama']) ?></h1><p class="subtitle">Periode penilaian aktif &middot; Batas waktu: <?= $periode ? date('d F Y', strtotime($periode['deadline'])) : '-' ?></p></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Menunggu Dinilai</div><div class="value" data-count="<?= $stats['pending'] ?>">0</div></div><div class="sub">Harus diselesaikan</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Sudah Selesai</div><div class="value" data-count="<?= $stats['completed'] ?>">0</div></div><div class="sub">Dari <?= $stats['total'] ?> penugasan</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Nilai Saya</div><div class="value" data-count="<?= $avgScore ?? 0 ?>" data-decimals="2">0</div></div><div class="sub">Dari skala 5.00</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Status Periode</div><div class="value" style="font-size:28px;">Aktif</div></div><div class="sub"><?= $periode['nama'] ?? '-' ?></div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Penilaian Tertunda</h3><a href="karyawan-penilaian.php" class="link">Lihat semua &rarr;</a></div>
    <?php if (empty($pending)): ?><p style="color:#64748B;padding:20px 0;">Tidak ada penilaian tertunda. Semua selesai!</p>
    <?php else: foreach ($pending as $p): $initials = getInitials($p['target_nama']); $color = $colors[($p['target_id'] % count($colors))]; ?>
    <div class="eval-item"><div class="eval-item-left"><div class="avatar avatar-sm" style="background:<?= $color ?>;"><?= $initials ?></div><div><div class="eval-item-name"><?= htmlspecialchars($p['target_nama']) ?></div><div class="eval-item-dept"><?= htmlspecialchars($p['target_jabatan']) ?> &middot; <?= htmlspecialchars($p['target_divisi']) ?></div></div></div><div class="eval-item-right"><?php renderBadge($p['tipe_assessor'] === 'diri' ? 'mandiri' : $p['tipe_assessor']); ?><a href="karyawan-form.php?mapping_id=<?= $p['mapping_id'] ?>" class="btn btn-primary btn-sm">Nilai &rarr;</a></div></div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card"><div class="card-header"><h3>Nilai AKHLAK Saya</h3></div><div style="max-width:280px;margin:0 auto;"><canvas id="radar-karyawan"></canvas></div><div style="text-align:center;margin-top:16px;"><span class="badge badge-berjalan">Rata-rata <?= $avgScore ? round($avgScore, 2) : '-' ?></span></div><div style="text-align:center;margin-top:12px;"><a href="karyawan-nilai.php" class="text-link">Lihat detail nilai &rarr;</a></div></div>
</div></main>
<script>const ctx = document.getElementById('radar-karyawan').getContext('2d');
new Chart(ctx,{type:'radar',data:{labels:['Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif'],datasets:[{label:'Nilai AKHLAK',data:[<?= $scoreData['am']??0 ?>,<?= $scoreData['ko']??0 ?>,<?= $scoreData['ha']??0 ?>,<?= $scoreData['lo']??0 ?>,<?= $scoreData['ad']??0 ?>,<?= $scoreData['kol']??0 ?>],backgroundColor:'rgba(27,42,74,0.2)',borderColor:'#1B2A4A',borderWidth:2,pointBackgroundColor:'#1B2A4A',pointRadius:4}]},options:{responsive:true,scales:{r:{beginAtZero:true,max:5,ticks:{stepSize:1,font:{size:10}},pointLabels:{font:{size:11}}}},plugins:{legend:{display:false}}}});
</script>
<?php renderPageEnd(); ?>