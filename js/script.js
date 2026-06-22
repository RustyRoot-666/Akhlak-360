/**
 * AKHLAK360 - Shared JavaScript
 * Handles login, navigation, animations, charts, and interactions
 */

// --- Utility: Animate counters ---
function animateCounters() {
  const counters = document.querySelectorAll('[data-count]');
  counters.forEach((el, i) => {
    const target = parseFloat(el.dataset.count);
    const decimals = el.dataset.decimals || (target % 1 !== 0 ? 2 : 0);
    const duration = 1200;
    const delay = i * 100;
    const startTime = performance.now() + delay;

    function update(now) {
      if (now < startTime) { requestAnimationFrame(update); return; }
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = target * eased;
      el.textContent = decimals > 0 ? current.toFixed(decimals) : Math.round(current);
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  });
}

// --- Utility: Animate progress bars ---
function animateProgressBars() {
  const bars = document.querySelectorAll('[data-progress]');
  bars.forEach((bar, i) => {
    const target = bar.dataset.progress;
    setTimeout(() => {
      bar.style.width = target + '%';
    }, i * 80);
  });
}

// --- Utility: Initialize radar chart ---
function initRadarChart(canvasId, scores) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  const labels = ['Amanah', 'Kompeten', 'Harmonis', 'Loyal', 'Adaptif', 'Kolaboratif'];
  const colors = ['#1565C0', '#2E7D32', '#E65100', '#C62828', '#4527A0', '#6D4C41'];

  // Single dataset
  const datasets = [{
    label: 'Nilai',
    data: scores,
    backgroundColor: 'rgba(27, 42, 74, 0.08)',
    borderColor: '#1B2A4A',
    borderWidth: 2,
    pointBackgroundColor: colors,
    pointBorderColor: colors,
    pointRadius: 4,
    pointHoverRadius: 6,
  }];

  // If second dataset provided (comparison)
  if (window.radarCompareData && canvasId === 'radar-compare') {
    datasets.push({
      label: 'Q1 2025',
      data: window.radarCompareData,
      backgroundColor: 'rgba(230, 81, 0, 0.05)',
      borderColor: '#E65100',
      borderWidth: 2,
      borderDash: [5, 5],
      pointBackgroundColor: '#E65100',
      pointBorderColor: '#E65100',
      pointRadius: 3,
    });
  }

  return new Chart(ctx, {
    type: 'radar',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      animation: { duration: 1000, easing: 'easeOutQuart' },
      scales: {
        r: {
          min: 1,
          max: 5,
          ticks: { stepSize: 1, font: { size: 10 }, color: '#64748B' },
          grid: { color: '#E8E0D8' },
          angleLines: { color: '#E8E0D8' },
          pointLabels: { font: { size: 11, weight: '500' }, color: '#1B2A4A' }
        }
      },
      plugins: {
        legend: { display: datasets.length > 1, position: 'bottom', labels: { font: { size: 11 } } }
      }
    }
  });
}

// --- Login Page Logic ---
function initLogin() {
  const roleTabs = document.querySelectorAll('.role-tab');
  const loginBtn = document.getElementById('loginBtn');

  // Role tab switching
  roleTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      roleTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    });
  });

  // Password toggle
  const pwToggles = document.querySelectorAll('.toggle-pw');
  pwToggles.forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.password-wrap').querySelector('input');
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
      lucide.createIcons();
    });
  });

  // Login form submission
  if (loginBtn) {
    loginBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const activeTab = document.querySelector('.role-tab.active');
      const role = activeTab ? activeTab.dataset.role : 'karyawan';

      // Redirect based on role
      const pages = {
        karyawan: 'karyawan-dashboard.html',
        manager: 'manager-dashboard.html',
        adminhrd: 'hrd-dashboard.html',
        adminit: 'adminit-dashboard.html'
      };
      window.location.href = pages[role] || pages.karyawan;
    });
  }

  // Auto-select role from URL hash
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    const tab = document.querySelector(`.role-tab[data-role="${hash}"]`);
    if (tab) {
      roleTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    }
  }
}

// --- Evaluation Form Logic ---
function initEvalForm() {
  const tabs = document.querySelectorAll('.dim-tab');
  const sections = document.querySelectorAll('.dim-section');
  const progressBar = document.getElementById('progressBar');
  const progressText = document.getElementById('progressText');
  let answered = 0;
  const totalQuestions = document.querySelectorAll('.question-row').length;

  function updateProgress() {
    const pct = Math.round((answered / totalQuestions) * 100);
    if (progressBar) progressBar.style.width = pct + '%';
    if (progressText) progressText.textContent = pct + '% (' + answered + '/' + totalQuestions + ' pertanyaan)';
  }

  // Tab switching
  tabs.forEach((tab, idx) => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      sections.forEach(s => s.style.display = 'none');
      const target = document.getElementById('dim-' + tab.dataset.dim);
      if (target) {
        target.style.display = 'block';
        target.style.animation = 'fadeIn 0.2s';
      }
    });
  });

  // Radio button selection
  document.querySelectorAll('.radio-label input').forEach(radio => {
    radio.addEventListener('change', () => {
      const wasAnswered = radio.closest('.question-row').dataset.answered === 'true';
      if (!wasAnswered) {
        answered++;
        radio.closest('.question-row').dataset.answered = 'true';
      }
      updateProgress();
    });
  });

  // Next/Prev navigation
  const nextBtn = document.getElementById('nextBtn');
  const prevBtn = document.getElementById('prevBtn');
  let currentTab = 0;

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (currentTab < tabs.length - 1) {
        currentTab++;
        tabs[currentTab].click();
      } else {
        // Submit - redirect to success
        window.location.href = 'karyawan-success.html';
      }
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentTab > 0) {
        currentTab--;
        tabs[currentTab].click();
      }
    });
  }
}

// --- Sidebar active state ---
function setActiveNav() {
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && href.includes(currentPage)) {
      item.classList.add('active');
    }
  });
}

// --- Initialize on DOM ready ---
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Login page
  initLogin();

  // Evaluation form
  initEvalForm();

  // Sidebar nav
  setActiveNav();

  // Counters
  animateCounters();

  // Progress bars
  animateProgressBars();

  // Radar charts
  if (typeof Chart !== 'undefined') {
    // Karyawan dashboard radar
    initRadarChart('radar-karyawan', [4.2, 3.8, 4.5, 4.0, 3.5, 4.3]);
    // Karyawan nilai detail radar
    initRadarChart('radar-nilai', [4.2, 3.8, 4.5, 4.0, 3.5, 4.3]);
    // Manager dashboard team radar
    initRadarChart('radar-team', [4.2, 3.8, 4.5, 4.0, 3.5, 4.3]);
    // Manager performa radar
    window.radarCompareData = [3.9, 3.6, 4.2, 3.8, 3.4, 4.1];
    initRadarChart('radar-performa', [4.2, 3.8, 4.5, 4.0, 3.5, 4.3]);
    // Manager detail skor radar
    initRadarChart('radar-detail', [4.2, 3.8, 4.5, 4.0, 3.5, 4.3]);
  }
});
