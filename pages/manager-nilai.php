<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('manager');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$stmt = $db->prepare("SELECT u.*, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = ?) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ? AND status = 'submitted') as completed, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ?) as total FROM users u WHERE u.manager_id = ? AND u.status = 'aktif' ORDER BY u.nama");
$stmt->execute([$periodeId, $periodeId, $periodeId, $user['id']]); $team = $stmt->fetchAll();
$colors = ['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C','#AD1457','#FF8F00'];

// Count per status for tab badges
$cntAll = count($team);
$cntSelesai = 0; $cntBerjalan = 0; $cntTertunda = 0;
foreach ($team as $m) {
    $pct = ($m['total'] ?? 0) > 0 ? round((($m['completed'] ?? 0) / $m['total']) * 100) : 0;
    if ($pct >= 100) $cntSelesai++;
    elseif ($pct > 0) $cntBerjalan++;
    else $cntTertunda++;
}

renderPageStart('Nilai Karyawan'); renderSidebar('manager', 'manager-nilai', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Nilai Karyawan Tim Saya</h1><p class="subtitle"><?= htmlspecialchars($user['nama']) ?> &middot; <?= htmlspecialchars($user['divisi']) ?> &middot; <?= $periode['nama'] ?? '-' ?></p></div><div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="exportTable()">Export XLS</button></div></div>
<div class="tabs">
  <button class="tab active" data-filter="all" onclick="filterTab('all', this)">Semua <span class="tab-count">(<?= $cntAll ?>)</span></button>
  <button class="tab" data-filter="selesai" onclick="filterTab('selesai', this)">Selesai <span class="tab-count">(<?= $cntSelesai ?>)</span></button>
  <button class="tab" data-filter="berjalan" onclick="filterTab('berjalan', this)">Berjalan <span class="tab-count">(<?= $cntBerjalan ?>)</span></button>
  <button class="tab" data-filter="tertunda" onclick="filterTab('tertunda', this)">Tertunda <span class="tab-count">(<?= $cntTertunda ?>)</span></button>
</div>
<div class="card">
<table class="data-table" id="teamTable"><thead><tr><th>Nama</th><th>Jabatan</th><th>Progress</th><th>Nilai Akhir</th><th>Status</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($team as $i => $member):
  $pct = ($member['total'] ?? 0) > 0 ? round((($member['completed'] ?? 0) / $member['total']) * 100) : 0;
  $initials = getInitials($member['nama']);
  $color = $colors[$i % count($colors)];
  $status = $pct >= 100 ? 'selesai' : ($pct > 0 ? 'berjalan' : 'tertunda');
?>
<tr data-status="<?= $status ?>">
  <td style="display:flex;align-items:center;gap:8px;"><div class="avatar avatar-sm" style="background:<?= $color ?>;"><?= $initials ?></div><?= htmlspecialchars($member['nama']) ?><br><span style="font-size:11px;color:#64748B;"><?= htmlspecialchars($member['jabatan']) ?></span></td>
  <td><?= htmlspecialchars($member['jabatan']) ?></td>
  <td><div class="progress-track"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#2E7D32' : ($pct >= 50 ? '#1565C0' : '#E65100') ?>;"></div></div><span style="font-size:11px;"><?= $pct ?>% (<?= (int)$member['completed'] ?>/<?= (int)$member['total'] ?>)</span></td>
  <td><?= $member['avg_score'] ? round($member['avg_score'], 2) : '&mdash;' ?></td>
  <td><?php renderBadge($status); ?></td>
  <td><a href="manager-detail-skor.php?karyawan_id=<?= $member['id'] ?>" class="text-link">Lihat &rarr;</a></td>
</tr>
<?php endforeach; ?>
<?php if (empty($team)): ?>
<tr><td colspan="6" style="text-align:center;color:#64748B;padding:20px;">Belum ada anggota tim terdaftar.</td></tr>
<?php endif; ?>
</tbody></table>
</div></main>
<style>
.tab-count { font-size: 11px; color: #64748B; font-weight: 400; }
.tab.active .tab-count { color: #fff; }
tr[data-status] { transition: opacity .2s; }
tr[data-status].hidden { display: none; }
</style>
<script>
function filterTab(filter, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#teamTable tbody tr[data-status]').forEach(tr => {
    if (filter === 'all' || tr.dataset.status === filter) {
      tr.classList.remove('hidden');
    } else {
      tr.classList.add('hidden');
    }
  });
}
function exportTable() {
  // Export only visible rows
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.table_to_sheet(document.getElementById('teamTable'));
  XLSX.utils.book_append_sheet(wb, ws, "Nilai Karyawan");
  XLSX.writeFile(wb, "nilai-karyawan-<?= $periode['nama'] ?? 'aktif' ?>.xlsx");
}
</script>
<?php renderPageEnd(); ?>
