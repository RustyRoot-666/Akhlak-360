<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stmt = $db->query("SELECT u.id, u.nama, u.divisi, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = {$periodeId}) as avg_score FROM users u WHERE u.role = 'karyawan' AND u.status = 'aktif' ORDER BY u.nama LIMIT 20");
$employees = $stmt->fetchAll();
renderPageStart('Detail Karyawan'); renderSidebar('adminhrd', 'hrd-laporan', $user);
?><main class="main-content">
<div class="page-header page-header-row"><div><h1>Detail Karyawan — Data Individu (XLS)</h1><p class="subtitle">Rincian skor AKHLAK per karyawan, siap diekspor sebagai spreadsheet</p></div><div class="page-header-actions"><a href="hrd-laporan.php" class="btn btn-secondary btn-sm">&larr; Kembali</a><button class="btn btn-primary btn-sm" onclick="exportXLS()">Download XLS</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Total Karyawan</div><div class="value" data-count="<?= count($employees) ?>">0</div></div><div class="sub">Seluruh divisi</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Kolom Data</div><div class="value" data-count="12">0</div></div><div class="sub">6 nilai AKHLAK + skor komposit</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Format File</div><div class="value" style="font-size:22px;">XLSX</div></div><div class="sub">Kompatibel Excel & Sheets</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Terakhir Diperbarui</div><div class="value" style="font-size:18px;"><?= date('d M Y') ?></div></div><div class="sub">Data final periode aktif</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Pratinjau Data Karyawan</h3></div>
    <div class="tabs"><button class="tab active">Semua Divisi</button><button class="tab">Lengkap</button><button class="tab">Menunggu</button></div>
    <table class="data-table" id="detailTable"><thead><tr><th>Nama Karyawan</th><th>Divisi</th><th>Total Score</th><th>Status Data</th></tr></thead><tbody>
    <?php foreach ($employees as $e): ?><tr><td style="font-weight:500;"><?= htmlspecialchars($e['nama']) ?></td><td><?= htmlspecialchars($e['divisi']) ?></td><td><?= $e['avg_score'] ? round($e['avg_score'], 2) : '&mdash;' ?></td><td><?php renderBadge($e['avg_score'] ? 'selesai' : 'berjalan'); ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Opsi Export XLS</h3></div>
    <div class="action-panel">
      <div class="action-btn"><div class="title">Sertakan Skor per Nilai</div><div class="sub">Amanah, Kompeten, Harmonis, dst</div></div>
      <div class="action-btn"><div class="title">Sertakan Komentar Assessor</div><div class="sub">Catatan kualitatif tiap penilai</div></div>
      <div class="action-btn"><div class="title">Sertakan Self Assessment</div><div class="sub">Skor penilaian diri karyawan</div></div>
      <div class="action-btn"><div class="title">Pisahkan per Divisi</div><div class="sub">1 sheet untuk setiap divisi</div></div>
    </div>
  </div>
</div>
<div class="insight-box"><h3>Ringkasan Konten Laporan</h3><p>Detail Karyawan berisi rincian skor AKHLAK setiap individu pada <?= $periode['nama'] ?? 'periode aktif' ?>, mencakup skor dari atasan, rekan kerja, dan self assessment. Cocok digunakan HRD untuk analisis individu, pemetaan talenta, dan dasar pengembangan karir.</p></div></main>
<script>function exportXLS(){const wb=XLSX.utils.book_new();const ws=XLSX.utils.table_to_sheet(document.getElementById('detailTable'));XLSX.utils.book_append_sheet(wb,ws,"Detail Karyawan");XLSX.writeFile(wb,"detail-karyawan-<?= $periode['nama'] ?? 'aktif' ?>.xlsx");}</script>
<?php renderPageEnd(); ?>