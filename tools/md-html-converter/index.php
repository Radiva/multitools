<?php
require '../../includes/config.php';
/**
 * Multi Tools — Markdown to HTML Converter
 * Konversi Markdown ke HTML dengan fitur lengkap:
 * preview realtime, syntax highlight, tabel, dan download.
 * ============================================================ */

// ── Markdown Parser PHP (tanpa library eksternal) ─────────────

class MarkdownParser {

  private array $opts;

  public function __construct(array $opts = []) {
    $this->opts = array_merge([
      'tables'        => true,
      'fenced_code'   => true,
      'task_lists'    => true,
      'autolinks'     => true,
      'strikethrough' => true,
      'footnotes'     => false,
      'toc'           => false,
      'sanitize'      => true,
    ], $opts);
  }

  public function parse(string $md): string {
    // Normalize line endings
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    // Protect code blocks from processing
    [$md, $codeBlocks] = $this->extractCodeBlocks($md);

    // Block-level processing
    $html = $this->processBlocks($md);

    // Restore code blocks
    foreach ($codeBlocks as $key => $block) {
      $html = str_replace($key, $block, $html);
    }

    return $html;
  }

  // ── Code block extraction ──────────────────────────────────
  private function extractCodeBlocks(string $md): array {
    $blocks = [];
    $counter = 0;

    // Fenced code blocks ```lang ... ```
    if ($this->opts['fenced_code']) {
      $md = preg_replace_callback(
        '/^(`{3,}|~{3,})([\w\-+#.]*)\n(.*?)^\1\s*$/ms',
        function($m) use (&$blocks, &$counter) {
          $lang = htmlspecialchars(trim($m[2]));
          $code = htmlspecialchars($m[3]);
          $key  = 'CODEBLOCK' . $counter++ . 'CODEBLOCK';
          $langAttr = $lang ? ' class="language-' . $lang . '"' : '';
          $langBadge = $lang ? '<span class="code-lang">' . $lang . '</span>' : '';
          $blocks[$key] = '<div class="code-wrap">' . $langBadge
            . '<pre><code' . $langAttr . '>' . $code . '</code></pre></div>';
          return $key . "\n";
        },
        $md
      );
    }

    // Indented code blocks (4 spaces or 1 tab)
    $md = preg_replace_callback(
      '/^((?:(?:    |\t)[^\n]*\n?)+)/m',
      function($m) use (&$blocks, &$counter) {
        $code = preg_replace('/^(?:    |\t)/m', '', $m[1]);
        $key  = 'CODEBLOCK' . $counter++ . 'CODEBLOCK';
        $blocks[$key] = '<pre><code>' . htmlspecialchars(rtrim($code)) . '</code></pre>';
        return $key . "\n";
      },
      $md
    );

    // Inline code `...`
    $md = preg_replace_callback(
      '/`([^`\n]+)`/',
      function($m) use (&$blocks, &$counter) {
        $key = 'CODEBLOCK' . $counter++ . 'CODEBLOCK';
        $blocks[$key] = '<code>' . htmlspecialchars($m[1]) . '</code>';
        return $key;
      },
      $md
    );

    return [$md, $blocks];
  }

  // ── Block processing ───────────────────────────────────────
  private function processBlocks(string $md): string {
    $lines  = explode("\n", $md);
    $output = '';
    $i      = 0;
    $n      = count($lines);

    while ($i < $n) {
      $line = $lines[$i];

      // Blank line
      if (trim($line) === '') { $output .= "\n"; $i++; continue; }

      // Setext headings (=== or ---)
      if ($i + 1 < $n) {
        $next = $lines[$i + 1];
        if (preg_match('/^=+\s*$/', $next)) {
          $output .= '<h1>' . $this->parseInline($line) . "</h1>\n";
          $i += 2; continue;
        }
        if (preg_match('/^-+\s*$/', $next) && strlen(trim($line)) > 0) {
          $output .= '<h2>' . $this->parseInline($line) . "</h2>\n";
          $i += 2; continue;
        }
      }

      // ATX headings # ## ### etc
      if (preg_match('/^(#{1,6})\s+(.+?)(?:\s+#+\s*)?$/', $line, $m)) {
        $level = strlen($m[1]);
        $text  = $this->parseInline(trim($m[2]));
        $slug  = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', strip_tags($text))));
        $output .= "<h{$level} id=\"{$slug}\">{$text}</h{$level}>\n";
        $i++; continue;
      }

      // Horizontal rule --- *** ___
      if (preg_match('/^(?:---+|\*\*\*+|___+)\s*$/', $line)) {
        $output .= "<hr>\n"; $i++; continue;
      }

      // Blockquote
      if (str_starts_with($line, '>')) {
        $bqLines = [];
        while ($i < $n && (str_starts_with($lines[$i], '>') || trim($lines[$i]) === '')) {
          $bqLines[] = preg_replace('/^>\s?/', '', $lines[$i]);
          $i++;
        }
        $inner = $this->parse(implode("\n", $bqLines));
        $output .= "<blockquote>\n{$inner}</blockquote>\n";
        continue;
      }

      // Tables
      if ($this->opts['tables'] && strpos($line, '|') !== false && $i + 1 < $n && preg_match('/^\|?[\s:*-]+\|/', $lines[$i + 1])) {
        [$table, $skip] = $this->parseTable($lines, $i);
        $output .= $table; $i += $skip; continue;
      }

      // Unordered list
      if (preg_match('/^[-*+]\s/', $line)) {
        [$list, $skip] = $this->parseList($lines, $i, 'ul');
        $output .= $list; $i += $skip; continue;
      }

      // Ordered list
      if (preg_match('/^\d+\.\s/', $line)) {
        [$list, $skip] = $this->parseList($lines, $i, 'ol');
        $output .= $list; $i += $skip; continue;
      }

      // Paragraph
      $paraLines = [];
      while ($i < $n) {
        $l = $lines[$i];
        if (trim($l) === '' || str_starts_with($l, '#') || str_starts_with($l, '>') ||
            preg_match('/^(?:[-*+]|\d+\.)\s/', $l) ||
            preg_match('/^(?:---+|\*\*\*+|___+)\s*$/', $l) ||
            (strpos($l, 'CODEBLOCK') !== false && strpos($l, 'CODEBLOCK') === 0)) {
          break;
        }
        $paraLines[] = $l;
        $i++;
      }
      if (!empty($paraLines)) {
        $text = implode("\n", $paraLines);
        $text = preg_replace('/  \n/', "<br>\n", $text);
        $output .= '<p>' . $this->parseInline($text) . "</p>\n";
      }
    }

    return $output;
  }

