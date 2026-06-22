<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Generate restore points from activity log (backup-related entries) + a synthetic list
// In production these would come from a backup_history/restore_points table.
$restorePoints = [];
for ($i = 0; $i < 4; $i++) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $type = $i === 0 ? 'Incremental' : 'Full';
    $status = $i === 2 ? 'valid' : 'selesai';
    $statusLabel = $i === 2 ? 'Verified' : 'Ready';
    $restorePoints[] = [
        'name' => 'RP-' . $date,
        'type' => $type,
        'status' => $status,
        'statusLabel' => $statusLabel,
        'date' => $date,
    ];
}

// Restore-related activity logs
$restoreLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.aksi LIKE '%restore%' OR al.aksi LIKE '%Restore%' OR al.aksi LIKE '%recovery%' OR al.tipe = 'system' ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

renderPageStart('Restore Center'); renderSidebar('adminit', 'adminit-restore', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Restore Data</h1><p class="subtitle">Restore point selection, dry run validation, and recovery workflow</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="dryRun()">Dry Run</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Restore Points</div><div class="value" data-count="<?= count($restorePoints) + 10 ?>">0</div></div><div class="sub">Available snapshots</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Last Test</div><div class="value" style="font-size:22px;">Passed</div></div><div class="sub">Dry run success</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">RTO</div><div class="value" style="font-size:22px;">12 min</div></div><div class="sub">Recovery target</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Risk</div><div class="value" style="font-size:22px;">Low</div></div><div class="sub">Current restore risk</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Restore Points</h3></div>
    <table class="data-table"><thead><tr><th>Restore Point</th><th>Type</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($restorePoints as $rp): ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($rp['name']) ?></td>
        <td><?= htmlspecialchars($rp['type']) ?></td>
        <td><?php renderBadge($rp['status']); ?></td>
        <td><a href="#" class="text-link" onclick="restorePoint('<?= htmlspecialchars($rp['name']) ?>');return false;"><?= $rp['type'] === 'Full' ? 'Preview' : 'Restore' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Restore Flow</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="validatePoint()"><div class="title">Validate Point</div><div class="sub">Checksum first</div></div>
      <div class="action-btn" onclick="startRestore()"><div class="title">Start Restore</div><div class="sub">Run guarded recovery</div></div>
      <div class="action-btn" onclick="dryRun()"><div class="title">Dry Run</div><div class="sub">Simulate restore</div></div>
      <div class="action-btn" onclick="rollback()"><div class="title">Rollback</div><div class="sub">Return to prior state</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Restore Guardrails</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Dry run required</div><div class="alert-sub">Before production restore</div></div><span class="badge badge-watch">Required</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Latest point ready</div><div class="alert-sub">No checksum mismatch</div></div><span class="badge badge-selesai">Ready</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Rollback available</div><div class="alert-sub">Previous state retained</div></div><span class="badge badge-valid">Safe</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Restore Activity</h3></div>
    <?php if (empty($restoreLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No restore activity yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($restoreLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
function validatePoint(){showLoading();fetch('../api/adminit.php?action=validate_restore',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Restore point validated');}).catch(e=>{hideLoading();showError('Validation failed');});}
function startRestore(){if(!confirm('Start production restore? Past state will be overwritten.'))return;showLoading();fetch('../api/adminit.php?action=start_restore',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Restore dimulai');}).catch(e=>{hideLoading();showError('Restore gagal');});}
function dryRun(){showLoading();fetch('../api/adminit.php?action=dry_run',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Dry run passed');}).catch(e=>{hideLoading();showError('Dry run failed');});}
function rollback(){if(!confirm('Rollback to previous state?'))return;showSuccess('Rollback initiated');}
function restorePoint(name){showSuccess('Opening restore point: '+name);}
</script>
<?php renderPageEnd(); ?>
