<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth();
$user = getCurrentUser(); if (!$user) { header('Location: ../index.php'); exit; }
$role = $user['role'];
$rolePage = ['karyawan' => 'karyawan-dashboard', 'manager' => 'manager-dashboard', 'adminhrd' => 'hrd-dashboard', 'adminit' => 'adminit-dashboard'][$role] ?? 'karyawan-dashboard';

// Handle POST: change password
$pwMsg = ''; $pwType = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$current || !$new || !$confirm) { $pwMsg = 'Semua field wajib diisi'; $pwType = 'error'; }
    elseif ($new !== $confirm) { $pwMsg = 'Password baru dan konfirmasi tidak cocok'; $pwType = 'error'; }
    elseif (strlen($new) < 6) { $pwMsg = 'Password baru minimal 6 karakter'; $pwType = 'error'; }
    else {
        $db = getDB();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?"); $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($current, $hash)) { $pwMsg = 'Password lama salah'; $pwType = 'error'; }
        else {
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            logActivity('Password changed (profile)', "User ID: {$user['id']}", 'login', $user['id']);
            $pwMsg = 'Password berhasil diubah';
            $pwType = 'success';
        }
    }
}

// Handle POST: update profile (nama, avatar_color)
$profMsg = ''; $profType = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $nama = trim($_POST['nama'] ?? '');
    $avatarColor = trim($_POST['avatar_color'] ?? '#1565C0');
    if (!$nama) { $profMsg = 'Nama wajib diisi'; $profType = 'error'; }
    else {
        $db = getDB();
        $db->prepare("UPDATE users SET nama = ?, avatar_color = ? WHERE id = ?")->execute([$nama, $avatarColor, $user['id']]);
        logActivity('Profile updated', "Nama: {$nama}", 'admin', $user['id']);
        $profMsg = 'Profil berhasil diupdate. Halaman akan di-refresh...';
        $profType = 'success';
        header('Refresh: 1.5; url=profile.php');
    }
}

// User notifications
$db = getDB();
$notifications = $db->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 20");
$notifications->execute([$user['id']]); $notifications = $notifications->fetchAll();
$unreadCount = (int)$db->query("SELECT COUNT(*) FROM notifikasi WHERE user_id = {$user['id']} AND is_read = 0")->fetchColumn();

// User activity
$activities = $db->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$activities->execute([$user['id']]); $activities = $activities->fetchAll();

$initials = getInitials($user['nama']);
$darkColor = adjustColor($user['avatar_color'], -30);

renderPageStart('Profil Saya'); renderSidebar($role, 'profile', $user);
?>
<main class="main-content">
<div class="page-header"><h1>Profil Saya</h1><p class="subtitle">Kelola informasi akun, keamanan, dan aktivitas Anda</p></div>

