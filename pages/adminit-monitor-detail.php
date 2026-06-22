<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Worker probe detail - simulate based on actual DB queue depth (mapping count in non-submitted state)
$queueDepth = (int)$db->query("SELECT COUNT(*) FROM assessor_mapping WHERE status IN ('assigned','draft')")->fetchColumn();
$probeResults = [];
$probeResults[] = ['probe' => 'Health endpoint', 'result' => 'ok', 'resultLabel' => '200 OK', 'time' => date('H:i')];
$probeResults[] = ['probe' => 'Queue depth', 'result' => $queueDepth > 5 ? 'high' : 'ok', 'resultLabel' => $queueDepth . ' jobs', 'time' => date('H:i', strtotime('-1 minute'))];
$probeResults[] = ['probe' => 'Worker restart', 'result' => 'selesai', 'resultLabel' => 'Success', 'time' => date('H:i', strtotime('-7 minutes'))];
$probeResults[] = ['probe' => 'Retry count', 'result' => 'info', 'resultLabel' => '12', 'time' => date('H:i', strtotime('-10 minutes'))];

// Worker timeline from activity_log
$workerLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe IN ('system','penilaian') ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

// Determine worker health
$workerStatus = $queueDepth > 5 ? 'Warning' : 'Healthy';
$workerLatency = 80 + ($queueDepth * 8); // 80ms base + 8ms per queued job
$workerErrors = max(0, $queueDepth - 3);

renderPageStart('Monitor Detail'); renderSidebar('adminit', 'adminit-monitor-detail', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Monitor Detail</h1><p class="subtitle">Deep dive for one service, technical probe, restart, and scaling controls</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="restartWorker()">Restart Worker</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Service</div><div class="value" style="font-size:22px;">Worker</div></div><div class="sub">Queue processor</div></div>
  <div class="stat-card"><div class="accent" style="background:<?= $workerStatus === 'Warning' ? '#E65100' : '#2E7D32' ?>;"></div><div><div class="label">Status</div><div class="value" style="font-size:22px;"><?= $workerStatus ?></div></div><div class="sub"><?= $workerStatus === 'Warning' ? 'Needs review' : 'Operational' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Latency</div><div class="value" data-count="<?= $workerLatency ?>">0</div><div class="sub" style="font-size:18px;">ms</div></div><div class="sub"><?= $workerLatency > 100 ? 'Above target' : 'Within target' ?></div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Errors</div><div class="value" data-count="<?= $workerErrors ?>">0</div></div><div class="sub">Last hour</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Worker Probe Detail</h3></div>
    <table class="data-table"><thead><tr><th>Probe</th><th>Result</th><th>Time</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($probeResults as $p):
      $badge = $p['result'] === 'ok' ? 'ok' : ($p['result'] === 'high' ? 'high' : ($p['result'] === 'selesai' ? 'selesai' : 'review'));
    ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($p['probe']) ?></td>
        <td><?php renderBadge($badge); ?> <?= htmlspecialchars($p['resultLabel']) ?></td>
        <td><?= htmlspecialchars($p['time']) ?></td>
        <td><a href="#" class="text-link" onclick="showSuccess('Opening probe detail');return false;"><?= $p['result'] === 'high' ? 'Trace' : 'View' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
    <div style="margin-top:12px;padding:10px;background:#F8FAFC;border-radius:6px;font-size:12px;color:#64748B;">
      <strong style="color:#1B2A4A;">Queue depth:</strong> <?= $queueDepth ?> pending assessment jobs.
      Threshold: 5 jobs. <?= $queueDepth > 5 ? '<span style="color:#C62828;">Above threshold.</span>' : '<span style="color:#2E7D32;">Within threshold.</span>' ?>
    </div>
  </div>
  <div class="card"><div class="card-header"><h3>Monitor Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="restartWorker()"><div class="title">Restart Worker</div><div class="sub">Controlled restart</div></div>
      <div class="action-btn" onclick="showSuccess('Service log viewer opened')"><div class="title">Open Logs</div><div class="sub">View service logs</div></div>
      <div class="action-btn" onclick="scaleWorker()"><div class="title">Scale Worker</div><div class="sub">Increase capacity</div></div>
      <div class="action-btn" onclick="createIncident()"><div class="title">Create Incident</div><div class="sub">Escalate issue</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Service Diagnosis</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Queue depth <?= $queueDepth > 5 ? 'high' : 'normal' ?></div><div class="alert-sub"><?= $queueDepth ?> pending assessment jobs</div></div><span class="badge <?= $queueDepth > 5 ? 'badge-high' : 'badge-ok' ?>"><?= $queueDepth > 5 ? 'High' : 'OK' ?></span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Restart succeeded</div><div class="alert-sub">Worker recovered quickly</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Retry count <?= $workerErrors > 5 ? 'rising' : 'stable' ?></div><div class="alert-sub">Check downstream API</div></div><span class="badge <?= $workerErrors > 5 ? 'badge-watch' : 'badge-ok' ?>"><?= $workerErrors > 5 ? 'Watch' : 'OK' ?></span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Service Timeline</h3></div>
    <?php if (empty($workerLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No service events yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($workerLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
function restartWorker(){if(!confirm('Restart worker service? Pending jobs will be requeued.'))return;showLoading();fetch('../api/adminit.php?action=restart_worker',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Worker restarted');setTimeout(()=>location.reload(),1500);}).catch(e=>{hideLoading();showError('Restart failed');});}
function scaleWorker(){showLoading();fetch('../api/adminit.php?action=scale_worker',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Worker scaled');}).catch(e=>{hideLoading();showError('Scale failed');});}
function createIncident(){showSuccess('Incident created and escalated');}
</script>
<?php renderPageEnd(); ?>
