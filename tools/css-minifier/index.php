<?php
require '../../includes/config.php';
/**
 * Multi Tools — CSS Minifier
 * Minify CSS dengan berbagai opsi: hapus komentar, whitespace,
 * satuan nol, warna hex, properti shorthand, dan lainnya.
 * Mendukung input teks, upload file, dan fetch URL.
 * ============================================================ */

// ── CSS Minifier Engine ───────────────────────────────────────

function minifyCSS(string $css, array $opts = []): string {
  $removeComments    = $opts['remove_comments']     ?? true;
  $removeWhitespace  = $opts['remove_whitespace']   ?? true;
  $removeZeroUnits   = $opts['remove_zero_units']   ?? true;
  $shortenColors     = $opts['shorten_colors']       ?? true;
  $removeSemicolon   = $opts['remove_last_semicolon']?? true;
  $shortenZeroDecimal= $opts['shorten_zero_decimal'] ?? true;
  $lowercaseProps    = $opts['lowercase_properties'] ?? false;
  $sortProps         = $opts['sort_properties']      ?? false;

  // 1. Preserve string literals & data URIs from modification
  $preserved = [];
  $ph        = 'CSSPH';
  $counter   = 0;
  // Preserve content: "..." and '...'
  $css = preg_replace_callback('/(content\s*:\s*)(\'[^\']*\'|"[^"]*")/', function($m) use (&$preserved, &$counter, $ph) {
    $key = $ph . ($counter++) . $ph;
    $preserved[$key] = $m[2];
    return $m[1] . $key;
  }, $css);
  // Preserve data URIs
  $css = preg_replace_callback('/url\s*\(\s*(\'[^\']*\'|"[^"]*"|[^)]+)\s*\)/', function($m) use (&$preserved, &$counter, $ph) {
    $key = $ph . ($counter++) . $ph;
    $preserved[$key] = 'url(' . trim($m[1]) . ')';
    return $key;
  }, $css);

  // 2. Remove comments
  if ($removeComments) {
    // Keep /*! important comments (licenses)
    $css = preg_replace('/\/\*(?!!)(.*?)\*\//s', '', $css);
  }

  // 3. Remove whitespace
  if ($removeWhitespace) {
    $css = preg_replace('/\s*([{};:,>~+|])\s*/', '$1', $css);
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = preg_replace('/[\r\n\t]/', '', $css);
    $css = preg_replace('/\s*!\s*important/i', '!important', $css);
    $css = preg_replace('/;\s*}/', '}', $css); // remove last semicolon in block
  }

  // 4. Remove units on zero values: 0px → 0, 0em → 0
  if ($removeZeroUnits) {
    $css = preg_replace('/\b0(px|em|rem|%|vh|vw|vmin|vmax|pt|pc|cm|mm|in|ex|ch)\b/', '0', $css);
    $css = preg_replace('/\b0\.0+\b/', '0', $css);
  }

  // 5. Shorten zero decimals: 0.5 → .5
  if ($shortenZeroDecimal) {
    $css = preg_replace('/\b0\.(\d)/', '.$1', $css);
  }

  // 6. Shorten hex colors: #aabbcc → #abc, #AABBCC → #abc
  if ($shortenColors) {
    $css = preg_replace_callback('/#([0-9a-fA-F]{6})\b/', function($m) {
      $hex = strtolower($m[1]);
      // Check if pairs are identical: aabbcc → abc
      if ($hex[0] === $hex[1] && $hex[2] === $hex[3] && $hex[4] === $hex[5]) {
        return '#' . $hex[0] . $hex[2] . $hex[4];
      }
      return '#' . $hex;
    }, $css);
    // Lowercase remaining hex
    $css = preg_replace_callback('/#([0-9a-fA-F]{3,8})\b/', fn($m) => '#' . strtolower($m[1]), $css);
  }

  // 7. Remove last semicolon before closing brace
  if ($removeSemicolon) {
    $css = preg_replace('/;}/', '}', $css);
  }

  // 8. Lowercase property names (optional)
  if ($lowercaseProps) {
    $css = preg_replace_callback('/([a-zA-Z\-]+)\s*:/', fn($m) => strtolower($m[1]) . ':', $css);
  }

  // 9. Sort properties within each rule (optional, basic)
  if ($sortProps) {
    $css = preg_replace_callback('/\{([^{}]+)\}/', function($m) {
      $props = array_filter(array_map('trim', explode(';', $m[1])));
      sort($props);
      return '{' . implode(';', $props) . '}';
    }, $css);
  }

  // 10. Final cleanup
  $css = preg_replace('/\s*{\s*}/', '{}', $css); // empty rules
  $css = preg_replace('/\s+/', ' ', $css);

  // 11. Restore preserved blocks
  foreach ($preserved as $key => $val) {
    $css = str_replace($key, $val, $css);
  }

  return trim($css);
}

