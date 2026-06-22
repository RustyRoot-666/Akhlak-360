<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stats = getHRDStats($periodeId);
$stmt = $db->prepare("SELECT u.divisi, COUNT(DISTINCT u.id) as total_karyawan, AVG(r.nilai_final) as avg_score FROM users u LEFT JOIN rekap_nilai r ON u.id = r.karyawan_id AND r.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi ORDER BY u.divisi");
$stmt->execute([$periodeId]); $divisions = $stmt->fetchAll();
$stmt = $db->query("SELECT u.*, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = {$periodeId}) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = {$periodeId} AND status = 'submitted') as completed, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = {$periodeId}) as total FROM users u WHERE u.role = 'karyawan' AND u.status = 'aktif' ORDER BY u.nama LIMIT 6");
$employees = $stmt->fetchAll();
$colors = ['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C'];
renderPageStart('Dashboard Admin HRD'); renderSidebar('adminhrd', 'hrd-dashboard', $user);
?><main class="main-content">
<div class="page-header page-header-row"><div><h1>Dashboard Admin HRD</h1><p class="subtitle">Monitoring penilaian &middot; <?= $periode['nama'] ?? '-' ?></p></div><div class="page-header-actions"><a href="hrd-laporan.php" class="btn btn-secondary btn-sm">Lihat Laporan</a><button class="btn btn-primary btn-sm" onclick="exportReport()">Export XLS</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Total Karyawan</div><div class="value" data-count="<?= $stats['total_employees'] ?>">0</div></div><div class="sub">Aktif di sistem</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Pengisian Selesai</div><div class="value" data-count="<?= $stats['completion_rate'] ?>">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub">Rate completion</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Belum Mulai</div><div class="value" data-count="<?= $stats['not_started'] ?>">0</div></div><div class="sub">Perlu tindakan</div></div>
  <div class="stat-card"><div class="accent" style="background:#1B2A4A;"></div><div><div class="label">Rata-rata Nilai</div><div class="value" data-count="<?= $stats['avg_score'] ?? 0 ?>" data-decimals="2">0</div></div><div class="sub">Dari skala 5.00</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Progress Pengisian per Divisi</h3></div>
    <?php if (empty($divisions)): ?>
      <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada data divisi.</p>
    <?php else: foreach ($divisions as $d):
      // Calculate real progress from assessor_mapping
      $stmtP = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) as completed FROM assessor_mapping WHERE karyawan_id IN (SELECT id FROM users WHERE divisi = ? AND role = 'karyawan' AND status = 'aktif') AND periode_id = ?");
      $stmtP->execute([$d['divisi'], $periodeId]); $p = $stmtP->fetch();
      $pct = ($p['total'] ?? 0) > 0 ? round((($p['completed'] ?? 0) / $p['total']) * 100) : 0;
    ?>
    <div class="div-progress-row"><div class="div-progress-label"><?= htmlspecialchars($d['divisi']) ?> <span style="font-size:11px;color:#64748B;">(<?= (int)$d['total_karyawan'] ?> karyawan)</span></div><div class="div-progress-track"><div class="div-progress-fill" style="width:<?= $pct ?>%;background:<?= $pct >= 80 ? '#2E7D32' : ($pct >= 60 ? '#1565C0' : '#E65100') ?>;"></div></div><div class="div-progress-pct"><?= $pct ?>%</div></div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card"><div class="card-header"><h3>Daftar Karyawan</h3><a href="hrd-data-karyawan.php" class="link">Lihat semua &rarr;</a></div>
    <?php foreach ($employees as $i => $emp): $initials = getInitials($emp['nama']); $color = $colors[$i % count($colors)]; $pct = ($emp['total'] ?? 0) > 0 ? round((($emp['completed'] ?? 0) / $emp['total']) * 100) : 0; ?>
    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #F0F0F0;"><div class="avatar avatar-sm" style="background:<?= $color ?>;"><?= $initials ?></div><div style="flex:1;"><div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($emp['nama']) ?></div><div style="font-size:11px;color:#64748B;"><?= htmlspecialchars($emp['divisi']) ?></div></div><div style="flex:1;"><div class="progress-track"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div></div><?php renderBadge($pct >= 100 ? 'selesai' : ($pct > 0 ? 'berjalan' : 'tertunda')); ?></div>
    <?php endforeach; ?>
  </div>
</div></main>
<script>
function exportReport() {
  showLoading();
  fetch('../api/laporan.php?action=detail_karyawan').then(r => r.json()).then(d => {
    hideLoading();
    if (!d.success) { showError('Gagal export'); return; }
    const rows = [['NIK','Nama','Divisi','Jabatan','Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif','Avg Score']];
    d.data.employees.forEach(e => {
      const s = e.scores;
      const vals = ['am','ko','ha','lo','ad','kol'].map(k => s[k] || 0).filter(v => v > 0);
      const avg = vals.length > 0 ? (vals.reduce((a,b)=>a+b,0) / vals.length).toFixed(2) : '-';
      rows.push([e.nik, e.nama, e.divisi, e.jabatan, s.am||'-', s.ko||'-', s.ha||'-', s.lo||'-', s.ad||'-', s.kol||'-', avg]);
    });
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(rows), 'Dashboard Export');
    XLSX.writeFile(wb, 'hrd-dashboard-<?= date('Ymd-His') ?>.xlsx');
    showSuccess('Export berhasil: ' + (rows.length - 1) + ' karyawan');
  }).catch(e => { hideLoading(); showError('Koneksi gagal'); });
}
</script>
<?php renderPageEnd(); ?>