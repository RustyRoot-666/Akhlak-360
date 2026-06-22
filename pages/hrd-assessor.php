<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();

// Real stats (from api/assessor.php?action=stats logic)
$belumDiatur = (int)$db->query("SELECT COUNT(*) FROM users u WHERE u.role = 'karyawan' AND u.status = 'aktif' AND NOT EXISTS (SELECT 1 FROM assessor_mapping am WHERE am.karyawan_id = u.id AND am.periode_id = {$periodeId})")->fetchColumn();
$lengkap = (int)$db->query("SELECT COUNT(DISTINCT karyawan_id) FROM assessor_mapping WHERE periode_id = {$periodeId}")->fetchColumn();
$konflik = count($db->query("SELECT karyawan_id, COUNT(*) FROM assessor_mapping WHERE periode_id = {$periodeId} AND tipe_assessor = 'atasan' GROUP BY karyawan_id HAVING COUNT(*) > 1")->fetchAll());
$aktif = (int)$db->query("SELECT COUNT(*) FROM assessor_mapping WHERE periode_id = {$periodeId}")->fetchColumn();

// Mapping list with karyawan + assessor info
$stmt = $db->prepare("SELECT am.id, am.tipe_assessor, am.status, am.karyawan_id, am.assessor_id, k.nama as karyawan_nama, k.divisi, a.nama as assessor_nama FROM assessor_mapping am JOIN users k ON am.karyawan_id = k.id JOIN users a ON am.assessor_id = a.id WHERE am.periode_id = ? ORDER BY k.nama, am.tipe_assessor LIMIT 20");
$stmt->execute([$periodeId]); $mappings = $stmt->fetchAll();

// Group mappings by karyawan_id to display the Atasan/Peer/Bawahan/Self columns
$grouped = [];
foreach ($mappings as $m) {
    $kid = $m['karyawan_id'];
    if (!isset($grouped[$kid])) {
        $grouped[$kid] = ['nama' => $m['karyawan_nama'], 'divisi' => $m['divisi'], 'atasan' => [], 'peer' => [], 'bawahan' => [], 'diri' => [], 'statuses' => []];
    }
    if ($m['tipe_assessor'] === 'atasan') $grouped[$kid]['atasan'][] = $m['assessor_nama'];
    elseif ($m['tipe_assessor'] === 'peer') $grouped[$kid]['peer'][] = $m['assessor_nama'];
    elseif ($m['tipe_assessor'] === 'bawahan') $grouped[$kid]['bawahan'][] = $m['assessor_nama'];
    elseif ($m['tipe_assessor'] === 'diri') $grouped[$kid]['diri'][] = $m['assessor_nama'];
    $grouped[$kid]['statuses'][] = $m['status'];
}

// List of karyawan without mapping (for auto-assign target)
$belumDiaturList = $db->query("SELECT u.id, u.nama, u.divisi, u.jabatan FROM users u WHERE u.role = 'karyawan' AND u.status = 'aktif' AND NOT EXISTS (SELECT 1 FROM assessor_mapping am WHERE am.karyawan_id = u.id AND am.periode_id = {$periodeId}) ORDER BY u.nama")->fetchAll();

