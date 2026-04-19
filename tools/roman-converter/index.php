<?php
require '../../includes/config.php';
/**
 * Multi Tools — Roman Numeral Converter
 * Konversi angka Arab ↔ Romawi.
 * Mendukung konversi tunggal, massal, validasi, dan tabel referensi.
 * ============================================================ */

// ── Logika konversi ──────────────────────────────────────────

const ROMAN_MAP = [
  1000 => 'M',  900 => 'CM', 800 => 'DCCC', 700 => 'DCC',
  600  => 'DC', 500 => 'D',  400 => 'CD',   300 => 'CCC',
  200  => 'CC', 100 => 'C',   90 => 'XC',    80 => 'LXXX',
   70  => 'LXX', 60 => 'LX',  50 => 'L',     40 => 'XL',
   30  => 'XXX', 20 => 'XX',  10 => 'X',      9 => 'IX',
    8  => 'VIII', 7 => 'VII',  6 => 'VI',      5 => 'V',
    4  => 'IV',   3 => 'III',  2 => 'II',      1 => 'I',
];

/**
 * Konversi integer (1–3999) ke Romawi.
 */
function toRoman(int $n): string|false {
  if ($n < 1 || $n > 3999) return false;
  $result = '';
  foreach (ROMAN_MAP as $value => $numeral) {
    while ($n >= $value) {
      $result .= $numeral;
      $n -= $value;
    }
  }
  return $result;
}

/**
 * Konversi string Romawi ke integer.
 * Mendukung huruf kecil dan besar.
 */
function fromRoman(string $roman): int|false {
  $roman = strtoupper(trim($roman));
  if (!preg_match('/^[IVXLCDM]+$/', $roman)) return false;

  $romanVals = ['I'=>1,'V'=>5,'X'=>10,'L'=>50,'C'=>100,'D'=>500,'M'=>1000];
  $result = 0;
  $prev   = 0;
  $chars  = array_reverse(str_split($roman));

  foreach ($chars as $ch) {
    if (!isset($romanVals[$ch])) return false;
    $val = $romanVals[$ch];
    if ($val < $prev) {
      $result -= $val;
    } else {
      $result += $val;
    }
    $prev = $val;
  }

  // Validasi dengan konversi balik
  if ($result < 1 || $result > 3999) return false;
  if (toRoman($result) !== $roman) return false; // cegah format invalid seperti IIII

  return $result;
}

/**
 * Validasi apakah string adalah Romawi yang valid.
 */
function isValidRoman(string $s): bool {
  return fromRoman($s) !== false;
}

/**
 * Deteksi input: apakah angka atau romawi?
 */
function detectInputType(string $s): string {
  $s = trim($s);
  if (is_numeric($s) && strpos($s, '.') === false) return 'arabic';
  if (preg_match('/^[IVXLCDMivxlcdm]+$/', $s)) return 'roman';
  return 'unknown';
}

/**
 * Uraikan angka Romawi menjadi komponen-komponennya.
 */
function explainRoman(string $roman): array {
  $roman   = strtoupper(trim($roman));
  $result  = [];
  $i       = 0;
  $symbols = ['CM','CD','XC','XL','IX','IV','DCCC','CCC','LXXX','XXX','VIII','III',
               'DCC','DC','LXX','LX','VII','VI','II','M','D','C','L','X','V','I'];

  while ($i < strlen($roman)) {
    $found = false;
    foreach ($symbols as $sym) {
      $len = strlen($sym);
      if (substr($roman, $i, $len) === $sym) {
        $romanVals = ['I'=>1,'V'=>5,'X'=>10,'L'=>50,'C'=>100,'D'=>500,'M'=>1000];
        $val = 0;
        for ($j = 0; $j < strlen($sym); $j++) {
          $v = $romanVals[$sym[$j]] ?? 0;
          $next = isset($sym[$j+1]) ? ($romanVals[$sym[$j+1]] ?? 0) : 0;
          $val += ($v < $next) ? -$v : $v;
        }
        $result[] = ['symbol' => $sym, 'value' => abs($val)];
        $i += $len;
        $found = true;
        break;
      }
    }
    if (!$found) $i++;
  }
  return $result;
}

