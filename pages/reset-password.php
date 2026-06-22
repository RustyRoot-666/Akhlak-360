<?php
require_once __DIR__ . '/../includes/template.php';
$msg = ''; $msgType = 'info'; $step = $_GET['step'] ?? 'request'; $token = $_GET['token'] ?? '';
// Demo token displayed after request (so user can actually use it without a mail server)
$demoToken = '';
$demoEmail = '';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 'request';
    if ($step === 'request') {
        $email = trim($_POST['email'] ?? '');
        if (!$email) { $msg = 'Email wajib diisi'; $msgType = 'error'; }
        else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, nama FROM users WHERE email = ? AND status = 'aktif'");
                $stmt->execute([$email]);
                $u = $stmt->fetch();
                // Always show success message to prevent email enumeration
                if ($u) {
                    // Generate token (store in system_config as password_reset_<userId>)
                    $resetToken = bin2hex(random_bytes(16));
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    $db->prepare("INSERT INTO system_config (config_key, config_value, deskripsi) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), deskripsi = VALUES(deskripsi)")
                       ->execute(['password_reset_' . $u['id'], $resetToken . '|' . $expiry, 'Password reset token for user ' . $u['id']]);
                    logActivity('Password reset requested', "Email: {$email}", 'login', $u['id']);
                    // DEMO ONLY: tampilkan token agar bisa langsung dipakai tanpa server email.
                    // Pada produksi, hapus baris $demoToken di bawah dan kirim token via email sungguhan.
                    $demoToken = $resetToken;
                    $demoEmail = $email;
                } else {
                    logActivity('Password reset - email not found', "Email: {$email}", 'security');
                }
                if ($demoToken) {
                    $msg = 'Token reset berhasil di-generate (DEMO: ditampilkan di bawah). Token berlaku 30 menit.';
                    $msgType = 'success';
                    $step = 'requested';
                } else {
                    $msg = 'Jika email terdaftar, link reset password telah dikirim ke email Anda. Token berlaku 30 menit.';
                    $msgType = 'success';
                    $step = 'requested';
                }
            } catch (Exception $e) {
                $msg = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                $msgType = 'error';
            }
        }
    } elseif ($step === 'reset') {
        $email = trim($_POST['email'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (!$email || !$token || !$newPassword) { $msg = 'Semua field wajib diisi'; $msgType = 'error'; }
        elseif ($newPassword !== $confirmPassword) { $msg = 'Password dan konfirmasi tidak cocok'; $msgType = 'error'; }
        elseif (strlen($newPassword) < 6) { $msg = 'Password minimal 6 karakter'; $msgType = 'error'; }
        else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, nama FROM users WHERE email = ? AND status = 'aktif'");
                $stmt->execute([$email]);
                $u = $stmt->fetch();
                if (!$u) { $msg = 'Email tidak ditemukan'; $msgType = 'error'; }
                else {
                    $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
                    $stmt->execute(['password_reset_' . $u['id']]);
                    $stored = $stmt->fetchColumn();
                    if (!$stored) { $msg = 'Token tidak valid atau sudah digunakan'; $msgType = 'error'; }
                    else {
                        list($storedToken, $expiry) = explode('|', $stored);
                        if (!hash_equals($storedToken, $token)) { $msg = 'Token tidak valid'; $msgType = 'error'; }
                        elseif (strtotime($expiry) < time()) { $msg = 'Token sudah kedaluwarsa. Silakan ajukan reset ulang.'; $msgType = 'error'; }
                        else {
                            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($newPassword, PASSWORD_DEFAULT), $u['id']]);
                            $db->prepare("DELETE FROM system_config WHERE config_key = ?")->execute(['password_reset_' . $u['id']]);
                            logActivity('Password reset completed', "User: {$u['nama']}", 'login', $u['id']);
                            $msg = 'Password berhasil direset. Silakan login dengan password baru.';
                            $msgType = 'success';
                            $step = 'done';
                        }
                    }
                }
            } catch (Exception $e) {
                $msg = 'Terjadi kesalahan: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
}

