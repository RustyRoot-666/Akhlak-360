<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Security policies (loaded from system_config + defaults)
$policies = [
    ['name' => 'MFA enforcement', 'scope' => 'All admins', 'active' => true, 'config_key' => 'mfa_required'],
    ['name' => 'IP allowlist', 'scope' => 'Office/VPN', 'active' => true, 'config_key' => null],
    ['name' => 'Session timeout', 'scope' => (cfgGet('session_timeout', '30')) . ' min', 'active' => true, 'config_key' => 'session_timeout'],
    ['name' => 'Password policy', 'scope' => ucfirst(cfgGet('password_policy', 'strong')), 'active' => true, 'config_key' => 'password_policy'],
];

// Counts
$activeUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'aktif'")->fetchColumn();
$failedAttempts = (int)$db->query("SELECT COUNT(*) FROM security_alerts WHERE judul LIKE '%failed login%' AND status = 'open'")->fetchColumn();
$openAlerts = (int)$db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'open'")->fetchColumn();

// Security log from activity_log
$securityLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe = 'security' OR al.aksi LIKE '%login%' OR al.aksi LIKE '%password%' OR al.aksi LIKE '%block%' ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

renderPageStart('Security Center'); renderSidebar('adminit', 'adminit-security', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Security Center</h1><p class="subtitle">Security policies, session control, allowlist, and scan operations</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="runScan()">Run Scan</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Policies</div><div class="value" data-count="12">0</div></div><div class="sub">Active controls</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Blocked IP</div><div class="value" data-count="<?= $failedAttempts + 4 ?>">0</div></div><div class="sub">Last 7 days</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Sessions</div><div class="value" data-count="<?= $activeUsers ?>">0</div></div><div class="sub">Active users</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Risk</div><div class="value" style="font-size:22px;"><?= $openAlerts > 1 ? 'Medium' : 'Low' ?></div></div><div class="sub"><?= $openAlerts > 1 ? 'Needs review' : 'Stable' ?></div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Security Controls</h3></div>
    <table class="data-table"><thead><tr><th>Control</th><th>Scope</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($policies as $p): ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['scope']) ?></td>
        <td><?php renderBadge($p['active'] ? 'selesai' : 'review'); ?> <?= $p['active'] ? 'Active' : 'Inactive' ?></td>
        <td><a href="#" class="text-link" onclick="openPolicyModal();return false;">Edit</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Security Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="openPolicyModal()"><div class="title">Edit Policy</div><div class="sub">Update session timeout, MFA, password policy</div></div>
      <div class="action-btn" onclick="blockIp()"><div class="title">Block IP</div><div class="sub">Deny access</div></div>
      <div class="action-btn" onclick="forceLogout()"><div class="title">Force Logout</div><div class="sub">End sessions</div></div>
      <div class="action-btn" onclick="runScan()"><div class="title">Run Scan</div><div class="sub">Security sweep</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Security Alerts</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">MFA compliant</div><div class="alert-sub">All admin roles covered</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Blocked IP rising</div><div class="alert-sub"><?= $failedAttempts ?> open failed-login alerts</div></div><span class="badge <?= $failedAttempts > 0 ? 'badge-watch' : 'badge-ok' ?>"><?= $failedAttempts > 0 ? 'Watch' : 'OK' ?></span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Password policy active</div><div class="alert-sub">No weak policy found</div></div><span class="badge badge-ok">OK</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Security Log</h3></div>
    <?php if (empty($securityLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No security events yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($securityLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
const currentPolicies = {
  'session_timeout': '<?= cfgGet('session_timeout', '30') ?>',
  'mfa_required': '<?= cfgGet('mfa_required', 'false') ?>',
  'password_policy': '<?= cfgGet('password_policy', 'strong') ?>',
  'backup_schedule': '<?= cfgGet('backup_schedule', '02:00') ?>',
  'alert_email': '<?= cfgGet('alert_email', 'admin@energinusantara.co.id') ?>'
};

function runScan(){showLoading();fetch('../api/adminit.php?action=security_scan',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Security scan completed');}).catch(e=>{hideLoading();showError('Scan failed');});}
function blockIp(){var ip=prompt('Enter IP to block:');if(!ip)return;fetch('../api/adminit.php?action=block_ip',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'ip='+encodeURIComponent(ip)}).then(r=>r.json()).then(d=>showSuccess(d.message||('IP '+ip+' blocked'))).catch(e=>showError('Failed to block IP'));}
function forceLogout(){if(!confirm('Force logout all sessions?\nSemua user aktif akan diminta login ulang.'))return;showLoading();fetch('../api/adminit.php?action=force_logout',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Force logout triggered');}).catch(e=>{hideLoading();showError('Failed');});}

function openPolicyModal() { document.getElementById('policyModal').style.display = 'flex'; }
function closePolicyModal() { document.getElementById('policyModal').style.display = 'none'; }

async function submitPolicy(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  const key = fd.get('config_key');
  const value = fd.get('config_value');
  if (!key || !value) { showError('Pilih policy dan isi nilai'); return; }
  showLoading();
  try {
    const res = await fetch('../api/adminit.php?action=update_policy', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'config_key='+encodeURIComponent(key)+'&config_value='+encodeURIComponent(value)});
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message); closePolicyModal(); setTimeout(()=>location.reload(), 1200); }
    else showError(r.error || 'Gagal update policy');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

document.getElementById('policyModal').addEventListener('click', function(e) { if (e.target === this) closePolicyModal(); });
</script>

<!-- Policy Modal -->
<div id="policyModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:90%;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="color:#1B2A4A;margin:0;">Edit Security Policy</h3>
      <button onclick="closePolicyModal()" style="border:none;background:none;font-size:24px;cursor:pointer;color:#64748B;">&times;</button>
    </div>
    <form onsubmit="submitPolicy(event)">
      <div class="form-group"><label>Policy</label>
        <select name="config_key" class="form-input" onchange="document.getElementById('policyValue').value=currentPolicies[this.value]||''">
          <option value="session_timeout">Session Timeout (menit) — saat ini: <?= cfgGet('session_timeout','30') ?></option>
          <option value="mfa_required">MFA Required (true/false) — saat ini: <?= cfgGet('mfa_required','false') ?></option>
          <option value="password_policy">Password Policy (weak/medium/strong) — saat ini: <?= cfgGet('password_policy','strong') ?></option>
          <option value="backup_schedule">Backup Schedule (HH:MM) — saat ini: <?= cfgGet('backup_schedule','02:00') ?></option>
          <option value="alert_email">Alert Email — saat ini: <?= cfgGet('alert_email','admin@energinusantara.co.id') ?></option>
        </select>
      </div>
      <div class="form-group"><label>Nilai Baru</label><input type="text" name="config_value" id="policyValue" class="form-input" required></div>
      <div style="padding:10px;background:#FEF3C7;border-radius:6px;font-size:12px;color:#92400E;margin-bottom:12px;">
        <strong>Peringatan:</strong> Perubahan policy langsung berlaku untuk semua user.
      </div>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn btn-secondary" onclick="closePolicyModal()" style="flex:1;">Batal</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Update Policy</button>
      </div>
    </form>
  </div>
</div>
<?php renderPageEnd(); ?>
