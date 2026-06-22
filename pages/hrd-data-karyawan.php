<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();

// Filters
$divisiF = $_GET['divisi'] ?? '';
$statusF = $_GET['status'] ?? '';
$searchQ = $_GET['search'] ?? '';

$sql = "SELECT u.*, m.nama as manager_nama, (SELECT AVG(nilai_final) FROM rekap_nilai WHERE karyawan_id = u.id AND periode_id = ?) as avg_score, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ? AND status = 'submitted') as completed_count, (SELECT COUNT(*) FROM assessor_mapping WHERE karyawan_id = u.id AND periode_id = ?) as total_assessors FROM users u LEFT JOIN users m ON u.manager_id = m.id WHERE u.role = 'karyawan'";
$params = [$periodeId, $periodeId, $periodeId];
if ($divisiF) { $sql .= " AND u.divisi = ?"; $params[] = $divisiF; }
if ($statusF) { $sql .= " AND u.status = ?"; $params[] = $statusF; }
if ($searchQ) { $sql .= " AND (u.nama LIKE ? OR u.email LIKE ? OR u.nik LIKE ?)"; $params[] = "%$searchQ%"; $params[] = "%$searchQ%"; $params[] = "%$searchQ%"; }
$sql .= " ORDER BY u.nama";
$stmt = $db->prepare($sql); $stmt->execute($params); $employees = $stmt->fetchAll();

// Divisions + managers for filter & dropdowns
$divisions = ['Operations','Finance','IT','HR','Marketing','Legal','Procurement'];
$managers = $db->query("SELECT id, nama, divisi FROM users WHERE role = 'manager' AND status = 'aktif' ORDER BY nama")->fetchAll();
$colors = ['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C','#AD1457','#FF8F00','#6D4C41','#00838F'];

