<?php
require '../../includes/config.php';
/**
 * Multi Tools — Word Counter
 * Contoh halaman tool individual.
 * ============================================================ */

// Breadcrumb & SEO untuk halaman ini
$seo = [
  'title'       => 'Word Counter Online — Hitung Kata & Karakter | Multi Tools',
  'description' => 'Hitung jumlah kata, karakter, kalimat, dan paragraf dari teks kamu secara realtime. Tanpa login, langsung di browser.',
  'keywords'    => 'word counter, hitung kata online, karakter counter, word count, hitung karakter, multi tools',
  'og_title'    => 'Word Counter Online — Hitung Kata & Karakter',
  'og_desc'     => 'Hitung kata, karakter, kalimat, dan paragraf secara realtime. Tanpa login, langsung pakai.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Text Tools', 'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Word Counter'],
  ],
  // Schema tambahan untuk halaman tool
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/word-counter#webpage',
      'url'         => SITE_URL . '/tools/word-counter',
      'name'        => 'Word Counter Online',
      'description' => 'Hitung kata, karakter, kalimat, dan paragraf secara realtime.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',     'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',  'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Word Counter','item' => SITE_URL . '/tools/word-counter'],
        ],
      ],
    ],
    [
      '@type'            => 'SoftwareApplication',
      'name'             => 'Word Counter',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'  => 'Web Browser',
      'offers'           => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'              => SITE_URL . '/tools/word-counter',
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
        <span aria-hidden="true">📊</span> Word <span>Counter</span>
      </div>
      <p class="page-lead">
        Hitung kata, karakter, kalimat, dan paragraf dari teks kamu secara realtime.
      </p>

      <div class="form-group" style="margin-top:1.5rem">
        <label for="input-text">Masukkan atau tempel teks di sini</label>
        <textarea
          id="input-text"
          placeholder="Ketik atau tempel teks kamu di sini..."
          oninput="countWords()"
          aria-describedby="word-stats"
        ></textarea>
      </div>

      <!-- Hasil statistik -->
      <div id="word-stats" class="stats" role="region" aria-live="polite" aria-label="Hasil hitungan">
        <div class="stat">
          <span class="stat-value" id="count-words">0</span>
          <span class="stat-label">Kata</span>
        </div>
        <div class="stat">
          <span class="stat-value" id="count-chars">0</span>
          <span class="stat-label">Karakter</span>
        </div>
        <div class="stat">
          <span class="stat-value" id="count-sentences">0</span>
          <span class="stat-label">Kalimat</span>
        </div>
        <div class="stat">
          <span class="stat-value" id="count-paragraphs">0</span>
          <span class="stat-label">Paragraf</span>
        </div>
      </div>

      <div style="margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap;">
        <button class="btn-ghost btn-sm" onclick="document.getElementById('input-text').value=''; countWords();">
          Bersihkan
        </button>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <aside>
    <div class="panel">
      <div class="panel-title">💡 Tips</div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem;">
        Word Counter menghitung secara realtime saat kamu mengetik.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2;">
        <li>Karakter dihitung <strong>termasuk spasi</strong></li>
        <li>Kalimat dipisah oleh <code>. ! ?</code></li>
        <li>Paragraf dipisah oleh baris kosong</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <a href="/tools/case-converter" class="dropdown" style="position:static;opacity:1;transform:none;pointer-events:all;box-shadow:none;padding:0;border:none;">
      </a>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/case-converter" class="btn-ghost btn-sm btn-full">Case Converter</a>
        <a href="/tools/text-cleaner"   class="btn-ghost btn-sm btn-full">Text Cleaner</a>
        <a href="/tools/base64"         class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
function countWords() {
  const text = document.getElementById('input-text').value;

  const words      = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
  const chars      = text.length;
  const sentences  = text.trim() === '' ? 0 : (text.match(/[^.!?]+[.!?]+/g) || []).length;
  const paragraphs = text.trim() === '' ? 0 : text.split(/\n\s*\n/).filter(p => p.trim()).length || (text.trim() ? 1 : 0);

  document.getElementById('count-words').textContent      = words;
  document.getElementById('count-chars').textContent      = chars;
  document.getElementById('count-sentences').textContent  = sentences;
  document.getElementById('count-paragraphs').textContent = paragraphs;
}
</script>

<?php require '../../includes/footer.php'; ?>