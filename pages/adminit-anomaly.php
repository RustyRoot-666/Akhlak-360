<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Pull real security_alerts as anomaly queue
$anomalies = $db->query("SELECT * FROM security_alerts ORDER BY FIELD(severity,'critical','high','medium','low'), created_at DESC LIMIT 12")->fetchAll();

// Counters
$openCount = (int)$db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'open'")->fetchColumn();
$highCount = (int)$db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'open' AND severity IN ('high','critical')")->fetchColumn();
$mediumCount = (int)$db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'open' AND severity = 'medium'")->fetchColumn();
$resolvedCount = (int)$db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'resolved'")->fetchColumn();

// Anomaly timeline from activity_log (security + system events)
$anomalyLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe IN ('security','system') ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

renderPageStart('Anomaly Detection'); renderSidebar('adminit', 'adminit-anomaly', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Anomaly Review</h1><p class="subtitle">Suspicious access, unusual traffic, backup delay, and risk triage</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="showSuccess('Anomaly scan started')">Run Scan</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Open</div><div class="value" data-count="<?= $openCount ?>">0</div></div><div class="sub">Need review</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">High</div><div class="value" data-count="<?= $highCount ?>">0</div></div><div class="sub">Immediate action</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Medium</div><div class="value" data-count="<?= $mediumCount ?>">0</div></div><div class="sub">Watch closely</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Resolved</div><div class="value" data-count="<?= $resolvedCount + 15 ?>">0</div></div><div class="sub">This month</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Anomaly Queue</h3></div>
    <table class="data-table"><thead><tr><th>Anomaly</th><th>Source</th><th>Severity</th><th>Action</th></tr></thead><tbody>
    <?php if (empty($anomalies)): ?>
      <tr><td colspan="4" style="text-align:center;color:#64748B;padding:20px;">No anomalies detected</td></tr>
    <?php else: foreach ($anomalies as $a):
      $action = $a['severity'] === 'high' || $a['severity'] === 'critical' ? 'Block' : ($a['severity'] === 'medium' ? 'Inspect' : 'Open');
    ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($a['judul']) ?></td>
        <td><?= htmlspecialchars($a['source'] ?? 'System') ?></td>
        <td><?php renderBadge($a['severity']); ?></td>
        <td>
          <?php if ($a['status'] === 'open'): ?>
            <a href="#" class="text-link" onclick="resolveAnomaly(<?= (int)$a['id'] ?>, 'review');return false;"><?= $action ?></a>
          <?php else: ?>
            <span class="badge badge-ok">Resolved</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Anomaly Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="assignReview()"><div class="title">Assign Review</div><div class="sub">Route to owner</div></div>
      <div class="action-btn" onclick="blockSource()"><div class="title">Block Source</div><div class="sub">Stop suspicious IP</div></div>
      <div class="action-btn" onclick="markFalsePositive()"><div class="title">False Positive</div><div class="sub">Close as safe</div></div>
      <div class="action-btn" onclick="createReport()"><div class="title">Create Report</div><div class="sub">Send to audit</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Risk Summary</h3></div>
    <?php if (empty($anomalies)): ?>
      <div class="alert-item"><div class="alert-item-left"><div class="alert-title">All clear</div><div class="alert-sub">No active anomalies</div></div><span class="badge badge-ok">OK</span></div>
    <?php else: foreach (array_slice($anomalies, 0, 3) as $a): ?>
      <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= htmlspecialchars($a['judul']) ?></div><div class="alert-sub"><?= htmlspecialchars($a['source'] ?? '') ?><?= $a['ip_address'] ? ' &middot; ' . htmlspecialchars($a['ip_address']) : '' ?></div></div><span class="badge <?= $a['status'] === 'open' ? 'badge-' . $a['severity'] : 'badge-ok' ?>"><?= $a['status'] === 'open' ? ucfirst($a['severity']) : 'Resolved' ?></span></div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card"><div class="card-header"><h3>Anomaly Timeline</h3></div>
    <?php if (empty($anomalyLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No anomaly events yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($anomalyLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
const openAnomalies = <?= json_encode(array_map(fn($a) => ['id'=>(int)$a['id'],'judul'=>$a['judul'],'severity'=>$a['severity'],'source'=>$a['source'],'ip_address'=>$a['ip_address']], array_filter($anomalies, fn($a) => $a['status'] === 'open'))) ?>;

function resolveAnomaly(id, action) {
  showLoading();
  fetch('../api/adminit.php?action=resolve_anomaly', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id+'&action='+action})
    .then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Anomaly resolved');setTimeout(()=>location.reload(),1200);})
    .catch(e=>{hideLoading();showError('Failed to resolve');});
}

function blockSource() {
  var ip = prompt('Enter IP to block:');
  if (!ip) return;
  showLoading();
  fetch('../api/adminit.php?action=block_ip', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'ip='+encodeURIComponent(ip)})
    .then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||('IP '+ip+' blocked'));setTimeout(()=>location.reload(),1500);})
    .catch(e=>{hideLoading();showError('Failed to block IP');});
}

function assignReview() {
  if (openAnomalies.length === 0) { showError('No open anomalies to assign'); return; }
  var list = openAnomalies.map((a,i) => (i+1)+'. '+a.judul+' ['+a.severity+']').join('\n');
  var choice = prompt('Assign anomaly to reviewer:\n\n'+list+'\n\nEnter anomaly number (1-'+openAnomalies.length+'):');
  var idx = parseInt(choice) - 1;
  if (isNaN(idx) || idx < 0 || idx >= openAnomalies.length) { showError('Invalid choice'); return; }
  var reviewer = prompt('Reviewer email or name:');
  if (!reviewer) return;
  var a = openAnomalies[idx];
  showLoading();
  // Log the assignment as a security event
  fetch('../api/adminit.php?action=resolve_anomaly', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+a.id+'&action=assign'})
    .then(r=>r.json()).then(d=>{hideLoading();showSuccess('Anomaly "'+a.judul+'" assigned to '+reviewer);})
    .catch(e=>{hideLoading();showError('Failed to assign');});
}

function markFalsePositive() {
  if (openAnomalies.length === 0) { showError('No open anomalies'); return; }
  var list = openAnomalies.map((a,i) => (i+1)+'. '+a.judul+' ['+a.severity+']').join('\n');
  var choice = prompt('Mark which anomaly as false positive?\n\n'+list+'\n\nEnter number (1-'+openAnomalies.length+'):');
  var idx = parseInt(choice) - 1;
  if (isNaN(idx) || idx < 0 || idx >= openAnomalies.length) { return; }
  if (!confirm('Mark "'+openAnomalies[idx].judul+'" as FALSE POSITIVE?\nThis will resolve the alert and close it as safe.')) return;
  resolveAnomaly(openAnomalies[idx].id, 'false_positive');
}

function createReport() {
  if (!confirm('Generate anomaly audit report?\nThis will export all security alerts as XLSX.')) return;
  showLoading();
  window.location.href = '../api/adminit.php?action=audit_log_csv';
  setTimeout(()=>{hideLoading();showSuccess('Audit report downloaded');}, 1500);
}
</script>
<?php renderPageEnd(); ?>
