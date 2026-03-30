<?php
require 'includes/config.php';
/**
 * Multi Tools — Halaman Utama (Landing Page)
 * ============================================================ */

// ── SEO Meta untuk halaman ini ──
$seo = [
  'title'       => 'Multi Tools — Kumpulan Tools Online Gratis untuk Developer & Desainer',
  'description' => 'Multi Tools menyediakan 30+ tools online gratis: text converter, image compressor, JSON formatter, QR generator, password generator, dan masih banyak lagi. Tanpa login, langsung pakai.',
  'keywords'    => 'multi tools, tools online gratis, text converter, image compressor, JSON formatter, QR generator, password generator, developer tools, case converter, word counter, base64',
  'og_title'    => 'Multi Tools — 30+ Tools Online Gratis',
  'og_desc'     => 'Kumpulan tools serba guna untuk developer, desainer, dan siapa saja. Text tools, image tools, developer tools, dan kalkulator. Gratis, tanpa login.',
  'breadcrumbs' => [
    ['name' => 'Beranda', 'url' => SITE_URL . '/'],
  ],
  // JSON-LD tambahan khusus halaman ini
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/#webpage',
      'url'         => SITE_URL . '/',
      'name'        => 'Multi Tools — Kumpulan Tools Online Gratis',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'about'       => ['@id' => SITE_URL . '/#organization'],
      'description' => '30+ tools online gratis: text converter, image compressor, JSON formatter, QR generator, dan lainnya.',
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => SITE_URL . '/'],
        ],
      ],
    ],
    [
      '@type'       => 'ItemList',
      'name'        => 'Daftar Tools Online Gratis',
      'description' => 'Koleksi lengkap tools online gratis di Multi Tools',
      'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1,  'name' => 'Case Converter',     'url' => SITE_URL . '/tools/case-converter'],
        ['@type' => 'ListItem', 'position' => 2,  'name' => 'Text Cleaner',       'url' => SITE_URL . '/tools/text-cleaner'],
        ['@type' => 'ListItem', 'position' => 3,  'name' => 'Word Counter',       'url' => SITE_URL . '/tools/word-counter'],
        ['@type' => 'ListItem', 'position' => 4,  'name' => 'Base64 Encode/Decode','url' => SITE_URL . '/tools/base64'],
        ['@type' => 'ListItem', 'position' => 5,  'name' => 'Image Compressor',   'url' => SITE_URL . '/tools/image-compressor'],
        ['@type' => 'ListItem', 'position' => 6,  'name' => 'Image Resizer',      'url' => SITE_URL . '/tools/image-resizer'],
        ['@type' => 'ListItem', 'position' => 7,  'name' => 'JSON Formatter',     'url' => SITE_URL . '/tools/json-formatter'],
        ['@type' => 'ListItem', 'position' => 8,  'name' => 'QR Generator',       'url' => SITE_URL . '/tools/qr-generator'],
        ['@type' => 'ListItem', 'position' => 9,  'name' => 'Password Generator', 'url' => SITE_URL . '/tools/password-generator'],
        ['@type' => 'ListItem', 'position' => 10, 'name' => 'UUID Generator',     'url' => SITE_URL . '/tools/uuid-generator'],
      ],
    ],
  ],
];

require 'includes/header.php';
?>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<section class="hero" aria-labelledby="hero-heading">
  <div class="glow glow-1" aria-hidden="true"></div>
  <div class="glow glow-2" aria-hidden="true"></div>

  <p class="hero-eyebrow" aria-hidden="true">// satu tempat · banyak solusi</p>

  <h1 class="hero-title" id="hero-heading">
    Multi<br><em>Tools</em>
  </h1>

  <p class="hero-sub">
    Kumpulan <strong>tools online gratis</strong> untuk developer, desainer,
    dan siapa saja yang ingin bekerja lebih cepat dan efisien.
  </p>

  <div class="hero-cta">
    <a href="#tools" class="btn-primary" aria-label="Jelajahi semua tools yang tersedia">
      Jelajahi Tools
    </a>
    <a href="/tools" class="btn-ghost" aria-label="Lihat semua tools">
      Lihat Semua →
    </a>
  </div>
</section>


<!-- ══════════════════════════════════════
     STATS BAR
══════════════════════════════════════ -->
<div class="stats" role="region" aria-label="Statistik Multi Tools">
  <div class="stat">
    <span class="stat-value" data-target="30">0</span>
    <span class="stat-label">Tools tersedia</span>
  </div>
  <div class="stat">
    <span class="stat-value" data-target="5">0</span>
    <span class="stat-label">Kategori</span>
  </div>
  <div class="stat">
    <span class="stat-value" data-target="100">0</span>
    <span class="stat-label">% Gratis</span>
  </div>
  <div class="stat">
    <span class="stat-value" data-target="0">0</span>
    <span class="stat-label">Login diperlukan</span>
  </div>
</div>


