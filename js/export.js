/**
 * AKHLAK360 - Export & Utility Functions
 * Handles: PDF export, Excel/XLS export, CSV export, Print
 */

// =====================================================
// TOAST NOTIFICATION
// =====================================================
function showToast(message, type = 'success') {
  const existing = document.querySelector('.akhlak-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = 'akhlak-toast';
  const bg = type === 'success' ? '#2E7D32' : type === 'error' ? '#C62828' : '#1565C0';
  const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
  toast.innerHTML = `<span style="font-weight:600;">${icon}</span> ${message}`;
  toast.style.cssText = `
    position:fixed; bottom:28px; right:28px; z-index:9999;
    background:${bg}; color:#fff; padding:12px 20px; border-radius:10px;
    font-family:'Inter',sans-serif; font-size:14px; font-weight:500;
    box-shadow:0 4px 16px rgba(0,0,0,0.2); display:flex; align-items:center; gap:8px;
    animation:toastIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
    max-width:320px;
  `;

  if (!document.querySelector('#toast-style')) {
    const s = document.createElement('style');
    s.id = 'toast-style';
    s.textContent = `@keyframes toastIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}`;
    document.head.appendChild(s);
  }

  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// =====================================================
// PROGRESS MODAL
// =====================================================
function showProgressModal(title, message) {
  const overlay = document.createElement('div');
  overlay.id = 'export-overlay';
  overlay.style.cssText = `
    position:fixed; inset:0; background:rgba(27,42,74,0.55); z-index:9998;
    display:flex; align-items:center; justify-content:center;
    backdrop-filter:blur(2px);
  `;
  overlay.innerHTML = `
    <div style="background:#fff; border-radius:16px; padding:36px 48px; text-align:center;
      font-family:'Inter',sans-serif; box-shadow:0 20px 60px rgba(0,0,0,0.2); min-width:280px;">
      <div style="margin-bottom:16px;">
        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="24" cy="24" r="20" stroke="#E8E0D8" stroke-width="4"/>
          <path d="M24 4 A20 20 0 0 1 44 24" stroke="#1B2A4A" stroke-width="4" stroke-linecap="round">
            <animateTransform attributeName="transform" type="rotate" from="0 24 24" to="360 24 24" dur="0.8s" repeatCount="indefinite"/>
          </path>
        </svg>
      </div>
      <div style="font-size:16px; font-weight:600; color:#1B2A4A; margin-bottom:6px;">${title}</div>
      <div style="font-size:13px; color:#64748B;">${message}</div>
    </div>
  `;
  document.body.appendChild(overlay);
  return overlay;
}

function hideProgressModal() {
  const overlay = document.getElementById('export-overlay');
  if (overlay) overlay.remove();
}

// =====================================================
// PRINT / PDF
// =====================================================
function exportToPrint() {
  window.print();
}

function exportToPDF(filename) {
  filename = filename || document.title.replace(/[^a-z0-9]/gi, '_') + '.pdf';
  
  if (typeof window.jspdf === 'undefined' && typeof window.jsPDF === 'undefined') {
    // Fallback: use print dialog
    showToast('Membuka dialog print untuk simpan PDF...', 'info');
    setTimeout(() => window.print(), 500);
    return;
  }

  const modal = showProgressModal('Membuat PDF...', 'Mohon tunggu, sedang memproses halaman');
  
  const { jsPDF } = window.jspdf || window;
  const main = document.querySelector('.main-content') || document.body;
  
  import('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js').then(() => {
    html2canvas(main, { scale: 1.5, useCORS: true, backgroundColor: '#f8f9fa' }).then(canvas => {
      const imgData = canvas.toDataURL('image/png');
      const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
      const pageW = pdf.internal.pageSize.getWidth();
      const pageH = pdf.internal.pageSize.getHeight();
      const imgW = pageW;
      const imgH = (canvas.height * imgW) / canvas.width;
      let y = 0;
      while (y < imgH) {
        if (y > 0) pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, -y, imgW, imgH);
        y += pageH;
      }
      pdf.save(filename);
      hideProgressModal();
      showToast('PDF berhasil diunduh!', 'success');
    });
  }).catch(() => {
    hideProgressModal();
    showToast('Membuka dialog print untuk simpan PDF...', 'info');
    setTimeout(() => window.print(), 500);
  });
}

// =====================================================
// CSV EXPORT
// =====================================================
function tableToCSV(tableEl) {
  const rows = tableEl.querySelectorAll('tr');
  return Array.from(rows).map(row => {
    const cells = row.querySelectorAll('th, td');
    return Array.from(cells).map(cell => {
      const text = cell.innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim();
      return `"${text}"`;
    }).join(',');
  }).join('\n');
}

function exportTableToCSV(filename) {
  filename = filename || 'akhlak360_data.csv';
  const table = document.querySelector('.data-table');
  if (!table) { showToast('Tidak ada tabel data untuk diekspor.', 'error'); return; }

  const csv = '\uFEFF' + tableToCSV(table); // BOM for Excel UTF-8
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
  showToast('CSV berhasil diunduh!', 'success');
}

// =====================================================
// EXCEL / XLS EXPORT
// =====================================================
function exportTableToExcel(filename, sheetName) {
  filename = filename || 'akhlak360_data.xlsx';
  sheetName = sheetName || 'Data AKHLAK360';

  if (typeof XLSX === 'undefined') {
    showToast('Library Excel belum dimuat, mencoba ulang...', 'info');
    loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', () => exportTableToExcel(filename, sheetName));
    return;
  }

  const table = document.querySelector('.data-table');
  if (!table) { showToast('Tidak ada tabel data untuk diekspor.', 'error'); return; }

  const modal = showProgressModal('Membuat Excel...', 'Memproses data tabel');

  setTimeout(() => {
    try {
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.table_to_sheet(table);

      // Style header row
      const range = XLSX.utils.decode_range(ws['!ref']);
      for (let C = range.s.c; C <= range.e.c; C++) {
        const cellAddr = XLSX.utils.encode_cell({ r: 0, c: C });
        if (ws[cellAddr]) {
          ws[cellAddr].s = {
            font: { bold: true, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "1B2A4A" } }
          };
        }
      }

      // Set column widths
      ws['!cols'] = Array.from({ length: range.e.c + 1 }, () => ({ wch: 22 }));

      // Add metadata sheet
      const metaData = [
        ['AKHLAK360 - PT Energi Nusantara'],
        ['Sistem Penilaian 360° Nilai AKHLAK'],
        [''],
        ['Periode Aktif', 'Semester I 2025'],
        ['Tanggal Export', new Date().toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' })],
        ['Total Data', table.querySelectorAll('tbody tr').length + ' entri'],
        [''],
        ['AKHLAK Values:'],
        ['A', 'Amanah'],
        ['K', 'Kompeten'],
        ['H', 'Harmonis'],
        ['L', 'Loyal'],
        ['A', 'Adaptif'],
        ['K', 'Kolaboratif'],
      ];
      const wsMeta = XLSX.utils.aoa_to_sheet(metaData);
      wsMeta['!cols'] = [{ wch: 28 }, { wch: 36 }];

      XLSX.utils.book_append_sheet(wb, wsMeta, 'Info');
      XLSX.utils.book_append_sheet(wb, ws, sheetName);
      XLSX.writeFile(wb, filename);

      hideProgressModal();
      showToast('Excel berhasil diunduh!', 'success');
    } catch (e) {
      hideProgressModal();
      showToast('Gagal membuat Excel: ' + e.message, 'error');
    }
  }, 300);
}

// =====================================================
// MULTI-TABLE EXCEL EXPORT (for laporan pages)
// =====================================================
function exportAllTablesToExcel(filename) {
  filename = filename || 'akhlak360_laporan_lengkap.xlsx';

  if (typeof XLSX === 'undefined') {
    loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', () => exportAllTablesToExcel(filename));
    return;
  }

  const tables = document.querySelectorAll('.data-table');
  if (!tables.length) { exportTableToExcel(filename); return; }

  const modal = showProgressModal('Membuat Laporan Excel...', 'Memproses ' + tables.length + ' tabel data');

  setTimeout(() => {
    try {
      const wb = XLSX.utils.book_new();

      // Info sheet
      const metaData = [
        ['AKHLAK360 - Laporan Lengkap PT Energi Nusantara'],
        ['Semester I 2025'],
        [''],
        ['Diekspor', new Date().toLocaleString('id-ID')],
        ['Dibuat oleh', 'Admin HRD — AKHLAK360 System'],
        [''],
        ['Catatan: File ini berisi data penilaian AKHLAK 360° dan bersifat RAHASIA.'],
      ];
      const wsMeta = XLSX.utils.aoa_to_sheet(metaData);
      wsMeta['!cols'] = [{ wch: 24 }, { wch: 44 }];
      XLSX.utils.book_append_sheet(wb, wsMeta, 'Info');

      // Each table as a sheet
      const sheetNames = ['Ringkasan Divisi', 'Detail Karyawan', 'Matrix AKHLAK', 'Audit Trail'];
      tables.forEach((table, i) => {
        const ws = XLSX.utils.table_to_sheet(table);
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let C = range.s.c; C <= range.e.c; C++) {
          const cellAddr = XLSX.utils.encode_cell({ r: 0, c: C });
          if (ws[cellAddr]) {
            ws[cellAddr].s = { font: { bold: true }, fill: { fgColor: { rgb: "E8E0D8" } } };
          }
        }
        ws['!cols'] = Array.from({ length: range.e.c + 1 }, () => ({ wch: 20 }));
        const sheetName = sheetNames[i] || `Data ${i + 1}`;
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
      });

      XLSX.writeFile(wb, filename);
      hideProgressModal();
      showToast(`Laporan Excel (${tables.length} sheet) berhasil diunduh!`, 'success');
    } catch (e) {
      hideProgressModal();
      showToast('Gagal: ' + e.message, 'error');
    }
  }, 400);
}

