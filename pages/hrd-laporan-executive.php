<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('adminhrd');
$user = getCurrentUser();
renderPageStart('Executive Summary'); renderSidebar('adminhrd', 'hrd-laporan', $user);
?><main class="main-content">
<div class="page-header page-header-row"><div><h1>Executive Summary — PDF Manajemen</h1><p class="subtitle">Ringkasan eksekutif kinerja AKHLAK untuk jajaran manajemen</p></div><div class="page-header-actions"><a href="hrd-laporan.php" class="btn btn-secondary btn-sm">&larr; Kembali</a><button class="btn btn-primary btn-sm" onclick="window.print()">Download PDF</button></div></div>
<div class="stat-cards">
  <div class="stat-card"><div class="accent" style="background:#1565C0;"></div><div><div class="label">Format File</div><div class="value" style="font-size:22px;">PDF &middot; A4</div></div><div class="sub">Siap diunduh</div></div>
  <div class="stat-card"><div class="accent" style="background:#2E7D32;"></div><div><div class="label">Jumlah Halaman</div><div class="value" data-count="8">0</div></div><div class="sub">Termasuk ringkasan &amp; lampiran</div></div>
  <div class="stat-card"><div class="accent" style="background:#E65100;"></div><div><div class="label">Ukuran File</div><div class="value" style="font-size:22px;">1.2 MB</div></div><div class="sub">Resolusi cetak tinggi</div></div>
  <div class="stat-card"><div class="accent" style="background:#4527A0;"></div><div><div class="label">Terakhir Dibuat</div><div class="value" style="font-size:18px;"><?= date('d M Y') ?></div></div><div class="sub">oleh Admin HRD</div></div>
</div>
<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Daftar Isi Laporan</h3></div>
    <table class="data-table"><thead><tr><th>Bagian</th><th>Deskripsi Konten</th><th>Halaman</th></tr></thead><tbody>
      <tr><td style="font-weight:500;">Cover &amp; Ringkasan Eksekutif</td><td>Skor rata-rata &amp; insight utama</td><td>1&ndash;2</td></tr>
      <tr><td style="font-weight:500;">Skor AKHLAK per Divisi</td><td>Radar chart 6 nilai per divisi</td><td>3</td></tr>
      <tr><td style="font-weight:500;">Ranking &amp; Top Performers</td><td>10 karyawan skor tertinggi</td><td>4&ndash;5</td></tr>
      <tr><td style="font-weight:500;">Analisis Tren Periode</td><td>Perbandingan Semester I vs II</td><td>6</td></tr>
      <tr><td style="font-weight:500;">Rekomendasi HRD</td><td>Program pengembangan per divisi</td><td>7&ndash;8</td></tr>
    </tbody></table>
  </div>
  <div class="card"><div class="card-header"><h3>Opsi Generate PDF</h3></div>
    <div class="action-panel">
      <div class="action-btn"><div class="title">Sertakan Radar Chart</div><div class="sub">6 nilai AKHLAK per divisi</div></div>
      <div class="action-btn"><div class="title">Sertakan Ranking Karyawan</div><div class="sub">Top 10 performer &amp; insight</div></div>
      <div class="action-btn"><div class="title">Sertakan Rekomendasi HRD</div><div class="sub">Saran program tindak lanjut</div></div>
      <div class="action-btn"><div class="title">Lampirkan Data Mentah</div><div class="sub">Tabel skor divisi (lampiran)</div></div>
    </div>
  </div>
</div>
<div class="insight-box"><h3>Ringkasan Konten Laporan</h3><p>Laporan Executive Summary merangkum hasil akhir Assessment AKHLAK 360 periode aktif dalam format siap presentasi untuk Direksi, mencakup skor rata-rata perusahaan, perbandingan antar divisi, ranking karyawan, serta rekomendasi tindak lanjut periode berikutnya.</p></div></main><?php renderPageEnd(); ?>