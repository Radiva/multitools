<?php
require '../../includes/config.php';
/**
 * Multi Tools — Remove Duplicates
 * Hapus baris duplikat dari teks: case-sensitive/insensitive,
 * trim spasi, urutkan hasil, dan hitung statistik.
 * ============================================================ */

// ── Handle POST (proses sisi server) ────────────────────────
$server_result  = '';
$server_error   = '';
$server_stats   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw        = $_POST['input_text'] ?? '';
  $sensitive  = isset($_POST['case_sensitive']);
  $trim_lines = isset($_POST['trim_lines']);
  $remove_empty = isset($_POST['remove_empty']);
  $sort_result  = isset($_POST['sort_result']);
  $sort_dir     = ($_POST['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

  if ($raw === '') {
    $server_error = 'Teks input tidak boleh kosong.';
  } else {
    // Pecah menjadi baris
    $lines = explode("\n", str_replace("\r\n", "\n", $raw));

    // Trim tiap baris jika dipilih
    if ($trim_lines) {
      $lines = array_map('trim', $lines);
    }

    // Hapus baris kosong jika dipilih
    if ($remove_empty) {
      $lines = array_filter($lines, fn($l) => $l !== '');
    }

    $total_before = count($lines);

    // Hapus duplikat
    if ($sensitive) {
      $unique = array_unique($lines);
    } else {
      // Case-insensitive: simpan versi asli, bandingkan versi lowercase
      $seen   = [];
      $unique = [];
      foreach ($lines as $line) {
        $key = mb_strtolower($line);
        if (!isset($seen[$key])) {
          $seen[$key] = true;
          $unique[]   = $line;
        }
      }
    }

    $total_after    = count($unique);
    $total_removed  = $total_before - $total_after;

    // Urutkan hasil jika dipilih
    if ($sort_result) {
      if ($sort_dir === 'desc') {
        rsort($unique);
      } else {
        sort($unique);
      }
    }

    $server_result = implode("\n", array_values($unique));
    $server_stats  = [
      'before'  => $total_before,
      'after'   => $total_after,
      'removed' => $total_removed,
    ];
  }
}

// ── Breadcrumb & SEO ─────────────────────────────────────────
$seo = [
  'title'       => 'Remove Duplicates Online — Hapus Baris Duplikat | Multi Tools',
  'description' => 'Hapus baris duplikat dari teks secara instan. Mendukung case-sensitive, trim spasi, hapus baris kosong, dan pengurutan hasil. Gratis, tanpa login.',
  'keywords'    => 'remove duplicates, hapus duplikat, remove duplicate lines, unique lines, baris unik, teks duplikat, multi tools',
  'og_title'    => 'Remove Duplicates Online — Hapus Baris Duplikat Instan',
  'og_desc'     => 'Hapus baris duplikat dari teks secara instan. Case-sensitive, trim, sort, dan statistik perubahan.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Text Tools', 'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Remove Duplicates'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/remove-duplicates#webpage',
      'url'         => SITE_URL . '/tools/remove-duplicates',
      'name'        => 'Remove Duplicates Online',
      'description' => 'Hapus baris duplikat dari teks secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',            'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',          'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Remove Duplicates',  'item' => SITE_URL . '/tools/remove-duplicates'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Remove Duplicates',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/remove-duplicates',
    ],
  ],
];