<div class="grid-2">
  <!-- Left: Profile card -->
  <div class="card">
    <div class="card-header"><h3>Informasi Akun</h3></div>
    <div style="text-align:center;padding:20px 0;">
      <div class="user-avatar" style="background: linear-gradient(135deg, <?= htmlspecialchars($user['avatar_color']) ?>, <?= htmlspecialchars($darkColor) ?>); width:96px; height:96px; font-size:36px; margin:0 auto 16px;"><?= $initials ?></div>
      <div style="font-size:18px;font-weight:600;color:#1B2A4A;"><?= htmlspecialchars($user['nama']) ?></div>
      <div style="font-size:13px;color:#64748B;margin-top:4px;"><?= htmlspecialchars(ucfirst($user['role'])) ?> &middot; <?= htmlspecialchars($user['divisi']) ?></div>
      <div style="font-size:12px;color:#64748B;margin-top:8px;"><?= htmlspecialchars($user['jabatan']) ?></div>
    </div>
    <table class="data-table" style="margin-top:12px;">
      <tbody>
        <tr><td style="font-weight:500;width:40%;">NIK</td><td><?= htmlspecialchars($user['nik']) ?></td></tr>
        <tr><td style="font-weight:500;">Email</td><td><?= htmlspecialchars($user['email']) ?></td></tr>
        <tr><td style="font-weight:500;">Divisi</td><td><?= htmlspecialchars($user['divisi']) ?></td></tr>
        <tr><td style="font-weight:500;">Jabatan</td><td><?= htmlspecialchars($user['jabatan']) ?></td></tr>
        <tr><td style="font-weight:500;">Status</td><td><?php renderBadge($user['status']); ?></td></tr>
      </tbody>
    </table>
  </div>

  <!-- Right: Edit profile + change password -->
  <div>
    <?php if ($profMsg): ?>
      <div style="padding:10px 14px;border-radius:6px;margin-bottom:12px;font-size:13px;background:<?= $profType === 'error' ? '#FFEBEE' : '#E8F5E9' ?>;color:<?= $profType === 'error' ? '#C62828' : '#2E7D32' ?>;border:1px solid <?= $profType === 'error' ? '#FFCDD2' : '#C8E6C9' ?>;"><?= htmlspecialchars($profMsg) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header"><h3>Edit Profil</h3></div>
      <form method="post">
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" class="form-input" value="<?= htmlspecialchars($user['nama']) ?>" required></div>
        <div class="form-group"><label>Warna Avatar</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach (['#1565C0','#2E7D32','#E65100','#C62828','#4527A0','#00695C','#AD1457','#FF8F00','#6D4C41','#00838F'] as $c): ?>
              <label style="cursor:pointer;">
                <input type="radio" name="avatar_color" value="<?= $c ?>" <?= $user['avatar_color'] === $c ? 'checked' : '' ?> style="display:none;" onchange="document.getElementById('avatarPreview').style.background='linear-gradient(135deg, <?= $c ?>, <?= adjustColor($c, -30) ?>)';">
                <div style="width:28px;height:28px;border-radius:50%;background:<?= $c ?>;<?= $user['avatar_color'] === $c ? 'box-shadow:0 0 0 3px #1B2A4A,0 0 0 5px #fff;' : 'border:2px solid #fff;box-shadow:0 0 0 1px #E2E8F0;' ?>"></div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Simpan Perubahan</button>
      </form>
    </div>

    <?php if ($pwMsg): ?>
      <div style="padding:10px 14px;border-radius:6px;margin-bottom:12px;font-size:13px;background:<?= $pwType === 'error' ? '#FFEBEE' : '#E8F5E9' ?>;color:<?= $pwType === 'error' ? '#C62828' : '#2E7D32' ?>;border:1px solid <?= $pwType === 'error' ? '#FFCDD2' : '#C8E6C9' ?>;"><?= htmlspecialchars($pwMsg) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h3>Ubah Password</h3></div>
      <form method="post">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group"><label>Password Lama</label><input type="password" name="current_password" class="form-input" required></div>
        <div class="form-group"><label>Password Baru</label><input type="password" name="new_password" class="form-input" placeholder="Min. 6 karakter" required></div>
        <div class="form-group"><label>Konfirmasi Password Baru</label><input type="password" name="confirm_password" class="form-input" required></div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Ubah Password</button>
      </form>
    </div>
  </div>
</div>

<div class="grid-2" style="margin-top:16px;">
  <div class="card">
    <div class="card-header"><h3>Notifikasi Saya (<?= $unreadCount ?> belum dibaca)</h3></div>
    <?php if (empty($notifications)): ?>
      <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada notifikasi.</p>
    <?php else: foreach ($notifications as $n): ?>
      <div class="alert-item" style="<?= $n['is_read'] ? 'opacity:0.6;' : '' ?>">
        <div class="alert-item-left">
          <div class="alert-title"><?= htmlspecialchars($n['judul']) ?></div>
          <div class="alert-sub"><?= htmlspecialchars($n['pesan']) ?> &middot; <?= date('d M H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
          <button class="btn btn-secondary btn-sm" onclick="markRead(<?= (int)$n['id'] ?>)">Tandai dibaca</button>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3>Aktivitas Terakhir</h3></div>
    <?php if (empty($activities)): ?>
      <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada aktivitas tercatat.</p>
    <?php else: foreach ($activities as $a): ?>
      <div class="log-entry">
        <div class="log-time"><?= date('d M H:i', strtotime($a['created_at'])) ?></div>
        <div class="log-text"><?= htmlspecialchars($a['aksi']) ?></div>
        <div class="log-actor"><?= htmlspecialchars(ucfirst($a['tipe'])) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div style="margin-top:24px;text-align:center;">
  <a href="<?= $rolePage ?>.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
</main>
<script>
function markRead(id) {
  fetch('../api/notifikasi.php?action=mark_read', {
    method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + id
  }).then(r => r.json()).then(d => {
    if (d.success) { showSuccess('Notifikasi ditandai dibaca'); setTimeout(() => location.reload(), 800); }
    else showError('Gagal update');
  }).catch(e => showError('Koneksi gagal'));
}
</script>
<?php renderPageEnd(); ?>