  // ── List parsing ──────────────────────────────────────────
  private function parseList(array $lines, int $start, string $type): array {
    $tag     = $type === 'ul' ? 'ul' : 'ol';
    $pattern = $type === 'ul' ? '/^[-*+]\s+/' : '/^\d+\.\s+/';
    $html    = "<{$tag}>\n";
    $i       = $start;
    $n       = count($lines);

    while ($i < $n) {
      $line = $lines[$i];
      if (!preg_match($pattern, $line)) break;

      $itemText = preg_replace($pattern, '', $line);

      // Task list
      if ($this->opts['task_lists'] && preg_match('/^\[(x| )\]\s/', $itemText)) {
        $checked   = $itemText[1] === 'x';
        $itemText  = substr($itemText, 4);
        $chkAttr   = $checked ? ' checked' : '';
        $html .= '<li class="task-item"><input type="checkbox" disabled' . $chkAttr . '> '
               . $this->parseInline($itemText) . "</li>\n";
      } else {
        $html .= '<li>' . $this->parseInline($itemText) . "</li>\n";
      }
      $i++;
    }

    $html .= "</{$tag}>\n";
    return [$html, $i - $start];
  }

  // ── Table parsing ─────────────────────────────────────────
  private function parseTable(array $lines, int $start): array {
    $i        = $start;
    $headers  = $this->splitTableRow($lines[$i]);
    $i++;
    $aligns   = $this->parseAlignRow($lines[$i]);
    $i++;

    $html = '<div class="table-wrap"><table><thead><tr>';
    foreach ($headers as $j => $h) {
      $align = $aligns[$j] ?? '';
      $style = $align ? ' style="text-align:' . $align . '"' : '';
      $html .= '<th' . $style . '>' . $this->parseInline(trim($h)) . '</th>';
    }
    $html .= "</tr></thead>\n<tbody>\n";

    while ($i < count($lines) && strpos($lines[$i], '|') !== false) {
      $cells = $this->splitTableRow($lines[$i]);
      $html .= '<tr>';
      foreach ($cells as $j => $cell) {
        $align = $aligns[$j] ?? '';
        $style = $align ? ' style="text-align:' . $align . '"' : '';
        $html .= '<td' . $style . '>' . $this->parseInline(trim($cell)) . '</td>';
      }
      $html .= "</tr>\n";
      $i++;
    }

    $html .= "</tbody></table></div>\n";
    return [$html, $i - $start];
  }

  private function splitTableRow(string $row): array {
    $row = trim($row, '| ');
    return array_map('trim', explode('|', $row));
  }

  private function parseAlignRow(string $row): array {
    $cells  = $this->splitTableRow($row);
    $aligns = [];
    foreach ($cells as $cell) {
      $cell = trim($cell);
      if (str_starts_with($cell, ':') && str_ends_with($cell, ':')) $aligns[] = 'center';
      elseif (str_ends_with($cell, ':'))  $aligns[] = 'right';
      elseif (str_starts_with($cell, ':')) $aligns[] = 'left';
      else $aligns[] = '';
    }
    return $aligns;
  }

  // ── Inline parsing ────────────────────────────────────────
  private function parseInline(string $text): string {
    // Bold + italic ***text*** or ___text___
    $text = preg_replace('/(\*\*\*|___)(.*?)\1/', '<strong><em>$2</em></strong>', $text);
    // Bold **text** or __text__
    $text = preg_replace('/(\*\*|__)(.*?)\1/', '<strong>$2</strong>', $text);
    // Italic *text* or _text_
    $text = preg_replace('/(\*|_)(.*?)\1/', '<em>$2</em>', $text);
    // Strikethrough ~~text~~
    if ($this->opts['strikethrough']) {
      $text = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text);
    }
    // Highlight ==text==
    $text = preg_replace('/==(.*?)==/', '<mark>$1</mark>', $text);
    // Links [text](url "title")
    $text = preg_replace_callback(
      '/\[([^\]]+)\]\(([^)"\s]+)(?:\s+"([^"]+)")?\)/',
      function($m) {
        $title = isset($m[3]) ? ' title="' . htmlspecialchars($m[3]) . '"' : '';
        $href  = htmlspecialchars($m[2]);
        $isExt = preg_match('/^https?:\/\//', $m[2]);
        $rel   = $isExt ? ' rel="noopener noreferrer" target="_blank"' : '';
        return '<a href="' . $href . '"' . $title . $rel . '>' . $m[1] . '</a>';
      },
      $text
    );
    // Images ![alt](src "title")
    $text = preg_replace_callback(
      '/!\[([^\]]*)\]\(([^)"\s]+)(?:\s+"([^"]+)")?\)/',
      function($m) {
        $alt   = htmlspecialchars($m[1]);
        $src   = htmlspecialchars($m[2]);
        $title = isset($m[3]) ? ' title="' . htmlspecialchars($m[3]) . '"' : '';
        return '<img src="' . $src . '" alt="' . $alt . '"' . $title . '>';
      },
      $text
    );
    // Autolinks <url> or plain urls
    if ($this->opts['autolinks']) {
      $text = preg_replace('/<(https?:\/\/[^>]+)>/', '<a href="$1" rel="noopener noreferrer" target="_blank">$1</a>', $text);
    }

    return $text;
  }
}

