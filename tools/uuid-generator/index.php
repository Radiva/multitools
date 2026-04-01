<?php
require '../../includes/config.php';
/**
 * Multi Tools — UUID Generator
 * Generate UUID v1, v4, v5, v6, v7, ULID, dan Nano ID.
 * Mendukung generate tunggal, massal, format kustom, dan validasi.
 * ============================================================ */

// ── UUID helpers ──────────────────────────────────────────────

/** UUID v4 — random (RFC 4122) */
function uuidV4(): string {
  $data    = random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** UUID v1 — time-based (simplified, non-MAC) */
function uuidV1(): string {
  $time  = microtime(true) * 10000000 + 0x01b21dd213814000;
  $timeL = $time & 0xFFFFFFFF;
  $timeM = ($time >> 32) & 0xFFFF;
  $timeH = (($time >> 48) & 0x0FFF) | 0x1000;
  $clock = random_int(0, 0x3FFF) | 0x8000;
  $node  = bin2hex(random_bytes(6));
  return sprintf('%08x-%04x-%04x-%04x-%s', $timeL, $timeM, $timeH, $clock, $node);
}

/** UUID v5 — name-based SHA-1 */
function uuidV5(string $namespace, string $name): string {
  // Parse namespace UUID
  $nhex = str_replace('-', '', $namespace);
  if (strlen($nhex) !== 32 || !ctype_xdigit($nhex)) {
    // Fallback ke DNS namespace
    $nhex = '6ba7b8109dad11d180b400c04fd430c8';
  }
  $nbin = hex2bin($nhex);
  $hash = sha1($nbin . $name);
  $h    = str_split($hash, 2);
  // Set version 5 dan variant
  $h[6]  = dechex((hexdec($h[6]) & 0x0f) | 0x50);
  $h[8]  = dechex((hexdec($h[8]) & 0x3f) | 0x80);
  $hStr  = implode('', $h);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(substr($hStr, 0, 32), 4));
}

/** UUID v7 — Unix timestamp ordered (draft RFC) */
function uuidV7(): string {
  $ms    = (int)(microtime(true) * 1000);
  $msHex = str_pad(dechex($ms), 12, '0', STR_PAD_LEFT);
  $rand  = bin2hex(random_bytes(10));
  // version 7, variant RFC 4122
  $ver   = dechex((hexdec(substr($rand, 0, 2)) & 0x0f) | 0x70);
  $var   = dechex((hexdec(substr($rand, 4, 2)) & 0x3f) | 0x80);
  $uuid  = substr($msHex, 0, 8) . '-'
         . substr($msHex, 8, 4) . '-'
         . $ver . substr($rand, 1, 3) . '-'
         . $var . substr($rand, 5, 3) . '-'
         . substr($rand, 8, 12);
  return $uuid;
}

/** ULID — Universally Unique Lexicographically Sortable Identifier */
function generateULID(): string {
  $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
  $ts    = (int)(microtime(true) * 1000);
  $time  = '';
  for ($i = 9; $i >= 0; $i--) {
    $time  = $chars[$ts % 32] . $time;
    $ts    = (int)($ts / 32);
  }
  $random = '';
  for ($i = 0; $i < 16; $i++) {
    $random .= $chars[random_int(0, 31)];
  }
  return $time . $random;
}

/** Nano ID — URL-friendly unique string */
function generateNanoID(int $size = 21, string $alphabet = '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
  $len    = strlen($alphabet);
  $mask   = (int)(2 ** ceil(log($len, 2))) - 1;
  $result = '';
  while (strlen($result) < $size) {
    $bytes = random_bytes($size);
    foreach (str_split($bytes) as $byte) {
      $idx = ord($byte) & $mask;
      if ($idx < $len) {
        $result .= $alphabet[$idx];
        if (strlen($result) === $size) break;
      }
    }
  }
  return $result;
}

/** Validasi apakah string adalah UUID yang valid */
function isValidUUID(string $uuid): bool {
  return (bool)preg_match(
    '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
    $uuid
  );
}

/** Deteksi versi UUID dari string */
function detectUUIDVersion(string $uuid): string {
  if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-([1-7])[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid, $m)) {
    return 'Tidak valid';
  }
  return 'v' . $m[1];
}

// ── Namespace UUID standar ─────────────────────────────────────
const NS_UUID = [
  'DNS'  => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
  'URL'  => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
  'OID'  => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
  'X500' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
];

