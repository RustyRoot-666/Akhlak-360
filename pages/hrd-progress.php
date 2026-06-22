<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();

// Progress per divisi (real)
$stmt = $db->prepare("SELECT u.divisi, COUNT(DISTINCT u.id) as total_karyawan, COUNT(DISTINCT am.karyawan_id) as karyawan_with_mapping, SUM(CASE WHEN am.status = 'submitted' THEN 1 ELSE 0 END) as completed, COUNT(am.id) as total_mapping FROM users u LEFT JOIN assessor_mapping am ON u.id = am.karyawan_id AND am.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.divisi ORDER BY u.divisi");
$stmt->execute([$periodeId]); $progress = $stmt->fetchAll();

// Real global stats
$totalMappings = (int)$db->query("SELECT COUNT(*) FROM assessor_mapping WHERE periode_id = {$periodeId}")->fetchColumn();
$completedMappings = (int)$db->query("SELECT COUNT(*) FROM assessor_mapping WHERE periode_id = {$periodeId} AND status = 'submitted'")->fetchColumn();
$draftMappings = (int)$db->query("SELECT COUNT(*) FROM assessor_mapping WHERE periode_id = {$periodeId} AND status = 'draft'")->fetchColumn();
$assignedMappings = (int)$db->query("SELECT COUNT(*) FROM assessor_mapping WHERE periode_id = {$periodeId} AND status = 'assigned'")->fetchColumn();
$completionPct = $totalMappings > 0 ? round(($completedMappings / $totalMappings) * 100) : 0;

// Real lateness list: karyawan who still have non-submitted mappings
$stmt = $db->prepare("SELECT u.id, u.nama, u.divisi, u.jabatan, COUNT(am.id) as total, SUM(CASE WHEN am.status='submitted' THEN 1 ELSE 0 END) as submitted, SUM(CASE WHEN am.status='draft' THEN 1 ELSE 0 END) as draft, SUM(CASE WHEN am.status='assigned' THEN 1 ELSE 0 END) as assigned FROM users u JOIN assessor_mapping am ON u.id = am.karyawan_id AND am.periode_id = ? WHERE u.role = 'karyawan' AND u.status = 'aktif' GROUP BY u.id HAVING submitted < total ORDER BY submitted ASC, u.nama LIMIT 50");
$stmt->execute([$periodeId]); $lateList = $stmt->fetchAll();

$deadline = $periode ? date('d M Y', strtotime($periode['deadline'])) : '-';

