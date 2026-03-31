<?php
require '../../includes/config.php';
/**
 * Multi Tools — Bcrypt Generator & Verifier
 * Hash password menggunakan bcrypt (PASSWORD_BCRYPT) yang aman.
 * Mendukung cost factor, verifikasi hash, dan generate massal.
 * ============================================================ */

// ── Handle POST ──────────────────────────────────────────────
$server_result  = '';
$server_error   = '';
$post_input     = '';
$post_mode      = 'hash';   // hash | verify | bulk
$post_cost      = 12;
$post_verify_hash = '';
$verify_result  = null;
$server_results = [];
$server_timing  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_mode  = in_array($_POST['mode'] ?? 'hash', ['hash','verify','bulk'])
                  ? $_POST['mode'] : 'hash';
  $post_cost  = max(4, min(16, (int)($_POST['cost'] ?? 12)));
  $post_input = $_POST['input_text'] ?? '';
  $post_verify_hash = trim($_POST['verify_hash'] ?? '');

  switch ($post_mode) {

    // ── Generate hash ──
    case 'hash':
      if (trim($post_input) === '') {
        $server_error = 'Password tidak boleh kosong.';
        break;
      }
      if (strlen($post_input) > 72) {
        $server_error = 'Bcrypt hanya memproses 72 karakter pertama. Password terlalu panjang.';
        break;
      }
      $t0 = microtime(true);
      $hash = password_hash($post_input, PASSWORD_BCRYPT, ['cost' => $post_cost]);
      $server_timing = round((microtime(true) - $t0) * 1000, 1);
      $server_result = $hash;
      break;

    // ── Verifikasi ──
    case 'verify':
      if (trim($post_input) === '') {
        $server_error = 'Password tidak boleh kosong.';
        break;
      }
      if (!preg_match('/^\$2[ayb]?\$\d{2}\$.{53}$/', $post_verify_hash)) {
        $server_error = 'Hash bcrypt tidak valid. Format: $2y$XX$...';
        break;
      }
      $t0 = microtime(true);
      $verify_result = password_verify($post_input, $post_verify_hash);
      $server_timing = round((microtime(true) - $t0) * 1000, 1);

      // Cek apakah perlu rehash (cost berubah)
      $needs_rehash = password_needs_rehash($post_verify_hash, PASSWORD_BCRYPT, ['cost' => $post_cost]);
      $server_result = $post_verify_hash;
      break;

    // ── Massal ──
    case 'bulk':
      if (trim($post_input) === '') {
        $server_error = 'Input tidak boleh kosong.';
        break;
      }
      // Batasi cost saat bulk agar tidak timeout
      $bulk_cost = min($post_cost, 10);
      $lines = array_filter(
        explode("\n", str_replace("\r\n", "\n", $post_input)),
        fn($l) => trim($l) !== ''
      );
      if (count($lines) > 20) {
        $server_error = 'Maksimal 20 password per sekali generate (bcrypt lambat by design).';
        break;
      }
      $t0 = microtime(true);
      foreach ($lines as $line) {
        $pw = trim($line);
        if (strlen($pw) > 72) {
          $server_results[] = ['input' => $pw, 'hash' => '[ERROR: >72 karakter]', 'ok' => false];
        } else {
          $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => $bulk_cost]);
          $server_results[] = ['input' => $pw, 'hash' => $hash, 'ok' => true];
        }
      }
      $server_timing = round((microtime(true) - $t0) * 1000, 1);
      break;
  }
}

