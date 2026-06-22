<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'karyawan': header('Location: pages/karyawan-dashboard.php'); break;
        case 'manager': header('Location: pages/manager-dashboard.php'); break;
        case 'adminhrd': header('Location: pages/hrd-dashboard.php'); break;
        case 'adminit': header('Location: pages/adminit-dashboard.php'); break;
        default: header('Location: index.php');
    } exit;
}
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — AKHLAK360</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .role-tabs{display:flex;gap:0;border:1px solid #D4C5B9;border-radius:8px;overflow:hidden;margin-bottom:20px}
    .role-tab{flex:1;padding:10px 8px;border:none;background:#fff;cursor:pointer;font-size:13px;font-weight:500;color:#64748B;transition:all .2s}
    .role-tab.active{background:#1B2A4A;color:#fff}.role-tab:hover:not(.active){background:#F5F5F5}
    .error-msg{color:#C62828;font-size:13px;margin-top:8px;display:none}.loading{opacity:.7;pointer-events:none}
  </style>
</head>
<body class="login-page">
  <div class="login-left">
    <div class="logo-block"><img src="assets/logo.png" alt="AKHLAK360"><span>AKHLAK360</span></div>
    <div class="hero-title"><div class="akhlak">AKHLAK</div><div class="num">360</div><div class="subtitle">PT Energi Nusantara</div></div>
    <div class="desc-card"><h3>Sistem Penilaian 360°</h3><p>Evaluasi karyawan berbasis nilai AKHLAK secara objektif, anonim, dan terstruktur.</p></div>
    <div class="pills"><span class="pill">Amanah</span><span class="pill">Kompeten</span><span class="pill">Harmonis</span><span class="pill">Loyal</span><span class="pill">Adaptif</span><span class="pill">Kolaboratif</span></div>
  </div>
  <div class="login-right">
    <div class="login-form-wrap">
      <h1>Selamat datang</h1><p class="subtitle">Masuk untuk melanjutkan penilaian</p>
      <div class="role-tabs">
        <button class="role-tab active" data-role="karyawan" onclick="selectRole(this)">Karyawan</button>
        <button class="role-tab" data-role="manager" onclick="selectRole(this)">Manager</button>
        <button class="role-tab" data-role="adminhrd" onclick="selectRole(this)">Admin HRD</button>
        <button class="role-tab" data-role="adminit" onclick="selectRole(this)">Admin IT</button>
      </div>
      <div class="form-group"><label>Email</label><input type="email" class="form-input" id="email" placeholder="nama@energinusantara.co.id" value="ahmad.fauzi@energinusantara.co.id"></div>
      <div class="form-group"><label>Password</label><div class="password-wrap"><input type="password" class="form-input" id="password" value="password123"><button type="button" class="toggle-pw" onclick="togglePw()"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
      <button class="btn btn-primary" id="loginBtn" onclick="doLogin()">Masuk →</button>
      <div class="error-msg" id="errorMsg"></div>
      <a href="pages/reset-password.php" class="text-link forgot-link">Lupa Password?</a>
      <p class="login-footer">PT Energi Nusantara &copy; 2025 · AKHLAK360 v1.0</p>
    </div>
  </div>
  <script>
    let currentRole='karyawan';
    const demoEmails={'karyawan':'ahmad.fauzi@energinusantara.co.id','manager':'hendra@energinusantara.co.id','adminhrd':'admin.hrd@energinusantara.co.id','adminit':'admin.it@energinusantara.co.id'};
    function selectRole(el){document.querySelectorAll('.role-tab').forEach(t=>t.classList.remove('active'));el.classList.add('active');currentRole=el.dataset.role;document.getElementById('email').value=demoEmails[currentRole]||'';}
    function togglePw(){const pw=document.getElementById('password');pw.type=pw.type==='password'?'text':'password';}
    function showError(msg){const el=document.getElementById('errorMsg');el.textContent=msg;el.style.display='block';}
    async function doLogin(){const email=document.getElementById('email').value.trim();const password=document.getElementById('password').value;const btn=document.getElementById('loginBtn');const err=document.getElementById('errorMsg');err.style.display='none';if(!email||!password){showError('Email dan password wajib diisi');return;}btn.classList.add('loading');btn.textContent='Memuat...';try{const res=await fetch('api/auth.php?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,password,role:currentRole})});const data=await res.json();if(data.success){window.location.href='pages/'+data.data.redirect;}else{showError(data.error||'Login gagal');}}catch(e){showError('Koneksi gagal');}finally{btn.classList.remove('loading');btn.textContent='Masuk →';}}
    document.getElementById('password').addEventListener('keypress',function(e){if(e.key==='Enter')doLogin();});
  </script>
</body>
</html>