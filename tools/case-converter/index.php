<?php
require '../../includes/config.php';
/**
 * Multi Tools — Case Converter
 * Konversi format penulisan teks: UPPERCASE, lowercase, Title Case,
 * camelCase, snake_case, kebab-case, dan lainnya secara realtime.
 * ============================================================ */

// Breadcrumb & SEO untuk halaman ini
$seo = [
  'title'       => 'Case Converter Online — Ubah Format Teks Instan | Multi Tools',
  'description' => 'Konversi teks ke UPPERCASE, lowercase, Title Case, camelCase, snake_case, kebab-case, dan lainnya secara realtime. Gratis, tanpa login.',
  'keywords'    => 'case converter, uppercase, lowercase, title case, camelcase, snake case, kebab case, pascal case, ubah format teks, multi tools',
  'og_title'    => 'Case Converter Online — Ubah Format Teks Instan',
  'og_desc'     => 'Konversi teks ke berbagai format penulisan secara realtime. UPPERCASE, camelCase, snake_case, dan 9 format lainnya.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Text Tools', 'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Case Converter'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/case-converter#webpage',
      'url'         => SITE_URL . '/tools/case-converter',
      'name'        => 'Case Converter Online',
      'description' => 'Konversi teks ke UPPERCASE, lowercase, Title Case, camelCase, snake_case, dan lainnya.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',     'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Case Converter', 'item' => SITE_URL . '/tools/case-converter'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Case Converter',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/case-converter',
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
        <span aria-hidden="true">🔤</span> Case <span>Converter</span>
      </div>
      <p class="page-lead">
        Ubah format penulisan teks secara instan — dari UPPERCASE hingga camelCase,
        snake_case, kebab-case, dan banyak lagi.
      </p>

      <!-- Pilihan mode -->
      <div class="form-group" style="margin-top:1.5rem">
        <label for="case-mode">Pilih format teks</label>
        <select id="case-mode" onchange="convertCase()" aria-label="Pilih format konversi">
          <option value="upper">UPPERCASE — semua huruf kapital</option>
          <option value="lower">lowercase — semua huruf kecil</option>
          <option value="title">Title Case — huruf pertama tiap kata kapital</option>
          <option value="sentence">Sentence case — kapital hanya di awal kalimat</option>
          <option value="camel">camelCase — untuk variabel JavaScript / Java</option>
          <option value="pascal">PascalCase — untuk nama Class / komponen</option>
          <option value="snake">snake_case — untuk variabel PHP / Python</option>
          <option value="kebab">kebab-case — untuk class CSS / URL slug</option>
          <option value="const">CONST_CASE — untuk konstanta</option>
          <option value="dot">dot.case — untuk kunci konfigurasi</option>
          <option value="alternating">aLtErNaTiNg — bergantian besar-kecil</option>
          <option value="inverse">iNVERSE — balik kondisi huruf saat ini</option>
        </select>
      </div>

      <!-- Input -->
      <div class="form-group">
        <label for="input-text">Teks input</label>
        <textarea
          id="input-text"
          placeholder="Ketik atau tempel teks kamu di sini..."
          oninput="convertCase()"
          aria-describedby="output-label"
        ></textarea>
      </div>

      <!-- Output -->
      <div class="form-group">
        <label id="output-label">Hasil konversi</label>
        <div class="copy-wrap">
          <textarea
            id="output-text"
            readonly
            placeholder="Hasil akan muncul di sini..."
            aria-live="polite"
            aria-label="Hasil konversi"
            style="min-height:120px;"
          ></textarea>
          <button
            class="copy-btn"
            id="copy-btn"
            data-copy-target="output-text"
            aria-label="Salin hasil">
            SALIN
          </button>
        </div>
      </div>

      <!-- Tombol aksi -->
      <div style="margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap;">
        <button class="btn-ghost btn-sm" onclick="clearAll()">
          Bersihkan
        </button>
        <button class="btn-ghost btn-sm" onclick="swapTexts()">
          ⇅ Balik input &amp; output
        </button>
        <button class="btn-ghost btn-sm" onclick="pasteText()">
          📋 Tempel dari clipboard
        </button>
      </div>
    </div><!-- /.panel -->
  </div>

  <!-- Sidebar -->
  <aside>
    <div class="panel">
      <div class="panel-title">💡 Tips</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        Pilih format dari dropdown lalu ketik atau tempel teks — hasil langsung muncul otomatis.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><strong>camelCase</strong> — variabel JS, Java, Swift</li>
        <li><strong>PascalCase</strong> — nama Class, komponen React</li>
        <li><strong>snake_case</strong> — variabel PHP, Python, kolom DB</li>
        <li><strong>kebab-case</strong> — class CSS, URL slug</li>
        <li><strong>CONST_CASE</strong> — konstanta di semua bahasa</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📋 Referensi Format</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $formats = [
          'UPPERCASE'    => 'HALO DUNIA',
          'lowercase'    => 'halo dunia',
          'Title Case'   => 'Halo Dunia',
          'camelCase'    => 'haloDunia',
          'PascalCase'   => 'HaloDunia',
          'snake_case'   => 'halo_dunia',
          'kebab-case'   => 'halo-dunia',
          'CONST_CASE'   => 'HALO_DUNIA',
        ];
        foreach ($formats as $label => $contoh): ?>
          <div style="display:flex; justify-content:space-between; align-items:center;
                      padding:.35rem .1rem; border-bottom:1px solid var(--border);">
            <span class="text-sm text-muted"><?= e($label) ?></span>
            <code style="font-family:var(--font-mono); font-size:.75rem; color:var(--accent);">
              <?= e($contoh) ?>
            </code>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/word-counter"  class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/text-cleaner"  class="btn-ghost btn-sm btn-full">Text Cleaner</a>
        <a href="/tools/slug-generator" class="btn-ghost btn-sm btn-full">Slug Generator</a>
        <a href="/tools/base64"        class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Case Converter — logika konversi
   Semua berjalan di sisi klien (JavaScript).
   ────────────────────────────────────────── */

const CONVERTERS = {
  upper: s => s.toUpperCase(),
  lower: s => s.toLowerCase(),

  title: s => s.replace(/\w\S*/g, w =>
    w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()
  ),

  sentence: s => {
    const low = s.toLowerCase();
    return low.replace(/(^|[.!?]\s+)([a-z])/g, (_, p, c) =>
      _.replace(c, c.toUpperCase())
    );
  },

  camel: s => {
    const words = s.trim().split(/[\s_\-]+/).map(w => w.toLowerCase());
    return words[0] + words.slice(1)
      .map(w => w.charAt(0).toUpperCase() + w.slice(1))
      .join('');
  },

  pascal: s => s.trim()
    .split(/[\s_\-]+/)
    .map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
    .join(''),

  snake: s => s.trim()
    .replace(/([A-Z])/g, '_$1')
    .replace(/[\s\-]+/g, '_')
    .toLowerCase()
    .replace(/^_/, ''),

  kebab: s => s.trim()
    .replace(/([A-Z])/g, '-$1')
    .replace(/[\s_]+/g, '-')
    .toLowerCase()
    .replace(/^-/, ''),

  const: s => s.trim()
    .replace(/([A-Z])/g, '_$1')
    .replace(/[\s\-]+/g, '_')
    .toUpperCase()
    .replace(/^_/, ''),

  dot: s => s.trim()
    .replace(/([A-Z])/g, '.$1')
    .replace(/[\s_\-]+/g, '.')
    .toLowerCase()
    .replace(/^\./, ''),

  alternating: s => s.split('')
    .map((c, i) => i % 2 === 0 ? c.toLowerCase() : c.toUpperCase())
    .join(''),

  inverse: s => s.split('')
    .map(c => c === c.toUpperCase() ? c.toLowerCase() : c.toUpperCase())
    .join(''),
};

function convertCase() {
  const input = document.getElementById('input-text').value;
  const mode  = document.getElementById('case-mode').value;
  const fn    = CONVERTERS[mode];

  document.getElementById('output-text').value =
    (input && fn) ? fn(input) : '';
}

function clearAll() {
  document.getElementById('input-text').value  = '';
  document.getElementById('output-text').value = '';
}

function swapTexts() {
  const out = document.getElementById('output-text').value;
  if (!out) return;
  document.getElementById('input-text').value = out;
  convertCase();
}

async function pasteText() {
  try {
    const text = await navigator.clipboard.readText();
    document.getElementById('input-text').value = text;
    convertCase();
  } catch {
    // clipboard diblokir browser — user harus tempel manual
    document.getElementById('input-text').focus();
  }
}
</script>

<?php require '../../includes/footer.php'; ?>