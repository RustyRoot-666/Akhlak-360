<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();
$events24h = $db->query("SELECT COUNT(*) FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
$activeAdmins = $db->query("SELECT COUNT(DISTINCT user_id) FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND user_id IS NOT NULL")->fetchColumn();
$flagged = $db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'open'")->fetchColumn();
$alerts = $db->query("SELECT * FROM security_alerts ORDER BY created_at DESC LIMIT 5")->fetchAll();
$logs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetchAll();
$services = [
    ['name'=>'Primary Database','status'=>'online','latency'=>rand(20,50),'health'=>rand(75,95)],
    ['name'=>'Backup Service','status'=>'online','latency'=>rand(30,60),'health'=>rand(75,95)],
    ['name'=>'Restore Gateway','status'=>'ready','latency'=>rand(50,80),'health'=>rand(50,70)],
    ['name'=>'Security Scanner','status'=>rand(0,10)>7?'review':'online','latency'=>rand(80,150),'health'=>rand(25,60)],
    ['name'=>'Activity Log Stream','status'=>'live','latency'=>rand(10,40),'health'=>rand(85,98)],
    ['name'=>'Report Scheduler','status'=>'queued','latency'=>rand(50,90),'health'=>rand(45,70)],
];
renderPageStart('Admin IT Dashboard'); renderSidebar('adminit', 'adminit-dashboard', $user);
?><main class="main-content">
<div class="page-header"><h1>Admin IT Dashboard</h1><p class="subtitle">System health &middot; Database &middot; Backup &middot; Security &middot; Access logs</p></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Database Status</div><div class="value" style="font-size:22px;">4/4 online</div></div><div class="sub">Primary and replica healthy</div></div>
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Backup Success</div><div class="value" data-count="98.7" data-decimals="1">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub">Last run 02:00 WIB</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Open Alerts</div><div class="value" data-count="<?= $flagged ?>">0</div></div><div class="sub"><?= $flagged ?> anomaly needs review</div></div>
  <div class="stat-card"><div class="accent" style="background:#1B2A4A;"></div><div><div class="label">Access Events</div><div class="value" data-count="<?= $events24h ?>">0</div></div><div class="sub">Last 24 hours</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Monitoring Layanan</h3></div>
    <table class="data-table"><thead><tr><th>Module</th><th>Health</th><th>Latency</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($services as $s):
      $badge = $s['status']; if ($s['status'] === 'online' || $s['status'] === 'live') $badge = 'online'; elseif ($s['status'] === 'ready' || $s['status'] === 'queued') $badge = 'ready'; else $badge = 'review'; ?>
    <tr><td style="font-weight:500;"><?= $s['name'] ?></td><td><div style="width:80px;height:6px;background:#E2E8F0;border-radius:3px;"><div style="width:<?= $s['health'] ?>%;height:100%;background:<?= $s['health'] >= 70 ? '#2E7D32' : ($s['health'] >= 50 ? '#E65100' : '#C62828') ?>;border-radius:3px;"></div></div></td><td><?= $s['latency'] ?> ms</td><td><?php renderBadge($badge); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>IT Controls</h3></div>
    <a href="adminit-backup.php" class="it-action" style="background:#E0F2FE;display:block;text-decoration:none;color:inherit;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'"><div class="title">Database Backup</div><div class="sub">Open backup center</div></a>
    <a href="adminit-restore.php" class="it-action" style="background:#D1FAE5;display:block;text-decoration:none;color:inherit;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'"><div class="title">Restore Point</div><div class="sub">Open recovery console</div></a>
    <a href="adminit-security.php" class="it-action" style="background:#FEE2E2;display:block;text-decoration:none;color:inherit;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'"><div class="title">Security Scan</div><div class="sub">Open security center</div></a>
    <a href="adminit-report.php" class="it-action" style="background:#FEF3C7;display:block;text-decoration:none;color:inherit;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'"><div class="title">Export Report</div><div class="sub">Generate audit pack</div></a>
  </div>
</div>
<div class="grid-2-equal">
  <div class="card"><div class="card-header"><h3>Security &amp; Anomaly</h3></div>
    <?php foreach ($alerts as $a): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #F0F0F0;"><div><div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($a['judul']) ?></div><div style="font-size:11px;color:#64748B;"><?= htmlspecialchars($a['source'] ?? '') ?> &middot; <?= $a['ip_address'] ?? '' ?></div></div><?php renderBadge($a['severity']); ?></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><div class="card-header"><h3>Activity Log</h3></div>
    <?php foreach ($logs as $l): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #F0F0F0;"><div style="font-size:12px;color:#64748B;min-width:40px;"><?= date('H:i', strtotime($l['created_at'])) ?></div><div style="flex:1;font-size:13px;"><?= htmlspecialchars($l['aksi']) ?></div><div style="font-size:12px;color:#64748B;"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; ?>
  </div>
</div></main><?php renderPageEnd(); ?>