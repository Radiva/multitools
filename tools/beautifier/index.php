<?php
require '../../includes/config.php';
/**
 * Multi Tools — Code Beautifier
 * Merapikan dan memformat kode HTML, CSS, JavaScript, JSON, PHP, SQL.
 * Mendukung indentasi kustom, preview hasil, dan download.
 * ============================================================ */

// ── Beautifier engines ────────────────────────────────────────

/**
 * Beautify JSON.
 */
function beautifyJSON(string $code, int $indent = 2): array {
  $decoded = json_decode($code);
  if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    return ['ok' => false, 'error' => 'JSON tidak valid: ' . json_last_error_msg(), 'result' => ''];
  }
  $flags  = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
  $result = json_encode($decoded, $flags);
  // Re-indent sesuai kustom (default PHP 4 spaces)
  if ($indent !== 4) {
    $result = preg_replace_callback('/^( +)/m', function($m) use ($indent) {
      $spaces = strlen($m[1]);
      $levels = (int)($spaces / 4);
      return str_repeat(' ', $levels * $indent);
    }, $result);
  }
  return ['ok' => true, 'error' => '', 'result' => $result];
}

/**
 * Beautify CSS — re-indentasi dan format properti.
 */
function beautifyCSS(string $code, int $indent = 2): array {
  $indentStr = str_repeat(' ', $indent);
  $result    = '';
  $level     = 0;

  // Normalize whitespace & comments
  $code = preg_replace('/\s+/', ' ', $code);
  // Split pada { } ;
  $tokens = preg_split('/([{}])/', $code, -1, PREG_SPLIT_DELIM_CAPTURE);

  foreach ($tokens as $token) {
    $t = trim($token);
    if ($t === '') continue;

    if ($t === '{') {
      $result  = rtrim($result) . " {\n";
      $level++;
    } elseif ($t === '}') {
      $level   = max(0, $level - 1);
      $result .= str_repeat($indentStr, $level) . "}\n\n";
    } else {
      // Properties inside a block
      $props = array_filter(array_map('trim', explode(';', $t)));
      foreach ($props as $prop) {
        if ($prop === '') continue;
        if ($level > 0) {
          // Add space after colon in property
          $prop    = preg_replace('/\s*:\s*/', ': ', $prop);
          $result .= str_repeat($indentStr, $level) . $prop . ";\n";
        } else {
          // Selector
          $result .= $prop . ' ';
        }
      }
    }
  }
  return ['ok' => true, 'error' => '', 'result' => trim($result)];
}

/**
 * Beautify HTML — re-indentasi tag.
 */
function beautifyHTML(string $code, int $indent = 2): array {
  $indentStr  = str_repeat(' ', $indent);
  $voidTags   = ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'];
  $inlineTags = ['a','abbr','acronym','b','bdo','big','br','button','cite','code','dfn','em','i',
                 'img','input','kbd','label','map','object','output','q','samp','select','small',
                 'span','strong','sub','sup','textarea','time','tt','var'];

  // Normalize
  $code = preg_replace('/>\s+</', ">\n<", $code);
  $lines  = explode("\n", $code);
  $result = '';
  $level  = 0;

  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    // Closing tag reduces indent before printing
    if (preg_match('/^<\/([a-zA-Z][a-zA-Z0-9]*)/', $line, $m)) {
      $tag = strtolower($m[1]);
      if (!in_array($tag, $inlineTags)) {
        $level = max(0, $level - 1);
      }
    }

    $result .= str_repeat($indentStr, $level) . $line . "\n";

    // Opening tag increases indent after printing (not void, not self-closing)
    if (preg_match('/^<([a-zA-Z][a-zA-Z0-9]*)(\s[^>]*)?>(?!.*<\/\1>)/', $line, $m)) {
      $tag = strtolower($m[1]);
      if (!in_array($tag, $voidTags) && !in_array($tag, $inlineTags) && !str_ends_with(trim($line), '/>')) {
        $level++;
      }
    }
  }

  return ['ok' => true, 'error' => '', 'result' => trim($result)];
}

/**
 * Beautify SQL — uppercase keywords dan format klausa.
 */
function beautifySQL(string $code, int $indent = 2): array {
  $indentStr = str_repeat(' ', $indent);

  // Uppercase SQL keywords
  $keywords = ['SELECT','FROM','WHERE','JOIN','LEFT JOIN','RIGHT JOIN','INNER JOIN','OUTER JOIN',
               'FULL JOIN','CROSS JOIN','ON','AS','AND','OR','NOT','IN','BETWEEN','LIKE','EXISTS',
               'GROUP BY','ORDER BY','HAVING','LIMIT','OFFSET','INSERT INTO','VALUES','UPDATE',
               'SET','DELETE FROM','CREATE TABLE','ALTER TABLE','DROP TABLE','INDEX','PRIMARY KEY',
               'FOREIGN KEY','REFERENCES','UNIQUE','NOT NULL','DEFAULT','AUTO_INCREMENT',
               'DISTINCT','ALL','UNION','INTERSECT','EXCEPT','CASE','WHEN','THEN','ELSE','END',
               'COUNT','SUM','AVG','MIN','MAX','COALESCE','NULLIF','CAST','CONVERT',
               'IS NULL','IS NOT NULL','ASC','DESC','RETURNING'];

  $result = preg_replace('/\s+/', ' ', trim($code));

  // Case-insensitive keyword replacement
  foreach ($keywords as $kw) {
    $result = preg_replace('/\b' . preg_quote($kw, '/') . '\b/i', $kw, $result);
  }

  // Newlines before major clauses
  $clauses = ['SELECT','FROM','WHERE','LEFT JOIN','RIGHT JOIN','INNER JOIN','OUTER JOIN',
              'FULL JOIN','CROSS JOIN','JOIN','ON','GROUP BY','ORDER BY','HAVING',
              'LIMIT','UNION','INSERT INTO','VALUES','UPDATE','SET','DELETE FROM'];

  foreach ($clauses as $clause) {
    $result = preg_replace('/\s+(' . preg_quote($clause, '/') . ')\s+/i',
                           "\n" . $clause . "\n" . $indentStr, $result);
  }

  // Indent comma-separated columns in SELECT
  $result = preg_replace('/,\s*(?=[a-zA-Z_`"\'*])/', ",\n" . $indentStr, $result);

  return ['ok' => true, 'error' => '', 'result' => trim($result)];
}

