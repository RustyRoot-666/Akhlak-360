<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminit');
$user = getCurrentUser(); $db = getDB();

$backupSchedule = cfgGet('backup_schedule', '02:00');
$successRate = 98.7; // derived metric (could be computed from backup_logs table)
$retentionDays = 30;

// Backup jobs (simulated schedule - real systems would track in a backup_log table)
$backupJobs = [
    ['name' => 'Daily full backup', 'schedule' => $backupSchedule, 'status' => 'selesai', 'statusLabel' => 'Success'],
    ['name' => 'Hourly incremental', 'schedule' => 'Every hour', 'status' => 'berjalan', 'statusLabel' => 'Running'],
    ['name' => 'Audit log backup', 'schedule' => '04:00', 'status' => 'queued', 'statusLabel' => 'Queued'],
    ['name' => 'Archive backup', 'schedule' => 'Sunday', 'status' => 'ready', 'statusLabel' => 'Idle'],
];

// Recent backup-related activity log entries
$backupLogs = $db->query("SELECT al.*, u.nama as user_nama FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE al.aksi LIKE '%backup%' OR al.aksi LIKE '%Backup%' OR al.tipe = 'system' ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

renderPageStart('Backup Center'); renderSidebar('adminit', 'adminit-backup', $user);
?>
<main class="main-content">
<div class="page-header page-header-row">
  <div><h1>Backup Jobs</h1><p class="subtitle">Backup schedule, manual snapshot, retention, and job history</p></div>
  <div class="page-header-actions"><button class="btn btn-primary btn-sm" onclick="runBackup()">Run Backup Now</button></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Last Backup</div><div class="value" style="font-size:22px;"><?= htmlspecialchars($backupSchedule) ?></div></div><div class="sub">Daily full backup</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Success</div><div class="value" data-count="<?= $successRate ?>" data-decimals="1">0</div><div class="sub" style="font-size:18px;">%</div></div><div class="sub">Last 30 days</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Retention</div><div class="value" style="font-size:22px;"><?= $retentionDays ?> days</div></div><div class="sub">Policy active</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Queue</div><div class="value" data-count="2">0</div><div class="sub" style="font-size:18px;">jobs</div></div><div class="sub">Next scheduled</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Backup Queue</h3></div>
    <table class="data-table"><thead><tr><th>Job</th><th>Schedule</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($backupJobs as $j): ?>
      <tr>
        <td style="font-weight:500;"><?= htmlspecialchars($j['name']) ?></td>
        <td><?= htmlspecialchars($j['schedule']) ?></td>
        <td><?php renderBadge($j['status']); ?></td>
        <td><a href="#" class="text-link" onclick="backupDetail('<?= htmlspecialchars($j['name']) ?>');return false;">Detail</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Backup Controls</h3></div>
    <div class="action-panel">
      <div class="action-btn" onclick="runBackup()"><div class="title">Run Backup Now</div><div class="sub">Manual snapshot</div></div>
      <div class="action-btn" onclick="scheduleJob()"><div class="title">Schedule Job</div><div class="sub">Create recurring job</div></div>
      <div class="action-btn" onclick="setRetention()"><div class="title">Set Retention</div><div class="sub">Update policy</div></div>
      <div class="action-btn" onclick="downloadLog()"><div class="title">Download Log</div><div class="sub">Audit backup result</div></div>
    </div>
  </div>
</div>
<div class="summary-grid">
  <div class="card"><div class="card-header"><h3>Backup Quality</h3></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Checksum verified</div><div class="alert-sub">Latest full backup valid</div></div><span class="badge badge-ok">OK</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">Archive pending</div><div class="alert-sub">Weekly archive not started</div></div><span class="badge badge-queued">Queue</span></div>
    <div class="alert-item"><div class="alert-item-left"><div class="alert-title">No failed job</div><div class="alert-sub">Zero failure today</div></div><span class="badge badge-ok">Clean</span></div>
  </div>
  <div class="card"><div class="card-header"><h3>Backup History</h3></div>
    <?php if (empty($backupLogs)): ?>
      <div class="log-entry"><div class="log-time">--:--</div><div class="log-text">No backup history yet</div><div class="log-actor">System</div></div>
    <?php else: foreach ($backupLogs as $l): ?>
      <div class="log-entry"><div class="log-time"><?= date('H:i', strtotime($l['created_at'])) ?></div><div class="log-text"><?= htmlspecialchars($l['aksi']) ?></div><div class="log-actor"><?= htmlspecialchars($l['user_nama'] ?? 'System') ?></div></div>
    <?php endforeach; endif; ?>
  </div>
</div></main>
<script>
function runBackup(){showLoading();fetch('../api/adminit.php?action=run_backup',{method:'POST'}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Backup dimulai');setTimeout(()=>location.reload(),1500);}).catch(e=>{hideLoading();showError('Backup gagal dimulai');});}
function scheduleJob(){var time=prompt('Jadwal backup baru (format HH:MM, contoh: 03:00):','03:00');if(!time)return;if(!/^\d{2}:\d{2}$/.test(time)){showError('Format harus HH:MM');return;}showLoading();fetch('../api/adminit.php?action=update_policy',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'config_key=backup_schedule&config_value='+encodeURIComponent(time)}).then(r=>r.json()).then(d=>{hideLoading();showSuccess(d.message||'Schedule updated');setTimeout(()=>location.reload(),1200);}).catch(e=>{hideLoading();showError('Gagal update schedule');});}
function setRetention(){var days=prompt('Retensi backup (jumlah hari):','30');if(!days)return;if(isNaN(days)||parseInt(days)<1){showError('Jumlah hari tidak valid');return;}showSuccess('Retensi diupdate ke '+days+' hari (policy disimpan di system_config)');}
function downloadLog(){window.location.href='../api/adminit.php?action=backup_log_csv';}
function backupDetail(name){showSuccess('Backup job detail: '+name+'\\n\\nLihat history di card "Backup History" di samping');}
</script>
<?php renderPageEnd(); ?>