require '../../includes/header.php';
?>

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
        <span aria-hidden="true">🗑️</span> Remove <span>Duplicates</span>
      </div>
      <p class="page-lead">
        Hapus baris duplikat dari teks secara instan — satu baris per entri.
        Mendukung pengurutan, trim spasi, dan mode case-sensitive.
      </p>

      <form method="POST" action="" id="dup-form" style="margin-top:1.5rem;" novalidate>

        <!-- Input -->
        <div class="form-group">
          <label for="input-text">Teks input <span class="text-muted text-sm">(satu entri per baris)</span></label>
          <textarea
            id="input-text"
            name="input_text"
            placeholder="Tempel teks di sini, satu baris per entri...&#10;&#10;Contoh:&#10;apel&#10;mangga&#10;apel&#10;jeruk&#10;mangga"
            oninput="processJS()"
            aria-describedby="result-stats"
            style="min-height:200px;"
          ><?= isset($_POST['input_text']) ? e($_POST['input_text']) : '' ?></textarea>
        </div>

        <!-- Opsi -->
        <div class="form-group">
          <label>Opsi pemrosesan</label>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:.6rem .75rem; margin-top:.25rem;">

            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="case_sensitive" name="case_sensitive"
                <?= isset($_POST['case_sensitive']) ? 'checked' : '' ?>
                onchange="processJS()"
                style="width:auto; accent-color:var(--accent);" />
              Case-sensitive
            </label>

            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="trim_lines" name="trim_lines"
                <?= isset($_POST['trim_lines']) ? 'checked' : 'checked' ?>
                onchange="processJS()"
                style="width:auto; accent-color:var(--accent);" />
              Trim spasi awal &amp; akhir
            </label>

            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="remove_empty" name="remove_empty"
                <?= isset($_POST['remove_empty']) ? 'checked' : 'checked' ?>
                onchange="processJS()"
                style="width:auto; accent-color:var(--accent);" />
              Hapus baris kosong
            </label>

            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="sort_result" name="sort_result"
                <?= isset($_POST['sort_result']) ? 'checked' : '' ?>
                onchange="toggleSortDir(); processJS();"
                style="width:auto; accent-color:var(--accent);" />
              Urutkan hasil
            </label>

          </div>

          <!-- Arah sort — muncul jika sort aktif -->
          <div id="sort-dir-wrap" style="margin-top:.75rem; display:<?= isset($_POST['sort_result']) ? 'flex' : 'none' ?>; align-items:center; gap:.75rem;">
            <span class="text-sm text-muted">Arah urutan:</span>
            <label style="display:flex; align-items:center; gap:.35rem; font-weight:400; cursor:pointer;">
              <input type="radio" name="sort_dir" value="asc"
                <?= ($_POST['sort_dir'] ?? 'asc') === 'asc' ? 'checked' : '' ?>
                onchange="processJS()"
                style="width:auto; accent-color:var(--accent);" />
              <span class="text-sm">A → Z</span>
            </label>
            <label style="display:flex; align-items:center; gap:.35rem; font-weight:400; cursor:pointer;">
              <input type="radio" name="sort_dir" value="desc"
                <?= ($_POST['sort_dir'] ?? '') === 'desc' ? 'checked' : '' ?>
                onchange="processJS()"
                style="width:auto; accent-color:var(--accent);" />
              <span class="text-sm">Z → A</span>
            </label>
          </div>
        </div>

        <!-- Statistik -->
        <div id="result-stats" class="stats" role="region" aria-live="polite" aria-label="Statistik hasil">
          <div class="stat">
            <span class="stat-value" id="stat-before"><?= isset($server_stats['before']) ? (int)$server_stats['before'] : '0' ?></span>
            <span class="stat-label">Baris awal</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-after"><?= isset($server_stats['after']) ? (int)$server_stats['after'] : '0' ?></span>
            <span class="stat-label">Baris unik</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-removed"><?= isset($server_stats['removed']) ? (int)$server_stats['removed'] : '0' ?></span>
            <span class="stat-label">Duplikat dihapus</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-pct">0%</span>
            <span class="stat-label">Pengurangan</span>
          </div>
        </div>

        <!-- Output -->
        <div class="form-group" style="margin-top:1.25rem;">
          <label>Hasil (baris unik)</label>
          <div class="copy-wrap">
            <textarea
              id="output-text"
              readonly
              placeholder="Hasil akan muncul di sini..."
              aria-live="polite"
              aria-label="Hasil baris unik"
              style="min-height:200px;"
            ><?= $server_result ? e($server_result) : '' ?></textarea>
            <button
              class="copy-btn"
              type="button"
              id="copy-btn"
              data-copy-target="output-text"
              aria-label="Salin hasil">
              SALIN
            </button>
          </div>
        </div>

        <!-- Tombol aksi -->
        <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
          <button type="submit" class="btn-primary btn-sm">
            ⚙ Proses via Server (PHP)
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">
            Bersihkan
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="swapTexts()">
            ⇅ Gunakan hasil sebagai input
          </button>
        </div>

      </form><!-- /#dup-form -->
    </div><!-- /.panel -->

    <?php if ($server_result): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>
        Berhasil: <strong><?= (int)$server_stats['removed'] ?> duplikat</strong> dihapus.
        Tersisa <strong><?= (int)$server_stats['after'] ?> baris</strong> unik
        dari <?= (int)$server_stats['before'] ?> baris.
      </span>
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
      <div class="panel-title">💡 Tips</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        Setiap baris dianggap sebagai satu entri. Duplikat dihapus, entri pertama yang ditemukan dipertahankan.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><strong>Case-sensitive</strong> — "Apel" ≠ "apel"</li>
        <li><strong>Trim spasi</strong> — "  apel  " = "apel"</li>
        <li><strong>Hapus baris kosong</strong> — buang baris spasi</li>
        <li><strong>Urutkan</strong> — A→Z atau Z→A setelah dedup</li>
        <li>Gunakan <strong>⇅ Balik</strong> untuk proses bertahap</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📋 Contoh Kasus</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $examples = [
          ['label' => 'Daftar email',      'text' => "user@example.com\nadmin@site.com\nuser@example.com\ninfo@site.com\nadmin@site.com"],
          ['label' => 'Daftar kata kunci', 'text' => "SEO\nmarketing\nSEO\nkonten\nmarketing\noptimasi"],
          ['label' => 'Daftar nama',       'text' => "Budi\nSiti\nBudi\nAgus\nsiti\nAgus"],
          ['label' => 'Daftar URL',        'text' => "https://example.com/\nhttps://site.com/\nhttps://example.com/\nhttps://blog.com/"],
        ];
        foreach ($examples as $ex): ?>
          <button
            class="btn-ghost btn-sm btn-full"
            type="button"
            onclick="loadExample(<?= htmlspecialchars(json_encode($ex['text']), ENT_QUOTES) ?>)"
            aria-label="Muat contoh: <?= e($ex['label']) ?>">
            <?= e($ex['label']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/word-counter"    class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/case-converter"  class="btn-ghost btn-sm btn-full">Case Converter</a>
        <a href="/tools/sort-lines"      class="btn-ghost btn-sm btn-full">Sort Lines</a>
        <a href="/tools/text-cleaner"    class="btn-ghost btn-sm btn-full">Text Cleaner</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Remove Duplicates — logika JS (realtime)
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

function getOpts() {
  return {
    caseSensitive : document.getElementById('case_sensitive').checked,
    trimLines     : document.getElementById('trim_lines').checked,
    removeEmpty   : document.getElementById('remove_empty').checked,
    sortResult    : document.getElementById('sort_result').checked,
    sortDir       : document.querySelector('input[name="sort_dir"]:checked')?.value ?? 'asc',
  };
}

function processJS() {
  const raw  = document.getElementById('input-text').value;
  const opts = getOpts();

  if (!raw.trim()) {
    document.getElementById('output-text').value = '';
    updateStats(0, 0);
    return;
  }

  // Pecah baris
  let lines = raw.split('\n');

  // Trim
  if (opts.trimLines) lines = lines.map(l => l.trim());

  // Hapus baris kosong
  if (opts.removeEmpty) lines = lines.filter(l => l !== '');

  const totalBefore = lines.length;

  // Hapus duplikat
  const seen   = new Set();
  const unique = [];
  for (const line of lines) {
    const key = opts.caseSensitive ? line : line.toLowerCase();
    if (!seen.has(key)) {
      seen.add(key);
      unique.push(line);
    }
  }

  // Urutkan
  if (opts.sortResult) {
    unique.sort((a, b) => {
      const cmp = a.localeCompare(b, 'id', { sensitivity: 'base' });
      return opts.sortDir === 'desc' ? -cmp : cmp;
    });
  }

  document.getElementById('output-text').value = unique.join('\n');
  updateStats(totalBefore, unique.length);
}

function updateStats(before, after) {
  const removed = Math.max(0, before - after);
  const pct     = before > 0 ? Math.round((removed / before) * 100) : 0;

  document.getElementById('stat-before').textContent  = before.toLocaleString('id');
  document.getElementById('stat-after').textContent   = after.toLocaleString('id');
  document.getElementById('stat-removed').textContent = removed.toLocaleString('id');
  document.getElementById('stat-pct').textContent     = pct + '%';
}

function toggleSortDir() {
  const wrap = document.getElementById('sort-dir-wrap');
  wrap.style.display = document.getElementById('sort_result').checked ? 'flex' : 'none';
}

function clearAll() {
  document.getElementById('input-text').value  = '';
  document.getElementById('output-text').value = '';
  updateStats(0, 0);
}

function swapTexts() {
  const out = document.getElementById('output-text').value;
  if (!out) return;
  document.getElementById('input-text').value = out;
  processJS();
}

function loadExample(text) {
  document.getElementById('input-text').value = text;
  processJS();
}

// Jalankan saat halaman load (jika belum ada hasil server)
<?php if (!$server_result): ?>
processJS();
<?php else: ?>
// Perbarui statistik dari hasil server
updateStats(
  <?= (int)($server_stats['before']  ?? 0) ?>,
  <?= (int)($server_stats['after']   ?? 0) ?>
);
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>