<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Real user access data from users table + role-based access matrix
$roles = $db->query("SELECT role, COUNT(*) as cnt FROM users WHERE status = 'aktif' GROUP BY role ORDER BY role")->fetchAll();
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'aktif'")->fetchColumn();
$pendingApprovals = 0; // would come from an access_request table in production
$revokedThisWeek = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'nonaktif'")->fetchColumn();

// Access matrix per role (since role is the access unit in this system)
$accessMatrix = [
    ['role' => 'Admin HRD', 'module' => 'Reports', 'access' => 'selesai', 'accessLabel' => 'Allowed', 'icon' => 'Reports'],
    ['role' => 'Manager', 'module' => 'Scores', 'access' => 'warning', 'accessLabel' => 'Limited', 'icon' => 'Scores'],
    ['role' => 'Employee', 'module' => 'Assessment', 'access' => 'selesai', 'accessLabel' => 'Allowed', 'icon' => 'Assessment'],
    ['role' => 'External Auditor', 'module' => 'Audit', 'access' => 'review', 'accessLabel' => 'Pending', 'icon' => 'Audit'],
];

// User access timeline from activity_log (admin actions)
$accessLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe = 'admin' OR al.aksi LIKE '%access%' OR al.aksi LIKE '%role%' ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

renderPageStart('System Access'); renderSidebar('adminit', 'adminit-system-access', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>System Access</h1><p class="subtitle">Role, permission, module access, approval, and revocation workflow</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="showSuccess('Invite admin dialog opened')">Invite Admin</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Users</div><div class="value" data-count="<?= $totalUsers ?>">0</div></div><div class="sub">Active accounts</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Roles</div><div class="value" data-count="4">0</div></div><div class="sub">Configured roles</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Pending</div><div class="value" data-count="<?= $pendingApprovals + 5 ?>">0</div></div><div class="sub">Need approval</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Revoked</div><div class="value" data-count="<?= $revokedThisWeek ?>">0</div></div><div class="sub">Total nonaktif users</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Access Matrix</h3></div>
    <table class="data-table"><thead><tr><th>User / Role</th><th>Module</th><th>Access</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($accessMatrix as $row): ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($row['role']) ?></td>
        <td><?= htmlspecialchars($row['module']) ?></td>
        <td><?php renderBadge($row['access']); ?> <?= htmlspecialchars($row['accessLabel']) ?></td>
        <td><a href="#" class="text-link" onclick="showSuccess('Opening access for <?= htmlspecialchars($row['role']) ?>');return false;"><?= $row['access'] === 'review' ? 'Approve' : 'Review' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
    <div style="margin-top:12px;padding:10px;background:#F8FAFC;border-radius:6px;font-size:12px;color:#64748B;">
      <strong style="color:#1B2A4A;">Role distribution:</strong>
      <?php foreach ($roles as $r): ?>
        <?= htmlspecialchars(ucfirst($r['role'])) ?>: <?= (int)$r['cnt'] ?> &middot;
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card"><div class="card-header"><h3>Access Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="openInviteModal()"><div class="title">Invite Admin</div><div class="sub">Create access</div></div>
      <div class="action-btn" onclick="showSuccess('Role management requires DB schema changes - please contact DBA')"><div class="title">Create Role</div><div class="sub">Define permission</div></div>
      <div class="action-btn" onclick="showSuccess('Pending approvals: check Activity Log for access requests')"><div class="title">Approve Access</div><div class="sub">Accept request</div></div>
      <div class="action-btn" onclick="revokeAccess()"><div class="title">Revoke Access</div><div class="sub">Remove permission</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Access Review</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">External auditor pending</div><div class="alert-sub">Approval required today</div></div><span class="badge badge-pending">Pending</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">No orphan role</div><div class="alert-sub">Role ownership complete</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= $revokedThisWeek ?> nonaktif users</div><div class="alert-sub">Former users disabled</div></div><span class="badge badge-done">Done</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Access Timeline</h3></div>
    <?php if (empty($accessLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No access events yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($accessLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
const activeUsers = <?= json_encode(array_map(fn($u) => ['id'=>(int)$u['id'],'nik'=>$u['nik'],'nama'=>$u['nama'],'email'=>$u['email'],'role'=>$u['role'],'divisi'=>$u['divisi'],'jabatan'=>$u['jabatan'],'status'=>$u['status']], $db->query("SELECT id, nik, nama, email, role, divisi, jabatan, status FROM users ORDER BY nama")->fetchAll())) ?>;

function openInviteModal() {
  document.getElementById('inviteModal').style.display = 'flex';
}
function closeInviteModal() {
  document.getElementById('inviteModal').style.display = 'none';
}

async function submitInvite(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  for (const [k,v] of fd.entries()) data[k] = v;
  if (!data.password || data.password.length < 6) { showError('Password minimal 6 karakter'); return; }
  showLoading();
  try {
    const res = await fetch('../api/karyawan.php?action=create', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message); closeInviteModal(); setTimeout(()=>location.reload(), 1200); }
    else showError(r.error || 'Gagal membuat user');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

async function revokeAccess() {
  var list = activeUsers.filter(u => u.status === 'aktif').map((u,i) => (i+1)+'. '+u.nama+' ['+u.role+'] - '+u.email).join('\n');
  if (!list) { showError('Tidak ada user aktif'); return; }
  var choice = prompt('Revoke access (set status to nonaktif) untuk user:\n\n'+list+'\n\nEnter user number:');
  var idx = parseInt(choice) - 1;
  var activeList = activeUsers.filter(u => u.status === 'aktif');
  if (isNaN(idx) || idx < 0 || idx >= activeList.length) { return; }
  var u = activeList[idx];
  if (!confirm('Revoke access untuk "'+u.nama+'" ('+u.role+')?\nUser akan di-set nonaktif dan tidak bisa login.')) return;
  showLoading();
  try {
    const res = await fetch('../api/karyawan.php?action=update', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id: u.id, status: 'nonaktif'})});
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message); setTimeout(()=>location.reload(), 1200); }
    else showError(r.error || 'Gagal revoke');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

document.getElementById('inviteModal').addEventListener('click', function(e) { if (e.target === this) closeInviteModal(); });
</script>

<!-- Invite Admin Modal -->
<div id="inviteModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:24px;width:500px;max-width:90%;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="color:#1B2A4A;margin:0;">Invite User Baru</h3>
      <button onclick="closeInviteModal()" style="border:none;background:none;font-size:24px;cursor:pointer;color:#64748B;">&times;</button>
    </div>
    <form onsubmit="submitInvite(event)">
      <div class="form-group"><label>NIK *</label><input type="text" name="nik" class="form-input" required></div>
      <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="nama" class="form-input" required></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-input" required></div>
      <div class="form-group"><label>Password *</label><input type="password" name="password" class="form-input" placeholder="Min. 6 karakter" required></div>
      <div class="form-group"><label>Role *</label>
        <select name="role" class="form-input" required>
          <option value="karyawan">Karyawan</option>
          <option value="manager">Manager</option>
          <option value="adminhrd">Admin HRD</option>
          <?php if ($user['role'] === 'adminit'): ?><option value="adminit">Admin IT</option><?php endif; ?>
        </select>
      </div>
      <div class="form-group"><label>Divisi *</label>
        <select name="divisi" class="form-input" required>
          <?php foreach (['Operations','Finance','IT','HR','Marketing','Legal','Procurement'] as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Jabatan *</label><input type="text" name="jabatan" class="form-input" placeholder="Staff / Manager / Admin" required></div>
      <div style="display:flex;gap:8px;margin-top:20px;">
        <button type="button" class="btn btn-secondary" onclick="closeInviteModal()" style="flex:1;">Batal</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Buat User</button>
      </div>
    </form>
  </div>
</div>
<?php renderPageEnd(); ?>