<!-- ══════════════════════════════════════
     SECTION: TEXT TOOLS
══════════════════════════════════════ -->
<div id="tools"></div>
<section class="section" aria-labelledby="heading-text-tools">
  <div class="section-header">
    <div>
      <h2 class="section-title" id="heading-text-tools">
        <span aria-hidden="true">✏️</span> Text <span>Tools</span>
      </h2>
      <p class="section-desc">Konversi, format, dan analisis teks dengan mudah.</p>
    </div>
    <a href="/tools?cat=text" class="section-view-all" aria-label="Lihat semua Text Tools">
      Lihat semua →
    </a>
  </div>
  <div class="cards" role="list">

    <article class="card cyan" role="listitem">
      <a href="/tools/case-converter" aria-label="Case Converter - Ubah format huruf teks">
        <div class="card-icon" aria-hidden="true">🔡</div>
        <div class="card-name">Case Converter</div>
        <div class="card-desc">Ubah teks ke UPPERCASE, lowercase, Title Case, dan lainnya.</div>
        <div class="card-footer">
          <span class="card-tag">Teks</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card cyan" role="listitem">
      <a href="/tools/text-cleaner" aria-label="Text Cleaner - Bersihkan format teks kotor">
        <div class="card-icon" aria-hidden="true">🧹</div>
        <div class="card-name">Text Cleaner</div>
        <div class="card-desc">Hapus spasi ganda, karakter khusus, dan format teks kotor.</div>
        <div class="card-footer">
          <span class="card-tag">Teks</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card cyan" role="listitem">
      <a href="/tools/word-counter" aria-label="Word Counter - Hitung kata dan karakter teks">
        <div class="card-icon" aria-hidden="true">📊</div>
        <div class="card-name">Word Counter</div>
        <div class="card-desc">Hitung kata, karakter, kalimat, dan paragraf secara realtime.</div>
        <div class="card-footer">
          <span class="card-tag">Teks</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card cyan" role="listitem">
      <a href="/tools/base64" aria-label="Base64 Encode Decode - Enkripsi teks dengan Base64">
        <div class="card-icon" aria-hidden="true">🔐</div>
        <div class="card-name">Base64 Encode/Decode</div>
        <div class="card-desc">Enkode dan dekode teks dengan algoritma Base64.</div>
        <div class="card-footer">
          <span class="card-tag">Enkripsi</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

  </div>
</section>

<div class="divider" aria-hidden="true"><span class="divider-text">— image tools —</span></div>


<!-- ══════════════════════════════════════
     SECTION: IMAGE TOOLS
══════════════════════════════════════ -->
<section class="section" aria-labelledby="heading-image-tools">
  <div class="section-header">
    <div>
      <h2 class="section-title" id="heading-image-tools">
        <span aria-hidden="true">🖼️</span> Image <span>Tools</span>
      </h2>
      <p class="section-desc">Kompres, resize, dan konversi gambar langsung di browser.</p>
    </div>
    <a href="/tools?cat=image" class="section-view-all" aria-label="Lihat semua Image Tools">
      Lihat semua →
    </a>
  </div>
  <div class="cards" role="list">

    <article class="card pink" role="listitem">
      <a href="/tools/image-compressor" aria-label="Image Compressor - Kompres ukuran file gambar">
        <div class="card-icon" aria-hidden="true">🗜️</div>
        <div class="card-name">Image Compressor</div>
        <div class="card-desc">Kompres gambar tanpa kehilangan kualitas visual yang berarti.</div>
        <div class="card-footer">
          <span class="card-tag">Populer</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card pink" role="listitem">
      <a href="/tools/image-resizer" aria-label="Image Resizer - Ubah dimensi gambar">
        <div class="card-icon" aria-hidden="true">↔️</div>
        <div class="card-name">Image Resizer</div>
        <div class="card-desc">Ubah dimensi gambar dengan menjaga rasio aspek.</div>
        <div class="card-footer">
          <span class="card-tag">Gambar</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card pink" role="listitem">
      <a href="/tools/image-converter" aria-label="Format Converter - Konversi format gambar PNG JPG WebP">
        <div class="card-icon" aria-hidden="true">🔄</div>
        <div class="card-name">Format Converter</div>
        <div class="card-desc">Konversi antara PNG, JPG, WebP, dan format lainnya.</div>
        <div class="card-footer">
          <span class="card-tag">Gambar</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card pink" role="listitem">
      <a href="/tools/image-cropper" aria-label="Image Cropper - Potong gambar dengan presisi">
        <div class="card-icon" aria-hidden="true">✂️</div>
        <div class="card-name">Image Cropper</div>
        <div class="card-desc">Potong gambar dengan kontrol presisi dan rasio custom.</div>
        <div class="card-footer">
          <span class="card-tag">Gambar</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

  </div>
</section>

<div class="divider" aria-hidden="true"><span class="divider-text">— developer tools —</span></div>