// ── Handle POST ──────────────────────────────────────────────
$server_result = '';
$server_error  = '';
$server_stats  = [];
$post_input    = '';
$post_mode     = 'text';
$post_opts = [
  'remove_comments'      => true,
  'remove_whitespace'    => true,
  'remove_zero_units'    => true,
  'shorten_colors'       => true,
  'remove_last_semicolon'=> true,
  'shorten_zero_decimal' => true,
  'lowercase_properties' => false,
  'sort_properties'      => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode = in_array($_POST['mode'] ?? 'text', ['text','file','url']) ? $_POST['mode'] : 'text';
  $post_opts = [
    'remove_comments'       => isset($_POST['remove_comments']),
    'remove_whitespace'     => isset($_POST['remove_whitespace']),
    'remove_zero_units'     => isset($_POST['remove_zero_units']),
    'shorten_colors'        => isset($_POST['shorten_colors']),
    'remove_last_semicolon' => isset($_POST['remove_last_semicolon']),
    'shorten_zero_decimal'  => isset($_POST['shorten_zero_decimal']),
    'lowercase_properties'  => isset($_POST['lowercase_properties']),
    'sort_properties'       => isset($_POST['sort_properties']),
  ];

  switch ($post_mode) {
    case 'text':
      $post_input = $_POST['input_css'] ?? '';
      if (trim($post_input) === '') { $server_error = 'Input CSS tidak boleh kosong.'; break; }
      $server_result = minifyCSS($post_input, $post_opts);
      break;

    case 'file':
      if (empty($_FILES['upload_file']['tmp_name'])) { $server_error = 'Pilih file CSS.'; break; }
      if ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) { $server_error = 'Gagal upload file.'; break; }
      $ext = strtolower(pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['css','scss','less','sass'])) { $server_error = 'Hanya file .css yang didukung.'; break; }
      if ($_FILES['upload_file']['size'] > 2 * 1024 * 1024) { $server_error = 'Ukuran file maks. 2 MB.'; break; }
      $post_input    = file_get_contents($_FILES['upload_file']['tmp_name']);
      $server_result = minifyCSS($post_input, $post_opts);
      break;

    case 'url':
      $url = filter_var(trim($_POST['fetch_url'] ?? ''), FILTER_VALIDATE_URL);
      if (!$url) { $server_error = 'URL tidak valid.'; break; }
      $ctx     = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);
      $fetched = @file_get_contents($url, false, $ctx);
      if ($fetched === false) { $server_error = 'Gagal mengambil konten dari URL.'; break; }
      $post_input    = $fetched;
      $server_result = minifyCSS($fetched, $post_opts);
      break;
  }

  if ($server_result && !$server_error) {
    $origLen  = strlen($post_input);
    $miniLen  = strlen($server_result);
    $saved    = $origLen - $miniLen;
    $server_stats = [
      'orig_bytes'  => $origLen,
      'mini_bytes'  => $miniLen,
      'saved_bytes' => $saved,
      'saved_pct'   => $origLen > 0 ? round(($saved / $origLen) * 100, 1) : 0,
      'orig_lines'  => substr_count($post_input, "\n") + 1,
      'mini_lines'  => substr_count($server_result, "\n") + 1,
      'orig_rules'  => preg_match_all('/\{/', $post_input),
      'mini_rules'  => preg_match_all('/\{/', $server_result),
    ];
  }
}