// ── Handle POST ──────────────────────────────────────────────
$server_result   = null;
$server_error    = '';
$post_input      = '';
$post_mode       = 'convert'; // convert | bulk | validate
$post_direction  = 'auto';    // auto | to_roman | to_arabic
$bulk_results    = [];
$post_bulk_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode      = in_array($_POST['mode'] ?? 'convert', ['convert','bulk','validate'])
                      ? $_POST['mode'] : 'convert';
  $post_direction = in_array($_POST['direction'] ?? 'auto', ['auto','to_roman','to_arabic'])
                      ? $_POST['direction'] : 'auto';
  $post_input     = trim($_POST['input_val'] ?? '');
  $post_bulk_input = $_POST['bulk_input'] ?? '';

  switch ($post_mode) {
    case 'convert':
      if ($post_input === '') { $server_error = 'Input tidak boleh kosong.'; break; }

      $dir = $post_direction;
      if ($dir === 'auto') $dir = detectInputType($post_input);

      if ($dir === 'arabic' || $dir === 'to_roman') {
        $n = (int)$post_input;
        if ($n < 1 || $n > 3999) {
          $server_error = 'Angka harus antara 1 dan 3999.'; break;
        }
        $roman = toRoman($n);
        $server_result = [
          'type'    => 'arabic_to_roman',
          'input'   => $n,
          'output'  => $roman,
          'explain' => explainRoman($roman),
        ];
      } elseif ($dir === 'roman' || $dir === 'to_arabic') {
        $arabic = fromRoman($post_input);
        if ($arabic === false) {
          $server_error = '"' . $post_input . '" bukan angka Romawi yang valid.'; break;
        }
        $server_result = [
          'type'    => 'roman_to_arabic',
          'input'   => strtoupper($post_input),
          'output'  => $arabic,
          'explain' => explainRoman(strtoupper($post_input)),
        ];
      } else {
        $server_error = 'Input tidak dikenali sebagai angka Arab maupun Romawi.';
      }
      break;

    case 'bulk':
      if (trim($post_bulk_input) === '') { $server_error = 'Input massal tidak boleh kosong.'; break; }
      $lines = array_filter(
        explode("\n", str_replace("\r\n", "\n", $post_bulk_input)),
        fn($l) => trim($l) !== ''
      );
      if (count($lines) > 500) { $server_error = 'Maksimal 500 baris.'; break; }
      foreach ($lines as $line) {
        $val = trim($line);
        $dir = detectInputType($val);
        if ($dir === 'arabic') {
          $n = (int)$val;
          $r = ($n >= 1 && $n <= 3999) ? toRoman($n) : false;
          $bulk_results[] = ['input' => $val, 'output' => $r ?: 'Error (1–3999)', 'ok' => $r !== false, 'dir' => '→'];
        } elseif ($dir === 'roman') {
          $n = fromRoman($val);
          $bulk_results[] = ['input' => strtoupper($val), 'output' => $n !== false ? (string)$n : 'Invalid', 'ok' => $n !== false, 'dir' => '←'];
        } else {
          $bulk_results[] = ['input' => $val, 'output' => 'Tidak dikenali', 'ok' => false, 'dir' => '?'];
        }
      }
      break;

    case 'validate':
      if ($post_input === '') { $server_error = 'Input tidak boleh kosong.'; break; }
      $isRoman  = isValidRoman($post_input);
      $isArabic = is_numeric($post_input) && (int)$post_input >= 1 && (int)$post_input <= 3999;
      $server_result = [
        'type'     => 'validate',
        'input'    => $post_input,
        'is_roman' => $isRoman,
        'is_arabic'=> $isArabic,
        'arabic_val' => $isRoman ? fromRoman($post_input) : null,
        'roman_val'  => $isArabic ? toRoman((int)$post_input) : null,
      ];
      break;
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Roman Numeral Converter Online — Angka Arab ↔ Romawi | Multi Tools',
  'description' => 'Konversi angka Arab ke Romawi dan sebaliknya secara instan. Mendukung 1–3999, penjelasan komponen, validasi, mode massal, dan tabel referensi lengkap.',
  'keywords'    => 'roman numeral converter, angka romawi, konversi romawi, arabic to roman, roman to arabic, MCMXCIX, multi tools',
  'og_title'    => 'Roman Numeral Converter Online — Arab ↔ Romawi',
  'og_desc'     => 'Konversi angka Arab ke Romawi dan sebaliknya instan. Bulk mode, validasi, penjelasan komponen.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Konversi',   'url' => SITE_URL . '/tools?cat=convert'],
    ['name' => 'Roman Numeral Converter'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/roman-numeral-converter#webpage',
      'url'         => SITE_URL . '/tools/roman-numeral-converter',
      'name'        => 'Roman Numeral Converter Online',
      'description' => 'Konversi angka Arab ke Romawi dan sebaliknya secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',    'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Konversi',   'item' => SITE_URL . '/tools?cat=convert'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Roman Numeral Converter', 'item' => SITE_URL . '/tools/roman-numeral-converter'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Roman Numeral Converter',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/roman-numeral-converter',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Mode tabs ── */
.mode-tabs {
  display: flex; gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1.5rem;
}
.mode-tab {
  flex: 1; padding: .55rem .4rem;
  background: var(--bg); border: none;
  border-right: 1px solid var(--border);
  font-family: var(--font-body); font-size: .85rem; font-weight: 600;
  color: var(--muted); cursor: pointer;
  transition: all var(--transition); text-align: center;
}
.mode-tab:last-child { border-right: none; }
.mode-tab:hover      { background: var(--surface); color: var(--text); }
.mode-tab.active     { background: var(--accent3); color: #fff; }

/* ── Direction toggle ── */
.dir-toggle {
  display: inline-flex;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1.25rem;
}
.dir-btn {
  padding: .45rem 1.1rem;
  background: var(--bg); border: none;
  font-family: var(--font-body); font-size: .82rem; font-weight: 600;
  color: var(--muted); cursor: pointer;
  transition: all var(--transition);
}
.dir-btn:not(:last-child) { border-right: 1px solid var(--border); }
.dir-btn.active { background: var(--accent3); color: #fff; }

/* ── Hero conversion display ── */
.roman-hero {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 1rem; align-items: center;
  margin-bottom: 1.5rem;
}
@media (max-width: 580px) {
  .roman-hero { grid-template-columns: 1fr; }
  .hero-arrow { transform: rotate(90deg); }
}
.hero-box {
  background: var(--bg); border: 2px solid var(--border);
  border-radius: var(--radius-lg); padding: 1.1rem 1.25rem;
  display: flex; flex-direction: column; gap: .4rem;
  min-height: 90px; transition: border-color .2s;
}
.hero-box.arabic { border-color: var(--accent2); }
.hero-box.roman  { border-color: var(--accent3); }
.hero-box-type {
  font-family: var(--font-mono); font-size: .68rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .1em;
}
.hero-box.arabic .hero-box-type { color: var(--accent2); }
.hero-box.roman  .hero-box-type { color: var(--accent3); }
.hero-box-value {
  font-size: clamp(1.4rem, 4vw, 2rem);
  font-weight: 800; letter-spacing: -.02em;
  line-height: 1.2; word-break: break-all;
}
.hero-box.arabic .hero-box-value { color: var(--accent2); font-family: var(--font-mono); }
.hero-box.roman  .hero-box-value { color: var(--accent3); font-family: 'Georgia', serif; }
.hero-box-value.placeholder      { color: var(--muted); font-size: 1rem; font-weight: 400; font-style: italic; }
.hero-box-sub { font-size: .75rem; color: var(--muted); }
.hero-arrow {
  font-size: 1.5rem; color: var(--muted);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ── Component breakdown ── */
.component-breakdown {
  display: flex; flex-wrap: wrap; gap: .4rem; align-items: center;
  padding: .85rem 1rem;
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-sm); margin-top: .75rem;
}
.comp-item {
  display: flex; flex-direction: column; align-items: center; gap: .15rem;
  padding: .5rem .75rem;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: border-color .2s;
  cursor: default;
}
.comp-item:hover { border-color: var(--accent3); }
.comp-symbol {
  font-family: 'Georgia', serif; font-size: 1.1rem; font-weight: 700;
  color: var(--accent3);
}
.comp-value  {
  font-family: var(--font-mono); font-size: .68rem; color: var(--muted);
}
.comp-plus {
  font-size: 1rem; color: var(--muted); align-self: center;
}

/* ── Input validation badge ── */
.input-wrap { position: relative; }
.input-wrap input { padding-right: 6rem !important; }
.detect-badge {
  position: absolute; right: .6rem; top: 50%;
  transform: translateY(-50%);
  font-family: var(--font-mono); font-size: .68rem; font-weight: 700;
  padding: .15rem .45rem; border-radius: 4px; pointer-events: none;
  transition: all .2s;
}
.detect-badge.arabic { background: rgba(14,165,233,.15); color: var(--accent2); }
.detect-badge.roman  { background: rgba(124,58,237,.15); color: var(--accent3); }
.detect-badge.err    { background: rgba(239,68,68,.15);  color: #dc2626; }
.detect-badge.empty  { display: none; }

/* ── Validate result ── */
.validate-box {
  display: flex; align-items: flex-start; gap: .75rem;
  padding: 1rem 1.1rem; border-radius: var(--radius-sm);
  border: 1px solid;
}
.validate-box.valid    { background: #f0fdf4; border-color: #86efac; }
.validate-box.invalid  { background: #fef2f2; border-color: #fca5a5; }
.validate-box.partial  { background: #fffbeb; border-color: #fcd34d; }
.validate-icon { font-size: 1.5rem; line-height: 1; flex-shrink: 0; }
.validate-info strong  { display: block; font-size: .9rem; margin-bottom: .4rem; }
.validate-row {
  display: flex; align-items: center; gap: .5rem;
  font-size: .8rem; color: var(--muted); margin-bottom: .25rem;
}
.validate-row .vr-key { min-width: 110px; flex-shrink: 0; }
.validate-row .vr-val { font-family: var(--font-mono); font-weight: 700; color: var(--text); }

/* ── Bulk table ── */
.bulk-table-wrap { max-height: 380px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-sm); }
.bulk-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.bulk-table th {
  position: sticky; top: 0; background: var(--surface);
  border-bottom: 1px solid var(--border); padding: .5rem .85rem;
  text-align: left; font-family: var(--font-mono); font-size: .67rem;
  letter-spacing: .06em; text-transform: uppercase; color: var(--muted); font-weight: 700;
}
.bulk-table td { padding: .45rem .85rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.bulk-table tr:last-child td { border-bottom: none; }
.bulk-table tr:hover td { background: rgba(124,58,237,.04); }
.bulk-table .td-input  { font-family: var(--font-mono); color: var(--muted); }
.bulk-table .td-dir    { text-align: center; color: var(--accent3); font-size: .8rem; }
.bulk-table .td-result {
  font-family: var(--font-mono); font-weight: 700;
  color: var(--accent3); word-break: break-all;
}
.bulk-table .td-result.err { color: #dc2626; font-weight: 400; }
.bulk-table .td-copy { text-align: right; white-space: nowrap; }
.bulk-copy-btn {
  padding: .18rem .45rem; font-size: .63rem; font-family: var(--font-mono); font-weight: 700;
  background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
  color: var(--muted); cursor: pointer; transition: all var(--transition);
}
.bulk-copy-btn:hover { background: var(--accent3); color: #fff; border-color: var(--accent3); }

/* ── Reference table ── */
.ref-table { width: 100%; border-collapse: collapse; }
.ref-table th {
  padding: .4rem .6rem; text-align: center;
  border-bottom: 1px solid var(--border);
  font-family: var(--font-mono); font-size: .67rem;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--muted); font-weight: 700;
}
.ref-table td {
  padding: .35rem .6rem; border-bottom: 1px solid var(--border);
  text-align: center; vertical-align: middle;
}
.ref-table tr:last-child td { border-bottom: none; }
.ref-table .arabic { font-family: var(--font-mono); font-weight: 700; color: var(--accent2); }
.ref-table .roman  { font-family: 'Georgia', serif; font-weight: 700; color: var(--accent3); }

/* ── Number slider ── */
.num-slider-wrap {
  display: flex; align-items: center; gap: .85rem; margin-top: .4rem;
}
.num-slider-wrap input[type="range"] {
  flex: 1; accent-color: var(--accent3);
}
.slider-val {
  font-family: var(--font-mono); font-weight: 800;
  font-size: 1.1rem; color: var(--accent3); min-width: 40px; text-align: center;
}

/* ── Roman font samples ── */
.roman-display {
  font-family: 'Georgia', serif; font-weight: 700;
  color: var(--accent3); letter-spacing: .05em;
}

/* ── Progress counter ── */
.counter-ring {
  position: relative; display: inline-flex;
  align-items: center; justify-content: center;
  width: 64px; height: 64px; flex-shrink: 0;
}
.counter-ring svg { position: absolute; inset: 0; transform: rotate(-90deg); }
.counter-ring .ring-track { fill: none; stroke: var(--border); stroke-width: 5; }
.counter-ring .ring-fill  { fill: none; stroke: var(--accent3); stroke-width: 5; stroke-linecap: round; transition: stroke-dashoffset .3s; }
.counter-ring .ring-text  {
  font-family: var(--font-mono); font-size: .65rem; font-weight: 800;
  color: var(--accent3); text-align: center; line-height: 1.1;
}
</style>

<!-- ══ BREADCRUMB ══ -->
<nav class="breadcrumb" aria-label="Breadcrumb">
  <?php foreach ($seo['breadcrumbs'] as $i => $crumb):
    $is_last = ($i === array_key_last($seo['breadcrumbs']));
  ?>
    <?php if ($i > 0): ?><span class="sep" aria-hidden="true">/</span><?php endif; ?>
    <?php if ($is_last || !isset($crumb['url'])): ?>
      <span class="current"><?= e($crumb['name']) ?></span>
    <?php else: ?>
      <a href="<?= e($crumb['url']) ?>"><?= e($crumb['name']) ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>

<!-- ══ TOOL LAYOUT ══ -->
<div class="tool-layout">

  <!-- Konten utama -->
  <div>
    <div class="panel">
      <div class="page-title">
        <span aria-hidden="true">🏛</span> Roman Numeral <span>Converter</span>
      </div>
      <p class="page-lead">
        Konversi angka Arab ↔ Romawi secara instan. Deteksi otomatis, penjelasan komponen,
        validasi kebenaran format, dan konversi massal.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php foreach (['convert'=>'🔄 Konversi','validate'=>'✔ Validasi','bulk'=>'📋 Massal'] as $v=>$l): ?>
          <button type="button" role="tab"
            class="mode-tab <?= $post_mode === $v ? 'active' : '' ?>"
            onclick="switchMode('<?= $v ?>')">
            <?= $l ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="roman-form" novalidate>
        <input type="hidden" id="mode-input"     name="mode"      value="<?= e($post_mode) ?>" />
        <input type="hidden" id="dir-input"      name="direction" value="<?= e($post_direction) ?>" />

        <!-- ══ PANEL: Konversi ══ -->
        <div id="panel-convert" class="mode-panel" <?= $post_mode !== 'convert' ? 'style="display:none;"' : '' ?>>

          <!-- Direction toggle -->
          <div>
            <div class="dir-toggle" role="group" aria-label="Arah konversi">
              <button type="button" class="dir-btn <?= $post_direction === 'auto' ? 'active' : '' ?>"
                onclick="setDir('auto')" id="dir-auto">🔄 Otomatis</button>
              <button type="button" class="dir-btn <?= $post_direction === 'to_roman' ? 'active' : '' ?>"
                onclick="setDir('to_roman')" id="dir-to-roman">123 → XIV</button>
              <button type="button" class="dir-btn <?= $post_direction === 'to_arabic' ? 'active' : '' ?>"
                onclick="setDir('to_arabic')" id="dir-to-arabic">XIV → 123</button>
            </div>
          </div>

          <!-- Hero display -->
          <div class="roman-hero" id="roman-hero">
            <div class="hero-box arabic" id="arabic-box">
              <span class="hero-box-type">Arab</span>
              <div class="hero-box-value placeholder" id="arabic-val">—</div>
              <span class="hero-box-sub" id="arabic-sub">angka 1–3999</span>
            </div>
            <div class="hero-arrow" id="hero-arrow">→</div>
            <div class="hero-box roman" id="roman-box">
              <span class="hero-box-type">Romawi</span>
              <div class="hero-box-value placeholder roman-display" id="roman-val">—</div>
              <span class="hero-box-sub" id="roman-sub">notasi romawi</span>
            </div>
          </div>

          <!-- Input -->
          <div class="form-group">
            <label for="main-input" id="main-input-label">
              Angka atau Romawi
              <span class="text-muted text-sm" id="dir-hint">(deteksi otomatis)</span>
            </label>
            <div class="input-wrap">
              <input type="text" id="main-input" name="input_val"
                placeholder="Contoh: 2025 atau MMXXV"
                value="<?= e($post_input) ?>"
                oninput="convertJS()"
                style="font-family:var(--font-mono); font-size:1rem; letter-spacing:.04em;"
                autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" />
              <span class="detect-badge empty" id="detect-badge"></span>
            </div>
          </div>

          <!-- Slider (angka Arab) -->
          <div id="slider-section" style="display:none;">
            <div class="section-mini-title" style="margin-bottom:.35rem;">Atau geser slider</div>
            <div class="num-slider-wrap">
              <input type="range" id="num-slider" min="1" max="3999" value="2025"
                oninput="document.getElementById('main-input').value=this.value; convertJS();" />
              <span class="slider-val" id="slider-val">2025</span>
            </div>
          </div>

          <!-- Component breakdown -->
          <div id="breakdown-section" style="display:none; margin-top:1rem;">
            <div class="section-mini-title" style="margin-bottom:.35rem;">📦 Komponen angka Romawi</div>
            <div class="component-breakdown" id="breakdown-area"></div>
          </div>

          <!-- Tombol -->
          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1.25rem;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Konversi via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="copyText(document.getElementById('roman-val').textContent)">
              📋 Salin Romawi
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="copyText(document.getElementById('arabic-val').textContent)">
              📋 Salin Arab
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearConvert()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: Validasi ══ -->
        <div id="panel-validate" class="mode-panel" <?= $post_mode !== 'validate' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Validasi apakah input adalah angka Arab (1–3999) atau Romawi yang valid.
              Format Romawi yang salah (seperti IIII) akan ditolak.</span>
          </div>

          <div class="form-group">
            <label for="validate-input">Masukkan angka atau Romawi</label>
            <div class="input-wrap">
              <input type="text" id="validate-input" name="input_val"
                placeholder="Contoh: MCMXCIX atau 1999"
                value="<?= ($post_mode === 'validate') ? e($post_input) : '' ?>"
                oninput="validateJS(this.value)"
                style="font-family:var(--font-mono); font-size:1rem; letter-spacing:.04em;"
                autocomplete="off" autocapitalize="characters" spellcheck="false" />
              <span class="detect-badge empty" id="validate-badge"></span>
            </div>
          </div>

          <!-- Validasi realtime -->
          <div id="validate-result-js" style="display:none;"></div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent3); border-color:var(--accent3);">
              ✔ Validasi via Server (PHP)
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="document.getElementById('validate-input').value=''; validateJS('');">
              Bersihkan
            </button>
          </div>
        </div>

        <!-- ══ PANEL: Massal ══ -->
        <div id="panel-bulk" class="mode-panel" <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Masukkan satu nilai per baris. Angka Arab dan Romawi bisa dicampur —
              sistem akan mendeteksi dan mengkonversi otomatis.</span>
          </div>

          <div class="form-group">
            <label for="bulk-input">
              Daftar angka atau Romawi <span class="text-muted text-sm">(maks. 500 baris)</span>
            </label>
            <textarea id="bulk-input-area" name="bulk_input"
              placeholder="2025&#10;1999&#10;MMXXV&#10;XIV&#10;500&#10;MCMXCIX"
              style="min-height:160px; font-family:var(--font-mono);"
            ><?= e($post_bulk_input) ?></textarea>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent3); border-color:var(--accent3);">
              🔄 Konversi Massal (PHP)
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="document.getElementById('bulk-input-area').value=''">Bersihkan</button>
          </div>
        </div>

      </form>
    </div><!-- /.panel -->

    <!-- ── Hasil server: konversi ── -->
    <?php if ($server_result && $server_result['type'] !== 'validate' && !$server_error): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Konversi berhasil via PHP server.</span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title">⚙ Hasil Server PHP</div>

      <div class="roman-hero">
        <?php if ($server_result['type'] === 'arabic_to_roman'): ?>
          <div class="hero-box arabic">
            <span class="hero-box-type">Arab</span>
            <div class="hero-box-value"><?= e($server_result['input']) ?></div>
          </div>
          <div class="hero-arrow">→</div>
          <div class="hero-box roman">
            <span class="hero-box-type">Romawi</span>
            <div class="hero-box-value roman-display"><?= e($server_result['output']) ?></div>
          </div>
        <?php else: ?>
          <div class="hero-box roman">
            <span class="hero-box-type">Romawi</span>
            <div class="hero-box-value roman-display"><?= e($server_result['input']) ?></div>
          </div>
          <div class="hero-arrow">→</div>
          <div class="hero-box arabic">
            <span class="hero-box-type">Arab</span>
            <div class="hero-box-value"><?= e($server_result['output']) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($server_result['explain'])): ?>
      <div class="section-mini-title" style="margin:.75rem 0 .5rem;">📦 Komponen</div>
      <div class="component-breakdown">
        <?php foreach ($server_result['explain'] as $i => $comp): ?>
          <?php if ($i > 0): ?><span class="comp-plus">+</span><?php endif; ?>
          <div class="comp-item">
            <span class="comp-symbol"><?= e($comp['symbol']) ?></span>
            <span class="comp-value"><?= $comp['value'] ?></span>
          </div>
        <?php endforeach; ?>
        <span class="comp-plus">=</span>
        <div class="comp-item" style="border-color:var(--accent3);">
          <span class="comp-symbol"><?= $server_result['type'] === 'arabic_to_roman' ? e($server_result['output']) : e($server_result['input']) ?></span>
          <span class="comp-value"><?= $server_result['type'] === 'arabic_to_roman' ? e($server_result['input']) : e($server_result['output']) ?></span>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Hasil server: validasi ── -->
    <?php if ($server_result && $server_result['type'] === 'validate' && !$server_error): ?>
    <?php
      $vr = $server_result;
      $isValid = $vr['is_roman'] || $vr['is_arabic'];
      $cls = $isValid ? ($vr['is_roman'] && $vr['is_arabic'] ? 'valid' : 'valid') : 'invalid';
    ?>
    <div class="validate-box <?= $cls ?>" style="margin-top:1rem;" role="alert">
      <span class="validate-icon"><?= $isValid ? '✅' : '❌' ?></span>
      <div class="validate-info">
        <strong style="color:<?= $isValid ? '#15803d' : '#b91c1c' ?>">
          <?= $isValid ? 'Valid!' : 'Tidak valid' ?>
        </strong>
        <div class="validate-row">
          <span class="vr-key">Input</span>
          <span class="vr-val roman-display"><?= e($vr['input']) ?></span>
        </div>
        <?php if ($vr['is_roman']): ?>
        <div class="validate-row">
          <span class="vr-key">Format Romawi</span>
          <span class="vr-val">✓ Valid — sama dengan <?= $vr['arabic_val'] ?></span>
        </div>
        <?php endif; ?>
        <?php if ($vr['is_arabic']): ?>
        <div class="validate-row">
          <span class="vr-key">Format Arab</span>
          <span class="vr-val">✓ Valid — sama dengan <span class="roman-display"><?= e($vr['roman_val']) ?></span></span>
        </div>
        <?php endif; ?>
        <?php if (!$isValid): ?>
        <div style="font-size:.82rem; color:#b91c1c; margin-top:.25rem;">
          Bukan angka Arab (1–3999) maupun angka Romawi yang valid.
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Hasil server: bulk ── -->
    <?php if (!empty($bulk_results)): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Berhasil konversi <strong><?= count($bulk_results) ?> nilai</strong>.</span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.85rem; flex-wrap:wrap; gap:.5rem;">
        <div class="panel-title" style="margin-bottom:0;">⚙ Hasil Konversi Massal</div>
        <div style="display:flex; gap:.5rem;">
          <button class="btn-ghost btn-sm" onclick="copyAllBulk()">📋 Salin hasil</button>
          <button class="btn-ghost btn-sm" onclick="downloadBulk()">⬇ Unduh .txt</button>
        </div>
      </div>
      <div class="bulk-table-wrap" id="bulk-result-wrap">
        <table class="bulk-table">
          <thead><tr><th>#</th><th>Input</th><th></th><th>Hasil</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($bulk_results as $idx => $row): ?>
            <tr>
              <td class="text-muted text-xs"><?= $idx + 1 ?></td>
              <td class="td-input"><?= e($row['input']) ?></td>
              <td class="td-dir"><?= e($row['dir']) ?></td>
              <td class="td-result <?= !$row['ok'] ? 'err' : '' ?> roman-display">
                <?= e($row['output']) ?>
              </td>
              <td class="td-copy">
                <?php if ($row['ok']): ?>
                  <button class="bulk-copy-btn"
                    onclick="copyText(<?= htmlspecialchars(json_encode($row['output']), ENT_QUOTES) ?>, this)">
                    SALIN
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($server_error): ?>
    <div class="alert danger" style="margin-top:1rem;" role="alert">
      <span>✕</span>
      <span><?= e($server_error) ?></span>
    </div>
    <?php endif; ?>

  </div><!-- /konten utama -->

  <!-- Sidebar -->
  <aside>
    <!-- Counter ring -->
    <div class="panel">
      <div class="panel-title">🔢 Progress 1–3999</div>
      <div style="display:flex; align-items:center; gap:1rem; margin-bottom:.75rem;">
        <div class="counter-ring">
          <svg viewBox="0 0 64 64">
            <circle class="ring-track" cx="32" cy="32" r="27"/>
            <circle class="ring-fill" cx="32" cy="32" r="27"
              stroke-dasharray="169.646" stroke-dashoffset="169.646"
              id="ring-fill-el"/>
          </svg>
          <div class="ring-text" id="ring-text">—<br>—</div>
        </div>
        <div style="flex:1;">
          <div style="font-size:.78rem; color:var(--muted);">Angka saat ini</div>
          <div style="font-family:var(--font-mono); font-weight:700; font-size:1.1rem; color:var(--accent3);" id="ring-roman-text">—</div>
          <div style="font-size:.72rem; color:var(--muted); margin-top:.2rem;" id="ring-pct-text">—% dari 3999</div>
        </div>
      </div>
    </div>

    <!-- Contoh konversi cepat -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Konversi Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.35rem;">
        <?php
        $quick = [
          1=>'I', 4=>'IV', 5=>'V', 9=>'IX', 10=>'X',
          14=>'XIV', 40=>'XL', 50=>'L', 90=>'XC',
          100=>'C', 400=>'CD', 500=>'D', 900=>'CM',
          1000=>'M', 1776=>'MDCCLXXVI', 1999=>'MCMXCIX',
          2024=>'MMXXIV', 2025=>'MMXXV', 3999=>'MMMCMXCIX',
        ];
        foreach ($quick as $arab => $rom): ?>
          <button class="btn-ghost btn-sm btn-full"
            style="display:flex; justify-content:space-between; padding:.4rem .75rem;"
            onclick="loadQuick(<?= $arab ?>)">
            <span style="font-family:var(--font-mono); color:var(--accent2); font-weight:700;"><?= $arab ?></span>
            <span style="color:var(--muted);">→</span>
            <span class="roman-display" style="font-size:.85rem;"><?= $rom ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Simbol referensi -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📖 Simbol Romawi</div>
      <table class="ref-table">
        <thead><tr><th>Simbol</th><th>Nilai</th><th>Contoh</th></tr></thead>
        <tbody>
          <?php
          $symbols = [
            'I'=>1, 'V'=>5, 'X'=>10, 'L'=>50,
            'C'=>100, 'D'=>500, 'M'=>1000,
          ];
          foreach ($symbols as $sym => $val): ?>
            <tr>
              <td class="roman"><?= $sym ?></td>
              <td class="arabic"><?= number_format($val) ?></td>
              <td style="font-size:.72rem; color:var(--muted);">
                <?= toRoman($val) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:.85rem; padding-top:.85rem; border-top:1px solid var(--border);">
        <div class="section-mini-title" style="margin-bottom:.5rem;">Aturan pengurangan</div>
        <div style="font-size:.75rem; color:var(--muted); line-height:1.9;">
          <div><span class="roman-display" style="font-size:.85rem;">IV</span> = 5−1 = 4</div>
          <div><span class="roman-display" style="font-size:.85rem;">IX</span> = 10−1 = 9</div>
          <div><span class="roman-display" style="font-size:.85rem;">XL</span> = 50−10 = 40</div>
          <div><span class="roman-display" style="font-size:.85rem;">XC</span> = 100−10 = 90</div>
          <div><span class="roman-display" style="font-size:.85rem;">CD</span> = 500−100 = 400</div>
          <div><span class="roman-display" style="font-size:.85rem;">CM</span> = 1000−100 = 900</div>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/base-converter"   class="btn-ghost btn-sm btn-full">Base Converter</a>
        <a href="/tools/number-to-words"  class="btn-ghost btn-sm btn-full">Number to Words</a>
        <a href="/tools/timestamp"        class="btn-ghost btn-sm btn-full">Timestamp Converter</a>
        <a href="/tools/unit-converter"   class="btn-ghost btn-sm btn-full">Unit Converter</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Roman Numeral Converter — logika JS (realtime)
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

const ROMAN_MAP_JS = [
  [1000,'M'],[900,'CM'],[800,'DCCC'],[700,'DCC'],[600,'DC'],
  [500,'D'],[400,'CD'],[300,'CCC'],[200,'CC'],[100,'C'],
  [90,'XC'],[80,'LXXX'],[70,'LXX'],[60,'LX'],[50,'L'],
  [40,'XL'],[30,'XXX'],[20,'XX'],[10,'X'],[9,'IX'],
  [8,'VIII'],[7,'VII'],[6,'VI'],[5,'V'],[4,'IV'],[3,'III'],[2,'II'],[1,'I'],
];

const ROMAN_VALS = {I:1,V:5,X:10,L:50,C:100,D:500,M:1000};

// ── Konversi JS ───────────────────────────────────────────────
function toRomanJS(n) {
  if (n < 1 || n > 3999 || !Number.isInteger(n)) return null;
  let result = '';
  for (const [val, sym] of ROMAN_MAP_JS) {
    while (n >= val) { result += sym; n -= val; }
  }
  return result;
}

function fromRomanJS(str) {
  str = str.toUpperCase().trim();
  if (!/^[IVXLCDM]+$/.test(str)) return null;
  let result = 0, prev = 0;
  for (const ch of [...str].reverse()) {
    const val = ROMAN_VALS[ch];
    if (!val) return null;
    result += val < prev ? -val : val;
    prev = val;
  }
  if (result < 1 || result > 3999) return null;
  if (toRomanJS(result) !== str) return null; // validasi format
  return result;
}

function explainRomanJS(roman) {
  const SYMBOLS = ['CM','CD','XC','XL','IX','IV','DCCC','CCC','LXXX','XXX','VIII','III',
                   'DCC','DC','LXX','LX','VII','VI','II','M','D','C','L','X','V','I'];
  const VALS = {I:1,V:5,X:10,L:50,C:100,D:500,M:1000};
  const symVals = {
    M:1000,D:500,C:100,L:50,X:10,V:5,I:1,
    CM:900,CD:400,XC:90,XL:40,IX:9,IV:4,
    MM:2000,MMM:3000,CC:200,CCC:300,DCCC:800,DCC:700,DC:600,
    LXX:70,LX:60,LXXX:80,XXX:30,XX:20,VII:7,VI:6,VIII:8,III:3,II:2,
  };
  const result = [];
  let i = 0;
  while (i < roman.length) {
    let found = false;
    for (const sym of SYMBOLS) {
      if (roman.startsWith(sym, i)) {
        const val = symVals[sym] || [...sym].reduce((a,c,j,arr) => {
          const v = VALS[c] || 0;
          const nx = VALS[arr[j+1]] || 0;
          return a + (v < nx ? -v : v);
        }, 0);
        result.push({ symbol: sym, value: Math.abs(val) });
        i += sym.length; found = true; break;
      }
    }
    if (!found) i++;
  }
  return result;
}

function detectType(s) {
  s = s.trim();
  if (!s) return 'empty';
  if (/^\d+$/.test(s)) return 'arabic';
  if (/^[IVXLCDMivxlcdm]+$/.test(s)) return 'roman';
  return 'unknown';
}

// ── Mode state ────────────────────────────────────────────────
let currentMode  = '<?= $post_mode ?>';
let currentDir   = '<?= $post_direction ?>';

function switchMode(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;
  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    t.classList.toggle('active', ['convert','validate','bulk'][i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

function setDir(dir) {
  currentDir = dir;
  document.getElementById('dir-input').value = dir;
  ['auto','to-roman','to-arabic'].forEach(id => {
    const btn = document.getElementById('dir-' + id);
    if (btn) btn.classList.toggle('active', id.replace('-','_') === dir || (id === 'auto' && dir === 'auto'));
  });
  const hint = document.getElementById('dir-hint');
  if (hint) {
    hint.textContent = dir === 'auto' ? '(deteksi otomatis)' :
                       dir === 'to_roman' ? '(Arab → Romawi)' : '(Romawi → Arab)';
  }
  convertJS();
}

// ── Main convert ──────────────────────────────────────────────
function convertJS() {
  const input    = document.getElementById('main-input')?.value.trim();
  const arabicEl = document.getElementById('arabic-val');
  const romanEl  = document.getElementById('roman-val');
  const badge    = document.getElementById('detect-badge');
  const slider   = document.getElementById('slider-section');
  const bdSect   = document.getElementById('breakdown-section');

  if (!input) {
    arabicEl.textContent = '—'; arabicEl.className = 'hero-box-value placeholder';
    romanEl.textContent  = '—'; romanEl.className  = 'hero-box-value placeholder roman-display';
    badge.className = 'detect-badge empty'; badge.textContent = '';
    slider.style.display  = 'none';
    bdSect.style.display  = 'none';
    updateRing(null);
    return;
  }

  const type = detectType(input);
  let arabicNum = null, romanStr = null;

  if ((currentDir === 'auto' && type === 'arabic') || currentDir === 'to_roman') {
    arabicNum = parseInt(input, 10);
    if (isNaN(arabicNum) || arabicNum < 1 || arabicNum > 3999) {
      badge.className = 'detect-badge err'; badge.textContent = '✕ 1–3999';
      arabicEl.textContent = input; arabicEl.className = 'hero-box-value';
      romanEl.textContent  = '—';  romanEl.className  = 'hero-box-value placeholder roman-display';
      slider.style.display = '';
      const sliderEl = document.getElementById('num-slider');
      if (sliderEl && arabicNum >= 1 && arabicNum <= 3999) sliderEl.value = arabicNum;
      document.getElementById('slider-val').textContent = input;
      bdSect.style.display = 'none';
      updateRing(null);
      return;
    }
    romanStr = toRomanJS(arabicNum);
    badge.className = 'detect-badge arabic'; badge.textContent = '123 Arab';
    slider.style.display = '';
    document.getElementById('num-slider').value = arabicNum;
    document.getElementById('slider-val').textContent = arabicNum;

  } else if ((currentDir === 'auto' && type === 'roman') || currentDir === 'to_arabic') {
    arabicNum = fromRomanJS(input.toUpperCase());
    romanStr  = input.toUpperCase();
    if (arabicNum === null) {
      badge.className = 'detect-badge err'; badge.textContent = '✕ Invalid';
      romanEl.textContent  = input.toUpperCase(); romanEl.className = 'hero-box-value roman-display';
      arabicEl.textContent = '—'; arabicEl.className = 'hero-box-value placeholder';
      slider.style.display = 'none';
      bdSect.style.display = 'none';
      updateRing(null);
      return;
    }
    badge.className = 'detect-badge roman'; badge.textContent = 'XIV Romawi';
    slider.style.display = 'none';

  } else {
    badge.className = 'detect-badge err'; badge.textContent = '✕ Unknown';
    arabicEl.textContent = '—'; arabicEl.className = 'hero-box-value placeholder';
    romanEl.textContent  = '—'; romanEl.className  = 'hero-box-value placeholder roman-display';
    bdSect.style.display = 'none';
    updateRing(null);
    return;
  }

  arabicEl.textContent = arabicNum; arabicEl.className = 'hero-box-value';
  romanEl.textContent  = romanStr;  romanEl.className  = 'hero-box-value roman-display';
  updateRing(arabicNum, romanStr);

  // Breakdown
  if (romanStr) {
    const parts = explainRomanJS(romanStr);
    if (parts.length > 1) {
      const html = parts.map((p, i) =>
        (i > 0 ? '<span class="comp-plus">+</span>' : '') +
        `<div class="comp-item"><span class="comp-symbol">${esc(p.symbol)}</span><span class="comp-value">${p.value}</span></div>`
      ).join('') +
      `<span class="comp-plus">=</span>
       <div class="comp-item" style="border-color:var(--accent3);">
         <span class="comp-symbol">${esc(romanStr)}</span>
         <span class="comp-value">${arabicNum}</span>
       </div>`;
      document.getElementById('breakdown-area').innerHTML = html;
      bdSect.style.display = '';
    } else {
      bdSect.style.display = 'none';
    }
  }
}

// ── Ring progress ─────────────────────────────────────────────
function updateRing(arabic, roman) {
  const fill   = document.getElementById('ring-fill-el');
  const text   = document.getElementById('ring-text');
  const rText  = document.getElementById('ring-roman-text');
  const pText  = document.getElementById('ring-pct-text');
  const circum = 2 * Math.PI * 27; // ≈ 169.646

  if (!arabic || arabic < 1 || arabic > 3999) {
    if (fill) fill.style.strokeDashoffset = circum;
    if (text) text.innerHTML = '—<br>—';
    if (rText) rText.textContent = '—';
    if (pText) pText.textContent = '—';
    return;
  }
  const pct    = arabic / 3999;
  const offset = circum * (1 - pct);
  if (fill) fill.style.strokeDashoffset = offset;
  if (text) text.innerHTML = arabic + '<br>' + (Math.round(pct * 100)) + '%';
  if (rText) rText.textContent = roman || toRomanJS(arabic) || '—';
  if (pText) pText.textContent = Math.round(pct * 100) + '% dari 3999';
}

// ── Validate JS ───────────────────────────────────────────────
function validateJS(val) {
  val = val.trim();
  const badge  = document.getElementById('validate-badge');
  const resEl  = document.getElementById('validate-result-js');
  if (!resEl) return;

  if (!val) {
    badge.className = 'detect-badge empty'; badge.textContent = '';
    resEl.style.display = 'none'; return;
  }

  const type     = detectType(val);
  const isRoman  = type === 'roman' && fromRomanJS(val.toUpperCase()) !== null;
  const isArabic = type === 'arabic' && parseInt(val) >= 1 && parseInt(val) <= 3999;
  const isValid  = isRoman || isArabic;

  badge.className = isValid ? 'detect-badge ' + (isRoman ? 'roman' : 'arabic') : 'detect-badge err';
  badge.textContent = isValid ? '✓ Valid' : '✕ Invalid';

  let infoHtml = '';
  if (isRoman) {
    const arabic = fromRomanJS(val.toUpperCase());
    infoHtml = `<div class="validate-row"><span class="vr-key">Format Romawi</span><span class="vr-val">✓ Valid — sama dengan ${arabic}</span></div>`;
  }
  if (isArabic) {
    const roman = toRomanJS(parseInt(val));
    infoHtml += `<div class="validate-row"><span class="vr-key">Format Arab</span><span class="vr-val">✓ Valid — sama dengan <span class="roman-display">${roman}</span></span></div>`;
  }

  resEl.style.display = '';
  resEl.innerHTML = `<div class="validate-box ${isValid ? 'valid' : 'invalid'}" style="margin-top:.5rem;">
    <span class="validate-icon">${isValid ? '✅' : '❌'}</span>
    <div class="validate-info">
      <strong style="color:${isValid ? '#15803d' : '#b91c1c'}">${isValid ? 'Valid!' : 'Tidak valid'}</strong>
      <div class="validate-row"><span class="vr-key">Input</span><span class="vr-val roman-display">${esc(val)}</span></div>
      ${infoHtml}
      ${!isValid ? '<div style="font-size:.82rem;color:#b91c1c;margin-top:.25rem;">Bukan angka Arab (1–3999) maupun Romawi yang valid.</div>' : ''}
    </div>
  </div>`;
}

// ── Quick load ────────────────────────────────────────────────
function loadQuick(arabic) {
  switchMode('convert');
  setDir('to_roman');
  document.getElementById('main-input').value = arabic;
  convertJS();
}

// ── Bulk copy/download ────────────────────────────────────────
function copyAllBulk() {
  const rows = document.querySelectorAll('#bulk-result-wrap .td-result');
  const all  = Array.from(rows).map(td => td.textContent.trim()).join('\n');
  if (all) navigator.clipboard.writeText(all).then(() => showToast && showToast('Disalin!', 'success'));
}

function downloadBulk() {
  const rows = document.querySelectorAll('#bulk-result-wrap tbody tr');
  const lines = Array.from(rows).map((tr, i) => {
    const tds = tr.querySelectorAll('td');
    return `${i+1}. ${tds[1]?.textContent.trim()} ${tds[2]?.textContent.trim()} ${tds[3]?.textContent.trim()}`;
  });
  if (!lines.length) return;
  const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'roman-conversion.txt';
  a.click();
}

function copyText(text, btn) {
  if (!text || text === '—') return;
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) { showToast && showToast('Disalin!', 'success', 1500); return; }
    const orig = btn.textContent;
    btn.textContent = '✓';
    btn.style.cssText = 'background:var(--accent3);border-color:var(--accent3);color:#fff;';
    setTimeout(() => { btn.textContent = orig; btn.style.cssText = ''; }, 1500);
  });
}

function clearConvert() {
  document.getElementById('main-input').value = '';
  convertJS();
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Init ──────────────────────────────────────────────────────
switchMode(currentMode);
setDir(currentDir);
<?php if ($post_input && $post_mode === 'convert'): ?>
convertJS();
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>