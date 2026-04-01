<?php
require '../../includes/config.php';
/**
 * Multi Tools — Password Generator
 * Generate password acak yang kuat dengan berbagai opsi kustomisasi.
 * Mendukung generate tunggal, massal, passphrase, dan PIN.
 * ============================================================ */

// ── Karakter pool ─────────────────────────────────────────────
const CHARS_UPPER   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const CHARS_LOWER   = 'abcdefghijklmnopqrstuvwxyz';
const CHARS_DIGITS  = '0123456789';
const CHARS_SYMBOLS = '!@#$%^&*()-_=+[]{}|;:,.<>?';
const CHARS_SIMILAR = 'iIlL1oO0'; // karakter mirip yang membingungkan

// ── Wordlist untuk passphrase ────────────────────────────────
const WORDLIST = [
  'apple','brave','cloud','dance','eagle','flame','grace','honor',
  'ivory','jungle','karma','lemon','magic','noble','ocean','piano',
  'queen','river','solar','tiger','ultra','vivid','water','xenon',
  'youth','zebra','alpha','blaze','coral','delta','ember','frost',
  'globe','haven','index','jewel','kneel','lunar','maple','nexus',
  'orbit','plume','quill','radar','storm','torch','unity','vapor',
  'wheat','xerox','yacht','zones','amber','birch','chess','digit',
  'frost','giant','haste','input','joker','knife','latch','metro',
  'nerve','olive','proxy','quota','relay','scout','trace','upper',
  'vista','witch','boxer','cyber','depot','elite','facet','gusto',
  'hotel','infer','joint','kudos','label','mango','night','oxide',
  'pilot','quirk','remit','swift','tower','under','vault','wrist',
  'expel','flute','grind','hurry','ideal','jelly','kinky','lobby',
  'mocha','niche','ozone','perch','quest','risky','spray','trout',
];

/**
 * Generate satu password di sisi server.
 */
function generatePassword(
  int    $length,
  bool   $upper,
  bool   $lower,
  bool   $digits,
  bool   $symbols,
  bool   $noSimilar,
  bool   $noAmbiguous,
  string $customSymbols = ''
): string {
  $pool = '';
  if ($upper)   $pool .= CHARS_UPPER;
  if ($lower)   $pool .= CHARS_LOWER;
  if ($digits)  $pool .= CHARS_DIGITS;
  if ($symbols) $pool .= ($customSymbols ?: CHARS_SYMBOLS);

  if (!$pool) $pool = CHARS_LOWER . CHARS_DIGITS; // fallback

  if ($noSimilar)    $pool = str_replace(str_split(CHARS_SIMILAR), '', $pool);
  if ($noAmbiguous)  $pool = preg_replace('/[{}[\]()/\\\\\'"`~,;:.<>]/', '', $pool);

  $pool = count_chars($pool, 3); // hapus duplikat
  $poolLen = strlen($pool);

  // Pastikan password mengandung minimal 1 karakter dari tiap kategori aktif
  $password = '';
  $required = [];
  if ($upper   && str_contains($pool, 'A')) { $c = CHARS_UPPER;   if ($noSimilar) $c = str_replace(str_split(CHARS_SIMILAR), '', $c); $required[] = $c[random_int(0, strlen($c)-1)]; }
  if ($lower   && str_contains($pool, 'a')) { $c = CHARS_LOWER;   if ($noSimilar) $c = str_replace(str_split(CHARS_SIMILAR), '', $c); $required[] = $c[random_int(0, strlen($c)-1)]; }
  if ($digits  && str_contains($pool, '0')) { $c = CHARS_DIGITS;  if ($noSimilar) $c = str_replace(str_split(CHARS_SIMILAR), '', $c); $required[] = $c[random_int(0, strlen($c)-1)]; }
  if ($symbols) { $sc = $customSymbols ?: CHARS_SYMBOLS; if ($noAmbiguous) $sc = preg_replace('/[{}[\]()/\\\\\'"`~,;:.<>]/', '', $sc); if ($sc) $required[] = $sc[random_int(0, strlen($sc)-1)]; }

  // Isi sisa panjang secara acak
  $remaining = $length - count($required);
  for ($i = 0; $i < max(0, $remaining); $i++) {
    $password .= $pool[random_int(0, $poolLen - 1)];
  }

  // Gabung dan acak urutan
  $all = array_merge(str_split($password), $required);
  shuffle($all);
  return implode('', array_slice($all, 0, $length));
}

/**
 * Generate passphrase dari wordlist.
 */
function generatePassphrase(int $words, string $sep, bool $capitalize, bool $addNumber): string {
  $list = WORDLIST;
  $picked = [];
  for ($i = 0; $i < $words; $i++) {
    $idx = random_int(0, count($list) - 1);
    $w   = $list[$idx];
    $picked[] = $capitalize ? ucfirst($w) : $w;
  }
  $phrase = implode($sep, $picked);
  if ($addNumber) $phrase .= $sep . random_int(10, 999);
  return $phrase;
}

