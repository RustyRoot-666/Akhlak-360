<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('manager');
$user = getCurrentUser(); $periode = getActivePeriode(); $periodeId = $periode['id'] ?? 0; $db = getDB();
$karyawanId = (int)($_GET['karyawan_id'] ?? 0);
$stmt = $db->prepare("SELECT id, nik, nama, email, divisi, jabatan, avatar_color, manager_id FROM users WHERE id = ?"); $stmt->execute([$karyawanId]); $employee = $stmt->fetch();
if (!$employee || $employee['manager_id'] != $user['id']) { header('Location: manager-nilai.php'); exit; }
$stmt = $db->prepare("SELECT d.kode, d.nama, d.warna, r.nilai_final, r.nilai_self, r.nilai_peer, r.nilai_atasan, r.nilai_bawahan FROM rekap_nilai r JOIN dimensi_akhlak d ON r.dimensi_id = d.id WHERE r.karyawan_id = ? AND r.periode_id = ? ORDER BY d.urutan"); $stmt->execute([$karyawanId, $periodeId]); $scores = $stmt->fetchAll();
$stmt = $db->prepare("SELECT * FROM catatan_manager WHERE karyawan_id = ? AND periode_id = ?"); $stmt->execute([$karyawanId, $periodeId]); $notes = $stmt->fetch();
$avgScore = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_final')) / count($scores), 2) : null;
$coachingStatus = 'on_track'; if ($avgScore !== null) { if ($avgScore < 3.5) $coachingStatus = 'need_coaching'; elseif ($avgScore >= 4.3) $coachingStatus = 'excellent'; }
$selfAvg = !empty($scores) ? round(array_sum(array_column($scores, 'nilai_self')) / count($scores), 2) : null;

// Find weakest + strongest dimension
$weakest = null; $strongest = null;
if (!empty($scores)) {
    $sorted = $scores;
    usort($sorted, fn($a, $b) => ($a['nilai_final'] ?? 0) <=> ($b['nilai_final'] ?? 0));
    $weakest = $sorted[0];
    $strongest = $sorted[count($sorted) - 1];
}

renderPageStart('Detail Skor Karyawan'); renderSidebar('manager', 'manager-detail-skor', $user);
?>
<main class="main-content">
<div class="page-header page-header-row"><div><h1>Detail Skor Karyawan</h1><p class="subtitle">Breakdown skor 360 dan rekomendasi tindak lanjut</p></div><div class="page-header-actions"><button class="btn btn-secondary btn-sm" onclick="window.print()">Export PDF</button><button class="btn btn-primary btn-sm" onclick="openFeedbackModal()">Kirim Feedback</button></div></div>
<div class="grid-2-equal" style="margin-bottom:20px;">
  <div class="emp-card"><div class="avatar" style="background:<?= htmlspecialchars($employee['avatar_color']) ?>;"><?= getInitials($employee['nama']) ?></div><div class="emp-info"><h4><?= htmlspecialchars($employee['nama']) ?></h4><p><?= htmlspecialchars($employee['jabatan']) ?> &middot; NIK <?= htmlspecialchars($employee['nik']) ?> &middot; <?= htmlspecialchars($employee['divisi']) ?></p><div class="emp-badges"><?php renderBadge($coachingStatus); ?></div></div></div>
  <div style="display:flex;gap:12px;">
    <div class="card" style="flex:1;text-align:center;"><div class="label" style="margin-bottom:8px;">Total Score</div><div class="value" style="font-size:28px;font-weight:700;" data-count="<?= $avgScore ?? 0 ?>" data-decimals="2">0</div><div class="sub" style="font-size:12px;color:#64748B;">Rata-rata 360</div><?php renderBadge($coachingStatus); ?></div>
    <div class="card" style="flex:1;text-align:center;"><div class="label" style="margin-bottom:8px;">Self</div><div class="value" style="font-size:28px;font-weight:700;" data-count="<?= $selfAvg ?? 0 ?>" data-decimals="2">0</div><div class="sub" style="font-size:12px;color:#64748B;">Penilaian diri</div></div>
  </div>
</div>