renderPageStart('Pantau Progress'); renderSidebar('adminhrd', 'hrd-progress', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Pantau Progress</h1><p class="subtitle">Monitoring pengisian assessment lintas divisi secara real-time</p></div><div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="sendReminder()">Kirim Reminder</button><button class="btn btn-secondary btn-sm" onclick="exportProgress()">Export XLS</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Completion</div><div class="value" data-count="<?= $completionPct ?>">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub"><?= $completedMappings ?> dari <?= $totalMappings ?> selesai</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Belum Mulai</div><div class="value" data-count="<?= $assignedMappings ?>">0</div></div><div class="sub">Perlu reminder</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Draft</div><div class="value" data-count="<?= $draftMappings ?>">0</div></div><div class="sub">Belum submit</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Selesai</div><div class="value" data-count="<?= $completedMappings ?>">0</div></div><div class="sub">Terkirim valid</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Progress per Divisi</h3></div>
    <?php if (empty($progress)): ?>
      <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada data karyawan aktif.</p>
    <?php else: foreach ($progress as $p):
      $pct = ($p['total_mapping'] ?? 0) > 0 ? round((($p['completed'] ?? 0) / $p['total_mapping']) * 100) : 0;
    ?>
    <div class="div-progress-row"><div class="div-progress-label"><?= htmlspecialchars($p['divisi']) ?> <span style="font-size:11px;color:#64748B;">(<?= (int)$p['total_karyawan'] ?> karyawan)</span></div><div class="div-progress-track"><div class="div-progress-fill" style="width:<?= $pct ?>%;background:<?= $pct >= 80 ? '#2E7D32' : ($pct >= 60 ? '#1565C0' : '#E65100') ?>;"></div></div><div class="div-progress-pct"><?= $pct ?>%</div></div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card"><div class="card-header"><h3>Reminder & Aksi</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="sendReminder()"><div class="title">Kirim Reminder</div><div class="sub">Untuk belum mulai (<?= $assignedMappings ?>)</div></div>
      <div class="action-btn" onclick="followUpDraft()"><div class="title">Follow-up Draft</div><div class="sub">Ingatkan submit (<?= $draftMappings ?>)</div></div>
      <div class="action-btn" onclick="lockPeriode()"><div class="title">Lock Periode</div><div class="sub">Tutup pengisian</div></div>
      <div class="action-btn" onclick="exportProgress()"><div class="title">Export Progress</div><div class="sub">Unduh XLS</div></div>
    </div>
  </div>
</div>
<div class="card"><div class="card-header"><h3>Daftar Keterlambatan (<?= count($lateList) ?> karyawan)</h3><span style="font-size:12px;color:#64748B;">Deadline: <?= $deadline ?></span></div>
  <?php if (empty($lateList)): ?>
    <p style="color:#2E7D32;padding:20px 0;text-align:center;">Semua karyawan sudah menyelesaikan penilaian!</p>
  <?php else: ?>
  <table class="data-table" id="lateTable"><thead><tr><th>Nama</th><th>Divisi</th><th>Jabatan</th><th>Progress</th><th>Status</th><th>Deadline</th></tr></thead><tbody>
    <?php foreach ($lateList as $l):
      $pct = $l['total'] > 0 ? round(($l['submitted'] / $l['total']) * 100) : 0;
      $statusBadge = $l['submitted'] > 0 ? 'berjalan' : 'tertunda';
    ?>
    <tr>
      <td style="font-weight:500;"><?= htmlspecialchars($l['nama']) ?></td>
      <td><?= htmlspecialchars($l['divisi']) ?></td>
      <td><?= htmlspecialchars($l['jabatan']) ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="progress-track" style="width:80px;"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct >= 80 ? '#2E7D32' : ($pct >= 50 ? '#1565C0' : '#E65100') ?>;"></div></div>
          <span style="font-size:11px;"><?= $l['submitted'] ?>/<?= $l['total'] ?> (<?= $pct ?>%)</span>
        </div>
      </td>
      <td><?php renderBadge($statusBadge); ?></td>
      <td style="font-size:12px;color:#64748B;"><?= $deadline ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div></main>
<script>
function sendReminder() {
  if (!confirm('Kirim reminder notifikasi ke semua karyawan yang belum menyelesaikan penilaian (<?= count($lateList) ?> karyawan)?')) return;
  showLoading();
  fetch('../api/notifikasi.php?action=broadcast', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      role: 'karyawan',
      judul: 'Pengingat: Selesaikan Penilaian AKHLAK',
      pesan: 'Anda memiliki penilaian yang belum diselesaikan. Deadline: <?= $deadline ?>. Segera selesaikan di menu Penilaian Saya.',
      tipe: 'peringatan'
    })
  }).then(r => r.json()).then(d => {
    hideLoading();
    if (d.success) showSuccess(d.message || 'Reminder terkirim');
    else showError(d.error || 'Gagal kirim reminder');
  }).catch(e => { hideLoading(); showError('Koneksi gagal'); });
}
function followUpDraft() {
  if (!confirm('Kirim follow-up ke karyawan dengan status draft?')) return;
  showLoading();
  fetch('../api/notifikasi.php?action=broadcast', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      role: 'karyawan',
      judul: 'Follow-up: Submit Penilaian Draft Anda',
      pesan: 'Anda memiliki penilaian dalam status draft. Mohon segera submit sebelum deadline <?= $deadline ?>.',
      tipe: 'info'
    })
  }).then(r => r.json()).then(d => {
    hideLoading();
    if (d.success) showSuccess(d.message || 'Follow-up terkirim');
    else showError(d.error || 'Gagal');
  }).catch(e => { hideLoading(); showError('Koneksi gagal'); });
}
function lockPeriode() {
  if (!confirm('Lock periode? Setelah dikunci, tidak ada penilaian yang bisa diubah.')) return;
  showSuccess('Permintaan lock periode dikirim ke Admin IT');
}
function exportProgress() {
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.table_to_sheet(document.getElementById('lateTable'));
  XLSX.utils.book_append_sheet(wb, ws, 'Progress Keterlambatan');
  XLSX.writeFile(wb, 'progress-keterlambatan-<?= date('Ymd') ?>.xlsx');
}
</script>
<?php renderPageEnd(); ?>