// ── Handle POST ──────────────────────────────────────────────
$server_results  = [];
$server_error    = '';
$post_mode       = 'password'; // password | passphrase | pin | bulk
$post_length     = 16;
$post_count      = 1;
$post_upper      = true;
$post_lower      = true;
$post_digits     = true;
$post_symbols    = true;
$post_no_similar = false;
$post_no_ambig   = false;
$post_custom_sym = '';
$post_words      = 4;
$post_sep        = '-';
$post_capitalize = true;
$post_add_num    = true;
$post_pin_len    = 6;
$post_bulk_count = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode       = in_array($_POST['mode'] ?? 'password', ['password','passphrase','pin','bulk'])
                       ? $_POST['mode'] : 'password';
  $post_length     = max(4, min(128, (int)($_POST['length']     ?? 16)));
  $post_count      = max(1, min(50,  (int)($_POST['count']      ?? 1)));
  $post_upper      = isset($_POST['use_upper']);
  $post_lower      = isset($_POST['use_lower']);
  $post_digits     = isset($_POST['use_digits']);
  $post_symbols    = isset($_POST['use_symbols']);
  $post_no_similar = isset($_POST['no_similar']);
  $post_no_ambig   = isset($_POST['no_ambiguous']);
  $post_custom_sym = preg_replace('/\s+/', '', $_POST['custom_symbols'] ?? '');
  $post_words      = max(2, min(10,  (int)($_POST['words']      ?? 4)));
  $post_sep        = substr(preg_replace('/\s/', '', $_POST['separator'] ?? '-'), 0, 1) ?: '-';
  $post_capitalize = isset($_POST['capitalize']);
  $post_add_num    = isset($_POST['add_number']);
  $post_pin_len    = max(4, min(12,  (int)($_POST['pin_length'] ?? 6)));
  $post_bulk_count = max(1, min(50,  (int)($_POST['bulk_count'] ?? 10)));

  switch ($post_mode) {
    case 'password':
      for ($i = 0; $i < $post_count; $i++) {
        $server_results[] = generatePassword(
          $post_length, $post_upper, $post_lower,
          $post_digits, $post_symbols, $post_no_similar,
          $post_no_ambig, $post_custom_sym
        );
      }
      break;

    case 'passphrase':
      for ($i = 0; $i < $post_count; $i++) {
        $server_results[] = generatePassphrase(
          $post_words, $post_sep, $post_capitalize, $post_add_num
        );
      }
      break;

    case 'pin':
      for ($i = 0; $i < $post_count; $i++) {
        $pin = '';
        for ($j = 0; $j < $post_pin_len; $j++) {
          $pin .= random_int(0, 9);
        }
        $server_results[] = $pin;
      }
      break;

    case 'bulk':
      if (!$post_upper && !$post_lower && !$post_digits && !$post_symbols) {
        $server_error = 'Pilih minimal satu jenis karakter.';
        break;
      }
      for ($i = 0; $i < $post_bulk_count; $i++) {
        $server_results[] = generatePassword(
          $post_length, $post_upper, $post_lower,
          $post_digits, $post_symbols, $post_no_similar,
          $post_no_ambig, $post_custom_sym
        );
      }
      break;
  }
}

// ── Hitung entropy ───────────────────────────────────────────
function calcEntropy(int $length, int $poolSize): float {
  if ($poolSize <= 0) return 0;
  return round($length * log($poolSize, 2), 1);
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Password Generator Online — Buat Password Kuat & Acak | Multi Tools',
  'description' => 'Generate password acak yang kuat secara instan. Pilih panjang, jenis karakter, passphrase, PIN, dan generate massal hingga 50 password. Aman, cepat, tanpa login.',
  'keywords'    => 'password generator, generate password, strong password, random password, passphrase generator, pin generator, kata sandi kuat, multi tools',
  'og_title'    => 'Password Generator Online — Password Kuat & Acak',
  'og_desc'     => 'Generate password acak, passphrase, atau PIN secara instan. Opsi lengkap, aman, dan tanpa login.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Enkripsi & Hash', 'url' => SITE_URL . '/tools?cat=crypto'],
    ['name' => 'Password Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/password-generator#webpage',
      'url'         => SITE_URL . '/tools/password-generator',
      'name'        => 'Password Generator Online',
      'description' => 'Generate password acak yang kuat secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',             'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Enkripsi & Hash',     'item' => SITE_URL . '/tools?cat=crypto'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Password Generator',  'item' => SITE_URL . '/tools/password-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Password Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/password-generator',
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
  padding: .55rem .35rem;
  background: var(--bg);
  border: none;
  border-right: 1px solid var(--border);
  font-family: var(--font-body);
  font-size: .8rem;
  font-weight: 600;
  color: var(--muted);
  cursor: pointer;
  transition: all var(--transition);
  text-align: center;
  white-space: nowrap;
}
.mode-tab:last-child  { border-right: none; }
.mode-tab:hover       { background: var(--surface); color: var(--text); }
.mode-tab.active      { background: var(--accent5); color: #fff; }

/* ── Password output hero ── */
.pw-hero {
  background: var(--bg);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1.25rem 1.25rem 1rem;
  font-family: var(--font-mono);
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: .06em;
  word-break: break-all;
  line-height: 1.6;
  color: var(--text);
  transition: border-color .2s, background .2s;
  min-height: 68px;
  position: relative;
}
.pw-hero.generated { border-color: var(--accent5); }
.pw-hero .pw-placeholder {
  font-size: .9rem;
  font-weight: 400;
  color: var(--muted);
  letter-spacing: 0;
}

/* ── Entropy badge ── */
.entropy-bar-wrap { margin-top: .75rem; }
.entropy-bar {
  height: 5px;
  background: var(--border);
  border-radius: 99px;
  overflow: hidden;
  margin-bottom: .3rem;
}
.entropy-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .4s cubic-bezier(.4,0,.2,1), background .3s;
}
.entropy-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: var(--font-mono);
  font-size: .7rem;
}
.entropy-label { font-weight: 700; }