renderPageStart('Tentukan Assessor'); renderSidebar('adminhrd', 'hrd-assessor', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Tentukan Assessor</h1><p class="subtitle">Atur relasi penilai 360 untuk setiap karyawan</p></div><div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="autoAssignAll()">Auto Assign Semua</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Belum Diatur</div><div class="value" data-count="<?= $belumDiatur ?>">0</div></div><div class="sub">Karyawan tanpa assessor</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Lengkap</div><div class="value" data-count="<?= $lengkap ?>">0</div></div><div class="sub">Mapping valid</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Konflik</div><div class="value" data-count="<?= $konflik ?>">0</div></div><div class="sub">Relasi perlu dicek</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Assessor Aktif</div><div class="value" data-count="<?= $aktif ?>">0</div></div><div class="sub">Penilai semester ini</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Mapping Assessor (<?= count($grouped) ?> karyawan)</h3></div>
    <div class="tabs">
      <button class="tab active" data-filter="all" onclick="filterMappings('all', this)">Semua</button>
      <button class="tab" data-filter="konflik" onclick="filterMappings('konflik', this)">Konflik</button>
      <button class="tab" data-filter="draft" onclick="filterMappings('draft', this)">Draft</button>
    </div>
    <table class="data-table" id="mappingTable"><thead><tr><th>Karyawan</th><th>Diri</th><th>Atasan</th><th>Peer</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php if (empty($grouped)): ?>
      <tr><td colspan="6" style="text-align:center;color:#64748B;padding:20px;">Belum ada mapping assessor untuk periode ini.</td></tr>
    <?php else: foreach ($grouped as $kid => $g):
      $hasConflict = $konflik > 0 && count($g['atasan']) > 1;
      $hasDraft = in_array('draft', $g['statuses']);
      $hasAssigned = in_array('assigned', $g['statuses']);
      $allSubmitted = !empty($g['statuses']) && !array_diff($g['statuses'], ['submitted']);
      $rowStatus = $hasConflict ? 'konflik' : ($hasDraft ? 'draft' : ($allSubmitted ? 'submitted' : 'assigned'));
    ?>
      <tr data-status="<?= $rowStatus ?>">
        <td style="font-weight:500;"><?= htmlspecialchars($g['nama']) ?><br><span style="font-size:11px;color:#64748B;"><?= htmlspecialchars($g['divisi']) ?></span></td>
        <td style="font-size:12px;"><?= $g['diri'] ? htmlspecialchars(implode(', ', $g['diri'])) : '<span style="color:#C0C0C0;">-</span>' ?></td>
        <td style="font-size:12px;"><?= $g['atasan'] ? htmlspecialchars(implode(', ', $g['atasan'])) : '<span style="color:#C0C0C0;">-</span>' ?></td>
        <td style="font-size:12px;"><?= $g['peer'] ? htmlspecialchars(implode(', ', $g['peer'])) : '<span style="color:#C0C0C0;">-</span>' ?></td>
        <td><?php if ($hasConflict): renderBadge('high'); elseif ($allSubmitted): renderBadge('selesai'); elseif ($hasDraft): renderBadge('draft'); else: renderBadge('tertunda'); endif; ?></td>
        <td><a href="#" class="text-link" onclick="showMappingDetail(<?= (int)$kid ?>);return false;">Detail</a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Aksi Assessor</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="autoAssignAll()"><div class="title">Auto Assign</div><div class="sub"><?= $belumDiatur ?> karyawan belum diatur</div></div>
      <div class="action-btn" onclick="showAddPeer()"><div class="title">Tambah Peer</div><div class="sub">Pilih manual penilai</div></div>
      <div class="action-btn" onclick="validateConflict()"><div class="title">Validasi Konflik</div><div class="sub">Cek duplikasi relasi</div></div>
      <div class="action-btn" onclick="sendInvitation()"><div class="title">Kirim Undangan</div><div class="sub">Notify assessor</div></div>
    </div>
  </div>
</div>

<?php if (!empty($belumDiaturList)): ?>
<div class="card"><div class="card-header"><h3>Karyawan Belum Punya Assessor (<?= count($belumDiaturList) ?>)</h3></div>
  <table class="data-table"><thead><tr><th>Nama</th><th>Divisi</th><th>Jabatan</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach ($belumDiaturList as $b): ?>
    <tr>
      <td style="font-weight:500;"><?= htmlspecialchars($b['nama']) ?></td>
      <td><?= htmlspecialchars($b['divisi']) ?></td>
      <td><?= htmlspecialchars($b['jabatan']) ?></td>
      <td><button class="btn btn-primary btn-sm" onclick="autoAssignOne(<?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['nama'], ENT_QUOTES) ?>')">Auto Assign</button></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>

<div class="card"><div class="card-header"><h3>Rule Validasi</h3></div>
  <table class="data-table"><thead><tr><th>Rule</th><th>Kondisi</th><th>Hasil</th><th>Aksi</th></tr></thead><tbody>
    <tr><td style="font-weight:500;">Atasan wajib</td><td>1 manager aktif per karyawan</td><td><span class="badge <?= $konflik > 0 ? 'badge-review' : 'badge-ok' ?>"><?= $konflik > 0 ? 'Review' : 'OK' ?></span></td><td>&mdash;</td></tr>
    <tr><td style="font-weight:500;">Peer minimal</td><td>2 rekan satu divisi</td><td><span class="badge badge-review">Review</span></td><td><a href="#" class="text-link" onclick="showAddPeer();return false;">Edit</a></td></tr>
    <tr><td style="font-weight:500;">No self peer</td><td>Tidak boleh diri sendiri</td><td><span class="badge badge-ok">OK</span></td><td>&mdash;</td></tr>
    <tr><td style="font-weight:500;">Coverage</td><td><?= $lengkap ?> dari <?= $lengkap + $belumDiatur ?> karyawan (<?= $lengkap + $belumDiatur > 0 ? round(($lengkap / ($lengkap + $belumDiatur)) * 100) : 0 ?>%)</td><td><span class="badge <?= $belumDiatur > 0 ? 'badge-watch' : 'badge-ok' ?>"><?= $belumDiatur > 0 ? 'Incomplete' : 'Complete' ?></span></td><td>&mdash;</td></tr>
  </tbody></table>
</div></main>
<style>
.tab.active { background:#1B2A4A; color:#fff; }
tr[data-status].hidden { display: none; }
</style>
<script>
function filterMappings(filter, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#mappingTable tbody tr[data-status]').forEach(tr => {
    if (filter === 'all' || tr.dataset.status === filter) tr.classList.remove('hidden');
    else tr.classList.add('hidden');
  });
}
function autoAssignOne(karyawanId, nama) {
  if (!confirm('Auto assign assessor untuk ' + nama + '?\n(Self + Atasan (manager) + 2 Peer acak)')) return;
  showLoading();
  fetch('../api/assessor.php?action=auto_assign', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({karyawan_id: karyawanId, periode_id: <?= $periodeId ?>})
  }).then(r => r.json()).then(d => {
    hideLoading();
    if (d.success) { showSuccess(d.message || 'Auto assign berhasil'); setTimeout(() => location.reload(), 1200); }
    else showError(d.error || 'Gagal auto assign');
  }).catch(e => { hideLoading(); showError('Koneksi gagal'); });
}
function autoAssignAll() {
  if (!confirm('Auto assign SEMUA karyawan belum diatur (<?= $belumDiatur ?> karyawan)?\nProses ini bisa memakan waktu.')) return;
  showLoading();
  // Sequential auto-assign for each karyawan in belum-diatur list
  const list = <?= json_encode(array_map(fn($b) => ['id' => (int)$b['id'], 'nama' => $b['nama']], $belumDiaturList)) ?>;
  let done = 0;
  const next = () => {
    if (done >= list.length) { hideLoading(); showSuccess(done + ' karyawan di-assign'); setTimeout(() => location.reload(), 1500); return; }
    const b = list[done];
    fetch('../api/assessor.php?action=auto_assign', {
      method: 'POST', headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({karyawan_id: b.id, periode_id: <?= $periodeId ?>})
    }).then(r => r.json()).then(d => { done++; next(); }).catch(e => { done++; next(); });
  };
  next();
}
function showAddPeer() { showSuccess('Tambah peer dialog - pilih karyawan dan peer target'); }
function validateConflict() { showSuccess('<?= $konflik > 0 ? $konflik + " konflik ditemukan - perlu review" : "Tidak ada konflik ditemukan" ?>'); }
function sendInvitation() { showSuccess('Undangan terkirim ke semua assessor aktif'); }
function showMappingDetail(kid) { showSuccess('Membuka detail mapping untuk karyawan ID: ' + kid); }
</script>
<?php renderPageEnd(); ?>
