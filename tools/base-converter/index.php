<?php
require '../../includes/config.php';
/**
 * Multi Tools — Base Converter
 * Konversi angka antar basis: Biner (2), Oktal (8), Desimal (10),
 * Heksadesimal (16), dan basis kustom (2-36).
 * Mendukung konversi instan, tabel semua basis, dan mode massal.
 * ============================================================ */

// ── Fungsi konversi ──────────────────────────────────────────

/**
 * Validasi apakah string valid untuk basis tertentu.
 */
function isValidBase(string $num, int $base): bool {
  if ($num === '' || $num === '-') return false;
  $digits = '0123456789abcdefghijklmnopqrstuvwxyz';
  $valid  = substr($digits, 0, $base);
  $num    = ltrim(strtolower($num), '-');
  return $num !== '' && strspn($num, $valid) === strlen($num);
}

/**
 * Konversi angka dari basis asal ke basis tujuan.
 * Mendukung angka besar via PHP GMP extension jika tersedia.
 */
function convertBase(string $num, int $fromBase, int $toBase): string|false {
  $num = trim($num);
  if ($num === '') return false;

  $negative = str_starts_with($num, '-');
  if ($negative) $num = ltrim($num, '-');

  $num = strtolower($num);
  if (!isValidBase($num, $fromBase)) return false;

  // Gunakan GMP jika tersedia (angka sangat besar)
  if (function_exists('gmp_init') && strlen($num) > 15) {
    $gmpNum = gmp_init($num, $fromBase);
    $result = gmp_strval($gmpNum, $toBase);
    return ($negative ? '-' : '') . strtoupper($result);
  }

  // Konversi manual via desimal sebagai intermediate
  $decimal = base_convert($num, $fromBase, 10);
  if ($decimal === false || !is_numeric($decimal)) return false;

  $result  = base_convert($decimal, 10, $toBase);
  return ($negative ? '-' : '') . strtoupper($result);
}

/**
 * Konversi ke semua basis standar sekaligus.
 */
function convertToAll(string $num, int $fromBase): array {
  $results = [];
  $bases   = [2 => 'Binary', 8 => 'Octal', 10 => 'Decimal', 16 => 'Hex'];
  foreach ($bases as $base => $label) {
    $r = convertBase($num, $fromBase, $base);
    $results[$base] = ['label' => $label, 'value' => $r !== false ? $r : 'Error'];
  }
  return $results;
}

/**
 * Format angka binary dengan pemisah per 4 bit (nibble).
 */
function formatBinary(string $bin): string {
  $bin = ltrim($bin, '-');
  // Pad ke kelipatan 4
  $padded = str_pad($bin, ceil(strlen($bin) / 4) * 4, '0', STR_PAD_LEFT);
  return implode(' ', str_split($padded, 4));
}

/**
 * Konversi desimal ke format lain termasuk representasi bit.
 */
function getNumberInfo(string $dec): array {
  if (!is_numeric($dec) || str_contains($dec, '.')) return [];
  $n = (int)$dec;
  return [
    'binary'    => decbin($n),
    'octal'     => decoct($n),
    'hex'       => strtoupper(dechex($n)),
    'bits'      => strlen(decbin(abs($n))),
    'bytes'     => ceil(strlen(decbin(abs($n))) / 8),
    'signed8'   => $n >= -128 && $n <= 127,
    'signed16'  => $n >= -32768 && $n <= 32767,
    'signed32'  => $n >= -2147483648 && $n <= 2147483647,
  ];
}