/**
 * Beautify PHP code menggunakan token_get_all.
 */
function beautifyPHP(string $code, int $indent = 4): array {
  if (!str_starts_with(ltrim($code), '<?')) {
    $code = '<?php ' . $code;
    $addedTag = true;
  } else {
    $addedTag = false;
  }

  try {
    $tokens    = @token_get_all($code);
    $result    = '';
    $level     = 0;
    $newline   = false;
    $indentStr = str_repeat(' ', $indent);

    foreach ($tokens as $tok) {
      if (is_array($tok)) {
        [$id, $val] = $tok;
        switch ($id) {
          case T_WHITESPACE:
            $val = preg_replace('/[^\n]/', '', $val); // keep newlines only
            if (strpos($val, "\n") !== false) { $newline = true; }
            break;
          default:
            if ($newline) { $result .= "\n" . str_repeat($indentStr, $level); $newline = false; }
            $result .= $val;
        }
      } else {
        $ch = $tok;
        if ($newline) { $result .= "\n" . str_repeat($indentStr, $level); $newline = false; }
        if ($ch === '{') { $result .= "{\n"; $level++; $newline = false; continue; }
        if ($ch === '}') { $level = max(0, $level - 1); $result .= "\n" . str_repeat($indentStr, $level) . "}\n"; $newline = false; continue; }
        if ($ch === ';') { $result .= ";\n"; $newline = false; continue; }
        $result .= $ch;
      }
    }
    if ($addedTag) {
      $result = ltrim(str_replace('<?php ', '', $result));
    }
    return ['ok' => true, 'error' => '', 'result' => trim($result)];
  } catch (Throwable $e) {
    return ['ok' => false, 'error' => 'Parse error: ' . $e->getMessage(), 'result' => ''];
  }
}

/**
 * Beautify JavaScript — basic indentation balancing.
 */
function beautifyJS(string $code, int $indent = 2): array {
  $indentStr = str_repeat(' ', $indent);
  $result    = '';
  $level     = 0;
  $inStr     = false;
  $strChar   = '';
  $prevChar  = '';
  $i         = 0;
  $len       = strlen($code);
  $lineBuffer = '';

  // Preprocessing: normalize line endings
  $code = str_replace(["\r\n","\r"], "\n", $code);

  // Simple token approach — split on structural chars
  $lines = explode("\n", $code);
  $lines = array_map('trim', $lines);
  $lines = array_filter($lines, fn($l) => $l !== '');

  foreach ($lines as $line) {
    // Detect if line starts with closing brace
    if (preg_match('/^[}\])]/', $line)) {
      $level = max(0, $level - 1);
    }

    $result .= str_repeat($indentStr, $level) . $line . "\n";

    // Count opening vs closing for next line indent
    $opens  = preg_match_all('/[{(\[](?![^"\']*["\'][^"\']*$)/', $line);
    $closes = preg_match_all('/[})\]](?![^"\']*["\'][^"\']*$)/', $line);
    $delta  = $opens - $closes;

    // Don't apply delta if line itself starts with a closer (already decremented)
    if (preg_match('/^[}\])]/', $line)) $delta++;

    $level = max(0, $level + $delta);
  }

  return ['ok' => true, 'error' => '', 'result' => trim($result)];
}

// ── Handle POST ──────────────────────────────────────────────
$server_result = '';
$server_error  = '';
$post_code     = '';
$post_lang     = 'json';
$post_indent   = 2;
$post_mode     = 'beautify'; // beautify | minify_check

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_lang   = in_array($_POST['language'] ?? 'json', ['json','css','html','js','php','sql'])
                   ? $_POST['language'] : 'json';
  $post_indent = max(1, min(8, (int)($_POST['indent'] ?? 2)));
  $post_code   = $_POST['code'] ?? '';

  if (trim($post_code) === '') {
    $server_error = 'Kode tidak boleh kosong.';
  } else {
    $out = match($post_lang) {
      'json' => beautifyJSON($post_code, $post_indent),
      'css'  => beautifyCSS($post_code, $post_indent),
      'html' => beautifyHTML($post_code, $post_indent),
      'sql'  => beautifySQL($post_code, $post_indent),
      'php'  => beautifyPHP($post_code, $post_indent),
      'js'   => beautifyJS($post_code, $post_indent),
      default => ['ok' => false, 'error' => 'Bahasa tidak didukung.', 'result' => ''],
    };
    if (!$out['ok']) {
      $server_error  = $out['error'];
    } else {
      $server_result = $out['result'];
    }
  }
}

