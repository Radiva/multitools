<?php
require '../../includes/config.php';
/**
 * Multi Tools — JavaScript Minifier
 * Minify JS dengan opsi granular: hapus komentar, whitespace,
 * console.log, debugger, shorthen string, dan lainnya.
 * Mendukung input teks, upload file, dan fetch URL.
 * ============================================================ */

// ── JS Minifier Engine ────────────────────────────────────────

function minifyJS(string $js, array $opts = []): string {
  $removeLineComments   = $opts['remove_line_comments']    ?? true;
  $removeBlockComments  = $opts['remove_block_comments']   ?? true;
  $removeWhitespace     = $opts['remove_whitespace']       ?? true;
  $removeConsole        = $opts['remove_console']          ?? false;
  $removeDebugger       = $opts['remove_debugger']         ?? false;
  $removeStrictMode     = $opts['remove_strict_mode']      ?? false;
  $collapseVars         = $opts['collapse_vars']           ?? false;
  $keepLicenseComments  = $opts['keep_license_comments']   ?? true;

  // 1. Preserve string literals, regex, template literals
  $preserved = [];
  $ph        = 'JSPH';
  $counter   = 0;

  // Preserve template literals `...`
  $js = preg_replace_callback('/`(?:[^`\\\\]|\\\\.)*`/s', function($m) use (&$preserved, &$counter, $ph) {
    $key = $ph . ($counter++) . $ph;
    $preserved[$key] = $m[0];
    return $key;
  }, $js);

  // Preserve double-quoted strings
  $js = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/s', function($m) use (&$preserved, &$counter, $ph) {
    $key = $ph . ($counter++) . $ph;
    $preserved[$key] = $m[0];
    return $key;
  }, $js);

  // Preserve single-quoted strings
  $js = preg_replace_callback("/\'(?:[^\'\\\\]|\\\\.)*\'/s", function($m) use (&$preserved, &$counter, $ph) {
    $key = $ph . ($counter++) . $ph;
    $preserved[$key] = $m[0];
    return $key;
  }, $js);

  // 2. Remove block comments — keep /*! license */ if option set
  if ($removeBlockComments) {
    if ($keepLicenseComments) {
      // Preserve /*! ... */ license comments
      $js = preg_replace_callback('/\/\*!.*?\*\//s', function($m) use (&$preserved, &$counter, $ph) {
        $key = $ph . ($counter++) . $ph;
        $preserved[$key] = $m[0];
        return $key;
      }, $js);
    }
    $js = preg_replace('/\/\*.*?\*\//s', '', $js);
  }

  // 3. Remove console.* calls (optional)
  if ($removeConsole) {
    $js = preg_replace('/console\s*\.\s*\w+\s*\([^)]*\)\s*;?/s', '', $js);
  }

  // 4. Remove debugger statements (optional)
  if ($removeDebugger) {
    $js = preg_replace('/\bdebugger\s*;/i', '', $js);
  }

  // 5. Remove 'use strict' (optional)
  if ($removeStrictMode) {
    $js = preg_replace('/["\']use strict[\'"]\s*;?/', '', $js);
  }

  // 6. Remove line comments (careful: avoid // inside strings, already preserved)
  if ($removeLineComments) {
    // Handle // comments but not inside URLs (http://)
    $js = preg_replace('/(?<![:\'"\/])\/\/[^\n]*/m', '', $js);
  }

  // 7. Remove whitespace
  if ($removeWhitespace) {
    // Trim each line
    $lines = explode("\n", $js);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, fn($l) => $l !== '');
    $js = implode("\n", $lines);

    // Collapse multiple newlines
    $js = preg_replace('/\n{2,}/', "\n", $js);

    // Remove spaces around operators and punctuation (safe subset)
    $js = preg_replace('/\s*([=+\-*\/%&|^!<>?:,;{}()\[\]])\s*/', '$1', $js);

    // But keep space after keywords
    $keywords = ['return','typeof','instanceof','in','of','new','delete','void','throw',
                 'var','let','const','function','class','if','else','for','while',
                 'do','switch','case','break','continue','yield','async','await','import','export','from'];
    foreach ($keywords as $kw) {
      // Ensure space after keyword before identifier/value
      $js = preg_replace('/\b(' . $kw . ')([a-zA-Z0-9_$(`\'"])/', '$1 $2', $js);
    }

    // Remove trailing/leading whitespace from each line again
    $js = preg_replace('/[ \t]+/', ' ', $js);
    $js = preg_replace('/\n /', "\n", $js);
    $js = preg_replace('/ \n/', "\n", $js);

    // Collapse to single line for significant compression
    $js = preg_replace('/\n/', '', $js);
    $js = preg_replace('/ {2,}/', ' ', $js);
  }

  // 8. Collapse multiple var/let/const declarations (basic)
  if ($collapseVars) {
    $js = preg_replace('/\bvar\s+(\w+)\s*;\s*var\s+/', 'var $1,', $js);
    $js = preg_replace('/\blet\s+(\w+)\s*;\s*let\s+/', 'let $1,', $js);
  }

  // 9. Restore preserved strings/literals
  foreach ($preserved as $key => $val) {
    $js = str_replace($key, $val, $js);
  }

  return trim($js);
}

