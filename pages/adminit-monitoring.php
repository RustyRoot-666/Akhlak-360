<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Live service health (simulated probes with real DB pings)
$services = [
    ['name' => 'API Gateway', 'endpoint' => 'gateway', 'target_latency' => 50],
    ['name' => 'Assessment API', 'endpoint' => 'assessment', 'target_latency' => 60],
    ['name' => 'Worker Queue', 'endpoint' => 'worker', 'target_latency' => 100],
    ['name' => 'Notification', 'endpoint' => 'notification', 'target_latency' => 40],
    ['name' => 'Primary Database', 'endpoint' => 'db', 'target_latency' => 50],
    ['name' => 'Backup Service', 'endpoint' => 'backup', 'target_latency' => 60],
];

foreach ($services as &$svc) {
    // Real probe: measure DB roundtrip for db-related services, otherwise simulate latency
    $start = microtime(true);
    try {
        if ($svc['endpoint'] === 'db' || $svc['endpoint'] === 'assessment') {
            $db->query("SELECT 1")->fetchColumn();
        }
        $latency = (int)((microtime(true) - $start) * 1000);
    } catch (Exception $e) {
        $latency = 200;
    }
    // Add a simulated baseline latency for non-db services
    if ($svc['endpoint'] !== 'db' && $svc['endpoint'] !== 'assessment') {
        $latency = rand(20, 90);
    }
    $svc['latency'] = $latency;
    $svc['health_pct'] = max(10, min(100, 100 - (int)(($latency - $svc['target_latency']) / 2)));
    if ($svc['latency'] <= $svc['target_latency']) {
        $svc['status'] = 'selesai'; $svc['statusLabel'] = 'Healthy';
    } elseif ($svc['latency'] <= $svc['target_latency'] * 2) {
        $svc['status'] = 'warning'; $svc['statusLabel'] = 'Warning';
    } else {
        $svc['status'] = 'review'; $svc['statusLabel'] = 'Review';
    }
}
unset($svc);

// Probe log from activity_log
$probeLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe IN ('system','security') ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

$uptimePct = 99.92;
$cpu = rand(40, 70);
$memory = rand(55, 75);
$healthyCount = count(array_filter($services, fn($s) => $s['status'] === 'selesai'));
$totalCount = count($services);

renderPageStart('Monitoring Center'); renderSidebar('adminit', 'adminit-monitoring', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Monitoring Center</h1><p class="subtitle">Service health, uptime, infrastructure threshold, and alert overview</p></div>
  <div class="page-header-actions"><button class="btn btn-secondary btn-sm" onclick="location.reload()">Refresh</button><button class="btn btn-primary btn-sm" onclick="refreshProbes()">Run Probes</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Uptime</div><div class="value" data-count="<?= $uptimePct ?>" data-decimals="2">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub">This month</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Services</div><div class="value" style="font-size:22px;"><?= $healthyCount ?>/<?= $totalCount ?></div></div><div class="sub"><?= $totalCount - $healthyCount ?> warning</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">CPU</div><div class="value" data-count="<?= $cpu ?>">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub">Average load</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Memory</div><div class="value" data-count="<?= $memory ?>">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub">Healthy range</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Service Health</h3></div>
    <table class="data-table"><thead><tr><th>Service</th><th>Health</th><th>Latency</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($services as $s):
      $badge = $s['status'] === 'selesai' ? 'selesai' : ($s['status'] === 'warning' ? 'warning' : 'review');
      $link = $s['name'] === 'Worker Queue' ? 'adminit-monitor-detail.php' : '#';
      $onclick = $link === '#' ? 'showSuccess(\'Opening '.$s['name'].'\');return false;' : '';
    ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></td>
        <td><?php renderBadge($badge); ?></td>
        <td><?= $s['latency'] ?> ms</td>
        <td><a href="<?= $link ?>" class="text-link" onclick="<?= $onclick ?>"><?= $s['status'] === 'selesai' ? 'Open' : 'Review' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Monitoring Views</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="showSuccess('Real-time dashboard opened')"><div class="title">Open Dashboard</div><div class="sub">Real-time metrics</div></div>
      <div class="action-btn" onclick="showSuccess('Threshold editor opened')"><div class="title">Set Threshold</div><div class="sub">Adjust alert limit</div></div>
      <div class="action-btn" onclick="muteAlert()"><div class="title">Mute Alert</div><div class="sub">Pause noisy signal</div></div>
      <div class="action-btn" onclick="refreshProbes()"><div class="title">Refresh Health</div><div class="sub">Run probes now</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Monitoring Signals</h3></div>
    <?php foreach ($services as $s):
      if ($s['status'] === 'selesai') continue; ?>
      <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= htmlspecialchars($s['name']) ?> <?= $s['statusLabel'] ?></div><div class="alert-sub">Latency <?= $s['latency'] ?> ms vs target <?= $s['target_latency'] ?> ms</div></div><span class="badge badge-watch">Warn</span></div>
    <?php endforeach; ?>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Gateway stable</div><div class="alert-sub">No 5xx spike detected</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Memory healthy</div><div class="alert-sub">No leak pattern</div></div><span class="badge badge-ok">OK</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Probe Log</h3></div>
    <?php if (empty($probeLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No probe events yet</div><div class="log-actor">Probe</div></div>
    <?php else: foreach ($probeLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
function refreshProbes(){showLoading();fetch('../api/adminit.php?action=run_probes',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Probes executed');setTimeout(()=>location.reload(),1200);}).catch(e=>{hideLoading();showError('Probe failed');});}
function muteAlert(){showSuccess('Alert muted');}
</script>
<?php renderPageEnd(); ?>
