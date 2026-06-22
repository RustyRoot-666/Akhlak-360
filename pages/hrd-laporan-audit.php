<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $db = getDB();
$logs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetchAll();
renderPageStart('Audit Trail'); renderSidebar('adminhrd', 'hrd-laporan', $user);
?><main class="main-content">
<div class="page-header page-header-row"><div><h1>Audit Trail — Dokumen Pendukung (ZIP)</h1><p class="subtitle">Riwayat aktivitas dan kumpulan dokumen pendukung proses assessment AKHLAK 360</p></div><div class="page-header-actions"><a href="hrd-laporan.php" class="btn btn-secondary btn-sm">&larr; Kembali</a><button class="btn btn-primary btn-sm" onclick="downloadAuditPack()">Download Pack (XLSX)</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Total Dokumen</div><div class="value" data-count="4">0</div><div class="sub" style="font-size:18px;">Sheet</div></div><div class="sub">Audit log, security, login, admin</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Total Aktivitas</div><div class="value" data-count="<?= $db->query('SELECT COUNT(*) FROM activity_log')->fetchColumn() ?>">0</div><div class="sub" style="font-size:18px;">Log</div></div><div class="sub">Sepanjang periode aktif</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Security Alerts</div><div class="value" data-count="<?= $db->query('SELECT COUNT(*) FROM security_alerts')->fetchColumn() ?>">0</div></div><div class="sub">Total recorded</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Terakhir Diperbarui</div><div class="value" style="font-size:18px;"><?= date('d M Y') ?></div></div><div class="sub"><?= date('H:i') ?> WIB</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Riwayat Aktivitas Terbaru</h3></div>
    <table class="data-table"><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Pengguna</th></tr></thead><tbody>
    <?php foreach ($logs as $l): ?><tr><td style="font-size:12px;"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td><td><?= htmlspecialchars($l['aksi']) ?></td><td><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Isi Arsip ZIP</h3></div>
    <div class="action-panel">
      <div class="action-btn"><div class="title">Form Penilaian (PDF)</div><div class="sub">Form atasan &amp; self assessment</div></div>
      <div class="action-btn"><div class="title">Log Aktivitas (CSV)</div><div class="sub">Seluruh aktivitas sistem</div></div>
      <div class="action-btn"><div class="title">Dokumen Pendukung</div><div class="sub">Lampiran tambahan tiap divisi</div></div>
      <div class="action-btn"><div class="title">Tanda Tangan Digital</div><div class="sub">Bukti approval tiap dokumen</div></div>
    </div>
  </div>
</div>
<div class="insight-box"><h3>Ringkasan Konten Laporan</h3><p>Audit Trail mengumpulkan seluruh dokumen pendukung dan riwayat aktivitas selama proses Assessment AKHLAK 360 ke dalam satu paket XLSX. Digunakan sebagai bukti kepatuhan proses dan referensi audit internal maupun eksternal.</p></div></main>
<script>
function downloadAuditPack() {
  showLoading();
  Promise.all([
    fetch('../api/adminit.php?action=activity_log_csv').then(r => r.text()),
    fetch('../api/adminit.php?action=audit_log_csv').then(r => r.text()),
    fetch('../api/adminit.php?action=backup_log_csv').then(r => r.text())
  ]).then(([activityCsv, auditCsv, backupCsv]) => {
    hideLoading();
    const wb = XLSX.utils.book_new();
    const parseCsv = (csv) => csv.split('\n').filter(l => l.trim()).map(l => l.split(',').map(c => c.replace(/^"|"$/g, '')));
    try { XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(parseCsv(activityCsv)), 'Activity Log'); } catch (e) {}
    try { XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(parseCsv(auditCsv)), 'Audit Events'); } catch (e) {}
    try { XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(parseCsv(backupCsv)), 'Backup Log'); } catch (e) {}
    XLSX.writeFile(wb, 'audit-trail-pack-<?= date('Ymd-His') ?>.xlsx');
    showSuccess('Audit pack berhasil diunduh (3 sheet)');
  }).catch(e => { hideLoading(); showError('Gagal download: ' + e.message); });
}
</script>
<?php renderPageEnd(); ?>