// ── Handle POST ──────────────────────────────────────────────
$server_result  = [];
$server_error   = '';
$post_input     = '';
$post_from_base = 10;
$post_to_base   = 2;
$post_mode      = 'single'; // single | all | custom | bulk
$post_custom_to = 16;
$post_bulk_input = '';
$post_bulk_from  = 10;
$post_bulk_to    = 16;
$bulk_results    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode       = in_array($_POST['mode'] ?? 'single', ['single','all','custom','bulk'])
                       ? $_POST['mode'] : 'single';
  $post_input      = trim($_POST['input_num'] ?? '');
  $post_from_base  = max(2, min(36, (int)($_POST['from_base'] ?? 10)));
  $post_to_base    = max(2, min(36, (int)($_POST['to_base'] ?? 2)));
  $post_custom_to  = max(2, min(36, (int)($_POST['custom_to'] ?? 16)));
  $post_bulk_input = $_POST['bulk_input'] ?? '';
  $post_bulk_from  = max(2, min(36, (int)($_POST['bulk_from'] ?? 10)));
  $post_bulk_to    = max(2, min(36, (int)($_POST['bulk_to'] ?? 16)));

  switch ($post_mode) {
    case 'single':
      if ($post_input === '') { $server_error = 'Input angka tidak boleh kosong.'; break; }
      if (!isValidBase($post_input, $post_from_base)) {
        $server_error = "Angka \"$post_input\" tidak valid untuk basis $post_from_base.";
        break;
      }
      $converted = convertBase($post_input, $post_from_base, $post_to_base);
      if ($converted === false) { $server_error = 'Konversi gagal.'; break; }
      $server_result = [
        'input'     => $post_input,
        'from_base' => $post_from_base,
        'to_base'   => $post_to_base,
        'result'    => $converted,
        'all'       => convertToAll($post_input, $post_from_base),
      ];
      break;

    case 'all':
      if ($post_input === '') { $server_error = 'Input angka tidak boleh kosong.'; break; }
      if (!isValidBase($post_input, $post_from_base)) {
        $server_error = "Angka \"$post_input\" tidak valid untuk basis $post_from_base.";
        break;
      }
      $server_result = [
        'input'     => $post_input,
        'from_base' => $post_from_base,
        'all'       => convertToAll($post_input, $post_from_base),
      ];
      // Tambah info jika dari desimal
      if ($post_from_base === 10) {
        $server_result['info'] = getNumberInfo($post_input);
      }
      break;

    case 'custom':
      if ($post_input === '') { $server_error = 'Input angka tidak boleh kosong.'; break; }
      if (!isValidBase($post_input, $post_from_base)) {
        $server_error = "Angka \"$post_input\" tidak valid untuk basis $post_from_base.";
        break;
      }
      $converted = convertBase($post_input, $post_from_base, $post_custom_to);
      if ($converted === false) { $server_error = 'Konversi gagal.'; break; }
      $server_result = [
        'input'     => $post_input,
        'from_base' => $post_from_base,
        'to_base'   => $post_custom_to,
        'result'    => $converted,
      ];
      break;

    case 'bulk':
      if (trim($post_bulk_input) === '') { $server_error = 'Input massal tidak boleh kosong.'; break; }
      $lines = array_filter(
        explode("\n", str_replace("\r\n", "\n", $post_bulk_input)),
        fn($l) => trim($l) !== ''
      );
      if (count($lines) > 200) { $server_error = 'Maksimal 200 angka per sekali konversi.'; break; }
      foreach ($lines as $line) {
        $num = trim($line);
        if (!isValidBase($num, $post_bulk_from)) {
          $bulk_results[] = ['input' => $num, 'result' => 'Invalid', 'ok' => false];
        } else {
          $r = convertBase($num, $post_bulk_from, $post_bulk_to);
          $bulk_results[] = ['input' => $num, 'result' => $r !== false ? $r : 'Error', 'ok' => $r !== false];
        }
      }
      break;
  }
}