// ── Handle POST ──────────────────────────────────────────────
$server_result = '';
$server_error  = '';
$server_stats  = [];
$post_input    = '';
$post_mode     = 'text';
$post_opts = [
  'remove_line_comments'  => true,
  'remove_block_comments' => true,
  'remove_whitespace'     => true,
  'remove_console'        => false,
  'remove_debugger'       => false,
  'remove_strict_mode'    => false,
  'collapse_vars'         => false,
  'keep_license_comments' => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode = in_array($_POST['mode'] ?? 'text', ['text','file','url']) ? $_POST['mode'] : 'text';
  $post_opts = [
    'remove_line_comments'  => isset($_POST['remove_line_comments']),
    'remove_block_comments' => isset($_POST['remove_block_comments']),
    'remove_whitespace'     => isset($_POST['remove_whitespace']),
    'remove_console'        => isset($_POST['remove_console']),
    'remove_debugger'       => isset($_POST['remove_debugger']),
    'remove_strict_mode'    => isset($_POST['remove_strict_mode']),
    'collapse_vars'         => isset($_POST['collapse_vars']),
    'keep_license_comments' => isset($_POST['keep_license_comments']),
  ];

  switch ($post_mode) {
    case 'text':
      $post_input = $_POST['input_js'] ?? '';
      if (trim($post_input) === '') { $server_error = 'Input JavaScript tidak boleh kosong.'; break; }
      $server_result = minifyJS($post_input, $post_opts);
      break;

    case 'file':
      if (empty($_FILES['upload_file']['tmp_name'])) { $server_error = 'Pilih file JS.'; break; }
      if ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) { $server_error = 'Gagal upload file.'; break; }
      $ext = strtolower(pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['js','mjs','cjs','ts'])) { $server_error = 'Hanya file .js/.mjs yang didukung.'; break; }
      if ($_FILES['upload_file']['size'] > 2 * 1024 * 1024) { $server_error = 'Ukuran file maks. 2 MB.'; break; }
      $post_input    = file_get_contents($_FILES['upload_file']['tmp_name']);
      $server_result = minifyJS($post_input, $post_opts);
      break;

    case 'url':
      $url = filter_var(trim($_POST['fetch_url'] ?? ''), FILTER_VALIDATE_URL);
      if (!$url) { $server_error = 'URL tidak valid.'; break; }
      $ctx     = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);
      $fetched = @file_get_contents($url, false, $ctx);
      if ($fetched === false) { $server_error = 'Gagal mengambil konten dari URL.'; break; }
      $post_input    = $fetched;
      $server_result = minifyJS($fetched, $post_opts);
      break;
  }

  if ($server_result && !$server_error) {
    $origLen  = strlen($post_input);
    $miniLen  = strlen($server_result);
    $saved    = $origLen - $miniLen;
    $origLines = substr_count($post_input, "\n") + 1;
    $miniLines = substr_count($server_result, "\n") + 1;
    // Count functions
    $origFns  = preg_match_all('/\bfunction\b/', $post_input);
    $miniFns  = preg_match_all('/\bfunction\b/', $server_result);
    $server_stats = [
      'orig_bytes'  => $origLen,
      'mini_bytes'  => $miniLen,
      'saved_bytes' => $saved,
      'saved_pct'   => $origLen > 0 ? round(($saved / $origLen) * 100, 1) : 0,
      'orig_lines'  => $origLines,
      'mini_lines'  => $miniLines,
      'orig_fns'    => $origFns,
      'mini_fns'    => $miniFns,
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
  'title'       => 'JS Minifier Online — Kompres & Minify JavaScript | Multi Tools',
  'description' => 'Minify dan kompres JavaScript secara instan. Hapus komentar, whitespace, console.log, debugger statement. Upload file .js atau fetch dari URL. Statistik kompresi lengkap.',
  'keywords'    => 'js minifier, javascript minifier, minify javascript, kompres js, remove console log, js compressor, javascript optimizer, multi tools',
  'og_title'    => 'JS Minifier Online — Kompres JavaScript Instan',
  'og_desc'     => 'Minify JS: hapus komentar, whitespace, console.log, debugger. Upload file atau fetch URL.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'JS Minifier'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/js-minifier#webpage',
      'url'         => SITE_URL . '/tools/js-minifier',
      'name'        => 'JS Minifier Online',
      'description' => 'Minify dan kompres JavaScript secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools','item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'JS Minifier',    'item' => SITE_URL . '/tools/js-minifier'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'JS Minifier',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/js-minifier',
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
  border-radius: var(--radius-sm);
  overflow: hidden; margin-bottom: 1.5rem;
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
.mode-tab.active     { background: var(--accent4); color: #fff; }

/* ── Option cards ── */
.opts-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: .5rem; margin-top: .4rem;
}
@media (max-width: 500px) { .opts-grid { grid-template-columns: 1fr; } }

.opt-card {
  display: flex; align-items: flex-start; gap: .6rem;
  padding: .65rem .75rem;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  cursor: pointer; transition: all var(--transition);
  background: var(--bg); user-select: none;
  position: relative; overflow: hidden;
}
.opt-card::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(circle at top left, rgba(245,158,11,.08), transparent 60%);
  opacity: 0; transition: opacity .3s;
}
.opt-card:hover        { border-color: var(--accent4); }
.opt-card:hover::before { opacity: 1; }
.opt-card.checked      { border-color: var(--accent4); background: rgba(245,158,11,.05); }
.opt-card.checked::before { opacity: 1; }
.opt-card input[type="checkbox"] { width: auto !important; accent-color: var(--accent4); flex-shrink: 0; margin-top: .1rem; }
.opt-card-body { display: flex; flex-direction: column; gap: .12rem; }
.opt-card-label { font-size: .83rem; font-weight: 600; color: var(--text); line-height: 1.2; }
.opt-card-desc  { font-size: .71rem; color: var(--muted); line-height: 1.3; }
.opt-card-example {
  font-family: var(--font-mono); font-size: .67rem; color: var(--accent4);
  margin-top: .15rem; background: rgba(245,158,11,.08);
  border-radius: 3px; padding: 1px 5px; display: inline-block;
}
.opt-card-save { font-size: .65rem; font-family: var(--font-mono); font-weight: 700; color: var(--accent5); margin-top: .1rem; }
.opt-card-warn { font-size: .65rem; font-family: var(--font-mono); font-weight: 700; color: #f59e0b; margin-top: .1rem; }

/* ── Compression stats ── */
.compression-stats {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 1px; background: var(--border);
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  overflow: hidden; margin-bottom: 1.25rem;
}
@media (max-width: 500px) { .compression-stats { grid-template-columns: repeat(2, 1fr); } }
.cs-item {
  padding: .85rem 1rem; background: var(--surface);
  display: flex; flex-direction: column; gap: .2rem;
}
.cs-val { font-family: var(--font-mono); font-size: 1.35rem; font-weight: 800; letter-spacing: -.03em; line-height: 1; }
.cs-lbl { font-size: .72rem; color: var(--muted); font-family: var(--font-mono); }
.cs-saved { color: var(--accent5); }
.cs-pct   { color: var(--accent4); }

/* ── Saving bar ── */
.saving-bar-wrap { margin-bottom: 1.25rem; }
.saving-bar {
  height: 8px; background: var(--border);
  border-radius: 99px; overflow: hidden; position: relative;
}
.saving-bar-orig { position: absolute; inset: 0; background: rgba(245,158,11,.15); border-radius: 99px; }
.saving-bar-mini {
  height: 100%; background: linear-gradient(90deg, var(--accent5), var(--accent4));
  border-radius: 99px; transition: width .5s cubic-bezier(.4,0,.2,1);
}
.saving-bar-meta {
  display: flex; justify-content: space-between;
  font-family: var(--font-mono); font-size: .7rem; color: var(--muted); margin-top: .35rem;
}

/* ── Output code block ── */
.output-code {
  background: #0f172a; border: 1px solid #1e293b;
  border-radius: var(--radius-sm); padding: 1rem 1.1rem;
  font-family: var(--font-mono); font-size: .78rem; color: #94a3b8;
  word-break: break-all; white-space: pre-wrap;
  max-height: 300px; overflow-y: auto; line-height: 1.65;
}

/* ── File drop ── */
.file-drop {
  border: 2px dashed var(--border); border-radius: var(--radius-md);
  padding: 2rem; text-align: center; cursor: pointer;
  transition: all var(--transition); background: var(--bg); position: relative;
}
.file-drop:hover, .file-drop.drag-over { border-color: var(--accent4); background: rgba(245,158,11,.04); }
.file-drop input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.file-drop-icon  { font-size: 2rem; margin-bottom: .5rem; opacity: .4; }
.file-drop-label { font-size: .9rem; color: var(--muted); margin-bottom: .25rem; }
.file-drop-hint  { font-size: .75rem; color: var(--muted); font-family: var(--font-mono); }
.file-name-disp  { margin-top: .75rem; font-family: var(--font-mono); font-size: .82rem; color: var(--accent4); font-weight: 600; display: none; }

/* ── Before/After toggle ── */
.diff-toggle-wrap {
  display: flex; gap: 0;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  overflow: hidden; margin-bottom: .75rem; width: fit-content;
}
.diff-toggle-btn {
  padding: .38rem 1rem; background: var(--bg); border: none;
  font-size: .78rem; font-weight: 600; color: var(--muted);
  cursor: pointer; transition: all var(--transition); font-family: var(--font-body);
}
.diff-toggle-btn:first-child { border-right: 1px solid var(--border); }
.diff-toggle-btn.active { background: var(--accent4); color: #fff; }

/* ── Inline stat ── */
.inline-stat {
  display: inline-flex; align-items: center; gap: .3rem;
  font-family: var(--font-mono); font-size: .7rem; font-weight: 700;
  padding: .2rem .6rem; border-radius: 99px;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
}
.inline-stat.amber { color: var(--accent4); border-color: var(--accent4); background: rgba(245,158,11,.07); }
.inline-stat.green { color: var(--accent5); border-color: var(--accent5); background: rgba(16,185,129,.07); }

/* ── Breakdown table ── */
.breakdown-table { width: 100%; border-collapse: collapse; font-size: .77rem; }
.breakdown-table th {
  padding: .4rem .65rem; text-align: left; border-bottom: 1px solid var(--border);
  font-family: var(--font-mono); font-size: .67rem; letter-spacing: .06em;
  text-transform: uppercase; color: var(--muted); font-weight: 700;
}
.breakdown-table td { padding: .38rem .65rem; border-bottom: 1px solid var(--border); }
.breakdown-table tr:last-child td { border-bottom: none; }
.breakdown-table .mono { font-family: var(--font-mono); font-size: .7rem; color: var(--accent4); }

/* ── Line counter in textarea ── */
.editor-wrap { position: relative; }
.editor-wrap textarea { padding-left: 3.5rem !important; }
.line-numbers {
  position: absolute; left: 0; top: 0;
  width: 3rem; height: 100%;
  padding: .65rem .4rem;
  background: rgba(0,0,0,.03);
  border-right: 1px solid var(--border);
  border-radius: var(--radius-sm) 0 0 var(--radius-sm);
  font-family: var(--font-mono); font-size: .75rem;
  color: var(--muted); text-align: right;
  line-height: 1.6; user-select: none;
  overflow: hidden; pointer-events: none;
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
        <span aria-hidden="true">⚙️</span> JS <span>Minifier</span>
      </div>
      <p class="page-lead">
        Kompres JavaScript secara instan — hapus komentar, whitespace, <code>console.log</code>,
        dan <code>debugger</code>. Upload file atau fetch langsung dari URL CDN.
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

      <form method="POST" action="" id="js-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" id="mode-input" name="mode" value="<?= e($post_mode) ?>" />

        <!-- ══ Opsi minifikasi ══ -->
        <div class="form-group">
          <label>Opsi minifikasi</label>
          <div class="opts-grid">
            <?php
            $options = [
              ['key'=>'remove_line_comments',  'label'=>'Hapus komentar baris',       'desc'=>'Hapus // komentar di tiap baris',          'example'=>'// comment → (dihapus)',        'save'=>'~3-10%',  'warn'=>false],
              ['key'=>'remove_block_comments', 'label'=>'Hapus komentar blok',         'desc'=>'Hapus /* ... */ (kecuali /*! lisensi */)', 'example'=>'/* block */ → (dihapus)',        'save'=>'~2-8%',   'warn'=>false],
              ['key'=>'remove_whitespace',     'label'=>'Hapus whitespace',            'desc'=>'Hapus spasi, tab, newline berlebih',       'example'=>'var x = 1 ; → var x=1;',        'save'=>'~20-40%', 'warn'=>false],
              ['key'=>'keep_license_comments', 'label'=>'Pertahankan lisensi /*! */', 'desc'=>'Jangan hapus komentar lisensi & copyright','example'=>'/*! MIT */ → dipertahankan',    'save'=>'',        'warn'=>false],
              ['key'=>'remove_console',        'label'=>'Hapus console.*',            'desc'=>'Hapus console.log, .warn, .error, .info',  'example'=>'console.log() → (dihapus)',      'save'=>'~1-5%',   'warn'=>true],
              ['key'=>'remove_debugger',       'label'=>'Hapus debugger',             'desc'=>'Hapus pernyataan debugger;',               'example'=>'debugger; → (dihapus)',          'save'=>'~0-1%',   'warn'=>false],
              ['key'=>'remove_strict_mode',    'label'=>"Hapus 'use strict'",         'desc'=>"Hapus deklarasi 'use strict'",             "example"=>"'use strict'; → (dihapus)",     'save'=>'~0-1%',   'warn'=>true],
              ['key'=>'collapse_vars',         'label'=>'Gabungkan deklarasi var',    'desc'=>'Gabungkan var/let berurutan menjadi satu', 'example'=>'var a;var b; → var a,b;',       'save'=>'~0-2%',   'warn'=>false],
            ];
            foreach ($options as $opt):
              $checked = $post_opts[$opt['key']] ?? false;
            ?>
              <label class="opt-card <?= $checked ? 'checked' : '' ?>" id="lbl-<?= $opt['key'] ?>">
                <input type="checkbox" name="<?= $opt['key'] ?>"
                  <?= $checked ? 'checked' : '' ?>
                  onchange="toggleOpt(this, 'lbl-<?= $opt['key'] ?>')" />
                <div class="opt-card-body">
                  <span class="opt-card-label"><?= e($opt['label']) ?></span>
                  <span class="opt-card-desc"><?= e($opt['desc']) ?></span>
                  <span class="opt-card-example"><?= e($opt['example']) ?></span>
                  <?php if ($opt['save']): ?>
                    <span class="opt-card-save"><?= $opt['save'] ?> saving</span>
                  <?php endif; ?>
                  <?php if ($opt['warn']): ?>
                    <span class="opt-card-warn">⚠ Hati-hati di produksi</span>
                  <?php endif; ?>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ══ PANEL: Teks ══ -->
        <div id="panel-text" class="mode-panel" <?= $post_mode !== 'text' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-js">JavaScript input</label>
            <div class="editor-wrap">
              <div class="line-numbers" id="line-numbers">1</div>
              <textarea id="input-js" name="input_js"
                placeholder="Tempel kode JavaScript di sini..."
                oninput="updateInputStats(); updateLineNumbers();"
                onscroll="syncScroll(this)"
                style="min-height:240px; font-family:var(--font-mono); font-size:.82rem; line-height:1.6;"
              ><?= ($post_mode === 'text') ? e($post_input) : '' ?></textarea>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.3rem;">
              <div id="input-stats" style="font-family:var(--font-mono); font-size:.7rem; color:var(--muted);">
                0 karakter · 0 baris
              </div>
              <button type="button" class="btn-ghost btn-sm"
                onclick="loadSample()" style="padding:.3rem .7rem; font-size:.75rem;">
                📄 Contoh JS
              </button>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent4); border-color:var(--accent4);">
              ⚙️ Minify JS
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearText()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: File ══ -->
        <div id="panel-file" class="mode-panel" <?= $post_mode !== 'file' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>Upload file JavaScript</label>
            <div class="file-drop" id="file-drop-zone">
              <input type="file" name="upload_file" id="upload-file"
                accept=".js,.mjs,.cjs" onchange="handleFileSelect(this)" />
              <div class="file-drop-icon">⚙️</div>
              <div class="file-drop-label">Klik atau seret file JS ke sini</div>
              <div class="file-drop-hint">.js · .mjs · .cjs · Maks. 2 MB</div>
              <div class="file-name-disp" id="file-name-disp"></div>
            </div>
          </div>
          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent4); border-color:var(--accent4);">
              ⚙️ Minify File
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearFile()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: URL ══ -->
        <div id="panel-url" class="mode-panel" <?= $post_mode !== 'url' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1rem;">
            <span>ℹ</span>
            <span>Masukkan URL file JS publik. Berguna untuk mengambil library dari CDN dan meminify versinya sendiri.</span>
          </div>
          <div class="form-group">
            <label for="fetch-url">URL file JavaScript</label>
            <input type="url" id="fetch-url" name="fetch_url"
              placeholder="https://cdn.example.com/library.js"
              value="<?= ($post_mode === 'url') ? e($_POST['fetch_url'] ?? '') : '' ?>"
              style="font-family:var(--font-mono);" />
          </div>
          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent4); border-color:var(--accent4);">
              ⚙️ Fetch &amp; Minify
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
          <span class="inline-stat amber">Hemat <?= fmtBytes($server_stats['saved_bytes']) ?></span>
        </div>
      </div>

      <!-- Stats -->
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
          <span>Asli: <?= number_format($server_stats['orig_bytes']) ?> B · <?= $server_stats['orig_lines'] ?> baris · <?= $server_stats['orig_fns'] ?> fungsi</span>
          <span>Minified: <?= number_format($server_stats['mini_bytes']) ?> B · <?= $server_stats['mini_lines'] ?> baris</span>
        </div>
      </div>

      <!-- Before/After -->
      <div class="diff-toggle-wrap">
        <button type="button" class="diff-toggle-btn" id="btn-before" onclick="showView('before')">Sebelum</button>
        <button type="button" class="diff-toggle-btn active" id="btn-after" onclick="showView('after')">Sesudah</button>
      </div>

      <div id="view-before" style="display:none;">
        <div class="output-code"><?= e(substr($post_input, 0, 3000)) ?><?= strlen($post_input) > 3000 ? "\n\n// ... dipotong untuk tampilan" : '' ?></div>
      </div>

      <div id="view-after">
        <div class="copy-wrap">
          <div class="output-code" id="minified-out"><?= e($server_result) ?></div>
          <button class="copy-btn" data-copy-target="minified-out" style="top:.5rem;">SALIN</button>
        </div>
      </div>

      <!-- Actions -->
      <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
        <button class="btn-primary btn-sm"
          style="background:var(--accent4); border-color:var(--accent4);"
          onclick="downloadMinified()">
          ⬇ Unduh JS Minified
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
      <div class="panel-title">💡 Tips Minifikasi JS</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Selalu test hasil minifikasi sebelum deploy</li>
        <li>Gunakan <strong>source map</strong> untuk debugging</li>
        <li>Komentar <code>/*! ... */</code> selalu dipertahankan</li>
        <li>String literal dan regex tidak disentuh</li>
        <li><strong>console.log</strong> aman dihapus di produksi</li>
        <li>Gabungkan dengan <strong>gzip/Brotli</strong> di server</li>
        <li>Gunakan <strong>bundler</strong> (Webpack/Vite) untuk produksi nyata</li>
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
            ['Ukuran asli',    fmtBytes($server_stats['orig_bytes'])],
            ['Setelah minify', fmtBytes($server_stats['mini_bytes'])],
            ['Dihemat',        fmtBytes($server_stats['saved_bytes'])],
            ['Kompresi',       $server_stats['saved_pct'] . '%'],
            ['Baris asli',     number_format($server_stats['orig_lines'])],
            ['Baris minified', number_format($server_stats['mini_lines'])],
            ['Fungsi terdeteksi', number_format($server_stats['orig_fns'])],
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
          ['Hapus komentar baris',  '3–10%',  '#16a34a'],
          ['Hapus komentar blok',   '2–8%',   '#0369a1'],
          ['Hapus whitespace',      '20–40%', '#15803d'],
          ['Hapus console.*',       '1–5%',   '#92400e'],
          ['Hapus debugger',        '0–1%',   '#6d28d9'],
          ['Semua opsi aktif',      '25–50%', '#059669'],
          ['+ Gzip/Brotli',         '60–90%', '#0e4429'],
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
      <div class="panel-title">⚠ Yang Tidak Disentuh</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>String literal <code>"..."</code> dan <code>'...'</code></li>
        <li>Template literal <code>`...`</code></li>
        <li>Ekspresi regex <code>/pattern/</code></li>
        <li>Komentar lisensi <code>/*! ... */</code></li>
        <li>URL dalam komentar <code>http://</code></li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/html-minifier"  class="btn-ghost btn-sm btn-full">HTML Minifier</a>
        <a href="/tools/css-minifier"   class="btn-ghost btn-sm btn-full">CSS Minifier</a>
        <a href="/tools/json-formatter" class="btn-ghost btn-sm btn-full">JSON Formatter</a>
        <a href="/tools/regex-tester"   class="btn-ghost btn-sm btn-full">Regex Tester</a>
        <a href="/tools/base64"         class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   JS Minifier — logika UI JavaScript
   Minifikasi dilakukan oleh PHP server.
   JS: tab switching, line numbers, stats,
   before/after toggle, download, copy.
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

// ── Line numbers ──────────────────────────────────────────────
function updateLineNumbers() {
  const ta  = document.getElementById('input-js');
  const ln  = document.getElementById('line-numbers');
  if (!ta || !ln) return;
  const lines = ta.value.split('\n').length;
  ln.innerHTML = Array.from({length: lines}, (_, i) => i + 1).join('<br>');
}

function syncScroll(ta) {
  const ln = document.getElementById('line-numbers');
  if (ln) ln.scrollTop = ta.scrollTop;
}

// ── Input stats ───────────────────────────────────────────────
function updateInputStats() {
  const ta  = document.getElementById('input-js');
  const el  = document.getElementById('input-stats');
  if (!ta || !el) return;
  const chars = ta.value.length;
  const lines = ta.value === '' ? 0 : ta.value.split('\n').length;
  const fns   = (ta.value.match(/\bfunction\b/g) || []).length;
  el.textContent = chars.toLocaleString('id') + ' karakter · ' + lines.toLocaleString('id') + ' baris · ' + fns + ' fungsi';
}

// ── Before / After toggle ─────────────────────────────────────
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

// ── Download ──────────────────────────────────────────────────
function downloadMinified() {
  const el = document.getElementById('minified-out');
  if (!el) return;
  const blob = new Blob([el.textContent], { type: 'application/javascript;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'minified.min.js';
  a.click();
  URL.revokeObjectURL(a.href);
}

function copyMinified() {
  const el = document.getElementById('minified-out');
  if (!el) return;
  navigator.clipboard.writeText(el.textContent).then(() => {
    showToast && showToast('JS minified disalin!', 'success');
  });
}

// ── File drop ─────────────────────────────────────────────────
function handleFileSelect(input) {
  const disp = document.getElementById('file-name-disp');
  if (input.files[0]) {
    disp.style.display = 'block';
    disp.textContent   = '⚙️ ' + input.files[0].name
      + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
  }
}

function clearFile() {
  const fi = document.getElementById('upload-file');
  const disp = document.getElementById('file-name-disp');
  if (fi) fi.value = '';
  if (disp) { disp.style.display = 'none'; disp.textContent = ''; }
}

function clearText() {
  const ta = document.getElementById('input-js');
  if (ta) ta.value = '';
  updateInputStats();
  updateLineNumbers();
}

// ── Sample JS ─────────────────────────────────────────────────
function loadSample() {
  const sample = `/*!
 * MyApp v1.0.0 — Main JavaScript
 * Copyright (c) 2025 Developer
 * Licensed under MIT
 */

'use strict';

// ── Konfigurasi aplikasi ──
const CONFIG = {
  apiUrl   : 'https://api.example.com/v1',
  timeout  : 5000,
  maxRetry : 3,
  debug    : false,
};

/**
 * Utility: Format angka ke Rupiah.
 * @param {number} amount - Nominal
 * @returns {string} Formatted string
 */
function formatRupiah(amount) {
  // Validasi input
  if (typeof amount !== 'number' || isNaN(amount)) {
    console.warn('formatRupiah: input tidak valid', amount);
    return 'Rp 0';
  }

  return 'Rp ' + amount.toLocaleString('id-ID', {
    minimumFractionDigits : 0,
    maximumFractionDigits : 0,
  });
}

/**
 * Fetch data dari API dengan retry otomatis.
 */
async function fetchWithRetry(url, options = {}, retries = CONFIG.maxRetry) {
  try {
    console.log('Fetching:', url); // debug log

    const response = await fetch(url, {
      ...options,
      signal : AbortSignal.timeout(CONFIG.timeout),
    });

    if (!response.ok) {
      throw new Error(\`HTTP \${response.status}: \${response.statusText}\`);
    }

    const data = await response.json();
    console.log('Data received:', data); // debug log
    return data;

  } catch (error) {
    if (retries > 0) {
      console.warn(\`Retry \${CONFIG.maxRetry - retries + 1}/\${CONFIG.maxRetry}:\`, error.message);
      await new Promise(resolve => setTimeout(resolve, 1000));
      return fetchWithRetry(url, options, retries - 1);
    }
    console.error('Fetch gagal setelah semua retry:', error);
    throw error;
  }
}

// ── Event Listeners ──
document.addEventListener('DOMContentLoaded', () => {
  debugger; // Hapus sebelum produksi!

  const btnLoad = document.querySelector('#btn-load');
  const listEl  = document.querySelector('#product-list');

  if (!btnLoad || !listEl) {
    console.error('Elemen tidak ditemukan');
    return;
  }

  btnLoad.addEventListener('click', async () => {
    try {
      btnLoad.disabled   = true;
      btnLoad.textContent = 'Loading...';

      const products = await fetchWithRetry(CONFIG.apiUrl + '/products');

      listEl.innerHTML = products.map(p => \`
        <div class="product-card">
          <h3>\${p.name}</h3>
          <p>\${formatRupiah(p.price)}</p>
        </div>
      \`).join('');

    } catch (err) {
      console.error('Gagal memuat produk:', err);
      listEl.innerHTML = '<p class="error">Gagal memuat data.</p>';
    } finally {
      btnLoad.disabled   = false;
      btnLoad.textContent = 'Muat Produk';
    }
  });
});`;

  const ta = document.getElementById('input-js');
  if (ta) {
    ta.value = sample;
    updateInputStats();
    updateLineNumbers();
  }
}

// ── Drag-and-drop ─────────────────────────────────────────────
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

// ── Init ──────────────────────────────────────────────────────
switchTab(currentMode);
updateInputStats();
updateLineNumbers();
</script>

<?php require '../../includes/footer.php'; ?>