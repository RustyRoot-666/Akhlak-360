<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Filters
$tipeF = $_GET['tipe'] ?? '';
$actorF = $_GET['actor'] ?? '';
$searchQ = $_GET['search'] ?? '';

$sql = "SELECT al.*, u.nama as user_nama, u.role as user_role, u.email as user_email FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
$params = [];
if ($tipeF) { $sql .= " AND al.tipe = ?"; $params[] = $tipeF; }
if ($actorF) {
    if ($actorF === 'system') $sql .= " AND al.user_id IS NULL";
    else { $sql .= " AND (u.role = ? OR u.nama = ?)"; $params[] = $actorF; $params[] = $actorF; }
}
if ($searchQ) { $sql .= " AND (al.aksi LIKE ? OR al.detail LIKE ?)"; $params[] = "%$searchQ%"; $params[] = "%$searchQ%"; }
$sql .= " ORDER BY al.created_at DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Stat counts
$events24h = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
$todayCount = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$activeAdmins = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND user_id IS NOT NULL")->fetchColumn();
$flaggedCount = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE tipe IN ('security','admin') AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

// Distinct types for filter dropdown
$types = $db->query("SELECT DISTINCT tipe FROM activity_log ORDER BY tipe")->fetchAll(PDO::FETCH_COLUMN);

renderPageStart('Activity Log'); renderSidebar('adminit', 'adminit-activity-log', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Activity Log</h1><p class="subtitle">Chronological audit trail of admin, security, system, and data changes</p></div>
  <div class="page-header-actions"><button class="btn btn-secondary btn-sm" onclick="exportCSV()">Export CSV</button><button class="btn btn-primary btn-sm" onclick="location.reload()">Refresh</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Events</div><div class="value" data-count="<?= $events24h ?>">0</div></div><div class="sub">Last 24 hours</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Admins</div><div class="value" data-count="<?= $activeAdmins ?>">0</div></div><div class="sub">Active today</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Today</div><div class="value" data-count="<?= $todayCount ?>">0</div></div><div class="sub">Total events</div></div>
  <div class="stat-card"><div class="accent" style="background:#C62828;"></div><div><div class="label">Flagged</div><div class="value" data-count="<?= $flaggedCount ?>">0</div></div><div class="sub">Security + admin</div></div>
</div>
<div class="card" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    <div style="flex:1;min-width:180px;"><label style="font-size:11px;color:#64748B;">Type</label>
      <select name="tipe" class="form-input">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?><option value="<?= htmlspecialchars($t) ?>" <?= $tipeF === $t ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($t)) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1;min-width:180px;"><label style="font-size:11px;color:#64748B;">Actor</label>
      <select name="actor" class="form-input">
        <option value="">All actors</option>
        <option value="system" <?= $actorF === 'system' ? 'selected' : '' ?>>System</option>
        <option value="karyawan" <?= $actorF === 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
        <option value="manager" <?= $actorF === 'manager' ? 'selected' : '' ?>>Manager</option>
        <option value="adminhrd" <?= $actorF === 'adminhrd' ? 'selected' : '' ?>>Admin HRD</option>
        <option value="adminit" <?= $actorF === 'adminit' ? 'selected' : '' ?>>Admin IT</option>
      </select>
    </div>
    <div style="flex:2;min-width:240px;"><label style="font-size:11px;color:#64748B;">Search</label>
      <input type="text" name="search" class="form-input" placeholder="Search activity or detail..." value="<?= htmlspecialchars($searchQ) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
    <a href="adminit-activity-log.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Activity Events (<?= count($logs) ?>)</h3></div>
    <table class="data-table"><thead><tr><th>Time</th><th>Activity</th><th>Actor</th><th>Type</th><th>Action</th></tr></thead><tbody>
    <?php if (empty($logs)): ?>
      <tr><td colspan="5" style="text-align:center;color:#64748B;padding:20px;">No activity log entries</td></tr>
    <?php else: foreach ($logs as $l): ?>
      <tr>
        <td style="white-space:nowrap;font-size:12px;"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
        <td style="font-weight:500;"><?= htmlspecialchars($l['aksi']) ?><br><?php if ($l['detail']): ?><span style="font-size:11px;color:#64748B;"><?= htmlspecialchars($l['detail']) ?></span><?php endif; ?></td>
        <td><?= htmlspecialchars($l['user_nama'] ?? 'System') ?><?php if ($l['user_role']): ?><br><span style="font-size:11px;color:#64748B;"><?= htmlspecialchars(ucfirst($l['user_role'])) ?></span><?php endif; ?></td>
        <td><?php renderBadge($l['tipe'] === 'system' ? 'berjalan' : ($l['tipe'] === 'security' ? 'high' : ($l['tipe'] === 'penilaian' ? 'selesai' : 'warning'))); ?> <?= htmlspecialchars(ucfirst($l['tipe'])) ?></td>
        <td><a href="#" class="text-link" onclick="showSuccess('Opening log entry <?= (int)$l['id'] ?>');return false;">Detail</a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Log Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="location.reload()"><div class="title">Refresh Logs</div><div class="sub">Pull latest events</div></div>
      <div class="action-btn" onclick="exportCSV()"><div class="title">Export CSV</div><div class="sub">Download evidence</div></div>
      <div class="action-btn" onclick="showSuccess('Pin event dialog opened')"><div class="title">Pin Event</div><div class="sub">Mark important</div></div>
      <div class="action-btn" onclick="showSuccess('Timeline view opened')"><div class="title">Open Timeline</div><div class="sub">Trace sequence</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Log Highlights</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= $flaggedCount ?> flagged events</div><div class="alert-sub">Security review queue (24h)</div></div><span class="badge <?= $flaggedCount > 0 ? 'badge-high' : 'badge-ok' ?>"><?= $flaggedCount > 0 ? 'Flag' : 'OK' ?></span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= $todayCount ?> events today</div><div class="alert-sub">Total activity volume</div></div><span class="badge badge-berjalan">Active</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= $activeAdmins ?> admins active</div><div class="alert-sub">Distinct admin users</div></div><span class="badge badge-ok">OK</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Recent Events</h3></div>
    <?php foreach (array_slice($logs, 0, 6) as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; ?>
  </div>
</div></main>
<script>
function exportCSV(){window.location.href='../api/adminit.php?action=activity_log_csv<?php
  $params = [];
  if ($tipeF) $params[] = 'tipe=' . urlencode($tipeF);
  if ($actorF) $params[] = 'actor=' . urlencode($actorF);
  if ($searchQ) $params[] = 'search=' . urlencode($searchQ);
  echo $params ? '&' . implode('&', $params) : '';
?>';}
</script>
<?php renderPageEnd(); ?>