/* ── Length slider ── */
.length-row {
  display: flex;
  align-items: center;
  gap: .9rem;
  margin-top: .35rem;
}
.length-row input[type="range"] {
  flex: 1;
  accent-color: var(--accent5);
}
.length-badge {
  font-family: var(--font-mono);
  font-weight: 800;
  font-size: 1.3rem;
  color: var(--accent5);
  min-width: 36px;
  text-align: center;
}
.length-input {
  width: 60px !important;
  text-align: center;
  font-family: var(--font-mono);
  font-weight: 700;
  padding: .4rem .5rem !important;
}

/* ── Checkbox options grid ── */
.opts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .5rem .75rem;
  margin-top: .4rem;
}
.opt-label {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .85rem;
  font-weight: 400;
  color: var(--text);
  cursor: pointer;
  padding: .5rem .65rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: all var(--transition);
  user-select: none;
}
.opt-label:hover { border-color: var(--accent5); background: rgba(16,185,129,.04); }
.opt-label input[type="checkbox"] {
  width: auto !important;
  accent-color: var(--accent5);
  flex-shrink: 0;
}
.opt-label.checked { border-color: var(--accent5); background: rgba(16,185,129,.06); }
.opt-label .opt-sample {
  margin-left: auto;
  font-family: var(--font-mono);
  font-size: .68rem;
  color: var(--muted);
}

/* ── Generate button ── */
.btn-generate {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  padding: .85rem 2rem;
  background: var(--accent5);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: var(--font-body);
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--transition);
  letter-spacing: .02em;
}
.btn-generate:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(16,185,129,.3);
}
.btn-generate:active { transform: translateY(0); }
.btn-generate .spin { display: none; animation: spin .6s linear infinite; }
.btn-generate.loading .spin { display: inline-block; }
.btn-generate.loading .icon { display: none; }

