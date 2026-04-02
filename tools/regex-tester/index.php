<?php
require '../../includes/config.php';
/**
 * Multi Tools — Regex Tester
 * Uji ekspresi reguler (regex) secara realtime di browser (JS)
 * dan via server (PHP preg_match/preg_match_all/preg_replace).
 * Mendukung flag, named groups, replace, explain, dan library.
 * ============================================================ */

// ── Handle POST (PHP regex engine) ──────────────────────────
$server_result  = null;
$server_error   = '';
$post_pattern   = '';
$post_flags     = 'g';
$post_subject   = '';
$post_replace   = '';
$post_action    = 'match'; // match | replace | split
$post_engine    = 'php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_pattern = $_POST['pattern']  ?? '';
  $post_flags   = $_POST['flags']    ?? '';
  $post_subject = $_POST['subject']  ?? '';
  $post_replace = $_POST['replace']  ?? '';
  $post_action  = in_array($_POST['action'] ?? 'match', ['match','replace','split'])
                    ? $_POST['action'] : 'match';

  if ($post_pattern === '') {
    $server_error = 'Pattern tidak boleh kosong.';
  } else {
    // Build PCRE pattern: strip JS flags, build PHP delimiter
    $jsFlags  = $post_flags;
    $phpFlags = '';
    if (str_contains($jsFlags, 'i')) $phpFlags .= 'i';
    if (str_contains($jsFlags, 's')) $phpFlags .= 's';
    if (str_contains($jsFlags, 'm')) $phpFlags .= 'm';
    if (str_contains($jsFlags, 'u')) $phpFlags .= 'u';
    if (str_contains($jsFlags, 'x')) $phpFlags .= 'x';

    $delimiter = '/';
    $safePattern = str_replace($delimiter, '\\' . $delimiter, $post_pattern);
    $pcre = $delimiter . $safePattern . $delimiter . $phpFlags;

    // Suppress errors & catch warnings
    set_error_handler(fn($no, $str) => throw new ErrorException($str));
    try {
      switch ($post_action) {
        case 'match':
          $isGlobal = str_contains($jsFlags, 'g');
          if ($isGlobal) {
            $count = preg_match_all($pcre, $post_subject, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            $server_result = [
              'action'  => 'match_all',
              'count'   => $count,
              'matches' => $count > 0 ? array_map(fn($set) => array_map(fn($m) => ['value' => $m[0], 'offset' => $m[1]], $set), $matches) : [],
            ];
          } else {
            $found = preg_match($pcre, $post_subject, $matches, PREG_OFFSET_CAPTURE);
            $server_result = [
              'action'  => 'match_one',
              'found'   => (bool)$found,
              'matches' => $found ? array_map(fn($m) => ['value' => $m[0], 'offset' => $m[1]], $matches) : [],
            ];
          }
          break;

        case 'replace':
          $result = preg_replace($pcre, $post_replace, $post_subject);
          if ($result === null) throw new ErrorException('preg_replace gagal.');
          $server_result = ['action' => 'replace', 'result' => $result];
          break;

        case 'split':
          $parts = preg_split($pcre, $post_subject, -1, PREG_SPLIT_NO_EMPTY);
          $server_result = ['action' => 'split', 'parts' => $parts, 'count' => count($parts)];
          break;
      }
    } catch (Throwable $e) {
      $server_error = 'Regex error: ' . preg_replace('/in .*$/s', '', $e->getMessage());
    }
    restore_error_handler();
  }
}