function fmtBytes(int $b): string {
  if ($b < 1024) return $b . ' B';
  if ($b < 1048576) return round($b / 1024, 1) . ' KB';
  return round($b / 1048576, 2) . ' MB';
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'CSS Minifier Online — Kompres & Minify CSS | Multi Tools',
  'description' => 'Minify dan kompres CSS secara instan. Hapus komentar, whitespace, unit nol, persingkat warna hex, dan banyak lagi. Upload file atau fetch dari URL. Statistik kompresi lengkap.',
  'keywords'    => 'css minifier, minify css, kompres css, css compressor, css optimizer, remove whitespace css, shorten hex color, multi tools',
  'og_title'    => 'CSS Minifier Online — Kompres CSS Instan',
  'og_desc'     => 'Minify CSS: hapus komentar, whitespace, persingkat hex, unit nol. Upload file atau fetch URL.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'CSS Minifier'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/css-minifier#webpage',
      'url'         => SITE_URL . '/tools/css-minifier',
      'name'        => 'CSS Minifier Online',
      'description' => 'Minify dan kompres CSS secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools','item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'CSS Minifier',   'item' => SITE_URL . '/tools/css-minifier'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'CSS Minifier',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/css-minifier',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Mode tabs ── */
.mode-tabs {
  display: flex;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.mode-tab {
  flex: 1;
  padding: .55rem .4rem;
  background: var(--bg);
  border: none;
  border-right: 1px solid var(--border);
  font-family: var(--font-body);
  font-size: .85rem;
  font-weight: 600;
  color: var(--muted);
  cursor: pointer;
  transition: all var(--transition);
  text-align: center;
}
.mode-tab:last-child { border-right: none; }
.mode-tab:hover      { background: var(--surface); color: var(--text); }
.mode-tab.active     { background: var(--accent2); color: #fff; }

/* ── Option cards ── */
.opts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .5rem;
  margin-top: .4rem;
}
@media (max-width: 500px) { .opts-grid { grid-template-columns: 1fr; } }

.opt-card {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  padding: .65rem .75rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--transition);
  background: var(--bg);
  user-select: none;
  position: relative;
  overflow: hidden;
}
.opt-card::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(circle at top left, rgba(14,165,233,.08), transparent 60%);
  opacity: 0; transition: opacity .3s;
}
.opt-card:hover { border-color: var(--accent2); }
.opt-card:hover::before { opacity: 1; }
.opt-card.checked { border-color: var(--accent2); background: rgba(14,165,233,.05); }
.opt-card.checked::before { opacity: 1; }
.opt-card input[type="checkbox"] { width: auto !important; accent-color: var(--accent2); flex-shrink: 0; margin-top: .1rem; }
.opt-card-body { display: flex; flex-direction: column; gap: .12rem; }
.opt-card-label { font-size: .83rem; font-weight: 600; color: var(--text); line-height: 1.2; }
.opt-card-desc  { font-size: .71rem; color: var(--muted); line-height: 1.3; }
.opt-card-example {
  font-family: var(--font-mono);
  font-size: .67rem;
  color: var(--accent2);
  margin-top: .15rem;
  background: rgba(14,165,233,.07);
  border-radius: 3px;
  padding: 1px 5px;
  display: inline-block;
}
.opt-card-save {
  font-size: .65rem;
  font-family: var(--font-mono);
  font-weight: 700;
  color: var(--accent5);
  margin-top: .1rem;
}

/* ── Compression stats ── */
.compression-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: var(--border);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  margin-bottom: 1.25rem;
}
@media (max-width: 500px) { .compression-stats { grid-template-columns: repeat(2, 1fr); } }
.cs-item {
  padding: .85rem 1rem;
  background: var(--surface);
  display: flex;
  flex-direction: column;
  gap: .2rem;
}
.cs-val { font-family: var(--font-mono); font-size: 1.35rem; font-weight: 800; letter-spacing: -.03em; line-height: 1; }
.cs-lbl { font-size: .72rem; color: var(--muted); font-family: var(--font-mono); }
.cs-saved { color: var(--accent5); }
.cs-pct   { color: var(--accent2); }

/* ── Saving bar ── */
.saving-bar-wrap { margin-bottom: 1.25rem; }
.saving-bar {
  height: 8px; background: var(--border);
  border-radius: 99px; overflow: hidden; position: relative;
}
.saving-bar-orig {
  position: absolute; inset: 0;
  background: rgba(14,165,233,.15);
  border-radius: 99px;
}
.saving-bar-mini {
  height: 100%;
  background: linear-gradient(90deg, var(--accent5), var(--accent2));
  border-radius: 99px;
  transition: width .5s cubic-bezier(.4,0,.2,1);
}
.saving-bar-meta {
  display: flex; justify-content: space-between;
  font-family: var(--font-mono); font-size: .7rem; color: var(--muted); margin-top: .35rem;
}

