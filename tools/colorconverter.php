<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    // Placeholder for server-side processing if needed
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Color Format Converter — Multi Tools</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>

  /* ── Converter-specific styles ── */

  /* Big input area at the top */
  .converter-input-wrap {
    position: relative;
  }
  .converter-input {
    font-family: var(--font-mono) !important;
    font-size: 1.15rem !important;
    font-weight: 700 !important;
    letter-spacing: .04em;
    padding: .85rem 1rem !important;
    border-radius: var(--radius-sm) !important;
    transition: border-color var(--transition), box-shadow var(--transition);
  }
  .converter-input.valid   { border-color: var(--accent5) !important; }
  .converter-input.invalid { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,.1) !important; }

  .format-detect {
    position: absolute;
    right: .75rem; top: 50%;
    transform: translateY(-50%);
    font-family: var(--font-mono);
    font-size: .65rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: .2rem .55rem;
    border-radius: 4px;
    pointer-events: none;
    transition: all .2s;
  }

  /* Swatch strip */
  .swatch-strip {
    width: 100%;
    height: 72px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
    transition: background .2s;
    cursor: pointer;
  }
  .swatch-strip::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, transparent 50%);
    pointer-events: none;
  }
  .swatch-strip .copy-overlay {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-mono);
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: 0;
    transition: opacity .2s;
    background: rgba(0,0,0,.15);
    color: #fff;
  }
  .swatch-strip:hover .copy-overlay { opacity: 1; }

  /* Format output cards grid */
  .formats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .85rem;
  }
  @media (max-width: 560px) {
    .formats-grid { grid-template-columns: 1fr; }
  }

  .format-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 1rem 1.1rem;
    position: relative;
    transition: border-color var(--transition), transform var(--transition);
    cursor: pointer;
    overflow: hidden;
  }
  .format-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--card-accent, var(--accent));
    border-radius: 3px 3px 0 0;
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .25s ease;
  }
  .format-card:hover {
    border-color: var(--card-accent, var(--accent));
    transform: translateY(-2px);
  }
  .format-card:hover::before { transform: scaleX(1); }

  .format-card .fc-label {
    font-family: var(--font-mono);
    font-size: .6rem;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: .45rem;
    display: flex;
    align-items: center;
    gap: .4rem;
  }
  .format-card .fc-label .fc-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--card-accent, var(--accent));
    flex-shrink: 0;
  }
  .format-card .fc-value {
    font-family: var(--font-mono);
    font-size: .9rem;
    font-weight: 700;
    color: var(--text);
    word-break: break-all;
    line-height: 1.4;
    padding-right: 2rem;
  }
  .format-card .fc-copy {
    position: absolute;
    top: .65rem; right: .65rem;
    width: 26px; height: 26px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    color: var(--muted);
    font-size: .75rem;
    transition: all var(--transition);
    cursor: pointer;
  }
  .format-card:hover .fc-copy {
    background: var(--card-accent, var(--accent));
    border-color: var(--card-accent, var(--accent));
    color: #fff;
  }

  /* Quick presets */
  .presets-row {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }
  .preset-btn {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem .75rem;
    border: 1px solid var(--border);
    border-radius: 99px;
    background: var(--surface);
    cursor: pointer;
    font-family: var(--font-mono);
    font-size: .7rem;
    font-weight: 700;
    color: var(--muted);
    transition: all var(--transition);
    white-space: nowrap;
  }
  .preset-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
    transform: translateY(-1px);
  }
  .preset-swatch {
    width: 12px; height: 12px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,.1);
    flex-shrink: 0;
  }

  /* History list */
  .history-list {
    display: flex;
    flex-direction: column;
    gap: .5rem;
  }
  .history-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .6rem .85rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
  }
  .history-item:hover {
    border-color: var(--accent);
    background: rgba(37,99,235,.04);
  }
  .history-dot {
    width: 28px; height: 28px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    flex-shrink: 0;
  }
  .history-hex {
    font-family: var(--font-mono);
    font-size: .8rem;
    font-weight: 700;
    color: var(--text);
    flex: 1;
  }
  .history-time {
    font-family: var(--font-mono);
    font-size: .65rem;
    color: var(--muted);
  }
  .history-del {
    font-size: .7rem;
    color: var(--muted);
    padding: .15rem .4rem;
    border-radius: 4px;
    transition: all var(--transition);
    background: none;
    border: none;
    cursor: pointer;
  }
  .history-del:hover { color: #ef4444; background: #fef2f2; }

  /* CSS snippet */
  .snippet-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 1rem;
    font-family: var(--font-mono);
    font-size: .78rem;
    line-height: 1.7;
    color: var(--text);
    position: relative;
    overflow-x: auto;
    white-space: pre;
  }
  .snippet-box .tok-prop  { color: var(--accent2); }
  .snippet-box .tok-val   { color: var(--accent5); }
  .snippet-box .tok-punct { color: var(--muted); }

  /* Empty state */
  .empty-state {
    padding: 1.5rem 0;
    text-align: center;
    font-family: var(--font-mono);
    font-size: .8rem;
    color: var(--muted);
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
      <div class="dropdown-label">Warna</div>
      <a href="color-picker.php"><span class="icon" style="background:#eff6ff">🎨</span> Color Picker</a>
      <a href="color-converter.php"><span class="icon" style="background:#fdf4ff">🔄</span> Color Converter <span class="tag hot">hot</span></a>
      <div class="dropdown-sep"></div>
      <div class="dropdown-label">Utilitas</div>
      <a href="#"><span class="icon" style="background:#f0fdf4">🔒</span> Hash Generator</a>
      <a href="#"><span class="icon" style="background:#fefce8">📐</span> Unit Converter</a>
    </div>
  </div>
</nav>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="/">Beranda</a>
  <span class="sep" aria-hidden="true">/</span>
  <a href="#">Warna</a>
  <span class="sep" aria-hidden="true">/</span>
  <span class="current">Color Format Converter</span>
</nav>

<!-- Hero Mini -->
<div class="hero-mini">
  <div class="glow glow-1"></div>
  <div class="glow glow-2"></div>
  <p class="hero-eyebrow" style="opacity:1;animation:none">🔄 Konversi Warna</p>
  <h1 class="page-title" style="opacity:1;animation:none">Color Format <span>Converter</span></h1>
  <p class="page-lead" style="margin:0 auto">Konversi warna antar format HEX, RGB, RGBA, HSL, HSLA, CMYK, HWB, dan CSS Variable secara instan. Ketik format apapun, semua langsung terkonversi.</p>
</div>

<!-- Main -->
<main id="main-content">
  <div class="tool-layout">

    <!-- LEFT: Input + Outputs -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Input -->
      <div class="panel">
        <div class="panel-title">⌨️ Masukkan Nilai Warna</div>

        <div class="form-group">
          <label for="colorInput">Ketik format apapun — HEX, RGB, HSL, CMYK, HWB...</label>
          <div class="converter-input-wrap">
            <input type="text" class="converter-input" id="colorInput"
                   placeholder="Contoh: #ff6b35 atau rgb(255,107,53) atau hsl(18,100%,60%)"
                   oninput="onConverterInput(this)"
                   onkeydown="onInputKeydown(event)"
                   spellcheck="false" autocomplete="off">
            <span class="format-detect badge" id="formatDetect" style="display:none"></span>
          </div>
          <p class="text-xs text-muted text-mono" style="margin-top:.4rem" id="inputHint">
            Format yang didukung: HEX (#rrggbb, #rgb), RGB, RGBA, HSL, HSLA, CMYK, HWB, nama warna CSS
          </p>
        </div>

        <!-- Color swatch -->
        <div class="swatch-strip" id="mainSwatch" onclick="copySwatchHex()" style="background:#f0f4f8">
          <div class="copy-overlay">📋 Salin HEX</div>
        </div>
        <p class="text-xs text-muted text-mono" style="margin-top:.5rem;text-align:center" id="swatchLabel">—</p>
      </div>

      <!-- Output formats -->
      <div class="panel">
        <div class="panel-title">📤 Semua Format</div>
        <p class="text-xs text-muted text-mono" style="margin-bottom:1rem">Klik kartu untuk menyalin nilai</p>
        <div class="formats-grid" id="formatsGrid">
          <div class="empty-state" style="grid-column:1/-1">Masukkan warna di atas untuk melihat semua format.</div>
        </div>
      </div>

      <!-- CSS Snippet -->
      <div class="panel">
        <div class="panel-title">💾 CSS Snippet</div>
        <div class="copy-wrap">
          <div class="snippet-box" id="cssSnippet">/* Masukkan warna untuk melihat snippet CSS */</div>
          <button class="copy-btn" onclick="copySnippet()">Salin</button>
        </div>
      </div>

    </div>

    <!-- RIGHT: Sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Quick Presets -->
      <div class="panel">
        <div class="panel-title">⚡ Warna Cepat</div>
        <div class="presets-row" id="presetsRow"></div>
      </div>

      <!-- Format Guide -->
      <div class="panel">
        <div class="panel-title">📖 Panduan Format</div>
        <div style="display:flex;flex-direction:column;gap:.6rem">
          <?php
          $formats = [
            ['HEX',  '#rrggbb atau #rgb',        '#ff6b35'],
            ['RGB',  'rgb(r, g, b)',              'rgb(255, 107, 53)'],
            ['RGBA', 'rgba(r, g, b, a)',          'rgba(255, 107, 53, 0.8)'],
            ['HSL',  'hsl(h, s%, l%)',            'hsl(18, 100%, 60%)'],
            ['HSLA', 'hsla(h, s%, l%, a)',        'hsla(18, 100%, 60%, 0.8)'],
            ['HWB',  'hwb(h w% b%)',              'hwb(18 0% 0%)'],
            ['CMYK', 'cmyk(c%, m%, y%, k%)',      'cmyk(0%, 58%, 79%, 0%)'],
            ['CSS',  'nama warna standar CSS',    'tomato / coral / crimson'],
          ];
          foreach ($formats as $f): ?>
          <div style="padding:.6rem .8rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm)">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem">
              <span class="badge accent" style="font-size:.55rem"><?= $f[0] ?></span>
              <span class="text-mono text-xs text-muted"><?= $f[1] ?></span>
            </div>
            <span class="text-mono text-xs" style="color:var(--accent5);cursor:pointer"
                  onclick="document.getElementById('colorInput').value='<?= $f[2] ?>';onConverterInput(document.getElementById('colorInput'))">
              <?= $f[2] ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- History -->
      <div class="panel">
        <div class="panel-title">🕓 Riwayat Konversi
          <button class="btn-ghost btn-sm" style="margin-left:auto;font-size:.7rem" onclick="clearHistory()">Hapus</button>
        </div>
        <div class="history-list" id="historyList">
          <div class="empty-state">Belum ada riwayat.</div>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- Footer -->
<footer>
  <span class="footer-logo">multi<span class="dot">.</span>tools</span>
  <span class="text-muted text-xs text-mono">Color Format Converter — v1.0</span>
  <a href="color-picker.php">🎨 Color Picker →</a>
</footer>

<script src="../assets/js/main.js"></script>
<script>
/* ============================================================
   COLOR FORMAT CONVERTER — Logic
   ============================================================ */

// ── State
let currentHex = null;
let history = JSON.parse(localStorage.getItem('cvt_history') || '[]');

// ── Format definitions for output cards
const FORMAT_DEFS = [
  { key: 'hex',    label: 'HEX',       accent: '#2563eb' },
  { key: 'hex3',   label: 'HEX Short', accent: '#0ea5e9' },
  { key: 'rgb',    label: 'RGB',       accent: '#7c3aed' },
  { key: 'rgba',   label: 'RGBA',      accent: '#9333ea' },
  { key: 'hsl',    label: 'HSL',       accent: '#10b981' },
  { key: 'hsla',   label: 'HSLA',      accent: '#059669' },
  { key: 'hwb',    label: 'HWB',       accent: '#f59e0b' },
  { key: 'cmyk',   label: 'CMYK',      accent: '#ef4444' },
  { key: 'cssvar', label: 'CSS Var',   accent: '#64748b' },
];

// ── Quick presets
const PRESETS = [
  { name: 'Tomato',   hex: '#FF6347' },
  { name: 'SkyBlue',  hex: '#87CEEB' },
  { name: 'Emerald',  hex: '#10B981' },
  { name: 'Violet',   hex: '#7C3AED' },
  { name: 'Amber',    hex: '#F59E0B' },
  { name: 'Rose',     hex: '#F43F5E' },
  { name: 'Slate',    hex: '#64748B' },
  { name: 'Lime',     hex: '#84CC16' },
];

// ── Init
renderPresets();
renderHistory();

// ── Input handler
function onConverterInput(el) {
  const raw = el.value.trim();
  if (!raw) {
    resetOutput();
    el.classList.remove('valid','invalid');
    document.getElementById('formatDetect').style.display = 'none';
    return;
  }

  const result = parseColor(raw);
  if (result) {
    el.classList.remove('invalid');
    el.classList.add('valid');
    currentHex = result.hex;
    showDetectedFormat(result.format);
    renderOutput(result);
    addToHistory(result.hex);
  } else {
    el.classList.remove('valid');
    el.classList.add('invalid');
    showDetectedFormat(null);
    resetOutput();
  }
}

function onInputKeydown(e) {
  if (e.key === 'Enter') onConverterInput(e.target);
}

function showDetectedFormat(fmt) {
  const el = document.getElementById('formatDetect');
  if (!fmt) { el.style.display = 'none'; return; }
  el.style.display = 'inline-flex';
  el.textContent = fmt;
  el.className = 'format-detect badge accent';
}

function resetOutput() {
  currentHex = null;
  document.getElementById('mainSwatch').style.background = 'var(--bg)';
  document.getElementById('swatchLabel').textContent = '—';
  document.getElementById('formatsGrid').innerHTML =
    '<div class="empty-state" style="grid-column:1/-1">Masukkan warna di atas untuk melihat semua format.</div>';
  document.getElementById('cssSnippet').innerHTML = '/* Masukkan warna untuk melihat snippet CSS */';
}

function renderOutput(result) {
  const { r, g, b } = result;
  const formats = buildFormats(r, g, b);

  // Swatch
  document.getElementById('mainSwatch').style.background = result.hex;
  document.getElementById('swatchLabel').textContent = result.hex.toUpperCase();

  // Format cards
  const grid = document.getElementById('formatsGrid');
  grid.innerHTML = '';
  FORMAT_DEFS.forEach(def => {
    const val = formats[def.key];
    if (!val) return;
    const card = document.createElement('div');
    card.className = 'format-card';
    card.style.setProperty('--card-accent', def.accent);
    card.title = 'Klik untuk menyalin';
    card.innerHTML = `
      <div class="fc-label">
        <span class="fc-dot"></span>${def.label}
      </div>
      <div class="fc-value" id="fc-${def.key}">${val}</div>
      <button class="fc-copy" onclick="event.stopPropagation();copyFmt('${def.key}')">📋</button>`;
    card.onclick = () => copyFmt(def.key);
    grid.appendChild(card);
  });

  // CSS Snippet
  renderSnippet(r, g, b, result.hex);
}

function buildFormats(r, g, b) {
  const hsl   = rgbToHsl(r, g, b);
  const cmyk  = rgbToCmyk(r, g, b);
  const hwb   = rgbToHwb(r, g, b);
  const hex   = rgbToHex(r, g, b);
  const hex3  = toHex3(hex);

  return {
    hex:    hex,
    hex3:   hex3 !== hex ? hex3 : null,
    rgb:    `rgb(${r}, ${g}, ${b})`,
    rgba:   `rgba(${r}, ${g}, ${b}, 1)`,
    hsl:    `hsl(${Math.round(hsl.h)}, ${Math.round(hsl.s)}%, ${Math.round(hsl.l)}%)`,
    hsla:   `hsla(${Math.round(hsl.h)}, ${Math.round(hsl.s)}%, ${Math.round(hsl.l)}%, 1)`,
    hwb:    `hwb(${Math.round(hwb.h)} ${Math.round(hwb.w)}% ${Math.round(hwb.b)}%)`,
    cmyk:   `cmyk(${cmyk.c}%, ${cmyk.m}%, ${cmyk.y}%, ${cmyk.k}%)`,
    cssvar: `--color: ${hex};`,
  };
}

function renderSnippet(r, g, b, hex) {
  const hsl  = rgbToHsl(r, g, b);
  const name = '--primary-color';
  const el = document.getElementById('cssSnippet');
  el.innerHTML =
    `<span class="tok-punct">:root {</span>\n` +
    `  <span class="tok-prop">${name}</span><span class="tok-punct">:</span> <span class="tok-val">${hex}</span><span class="tok-punct">;</span>\n` +
    `  <span class="tok-prop">${name}-rgb</span><span class="tok-punct">:</span> <span class="tok-val">rgb(${r}, ${g}, ${b})</span><span class="tok-punct">;</span>\n` +
    `  <span class="tok-prop">${name}-hsl</span><span class="tok-punct">:</span> <span class="tok-val">hsl(${Math.round(hsl.h)}, ${Math.round(hsl.s)}%, ${Math.round(hsl.l)}%)</span><span class="tok-punct">;</span>\n` +
    `  <span class="tok-prop">${name}-r</span><span class="tok-punct">:</span> <span class="tok-val">${r}</span><span class="tok-punct">;</span>\n` +
    `  <span class="tok-prop">${name}-g</span><span class="tok-punct">:</span> <span class="tok-val">${g}</span><span class="tok-punct">;</span>\n` +
    `  <span class="tok-prop">${name}-b</span><span class="tok-punct">:</span> <span class="tok-val">${b}</span><span class="tok-punct">;</span>\n` +
    `<span class="tok-punct">}</span>`;
}

function copyFmt(key) {
  const el = document.getElementById('fc-' + key);
  if (!el) return;
  copyToClipboard(el.textContent, null);
  showToast('Tersalin: ' + el.textContent, 'success');
}

function copySwatchHex() {
  if (!currentHex) return;
  copyToClipboard(currentHex, null);
  showToast('HEX tersalin: ' + currentHex, 'success');
}

function copySnippet() {
  const el = document.getElementById('cssSnippet');
  copyToClipboard(el.textContent, null);
  showToast('CSS Snippet tersalin!', 'success');
}

// ── Presets
function renderPresets() {
  const row = document.getElementById('presetsRow');
  row.innerHTML = '';
  PRESETS.forEach(p => {
    const btn = document.createElement('button');
    btn.className = 'preset-btn';
    btn.innerHTML = `<span class="preset-swatch" style="background:${p.hex}"></span>${p.name}`;
    btn.onclick = () => {
      const input = document.getElementById('colorInput');
      input.value = p.hex;
      onConverterInput(input);
      input.focus();
    };
    row.appendChild(btn);
  });
}

// ── History
function addToHistory(hex) {
  hex = hex.toUpperCase();
  // Don't duplicate last entry
  if (history.length && history[0].hex === hex) return;
  history.unshift({ hex, time: new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}) });
  if (history.length > 12) history.pop();
  localStorage.setItem('cvt_history', JSON.stringify(history));
  renderHistory();
}

