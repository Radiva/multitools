<?php
require '../../includes/config.php';
/**
 * Multi Tools — MD5 Hash Generator
 * Generate hash MD5 dari teks, string, atau file upload.
 * Mendukung verifikasi hash, uppercase/lowercase, dan generate massal.
 * ============================================================ */

// ── Handle POST ──────────────────────────────────────────────
$server_results  = [];
$server_error    = '';
$post_input      = '';
$post_uppercase  = false;
$post_bulk       = false;
$post_verify     = '';
$verify_result   = null;
$post_mode       = 'text'; // text | file | verify | bulk

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode      = in_array($_POST['mode'] ?? 'text', ['text','file','verify','bulk'])
                      ? $_POST['mode'] : 'text';
  $post_uppercase = isset($_POST['uppercase']);
  $post_input     = $_POST['input_text'] ?? '';
  $post_verify    = trim($_POST['verify_hash'] ?? '');

  switch ($post_mode) {

    // ── Mode teks tunggal ──
    case 'text':
      if (trim($post_input) === '') {
        $server_error = 'Teks input tidak boleh kosong.';
      } else {
        $hash = md5($post_input);
        if ($post_uppercase) $hash = strtoupper($hash);
        $server_results[] = ['input' => $post_input, 'hash' => $hash];
      }
      break;

    // ── Mode upload file ──
    case 'file':
      if (empty($_FILES['upload_file']['tmp_name'])) {
        $server_error = 'Pilih file untuk di-hash.';
      } elseif ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
        $server_error = 'Gagal mengupload file. Kode error: ' . (int)$_FILES['upload_file']['error'];
      } elseif ($_FILES['upload_file']['size'] > 50 * 1024 * 1024) {
        $server_error = 'Ukuran file maksimal 50 MB.';
      } else {
        $hash = md5_file($_FILES['upload_file']['tmp_name']);
        if ($post_uppercase) $hash = strtoupper($hash);
        $server_results[] = [
          'input' => $_FILES['upload_file']['name']
                   . ' (' . round($_FILES['upload_file']['size'] / 1024, 1) . ' KB)',
          'hash'  => $hash,
        ];
      }
      break;

    // ── Mode verifikasi ──
    case 'verify':
      if (trim($post_input) === '') {
        $server_error = 'Masukkan teks untuk diverifikasi.';
      } elseif (strlen($post_verify) !== 32 || !ctype_xdigit($post_verify)) {
        $server_error = 'Hash MD5 yang dimasukkan tidak valid (harus 32 karakter hex).';
      } else {
        $hash          = md5($post_input);
        $verify_result = strtolower($hash) === strtolower($post_verify);
        $server_results[] = ['input' => $post_input, 'hash' => $hash];
      }
      break;

    // ── Mode massal ──
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
            $hash = md5(trim($line));
            if ($post_uppercase) $hash = strtoupper($hash);
            $server_results[] = ['input' => trim($line), 'hash' => $hash];
          }
        }
      }
      break;
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'MD5 Generator Online — Hash MD5 Teks & File | Multi Tools',
  'description' => 'Generate hash MD5 dari teks atau file secara instan. Mendukung verifikasi hash, uppercase/lowercase, generate massal hingga 500 baris, dan upload file hingga 50MB.',
  'keywords'    => 'md5 generator, md5 hash, md5 online, hash generator, md5 checksum, md5 file, verifikasi md5, multi tools',
  'og_title'    => 'MD5 Generator Online — Hash Teks & File Instan',
  'og_desc'     => 'Generate dan verifikasi hash MD5 dari teks atau file. Mode massal, uppercase, dan upload file.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Enkripsi & Hash', 'url' => SITE_URL . '/tools?cat=crypto'],
    ['name' => 'MD5 Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/md5-generator#webpage',
      'url'         => SITE_URL . '/tools/md5-generator',
      'name'        => 'MD5 Generator Online',
      'description' => 'Generate hash MD5 dari teks atau file secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',          'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Enkripsi & Hash',  'item' => SITE_URL . '/tools?cat=crypto'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'MD5 Generator',    'item' => SITE_URL . '/tools/md5-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'MD5 Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/md5-generator',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Tab mode ── */
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
  padding: .55rem .5rem;
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
.mode-tab.active {
  background: var(--accent);
  color: #fff;
}

/* ── Hash output ── */
.hash-display {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .7rem 1rem;
  font-family: var(--font-mono);
  font-size: .95rem;
  color: var(--accent);
  word-break: break-all;
  min-height: 48px;
  letter-spacing: .04em;
  transition: border-color var(--transition);
}
.hash-display .hash-val { flex: 1; }
.hash-display.success { border-color: var(--accent5); background: #f0fdf4; color: #15803d; }
.hash-display.warning { border-color: var(--accent4); background: #fffbeb; color: #92400e; }

/* ── Verify result ── */
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

/* ── File drop zone ── */
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
.file-drop:hover,
.file-drop.drag-over {
  border-color: var(--accent);
  background: rgba(37,99,235,.04);
}
.file-drop input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.file-drop-icon { font-size: 2rem; margin-bottom: .5rem; opacity: .5; }
.file-drop-label {
  font-size: .9rem;
  color: var(--muted);
  margin-bottom: .25rem;
}
.file-drop-hint { font-size: .75rem; color: var(--muted); font-family: var(--font-mono); }
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
.bulk-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .8rem;
}
.bulk-table th {
  position: sticky; top: 0;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: .5rem .9rem;
  text-align: left;
  font-family: var(--font-mono);
  font-size: .68rem;
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
  font-size: .75rem;
  color: var(--accent);
  word-break: break-all;
}
.bulk-table .td-input { color: var(--muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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

/* ── Char counter ── */
.char-counter {
  font-family: var(--font-mono);
  font-size: .7rem;
  color: var(--muted);
  text-align: right;
  margin-top: .25rem;
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
        <span aria-hidden="true">#</span> MD5 <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate hash MD5 dari teks atau file secara instan.
        Mendukung verifikasi checksum, mode massal, dan upload file hingga 50 MB.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist" aria-label="Mode generate">
        <?php
        $tabs = [
          'text'   => '📝 Teks',
          'verify' => '✔ Verifikasi',
          'bulk'   => '📋 Massal',
          'file'   => '📁 File',
        ];
        foreach ($tabs as $val => $lbl): ?>
          <button
            class="mode-tab <?= ($post_mode === $val || (empty($post_mode) && $val === 'text')) ? 'active' : '' ?>"
            type="button"
            role="tab"
            aria-selected="<?= $post_mode === $val ? 'true' : 'false' ?>"
            onclick="switchTab('<?= $val ?>')">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="md5-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" id="mode-input" name="mode" value="<?= e($post_mode ?: 'text') ?>" />

        <!-- ── PANEL: Teks ── -->
        <div id="panel-text" class="mode-panel" <?= $post_mode !== 'text' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-text-main">Teks input</label>
            <textarea
              id="input-text-main"
              name="input_text"
              placeholder="Ketik atau tempel teks yang ingin di-hash..."
              oninput="generateJS(); updateCharCount(this, 'char-count-main')"
              style="min-height:120px;"
            ><?= $post_mode === 'text' ? e($post_input) : '' ?></textarea>
            <div class="char-counter" id="char-count-main">0 karakter</div>
          </div>

          <div class="form-group">
            <label>Hash MD5</label>
            <div class="copy-wrap">
              <div class="hash-display" id="hash-display-main">
                <span class="hash-val" id="hash-val-main">—</span>
              </div>
              <button class="copy-btn" type="button" id="copy-main-btn"
                onclick="copyHash('hash-val-main', this)">SALIN</button>
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

        <!-- ── PANEL: Verifikasi ── -->
        <div id="panel-verify" class="mode-panel" <?= $post_mode !== 'verify' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Masukkan teks asli dan hash MD5 yang ingin diverifikasi. Tool akan membandingkan keduanya.</span>
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
            <label for="verify-hash-input">Hash MD5 yang ingin dicocokkan</label>
            <input
              type="text"
              id="verify-hash-input"
              name="verify_hash"
              placeholder="Contoh: 5d41402abc4b2a76b9719d911017c592"
              value="<?= e($post_verify) ?>"
              oninput="generateVerifyPreview()"
              maxlength="32"
              style="font-family:var(--font-mono); letter-spacing:.04em;" />
            <div class="char-counter" id="verify-hash-len">0 / 32 karakter</div>
          </div>

          <div class="form-group">
            <label>Hash MD5 dari teks input</label>
            <div class="hash-display" id="hash-display-verify">
              <span class="hash-val" id="hash-val-verify">—</span>
            </div>
          </div>

          <!-- Hasil verifikasi realtime -->
          <div id="verify-result-js" style="display:none;" class="verify-box">
            <span class="verify-icon" id="verify-icon-js"></span>
            <span id="verify-msg-js"></span>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
            <button type="submit" class="btn-primary btn-sm">✔ Verifikasi via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('verify')">Bersihkan</button>
          </div>
        </div>

        <!-- ── PANEL: Massal ── -->
        <div id="panel-bulk" class="mode-panel" <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-text-bulk">
              Teks massal <span class="text-muted text-sm">(satu baris = satu hash, maks. 500 baris)</span>
            </label>
            <textarea
              id="input-text-bulk"
              name="input_text"
              placeholder="password123&#10;admin&#10;hello world&#10;secret_key_2025"
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
            <button type="button" class="btn-ghost btn-sm" onclick="copyHashInputPairs()">📋 Salin pasangan input:hash</button>
            <button type="submit" class="btn-secondary btn-sm">⚙ Generate via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('bulk')">Bersihkan</button>
          </div>
        </div>

        <!-- ── PANEL: File ── -->
        <div id="panel-file" class="mode-panel" <?= $post_mode !== 'file' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>ℹ</span>
            <span>Hash MD5 file dihitung di <strong>server PHP</strong> menggunakan <code>md5_file()</code>. Maksimal ukuran file: 50 MB.</span>
          </div>

          <div class="form-group">
            <label>Pilih file</label>
            <div class="file-drop" id="file-drop-zone">
              <input type="file" name="upload_file" id="upload-file"
                onchange="handleFileSelect(this)" />
              <div class="file-drop-icon">📁</div>
              <div class="file-drop-label">Klik atau seret file ke sini</div>
              <div class="file-drop-hint">Semua jenis file didukung · Maks. 50 MB</div>
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
            <button type="submit" class="btn-primary btn-sm">⚙ Hitung MD5 File</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearPanel('file')">Bersihkan</button>
          </div>
        </div>

      </form><!-- /#md5-form -->
    </div><!-- /.panel -->

    <!-- Alert hasil server -->
    <?php if (!empty($server_results) && $post_mode !== 'bulk'): ?>
      <?php if ($post_mode === 'verify' && $verify_result !== null): ?>
        <div class="verify-box <?= $verify_result ? 'match' : 'mismatch' ?>" style="margin-top:1rem;" role="alert">
          <span class="verify-icon"><?= $verify_result ? '✅' : '❌' ?></span>
          <div>
            <div><?= $verify_result ? 'Hash cocok! Teks valid.' : 'Hash tidak cocok! Teks telah berubah atau salah.' ?></div>
            <div style="font-size:.8rem; margin-top:.3rem; font-family:var(--font-mono); opacity:.8;">
              Dihitung: <?= e($server_results[0]['hash']) ?><br>
              Dimasukkan: <?= e($post_verify) ?>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="alert success" style="margin-top:1rem;" role="alert">
          <span>✓</span>
          <span>Hash berhasil digenerate via PHP server.</span>
        </div>
        <div class="panel" style="margin-top:1rem;">
          <div class="panel-title">⚙ Hasil Server PHP</div>
          <div class="form-group">
            <label>Input</label>
            <div class="result-box"><?= e($server_results[0]['input']) ?></div>
          </div>
          <div class="form-group">
            <label>Hash MD5</label>
            <div class="copy-wrap">
              <div class="result-box success" id="server-hash-out"><?= e($server_results[0]['hash']) ?></div>
              <button class="copy-btn" data-copy-target="server-hash-out">SALIN</button>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Tabel hasil server bulk -->
    <?php if (!empty($server_results) && $post_mode === 'bulk'): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>Berhasil generate <strong><?= count($server_results) ?> hash</strong> via PHP server.</span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div class="panel-title">⚙ Hasil Server PHP — Bulk</div>
        <div class="bulk-table-wrap">
          <table class="bulk-table">
            <thead>
              <tr><th>#</th><th>Input</th><th>Hash MD5</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($server_results as $idx => $row): ?>
              <tr>
                <td class="text-muted text-xs"><?= $idx + 1 ?></td>
                <td class="td-input"><?= e($row['input']) ?></td>
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
      <div class="panel-title">💡 Tentang MD5</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        MD5 (Message Digest 5) menghasilkan hash 128-bit (32 karakter hex). Digunakan untuk verifikasi integritas data, bukan enkripsi password.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Selalu menghasilkan <strong>32 karakter</strong> hex</li>
        <li>Input yang sama → hash yang <strong>selalu sama</strong></li>
        <li>Perubahan 1 karakter → hash <strong>berbeda total</strong></li>
        <li><strong>Tidak bisa</strong> dibalik (one-way)</li>
        <li>Untuk password: gunakan <strong>bcrypt</strong> / <strong>Argon2</strong></li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Contoh Hash</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $examples = [
          'hello'         => md5('hello'),
          'Hello'         => md5('Hello'),
          'hello world'   => md5('hello world'),
          'password123'   => md5('password123'),
          '1234567890'    => md5('1234567890'),
        ];
        foreach ($examples as $input => $hash): ?>
          <div style="padding:.4rem 0; border-bottom:1px solid var(--border);">
            <div style="font-size:.78rem; color:var(--muted); margin-bottom:.2rem;">
              "<?= e($input) ?>"
            </div>
            <div style="font-family:var(--font-mono); font-size:.7rem; color:var(--accent);
                        word-break:break-all; cursor:pointer;"
              onclick="loadExample(<?= htmlspecialchars(json_encode($input), ENT_QUOTES) ?>)"
              title="Klik untuk load ke input">
              <?= $hash ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚠ Keamanan</div>
      <div class="alert warning" style="margin-bottom:0;">
        <span>⚠</span>
        <div class="text-sm">
          MD5 <strong>tidak aman</strong> untuk hashing password karena rentan terhadap serangan brute-force dan rainbow table.
          Gunakan <strong>password_hash()</strong> PHP dengan bcrypt atau Argon2 untuk password.
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/sha256-generator" class="btn-ghost btn-sm btn-full">SHA256 Generator</a>
        <a href="/tools/bcrypt-generator" class="btn-ghost btn-sm btn-full">Bcrypt Generator</a>
        <a href="/tools/base64"           class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
        <a href="/tools/password-generator" class="btn-ghost btn-sm btn-full">Password Generator</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   MD5 Generator — logika JS (realtime)
   MD5 di sisi klien menggunakan algoritma
   pure-JS. PHP dipakai saat form di-submit.
   ────────────────────────────────────────── */

/* ── Implementasi MD5 pure JavaScript ─────────────────────── */
function md5(string) {
  function safeAdd(x, y) {
    const lsw = (x & 0xffff) + (y & 0xffff);
    const msw = (x >> 16) + (y >> 16) + (lsw >> 16);
    return (msw << 16) | (lsw & 0xffff);
  }
  function bitRotateLeft(num, cnt) { return (num << cnt) | (num >>> (32 - cnt)); }
  function md5cmn(q, a, b, x, s, t) { return safeAdd(bitRotateLeft(safeAdd(safeAdd(a, q), safeAdd(x, t)), s), b); }
  function md5ff(a,b,c,d,x,s,t) { return md5cmn((b&c)|((~b)&d),a,b,x,s,t); }
  function md5gg(a,b,c,d,x,s,t) { return md5cmn((b&d)|(c&(~d)),a,b,x,s,t); }
  function md5hh(a,b,c,d,x,s,t) { return md5cmn(b^c^d,a,b,x,s,t); }
  function md5ii(a,b,c,d,x,s,t) { return md5cmn(c^(b|(~d)),a,b,x,s,t); }

  function md5blks(s) {
    const md5blks = [], len = s.length;
    for (let i = 0; i < len; i += 4) {
      md5blks[i>>2] = s.charCodeAt(i)+(s.charCodeAt(i+1)<<8)+
                      (s.charCodeAt(i+2)<<16)+(s.charCodeAt(i+3)<<24);
    }
    md5blks[len>>2] |= 0x80 << ((len%4)<<3);
    md5blks[(((len+8)>>6)<<4)+14] = len*8;
    return md5blks;
  }

  // Convert UTF-8
  const utf8 = unescape(encodeURIComponent(string));
  const blks = md5blks(utf8);
  let a=1732584193,b=-271733879,c=-1732584194,d=271733878;

  for (let i = 0; i < blks.length; i += 16) {
    const aa=a,bb=b,cc=c,dd=d;
    a=md5ff(a,b,c,d,blks[i],    7,-680876936);   d=md5ff(d,a,b,c,blks[i+1], 12,-389564586);
    c=md5ff(c,d,a,b,blks[i+2], 17, 606105819);   b=md5ff(b,c,d,a,blks[i+3], 22,-1044525330);
    a=md5ff(a,b,c,d,blks[i+4],  7,-176418897);   d=md5ff(d,a,b,c,blks[i+5], 12, 1200080426);
    c=md5ff(c,d,a,b,blks[i+6], 17,-1473231341);  b=md5ff(b,c,d,a,blks[i+7], 22,-45705983);
    a=md5ff(a,b,c,d,blks[i+8],  7, 1770035416);  d=md5ff(d,a,b,c,blks[i+9], 12,-1958414417);
    c=md5ff(c,d,a,b,blks[i+10],17,-42063);        b=md5ff(b,c,d,a,blks[i+11],22,-1990404162);
    a=md5ff(a,b,c,d,blks[i+12], 7, 1804603682);  d=md5ff(d,a,b,c,blks[i+13],12,-40341101);
    c=md5ff(c,d,a,b,blks[i+14],17,-1502002290);  b=md5ff(b,c,d,a,blks[i+15],22,1236535329);

    a=md5gg(a,b,c,d,blks[i+1],  5,-165796510);   d=md5gg(d,a,b,c,blks[i+6],  9,-1069501632);
    c=md5gg(c,d,a,b,blks[i+11],14, 643717713);   b=md5gg(b,c,d,a,blks[i],   20,-373897302);
    a=md5gg(a,b,c,d,blks[i+5],  5,-701558691);   d=md5gg(d,a,b,c,blks[i+10], 9, 38016083);
    c=md5gg(c,d,a,b,blks[i+15],14,-660478335);   b=md5gg(b,c,d,a,blks[i+4], 20,-405537848);
    a=md5gg(a,b,c,d,blks[i+9],  5, 568446438);   d=md5gg(d,a,b,c,blks[i+14], 9,-1019803690);
    c=md5gg(c,d,a,b,blks[i+3], 14,-187363961);   b=md5gg(b,c,d,a,blks[i+8], 20, 1163531501);
    a=md5gg(a,b,c,d,blks[i+13], 5,-1444681467);  d=md5gg(d,a,b,c,blks[i+2],  9,-51403784);
    c=md5gg(c,d,a,b,blks[i+7], 14, 1735328473);  b=md5gg(b,c,d,a,blks[i+12],20,-1926607734);

    a=md5hh(a,b,c,d,blks[i+5],  4,-378558);       d=md5hh(d,a,b,c,blks[i+8], 11,-2022574463);
    c=md5hh(c,d,a,b,blks[i+11],16, 1839030562);  b=md5hh(b,c,d,a,blks[i+14],23,-35309556);
    a=md5hh(a,b,c,d,blks[i+1],  4,-1530992060);  d=md5hh(d,a,b,c,blks[i+4], 11, 1272893353);
    c=md5hh(c,d,a,b,blks[i+7], 16,-155497632);   b=md5hh(b,c,d,a,blks[i+10],23,-1094730640);
    a=md5hh(a,b,c,d,blks[i+13], 4, 681279174);   d=md5hh(d,a,b,c,blks[i],   11,-358537222);
    c=md5hh(c,d,a,b,blks[i+3], 16,-722521979);   b=md5hh(b,c,d,a,blks[i+6], 23, 76029189);
    a=md5hh(a,b,c,d,blks[i+9],  4,-640364487);   d=md5hh(d,a,b,c,blks[i+12],11,-421815835);
    c=md5hh(c,d,a,b,blks[i+15],16, 530742520);   b=md5hh(b,c,d,a,blks[i+2], 23,-995338651);

    a=md5ii(a,b,c,d,blks[i],    6,-198630844);    d=md5ii(d,a,b,c,blks[i+7], 10, 1126891415);
    c=md5ii(c,d,a,b,blks[i+14],15,-1416354905);  b=md5ii(b,c,d,a,blks[i+5], 21,-57434055);
    a=md5ii(a,b,c,d,blks[i+12], 6, 1700485571);  d=md5ii(d,a,b,c,blks[i+3], 10,-1894986606);
    c=md5ii(c,d,a,b,blks[i+10],15,-1051523);      b=md5ii(b,c,d,a,blks[i+1], 21,-2054922799);
    a=md5ii(a,b,c,d,blks[i+8],  6, 1873313359);  d=md5ii(d,a,b,c,blks[i+15],10,-30611744);
    c=md5ii(c,d,a,b,blks[i+6], 15,-1560198380);  b=md5ii(b,c,d,a,blks[i+13],21, 1309151649);
    a=md5ii(a,b,c,d,blks[i+4],  6,-145523070);   d=md5ii(d,a,b,c,blks[i+11],10,-1120210379);
    c=md5ii(c,d,a,b,blks[i+2], 15, 718787259);   b=md5ii(b,c,d,a,blks[i+9], 21,-343485551);

    a=safeAdd(a,aa); b=safeAdd(b,bb); c=safeAdd(c,cc); d=safeAdd(d,dd);
  }

  function hex(n) {
    let s='', v;
    for (let i=0; i<4; i++) {
      v = (n >>> (i*8)) & 0xff;
      s += ('0'+v.toString(16)).slice(-2);
    }
    return s;
  }
  return hex(a)+hex(b)+hex(c)+hex(d);
}

/* ── Tab switching ─────────────────────────────────────────── */
function switchTab(mode) {
  document.getElementById('mode-input').value = mode;

  document.querySelectorAll('.mode-tab').forEach(t => {
    t.classList.toggle('active', t.getAttribute('onclick').includes("'" + mode + "'"));
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });

  // Sync nama field textarea ke name="input_text" yang benar
  // (semua panel punya textarea sendiri; PHP hanya baca $_POST['input_text'])
}

/* ── Generate teks tunggal ─────────────────────────────────── */
function generateJS() {
  const text  = document.getElementById('input-text-main').value;
  const upper = document.getElementById('uppercase-main').checked;
  const valEl = document.getElementById('hash-val-main');

  if (!text) { valEl.textContent = '—'; return; }
  let h = md5(text);
  if (upper) h = h.toUpperCase();
  valEl.textContent = h;
}

/* ── Generate verifikasi ───────────────────────────────────── */
function generateVerifyPreview() {
  const text    = document.getElementById('input-text-verify').value;
  const hashIn  = document.getElementById('verify-hash-input').value.trim();
  const lenEl   = document.getElementById('verify-hash-len');
  const valEl   = document.getElementById('hash-val-verify');
  const resultEl = document.getElementById('verify-result-js');

  lenEl.textContent = hashIn.length + ' / 32 karakter';

  if (!text) { valEl.textContent = '—'; resultEl.style.display = 'none'; return; }

  const computed = md5(text);
  valEl.textContent = computed;

  if (hashIn.length === 32 && /^[0-9a-fA-F]+$/.test(hashIn)) {
    const match = computed.toLowerCase() === hashIn.toLowerCase();
    resultEl.style.display = 'flex';
    resultEl.className = 'verify-box ' + (match ? 'match' : 'mismatch');
    document.getElementById('verify-icon-js').textContent = match ? '✅' : '❌';
    document.getElementById('verify-msg-js').textContent  = match
      ? 'Hash cocok! Teks valid.' : 'Hash tidak cocok! Teks berbeda.';
  } else {
    resultEl.style.display = 'none';
  }
}

/* ── Generate massal ───────────────────────────────────────── */
function generateBulkJS() {
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

  const rows = lines.map((line, i) => {
    let h = md5(line.trim());
    if (upper) h = h.toUpperCase();
    const safeH = h.replace(/'/g, "\\'");
    return `<tr>
      <td class="text-muted text-xs">${i+1}</td>
      <td class="td-input" title="${esc(line.trim())}">${esc(line.trim())}</td>
      <td class="td-hash">${h}</td>
      <td class="td-copy">
        <button class="bulk-copy-btn" onclick="copyText('${safeH}', this)">SALIN</button>
      </td>
    </tr>`;
  }).join('');

  wrap.innerHTML = `<table class="bulk-table">
    <thead><tr><th>#</th><th>Input</th><th>Hash MD5</th><th></th></tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

/* ── Copy helpers ──────────────────────────────────────────── */
function copyHash(elId, btn) {
  const text = document.getElementById(elId)?.textContent;
  if (!text || text === '—') return;
  copyText(text, btn);
}

function copyAllHashes() {
  const rows  = document.querySelectorAll('#bulk-table-wrap .td-hash');
  const hashes = Array.from(rows).map(td => td.textContent.trim());
  if (!hashes.length) return;
  copyText(hashes.join('\n'));
}

function copyHashInputPairs() {
  const rows = document.querySelectorAll('#bulk-table-wrap tr:not(:first-child)');
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

/* ── Utilitas ──────────────────────────────────────────────── */
function updateCharCount(el, countId) {
  document.getElementById(countId).textContent = el.value.length + ' karakter';
}

function handleFileSelect(input) {
  const disp = document.getElementById('file-name-display');
  if (input.files[0]) {
    disp.style.display = 'block';
    disp.textContent   = '📄 ' + input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
  }
}

function clearPanel(panel) {
  if (panel === 'main') {
    document.getElementById('input-text-main').value = '';
    document.getElementById('hash-val-main').textContent = '—';
    document.getElementById('char-count-main').textContent = '0 karakter';
  } else if (panel === 'verify') {
    document.getElementById('input-text-verify').value = '';
    document.getElementById('verify-hash-input').value = '';
    document.getElementById('hash-val-verify').textContent = '—';
    document.getElementById('verify-result-js').style.display = 'none';
    document.getElementById('verify-hash-len').textContent = '0 / 32 karakter';
  } else if (panel === 'bulk') {
    document.getElementById('input-text-bulk').value = '';
    document.getElementById('bulk-table-wrap').innerHTML =
      '<div style="padding:1.5rem;text-align:center;color:var(--muted);font-size:.85rem;">Ketik teks di atas untuk melihat hasilnya.</div>';
  } else if (panel === 'file') {
    document.getElementById('upload-file').value = '';
    document.getElementById('file-name-display').style.display = 'none';
  }
}

function loadSample() {
  document.getElementById('input-text-main').value = 'hello world';
  generateJS();
  updateCharCount(document.getElementById('input-text-main'), 'char-count-main');
}

function loadExample(text) {
  switchTab('text');
  document.getElementById('input-text-main').value = text;
  generateJS();
  updateCharCount(document.getElementById('input-text-main'), 'char-count-main');
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Drag-and-drop styling
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

// Init
generateJS();
</script>

<?php require '../../includes/footer.php'; ?>