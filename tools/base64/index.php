<?php
require '../../includes/config.php';
/**
 * Multi Tools — Base64 Encode / Decode
 * Encode dan decode teks, URL, atau file ke/dari Base64.
 * Mendukung Base64 standar, URL-safe, dan image preview.
 * ============================================================ */

// ── Handle POST ──────────────────────────────────────────────
$server_result  = '';
$server_error   = '';
$post_input     = '';
$post_action    = 'encode'; // encode | decode
$post_mode      = 'text';   // text | url | file | image
$post_urlsafe   = false;
$post_linebreak = false;
$server_is_image = false;
$server_mime     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_action    = $_POST['action']   === 'decode' ? 'decode' : 'encode';
  $post_mode      = in_array($_POST['mode'] ?? 'text', ['text','url','file','image'])
                      ? $_POST['mode'] : 'text';
  $post_urlsafe   = isset($_POST['urlsafe']);
  $post_linebreak = isset($_POST['linebreak']);
  $post_input     = $_POST['input_text'] ?? '';

  switch ($post_mode) {

    // ── Teks & URL ──
    case 'text':
    case 'url':
      if (trim($post_input) === '') {
        $server_error = 'Input tidak boleh kosong.';
        break;
      }
      if ($post_action === 'encode') {
        $encoded = base64_encode($post_input);
        if ($post_urlsafe)   $encoded = strtr($encoded, '+/', '-_');
        if ($post_linebreak) $encoded = chunk_split($encoded, 76, "\n");
        $server_result = rtrim($encoded, "\n");
      } else {
        // Decode
        $clean = strtr(trim($post_input), '-_', '+/');
        // Tambah padding jika kurang
        $pad    = strlen($clean) % 4;
        if ($pad) $clean .= str_repeat('=', 4 - $pad);
        $decoded = base64_decode($clean, true);
        if ($decoded === false) {
          $server_error = 'Input bukan Base64 yang valid.';
        } else {
          $server_result = $decoded;
        }
      }
      break;

    // ── File upload ──
    case 'file':
      if (empty($_FILES['upload_file']['tmp_name'])) {
        $server_error = 'Pilih file untuk di-encode.';
        break;
      }
      if ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
        $server_error = 'Gagal mengupload file. Kode error: ' . (int)$_FILES['upload_file']['error'];
        break;
      }
      if ($_FILES['upload_file']['size'] > 5 * 1024 * 1024) {
        $server_error = 'Ukuran file maksimal 5 MB.';
        break;
      }
      $fileContent = file_get_contents($_FILES['upload_file']['tmp_name']);
      $mime        = mime_content_type($_FILES['upload_file']['tmp_name']);
      $encoded     = base64_encode($fileContent);
      if ($post_urlsafe)   $encoded = strtr($encoded, '+/', '-_');
      if ($post_linebreak) $encoded = chunk_split($encoded, 76, "\n");
      $server_result   = 'data:' . $mime . ';base64,' . rtrim($encoded, "\n");
      $server_is_image = str_starts_with($mime, 'image/');
      $server_mime     = $mime;
      break;

    // ── Image decode (Data URI → preview) ──
    case 'image':
      if (trim($post_input) === '') {
        $server_error = 'Masukkan string Base64 gambar.';
        break;
      }
      // Ekstrak base64 dari data URI jika ada
      if (preg_match('/^data:(image\/\w+);base64,(.+)$/s', trim($post_input), $m)) {
        $server_mime   = $m[1];
        $b64           = $m[2];
      } else {
        $b64           = trim($post_input);
        $server_mime   = 'image/png'; // default
      }
      $clean = strtr($b64, '-_', '+/');
      $pad   = strlen($clean) % 4;
      if ($pad) $clean .= str_repeat('=', 4 - $pad);
      $decoded = base64_decode($clean, true);
      if ($decoded === false) {
        $server_error = 'String Base64 tidak valid.';
      } else {
        $server_result   = 'data:' . $server_mime . ';base64,' . base64_encode($decoded);
        $server_is_image = true;
      }
      break;
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Base64 Encode & Decode Online — Konversi Teks & File | Multi Tools',
  'description' => 'Encode dan decode Base64 dari teks, URL, atau file secara instan. Mendukung Base64 URL-safe, line break, preview gambar dari Data URI, dan upload file hingga 5MB.',
  'keywords'    => 'base64 encode, base64 decode, base64 online, konversi base64, url safe base64, data uri, base64 image, multi tools',
  'og_title'    => 'Base64 Encode & Decode Online — Teks, URL, dan File',
  'og_desc'     => 'Encode/decode Base64 dari teks atau file. URL-safe, image preview, line break, dan mode massal.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Enkripsi & Hash', 'url' => SITE_URL . '/tools?cat=crypto'],
    ['name' => 'Base64 Encode/Decode'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/base64#webpage',
      'url'         => SITE_URL . '/tools/base64',
      'name'        => 'Base64 Encode & Decode Online',
      'description' => 'Encode dan decode Base64 dari teks atau file secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',              'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Enkripsi & Hash',      'item' => SITE_URL . '/tools?cat=crypto'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Base64 Encode/Decode', 'item' => SITE_URL . '/tools/base64'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Base64 Encode/Decode',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/base64',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Action toggle (Encode / Decode) ── */
.action-toggle {
  display: inline-flex;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.action-btn {
  padding: .55rem 1.5rem;
  background: var(--bg);
  border: none;
  font-family: var(--font-body);
  font-size: .88rem;
  font-weight: 700;
  color: var(--muted);
  cursor: pointer;
  transition: all var(--transition);
  letter-spacing: .02em;
}
.action-btn:first-child { border-right: 1px solid var(--border); }
.action-btn.active      { background: var(--accent); color: #fff; }
.action-btn:hover:not(.active) { background: var(--surface); color: var(--text); }

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
  padding: .5rem .4rem;
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
.mode-tab.active      { background: var(--accent2); color: #fff; }

/* ── Output area ── */
.output-area {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .85rem 1rem;
  font-family: var(--font-mono);
  font-size: .82rem;
  line-height: 1.65;
  word-break: break-all;
  min-height: 100px;
  white-space: pre-wrap;
  color: var(--accent);
  transition: border-color var(--transition);
}
.output-area.decoded { color: var(--text); font-family: var(--font-body); font-size: .9rem; }
.output-area.error   { color: #dc2626; border-color: #fca5a5; background: #fef2f2; }

/* ── Stats bar ── */
.mini-stats {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  padding: .65rem .9rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: var(--font-mono);
  font-size: .72rem;
  color: var(--muted);
  margin-top: .5rem;
}
.mini-stats .ms-item { display: flex; gap: .35rem; align-items: center; }
.mini-stats .ms-val  { color: var(--accent); font-weight: 700; }

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
  border-color: var(--accent2);
  background: rgba(14,165,233,.04);
}
.file-drop input[type="file"] {
  position: absolute; inset: 0;
  opacity: 0; cursor: pointer;
  width: 100%; height: 100%;
}
.file-drop-icon  { font-size: 2rem; margin-bottom: .5rem; opacity: .4; }
.file-drop-label { font-size: .9rem; color: var(--muted); margin-bottom: .25rem; }
.file-drop-hint  { font-size: .75rem; color: var(--muted); font-family: var(--font-mono); }
.file-name-display {
  margin-top: .75rem;
  font-family: var(--font-mono);
  font-size: .82rem;
  color: var(--accent2);
  font-weight: 600;
  display: none;
}

/* ── Image preview ── */
.image-preview-wrap {
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: repeating-conic-gradient(#e0e0e0 0% 25%, #fff 0% 50%) 0 0 / 16px 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 120px;
  max-height: 320px;
}
.image-preview-wrap img {
  max-width: 100%;
  max-height: 320px;
  object-fit: contain;
  display: block;
}
.image-preview-empty {
  color: var(--muted);
  font-size: .85rem;
  padding: 2rem;
  text-align: center;
}

/* ── Char count ── */
.char-count {
  font-family: var(--font-mono);
  font-size: .7rem;
  color: var(--muted);
  text-align: right;
  margin-top: .25rem;
}

/* ── Swap arrow ── */
.swap-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  width: 100%;
  padding: .45rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--muted);
  font-size: .85rem;
  font-weight: 700;
  transition: all var(--transition);
  margin: .5rem 0;
}
.swap-btn:hover { border-color: var(--accent2); color: var(--accent2); background: rgba(14,165,233,.05); }

/* ── Ref table ── */
.ref-table { width: 100%; border-collapse: collapse; font-size: .77rem; }
.ref-table th {
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  padding: .4rem .65rem;
  text-align: left;
  font-family: var(--font-mono);
  font-size: .65rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--muted);
}
.ref-table td {
  padding: .38rem .65rem;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.ref-table tr:last-child td { border-bottom: none; }
.ref-table .mono { font-family: var(--font-mono); font-size: .72rem; color: var(--accent); word-break: break-all; }
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
        <span aria-hidden="true">🔄</span> Base64 <span>Encode / Decode</span>
      </div>
      <p class="page-lead">
        Konversi teks, URL, atau file ke/dari Base64 secara instan.
        Mendukung Base64 URL-safe, line break, dan preview gambar dari Data URI.
      </p>

      <form method="POST" action="" id="b64-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" id="action-input" name="action" value="<?= e($post_action) ?>" />
        <input type="hidden" id="mode-input"   name="mode"   value="<?= e($post_mode) ?>" />

        <!-- Encode / Decode toggle -->
        <div>
          <div class="action-toggle" role="group" aria-label="Pilih aksi">
            <button type="button" class="action-btn <?= $post_action === 'encode' ? 'active' : '' ?>"
              onclick="setAction('encode')" id="btn-encode">
              ↑ Encode
            </button>
            <button type="button" class="action-btn <?= $post_action === 'decode' ? 'active' : '' ?>"
              onclick="setAction('decode')" id="btn-decode">
              ↓ Decode
            </button>
          </div>
        </div>

        <!-- Mode tabs -->
        <div class="mode-tabs" role="tablist">
          <?php
          $tabs = ['text' => '📝 Teks', 'url' => '🔗 URL', 'file' => '📁 File', 'image' => '🖼 Gambar'];
          foreach ($tabs as $val => $lbl): ?>
            <button type="button" role="tab"
              class="mode-tab <?= $post_mode === $val ? 'active' : '' ?>"
              onclick="switchTab('<?= $val ?>')">
              <?= $lbl ?>
            </button>
          <?php endforeach; ?>
        </div>

        <!-- ══ PANEL: Teks ══ -->
        <div id="panel-text" class="mode-panel" <?= $post_mode !== 'text' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-text-main" id="label-input-main">Teks input</label>
            <textarea
              id="input-text-main"
              name="input_text"
              placeholder="Ketik atau tempel teks di sini..."
              oninput="processJS()"
              style="min-height:130px;"
            ><?= ($post_mode === 'text') ? e($post_input) : '' ?></textarea>
            <div class="char-count" id="char-count-main">0 karakter</div>
          </div>

          <button type="button" class="swap-btn" onclick="swapTexts()" title="Tukar input dan output">
            ⇅ Tukar input &amp; output
          </button>

          <div class="form-group">
            <label id="label-output-main">Hasil</label>
            <div class="copy-wrap">
              <div class="output-area" id="output-main" aria-live="polite">—</div>
              <button class="copy-btn" type="button" onclick="copyOutput('output-main', this)">SALIN</button>
            </div>
            <div class="mini-stats" id="mini-stats-main" style="display:none;">
              <span class="ms-item">Input: <span class="ms-val" id="ms-in-len">0</span> karakter</span>
              <span class="ms-item">Output: <span class="ms-val" id="ms-out-len">0</span> karakter</span>
              <span class="ms-item">Rasio: <span class="ms-val" id="ms-ratio">—</span></span>
            </div>
          </div>

          <div class="form-group">
            <label>Opsi</label>
            <div style="display:flex; flex-wrap:wrap; gap:.75rem 1.5rem;">
              <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
                <input type="checkbox" name="urlsafe" id="opt-urlsafe"
                  <?= $post_urlsafe ? 'checked' : '' ?>
                  onchange="processJS()"
                  style="width:auto; accent-color:var(--accent2);" />
                URL-safe (<code>+/</code> → <code>-_</code>)
              </label>
              <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;"
                id="opt-lb-wrap">
                <input type="checkbox" name="linebreak" id="opt-linebreak"
                  <?= $post_linebreak ? 'checked' : '' ?>
                  onchange="processJS()"
                  style="width:auto; accent-color:var(--accent2);" />
                Tambah line break per 76 karakter (MIME)
              </label>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Proses via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">Bersihkan</button>
            <button type="button" class="btn-ghost btn-sm" onclick="loadSample()">📄 Contoh</button>
          </div>
        </div>

        <!-- ══ PANEL: URL ══ -->
        <div id="panel-url" class="mode-panel" <?= $post_mode !== 'url' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>🔗</span>
            <span>Mode URL otomatis menggunakan Base64 <strong>URL-safe</strong> (<code>+</code>→<code>-</code>, <code>/</code>→<code>_</code>) dan menghilangkan padding <code>=</code> — cocok untuk parameter URL dan JWT.</span>
          </div>

          <div class="form-group">
            <label for="input-url">URL atau string untuk di-encode/decode</label>
            <textarea
              id="input-url"
              name="input_text"
              placeholder="Contoh: https://example.com/path?key=value&other=123"
              oninput="processUrlJS()"
              style="min-height:100px;"
            ><?= ($post_mode === 'url') ? e($post_input) : '' ?></textarea>
          </div>

          <button type="button" class="swap-btn" onclick="swapUrl()">⇅ Tukar input &amp; output</button>

          <div class="form-group">
            <label>Hasil</label>
            <div class="copy-wrap">
              <div class="output-area" id="output-url" aria-live="polite">—</div>
              <button class="copy-btn" type="button" onclick="copyOutput('output-url', this)">SALIN</button>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-secondary btn-sm">⚙ Proses via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="document.getElementById('input-url').value=''; processUrlJS();">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: File ══ -->
        <div id="panel-file" class="mode-panel" <?= $post_mode !== 'file' ? 'style="display:none;"' : '' ?>>
          <div class="alert info" style="margin-bottom:1.25rem;">
            <span>📁</span>
            <span>File akan diencode menjadi string Base64 + Data URI. Cocok untuk menyisipkan file ke HTML/CSS. Maks. <strong>5 MB</strong>.</span>
          </div>

          <div class="form-group">
            <label>Pilih file</label>
            <div class="file-drop" id="file-drop-zone">
              <input type="file" name="upload_file" id="upload-file"
                onchange="handleFileSelect(this)" />
              <div class="file-drop-icon">📁</div>
              <div class="file-drop-label">Klik atau seret file ke sini</div>
              <div class="file-drop-hint">Semua jenis file · Maks. 5 MB</div>
              <div class="file-name-display" id="file-name-display"></div>
            </div>
          </div>

          <div class="form-group">
            <label>Opsi</label>
            <div style="display:flex; flex-wrap:wrap; gap:.75rem 1.5rem;">
              <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
                <input type="checkbox" name="urlsafe" id="opt-urlsafe-file"
                  style="width:auto; accent-color:var(--accent2);" />
                URL-safe
              </label>
              <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
                <input type="checkbox" name="linebreak" id="opt-lb-file"
                  style="width:auto; accent-color:var(--accent2);" />
                Line break per 76 karakter
              </label>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm">⚙ Encode File via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearFile()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: Gambar ══ -->
        <div id="panel-image" class="mode-panel" <?= $post_mode !== 'image' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="input-image">String Base64 gambar (atau Data URI)</label>
            <textarea
              id="input-image"
              name="input_text"
              placeholder="Tempel string Base64 gambar atau Data URI...&#10;Contoh: data:image/png;base64,iVBORw0KGgo..."
              oninput="previewImageJS()"
              style="min-height:100px; font-family:var(--font-mono); font-size:.78rem;"
            ><?= ($post_mode === 'image') ? e($post_input) : '' ?></textarea>
          </div>

          <div class="form-group">
            <label>Preview gambar</label>
            <div class="image-preview-wrap" id="img-preview-wrap">
              <?php if ($server_is_image && $post_mode === 'image'): ?>
                <img src="<?= e($server_result) ?>" alt="Preview gambar dari Base64" />
              <?php else: ?>
                <div class="image-preview-empty">
                  Tempel string Base64 gambar untuk melihat preview
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label>Upload gambar → encode ke Base64</label>
            <div class="file-drop" id="img-drop-zone" style="padding:1.25rem;">
              <input type="file" id="img-upload" name="upload_file"
                accept="image/*"
                onchange="encodeImageJS(this)" />
              <div class="file-drop-label">Klik atau seret gambar untuk di-encode</div>
              <div class="file-drop-hint">PNG, JPG, GIF, WebP, SVG · Maks. 5 MB</div>
            </div>
          </div>

          <div class="form-group" id="img-output-wrap" style="display:none;">
            <label>Data URI hasil encode</label>
            <div class="copy-wrap">
              <div class="output-area" id="output-image" style="max-height:80px; overflow-y:auto;">—</div>
              <button class="copy-btn" type="button" onclick="copyOutput('output-image', this)">SALIN</button>
            </div>
            <div class="mini-stats" id="img-stats">
              <span class="ms-item">Ukuran: <span class="ms-val" id="img-size">—</span></span>
              <span class="ms-item">Tipe: <span class="ms-val" id="img-mime">—</span></span>
              <span class="ms-item">Panjang Base64: <span class="ms-val" id="img-b64len">—</span></span>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-secondary btn-sm" name="mode" value="image">⚙ Decode via Server (PHP)</button>
            <button type="button" class="btn-ghost btn-sm"
              onclick="document.getElementById('input-image').value=''; previewImageJS(); document.getElementById('img-output-wrap').style.display='none';">
              Bersihkan
            </button>
          </div>
        </div>

      </form><!-- /#b64-form -->
    </div><!-- /.panel -->

    <!-- Hasil server -->
    <?php if ($server_result && !$server_is_image): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>Berhasil <?= $post_action === 'encode' ? 'encode' : 'decode' ?> via PHP server.</span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div class="panel-title">⚙ Hasil Server PHP</div>
        <div class="form-group">
          <label>Input</label>
          <div class="result-box"
            style="word-break:break-all; font-family:var(--font-mono); font-size:.82rem;">
            <?= e(mb_substr($post_input, 0, 200)) ?><?= mb_strlen($post_input) > 200 ? '…' : '' ?>
          </div>
        </div>
        <div class="form-group">
          <label>Hasil <?= $post_action === 'encode' ? 'Encode' : 'Decode' ?></label>
          <div class="copy-wrap">
            <div class="result-box success" id="server-out"
              style="word-break:break-all; font-family:var(--font-mono); font-size:.82rem; max-height:160px; overflow-y:auto;">
              <?= e($server_result) ?>
            </div>
            <button class="copy-btn" data-copy-target="server-out">SALIN</button>
          </div>
        </div>
      </div>
    <?php elseif ($server_is_image): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>File berhasil diencode. MIME type: <strong><?= e($server_mime) ?></strong>.</span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div class="panel-title">⚙ Hasil Server PHP — Data URI</div>
        <?php if (str_starts_with($server_mime, 'image/')): ?>
        <div class="form-group">
          <label>Preview</label>
          <div class="image-preview-wrap">
            <img src="<?= e($server_result) ?>" alt="Preview gambar" />
          </div>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>Data URI</label>
          <div class="copy-wrap">
            <div class="result-box success" id="server-img-out"
              style="word-break:break-all; font-family:var(--font-mono); font-size:.78rem; max-height:100px; overflow-y:auto;">
              <?= e(substr($server_result, 0, 300)) ?>…
            </div>
            <button class="copy-btn" onclick="copyText(<?= htmlspecialchars(json_encode($server_result), ENT_QUOTES) ?>, this)">SALIN</button>
          </div>
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
      <div class="panel-title">💡 Tentang Base64</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        Base64 mengkonversi data biner menjadi karakter ASCII yang aman untuk dikirim melalui teks (email, URL, JSON, XML).
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Setiap 3 byte → <strong>4 karakter</strong> Base64</li>
        <li>Output <strong>~33% lebih besar</strong> dari input</li>
        <li>Karakter yang digunakan: <code>A-Z a-z 0-9 + / =</code></li>
        <li>URL-safe: <code>+</code>→<code>-</code>, <code>/</code>→<code>_</code></li>
        <li><strong>Bukan enkripsi</strong> — mudah di-decode</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Contoh Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $samples = [
          ['label' => 'Encode "Hello, World!"',  'text' => 'Hello, World!', 'action' => 'encode'],
          ['label' => 'Decode "SGVsbG8="',        'text' => 'SGVsbG8=',      'action' => 'decode'],
          ['label' => 'Encode JSON sederhana',    'text' => '{"user":"admin","role":"superuser"}', 'action' => 'encode'],
          ['label' => 'Encode URL panjang',       'text' => 'https://example.com/path?key=hello world&lang=id', 'action' => 'encode'],
        ];
        foreach ($samples as $s): ?>
          <button
            class="btn-ghost btn-sm btn-full"
            type="button"
            onclick="loadExample(<?= htmlspecialchars(json_encode($s['text']), ENT_QUOTES) ?>, '<?= $s['action'] ?>')"
            style="text-align:left; white-space:normal; height:auto; padding:.45rem .9rem;">
            <?= e($s['label']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📖 Referensi Base64</div>
      <table class="ref-table">
        <thead><tr><th>Input</th><th>Base64</th></tr></thead>
        <tbody>
          <?php
          $refs = ['A' => 'QQ==', 'Hi' => 'SGk=', 'Man' => 'TWFu', '{"a":1}' => 'eyJhIjoxfQ=='];
          foreach ($refs as $in => $b64): ?>
          <tr>
            <td class="text-sm"><?= e($in) ?></td>
            <td class="mono"><?= e($b64) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/url-encode-decode"  class="btn-ghost btn-sm btn-full">URL Encode/Decode</a>
        <a href="/tools/md5-generator"      class="btn-ghost btn-sm btn-full">MD5 Generator</a>
        <a href="/tools/sha256-generator"   class="btn-ghost btn-sm btn-full">SHA256 Generator</a>
        <a href="/tools/jwt-decoder"        class="btn-ghost btn-sm btn-full">JWT Decoder</a>
        <a href="/tools/password-generator" class="btn-ghost btn-sm btn-full">Password Generator</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Base64 Encode / Decode — logika JS (realtime)
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

let currentAction = '<?= $post_action ?>';
let currentMode   = '<?= $post_mode ?>';

// ── Action toggle ─────────────────────────────────────────────
function setAction(action) {
  currentAction = action;
  document.getElementById('action-input').value = action;
  document.getElementById('btn-encode').classList.toggle('active', action === 'encode');
  document.getElementById('btn-decode').classList.toggle('active', action === 'decode');

  // Update label
  const isEncode = action === 'encode';
  document.getElementById('label-input-main').textContent  = isEncode ? 'Teks input' : 'String Base64';
  document.getElementById('label-output-main').textContent = isEncode ? 'Hasil encode' : 'Hasil decode';

  // Sembunyikan opsi line break saat decode
  document.getElementById('opt-lb-wrap').style.opacity = isEncode ? '1' : '.4';
  document.getElementById('opt-linebreak').disabled = !isEncode;

  processJS();
  processUrlJS();
}

// ── Mode tabs ─────────────────────────────────────────────────
function switchTab(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;

  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    const modes = ['text','url','file','image'];
    t.classList.toggle('active', modes[i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

// ── Encode / Decode teks ──────────────────────────────────────
function processJS() {
  const input   = document.getElementById('input-text-main').value;
  const urlSafe = document.getElementById('opt-urlsafe').checked;
  const lb      = document.getElementById('opt-linebreak').checked;
  const outEl   = document.getElementById('output-main');
  const statsEl = document.getElementById('mini-stats-main');
  const ccEl    = document.getElementById('char-count-main');

  ccEl.textContent = input.length + ' karakter';

  if (!input) {
    outEl.textContent = '—';
    outEl.className   = 'output-area';
    statsEl.style.display = 'none';
    return;
  }

  let result = '';
  let isError = false;

  if (currentAction === 'encode') {
    try {
      result = btoa(unescape(encodeURIComponent(input)));
      if (urlSafe)   result = result.replace(/\+/g, '-').replace(/\//g, '_');
      if (lb)        result = result.match(/.{1,76}/g).join('\n');
    } catch {
      result  = 'Gagal encode — teks mengandung karakter yang tidak didukung.';
      isError = true;
    }
  } else {
    try {
      const clean = input.trim().replace(/\s/g, '').replace(/-/g, '+').replace(/_/g, '/');
      const pad   = clean.length % 4;
      const padded = pad ? clean + '='.repeat(4 - pad) : clean;
      result = decodeURIComponent(escape(atob(padded)));
    } catch {
      result  = 'Input bukan Base64 yang valid.';
      isError = true;
    }
  }

  outEl.textContent = result;
  outEl.className   = 'output-area' + (isError ? ' error' : (currentAction === 'decode' ? ' decoded' : ''));

  if (!isError) {
    statsEl.style.display = 'flex';
    document.getElementById('ms-in-len').textContent  = input.length.toLocaleString('id');
    document.getElementById('ms-out-len').textContent = result.length.toLocaleString('id');
    const ratio = input.length > 0 ? (result.length / input.length).toFixed(2) : '—';
    document.getElementById('ms-ratio').textContent   = ratio + 'x';
  } else {
    statsEl.style.display = 'none';
  }
}

// ── Encode / Decode URL ───────────────────────────────────────
function processUrlJS() {
  const input  = document.getElementById('input-url').value;
  const outEl  = document.getElementById('output-url');

  if (!input) { outEl.textContent = '—'; outEl.className = 'output-area'; return; }

  let result  = '';
  let isError = false;

  if (currentAction === 'encode') {
    try {
      // URL-safe Base64, tanpa padding
      result = btoa(unescape(encodeURIComponent(input)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    } catch { result = 'Gagal encode.'; isError = true; }
  } else {
    try {
      const clean = input.trim().replace(/\s/g,'').replace(/-/g,'+').replace(/_/g,'/');
      const pad   = clean.length % 4;
      result = decodeURIComponent(escape(atob(pad ? clean + '='.repeat(4 - pad) : clean)));
    } catch { result = 'Input bukan Base64 URL-safe yang valid.'; isError = true; }
  }

  outEl.textContent = result;
  outEl.className   = 'output-area' + (isError ? ' error' : (currentAction === 'decode' ? ' decoded' : ''));
}

// ── Swap teks ─────────────────────────────────────────────────
function swapTexts() {
  const inp = document.getElementById('input-text-main');
  const out = document.getElementById('output-main');
  if (out.textContent === '—' || out.classList.contains('error')) return;
  inp.value = out.textContent;
  setAction(currentAction === 'encode' ? 'decode' : 'encode');
}

function swapUrl() {
  const inp = document.getElementById('input-url');
  const out = document.getElementById('output-url');
  if (out.textContent === '—' || out.classList.contains('error')) return;
  inp.value = out.textContent;
  setAction(currentAction === 'encode' ? 'decode' : 'encode');
}

// ── Image preview dari Base64 ─────────────────────────────────
function previewImageJS() {
  const raw   = document.getElementById('input-image').value.trim();
  const wrap  = document.getElementById('img-preview-wrap');

  if (!raw) {
    wrap.innerHTML = '<div class="image-preview-empty">Tempel string Base64 gambar untuk melihat preview</div>';
    return;
  }

  let src = raw;
  if (!raw.startsWith('data:')) {
    // Tambah data URI prefix jika belum ada
    const clean = raw.replace(/\s/g,'').replace(/-/g,'+').replace(/_/g,'/');
    src = 'data:image/png;base64,' + clean;
  }

  const img = document.createElement('img');
  img.alt = 'Preview gambar dari Base64';
  img.onload = () => { wrap.innerHTML = ''; wrap.appendChild(img); };
  img.onerror = () => {
    wrap.innerHTML = '<div class="image-preview-empty" style="color:#dc2626;">String Base64 tidak valid atau bukan gambar.</div>';
  };
  img.src = src;
}

// ── Encode gambar → Base64 dari file input ────────────────────
function encodeImageJS(input) {
  const file = input.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    showToast('Ukuran file maksimal 5 MB.', 'warning');
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    const dataUri = e.target.result;
    const b64     = dataUri.split(',')[1];

    // Tampilkan preview
    document.getElementById('input-image').value = dataUri;
    const wrap = document.getElementById('img-preview-wrap');
    wrap.innerHTML = `<img src="${dataUri}" alt="Preview gambar" />`;

    // Tampilkan output
    const outWrap = document.getElementById('img-output-wrap');
    outWrap.style.display = 'block';
    document.getElementById('output-image').textContent = dataUri;
    document.getElementById('img-size').textContent    = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('img-mime').textContent    = file.type;
    document.getElementById('img-b64len').textContent  = b64.length.toLocaleString('id') + ' karakter';
  };
  reader.readAsDataURL(file);
}

// ── File upload handler ───────────────────────────────────────
function handleFileSelect(input) {
  const disp = document.getElementById('file-name-display');
  if (input.files[0]) {
    disp.style.display = 'block';
    disp.textContent   = '📄 ' + input.files[0].name
      + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
  }
}

// ── Copy output ───────────────────────────────────────────────
function copyOutput(elId, btn) {
  const el   = document.getElementById(elId);
  const text = el?.textContent?.trim();
  if (!text || text === '—') return;
  copyText(text, btn);
}

function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) return;
    const orig = btn.textContent;
    btn.textContent = '✓ TERSALIN';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}

// ── Utilitas ──────────────────────────────────────────────────
function clearAll() {
  document.getElementById('input-text-main').value  = '';
  document.getElementById('output-main').textContent = '—';
  document.getElementById('output-main').className  = 'output-area';
  document.getElementById('mini-stats-main').style.display = 'none';
  document.getElementById('char-count-main').textContent = '0 karakter';
}

function clearFile() {
  document.getElementById('upload-file').value = '';
  document.getElementById('file-name-display').style.display = 'none';
}

function loadSample() {
  switchTab('text');
  document.getElementById('input-text-main').value = 'Halo Dunia! Hello World! 123 #@&';
  setAction('encode');
  processJS();
}

function loadExample(text, action) {
  switchTab('text');
  document.getElementById('input-text-main').value = text;
  setAction(action);
}

// ── Drag-and-drop file ────────────────────────────────────────
function setupDrop(zoneId, inputId, handler) {
  const zone = document.getElementById(zoneId);
  if (!zone) return;
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag-over');
    const inp = document.getElementById(inputId);
    if (e.dataTransfer.files[0]) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      inp.files = dt.files;
      handler(inp);
    }
  });
}
setupDrop('file-drop-zone', 'upload-file', handleFileSelect);
setupDrop('img-drop-zone',  'img-upload',  encodeImageJS);

// ── Init ──────────────────────────────────────────────────────
setAction(currentAction);
switchTab(currentMode);
</script>

<?php require '../../includes/footer.php'; ?>