<!-- ══════════════════════════════════════
     SECTION: DEVELOPER TOOLS
══════════════════════════════════════ -->
<section class="section" aria-labelledby="heading-dev-tools">
  <div class="section-header">
    <div>
      <h2 class="section-title" id="heading-dev-tools">
        <span aria-hidden="true">💻</span> Developer <span>Tools</span>
      </h2>
      <p class="section-desc">Format, minify, dan generate kode lebih cepat.</p>
    </div>
    <a href="/tools?cat=developer" class="section-view-all" aria-label="Lihat semua Developer Tools">
      Lihat semua →
    </a>
  </div>
  <div class="cards" role="list">

    <article class="card violet" role="listitem">
      <a href="/tools/json-formatter" aria-label="JSON Formatter - Beautify dan validasi JSON">
        <div class="card-icon" aria-hidden="true">{ }</div>
        <div class="card-name">JSON Formatter</div>
        <div class="card-desc">Beautify dan validasi JSON dengan highlight syntax.</div>
        <div class="card-footer">
          <span class="card-tag">Dev</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card violet" role="listitem">
      <a href="/tools/html-beautifier" aria-label="HTML Beautifier - Format kode HTML menjadi rapi">
        <div class="card-icon" aria-hidden="true">🌐</div>
        <div class="card-name">HTML Beautifier</div>
        <div class="card-desc">Format HTML yang berantakan menjadi rapi dan terbaca.</div>
        <div class="card-footer">
          <span class="card-tag">Dev</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card violet" role="listitem">
      <a href="/tools/uuid-generator" aria-label="UUID Generator - Buat ID unik UUID v1 dan v4">
        <div class="card-icon" aria-hidden="true">🔗</div>
        <div class="card-name">UUID Generator</div>
        <div class="card-desc">Generate UUID v1/v4 untuk kebutuhan ID unik aplikasi.</div>
        <div class="card-footer">
          <span class="card-tag">Generator</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card violet" role="listitem">
      <a href="/tools/password-generator" aria-label="Password Generator - Buat password kuat dan aman">
        <div class="card-icon" aria-hidden="true">🔓</div>
        <div class="card-name">Password Generator</div>
        <div class="card-desc">Buat password kuat dengan aturan karakter yang fleksibel.</div>
        <div class="card-footer">
          <span class="card-tag">Keamanan</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

  </div>
</section>

<div class="divider" aria-hidden="true"><span class="divider-text">— utilities —</span></div>


<!-- ══════════════════════════════════════
     SECTION: UTILITIES
══════════════════════════════════════ -->
<section class="section" aria-labelledby="heading-util-tools">
  <div class="section-header">
    <div>
      <h2 class="section-title" id="heading-util-tools">
        <span aria-hidden="true">⚡</span> Tools <span>Lainnya</span>
      </h2>
      <p class="section-desc">Utilitas serbaguna untuk kebutuhan sehari-hari.</p>
    </div>
    <a href="/tools?cat=utility" class="section-view-all" aria-label="Lihat semua Tools Lainnya">
      Lihat semua →
    </a>
  </div>
  <div class="cards" role="list">

    <article class="card yellow" role="listitem">
      <a href="/tools/qr-generator" aria-label="QR Generator - Buat QR Code dari URL atau teks">
        <div class="card-icon" aria-hidden="true">📋</div>
        <div class="card-name">QR Generator</div>
        <div class="card-desc">Buat QR Code dari URL, teks, atau kontak dalam hitungan detik.</div>
        <div class="card-footer">
          <span class="card-tag">Populer</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card yellow" role="listitem">
      <a href="/tools/color-picker" aria-label="Color Picker - Pilih dan konversi warna HEX RGB HSL">
        <div class="card-icon" aria-hidden="true">🌈</div>
        <div class="card-name">Color Picker</div>
        <div class="card-desc">Ambil dan konversi warna antara HEX, RGB, dan HSL.</div>
        <div class="card-footer">
          <span class="card-tag">Desain</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card orange" role="listitem">
      <a href="/tools/unit-converter" aria-label="Unit Converter - Konversi satuan panjang berat dan suhu">
        <div class="card-icon" aria-hidden="true">📏</div>
        <div class="card-name">Unit Converter</div>
        <div class="card-desc">Konversi satuan panjang, berat, suhu, dan masih banyak lagi.</div>
        <div class="card-footer">
          <span class="card-tag">Kalkulator</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

    <article class="card orange" role="listitem">
      <a href="/tools/timezone-converter" aria-label="Time Zone Converter - Konversi waktu antar zona waktu dunia">
        <div class="card-icon" aria-hidden="true">🕐</div>
        <div class="card-name">Time Zone Converter</div>
        <div class="card-desc">Konversi waktu antar zona waktu dunia secara akurat.</div>
        <div class="card-footer">
          <span class="card-tag">Waktu</span>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
      </a>
    </article>

  </div>
</section>

<?php require 'includes/footer.php'; ?>