<?php if ($weakest && $strongest): ?>
<div class="insight-box" style="margin-bottom:16px;">
  <h3>Analisis Otomatis</h3>
  <p>
    Area terkuat: <strong style="color:<?= htmlspecialchars($strongest['warna']) ?>;"><?= htmlspecialchars($strongest['nama']) ?></strong> (<?= round($strongest['nilai_final'], 2) ?>).
    Area pengembangan: <strong style="color:<?= htmlspecialchars($weakest['warna']) ?>;"><?= htmlspecialchars($weakest['nama']) ?></strong> (<?= round($weakest['nilai_final'], 2) ?>).
    <?php if ($coachingStatus === 'need_coaching'): ?>Skor rata-rata di bawah 3.5 — perlu coaching plan segera.<?php elseif ($coachingStatus === 'excellent'): ?>Performa excellent — pertahankan dengan recognition dan stretch assignment.<?php else: ?>Performa on-track — fokus pada area pengembangan untuk mencapai excellent.<?php endif; ?>
  </p>
</div>
<?php endif; ?>

<div class="grid-2">
  <div class="card"><div class="card-header"><h3>Breakdown AKHLAK</h3></div>
    <?php if (empty($scores)): ?>
      <p style="color:#64748B;padding:20px 0;text-align:center;">Belum ada data nilai untuk karyawan ini pada periode aktif.</p>
    <?php else: ?>
    <table class="data-table"><thead><tr><th>Value</th><th>Self</th><th>Peer</th><th>Manager</th><th>Final</th></tr></thead><tbody>
    <?php foreach ($scores as $s): ?>
      <tr><td><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= htmlspecialchars($s['warna']) ?>;margin-right:6px;"></span><?= htmlspecialchars($s['nama']) ?></td><td><?= $s['nilai_self'] ? round($s['nilai_self'], 2) : '-' ?></td><td><?= $s['nilai_peer'] ? round($s['nilai_peer'], 2) : '-' ?></td><td><?= $s['nilai_atasan'] ? round($s['nilai_atasan'], 2) : '-' ?></td><td><strong><?= $s['nilai_final'] ? round($s['nilai_final'], 2) : '-' ?></strong></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
  </div>
  <div>
    <div class="card" style="margin-bottom:16px;"><div class="card-header"><h3>Tindak Lanjut</h3></div>
      <div class="action-panel">
        <div class="action-btn" onclick="openCoachingModal()"><div class="title">Buat Coaching Plan</div><div class="sub"><?= $notes ? 'Edit catatan existing' : 'Target 30 hari' ?></div></div>
        <div class="action-btn" onclick="openFeedbackModal()"><div class="title">Kirim Feedback</div><div class="sub">Bagikan ke karyawan</div></div>
        <div class="action-btn" onclick="window.print()"><div class="title">Export PDF</div><div class="sub">Unduh detail nilai</div></div>
      </div>
    </div>
    <div class="card"><div class="card-header"><h3>Catatan Manager</h3></div>
      <?php if ($notes): ?>
        <div class="note-box" style="padding:12px;background:#F8FAFC;border-radius:6px;border-left:3px solid #1565C0;">
          <div style="font-size:11px;color:#64748B;margin-bottom:6px;">Status: <?php renderBadge($notes['status_coaching']); ?> &middot; Updated: <?= date('d M Y H:i', strtotime($notes['updated_at'])) ?></div>
          <div style="font-size:13px;color:#1B2A4A;margin-bottom:8px;"><?= nl2br(htmlspecialchars($notes['catatan'])) ?></div>
          <?php if ($notes['rekomendasi']): ?>
            <div style="font-size:12px;color:#64748B;border-top:1px solid #E2E8F0;padding-top:8px;margin-top:8px;"><strong>Rekomendasi:</strong> <?= htmlspecialchars($notes['rekomendasi']) ?></div>
          <?php endif; ?>
        </div>
        <button class="btn btn-secondary btn-sm" style="margin-top:8px;" onclick="openCoachingModal()">Edit Catatan</button>
      <?php else: ?>
        <div class="note-box" style="padding:20px;background:#F8FAFC;border-radius:6px;text-align:center;color:#64748B;">Belum ada catatan. Klik <strong>"Buat Coaching Plan"</strong> untuk menambahkan.</div>
      <?php endif; ?>
    </div>
  </div>
</div></main>