// ── Library regex siap pakai ─────────────────────────────────
$REGEX_LIBRARY = [
  'Email & URL' => [
    ['name'=>'Email',           'pattern'=>'^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$', 'flags'=>'i', 'desc'=>'Validasi format email'],
    ['name'=>'URL',             'pattern'=>'https?:\/\/(www\.)?[-a-zA-Z0-9@:%._+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_+.~#?&\/=]*)', 'flags'=>'gi', 'desc'=>'URL HTTP/HTTPS'],
    ['name'=>'IP Address v4',   'pattern'=>'\b((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)\b', 'flags'=>'g', 'desc'=>'IPv4 address'],
    ['name'=>'Domain',          'pattern'=>'(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}', 'flags'=>'gi', 'desc'=>'Nama domain'],
  ],
  'Angka & Format' => [
    ['name'=>'Bilangan bulat',  'pattern'=>'^-?\d+$', 'flags'=>'', 'desc'=>'Integer positif/negatif'],
    ['name'=>'Desimal',         'pattern'=>'^-?\d+(\.\d+)?$', 'flags'=>'', 'desc'=>'Angka desimal'],
    ['name'=>'Rupiah',          'pattern'=>'Rp\s?\d{1,3}(?:\.\d{3})*(?:,\d{2})?', 'flags'=>'g', 'desc'=>'Format mata uang Rupiah'],
    ['name'=>'Nomor telepon ID','pattern'=>'(^|\s)(\+62|0)[0-9]{8,12}', 'flags'=>'g', 'desc'=>'Nomor HP Indonesia'],
    ['name'=>'Kode pos Indonesia','pattern'=>'\b[1-9][0-9]{4}\b', 'flags'=>'g', 'desc'=>'Kode pos 5 digit'],
  ],
  'Tanggal & Waktu' => [
    ['name'=>'Tanggal DD/MM/YYYY','pattern'=>'(0[1-9]|[12]\d|3[01])\/(0[1-9]|1[0-2])\/\d{4}', 'flags'=>'g', 'desc'=>'Format tanggal Indonesia'],
    ['name'=>'Tanggal YYYY-MM-DD','pattern'=>'\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])', 'flags'=>'g', 'desc'=>'Format ISO 8601'],
    ['name'=>'Waktu HH:MM',     'pattern'=>'([01]\d|2[0-3]):[0-5]\d', 'flags'=>'g', 'desc'=>'Format waktu 24 jam'],
    ['name'=>'Timestamp Unix',  'pattern'=>'\b1[0-9]{9}\b', 'flags'=>'g', 'desc'=>'Unix timestamp (10 digit)'],
  ],
  'Kode & Developer' => [
    ['name'=>'Hex color',       'pattern'=>'#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})\b', 'flags'=>'g', 'desc'=>'Warna HEX CSS'],
    ['name'=>'UUID v4',         'pattern'=>'[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}', 'flags'=>'gi', 'desc'=>'UUID versi 4'],
    ['name'=>'Variabel JS/PHP', 'pattern'=>'[$_a-zA-Z][$_a-zA-Z0-9]*', 'flags'=>'g', 'desc'=>'Nama variabel valid'],
    ['name'=>'Tag HTML',        'pattern'=>'<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>(.*?)<\/\1>', 'flags'=>'gis', 'desc'=>'Tag HTML dengan konten'],
    ['name'=>'Komentar HTML',   'pattern'=>'<!--[\s\S]*?-->', 'flags'=>'g', 'desc'=>'Komentar HTML'],
    ['name'=>'JSON key',        'pattern'=>'"([^"]+)"\s*:', 'flags'=>'g', 'desc'=>'Key dalam JSON'],
    ['name'=>'Versi semver',    'pattern'=>'\bv?\d+\.\d+\.\d+(?:-[a-zA-Z0-9.]+)?\b', 'flags'=>'g', 'desc'=>'Semantic versioning'],
  ],
  'Teks & Konten' => [
    ['name'=>'Kata berulang',   'pattern'=>'\b(\w+)\s+\1\b', 'flags'=>'gi', 'desc'=>'Kata yang muncul dua kali berturut'],
    ['name'=>'Spasi berlebih',  'pattern'=>'  +', 'flags'=>'g', 'desc'=>'Dua atau lebih spasi berturut'],
    ['name'=>'Baris kosong',    'pattern'=>'^\s*$', 'flags'=>'gm', 'desc'=>'Baris yang hanya berisi whitespace'],
    ['name'=>'Hashtag',         'pattern'=>'#[a-zA-Z0-9_]+', 'flags'=>'g', 'desc'=>'Hashtag media sosial'],
    ['name'=>'Mention',         'pattern'=>'@[a-zA-Z0-9_.]+', 'flags'=>'g', 'desc'=>'Mention / username'],
    ['name'=>'Emoji',           'pattern'=>'[\u{1F300}-\u{1F9FF}]', 'flags'=>'gu', 'desc'=>'Karakter emoji unicode'],
  ],
];

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Regex Tester Online — Uji Ekspresi Reguler JavaScript & PHP | Multi Tools',
  'description' => 'Uji dan debug ekspresi reguler (regex) secara realtime. Dukung JavaScript dan PHP, named capture groups, mode replace, split, explain, dan library regex siap pakai.',
  'keywords'    => 'regex tester, regular expression, uji regex, regex online, preg match php, javascript regex, named groups, regex library, multi tools',
  'og_title'    => 'Regex Tester Online — JavaScript & PHP',
  'og_desc'     => 'Uji regex realtime: match, replace, split. Named groups, flags, library siap pakai.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'Regex Tester'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/regex-tester#webpage',
      'url'         => SITE_URL . '/tools/regex-tester',
      'name'        => 'Regex Tester Online',
      'description' => 'Uji ekspresi reguler JavaScript dan PHP secara realtime.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools','item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Regex Tester',   'item' => SITE_URL . '/tools/regex-tester'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Regex Tester',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/regex-tester',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Action tabs ── */