// ── Statistik kode ────────────────────────────────────────────
function codeStats(string $code): array {
  $lines    = explode("\n", $code);
  $noBlank  = array_filter($lines, fn($l) => trim($l) !== '');
  $charComp = strlen(preg_replace('/\s+/', '', $code));
  return [
    'chars'     => strlen($code),
    'lines'     => count($lines),
    'non_blank' => count($noBlank),
    'chars_no_ws' => $charComp,
  ];
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Code Beautifier Online — Format HTML, CSS, JS, JSON, PHP, SQL | Multi Tools',
  'description' => 'Beautify dan format kode HTML, CSS, JavaScript, JSON, PHP, dan SQL secara instan. Indentasi kustom, syntax highlight, preview hasil, dan download. Gratis tanpa login.',
  'keywords'    => 'code beautifier, code formatter, beautify json, format css, prettify html, js formatter, php beautifier, sql formatter, multi tools',
  'og_title'    => 'Code Beautifier Online — Format HTML, CSS, JS, JSON, PHP, SQL',
  'og_desc'     => 'Format kode HTML, CSS, JavaScript, JSON, PHP, SQL secara instan. Indentasi kustom dan download.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'Code Beautifier'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/code-beautifier#webpage',
      'url'         => SITE_URL . '/tools/code-beautifier',
      'name'        => 'Code Beautifier Online',
      'description' => 'Beautify kode HTML, CSS, JavaScript, JSON, PHP, dan SQL secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools','item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Code Beautifier','item' => SITE_URL . '/tools/code-beautifier'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Code Beautifier',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/code-beautifier',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Language tabs ── */
.lang-tabs {
  display: flex; gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1.5rem;
}
.lang-tab {
  flex: 1; padding: .55rem .3rem;
  background: var(--bg); border: none;
  border-right: 1px solid var(--border);
  font-family: var(--font-mono); font-size: .8rem; font-weight: 700;
  color: var(--muted); cursor: pointer;
  transition: all var(--transition); text-align: center;
}
.lang-tab:last-child  { border-right: none; }
.lang-tab:hover       { background: var(--surface); color: var(--text); }
.lang-tab.active      { background: var(--accent); color: #fff; }
.lang-tab .lt-name    { font-size: .7rem; display: block; }
.lang-tab .lt-ext     { font-size: .62rem; opacity: .7; display: block; margin-top: .05rem; }

/* ── Split editor ── */
.code-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  min-height: 440px;
}
@media (max-width: 768px) { .code-layout { grid-template-columns: 1fr; } }

.code-pane { display: flex; flex-direction: column; }
.code-pane-left { border-right: 1px solid var(--border); }
.code-pane-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .5rem .85rem;
  background: #0f172a; border-bottom: 1px solid #1e293b;
  font-family: var(--font-mono); font-size: .72rem; font-weight: 700;
  color: #64748b; flex-shrink: 0;
}
.code-pane-header .pane-title { color: #94a3b8; }
.code-pane-header .pane-btns { display: flex; gap: .3rem; }
.pane-btn {
  padding: .2rem .5rem; border: 1px solid #334155; border-radius: 4px;
  font-size: .65rem; font-family: var(--font-mono); font-weight: 700;
  background: #1e293b; color: #94a3b8; cursor: pointer; transition: all var(--transition);
}
.pane-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── Code textareas ── */
.code-textarea {
  flex: 1; width: 100%; resize: none;
  border: none !important; outline: none !important; box-shadow: none !important;
  border-radius: 0 !important;
  background: #0f172a !important; color: #e2e8f0 !important;
  font-family: var(--font-mono) !important; font-size: .82rem !important;
  line-height: 1.7; padding: 1rem !important;
  tab-size: 2; -moz-tab-size: 2;
  min-height: 380px;
  caret-color: #60a5fa;
}
.code-textarea::placeholder { color: #475569; }
.code-textarea::-webkit-scrollbar { width: 6px; height: 6px; }
.code-textarea::-webkit-scrollbar-track { background: #0f172a; }
.code-textarea::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

/* ── Code output with highlight ── */
.code-output-wrap {
  flex: 1; position: relative; overflow: auto;
  background: #0f172a;
}
.code-output-pre {
  margin: 0; padding: 1rem;
  font-family: var(--font-mono); font-size: .82rem;
  line-height: 1.7; color: #e2e8f0;
  white-space: pre; overflow: visible; min-height: 380px;
}

/* ── Syntax highlight token colors ── */
.hl-kw     { color: #c084fc; } /* keyword */
.hl-str    { color: #86efac; } /* string */
.hl-num    { color: #fb923c; } /* number */
.hl-cmt    { color: #475569; font-style: italic; } /* comment */
.hl-fn     { color: #60a5fa; } /* function */
.hl-op     { color: #f8fafc; } /* operator */
.hl-tag    { color: #f87171; } /* html tag */
.hl-attr   { color: #fbbf24; } /* attribute */
.hl-val    { color: #86efac; } /* attribute value */
.hl-sel    { color: #60a5fa; } /* css selector */
.hl-prop   { color: #c084fc; } /* css property */
.hl-csval  { color: #fb923c; } /* css value */
.hl-key    { color: #60a5fa; } /* json key */
.hl-bool   { color: #fb923c; } /* json bool/null */
.hl-sqlkw  { color: #c084fc; } /* sql keyword */
.hl-sqltbl { color: #60a5fa; } /* sql table/column */

/* ── Stats bar ── */
.code-stats {
  display: flex; flex-wrap: wrap; gap: 0;
  border-top: 1px solid var(--border);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  background: #0f172a; overflow: hidden;
}
.cs-item {
  padding: .4rem .8rem; display: flex; flex-direction: column;
  gap: .05rem; border-right: 1px solid #1e293b; flex-shrink: 0;
}
.cs-item:last-child { border-right: none; }
.cs-item .cv { font-family: var(--font-mono); font-size: .8rem; font-weight: 800; color: #60a5fa; }
.cs-item .cl { font-size: .62rem; color: #475569; font-family: var(--font-mono); }

/* ── Options bar ── */
.opts-bar {
  display: flex; flex-wrap: wrap; gap: .5rem;
  align-items: center; margin-bottom: 1rem;
}
.indent-sel {
  display: flex; gap: 0;
  border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden;
}
.indent-opt {
  padding: .3rem .6rem; background: var(--bg); border: none;
  font-family: var(--font-mono); font-size: .75rem; font-weight: 700;
  color: var(--muted); cursor: pointer; transition: all var(--transition);
  border-right: 1px solid var(--border);
}
.indent-opt:last-child { border-right: none; }
.indent-opt.active     { background: var(--accent); color: #fff; }
.indent-opt:hover:not(.active) { background: var(--surface); color: var(--text); }

/* ── Diff badge ── */
.diff-badge {
  display: inline-flex; align-items: center; gap: .3rem;
  font-family: var(--font-mono); font-size: .7rem; font-weight: 700;
  padding: .2rem .6rem; border-radius: 99px; border: 1px solid;
}
.diff-badge.smaller { color: var(--accent5); border-color: var(--accent5); background: rgba(16,185,129,.07); }
.diff-badge.bigger  { color: var(--accent4); border-color: var(--accent4); background: rgba(245,158,11,.07); }
.diff-badge.same    { color: var(--muted);   border-color: var(--border);  background: var(--surface); }

/* ── Action buttons ── */
.action-bar {
  display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem;
}
.action-bar .sep { flex: 1; }

/* ── Copy confirm animation ── */
@keyframes popIn { 0%{transform:scale(.8);opacity:0} 50%{transform:scale(1.05)} 100%{transform:scale(1);opacity:1} }
.pop-in { animation: popIn .25s ease forwards; }
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
        <span aria-hidden="true">✨</span> Code <span>Beautifier</span>
      </div>
      <p class="page-lead">
        Format dan rapikan kode secara instan — JSON, HTML, CSS, JavaScript, PHP, dan SQL.
        Syntax highlight realtime, indentasi kustom, download hasil.
      </p>

      <!-- Language tabs -->
      <div class="lang-tabs" role="tablist" id="lang-tabs">
        <?php
        $langs = [
          'json' => ['JSON','json'],
          'html' => ['HTML','html'],
          'css'  => ['CSS', 'css'],
          'js'   => ['JS',  'js'],
          'php'  => ['PHP', 'php'],
          'sql'  => ['SQL', 'sql'],
        ];
        foreach ($langs as $val => [$name, $ext]): ?>
          <button type="button" role="tab"
            class="lang-tab <?= $post_lang === $val ? 'active' : '' ?>"
            onclick="setLang('<?= $val ?>')"
            data-lang="<?= $val ?>">
            <span class="lt-name"><?= $name ?></span>
            <span class="lt-ext">.<?= $ext ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Options bar -->
      <div class="opts-bar">
        <label class="text-sm text-muted" style="margin:0;">Indentasi:</label>
        <div class="indent-sel" id="indent-sel">
          <?php foreach ([2 => '2 sp', 4 => '4 sp', 8 => '8 sp', 1 => '1 tab'] as $n => $lbl): ?>
            <button type="button" class="indent-opt <?= $post_indent === $n ? 'active' : '' ?>"
              onclick="setIndent(<?= $n ?>)" data-indent="<?= $n ?>">
              <?= $lbl ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="sep"></div>
        <span class="diff-badge same" id="size-diff" style="display:none;"></span>
      </div>

      <!-- Split code editor -->
      <form method="POST" action="" id="beauty-form" novalidate>
        <input type="hidden" id="lang-input"   name="language" value="<?= e($post_lang) ?>" />
        <input type="hidden" id="indent-input" name="indent"   value="<?= $post_indent ?>" />

        <div class="code-layout">
          <!-- Left: Input -->
          <div class="code-pane code-pane-left">
            <div class="code-pane-header">
              <span class="pane-title" id="input-pane-title">INPUT — <?= strtoupper($post_lang) ?></span>
              <div class="pane-btns">
                <button type="button" class="pane-btn" onclick="pasteClipboard()">Tempel</button>
                <button type="button" class="pane-btn" onclick="document.getElementById('file-upload').click()">Upload</button>
                <button type="button" class="pane-btn" onclick="clearInput()">Hapus</button>
                <input type="file" id="file-upload" style="display:none;" onchange="loadFile(this)" />
              </div>
            </div>
            <textarea class="code-textarea" id="code-input" name="code"
              placeholder="Tempel kode di sini..."
              oninput="beautifyJS(); updateStats();"
              spellcheck="false"
              autocomplete="off" autocorrect="off" autocapitalize="off"
            ><?= e($post_code) ?></textarea>
          </div>

          <!-- Right: Output with highlight -->
          <div class="code-pane">
            <div class="code-pane-header">
              <span class="pane-title" id="output-pane-title">OUTPUT — BEAUTIFIED</span>
              <div class="pane-btns">
                <button type="button" class="pane-btn" onclick="copyOutput()">Salin</button>
                <button type="button" class="pane-btn" onclick="useAsInput()">→ Input</button>
              </div>
            </div>
            <div class="code-output-wrap">
              <pre class="code-output-pre" id="code-output"><span style="color:#475569;">Output akan muncul di sini...</span></pre>
            </div>
          </div>
        </div>

        <!-- Stats bar -->
        <div class="code-stats">
          <?php
          $statDefs = [
            ['0', 'Karakter input',  'stat-in-chars'],
            ['0', 'Baris input',     'stat-in-lines'],
            ['0', 'Karakter output', 'stat-out-chars'],
            ['0', 'Baris output',    'stat-out-lines'],
          ];
          foreach ($statDefs as [$v, $l, $id]): ?>
            <div class="cs-item">
              <span class="cv" id="<?= $id ?>"><?= $v ?></span>
              <span class="cl"><?= $l ?></span>
            </div>
          <?php endforeach; ?>
        </div>

      </form>

      <!-- Action bar -->
      <div class="action-bar">
        <button type="button" class="btn-primary btn-sm" onclick="copyOutput()">
          📋 Salin Output
        </button>
        <button type="button" class="btn-ghost btn-sm" onclick="downloadOutput()">
          ⬇ Download
        </button>
        <button type="button" class="btn-secondary btn-sm" onclick="submitServer()">
          ⚙ Beautify via Server (PHP)
        </button>
        <span class="sep"></span>
        <button type="button" class="btn-ghost btn-sm" onclick="loadSample()">
          📄 Contoh
        </button>
      </div>
    </div><!-- /.panel -->

    <!-- Hasil server -->
    <?php if ($server_result && !$server_error): ?>
    <?php
      $inStats  = codeStats($post_code);
      $outStats = codeStats($server_result);
      $diff     = $outStats['chars'] - $inStats['chars'];
      $diffPct  = $inStats['chars'] > 0 ? round(abs($diff) / $inStats['chars'] * 100, 1) : 0;
      $diffCls  = $diff < 0 ? 'smaller' : ($diff > 0 ? 'bigger' : 'same');
      $diffLbl  = $diff < 0 ? "↓ {$diffPct}% lebih kecil" : ($diff > 0 ? "↑ {$diffPct}% lebih besar" : "Ukuran sama");
    ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>
        Beautify berhasil via PHP server.
        <span class="diff-badge <?= $diffCls ?>"><?= $diffLbl ?></span>
      </span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title">⚙ Hasil Server PHP — <?= strtoupper($post_lang) ?></div>
      <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; margin-bottom:.75rem;">
        <span class="badge">Input: <?= $inStats['lines'] ?> baris · <?= number_format($inStats['chars']) ?> char</span>
        <span>→</span>
        <span class="badge accent">Output: <?= $outStats['lines'] ?> baris · <?= number_format($outStats['chars']) ?> char</span>
      </div>
      <div class="copy-wrap">
        <pre style="background:#0f172a; border:1px solid #1e293b; border-radius:var(--radius-sm); padding:1rem; font-family:var(--font-mono); font-size:.78rem; color:#e2e8f0; max-height:360px; overflow:auto; white-space:pre; margin:0;" id="server-code-out"><?= e($server_result) ?></pre>
        <button class="copy-btn" data-copy-target="server-code-out" style="top:.5rem;">SALIN</button>
      </div>
      <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.85rem;">
        <button class="btn-primary btn-sm" onclick="downloadServerOutput()">⬇ Download</button>
        <button class="btn-ghost btn-sm" onclick="useServerAsInput()">→ Gunakan sebagai input</button>
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
      <div class="panel-title">💡 Tips per Bahasa</div>
      <div style="display:flex; flex-direction:column; gap:.75rem; font-size:.8rem;">
        <?php
        $tips = [
          ['json','#f59e0b', 'JSON', 'Validasi otomatis — jika error muncul, cek koma berlebih atau tanda kutip yang salah.'],
          ['html','#2563eb', 'HTML', 'Tag block (div, p, section) di-indent, tag inline (span, a, em) dibiarkan.'],
          ['css', '#0ea5e9', 'CSS',  'Setiap properti diformat satu baris. Selector pada baris tersendiri.'],
          ['js',  '#7c3aed', 'JS',   'Indentasi berbasis { } — untuk minified JS, beautifier akan memecah baris.'],
          ['php', '#10b981', 'PHP',  'Menggunakan token_get_all PHP. Pastikan sintaks valid. Tag <?php opsional.'],
          ['sql', '#ef4444', 'SQL',  'Keyword diubah UPPERCASE. Setiap klausa (SELECT, FROM, WHERE) pada baris baru.'],
        ];
        foreach ($tips as [$lang, $color, $name, $tip]): ?>
          <div style="padding:.5rem .6rem; border:1px solid var(--border); border-left:3px solid <?= $color ?>; border-radius:var(--radius-sm); background:var(--bg);"
            onclick="setLang('<?= $lang ?>')" style="cursor:pointer;">
            <div style="font-weight:700; font-size:.75rem; color:<?= $color ?>; margin-bottom:.2rem; cursor:pointer;"
              onclick="setLang('<?= $lang ?>')"><?= $name ?></div>
            <div style="color:var(--muted); font-size:.72rem; line-height:1.5;"><?= $tip ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Contoh Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.4rem;">
        <?php
        $samples = [
          ['JSON minified', 'json'],
          ['HTML tanpa indent', 'html'],
          ['CSS satu baris', 'css'],
          ['JS minified', 'js'],
          ['Query SQL panjang', 'sql'],
        ];
        foreach ($samples as [$label, $lang]): ?>
          <button class="btn-ghost btn-sm btn-full"
            style="text-align:left; display:flex; justify-content:space-between;"
            onclick="loadSampleLang('<?= $lang ?>')">
            <span><?= e($label) ?></span>
            <span style="font-family:var(--font-mono); font-size:.68rem; color:var(--muted);">.<?= $lang ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/json-formatter"  class="btn-ghost btn-sm btn-full">JSON Formatter</a>
        <a href="/tools/html-minifier"   class="btn-ghost btn-sm btn-full">HTML Minifier</a>
        <a href="/tools/css-minifier"    class="btn-ghost btn-sm btn-full">CSS Minifier</a>
        <a href="/tools/js-minifier"     class="btn-ghost btn-sm btn-full">JS Minifier</a>
        <a href="/tools/regex-tester"    class="btn-ghost btn-sm btn-full">Regex Tester</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Code Beautifier — logika JS (realtime)
   Menggunakan js-beautify dari CDN.
   PHP server untuk validasi ketat (JSON, PHP).
   ────────────────────────────────────────── */

// Load js-beautify
(function() {
  const s = document.createElement('script');
  s.src   = 'https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify.min.js';
  s.onload = () => {
    const s2 = document.createElement('script');
    s2.src   = 'https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify-css.min.js';
    s2.onload = () => {
      const s3 = document.createElement('script');
      s3.src   = 'https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify-html.min.js';
      s3.onload = () => { window.JS_BEAUTIFY_READY = true; beautifyJS(); };
      document.head.appendChild(s3);
    };
    document.head.appendChild(s2);
  };
  document.head.appendChild(s);
})();

// ── State ─────────────────────────────────────────────────────
let currentLang   = '<?= $post_lang ?>';
let currentIndent = <?= $post_indent ?>;
let currentOutput = '';

// ── Lang switch ───────────────────────────────────────────────
function setLang(lang) {
  currentLang = lang;
  document.getElementById('lang-input').value = lang;
  document.querySelectorAll('.lang-tab').forEach(t =>
    t.classList.toggle('active', t.dataset.lang === lang));
  document.getElementById('input-pane-title').textContent  = 'INPUT — ' + lang.toUpperCase();
  document.getElementById('output-pane-title').textContent = 'OUTPUT — BEAUTIFIED';

  const extMap = {json:'.json',html:'.html',css:'.css',js:'.js',php:'.php',sql:'.sql'};
  document.getElementById('file-upload').accept = extMap[lang] || '';

  const ph = {
    json: '{"key":"value","array":[1,2,3]}',
    html: '<div><p>Hello <strong>World</strong></p></div>',
    css:  '.card{background:#fff;padding:1rem;border-radius:8px}',
    js:   'function hello(name){return "Hello "+name;}',
    php:  '<?php function add($a,$b){return $a+$b;} echo add(1,2);',
    sql:  'select id,name from users where active=1 order by name',
  };
  document.getElementById('code-input').placeholder = ph[lang] || 'Tempel kode di sini...';
  beautifyJS();
}

// ── Indent switch ─────────────────────────────────────────────
function setIndent(n) {
  currentIndent = n;
  document.getElementById('indent-input').value = n;
  document.querySelectorAll('.indent-opt').forEach(b =>
    b.classList.toggle('active', parseInt(b.dataset.indent) === n));
  beautifyJS();
}

// ── JS-side beautify ─────────────────────────────────────────
function beautifyJS() {
  const code = document.getElementById('code-input').value;
  const out  = document.getElementById('code-output');

  if (!code.trim()) {
    out.innerHTML = '<span style="color:#475569;">Output akan muncul di sini...</span>';
    currentOutput = '';
    updateStats();
    updateDiff(0, 0);
    return;
  }

  if (!window.JS_BEAUTIFY_READY) {
    out.innerHTML = '<span style="color:#475569;">Memuat library...</span>';
    return;
  }

  try {
    const opts = {
      indent_size: currentIndent,
      indent_char: ' ',
      max_preserve_newlines: 2,
      preserve_newlines: true,
      keep_array_indentation: false,
      break_chained_methods: false,
      brace_style: 'collapse',
      space_before_conditional: true,
      unescape_strings: false,
      jslint_happy: false,
      end_with_newline: false,
      wrap_line_length: 0,
    };

    let beautified = '';
    switch (currentLang) {
      case 'json':
        try {
          const parsed = JSON.parse(code);
          beautified = JSON.stringify(parsed, null, currentIndent);
        } catch(e) {
          out.innerHTML = `<span style="color:#f87171;">✕ JSON tidak valid: ${esc(e.message)}</span>`;
          currentOutput = ''; updateStats(); return;
        }
        break;
      case 'html':
        beautified = html_beautify(code, { ...opts, indent_inner_html: true });
        break;
      case 'css':
        beautified = css_beautify(code, opts);
        break;
      case 'js':
        beautified = js_beautify(code, opts);
        break;
      case 'php':
        // PHP: use JS beautify as fallback (treat like JS after <?php)
        let phpCode = code;
        let hasTag   = phpCode.trimStart().startsWith('<?');
        if (!hasTag) phpCode = '<?php\n' + phpCode;
        beautified = js_beautify(phpCode, opts);
        if (!hasTag) beautified = beautified.replace(/^<\?php\n/, '');
        break;
      case 'sql':
        // Basic SQL formatting in JS
        beautified = formatSQLjs(code);
        break;
      default:
        beautified = js_beautify(code, opts);
    }

    currentOutput = beautified;
    out.innerHTML = syntaxHighlight(beautified, currentLang);
    updateStats(code, beautified);
    updateDiff(code.length, beautified.length);
  } catch(e) {
    out.innerHTML = `<span style="color:#f87171;">Error: ${esc(e.message)}</span>`;
    currentOutput = '';
    updateStats();
  }
}

// ── SQL formatter JS ──────────────────────────────────────────
function formatSQLjs(sql) {
  const keywords = ['SELECT','FROM','WHERE','JOIN','LEFT JOIN','RIGHT JOIN','INNER JOIN',
                    'ON','AND','OR','GROUP BY','ORDER BY','HAVING','LIMIT','OFFSET',
                    'INSERT INTO','VALUES','UPDATE','SET','DELETE FROM','UNION'];
  let result = sql.replace(/\s+/g, ' ').trim();
  result = result.toUpperCase();

  // Naive clause formatting
  for (const kw of keywords) {
    result = result.replace(new RegExp('\\s+(' + kw.replace(/ /g,'\\s+') + ')\\s+', 'gi'),
                            '\n' + kw + '\n  ');
  }
  // Lowercase string literals back (we lost them by uppercasing)
  return result.trim();
}

// ── Syntax highlight ──────────────────────────────────────────
function syntaxHighlight(code, lang) {
  const e = (s) => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  if (lang === 'json') {
    return e(code).replace(
      /("(?:[^"\\]|\\.)*")(\s*:)?|(\btrue\b|\bfalse\b|\bnull\b)|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)/g,
      (m, str, colon, bool, num) => {
        if (bool || num) return `<span class="hl-${bool?'bool':'num'}">${m}</span>`;
        if (str && colon) return `<span class="hl-key">${str}</span>${colon}`;
        if (str)          return `<span class="hl-str">${str}</span>`;
        return m;
      }
    );
  }

  if (lang === 'css') {
    return e(code).replace(
      /(\/\*[\s\S]*?\*\/)|([^{};\n]+)(?=\s*\{)|([\w-]+)(\s*:\s*)([^;}\n]+)/g,
      (m, cmt, sel, prop, colon, val) => {
        if (cmt)  return `<span class="hl-cmt">${cmt}</span>`;
        if (sel)  return `<span class="hl-sel">${e(sel)}</span>`;
        if (prop) return `<span class="hl-prop">${e(prop)}</span>${colon}<span class="hl-csval">${e(val)}</span>`;
        return m;
      }
    );
  }

  if (lang === 'html') {
    return e(code).replace(
      /(&lt;\/?)([\w-]+)((?:\s+[\w-]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+))?)*\s*\/?)(&gt;)|(<!--[\s\S]*?-->)/g,
      (m, open, tag, attrs, close, cmt) => {
        if (cmt) return `<span class="hl-cmt">${cmt}</span>`;
        const attrHl = attrs ? attrs.replace(/([\w-]+)(\s*=\s*)("(?:[^"]*)")/g,
          (_,a,eq,v) => `<span class="hl-attr">${a}</span>${eq}<span class="hl-val">${v}</span>`) : '';
        return `${open}<span class="hl-tag">${tag}</span>${attrHl}${close}`;
      }
    );
  }

  if (lang === 'sql') {
    const kws = /\b(SELECT|FROM|WHERE|JOIN|LEFT|RIGHT|INNER|ON|AND|OR|GROUP|BY|ORDER|HAVING|LIMIT|OFFSET|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|TABLE|ALTER|DROP|INDEX|UNION|AS|DISTINCT|COUNT|SUM|AVG|MIN|MAX|NOT|NULL|IS|IN|BETWEEN|LIKE|EXISTS|CASE|WHEN|THEN|ELSE|END|ASC|DESC)\b/gi;
    return e(code).replace(kws, m => `<span class="hl-sqlkw">${m.toUpperCase()}</span>`)
                  .replace(/("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')/g, m => `<span class="hl-str">${m}</span>`)
                  .replace(/\b(\d+)\b/g, m => `<span class="hl-num">${m}</span>`);
  }

  if (lang === 'js' || lang === 'php') {
    const jsKws = /\b(function|return|var|let|const|if|else|for|while|do|switch|case|break|continue|class|new|this|typeof|instanceof|import|export|default|async|await|yield|try|catch|finally|throw|delete|void|in|of|true|false|null|undefined|echo|print|isset|empty|array|string|int|float|bool|public|private|protected|static|abstract|extends|implements|interface|namespace|use|require|include)\b/g;
    return e(code)
      .replace(/(\/\/[^\n]*)|(\/\*[\s\S]*?\*\/)/g, m => `<span class="hl-cmt">${m}</span>`)
      .replace(/(["'`])(?:[^\\]|\\.)*?\1/g, m => `<span class="hl-str">${m}</span>`)
      .replace(jsKws, m => `<span class="hl-kw">${m}</span>`)
      .replace(/\b(\d+(?:\.\d+)?)\b/g, m => `<span class="hl-num">${m}</span>`)
      .replace(/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*(?=\()/g, m => `<span class="hl-fn">${m}</span>`);
  }

  return e(code);
}

// ── Stats & diff ──────────────────────────────────────────────
function updateStats(input, output) {
  const inp = input || document.getElementById('code-input').value;
  const out = output || currentOutput;
  const inLines  = inp ? inp.split('\n').length : 0;
  const outLines = out ? out.split('\n').length : 0;
  document.getElementById('stat-in-chars').textContent  = inp.length.toLocaleString('id');
  document.getElementById('stat-in-lines').textContent  = inLines.toLocaleString('id');
  document.getElementById('stat-out-chars').textContent = out.length.toLocaleString('id');
  document.getElementById('stat-out-lines').textContent = outLines.toLocaleString('id');
}

function updateDiff(inLen, outLen) {
  const badge = document.getElementById('size-diff');
  if (!inLen || !outLen) { badge.style.display = 'none'; return; }
  const diff = outLen - inLen;
  const pct  = Math.round(Math.abs(diff) / inLen * 100);
  badge.style.display = 'inline-flex';
  if (diff < 0) {
    badge.className = 'diff-badge smaller';
    badge.textContent = '↓ ' + pct + '% lebih kecil';
  } else if (diff > 0) {
    badge.className = 'diff-badge bigger';
    badge.textContent = '↑ ' + pct + '% lebih besar';
  } else {
    badge.className = 'diff-badge same';
    badge.textContent = 'Ukuran sama';
  }
}

// ── Copy & Download ───────────────────────────────────────────
function copyOutput() {
  if (!currentOutput) { showToast && showToast('Belum ada output!', 'warning'); return; }
  navigator.clipboard.writeText(currentOutput).then(() =>
    showToast && showToast('Kode disalin!', 'success'));
}

function downloadOutput() {
  if (!currentOutput) { showToast && showToast('Belum ada output!', 'warning'); return; }
  const extMap = {json:'.json',html:'.html',css:'.css',js:'.js',php:'.php',sql:'.sql'};
  const blob = new Blob([currentOutput], { type: 'text/plain;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'beautified' + (extMap[currentLang] || '.txt');
  a.click();
}

function downloadServerOutput() {
  const el = document.getElementById('server-code-out');
  if (!el) return;
  const extMap = {json:'.json',html:'.html',css:'.css',js:'.js',php:'.php',sql:'.sql'};
  const blob = new Blob([el.textContent], { type: 'text/plain;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'beautified-server' + (extMap[currentLang] || '.txt');
  a.click();
}

function useAsInput() {
  if (!currentOutput) return;
  document.getElementById('code-input').value = currentOutput;
  beautifyJS(); updateStats();
}

function useServerAsInput() {
  const el = document.getElementById('server-code-out');
  if (!el) return;
  document.getElementById('code-input').value = el.textContent;
  beautifyJS(); updateStats();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function copyServerOutput() {
  const el = document.getElementById('server-code-out');
  if (el) navigator.clipboard.writeText(el.textContent).then(() =>
    showToast && showToast('Disalin!', 'success'));
}

// ── Server submit ─────────────────────────────────────────────
function submitServer() {
  document.getElementById('lang-input').value = currentLang;
  document.getElementById('indent-input').value = currentIndent;
  document.getElementById('beauty-form').method = 'POST';
  document.getElementById('beauty-form').submit();
}

// ── Clipboard & file ──────────────────────────────────────────
async function pasteClipboard() {
  try {
    const text = await navigator.clipboard.readText();
    document.getElementById('code-input').value = text;
    beautifyJS(); updateStats();
  } catch { showToast && showToast('Izin clipboard ditolak.', 'warning'); }
}

function loadFile(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('code-input').value = e.target.result;
    beautifyJS(); updateStats();
    showToast && showToast('File dimuat!', 'success');
  };
  reader.readAsText(file);
  input.value = '';
}

function clearInput() {
  document.getElementById('code-input').value = '';
  document.getElementById('code-output').innerHTML = '<span style="color:#475569;">Output akan muncul di sini...</span>';
  currentOutput = '';
  updateStats('', '');
  updateDiff(0, 0);
  document.getElementById('size-diff').style.display = 'none';
}

// ── Samples ───────────────────────────────────────────────────
const SAMPLES = {
  json: `{"name":"Multi Tools","version":"1.0.0","features":["beautifier","minifier","converter"],"config":{"debug":false,"port":3000,"db":{"host":"localhost","port":5432,"name":"multitools"}}}`,
  html: `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Halaman</title></head><body><div class="container"><header><h1>Judul</h1><nav><ul><li><a href="/">Beranda</a></li><li><a href="/tentang">Tentang</a></li></ul></nav></header><main><p>Konten halaman di sini.</p></main></div></body></html>`,
  css:  `.container{max-width:1200px;margin:0 auto;padding:0 1.5rem}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;background:#2563eb;color:#fff;border:none;border-radius:.375rem;font-weight:600;cursor:pointer;transition:all .2s}.btn:hover{background:#1d4ed8;transform:translateY(-2px)}.btn:disabled{opacity:.5;cursor:not-allowed}@media(max-width:768px){.container{padding:0 1rem}.btn{width:100%}}`,
  js:   `const API_URL='https://api.example.com/v1';async function fetchData(endpoint,options={}){try{const response=await fetch(API_URL+endpoint,{headers:{'Content-Type':'application/json','Authorization':'Bearer '+getToken(),...options.headers},...options});if(!response.ok){throw new Error('HTTP '+response.status)}return await response.json()}catch(error){console.error('Fetch error:',error);throw error}}function getToken(){return localStorage.getItem('auth_token')||''}`,
  sql:  `select u.id,u.name,u.email,count(o.id) as total_orders,sum(o.total_amount) as total_spent from users u left join orders o on u.id=o.user_id where u.created_at >= '2024-01-01' and u.status = 'active' group by u.id,u.name,u.email having count(o.id) > 0 order by total_spent desc limit 50`,
  php:  `<?php class UserRepository {private PDO $db;public function __construct(PDO $db){$this->db=$db;}public function findById(int $id):?array{$stmt=$this->db->prepare('SELECT * FROM users WHERE id = ?');$stmt->execute([$id]);return $stmt->fetch(PDO::FETCH_ASSOC)?:null;}public function findAll(int $limit=50):array{$stmt=$this->db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit");return $stmt->fetchAll(PDO::FETCH_ASSOC);}}`,
};

function loadSample() {
  const sample = SAMPLES[currentLang] || SAMPLES.json;
  document.getElementById('code-input').value = sample;
  beautifyJS(); updateStats();
}

function loadSampleLang(lang) {
  setLang(lang);
  document.getElementById('code-input').value = SAMPLES[lang] || '';
  beautifyJS(); updateStats();
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Tab key in textarea ───────────────────────────────────────
document.getElementById('code-input').addEventListener('keydown', function(e) {
  if (e.key === 'Tab') {
    e.preventDefault();
    const start = this.selectionStart;
    const end   = this.selectionEnd;
    const spaces = ' '.repeat(currentIndent);
    this.value = this.value.slice(0, start) + spaces + this.value.slice(end);
    this.selectionStart = this.selectionEnd = start + currentIndent;
    beautifyJS(); updateStats();
  }
});

// ── Init ──────────────────────────────────────────────────────
setLang(currentLang);
setIndent(currentIndent);
<?php if ($post_code): ?>
setTimeout(beautifyJS, 100);
<?php endif; ?>
updateStats('', '');
</script>

<?php require '../../includes/footer.php'; ?>