<!-- Coaching Plan Modal -->
<div id="coachingModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:24px;width:540px;max-width:90%;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="color:#1B2A4A;margin:0;">Coaching Plan: <?= htmlspecialchars($employee['nama']) ?></h3>
      <button onclick="document.getElementById('coachingModal').style.display='none'" style="border:none;background:none;font-size:24px;cursor:pointer;color:#64748B;">&times;</button>
    </div>
    <form id="coachingForm" onsubmit="submitCoaching(event)">
      <input type="hidden" name="karyawan_id" value="<?= (int)$karyawanId ?>">
      <input type="hidden" name="periode_id" value="<?= (int)$periodeId ?>">
      <div class="form-group"><label>Status Coaching</label>
        <select name="status_coaching" class="form-input" id="coachingStatus">
          <option value="on_track" <?= ($notes['status_coaching'] ?? '') === 'on_track' ? 'selected' : '' ?>>On Track</option>
          <option value="need_coaching" <?= ($notes['status_coaching'] ?? '') === 'need_coaching' ? 'selected' : '' ?>>Need Coaching</option>
          <option value="excellent" <?= ($notes['status_coaching'] ?? '') === 'excellent' ? 'selected' : '' ?>>Excellent</option>
        </select>
      </div>
      <div class="form-group"><label>Catatan</label><textarea name="catatan" id="coachingCatatan" class="form-input" rows="5" placeholder="Catatan untuk karyawan, area yang perlu diperbaiki, target 30 hari..."><?= htmlspecialchars($notes['catatan'] ?? '') ?></textarea></div>
      <div class="form-group"><label>Rekomendasi</label><textarea name="rekomendasi" id="coachingRekomendasi" class="form-input" rows="3" placeholder="Rekomendasi tindak lanjut..."><?= htmlspecialchars($notes['rekomendasi'] ?? '') ?></textarea></div>
      <div style="display:flex;gap:8px;margin-top:20px;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('coachingModal').style.display='none'" style="flex:1;">Batal</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Simpan Coaching Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:24px;width:540px;max-width:90%;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="color:#1B2A4A;margin:0;">Kirim Feedback ke <?= htmlspecialchars($employee['nama']) ?></h3>
      <button onclick="document.getElementById('feedbackModal').style.display='none'" style="border:none;background:none;font-size:24px;cursor:pointer;color:#64748B;">&times;</button>
    </div>
    <form id="feedbackForm" onsubmit="submitFeedback(event)">
      <input type="hidden" name="karyawan_id" value="<?= (int)$karyawanId ?>">
      <div class="form-group"><label>Pesan Feedback</label><textarea name="feedback" id="feedbackText" class="form-input" rows="5" placeholder="Tulis feedback singkat untuk karyawan. Pesan akan dikirim sebagai notifikasi." required></textarea></div>
      <div style="padding:10px;background:#FEF3C7;border-radius:6px;font-size:12px;color:#92400E;margin-bottom:12px;">
        <strong>Catatan:</strong> Feedback akan dikirim sebagai notifikasi ke karyawan dan terlihat di halaman Profil Saya mereka.
      </div>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('feedbackModal').style.display='none'" style="flex:1;">Batal</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Kirim Feedback</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCoachingModal() { document.getElementById('coachingModal').style.display = 'flex'; }
function openFeedbackModal() { document.getElementById('feedbackModal').style.display = 'flex'; }

async function submitCoaching(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  for (const [k, v] of fd.entries()) data[k] = v;
  showLoading();
  try {
    const res = await fetch('../api/penilaian.php?action=save_coaching', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message); document.getElementById('coachingModal').style.display = 'none'; setTimeout(() => location.reload(), 1000); }
    else showError(r.error || 'Gagal menyimpan');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

async function submitFeedback(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  for (const [k, v] of fd.entries()) data[k] = v;
  if (!data.feedback || data.feedback.trim().length < 10) { showError('Feedback minimal 10 karakter'); return; }
  showLoading();
  try {
    const res = await fetch('../api/penilaian.php?action=send_feedback', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const r = await res.json();
    hideLoading();
    if (r.success) { showSuccess(r.message); document.getElementById('feedbackModal').style.display = 'none'; document.getElementById('feedbackText').value = ''; }
    else showError(r.error || 'Gagal kirim feedback');
  } catch (err) { hideLoading(); showError('Koneksi gagal'); }
}

// Close modals on outside click
['coachingModal', 'feedbackModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
});
</script>
<?php renderPageEnd(); ?>