function removeHistory(i) {
  history.splice(i, 1);
  localStorage.setItem('cvt_history', JSON.stringify(history));
  renderHistory();
}

function clearHistory() {
  history = [];
  localStorage.setItem('cvt_history', JSON.stringify(history));
  renderHistory();
}

function renderHistory() {
  const list = document.getElementById('historyList');
  if (!history.length) {
    list.innerHTML = '<div class="empty-state">Belum ada riwayat.</div>';
    return;
  }
  list.innerHTML = '';
  history.forEach((h, i) => {
    const item = document.createElement('div');
    item.className = 'history-item';
    item.onclick = () => {
      const input = document.getElementById('colorInput');
      input.value = h.hex;
      onConverterInput(input);
    };
    item.innerHTML = `
      <div class="history-dot" style="background:${h.hex}"></div>
      <span class="history-hex">${h.hex}</span>
      <span class="history-time">${h.time}</span>
      <button class="history-del" onclick="event.stopPropagation();removeHistory(${i})" title="Hapus">✕</button>`;
    list.appendChild(item);
  });
}

/* ============================================================
   COLOR PARSING — supports many formats
   ============================================================ */
function parseColor(input) {
  input = input.trim();

  // HEX #rrggbb or #rgb or #rrggbbaa
  let m;
  if ((m = input.match(/^#?([0-9a-fA-F]{6})([0-9a-fA-F]{2})?$/))) {
    const r = parseInt(m[1].slice(0,2),16), g = parseInt(m[1].slice(2,4),16), b = parseInt(m[1].slice(4,6),16);
    return { r, g, b, hex: rgbToHex(r,g,b), format: 'HEX' };
  }
  if ((m = input.match(/^#?([0-9a-fA-F]{3})$/))) {
    const r = parseInt(m[1][0]+m[1][0],16), g = parseInt(m[1][1]+m[1][1],16), b = parseInt(m[1][2]+m[1][2],16);
    return { r, g, b, hex: rgbToHex(r,g,b), format: 'HEX3' };
  }

  // RGB / RGBA
  if ((m = input.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*[\d.]+)?\s*\)$/i))) {
    const r = clamp(+m[1],0,255), g = clamp(+m[2],0,255), b = clamp(+m[3],0,255);
    return { r, g, b, hex: rgbToHex(r,g,b), format: input.toLowerCase().startsWith('rgba') ? 'RGBA' : 'RGB' };
  }
  // RGB space-separated modern syntax: rgb(255 107 53)
  if ((m = input.match(/^rgba?\(\s*(\d+)\s+(\d+)\s+(\d+)\s*(?:\/\s*[\d.%]+)?\s*\)$/i))) {
    const r = clamp(+m[1],0,255), g = clamp(+m[2],0,255), b = clamp(+m[3],0,255);
    return { r, g, b, hex: rgbToHex(r,g,b), format: 'RGB' };
  }

  // HSL / HSLA
  if ((m = input.match(/^hsla?\(\s*([\d.]+)\s*,\s*([\d.]+)%\s*,\s*([\d.]+)%\s*(?:,\s*[\d.]+)?\s*\)$/i))) {
    const rgb = hslToRgb(+m[1], +m[2], +m[3]);
    return { ...rgb, hex: rgbToHex(rgb.r,rgb.g,rgb.b), format: input.toLowerCase().startsWith('hsla') ? 'HSLA' : 'HSL' };
  }
  // HSL modern: hsl(18 100% 60%)
  if ((m = input.match(/^hsla?\(\s*([\d.]+)\s+([\d.]+)%\s+([\d.]+)%\s*(?:\/\s*[\d.%]+)?\s*\)$/i))) {
    const rgb = hslToRgb(+m[1], +m[2], +m[3]);
    return { ...rgb, hex: rgbToHex(rgb.r,rgb.g,rgb.b), format: 'HSL' };
  }

  // HWB: hwb(h w% b%)
  if ((m = input.match(/^hwb\(\s*([\d.]+)\s+([\d.]+)%\s+([\d.]+)%\s*(?:\/\s*[\d.%]+)?\s*\)$/i))) {
    const rgb = hwbToRgb(+m[1], +m[2], +m[3]);
    return { ...rgb, hex: rgbToHex(rgb.r,rgb.g,rgb.b), format: 'HWB' };
  }

  // CMYK: cmyk(c%, m%, y%, k%)
  if ((m = input.match(/^cmyk\(\s*([\d.]+)%?\s*,\s*([\d.]+)%?\s*,\s*([\d.]+)%?\s*,\s*([\d.]+)%?\s*\)$/i))) {
    const rgb = cmykToRgb(+m[1], +m[2], +m[3], +m[4]);
    return { ...rgb, hex: rgbToHex(rgb.r,rgb.g,rgb.b), format: 'CMYK' };
  }

  // CSS named colors
  const named = cssNameToHex(input.toLowerCase());
  if (named) {
    const res = hexToRgb(named);
    return { ...res, hex: named, format: 'CSS Name' };
  }

  return null;
}

/* ============================================================
   COLOR MATH
   ============================================================ */
function clamp(v, mn, mx) { return Math.max(mn, Math.min(mx, v)); }

function rgbToHex(r, g, b) {
  return '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('').toUpperCase();
}
function hexToRgb(hex) {
  const m = hex.replace('#','').match(/.{2}/g);
  return { r: parseInt(m[0],16), g: parseInt(m[1],16), b: parseInt(m[2],16) };
}
function toHex3(hex) {
  const h = hex.replace('#','');
  if (h[0]===h[1] && h[2]===h[3] && h[4]===h[5])
    return '#' + h[0] + h[2] + h[4];
  return hex;
}
function rgbToHsl(r, g, b) {
  r/=255; g/=255; b/=255;
  const mx=Math.max(r,g,b), mn=Math.min(r,g,b);
  let h, s, l=(mx+mn)/2;
  if (mx===mn) { h=s=0; }
  else {
    const d=mx-mn;
    s = l>.5 ? d/(2-mx-mn) : d/(mx+mn);
    switch(mx){
      case r: h=((g-b)/d+(g<b?6:0))/6; break;
      case g: h=((b-r)/d+2)/6; break;
      default: h=((r-g)/d+4)/6;
    }
  }
  return {h:h*360, s:s*100, l:l*100};
}
function hslToRgb(h, s, l) {
  h/=360; s/=100; l/=100;
  let r,g,b;
  if (s===0) { r=g=b=l; }
  else {
    const hue2rgb=(p,q,t)=>{if(t<0)t+=1;if(t>1)t-=1;if(t<1/6)return p+(q-p)*6*t;if(t<1/2)return q;if(t<2/3)return p+(q-p)*(2/3-t)*6;return p;};
    const q=l<.5?l*(1+s):l+s-l*s, p=2*l-q;
    r=hue2rgb(p,q,h+1/3); g=hue2rgb(p,q,h); b=hue2rgb(p,q,h-1/3);
  }
  return {r:Math.round(r*255), g:Math.round(g*255), b:Math.round(b*255)};
}
function rgbToCmyk(r, g, b) {
  r/=255; g/=255; b/=255;
  const k=1-Math.max(r,g,b);
  if (k===1) return {c:0,m:0,y:0,k:100};
  return {
    c: Math.round((1-r-k)/(1-k)*100),
    m: Math.round((1-g-k)/(1-k)*100),
    y: Math.round((1-b-k)/(1-k)*100),
    k: Math.round(k*100)
  };
}
function cmykToRgb(c, m, y, k) {
  c/=100; m/=100; y/=100; k/=100;
  return {
    r: Math.round(255*(1-c)*(1-k)),
    g: Math.round(255*(1-m)*(1-k)),
    b: Math.round(255*(1-y)*(1-k))
  };
}
function rgbToHwb(r, g, b) {
  const hsl = rgbToHsl(r,g,b);
  const w = Math.min(r,g,b)/255*100;
  const bk = (1-Math.max(r,g,b)/255)*100;
  return { h: hsl.h, w, b: bk };
}
function hwbToRgb(h, w, b) {
  w/=100; b/=100;
  if (w+b >= 1) {
    const g = Math.round(w/(w+b)*255);
    return {r:g, g, b:g};
  }
  const rgb = hslToRgb(h, 100, 50);
  const f = v => Math.round((v/255)*(1-w-b)*255 + w*255);
  return {r:f(rgb.r), g:f(rgb.g), b:f(rgb.b)};
}

/* CSS Named Colors (common subset) */
function cssNameToHex(name) {
  const colors = {
    aliceblue:'#F0F8FF',antiquewhite:'#FAEBD7',aqua:'#00FFFF',aquamarine:'#7FFFD4',
    azure:'#F0FFFF',beige:'#F5F5DC',bisque:'#FFE4C4',black:'#000000',blanchedalmond:'#FFEBCD',
    blue:'#0000FF',blueviolet:'#8A2BE2',brown:'#A52A2A',burlywood:'#DEB887',cadetblue:'#5F9EA0',
    chartreuse:'#7FFF00',chocolate:'#D2691E',coral:'#FF7F50',cornflowerblue:'#6495ED',
    cornsilk:'#FFF8DC',crimson:'#DC143C',cyan:'#00FFFF',darkblue:'#00008B',darkcyan:'#008B8B',
    darkgoldenrod:'#B8860B',darkgray:'#A9A9A9',darkgreen:'#006400',darkkhaki:'#BDB76B',
    darkmagenta:'#8B008B',darkolivegreen:'#556B2F',darkorange:'#FF8C00',darkorchid:'#9932CC',
    darkred:'#8B0000',darksalmon:'#E9967A',darkseagreen:'#8FBC8F',darkslateblue:'#483D8B',
    darkslategray:'#2F4F4F',darkturquoise:'#00CED1',darkviolet:'#9400D3',deeppink:'#FF1493',
    deepskyblue:'#00BFFF',dimgray:'#696969',dodgerblue:'#1E90FF',firebrick:'#B22222',
    floralwhite:'#FFFAF0',forestgreen:'#228B22',fuchsia:'#FF00FF',gainsboro:'#DCDCDC',
    ghostwhite:'#F8F8FF',gold:'#FFD700',goldenrod:'#DAA520',gray:'#808080',green:'#008000',
    greenyellow:'#ADFF2F',honeydew:'#F0FFF0',hotpink:'#FF69B4',indianred:'#CD5C5C',
    indigo:'#4B0082',ivory:'#FFFFF0',khaki:'#F0E68C',lavender:'#E6E6FA',lavenderblush:'#FFF0F5',
    lawngreen:'#7CFC00',lemonchiffon:'#FFFACD',lightblue:'#ADD8E6',lightcoral:'#F08080',
    lightcyan:'#E0FFFF',lightgoldenrodyellow:'#FAFAD2',lightgray:'#D3D3D3',lightgreen:'#90EE90',
    lightpink:'#FFB6C1',lightsalmon:'#FFA07A',lightseagreen:'#20B2AA',lightskyblue:'#87CEFA',
    lightslategray:'#778899',lightsteelblue:'#B0C4DE',lightyellow:'#FFFFE0',lime:'#00FF00',
    limegreen:'#32CD32',linen:'#FAF0E6',magenta:'#FF00FF',maroon:'#800000',
    mediumaquamarine:'#66CDAA',mediumblue:'#0000CD',mediumorchid:'#BA55D3',mediumpurple:'#9370DB',
    mediumseagreen:'#3CB371',mediumslateblue:'#7B68EE',mediumspringgreen:'#00FA9A',
    mediumturquoise:'#48D1CC',mediumvioletred:'#C71585',midnightblue:'#191970',mintcream:'#F5FFFA',
    mistyrose:'#FFE4E1',moccasin:'#FFE4B5',navajowhite:'#FFDEAD',navy:'#000080',oldlace:'#FDF5E6',
    olive:'#808000',olivedrab:'#6B8E23',orange:'#FFA500',orangered:'#FF4500',orchid:'#DA70D6',
    palegoldenrod:'#EEE8AA',palegreen:'#98FB98',paleturquoise:'#AFEEEE',palevioletred:'#DB7093',
    papayawhip:'#FFEFD5',peachpuff:'#FFDAB9',peru:'#CD853F',pink:'#FFC0CB',plum:'#DDA0DD',
    powderblue:'#B0E0E6',purple:'#800080',red:'#FF0000',rosybrown:'#BC8F8F',royalblue:'#4169E1',
    saddlebrown:'#8B4513',salmon:'#FA8072',sandybrown:'#F4A460',seagreen:'#2E8B57',seashell:'#FFF5EE',
    sienna:'#A0522D',silver:'#C0C0C0',skyblue:'#87CEEB',slateblue:'#6A5ACD',slategray:'#708090',
    snow:'#FFFAFA',springgreen:'#00FF7F',steelblue:'#4682B4',tan:'#D2B48C',teal:'#008080',
    thistle:'#D8BFD8',tomato:'#FF6347',turquoise:'#40E0D0',violet:'#EE82EE',wheat:'#F5DEB3',
    white:'#FFFFFF',whitesmoke:'#F5F5F5',yellow:'#FFFF00',yellowgreen:'#9ACD32',
  };
  return colors[name] || null;
}
</script>
</body>
</html>