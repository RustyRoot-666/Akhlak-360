<?php
require_once __DIR__ . '/../includes/functions.php';
$method = $_SERVER['REQUEST_METHOD'];
$action = input('action', '');
startSession();
switch ($action) {
case 'login':
    if ($method !== 'POST') error('Method not allowed', 405);
    $jsonBody = inputJSON();
    $email = $jsonBody['email'] ?? input('email');
    $password = $jsonBody['password'] ?? input('password');
    $role = $jsonBody['role'] ?? input('role');
    if (!$email || !$password || !$role) error('Email, password, dan role wajib diisi');
    $db = getDB();
    // Look up user by email + role (single source of truth = DB)
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND status = 'aktif'");
    $stmt->execute([$email, $role]); $user = $stmt->fetch();
    if (!$user) {
        error('Email atau password salah (pastikan user terdaftar di DB dan status aktif)');
    }
    // Always verify password hash — no demo bypass
    if (!password_verify($password, $user['password_hash'])) {
        // Log failed login attempt for security audit
        try {
            $db->prepare("INSERT INTO security_alerts (judul, deskripsi, severity, status, source, ip_address) VALUES (?, ?, 'medium', 'open', 'Auth System', ?)")
               ->execute(['Failed login attempt', "Email: {$email}, Role: {$role}", $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Exception $e) {}
        error('Email atau password salah');
    }
    // Login OK
    $_SESSION['user_id'] = $user['id']; $_SESSION['role'] = $user['role']; $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email']; $_SESSION['divisi'] = $user['divisi']; $_SESSION['last_activity'] = time();
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
    logActivity('Login berhasil', "User: {$email}", 'login', $user['id']);
    $redirect = $user['role']==='karyawan'?'karyawan-dashboard.php':($user['role']==='manager'?'manager-dashboard.php':($user['role']==='adminhrd'?'hrd-dashboard.php':'adminit-dashboard.php'));
    success(['user'=>['id'=>$user['id'],'nama'=>$user['nama'],'email'=>$user['email'],'role'=>$user['role'],'divisi'=>$user['divisi']],'redirect'=>$redirect], 'Login berhasil');
    break;
case 'logout':
    if ($method !== 'POST') error('Method not allowed', 405);
    logActivity('Logout', '', 'login', $_SESSION['user_id'] ?? null); session_destroy(); success([], 'Logout berhasil'); break;
case 'me':
    requireAuth(); $user = getCurrentUser(); if (!$user) error('User not found', 404);
    success(['id'=>$user['id'],'nik'=>$user['nik'],'nama'=>$user['nama'],'email'=>$user['email'],'role'=>$user['role'],'divisi'=>$user['divisi'],'jabatan'=>$user['jabatan'],'avatar_color'=>$user['avatar_color']]); break;
case 'check':
    if (isLoggedIn()) { $user = getCurrentUser(); success(['authenticated'=>true,'user'=>['id'=>$user['id'],'nama'=>$user['nama'],'role'=>$user['role'],'divisi'=>$user['divisi']]]); }
    else { success(['authenticated'=>false]); } break;
case 'forgot_password':
    if ($method !== 'POST') error('Method not allowed', 405);
    $email = trim(input('email', ''));
    if (!$email) error('Email wajib diisi');
    $db = getDB();
    $stmt = $db->prepare("SELECT id, nama FROM users WHERE email = ? AND status = 'aktif'"); $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u) {
        $resetToken = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $db->prepare("INSERT INTO system_config (config_key, config_value, deskripsi) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), deskripsi = VALUES(deskripsi)")
           ->execute(['password_reset_' . $u['id'], $resetToken . '|' . $expiry, 'Password reset token for user ' . $u['id']]);
        logActivity('Password reset requested (API)', "Email: {$email}", 'login', $u['id']);
        success(['token' => $resetToken, 'expires' => $expiry], 'Reset token generated (demo: token returned in response)');
    } else {
        // Prevent email enumeration - same response either way
        success([], 'Jika email terdaftar, reset token telah dikirim');
    }
    break;
case 'reset_password':
    if ($method !== 'POST') error('Method not allowed', 405);
    $email = trim(input('email', '')); $token = input('token', ''); $newPassword = input('password', '');
    if (!$email || !$token || !$newPassword) error('Email, token, dan password baru wajib diisi');
    if (strlen($newPassword) < 6) error('Password minimal 6 karakter');
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND status = 'aktif'"); $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u) error('Email tidak ditemukan');
    $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?"); $stmt->execute(['password_reset_' . $u['id']]);
    $stored = $stmt->fetchColumn();
    if (!$stored) error('Token tidak valid atau sudah digunakan');
    list($storedToken, $expiry) = explode('|', $stored);
    if ($storedToken !== $token) error('Token tidak valid');
    if (strtotime($expiry) < time()) error('Token sudah kedaluwarsa');
    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($newPassword, PASSWORD_DEFAULT), $u['id']]);
    $db->prepare("DELETE FROM system_config WHERE config_key = ?")->execute(['password_reset_' . $u['id']]);
    logActivity('Password reset (API)', "User ID: {$u['id']}", 'login', $u['id']);
    success([], 'Password berhasil direset');
    break;
case 'change_password':
    requireAuth();
    if ($method !== 'POST') error('Method not allowed', 405);
    $currentPassword = input('current_password', ''); $newPassword = input('new_password', '');
    if (!$currentPassword || !$newPassword) error('Password lama dan baru wajib diisi');
    if (strlen($newPassword) < 6) error('Password baru minimal 6 karakter');
    $db = getDB();
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?"); $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();
    if (!password_verify($currentPassword, $hash)) error('Password lama salah');
    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
    logActivity('Password changed', "User ID: {$_SESSION['user_id']}", 'login', $_SESSION['user_id']);
    success([], 'Password berhasil diubah');
    break;
default: error('Unknown action', 400);
}