renderPageStart('Reset Password');
?>
<div style="display:flex;justify-content:center;align-items:center;min-height:100vh;background:#D4C5B9;">
<div style="background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1);width:440px;max-width:90%;">
  <div style="text-align:center;margin-bottom:24px;">
    <img src="../assets/logo.png" alt="AKHLAK360" style="height:40px;">
    <h2 style="margin-top:12px;color:#1B2A4A;"><?= $step === 'reset' ? 'Set Password Baru' : ($step === 'done' ? 'Reset Berhasil' : 'Reset Password') ?></h2>
    <p style="color:#64748B;font-size:13px;">
      <?php if ($step === 'reset'): ?>Masukkan password baru Anda<?php elseif ($step === 'done'): ?>Anda dapat login sekarang<?php else: ?>Masukkan email Anda untuk menerima link reset password<?php endif; ?>
    </p>
  </div>

  <?php if ($msg): ?>
    <div style="padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:13px;background:<?= $msgType === 'error' ? '#FFEBEE' : ($msgType === 'success' ? '#E8F5E9' : '#E3F2FD') ?>;color:<?= $msgType === 'error' ? '#C62828' : ($msgType === 'success' ? '#2E7D32' : '#1565C0') ?>;border:1px solid <?= $msgType === 'error' ? '#FFCDD2' : ($msgType === 'success' ? '#C8E6C9' : '#BBDEFB') ?>;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if ($step === 'done'): ?>
    <a href="../index.php" class="btn btn-primary" style="width:100%;text-align:center;display:block;text-decoration:none;">Kembali ke Login</a>
  <?php elseif ($step === 'reset' || ($step === 'request' && $token)): ?>
    <form method="post" action="reset-password.php">
      <input type="hidden" name="step" value="reset">
      <div class="form-group"><label>Email</label><input type="email" name="email" class="form-input" placeholder="nama@energinusantara.co.id" value="<?= htmlspecialchars($_GET['email'] ?? $demoEmail) ?>" required></div>
      <div class="form-group"><label>Token Reset</label><input type="text" name="token" class="form-input" placeholder="Token dari email" value="<?= htmlspecialchars($token ?: '') ?>" required></div>
      <div class="form-group"><label>Password Baru</label><input type="password" name="password" class="form-input" placeholder="Min. 6 karakter" required></div>
      <div class="form-group"><label>Konfirmasi Password</label><input type="password" name="confirm_password" class="form-input" placeholder="Ulangi password" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:12px;">Reset Password</button>
    </form>
  <?php elseif ($step === 'requested' && $demoToken): ?>
    <div style="padding:14px;background:#F8FAFC;border:1px dashed #1565C0;border-radius:6px;margin-bottom:16px;">
      <div style="font-size:12px;color:#64748B;margin-bottom:6px;">DEMO MODE &mdash; Token Reset Anda:</div>
      <code style="font-family:monospace;font-size:13px;color:#1B2A4A;word-break:break-all;"><?= htmlspecialchars($demoToken) ?></code>
      <div style="font-size:11px;color:#64748B;margin-top:8px;">Berlaku 30 menit. Salin token ini lalu tempel di form reset.</div>
    </div>
    <a href="reset-password.php?step=reset&email=<?= urlencode($demoEmail) ?>&token=<?= urlencode($demoToken) ?>" class="btn btn-primary" style="width:100%;text-align:center;display:block;text-decoration:none;margin-bottom:8px;">Buka Form Reset Password</a>
    <a href="../index.php" class="btn btn-secondary" style="width:100%;text-align:center;display:block;text-decoration:none;">Kembali ke Login</a>
  <?php elseif ($step === 'requested'): ?>
    <p style="font-size:13px;color:#64748B;text-align:center;margin-bottom:16px;">
      Jika email terdaftar, link reset telah dikirim. Hubungi Admin IT jika tidak menerima email dalam 5 menit.
    </p>
    <a href="../index.php" class="btn btn-secondary" style="width:100%;text-align:center;display:block;text-decoration:none;">Kembali ke Login</a>
  <?php else: ?>
    <form method="post" action="reset-password.php">
      <input type="hidden" name="step" value="request">
      <div class="form-group"><label>Email</label><input type="email" name="email" class="form-input" placeholder="nama@energinusantara.co.id" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:12px;">Kirim Link Reset</button>
    </form>
  <?php endif; ?>

  <div style="text-align:center;margin-top:16px;"><a href="../index.php" style="color:#1B2A4A;font-size:13px;text-decoration:none;">&larr; Kembali ke Login</a></div>
</div></div>
<?php renderPageEnd(); ?>