// ── Handle POST ──────────────────────────────────────────────
$server_results  = [];
$server_error    = '';
$post_mode       = 'generate'; // generate | validate | bulk
$post_type       = 'v4';       // v1 | v4 | v5 | v7 | ulid | nanoid
$post_count      = 1;
$post_uppercase  = false;
$post_braces     = false;
$post_no_hyphens = false;
$post_v5_ns      = 'DNS';
$post_v5_name    = '';
$post_validate   = '';
$post_bulk_count = 10;
$post_nano_size  = 21;
$validate_result = null;
$validate_info   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode       = in_array($_POST['mode'] ?? 'generate', ['generate','validate','bulk'])
                       ? $_POST['mode'] : 'generate';
  $post_type       = in_array($_POST['type'] ?? 'v4', ['v1','v4','v5','v7','ulid','nanoid'])
                       ? $_POST['type'] : 'v4';
  $post_count      = max(1, min(50, (int)($_POST['count'] ?? 1)));
  $post_uppercase  = isset($_POST['uppercase']);
  $post_braces     = isset($_POST['braces']);
  $post_no_hyphens = isset($_POST['no_hyphens']);
  $post_v5_ns      = array_key_exists($_POST['v5_namespace'] ?? '', NS_UUID)
                       ? $_POST['v5_namespace'] : 'DNS';
  $post_v5_name    = $_POST['v5_name'] ?? '';
  $post_validate   = trim($_POST['validate_input'] ?? '');
  $post_bulk_count = max(1, min(100, (int)($_POST['bulk_count'] ?? 10)));
  $post_nano_size  = max(6, min(64, (int)($_POST['nano_size'] ?? 21)));

  $format = function(string $id) use ($post_uppercase, $post_braces, $post_no_hyphens): string {
    if ($post_uppercase)  $id = strtoupper($id);
    if ($post_no_hyphens) $id = str_replace('-', '', $id);
    if ($post_braces)     $id = '{' . $id . '}';
    return $id;
  };

  switch ($post_mode) {

    case 'generate':
      $count = $post_count;
      if ($post_type === 'v5' && trim($post_v5_name) === '') {
        $server_error = 'Nama (name) wajib diisi untuk UUID v5.';
        break;
      }
      for ($i = 0; $i < $count; $i++) {
        switch ($post_type) {
          case 'v1':     $id = uuidV1(); break;
          case 'v4':     $id = uuidV4(); break;
          case 'v5':     $id = uuidV5(NS_UUID[$post_v5_ns], $post_v5_name); break;
          case 'v7':     $id = uuidV7(); break;
          case 'ulid':   $id = generateULID(); break;
          case 'nanoid': $id = generateNanoID($post_nano_size); break;
          default:       $id = uuidV4();
        }
        // ULID & NanoID tidak diformat (uppercase/hyphen tidak relevan)
        if (in_array($post_type, ['ulid','nanoid'])) {
          $server_results[] = $post_uppercase ? strtoupper($id) : $id;
        } else {
          $server_results[] = $format($id);
        }
        // v5 deterministik — satu saja cukup
        if ($post_type === 'v5') break;
      }
      break;

    case 'validate':
      if ($post_validate === '') {
        $server_error = 'Masukkan UUID yang ingin divalidasi.';
        break;
      }
      $clean = trim(str_replace(['{','}'], '', $post_validate));
      $validate_result = isValidUUID($clean);
      if ($validate_result) {
        $ver = detectUUIDVersion($clean);
        $validate_info = [
          'version'  => $ver,
          'uppercase'=> $clean === strtoupper($clean),
          'braces'   => str_contains($post_validate, '{'),
          'length'   => strlen($clean),
          'nil'      => $clean === '00000000-0000-0000-0000-000000000000',
        ];
      }
      break;

    case 'bulk':
      for ($i = 0; $i < $post_bulk_count; $i++) {
        switch ($post_type) {
          case 'v1':     $id = uuidV1(); break;
          case 'v7':     $id = uuidV7(); break;
          case 'ulid':   $id = generateULID(); break;
          case 'nanoid': $id = generateNanoID($post_nano_size); break;
          default:       $id = uuidV4();
        }
        $server_results[] = in_array($post_type, ['ulid','nanoid'])
          ? ($post_uppercase ? strtoupper($id) : $id)
          : $format($id);
      }
      break;
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'UUID Generator Online — Generate UUID v4, v7, ULID, Nano ID | Multi Tools',
  'description' => 'Generate UUID v1, v4, v5, v7, ULID, dan Nano ID secara instan. Validasi UUID, format kustom (uppercase, braces, no-hyphen), dan generate massal hingga 100.',
  'keywords'    => 'uuid generator, uuid v4, uuid v7, ulid, nano id, generate uuid online, validasi uuid, unique id, multi tools',
  'og_title'    => 'UUID Generator Online — UUID v4, v7, ULID, Nano ID',
  'og_desc'     => 'Generate dan validasi UUID v1/v4/v5/v7, ULID, Nano ID. Format kustom dan bulk mode.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'UUID Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/uuid-generator#webpage',
      'url'         => SITE_URL . '/tools/uuid-generator',
      'name'        => 'UUID Generator Online',
      'description' => 'Generate UUID v1, v4, v5, v7, ULID, dan Nano ID secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',           'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools',   'item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'UUID Generator',    'item' => SITE_URL . '/tools/uuid-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'UUID Generator',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/uuid-generator',
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
.mode-tab:last-child  { border-right: none; }
.mode-tab:hover       { background: var(--surface); color: var(--text); }
.mode-tab.active      { background: var(--accent4); color: #fff; }

/* ── Type selector grid ── */
.type-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 480px) { .type-grid { grid-template-columns: repeat(2, 1fr); } }

.type-card {
  display: flex;
  flex-direction: column;
  gap: .2rem;
  padding: .65rem .8rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition);
  text-align: left;
  position: relative;
  overflow: hidden;
}
.type-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top left, color-mix(in srgb, var(--c) 12%, transparent), transparent 60%);
  opacity: 0;
  transition: opacity .3s;
}
.type-card:hover, .type-card.active {
  border-color: var(--c, var(--accent4));
  transform: translateY(-2px);
}
.type-card:hover::before, .type-card.active::before { opacity: 1; }
.type-card.active { box-shadow: 0 0 0 2px color-mix(in srgb, var(--c) 30%, transparent); }
.type-card .tc-name {
  font-weight: 800;
  font-size: .88rem;
  color: var(--c, var(--accent4));
  font-family: var(--font-mono);
}
.type-card .tc-desc { font-size: .72rem; color: var(--muted); line-height: 1.3; }
.type-card .tc-badge {
  font-size: .6rem;
  font-family: var(--font-mono);
  font-weight: 700;
  color: var(--c, var(--accent4));
  background: color-mix(in srgb, var(--c, var(--accent4)) 12%, transparent);
  border-radius: 4px;
  padding: 1px 5px;
  margin-top: .15rem;
  align-self: flex-start;
}
.tc-check {
  position: absolute;
  top: .4rem; right: .4rem;
  width: 16px; height: 16px;
  border-radius: 50%;
  background: var(--c, var(--accent4));
  color: #fff;
  font-size: .6rem;
  display: none;
  align-items: center;
  justify-content: center;
}
.type-card.active .tc-check { display: flex; }

