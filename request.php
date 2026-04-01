<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Request Tools — Multi Tools</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <style>
    /* ── Form Steps ── */
    .steps-indicator {
      display: flex; align-items: center; gap: 0;
      margin-bottom: 2.5rem;
    }
    .step-node {
      display: flex; flex-direction: column; align-items: center;
      gap: .3rem; flex: 1;
      position: relative;
    }
    .step-node:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 16px; left: 50%; right: -50%;
      height: 2px;
      background: var(--border);
      z-index: 0;
      transition: background .3s;
    }
    .step-node.done::after { background: var(--accent); }

    .step-circle {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: var(--surface);
      border: 2px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: .75rem; font-weight: 700;
      font-family: var(--font-mono);
      color: var(--muted);
      transition: all .3s;
      z-index: 1;
      position: relative;
    }
    .step-node.active .step-circle {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
      box-shadow: 0 0 0 4px rgba(37,99,235,.15);
    }
    .step-node.done .step-circle {
      background: var(--accent5);
      border-color: var(--accent5);
      color: #fff;
    }
    .step-label {
      font-size: .68rem; font-family: var(--font-mono);
      color: var(--muted); letter-spacing: .05em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .step-node.active .step-label { color: var(--accent); font-weight: 700; }
    .step-node.done  .step-label  { color: var(--accent5); }

    /* ── Form Panels ── */
    .form-step { display: none; }
    .form-step.active { display: block; animation: fadeUp .35s forwards; }

    /* ── Category Picker ── */
    .cat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: .75rem;
      margin-bottom: 1.25rem;
    }
    .cat-pick {
      display: flex; flex-direction: column;
      align-items: center; gap: .5rem;
      padding: 1rem .75rem;
      background: var(--bg);
      border: 2px solid var(--border);
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: all .15s;
      text-align: center;
      font-size: .82rem; font-weight: 600;
      color: var(--muted);
    }
    .cat-pick .ce { font-size: 1.6rem; }
    .cat-pick:hover { border-color: var(--accent); color: var(--text); }
    .cat-pick.selected {
      border-color: var(--accent);
      background: rgba(37,99,235,.06);
      color: var(--accent);
    }
    input[type="radio"].cat-radio { display: none; }

    /* ── Priority Slider ── */
    .priority-group {
      display: flex; gap: .5rem;
      flex-wrap: wrap;
    }
    .priority-btn {
      flex: 1; min-width: 80px;
      padding: .6rem .5rem;
      border: 2px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--bg);
      font-family: var(--font-body);
      font-size: .82rem; font-weight: 700;
      color: var(--muted);
      cursor: pointer;
      transition: all .15s;
      text-align: center;
    }
    .priority-btn:hover { border-color: var(--muted); color: var(--text); }
    .priority-btn.selected-low    { border-color: var(--accent5); background: #f0fdf4; color: var(--accent5); }
    .priority-btn.selected-medium { border-color: var(--accent4); background: #fffbeb; color: var(--accent4); }
    .priority-btn.selected-high   { border-color: #ef4444; background: #fef2f2; color: #ef4444; }

    /* ── Upvote List ── */
    .request-list { display: flex; flex-direction: column; gap: .75rem; }
    .request-item {
      display: flex; align-items: center; gap: 1rem;
      padding: 1rem 1.25rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      transition: border-color .15s, transform .15s;
    }
    .request-item:hover {
      border-color: var(--border);
      transform: translateX(3px);
    }
    .request-emoji { font-size: 1.5rem; flex-shrink: 0; }
    .request-info { flex: 1; min-width: 0; }
    .request-name { font-weight: 700; font-size: .9rem; margin-bottom: .15rem; }
    .request-meta { font-size: .78rem; color: var(--muted); display: flex; gap: .75rem; flex-wrap: wrap; }
    .request-meta .rcat {
      font-family: var(--font-mono);
      font-size: .65rem;
      padding: 2px 7px;
      border-radius: 4px;
      background: rgba(37,99,235,.08);
      color: var(--accent);
    }
    .upvote-btn {
      display: flex; flex-direction: column; align-items: center; gap: .15rem;
      padding: .5rem .75rem;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--bg);
      cursor: pointer;
      font-family: var(--font-body);
      transition: all .15s;
      flex-shrink: 0;
    }
    .upvote-btn:hover { border-color: var(--accent); background: rgba(37,99,235,.05); }
    .upvote-btn.voted {
      border-color: var(--accent);
      background: rgba(37,99,235,.08);
      color: var(--accent);
    }
    .upvote-btn .uv-icon { font-size: 1rem; }
    .upvote-btn .uv-count {
      font-family: var(--font-mono);
      font-size: .7rem; font-weight: 700;
    }
    .upvote-btn.voted .uv-count { color: var(--accent); }

    /* ── Success State ── */
    .success-state {
      display: none;
      text-align: center;
      padding: 3rem 2rem;
      animation: fadeUp .5s forwards;
    }
    .success-state .big-icon { font-size: 4rem; margin-bottom: 1rem; }
    .success-state h2 { font-size: 1.75rem; font-weight: 800; letter-spacing: -.03em; margin-bottom: .5rem; }
    .success-state p { color: var(--muted); margin-bottom: 1.5rem; }

    /* ── Tips panel ── */
    .tip-list { display: flex; flex-direction: column; gap: .65rem; }
    .tip-item {
      display: flex; gap: .7rem; align-items: flex-start;
      font-size: .82rem; color: var(--muted); line-height: 1.5;
    }
    .tip-item .tip-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--accent); flex-shrink: 0; margin-top: 6px;
    }

    /* ── Char counter ── */
    .char-count {
      font-family: var(--font-mono);
      font-size: .7rem; color: var(--muted);
      text-align: right; margin-top: .25rem;
    }
    .char-count.warn { color: var(--accent4); }
    .char-count.over { color: #ef4444; }
  </style>
</head>
<body>

<a class="skip-link" href="#main">Skip to content</a>

<!-- NAVBAR -->
<nav>
  <a href="/" class="nav-logo">Multi<span class="dot">.</span>Tools</a>
  <span class="nav-separator"></span>
  <div class="nav-group" id="group-tools">
    <button class="nav-btn" onclick="toggleDropdown('group-tools')">
      Tools
      <svg class="chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="dropdown">
      <div class="dropdown-label">Utilitas</div>
      <a href="/tools/text"><span class="icon" style="background:#eff6ff">📝</span> Text Tools <span class="tag hot">HOT</span></a>
      <a href="/tools/image"><span class="icon" style="background:#f0fdf4">🖼️</span> Image Tools</a>
      <a href="/tools/convert"><span class="icon" style="background:#fefce8">🔄</span> Converter</a>
      <div class="dropdown-sep"></div>
      <a href="/tools/dev"><span class="icon" style="background:#faf5ff">💻</span> Dev Tools <span class="tag new">NEW</span></a>
    </div>
  </div>
  <div class="nav-group" id="group-pages">
    <button class="nav-btn" onclick="toggleDropdown('group-pages')">
      Halaman
      <svg class="chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="dropdown">
      <a href="/about"><span class="icon" style="background:#eff6ff">ℹ️</span> Tentang Kami</a>
      <a href="/sitemap"><span class="icon" style="background:#f0fdf4">🗺️</span> Sitemap</a>
      <a href="/request"><span class="icon" style="background:#fefce8">💡</span> Request Tools</a>
    </div>
  </div>
</nav>

<!-- BREADCRUMB -->
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="/">Beranda</a>
  <span class="sep">/</span>
  <span class="current">Request Tools</span>
</nav>

<main id="main">
  <div class="tool-layout">

    <!-- ── LEFT: FORM ── -->
    <div>
      <!-- Header -->
      <div style="margin-bottom:2rem">
        <div class="page-title">Request <span>Tool</span> Baru 💡</div>
        <p class="page-lead">Punya ide tool yang belum ada? Ceritakan kepada kami! Setiap request dibaca langsung oleh tim dan diprioritaskan berdasarkan jumlah upvote.</p>
      </div>

      <!-- Steps Indicator -->
      <div class="steps-indicator" id="stepsIndicator">
        <div class="step-node active" id="sn1">
          <div class="step-circle">1</div>
          <span class="step-label">Detail</span>
        </div>
        <div class="step-node" id="sn2">
          <div class="step-circle">2</div>
          <span class="step-label">Kategori</span>
        </div>
        <div class="step-node" id="sn3">
          <div class="step-circle">3</div>
          <span class="step-label">Konfirmasi</span>
        </div>
      </div>

      <!-- FORM -->
      <div class="panel" id="requestPanel">

        <!-- STEP 1 -->
        <div class="form-step active" id="step1">
          <p class="panel-title">📝 Detail Tool</p>

          <div class="form-group">
            <label for="toolName">Nama Tool <span style="color:#ef4444">*</span></label>
            <input type="text" id="toolName" placeholder="Contoh: QR Code Generator" maxlength="60" />
            <div class="char-count" id="nameCount">0 / 60</div>
          </div>

          <div class="form-group">
            <label for="toolDesc">Deskripsi Singkat <span style="color:#ef4444">*</span></label>
            <textarea id="toolDesc" placeholder="Jelaskan apa yang dilakukan tool ini dan mengapa berguna…" maxlength="500" rows="4"></textarea>
            <div class="char-count" id="descCount">0 / 500</div>
          </div>

          <div class="form-group">
            <label for="toolUseCase">Kasus Penggunaan</label>
            <textarea id="toolUseCase" placeholder="Contoh: Saya sering butuh membuat QR code untuk tautan produk, tapi situs lain lambat dan punya iklan…" rows="3" maxlength="400"></textarea>
          </div>

          <div class="form-group">
            <label for="toolRef">Referensi (opsional)</label>
            <input type="url" id="toolRef" placeholder="https://contoh-tool-serupa.com" />
          </div>

          <div style="display:flex; justify-content:flex-end; margin-top:.5rem">
            <button class="btn-primary" onclick="goStep(2)">Lanjut →</button>
          </div>
        </div>

        <!-- STEP 2 -->
        <div class="form-step" id="step2">
          <p class="panel-title">🏷️ Kategori & Prioritas</p>

          <div class="form-group">
            <label>Kategori Tool <span style="color:#ef4444">*</span></label>
            <div class="cat-grid" id="catGrid">
              <label class="cat-pick" id="cp-text">
                <input type="radio" name="category" value="text" class="cat-radio" />
                <span class="ce">📝</span> Text
              </label>
              <label class="cat-pick" id="cp-image">
                <input type="radio" name="category" value="image" class="cat-radio" />
                <span class="ce">🖼️</span> Image
              </label>
              <label class="cat-pick" id="cp-convert">
                <input type="radio" name="category" value="convert" class="cat-radio" />
                <span class="ce">🔄</span> Converter
              </label>
              <label class="cat-pick" id="cp-dev">
                <input type="radio" name="category" value="dev" class="cat-radio" />
                <span class="ce">💻</span> Dev
              </label>
              <label class="cat-pick" id="cp-seo">
                <input type="radio" name="category" value="seo" class="cat-radio" />
                <span class="ce">📈</span> SEO
              </label>
              <label class="cat-pick" id="cp-math">
                <input type="radio" name="category" value="math" class="cat-radio" />
                <span class="ce">🔢</span> Math
              </label>
              <label class="cat-pick" id="cp-other">
                <input type="radio" name="category" value="other" class="cat-radio" />
                <span class="ce">✨</span> Lainnya
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Seberapa Penting Ini Bagimu?</label>
            <div class="priority-group">
              <button type="button" class="priority-btn" data-priority="low"    onclick="setPriority('low')">🟢 Santai</button>
              <button type="button" class="priority-btn" data-priority="medium" onclick="setPriority('medium')">🟡 Perlu</button>
              <button type="button" class="priority-btn" data-priority="high"   onclick="setPriority('high')">🔴 Sangat Butuh</button>
            </div>
          </div>

          <div class="form-group">
            <label for="reqEmail">Email Kamu (opsional)</label>
            <input type="email" id="reqEmail" placeholder="untuk notifikasi saat tool live" />
          </div>

          <div style="display:flex; justify-content:space-between; margin-top:.5rem">
            <button class="btn-ghost" onclick="goStep(1)">← Kembali</button>
            <button class="btn-primary" onclick="goStep(3)">Review →</button>
          </div>
        </div>

        <!-- STEP 3 -->
        <div class="form-step" id="step3">
          <p class="panel-title">✅ Konfirmasi Request</p>

          <div class="result-box" id="reviewBox" style="margin-bottom:1.25rem; white-space:pre-wrap;"></div>

          <div class="alert info">
            <span>ℹ️</span>
            <span>Dengan mengirim request, kamu menyetujui bahwa ide ini akan dibagikan secara anonim kepada komunitas Multi Tools untuk di-upvote.</span>
          </div>

          <div style="display:flex; justify-content:space-between; margin-top:1rem">
            <button class="btn-ghost" onclick="goStep(2)">← Kembali</button>
            <button class="btn-primary" onclick="submitRequest()">🚀 Kirim Request</button>
          </div>
        </div>

      </div><!-- /panel -->

      <!-- SUCCESS -->
      <div class="success-state" id="successState">
        <div class="big-icon">🎉</div>
        <h2>Request Terkirim!</h2>
        <p>Terima kasih! Tim kami akan meninjau request kamu. Kamu bisa upvote request di bawah agar diprioritaskan lebih cepat.</p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
          <button class="btn-primary" onclick="resetForm()">+ Kirim Request Lagi</button>
          <a href="/sitemap" class="btn-ghost">Lihat Semua Tools</a>
        </div>
      </div>

    </div><!-- /left -->

    <!-- ── RIGHT: SIDEBAR ── -->
    <aside>
      <!-- Tips -->
      <div class="panel" style="margin-bottom:1.5rem">
        <p class="panel-title">💡 Tips Request Bagus</p>
        <div class="tip-list">
          <div class="tip-item"><div class="tip-dot"></div> Jelaskan masalah yang ingin dipecahkan, bukan hanya nama tool.</div>
          <div class="tip-item"><div class="tip-dot"></div> Sertakan referensi tool serupa agar tim lebih mudah memahami.</div>
          <div class="tip-item"><div class="tip-dot"></div> Spesifik lebih baik daripada umum — "Image Resize ke Rasio 16:9" lebih jelas dari "Image Tool".</div>
          <div class="tip-item"><div class="tip-dot"></div> Request dengan upvote banyak akan didahulukan.</div>
        </div>
      </div>

      <!-- Status -->
      <div class="panel" style="margin-bottom:1.5rem">
        <p class="panel-title">📊 Status Request</p>
        <div style="display:flex;flex-direction:column;gap:.65rem">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.85rem;font-weight:600">Menunggu Review</span>
            <span class="badge warning">23</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.85rem;font-weight:600">Dalam Pengerjaan</span>
            <span class="badge accent">4</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.85rem;font-weight:600">Selesai Bulan Ini</span>
            <span class="badge success">7</span>
          </div>
        </div>
      </div>

      <!-- Upvote Popular -->
      <div class="panel">
        <p class="panel-title">🔥 Request Populer</p>
        <div class="request-list">
          <div class="request-item">
            <div class="request-emoji">📱</div>
            <div class="request-info">
              <div class="request-name">QR Code Generator</div>
              <div class="request-meta"><span class="rcat">Dev</span> 3 hari lalu</div>
            </div>
            <button class="upvote-btn" onclick="toggleUpvote(this, 142)">
              <span class="uv-icon">▲</span>
              <span class="uv-count">142</span>
            </button>
          </div>
          <div class="request-item">
            <div class="request-emoji">📊</div>
            <div class="request-info">
              <div class="request-name">Chart Builder</div>
              <div class="request-meta"><span class="rcat">Converter</span> 1 minggu lalu</div>
            </div>
            <button class="upvote-btn" onclick="toggleUpvote(this, 98)">
              <span class="uv-icon">▲</span>
              <span class="uv-count">98</span>
            </button>
          </div>
          <div class="request-item">
            <div class="request-emoji">🎵</div>
            <div class="request-info">
              <div class="request-name">Audio Converter</div>
              <div class="request-meta"><span class="rcat">Converter</span> 2 minggu lalu</div>
            </div>
            <button class="upvote-btn" onclick="toggleUpvote(this, 76)">
              <span class="uv-icon">▲</span>
              <span class="uv-count">76</span>
            </button>
          </div>
          <div class="request-item">
            <div class="request-emoji">🔏</div>
            <div class="request-info">
              <div class="request-name">PDF Password Remover</div>
              <div class="request-meta"><span class="rcat">Dev</span> 3 minggu lalu</div>
            </div>
            <button class="upvote-btn" onclick="toggleUpvote(this, 54)">
              <span class="uv-icon">▲</span>
              <span class="uv-count">54</span>
            </button>
          </div>
          <div class="request-item">
            <div class="request-emoji">💬</div>
            <div class="request-info">
              <div class="request-name">Subtitle Translator</div>
              <div class="request-meta"><span class="rcat">Text</span> 1 bulan lalu</div>
            </div>
            <button class="upvote-btn" onclick="toggleUpvote(this, 41)">
              <span class="uv-icon">▲</span>
              <span class="uv-count">41</span>
            </button>
          </div>
        </div>
      </div>

    </aside>

  </div><!-- /tool-layout -->
</main>

<!-- FOOTER -->
<footer>
  <span class="footer-logo">Multi<span class="dot">.</span>Tools</span>
  <span>© 2025 Multi Tools. Dibuat dengan ❤️ untuk semua orang.</span>
  <span>
    <a href="/about">Tentang</a> ·
    <a href="/sitemap">Sitemap</a> ·
    <a href="/request">Request</a>
  </span>
</footer>

<script src="assets/js/main.js" defer></script>
<script>
  /* ── Multi-step form ── */
  let currentStep = 1;
  let selectedCategory = '';
  let selectedPriority = '';

  function goStep(n) {
    // Validate step 1
    if (n > 1) {
      const name = document.getElementById('toolName').value.trim();
      const desc = document.getElementById('toolDesc').value.trim();
      if (!name || !desc) {
        showToast('Nama dan deskripsi tool wajib diisi.', 'error');
        return;
      }
    }
    // Validate step 2
    if (n > 2) {
      if (!selectedCategory) {
        showToast('Pilih kategori tool terlebih dahulu.', 'error');
        return;
      }
      // Populate review
      const priority = selectedPriority
        ? { low: '🟢 Santai', medium: '🟡 Perlu', high: '🔴 Sangat Butuh' }[selectedPriority]
        : '—';
      document.getElementById('reviewBox').textContent =
        `Nama Tool   : ${document.getElementById('toolName').value.trim()}\n` +
        `Deskripsi   : ${document.getElementById('toolDesc').value.trim()}\n` +
        `Kategori    : ${selectedCategory}\n` +
        `Prioritas   : ${priority}\n` +
        `Email       : ${document.getElementById('reqEmail').value.trim() || '—'}\n` +
        `Referensi   : ${document.getElementById('toolRef').value.trim() || '—'}`;
    }

    document.getElementById(`step${currentStep}`).classList.remove('active');
    document.getElementById(`step${n}`).classList.add('active');

    // Update step nodes
    for (let i = 1; i <= 3; i++) {
      const node = document.getElementById(`sn${i}`);
      node.classList.remove('active', 'done');
      if (i < n) node.classList.add('done');
      if (i === n) node.classList.add('active');
      // Update checkmark
      if (i < n) node.querySelector('.step-circle').textContent = '✓';
      else node.querySelector('.step-circle').textContent = i;
    }
    currentStep = n;
  }

  function setPriority(p) {
    selectedPriority = p;
    document.querySelectorAll('.priority-btn').forEach(btn => {
      btn.classList.remove('selected-low', 'selected-medium', 'selected-high');
    });
    document.querySelector(`[data-priority="${p}"]`).classList.add(`selected-${p}`);
  }

  // Category picker
  document.querySelectorAll('.cat-radio').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.cat-pick').forEach(p => p.classList.remove('selected'));
      radio.closest('.cat-pick').classList.add('selected');
      selectedCategory = radio.value;
    });
  });

  function submitRequest() {
    document.getElementById('requestPanel').style.display = 'none';
    document.getElementById('stepsIndicator').style.display = 'none';
    document.getElementById('successState').style.display = 'block';
    showToast('Request berhasil dikirim! 🎉', 'success');
  }

  function resetForm() {
    document.getElementById('toolName').value = '';
    document.getElementById('toolDesc').value = '';
    document.getElementById('toolUseCase').value = '';
    document.getElementById('toolRef').value = '';
    document.getElementById('reqEmail').value = '';
    selectedCategory = ''; selectedPriority = '';
    document.querySelectorAll('.cat-pick').forEach(p => p.classList.remove('selected'));
    document.querySelectorAll('.priority-btn').forEach(b => b.classList.remove('selected-low','selected-medium','selected-high'));
    document.getElementById('successState').style.display = 'none';
    document.getElementById('requestPanel').style.display = '';
    document.getElementById('stepsIndicator').style.display = '';
    document.getElementById('nameCount').textContent = '0 / 60';
    document.getElementById('descCount').textContent = '0 / 500';
    currentStep = 1;
    goStep(1);
  }

  /* ── Char counters ── */
  function setupCharCount(inputId, countId, max) {
    const input = document.getElementById(inputId);
    const count = document.getElementById(countId);
    input.addEventListener('input', () => {
      const len = input.value.length;
      count.textContent = `${len} / ${max}`;
      count.classList.remove('warn', 'over');
      if (len > max * .85) count.classList.add('warn');
      if (len >= max) count.classList.add('over');
    });
  }
  setupCharCount('toolName', 'nameCount', 60);
  setupCharCount('toolDesc', 'descCount', 500);

  /* ── Upvote toggle ── */
  function toggleUpvote(btn, baseCount) {
    const isVoted = btn.classList.toggle('voted');
    const countEl = btn.querySelector('.uv-count');
    countEl.textContent = isVoted ? baseCount + 1 : baseCount;
    if (isVoted) showToast('Upvote tercatat! 👍', 'success');
  }
</script>
</body>
</html>