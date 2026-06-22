<?php
require_once __DIR__ . '/functions.php';

function getSidebarMenu($role) {
    $menus = [
        'karyawan' => [
            ['page' => 'karyawan-dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
            ['page' => 'karyawan-penilaian', 'label' => 'Penilaian Saya', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'],
            ['page' => 'karyawan-form', 'label' => 'Isi Penilaian', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'],
            ['page' => 'karyawan-nilai', 'label' => 'Nilai AKHLAK', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
        ],
        'manager' => [
            ['page' => 'manager-dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
            ['page' => 'manager-nilai', 'label' => 'Nilai Karyawan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
            ['page' => 'manager-performa', 'label' => 'Dashboard Performa', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'],
            ['page' => 'manager-detail-skor', 'label' => 'Detail Skor', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'],
        ],
        'adminhrd' => [
            ['page' => 'hrd-dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
            ['page' => 'hrd-data-karyawan', 'label' => 'Data Karyawan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
            ['page' => 'hrd-assessor', 'label' => 'Tentukan Assessor', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>'],
            ['page' => 'hrd-progress', 'label' => 'Pantau Progress', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>'],
            ['page' => 'hrd-laporan', 'label' => 'Laporan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'],
        ],
        'adminit' => [
            ['page' => 'adminit-dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
            ['page' => 'adminit-database', 'label' => 'Database', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>'],
            ['page' => 'adminit-db-audit', 'label' => 'DB Audit', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>'],
            ['page' => 'adminit-backup', 'label' => 'Backup', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>'],
            ['page' => 'adminit-restore', 'label' => 'Restore', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>'],
            ['page' => 'adminit-monitoring', 'label' => 'Monitoring', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>'],
            ['page' => 'adminit-monitor-detail', 'label' => 'Monitor', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'],
            ['page' => 'adminit-anomaly', 'label' => 'Anomaly', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'],
            ['page' => 'adminit-security', 'label' => 'Security', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'],
            ['page' => 'adminit-system-access', 'label' => 'System Access', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'],
            ['page' => 'adminit-activity-log', 'label' => 'Activity Log', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'],
            ['page' => 'adminit-report', 'label' => 'Report', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>'],
        ],
    ];
    return $menus[$role] ?? [];
}

function adjustColor($hex, $amount) {
    $hex = ltrim($hex, '#');
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $amount));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $amount));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $amount));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function renderSidebar($role, $activePage, $user) {
    $menus = getSidebarMenu($role);
    $periode = getActivePeriode();
    $initials = getInitials($user['nama']);
    $periodeLabel = $periode ? $periode['nama'] : 'Tidak ada periode aktif';
    $darkColor = adjustColor($user['avatar_color'], -30);

    // Notifications count (bell icon)
    $unread = 0;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user['id']]);
        $unread = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
    ?>
    <aside class="sidebar">
      <div class="sidebar-logo"><img src="../assets/logo.png" alt="AKHLAK360"><span>AKHLAK360</span></div>
      <div class="sidebar-period"><div class="label">Periode Aktif</div><div class="value"><?= htmlspecialchars($periodeLabel) ?></div></div>
      <nav class="sidebar-nav">
        <?php foreach ($menus as $menu):
          $isActive = $activePage === $menu['page'] ? 'active' : '';
        ?>
        <a href="<?= $menu['page'] ?>.php" class="nav-item <?= $isActive ?>"><?= $menu['icon'] ?><?= htmlspecialchars($menu['label']) ?></a>
        <?php endforeach; ?>
        <a href="profile.php" class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profil Saya<?= $unread > 0 ? ' <span style="background:#C62828;color:#fff;font-size:10px;padding:1px 6px;border-radius:10px;margin-left:4px;">' . $unread . '</span>' : '' ?></a>
      </nav>
      <div class="sidebar-user">
        <div class="user-card">
          <div class="user-avatar" style="background: linear-gradient(135deg, <?= $user['avatar_color'] ?>, <?= $darkColor ?>);"><?= $initials ?></div>
          <div class="user-info">
            <div class="name"><?= htmlspecialchars($user['nama']) ?></div>
            <div class="role"><?= htmlspecialchars(ucfirst($user['role'])) ?> &middot; <?= htmlspecialchars($user['divisi']) ?></div>
          </div>
        </div>
        <a href="#" class="logout-link" onclick="doLogout(event)">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Keluar
        </a>
      </div>
    </aside>
    <?php
}

function renderPageStart($title) { ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> &mdash; AKHLAK360</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    .loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.85);display:none;align-items:center;justify-content:center;z-index:9999}
    .loading-overlay.show{display:flex}.spinner{width:36px;height:36px;border:3px solid #E2E8F0;border-top-color:#1B2A4A;border-radius:50%;animation:spin .8s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}.toast{position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:8px;font-size:13px;z-index:9999;display:none;color:#fff}
    .toast.show{display:block}.toast.error{background:#C62828}.toast.success{background:#2E7D32}
  </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>
<div class="toast error" id="errorToast"></div>
<div class="toast success" id="successToast"></div>
<div class="app-layout">
<?php }

function renderPageEnd($extraJS = '') { ?>
</div>
<script>
async function doLogout(e){e.preventDefault();try{await fetch('../api/auth.php?action=logout',{method:'POST'});}catch(err){}window.location.href='../index.php';}
function showLoading(){document.getElementById('loadingOverlay').classList.add('show');}
function hideLoading(){document.getElementById('loadingOverlay').classList.remove('show');}
function showError(msg){const t=document.getElementById('errorToast');t.textContent=msg;t.className='toast error show';setTimeout(()=>t.className='toast error',4000);}
function showSuccess(msg){const t=document.getElementById('successToast');t.textContent=msg;t.className='toast success show';setTimeout(()=>t.className='toast success',4000);}
// Counter animation
document.querySelectorAll('.stat-card .value[data-count]').forEach(el=>{
  const target=parseFloat(el.dataset.count);const dec=el.dataset.count.includes('.')?2:0;const dur=1000;const start=performance.now();
  (function upd(now){const p=Math.min((now-start)/dur,1);const e=1-Math.pow(1-p,3);el.textContent=(target*e).toFixed(dec);if(p<1)requestAnimationFrame(upd);})(performance.now());
});
<?= $extraJS ?>
</script>
</body>
</html>
<?php }

function renderBadge($status) {
    $map = ['selesai'=>'badge-selesai','submitted'=>'badge-selesai','berjalan'=>'badge-berjalan','tertunda'=>'badge-tertunda','draft'=>'badge-draft','assigned'=>'badge-tertunda','aktif'=>'badge-online','lengkap'=>'badge-selesai','need_coaching'=>'badge-coaching','on_track'=>'badge-berjalan','excellent'=>'badge-selesai','mandiri'=>'badge-berjalan','atasan'=>'badge-berjalan','peer'=>'badge-berjalan','bawahan'=>'badge-tertunda','diri'=>'badge-berjalan','open'=>'badge-high','resolved'=>'badge-ok','low'=>'badge-low','medium'=>'badge-medium','high'=>'badge-high','critical'=>'badge-high','online'=>'badge-online','offline'=>'badge-offline','ready'=>'badge-ready','review'=>'badge-review','live'=>'badge-live','queued'=>'badge-queued','warning'=>'badge-watch'];
    $labels = ['selesai'=>'Selesai','submitted'=>'Submitted','berjalan'=>'Berjalan','tertunda'=>'Tertunda','draft'=>'Draft','assigned'=>'Assigned','aktif'=>'Aktif','lengkap'=>'Lengkap','need_coaching'=>'Need Coaching','on_track'=>'On Track','excellent'=>'Excellent','mandiri'=>'Mandiri','atasan'=>'Atasan','peer'=>'Peer','bawahan'=>'Bawahan','diri'=>'Self','open'=>'Open','resolved'=>'Resolved','low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical','online'=>'Online','offline'=>'Offline','ready'=>'Ready','review'=>'Review','live'=>'Live','queued'=>'Queued','warning'=>'Warning'];
    $cls = $map[$status] ?? 'badge-berjalan'; $lbl = $labels[$status] ?? ucfirst($status);
    echo '<span class="badge ' . $cls . '">' . $lbl . '</span>';
}