renderPageStart('Data Karyawan'); renderSidebar('adminhrd', 'hrd-data-karyawan', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Data Karyawan</h1><p class="subtitle"><?= count($employees) ?> karyawan &middot; Periode <?= $periode['nama'] ?? '-' ?></p></div><div class="page-header-actions"><button class="btn btn-secondary btn-sm" onclick="document.getElementById('filterCard').style.display=document.getElementById('filterCard').style.display==='none'?'block':'none'">Filter</button><button class="btn btn-secondary btn-sm" onclick="exportTable()">Export XLS</button><button class="btn btn-primary btn-sm" onclick="exportIDP()">Export IDP</button><button class="btn btn-primary btn-sm" onclick="openCreateModal()">+ Tambah Karyawan</button></div></div>

<!-- Filter card -->
<div id="filterCard" class="card" style="margin-bottom:16px;display:<?= ($divisiF || $statusF || $searchQ) ? 'block' : 'none' ?>;">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    <div style="flex:1;min-width:180px;"><label style="font-size:11px;color:#64748B;">Divisi</label>
      <select name="divisi" class="form-input">
        <option value="">Semua divisi</option>
        <?php foreach ($divisions as $d): ?><option value="<?= htmlspecialchars($d) ?>" <?= $divisiF === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1;min-width:180px;"><label style="font-size:11px;color:#64748B;">Status</label>
      <select name="status" class="form-input">
        <option value="">Semua status</option>
        <option value="aktif" <?= $statusF === 'aktif' ? 'selected' : '' ?>>Aktif</option>
        <option value="nonaktif" <?= $statusF === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        <option value="cuti" <?= $statusF === 'cuti' ? 'selected' : '' ?>>Cuti</option>
      </select>
    </div>
    <div style="flex:2;min-width:240px;"><label style="font-size:11px;color:#64748B;">Cari</label>
      <input type="text" name="search" class="form-input" placeholder="Nama, email, atau NIK..." value="<?= htmlspecialchars($searchQ) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
    <a href="hrd-data-karyawan.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>
</div>

<div class="card">
<table class="data-table" id="empTable"><thead><tr><th>Nama Karyawan</th><th>Divisi</th><th>Jabatan</th><th>Manager</th><th>Progress</th><th>Nilai Akhir</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if (empty($employees)): ?>
  <tr><td colspan="8" style="text-align:center;color:#64748B;padding:20px;">Tidak ada karyawan yang cocok dengan filter.</td></tr>
<?php else: foreach ($employees as $i => $emp):
  $initials = getInitials($emp['nama']);
  $color = $colors[$i % count($colors)];
  $total = (int)$emp['total_assessors'];
  $completed = (int)$emp['completed_count'];
  $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<tr>
  <td style="display:flex;align-items:center;gap:8px;"><div class="avatar avatar-sm" style="background:<?= $color ?>;"><?= $initials ?></div><div><div style="font-weight:500;"><?= htmlspecialchars($emp['nama']) ?></div><div style="font-size:11px;color:#64748B;"><?= htmlspecialchars($emp['email']) ?></div></div></td>
  <td><?= htmlspecialchars($emp['divisi']) ?></td>
  <td><?= htmlspecialchars($emp['jabatan']) ?></td>
  <td><?= htmlspecialchars($emp['manager_nama'] ?? '-') ?></td>
  <td><div class="progress-track"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#2E7D32' : ($pct >= 50 ? '#1565C0' : '#E65100') ?>;"></div></div><span style="font-size:11px;"><?= $pct ?>%</span></td>
  <td><?= $emp['avg_score'] ? round($emp['avg_score'], 2) : '&mdash;' ?></td>
  <td><?php renderBadge($emp['status'] === 'aktif' ? 'aktif' : ($emp['status'] === 'cuti' ? 'berjalan' : 'tertunda')); ?></td>
  <td>
    <a href="#" class="text-link" onclick="openEditModal(<?= (int)$emp['id'] ?>);return false;">Edit</a>
    &middot;
    <a href="#" class="text-link" style="color:#C62828;" onclick="deleteKaryawan(<?= (int)$emp['id'] ?>, '<?= htmlspecialchars($emp['nama'], ENT_QUOTES) ?>');return false;">Hapus</a>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
</div></main>

<!-- Modal: Create/Edit Karyawan -->
<div id="empModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:24px;width:500px;max-width:90%;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 id="modalTitle" style="color:#1B2A4A;margin:0;">Tambah Karyawan</h3>
      <button onclick="closeModal()" style="border:none;background:none;font-size:24px;cursor:pointer;color:#64748B;">&times;</button>
    </div>
    <form id="empForm" onsubmit="submitForm(event)">
      <input type="hidden" name="id" id="empId" value="">
      <div class="form-group"><label>NIK *</label><input type="text" name="nik" id="empNik" class="form-input" required></div>
      <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="nama" id="empNama" class="form-input" required></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" id="empEmail" class="form-input" required></div>
      <div class="form-group"><label>Password <?= isset($_GET['edit']) ? '(kosongkan jika tidak diubah)' : '*' ?></label><input type="password" name="password" id="empPassword" class="form-input" <?= !isset($_GET['edit']) ? 'required' : '' ?>></div>
      <div class="form-group"><label>Divisi *</label>
        <select name="divisi" id="empDivisi" class="form-input" required>
          <?php foreach ($divisions as $d): ?><option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Jabatan *</label><input type="text" name="jabatan" id="empJabatan" class="form-input" placeholder="Staff Operations" required></div>
      <div class="form-group"><label>Manager</label>
        <select name="manager_id" id="empManager" class="form-input">
          <option value="">- Tanpa manager -</option>
          <?php foreach ($managers as $m): ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nama']) ?> (<?= htmlspecialchars($m['divisi']) ?>)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="status" id="empStatus" class="form-input">
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
          <option value="cuti">Cuti</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;margin-top:20px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal()" style="flex:1;">Batal</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
const managers = <?= json_encode($managers) ?>;
const employees = <?= json_encode(array_map(fn($e) => ['id'=>(int)$e['id'],'nik'=>$e['nik'],'nama'=>$e['nama'],'email'=>$e['email'],'divisi'=>$e['divisi'],'jabatan'=>$e['jabatan'],'manager_id'=>$e['manager_id'],'status'=>$e['status']], $employees)) ?>;

function openCreateModal() {
  document.getElementById('modalTitle').textContent = 'Tambah Karyawan';
  document.getElementById('empForm').reset();
  document.getElementById('empId').value = '';
  document.getElementById('empPassword').required = true;
  document.getElementById('empPassword').placeholder = '';
  document.getElementById('empModal').style.display = 'flex';
}

function openEditModal(id) {
  const emp = employees.find(e => e.id === id);
  if (!emp) { showError('Karyawan tidak ditemukan'); return; }
  document.getElementById('modalTitle').textContent = 'Edit Karyawan: ' + emp.nama;
  document.getElementById('empId').value = emp.id;
  document.getElementById('empNik').value = emp.nik;
  document.getElementById('empNama').value = emp.nama;
  document.getElementById('empEmail').value = emp.email;
  document.getElementById('empDivisi').value = emp.divisi;
  document.getElementById('empJabatan').value = emp.jabatan;
  document.getElementById('empManager').value = emp.manager_id || '';
  document.getElementById('empStatus').value = emp.status;
  document.getElementById('empPassword').value = '';
  document.getElementById('empPassword').required = false;
  document.getElementById('empPassword').placeholder = '(kosongkan jika tidak diubah)';
  document.getElementById('empModal').style.display = 'flex';
}

function closeModal() { document.getElementById('empModal').style.display = 'none'; }

async function submitForm(e) {
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);
  const id = fd.get('id');
  const data = {};
  for (const [k, v] of fd.entries()) { if (v || k === 'password' || k === 'manager_id') data[k] = v; }
  if (!data.password) delete data.password;
  if (!data.manager_id) data.manager_id = null;
  showLoading();
  try {
    const url = '../api/karyawan.php?action=' + (id ? 'update' : 'create');
    const res = await fetch(url, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message || 'Karyawan disimpan'); closeModal(); setTimeout(() => location.reload(), 1000); }
    else showError(r.error || 'Gagal menyimpan');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

async function deleteKaryawan(id, nama) {
  if (!confirm('Nonaktifkan karyawan "' + nama + '"?\n(soft delete - data tetap tersimpan, hanya status jadi nonaktif)')) return;
  showLoading();
  try {
    const res = await fetch('../api/karyawan.php?action=delete&id=' + id, { method: 'GET' });
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message || 'Karyawan dinonaktifkan'); setTimeout(() => location.reload(), 1000); }
    else showError(r.error || 'Gagal menghapus');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

function exportTable() {
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.table_to_sheet(document.getElementById('empTable'));
  XLSX.utils.book_append_sheet(wb, ws, "Data Karyawan");
  XLSX.writeFile(wb, "data-karyawan-<?= date('Ymd') ?>.xlsx");
}

function exportIDP() {
  showLoading();
  // IDP = Individual Development Plan - export karyawan dengan avg score + rekomendasi per dimensi
  fetch('../api/laporan.php?action=detail_karyawan').then(r => r.json()).then(d => {
    hideLoading();
    if (!d.success) { showError('Gagal export IDP'); return; }
    const rows = [['NIK', 'Nama', 'Divisi', 'Jabatan', 'Amanah', 'Kompeten', 'Harmonis', 'Loyal', 'Adaptif', 'Kolaboratif', 'Avg', 'Area Pengembangan', 'Rekomendasi IDP']];
    const recs = {am:'Review komitmen target', ko:'Upskill teknis', ha:'Conflict resolution', lo:'Engagement session', ad:'Change management training', kol:'Cross-functional project'};
    d.data.employees.forEach(e => {
      const scores = e.scores;
      const keys = ['am','ko','ha','lo','ad','kol'];
      const vals = keys.map(k => scores[k] || 0);
      const avg = vals.filter(v => v > 0).length > 0 ? (vals.reduce((a,b)=>a+b,0) / vals.filter(v=>v>0).length).toFixed(2) : '-';
      // Find weakest dimension
      const validKeys = keys.filter(k => scores[k]);
      const weakest = validKeys.length > 0 ? validKeys.reduce((a,b) => scores[a] < scores[b] ? a : b) : null;
      rows.push([e.nik, e.nama, e.divisi, e.jabatan, scores.am||'-', scores.ko||'-', scores.ha||'-', scores.lo||'-', scores.ad||'-', scores.kol||'-', avg, weakest ? weakest.toUpperCase() : '-', weakest ? recs[weakest] : 'Belum ada data nilai']);
    });
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(rows);
    XLSX.utils.book_append_sheet(wb, ws, "IDP");
    XLSX.writeFile(wb, "idp-karyawan-<?= date('Ymd') ?>.xlsx");
    showSuccess('IDP exported: ' + (rows.length - 1) + ' karyawan');
  }).catch(e => { hideLoading(); showError('Koneksi gagal'); });
}

// Close modal on outside click
document.getElementById('empModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>
<?php renderPageEnd(); ?>
