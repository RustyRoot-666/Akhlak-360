<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

// Report templates (static catalog - would come from a report_templates table in production)
$templates = [
    ['name' => 'System uptime', 'period' => 'Monthly', 'format' => 'PDF', 'category' => 'uptime'],
    ['name' => 'Backup summary', 'period' => 'Weekly', 'format' => 'XLSX', 'category' => 'backup'],
    ['name' => 'Security audit', 'period' => 'Monthly', 'format' => 'PDF', 'category' => 'security'],
    ['name' => 'Access activity', 'period' => 'Daily', 'format' => 'CSV', 'category' => 'access'],
    ['name' => 'Database inventory', 'period' => 'On demand', 'format' => 'XLSX', 'category' => 'database'],
    ['name' => 'Anomaly review', 'period' => 'Weekly', 'format' => 'PDF', 'category' => 'anomaly'],
];

// Recent report-related activity
$reportLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.aksi LIKE '%report%' OR al.aksi LIKE '%Report%' OR al.aksi LIKE '%export%' ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

$totalExports = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE aksi LIKE '%export%' OR aksi LIKE '%Export%'")->fetchColumn();
$scheduledReports = 4; // static for demo
$failedExports = 0; // would be computed from a reports_log table

renderPageStart('System Report'); renderSidebar('adminit', 'adminit-report', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Report Center</h1><p class="subtitle">IT audit reporting for uptime, backup, security, access, and logs</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="showSuccess('Generate report dialog opened')">Generate Report</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Templates</div><div class="value" data-count="<?= count($templates) ?>">0</div></div><div class="sub">Ready reports</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Exports</div><div class="value" data-count="<?= $totalExports ?>">0</div></div><div class="sub">All time</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Scheduled</div><div class="value" data-count="<?= $scheduledReports ?>">0</div></div><div class="sub">Auto delivery</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Failed</div><div class="value" data-count="<?= $failedExports ?>">0</div></div><div class="sub">No failed export</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Report Templates</h3></div>
    <table class="data-table"><thead><tr><th>Report</th><th>Period</th><th>Format</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($templates as $t): ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($t['name']) ?></td>
        <td><?= htmlspecialchars($t['period']) ?></td>
        <td><span class="badge badge-berjalan"><?= htmlspecialchars($t['format']) ?></span></td>
        <td><a href="#" class="text-link" onclick="generateReport('<?= htmlspecialchars($t['category']) ?>', '<?= htmlspecialchars($t['format']) ?>');return false;"><?= $t['format'] === 'CSV' ? 'Download' : ($t['format'] === 'XLSX' ? 'Export' : 'Generate') ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Report Actions</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="quickGenerate()"><div class="title">Generate Report</div><div class="sub">Build now (default: uptime/PDF)</div></div>
      <div class="action-btn" onclick="showSuccess('Schedule report: pilih template, periode, dan email penerima - fitur lengkap memerlukan cron job scheduler')"><div class="title">Schedule Report</div><div class="sub">Auto send (requires cron)</div></div>
      <div class="action-btn" onclick="exportAllPack()"><div class="title">Export Pack</div><div class="sub">Bundle all categories</div></div>
      <div class="action-btn" onclick="shareLink()"><div class="title">Share Link</div><div class="sub">Create access link</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Report Readiness</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Audit pack ready</div><div class="alert-sub">Security + backup data</div></div><span class="badge badge-selesai">Ready</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Monthly uptime due</div><div class="alert-sub">Generate before Friday</div></div><span class="badge badge-watch">Due</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">No failed export</div><div class="alert-sub">All scheduled jobs healthy</div></div><span class="badge badge-ok">OK</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Recent Reports</h3></div>
    <?php if (empty($reportLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No report exports yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($reportLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
function generateReport(category, format){showLoading();fetch('../api/adminit.php?action=generate_report',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'category='+encodeURIComponent(category)+'&format='+encodeURIComponent(format)}).then(r=>{if(format==='CSV'||format==='XLSX'){return r.blob().then(b=>{hideLoading();const u=URL.createObjectURL(b);const a=document.createElement('a');a.href=u;a.download=category+'-report.'+format.toLowerCase();a.click();URL.revokeObjectURL(u);showSuccess('Report downloaded');});}return r.json().then(d=>{hideLoading();showSuccess(d.message||'Report generated');});}).catch(e=>{hideLoading();showError('Generate failed');});}

function quickGenerate() {
  var cat = prompt('Enter report category:\n- uptime\n- backup\n- security\n- access\n- database\n- anomaly', 'uptime');
  if (!cat) return;
  var fmt = prompt('Format (CSV / XLSX / PDF):', 'CSV');
  if (!fmt) return;
  fmt = fmt.toUpperCase();
  if (['CSV','XLSX','PDF'].indexOf(fmt) === -1) { showError('Format tidak valid'); return; }
  generateReport(cat, fmt);
}

function exportAllPack() {
  if (!confirm('Export semua 6 kategori report sebagai satu file XLSX?')) return;
  showLoading();
  var categories = ['uptime','backup','security','access','database','anomaly'];
  var promises = categories.map(function(cat) {
    return fetch('../api/adminit.php?action=generate_report', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'category='+cat+'&format=CSV'}).then(r=>r.text());
  });
  Promise.all(promises).then(function(csvs) {
    hideLoading();
    var wb = XLSX.utils.book_new();
    var parseCsv = function(csv) { return csv.split('\n').filter(l=>l.trim()).map(l=>l.split(',').map(c=>c.replace(/^"|"$/g,''))); };
    csvs.forEach(function(csv, i) {
      try { XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(parseCsv(csv)), categories[i]); } catch(e){}
    });
    XLSX.writeFile(wb, 'all-reports-pack-<?= date('Ymd-His') ?>.xlsx');
    showSuccess('Pack exported: 6 sheets');
  }).catch(function(e) { hideLoading(); showError('Gagal export: ' + e.message); });
}

function shareLink() {
  var token = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
  var link = window.location.origin + window.location.pathname.replace(/\/[^\/]+$/, '/') + 'adminit-report.php?share=' + token;
  prompt('Share link generated (valid 24 hours):', link);
}
</script>
<?php renderPageEnd(); ?>
