<?php
/**
 * Multi Tools — Konfigurasi Global
 * File ini di-include oleh semua halaman.
 * ============================================================ */

// ── URL & Identitas Situs ──
define('SITE_URL',    'https://multitools.test');
define('SITE_NAME',   'Multi Tools');
define('SITE_TAGLINE','Kumpulan Tools Online untuk Developer & Desainer');
define('SITE_LANG',   'id');
define('SITE_LOCALE', 'id_ID');
define('TWITTER_HANDLE', '@multitools');

// ── Default SEO Meta (bisa di-override per halaman) ──
define('DEFAULT_TITLE',       'Multi Tools — Kumpulan Tools Online untuk Developer & Desainer');
define('DEFAULT_DESCRIPTION', 'Multi Tools menyediakan 30+ tools online: text converter, image compressor, JSON formatter, QR generator, password generator, dan masih banyak lagi. Tanpa login, langsung pakai.');
define('DEFAULT_KEYWORDS',    'multi tools, tools online, text converter, image compressor, JSON formatter, QR generator, password generator, developer tools');
define('DEFAULT_OG_IMAGE',    SITE_URL . '/assets/img/og-image.png');

// ── Navigasi Dropdown ──
// Struktur: ['id', 'label', 'emoji', 'badge', [['label', 'url', 'emoji', 'bg', 'tag?'], ...], 'group_label?']
$nav_items = [
  [
    'id'      => 'g-text',
    'label'   => 'Teks',
    'emoji'   => '✏️',
    'badge'   => null,
    'groups'  => [
      'Konversi & Format' => [
        ['Case Converter',    '/tools/case-converter', '🔡', '#dbeafe', 'new'],
        ['Text Cleaner',      '/tools/text-cleaner',   '🧹', '#dbeafe'],
        ['Word Counter',      '/tools/word-counter',   '📊', '#dbeafe'],
      ],
      'Enkripsi' => [
        ['Base64 Encode/Decode', '/tools/base64',  '🔐', '#dbeafe'],
        ['MD5 / SHA Hash',       '/tools/md5-hash','🔑', '#dbeafe'],
      ],
    ],
  ],
  [
    'id'      => 'g-image',
    'label'   => 'Gambar',
    'emoji'   => '🖼️',
    'badge'   => null,
    'groups'  => [
      'Optimasi' => [
        ['Image Compressor', '/tools/image-compressor', '🗜️', '#e0f2fe', 'hot'],
        ['Image Cropper',    '/tools/image-cropper',    '✂️', '#e0f2fe'],
        ['Image Resizer',    '/tools/image-resizer',    '↔️', '#e0f2fe'],
      ],
      'Konversi' => [
        ['PNG ↔ JPG ↔ WebP', '/tools/image-converter', '🔄', '#e0f2fe'],
        ['SVG Converter',    '/tools/svg-converter',   '📐', '#e0f2fe'],
      ],
    ],
  ],
  [
    'id'      => 'g-dev',
    'label'   => 'Developer',
    'emoji'   => '💻',
    'badge'   => '5',
    'groups'  => [
      'Formatter' => [
        ['JSON Formatter',  '/tools/json-formatter',  '{ }', '#ede9fe'],
        ['HTML Beautifier', '/tools/html-beautifier', '🌐',  '#ede9fe'],
        ['CSS Minifier',    '/tools/css-minifier',    '🎨',  '#ede9fe'],
      ],
      'Generator' => [
        ['UUID Generator',     '/tools/uuid-generator',     '🔗', '#ede9fe', 'new'],
        ['Password Generator', '/tools/password-generator', '🔓', '#ede9fe'],
      ],
    ],
  ],
  [
    'id'      => 'g-math',
    'label'   => 'Kalkulator',
    'emoji'   => '🧮',
    'badge'   => null,
    'groups'  => [
      '' => [
        ['Kalkulator BMI',    '/tools/bmi-calculator',      '💰', '#fef3c7'],
        ['Kalkulator Bunga',  '/tools/interest-calculator', '📈', '#fef3c7'],
        ['Time Zone Converter','/tools/timezone-converter', '🕐', '#fef3c7'],
        ['Unit Converter',    '/tools/unit-converter',      '📏', '#fef3c7'],
      ],
    ],
  ],
  [
    'id'      => 'g-more',
    'label'   => 'Lainnya',
    'emoji'   => '⚡',
    'badge'   => null,
    'groups'  => [
      'Utilitas' => [
        ['QR Generator',         '/tools/qr-generator',   '📋', '#d1fae5', 'hot'],
        ['Color Picker',         '/tools/color-picker',   '🌈', '#d1fae5'],
        ['Lorem Ipsum Generator','/tools/lorem-ipsum',    '🔊', '#d1fae5'],
      ],
      'Produktivitas' => [
        ['Date Calculator', '/tools/date-calculator', '📅', '#d1fae5'],
        ['Markdown Editor', '/tools/markdown-editor', '📌', '#d1fae5'],
      ],
    ],
  ],
];

/**
 * Mendapatkan URL saat ini secara dinamis (untuk canonical & OG URL).
 */
function current_url(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Helper: escape output HTML.
 */
function e(string $str): string {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}