/* ── Output code block ── */
.output-code {
  background: #0f172a;
  border: 1px solid #1e293b;
  border-radius: var(--radius-sm);
  padding: 1rem 1.1rem;
  font-family: var(--font-mono);
  font-size: .78rem;
  color: #94a3b8;
  word-break: break-all;
  white-space: pre-wrap;
  max-height: 300px;
  overflow-y: auto;
  line-height: 1.65;
}
/* Syntax highlight classes for CSS */
.css-selector { color: #7dd3fc; }
.css-prop     { color: #86efac; }
.css-value    { color: #fcd34d; }
.css-punc     { color: #64748b; }
.css-at       { color: #c084fc; }
.css-comment  { color: #475569; font-style: italic; }

/* ── File drop ── */
.file-drop {
  border: 2px dashed var(--border);
  border-radius: var(--radius-md);
  padding: 2rem; text-align: center;
  cursor: pointer; transition: all var(--transition);
  background: var(--bg); position: relative;
}
.file-drop:hover, .file-drop.drag-over {
  border-color: var(--accent2); background: rgba(14,165,233,.04);
}
.file-drop input[type="file"] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.file-drop-icon  { font-size: 2rem; margin-bottom: .5rem; opacity: .4; }
.file-drop-label { font-size: .9rem; color: var(--muted); margin-bottom: .25rem; }
.file-drop-hint  { font-size: .75rem; color: var(--muted); font-family: var(--font-mono); }
.file-name-disp  { margin-top: .75rem; font-family: var(--font-mono); font-size: .82rem; color: var(--accent2); font-weight: 600; display: none; }

/* ── Before/After toggle ── */
.diff-toggle-wrap {
  display: flex; gap: 0;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  overflow: hidden; margin-bottom: .75rem; width: fit-content;
}
.diff-toggle-btn {
  padding: .38rem 1rem; background: var(--bg);
  border: none; font-size: .78rem; font-weight: 600; color: var(--muted);
  cursor: pointer; transition: all var(--transition); font-family: var(--font-body);
}
.diff-toggle-btn:first-child { border-right: 1px solid var(--border); }
.diff-toggle-btn.active { background: var(--accent2); color: #fff; }

/* ── Inline stat ── */
.inline-stat {
  display: inline-flex; align-items: center; gap: .3rem;
  font-family: var(--font-mono); font-size: .7rem; font-weight: 700;
  padding: .2rem .6rem; border-radius: 99px;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
}
.inline-stat.sky   { color: var(--accent2); border-color: var(--accent2); background: rgba(14,165,233,.07); }
.inline-stat.green { color: var(--accent5); border-color: var(--accent5); background: rgba(16,185,129,.07); }

/* ── Selectors breakdown ── */
.breakdown-table { width: 100%; border-collapse: collapse; font-size: .77rem; }
.breakdown-table th {
  padding: .4rem .65rem; text-align: left; border-bottom: 1px solid var(--border);
  font-family: var(--font-mono); font-size: .67rem; letter-spacing: .06em;
  text-transform: uppercase; color: var(--muted); font-weight: 700;
}
.breakdown-table td { padding: .38rem .65rem; border-bottom: 1px solid var(--border); }
.breakdown-table tr:last-child td { border-bottom: none; }
.breakdown-table .mono { font-family: var(--font-mono); font-size: .7rem; color: var(--accent2); }
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
        <span aria-hidden="true">🎨</span> CSS <span>Minifier</span>
      </div>
      <p class="page-lead">
        Kompres CSS secara instan — hapus komentar, whitespace, persingkat warna hex,
        hilangkan unit pada nilai nol, dan banyak optimasi lainnya.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php foreach (['text' => '📝 Teks / Paste', 'file' => '📁 Upload File', 'url' => '🌐 Fetch URL'] as $val => $lbl): ?>
          <button type="button" role="tab"
            class="mode-tab <?= $post_mode === $val ? 'active' : '' ?>"
            onclick="switchTab('<?= $val ?>')">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="css-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" id="mode-input" name="mode" value="<?= e($post_mode) ?>" />

        <!-- ══ Opsi minifikasi (semua mode) ══ -->
        <div class="form-group">
          <label>Opsi minifikasi</label>
          <div class="opts-grid">
            <?php
            $options = [
              ['key' => 'remove_comments',       'label' => 'Hapus komentar',          'desc' => 'Hapus /* ... */ (kecuali /*! lisensi)',       'example' => '/* comment */ → (dihapus)',      'save' => '~5-15%'],
              ['key' => 'remove_whitespace',      'label' => 'Hapus whitespace',         'desc' => 'Hapus spasi, newline, tab tidak perlu',       'example' => 'color : red ; → color:red;',     'save' => '~15-35%'],
              ['key' => 'remove_zero_units',      'label' => 'Hapus unit pada nol',      'desc' => 'Nilai 0 tidak butuh satuan',                  'example' => '0px 0em → 0 0',                  'save' => '~1-3%'],
              ['key' => 'shorten_colors',         'label' => 'Persingkat warna hex',     'desc' => '#aabbcc dapat dipersingkat',                  'example' => '#ffffff → #fff',                 'save' => '~1-4%'],
              ['key' => 'shorten_zero_decimal',   'label' => 'Persingkat desimal nol',   'desc' => 'Hilangkan nol di depan desimal',              'example' => '0.5em → .5em',                   'save' => '~1-2%'],
              ['key' => 'remove_last_semicolon',  'label' => 'Hapus titik koma terakhir','desc' => 'Titik koma sebelum } tidak perlu',            'example' => '{color:red;} → {color:red}',     'save' => '~1-2%'],
              ['key' => 'lowercase_properties',   'label' => 'Lowercase properti',       'desc' => 'Konversi nama properti ke huruf kecil',       'example' => 'FONT-SIZE → font-size',          'save' => '~0-1%'],
              ['key' => 'sort_properties',        'label' => 'Urutkan properti',         'desc' => 'Sortir properti A-Z (meningkatkan kompresi)', 'example' => 'z-index;color → color;z-index',  'save' => '~0-2%'],
            ];
            foreach ($options as $opt):
              $checked = $post_opts[$opt['key']] ?? false;
            ?>
              <label class="opt-card <?= $checked ? 'checked' : '' ?>" id="lbl-<?= $opt['key'] ?>">
                <input type="checkbox" name="<?= $opt['key'] ?>" id="opt-<?= $opt['key'] ?>"
                  <?= $checked ? 'checked' : '' ?>
                  onchange="toggleOpt(this, 'lbl-<?= $opt['key'] ?>')" />
                <div class="opt-card-body">
                  <span class="opt-card-label"><?= e($opt['label']) ?></span>
                  <span class="opt-card-desc"><?= e($opt['desc']) ?></span>
                  <span class="opt-card-example"><?= e($opt['example']) ?></span>
                  <span class="opt-card-save"><?= $opt['save'] ?> saving</span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ══ PANEL: Teks ══ -->
        <div id="panel-text" class="mode-panel" <?= $post_mode !== 'text' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-css">CSS input</label>
            <textarea id="input-css" name="input_css"
              placeholder="Tempel CSS di sini..."
              oninput="updateInputStats()"
              style="min-height:220px; font-family:var(--font-mono); font-size:.82rem;"
            ><?= ($post_mode === 'text') ? e($post_input) : '' ?></textarea>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.3rem;">
              <div id="input-stats" style="font-family:var(--font-mono); font-size:.7rem; color:var(--muted);">
                0 karakter · 0 baris
              </div>
              <button type="button" class="btn-ghost btn-sm"
                onclick="loadSample()" style="padding:.3rem .7rem; font-size:.75rem;">
                📄 Contoh CSS
              </button>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent2); border-color:var(--accent2);">
              🎨 Minify CSS
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearText()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: File ══ -->
        <div id="panel-file" class="mode-panel" <?= $post_mode !== 'file' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>Upload file CSS</label>
            <div class="file-drop" id="file-drop-zone">
              <input type="file" name="upload_file" id="upload-file"
                accept=".css" onchange="handleFileSelect(this)" />
              <div class="file-drop-icon">🎨</div>
              <div class="file-drop-label">Klik atau seret file CSS ke sini</div>
              <div class="file-drop-hint">.css · Maks. 2 MB</div>
              <div class="file-name-disp" id="file-name-disp"></div>
            </div>
          </div>
          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent2); border-color:var(--accent2);">
              🎨 Minify File
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearFile()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: URL ══ -->
        <div id="panel-url" class="mode-panel" <?= $post_mode !== 'url' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1rem;">
            <span>ℹ</span>
            <span>Masukkan URL file CSS publik. Server akan mengambil kontennya dan meminify.</span>
          </div>
          <div class="form-group">
            <label for="fetch-url">URL file CSS</label>
            <input type="url" id="fetch-url" name="fetch_url"
              placeholder="https://example.com/style.css"
              value="<?= ($post_mode === 'url') ? e($_POST['fetch_url'] ?? '') : '' ?>"
              style="font-family:var(--font-mono);" />
          </div>
          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent2); border-color:var(--accent2);">
              🎨 Fetch &amp; Minify
            </button>
          </div>
        </div>

      </form>
    </div><!-- /.panel -->

    <!-- ── Hasil server ── -->
    <?php if ($server_result && !$server_error): ?>
    <div class="panel" style="margin-top:1rem;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
        <div class="panel-title" style="margin-bottom:0;">📊 Hasil Minifikasi</div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
          <span class="inline-stat green">✓ <?= $server_stats['saved_pct'] ?>% lebih kecil</span>
          <span class="inline-stat sky">Hemat <?= fmtBytes($server_stats['saved_bytes']) ?></span>
        </div>
      </div>

      <!-- Stats grid -->
      <div class="compression-stats">
        <div class="cs-item">
          <span class="cs-val"><?= fmtBytes($server_stats['orig_bytes']) ?></span>
          <span class="cs-lbl">Ukuran asli</span>
        </div>
        <div class="cs-item">
          <span class="cs-val cs-saved"><?= fmtBytes($server_stats['mini_bytes']) ?></span>
          <span class="cs-lbl">Setelah minify</span>
        </div>
        <div class="cs-item">
          <span class="cs-val cs-saved">-<?= fmtBytes($server_stats['saved_bytes']) ?></span>
          <span class="cs-lbl">Dihemat</span>
        </div>
        <div class="cs-item">
          <span class="cs-val cs-pct"><?= $server_stats['saved_pct'] ?>%</span>
          <span class="cs-lbl">Kompresi</span>
        </div>
      </div>

      <!-- Saving bar -->
      <div class="saving-bar-wrap">
        <div class="saving-bar">
          <div class="saving-bar-orig"></div>
          <div class="saving-bar-mini" style="width:<?= max(1, 100 - $server_stats['saved_pct']) ?>%"></div>
        </div>
        <div class="saving-bar-meta">
          <span>Asli: <?= number_format($server_stats['orig_bytes']) ?> B · <?= $server_stats['orig_lines'] ?> baris · <?= $server_stats['orig_rules'] ?> rule</span>
          <span>Minified: <?= number_format($server_stats['mini_bytes']) ?> B · <?= $server_stats['mini_lines'] ?> baris · <?= $server_stats['mini_rules'] ?> rule</span>
        </div>
      </div>

      <!-- Before/After toggle -->
      <div class="diff-toggle-wrap">
        <button type="button" class="diff-toggle-btn" id="btn-before" onclick="showView('before')">Sebelum</button>
        <button type="button" class="diff-toggle-btn active" id="btn-after" onclick="showView('after')">Sesudah</button>
      </div>

      <div id="view-before" style="display:none;">
        <div class="output-code" id="before-code"><?= e(substr($post_input, 0, 3000)) ?><?= strlen($post_input) > 3000 ? "\n\n/* ... dipotong untuk tampilan */" : '' ?></div>
      </div>

      <div id="view-after">
        <div class="copy-wrap">
          <div class="output-code" id="minified-out"><?= e($server_result) ?></div>
          <button class="copy-btn" data-copy-target="minified-out" style="top:.5rem;">SALIN</button>
        </div>
      </div>

      <!-- Action buttons -->
      <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
        <button class="btn-primary btn-sm"
          style="background:var(--accent2); border-color:var(--accent2);"
          onclick="downloadMinified()">
          ⬇ Unduh CSS Minified
        </button>
        <button class="btn-ghost btn-sm" onclick="copyMinified()">📋 Salin semua</button>
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
      <div class="panel-title">💡 Tips Minifikasi CSS</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Aktifkan semua opsi untuk <strong>kompresi maksimal</strong></li>
        <li>Komentar <code>/*! ... */</code> selalu dipertahankan (lisensi)</li>
        <li><code>content: "..."</code> dan <code>url()</code> tidak disentuh</li>
        <li>Gabungkan dengan <strong>gzip</strong> untuk 60-90% penghematan total</li>
        <li><strong>Urutkan properti</strong> meningkatkan kompresi gzip</li>
        <li>Gunakan bersama <strong>HTML Minifier</strong> untuk hasil optimal</li>
      </ul>
    </div>

    <?php if (!empty($server_stats)): ?>
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📈 Detail Kompresi</div>
      <table class="breakdown-table">
        <thead><tr><th>Metrik</th><th>Nilai</th></tr></thead>
        <tbody>
          <?php
          $rows = [
            ['Ukuran asli',     fmtBytes($server_stats['orig_bytes'])],
            ['Setelah minify',  fmtBytes($server_stats['mini_bytes'])],
            ['Dihemat',         fmtBytes($server_stats['saved_bytes'])],
            ['Kompresi',        $server_stats['saved_pct'] . '%'],
            ['Baris asli',      number_format($server_stats['orig_lines'])],
            ['Baris minified',  number_format($server_stats['mini_lines'])],
            ['Rule asli',       number_format($server_stats['orig_rules'])],
            ['Rule minified',   number_format($server_stats['mini_rules'])],
          ];
          foreach ($rows as [$k, $v]): ?>
            <tr>
              <td style="color:var(--muted);"><?= $k ?></td>
              <td class="mono"><?= $v ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📊 Estimasi Penghematan</div>
      <div style="display:flex; flex-direction:column; gap:.35rem; font-size:.78rem;">
        <?php
        $savings = [
          ['Hapus komentar',        '5–15%',  '#16a34a'],
          ['Hapus whitespace',      '15–35%', '#15803d'],
          ['Unit nol',              '1–3%',   '#0369a1'],
          ['Warna hex singkat',     '1–4%',   '#0ea5e9'],
          ['Desimal nol',           '1–2%',   '#7c3aed'],
          ['Titik koma terakhir',   '1–2%',   '#6d28d9'],
          ['Semua opsi aktif',      '20–50%', '#059669'],
          ['+ Gzip compression',    '60–90%', '#0e4429'],
        ];
        foreach ($savings as [$label, $pct, $color]): ?>
          <div style="display:flex; justify-content:space-between; align-items:center;
                      padding:.3rem 0; border-bottom:1px solid var(--border);">
            <span style="color:var(--muted);"><?= $label ?></span>
            <span style="font-family:var(--font-mono); font-weight:700; color:<?= $color ?>;"><?= $pct ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/html-minifier"  class="btn-ghost btn-sm btn-full">HTML Minifier</a>
        <a href="/tools/js-minifier"    class="btn-ghost btn-sm btn-full">JS Minifier</a>
        <a href="/tools/json-formatter" class="btn-ghost btn-sm btn-full">JSON Formatter</a>
        <a href="/tools/text-diff"      class="btn-ghost btn-sm btn-full">Text Diff Checker</a>
        <a href="/tools/base64"         class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   CSS Minifier — logika UI JavaScript
   Minifikasi dilakukan oleh PHP server.
   JS: tab switching, stats, before/after,
   download, copy, drag-and-drop.
   ────────────────────────────────────────── */

let currentMode = '<?= $post_mode ?>';

function switchTab(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;
  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    t.classList.toggle('active', ['text','file','url'][i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

function toggleOpt(el, lblId) {
  const lbl = document.getElementById(lblId);
  if (lbl) lbl.classList.toggle('checked', el.checked);
}

function updateInputStats() {
  const ta = document.getElementById('input-css');
  const el = document.getElementById('input-stats');
  if (!ta || !el) return;
  const chars = ta.value.length;
  const lines = ta.value === '' ? 0 : ta.value.split('\n').length;
  el.textContent = chars.toLocaleString('id') + ' karakter · ' + lines.toLocaleString('id') + ' baris';
}

function showView(which) {
  const before = document.getElementById('view-before');
  const after  = document.getElementById('view-after');
  const btnB   = document.getElementById('btn-before');
  const btnA   = document.getElementById('btn-after');
  if (!before || !after) return;
  before.style.display = which === 'before' ? '' : 'none';
  after.style.display  = which === 'after'  ? '' : 'none';
  if (btnB) btnB.classList.toggle('active', which === 'before');
  if (btnA) btnA.classList.toggle('active', which === 'after');
}

function downloadMinified() {
  const el = document.getElementById('minified-out');
  if (!el) return;
  const blob = new Blob([el.textContent], { type: 'text/css;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'minified.css';
  a.click();
  URL.revokeObjectURL(a.href);
}

function copyMinified() {
  const el = document.getElementById('minified-out');
  if (!el) return;
  navigator.clipboard.writeText(el.textContent).then(() => {
    showToast && showToast('CSS minified disalin!', 'success');
  });
}

function handleFileSelect(input) {
  const disp = document.getElementById('file-name-disp');
  if (input.files[0]) {
    disp.style.display = 'block';
    disp.textContent   = '🎨 ' + input.files[0].name
      + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
  }
}

function clearFile() {
  const fi   = document.getElementById('upload-file');
  const disp = document.getElementById('file-name-disp');
  if (fi)   fi.value = '';
  if (disp) { disp.style.display = 'none'; disp.textContent = ''; }
}

function clearText() {
  const ta = document.getElementById('input-css');
  if (ta) ta.value = '';
  updateInputStats();
}

function loadSample() {
  const sample = `/* ============================================================
   Style utama aplikasi — v2.5.0
   Author: Developer <dev@example.com>
   ============================================================ */

/*! Lisensi MIT — komentar ini TIDAK akan dihapus */

/* Reset dasar */
* ,
*::before ,
*::after {
  box-sizing : border-box ;
  margin     : 0          ;
  padding    : 0          ;
}

body {
  font-family : 'Inter' , Arial , sans-serif ;
  font-size   : 16px    ;
  line-height : 1.6     ;
  color       : #333333 ;
  background  : #FFFFFF ;
}

/* ── Container ── */
.container {
  max-width  : 1200px     ;
  margin     : 0px auto   ;
  padding    : 0px 1.5rem ;
}

/* ── Tombol ── */
.btn {
  display         : inline-flex   ;
  align-items     : center        ;
  gap             : 0.5rem        ;
  padding         : 0.75rem 1.5rem ;
  background      : #2563EB       ;
  color           : #FFFFFF       ;
  border          : 0px           ;
  border-radius   : 0.375rem      ;
  font-weight     : 600           ;
  cursor          : pointer       ;
  transition      : all 0.2s ease ;
}

.btn:hover {
  background  : #1d4ed8       ;
  transform   : translateY( -2px ) ;
  box-shadow  : 0px 8px 24px rgba( 37, 99, 235, 0.30 ) ;
}

.btn:disabled ,
.btn[disabled] {
  opacity    : 0.5   ;
  cursor     : not-allowed ;
}

/* ── Grid responsif ── */
.grid {
  display               : grid                           ;
  grid-template-columns : repeat( auto-fill , minmax( 240px , 1fr ) ) ;
  gap                   : 1.25rem                        ;
}

/* ── Warna hex yang bisa dipersingkat ── */
.red-box    { background-color: #ff0000; border-color: #ffcccc; }
.green-box  { background-color: #00ff00; border-color: #ccffcc; }
.blue-box   { background-color: #0000ff; border-color: #ccccff; }
.white-box  { background-color: #ffffff; color: #000000; }

/* ── Media query ── */
@media ( max-width: 768px ) {
  .container { padding: 0px 1rem; }
  .grid      { grid-template-columns: 1fr; gap: 0.75rem; }
  .btn       { width: 100%; justify-content: center; }
}`;
  const ta = document.getElementById('input-css');
  if (ta) { ta.value = sample; updateInputStats(); }
}

// Drag-and-drop
const dropZone = document.getElementById('file-drop-zone');
if (dropZone) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    const inp = document.getElementById('upload-file');
    if (e.dataTransfer.files[0]) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      inp.files = dt.files;
      handleFileSelect(inp);
    }
  });
}

// Init
switchTab(currentMode);
updateInputStats();
</script>

<?php require '../../includes/footer.php'; ?>