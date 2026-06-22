<?php
require_once __DIR__ . '/../includes/template.php';
requireAuth(); requireRole('karyawan');
$user = getCurrentUser(); $db = getDB();
$mappingId = (int)($_GET['mapping_id'] ?? 0);
if (!$mappingId) { header('Location: karyawan-penilaian.php'); exit; }
$stmt = $db->prepare("SELECT am.*, u.nama as target_nama, u.divisi, u.jabatan FROM assessor_mapping am JOIN users u ON am.karyawan_id = u.id WHERE am.id = ? AND am.assessor_id = ?");
$stmt->execute([$mappingId, $user['id']]); $mapping = $stmt->fetch();
if (!$mapping) { header('Location: karyawan-penilaian.php'); exit; }
$stmt = $db->query("SELECT d.id as dimensi_id, d.kode, d.nama, d.warna, d.deskripsi, p.id as pertanyaan_id, p.teks, p.urutan FROM dimensi_akhlak d JOIN pertanyaan p ON d.id = p.dimensi_id WHERE p.status = 'aktif' ORDER BY d.urutan, p.urutan"); $rows = $stmt->fetchAll();
$dimensions = []; foreach ($rows as $row) { $k = $row['kode']; if (!isset($dimensions[$k])) $dimensions[$k] = ['id'=>$row['dimensi_id'],'kode'=>$k,'nama'=>$row['nama'],'warna'=>$row['warna'],'deskripsi'=>$row['deskripsi'],'pertanyaan'=>[]]; $dimensions[$k]['pertanyaan'][] = ['id'=>$row['pertanyaan_id'],'teks'=>$row['teks'],'urutan'=>$row['urutan']]; }
$stmt = $db->prepare("SELECT pertanyaan_id, nilai FROM hasil_penilaian WHERE mapping_id = ?"); $stmt->execute([$mappingId]); $existing = []; foreach ($stmt->fetchAll() as $a) $existing[$a['pertanyaan_id']] = (int)$a['nilai'];
renderPageStart('Isi Penilaian'); renderSidebar('karyawan', 'karyawan-form', $user);
?><main class="main-content">
<div class="form-header">
  <a href="karyawan-penilaian.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg></a>
  <div><h1 style="font-size:22px;">Penilaian: <?= htmlspecialchars($mapping['target_nama']) ?></h1><p class="subtitle"><?= htmlspecialchars($mapping['jabatan']) ?> &middot; <?= ucfirst($mapping['tipe_assessor']) ?></p></div>
</div>
<div style="margin-bottom:16px;"><div class="progress-header"><span>Progress pengisian</span><span id="progressText">0% (0/<?= count($rows) ?> pertanyaan)</span></div><div class="progress-track" style="height:8px;"><div class="progress-fill" id="progressBar" style="width:0%;background:#1B2A4A;" data-progress="0"></div></div></div>
<div class="tabs">
  <?php $first = true; foreach ($dimensions as $k => $d): ?><button class="dim-tab tab <?= $first ? 'active' : '' ?>" data-dim="<?= $k ?>" onclick="switchTab('<?= $k ?>')"><?= $d['nama'] ?></button><?php $first = false; endforeach; ?>
</div>
<div class="card">
  <?php $first = true; foreach ($dimensions as $k => $d): ?>
  <div id="dim-<?= $k ?>" class="dim-section" style="display:<?= $first ? 'block' : 'none' ?>;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;"><div class="avatar avatar-sm" style="background:<?= $d['warna'] ?>;color:#fff;"><?= substr($d['nama'], 0, 1) ?></div><div><div style="font-weight:600;color:#1B2A4A;"><?= $d['nama'] ?></div><div style="font-size:12px;color:#64748B;">Nilai 1 = Sangat Kurang &middot; 5 = Sangat Baik</div></div></div>
    <?php foreach ($d['pertanyaan'] as $i => $q): $qnum = (($d['id'] - 1) * 3) + $i + 1; $selected = $existing[$q['id']] ?? 0; ?>
    <div class="question-row" data-qid="<?= $q['id'] ?>"><div class="question-text"><?= $qnum ?>. <?= htmlspecialchars($q['teks']) ?></div><div class="radio-group">
      <?php for ($v = 1; $v <= 5; $v++): $checked = $selected === $v ? 'checked' : ''; ?>
      <label class="radio-label"><input type="radio" name="q<?= $q['id'] ?>" value="<?= $v ?>" <?= $checked ?> onchange="saveAnswer(<?= $mappingId ?>, <?= $q['id'] ?>, <?= $v ?>)"><div class="radio-circle"></div><span><?= $v ?></span></label>
      <?php endfor; ?>
    </div></div>
    <?php endforeach; ?>
  </div>
  <?php $first = false; endforeach; ?>
  <div class="form-nav">
    <button class="btn btn-secondary" onclick="prevDim()">&larr; Sebelumnya</button>
    <button class="btn btn-primary" id="nextBtn" onclick="nextDim()" style="width:auto;">Berikutnya &rarr;</button>
    <button class="btn btn-primary" id="submitBtn" onclick="submitForm()" style="display:none;width:auto;">Kirim Penilaian</button>
  </div>
</div></main>
<script>
const dimensions = <?= json_encode(array_keys($dimensions)) ?>; let currentIdx = 0;
function switchTab(k){document.querySelectorAll('.dim-tab').forEach(t=>t.classList.toggle('active',t.dataset.dim===k));document.querySelectorAll('.dim-section').forEach(s=>s.style.display=s.id==='dim-'+k?'block':'none');currentIdx=dimensions.indexOf(k);updateNav();updateProgress();}
function prevDim(){if(currentIdx>0){currentIdx--;switchTab(dimensions[currentIdx]);}}
function nextDim(){if(currentIdx<dimensions.length-1){currentIdx++;switchTab(dimensions[currentIdx]);}}
function updateNav(){document.getElementById('nextBtn').style.display=currentIdx===dimensions.length-1?'none':'inline-block';document.getElementById('submitBtn').style.display=currentIdx===dimensions.length-1?'inline-block':'none';}
const answers = <?= json_encode($existing) ?>;
function saveAnswer(mappingId, qid, val){answers[qid]=val;fetch('../api/penilaian.php?action=save_answer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'mapping_id='+mappingId+'&pertanyaan_id='+qid+'&nilai='+val});updateProgress();}
function updateProgress(){const total=<?= count($rows) ?>;const filled=Object.keys(answers).length;const pct=Math.round((filled/total)*100);document.getElementById('progressText').textContent=pct+'% ('+filled+'/'+total+' pertanyaan)';document.getElementById('progressBar').style.width=pct+'%';}
async function submitForm(){const mappingId=<?= $mappingId ?>;try{const res=await fetch('../api/penilaian.php?action=submit',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mapping_id:mappingId,answers})});const data=await res.json();if(data.success){showSuccess(data.message||'Penilaian berhasil dikirim');setTimeout(()=>window.location.href='karyawan-success.php',1200);}else{showError(data.error||'Gagal mengirim');}}catch(e){showError('Terjadi kesalahan koneksi');}}
updateNav();updateProgress();
</script>
<?php renderPageEnd(); ?>