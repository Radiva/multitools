<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'save_color') {
        $color = htmlspecialchars($_POST['color'] ?? '#000000');
        $name  = htmlspecialchars($_POST['name']  ?? 'Color');
        echo json_encode(['success' => true, 'color' => $color, 'name' => $name]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Color Picker — Multi Tools</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  /* ── Color Picker Specific Styles ── */

  /* Preview swatch */
  .color-preview {
    width: 100%;
    height: 160px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    transition: background 0.25s ease;
    position: relative;
    overflow: hidden;
    cursor: pointer;
  }
  .color-preview::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 50%);
    pointer-events: none;
  }

  .color-hex-display {
    font-family: var(--font-mono);
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -.02em;
    cursor: pointer;
    margin: 1rem 0 .25rem;
    transition: color var(--transition);
    display: flex;
    align-items: center;
    gap: .5rem;
  }
  .color-hex-display:hover { color: var(--accent); }

  /* Native color input */
  input[type="color"] {
    width: 100%;
    height: 52px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg);
    cursor: pointer;
    padding: 4px;
    transition: border-color var(--transition);
  }
  input[type="color"]:focus { border-color: var(--accent); }
  input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; border-radius: 4px; }
  input[type="color"]::-webkit-color-swatch { border: none; border-radius: 4px; }

  /* Tab row for RGB/HSL */
  .tab-row {
    display: flex;
    gap: .4rem;
    margin-bottom: 1rem;
  }
  .tab-btn {
    font-family: var(--font-mono);
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .35rem .9rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    transition: all var(--transition);
  }
  .tab-btn.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }
  .tab-btn:hover:not(.active) {
    color: var(--text);
    border-color: var(--muted);
  }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  /* Channel sliders */
  .channel-row {
    display: grid;
    grid-template-columns: 22px 1fr 54px;
    align-items: center;
    gap: .75rem;
    margin-bottom: .85rem;
  }
  .ch-label {
    font-family: var(--font-mono);
    font-size: .75rem;
    font-weight: 700;
    color: var(--muted);
    text-align: center;
  }
  input[type="range"] {
    -webkit-appearance: none;
    width: 100%;
    height: 5px;
    border-radius: 3px;
    outline: none;
    cursor: pointer;
  }
  input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: var(--surface);
    border: 2px solid var(--accent);
    cursor: pointer;
    transition: transform .15s;
  }
  input[type="range"]::-webkit-slider-thumb:hover { transform: scale(1.2); }

  #range-r { background: linear-gradient(to right, #000, #ff0000); }
  #range-g { background: linear-gradient(to right, #000, #00cc00); }
  #range-b { background: linear-gradient(to right, #000, #0066ff); }
  #range-h { background: linear-gradient(to right, hsl(0,100%,50%), hsl(60,100%,50%), hsl(120,100%,50%), hsl(180,100%,50%), hsl(240,100%,50%), hsl(300,100%,50%), hsl(360,100%,50%)); }
  #range-s { background: linear-gradient(to right, #888, #ff4444); }
  #range-l { background: linear-gradient(to right, #000, #888, #fff); }

  /* Editable channel value inputs */
  .ch-input {
    font-family: var(--font-mono);
    font-size: .8rem;
    font-weight: 700;
    text-align: center;
    color: var(--text);
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: .2rem .3rem;
    width: 100%;
    transition: border-color var(--transition), box-shadow var(--transition);
    -moz-appearance: textfield;
  }
  .ch-input::-webkit-outer-spin-button,
  .ch-input::-webkit-inner-spin-button { -webkit-appearance: none; }
  .ch-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    outline: none;
  }
  .ch-input.invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,.12);
  }

  /* Editable HEX input in preview panel */
  .hex-input-wrap {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 1rem 0 .25rem;
  }
  .hex-prefix {
    font-family: var(--font-mono);
    font-size: 2rem;
    font-weight: 700;
    color: var(--muted);
    line-height: 1;
    user-select: none;
  }
  .hex-editable {
    font-family: var(--font-mono);
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -.02em;
    color: var(--text);
    background: transparent;
    border: none;
    border-bottom: 2px solid var(--border);
    border-radius: 0;
    padding: 0 .1rem;
    width: 7ch;
    text-transform: uppercase;
    transition: border-color var(--transition), color var(--transition);
    outline: none;
    box-shadow: none;
  }
  .hex-editable:focus {
    border-bottom-color: var(--accent);
    color: var(--accent);
    box-shadow: none;
  }
  .hex-editable.invalid {
    border-bottom-color: #ef4444;
    color: #ef4444;
  }

  /* Info grid */
  .info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .75rem;
  }
  .info-item {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: .75rem 1rem;
    cursor: pointer;
    transition: border-color var(--transition);
  }
  .info-item:hover { border-color: var(--accent); }
  .info-item .lbl {
    display: block;
    font-family: var(--font-mono);
    font-size: .6rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: .3rem;
    font-weight: 400;
  }
  .info-item .val {
    font-family: var(--font-mono);
    font-size: .85rem;
    font-weight: 700;
    word-break: break-all;
    color: var(--text);
  }

  /* Harmonies */
  .harmony-section { margin-bottom: 1.25rem; }
  .harmony-title {
    font-family: var(--font-mono);
    font-size: .65rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: .5rem;
  }
  .harmony-row {
    display: flex;
    gap: .5rem;
  }
  .h-swatch {
    flex: 1;
    height: 52px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    cursor: pointer;
    position: relative;
    transition: transform .15s, box-shadow .15s;
    overflow: hidden;
  }
  .h-swatch:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
  .h-swatch .h-hex {
    position: absolute;
    bottom: 3px; left: 50%;
    transform: translateX(-50%);
    font-family: var(--font-mono);
    font-size: 7px;
    background: rgba(0,0,0,0.45);
    color: #fff;
    padding: 1px 4px;
    border-radius: 3px;
    white-space: nowrap;
  }

  /* Palette */
  .swatch-grid {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    min-height: 60px;
  }
  .swatch-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    cursor: pointer;
    transition: transform .15s;
  }
  .swatch-item:hover { transform: translateY(-3px); }
  .swatch-dot {
    width: 44px; height: 44px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    position: relative;
    transition: border-color .15s;
  }
  .swatch-item:hover .swatch-dot { border-color: var(--accent); }
  .swatch-name {
    font-family: var(--font-mono);
    font-size: .6rem;
    color: var(--muted);
    max-width: 48px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .swatch-del {
    position: absolute;
    top: -5px; right: -5px;
    width: 15px; height: 15px;
    background: #ef4444;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    color: #fff;
    font-weight: bold;
    line-height: 1;
  }
  .swatch-item:hover .swatch-del { display: flex; }

  .palette-controls {
    display: flex;
    gap: .75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
  }
  .palette-controls input[type="text"] {
    flex: 1;
    min-width: 120px;
  }
</style>
</head>
<body>

<a href="#main-content" class="skip-link">Lewati ke konten</a>

<!-- Navbar -->
<nav>
  <a href="/" class="nav-logo">multi<span class="dot">.</span>tools</a>

  <div class="nav-separator"></div>

  <div class="nav-group" id="group-tools">
    <button class="nav-btn" onclick="toggleDropdown('group-tools')">
      🛠 Tools
      <svg class="chevron" width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </button>
    <div class="dropdown">
      <div class="dropdown-label">Utilitas</div>
      <a href="#"><span class="icon" style="background:#eff6ff">🎨</span> Color Picker <span class="tag hot">hot</span></a>
      <a href="#"><span class="icon" style="background:#f0fdf4">🔒</span> Hash Generator</a>
      <a href="#"><span class="icon" style="background:#fefce8">📐</span> Unit Converter</a>
      <div class="dropdown-sep"></div>
      <div class="dropdown-label">Teks</div>
      <a href="#"><span class="icon" style="background:#fdf4ff">✂️</span> Text Tools <span class="tag new">new</span></a>
    </div>
  </div>
</nav>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="/">Beranda</a>
  <span class="sep" aria-hidden="true">/</span>
  <span class="current">Color Picker</span>
</nav>

<!-- Hero Mini -->
<div class="hero-mini">
  <div class="glow glow-1"></div>
  <div class="glow glow-2"></div>
  <p class="hero-eyebrow" style="opacity:1;animation:none">🎨 Utilitas Warna</p>
  <h1 class="page-title" style="opacity:1;animation:none">Color <span>Picker</span></h1>
  <p class="page-lead" style="margin:0 auto">Pilih, eksplorasi, dan simpan warna favoritmu. Dapatkan format HEX, RGB, HSL, CMYK beserta harmoni warna otomatis.</p>
</div>

<!-- Main Content -->
<main id="main-content">
  <div class="tool-layout">

    <!-- Left Column -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Preview & Native Picker -->
      <div class="panel">
        <div class="panel-title">🖼 Preview Warna</div>
        <div class="color-preview" id="swatch" onclick="copyHex()" title="Klik untuk salin HEX"></div>
        <div class="hex-input-wrap">
          <span class="hex-prefix">#</span>
          <input type="text" class="hex-editable" id="hexInput" value="C8FF00" maxlength="6"
                 spellcheck="false" autocomplete="off"
                 oninput="onHexInput(this)" onblur="onHexBlur(this)">
        </div>
        <p class="text-xs text-muted text-mono">ketik HEX atau klik swatch untuk menyalin</p>
        <div class="form-group" style="margin-top:1rem">
          <label for="colorNative">Buka color picker browser</label>
          <input type="color" id="colorNative" value="#c8ff00">
        </div>
      </div>

      <!-- Sliders -->
      <div class="panel">
        <div class="panel-title">🎚 Kontrol Kanal</div>
        <div class="tab-row">
          <button class="tab-btn active" onclick="switchTab('rgb',this)">RGB</button>
          <button class="tab-btn" onclick="switchTab('hsl',this)">HSL</button>
        </div>

        <div id="tab-rgb" class="tab-panel active">
          <div class="channel-row">
            <span class="ch-label">R</span>
            <input type="range" id="range-r" min="0" max="255" value="200" oninput="onRgbChange()">
            <input type="number" class="ch-input" id="val-r" min="0" max="255" value="200" oninput="onRgbInputChange('r')">
          </div>
          <div class="channel-row">
            <span class="ch-label">G</span>
            <input type="range" id="range-g" min="0" max="255" value="255" oninput="onRgbChange()">
            <input type="number" class="ch-input" id="val-g" min="0" max="255" value="255" oninput="onRgbInputChange('g')">
          </div>
          <div class="channel-row">
            <span class="ch-label">B</span>
            <input type="range" id="range-b" min="0" max="255" value="0" oninput="onRgbChange()">
            <input type="number" class="ch-input" id="val-b" min="0" max="255" value="0" oninput="onRgbInputChange('b')">
          </div>
        </div>

        <div id="tab-hsl" class="tab-panel">
          <div class="channel-row">
            <span class="ch-label">H</span>
            <input type="range" id="range-h" min="0" max="360" value="72" oninput="onHslChange()">
            <input type="number" class="ch-input" id="val-h" min="0" max="360" value="72" oninput="onHslInputChange('h')">
          </div>
          <div class="channel-row">
            <span class="ch-label">S</span>
            <input type="range" id="range-s" min="0" max="100" value="100" oninput="onHslChange()">
            <input type="number" class="ch-input" id="val-s" min="0" max="100" value="100" oninput="onHslInputChange('s')">
          </div>
          <div class="channel-row">
            <span class="ch-label">L</span>
            <input type="range" id="range-l" min="0" max="100" value="50" oninput="onHslChange()">
            <input type="number" class="ch-input" id="val-l" min="0" max="100" value="50" oninput="onHslInputChange('l')">
          </div>
        </div>
      </div>

      <!-- Palette -->
      <div class="panel">
        <div class="panel-title">🗂 Palet Tersimpan</div>
        <div class="palette-controls">
          <input type="text" id="colorName" placeholder="Nama warna...">
          <button class="btn-primary btn-sm" onclick="saveColor()">+ Simpan</button>
          <button class="btn-ghost btn-sm" onclick="clearPalette()">Hapus Semua</button>
        </div>
        <div class="swatch-grid" id="swatchGrid">
          <p class="text-muted text-sm text-mono" id="emptyMsg">Belum ada warna tersimpan.</p>
        </div>
      </div>

    </div>

    <!-- Right Sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Info Panel -->
      <div class="panel">
        <div class="panel-title">📋 Informasi Warna</div>
        <p class="text-xs text-muted text-mono" style="margin-bottom:.75rem">Klik item untuk menyalin nilainya</p>
        <div class="info-grid">
          <div class="info-item" onclick="copyVal('info-hex')">
            <span class="lbl">HEX</span>
            <span class="val" id="info-hex">#C8FF00</span>
          </div>
          <div class="info-item" onclick="copyVal('info-rgb')">
            <span class="lbl">RGB</span>
            <span class="val" id="info-rgb">200, 255, 0</span>
          </div>
          <div class="info-item" onclick="copyVal('info-hsl')">
            <span class="lbl">HSL</span>
            <span class="val" id="info-hsl">72°, 100%, 50%</span>
          </div>
          <div class="info-item" onclick="copyVal('info-cmyk')">
            <span class="lbl">CMYK</span>
            <span class="val" id="info-cmyk">22%, 0%, 100%, 0%</span>
          </div>
          <div class="info-item" onclick="copyVal('info-lum')">
            <span class="lbl">Luminance</span>
            <span class="val" id="info-lum">—</span>
          </div>
          <div class="info-item" onclick="copyVal('info-contrast')">
            <span class="lbl">Kontras (vs putih)</span>
            <span class="val" id="info-contrast">—</span>
          </div>
        </div>
        <div style="margin-top:1rem" id="contrastBadge"></div>
      </div>

      <!-- Harmonies -->
      <div class="panel">
        <div class="panel-title">🌈 Harmoni Warna</div>
        <div class="harmony-section">
          <div class="harmony-title">Complementary</div>
          <div class="harmony-row" id="harm-comp"></div>
        </div>
        <div class="harmony-section">
          <div class="harmony-title">Triadic</div>
          <div class="harmony-row" id="harm-tri"></div>
        </div>
        <div class="harmony-section">
          <div class="harmony-title">Analogous</div>
          <div class="harmony-row" id="harm-ana"></div>
        </div>
      </div>

      <!-- Tip -->
      <div class="alert info">
        <span>💡</span>
        <span>Klik swatch harmoni untuk langsung memuatnya ke picker. Simpan warna favorit ke palet pribadimu.</span>
      </div>

    </div>
  </div>
</main>

<!-- Footer -->
<footer>
  <span class="footer-logo">multi<span class="dot">.</span>tools</span>
  <span class="text-muted text-xs text-mono">Color Picker — v1.0</span>
  <a href="/">← Kembali ke Beranda</a>
</footer>

<!-- Global JS -->
<script src="../assets/js/main.js"></script>

<!-- Color Picker Logic -->
<script>
let r = 200, g = 255, b = 0;
let palette = JSON.parse(localStorage.getItem('chroma_palette') || '[]');

renderAll();
renderPalette();

document.getElementById('colorNative').addEventListener('input', function () {
  const res = hexToRgb(this.value);
  r = res.r; g = res.g; b = res.b;
  syncFromRgb();
});

function onRgbChange() {
  r = +document.getElementById('range-r').value;
  g = +document.getElementById('range-g').value;
  b = +document.getElementById('range-b').value;
  syncFromRgb();
}

function onHslChange() {
  const h = +document.getElementById('range-h').value;
  const s = +document.getElementById('range-s').value;
  const l = +document.getElementById('range-l').value;
  const rgb = hslToRgb(h, s, l);
  r = rgb.r; g = rgb.g; b = rgb.b;
  syncFromRgb();
}

function syncFromRgb() {
  renderAll();
  document.getElementById('colorNative').value = rgbToHex(r, g, b);
}

function renderAll() {
  const hex = rgbToHex(r, g, b);
  const hsl = rgbToHsl(r, g, b);
  const cmyk = rgbToCmyk(r, g, b);
  const lum = luminance(r, g, b);

  document.getElementById('swatch').style.background = hex;

  // Update HEX input only when not focused (avoid cursor jump while typing)
  const hexInput = document.getElementById('hexInput');
  if (document.activeElement !== hexInput) {
    hexInput.value = hex.replace('#', '');
    hexInput.classList.remove('invalid');
  }

  // RGB sliders + inputs
  document.getElementById('range-r').value = r;
  document.getElementById('range-g').value = g;
  document.getElementById('range-b').value = b;
  if (document.activeElement !== document.getElementById('val-r')) document.getElementById('val-r').value = r;
  if (document.activeElement !== document.getElementById('val-g')) document.getElementById('val-g').value = g;
  if (document.activeElement !== document.getElementById('val-b')) document.getElementById('val-b').value = b;

  // HSL sliders + inputs
  const hR = Math.round(hsl.h), sR = Math.round(hsl.s), lR = Math.round(hsl.l);
  document.getElementById('range-h').value = hR;
  document.getElementById('range-s').value = sR;
  document.getElementById('range-l').value = lR;
  if (document.activeElement !== document.getElementById('val-h')) document.getElementById('val-h').value = hR;
  if (document.activeElement !== document.getElementById('val-s')) document.getElementById('val-s').value = sR;
  if (document.activeElement !== document.getElementById('val-l')) document.getElementById('val-l').value = lR;

  document.getElementById('info-hex').textContent = hex;
  document.getElementById('info-rgb').textContent = `${r}, ${g}, ${b}`;
  document.getElementById('info-hsl').textContent = `${Math.round(hsl.h)}°, ${Math.round(hsl.s)}%, ${Math.round(hsl.l)}%`;
  document.getElementById('info-cmyk').textContent = `${cmyk.c}%, ${cmyk.m}%, ${cmyk.y}%, ${cmyk.k}%`;
  document.getElementById('info-lum').textContent = lum.toFixed(3);

  const contrast = (1 + 0.05) / (lum + 0.05);
  document.getElementById('info-contrast').textContent = contrast.toFixed(1) + ':1';

  const badge = document.getElementById('contrastBadge');
  let cls = 'danger', label = '✕ Gagal AA';
  if (contrast >= 7)        { cls = 'success'; label = '✓ Lulus AAA'; }
  else if (contrast >= 4.5) { cls = 'success'; label = '✓ Lulus AA'; }
  else if (contrast >= 3)   { cls = 'warning'; label = '~ AA Large Only'; }
  badge.innerHTML = `<span class="badge ${cls}">WCAG: ${label}</span>`;

  renderHarmonies(hsl.h, hsl.s, hsl.l);
}

function renderHarmonies(h, s, l) {
  renderHarmonyRow('harm-comp', [{h, s, l}, {h: (h+180)%360, s, l}]);
  renderHarmonyRow('harm-tri',  [{h, s, l}, {h: (h+120)%360, s, l}, {h: (h+240)%360, s, l}]);
  renderHarmonyRow('harm-ana',  [{h: (h-30+360)%360, s, l}, {h, s, l}, {h: (h+30)%360, s, l}, {h: (h+60)%360, s, l}]);
}

function renderHarmonyRow(id, colors) {
  const el = document.getElementById(id);
  el.innerHTML = '';
  colors.forEach(c => {
    const rgb = hslToRgb(c.h, c.s, c.l);
    const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
    const div = document.createElement('div');
    div.className = 'h-swatch';
    div.style.background = hex;
    div.title = hex;
    div.innerHTML = `<span class="h-hex">${hex}</span>`;
    div.onclick = () => {
      r = rgb.r; g = rgb.g; b = rgb.b;
      document.getElementById('colorNative').value = hex;
      syncFromRgb();
    };
    el.appendChild(div);
  });
}

// Manual HEX input
function onHexInput(el) {
  const raw = el.value.replace(/[^0-9a-fA-F]/g, '').slice(0, 6);
  el.value = raw.toUpperCase();
  if (raw.length === 6) {
    el.classList.remove('invalid');
    const res = hexToRgb('#' + raw);
    r = res.r; g = res.g; b = res.b;
    document.getElementById('colorNative').value = '#' + raw;
    renderAll();
  } else {
    el.classList.add('invalid');
  }
}
function onHexBlur(el) {
  // Restore valid value if left incomplete
  if (el.value.length !== 6) {
    el.value = rgbToHex(r, g, b).replace('#', '');
    el.classList.remove('invalid');
  }
}

// Manual RGB number input
function onRgbInputChange(ch) {
  const el = document.getElementById('val-' + ch);
  let val = parseInt(el.value);
  if (isNaN(val)) { el.classList.add('invalid'); return; }
  val = Math.max(0, Math.min(255, val));
  el.classList.remove('invalid');
  if (ch === 'r') r = val;
  if (ch === 'g') g = val;
  if (ch === 'b') b = val;
  syncFromRgb();
}

// Manual HSL number input
function onHslInputChange(ch) {
  const el = document.getElementById('val-' + ch);
  let val = parseInt(el.value);
  const max = ch === 'h' ? 360 : 100;
  if (isNaN(val)) { el.classList.add('invalid'); return; }
  val = Math.max(0, Math.min(max, val));
  el.classList.remove('invalid');
  const hVal = ch === 'h' ? val : +document.getElementById('val-h').value;
  const sVal = ch === 's' ? val : +document.getElementById('val-s').value;
  const lVal = ch === 'l' ? val : +document.getElementById('val-l').value;
  const rgb = hslToRgb(hVal || 0, sVal || 0, lVal || 0);
  r = rgb.r; g = rgb.g; b = rgb.b;
  syncFromRgb();
}

// Uses copyToClipboard + showToast from main.js
function copyHex() {
  const hex = rgbToHex(r, g, b);
  copyToClipboard(hex, null);
  showToast('HEX tersalin: ' + hex, 'success');
}

function copyVal(id) {
  const text = document.getElementById(id).textContent;
  copyToClipboard(text, null);
  showToast('Tersalin: ' + text, 'success');
}

function saveColor() {
  const hex = rgbToHex(r, g, b);
  const name = document.getElementById('colorName').value.trim() || hex;
  if (palette.find(p => p.hex === hex)) { showToast('Warna sudah ada di palet!', 'warning'); return; }
  palette.push({ hex, name });
  localStorage.setItem('chroma_palette', JSON.stringify(palette));
  renderPalette();
  showToast('Disimpan: ' + name, 'success');
}

function removeColor(i) {
  palette.splice(i, 1);
  localStorage.setItem('chroma_palette', JSON.stringify(palette));
  renderPalette();
}

function clearPalette() {
  if (!palette.length) return;
  palette = [];
  localStorage.setItem('chroma_palette', JSON.stringify(palette));
  renderPalette();
  showToast('Palet dikosongkan.', 'info');
}

function renderPalette() {
  const grid = document.getElementById('swatchGrid');
  if (!palette.length) {
    grid.innerHTML = '<p class="text-muted text-sm text-mono" id="emptyMsg">Belum ada warna tersimpan.</p>';
    return;
  }
  grid.innerHTML = '';
  palette.forEach((p, i) => {
    const item = document.createElement('div');
    item.className = 'swatch-item';
    item.title = p.hex;
    item.onclick = () => {
      const rgb = hexToRgb(p.hex);
      r = rgb.r; g = rgb.g; b = rgb.b;
      document.getElementById('colorNative').value = p.hex;
      syncFromRgb();
    };
    item.innerHTML = `
      <div class="swatch-dot" style="background:${p.hex}">
        <div class="swatch-del" onclick="event.stopPropagation();removeColor(${i})">✕</div>
      </div>
      <span class="swatch-name">${p.name}</span>`;
    grid.appendChild(item);
  });
}

function switchTab(id, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-' + id).classList.add('active');
}

// ── Color Utils ──
function rgbToHex(r, g, b) {
  return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
}
function hexToRgb(hex) {
  const m = hex.replace('#', '').match(/.{2}/g);
  return { r: parseInt(m[0], 16), g: parseInt(m[1], 16), b: parseInt(m[2], 16) };
}
function rgbToHsl(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const mx = Math.max(r, g, b), mn = Math.min(r, g, b);
  let h, s, l = (mx + mn) / 2;
  if (mx === mn) { h = s = 0; }
  else {
    const d = mx - mn;
    s = l > 0.5 ? d / (2 - mx - mn) : d / (mx + mn);
    switch (mx) {
      case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
      case g: h = ((b - r) / d + 2) / 6; break;
      default: h = ((r - g) / d + 4) / 6;
    }
  }
  return { h: h * 360, s: s * 100, l: l * 100 };
}
function hslToRgb(h, s, l) {
  h /= 360; s /= 100; l /= 100;
  let r, g, b;
  if (s === 0) { r = g = b = l; }
  else {
    const hue2rgb = (p, q, t) => {
      if (t < 0) t += 1; if (t > 1) t -= 1;
      if (t < 1/6) return p + (q - p) * 6 * t;
      if (t < 1/2) return q;
      if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
      return p;
    };
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
    r = hue2rgb(p, q, h + 1/3); g = hue2rgb(p, q, h); b = hue2rgb(p, q, h - 1/3);
  }
  return { r: Math.round(r * 255), g: Math.round(g * 255), b: Math.round(b * 255) };
}
function rgbToCmyk(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const k = 1 - Math.max(r, g, b);
  if (k === 1) return { c: 0, m: 0, y: 0, k: 100 };
  return {
    c: Math.round((1 - r - k) / (1 - k) * 100),
    m: Math.round((1 - g - k) / (1 - k) * 100),
    y: Math.round((1 - b - k) / (1 - k) * 100),
    k: Math.round(k * 100)
  };
}
function luminance(r, g, b) {
  const a = [r, g, b].map(v => {
    v /= 255;
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
}
</script>
</body>
</html>