// ── Nama basis ───────────────────────────────────────────────
function basisName(int $base): string {
  return match($base) {
    2  => 'Binary',
    8  => 'Octal',
    10 => 'Decimal',
    16 => 'Hexadecimal',
    default => "Base-$base",
  };
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Base Converter Online — Konversi Biner, Oktal, Desimal, Hex | Multi Tools',
  'description' => 'Konversi angka antar basis bilangan secara instan: biner (2), oktal (8), desimal (10), heksadesimal (16), dan basis kustom 2-36. Mode massal, tabel semua basis, dan info bit.',
  'keywords'    => 'base converter, konversi bilangan, biner ke desimal, hex to decimal, binary converter, number base, oktal, heksadesimal, multi tools',
  'og_title'    => 'Base Converter Online — Biner, Oktal, Desimal, Hex',
  'og_desc'     => 'Konversi angka antar basis instan. Biner, oktal, desimal, hex, dan basis kustom 2-36.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'Base Converter'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/base-converter#webpage',
      'url'         => SITE_URL . '/tools/base-converter',
      'name'        => 'Base Converter Online',
      'description' => 'Konversi angka antar basis bilangan secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools','item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Base Converter', 'item' => SITE_URL . '/tools/base-converter'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Base Converter',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/base-converter',
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
  flex: 1; padding: .5rem .35rem;
  background: var(--bg); border: none;
  border-right: 1px solid var(--border);
  font-family: var(--font-body); font-size: .8rem; font-weight: 600;
  color: var(--muted); cursor: pointer;
  transition: all var(--transition); text-align: center; white-space: nowrap;
}
.mode-tab:last-child { border-right: none; }
.mode-tab:hover      { background: var(--surface); color: var(--text); }
.mode-tab.active     { background: var(--accent); color: #fff; }

/* ── Base selector pills ── */
.base-pills {
  display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .75rem;
}
.base-pill {
  padding: .3rem .8rem;
  border: 1px solid var(--border); border-radius: 99px;
  font-family: var(--font-mono); font-size: .78rem; font-weight: 700;
  cursor: pointer; background: var(--surface); color: var(--muted);
  transition: all var(--transition); user-select: none;
}
.base-pill:hover  { border-color: var(--accent); color: var(--accent); background: rgba(37,99,235,.06); }
.base-pill.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.base-pill .sub   { font-size: .6rem; opacity: .75; }

/* ── Main conversion display ── */
.conv-hero {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 1rem; align-items: center;
  margin-bottom: 1.5rem;
}
@media (max-width: 600px) {
  .conv-hero { grid-template-columns: 1fr; }
  .conv-arrow { transform: rotate(90deg); }
}
.conv-box {
  background: var(--bg); border: 2px solid var(--border);
  border-radius: var(--radius-md); padding: 1rem 1.1rem;
  transition: border-color .2s;
}
.conv-box.from { border-color: var(--accent2); }
.conv-box.to   { border-color: var(--accent5); }
.conv-box-label {
  font-family: var(--font-mono); font-size: .68rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .08em;
  margin-bottom: .35rem;
}
.conv-box.from .conv-box-label { color: var(--accent2); }
.conv-box.to   .conv-box-label { color: var(--accent5); }
.conv-value {
  font-family: var(--font-mono); font-size: 1.15rem; font-weight: 800;
  word-break: break-all; line-height: 1.4; color: var(--text);
  min-height: 32px;
}
.conv-value.placeholder { color: var(--muted); font-weight: 400; font-size: .9rem; }
.conv-base-tag {
  font-family: var(--font-mono); font-size: .68rem;
  color: var(--muted); margin-top: .3rem;
}
.conv-arrow {
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: var(--muted); flex-shrink: 0;
  transition: color .2s;
}

/* ── All bases table ── */
.bases-table { width: 100%; border-collapse: collapse; }
.bases-table th {
  padding: .5rem .85rem; text-align: left;
  border-bottom: 1px solid var(--border);
  font-family: var(--font-mono); font-size: .68rem;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--muted); font-weight: 700;
  background: var(--surface);
}
.bases-table td {
  padding: .5rem .85rem; border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.bases-table tr:last-child td { border-bottom: none; }
.bases-table tr:hover td { background: rgba(37,99,235,.04); }
.bases-table .td-base {
  font-family: var(--font-mono); font-size: .75rem; font-weight: 700;
  color: var(--muted); white-space: nowrap;
}
.bases-table .td-value {
  font-family: var(--font-mono); font-size: .88rem; font-weight: 700;
  color: var(--text); word-break: break-all;
}
.bases-table .td-copy { text-align: right; white-space: nowrap; }
.base-indicator {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .65rem; font-family: var(--font-mono); font-weight: 700;
  padding: .15rem .45rem; border-radius: 4px;
}
.bi-bin  { background: rgba(37,99,235,.1);  color: var(--accent); }
.bi-oct  { background: rgba(14,165,233,.1); color: var(--accent2); }
.bi-dec  { background: rgba(16,185,129,.1); color: var(--accent5); }
.bi-hex  { background: rgba(245,158,11,.1); color: var(--accent4); }
.bi-cust { background: rgba(124,58,237,.1); color: var(--accent3); }

/* ── Binary bits display ── */
.bits-display {
  display: flex; flex-wrap: wrap; gap: .2rem;
  padding: .85rem 1rem;
  background: #0f172a; border-radius: var(--radius-sm);
  border: 1px solid #1e293b; font-family: var(--font-mono);
  font-size: .9rem; line-height: 1;
}
.bit-1 { color: #fcd34d; font-weight: 800; }
.bit-0 { color: #475569; }
.bit-sep { color: #334155; width: .4rem; }
.nibble-group { display: flex; gap: .1rem; }

/* ── Info grid ── */
.info-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: .6rem; margin-top: 1rem;
}
.info-card {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: .65rem .85rem;
  display: flex; flex-direction: column; gap: .2rem;
}
.info-card .ic-val {
  font-family: var(--font-mono); font-size: 1.1rem; font-weight: 800;
  color: var(--accent); line-height: 1;
}
.info-card .ic-lbl { font-size: .7rem; color: var(--muted); font-family: var(--font-mono); }

/* ── Input validation ── */
.num-input-wrap { position: relative; }
.num-input-wrap input { padding-right: 5rem !important; }
.valid-badge {
  position: absolute; right: .6rem; top: 50%;
  transform: translateY(-50%);
  font-family: var(--font-mono); font-size: .68rem; font-weight: 700;
  padding: .15rem .45rem; border-radius: 4px; pointer-events: none;
}
.valid-badge.ok  { background: rgba(16,185,129,.15); color: #15803d; }
.valid-badge.err { background: rgba(239,68,68,.15);  color: #dc2626; }

/* ── Bulk table ── */
.bulk-table-wrap { max-height: 360px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-sm); }
.bulk-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.bulk-table th {
  position: sticky; top: 0; background: var(--surface);
  border-bottom: 1px solid var(--border); padding: .5rem .85rem;
  text-align: left; font-family: var(--font-mono); font-size: .67rem;
  letter-spacing: .06em; text-transform: uppercase; color: var(--muted); font-weight: 700;
}
.bulk-table td { padding: .42rem .85rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.bulk-table tr:last-child td { border-bottom: none; }
.bulk-table tr:hover td { background: rgba(37,99,235,.04); }
.bulk-table .td-result { font-family: var(--font-mono); font-size: .8rem; font-weight: 700; color: var(--accent); word-break: break-all; }
.bulk-table .td-input  { font-family: var(--font-mono); font-size: .75rem; color: var(--muted); }
.bulk-copy-btn {
  padding: .18rem .45rem; font-size: .63rem; font-family: var(--font-mono); font-weight: 700;
  background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
  color: var(--muted); cursor: pointer; transition: all var(--transition);
}
.bulk-copy-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── Copy button small ── */
.copy-sm {
  padding: .2rem .55rem; font-size: .65rem; font-family: var(--font-mono); font-weight: 700;
  background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
  color: var(--muted); cursor: pointer; transition: all var(--transition);
}
.copy-sm:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
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
        <span aria-hidden="true">🔢</span> Base <span>Converter</span>
      </div>
      <p class="page-lead">
        Konversi angka antar basis bilangan secara instan — biner, oktal, desimal, heksadesimal,
        dan basis kustom 2–36. Tampilan bit, info ukuran, dan mode massal.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php foreach ([
          'single' => '🔄 Konversi',
          'all'    => '📊 Semua Basis',
          'custom' => '⚙ Basis Kustom',
          'bulk'   => '📋 Massal',
        ] as $v => $l): ?>
          <button type="button" role="tab"
            class="mode-tab <?= $post_mode === $v ? 'active' : '' ?>"
            onclick="switchMode('<?= $v ?>')">
            <?= $l ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="conv-form" novalidate>
        <input type="hidden" id="mode-input"     name="mode"      value="<?= e($post_mode) ?>" />
        <input type="hidden" id="from-base-input" name="from_base" value="<?= $post_from_base ?>" />
        <input type="hidden" id="to-base-input"   name="to_base"   value="<?= $post_to_base ?>" />

        <!-- ══ PANEL: Konversi tunggal ══ -->
        <div id="panel-single" class="mode-panel" <?= $post_mode !== 'single' ? 'style="display:none;"' : '' ?>>

          <!-- Conv hero display -->
          <div class="conv-hero" id="conv-hero">
            <div class="conv-box from">
              <div class="conv-box-label" id="from-label"><?= e(basisName($post_from_base)) ?> (<?= $post_from_base ?>)</div>
              <div class="conv-value <?= $post_input ? '' : 'placeholder' ?>" id="from-val">
                <?= $post_input ? e(strtoupper($post_input)) : 'Input di bawah...' ?>
              </div>
              <div class="conv-base-tag" id="from-tag">basis <?= $post_from_base ?></div>
            </div>
            <div class="conv-arrow" id="conv-arrow">→</div>
            <div class="conv-box to">
              <div class="conv-box-label" id="to-label"><?= e(basisName($post_to_base)) ?> (<?= $post_to_base ?>)</div>
              <div class="conv-value <?= !empty($server_result['result']) ? '' : 'placeholder' ?>" id="to-val">
                <?= !empty($server_result['result']) ? e($server_result['result']) : '—' ?>
              </div>
              <div class="conv-base-tag" id="to-tag">basis <?= $post_to_base ?></div>
            </div>
          </div>

          <!-- From base selector -->
          <div class="form-group">
            <label>Dari basis</label>
            <div class="base-pills" id="from-pills">
              <?php foreach ([2=>'BIN',8=>'OCT',10=>'DEC',16=>'HEX'] as $b=>$lbl): ?>
                <span class="base-pill <?= $post_from_base === $b ? 'active' : '' ?>"
                  data-base="<?= $b ?>" onclick="setFromBase(<?= $b ?>)">
                  <?= $lbl ?><span class="sub"> <?= $b ?></span>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- To base selector -->
          <div class="form-group">
            <label>Ke basis</label>
            <div class="base-pills" id="to-pills">
              <?php foreach ([2=>'BIN',8=>'OCT',10=>'DEC',16=>'HEX'] as $b=>$lbl): ?>
                <span class="base-pill <?= $post_to_base === $b ? 'active' : '' ?>"
                  data-base="<?= $b ?>" onclick="setToBase(<?= $b ?>)">
                  <?= $lbl ?><span class="sub"> <?= $b ?></span>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Input number -->
          <div class="form-group">
            <label for="input-num" id="input-num-label">
              Masukkan angka <span class="text-muted text-sm">(basis <?= $post_from_base ?>)</span>
            </label>
            <div class="num-input-wrap">
              <input type="text" id="input-num" name="input_num"
                placeholder="Contoh: <?= $post_from_base === 16 ? 'FF' : ($post_from_base === 2 ? '1010' : '255') ?>"
                value="<?= e($post_input) ?>"
                oninput="convertJS()"
                style="font-family:var(--font-mono); font-size:1rem; text-transform:uppercase; letter-spacing:.04em;"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
              <span class="valid-badge" id="valid-badge"></span>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Konversi via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="swapBases()">⇄ Tukar Basis</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearSingle()">Bersihkan</button>
          </div>

          <!-- Binary bits display -->
          <div id="bits-section" style="display:none; margin-top:1.25rem;">
            <div class="section-mini-title" style="margin-bottom:.5rem;">⚡ Representasi bit</div>
            <div class="bits-display" id="bits-display"></div>
            <div style="font-family:var(--font-mono); font-size:.7rem; color:var(--muted); margin-top:.4rem;" id="bits-info"></div>
          </div>
        </div>

        <!-- ══ PANEL: Semua basis ══ -->
        <div id="panel-all" class="mode-panel" <?= $post_mode !== 'all' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>Dari basis</label>
            <div class="base-pills" id="all-from-pills">
              <?php foreach ([2=>'BIN',8=>'OCT',10=>'DEC',16=>'HEX'] as $b=>$lbl): ?>
                <span class="base-pill <?= $post_from_base === $b ? 'active' : '' ?>"
                  data-base="<?= $b ?>" onclick="setAllFrom(<?= $b ?>)">
                  <?= $lbl ?><span class="sub"> <?= $b ?></span>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label for="all-input">Masukkan angka</label>
            <div class="num-input-wrap">
              <input type="text" id="all-input" name="input_num"
                placeholder="Contoh: 255"
                value="<?= ($post_mode === 'all') ? e($post_input) : '' ?>"
                oninput="convertAllJS()"
                style="font-family:var(--font-mono); font-size:1rem; text-transform:uppercase; letter-spacing:.04em;"
                autocomplete="off" />
              <span class="valid-badge" id="all-valid-badge"></span>
            </div>
          </div>

          <!-- Result table (realtime JS + server) -->
          <div id="all-result-table">
            <?php if ($post_mode === 'all' && !empty($server_result['all'])): ?>
            <div class="panel" style="padding:.85rem; margin-top:.75rem;">
              <table class="bases-table">
                <thead><tr><th>Basis</th><th>Nilai</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($server_result['all'] as $base => $data): ?>
                  <tr>
                    <td class="td-base">
                      <span class="base-indicator bi-<?= $base === 2 ? 'bin' : ($base === 8 ? 'oct' : ($base === 10 ? 'dec' : 'hex')) ?>">
                        <?= $data['label'] ?> (<?= $base ?>)
                      </span>
                    </td>
                    <td class="td-value"><?= e($data['value']) ?></td>
                    <td class="td-copy">
                      <button class="copy-sm"
                        onclick="copyText(<?= htmlspecialchars(json_encode($data['value']), ENT_QUOTES) ?>, this)">
                        SALIN
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($post_mode === 'all' && !empty($server_result['info'])): ?>
          <div class="info-grid">
            <?php $info = $server_result['info'];
            $cards = [
              ['Bit digunakan', $info['bits'] . ' bit'],
              ['Ukuran byte', $info['bytes'] . ' byte'],
              ['Signed 8-bit',  $info['signed8']  ? '✓ Ya' : '✕ Tidak'],
              ['Signed 16-bit', $info['signed16'] ? '✓ Ya' : '✕ Tidak'],
              ['Signed 32-bit', $info['signed32'] ? '✓ Ya' : '✕ Tidak'],
            ];
            foreach ($cards as [$lbl, $val]): ?>
              <div class="info-card">
                <span class="ic-val"><?= e($val) ?></span>
                <span class="ic-lbl"><?= e($lbl) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div style="margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Konversi via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: Basis kustom ══ -->
        <div id="panel-custom" class="mode-panel" <?= $post_mode !== 'custom' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Konversi ke atau dari basis kustom 2–36. Digit yang digunakan: 0-9 A-Z.</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="custom-from">Dari basis (2-36)</label>
              <input type="number" id="custom-from" name="from_base"
                min="2" max="36" value="<?= ($post_mode === 'custom') ? $post_from_base : 10 ?>"
                oninput="convertCustomJS()" />
            </div>
            <div class="form-group">
              <label for="custom-to">Ke basis (2-36)</label>
              <input type="number" id="custom-to" name="custom_to"
                min="2" max="36" value="<?= $post_custom_to ?>"
                oninput="convertCustomJS()" />
            </div>
          </div>

          <div class="form-group">
            <label for="custom-input">Angka input</label>
            <div class="num-input-wrap">
              <input type="text" id="custom-input" name="input_num"
                placeholder="Masukkan angka..."
                value="<?= ($post_mode === 'custom') ? e($post_input) : '' ?>"
                oninput="convertCustomJS()"
                style="font-family:var(--font-mono); text-transform:uppercase; letter-spacing:.04em;"
                autocomplete="off" />
              <span class="valid-badge" id="custom-valid-badge"></span>
            </div>
          </div>

          <div class="form-group">
            <label>Hasil konversi</label>
            <div class="copy-wrap">
              <div class="result-box" id="custom-result"
                style="font-family:var(--font-mono); font-size:1.1rem; font-weight:700;">
                —
              </div>
              <button class="copy-btn" type="button"
                onclick="copyText(document.getElementById('custom-result').textContent, this)">SALIN</button>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Konversi via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearCustom()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: Massal ══ -->
        <div id="panel-bulk" class="mode-panel" <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>
          <div class="form-row">
            <div class="form-group">
              <label for="bulk-from">Dari basis</label>
              <select id="bulk-from" name="bulk_from">
                <?php foreach ([2=>'Binary (2)',8=>'Octal (8)',10=>'Decimal (10)',16=>'Hex (16)'] as $b=>$l): ?>
                  <option value="<?= $b ?>" <?= $post_bulk_from === $b ? 'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="bulk-to">Ke basis</label>
              <select id="bulk-to" name="bulk_to">
                <?php foreach ([2=>'Binary (2)',8=>'Octal (8)',10=>'Decimal (10)',16=>'Hex (16)'] as $b=>$l): ?>
                  <option value="<?= $b ?>" <?= $post_bulk_to === $b ? 'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="bulk-textarea">
              Angka massal <span class="text-muted text-sm">(satu per baris, maks. 200)</span>
            </label>
            <textarea id="bulk-textarea" name="bulk_input"
              placeholder="255&#10;128&#10;64&#10;0&#10;255"
              style="min-height:140px; font-family:var(--font-mono); font-size:.88rem;"
            ><?= e($post_bulk_input) ?></textarea>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm">🔄 Konversi Massal (PHP)</button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="document.getElementById('bulk-textarea').value=''">Bersihkan</button>
          </div>
        </div>

      </form>
    </div><!-- /.panel -->

    <!-- Hasil server hasil bulk -->
    <?php if (!empty($bulk_results)): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Berhasil konversi <strong><?= count($bulk_results) ?> angka</strong>
        dari basis <?= $post_bulk_from ?> ke basis <?= $post_bulk_to ?>.</span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.85rem; flex-wrap:wrap; gap:.5rem;">
        <div class="panel-title" style="margin-bottom:0;">⚙ Hasil Konversi Massal</div>
        <div style="display:flex; gap:.5rem;">
          <button class="btn-ghost btn-sm" onclick="copyAllBulk()">📋 Salin semua hasil</button>
          <button class="btn-ghost btn-sm" onclick="downloadBulk()">⬇ Unduh .txt</button>
        </div>
      </div>
      <div class="bulk-table-wrap" id="bulk-result-table">
        <table class="bulk-table">
          <thead><tr>
            <th>#</th>
            <th>Input (basis <?= $post_bulk_from ?>)</th>
            <th>Hasil (basis <?= $post_bulk_to ?>)</th>
            <th></th>
          </tr></thead>
          <tbody>
            <?php foreach ($bulk_results as $idx => $row): ?>
            <tr>
              <td class="text-muted text-xs"><?= $idx + 1 ?></td>
              <td class="td-input"><?= e($row['input']) ?></td>
              <td class="td-result <?= !$row['ok'] ? 'text-muted' : '' ?>"><?= e($row['result']) ?></td>
              <td class="td-copy">
                <?php if ($row['ok']): ?>
                  <button class="bulk-copy-btn"
                    onclick="copyText(<?= htmlspecialchars(json_encode($row['result']), ENT_QUOTES) ?>, this)">
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
    <div class="panel">
      <div class="panel-title">⚡ Konversi Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.4rem; font-size:.8rem;">
        <?php
        $quickConv = [
          ['0',   10, 2,  'DEC→BIN'],
          ['255', 10, 2,  'DEC→BIN'],
          ['FF',  16, 10, 'HEX→DEC'],
          ['FF',  16, 2,  'HEX→BIN'],
          ['1010',2,  10, 'BIN→DEC'],
          ['377', 8,  10, 'OCT→DEC'],
          ['255', 10, 16, 'DEC→HEX'],
          ['255', 10, 8,  'DEC→OCT'],
        ];
        foreach ($quickConv as [$num, $from, $to, $lbl]): ?>
          <button class="btn-ghost btn-sm btn-full"
            style="text-align:left; justify-content:flex-start; gap:.5rem;"
            onclick="loadQuick('<?= $num ?>', <?= $from ?>, <?= $to ?>)">
            <span style="font-family:var(--font-mono); font-weight:700; color:var(--accent); min-width:32px;"><?= e($num) ?></span>
            <span class="text-muted"><?= e($lbl) ?></span>
            <span style="margin-left:auto; font-family:var(--font-mono); font-size:.72rem; color:var(--muted);">
              = <?= e(strtoupper(base_convert(strtolower($num), $from, $to))) ?>
            </span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📖 Tabel Referensi</div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:.72rem;">
          <thead>
            <tr>
              <th style="padding:.35rem .5rem; border-bottom:1px solid var(--border); text-align:right; color:var(--muted); font-family:var(--font-mono); font-size:.65rem;">DEC</th>
              <th style="padding:.35rem .5rem; border-bottom:1px solid var(--border); text-align:right; color:var(--accent); font-family:var(--font-mono); font-size:.65rem;">BIN</th>
              <th style="padding:.35rem .5rem; border-bottom:1px solid var(--border); text-align:right; color:var(--accent2); font-family:var(--font-mono); font-size:.65rem;">OCT</th>
              <th style="padding:.35rem .5rem; border-bottom:1px solid var(--border); text-align:right; color:var(--accent4); font-family:var(--font-mono); font-size:.65rem;">HEX</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($n = 0; $n <= 15; $n++): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:.28rem .5rem; text-align:right; font-family:var(--font-mono);"><?= $n ?></td>
                <td style="padding:.28rem .5rem; text-align:right; font-family:var(--font-mono); color:var(--accent);"><?= str_pad(decbin($n), 4, '0', STR_PAD_LEFT) ?></td>
                <td style="padding:.28rem .5rem; text-align:right; font-family:var(--font-mono); color:var(--accent2);"><?= decoct($n) ?></td>
                <td style="padding:.28rem .5rem; text-align:right; font-family:var(--font-mono); color:var(--accent4);"><?= strtoupper(dechex($n)) ?></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">💡 Basis Umum</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><strong>Basis 2 (Binary)</strong> — CPU, bit, boolean</li>
        <li><strong>Basis 8 (Octal)</strong> — Permission Unix (<code>chmod 755</code>)</li>
        <li><strong>Basis 10 (Decimal)</strong> — Angka sehari-hari</li>
        <li><strong>Basis 16 (Hex)</strong> — Warna CSS, alamat memory</li>
        <li><strong>Basis 36</strong> — URL shortener, ID pendek</li>
        <li><strong>Basis 64</strong> — Lihat tool Base64 Encode</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/base64"           class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
        <a href="/tools/uuid-generator"   class="btn-ghost btn-sm btn-full">UUID Generator</a>
        <a href="/tools/color-converter"  class="btn-ghost btn-sm btn-full">Color Converter HEX/RGB</a>
        <a href="/tools/json-formatter"   class="btn-ghost btn-sm btn-full">JSON Formatter</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Base Converter — logika JS (realtime)
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

let currentMode  = '<?= $post_mode ?>';
let fromBase     = <?= $post_from_base ?>;
let toBase       = <?= $post_to_base ?>;
let allFromBase  = <?= $post_from_base ?>;
let customFromBase = <?= ($post_mode === 'custom') ? $post_from_base : 10 ?>;
let customToBase   = <?= $post_custom_to ?>;

const DIGITS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

// ── Base conversion JS ────────────────────────────────────────
function isValidForBase(str, base) {
  if (!str) return false;
  const valid = DIGITS.slice(0, base);
  return [...str.toUpperCase()].every(c => valid.includes(c));
}

function convertBaseJS(num, fromB, toB) {
  if (!num || !isValidForBase(num, fromB)) return null;
  try {
    const dec = parseInt(num, fromB);
    if (isNaN(dec)) return null;
    return dec.toString(toB).toUpperCase();
  } catch { return null; }
}

// ── Mode switching ────────────────────────────────────────────
function switchMode(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;
  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    t.classList.toggle('active', ['single','all','custom','bulk'][i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

// ── Single mode ───────────────────────────────────────────────
function setFromBase(b) {
  fromBase = b;
  document.getElementById('from-base-input').value = b;
  document.querySelectorAll('#from-pills .base-pill').forEach(p =>
    p.classList.toggle('active', parseInt(p.dataset.base) === b));
  document.getElementById('from-label').textContent = basisName(b) + ' (' + b + ')';
  document.getElementById('from-tag').textContent   = 'basis ' + b;
  document.getElementById('input-num-label').innerHTML =
    `Masukkan angka <span class="text-muted text-sm">(basis ${b})</span>`;
  convertJS();
}

function setToBase(b) {
  toBase = b;
  document.getElementById('to-base-input').value = b;
  document.querySelectorAll('#to-pills .base-pill').forEach(p =>
    p.classList.toggle('active', parseInt(p.dataset.base) === b));
  document.getElementById('to-label').textContent = basisName(b) + ' (' + b + ')';
  document.getElementById('to-tag').textContent   = 'basis ' + b;
  convertJS();
}

function convertJS() {
  const input    = document.getElementById('input-num').value.trim();
  const fromVal  = document.getElementById('from-val');
  const toVal    = document.getElementById('to-val');
  const badge    = document.getElementById('valid-badge');
  const bitsSect = document.getElementById('bits-section');

  if (!input) {
    fromVal.className = 'conv-value placeholder';
    fromVal.textContent = 'Input di bawah...';
    toVal.className   = 'conv-value placeholder';
    toVal.textContent = '—';
    badge.className   = 'valid-badge';
    badge.textContent = '';
    bitsSect.style.display = 'none';
    return;
  }

  fromVal.className   = 'conv-value';
  fromVal.textContent = input.toUpperCase();

  if (!isValidForBase(input, fromBase)) {
    badge.className   = 'valid-badge err';
    badge.textContent = '✕ Invalid';
    toVal.className   = 'conv-value placeholder';
    toVal.textContent = 'Input tidak valid';
    bitsSect.style.display = 'none';
    return;
  }

  badge.className   = 'valid-badge ok';
  badge.textContent = '✓ Valid';

  const result = convertBaseJS(input, fromBase, toBase);
  if (result === null) {
    toVal.className   = 'conv-value placeholder';
    toVal.textContent = 'Error';
    return;
  }

  toVal.className   = 'conv-value';
  toVal.textContent = result;

  // Bits display
  const dec = parseInt(input, fromBase);
  if (!isNaN(dec) && dec >= 0) {
    const bin = dec.toString(2);
    renderBits(bin, dec);
    bitsSect.style.display = '';
  } else {
    bitsSect.style.display = 'none';
  }
}

function renderBits(bin, dec) {
  const padded = bin.padStart(Math.ceil(bin.length / 4) * 4, '0');
  const nibbles = padded.match(/.{1,4}/g) || [];
  const html = nibbles.map(nibble =>
    `<span class="nibble-group">${[...nibble].map(b =>
      `<span class="bit-${b}">${b}</span>`
    ).join('')}</span>`
  ).join('<span class="bit-sep"></span>');
  document.getElementById('bits-display').innerHTML = html;
  document.getElementById('bits-info').textContent =
    `${bin.length} bit · ${Math.ceil(bin.length / 8)} byte · Desimal: ${dec}`;
}

function swapBases() {
  const inp = document.getElementById('input-num');
  const toVal = document.getElementById('to-val');
  if (toVal.textContent && toVal.textContent !== '—' && !toVal.classList.contains('placeholder')) {
    inp.value = toVal.textContent;
  }
  const temp = fromBase;
  setFromBase(toBase);
  setToBase(temp);
}

function clearSingle() {
  document.getElementById('input-num').value = '';
  convertJS();
}

// ── All bases mode ────────────────────────────────────────────
function setAllFrom(b) {
  allFromBase = b;
  document.getElementById('from-base-input').value = b;
  document.querySelectorAll('#all-from-pills .base-pill').forEach(p =>
    p.classList.toggle('active', parseInt(p.dataset.base) === b));
  convertAllJS();
}

function convertAllJS() {
  const input   = document.getElementById('all-input')?.value.trim();
  const badge   = document.getElementById('all-valid-badge');
  const tableEl = document.getElementById('all-result-table');
  if (!tableEl) return;

  if (!input) { tableEl.innerHTML = ''; badge.className = 'valid-badge'; badge.textContent = ''; return; }

  if (!isValidForBase(input, allFromBase)) {
    badge.className   = 'valid-badge err'; badge.textContent = '✕ Invalid';
    tableEl.innerHTML = ''; return;
  }
  badge.className = 'valid-badge ok'; badge.textContent = '✓ Valid';

  const dec = parseInt(input, allFromBase);
  const all = [
    { base: 2,  label: 'Binary',      cls: 'bi-bin', val: dec.toString(2).toUpperCase() },
    { base: 8,  label: 'Octal',       cls: 'bi-oct', val: dec.toString(8).toUpperCase() },
    { base: 10, label: 'Decimal',     cls: 'bi-dec', val: dec.toString(10) },
    { base: 16, label: 'Hexadecimal', cls: 'bi-hex', val: dec.toString(16).toUpperCase() },
  ];

  tableEl.innerHTML = `<div class="panel" style="padding:.85rem; margin-top:.75rem;">
    <table class="bases-table"><thead><tr><th>Basis</th><th>Nilai</th><th></th></tr></thead><tbody>
    ${all.map(r => `<tr>
      <td class="td-base"><span class="base-indicator ${r.cls}">${r.label} (${r.base})</span></td>
      <td class="td-value">${esc(r.val)}</td>
      <td class="td-copy"><button class="copy-sm" onclick="copyText(${JSON.stringify(r.val)}, this)">SALIN</button></td>
    </tr>`).join('')}
    </tbody></table></div>`;

  // Info section
  if (allFromBase === 10 || !isNaN(dec)) {
    const bits = dec.toString(2).length;
    tableEl.innerHTML += `<div class="info-grid">
      ${[
        ['Bit digunakan', bits + ' bit'],
        ['Ukuran byte', Math.ceil(bits / 8) + ' byte'],
        ['Signed 8-bit',  (dec >= -128 && dec <= 127) ? '✓ Ya' : '✕ Tidak'],
        ['Signed 16-bit', (dec >= -32768 && dec <= 32767) ? '✓ Ya' : '✕ Tidak'],
        ['Signed 32-bit', (dec >= -2147483648 && dec <= 2147483647) ? '✓ Ya' : '✕ Tidak'],
      ].map(([l, v]) => `<div class="info-card"><span class="ic-val">${esc(v)}</span><span class="ic-lbl">${esc(l)}</span></div>`).join('')}
    </div>`;
  }
}

function clearAll() {
  const el = document.getElementById('all-input');
  if (el) el.value = '';
  document.getElementById('all-result-table').innerHTML = '';
}

// ── Custom base mode ──────────────────────────────────────────
function convertCustomJS() {
  const fromEl  = document.getElementById('custom-from');
  const toEl    = document.getElementById('custom-to');
  const input   = document.getElementById('custom-input')?.value.trim();
  const resEl   = document.getElementById('custom-result');
  const badge   = document.getElementById('custom-valid-badge');
  if (!fromEl || !toEl || !resEl) return;

  customFromBase = Math.max(2, Math.min(36, parseInt(fromEl.value) || 10));
  customToBase   = Math.max(2, Math.min(36, parseInt(toEl.value)   || 16));

  if (!input) { resEl.textContent = '—'; badge.className = 'valid-badge'; badge.textContent = ''; return; }

  if (!isValidForBase(input, customFromBase)) {
    badge.className   = 'valid-badge err'; badge.textContent = '✕ Invalid';
    resEl.textContent = 'Input tidak valid untuk basis ' + customFromBase;
    return;
  }
  badge.className = 'valid-badge ok'; badge.textContent = '✓ Valid';
  const result = convertBaseJS(input, customFromBase, customToBase);
  resEl.textContent = result || 'Error';
}

function clearCustom() {
  const el = document.getElementById('custom-input');
  if (el) el.value = '';
  const r = document.getElementById('custom-result');
  if (r) r.textContent = '—';
  const b = document.getElementById('custom-valid-badge');
  if (b) { b.className = 'valid-badge'; b.textContent = ''; }
}

// ── Quick load ────────────────────────────────────────────────
function loadQuick(num, from, to) {
  switchMode('single');
  setFromBase(from);
  setToBase(to);
  document.getElementById('input-num').value = num;
  convertJS();
}

// ── Bulk download & copy ──────────────────────────────────────
function copyAllBulk() {
  const rows = document.querySelectorAll('#bulk-result-table .td-result');
  const all  = Array.from(rows).map(td => td.textContent.trim()).filter(Boolean).join('\n');
  if (all) navigator.clipboard.writeText(all).then(() => showToast && showToast('Disalin!', 'success'));
}

function downloadBulk() {
  const rows = document.querySelectorAll('#bulk-result-table tbody tr');
  const lines = Array.from(rows).map((tr, i) => {
    const tds = tr.querySelectorAll('td');
    return `${i+1}. ${tds[1]?.textContent.trim()} → ${tds[2]?.textContent.trim()}`;
  });
  if (!lines.length) return;
  const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'base-conversion.txt';
  a.click();
}

// ── Utilities ─────────────────────────────────────────────────
function basisName(b) {
  return {2:'Binary',8:'Octal',10:'Decimal',16:'Hexadecimal'}[b] || 'Base-'+b;
}

function copyText(text, btn) {
  if (!text || text === '—') return;
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) return;
    const orig = btn.textContent;
    btn.textContent = '✓';
    btn.style.cssText = 'background:var(--accent5);border-color:var(--accent5);color:#fff;';
    setTimeout(() => { btn.textContent = orig; btn.style.cssText = ''; }, 1500);
  });
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Init ──────────────────────────────────────────────────────
switchMode(currentMode);
<?php if ($post_input && $post_mode === 'single'): ?>
convertJS();
<?php elseif ($post_input && $post_mode === 'all'): ?>
convertAllJS();
<?php elseif ($post_input && $post_mode === 'custom'): ?>
convertCustomJS();
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>