.action-tabs {
  display: flex; gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden; margin-bottom: 1.5rem;
}
.action-tab {
  flex: 1; padding: .5rem .4rem;
  background: var(--bg); border: none;
  border-right: 1px solid var(--border);
  font-family: var(--font-body); font-size: .82rem; font-weight: 600;
  color: var(--muted); cursor: pointer;
  transition: all var(--transition); text-align: center;
}
.action-tab:last-child { border-right: none; }
.action-tab:hover      { background: var(--surface); color: var(--text); }
.action-tab.active     { background: var(--accent3); color: #fff; }

/* ── Pattern input area ── */
.pattern-row {
  display: flex; align-items: stretch; gap: 0;
  border: 2px solid var(--border); border-radius: var(--radius-sm);
  overflow: hidden; transition: border-color var(--transition);
  background: var(--bg);
}
.pattern-row:focus-within { border-color: var(--accent3); box-shadow: 0 0 0 3px rgba(124,58,237,.12); }
.pattern-row.error        { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }

.pattern-delimiter {
  padding: .65rem .75rem; background: rgba(124,58,237,.08);
  font-family: var(--font-mono); font-size: 1.1rem; font-weight: 800;
  color: var(--accent3); flex-shrink: 0;
  display: flex; align-items: center; line-height: 1;
  user-select: none; border-right: 1px solid var(--border);
}
.pattern-input {
  flex: 1; border: none !important; outline: none !important;
  background: transparent !important; box-shadow: none !important;
  font-family: var(--font-mono) !important; font-size: .95rem !important;
  color: var(--text) !important;
  padding: .65rem .75rem !important;
}
.flags-input {
  border: none !important; outline: none !important;
  background: rgba(124,58,237,.06) !important; box-shadow: none !important;
  width: 80px !important; font-family: var(--font-mono) !important;
  font-size: .9rem !important; color: var(--accent3) !important;
  border-left: 1px solid var(--border) !important;
  padding: .65rem .6rem !important; text-align: center !important;
}

/* ── Flag pills ── */
.flag-pills {
  display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem;
}
.flag-pill {
  padding: .2rem .7rem;
  border: 1px solid var(--border); border-radius: 99px;
  font-family: var(--font-mono); font-size: .75rem; font-weight: 700;
  cursor: pointer; background: var(--surface); color: var(--muted);
  transition: all var(--transition); user-select: none;
}
.flag-pill:hover { border-color: var(--accent3); color: var(--accent3); background: rgba(124,58,237,.06); }
.flag-pill.active { border-color: var(--accent3); color: var(--accent3); background: rgba(124,58,237,.1); }

/* ── Subject textarea ── */
.subject-wrap { position: relative; }
.subject-highlight {
  position: absolute; inset: 0;
  padding: .65rem .9rem;
  font-family: var(--font-mono); font-size: .9rem; line-height: 1.6;
  pointer-events: none; word-break: break-all;
  white-space: pre-wrap; overflow: hidden;
  color: transparent;
}
.subject-highlight mark {
  border-radius: 2px;
  padding: 0;
}
mark.match-0  { background: rgba(124,58,237,.25); }
mark.match-1  { background: rgba(37,99,235,.2); }
mark.match-2  { background: rgba(14,165,233,.2); }
mark.match-3  { background: rgba(16,185,129,.2); }
mark.match-4  { background: rgba(245,158,11,.2); }

/* ── Match results ── */
.match-list {
  display: flex; flex-direction: column; gap: .4rem;
  max-height: 320px; overflow-y: auto;
}
.match-item {
  display: flex; align-items: flex-start; gap: .65rem;
  padding: .55rem .75rem;
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-sm); transition: border-color var(--transition);
}
.match-item:hover { border-color: var(--accent3); }
.match-badge {
  font-family: var(--font-mono); font-size: .65rem; font-weight: 800;
  padding: .15rem .45rem; border-radius: 4px; flex-shrink: 0;
  margin-top: .1rem; min-width: 26px; text-align: center;
}
.match-badge.m0 { background: rgba(124,58,237,.15); color: var(--accent3); }
.match-badge.m1 { background: rgba(37,99,235,.15);  color: var(--accent); }
.match-badge.m2 { background: rgba(14,165,233,.15); color: var(--accent2); }
.match-badge.m3 { background: rgba(16,185,129,.15); color: var(--accent5); }
.match-badge.m4 { background: rgba(245,158,11,.15); color: var(--accent4); }

.match-body { flex: 1; min-width: 0; }
.match-value {
  font-family: var(--font-mono); font-size: .85rem; font-weight: 700;
  color: var(--text); word-break: break-all;
}
.match-offset {
  font-family: var(--font-mono); font-size: .68rem; color: var(--muted);
  margin-top: .15rem;
}
.match-groups {
  margin-top: .4rem; display: flex; flex-wrap: wrap; gap: .3rem;
}
.group-chip {
  display: inline-flex; align-items: center; gap: .3rem;
  font-family: var(--font-mono); font-size: .68rem;
  padding: .15rem .5rem; border-radius: 4px;
  background: rgba(124,58,237,.07); border: 1px solid rgba(124,58,237,.2);
  color: var(--accent3);
}
.group-chip .gname { color: var(--muted); }

