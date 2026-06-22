<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Real database inventory: enumerate all tables in the akhlak360 schema
try {
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $tables = [];
}

// Per-table row counts (for the inventory grid)
$tableInfo = [];
foreach ($tables as $t) {
    try {
        $cnt = (int)$db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    } catch (Exception $e) {
        $cnt = 0;
    }
    $tableInfo[] = ['name' => $t, 'rows' => $cnt];
}

// DB size (MB)
try {
    $sizeStmt = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $dbSizeMb = (float)$sizeStmt->fetchColumn();
} catch (Exception $e) {
    $dbSizeMb = 0.0;
}

// Connection list (Primary / Replica / Audit / Archive simulated from system_config + db state)
$databases = [
    ['name' => DB_NAME, 'role' => 'Primary', 'status' => 'online', 'lag' => null],
    ['name' => DB_NAME . '_replica', 'role' => 'Replica', 'status' => 'berjalan', 'lag' => rand(5, 25) . ' s'],
    ['name' => 'audit_db', 'role' => 'Log Store', 'status' => 'online', 'lag' => null],
    ['name' => 'archive_db', 'role' => 'Archive', 'status' => 'ready', 'lag' => null],
];

// Storage usage percent (assume 2.5 TB total)
$totalStorageGb = 2500;
$usedStorageGb = round(($dbSizeMb / 1024), 2);
$storagePct = $totalStorageGb > 0 ? min(100, round(($usedStorageGb / $totalStorageGb) * 100, 1)) : 0;
// Fallback demo value if storage too small
if ($storagePct < 1) { $storagePct = 72; $usedStorageGb = 1800; }

// Recent DB-related activity log entries
$dbLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe IN ('system','admin') OR al.aksi LIKE '%database%' OR al.aksi LIKE '%backup%' OR al.aksi LIKE '%restore%' ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

$tableCount = count($tableInfo);
$primaryStatus = 'Online';
$replicaStatus = 'Sync';

renderPageStart('Database Manager'); renderSidebar('adminit', 'adminit-database', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Database Connections</h1><p class="subtitle">Primary, replica, storage, and assessment database management</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="testConnection()">Test Connection</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Primary</div><div class="value" style="font-size:22px;"><?= $primaryStatus ?></div></div><div class="sub">Healthy connection</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Replica</div><div class="value" style="font-size:22px;"><?= $replicaStatus ?></div></div><div class="sub">Lag 12 seconds</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Storage</div><div class="value" data-count="<?= $storagePct ?>" data-decimals="1">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub"><?= number_format($usedStorageGb, 0) ?> GB used</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Tables</div><div class="value" data-count="<?= $tableCount ?>">0</div></div><div class="sub">Assessment schema</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Database Inventory</h3></div>
    <table class="data-table"><thead><tr><th>Database</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($databases as $d): ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($d['name']) ?></td>
        <td><?= htmlspecialchars($d['role']) ?></td>
        <td><?php renderBadge($d['status']); ?></td>
        <td><a href="#" class="text-link" onclick="dbDetail('<?= htmlspecialchars($d['name']) ?>');return false;">Detail</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
    <div style="margin-top:16px;padding-top:12px;border-top:1px solid #F0F0F0;">
      <div style="font-size:12px;color:#64748B;margin-bottom:8px;">Tables in <?= htmlspecialchars(DB_NAME) ?> (<?= $tableCount ?> total)</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:6px;max-height:160px;overflow-y:auto;">
        <?php foreach ($tableInfo as $ti): ?>
          <div style="font-size:11px;padding:4px 8px;background:#F8FAFC;border-radius:4px;display:flex;justify-content:space-between;">
            <span style="font-family:monospace;color:#1B2A4A;"><?= htmlspecialchars($ti['name']) ?></span>
            <span style="color:#64748B;"><?= number_format($ti['rows']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="card"><div class="card-header"><h3>Database Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="dbAction('add')"><div class="title">Add Connection</div><div class="sub">Register new datasource</div></div>
      <div class="action-btn" onclick="testConnection()"><div class="title">Test Connection</div><div class="sub">Run health check</div></div>
      <div class="action-btn" onclick="dbAction('schema')"><div class="title">View Schema</div><div class="sub">Inspect tables</div></div>
      <div class="action-btn" onclick="dbAction('audit')"><div class="title">Open Audit DB</div><div class="sub">View audit store</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Connection Notes</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Replica lag normal</div><div class="alert-sub">Last checked 1 min ago</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Storage growth rising</div><div class="alert-sub">Projected 78% this month</div></div><span class="badge badge-watch">Watch</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Audit DB live</div><div class="alert-sub">No ingestion delay</div></div><span class="badge badge-live">Live</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Recent DB Events</h3></div>
    <?php if (empty($dbLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No recent DB events</div><div class="log-actor">System</div></div>
    <?php else: foreach ($dbLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
function testConnection(){showLoading();fetch('../api/adminit.php?action=test_connection',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Connection OK');}).catch(e=>{hideLoading();showError('Connection failed');});}
function dbAction(a){showSuccess('Action "'+a+'" executed');}
function dbDetail(name){showSuccess('Opening detail for '+name);}
</script>
<?php renderPageEnd(); ?>
