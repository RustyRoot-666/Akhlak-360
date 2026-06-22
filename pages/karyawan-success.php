<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('karyawan');
$user = getCurrentUser();
renderPageStart('Penilaian Terkirim'); renderSidebar('karyawan', 'karyawan-form', $user);
?><main class="main-content" style="display:flex;align-items:center;justify-content:center;min-height:80vh;">
<div style="text-align:center;">
  <div style="width:80px;height:80px;background:#E8F5E9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2E7D32" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <h1 style="color:#1B2A4A;margin-bottom:8px;">Penilaian Terkirim!</h1>
  <p style="color:#64748B;max-width:400px;margin:0 auto 24px;">Terima kasih telah menyelesaikan penilaian AKHLAK 360°. Kontribusi Anda sangat berarti untuk pengembangan budaya kerja berbasis nilai AKHLAK.</p>
  <a href="karyawan-dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
</div></main><?php renderPageEnd(); ?>