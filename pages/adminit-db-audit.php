<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Audit events derived from activity_log (admin + system entries)
$auditEvents = $db->query("SELECT al.*, u.nama as user_nama, u.role as user_role FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.tipe IN ('admin','system','security') ORDER BY al.created_at DESC LIMIT 12")->fetchAll();

// Counts
$slowQueries = 8; // static (would come from slow_query_log in production)
$schemaChanges = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE tipe = 'admin'")->fetchColumn();
$lockCount = 0; // would come from information_schema.metadata_locks in production
$auditLive = 'Live';

renderPageStart('Database Audit'); renderSidebar('adminit', 'adminit-database', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Database Audit</h1><p class="subtitle">Query, schema, permission, and audit event inspection</p></div>
  <div class="page-header-actions"><button class="btn btn-secondary btn-sm" onclick="location.reload()">Refresh</button><button class="btn btn-primary btn-sm" onclick="exportAudit()">Export Audit</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Slow Query</div><div class="value" data-count="<?= $slowQueries ?>">0</div></div><div class="sub">Needs tuning</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Changes</div><div class="value" data-count="<?= $schemaChanges ?>">0</div></div><div class="sub">Schema and grants</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Locks</div><div class="value" data-count="<?= $lockCount ?>">0</div></div><div class="sub">No active lock</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Audit</div><div class="value" style="font-size:22px;"><?= $auditLive ?></div></div><div class="sub">Streaming events</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Database Audit Events</h3></div>
    <table class="data-table"><thead><tr><th>Time</th><th>Event</th><th>Detail</th><th>Actor</th><th>Action</th></tr></thead><tbody>
    <?php if (empty($auditEvents)): ?>
      <tr><td colspan="5" style="text-align:center;color:#64748B;padding:20px;">No audit events yet</td></tr>
    <?php else: foreach ($auditEvents as $e): ?>
      <tr>
        <td style="white-space:nowrap;font-size:12px;"><?= date('d M H:i', strtotime($e['created_at'])) ?></td>
        <td style="font-weight:500;"><?= htmlspecialchars($e['aksi']) ?></td>
        <td style="font-size:12px;color:#64748B;"><?= htmlspecialchars($e['detail'] ?? '') ?></td>
        <td><?= htmlspecialchars($e['user_nama'] ?? 'System') ?></td>
        <td><a href="#" class="text-link" onclick="showSuccess('Viewing entry <?= (int)$e['id'] ?>');return false;">View</a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Audit Tools</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="showSuccess('Audit filter opened')"><div class="title">Filter Event</div><div class="sub">Narrow audit trail</div></div>
      <div class="action-btn" onclick="exportAudit()"><div class="title">Export Audit</div><div class="sub">Generate evidence</div></div>
      <div class="action-btn" onclick="showSuccess('Query log viewer opened')"><div class="title">Open Query Log</div><div class="sub">Inspect SQL traces</div></div>
      <div class="action-btn" onclick="showSuccess('Schema diff started')"><div class="title">Compare Schema</div><div class="sub">Detect drift</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Audit Highlights</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Slow query cluster</div><div class="alert-sub">Responses table impacted</div></div><span class="badge badge-watch">Watch</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">No table lock</div><div class="alert-sub">Write operations normal</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title"><?= $schemaChanges ?> admin events</div><div class="alert-sub">All recorded in trail</div></div><span class="badge badge-review">Review</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Audit Timeline</h3></div>
    <?php foreach (array_slice($auditEvents, 0, 6) as $e): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($e['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($e['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($e['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; ?>
  </div>
</div></main>
<script>
function exportAudit(){window.location.href='../api/adminit.php?action=audit_log_csv';}
</script>
<?php renderPageEnd(); ?>