// =====================================================
// DATA GENERATION FOR AKHLAK360 (dummy realistic data)
// =====================================================
function generateAkhlakExcel() {
  if (typeof XLSX === 'undefined') {
    loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', generateAkhlakExcel);
    return;
  }

  const modal = showProgressModal('Membuat Laporan Lengkap...', 'Menyiapkan 106 data karyawan');

  const karyawan = [
    ['Ahmad Fauzi', 'Operations', 'Staff', 4.3, 4.1, 4.5, 4.0, 3.8, 4.4],
    ['Dewi Lestari', 'Finance', 'Senior Staff', 4.6, 4.5, 4.7, 4.4, 4.3, 4.5],
    ['Eko Prasetyo', 'IT', 'Staff', 3.8, 4.2, 3.9, 3.7, 3.6, 4.0],
    ['Siti Rahma', 'Marketing', 'Staff', 4.1, 3.9, 4.2, 4.0, 3.9, 4.2],
    ['Budi Santoso', 'HR', 'Senior Staff', 4.4, 4.3, 4.5, 4.2, 4.1, 4.4],
    ['Ratna Dewi', 'Finance', 'Manager', 4.7, 4.6, 4.8, 4.5, 4.4, 4.6],
    ['Hendra Kusuma', 'Operations', 'Supervisor', 4.2, 4.0, 4.3, 4.1, 3.9, 4.3],
    ['Maya Sari', 'Marketing', 'Staff', 3.9, 4.1, 4.0, 3.8, 4.0, 4.1],
    ['Rizki Pratama', 'IT', 'Senior Staff', 4.0, 4.4, 4.1, 3.9, 4.2, 4.2],
    ['Indah Permata', 'HR', 'Staff', 4.3, 4.0, 4.4, 4.2, 3.8, 4.3],
    ['Fajar Nugroho', 'Operations', 'Staff', 3.7, 3.9, 3.8, 3.6, 3.5, 3.9],
    ['Lisa Anggraini', 'Finance', 'Staff', 4.5, 4.4, 4.6, 4.3, 4.2, 4.5],
    ['Dodi Wahyudi', 'IT', 'Staff', 3.6, 4.0, 3.7, 3.5, 3.8, 3.9],
    ['Kartini Suhartono', 'Marketing', 'Supervisor', 4.2, 4.1, 4.3, 4.0, 4.1, 4.3],
    ['Andi Permana', 'Operations', 'Senior Staff', 4.1, 3.8, 4.2, 4.0, 3.7, 4.1],
  ];

  // Fill to ~106 with variations
  const divisi = ['Operations', 'Finance', 'IT', 'HR', 'Marketing'];
  const level = ['Staff', 'Senior Staff', 'Supervisor', 'Manager'];
  const namaPrefix = ['Agus','Budi','Citra','Desi','Eko','Fika','Gandi','Hani','Imam','Joko','Kiki','Lena','Mudi','Nana','Otto','Prita'];
  const namaSuffix = ['Pratama','Sari','Nugraha','Lestari','Kusuma','Dewi','Wijaya','Santoso','Prasetyo','Wati','Handoko','Setiawan'];

  while (karyawan.length < 106) {
    const nama = namaPrefix[karyawan.length % namaPrefix.length] + ' ' + namaSuffix[karyawan.length % namaSuffix.length];
    const div = divisi[karyawan.length % divisi.length];
    const lv = level[karyawan.length % level.length];
    const base = 3.5 + Math.random() * 1.2;
    karyawan.push([
      nama, div, lv,
      +(base + (Math.random()-0.5)*0.4).toFixed(2),
      +(base + (Math.random()-0.5)*0.4).toFixed(2),
      +(base + (Math.random()-0.5)*0.4).toFixed(2),
      +(base + (Math.random()-0.5)*0.4).toFixed(2),
      +(base + (Math.random()-0.5)*0.4).toFixed(2),
      +(base + (Math.random()-0.5)*0.4).toFixed(2),
    ]);
  }

  // Add composite score
  const dataWithScore = karyawan.map(k => {
    const scores = k.slice(3);
    const avg = (scores.reduce((a,b)=>a+b,0)/scores.length).toFixed(2);
    const grade = avg >= 4.5 ? 'Excellent' : avg >= 4.0 ? 'Good' : avg >= 3.5 ? 'Fair' : 'Needs Improvement';
    return [...k, +avg, grade];
  });

  setTimeout(() => {
    try {
      const wb = XLSX.utils.book_new();

      // ---- Sheet 1: Info
      const wsMeta = XLSX.utils.aoa_to_sheet([
        ['AKHLAK360 — Laporan Assessment Semester I 2025'],
        ['PT Energi Nusantara'],
        [''],
        ['Tanggal Export', new Date().toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'})],
        ['Total Karyawan', 106],
        ['Divisi', 'Operations, Finance, IT, HR, Marketing'],
        [''],
        ['Dimensi Penilaian AKHLAK:'],
        ['Amanah', 'Integritas dan dapat dipercaya dalam menjalankan tugas'],
        ['Kompeten', 'Terus belajar dan mengembangkan kapabilitas'],
        ['Harmonis', 'Saling peduli dan menghargai perbedaan'],
        ['Loyal', 'Berdedikasi dan mengutamakan kepentingan bangsa dan negara'],
        ['Adaptif', 'Terus berinovasi dan antusias dalam menggerakkan perubahan'],
        ['Kolaboratif', 'Membangun kerja sama yang sinergis'],
        [''],
        ['Grade:'],
        ['Excellent', '>= 4.5'],
        ['Good', '>= 4.0'],
        ['Fair', '>= 3.5'],
        ['Needs Improvement', '< 3.5'],
      ]);
      wsMeta['!cols'] = [{wch:22},{wch:60}];
      XLSX.utils.book_append_sheet(wb, wsMeta, 'Info');

      // ---- Sheet 2: Data Lengkap
      const header = ['No.','Nama Karyawan','Divisi','Level','Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif','Skor Komposit','Grade'];
      const rows = [header, ...dataWithScore.map((k,i) => [i+1,...k])];
      const ws2 = XLSX.utils.aoa_to_sheet(rows);
      ws2['!cols'] = [{wch:5},{wch:24},{wch:14},{wch:14},{wch:10},{wch:10},{wch:10},{wch:8},{wch:10},{wch:13},{wch:14},{wch:18}];
      XLSX.utils.book_append_sheet(wb, ws2, 'Data Lengkap');

      // ---- Sheet 3: Ringkasan per Divisi
      const byDiv = {};
      dataWithScore.forEach(k => {
        if (!byDiv[k[1]]) byDiv[k[1]] = [];
        byDiv[k[1]].push(k);
      });
      const divSummary = [['Divisi','Jumlah Karyawan','Avg Amanah','Avg Kompeten','Avg Harmonis','Avg Loyal','Avg Adaptif','Avg Kolaboratif','Avg Komposit']];
      Object.entries(byDiv).forEach(([div, list]) => {
        const avg = (idx) => (list.reduce((s,k)=>s+k[idx],0)/list.length).toFixed(2);
        divSummary.push([div, list.length, avg(3), avg(4), avg(5), avg(6), avg(7), avg(8), avg(9)]);
      });
      const ws3 = XLSX.utils.aoa_to_sheet(divSummary);
      ws3['!cols'] = divSummary[0].map(() => ({wch:18}));
      XLSX.utils.book_append_sheet(wb, ws3, 'Ringkasan Divisi');

      // ---- Sheet 4: Matrix AKHLAK (Top performers)
      const sorted = [...dataWithScore].sort((a,b)=>b[9]-a[9]);
      const matrixHeader = ['Rank','Nama','Divisi','Skor','Grade'];
      const matrixRows = [matrixHeader, ...sorted.slice(0,30).map((k,i)=>[i+1,k[0],k[1],k[9],k[10]])];
      const ws4 = XLSX.utils.aoa_to_sheet(matrixRows);
      ws4['!cols'] = [{wch:6},{wch:24},{wch:16},{wch:14},{wch:20}];
      XLSX.utils.book_append_sheet(wb, ws4, 'Top Performers');

      XLSX.writeFile(wb, 'AKHLAK360_Laporan_Semester1_2025.xlsx');
      hideProgressModal();
      showToast('Laporan Excel lengkap (4 sheet) berhasil diunduh!', 'success');
    } catch(e) {
      hideProgressModal();
      showToast('Gagal membuat Excel: ' + e.message, 'error');
    }
  }, 600);
}

// =====================================================
// CSV MATRIX EXPORT
// =====================================================
function exportMatrixCSV() {
  const headers = ['No.','Nama Karyawan','Divisi','Level','Amanah','Kompeten','Harmonis','Loyal','Adaptif','Kolaboratif','Skor Komposit'];
  const rows = [
    [1,'Ahmad Fauzi','Operations','Staff',4.3,4.1,4.5,4.0,3.8,4.4,4.18],
    [2,'Dewi Lestari','Finance','Senior Staff',4.6,4.5,4.7,4.4,4.3,4.5,4.50],
    [3,'Eko Prasetyo','IT','Staff',3.8,4.2,3.9,3.7,3.6,4.0,3.87],
    [4,'Siti Rahma','Marketing','Staff',4.1,3.9,4.2,4.0,3.9,4.2,4.05],
    [5,'Budi Santoso','HR','Senior Staff',4.4,4.3,4.5,4.2,4.1,4.4,4.32],
    [6,'Ratna Dewi','Finance','Manager',4.7,4.6,4.8,4.5,4.4,4.6,4.60],
    [7,'Hendra Kusuma','Operations','Supervisor',4.2,4.0,4.3,4.1,3.9,4.3,4.13],
    [8,'Maya Sari','Marketing','Staff',3.9,4.1,4.0,3.8,4.0,4.1,3.98],
    [9,'Rizki Pratama','IT','Senior Staff',4.0,4.4,4.1,3.9,4.2,4.2,4.13],
    [10,'Indah Permata','HR','Staff',4.3,4.0,4.4,4.2,3.8,4.3,4.17],
  ];

  const bom = '\uFEFF';
  const csvContent = bom + [headers, ...rows].map(r => r.map(v=>`"${v}"`).join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = 'AKHLAK360_Matrix_Nilai.csv'; a.click();
  URL.revokeObjectURL(url);
  showToast('Matrix CSV berhasil diunduh!', 'success');
}

// =====================================================
// LOAD EXTERNAL SCRIPT HELPER
// =====================================================
function loadScript(src, callback) {
  const existing = document.querySelector(`script[src="${src}"]`);
  if (existing) { if(callback) callback(); return; }
  const s = document.createElement('script');
  s.src = src; s.onload = callback || (() => {});
  document.head.appendChild(s);
}

// =====================================================
// WIRE UP EXPORT BUTTONS ON PAGE LOAD
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
  // Preload XLSX
  loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js');

  // --- Wire buttons by text content ---
  document.querySelectorAll('button, .btn, a.btn').forEach(btn => {
    const text = btn.textContent.trim().toLowerCase();

    if (text.includes('download xls') || text.includes('export xls') || text.includes('unduh xls')) {
      btn.addEventListener('click', (e) => { e.preventDefault(); generateAkhlakExcel(); });
    }
    else if (text.includes('download excel') || text.includes('export excel')) {
      btn.addEventListener('click', (e) => { e.preventDefault(); generateAkhlakExcel(); });
    }
    else if (text.includes('csv') || text.includes('matrix')) {
      if (btn.tagName !== 'A' || !btn.href || btn.href === '#') {
        btn.addEventListener('click', (e) => { e.preventDefault(); exportMatrixCSV(); });
      }
    }
    else if (text.includes('cetak') || text.includes('print')) {
      btn.addEventListener('click', (e) => { e.preventDefault(); exportToPrint(); });
    }
    else if (text.includes('pdf') || text.includes('executive summary')) {
      if (btn.tagName !== 'A' || !btn.href || btn.href === '#') {
        btn.addEventListener('click', (e) => { e.preventDefault(); exportToPDF(); });
      }
    }
    else if (text.includes('generate pack') || text.includes('generate laporan') || text.includes('unduh laporan')) {
      btn.addEventListener('click', (e) => { e.preventDefault(); generateAkhlakExcel(); });
    }
  });

  // --- Wire action buttons in laporan pages ---
  document.querySelectorAll('.action-btn').forEach(btn => {
    const title = btn.querySelector('.title');
    if (!title) return;
    const t = title.textContent.trim().toLowerCase();

    // Only add listener if not already an <a> with real href
    if (btn.tagName === 'A' && btn.href && !btn.href.endsWith('#')) return;

    if (t.includes('detail karyawan') || t.includes('xls') || t.includes('excel')) {
      btn.style.cursor = 'pointer';
      btn.addEventListener('click', () => generateAkhlakExcel());
    } else if (t.includes('matrix') || t.includes('csv')) {
      btn.style.cursor = 'pointer';
      btn.addEventListener('click', () => exportMatrixCSV());
    } else if (t.includes('executive') || t.includes('pdf')) {
      btn.style.cursor = 'pointer';
      btn.addEventListener('click', () => {
        showToast('Membuka dialog print untuk simpan PDF...', 'info');
        setTimeout(() => window.print(), 500);
      });
    } else if (t.includes('audit') || t.includes('zip')) {
      btn.style.cursor = 'pointer';
      btn.addEventListener('click', () => {
        showToast('Audit Trail — fitur ini tersedia di modul Admin IT.', 'info');
      });
    }
  });

  // --- Checkboxes for export options ---
  document.querySelectorAll('.action-btn input[type="checkbox"], .action-btn').forEach(el => {
    if (el.tagName === 'DIV' && !el.querySelector('a')) {
      el.style.cursor = 'pointer';
      el.addEventListener('click', () => {
        el.classList.toggle('selected');
        showToast(el.querySelector('.title')?.textContent + (el.classList.contains('selected') ? ' ✓ dipilih' : ' ✗ dihapus'), 'info');
      });
    }
  });
});

// Export globally
window.AKHLAK360Export = {
  toPDF: exportToPDF,
  toPrint: exportToPrint,
  toCSV: exportTableToCSV,
  toExcel: exportTableToExcel,
  generateReport: generateAkhlakExcel,
  matrixCSV: exportMatrixCSV,
  showToast
};