// ── Handle POST ──────────────────────────────────────────────
$server_result  = '';
$server_error   = '';
$post_markdown  = '';
$post_mode      = 'convert'; // convert | sanitize
$post_opts      = [
  'tables'        => true,
  'fenced_code'   => true,
  'task_lists'    => true,
  'autolinks'     => true,
  'strikethrough' => true,
  'sanitize'      => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_markdown = $_POST['markdown'] ?? '';
  $post_opts = [
    'tables'        => isset($_POST['opt_tables']),
    'fenced_code'   => isset($_POST['opt_fenced_code']),
    'task_lists'    => isset($_POST['opt_task_lists']),
    'autolinks'     => isset($_POST['opt_autolinks']),
    'strikethrough' => isset($_POST['opt_strikethrough']),
    'sanitize'      => isset($_POST['opt_sanitize']),
  ];

  if (trim($post_markdown) === '') {
    $server_error = 'Konten Markdown tidak boleh kosong.';
  } else {
    $parser       = new MarkdownParser($post_opts);
    $server_result = $parser->parse($post_markdown);
  }
}

// ── Hitung statistik markdown ─────────────────────────────────
function mdStats(string $md): array {
  $lines    = explode("\n", $md);
  $words    = str_word_count(strip_tags($md));
  $headings = preg_match_all('/^#{1,6}\s/m', $md);
  $links    = preg_match_all('/\[.*?\]\(.*?\)/', $md);
  $images   = preg_match_all('/!\[.*?\]\(.*?\)/', $md);
  $tables   = preg_match_all('/^\|.*\|/m', $md);
  $codeBlks = preg_match_all('/^```/m', $md) / 2;
  return [
    'chars'    => strlen($md),
    'words'    => $words,
    'lines'    => count($lines),
    'headings' => $headings,
    'links'    => $links,
    'images'   => $images,
    'tables'   => (int)floor($tables / 2),
    'code'     => (int)$codeBlks,
  ];
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Markdown to HTML Converter Online — Konversi MD ke HTML | Multi Tools',
  'description' => 'Konversi Markdown ke HTML secara instan dengan preview realtime. Mendukung tabel, code block, task list, syntax highlight, dan download hasilnya. Gratis, tanpa login.',
  'keywords'    => 'markdown to html, md to html, konversi markdown, markdown converter, preview markdown, markdown editor, multi tools',
  'og_title'    => 'Markdown to HTML Converter Online',
  'og_desc'     => 'Konversi Markdown ke HTML realtime. Tabel, code block, task list, preview langsung.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Text Tools', 'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Markdown to HTML'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/markdown-to-html#webpage',
      'url'         => SITE_URL . '/tools/markdown-to-html',
      'name'        => 'Markdown to HTML Converter Online',
      'description' => 'Konversi Markdown ke HTML secara instan dengan preview realtime.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',           'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',         'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Markdown to HTML',  'item' => SITE_URL . '/tools/markdown-to-html'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Markdown to HTML Converter',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/markdown-to-html',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Split editor layout ── */
.md-editor-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  min-height: 480px;
}
@media (max-width: 768px) {
  .md-editor-layout { grid-template-columns: 1fr; }
}

