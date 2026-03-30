/* ============================================================
   Multi Tools — Global JavaScript
   Digunakan oleh seluruh halaman di dalam proyek ini.
   Import via: <script src="/assets/js/main.js" defer></script>
   ============================================================ */

/* ── Navbar: Toggle Dropdown ── */
function toggleDropdown(id) {
  const all = document.querySelectorAll('.nav-group');
  all.forEach(g => { if (g.id !== id) g.classList.remove('open'); });
  document.getElementById(id)?.classList.toggle('open');
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', e => {
  if (!e.target.closest('.nav-group')) {
    document.querySelectorAll('.nav-group').forEach(g => g.classList.remove('open'));
  }
});

// Tutup dropdown saat tekan Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.nav-group').forEach(g => g.classList.remove('open'));
  }
});

/* ── Counter Animasi (Stats Bar) ── */
function animateCount(el, target, duration = 1400) {
  let start = null;
  const step = ts => {
    if (!start) start = ts;
    const p = Math.min((ts - start) / duration, 1);
    const ease = 1 - Math.pow(1 - p, 3); // cubic ease-out
    el.textContent = Math.floor(ease * target);
    if (p < 1) requestAnimationFrame(step);
    else el.textContent = target;
  };
  requestAnimationFrame(step);
}

const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const target = +e.target.dataset.target;
      animateCount(e.target, target);
      counterObserver.unobserve(e.target);
    }
  });
}, { threshold: .5 });

document.querySelectorAll('[data-target]').forEach(el => counterObserver.observe(el));

/* ── Copy to Clipboard ── */
function copyToClipboard(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const original = btn?.textContent;
    if (btn) {
      btn.textContent = '✓ Tersalin!';
      btn.style.color = 'var(--accent5)';
      setTimeout(() => {
        btn.textContent = original;
        btn.style.color = '';
      }, 2000);
    }
  }).catch(() => {
    // Fallback untuk browser lama
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  });
}

// Auto-init tombol copy: <button class="copy-btn" data-copy-target="id-element">
document.querySelectorAll('[data-copy-target]').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = document.getElementById(btn.dataset.copyTarget);
    const text = target?.value || target?.textContent || '';
    copyToClipboard(text, btn);
  });
});

// Auto-init: <button data-copy-text="teks langsung">
document.querySelectorAll('[data-copy-text]').forEach(btn => {
  btn.addEventListener('click', () => {
    copyToClipboard(btn.dataset.copyText, btn);
  });
});

/* ── Toast Notification ── */
function showToast(message, type = 'info', duration = 3000) {
  const existing = document.querySelector('.mt-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = `mt-toast mt-toast--${type}`;
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed; bottom: 1.5rem; right: 1.5rem;
    padding: .75rem 1.25rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: .875rem; font-weight: 600;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    z-index: 9998;
    animation: fadeUp .3s forwards;
    max-width: 320px;
    color: var(--text);
  `;
  if (type === 'success') toast.style.borderColor = 'var(--accent5)';
  if (type === 'error')   toast.style.borderColor = '#ef4444';
  if (type === 'warning') toast.style.borderColor = 'var(--accent4)';

  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity .3s';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/* ── Highlight Nav Item Aktif ── */
(function highlightActiveNav() {
  const path = window.location.pathname;
  document.querySelectorAll('.dropdown a').forEach(link => {
    if (link.getAttribute('href') && path.startsWith(link.getAttribute('href'))) {
      link.style.color = 'var(--accent)';
      link.style.background = 'rgba(37,99,235,.07)';
      // Buka parent dropdown group
      const group = link.closest('.nav-group');
      if (group) {
        const btn = group.querySelector('.nav-btn');
        if (btn) btn.style.color = 'var(--text)';
      }
    }
  });
})();

/* ── Breadcrumb Generator (opsional) ──
   Gunakan dengan: <nav class="breadcrumb" id="auto-breadcrumb"></nav>
   Akan auto-generate berdasarkan URL path.
── */
(function generateBreadcrumb() {
  const nav = document.getElementById('auto-breadcrumb');
  if (!nav) return;

  const segments = window.location.pathname.split('/').filter(Boolean);
  const items = [{ label: 'Beranda', href: '/' }];

  let cumulative = '';
  segments.forEach((seg, i) => {
    cumulative += '/' + seg;
    const label = seg.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    items.push({ label, href: cumulative + (i < segments.length - 1 ? '/' : '') });
  });

  nav.innerHTML = items.map((item, i) => {
    const isLast = i === items.length - 1;
    if (isLast) return `<span class="current">${item.label}</span>`;
    return `<a href="${item.href}">${item.label}</a><span class="sep" aria-hidden="true">/</span>`;
  }).join('');
})();