/* ── UUID hero output ── */
.uuid-hero {
  background: var(--bg);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1.1rem 1.25rem;
  font-family: var(--font-mono);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .06em;
  word-break: break-all;
  line-height: 1.65;
  color: var(--text);
  transition: border-color .25s;
  min-height: 62px;
  position: relative;
}
.uuid-hero.active  { border-color: var(--accent4); }
.uuid-hero .placeholder { font-size: .88rem; font-weight: 400; color: var(--muted); letter-spacing: 0; }

/* Warna per segmen UUID */
.uuid-seg-1 { color: #2563eb; }
.uuid-seg-2 { color: #0ea5e9; }
.uuid-seg-3 { color: #f59e0b; font-weight: 900; } /* versi */
.uuid-seg-4 { color: #7c3aed; }
.uuid-seg-5 { color: #10b981; }
.uuid-hyphen { color: var(--muted); }

/* ── Format options ── */
.fmt-pills {
  display: flex;
  flex-wrap: wrap;
  gap: .4rem;
  margin-top: .4rem;
}
.fmt-pill {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .75rem;
  border: 1px solid var(--border);
  border-radius: 99px;
  font-size: .78rem;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--surface);
  color: var(--muted);
  user-select: none;
}
.fmt-pill:hover { border-color: var(--accent4); color: var(--accent4); background: rgba(245,158,11,.06); }
.fmt-pill.active { border-color: var(--accent4); color: var(--accent4); background: rgba(245,158,11,.1); font-weight: 700; }
.fmt-pill input[type="checkbox"] { display: none; }

/* ── UUID list (bulk) ── */
.uuid-list {
  display: flex;
  flex-direction: column;
  gap: .35rem;
  max-height: 400px;
  overflow-y: auto;
}
.uuid-list-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .45rem .75rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: border-color var(--transition);
}
.uuid-list-item:hover { border-color: var(--accent4); }
.uuid-list-item .uuid-num {
  font-family: var(--font-mono);
  font-size: .68rem;
  color: var(--muted);
  min-width: 22px;
  flex-shrink: 0;
}
.uuid-list-item .uuid-text {
  flex: 1;
  font-family: var(--font-mono);
  font-size: .8rem;
  word-break: break-all;
  color: var(--text);
}
.uuid-copy-btn {
  padding: .2rem .5rem;
  font-size: .65rem;
  font-family: var(--font-mono);
  font-weight: 700;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 4px;
  color: var(--muted);
  cursor: pointer;
  transition: all var(--transition);
  flex-shrink: 0;
}
.uuid-copy-btn:hover { background: var(--accent4); color: #fff; border-color: var(--accent4); }

/* ── Validate result ── */
.validate-box {
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  padding: 1rem 1.1rem;
  border-radius: var(--radius-sm);
  border: 1px solid;
  margin-top: 1rem;
}
.validate-box.valid   { background: #f0fdf4; border-color: #86efac; }
.validate-box.invalid { background: #fef2f2; border-color: #fca5a5; }
.validate-icon { font-size: 1.4rem; line-height: 1; flex-shrink: 0; }
.validate-info strong { display: block; font-size: .9rem; margin-bottom: .5rem; }
.info-row {
  display: flex;
  gap: .5rem;
  align-items: center;
  font-size: .8rem;
  margin-bottom: .3rem;
}
.info-key {
  font-family: var(--font-mono);
  font-size: .7rem;
  color: var(--muted);
  min-width: 80px;
  flex-shrink: 0;
}
.info-val { font-weight: 600; color: var(--text); }

/* ── History ── */
.history-list {
  display: flex;
  flex-direction: column;
  gap: .3rem;
  max-height: 260px;
  overflow-y: auto;
}
.history-item {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .38rem .6rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: var(--font-mono);
  font-size: .72rem;
  background: var(--bg);
  transition: border-color var(--transition);
}
.history-item:hover { border-color: var(--accent4); }
.history-item .h-id { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text); }
.history-item .h-type { font-size: .6rem; color: var(--accent4); font-weight: 700; flex-shrink: 0; }
.history-item .h-cp { border: none; background: none; color: var(--muted); font-size: .7rem; cursor: pointer; padding: 0 .2rem; }
.history-item .h-cp:hover { color: var(--accent4); }
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
        <span aria-hidden="true">🆔</span> UUID <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate UUID v1, v4, v5, v7, ULID, dan Nano ID secara instan dan kriptografis aman.
        Validasi, format kustom, dan generate massal hingga 100 sekaligus.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php
        $tabs = ['generate' => '🆔 Generate', 'validate' => '✔ Validasi', 'bulk' => '📋 Massal'];
        foreach ($tabs as $val => $lbl): ?>
          <button type="button" role="tab"
            class="mode-tab <?= $post_mode === $val ? 'active' : '' ?>"
            onclick="switchTab('<?= $val ?>')">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="uuid-form" novalidate>
        <input type="hidden" id="mode-input" name="mode" value="<?= e($post_mode) ?>" />
        <input type="hidden" id="type-input" name="type" value="<?= e($post_type) ?>" />

        <!-- ══ PANEL: Generate ══ -->
        <div id="panel-generate" class="mode-panel"
          <?= $post_mode !== 'generate' ? 'style="display:none;"' : '' ?>>

          <!-- Tipe selector -->
          <div class="form-group">
            <label>Tipe identifier</label>
            <div class="type-grid" id="type-grid">
              <?php
              $types = [
                'v4'     => ['name'=>'UUID v4',  'desc'=>'Acak penuh, paling umum',      'badge'=>'RFC 4122',  'c'=>'#f59e0b'],
                'v7'     => ['name'=>'UUID v7',  'desc'=>'Time-ordered, sortable',        'badge'=>'Draft RFC', 'c'=>'#2563eb'],
                'v1'     => ['name'=>'UUID v1',  'desc'=>'Berbasis waktu',                'badge'=>'RFC 4122',  'c'=>'#0ea5e9'],
                'v5'     => ['name'=>'UUID v5',  'desc'=>'Deterministik SHA-1',           'badge'=>'RFC 4122',  'c'=>'#7c3aed'],
                'ulid'   => ['name'=>'ULID',     'desc'=>'Lexicographically sortable',    'badge'=>'Spec',      'c'=>'#10b981'],
                'nanoid' => ['name'=>'Nano ID',  'desc'=>'URL-friendly, kompak',          'badge'=>'Library',   'c'=>'#ec4899'],
              ];
              foreach ($types as $val => $t): ?>
                <button type="button"
                  class="type-card <?= $post_type === $val ? 'active' : '' ?>"
                  style="--c:<?= $t['c'] ?>"
                  onclick="setType('<?= $val ?>')"
                  data-type="<?= $val ?>">
                  <span class="tc-check">✓</span>
                  <span class="tc-name"><?= $t['name'] ?></span>
                  <span class="tc-desc"><?= $t['desc'] ?></span>
                  <span class="tc-badge"><?= $t['badge'] ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- UUID hero display -->
          <div class="form-group">
            <label>Identifier yang dihasilkan</label>
            <div class="uuid-hero <?= !empty($server_results) && $post_mode==='generate' ? 'active' : '' ?>"
              id="uuid-hero">
              <?php if (!empty($server_results) && $post_mode === 'generate'): ?>
                <?php echo renderUUIDColored($server_results[0], $post_type); ?>
              <?php else: ?>
                <span class="placeholder">Klik "Generate" untuk menghasilkan identifier baru...</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Format options -->
          <div class="form-group" id="fmt-section">
            <label>Format output</label>
            <div class="fmt-pills">
              <label class="fmt-pill <?= $post_uppercase ? 'active' : '' ?>" id="pill-upper">
                <input type="checkbox" name="uppercase" id="fmt-upper"
                  <?= $post_uppercase ? 'checked' : '' ?>
                  onchange="togglePill(this, 'pill-upper')" />
                UPPERCASE
              </label>
              <label class="fmt-pill <?= $post_braces ? 'active' : '' ?>" id="pill-braces">
                <input type="checkbox" name="braces" id="fmt-braces"
                  <?= $post_braces ? 'checked' : '' ?>
                  onchange="togglePill(this, 'pill-braces')" />
                {dengan braces}
              </label>
              <label class="fmt-pill <?= $post_no_hyphens ? 'active' : '' ?>" id="pill-nohyphen">
                <input type="checkbox" name="no_hyphens" id="fmt-nohyphen"
                  <?= $post_no_hyphens ? 'checked' : '' ?>
                  onchange="togglePill(this, 'pill-nohyphen')" />
                Tanpa tanda hubung
              </label>
            </div>
          </div>

          <!-- UUID v5 options -->
          <div id="v5-options" style="<?= $post_type !== 'v5' ? 'display:none;' : '' ?>">
            <div class="alert info" style="margin-bottom:1rem;">
              <span>ℹ</span>
              <span>UUID v5 bersifat <strong>deterministik</strong> — namespace + name yang sama selalu menghasilkan UUID yang sama.</span>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="v5-ns">Namespace</label>
                <select id="v5-ns" name="v5_namespace">
                  <?php foreach (NS_UUID as $ns => $val): ?>
                    <option value="<?= $ns ?>" <?= $post_v5_ns === $ns ? 'selected' : '' ?>>
                      <?= $ns ?> (<?= $val ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="v5-name">Name</label>
                <input type="text" id="v5-name" name="v5_name"
                  placeholder="Contoh: https://example.com"
                  value="<?= e($post_v5_name) ?>" />
              </div>
            </div>
          </div>

          <!-- Nano ID size -->
          <div id="nanoid-options" style="<?= $post_type !== 'nanoid' ? 'display:none;' : '' ?>">
            <div class="form-group">
              <label for="nano-size">Panjang Nano ID (6–64)</label>
              <input type="number" id="nano-size" name="nano_size"
                min="6" max="64" value="<?= $post_nano_size ?>"
                style="max-width:100px;" />
            </div>
          </div>

          <!-- Jumlah -->
          <div class="form-group" id="count-section">
            <label for="gen-count">Jumlah yang digenerate</label>
            <input type="number" id="gen-count" name="count"
              min="1" max="50" value="<?= $post_count ?>"
              style="max-width:100px;" />
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent4); border-color:var(--accent4);">
              🆔 Generate
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="copyHero()">📋 Salin</button>
          </div>
        </div>

        <!-- ══ PANEL: Validate ══ -->
        <div id="panel-validate" class="mode-panel"
          <?= $post_mode !== 'validate' ? 'style="display:none;"' : '' ?>>

          <div class="form-group">
            <label for="validate-input">UUID yang ingin divalidasi</label>
            <input type="text" id="validate-input" name="validate_input"
              placeholder="Contoh: 550e8400-e29b-41d4-a716-446655440000"
              value="<?= e($post_validate) ?>"
              oninput="validateJS(this.value)"
              style="font-family:var(--font-mono); letter-spacing:.04em;" />
            <div style="font-family:var(--font-mono); font-size:.7rem; color:var(--muted); margin-top:.3rem;"
              id="validate-len">0 karakter</div>
          </div>

          <!-- Hasil validasi realtime -->
          <div id="validate-result-js" style="display:none;" class="validate-box">
            <span class="validate-icon" id="vr-icon"></span>
            <div class="validate-info">
              <strong id="vr-title"></strong>
              <div id="vr-details"></div>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent4); border-color:var(--accent4);">
              ✔ Validasi via Server (PHP)
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="document.getElementById('validate-input').value=''; validateJS(''); document.getElementById('validate-result-js').style.display='none';">
              Bersihkan
            </button>
          </div>
        </div>

        <!-- ══ PANEL: Bulk ══ -->
        <div id="panel-bulk" class="mode-panel"
          <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>

          <!-- Tipe selector (simplified) -->
          <div class="form-group">
            <label for="bulk-type">Tipe identifier</label>
            <select name="type" id="bulk-type-select"
              onchange="document.getElementById('type-input').value = this.value">
              <?php foreach ($types as $val => $t): ?>
                <option value="<?= $val ?>" <?= ($post_type === $val || ($val==='v4' && $post_type==='v5')) ? 'selected' : '' ?>>
                  <?= $t['name'] ?> — <?= $t['desc'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="bulk-count-input">Jumlah (maks. 100)</label>
              <input type="number" id="bulk-count-input" name="bulk_count"
                min="1" max="100" value="<?= $post_bulk_count ?>" />
            </div>
            <div class="form-group">
              <label for="bulk-nano-size">Panjang Nano ID</label>
              <input type="number" id="bulk-nano-size" name="nano_size"
                min="6" max="64" value="<?= $post_nano_size ?>" />
            </div>
          </div>

          <div class="form-group">
            <label>Format output</label>
            <div class="fmt-pills">
              <label class="fmt-pill" id="pill-upper-b">
                <input type="checkbox" name="uppercase" id="fmt-upper-b"
                  onchange="togglePill(this, 'pill-upper-b')" />
                UPPERCASE
              </label>
              <label class="fmt-pill" id="pill-braces-b">
                <input type="checkbox" name="braces" id="fmt-braces-b"
                  onchange="togglePill(this, 'pill-braces-b')" />
                {dengan braces}
              </label>
              <label class="fmt-pill" id="pill-nohyphen-b">
                <input type="checkbox" name="no_hyphens" id="fmt-nohyphen-b"
                  onchange="togglePill(this, 'pill-nohyphen-b')" />
                Tanpa tanda hubung
              </label>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm"
              style="background:var(--accent4); border-color:var(--accent4);">
              📋 Generate Massal
            </button>
          </div>
        </div>

      </form><!-- /#uuid-form -->
    </div><!-- /.panel -->

    <!-- ── Hasil server: list ── -->
    <?php if (!empty($server_results) && ($post_mode !== 'generate' || count($server_results) > 1)): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>Berhasil generate <strong><?= count($server_results) ?> identifier</strong>.</span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
          <div class="panel-title" style="margin-bottom:0;">⚙ Hasil Generate</div>
          <div style="display:flex; gap:.5rem;">
            <button class="btn-ghost btn-sm" onclick="copyAll()">📋 Salin semua</button>
            <button class="btn-ghost btn-sm" onclick="downloadAll()">⬇ Unduh .txt</button>
          </div>
        </div>
        <div class="uuid-list" id="server-uuid-list">
          <?php foreach ($server_results as $idx => $id): ?>
            <div class="uuid-list-item">
              <span class="uuid-num"><?= $idx + 1 ?>.</span>
              <span class="uuid-text"><?= e($id) ?></span>
              <button class="uuid-copy-btn"
                onclick="copyText(<?= htmlspecialchars(json_encode($id), ENT_QUOTES) ?>, this)">
                SALIN
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Hasil validasi server ── -->
    <?php if ($post_mode === 'validate' && $validate_result !== null): ?>
      <div class="validate-box <?= $validate_result ? 'valid' : 'invalid' ?>"
        style="margin-top:1rem;" role="alert">
        <span class="validate-icon"><?= $validate_result ? '✅' : '❌' ?></span>
        <div class="validate-info">
          <strong style="color:<?= $validate_result ? '#15803d' : '#b91c1c' ?>">
            <?= $validate_result ? 'UUID Valid!' : 'UUID Tidak Valid' ?>
          </strong>
          <?php if ($validate_result): ?>
            <div class="info-row"><span class="info-key">Versi</span><span class="info-val badge accent"><?= e($validate_info['version']) ?></span></div>
            <div class="info-row"><span class="info-key">Panjang</span><span class="info-val"><?= $validate_info['length'] ?> karakter</span></div>
            <div class="info-row"><span class="info-key">Format</span><span class="info-val"><?= $validate_info['uppercase'] ? 'UPPERCASE' : 'lowercase' ?><?= $validate_info['braces'] ? ' + braces' : '' ?></span></div>
            <?php if ($validate_info['nil']): ?>
              <div class="info-row"><span class="info-key">Catatan</span><span class="info-val" style="color:#f59e0b;">Nil UUID (semua nol)</span></div>
            <?php endif; ?>
          <?php else: ?>
            <div style="font-size:.82rem; color:#b91c1c;">Format tidak sesuai RFC 4122. UUID harus: xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx</div>
          <?php endif; ?>
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
    <!-- Riwayat -->
    <div class="panel">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
        <div class="panel-title" style="margin-bottom:0;">🕑 Riwayat</div>
        <button class="btn-ghost btn-sm" onclick="clearHistory()"
          style="padding:.25rem .6rem; font-size:.75rem;">Hapus</button>
      </div>
      <div class="history-list" id="history-list">
        <div style="text-align:center; padding:1.25rem; color:var(--muted); font-size:.82rem;">
          Belum ada riwayat
        </div>
      </div>
    </div>

    <!-- Perbandingan tipe -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📊 Perbandingan Tipe</div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:.75rem;">
          <thead>
            <tr>
              <th style="padding:.4rem .5rem; text-align:left; border-bottom:1px solid var(--border); color:var(--muted); font-family:var(--font-mono); font-size:.65rem; letter-spacing:.06em; text-transform:uppercase;">Tipe</th>
              <th style="padding:.4rem .5rem; text-align:left; border-bottom:1px solid var(--border); color:var(--muted); font-family:var(--font-mono); font-size:.65rem; letter-spacing:.06em; text-transform:uppercase;">Sortable</th>
              <th style="padding:.4rem .5rem; text-align:left; border-bottom:1px solid var(--border); color:var(--muted); font-family:var(--font-mono); font-size:.65rem; letter-spacing:.06em; text-transform:uppercase;">Panjang</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $cmp = [
              ['UUID v4',  '✕', '36', 'Random, tipe paling umum'],
              ['UUID v7',  '✓', '36', 'Time-ordered, baik untuk DB'],
              ['UUID v1',  '≈', '36', 'Time-based, tidak sortable murni'],
              ['UUID v5',  '✕', '36', 'Deterministik dari nama'],
              ['ULID',     '✓', '26', 'Sortable & URL-friendly'],
              ['Nano ID',  '✕', '21*','Kompak & URL-friendly'],
            ];
            foreach ($cmp as [$name, $sort, $len, $note]): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:.38rem .5rem; font-family:var(--font-mono); font-size:.72rem; font-weight:700; color:var(--accent4);"><?= $name ?></td>
                <td style="padding:.38rem .5rem; text-align:center;"><?= $sort ?></td>
                <td style="padding:.38rem .5rem; font-family:var(--font-mono); font-size:.7rem;"><?= $len ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="text-xs text-muted" style="margin-top:.4rem;">* default, bisa dikonfigurasi</div>
      </div>
    </div>

    <!-- Panduan penggunaan -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">💡 Kapan Pakai Apa?</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><strong>v4</strong> — default, ID umum, session token</li>
        <li><strong>v7</strong> — primary key database (sortable!)</li>
        <li><strong>v5</strong> — ID deterministik dari URL/nama</li>
        <li><strong>ULID</strong> — log, event, sortable ID</li>
        <li><strong>Nano ID</strong> — URL shortener, token pendek</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/password-generator" class="btn-ghost btn-sm btn-full">Password Generator</a>
        <a href="/tools/md5-generator"      class="btn-ghost btn-sm btn-full">MD5 Generator</a>
        <a href="/tools/sha256-generator"   class="btn-ghost btn-sm btn-full">SHA256 Generator</a>
        <a href="/tools/base64"             class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<?php
// ── Helper untuk render UUID berwarna ─────────────────────────
function renderUUIDColored(string $uuid, string $type): string {
  if (in_array($type, ['ulid','nanoid'])) {
    return '<span style="color:var(--accent4);">' . htmlspecialchars($uuid) . '</span>';
  }
  $parts = explode('-', $uuid);
  if (count($parts) !== 5) {
    return '<span style="color:var(--accent4);">' . htmlspecialchars($uuid) . '</span>';
  }
  $colors = ['uuid-seg-1','uuid-seg-2','uuid-seg-3','uuid-seg-4','uuid-seg-5'];
  $html   = '';
  foreach ($parts as $i => $p) {
    if ($i > 0) $html .= '<span class="uuid-hyphen">-</span>';
    $html .= '<span class="' . $colors[$i] . '">' . htmlspecialchars($p) . '</span>';
  }
  return $html;
}
?>

<script>
/* ──────────────────────────────────────────
   UUID Generator — logika JS (realtime)
   Generate menggunakan crypto.randomUUID()
   dan crypto.getRandomValues() sebagai fallback.
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

// ── State ─────────────────────────────────────────────────────
let currentMode = '<?= $post_mode ?>';
let currentType = '<?= $post_type ?>';

// ── UUID v4 via Web Crypto ─────────────────────────────────────
function generateV4() {
  if (crypto.randomUUID) return crypto.randomUUID();
  // Fallback manual
  const b = crypto.getRandomValues(new Uint8Array(16));
  b[6] = (b[6] & 0x0f) | 0x40;
  b[8] = (b[8] & 0x3f) | 0x80;
  const hex = [...b].map(x => x.toString(16).padStart(2,'0')).join('');
  return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
}

// ── UUID v7 ────────────────────────────────────────────────────
function generateV7() {
  const ms  = BigInt(Date.now());
  const b   = crypto.getRandomValues(new Uint8Array(10));
  const msH = Number((ms >> 12n) & 0xFFFFFFFFn);
  const msL = Number(ms & 0xFFFn);
  b[0] = (b[0] & 0x0f) | 0x70;
  b[2] = (b[2] & 0x3f) | 0x80;
  const msHex = msH.toString(16).padStart(8,'0');
  const msMid = msL.toString(16).padStart(3,'0');
  const rand  = [...b].map(x=>x.toString(16).padStart(2,'0')).join('');
  return `${msHex}-${rand.slice(0,4)}-7${msMid}-${rand.slice(4,6)}${rand.slice(6,8)}-${rand.slice(8)}`;
}

// ── ULID ───────────────────────────────────────────────────────
function generateULID() {
  const CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
  let ts = Date.now();
  let time = '';
  for (let i = 9; i >= 0; i--) {
    time = CHARS[ts % 32] + time;
    ts = Math.floor(ts / 32);
  }
  const rnd = crypto.getRandomValues(new Uint8Array(16));
  let random = '';
  for (const b of rnd) random += CHARS[b % 32];
  return time + random;
}

// ── Nano ID ────────────────────────────────────────────────────
function generateNanoID(size = 21, alphabet = '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
  const len  = alphabet.length;
  const mask = (1 << Math.ceil(Math.log2(len))) - 1;
  let result = '';
  while (result.length < size) {
    const bytes = crypto.getRandomValues(new Uint8Array(size));
    for (const b of bytes) {
      const idx = b & mask;
      if (idx < len) {
        result += alphabet[idx];
        if (result.length === size) break;
      }
    }
  }
  return result;
}

// ── Format output ──────────────────────────────────────────────
function applyFormat(id) {
  const upper   = document.getElementById('fmt-upper')?.checked;
  const braces  = document.getElementById('fmt-braces')?.checked;
  const noHyph  = document.getElementById('fmt-nohyphen')?.checked;
  const isRaw   = ['ulid','nanoid'].includes(currentType);
  if (upper)   id = id.toUpperCase();
  if (!isRaw && noHyph) id = id.replace(/-/g, '');
  if (!isRaw && braces) id = '{' + id + '}';
  return id;
}

// ── Render colored UUID ────────────────────────────────────────
function colorizeUUID(uuid, type) {
  if (['ulid','nanoid'].includes(type)) {
    return `<span style="color:var(--accent4);">${esc(uuid)}</span>`;
  }
  const parts = uuid.replace(/[{}]/g,'').split('-');
  if (parts.length !== 5) return `<span style="color:var(--accent4);">${esc(uuid)}</span>`;
  const cls = ['uuid-seg-1','uuid-seg-2','uuid-seg-3','uuid-seg-4','uuid-seg-5'];
  const braces = uuid.startsWith('{');
  let html = braces ? '<span class="uuid-hyphen">{</span>' : '';
  parts.forEach((p, i) => {
    if (i > 0) html += '<span class="uuid-hyphen">-</span>';
    html += `<span class="${cls[i]}">${esc(p)}</span>`;
  });
  if (braces) html += '<span class="uuid-hyphen">}</span>';
  return html;
}

// ── Generate (JS) ──────────────────────────────────────────────
function generateJS() {
  let id = '';
  switch (currentType) {
    case 'v4':     id = generateV4(); break;
    case 'v7':     id = generateV7(); break;
    case 'v1':     id = generateV4(); break; // simplified
    case 'ulid':   id = generateULID(); break;
    case 'nanoid':
      const sz = parseInt(document.getElementById('nano-size')?.value) || 21;
      id = generateNanoID(sz); break;
    default:       id = generateV4();
  }
  id = applyFormat(id);
  const hero = document.getElementById('uuid-hero');
  hero.innerHTML = colorizeUUID(id, currentType);
  hero.classList.add('active');
  addHistory(id, currentType);
}

// ── Tab switching ─────────────────────────────────────────────
function switchTab(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;
  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    t.classList.toggle('active', ['generate','validate','bulk'][i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

// ── Type selection ────────────────────────────────────────────
function setType(type) {
  currentType = type;
  document.getElementById('type-input').value = type;
  document.querySelectorAll('.type-card').forEach(c => {
    c.classList.toggle('active', c.dataset.type === type);
  });
  // Show/hide conditional options
  document.getElementById('v5-options').style.display     = type === 'v5'     ? '' : 'none';
  document.getElementById('nanoid-options').style.display = type === 'nanoid' ? '' : 'none';
  // Format pills irrelevant for v5 (deterministic)
  const countSect = document.getElementById('count-section');
  if (countSect) countSect.style.display = type === 'v5' ? 'none' : '';
}

// ── Format pill toggle ────────────────────────────────────────
function togglePill(el, pillId) {
  const pill = document.getElementById(pillId);
  if (pill) pill.classList.toggle('active', el.checked);
}

// ── Validate UUID (JS, realtime) ──────────────────────────────
function validateJS(val) {
  const lenEl = document.getElementById('validate-len');
  const resEl = document.getElementById('validate-result-js');
  if (lenEl) lenEl.textContent = val.length + ' karakter';
  if (!val) { if (resEl) resEl.style.display = 'none'; return; }

  const clean   = val.trim().replace(/[{}]/g, '');
  const pattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  const valid   = pattern.test(clean);
  const verMatch = clean.match(/^[0-9a-f]{8}-[0-9a-f]{4}-([1-7])/i);
  const ver     = verMatch ? 'v' + verMatch[1] : '—';
  const isUpper = clean === clean.toUpperCase();
  const hasBraces = val.trim().startsWith('{');

  resEl.className = 'validate-box ' + (valid ? 'valid' : 'invalid');
  resEl.style.display = 'flex';
  document.getElementById('vr-icon').textContent = valid ? '✅' : '❌';
  document.getElementById('vr-title').textContent = valid ? 'UUID Valid!' : 'UUID Tidak Valid';
  document.getElementById('vr-title').style.color = valid ? '#15803d' : '#b91c1c';

  if (valid) {
    document.getElementById('vr-details').innerHTML = `
      <div class="info-row"><span class="info-key">Versi</span><span class="info-val"><span class="badge accent">${ver}</span></span></div>
      <div class="info-row"><span class="info-key">Format</span><span class="info-val">${isUpper ? 'UPPERCASE' : 'lowercase'}${hasBraces ? ' + braces' : ''}</span></div>
      <div class="info-row"><span class="info-key">Panjang</span><span class="info-val">${clean.length} karakter</span></div>
      ${clean === '00000000-0000-0000-0000-000000000000' ? '<div class="info-row"><span class="info-key">Catatan</span><span class="info-val" style="color:#f59e0b;">Nil UUID</span></div>' : ''}
    `;
  } else {
    document.getElementById('vr-details').innerHTML =
      '<div style="font-size:.8rem; color:#b91c1c;">Format tidak sesuai RFC 4122.<br>Contoh valid: 550e8400-e29b-41d4-a716-446655440000</div>';
  }
}

// ── Copy helpers ──────────────────────────────────────────────
function copyHero() {
  const el = document.getElementById('uuid-hero');
  const text = el?.innerText?.trim().replace(/[{}]/g, m => m);
  if (!text || text.includes('Klik')) return;
  copyText(text);
}

function copyAll() {
  const items = document.querySelectorAll('#server-uuid-list .uuid-text');
  const all   = Array.from(items).map(el => el.textContent.trim()).join('\n');
  if (!all) return;
  navigator.clipboard.writeText(all).then(() => {
    showToast && showToast('Semua UUID disalin!', 'success');
  });
}

function downloadAll() {
  const items = document.querySelectorAll('#server-uuid-list .uuid-text');
  const lines = Array.from(items).map((el, i) => `${i+1}. ${el.textContent.trim()}`);
  if (!lines.length) return;
  const blob = new Blob(
    ['UUID Generator — Multi Tools\n' + new Date().toLocaleString('id-ID') + '\n\n' + lines.join('\n')],
    { type: 'text/plain;charset=utf-8' }
  );
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'uuid-list.txt';
  a.click();
}

function copyText(text, btn) {
  if (!text) return;
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) { showToast && showToast('Disalin!', 'success', 1500); return; }
    const orig = btn.textContent;
    btn.textContent = '✓';
    btn.style.cssText = 'background:var(--accent4);border-color:var(--accent4);color:#fff;';
    setTimeout(() => { btn.textContent = orig; btn.style.cssText = ''; }, 1500);
  });
}

// ── History ───────────────────────────────────────────────────
let uuidHistory = [];

function addHistory(id, type) {
  uuidHistory.unshift({ id, type });
  if (uuidHistory.length > 20) uuidHistory.pop();
  renderHistory();
}

function renderHistory() {
  const el = document.getElementById('history-list');
  if (!uuidHistory.length) {
    el.innerHTML = '<div style="text-align:center; padding:1.25rem; color:var(--muted); font-size:.82rem;">Belum ada riwayat</div>';
    return;
  }
  el.innerHTML = uuidHistory.map((h, i) => `
    <div class="history-item">
      <span class="h-type">${esc(h.type.toUpperCase())}</span>
      <span class="h-id">${esc(h.id)}</span>
      <button class="h-cp" onclick="copyText(${JSON.stringify(h.id)})" title="Salin">📋</button>
    </div>`).join('');
}

function clearHistory() {
  uuidHistory = [];
  renderHistory();
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Auto-add server results ke history ───────────────────────
<?php if (!empty($server_results) && $post_mode === 'generate'): ?>
(function() {
  const results = <?= json_encode(array_slice($server_results, 0, 5)) ?>;
  results.forEach(id => addHistory(id, '<?= $post_type ?>'));
})();
<?php endif; ?>

// ── Init ──────────────────────────────────────────────────────
switchTab(currentMode);
setType(currentType);
</script>

<?php require '../../includes/footer.php'; ?>