/* ── Status badge ── */
.status-bar {
  display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
  padding: .6rem .9rem;
  border-radius: var(--radius-sm); border: 1px solid;
  font-size: .82rem; font-weight: 600; margin-bottom: 1rem;
}
.status-bar.match    { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.status-bar.no-match { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }
.status-bar.idle     { background: var(--bg); border-color: var(--border); color: var(--muted); }
.status-bar.error    { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }

/* ── Replace output ── */
.replace-output {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: .85rem 1rem;
  font-family: var(--font-mono); font-size: .85rem; line-height: 1.65;
  word-break: break-all; white-space: pre-wrap; min-height: 60px;
  color: var(--text);
}
.replace-output ins  { background: rgba(16,185,129,.2); text-decoration: none; border-radius: 2px; }
.replace-output del  { background: rgba(239,68,68,.15); text-decoration: line-through; border-radius: 2px; color: #b91c1c; }

/* ── Split output ── */
.split-list {
  display: flex; flex-wrap: wrap; gap: .4rem;
}
.split-chip {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .25rem .65rem; border-radius: 99px;
  border: 1px solid var(--accent3); background: rgba(124,58,237,.07);
  font-family: var(--font-mono); font-size: .78rem; color: var(--accent3);
  font-weight: 600;
}
.split-chip .si { font-size: .6rem; color: var(--muted); font-weight: 400; }

/* ── Regex library ── */
.lib-category { margin-bottom: 1.25rem; }
.lib-cat-title {
  font-size: .7rem; letter-spacing: .1em; text-transform: uppercase;
  font-family: var(--font-mono); color: var(--muted);
  margin-bottom: .5rem; font-weight: 700;
}
.lib-items { display: flex; flex-direction: column; gap: .3rem; }
.lib-item {
  display: flex; align-items: center; gap: .5rem;
  padding: .45rem .65rem;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  cursor: pointer; transition: all var(--transition); background: var(--bg);
}
.lib-item:hover { border-color: var(--accent3); background: rgba(124,58,237,.04); }
.lib-item-name { font-size: .8rem; font-weight: 600; color: var(--text); flex: 1; }
.lib-item-desc { font-size: .7rem; color: var(--muted); }
.lib-item-btn  { font-family: var(--font-mono); font-size: .65rem; font-weight: 700; color: var(--accent3); flex-shrink: 0; }

/* ── Explain box ── */
.explain-token {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .15rem .5rem; border-radius: 4px; margin: .1rem .15rem;
  font-family: var(--font-mono); font-size: .78rem; font-weight: 600;
  cursor: help;
}
.et-char     { background: rgba(124,58,237,.12); color: var(--accent3); border: 1px solid rgba(124,58,237,.2); }
.et-class    { background: rgba(37,99,235,.12);  color: var(--accent);  border: 1px solid rgba(37,99,235,.2); }
.et-quant    { background: rgba(245,158,11,.12); color: var(--accent4); border: 1px solid rgba(245,158,11,.2); }
.et-anchor   { background: rgba(16,185,129,.12); color: var(--accent5); border: 1px solid rgba(16,185,129,.2); }
.et-group    { background: rgba(14,165,233,.12); color: var(--accent2); border: 1px solid rgba(14,165,233,.2); }
.et-alt      { background: rgba(239,68,68,.12);  color: #dc2626;        border: 1px solid rgba(239,68,68,.2); }
.et-escape   { background: rgba(100,116,139,.12);color: var(--muted);   border: 1px solid var(--border); }
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
        <span aria-hidden="true">🔎</span> Regex <span>Tester</span>
      </div>
      <p class="page-lead">
        Uji ekspresi reguler secara realtime di browser (JavaScript) atau server (PHP).
        Tampilan highlight match, named groups, replace, split, dan explain.
      </p>

      <!-- Action tabs -->
      <div class="action-tabs" role="tablist">
        <?php foreach (['match'=>'🎯 Match','replace'=>'✏ Replace','split'=>'✂ Split'] as $v => $l): ?>
          <button type="button" role="tab"
            class="action-tab <?= $post_action === $v ? 'active' : '' ?>"
            onclick="setAction('<?= $v ?>')">
            <?= $l ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="regex-form" novalidate>
        <input type="hidden" id="action-input" name="action" value="<?= e($post_action) ?>" />

        <!-- Pattern row -->
        <div class="form-group">
          <label>Ekspresi reguler</label>
          <div class="pattern-row" id="pattern-row">
            <span class="pattern-delimiter" aria-hidden="true">/</span>
            <input type="text" class="pattern-input" id="pattern"
              name="pattern" placeholder="pattern..." autocomplete="off" autocorrect="off"
              spellcheck="false" value="<?= e($post_pattern) ?>"
              oninput="runJS()" aria-label="Pattern regex" />
            <span class="pattern-delimiter" aria-hidden="true">/</span>
            <input type="text" class="flags-input" id="flags"
              name="flags" placeholder="gim" maxlength="8"
              value="<?= e($post_flags ?: 'g') ?>"
              oninput="syncFlagPills(); runJS()"
              aria-label="Flags regex" autocomplete="off" />
          </div>

          <!-- Flag pills -->
          <div class="flag-pills" id="flag-pills">
            <?php
            $flagDefs = [
              'g' => ['Global — temukan semua match'],
              'i' => ['Case insensitive'],
              'm' => ['Multiline — ^ dan $ per baris'],
              's' => ['Single-line — . cocok \\n'],
              'u' => ['Unicode mode'],
              'd' => ['Indices — catat posisi group (JS)'],
            ];
            foreach ($flagDefs as $flag => [$desc]): ?>
              <span class="flag-pill <?= str_contains($post_flags ?: 'g', $flag) ? 'active' : '' ?>"
                id="pill-<?= $flag ?>"
                onclick="toggleFlag('<?= $flag ?>')"
                title="<?= e($desc) ?>">
                <?= $flag ?>
              </span>
            <?php endforeach; ?>
          </div>

          <!-- Error display -->
          <div id="regex-error" class="alert danger" style="display:none; margin-top:.5rem;"></div>
        </div>

        <!-- Subject textarea -->
        <div class="form-group">
          <label for="subject">
            Teks uji
            <span id="match-count-badge" class="badge" style="margin-left:.4rem; display:none;"></span>
          </label>
          <div class="subject-wrap">
            <div class="subject-highlight" id="subject-highlight" aria-hidden="true"></div>
            <textarea id="subject" name="subject"
              placeholder="Masukkan teks yang ingin diuji dengan regex..."
              oninput="runJS()"
              style="min-height:160px; font-family:var(--font-mono); font-size:.9rem; line-height:1.6; background:transparent; position:relative; z-index:1;"
              autocomplete="off" spellcheck="false"
            ><?= e($post_subject) ?></textarea>
          </div>
        </div>

        <!-- Replace input (muncul di mode replace) -->
        <div id="replace-section" class="form-group" style="<?= $post_action !== 'replace' ? 'display:none;' : '' ?>">
          <label for="replace-input">
            String pengganti
            <span class="text-muted text-sm" style="font-weight:400;">
              — gunakan $1, $2 atau \${name} untuk grup
            </span>
          </label>
          <input type="text" id="replace-input" name="replace"
            placeholder='Contoh: [$1] atau ${namaGroup}'
            value="<?= e($post_replace) ?>"
            oninput="runJS()"
            style="font-family:var(--font-mono);" />
        </div>

        <!-- Status bar -->
        <div class="status-bar idle" id="status-bar">
          <span id="status-icon">○</span>
          <span id="status-text">Masukkan pattern dan teks untuk mulai</span>
        </div>

        <!-- Results area -->
        <div id="results-area"></div>

        <!-- Action buttons -->
        <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
          <button type="submit" class="btn-secondary btn-sm">
            ⚙ Uji via Server PHP
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">Bersihkan</button>
          <button type="button" class="btn-ghost btn-sm" onclick="toggleExplain()">
            💡 <span id="explain-toggle-text">Explain</span>
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="copyPattern()">
            📋 Salin Pattern
          </button>
        </div>

        <!-- Explain panel -->
        <div id="explain-panel" style="display:none; margin-top:1rem;">
          <div class="panel" style="padding:1rem;">
            <div class="section-mini-title" style="margin-bottom:.6rem;">💡 Penjelasan pattern</div>
            <div id="explain-output" style="line-height:1.8; font-size:.85rem;">
              —
            </div>
          </div>
        </div>

      </form><!-- /#regex-form -->
    </div><!-- /.panel -->

    <!-- Hasil PHP server -->
    <?php if ($server_result && !$server_error): ?>
    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title">⚙ Hasil Server PHP (PCRE)</div>

      <?php if ($server_result['action'] === 'match_all' || $server_result['action'] === 'match_one'): ?>
        <?php $found = $server_result['action'] === 'match_all' ? $server_result['count'] > 0 : $server_result['found']; ?>
        <div class="status-bar <?= $found ? 'match' : 'no-match' ?>" style="margin-bottom:1rem;">
          <span><?= $found ? '✓' : '✕' ?></span>
          <span>
            <?php if ($server_result['action'] === 'match_all'): ?>
              <?= $server_result['count'] ?> match ditemukan
            <?php else: ?>
              <?= $found ? 'Match ditemukan' : 'Tidak ada match' ?>
            <?php endif; ?>
          </span>
        </div>
        <?php if ($found && !empty($server_result['matches'])): ?>
          <div class="match-list">
            <?php
            $matchSets = $server_result['action'] === 'match_all'
              ? $server_result['matches']
              : [$server_result['matches']];
            foreach ($matchSets as $mi => $set):
              $cls = 'm' . ($mi % 5);
            ?>
              <div class="match-item">
                <span class="match-badge <?= $cls ?>"><?= $mi ?></span>
                <div class="match-body">
                  <div class="match-value"><?= e($set[0]['value']) ?></div>
                  <div class="match-offset">offset <?= $set[0]['offset'] ?> · panjang <?= mb_strlen($set[0]['value']) ?></div>
                  <?php if (count($set) > 1): ?>
                    <div class="match-groups">
                      <?php foreach (array_slice($set, 1) as $gi => $grp): ?>
                        <span class="group-chip">
                          <span class="gname">$<?= $gi + 1 ?></span>
                          <?= e($grp['value']) ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($server_result['action'] === 'replace'): ?>
        <div class="form-group">
          <label>Hasil replace</label>
          <div class="copy-wrap">
            <div class="replace-output" id="server-replace-out"><?= e($server_result['result']) ?></div>
            <button class="copy-btn" data-copy-target="server-replace-out">SALIN</button>
          </div>
        </div>

      <?php elseif ($server_result['action'] === 'split'): ?>
        <div class="form-group">
          <label><?= $server_result['count'] ?> bagian setelah split</label>
          <div class="split-list">
            <?php foreach ($server_result['parts'] as $si => $part): ?>
              <span class="split-chip">
                <span class="si">#<?= $si + 1 ?></span>
                <?= e($part) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($server_error): ?>
    <div class="alert danger" style="margin-top:1rem;" role="alert">
      <span>✕</span>
      <span><?= e($server_error) ?></span>
    </div>
    <?php endif; ?>

  </div><!-- /konten utama -->

  <!-- Sidebar: Library & Referensi -->
  <aside>
    <div class="panel">
      <div class="panel-title">📚 Library Regex</div>
      <?php foreach ($REGEX_LIBRARY as $category => $items): ?>
        <div class="lib-category">
          <div class="lib-cat-title"><?= e($category) ?></div>
          <div class="lib-items">
            <?php foreach ($items as $item): ?>
              <div class="lib-item"
                onclick="loadLibrary(<?= htmlspecialchars(json_encode($item['pattern']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($item['flags']), ENT_QUOTES) ?>)"
                title="<?= e($item['desc']) ?>">
                <span class="lib-item-name"><?= e($item['name']) ?></span>
                <span class="lib-item-btn">← Load</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📖 Referensi Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.25rem; font-size:.78rem;">
        <?php
        $refs = [
          ['.', 'Karakter apapun (kecuali \\n)', 'et-class'],
          ['\\d', 'Digit 0-9', 'et-class'],
          ['\\w', 'Huruf, angka, underscore', 'et-class'],
          ['\\s', 'Whitespace', 'et-class'],
          ['^', 'Awal string/baris', 'et-anchor'],
          ['$', 'Akhir string/baris', 'et-anchor'],
          ['*', 'Nol atau lebih (greedy)', 'et-quant'],
          ['+', 'Satu atau lebih (greedy)', 'et-quant'],
          ['?', 'Nol atau satu', 'et-quant'],
          ['{n,m}', 'Antara n sampai m kali', 'et-quant'],
          ['(abc)', 'Capture group', 'et-group'],
          ['(?:abc)', 'Non-capture group', 'et-group'],
          ['(?<name>)', 'Named capture group', 'et-group'],
          ['(?=abc)', 'Lookahead positif', 'et-group'],
          ['(?!abc)', 'Lookahead negatif', 'et-group'],
          ['[abc]', 'Character class', 'et-class'],
          ['[^abc]', 'Negated class', 'et-class'],
          ['a|b', 'Alternation (a atau b)', 'et-alt'],
        ];
        foreach ($refs as [$sym, $desc, $cls]): ?>
          <div style="display:flex; align-items:center; gap:.5rem; padding:.28rem 0; border-bottom:1px solid var(--border);">
            <span class="explain-token <?= $cls ?>" style="min-width:84px; justify-content:center; font-size:.72rem;"><?= htmlspecialchars($sym) ?></span>
            <span style="color:var(--muted); font-size:.72rem;"><?= e($desc) ?></span>
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
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Regex Tester — realtime JavaScript engine
   PHP server dipakai saat form di-submit.
   ────────────────────────────────────────── */

const MATCH_COLORS = [
  'rgba(124,58,237,.25)',
  'rgba(37,99,235,.2)',
  'rgba(14,165,233,.2)',
  'rgba(16,185,129,.2)',
  'rgba(245,158,11,.2)',
];
const BADGE_CLS = ['m0','m1','m2','m3','m4'];

let currentAction  = '<?= $post_action ?>';
let explainVisible = false;

// ── Action switching ──────────────────────────────────────────
function setAction(action) {
  currentAction = action;
  document.getElementById('action-input').value = action;
  document.querySelectorAll('.action-tab').forEach((t, i) => {
    t.classList.toggle('active', ['match','replace','split'][i] === action);
  });
  const replSect = document.getElementById('replace-section');
  if (replSect) replSect.style.display = action === 'replace' ? '' : 'none';
  runJS();
}

// ── Build regex safely ────────────────────────────────────────
function buildRegex() {
  const pattern = document.getElementById('pattern').value;
  const flags   = document.getElementById('flags').value;
  if (!pattern) return null;
  try {
    return new RegExp(pattern, flags.replace(/[^gimsud]/g, ''));
  } catch(e) {
    return { error: e.message };
  }
}

// ── Main run ─────────────────────────────────────────────────
function runJS() {
  const patternEl = document.getElementById('pattern');
  const subjectEl = document.getElementById('subject');
  const patRow    = document.getElementById('pattern-row');
  const errEl     = document.getElementById('regex-error');
  const subject   = subjectEl.value;

  clearResults();

  if (!patternEl.value) {
    setStatus('idle', '○', 'Masukkan pattern dan teks untuk mulai');
    clearHighlight();
    return;
  }

  const re = buildRegex();
  if (re && re.error) {
    patRow.classList.add('error');
    errEl.style.display = 'flex';
    errEl.querySelector ? (errEl.textContent = re.error) : (errEl.innerText = '⚠ ' + re.error);
    errEl.innerHTML = '<span>⚠</span><span>' + esc(re.error) + '</span>';
    setStatus('error', '✕', 'Pattern tidak valid: ' + re.error);
    clearHighlight();
    explainPattern(patternEl.value);
    return;
  }
  patRow.classList.remove('error');
  errEl.style.display = 'none';

  if (!subject) {
    setStatus('idle', '○', 'Masukkan teks uji');
    clearHighlight();
    explainPattern(patternEl.value);
    return;
  }

  switch (currentAction) {
    case 'match':   runMatch(re, subject);   break;
    case 'replace': runReplace(re, subject); break;
    case 'split':   runSplit(re, subject);   break;
  }

  explainPattern(patternEl.value);
}

// ── Match mode ────────────────────────────────────────────────
function runMatch(re, subject) {
  const matches = [];
  let m;

  if (re.global || re.sticky) {
    re.lastIndex = 0;
    while ((m = re.exec(subject)) !== null) {
      matches.push({ index: m.index, match: m[0], groups: [...m].slice(1), namedGroups: m.groups || {} });
      if (!re.global) break;
      if (m[0].length === 0) re.lastIndex++;
    }
  } else {
    m = re.exec(subject);
    if (m) matches.push({ index: m.index, match: m[0], groups: [...m].slice(1), namedGroups: m.groups || {} });
  }

  const badge = document.getElementById('match-count-badge');
  if (matches.length > 0) {
    badge.style.display = 'inline-flex';
    badge.className     = 'badge accent';
    badge.textContent   = matches.length + ' match';
    setStatus('match', '✓', matches.length + ' match ditemukan');
    renderHighlight(subject, matches);
    renderMatchList(matches);
  } else {
    badge.style.display = 'none';
    setStatus('no-match', '✕', 'Tidak ada match');
    clearHighlight();
    renderNoMatch();
  }
}

function renderMatchList(matches) {
  const html = matches.map((m, i) => {
    const cls   = BADGE_CLS[i % 5];
    const groups = Object.entries(m.namedGroups || {}).length
      ? Object.entries(m.namedGroups).map(([k, v]) =>
          `<span class="group-chip"><span class="gname">\${${k}}</span>${esc(v ?? '')}</span>`
        ).join('')
      : m.groups.map((g, gi) =>
          g !== undefined ? `<span class="group-chip"><span class="gname">$${gi+1}</span>${esc(g)}</span>` : ''
        ).join('');

    return `<div class="match-item">
      <span class="match-badge ${cls}">${i}</span>
      <div class="match-body">
        <div class="match-value">${esc(m.match)}</div>
        <div class="match-offset">offset ${m.index} · panjang ${[...m.match].length}</div>
        ${groups ? `<div class="match-groups">${groups}</div>` : ''}
      </div>
    </div>`;
  }).join('');

  document.getElementById('results-area').innerHTML =
    `<div class="match-list">${html}</div>`;
}

function renderNoMatch() {
  document.getElementById('results-area').innerHTML =
    `<div class="text-sm text-muted" style="padding:.5rem 0;">Tidak ada match ditemukan.</div>`;
}

// ── Highlight overlay ────────────────────────────────────────
function renderHighlight(subject, matches) {
  const hl = document.getElementById('subject-highlight');
  if (!hl) return;

  let result = '';
  let last = 0;
  matches.forEach((m, i) => {
    result += esc(subject.slice(last, m.index));
    result += `<mark class="match-${i % 5}">${esc(m.match)}</mark>`;
    last = m.index + m.match.length;
  });
  result += esc(subject.slice(last));
  hl.innerHTML = result;
}

function clearHighlight() {
  const hl = document.getElementById('subject-highlight');
  if (hl) hl.innerHTML = '';
}

// ── Replace mode ─────────────────────────────────────────────
function runReplace(re, subject) {
  const repl = document.getElementById('replace-input').value;
  // Convert ${name} to $<name> for named groups
  const phpRepl = repl.replace(/\$\{(\w+)\}/g, '$<$1>');
  try {
    const result = subject.replace(re, phpRepl || repl);
    document.getElementById('results-area').innerHTML = `
      <div class="form-group">
        <label>Hasil replace</label>
        <div class="copy-wrap">
          <div class="replace-output" id="js-replace-out">${esc(result)}</div>
          <button class="copy-btn" onclick="copyText(document.getElementById('js-replace-out').textContent, this)">SALIN</button>
        </div>
      </div>`;
    setStatus('match', '✓', 'Replace berhasil');
    renderHighlight(subject, collectMatches(re, subject));
  } catch(e) {
    setStatus('error', '✕', 'Replace error: ' + e.message);
  }
}

function collectMatches(re, subject) {
  const matches = [];
  let m;
  const r2 = new RegExp(re.source, re.flags.includes('g') ? re.flags : re.flags + 'g');
  r2.lastIndex = 0;
  while ((m = r2.exec(subject)) !== null) {
    matches.push({ index: m.index, match: m[0] });
    if (m[0].length === 0) r2.lastIndex++;
  }
  return matches;
}

// ── Split mode ────────────────────────────────────────────────
function runSplit(re, subject) {
  const parts = subject.split(re).filter(p => p !== '');
  document.getElementById('results-area').innerHTML = `
    <div class="form-group">
      <label>${parts.length} bagian setelah split</label>
      <div class="split-list">
        ${parts.map((p, i) =>
          `<span class="split-chip"><span class="si">#${i+1}</span>${esc(p)}</span>`
        ).join('')}
      </div>
    </div>`;
  setStatus(parts.length > 0 ? 'match' : 'no-match', parts.length > 0 ? '✓' : '✕',
    parts.length + ' bagian');
}

// ── Status bar ────────────────────────────────────────────────
function setStatus(cls, icon, text) {
  const bar = document.getElementById('status-bar');
  const ic  = document.getElementById('status-icon');
  const tx  = document.getElementById('status-text');
  bar.className = 'status-bar ' + cls;
  if (ic) ic.textContent = icon;
  if (tx) tx.textContent = text;
}

// ── Flag pills ────────────────────────────────────────────────
function toggleFlag(f) {
  const el    = document.getElementById('flags');
  const pill  = document.getElementById('pill-' + f);
  const flags = el.value;
  el.value = flags.includes(f) ? flags.replace(f, '') : flags + f;
  syncFlagPills();
  runJS();
}

function syncFlagPills() {
  const flags = document.getElementById('flags').value;
  document.querySelectorAll('.flag-pill').forEach(p => {
    const f = p.id.replace('pill-', '');
    p.classList.toggle('active', flags.includes(f));
  });
}

// ── Explain pattern ───────────────────────────────────────────
const EXPLAIN_MAP = [
  [/^\^$/, 'et-anchor', 'Awal string (atau baris jika flag m)'],
  [/^\$$/, 'et-anchor', 'Akhir string (atau baris jika flag m)'],
  [/^\\b$/, 'et-anchor', 'Word boundary'],
  [/^\\B$/, 'et-anchor', 'Non-word boundary'],
  [/^\.$/, 'et-class', 'Karakter apapun (kecuali newline)'],
  [/^\\d$/, 'et-class', 'Digit [0-9]'],
  [/^\\D$/, 'et-class', 'Non-digit [^0-9]'],
  [/^\\w$/, 'et-class', 'Word char [a-zA-Z0-9_]'],
  [/^\\W$/, 'et-class', 'Non-word char'],
  [/^\\s$/, 'et-class', 'Whitespace'],
  [/^\\S$/, 'et-class', 'Non-whitespace'],
  [/^\\n$/, 'et-escape', 'Newline'],
  [/^\\t$/, 'et-escape', 'Tab'],
  [/^\*$/, 'et-quant', '0 atau lebih (greedy)'],
  [/^\*\?$/, 'et-quant', '0 atau lebih (lazy)'],
  [/^\+$/, 'et-quant', '1 atau lebih (greedy)'],
  [/^\+\?$/, 'et-quant', '1 atau lebih (lazy)'],
  [/^\?$/, 'et-quant', '0 atau 1 (opsional)'],
  [/^\{(\d+)\}$/, 'et-quant', 'Tepat $1 kali'],
  [/^\{(\d+),\}$/, 'et-quant', 'Minimal $1 kali'],
  [/^\{(\d+),(\d+)\}$/, 'et-quant', 'Antara $1 dan $2 kali'],
  [/^\|$/, 'et-alt', 'Atau (alternation)'],
];

function explainPattern(pattern) {
  const el = document.getElementById('explain-output');
  if (!el || !explainVisible) return;

  if (!pattern) { el.innerHTML = '—'; return; }

  // Tokenize pattern sederhana
  const tokens = [];
  let i = 0;
  while (i < pattern.length) {
    const ch = pattern[i];
    // Groups
    if (ch === '(') {
      let depth = 1, j = i + 1;
      while (j < pattern.length && depth > 0) {
        if (pattern[j] === '(' && pattern[j-1] !== '\\') depth++;
        if (pattern[j] === ')' && pattern[j-1] !== '\\') depth--;
        j++;
      }
      const inner = pattern.slice(i, j);
      let gtype = 'et-group', gdesc = 'Capture group';
      if (inner.startsWith('(?:'))       { gtype = 'et-group'; gdesc = 'Non-capture group'; }
      else if (inner.startsWith('(?='))  { gtype = 'et-group'; gdesc = 'Lookahead positif'; }
      else if (inner.startsWith('(?!'))  { gtype = 'et-group'; gdesc = 'Lookahead negatif'; }
      else if (inner.startsWith('(?<=')) { gtype = 'et-group'; gdesc = 'Lookbehind positif'; }
      else if (inner.startsWith('(?<!')) { gtype = 'et-group'; gdesc = 'Lookbehind negatif'; }
      else if (inner.startsWith('(?<'))  { const nm = inner.match(/^\(\?<(\w+)>/); gdesc = 'Named group: ' + (nm ? nm[1] : '?'); }
      tokens.push({ raw: inner, cls: gtype, desc: gdesc });
      i = j; continue;
    }
    // Character classes [...]
    if (ch === '[') {
      let j = i + 1;
      while (j < pattern.length && pattern[j] !== ']') j++;
      const inner = pattern.slice(i, j + 1);
      const negated = inner[1] === '^';
      tokens.push({ raw: inner, cls: 'et-class', desc: (negated ? 'Negated ' : '') + 'Character class' });
      i = j + 1; continue;
    }
    // Escaped sequences
    if (ch === '\\' && i + 1 < pattern.length) {
      const seq = '\\' + pattern[i+1];
      let cls = 'et-escape', desc = 'Escaped char';
      for (const [re, c, d] of EXPLAIN_MAP) {
        if (re.test(seq)) { cls = c; desc = d; break; }
      }
      tokens.push({ raw: seq, cls, desc });
      i += 2; continue;
    }
    // Quantifiers
    if ('*+?'.includes(ch) || (ch === '{' && /\{\d+/.test(pattern.slice(i)))) {
      let raw = ch;
      if (ch === '{') {
        const end = pattern.indexOf('}', i);
        raw = pattern.slice(i, end + 1);
        i = end + 1;
      } else { i++; }
      if (pattern[i] === '?') { raw += '?'; i++; }
      let desc = 'Quantifier';
      for (const [re, , d] of EXPLAIN_MAP) {
        if (re.test(raw)) { desc = d.replace('$1', raw.match(/\d+/)?.[0] || '').replace('$2', raw.match(/\d+.*?(\d+)/)?.[1] || ''); break; }
      }
      tokens.push({ raw, cls: 'et-quant', desc });
      continue;
    }
    // Anchors & alternation
    if ('^$|'.includes(ch)) {
      let cls = ch === '|' ? 'et-alt' : 'et-anchor';
      let desc = ch === '^' ? 'Awal string' : ch === '$' ? 'Akhir string' : 'Atau';
      tokens.push({ raw: ch, cls, desc });
      i++; continue;
    }
    // Literal char
    tokens.push({ raw: ch, cls: 'et-char', desc: `Karakter literal "${ch}"` });
    i++;
  }

  el.innerHTML = tokens.map(t =>
    `<span class="explain-token ${t.cls}" title="${esc(t.desc)}">${esc(t.raw)}</span>`
  ).join(' ');
}

// ── Toggle explain ────────────────────────────────────────────
function toggleExplain() {
  explainVisible = !explainVisible;
  document.getElementById('explain-panel').style.display = explainVisible ? '' : 'none';
  document.getElementById('explain-toggle-text').textContent = explainVisible ? 'Sembunyikan' : 'Explain';
  if (explainVisible) explainPattern(document.getElementById('pattern').value);
}

// ── Load library pattern ──────────────────────────────────────
function loadLibrary(pattern, flags) {
  document.getElementById('pattern').value = pattern;
  document.getElementById('flags').value   = flags || 'g';
  syncFlagPills();
  runJS();
  document.getElementById('pattern').focus();
}

// ── Clear ─────────────────────────────────────────────────────
function clearAll() {
  document.getElementById('pattern').value  = '';
  document.getElementById('flags').value    = 'g';
  document.getElementById('subject').value  = '';
  if (document.getElementById('replace-input'))
    document.getElementById('replace-input').value = '';
  syncFlagPills();
  clearResults();
  clearHighlight();
  setStatus('idle', '○', 'Masukkan pattern dan teks untuk mulai');
  const badge = document.getElementById('match-count-badge');
  if (badge) badge.style.display = 'none';
  document.getElementById('pattern-row').classList.remove('error');
  document.getElementById('regex-error').style.display = 'none';
}

function clearResults() {
  document.getElementById('results-area').innerHTML = '';
}

function copyPattern() {
  const pat   = document.getElementById('pattern').value;
  const flags = document.getElementById('flags').value;
  if (!pat) return;
  copyText('/' + pat + '/' + flags);
}

// ── Utility ───────────────────────────────────────────────────
function copyText(text, btn) {
  if (!text) return;
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) { showToast && showToast('Disalin!', 'success', 1500); return; }
    const orig = btn.textContent;
    btn.textContent = '✓ TERSALIN';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────────
setAction(currentAction);
syncFlagPills();
<?php if ($post_pattern || $post_subject): ?>
runJS();
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>