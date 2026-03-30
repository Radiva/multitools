<?php
/**
 * Multi Tools — Header & <head> Global
 *
 * Cara pakai di setiap halaman:
 *
 *   $seo = [
 *     'title'       => 'Judul Halaman | Multi Tools',
 *     'description' => 'Deskripsi halaman.',
 *     'keywords'    => 'kata, kunci',
 *     'og_image'    => SITE_URL . '/assets/img/og-custom.png', // opsional
 *     'og_type'     => 'article',    // default: website
 *     'schema'      => [...],        // JSON-LD tambahan (opsional)
 *     'breadcrumbs' => [...],        // opsional
 *     'extra_head'  => '<style>...</style>', // CSS inline (opsional)
 *     'extra_scripts' => '<script>...</script>', // JS inline (opsional)
 *   ];
 *   require 'includes/header.php';
 * ============================================================ */

require_once __DIR__ . '/config.php';

// ── Resolusi nilai SEO (fallback ke default jika tidak diset) ──
$seo = $seo ?? [];
$meta_title       = e($seo['title']       ?? DEFAULT_TITLE);
$meta_desc        = e($seo['description'] ?? DEFAULT_DESCRIPTION);
$meta_keywords    = e($seo['keywords']    ?? DEFAULT_KEYWORDS);
$meta_og_image    = e($seo['og_image']    ?? DEFAULT_OG_IMAGE);
$meta_og_type     = e($seo['og_type']     ?? 'website');
$meta_robots      = e($seo['robots']      ?? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1');
$meta_canonical   = e($seo['canonical']   ?? current_url());
$meta_og_title    = e($seo['og_title']    ?? ($seo['title'] ?? DEFAULT_TITLE));
$meta_og_desc     = e($seo['og_desc']     ?? ($seo['description'] ?? DEFAULT_DESCRIPTION));

// ── JSON-LD: schema dasar WebSite + Organization (selalu ada) ──
$base_schema = [
  [
    '@type'       => 'WebSite',
    '@id'         => SITE_URL . '/#website',
    'url'         => SITE_URL . '/',
    'name'        => SITE_NAME,
    'description' => DEFAULT_DESCRIPTION,
    'inLanguage'  => 'id-ID',
    'potentialAction' => [
      '@type'  => 'SearchAction',
      'target' => [
        '@type'       => 'EntryPoint',
        'urlTemplate' => SITE_URL . '/search?q={search_term_string}',
      ],
      'query-input' => 'required name=search_term_string',
    ],
  ],
  [
    '@type' => 'Organization',
    '@id'   => SITE_URL . '/#organization',
    'name'  => SITE_NAME,
    'url'   => SITE_URL . '/',
    'logo'  => [
      '@type'  => 'ImageObject',
      'url'    => SITE_URL . '/assets/img/logo.png',
      'width'  => 512,
      'height' => 512,
    ],
  ],
];

// Gabungkan dengan schema tambahan dari halaman (jika ada)
$extra_schema = $seo['schema'] ?? [];
$full_schema  = array_merge($base_schema, $extra_schema);
$json_ld      = json_encode(
  ['@context' => 'https://schema.org', '@graph' => $full_schema],
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);

// ── Breadcrumb untuk <head> ──
$breadcrumbs = $seo['breadcrumbs'] ?? [['name' => 'Beranda', 'url' => SITE_URL . '/']];
?>
<!DOCTYPE html>
<html lang="<?= SITE_LANG ?>" prefix="og: https://ogp.me/ns#">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- ══ PRIMARY META ══ -->
  <title><?= $meta_title ?></title>
  <meta name="description"  content="<?= $meta_desc ?>" />
  <meta name="keywords"     content="<?= $meta_keywords ?>" />
  <meta name="author"       content="<?= e(SITE_NAME) ?>" />
  <meta name="robots"       content="<?= $meta_robots ?>" />
  <meta name="theme-color"  content="#2563eb" />
  <link rel="canonical"     href="<?= $meta_canonical ?>" />

  <!-- ══ OPEN GRAPH ══ -->
  <meta property="og:type"        content="<?= $meta_og_type ?>" />
  <meta property="og:url"         content="<?= $meta_canonical ?>" />
  <meta property="og:title"       content="<?= $meta_og_title ?>" />
  <meta property="og:description" content="<?= $meta_og_desc ?>" />
  <meta property="og:image"       content="<?= $meta_og_image ?>" />
  <meta property="og:image:width"  content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:image:alt"   content="<?= e(SITE_NAME) ?> - <?= e(SITE_TAGLINE) ?>" />
  <meta property="og:locale"      content="<?= e(SITE_LOCALE) ?>" />
  <meta property="og:site_name"   content="<?= e(SITE_NAME) ?>" />

  <!-- ══ TWITTER CARD ══ -->
  <meta name="twitter:card"        content="summary_large_image" />
  <meta name="twitter:site"        content="<?= e(TWITTER_HANDLE) ?>" />
  <meta name="twitter:title"       content="<?= $meta_og_title ?>" />
  <meta name="twitter:description" content="<?= $meta_og_desc ?>" />
  <meta name="twitter:image"       content="<?= $meta_og_image ?>" />
  <meta name="twitter:image:alt"   content="<?= e(SITE_NAME) ?> - <?= e(SITE_TAGLINE) ?>" />

  <!-- ══ STRUCTURED DATA / JSON-LD ══ -->
  <script type="application/ld+json"><?= $json_ld ?></script>

  <!-- ══ FONTS ══ -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet" />

  <!-- ══ STYLESHEET ══ -->
  <link rel="stylesheet" href="/assets/css/style.css" />

  <?php if (!empty($seo['extra_head'])): ?>
    <!-- ══ EXTRA HEAD (dari halaman) ══ -->
    <?= $seo['extra_head'] ?>
  <?php endif; ?>
</head>
<body>

<a class="skip-link" href="#main-content">Langsung ke konten</a>

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav aria-label="Navigasi utama <?= e(SITE_NAME) ?>">

  <a class="nav-logo" href="/" aria-label="<?= e(SITE_NAME) ?> - Beranda">
    <span>Multi</span><span class="dot">Tools</span>
  </a>

  <?php
  $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

  foreach ($nav_items as $i => $item):
    // Tambahkan separator antar item (kecuali sebelum item pertama)
    if ($i > 0): ?>
      <div class="nav-separator" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="nav-group" id="<?= e($item['id']) ?>">
      <button
        class="nav-btn"
        onclick="toggleDropdown('<?= e($item['id']) ?>')"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="dropdown-<?= e($item['id']) ?>"
      >
        <span aria-hidden="true"><?= $item['emoji'] ?></span>
        <?= e($item['label']) ?>
        <svg class="chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
          <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <?php if ($item['badge']): ?>
          <span class="nav-badge"><?= e($item['badge']) ?></span>
        <?php endif; ?>
      </button>

      <div class="dropdown" id="dropdown-<?= e($item['id']) ?>" role="menu">
        <?php foreach ($item['groups'] as $group_label => $links): ?>
          <?php if ($group_label !== ''): ?>
            <div class="dropdown-label"><?= e($group_label) ?></div>
          <?php endif; ?>

          <?php foreach ($links as $link):
            $is_active = ($current_path === $link[1]);
          ?>
            <a
              href="<?= e($link[1]) ?>"
              role="menuitem"
              <?= $is_active ? 'aria-current="page"' : '' ?>
            >
              <span class="icon" style="background:<?= e($link[3]) ?>" aria-hidden="true">
                <?= $link[2] ?>
              </span>
              <?= e($link[0]) ?>
              <?php if (isset($link[4])): ?>
                <span class="tag <?= e($link[4]) ?>"><?= e($link[4]) ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>

          <?php
          // Tambah separator antara grup (kecuali grup terakhir)
          $groups = array_keys($item['groups']);
          if ($group_label !== end($groups) && $group_label !== ''):
          ?>
            <div class="dropdown-sep" aria-hidden="true"></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div><!-- /.dropdown -->
    </div><!-- /.nav-group -->

  <?php endforeach; ?>
</nav>

<!-- ══ MAIN CONTENT dimulai di sini ══ -->
<main id="main-content">