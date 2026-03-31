<?php
require '../../includes/config.php';
/**
 * Multi Tools — SHA256 Hash Generator
 * Generate hash SHA-256 dari teks atau file upload.
 * Mendukung SHA-224/256/384/512, verifikasi, HMAC, dan generate massal.
 * ============================================================ */

// ── Handle POST ──────────────────────────────────────────────
$server_results  = [];
$server_error    = '';
$post_input      = '';
$post_algo       = 'sha256';
$post_uppercase  = false;
$post_mode       = 'text';
$post_verify     = '';
$post_hmac_key   = '';
$verify_result   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode      = in_array($_POST['mode'] ?? 'text', ['text','file','verify','bulk','hmac'])
                      ? $_POST['mode'] : 'text';
  $post_algo      = in_array($_POST['algo'] ?? 'sha256', ['sha224','sha256','sha384','sha512'])
                      ? $_POST['algo'] : 'sha256';
  $post_uppercase = isset($_POST['uppercase']);
  $post_input     = $_POST['input_text'] ?? '';
  $post_verify    = trim($_POST['verify_hash'] ?? '');
  $post_hmac_key  = $_POST['hmac_key'] ?? '';

  // Panjang hash yang diharapkan per algoritma
  $hash_lengths = ['sha224' => 56, 'sha256' => 64, 'sha384' => 96, 'sha512' => 128];
  $expected_len = $hash_lengths[$post_algo] ?? 64;

  switch ($post_mode) {

    // ── Teks tunggal ──
    case 'text':
      if (trim($post_input) === '') {
        $server_error = 'Teks input tidak boleh kosong.';
      } else {
        $hash = hash($post_algo, $post_input);
        if ($post_uppercase) $hash = strtoupper($hash);
        $server_results[] = ['input' => $post_input, 'hash' => $hash, 'algo' => $post_algo];
      }
      break;

    // ── Upload file ──
    case 'file':
      if (empty($_FILES['upload_file']['tmp_name'])) {
        $server_error = 'Pilih file untuk di-hash.';
      } elseif ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
        $server_error = 'Gagal mengupload file. Kode error: ' . (int)$_FILES['upload_file']['error'];
      } elseif ($_FILES['upload_file']['size'] > 100 * 1024 * 1024) {
        $server_error = 'Ukuran file maksimal 100 MB.';
      } else {
        $hash = hash_file($post_algo, $_FILES['upload_file']['tmp_name']);
        if ($post_uppercase) $hash = strtoupper($hash);
        $server_results[] = [
          'input' => $_FILES['upload_file']['name']
                   . ' (' . number_format($_FILES['upload_file']['size'] / 1024, 1) . ' KB)',
          'hash'  => $hash,
          'algo'  => $post_algo,
        ];
      }
      break;

    // ── Verifikasi ──
    case 'verify':
      if (trim($post_input) === '') {
        $server_error = 'Masukkan teks untuk diverifikasi.';
      } elseif (strlen($post_verify) !== $expected_len || !ctype_xdigit($post_verify)) {
        $server_error = "Hash {$post_algo} tidak valid (harus {$expected_len} karakter hex).";
      } else {
        $hash          = hash($post_algo, $post_input);
        $verify_result = hash_equals(strtolower($hash), strtolower($post_verify));
        if ($post_uppercase) $hash = strtoupper($hash);
        $server_results[] = ['input' => $post_input, 'hash' => $hash, 'algo' => $post_algo];
      }
      break;

    // ── HMAC ──
    case 'hmac':
      if (trim($post_input) === '') {
        $server_error = 'Teks input tidak boleh kosong.';
      } elseif (trim($post_hmac_key) === '') {
        $server_error = 'Secret key tidak boleh kosong.';
      } else {
        $hash = hash_hmac($post_algo, $post_input, $post_hmac_key);
        if ($post_uppercase) $hash = strtoupper($hash);
        $server_results[] = [
          'input' => $post_input,
          'hash'  => $hash,
          'algo'  => 'HMAC-' . strtoupper($post_algo),
          'key'   => str_repeat('*', min(strlen($post_hmac_key), 8)) . '...',
        ];
      }
      break;

    // ── Massal ──
    case 'bulk':
      if (trim($post_input) === '') {
        $server_error = 'Teks input tidak boleh kosong.';
      } else {
        $lines = array_filter(
          explode("\n", str_replace("\r\n", "\n", $post_input)),
          fn($l) => trim($l) !== ''
        );
        if (count($lines) > 500) {
          $server_error = 'Maksimal 500 baris per sekali generate.';
        } else {
          foreach ($lines as $line) {
            $hash = hash($post_algo, trim($line));
            if ($post_uppercase) $hash = strtoupper($hash);
            $server_results[] = ['input' => trim($line), 'hash' => $hash, 'algo' => $post_algo];
          }
        }
      }
      break;
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'SHA256 Generator Online — Hash SHA-256/512 Teks & File | Multi Tools',
  'description' => 'Generate hash SHA-224, SHA-256, SHA-384, SHA-512 dari teks atau file secara instan. Mendukung HMAC, verifikasi hash, generate massal, dan upload file hingga 100MB.',
  'keywords'    => 'sha256 generator, sha256 hash, sha512, sha-256, hmac, hash generator, checksum, verifikasi hash, multi tools',
  'og_title'    => 'SHA256 Generator Online — Hash SHA-256/512 Teks & File',
  'og_desc'     => 'Generate dan verifikasi SHA-256/512/384/224 dari teks atau file. HMAC, bulk mode, upload file.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Enkripsi & Hash', 'url' => SITE_URL . '/tools?cat=crypto'],
    ['name' => 'SHA256 Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/sha256-generator#webpage',
      'url'         => SITE_URL . '/tools/sha256-generator',
      'name'        => 'SHA256 Generator Online',
      'description' => 'Generate hash SHA-256/512 dari teks atau file secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',           'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Enkripsi & Hash',   'item' => SITE_URL . '/tools?cat=crypto'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'SHA256 Generator',  'item' => SITE_URL . '/tools/sha256-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'SHA256 Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/sha256-generator',
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
  font-size: .8rem;
  font-weight: 600;
  color: var(--muted);
  cursor: pointer;
  transition: all var(--transition);
  text-align: center;
  white-space: nowrap;
}
.mode-tab:last-child { border-right: none; }
.mode-tab:hover { background: var(--surface); color: var(--text); }
.mode-tab.active { background: var(--accent); color: #fff; }

/* ── Algo pills ── */
.algo-pills {
  display: flex;
  gap: .4rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.algo-pill {
  padding: .3rem .85rem;
  border: 1px solid var(--border);
  border-radius: 99px;
  font-family: var(--font-mono);
  font-size: .78rem;
  cursor: pointer;
  background: var(--surface);
  color: var(--muted);
  transition: all var(--transition);
  font-weight: 700;
}
.algo-pill:hover { border-color: var(--accent); color: var(--accent); background: rgba(37,99,235,.07); }
.algo-pill.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.algo-pill .pill-bits {
  font-size: .65rem;
  opacity: .75;
  margin-left: .2rem;
}

/* ── Hash output ── */
.hash-display {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .75rem 1rem;
  font-family: var(--font-mono);
  font-size: .85rem;
  color: var(--accent);
  word-break: break-all;
  min-height: 52px;
  letter-spacing: .03em;
  line-height: 1.6;
  transition: border-color var(--transition);
  position: relative;
}
.hash-display .hash-algo-badge {
  position: absolute;
  top: .4rem; right: .4rem;
  font-size: .6rem;
  font-family: var(--font-mono);
  font-weight: 700;
  background: rgba(37,99,235,.1);
  color: var(--accent);
  border-radius: 4px;
  padding: 1px 5px;
}

/* ── Hash length indicator ── */
.hash-len-bar {
  height: 3px;
  background: var(--border);
  border-radius: 99px;
  margin-top: .5rem;
  overflow: hidden;
}
.hash-len-fill {
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, var(--accent), var(--accent2));
  transition: width .3s;
}

/* ── Verify box ── */
.verify-box {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .85rem 1.1rem;
  border-radius: var(--radius-sm);
  border: 1px solid;
  font-size: .9rem;
  font-weight: 600;
  margin-top: 1rem;
}
.verify-box.match    { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.verify-box.mismatch { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }
.verify-icon { font-size: 1.25rem; flex-shrink: 0; }

/* ── File drop ── */
.file-drop {
  border: 2px dashed var(--border);
  border-radius: var(--radius-md);
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--bg);
  position: relative;
}
.file-drop:hover, .file-drop.drag-over {
  border-color: var(--accent);
  background: rgba(37,99,235,.04);
}
.file-drop input[type="file"] {
  position: absolute; inset: 0;
  opacity: 0; cursor: pointer;
  width: 100%; height: 100%;
}
.file-drop-icon  { font-size: 2rem; margin-bottom: .5rem; opacity: .45; }
.file-drop-label { font-size: .9rem; color: var(--muted); margin-bottom: .25rem; }
.file-drop-hint  { font-size: .75rem; color: var(--muted); font-family: var(--font-mono); }
.file-name-display {
  margin-top: .75rem;
  font-family: var(--font-mono);
  font-size: .82rem;
  color: var(--accent);
  font-weight: 600;
  display: none;
}

/* ── Bulk table ── */
.bulk-table-wrap {
  max-height: 360px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.bulk-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.bulk-table th {
  position: sticky; top: 0;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: .5rem .9rem;
  text-align: left;
  font-family: var(--font-mono);
  font-size: .67rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--muted);
  font-weight: 700;
}
.bulk-table td {
  padding: .4rem .9rem;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.bulk-table tr:last-child td { border-bottom: none; }
.bulk-table tr:hover td { background: rgba(37,99,235,.04); }
.bulk-table .td-hash {
  font-family: var(--font-mono);
  font-size: .72rem;
  color: var(--accent);
  word-break: break-all;
}
.bulk-table .td-input {
  color: var(--muted);
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.bulk-table .td-copy { white-space: nowrap; text-align: right; }
.bulk-copy-btn {
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
}
.bulk-copy-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── Algo comparison table ── */
.algo-compare { width:100%; border-collapse:collapse; font-size:.78rem; }
.algo-compare th {
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  padding: .4rem .7rem;
  text-align: left;
  font-family: var(--font-mono);
  font-size: .67rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--muted);
}
.algo-compare td {
  padding: .4rem .7rem;
  border-bottom: 1px solid var(--border);
  color: var(--text);
}
.algo-compare tr:last-child td { border-bottom: none; }
.algo-compare .mono { font-family: var(--font-mono); font-size: .72rem; color: var(--accent); }
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
        <span aria-hidden="true">🔒</span> SHA256 <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate hash SHA-224, SHA-256, SHA-384, atau SHA-512 dari teks atau file secara instan.
        Mendukung HMAC, verifikasi checksum, dan mode massal.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php
        $tabs = [
          'text'   => '📝 Teks',
          'verify' => '✔ Verifikasi',
          'hmac'   => '🔑 HMAC',
          'bulk'   => '📋 Massal',
          'file'   => '📁 File',
        ];
        foreach ($tabs as $val => $lbl): ?>
          <button
            class="mode-tab <?= ($post_mode === $val || (!$post_mode && $val === 'text')) ? 'active' : '' ?>"
            type="button"
            role="tab"
            onclick="switchTab('<?= $val ?>')">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="sha-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" id="mode-input" name="mode"      value="<?= e($post_mode ?: 'text') ?>" />
        <input type="hidden" id="algo-input" name="algo"      value="<?= e($post_algo) ?>" />

        <!-- Algo selector (tampil di semua mode kecuali file) -->
        <div id="algo-selector">
          <label style="font-size:.85rem; font-weight:600; color:var(--muted); margin-bottom:.5rem; display:block;">
            Algoritma SHA
          </label>
          <div class="algo-pills">
            <?php
            $algos = [
              'sha224' => ['label' => 'SHA-224', 'bits' => '224'],
              'sha256' => ['label' => 'SHA-256', 'bits' => '256'],
              'sha384' => ['label' => 'SHA-384', 'bits' => '384'],
              'sha512' => ['label' => 'SHA-512', 'bits' => '512'],
            ];
            foreach ($algos as $val => $info): ?>
              <button type="button"
                class="algo-pill <?= $post_algo === $val ? 'active' : '' ?>"
                onclick="setAlgo('<?= $val ?>')"
                data-algo="<?= $val ?>"
                title="Output: <?= $info['bits'] ?> bit (<?= (int)$info['bits'] / 4 ?> karakter hex)">
                <?= $info['label'] ?><span class="pill-bits"><?= $info['bits'] ?>-bit</span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ══ PANEL: Teks ══ -->
        <div id="panel-text" class="mode-panel" <?= $post_mode !== 'text' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-text-main">Teks input</label>
            <textarea
              id="input-text-main"
              name="input_text"
              placeholder="Ketik atau tempel teks yang ingin di-hash..."
              oninput="generateJS()"
              style="min-height:120px;"
            ><?= $post_mode === 'text' ? e($post_input) : '' ?></textarea>
          </div>

          <div class="form-group">
            <label>Hash <span id="algo-label-main"><?= strtoupper($post_algo) ?></span></label>
            <div class="copy-wrap">
              <div class="hash-display" id="hash-display-main">
                <span class="hash-algo-badge" id="algo-badge-main"><?= strtoupper($post_algo) ?></span>
                <span id="hash-val-main">—</span>
              </div>
              <button class="copy-btn" type="button"
                onclick="copyHash('hash-val-main', this)">SALIN</button>
            </div>
            <div class="hash-len-bar">
              <div class="hash-len-fill" id="hash-len-fill" style="width:50%;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; font-family:var(--font-mono); font-size:.7rem; color:var(--muted); margin-top:.3rem;">
              <span id="hash-char-count">0 karakter</span>
              <span id="hash-bit-info"><?= (int)substr($post_algo, 3) ?> bit</span>
            </div>
          </div>

          <div class="form-group">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; cursor:pointer;">
              <input type="checkbox" name="uppercase" id="uppercase-main"
                <?= $post_uppercase ? 'checked' : '' ?>
                onchange="generateJS()"
                style="width:auto; accent-color:var(--accent);" />
              Tampilkan dalam UPPERCASE
            </label>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Generate via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('main')">Bersihkan</button>
            <button type="button" class="btn-ghost btn-sm" onclick="loadSample()">📄 Contoh</button>
          </div>
        </div>

        <!-- ══ PANEL: Verifikasi ══ -->
        <div id="panel-verify" class="mode-panel" <?= $post_mode !== 'verify' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Masukkan teks asli dan hash SHA yang ingin diverifikasi. Cocok digunakan untuk memverifikasi integritas unduhan atau data.</span>
          </div>

          <div class="form-group">
            <label for="input-text-verify">Teks / string asli</label>
            <textarea
              id="input-text-verify"
              name="input_text"
              placeholder="Masukkan teks asli yang ingin diverifikasi..."
              oninput="generateVerifyPreview()"
              style="min-height:100px;"
            ><?= $post_mode === 'verify' ? e($post_input) : '' ?></textarea>
          </div>

          <div class="form-group">
            <label for="verify-hash-input">Hash SHA yang ingin dicocokkan</label>
            <input
              type="text"
              id="verify-hash-input"
              name="verify_hash"
              placeholder="Tempel hash SHA di sini..."
              value="<?= e($post_verify) ?>"
              oninput="generateVerifyPreview()"
              style="font-family:var(--font-mono); font-size:.82rem; letter-spacing:.03em;" />
            <div style="font-family:var(--font-mono); font-size:.7rem; color:var(--muted); margin-top:.3rem;" id="verify-hash-len">
              0 karakter
            </div>
          </div>

          <div class="form-group">
            <label>Hash <span id="algo-label-verify"><?= strtoupper($post_algo) ?></span> dari teks input</label>
            <div class="hash-display" id="hash-display-verify">
              <span class="hash-algo-badge" id="algo-badge-verify"><?= strtoupper($post_algo) ?></span>
              <span id="hash-val-verify">—</span>
            </div>
          </div>

          <div id="verify-result-js" style="display:none;" class="verify-box">
            <span class="verify-icon" id="verify-icon-js"></span>
            <div>
              <div id="verify-msg-js"></div>
              <div id="verify-detail-js" style="font-size:.78rem; margin-top:.25rem; font-family:var(--font-mono); opacity:.75;"></div>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
            <button type="submit" class="btn-primary btn-sm">✔ Verifikasi via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('verify')">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: HMAC ══ -->
        <div id="panel-hmac" class="mode-panel" <?= $post_mode !== 'hmac' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>🔑</span>
            <span><strong>HMAC</strong> (Hash-based Message Authentication Code) — hash SHA yang dikombinasikan dengan secret key untuk memverifikasi keaslian dan integritas pesan.</span>
          </div>

          <div class="form-group">
            <label for="input-text-hmac">Pesan / data</label>
            <textarea
              id="input-text-hmac"
              name="input_text"
              placeholder="Masukkan pesan yang ingin di-sign dengan HMAC..."
              oninput="generateHMACPreview()"
              style="min-height:100px;"
            ><?= $post_mode === 'hmac' ? e($post_input) : '' ?></textarea>
          </div>

          <div class="form-group">
            <label for="hmac-key-input">Secret key</label>
            <input
              type="text"
              id="hmac-key-input"
              name="hmac_key"
              placeholder="Masukkan secret key rahasia..."
              value="<?= e($post_hmac_key) ?>"
              oninput="generateHMACPreview()"
              style="font-family:var(--font-mono);" />
            <div class="text-xs text-muted" style="margin-top:.3rem;">
              Gunakan key yang kuat dan acak. Jangan gunakan key yang sama di produksi.
            </div>
          </div>

          <div class="form-group">
            <label>HMAC-<span id="algo-label-hmac"><?= strtoupper($post_algo) ?></span></label>
            <div class="copy-wrap">
              <div class="hash-display" id="hash-display-hmac">
                <span class="hash-algo-badge" id="algo-badge-hmac">HMAC-<?= strtoupper($post_algo) ?></span>
                <span id="hash-val-hmac">—</span>
              </div>
              <button class="copy-btn" type="button"
                onclick="copyHash('hash-val-hmac', this)">SALIN</button>
            </div>
          </div>

          <div class="form-group">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; cursor:pointer;">
              <input type="checkbox" name="uppercase" id="uppercase-hmac"
                <?= $post_uppercase ? 'checked' : '' ?>
                onchange="generateHMACPreview()"
                style="width:auto; accent-color:var(--accent);" />
              Tampilkan dalam UPPERCASE
            </label>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm">⚙ Generate HMAC via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('hmac')">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: Massal ══ -->
        <div id="panel-bulk" class="mode-panel" <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-text-bulk">
              Teks massal <span class="text-muted text-sm">(satu baris = satu hash, maks. 500 baris)</span>
            </label>
            <textarea
              id="input-text-bulk"
              name="input_text"
              placeholder="secret_token_1&#10;api_key_production&#10;user_password_hash&#10;data_integrity_check"
              oninput="generateBulkJS()"
              style="min-height:160px; font-family:var(--font-mono);"
            ><?= $post_mode === 'bulk' ? e($post_input) : '' ?></textarea>
          </div>

          <div class="form-group">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; cursor:pointer;">
              <input type="checkbox" name="uppercase" id="uppercase-bulk"
                <?= $post_uppercase ? 'checked' : '' ?>
                onchange="generateBulkJS()"
                style="width:auto; accent-color:var(--accent);" />
              Tampilkan hash dalam UPPERCASE
            </label>
          </div>

          <div class="form-group">
            <label>Hasil hash massal</label>
            <div class="bulk-table-wrap" id="bulk-table-wrap">
              <div style="padding:1.5rem; text-align:center; color:var(--muted); font-size:.85rem;">
                Ketik teks di atas untuk melihat hasilnya.
              </div>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="button" class="btn-primary btn-sm" onclick="copyAllHashes()">📋 Salin semua hash</button>
            <button type="button" class="btn-ghost btn-sm" onclick="copyHashPairs()">📋 Salin pasangan input:hash</button>
            <button type="submit" class="btn-secondary btn-sm">⚙ Generate via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('bulk')">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: File ══ -->
        <div id="panel-file" class="mode-panel" <?= $post_mode !== 'file' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Hash dihitung di <strong>server PHP</strong> menggunakan fungsi <code>hash_file()</code>. Cocok untuk verifikasi integritas unduhan. Maks. 100 MB.</span>
          </div>

          <div class="form-group">
            <label>Pilih file</label>
            <div class="file-drop" id="file-drop-zone">
              <input type="file" name="upload_file" id="upload-file"
                onchange="handleFileSelect(this)" />
              <div class="file-drop-icon">📁</div>
              <div class="file-drop-label">Klik atau seret file ke sini</div>
              <div class="file-drop-hint">Semua jenis file · Maks. 100 MB</div>
              <div class="file-name-display" id="file-name-display"></div>
            </div>
          </div>

          <div class="form-group">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; cursor:pointer;">
              <input type="checkbox" name="uppercase" id="uppercase-file"
                <?= $post_uppercase ? 'checked' : '' ?>
                style="width:auto; accent-color:var(--accent);" />
              Tampilkan hash dalam UPPERCASE
            </label>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm">⚙ Hitung Hash File</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('file')">Bersihkan</button>
          </div>
        </div>

      </form><!-- /#sha-form -->
    </div><!-- /.panel -->

    <!-- ── Alert & hasil server ── -->
    <?php if (!empty($server_results) && $post_mode !== 'bulk'): ?>

      <?php if ($post_mode === 'verify' && $verify_result !== null): ?>
        <div class="verify-box <?= $verify_result ? 'match' : 'mismatch' ?>" style="margin-top:1rem;" role="alert">
          <span class="verify-icon"><?= $verify_result ? '✅' : '❌' ?></span>
          <div>
            <div><?= $verify_result ? 'Hash cocok! Integritas data terverifikasi.' : 'Hash tidak cocok! Data mungkin telah berubah atau rusak.' ?></div>
            <div style="font-size:.78rem; margin-top:.3rem; font-family:var(--font-mono); opacity:.8;">
              Dihitung : <?= e($server_results[0]['hash']) ?><br>
              Dimasukkan: <?= e($post_verify) ?>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="alert success" style="margin-top:1rem;" role="alert">
          <span>✓</span>
          <span>Hash <strong><?= e(strtoupper($server_results[0]['algo'])) ?></strong> berhasil digenerate via PHP server.</span>
        </div>
        <div class="panel" style="margin-top:1rem;">
          <div class="panel-title">⚙ Hasil Server PHP</div>
          <div class="form-group">
            <label>Input</label>
            <div class="result-box"><?= e($server_results[0]['input']) ?></div>
          </div>
          <div class="form-group">
            <label>Hash <?= e(strtoupper($server_results[0]['algo'])) ?></label>
            <div class="copy-wrap">
              <div class="result-box success" id="server-hash-out"><?= e($server_results[0]['hash']) ?></div>
              <button class="copy-btn" data-copy-target="server-hash-out">SALIN</button>
            </div>
          </div>
          <?php if (isset($server_results[0]['key'])): ?>
          <div class="form-group">
            <label>Key (tersembunyi)</label>
            <div class="result-box"><?= e($server_results[0]['key']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php elseif (!empty($server_results) && $post_mode === 'bulk'): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>Berhasil generate <strong><?= count($server_results) ?> hash <?= e(strtoupper($post_algo)) ?></strong> via PHP server.</span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div class="panel-title">⚙ Hasil Server PHP — Bulk</div>
        <div class="bulk-table-wrap">
          <table class="bulk-table">
            <thead><tr><th>#</th><th>Input</th><th>Hash <?= e(strtoupper($post_algo)) ?></th><th></th></tr></thead>
            <tbody>
              <?php foreach ($server_results as $idx => $row): ?>
              <tr>
                <td class="text-muted text-xs"><?= $idx + 1 ?></td>
                <td class="td-input" title="<?= e($row['input']) ?>"><?= e($row['input']) ?></td>
                <td class="td-hash"><?= e($row['hash']) ?></td>
                <td class="td-copy">
                  <button class="bulk-copy-btn"
                    onclick="copyText(<?= htmlspecialchars(json_encode($row['hash']), ENT_QUOTES) ?>, this)">
                    SALIN
                  </button>
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
      <div class="panel-title">💡 SHA-2 Family</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        SHA-2 adalah standar hash kriptografi yang direkomendasikan NIST, jauh lebih aman dari MD5 dan SHA-1.
      </p>
      <div style="overflow-x:auto;">
        <table class="algo-compare">
          <thead>
            <tr><th>Algo</th><th>Bit</th><th>Hex</th><th>Keamanan</th></tr>
          </thead>
          <tbody>
            <?php
            $compare = [
              ['SHA-224', '224', '56',  'Cukup'],
              ['SHA-256', '256', '64',  'Kuat ✓'],
              ['SHA-384', '384', '96',  'Sangat kuat'],
              ['SHA-512', '512', '128', 'Maksimal'],
            ];
            foreach ($compare as [$a,$b,$h,$s]): ?>
              <tr>
                <td class="mono"><?= $a ?></td>
                <td class="text-muted text-xs"><?= $b ?></td>
                <td class="text-muted text-xs"><?= $h ?></td>
                <td class="text-xs"><?= $s ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Contoh Hash SHA-256</div>
      <div style="display:flex; flex-direction:column; gap:.6rem;">
        <?php
        $examples = ['hello', 'Hello', 'hello world', 'SHA256', ''];
        foreach ($examples as $ex): ?>
          <div style="padding:.4rem 0; border-bottom:1px solid var(--border);">
            <div style="font-size:.78rem; color:var(--muted); margin-bottom:.2rem;">
              "<?= $ex === '' ? '(string kosong)' : e($ex) ?>"
            </div>
            <div
              style="font-family:var(--font-mono); font-size:.68rem; color:var(--accent); word-break:break-all; cursor:pointer;"
              onclick="loadExample(<?= htmlspecialchars(json_encode($ex), ENT_QUOTES) ?>)"
              title="Klik untuk load ke input">
              <?= hash('sha256', $ex) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔑 Kegunaan SHA-256</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><strong>JWT</strong> — tanda tangan token (HS256)</li>
        <li><strong>SSL/TLS</strong> — sertifikat digital</li>
        <li><strong>Git</strong> — identifikasi commit</li>
        <li><strong>Bitcoin</strong> — proof-of-work mining</li>
        <li><strong>Checksum</strong> — verifikasi unduhan</li>
        <li><strong>HMAC-SHA256</strong> — autentikasi API</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚠ SHA-256 vs MD5</div>
      <div class="alert warning" style="margin-bottom:0;">
        <span>⚠</span>
        <div class="text-sm">
          SHA-256 <strong>jauh lebih aman</strong> dari MD5. Untuk password, tetap gunakan
          <strong>bcrypt</strong> atau <strong>Argon2</strong> via <code>password_hash()</code>.
          SHA-256 ideal untuk verifikasi integritas, bukan hashing password.
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/md5-generator"      class="btn-ghost btn-sm btn-full">MD5 Generator</a>
        <a href="/tools/bcrypt-generator"   class="btn-ghost btn-sm btn-full">Bcrypt Generator</a>
        <a href="/tools/base64"             class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
        <a href="/tools/password-generator" class="btn-ghost btn-sm btn-full">Password Generator</a>
        <a href="/tools/jwt-decoder"        class="btn-ghost btn-sm btn-full">JWT Decoder</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   SHA256 Generator — logika JS (realtime)
   Menggunakan Web Crypto API (SubtleCrypto)
   yang sudah built-in di semua browser modern.
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

// ── Panjang hash per algoritma ───────────────────────────────
const ALGO_LENGTHS = { sha224: 56, sha256: 64, sha384: 96, sha512: 128 };
const ALGO_BITS    = { sha224: 224, sha256: 256, sha384: 384, sha512: 512 };

// Mapping nama JS → Web Crypto API
const ALGO_WEBCRYPTO = {
  sha224: 'SHA-1',      // fallback: SHA-224 tidak didukung Web Crypto, pakai pure JS
  sha256: 'SHA-256',
  sha384: 'SHA-384',
  sha512: 'SHA-512',
};

// ── SHA-224 pure JS (Web Crypto tidak support SHA-224) ───────
// Menggunakan konstanta K dan H yang berbeda dari SHA-256
function sha224pure(msg) {
  const H = [
    0xc1059ed8,0x367cd507,0x3070dd17,0xf70e5939,
    0xffc00b31,0x68581511,0x64f98fa7,0xbefa4fa4,
  ];
  return sha2core(msg, H, 224);
}

function sha256pure(msg) {
  const H = [
    0x6a09e667,0xbb67ae85,0x3c6ef372,0xa54ff53a,
    0x510e527f,0x9b05688c,0x1f83d9ab,0x5be0cd19,
  ];
  return sha2core(msg, H, 256);
}

function sha2core(msg, H, bits) {
  const K = [
    0x428a2f98,0x71374491,0xb5c0fbcf,0xe9b5dba5,
    0x3956c25b,0x59f111f1,0x923f82a4,0xab1c5ed5,
    0xd807aa98,0x12835b01,0x243185be,0x550c7dc3,
    0x72be5d74,0x80deb1fe,0x9bdc06a7,0xc19bf174,
    0xe49b69c1,0xefbe4786,0x0fc19dc6,0x240ca1cc,
    0x2de92c6f,0x4a7484aa,0x5cb0a9dc,0x76f988da,
    0x983e5152,0xa831c66d,0xb00327c8,0xbf597fc7,
    0xc6e00bf3,0xd5a79147,0x06ca6351,0x14292967,
    0x27b70a85,0x2e1b2138,0x4d2c6dfc,0x53380d13,
    0x650a7354,0x766a0abb,0x81c2c92e,0x92722c85,
    0xa2bfe8a1,0xa81a664b,0xc24b8b70,0xc76c51a3,
    0xd192e819,0xd6990624,0xf40e3585,0x106aa070,
    0x19a4c116,0x1e376c08,0x2748774c,0x34b0bcb5,
    0x391c0cb3,0x4ed8aa4a,0x5b9cca4f,0x682e6ff3,
    0x748f82ee,0x78a5636f,0x84c87814,0x8cc70208,
    0x90befffa,0xa4506ceb,0xbef9a3f7,0xc67178f2,
  ];

  // Pre-process
  const bytes = [];
  for (let i = 0; i < msg.length; i++) {
    const c = msg.charCodeAt(i);
    if (c < 128) { bytes.push(c); }
    else if (c < 2048) { bytes.push(192|(c>>6), 128|(c&63)); }
    else { bytes.push(224|(c>>12), 128|((c>>6)&63), 128|(c&63)); }
  }
  const bitLen = bytes.length * 8;
  bytes.push(0x80);
  while (bytes.length % 64 !== 56) bytes.push(0);
  for (let i = 7; i >= 0; i--) bytes.push((bitLen / Math.pow(256, i)) & 0xff);

  // Process blocks
  const h = [...H];
  for (let i = 0; i < bytes.length; i += 64) {
    const w = [];
    for (let j = 0; j < 16; j++) {
      w[j] = (bytes[i+j*4]<<24)|(bytes[i+j*4+1]<<16)|(bytes[i+j*4+2]<<8)|bytes[i+j*4+3];
    }
    for (let j = 16; j < 64; j++) {
      const s0 = rotr(w[j-15],7)^rotr(w[j-15],18)^(w[j-15]>>>3);
      const s1 = rotr(w[j-2],17)^rotr(w[j-2],19)^(w[j-2]>>>10);
      w[j] = (w[j-16]+s0+w[j-7]+s1)|0;
    }
    let [a,b,c,d,e,f,g,hh] = h;
    for (let j = 0; j < 64; j++) {
      const S1  = rotr(e,6)^rotr(e,11)^rotr(e,25);
      const ch  = (e&f)^((~e)&g);
      const t1  = (hh+S1+ch+K[j]+w[j])|0;
      const S0  = rotr(a,2)^rotr(a,13)^rotr(a,22);
      const maj = (a&b)^(a&c)^(b&c);
      const t2  = (S0+maj)|0;
      hh=g; g=f; f=e; e=(d+t1)|0; d=c; c=b; b=a; a=(t1+t2)|0;
    }
    h[0]=(h[0]+a)|0; h[1]=(h[1]+b)|0; h[2]=(h[2]+c)|0; h[3]=(h[3]+d)|0;
    h[4]=(h[4]+e)|0; h[5]=(h[5]+f)|0; h[6]=(h[6]+g)|0; h[7]=(h[7]+hh)|0;
  }
  const hexArr = bits === 224 ? h.slice(0,7) : h;
  return hexArr.map(n => ('00000000' + (n >>> 0).toString(16)).slice(-8)).join('');
}

function rotr(n, x) { return (n>>>x)|(n<<(32-x)); }

// ── Web Crypto API untuk SHA-256/384/512 ─────────────────────
async function hashWebCrypto(text, algo) {
  const encoder = new TextEncoder();
  const data    = encoder.encode(text);
  const hashBuf = await crypto.subtle.digest(ALGO_WEBCRYPTO[algo], data);
  const hashArr = Array.from(new Uint8Array(hashBuf));
  return hashArr.map(b => ('00' + b.toString(16)).slice(-2)).join('');
}

async function computeHash(text, algo) {
  if (!text) return '';
  if (algo === 'sha224') return sha224pure(text);
  if (algo === 'sha256') return sha256pure(text);
  return await hashWebCrypto(text, algo);
}

// ── HMAC-SHA256 via Web Crypto ────────────────────────────────
async function computeHMAC(text, key, algo) {
  if (!text || !key) return '';
  try {
    const enc      = new TextEncoder();
    const keyData  = await crypto.subtle.importKey(
      'raw', enc.encode(key),
      { name: 'HMAC', hash: ALGO_WEBCRYPTO[algo] || 'SHA-256' },
      false, ['sign']
    );
    const sig  = await crypto.subtle.sign('HMAC', keyData, enc.encode(text));
    return Array.from(new Uint8Array(sig))
      .map(b => ('00' + b.toString(16)).slice(-2)).join('');
  } catch {
    // SHA-224 tidak didukung HMAC di Web Crypto, fallback info
    return '(HMAC-SHA224 hanya tersedia via server PHP)';
  }
}

// ── State ────────────────────────────────────────────────────
let currentMode = '<?= $post_mode ?: 'text' ?>';
let currentAlgo = '<?= $post_algo ?>';

// ── Tab switching ─────────────────────────────────────────────
function switchTab(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;

  document.querySelectorAll('.mode-tab').forEach(t => {
    t.classList.toggle('active', t.getAttribute('onclick').includes("'" + mode + "'"));
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });

  // Sembunyikan algo selector di panel file
  document.getElementById('algo-selector').style.display = mode === 'file' ? 'none' : 'block';
}

// ── Set algoritma ─────────────────────────────────────────────
function setAlgo(algo) {
  currentAlgo = algo;
  document.getElementById('algo-input').value = algo;

  document.querySelectorAll('.algo-pill').forEach(p => {
    p.classList.toggle('active', p.dataset.algo === algo);
  });

  const label = algo.replace('sha', 'SHA-');
  document.querySelectorAll('[id^="algo-label-"]').forEach(el => el.textContent = label.toUpperCase());
  document.querySelectorAll('[id^="algo-badge-"]').forEach(el => {
    el.textContent = el.id.includes('hmac') ? 'HMAC-' + label.toUpperCase() : label.toUpperCase();
  });
  document.getElementById('hash-bit-info').textContent = ALGO_BITS[algo] + ' bit';

  // Update panjang bar
  const maxLen = 128; // sha512
  const fillPct = ((ALGO_LENGTHS[algo] / maxLen) * 100).toFixed(1);
  document.getElementById('hash-len-fill').style.width = fillPct + '%';

  // Re-generate
  generateJS();
  generateVerifyPreview();
  generateHMACPreview();
  generateBulkJS();
}

// ── Generate teks tunggal ─────────────────────────────────────
async function generateJS() {
  const text  = document.getElementById('input-text-main').value;
  const upper = document.getElementById('uppercase-main').checked;
  const valEl = document.getElementById('hash-val-main');
  const lenEl = document.getElementById('hash-char-count');

  if (!text) {
    valEl.textContent = '—';
    lenEl.textContent = '0 karakter';
    return;
  }

  let h = await computeHash(text, currentAlgo);
  if (upper) h = h.toUpperCase();
  valEl.textContent = h;
  lenEl.textContent = h.length + ' karakter';
}

// ── Generate verifikasi ───────────────────────────────────────
async function generateVerifyPreview() {
  const text    = document.getElementById('input-text-verify').value;
  const hashIn  = document.getElementById('verify-hash-input').value.trim();
  const lenEl   = document.getElementById('verify-hash-len');
  const valEl   = document.getElementById('hash-val-verify');
  const resEl   = document.getElementById('verify-result-js');
  const expected = ALGO_LENGTHS[currentAlgo] || 64;

  lenEl.textContent = hashIn.length + ' / ' + expected + ' karakter';

  if (!text) { valEl.textContent = '—'; resEl.style.display = 'none'; return; }

  const computed = await computeHash(text, currentAlgo);
  valEl.textContent = computed;

  if (hashIn.length === expected && /^[0-9a-fA-F]+$/i.test(hashIn)) {
    const match = computed.toLowerCase() === hashIn.toLowerCase();
    resEl.style.display = 'flex';
    resEl.className = 'verify-box ' + (match ? 'match' : 'mismatch');
    document.getElementById('verify-icon-js').textContent = match ? '✅' : '❌';
    document.getElementById('verify-msg-js').textContent  =
      match ? 'Hash cocok! Integritas data terverifikasi.' : 'Hash tidak cocok! Data mungkin telah berubah.';
    document.getElementById('verify-detail-js').textContent =
      match ? '' : 'Dihitung: ' + computed;
  } else {
    resEl.style.display = 'none';
  }
}

// ── Generate HMAC ─────────────────────────────────────────────
async function generateHMACPreview() {
  const text  = document.getElementById('input-text-hmac').value;
  const key   = document.getElementById('hmac-key-input').value;
  const upper = document.getElementById('uppercase-hmac').checked;
  const valEl = document.getElementById('hash-val-hmac');

  if (!text || !key) { valEl.textContent = '—'; return; }

  let h = await computeHMAC(text, key, currentAlgo);
  if (upper && !h.startsWith('(')) h = h.toUpperCase();
  valEl.textContent = h;
}

// ── Generate massal ───────────────────────────────────────────
async function generateBulkJS() {
  const raw   = document.getElementById('input-text-bulk').value;
  const upper = document.getElementById('uppercase-bulk').checked;
  const lines = raw.split('\n').filter(l => l.trim() !== '');
  const wrap  = document.getElementById('bulk-table-wrap');

  if (!lines.length) {
    wrap.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--muted);font-size:.85rem;">Ketik teks di atas untuk melihat hasilnya.</div>';
    return;
  }
  if (lines.length > 500) {
    wrap.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--accent4);font-size:.85rem;">⚠ Maksimal 500 baris.</div>';
    return;
  }

  // Batch hash agar tidak block UI
  const hashes = await Promise.all(lines.map(l => computeHash(l.trim(), currentAlgo)));

  const rows = lines.map((line, i) => {
    let h = hashes[i];
    if (upper) h = h.toUpperCase();
    return `<tr>
      <td class="text-muted text-xs">${i+1}</td>
      <td class="td-input" title="${esc(line.trim())}">${esc(line.trim())}</td>
      <td class="td-hash">${h}</td>
      <td class="td-copy">
        <button class="bulk-copy-btn" onclick="copyText(${JSON.stringify(h)}, this)">SALIN</button>
      </td>
    </tr>`;
  }).join('');

  const algoLabel = currentAlgo.replace('sha','SHA-').toUpperCase();
  wrap.innerHTML = `<table class="bulk-table">
    <thead><tr><th>#</th><th>Input</th><th>Hash ${algoLabel}</th><th></th></tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

// ── Copy helpers ──────────────────────────────────────────────
function copyHash(elId, btn) {
  const text = document.getElementById(elId)?.textContent;
  if (!text || text === '—') return;
  copyText(text, btn);
}

function copyAllHashes() {
  const cells = document.querySelectorAll('#bulk-table-wrap .td-hash');
  const hashes = Array.from(cells).map(td => td.textContent.trim());
  if (!hashes.length) return;
  copyText(hashes.join('\n'));
}

function copyHashPairs() {
  const rows = document.querySelectorAll('#bulk-table-wrap tbody tr');
  const pairs = Array.from(rows).map(tr => {
    const tds = tr.querySelectorAll('td');
    return (tds[1]?.textContent.trim() || '') + ':' + (tds[2]?.textContent.trim() || '');
  });
  if (!pairs.length) return;
  copyText(pairs.join('\n'));
}

function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) return;
    const orig = btn.textContent;
    btn.textContent = '✓';
    btn.style.cssText = 'background:var(--accent5);border-color:var(--accent5);color:#fff;';
    setTimeout(() => { btn.textContent = orig; btn.style.cssText = ''; }, 1500);
  });
}

// ── Utilitas ──────────────────────────────────────────────────
function handleFileSelect(input) {
  const disp = document.getElementById('file-name-display');
  if (input.files[0]) {
    disp.style.display = 'block';
    disp.textContent   = '📄 ' + input.files[0].name
      + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
  }
}

function clearPanel(panel) {
  const map = {
    main:   ['input-text-main'],
    verify: ['input-text-verify','verify-hash-input'],
    hmac:   ['input-text-hmac','hmac-key-input'],
    bulk:   ['input-text-bulk'],
  };
  if (map[panel]) {
    map[panel].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
  }
  if (panel === 'main')   { document.getElementById('hash-val-main').textContent = '—'; document.getElementById('hash-char-count').textContent = '0 karakter'; }
  if (panel === 'verify') { document.getElementById('hash-val-verify').textContent = '—'; document.getElementById('verify-result-js').style.display = 'none'; document.getElementById('verify-hash-len').textContent = '0 karakter'; }
  if (panel === 'hmac')   { document.getElementById('hash-val-hmac').textContent = '—'; }
  if (panel === 'bulk')   { document.getElementById('bulk-table-wrap').innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--muted);font-size:.85rem;">Ketik teks di atas untuk melihat hasilnya.</div>'; }
  if (panel === 'file')   { document.getElementById('upload-file').value = ''; document.getElementById('file-name-display').style.display = 'none'; }
}

function loadSample() {
  document.getElementById('input-text-main').value = 'hello world';
  generateJS();
}

function loadExample(text) {
  switchTab('text');
  document.getElementById('input-text-main').value = text;
  generateJS();
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Drag-and-drop ─────────────────────────────────────────────
const dropZone = document.getElementById('file-drop-zone');
if (dropZone) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    const fi = document.getElementById('upload-file');
    if (e.dataTransfer.files[0]) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      fi.files = dt.files;
      handleFileSelect(fi);
    }
  });
}

// ── Init ──────────────────────────────────────────────────────
(function() {
  const algo = document.getElementById('algo-input').value || 'sha256';
  document.querySelectorAll('.algo-pill').forEach(p => {
    p.classList.toggle('active', p.dataset.algo === algo);
  });
  const fillPct = ((ALGO_LENGTHS[algo] / 128) * 100).toFixed(1);
  document.getElementById('hash-len-fill').style.width = fillPct + '%';

  switchTab(currentMode);
  generateJS();
})();
</script>

<?php require '../../includes/footer.php'; ?>