// ── Info cost factor ─────────────────────────────────────────
// Estimasi waktu berdasarkan cost (berlipat 2 tiap +1)
function estimateCostTime(int $cost): string {
  // Baseline: cost=10 ≈ ~100ms di server modern
  $ms = 100 * pow(2, $cost - 10);
  if ($ms < 1000) return round($ms) . ' ms';
  if ($ms < 60000) return round($ms / 1000, 1) . ' detik';
  return round($ms / 60000, 1) . ' menit';
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Bcrypt Generator Online — Hash Password Aman | Multi Tools',
  'description' => 'Generate dan verifikasi hash bcrypt untuk password secara aman. Pilih cost factor 4-16, verifikasi hash, dan generate massal. Menggunakan password_hash() PHP.',
  'keywords'    => 'bcrypt generator, bcrypt hash, hash password, password_hash php, bcrypt online, verifikasi bcrypt, bcrypt cost factor, multi tools',
  'og_title'    => 'Bcrypt Generator Online — Hash Password Aman',
  'og_desc'     => 'Generate dan verifikasi bcrypt hash. Cost factor 4-16, bulk mode, timing info.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Enkripsi & Hash', 'url' => SITE_URL . '/tools?cat=crypto'],
    ['name' => 'Bcrypt Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/bcrypt-generator#webpage',
      'url'         => SITE_URL . '/tools/bcrypt-generator',
      'name'        => 'Bcrypt Generator Online',
      'description' => 'Generate dan verifikasi hash bcrypt untuk password secara aman.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',           'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Enkripsi & Hash',   'item' => SITE_URL . '/tools?cat=crypto'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Bcrypt Generator',  'item' => SITE_URL . '/tools/bcrypt-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Bcrypt Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/bcrypt-generator',
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
.mode-tab.active      { background: var(--accent3); color: #fff; }

/* ── Cost slider ── */
.cost-slider-wrap {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: .5rem;
}
.cost-slider-wrap input[type="range"] {
  flex: 1;
  accent-color: var(--accent3);
  height: 4px;
}
.cost-badge {
  font-family: var(--font-mono);
  font-weight: 800;
  font-size: 1.4rem;
  color: var(--accent3);
  min-width: 32px;
  text-align: center;
  line-height: 1;
}
.cost-meta {
  display: flex;
  justify-content: space-between;
  font-family: var(--font-mono);
  font-size: .7rem;
  color: var(--muted);
  margin-top: .4rem;
}
.cost-warning {
  display: flex;
  align-items: center;
  gap: .4rem;
  font-size: .78rem;
  font-weight: 600;
  margin-top: .5rem;
  padding: .4rem .75rem;
  border-radius: var(--radius-sm);
  border: 1px solid;
  transition: all .2s;
}
.cost-warning.fast   { color: #dc2626; border-color: #fca5a5; background: #fef2f2; }
.cost-warning.ok     { color: #15803d; border-color: #86efac; background: #f0fdf4; }
.cost-warning.strong { color: #7c3aed; border-color: #c4b5fd; background: #f5f3ff; }
.cost-warning.slow   { color: #92400e; border-color: #fcd34d; background: #fffbeb; }

/* ── Hash output ── */
.hash-out {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .85rem 1rem;
  font-family: var(--font-mono);
  font-size: .82rem;
  color: var(--accent3);
  word-break: break-all;
  line-height: 1.65;
  letter-spacing: .01em;
  min-height: 54px;
}
.hash-out .hash-part-prefix { color: var(--muted); }
.hash-out .hash-part-cost   { color: var(--accent4); font-weight: 700; }
.hash-out .hash-part-salt   { color: var(--accent2); }
.hash-out .hash-part-hash   { color: var(--accent3); }

/* ── Anatomy legend ── */
.anatomy-wrap {
  margin-top: .75rem;
  padding: .65rem .9rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: .75rem;
}
.anatomy-row {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: .3rem;
}
.anatomy-row:last-child { margin-bottom: 0; }
.anatomy-dot {
  width: 10px; height: 10px;
  border-radius: 2px;
  flex-shrink: 0;
}

/* ── Verify result ── */
.verify-box {
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  padding: 1rem 1.1rem;
  border-radius: var(--radius-sm);
  border: 1px solid;
  margin-top: 1rem;
}
.verify-box.match    { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.verify-box.mismatch { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }
.verify-icon { font-size: 1.35rem; flex-shrink: 0; line-height: 1; }
.verify-info { flex: 1; }
.verify-info strong { display: block; font-size: .9rem; margin-bottom: .25rem; }
.verify-info .verify-detail { font-size: .78rem; font-family: var(--font-mono); opacity: .8; }

/* ── Timing badge ── */
.timing-badge {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  font-family: var(--font-mono);
  font-size: .72rem;
  font-weight: 700;
  padding: .25rem .65rem;
  border-radius: 99px;
  border: 1px solid var(--border);
  color: var(--muted);
  background: var(--surface);
}

/* ── Password strength ── */
.pw-strength-bar {
  height: 4px;
  background: var(--border);
  border-radius: 99px;
  margin-top: .4rem;
  overflow: hidden;
}
.pw-strength-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .3s, background .3s;
}
.pw-strength-label {
  font-family: var(--font-mono);
  font-size: .7rem;
  margin-top: .25rem;
  font-weight: 700;
}

/* ── Show/hide password ── */
.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 2.75rem; }
.pw-toggle {
  position: absolute;
  right: .6rem; top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--muted);
  font-size: .85rem;
  cursor: pointer;
  padding: .2rem .3rem;
  transition: color var(--transition);
  line-height: 1;
}
.pw-toggle:hover { color: var(--text); }

/* ── Bulk table ── */
.bulk-table-wrap {
  max-height: 360px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.bulk-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
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
  padding: .45rem .9rem;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.bulk-table tr:last-child td { border-bottom: none; }
.bulk-table tr:hover td { background: rgba(124,58,237,.04); }
.bulk-table .td-hash {
  font-family: var(--font-mono);
  font-size: .7rem;
  color: var(--accent3);
  word-break: break-all;
}
.bulk-table .td-input { color: var(--muted); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bulk-table .td-copy  { white-space: nowrap; text-align: right; }
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
.bulk-copy-btn:hover { background: var(--accent3); color: #fff; border-color: var(--accent3); }

/* ── PHP code snippet ── */
.code-block {
  background: #1e293b;
  border-radius: var(--radius-md);
  padding: 1.1rem 1.25rem;
  font-family: var(--font-mono);
  font-size: .8rem;
  line-height: 1.75;
  overflow-x: auto;
  color: #94a3b8;
}
.code-block .kw  { color: #c084fc; }
.code-block .fn  { color: #60a5fa; }
.code-block .str { color: #34d399; }
.code-block .cmt { color: #475569; font-style: italic; }
.code-block .var { color: #f8fafc; }
.code-block .num { color: #fb923c; }
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
        <span aria-hidden="true">🔐</span> Bcrypt <span>Generator</span>
      </div>
      <p class="page-lead">
        Hash password menggunakan bcrypt — algoritma hashing paling direkomendasikan untuk password.
        Generate, verifikasi, dan uji kekuatan cost factor secara instan.
      </p>

      <!-- Mode tabs -->
      <div class="mode-tabs" role="tablist">
        <?php
        $tabs = ['hash' => '🔐 Generate Hash', 'verify' => '✔ Verifikasi', 'bulk' => '📋 Massal'];
        foreach ($tabs as $val => $lbl): ?>
          <button type="button" role="tab"
            class="mode-tab <?= $post_mode === $val ? 'active' : '' ?>"
            onclick="switchTab('<?= $val ?>')">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="" id="bcrypt-form" novalidate>
        <input type="hidden" id="mode-input" name="mode" value="<?= e($post_mode) ?>" />

        <!-- ── Cost Factor (tampil di semua mode) ── -->
        <div class="form-group" id="cost-section">
          <label for="cost-slider">
            Cost Factor (work factor)
            <?php if ($server_timing > 0): ?>
              <span class="timing-badge" style="margin-left:.5rem;">
                ⏱ <?= $server_timing ?> ms (server)
              </span>
            <?php endif; ?>
          </label>
          <div class="cost-slider-wrap">
            <input type="range" id="cost-slider" name="cost"
              min="4" max="16" step="1"
              value="<?= $post_cost ?>"
              oninput="updateCost(this.value)" />
            <span class="cost-badge" id="cost-badge"><?= $post_cost ?></span>
          </div>
          <div class="cost-meta">
            <span>4 (sangat cepat)</span>
            <span>10 (rekomendasi min.)</span>
            <span>16 (sangat lambat)</span>
          </div>
          <div class="cost-warning" id="cost-warning"></div>
        </div>

        <!-- ══ PANEL: Hash ══ -->
        <div id="panel-hash" class="mode-panel" <?= $post_mode !== 'hash' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="pw-input">Password</label>
            <div class="pw-wrap">
              <input type="password" id="pw-input" name="input_text"
                placeholder="Masukkan password yang akan di-hash..."
                oninput="checkStrength(this.value)"
                value="<?= $post_mode === 'hash' ? e($post_input) : '' ?>"
                maxlength="72"
                autocomplete="off" />
              <button type="button" class="pw-toggle" onclick="togglePw('pw-input', this)"
                aria-label="Tampilkan/sembunyikan password">👁</button>
            </div>
            <!-- Password strength -->
            <div class="pw-strength-bar">
              <div class="pw-strength-fill" id="pw-strength-fill" style="width:0%;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span class="pw-strength-label text-muted" id="pw-strength-label">Masukkan password</span>
              <span class="text-xs text-muted" id="pw-char-count">0 / 72 karakter</span>
            </div>
          </div>

          <div class="form-group">
            <label>Hash Bcrypt</label>
            <div class="copy-wrap">
              <div class="hash-out" id="hash-output-display">
                <span class="text-muted" style="font-size:.85rem;">
                  Klik "Generate Hash" untuk menghasilkan hash bcrypt...
                </span>
              </div>
              <button class="copy-btn" type="button" id="copy-hash-btn"
                onclick="copyHashOut()">SALIN</button>
            </div>
            <!-- Anatomy legend -->
            <div class="anatomy-wrap" id="anatomy-wrap" style="display:none;">
              <div style="font-family:var(--font-mono); font-size:.68rem; color:var(--muted); margin-bottom:.5rem; text-transform:uppercase; letter-spacing:.06em;">Anatomi hash bcrypt</div>
              <div class="anatomy-row">
                <div class="anatomy-dot" style="background:#64748b;"></div>
                <span style="color:var(--muted);"><code>$2y$</code> — versi algoritma</span>
              </div>
              <div class="anatomy-row">
                <div class="anatomy-dot" style="background:#f59e0b;"></div>
                <span style="color:var(--muted);"><code id="anat-cost">$12$</code> — cost factor</span>
              </div>
              <div class="anatomy-row">
                <div class="anatomy-dot" style="background:#0ea5e9;"></div>
                <span style="color:var(--muted);">22 karakter — salt acak</span>
              </div>
              <div class="anatomy-row">
                <div class="anatomy-dot" style="background:#7c3aed;"></div>
                <span style="color:var(--muted);">31 karakter — hash password</span>
              </div>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
            <button type="submit" class="btn-primary btn-sm" style="background:var(--accent3); border-color:var(--accent3);">
              🔐 Generate Hash (PHP Server)
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearHash()">Bersihkan</button>
          </div>

          <div class="alert warning" style="margin-top:1.25rem;">
            <span>⚠</span>
            <div class="text-sm">
              Bcrypt membutuhkan <strong>server PHP</strong> untuk generate — tidak bisa dilakukan di browser (JavaScript).
              Klik tombol di atas untuk mengirim ke server.
            </div>
          </div>
        </div>

        <!-- ══ PANEL: Verifikasi ══ -->
        <div id="panel-verify" class="mode-panel" <?= $post_mode !== 'verify' ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label for="pw-verify-input">Password asli</label>
            <div class="pw-wrap">
              <input type="password" id="pw-verify-input" name="input_text"
                placeholder="Masukkan password yang ingin diverifikasi..."
                value="<?= $post_mode === 'verify' ? e($post_input) : '' ?>"
                maxlength="72"
                autocomplete="off" />
              <button type="button" class="pw-toggle" onclick="togglePw('pw-verify-input', this)"
                aria-label="Tampilkan/sembunyikan password">👁</button>
            </div>
          </div>

          <div class="form-group">
            <label for="verify-hash-inp">Hash bcrypt yang ingin dicocokkan</label>
            <input type="text" id="verify-hash-inp" name="verify_hash"
              placeholder="$2y$12$..."
              value="<?= e($post_verify_hash) ?>"
              style="font-family:var(--font-mono); font-size:.82rem; letter-spacing:.01em;" />
            <div style="font-family:var(--font-mono); font-size:.7rem; color:var(--muted); margin-top:.3rem;" id="hash-input-len">
              0 karakter — format: $2y$XX$[22 karakter salt][31 karakter hash]
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary btn-sm" style="background:var(--accent3); border-color:var(--accent3);">
              ✔ Verifikasi via Server (PHP)
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearVerify()">Bersihkan</button>
          </div>
        </div>

        <!-- ══ PANEL: Massal ══ -->
        <div id="panel-bulk" class="mode-panel" <?= $post_mode !== 'bulk' ? 'style="display:none;"' : '' ?>>
          <div class="alert warning" style="margin-bottom:1.25rem;">
            <span>⏱</span>
            <div class="text-sm">
              Bcrypt sengaja lambat — generate massal di server membutuhkan waktu lebih lama.
              Maksimal <strong>20 password</strong> per sekali generate.
              Cost factor otomatis dibatasi ke <strong>10</strong> untuk mode massal.
            </div>
          </div>

          <div class="form-group">
            <label for="bulk-input">
              Daftar password <span class="text-muted text-sm">(satu per baris, maks. 20)</span>
            </label>
            <textarea id="bulk-input" name="input_text"
              placeholder="password123&#10;admin2025&#10;secret_key&#10;MyP@ssw0rd!"
              style="min-height:160px; font-family:var(--font-mono);"
            ><?= $post_mode === 'bulk' ? e($post_input) : '' ?></textarea>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
            <button type="submit" class="btn-primary btn-sm" style="background:var(--accent3); border-color:var(--accent3);">
              🔐 Generate Semua Hash (PHP Server)
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearBulk()">Bersihkan</button>
          </div>
        </div>

      </form><!-- /#bcrypt-form -->
    </div><!-- /.panel -->

    <!-- ── Hasil server: hash tunggal ── -->
    <?php if ($server_result && $post_mode === 'hash'): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>
          Hash bcrypt berhasil dibuat dalam <strong><?= $server_timing ?> ms</strong>
          (cost=<?= $post_cost ?>).
        </span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div class="panel-title">⚙ Hasil Server PHP</div>
        <div class="form-group">
          <label>Password (input)</label>
          <div class="result-box">
            <?= str_repeat('●', min(strlen($post_input), 20)) ?>
            <span style="color:var(--muted); font-size:.75rem; margin-left:.5rem;">(<?= strlen($post_input) ?> karakter)</span>
          </div>
        </div>
        <div class="form-group">
          <label>Hash Bcrypt</label>
          <div class="copy-wrap">
            <div class="result-box success" id="server-hash-out"
              style="font-family:var(--font-mono); font-size:.82rem; word-break:break-all;">
              <?= e($server_result) ?>
            </div>
            <button class="copy-btn" data-copy-target="server-hash-out">SALIN</button>
          </div>
        </div>
        <div style="display:flex; gap:.75rem; flex-wrap:wrap; font-size:.8rem; color:var(--muted); margin-top:.5rem;">
          <span>📏 Panjang: <strong><?= strlen($server_result) ?> karakter</strong></span>
          <span>⚙ Cost: <strong><?= $post_cost ?></strong></span>
          <span>⏱ Waktu: <strong><?= $server_timing ?> ms</strong></span>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Hasil server: verifikasi ── -->
    <?php if ($post_mode === 'verify' && $verify_result !== null): ?>
      <div class="verify-box <?= $verify_result ? 'match' : 'mismatch' ?>" role="alert">
        <span class="verify-icon"><?= $verify_result ? '✅' : '❌' ?></span>
        <div class="verify-info">
          <strong>
            <?= $verify_result
              ? 'Password cocok! Hash terverifikasi.'
              : 'Password tidak cocok! Hash berbeda.' ?>
          </strong>
          <div class="verify-detail">
            Hash: <?= e(substr($post_verify_hash, 0, 29)) ?>...<br>
            Waktu verifikasi: <?= $server_timing ?> ms
            <?php if ($verify_result && isset($needs_rehash) && $needs_rehash): ?>
              <br><span style="color:#d97706;">⚠ Hash perlu di-rehash ke cost <?= $post_cost ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Hasil server: bulk ── -->
    <?php if ($post_mode === 'bulk' && !empty($server_results)): ?>
      <div class="alert success" style="margin-top:1rem;" role="alert">
        <span>✓</span>
        <span>
          Berhasil generate <strong><?= count($server_results) ?> hash</strong>
          dalam <strong><?= $server_timing ?> ms</strong>
          (cost=<?= min($post_cost, 10) ?>).
        </span>
      </div>
      <div class="panel" style="margin-top:1rem;">
        <div class="panel-title">⚙ Hasil Server PHP — Bulk</div>
        <div class="bulk-table-wrap">
          <table class="bulk-table">
            <thead>
              <tr><th>#</th><th>Password</th><th>Hash Bcrypt</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($server_results as $idx => $row): ?>
              <tr>
                <td class="text-muted text-xs"><?= $idx + 1 ?></td>
                <td class="td-input">
                  <?= str_repeat('●', min(strlen($row['input']), 12)) ?>
                  <span style="font-size:.65rem; color:var(--muted);">(<?= strlen($row['input']) ?>)</span>
                </td>
                <td class="td-hash <?= $row['ok'] ? '' : 'text-muted' ?>">
                  <?= e($row['hash']) ?>
                </td>
                <td class="td-copy">
                  <?php if ($row['ok']): ?>
                    <button class="bulk-copy-btn"
                      onclick="copyText(<?= htmlspecialchars(json_encode($row['hash']), ENT_QUOTES) ?>, this)">
                      SALIN
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.75rem; flex-wrap:wrap;">
          <button class="btn-ghost btn-sm"
            onclick="copyAllHashes()">📋 Salin semua hash</button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($server_error): ?>
      <div class="alert danger" style="margin-top:1rem;" role="alert">
        <span>✕</span>
        <span><?= e($server_error) ?></span>
      </div>
    <?php endif; ?>

    <!-- ── Kode PHP referensi ── -->
    <div class="panel" style="margin-top:1.5rem;">
      <div class="panel-title">📋 Implementasi PHP</div>
      <p class="text-sm text-muted" style="margin-bottom:1rem;">
        Salin kode berikut untuk mengimplementasikan bcrypt di proyek PHP kamu:
      </p>

      <div style="font-family:var(--font-mono); font-size:.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:.4rem;">
        Hash password baru
      </div>
      <div class="copy-wrap" style="margin-bottom:1.25rem;">
        <div class="code-block" id="code-hash"><span class="cmt">// Saat user daftar / ganti password</span>
<span class="var">$password</span> = <span class="var">$_POST</span>[<span class="str">'password'</span>];
<span class="var">$options</span>  = [<span class="str">'cost'</span> => <span class="num">12</span>]; <span class="cmt">// Sesuaikan dengan server</span>
<span class="var">$hash</span>     = <span class="fn">password_hash</span>(<span class="var">$password</span>, <span class="kw">PASSWORD_BCRYPT</span>, <span class="var">$options</span>);

<span class="cmt">// Simpan $hash ke database (bukan $password!)</span>
<span class="var">$pdo</span>-><span class="fn">prepare</span>(<span class="str">"UPDATE users SET password = ? WHERE id = ?"</span>)
    -><span class="fn">execute</span>([<span class="var">$hash</span>, <span class="var">$userId</span>]);</div>
        <button class="copy-btn" data-copy-target="code-hash">SALIN</button>
      </div>

      <div style="font-family:var(--font-mono); font-size:.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:.4rem;">
        Verifikasi saat login
      </div>
      <div class="copy-wrap" style="margin-bottom:1.25rem;">
        <div class="code-block" id="code-verify"><span class="cmt">// Saat user login</span>
<span class="var">$password</span>  = <span class="var">$_POST</span>[<span class="str">'password'</span>];
<span class="var">$hashFromDb</span> = <span class="var">$user</span>[<span class="str">'password'</span>]; <span class="cmt">// Dari database</span>

<span class="kw">if</span> (<span class="fn">password_verify</span>(<span class="var">$password</span>, <span class="var">$hashFromDb</span>)) {
    <span class="cmt">// Login berhasil — cek apakah perlu rehash</span>
    <span class="kw">if</span> (<span class="fn">password_needs_rehash</span>(<span class="var">$hashFromDb</span>, <span class="kw">PASSWORD_BCRYPT</span>, [<span class="str">'cost'</span> => <span class="num">12</span>])) {
        <span class="var">$newHash</span> = <span class="fn">password_hash</span>(<span class="var">$password</span>, <span class="kw">PASSWORD_BCRYPT</span>, [<span class="str">'cost'</span> => <span class="num">12</span>]);
        <span class="cmt">// Perbarui hash di database</span>
    }
    <span class="cmt">// Lanjutkan session...</span>
} <span class="kw">else</span> {
    <span class="cmt">// Password salah</span>
}</div>
        <button class="copy-btn" data-copy-target="code-verify">SALIN</button>
      </div>

      <div style="font-family:var(--font-mono); font-size:.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:.4rem;">
        Benchmark cost factor yang tepat
      </div>
      <div class="copy-wrap">
        <div class="code-block" id="code-bench"><span class="cmt">// Jalankan sekali di server produksi untuk menemukan cost optimal</span>
<span class="var">$targetMs</span> = <span class="num">250</span>; <span class="cmt">// Target: hash selesai dalam 250ms</span>
<span class="var">$cost</span>     = <span class="num">10</span>;

<span class="kw">do</span> {
    <span class="var">$cost</span>++;
    <span class="var">$start</span> = <span class="fn">microtime</span>(<span class="kw">true</span>);
    <span class="fn">password_hash</span>(<span class="str">'benchmark_test'</span>, <span class="kw">PASSWORD_BCRYPT</span>, [<span class="str">'cost'</span> => <span class="var">$cost</span>]);
    <span class="var">$elapsed</span> = (<span class="fn">microtime</span>(<span class="kw">true</span>) - <span class="var">$start</span>) * <span class="num">1000</span>;
} <span class="kw">while</span> (<span class="var">$elapsed</span> < <span class="var">$targetMs</span>);

<span class="fn">echo</span> <span class="str">"Cost optimal untuk server ini: {$cost}\n"</span>;
<span class="fn">echo</span> <span class="str">"Waktu hash: {$elapsed}ms\n"</span>;</div>
        <button class="copy-btn" data-copy-target="code-bench">SALIN</button>
      </div>
    </div>

  </div><!-- /konten utama -->

  <!-- Sidebar -->
  <aside>
    <div class="panel">
      <div class="panel-title">💡 Mengapa Bcrypt?</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        Bcrypt dirancang khusus untuk hashing password — sengaja lambat dan memiliki salt bawaan.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Salt acak <strong>built-in</strong> — tidak perlu generate manual</li>
        <li><strong>Adaptif</strong> — naikkan cost seiring perkembangan hardware</li>
        <li>Output selalu <strong>60 karakter</strong></li>
        <li>Aman terhadap <strong>rainbow table</strong> & brute force</li>
        <li>Standar de-facto untuk hashing password sejak 1999</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚙ Panduan Cost Factor</div>
      <div style="display:flex; flex-direction:column; gap:.4rem; font-size:.78rem;">
        <?php
        $costs = [
          [4,  'fast',   '#dc2626', 'Sangat cepat — hanya untuk testing'],
          [8,  'fast',   '#ea580c', 'Cepat — tidak direkomendasikan produksi'],
          [10, 'ok',     '#16a34a', 'Minimum produksi (~100ms)'],
          [12, 'ok',     '#15803d', 'Rekomendasi default (~400ms)'],
          [14, 'strong', '#7c3aed', 'Keamanan tinggi (~1.5 detik)'],
          [16, 'slow',   '#92400e', 'Maksimal — sangat lambat (~6 detik)'],
        ];
        foreach ($costs as [$c, $cls, $col, $desc]): ?>
          <div style="display:flex; gap:.5rem; align-items:flex-start; padding:.3rem 0; border-bottom:1px solid var(--border);">
            <span style="font-family:var(--font-mono); font-weight:700; color:<?= $col ?>; min-width:24px;"><?= $c ?></span>
            <span style="color:var(--muted);"><?= e($desc) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📊 Estimasi Waktu</div>
      <div style="display:flex; flex-direction:column; gap:.3rem; font-size:.78rem;">
        <?php for ($c = 10; $c <= 16; $c++): ?>
          <div style="display:flex; justify-content:space-between; align-items:center;
                      padding:.3rem 0; border-bottom:1px solid var(--border);">
            <span style="font-family:var(--font-mono); color:var(--muted);">cost=<?= $c ?></span>
            <span style="font-family:var(--font-mono); color:var(--accent3); font-weight:700;">
              <?= estimateCostTime($c) ?>
            </span>
          </div>
        <?php endfor; ?>
        <div class="text-xs text-muted" style="margin-top:.35rem;">
          * Estimasi pada server modern. Waktu aktual bervariasi — gunakan benchmark.
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚠ Batasan Bcrypt</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Maks. <strong>72 karakter</strong> password</li>
        <li><strong>Tidak bisa</strong> decrypt — one-way</li>
        <li>Untuk password di atas 72 karakter: pre-hash dengan SHA-256 dulu</li>
        <li>Gunakan <strong>Argon2</strong> untuk keamanan lebih tinggi (<code>PASSWORD_ARGON2ID</code>)</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/md5-generator"       class="btn-ghost btn-sm btn-full">MD5 Generator</a>
        <a href="/tools/sha256-generator"    class="btn-ghost btn-sm btn-full">SHA256 Generator</a>
        <a href="/tools/password-generator"  class="btn-ghost btn-sm btn-full">Password Generator</a>
        <a href="/tools/password-strength"   class="btn-ghost btn-sm btn-full">Password Strength Checker</a>
        <a href="/tools/jwt-decoder"         class="btn-ghost btn-sm btn-full">JWT Decoder</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Bcrypt Generator — logika UI JavaScript
   SEMUA hashing dilakukan oleh PHP server.
   JS hanya untuk UX: password strength,
   cost info, tab switching, copy.
   ────────────────────────────────────────── */

// ── Tab switching ─────────────────────────────────────────────
let currentMode = '<?= $post_mode ?>';

function switchTab(mode) {
  currentMode = mode;
  document.getElementById('mode-input').value = mode;

  document.querySelectorAll('.mode-tab').forEach((t, i) => {
    const modes = ['hash','verify','bulk'];
    t.classList.toggle('active', modes[i] === mode);
  });
  document.querySelectorAll('.mode-panel').forEach(p => {
    p.style.display = p.id === 'panel-' + mode ? '' : 'none';
  });
}

// ── Cost factor UI ────────────────────────────────────────────
const COST_INFO = {
  4:  { cls: 'fast',   icon: '🔴', msg: 'Terlalu cepat — tidak aman untuk produksi!' },
  5:  { cls: 'fast',   icon: '🔴', msg: 'Sangat cepat — hanya untuk development.' },
  6:  { cls: 'fast',   icon: '🟠', msg: 'Cepat — tidak direkomendasikan produksi.' },
  7:  { cls: 'fast',   icon: '🟠', msg: 'Cepat — pertimbangkan cost lebih tinggi.' },
  8:  { cls: 'fast',   icon: '🟡', msg: 'Di bawah rekomendasi minimum produksi.' },
  9:  { cls: 'fast',   icon: '🟡', msg: 'Mendekati minimum — pertimbangkan cost 10+.' },
  10: { cls: 'ok',     icon: '🟢', msg: 'Minimum produksi (~100ms). Cukup untuk server lambat.' },
  11: { cls: 'ok',     icon: '🟢', msg: 'Baik (~200ms). Keseimbangan keamanan & kecepatan.' },
  12: { cls: 'ok',     icon: '✅', msg: 'Rekomendasi default (~400ms). Pilihan terbaik untuk kebanyakan aplikasi.' },
  13: { cls: 'strong', icon: '🔵', msg: 'Kuat (~800ms). Cocok untuk data sensitif.' },
  14: { cls: 'strong', icon: '🟣', msg: 'Sangat kuat (~1.5 detik). Untuk sistem keamanan tinggi.' },
  15: { cls: 'slow',   icon: '⚠',  msg: 'Sangat lambat (~3 detik). Pastikan timeout server cukup.' },
  16: { cls: 'slow',   icon: '🔥', msg: 'Ekstrem (~6 detik). Hanya jika server sangat cepat.' },
};

function updateCost(val) {
  val = parseInt(val);
  document.getElementById('cost-badge').textContent = val;
  document.getElementById('anat-cost') && (document.getElementById('anat-cost').textContent = '$' + val + '$');

  const info = COST_INFO[val] || COST_INFO[12];
  const warn = document.getElementById('cost-warning');
  warn.className = 'cost-warning ' + info.cls;
  warn.innerHTML = `<span>${info.icon}</span><span>${info.msg}</span>`;
}

// ── Password strength ─────────────────────────────────────────
function checkStrength(pw) {
  const countEl = document.getElementById('pw-char-count');
  const fillEl  = document.getElementById('pw-strength-fill');
  const lblEl   = document.getElementById('pw-strength-label');

  countEl.textContent = pw.length + ' / 72 karakter';

  if (!pw) {
    fillEl.style.width = '0%';
    fillEl.style.background = 'var(--border)';
    lblEl.textContent = 'Masukkan password';
    lblEl.style.color = 'var(--muted)';
    return;
  }

  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (pw.length >= 16) score++;
  if (/[a-z]/.test(pw)) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^a-zA-Z0-9]/.test(pw)) score++;
  if (pw.length >= 20) score++;

  const levels = [
    { pct: '12%',  bg: '#ef4444', lbl: 'Sangat lemah', color: '#ef4444' },
    { pct: '25%',  bg: '#f97316', lbl: 'Lemah',         color: '#f97316' },
    { pct: '40%',  bg: '#eab308', lbl: 'Cukup',         color: '#eab308' },
    { pct: '55%',  bg: '#84cc16', lbl: 'Sedang',        color: '#84cc16' },
    { pct: '70%',  bg: '#22c55e', lbl: 'Kuat',          color: '#22c55e' },
    { pct: '85%',  bg: '#10b981', lbl: 'Sangat kuat',   color: '#10b981' },
    { pct: '100%', bg: '#6366f1', lbl: 'Luar biasa',    color: '#6366f1' },
  ];
  const lvl = levels[Math.min(Math.floor(score / 1.15), levels.length - 1)];
  fillEl.style.width = lvl.pct;
  fillEl.style.background = lvl.bg;
  lblEl.textContent = lvl.lbl;
  lblEl.style.color = lvl.color;
}

// ── Show/hide password ────────────────────────────────────────
function togglePw(inputId, btn) {
  const inp = document.getElementById(inputId);
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}

// ── Hash input verify len ────────────────────────────────────
const hashInp = document.getElementById('verify-hash-inp');
if (hashInp) {
  hashInp.addEventListener('input', function() {
    const len = this.value.trim().length;
    const el  = document.getElementById('hash-input-len');
    const isValid = /^\$2[ayb]?\$\d{2}\$.{53}$/.test(this.value.trim());
    el.textContent = len + ' karakter' + (isValid ? ' ✓ Format valid' : ' — format: $2y$XX$[22 salt][31 hash]');
    el.style.color = isValid ? '#16a34a' : 'var(--muted)';
  });
}

// ── Copy helpers ──────────────────────────────────────────────
function copyHashOut() {
  const el = document.getElementById('hash-output-display');
  const text = el?.textContent?.trim();
  if (!text || text.includes('Klik')) return;
  copyText(text, document.getElementById('copy-hash-btn'));
}

function copyAllHashes() {
  const rows = document.querySelectorAll('.bulk-table .td-hash');
  const hashes = Array.from(rows).map(td => td.textContent.trim()).filter(h => h.startsWith('$'));
  if (!hashes.length) return;
  copyText(hashes.join('\n'));
}

function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) return;
    const orig = btn.textContent;
    btn.textContent = '✓ TERSALIN';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}

// ── Clear helpers ─────────────────────────────────────────────
function clearHash() {
  const pw = document.getElementById('pw-input');
  if (pw) { pw.value = ''; checkStrength(''); }
  document.getElementById('hash-output-display').innerHTML =
    '<span class="text-muted" style="font-size:.85rem;">Klik "Generate Hash" untuk menghasilkan hash bcrypt...</span>';
  document.getElementById('anatomy-wrap') && (document.getElementById('anatomy-wrap').style.display = 'none');
}

function clearVerify() {
  const pw  = document.getElementById('pw-verify-input');
  const hi  = document.getElementById('verify-hash-inp');
  const len = document.getElementById('hash-input-len');
  if (pw)  pw.value = '';
  if (hi)  hi.value = '';
  if (len) { len.textContent = '0 karakter — format: $2y$XX$[22 karakter salt][31 karakter hash]'; len.style.color = 'var(--muted)'; }
}

function clearBulk() {
  const b = document.getElementById('bulk-input');
  if (b) b.value = '';
}

// ── Init ──────────────────────────────────────────────────────
updateCost(<?= $post_cost ?>);

// Jika ada hasil server, tampilkan anatomy
<?php if ($server_result && $post_mode === 'hash'): ?>
(function() {
  const hash = <?= json_encode($server_result) ?>;
  const disp = document.getElementById('hash-output-display');
  // Warnai bagian-bagian hash: $2y$ | $12$ | salt(22) | hash(31)
  const prefix = hash.slice(0, 4);       // $2y$
  const cost   = hash.slice(4, 7);       // 12$
  const salt   = hash.slice(7, 29);      // 22 karakter
  const hpart  = hash.slice(29);         // 31 karakter
  disp.innerHTML =
    `<span class="hash-part-prefix">${prefix}</span>` +
    `<span class="hash-part-cost">${cost}</span>` +
    `<span class="hash-part-salt">${salt}</span>` +
    `<span class="hash-part-hash">${hpart}</span>`;
  const aw = document.getElementById('anatomy-wrap');
  if (aw) { aw.style.display = 'block'; document.getElementById('anat-cost').textContent = '$' + <?= $post_cost ?> + '$'; }
})();
<?php endif; ?>

// Restore strength bar jika ada input tersimpan
const savedPw = document.getElementById('pw-input');
if (savedPw && savedPw.value) checkStrength(savedPw.value);
</script>

<?php require '../../includes/footer.php'; ?>