.md-pane {
  display: flex; flex-direction: column;
}
.md-pane-left  { border-right: 1px solid var(--border); }
.md-pane-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .55rem .9rem;
  background: var(--bg); border-bottom: 1px solid var(--border);
  font-family: var(--font-mono); font-size: .72rem; font-weight: 700;
  color: var(--muted); text-transform: uppercase; letter-spacing: .07em;
  flex-shrink: 0;
}
.md-pane-header .pane-btns { display: flex; gap: .3rem; }
.pane-btn {
  padding: .2rem .5rem; border: 1px solid var(--border); border-radius: 4px;
  font-size: .68rem; font-family: var(--font-mono); font-weight: 700;
  background: var(--surface); color: var(--muted);
  cursor: pointer; transition: all var(--transition);
}
.pane-btn:hover { background: var(--accent3); color: #fff; border-color: var(--accent3); }

.md-textarea {
  flex: 1; width: 100%; resize: none;
  border: none !important; outline: none !important; box-shadow: none !important;
  border-radius: 0 !important; background: var(--surface) !important;
  font-family: var(--font-mono) !important; font-size: .85rem !important;
  line-height: 1.7; padding: 1rem !important;
  color: var(--text) !important;
  min-height: 400px;
}

/* ── Preview pane ── */
.md-preview {
  flex: 1; overflow-y: auto; padding: 1.1rem 1.25rem;
  background: var(--surface);
  font-family: var(--font-body);
  font-size: .9rem; line-height: 1.75; color: var(--text);
}

/* ── Preview typography ── */
.md-preview h1,
.md-preview h2,
.md-preview h3,
.md-preview h4,
.md-preview h5,
.md-preview h6 {
  font-weight: 800; margin: 1.25em 0 .5em; line-height: 1.25;
  color: var(--text); letter-spacing: -.02em;
}
.md-preview h1 { font-size: 1.7rem; border-bottom: 2px solid var(--border); padding-bottom: .35em; }
.md-preview h2 { font-size: 1.35rem; border-bottom: 1px solid var(--border); padding-bottom: .25em; }
.md-preview h3 { font-size: 1.1rem; }
.md-preview h4 { font-size: 1rem; }
.md-preview p  { margin: .75em 0; }
.md-preview ul,
.md-preview ol { margin: .5em 0 .5em 1.5em; }
.md-preview li { margin: .3em 0; }
.md-preview blockquote {
  border-left: 4px solid var(--accent3); margin: 1em 0;
  padding: .5em 1em; background: rgba(124,58,237,.05);
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  color: var(--muted); font-style: italic;
}
.md-preview blockquote p { margin: 0; }
.md-preview a { color: var(--accent); text-decoration: underline; }
.md-preview a:hover { color: var(--accent3); }
.md-preview hr { border: none; border-top: 2px solid var(--border); margin: 1.5em 0; }
.md-preview img { max-width: 100%; border-radius: var(--radius-sm); }
.md-preview code {
  font-family: var(--font-mono); font-size: .82rem;
  background: rgba(124,58,237,.08); color: var(--accent3);
  padding: .1em .35em; border-radius: 3px;
  border: 1px solid rgba(124,58,237,.15);
}
.md-preview strong { font-weight: 700; }
.md-preview em     { font-style: italic; }
.md-preview del    { text-decoration: line-through; color: var(--muted); }
.md-preview mark   { background: rgba(245,158,11,.25); padding: .05em .2em; border-radius: 2px; }
.md-preview pre {
  background: #0f172a; border-radius: var(--radius-sm);
  overflow-x: auto; margin: 1em 0; border: 1px solid #1e293b;
}
.md-preview pre code {
  background: none; color: #e2e8f0; border: none; padding: 0;
  font-size: .82rem; border-radius: 0;
}
.md-preview .code-wrap {
  position: relative; margin: 1em 0;
}
.md-preview .code-wrap pre { margin: 0; padding: 1rem 1.1rem; }
.md-preview .code-lang {
  position: absolute; top: .4rem; right: .4rem;
  font-family: var(--font-mono); font-size: .62rem; font-weight: 700;
  color: #94a3b8; background: rgba(255,255,255,.08);
  border-radius: 3px; padding: 1px 6px; pointer-events: none;
}
.md-preview .table-wrap { overflow-x: auto; margin: 1em 0; }
.md-preview table { width: 100%; border-collapse: collapse; font-size: .87rem; }
.md-preview th { background: rgba(124,58,237,.08); font-weight: 700; border: 1px solid var(--border); padding: .5rem .75rem; }
.md-preview td { border: 1px solid var(--border); padding: .45rem .75rem; }
.md-preview tr:nth-child(even) td { background: rgba(37,99,235,.03); }
.md-preview .task-item { list-style: none; margin-left: -1em; }
.md-preview .task-item input { margin-right: .4em; accent-color: var(--accent3); }

/* ── Stats bar ── */
.md-stats {
  display: flex; flex-wrap: wrap; gap: 0;
  border: 1px solid var(--border); border-top: none;
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  background: var(--bg); overflow: hidden;
}
.md-stat {
  padding: .45rem .85rem; display: flex; flex-direction: column;
  gap: .1rem; border-right: 1px solid var(--border);
  flex-shrink: 0;
}
.md-stat:last-child { border-right: none; }
.md-stat .sv { font-family: var(--font-mono); font-size: .82rem; font-weight: 800; color: var(--accent3); }
.md-stat .sl { font-size: .65rem; color: var(--muted); font-family: var(--font-mono); }

/* ── Toolbar buttons ── */
.md-toolbar {
  display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .75rem;
  padding: .5rem .75rem;
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.tb-btn {
  padding: .28rem .55rem;
  border: 1px solid var(--border); border-radius: 4px;
  font-size: .75rem; font-family: var(--font-mono); font-weight: 700;
  background: var(--surface); color: var(--muted);
  cursor: pointer; transition: all var(--transition);
  display: inline-flex; align-items: center; gap: .25rem;
}
.tb-btn:hover { background: var(--accent3); color: #fff; border-color: var(--accent3); }
.tb-sep { width: 1px; background: var(--border); align-self: stretch; flex-shrink: 0; margin: 0 .15rem; }

/* ── Output HTML area ── */
.html-output {
  background: #0f172a; border: 1px solid #1e293b;
  border-radius: var(--radius-sm); padding: 1rem 1.1rem;
  font-family: var(--font-mono); font-size: .78rem; color: #94a3b8;
  white-space: pre-wrap; word-break: break-all;
  max-height: 360px; overflow-y: auto; line-height: 1.65;
}

/* ── View toggle ── */
.view-toggle {
  display: flex; gap: 0;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  overflow: hidden; width: fit-content;
}
.view-btn {
  padding: .38rem .85rem; background: var(--bg); border: none;
  font-size: .78rem; font-weight: 600; color: var(--muted);
  cursor: pointer; transition: all var(--transition);
  font-family: var(--font-body);
}
.view-btn:not(:last-child) { border-right: 1px solid var(--border); }
.view-btn.active { background: var(--accent3); color: #fff; }

/* ── Opts grid ── */
.opts-row {
  display: flex; flex-wrap: wrap; gap: .4rem .75rem;
}
.opt-chk {
  display: flex; align-items: center; gap: .4rem;
  font-size: .8rem; font-weight: 400; color: var(--text); cursor: pointer;
}
.opt-chk input { width: auto !important; accent-color: var(--accent3); }
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
        <span aria-hidden="true">📝</span> Markdown → <span>HTML</span>
      </div>
      <p class="page-lead">
        Konversi Markdown ke HTML dengan preview realtime di samping editor.
        Mendukung tabel, code block, task list, dan semua sintaks Markdown standar.
      </p>

      <!-- Toolbar -->
      <div class="md-toolbar" id="md-toolbar">
        <!-- Format -->
        <button type="button" class="tb-btn" onclick="insertMD('**', '**')" title="Bold"><strong>B</strong></button>
        <button type="button" class="tb-btn" onclick="insertMD('*', '*')" title="Italic"><em>I</em></button>
        <button type="button" class="tb-btn" onclick="insertMD('~~', '~~')" title="Strikethrough"><del>S</del></button>
        <button type="button" class="tb-btn" onclick="insertMD('`', '`')" title="Inline code">` `</button>
        <button type="button" class="tb-btn" onclick="insertMD('==', '==')" title="Highlight">H</button>
        <div class="tb-sep"></div>
        <!-- Headings -->
        <button type="button" class="tb-btn" onclick="insertHeading(1)">H1</button>
        <button type="button" class="tb-btn" onclick="insertHeading(2)">H2</button>
        <button type="button" class="tb-btn" onclick="insertHeading(3)">H3</button>
        <div class="tb-sep"></div>
        <!-- Blocks -->
        <button type="button" class="tb-btn" onclick="insertLine('- ')" title="Unordered list">• List</button>
        <button type="button" class="tb-btn" onclick="insertLine('1. ')" title="Ordered list">1. List</button>
        <button type="button" class="tb-btn" onclick="insertLine('> ')" title="Blockquote">" Quote</button>
        <button type="button" class="tb-btn" onclick="insertCodeBlock()" title="Code block">{ } Code</button>
        <button type="button" class="tb-btn" onclick="insertTable()" title="Insert table">⊞ Table</button>
        <button type="button" class="tb-btn" onclick="insertLine('---')" title="Horizontal rule">─ HR</button>
        <div class="tb-sep"></div>
        <!-- Link & Image -->
        <button type="button" class="tb-btn" onclick="insertLink()" title="Link">🔗 Link</button>
        <button type="button" class="tb-btn" onclick="insertMD('![alt](', ')')" title="Image">🖼 Img</button>
        <button type="button" class="tb-btn" onclick="insertLine('- [ ] ')" title="Task item">☐ Task</button>
        <div class="tb-sep"></div>
        <!-- Actions -->
        <button type="button" class="tb-btn" onclick="loadSample()">📄 Contoh</button>
        <button type="button" class="tb-btn" onclick="clearEditor()">✕ Bersihkan</button>
      </div>

      <!-- Split editor -->
      <div class="md-editor-layout">
        <!-- Left: Editor -->
        <div class="md-pane md-pane-left">
          <div class="md-pane-header">
            <span>📝 Markdown</span>
            <div class="pane-btns">
              <button class="pane-btn" onclick="copyMarkdown()">Salin MD</button>
              <button class="pane-btn" onclick="document.getElementById('md-file-input').click()">Upload</button>
              <input type="file" id="md-file-input" accept=".md,.txt,.markdown" style="display:none;" onchange="loadFile(this)">
            </div>
          </div>
          <textarea class="md-textarea" id="md-editor"
            name="markdown"
            placeholder="Ketik atau tempel Markdown di sini...&#10;&#10;# Heading 1&#10;## Heading 2&#10;&#10;Teks **tebal**, *miring*, ~~coret~~&#10;&#10;- Item list&#10;- [ ] Task unchecked&#10;- [x] Task checked&#10;&#10;\`\`\`js&#10;console.log('Hello, World!');&#10;\`\`\`&#10;&#10;| Kolom A | Kolom B |&#10;|---------|---------|&#10;| Data 1  | Data 2  |"
            oninput="convertJS(); updateStats();"
            spellcheck="false"
          ><?= e($post_markdown) ?></textarea>
        </div>

        <!-- Right: Preview -->
        <div class="md-pane">
          <div class="md-pane-header">
            <span>👁 Preview HTML</span>
            <div class="pane-btns">
              <button class="pane-btn" id="btn-preview" onclick="setRightPane('preview')" style="background:var(--accent3);color:#fff;border-color:var(--accent3);">Preview</button>
              <button class="pane-btn" id="btn-html" onclick="setRightPane('html')">HTML</button>
            </div>
          </div>
          <div class="md-preview" id="md-preview-pane">
            <em style="color:var(--muted);">Preview akan muncul di sini...</em>
          </div>
          <div class="html-output" id="md-html-pane" style="display:none; border-radius:0; border:none; border-top:1px solid #1e293b; flex:1;"></div>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="md-stats" id="md-stats">
        <?php
        $statLabels = [
          ['0','Karakter','md-stat-chars'],
          ['0','Kata','md-stat-words'],
          ['0','Baris','md-stat-lines'],
          ['0','Heading','md-stat-headings'],
          ['0','Link','md-stat-links'],
          ['0','Tabel','md-stat-tables'],
          ['0','Code Block','md-stat-code'],
        ];
        foreach ($statLabels as [$val, $lbl, $id]): ?>
          <div class="md-stat">
            <span class="sv" id="<?= $id ?>"><?= $val ?></span>
            <span class="sl"><?= $lbl ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Options & Actions -->
      <div class="form-group" style="margin-top:1.25rem;">
        <label>Opsi konversi</label>
        <div class="opts-row" style="margin-top:.4rem;">
          <?php
          $optDefs = [
            ['opt_tables',        'tables',        'Tabel',          $post_opts['tables']],
            ['opt_fenced_code',   'fenced_code',   'Fenced Code',    $post_opts['fenced_code']],
            ['opt_task_lists',    'task_lists',     'Task List',     $post_opts['task_lists']],
            ['opt_autolinks',     'autolinks',      'Autolinks',     $post_opts['autolinks']],
            ['opt_strikethrough', 'strikethrough',  '~~Strikethrough~~', $post_opts['strikethrough']],
            ['opt_sanitize',      'sanitize',       'Sanitize HTML', $post_opts['sanitize']],
          ];
          foreach ($optDefs as [$name, $key, $label, $checked]): ?>
            <label class="opt-chk">
              <input type="checkbox" name="<?= $name ?>" id="<?= $key ?>"
                <?= $checked ? 'checked' : '' ?>
                onchange="convertJS()" />
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <form method="POST" action="" id="md-form" style="display:none;">
        <input type="hidden" name="markdown" id="form-markdown" />
        <?php foreach (array_keys($post_opts) as $key): ?>
          <input type="hidden" name="opt_<?= $key ?>" id="form-opt-<?= $key ?>" value="" />
        <?php endforeach; ?>
      </form>

      <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.75rem;">
        <button type="button" class="btn-primary btn-sm"
          style="background:var(--accent3); border-color:var(--accent3);"
          onclick="downloadHTML()">
          ⬇ Download HTML
        </button>
        <button type="button" class="btn-ghost btn-sm" onclick="copyHTML()">📋 Salin HTML</button>
        <button type="button" class="btn-secondary btn-sm" onclick="submitToServer()">⚙ Konversi via Server (PHP)</button>
        <button type="button" class="btn-ghost btn-sm" onclick="downloadFullPage()">⬇ Download Halaman Lengkap</button>
      </div>
    </div><!-- /.panel -->

    <!-- Hasil server -->
    <?php if ($server_result && !$server_error): ?>
    <?php $stats = mdStats($post_markdown); ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Konversi berhasil via PHP server. <?= $stats['words'] ?> kata → <?= strlen($server_result) ?> byte HTML.</span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title">⚙ Hasil Server PHP</div>

      <div class="view-toggle" style="margin-bottom:.75rem;">
        <button class="view-btn active" id="srv-btn-preview" onclick="serverShowView('preview')">Preview</button>
        <button class="view-btn" id="srv-btn-html" onclick="serverShowView('html')">HTML Mentah</button>
      </div>

      <div id="srv-preview" class="md-preview"
        style="border:1px solid var(--border); border-radius:var(--radius-sm); max-height:400px; overflow-y:auto; padding:1.25rem; margin-bottom:.75rem;">
        <?= $server_result ?>
      </div>

      <div id="srv-html" style="display:none;">
        <div class="copy-wrap">
          <div class="html-output" id="server-html-out"><?= e($server_result) ?></div>
          <button class="copy-btn" data-copy-target="server-html-out" style="top:.5rem;">SALIN</button>
        </div>
      </div>

      <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.85rem;">
        <button class="btn-primary btn-sm"
          style="background:var(--accent3); border-color:var(--accent3);"
          onclick="downloadServerHTML()">
          ⬇ Download HTML
        </button>
        <button class="btn-ghost btn-sm" onclick="copyServerHTML()">📋 Salin semua</button>
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
      <div class="panel-title">📖 Cheat Sheet Markdown</div>
      <div style="display:flex; flex-direction:column; gap:.3rem; font-size:.77rem;">
        <?php
        $cheatSheet = [
          ['# Heading 1',         'h1'],
          ['## Heading 2',        'h2'],
          ['### Heading 3',       'h3'],
          ['**tebal**',           '<strong>'],
          ['*miring*',            '<em>'],
          ['~~coret~~',           '<del>'],
          ['`kode inline`',       '<code>'],
          ['==highlight==',       '<mark>'],
          ['[teks](url)',         '<a>'],
          ['![alt](img.png)',     '<img>'],
          ['> blockquote',        '<blockquote>'],
          ['- item list',         '<ul>'],
          ['1. item list',        '<ol>'],
          ['- [ ] belum selesai', 'task unchecked'],
          ['- [x] selesai',       'task checked'],
          ['---',                 '<hr>'],
          ['```js ... ```',       '<pre><code>'],
          ['| A | B |',           '<table>'],
        ];
        foreach ($cheatSheet as [$md, $html]): ?>
          <div style="display:flex; align-items:center; gap:.4rem; padding:.25rem 0; border-bottom:1px solid var(--border);"
            onclick="insertRaw(<?= htmlspecialchars(json_encode($md), ENT_QUOTES) ?>)"
            style="cursor:pointer;"
            title="Klik untuk insert">
            <span style="font-family:var(--font-mono); font-size:.72rem; color:var(--accent3); flex:1; cursor:pointer;"
              onclick="insertRaw(<?= htmlspecialchars(json_encode($md), ENT_QUOTES) ?>)">
              <?= e($md) ?>
            </span>
            <span style="color:var(--muted); font-size:.68rem; flex-shrink:0;"><?= e($html) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📄 Template Siap Pakai</div>
      <div style="display:flex; flex-direction:column; gap:.4rem;">
        <?php
        $templates = [
          ['README GitHub',      'readme'],
          ['Artikel / Blog',     'article'],
          ['Dokumentasi API',    'apidoc'],
          ['Changelog',          'changelog'],
          ['Meeting Notes',      'meeting'],
        ];
        foreach ($templates as [$label, $key]): ?>
          <button class="btn-ghost btn-sm btn-full"
            style="text-align:left;"
            onclick="loadTemplate('<?= $key ?>')">
            <?= e($label) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/html-minifier"  class="btn-ghost btn-sm btn-full">HTML Minifier</a>
        <a href="/tools/word-counter"   class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/slug-generator" class="btn-ghost btn-sm btn-full">Slug Generator</a>
        <a href="/tools/text-diff"      class="btn-ghost btn-sm btn-full">Text Diff Checker</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Markdown to HTML — logika JS (realtime)
   Menggunakan marked.js untuk preview JS.
   PHP parser dipakai saat form di-submit.
   ────────────────────────────────────────── */

// Load marked.js dari CDN
(function() {
  const s = document.createElement('script');
  s.src = 'https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js';
  s.onload = () => {
    window.MARKED_READY = true;
    // Konfigurasi marked
    marked.setOptions({
      breaks: true,
      gfm:    true,
    });
    convertJS();
  };
  document.head.appendChild(s);
})();

let rightPane = 'preview';
let currentHTML = '';

// ── Convert realtime ──────────────────────────────────────────
function convertJS() {
  const md      = document.getElementById('md-editor').value;
  const preview = document.getElementById('md-preview-pane');
  const htmlOut = document.getElementById('md-html-pane');

  if (!window.MARKED_READY) {
    preview.innerHTML = '<em style="color:var(--muted);">Memuat parser...</em>';
    return;
  }

  if (!md.trim()) {
    preview.innerHTML = '<em style="color:var(--muted);">Preview akan muncul di sini...</em>';
    htmlOut.textContent = '';
    currentHTML = '';
    return;
  }

  try {
    currentHTML = marked.parse(md);
    preview.innerHTML = currentHTML;
    htmlOut.textContent = currentHTML;
    // Syntax highlight if available
    if (window.Prism) Prism.highlightAllUnder(preview);
  } catch(e) {
    preview.innerHTML = '<em style="color:#dc2626;">Error: ' + esc(e.message) + '</em>';
  }
}

// ── Stats update ──────────────────────────────────────────────
function updateStats() {
  const md = document.getElementById('md-editor').value;
  if (!md) {
    ['chars','words','lines','headings','links','tables','code'].forEach(k =>
      document.getElementById('md-stat-' + k).textContent = '0');
    return;
  }
  document.getElementById('md-stat-chars').textContent    = md.length.toLocaleString('id');
  document.getElementById('md-stat-words').textContent    = md.trim() ? md.trim().split(/\s+/).length.toLocaleString('id') : '0';
  document.getElementById('md-stat-lines').textContent    = md.split('\n').length.toLocaleString('id');
  document.getElementById('md-stat-headings').textContent = (md.match(/^#{1,6}\s/gm) || []).length;
  document.getElementById('md-stat-links').textContent    = (md.match(/\[.*?\]\(.*?\)/g) || []).length;
  document.getElementById('md-stat-tables').textContent   = Math.floor((md.match(/^\|.*\|/gm) || []).length / 2);
  document.getElementById('md-stat-code').textContent     = Math.floor((md.match(/^```/gm) || []).length / 2);
}

// ── Right pane switching ──────────────────────────────────────
function setRightPane(pane) {
  rightPane = pane;
  const previewEl = document.getElementById('md-preview-pane');
  const htmlEl    = document.getElementById('md-html-pane');
  const btnPrev   = document.getElementById('btn-preview');
  const btnHtml   = document.getElementById('btn-html');
  previewEl.style.display = pane === 'preview' ? '' : 'none';
  htmlEl.style.display    = pane === 'html'    ? '' : 'none';
  btnPrev.classList.toggle('active', pane === 'preview');
  btnHtml.classList.toggle('active', pane === 'html');
  btnPrev.style.cssText = pane === 'preview' ? 'background:var(--accent3);color:#fff;border-color:var(--accent3);' : '';
  btnHtml.style.cssText = pane === 'html' ? 'background:var(--accent3);color:#fff;border-color:var(--accent3);' : '';
}

// ── Toolbar: insert markdown ──────────────────────────────────
function getTA() { return document.getElementById('md-editor'); }

function insertMD(before, after) {
  const ta    = getTA();
  const start = ta.selectionStart;
  const end   = ta.selectionEnd;
  const sel   = ta.value.slice(start, end) || 'teks';
  const ins   = before + sel + after;
  ta.value    = ta.value.slice(0, start) + ins + ta.value.slice(end);
  ta.setSelectionRange(start + before.length, start + before.length + sel.length);
  ta.focus();
  convertJS(); updateStats();
}

function insertLine(prefix) {
  const ta  = getTA();
  const pos = ta.selectionStart;
  const ls  = ta.value.lastIndexOf('\n', pos - 1) + 1;
  ta.value  = ta.value.slice(0, ls) + prefix + ta.value.slice(ls);
  ta.setSelectionRange(ls + prefix.length, ls + prefix.length);
  ta.focus();
  convertJS(); updateStats();
}

function insertHeading(level) {
  insertLine('#'.repeat(level) + ' ');
}

function insertCodeBlock() {
  const ta  = getTA();
  const ins = '\n```js\n// kode di sini\n```\n';
  const pos = ta.selectionStart;
  ta.value  = ta.value.slice(0, pos) + ins + ta.value.slice(pos);
  ta.setSelectionRange(pos + 5, pos + 5);
  ta.focus();
  convertJS(); updateStats();
}

function insertTable() {
  const ins = '\n| Kolom A | Kolom B | Kolom C |\n|---------|---------|----------|\n| Data 1  | Data 2  | Data 3   |\n| Data 4  | Data 5  | Data 6   |\n';
  const ta  = getTA();
  const pos = ta.selectionStart;
  ta.value  = ta.value.slice(0, pos) + ins + ta.value.slice(pos);
  ta.focus();
  convertJS(); updateStats();
}

function insertLink() {
  const url  = prompt('URL:', 'https://');
  if (!url) return;
  const text = prompt('Teks link:', 'teks link') || url;
  insertRaw('[' + text + '](' + url + ')');
}

function insertRaw(text) {
  const ta  = getTA();
  const pos = ta.selectionStart;
  ta.value  = ta.value.slice(0, pos) + text + ta.value.slice(pos);
  ta.setSelectionRange(pos + text.length, pos + text.length);
  ta.focus();
  convertJS(); updateStats();
}

// ── Download & Copy ───────────────────────────────────────────
function downloadHTML() {
  if (!currentHTML) { showToast && showToast('Ketik Markdown dulu!', 'warning'); return; }
  const blob = new Blob([currentHTML], { type: 'text/html;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'output.html';
  a.click();
}

function downloadFullPage() {
  if (!currentHTML) { showToast && showToast('Ketik Markdown dulu!', 'warning'); return; }
  const full = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dokumen</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; line-height: 1.7; color: #1e293b; }
    h1,h2,h3 { font-weight: 800; }
    h1 { border-bottom: 2px solid #e2e8f0; padding-bottom: .35em; }
    h2 { border-bottom: 1px solid #e2e8f0; padding-bottom: .25em; }
    code { background: #f1f5f9; padding: .1em .3em; border-radius: 3px; font-size: .88em; }
    pre { background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: 6px; overflow-x: auto; }
    pre code { background: none; color: inherit; }
    blockquote { border-left: 4px solid #7c3aed; margin: 1em 0; padding: .5em 1em; background: #faf5ff; border-radius: 0 4px 4px 0; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #e2e8f0; padding: .5rem .75rem; }
    th { background: #f8fafc; }
    img { max-width: 100%; }
    a { color: #2563eb; }
  </style>
</head>
<body>
${currentHTML}
</body>
</html>`;
  const blob = new Blob([full], { type: 'text/html;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'page.html';
  a.click();
}

function copyHTML() {
  if (!currentHTML) return;
  navigator.clipboard.writeText(currentHTML).then(() =>
    showToast && showToast('HTML disalin!', 'success'));
}

function copyMarkdown() {
  const md = getTA().value;
  if (!md) return;
  navigator.clipboard.writeText(md).then(() =>
    showToast && showToast('Markdown disalin!', 'success'));
}

// ── Server submit ─────────────────────────────────────────────
function submitToServer() {
  const md   = getTA().value;
  const form = document.getElementById('md-form');
  document.getElementById('form-markdown').value = md;
  // Sync checkboxes
  ['tables','fenced_code','task_lists','autolinks','strikethrough','sanitize'].forEach(key => {
    const cb  = document.getElementById(key);
    const inp = document.getElementById('form-opt-' + key);
    if (cb && inp) inp.value = cb.checked ? '1' : '';
    if (inp && !cb?.checked) inp.removeAttribute('name');
  });
  form.method = 'POST'; form.style.display = 'block';
  form.submit();
}

// ── Server result actions ─────────────────────────────────────
function serverShowView(pane) {
  document.getElementById('srv-preview').style.display = pane === 'preview' ? '' : 'none';
  document.getElementById('srv-html').style.display    = pane === 'html' ? '' : 'none';
  document.getElementById('srv-btn-preview').classList.toggle('active', pane === 'preview');
  document.getElementById('srv-btn-html').classList.toggle('active', pane === 'html');
}

function downloadServerHTML() {
  const el = document.getElementById('server-html-out');
  if (!el) return;
  const blob = new Blob([el.textContent], { type: 'text/html;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'output-server.html';
  a.click();
}

function copyServerHTML() {
  const el = document.getElementById('server-html-out');
  if (el) navigator.clipboard.writeText(el.textContent).then(() =>
    showToast && showToast('HTML disalin!', 'success'));
}

// ── File upload ───────────────────────────────────────────────
function loadFile(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    getTA().value = e.target.result;
    convertJS(); updateStats();
    showToast && showToast('File berhasil dimuat!', 'success');
  };
  reader.readAsText(file);
  input.value = '';
}

// ── Clear ─────────────────────────────────────────────────────
function clearEditor() {
  getTA().value = '';
  convertJS(); updateStats();
}

// ── Sample content ────────────────────────────────────────────
function loadSample() {
  getTA().value = `# Judul Dokumen

Ini adalah paragraf pembuka yang berisi teks **tebal**, *miring*, dan ~~dicoret~~.
Bisa juga \`kode inline\` dan ==highlight==.

## Daftar

- Item satu
- Item dua
  - Sub-item A
  - Sub-item B
- Item tiga

### Ordered List

1. Langkah pertama
2. Langkah kedua
3. Langkah ketiga

## Task List

- [x] Belajar Markdown
- [x] Buat converter
- [ ] Deploy ke produksi
- [ ] Tulis dokumentasi

## Kode

\`\`\`javascript
function hitung(a, b) {
  // Mengembalikan jumlah dua angka
  return a + b;
}

console.log(hitung(10, 20)); // 30
\`\`\`

\`\`\`php
<?php
function salam(string \$nama): string {
  return "Halo, \$nama!";
}
echo salam("Dunia");
\`\`\`

## Tabel

| Nama        | Usia | Kota      |
|-------------|:----:|----------:|
| Budi        | 25   | Jakarta   |
| Siti        | 30   | Bandung   |
| Agus        | 22   | Surabaya  |

## Blockquote

> Belajar tidak ada habisnya. Setiap hari adalah kesempatan untuk menjadi lebih baik.
>
> — Pepatah Bijak

## Link dan Gambar

Kunjungi [Google](https://google.com "Mesin pencari") untuk informasi lebih lanjut.

---

*Dibuat dengan ❤ menggunakan Multi Tools Markdown Converter*
`;
  convertJS(); updateStats();
}

// ── Templates ─────────────────────────────────────────────────
const TEMPLATES = {
  readme: `# Nama Proyek

[![License](https://img.shields.io/badge/license-MIT-blue.svg)]()

Deskripsi singkat tentang proyek ini.

## Fitur

- ✅ Fitur 1
- ✅ Fitur 2
- 🚧 Fitur 3 (dalam pengembangan)

## Instalasi

\`\`\`bash
git clone https://github.com/username/repo.git
cd repo
npm install
\`\`\`

## Penggunaan

\`\`\`bash
npm start
\`\`\`

## Kontribusi

Pull request sangat disambut. Untuk perubahan besar, buka issue terlebih dahulu.

## Lisensi

[MIT](LICENSE)
`,
  article: `# Judul Artikel

*Ditulis oleh Penulis · 1 Januari 2025 · 5 menit baca*

---

## Pendahuluan

Tuliskan paragraf pembuka yang menarik perhatian pembaca...

## Isi Utama

### Poin Pertama

Jelaskan poin pertama dengan detail...

### Poin Kedua

Jelaskan poin kedua dengan contoh konkret...

## Kesimpulan

Rangkum poin-poin utama dan berikan call to action...

---

*Tags: tag1, tag2, tag3*
`,
  apidoc: `# Dokumentasi API

## Base URL

\`https://api.example.com/v1\`

## Authentication

Semua request memerlukan header:

\`\`\`
Authorization: Bearer {token}
\`\`\`

## Endpoints

### GET /users

Mengambil daftar semua pengguna.

**Response:**

\`\`\`json
{
  "data": [
    { "id": 1, "name": "Budi", "email": "budi@example.com" }
  ],
  "total": 1
}
\`\`\`

### POST /users

Membuat pengguna baru.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| name  | string | Ya | Nama lengkap |
| email | string | Ya | Alamat email |

**Response:** \`201 Created\`

## Error Codes

| Code | Keterangan |
|------|-----------|
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 500 | Server Error |
`,
  changelog: `# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan di sini.

## [Unreleased]

### Ditambahkan
- Fitur baru X

## [1.2.0] - 2025-01-01

### Ditambahkan
- Fitur A
- Fitur B

### Diperbaiki
- Bug pada login

### Dihapus
- Fitur lama yang sudah deprecated

## [1.1.0] - 2024-12-01

### Ditambahkan
- Integrasi dengan API eksternal

### Diubah
- Performa query database ditingkatkan
`,
  meeting: `# Catatan Rapat

**Tanggal:** 1 Januari 2025
**Waktu:** 09:00 – 10:00 WIB
**Peserta:** Budi, Siti, Agus

---

## Agenda

1. Review progress sprint sebelumnya
2. Planning sprint berikutnya
3. Diskusi isu teknis

## Diskusi

### Review Sprint

- [x] Fitur login selesai
- [x] Dashboard tersedia
- [ ] Laporan masih dalam proses

### Planning Sprint Berikutnya

Prioritas untuk sprint mendatang:

| Task | Assignee | Estimasi |
|------|----------|----------|
| Fitur export PDF | Budi | 3 hari |
| Bug fix mobile | Siti | 1 hari |
| Testing | Agus | 2 hari |

## Action Items

- [ ] Budi: Deploy ke staging sebelum Rabu
- [ ] Siti: Update dokumentasi API
- [ ] Agus: Setup environment testing

## Rapat Berikutnya

**Tanggal:** 8 Januari 2025, 09:00 WIB
`,
};

function loadTemplate(key) {
  const tmpl = TEMPLATES[key];
  if (!tmpl) return;
  getTA().value = tmpl;
  convertJS(); updateStats();
  showToast && showToast('Template dimuat!', 'success');
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Init ──────────────────────────────────────────────────────
<?php if ($post_markdown): ?>
convertJS();
<?php endif; ?>
updateStats();
setRightPane('preview');
</script>

<?php require '../../includes/footer.php'; ?>