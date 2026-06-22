<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stmt = $db->prepare("SELECT u.divisi, d.kode, d.nama as dimensi_nama, AVG(r.nilai_final) as avg_nilai FROM users u JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi, d.id ORDER BY u.divisi, d.urutan");
$stmt->execute([$periodeId]); $rows = $stmt->fetchAll();
$matrix = []; $dimensions = []; foreach ($rows as $r) { $div = $r['divisi']; $dim = $r['dimensi_nama']; if (!isset($matrix[$div])) $matrix[$div] = []; $matrix[$div][$dim] = round($r['avg_nilai'], 2); if (!in_array($dim, $dimensions)) $dimensions[] = $dim; }
renderPageStart('Matrix AKHLAK'); renderSidebar('adminhrd', 'hrd-laporan', $user);
// Compute highest/lowest dimension from matrix
$dimAverages = [];
foreach ($matrix as $div => $scores) {
    foreach ($scores as $dim => $val) {
        if (!isset($dimAverages[$dim])) $dimAverages[$dim] = [];
        $dimAverages[$dim][] = $val;
    }
}
foreach ($dimAverages as $dim => $vals) $dimAverages[$dim] = round(array_sum($vals) / count($vals), 2);
$topDim = !empty($dimAverages) ? array_keys($dimAverages, max($dimAverages))[0] : null;
$lowDim = !empty($dimAverages) ? array_keys($dimAverages, min($dimAverages))[0] : null;
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Matrix AKHLAK — Analytics (CSV)</h1><p class="subtitle">Matriks skor 6 nilai AKHLAK lintas seluruh divisi untuk kebutuhan analitik</p></div><div class="page-header-actions"><a href="hrd-laporan.php" class="btn btn-secondary btn-sm">&larr; Kembali</a><button class="btn btn-primary btn-sm" onclick="exportCSV()">Download CSV</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Total Sel Data</div><div class="value" data-count="<?= count($matrix) * 6 ?>">0</div></div><div class="sub"><?= count($matrix) ?> divisi &times; 6 nilai AKHLAK</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Nilai Tertinggi</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($topDim ?? '-') ?></div></div><div class="sub">Rata-rata <?= $topDim ? $dimAverages[$topDim] : '-' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Nilai Terendah</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($lowDim ?? '-') ?></div></div><div class="sub">Rata-rata <?= $lowDim ? $dimAverages[$lowDim] : '-' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Format File</div><div class="value" style="font-size:22px;">CSV</div></div><div class="sub">Siap untuk tools analytics</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Pratinjau Matrix Skor AKHLAK</h3></div>
    <div class="tabs"><button class="tab active">6 Nilai</button><button class="tab">Komposit</button><button class="tab">Tren</button></div>
    <table class="data-table" id="matrixTable"><thead><tr><th>Divisi</th><?php foreach ($dimensions as $d): ?><th><?= $d ?></th><?php endforeach; ?><th>Komposit</th></tr></thead><tbody>
    <?php foreach ($matrix as $div => $scores): ?><tr><td style="font-weight:500;"><?= $div ?></td><?php foreach ($dimensions as $d): ?><td><?= $scores[$d] ?? '&mdash;' ?></td><?php endforeach; ?><td><?= !empty($scores) ? round(array_sum($scores) / count($scores), 2) : '-' ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Opsi Export CSV</h3></div>
    <div class="action-panel">
      <div class="action-btn"><div class="title">Sertakan 6 Nilai AKHLAK</div><div class="sub">Amanah s.d Kolaboratif</div></div>
      <div class="action-btn"><div class="title">Sertakan Skor Komposit</div><div class="sub">Rata-rata tertimbang nilai</div></div>
      <div class="action-btn"><div class="title">Sertakan Data Historis</div><div class="sub">vs Semester II 2024</div></div>
      <div class="action-btn"><div class="title">Format Delimiter</div><div class="sub">Pisahkan kolom dengan koma (,)</div></div>
    </div>
  </div>
</div>
<div class="insight-box"><h3>Ringkasan Konten Laporan</h3><p>Matrix AKHLAK menyajikan rata-rata skor 6 nilai (Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif) untuk setiap divisi dalam format tabular CSV. File ini dirancang untuk diolah lebih lanjut menggunakan tools analytics atau BI dashboard internal.</p></div></main>
<script>
function exportCSV(){const rows=[];const headers=['Divisi',<?php foreach ($dimensions as $d): ?>'<?= $d ?>',<?php endforeach; ?>'Komposit']; rows.push(headers);<?php foreach ($matrix as $div => $scores): ?>rows.push(['<?= $div ?>',<?php foreach ($dimensions as $d): ?>'<?= $scores[$d] ?? '' ?>',<?php endforeach; ?>'<?= !empty($scores) ? round(array_sum($scores) / count($scores), 2) : '' ?>']);<?php endforeach; ?>let csv=rows.map(r=>r.join(',')).join('\n');const blob=new Blob([csv],{type:'text/csv'});const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='matrix-akhlak.csv';a.click();}
</script>
<?php renderPageEnd(); ?>