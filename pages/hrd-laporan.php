<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();

// Real division summary
$stmt = $db->prepare("SELECT u.divisi, COUNT(DISTINCT u.id) as jumlah_karyawan, AVG(r.nilai_final) as avg_score FROM users u LEFT JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi ORDER BY avg_score DESC");
$stmt->execute([$periodeId]); $divisions = $stmt->fetchAll();

// Real company-wide stats
$avgCompany = (float)$db->query("SELECT AVG(nilai_final) FROM rekap_nilai WHERE periode_id = {$periodeId}")->fetchColumn();
$avgCompany = $avgCompany ? round($avgCompany, 2) : 0;

// Top division (highest avg)
$topDiv = !empty($divisions) && $divisions[0]['avg_score'] ? $divisions[0] : null;

// Focus value (weakest dimension across company)
$stmt = $db->prepare("SELECT d.kode, d.nama, AVG(r.nilai_final) as avg_score FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.periode_id = ? GROUP BY d.id ORDER BY avg_score ASC LIMIT 1");
$stmt->execute([$periodeId]); $focusValue = $stmt->fetch();

renderPageStart('Laporan'); renderSidebar('adminhrd', 'hrd-laporan', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Laporan Assessment AKHLAK 360</h1><p class="subtitle">Konsolidasi hasil akhir, ranking value, dan paket laporan</p></div><div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="generatePack()">Generate Pack (ZIP)</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1B2A4A;"></div><div><div class="label">Avg Company</div><div class="value" data-count="<?= $avgCompany ?>" data-decimals="2">0</div></div><div class="sub">Skala 5.00</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Top Division</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($topDiv['divisi'] ?? '-') ?></div></div><div class="sub"><?= $topDiv && $topDiv['avg_score'] ? round($topDiv['avg_score'], 2) . ' rata-rata' : 'Belum ada data' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Focus Value</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($focusValue['nama'] ?? '-') ?></div></div><div class="sub"><?= $focusValue && $focusValue['avg_score'] ? round($focusValue['avg_score'], 2) . ' rata-rata' : 'Belum ada data' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Ready Export</div><div class="value" data-count="4">0</div></div><div class="sub">Format laporan</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Ringkasan Divisi (<?= count($divisions) ?> divisi)</h3></div>
    <table class="data-table"><thead><tr><th>Divisi</th><th>Karyawan</th><th>Avg Score</th><th>Status</th></tr></thead><tbody>
    <?php if (empty($divisions)): ?>
      <tr><td colspan="4" style="text-align:center;color:#64748B;padding:20px;">Belum ada data divisi.</td></tr>
    <?php else: foreach ($divisions as $d): ?>
      <tr><td style="font-weight:500;"><?= htmlspecialchars($d['divisi']) ?></td><td><?= (int)$d['jumlah_karyawan'] ?></td><td><?= $d['avg_score'] ? round($d['avg_score'], 2) : '&mdash;' ?></td><td><?php renderBadge($d['avg_score'] && $d['avg_score'] >= 4.0 ? 'selesai' : ($d['avg_score'] ? 'berjalan' : 'tertunda')); ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Generate Laporan</h3></div>
    <div class="action-panel">
      <a href="hrd-laporan-executive.php" class="action-btn" style="display:block;text-decoration:none;color:inherit;"><div class="title">Executive Summary</div><div class="sub">PDF manajemen (window.print)</div></a>
      <a href="hrd-laporan-detail.php" class="action-btn" style="display:block;text-decoration:none;color:inherit;"><div class="title">Detail Karyawan</div><div class="sub">XLS per individu</div></a>
      <a href="hrd-laporan-matrix.php" class="action-btn" style="display:block;text-decoration:none;color:inherit;"><div class="title">Matrix AKHLAK</div><div class="sub">CSV analytics</div></a>
      <a href="hrd-laporan-audit.php" class="action-btn" style="display:block;text-decoration:none;color:inherit;"><div class="title">Audit Trail</div><div class="sub">ZIP dokumen</div></a>
    </div>
  </div>
</div>
<div class="insight-box"><h3>Insight Utama</h3><p>
<?php if (!$avgCompany): ?>
  Belum ada data nilai untuk periode aktif. Insight akan muncul setelah penilaian di-submit oleh assessor.
<?php else: ?>
  Rata-rata skor perusahaan saat ini <strong><?= $avgCompany ?></strong> dari 5.00.
  <?php if ($topDiv): ?>Divisi terkuat: <strong><?= htmlspecialchars($topDiv['divisi']) ?></strong> (<?= round($topDiv['avg_score'], 2) ?>).<?php endif; ?>
  <?php if ($focusValue): ?>Area pengembangan utama: <strong><?= htmlspecialchars($focusValue['nama']) ?></strong> (<?= round($focusValue['avg_score'], 2) ?>) - direkomendasikan program pengembangan lintas divisi pada periode berikutnya.<?php endif; ?>
<?php endif; ?>
</p></div></main>
<script>
function generatePack() {
  if (!confirm('Generate paket laporan lengkap?\n\nPaket akan berisi:\n- Ringkasan divisi (CSV)\n- Detail nilai karyawan (CSV)\n- Matrix AKHLAK (CSV)\n- Audit log (CSV)\n\nFile akan diunduh sebagai ZIP.')) return;
  // Generate 4 CSV files client-side and bundle into ZIP via XLSX
  showLoading();
  Promise.all([
    fetch('../api/laporan.php?action=divisi').then(r => r.json()),
    fetch('../api/laporan.php?action=detail_karyawan').then(r => r.json()),
    fetch('../api/laporan.php?action=matrix').then(r => r.json()),
    fetch('../api/adminit.php?action=audit_log_csv').then(r => r.text())
  ]).then(([divRes, detRes, matRes, auditCsv]) => {
    hideLoading();
    const wb = XLSX.utils.book_new();
    // Sheet 1: Divisi
    if (divRes.success) {
      const divRows = [['Divisi', 'Jumlah Karyawan', 'Avg Score']];
      divRes.data.divisions.forEach(d => divRows.push([d.divisi, d.jumlah_karyawan, d.avg_score ? round2(d.avg_score) : '-']));
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(divRows), 'Ringkasan Divisi');
    }
    // Sheet 2: Detail Karyawan
    if (detRes.success) {
      const detRows = [['NIK','Nama','Divisi','Jabatan','Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif']];
      detRes.data.employees.forEach(e => detRows.push([e.nik, e.nama, e.divisi, e.jabatan, e.scores.am||'-', e.scores.ko||'-', e.scores.ha||'-', e.scores.lo||'-', e.scores.ad||'-', e.scores.kol||'-']));
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(detRows), 'Detail Karyawan');
    }
    // Sheet 3: Matrix
    if (matRes.success) {
      const dims = matRes.data.dimensions;
      const matRows = [['Divisi', ...dims]];
      Object.keys(matRes.data.matrix).forEach(div => {
        const row = [div];
        dims.forEach(d => row.push(matRes.data.matrix[div][d] || '-'));
        matRows.push(row);
      });
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(matRows), 'Matrix AKHLAK');
    }
    // Sheet 4: Audit log (parse CSV)
    try {
      const auditRows = auditCsv.split('\n').filter(l => l.trim()).map(l => l.split(',').map(c => c.replace(/^"|"$/g, '')));
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(auditRows), 'Audit Log');
    } catch (e) {}
    XLSX.writeFile(wb, 'laporan-pack-<?= $periode['nama'] ?? 'aktif' ?>-<?= date('Ymd') ?>.xlsx');
    showSuccess('Paket laporan berhasil diunduh');
  }).catch(e => { hideLoading(); showError('Gagal generate pack: ' + e.message); });
}
function round2(v) { return Math.round(v * 100) / 100; }
</script>
<?php renderPageEnd(); ?>