/* ── Password list (multi / bulk) ── */
.pw-list {
  display: flex;
  flex-direction: column;
  gap: .4rem;
}
.pw-list-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .55rem .75rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: border-color var(--transition);
}
.pw-list-item:hover { border-color: var(--accent5); }
.pw-list-item .pw-text {
  flex: 1;
  font-family: var(--font-mono);
  font-size: .82rem;
  word-break: break-all;
  color: var(--text);
}
.pw-list-item .pw-num {
  font-family: var(--font-mono);
  font-size: .68rem;
  color: var(--muted);
  min-width: 20px;
  flex-shrink: 0;
}
.pw-list-copy {
  padding: .22rem .55rem;
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
.pw-list-copy:hover { background: var(--accent5); color: #fff; border-color: var(--accent5); }

/* ── Passphrase preview ── */
.phrase-word {
  display: inline-block;
  padding: .1rem .3rem;
  border-radius: 3px;
  margin: 0 1px;
}
.phrase-word:nth-child(4n+1) { background: rgba(37,99,235,.1); color: #1d4ed8; }
.phrase-word:nth-child(4n+2) { background: rgba(16,185,129,.1); color: #065f46; }
.phrase-word:nth-child(4n+3) { background: rgba(124,58,237,.1); color: #5b21b6; }
.phrase-word:nth-child(4n+4) { background: rgba(245,158,11,.1); color: #92400e; }
.phrase-sep { color: var(--muted); font-weight: 700; }
.phrase-num { background: rgba(239,68,68,.1); color: #991b1b; border-radius: 3px; padding: .1rem .3rem; }

/* ── PIN display ── */
.pin-display {
  display: flex;
  justify-content: center;
  gap: .5rem;
  flex-wrap: wrap;
  margin: .5rem 0;
}
.pin-digit {
  width: 44px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  border: 2px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: var(--font-mono);
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--accent5);
  transition: border-color .2s;
}
.pin-digit.filled { border-color: var(--accent5); }

/* ── Strength indicator ── */
.strength-ring {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: 3px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-mono);
  font-size: .72rem;
  font-weight: 800;
  flex-shrink: 0;
  transition: all .3s;
}

/* ── History ── */
.history-list {
  display: flex;
  flex-direction: column;
  gap: .35rem;
  max-height: 280px;
  overflow-y: auto;
}
.history-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .4rem .65rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: var(--font-mono);
  font-size: .75rem;
  transition: border-color var(--transition);
}
.history-item:hover { border-color: var(--accent5); }
.history-item .h-pw  { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.history-item .h-del {
  opacity: 0;
  border: none;
  background: none;
  color: var(--muted);
  font-size: .75rem;
  cursor: pointer;
  padding: 0 .2rem;
  transition: opacity .15s, color .15s;
}
.history-item:hover .h-del { opacity: 1; }
.history-item .h-del:hover { color: #ef4444; }
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
        <span aria-hidden="true">🔑</span> Password <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate password acak yang kuat secara kriptografis.
        Pilih panjang, jenis karakter, passphrase, atau PIN — instan dan aman.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php
        $tabs = ['password' => '🔑 Password', 'passphrase' => '💬 Passphrase', 'pin' => '🔢 PIN', 'bulk' => '📋 Massal'];
        foreach ($tabs as $val => $lbl): ?>
          <button type="button" role="tab"
            class="mode-tab <?= $post_mode === $val ? 'active' : '' ?>"
            onclick="switchTab('<?= $val ?>')">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="passgen-form" novalidate>
        <input type="hidden" id="mode-input" name="mode" value="<?= e($post_mode) ?>" />

        <!-- ══ PANEL: Password ══ -->
        <div id="panel-password" class="mode-panel" <?= $post_mode !== 'password' ? 'style="display:none;"' : '' ?>>

          <!-- Password output hero -->
          <div class="form-group">
            <label>Password yang dihasilkan</label>
            <div class="pw-hero <?= !empty($server_results) ? 'generated' : '' ?>" id="pw-hero-display">
              <?php if (!empty($server_results)): ?>
                <?= e($server_results[0]) ?>
              <?php else: ?>
                <span class="pw-placeholder">Klik "Generate Password" untuk memulai...</span>
              <?php endif; ?>
            </div>
            <!-- Entropy bar -->
            <div class="entropy-bar-wrap" id="entropy-wrap">
              <div class="entropy-bar">
                <div class="entropy-fill" id="entropy-fill" style="width:0%;"></div>
              </div>
              <div class="entropy-meta">
                <span class="entropy-label" id="entropy-label" style="color:var(--muted);">—</span>
                <span id="entropy-bits" style="color:var(--muted); font-family:var(--font-mono); font-size:.7rem;">0 bit</span>
              </div>
            </div>
          </div>

          <!-- Panjang -->
          <div class="form-group">
            <label for="pw-length-slider">
              Panjang password
            </label>
            <div class="length-row">
              <input type="range" id="pw-length-slider" min="4" max="128" step="1"
                value="<?= $post_length ?>"
                oninput="syncLength(this.value); updateEntropy();" />
              <input type="number" class="length-input" id="pw-length-num" name="length"
                min="4" max="128" value="<?= $post_length ?>"
                oninput="syncLength(this.value, true); updateEntropy();" />
            </div>
            <div style="display:flex; justify-content:space-between; font-family:var(--font-mono); font-size:.68rem; color:var(--muted); margin-top:.25rem;">
              <span>4</span><span>16</span><span>32</span><span>64</span><span>128</span>
            </div>
          </div>

          <!-- Opsi karakter -->
          <div class="form-group">
            <label>Jenis karakter</label>
            <div class="opts-grid">
              <label class="opt-label <?= $post_upper ? 'checked' : '' ?>" id="lbl-upper">
                <input type="checkbox" name="use_upper" id="opt-upper"
                  <?= $post_upper ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-upper'); updateEntropy();" />
                Huruf kapital
                <span class="opt-sample">A-Z</span>
              </label>
              <label class="opt-label <?= $post_lower ? 'checked' : '' ?>" id="lbl-lower">
                <input type="checkbox" name="use_lower" id="opt-lower"
                  <?= $post_lower ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-lower'); updateEntropy();" />
                Huruf kecil
                <span class="opt-sample">a-z</span>
              </label>
              <label class="opt-label <?= $post_digits ? 'checked' : '' ?>" id="lbl-digits">
                <input type="checkbox" name="use_digits" id="opt-digits"
                  <?= $post_digits ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-digits'); updateEntropy();" />
                Angka
                <span class="opt-sample">0-9</span>
              </label>
              <label class="opt-label <?= $post_symbols ? 'checked' : '' ?>" id="lbl-symbols">
                <input type="checkbox" name="use_symbols" id="opt-symbols"
                  <?= $post_symbols ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-symbols'); updateEntropy();" />
                Simbol
                <span class="opt-sample">!@#$</span>
              </label>
            </div>
          </div>

          <!-- Opsi tambahan -->
          <div class="form-group">
            <label>Opsi tambahan</label>
            <div class="opts-grid">
              <label class="opt-label <?= $post_no_similar ? 'checked' : '' ?>" id="lbl-nosim">
                <input type="checkbox" name="no_similar" id="opt-nosim"
                  <?= $post_no_similar ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-nosim'); updateEntropy();" />
                Hindari karakter mirip
                <span class="opt-sample">iIl1oO0</span>
              </label>
              <label class="opt-label <?= $post_no_ambig ? 'checked' : '' ?>" id="lbl-noamb">
                <input type="checkbox" name="no_ambiguous" id="opt-noamb"
                  <?= $post_no_ambig ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-noamb'); updateEntropy();" />
                Hindari simbol ambigu
                <span class="opt-sample">{}[]()\/</span>
              </label>
            </div>
          </div>

          <!-- Simbol kustom -->
          <div class="form-group" id="custom-sym-wrap" style="<?= !$post_symbols ? 'display:none;' : '' ?>">
            <label for="custom-sym-input">Simbol kustom <span class="text-muted text-sm">(kosongkan untuk default)</span></label>
            <input type="text" id="custom-sym-input" name="custom_symbols"
              placeholder="Contoh: !@#$%-+"
              value="<?= e($post_custom_sym) ?>"
              style="font-family:var(--font-mono);"
              oninput="updateEntropy()" />
          </div>

          <!-- Jumlah password -->
          <div class="form-group">
            <label for="pw-count">Jumlah password yang digenerate</label>
            <input type="number" id="pw-count" name="count"
              min="1" max="50" value="<?= $post_count ?>"
              style="max-width:100px;" />
          </div>

          <!-- Tombol generate -->
          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center; margin-top:.5rem;">
            <button type="submit" class="btn-generate" id="btn-generate-pw">
              <span class="icon">🔑</span>
              <span class="spin">↻</span>
              Generate Password
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="copyText(document.getElementById('pw-hero-display').innerText.trim())">
              📋 Salin
            </button>
          </div>
        </div>

        <!-- ══ PANEL: Passphrase ══ -->
        <div id="panel-passphrase" class="mode-panel" <?= $post_mode !== 'passphrase' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>💬</span>
            <span>
              Passphrase — rangkaian kata acak yang mudah diingat namun sulit ditebak.
              Contoh: <strong>brave-ocean-solar-7</strong>
            </span>
          </div>

          <!-- Preview passphrase -->
          <div class="form-group">
            <label>Passphrase yang dihasilkan</label>
            <div class="pw-hero <?= ($post_mode === 'passphrase' && !empty($server_results)) ? 'generated' : '' ?>"
              id="phrase-hero" style="font-size:.95rem;">
              <?php if ($post_mode === 'passphrase' && !empty($server_results)): ?>
                <?php
                $parts = explode($post_sep, $server_results[0]);
                foreach ($parts as $idx => $part):
                  if ($idx > 0) echo '<span class="phrase-sep">' . e($post_sep) . '</span>';
                  if (is_numeric($part)):
                ?>
                  <span class="phrase-num"><?= e($part) ?></span>
                <?php else: ?>
                  <span class="phrase-word"><?= e($part) ?></span>
                <?php endif; endforeach; ?>
              <?php else: ?>
                <span class="pw-placeholder">Klik "Generate Passphrase" untuk memulai...</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="phrase-words">Jumlah kata</label>
              <input type="number" id="phrase-words" name="words"
                min="2" max="10" value="<?= $post_words ?>"
                oninput="updatePhrasePreview()" />
            </div>
            <div class="form-group">
              <label for="phrase-sep">Pemisah kata</label>
              <input type="text" id="phrase-sep" name="separator"
                maxlength="1" value="<?= e($post_sep) ?>"
                style="font-family:var(--font-mono); max-width:80px;"
                oninput="updatePhrasePreview()" />
            </div>
          </div>

          <div class="form-group">
            <label>Opsi</label>
            <div class="opts-grid">
              <label class="opt-label <?= $post_capitalize ? 'checked' : '' ?>" id="lbl-cap">
                <input type="checkbox" name="capitalize" id="opt-cap"
                  <?= $post_capitalize ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-cap'); updatePhrasePreview();" />
                Kapitalkan tiap kata
                <span class="opt-sample">Brave</span>
              </label>
              <label class="opt-label <?= $post_add_num ? 'checked' : '' ?>" id="lbl-num">
                <input type="checkbox" name="add_number" id="opt-num"
                  <?= $post_add_num ? 'checked' : '' ?>
                  onchange="toggleOptLabel(this, 'lbl-num'); updatePhrasePreview();" />
                Tambah angka di akhir
                <span class="opt-sample">-42</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label for="phrase-count">Jumlah passphrase</label>
            <input type="number" id="phrase-count" name="count"
              min="1" max="20" value="<?= $post_count ?>"
              style="max-width:100px;" />
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.5rem;">
            <button type="submit" class="btn-generate">
              💬 Generate Passphrase
            </button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="copyText(document.getElementById('phrase-hero').innerText.trim())">
              📋 Salin
            </button>
          </div>
        </div>

        <!-- ══ PANEL: PIN ══ -->
        <div id="panel-pin" class="mode-panel" <?= $post_mode !== 'pin' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>PIN yang dihasilkan</label>
            <div class="pin-display" id="pin-display">
              <?php if ($post_mode === 'pin' && !empty($server_results)): ?>
                <?php foreach (str_split($server_results[0]) as $d): ?>
                  <div class="pin-digit filled"><?= e($d) ?></div>
                <?php endforeach; ?>
              <?php else: ?>
                <?php for ($i = 0; $i < $post_pin_len; $i++): ?>
                  <div class="pin-digit">—</div>
                <?php endfor; ?>
              <?php endif; ?>
            </div>
            <?php if ($post_mode === 'pin' && !empty($server_results)): ?>
              <div style="text-align:center; margin-top:.5rem;">
                <div class="copy-wrap" style="display:inline-flex; max-width:240px;">
                  <div class="result-box success" id="pin-out"
                    style="font-family:var(--font-mono); font-size:1.1rem; font-weight:700; letter-spacing:.2em; text-align:center;">
                    <?= e($server_results[0]) ?>
                  </div>
                  <button class="copy-btn" data-copy-target="pin-out">SALIN</button>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="pin-len">Panjang PIN</label>
              <input type="number" id="pin-len" name="pin_length"
                min="4" max="12" value="<?= $post_pin_len ?>"
                oninput="updatePinPreview(this.value)" />
            </div>
            <div class="form-group">
              <label for="pin-count">Jumlah PIN</label>
              <input type="number" id="pin-count" name="count"
                min="1" max="20" value="<?= $post_count ?>"
                style="max-width:100px;" />
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.5rem;">
            <button type="submit" class="btn-generate">
              🔢 Generate PIN
            </button>
          </div>
        </div>

        <!-- ══ PANEL: Bulk ══ -->
        <div id="panel-bulk" class="mode-panel" <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>Jenis karakter</label>
            <div class="opts-grid">
              <label class="opt-label checked" id="lbl-upper-b">
                <input type="checkbox" name="use_upper" id="opt-upper-b" checked
                  onchange="toggleOptLabel(this, 'lbl-upper-b')" />
                Huruf kapital <span class="opt-sample">A-Z</span>
              </label>
              <label class="opt-label checked" id="lbl-lower-b">
                <input type="checkbox" name="use_lower" id="opt-lower-b" checked
                  onchange="toggleOptLabel(this, 'lbl-lower-b')" />
                Huruf kecil <span class="opt-sample">a-z</span>
              </label>
              <label class="opt-label checked" id="lbl-digits-b">
                <input type="checkbox" name="use_digits" id="opt-digits-b" checked
                  onchange="toggleOptLabel(this, 'lbl-digits-b')" />
                Angka <span class="opt-sample">0-9</span>
              </label>
              <label class="opt-label checked" id="lbl-symbols-b">
                <input type="checkbox" name="use_symbols" id="opt-symbols-b" checked
                  onchange="toggleOptLabel(this, 'lbl-symbols-b')" />
                Simbol <span class="opt-sample">!@#$</span>
              </label>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="bulk-len">Panjang tiap password</label>
              <input type="number" id="bulk-len" name="length"
                min="4" max="128" value="<?= $post_length ?>" />
            </div>
            <div class="form-group">
              <label for="bulk-count">Jumlah password</label>
              <input type="number" id="bulk-count" name="bulk_count"
                min="1" max="50" value="<?= $post_bulk_count ?>" />
            </div>
          </div>

          <div class="form-group">
            <label>Opsi tambahan</label>
            <div class="opts-grid">
              <label class="opt-label" id="lbl-nosim-b">
                <input type="checkbox" name="no_similar" id="opt-nosim-b"
                  onchange="toggleOptLabel(this, 'lbl-nosim-b')" />
                Hindari karakter mirip <span class="opt-sample">iIl1</span>
              </label>
              <label class="opt-label" id="lbl-noamb-b">
                <input type="checkbox" name="no_ambiguous" id="opt-noamb-b"
                  onchange="toggleOptLabel(this, 'lbl-noamb-b')" />
                Hindari simbol ambigu <span class="opt-sample">{}[]</span>
              </label>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.5rem;">
            <button type="submit" class="btn-generate">
              📋 Generate Massal
            </button>
          </div>
        </div>

      </form><!-- /#passgen-form -->
    </div><!-- /.panel -->

    <!-- ── Hasil server: daftar password (count > 1 atau bulk) ── -->
    <?php if (!empty($server_results) && (count($server_results) > 1 || $post_mode === 'bulk')): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>Berhasil generate <strong><?= count($server_results) ?> password</strong>.</span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
          <div class="panel-title" style="margin-bottom:0;">⚙ Hasil Generate</div>
          <div style="display:flex; gap:.5rem;">
            <button class="btn-ghost btn-sm" onclick="copyAllPasswords()">📋 Salin semua</button>
            <button class="btn-ghost btn-sm" onclick="downloadPasswords()">⬇ Unduh .txt</button>
          </div>
        </div>
        <div class="pw-list" id="server-pw-list">
          <?php foreach ($server_results as $idx => $pw): ?>
            <div class="pw-list-item">
              <span class="pw-num"><?= $idx + 1 ?>.</span>
              <span class="pw-text"><?= e($pw) ?></span>
              <button class="pw-list-copy"
                onclick="copyText(<?= htmlspecialchars(json_encode($pw), ENT_QUOTES) ?>, this)">
                SALIN
              </button>
            </div>
          <?php endforeach; ?>
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
        <button class="btn-ghost btn-sm" onclick="clearHistory()" style="padding:.25rem .6rem;">Hapus</button>
      </div>
      <div class="history-list" id="history-list">
        <div style="text-align:center; padding:1.25rem; color:var(--muted); font-size:.82rem;">
          Belum ada riwayat
        </div>
      </div>
    </div>

    <!-- Preset panjang -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Preset Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.4rem;">
        <?php
        $presets = [
          ['label' => '8 karakter — minimal',       'len' => 8,  'mode' => 'password'],
          ['label' => '12 karakter — standar',       'len' => 12, 'mode' => 'password'],
          ['label' => '16 karakter — rekomendasi',   'len' => 16, 'mode' => 'password'],
          ['label' => '24 karakter — sangat kuat',   'len' => 24, 'mode' => 'password'],
          ['label' => '32 karakter — API key',       'len' => 32, 'mode' => 'password'],
          ['label' => '4 kata — passphrase mudah',   'len' => 4,  'mode' => 'passphrase'],
          ['label' => '6 digit PIN',                 'len' => 6,  'mode' => 'pin'],
        ];
        foreach ($presets as $p): ?>
          <button
            class="btn-ghost btn-sm btn-full"
            type="button"
            onclick="applyPreset(<?= $p['len'] ?>, '<?= $p['mode'] ?>')"
            style="text-align:left;">
            <?= e($p['label']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Panduan keamanan -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🛡 Panduan Keamanan</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Minimal <strong>12 karakter</strong> untuk akun penting</li>
        <li>Gunakan password <strong>berbeda</strong> tiap akun</li>
        <li>Simpan di <strong>password manager</strong> (Bitwarden, 1Password)</li>
        <li>Aktifkan <strong>2FA</strong> jika tersedia</li>
        <li>Jangan simpan di file teks biasa</li>
        <li>Passphrase lebih mudah diingat namun tetap kuat</li>
      </ul>
    </div>

    <!-- Entropy guide -->
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📊 Panduan Entropy</div>
      <div style="display:flex; flex-direction:column; gap:.35rem; font-size:.78rem;">
        <?php
        $ent = [
          ['< 28 bit',   '#ef4444', 'Sangat lemah'],
          ['28-35 bit',  '#f97316', 'Lemah'],
          ['36-59 bit',  '#eab308', 'Cukup'],
          ['60-127 bit', '#22c55e', 'Kuat'],
          ['≥ 128 bit',  '#6366f1', 'Sangat kuat'],
        ];
        foreach ($ent as [$range, $color, $label]): ?>
          <div style="display:flex; justify-content:space-between; align-items:center;
                      padding:.3rem 0; border-bottom:1px solid var(--border);">
            <span style="font-family:var(--font-mono); color:var(--muted);"><?= $range ?></span>
            <span style="font-weight:700; color:<?= $color ?>;"><?= $label ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/bcrypt-generator"       class="btn-ghost btn-sm btn-full">Bcrypt Generator</a>
        <a href="/tools/sha256-generator"       class="btn-ghost btn-sm btn-full">SHA256 Generator</a>
        <a href="/tools/password-strength"      class="btn-ghost btn-sm btn-full">Password Strength</a>
        <a href="/tools/uuid-generator"         class="btn-ghost btn-sm btn-full">UUID Generator</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Password Generator — logika JS (realtime)
   Generate menggunakan crypto.getRandomValues()
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

const CHARS_UPPER   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const CHARS_LOWER   = 'abcdefghijklmnopqrstuvwxyz';
const CHARS_DIGITS  = '0123456789';
const CHARS_SYMBOLS = '!@#$%^&*()-_=+[]{}|;:,.<>?';
const CHARS_SIMILAR = new Set([...'iIlL1oO0']);
const CHARS_AMBIG   = new Set([...'{}[]()/\\\'"`~,;:.<>']);

const WORDLIST = [
  'apple','brave','cloud','dance','eagle','flame','grace','honor',
  'ivory','jungle','karma','lemon','magic','noble','ocean','piano',
  'queen','river','solar','tiger','ultra','vivid','water','xenon',
  'youth','zebra','alpha','blaze','coral','delta','ember','frost',
  'globe','haven','index','jewel','kneel','lunar','maple','nexus',
  'orbit','plume','quill','radar','storm','torch','unity','vapor',
  'wheat','xerox','yacht','zones','amber','birch','chess','digit',
  'frost','giant','haste','input','joker','knife','latch','metro',
  'nerve','olive','proxy','quota','relay','scout','trace','upper',
  'vista','witch','boxer','cyber','depot','elite','facet','gusto',
];

// ── Kriptografis random ───────────────────────────────────────
function secureRandom(max) {
  const arr = new Uint32Array(1);
  let result;
  do {
    crypto.getRandomValues(arr);
    result = arr[0];
  } while (result >= Math.floor(0xFFFFFFFF / max) * max);
  return result % max;
}

function secureChoice(pool) {
  return pool[secureRandom(pool.length)];
}

// ── Build karakter pool ───────────────────────────────────────
function buildPool() {
  const noSim  = document.getElementById('opt-nosim')?.checked;
  const noAmb  = document.getElementById('opt-noamb')?.checked;
  const useSym = document.getElementById('opt-symbols')?.checked;
  const custom = document.getElementById('custom-sym-input')?.value.trim();

  let pool = '';
  if (document.getElementById('opt-upper')?.checked)  pool += CHARS_UPPER;
  if (document.getElementById('opt-lower')?.checked)  pool += CHARS_LOWER;
  if (document.getElementById('opt-digits')?.checked) pool += CHARS_DIGITS;
  if (useSym) pool += custom || CHARS_SYMBOLS;

  if (noSim) pool = [...pool].filter(c => !CHARS_SIMILAR.has(c)).join('');
  if (noAmb) pool = [...pool].filter(c => !CHARS_AMBIG.has(c)).join('');

  // Hapus duplikat
  pool = [...new Set([...pool])].join('');
  return pool || CHARS_LOWER + CHARS_DIGITS;
}

// ── Generate password tunggal (JS) ────────────────────────────
function generateOne(length, pool) {
  // Pastikan tiap kategori aktif terwakili
  const required = [];
  if (document.getElementById('opt-upper')?.checked) {
    let c = [...CHARS_UPPER].filter(x => pool.includes(x));
    if (c.length) required.push(secureChoice(c));
  }
  if (document.getElementById('opt-lower')?.checked) {
    let c = [...CHARS_LOWER].filter(x => pool.includes(x));
    if (c.length) required.push(secureChoice(c));
  }
  if (document.getElementById('opt-digits')?.checked) {
    let c = [...CHARS_DIGITS].filter(x => pool.includes(x));
    if (c.length) required.push(secureChoice(c));
  }
  if (document.getElementById('opt-symbols')?.checked) {
    const sym = document.getElementById('custom-sym-input')?.value.trim() || CHARS_SYMBOLS;
    let c = [...sym].filter(x => pool.includes(x));
    if (c.length) required.push(secureChoice(c));
  }

  const remaining = length - required.length;
  const random = Array.from({ length: Math.max(0, remaining) }, () => secureChoice(pool));
  const all = [...required, ...random];

  // Fisher-Yates shuffle
  for (let i = all.length - 1; i > 0; i--) {
    const j = secureRandom(i + 1);
    [all[i], all[j]] = [all[j], all[i]];
  }
  return all.slice(0, length).join('');
}

// ── Generate passphrase (JS) ──────────────────────────────────
function generatePhraseOne() {
  const words    = parseInt(document.getElementById('phrase-words')?.value) || 4;
  const sep      = document.getElementById('phrase-sep')?.value || '-';
  const cap      = document.getElementById('opt-cap')?.checked;
  const addNum   = document.getElementById('opt-num')?.checked;

  const picked = Array.from({ length: words }, () => {
    let w = WORDLIST[secureRandom(WORDLIST.length)];
    return cap ? w.charAt(0).toUpperCase() + w.slice(1) : w;
  });
  if (addNum) picked.push(String(secureRandom(900) + 10));
  return picked.join(sep);
}

// ── Generate PIN (JS) ─────────────────────────────────────────
function generatePinOne(len) {
  return Array.from({ length: len }, () => secureRandom(10)).join('');
}

// ── Entropy calculation ───────────────────────────────────────
function updateEntropy() {
  const len    = parseInt(document.getElementById('pw-length-num')?.value) || 16;
  const pool   = buildPool();
  const bits   = pool.length > 0 ? Math.floor(len * Math.log2(pool.length)) : 0;

  const fill  = document.getElementById('entropy-fill');
  const label = document.getElementById('entropy-label');
  const bitsEl= document.getElementById('entropy-bits');

  if (!fill) return;

  const pct  = Math.min(100, (bits / 128) * 100);
  bitsEl.textContent = bits + ' bit entropy';

  let color, lbl;
  if (bits < 28)       { color = '#ef4444'; lbl = 'Sangat lemah'; }
  else if (bits < 36)  { color = '#f97316'; lbl = 'Lemah'; }
  else if (bits < 60)  { color = '#eab308'; lbl = 'Cukup'; }
  else if (bits < 128) { color = '#22c55e'; lbl = 'Kuat'; }
  else                 { color = '#6366f1'; lbl = 'Sangat kuat'; }

  fill.style.width      = pct + '%';
  fill.style.background = color;
  label.textContent     = lbl;
  label.style.color     = color;
  bitsEl.style.color    = color;
}

// ── History ───────────────────────────────────────────────────
let history = [];

function addHistory(pw) {
  if (!pw || pw.length < 2) return;
  history.unshift(pw);
  if (history.length > 20) history.pop();
  renderHistory();
}

function renderHistory() {
  const el = document.getElementById('history-list');
  if (!history.length) {
    el.innerHTML = '<div style="text-align:center; padding:1.25rem; color:var(--muted); font-size:.82rem;">Belum ada riwayat</div>';
    return;
  }
  el.innerHTML = history.map((pw, i) => `
    <div class="history-item">
      <span class="h-pw">${esc(pw)}</span>
      <button class="pw-list-copy" onclick="copyText(${JSON.stringify(pw)}, this)">SALIN</button>
      <button class="h-del" onclick="history.splice(${i},1); renderHistory();">✕</button>
    </div>`).join('');
}

function clearHistory() {
  history = [];
  renderHistory();
}

// ── Tab switching ─────────────────────────────────────────────
let currentMode = '<?= $post_mode ?>';

function switchTab(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;
  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    const modes = ['password','passphrase','pin','bulk'];
    t.classList.toggle('active', modes[i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

// ── Sync length slider ↔ input ────────────────────────────────
function syncLength(val, fromInput = false) {
  val = Math.max(4, Math.min(128, parseInt(val) || 16));
  document.getElementById('pw-length-slider').value = val;
  document.getElementById('pw-length-num').value    = val;
}

// ── Toggle opt label style ────────────────────────────────────
function toggleOptLabel(el, lblId) {
  const lbl = document.getElementById(lblId);
  if (lbl) lbl.classList.toggle('checked', el.checked);
  // Toggle custom symbols visibility
  if (el.id === 'opt-symbols') {
    const wrap = document.getElementById('custom-sym-wrap');
    if (wrap) wrap.style.display = el.checked ? '' : 'none';
  }
}

// ── PIN preview ───────────────────────────────────────────────
function updatePinPreview(len) {
  len = Math.max(4, Math.min(12, parseInt(len) || 6));
  const disp = document.getElementById('pin-display');
  if (!disp) return;
  disp.innerHTML = Array.from({ length: len }, () =>
    '<div class="pin-digit">—</div>'
  ).join('');
}

// ── Passphrase preview (placeholder) ─────────────────────────
function updatePhrasePreview() {
  // Tidak generate saat mengetik — hanya saat submit
}

// ── Copy helpers ──────────────────────────────────────────────
function copyText(text, btn) {
  if (!text || typeof text !== 'string') return;
  navigator.clipboard.writeText(text.trim()).then(() => {
    if (!btn) { showToast && showToast('Tersalin!', 'success', 1500); return; }
    const orig = btn.textContent;
    btn.textContent = '✓';
    btn.style.cssText = 'background:var(--accent5);border-color:var(--accent5);color:#fff;';
    setTimeout(() => { btn.textContent = orig; btn.style.cssText = ''; }, 1500);
  });
}

function copyAllPasswords() {
  const items = document.querySelectorAll('#server-pw-list .pw-text');
  const all   = Array.from(items).map(el => el.textContent.trim()).join('\n');
  if (!all) return;
  navigator.clipboard.writeText(all).then(() => showToast && showToast('Semua password disalin!', 'success'));
}

function downloadPasswords() {
  const items = document.querySelectorAll('#server-pw-list .pw-text');
  const lines = Array.from(items).map((el, i) => `${i+1}. ${el.textContent.trim()}`);
  if (!lines.length) return;
  const blob = new Blob(
    ['Password Generator — Multi Tools\n' + new Date().toLocaleString('id-ID') + '\n\n' + lines.join('\n')],
    { type: 'text/plain;charset=utf-8' }
  );
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'passwords.txt';
  a.click();
}

// ── Apply preset ──────────────────────────────────────────────
function applyPreset(val, mode) {
  switchTab(mode);
  if (mode === 'password') {
    syncLength(val);
    updateEntropy();
  } else if (mode === 'passphrase') {
    const el = document.getElementById('phrase-words');
    if (el) el.value = val;
  } else if (mode === 'pin') {
    const el = document.getElementById('pin-len');
    if (el) { el.value = val; updatePinPreview(val); }
  }
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Auto-add hasil server ke history ─────────────────────────
<?php if (!empty($server_results)): ?>
(function() {
  const results = <?= json_encode(array_slice($server_results, 0, 5)) ?>;
  results.forEach(pw => addHistory(pw));
})();
<?php endif; ?>

// ── Init ──────────────────────────────────────────────────────
updateEntropy();
switchTab(currentMode);
</script>

<?php require '../../includes/footer.php'; ?>