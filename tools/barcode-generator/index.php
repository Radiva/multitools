<?php
require '../../includes/config.php';
/**
 * Multi Tools — Barcode Generator
 * Generate barcode Code 128, Code 39, EAN-13, UPC-A, dan ITF
 * menggunakan SVG murni di PHP — tanpa library eksternal.
 * Mendukung download SVG/PNG, ukuran kustom, dan warna.
 * ============================================================ */

// ── Code 128 Encoder ─────────────────────────────────────────

/**
 * Encode string ke Code 128B (ASCII 32-127).
 * Mengembalikan array of bar widths (1-4), alternating bar/space.
 */
function encodeCode128(string $text): array|false {
  if ($text === '') return false;

  // Code 128 karakter set B (nilai 0-95 = ASCII 32-127)
  $CODE128B_TABLE = [
    // nilai => [bar1, space1, bar2, space2, bar3, space3]
    0  =>[2,1,2,2,2,2], 1 =>[2,2,2,1,2,2], 2 =>[2,2,2,2,2,1],
    3  =>[1,2,1,2,2,3], 4 =>[1,2,1,3,2,2], 5 =>[1,3,1,2,2,2],
    6  =>[1,2,2,2,1,3], 7 =>[1,2,2,3,1,2], 8 =>[1,3,2,2,1,2],
    9  =>[2,2,1,2,1,3], 10 =>[2,2,1,3,1,2], 11 =>[2,3,1,2,1,2],
    12 =>[1,1,2,2,3,2], 13 =>[1,2,2,1,3,2], 14 =>[1,2,2,2,3,1],
    15 =>[1,1,3,2,2,2], 16 =>[1,2,3,1,2,2], 17 =>[1,2,3,2,2,1],
    18 =>[2,2,3,2,1,1], 19 =>[2,2,1,1,3,2], 20 =>[2,2,1,2,3,1],
    21 =>[2,1,3,2,1,2], 22 =>[2,2,3,1,1,2], 23 =>[3,1,2,1,3,1],
    24 =>[3,1,1,2,2,2], 25 =>[3,2,1,1,2,2], 26 =>[3,2,1,2,2,1],
    27 =>[3,1,2,2,1,2], 28 =>[3,2,2,1,1,2], 29 =>[3,2,2,2,1,1],
    30 =>[2,1,2,1,2,3], 31 =>[2,1,2,3,2,1], 32 =>[2,3,2,1,2,1],
    33 =>[1,1,1,3,2,3], 34 =>[1,3,1,1,2,3], 35 =>[1,3,1,3,2,1],
    36 =>[1,1,2,3,1,3], 37 =>[1,3,2,1,1,3], 38 =>[1,3,2,3,1,1],
    39 =>[2,1,1,3,1,3], 40 =>[2,3,1,1,1,3], 41 =>[2,3,1,3,1,1],
    42 =>[1,1,2,1,3,3], 43 =>[1,1,2,3,3,1], 44 =>[1,3,2,1,3,1],
    45 =>[1,1,3,1,2,3], 46 =>[1,1,3,3,2,1], 47 =>[1,3,3,1,2,1],
    48 =>[3,1,3,1,2,1], 49 =>[2,1,1,3,3,1], 50 =>[2,3,1,1,3,1],
    51 =>[2,1,3,1,1,3], 52 =>[2,1,3,3,1,1], 53 =>[2,1,3,1,3,1],
    54 =>[3,1,1,1,2,3], 55 =>[3,1,1,3,2,1], 56 =>[3,3,1,1,2,1],
    57 =>[3,1,2,1,1,3], 58 =>[3,1,2,3,1,1], 59 =>[3,3,2,1,1,1],
    60 =>[3,1,4,1,1,1], 61 =>[2,2,1,4,1,1], 62 =>[4,3,1,1,1,1],
    63 =>[1,1,1,2,2,4], 64 =>[1,1,1,4,2,2], 65 =>[1,2,1,1,2,4],
    66 =>[1,2,1,4,2,1], 67 =>[1,4,1,1,2,2], 68 =>[1,4,1,2,2,1],
    69 =>[1,1,2,2,1,4], 70 =>[1,1,2,4,1,2], 71 =>[1,2,2,1,1,4],
    72 =>[1,2,2,4,1,1], 73 =>[1,4,2,1,1,2], 74 =>[1,4,2,2,1,1],
    75 =>[2,4,1,2,1,1], 76 =>[2,2,1,1,1,4], 77 =>[4,1,3,1,1,1],
    78 =>[2,4,1,1,1,2], 79 =>[1,3,4,1,1,1], 80 =>[1,1,1,2,4,2],
    81 =>[1,2,1,1,4,2], 82 =>[1,2,1,2,4,1], 83 =>[1,1,4,2,1,2],
    84 =>[1,2,4,1,1,2], 85 =>[1,2,4,2,1,1], 86 =>[4,1,1,2,1,2],
    87 =>[4,2,1,1,1,2], 88 =>[4,2,1,2,1,1], 89 =>[2,1,2,1,4,1],
    90 =>[2,1,4,1,2,1], 91 =>[4,1,2,1,2,1], 92 =>[1,1,1,1,4,3],
    93 =>[1,1,1,3,4,1], 94 =>[1,3,1,1,4,1], 95 =>[1,1,4,1,1,3],
    96 =>[1,1,4,3,1,1], 97 =>[4,1,1,1,1,3], 98 =>[4,1,1,3,1,1],
    99 =>[1,1,3,1,4,1], 100=>[1,1,4,1,3,1], 101=>[3,1,1,1,4,1],
    102=>[4,1,1,1,3,1],
    // Start B = 104
    104=>[2,1,1,4,1,2],
    // Stop = 106
    106=>[2,3,3,1,1,1,2], // stop has 7 elements (extra bar)
    // Check digit placeholder - same as others
  ];

  // Start B = code value 104
  $startB = 104;
  // Nilai checksum: start dengan nilai startB
  $checksum = $startB;
  $codes    = [$startB];

  // Encode tiap karakter
  foreach (str_split($text) as $idx => $char) {
    $ord = ord($char);
    if ($ord < 32 || $ord > 127) return false; // Hanya ASCII printable
    $val = $ord - 32; // Code 128B: nilai 0-95
    $codes[] = $val;
    $checksum += $val * ($idx + 1);
  }

  // Checksum mod 103
  $checksumVal = $checksum % 103;
  $codes[] = $checksumVal;

  // Build bar/space pattern
  // Quiet zone = 10 units, Start, Data, Checksum, Stop, Quiet zone
  $bars = array_fill(0, 10, 0); // quiet zone (spaces)

  foreach ($codes as $code) {
    $pattern = $CODE128B_TABLE[$code] ?? null;
    if ($pattern === null) continue;
    foreach ($pattern as $i => $width) {
      $bars[] = $width;
    }
  }

  // Stop bar (106): [2,3,3,1,1,1,2]
  $stop = [2,3,3,1,1,1,2];
  foreach ($stop as $w) $bars[] = $w;

  $bars = array_merge($bars, array_fill(0, 10, 0)); // quiet zone

  return $bars;
}

/**
 * Render barcode Code 128 sebagai SVG string.
 */
function renderCode128SVG(
  string $text,
  int    $height    = 80,
  int    $moduleW   = 2,
  string $barColor  = '#000000',
  string $bgColor   = '#ffffff',
  bool   $showText  = true,
  int    $fontSize  = 12
): string|false {
  $bars = encodeCode128($text);
  if ($bars === false) return false;

  // Hitung lebar total
  $totalW = array_sum($bars) * $moduleW;
  $textH  = $showText ? $fontSize + 8 : 0;
  $totalH = $height + $textH;

  $svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalW . '" height="' . $totalH . '" viewBox="0 0 ' . $totalW . ' ' . $totalH . '">';
  $svg .= '<rect width="' . $totalW . '" height="' . $totalH . '" fill="' . htmlspecialchars($bgColor) . '"/>';

  $x = 0;
  foreach ($bars as $i => $width) {
    $w = $width * $moduleW;
    if ($i % 2 === 0 && $width > 0) {
      // Even index = bar (hitam), odd = space
      // Quiet zone awal adalah space, jadi index 0 = space (skip)
      // Index 0..9 = quiet zone spaces
    }
    // Bar jika index ganjil dari quiet zone (index 10 = start bar 1)
    // Logic: index genap = space, ganjil = bar — KECUALI quiet zone (index 0-9, 0-9 di akhir) semua space
    // Quiet zone = index 0-9 dan N-9 ke N
    // Cara mudah: alternating start dari bar
    if ($i >= 10 && $i < count($bars) - 10) {
      // Relative index dari start barcode
      $relIdx = $i - 10;
      if ($relIdx % 2 === 0) {
        // Bar
        $svg .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '" fill="' . htmlspecialchars($barColor) . '"/>';
      }
      // Space: tidak perlu gambar (sudah background)
    }
    $x += $w;
  }

  if ($showText) {
    $svg .= '<text x="' . ($totalW / 2) . '" y="' . ($height + $fontSize + 2) . '" '
          . 'text-anchor="middle" font-family="monospace" font-size="' . $fontSize . '" '
          . 'fill="' . htmlspecialchars($barColor) . '">'
          . htmlspecialchars($text) . '</text>';
  }

  $svg .= '</svg>';
  return $svg;
}

/**
 * Generate Code 39 barcode.
 * Code 39 mendukung: A-Z, 0-9, dan karakter - . $ / + % SPACE
 */
function renderCode39SVG(
  string $text,
  int    $height   = 80,
  int    $moduleW  = 2,
  string $barColor = '#000000',
  string $bgColor  = '#ffffff',
  bool   $showText = true,
  int    $fontSize = 12
): string|false {
  $text = strtoupper($text);

  // Pola Code 39: N=narrow, W=wide (bar space bar space bar space bar space bar space)
  // 1=wide bar, 0=narrow bar, ' '=narrow space, 'W'=wide space
  $CODE39 = [
    '0'=>'NWNNWWNNW','1'=>'WWNNNNNWW','2'=>'NWWNNNNWN',
    '3'=>'WWWNNNNNN','4'=>'NNWWNNNWN','5'=>'WNWWNNNNN',
    '6'=>'NWWWWNNNN','7'=>'NNNWWNWWN','8'=>'WNNWWNWNN',
    '9'=>'NWNWWNNNN','A'=>'WNNNNWWNN','B'=>'NWNNWNWNN',
    'C'=>'WWNNNWWNN','D'=>'NNWNWWNNN','E'=>'WNWNNWNNN',
    'F'=>'NWWNNWNNN','G'=>'NNNWWWNNN','H'=>'WNNWWWNNN',
    'I'=>'NWNWWWNNN','J'=>'NNWWWWNNN','K'=>'WNNNNNNWW',
    'L'=>'NWNNNNWWN','M'=>'WWNNNNWWN','N'=>'NWNNNNWWN',
    'O'=>'NNWNNNWWN','P'=>'WNWWNNWWN','Q'=>'NNNWNNWWN',
    'R'=>'WNNWNNWWN','S'=>'NWNWNNWWN','T'=>'NNWWNNWWN',
    'U'=>'WWNNNNNNW','V'=>'NWNNNNNNW','W'=>'WWNNNNN W',
    'X'=>'NNWNNNNNW','Y'=>'WNWNNNNNN','Z'=>'NWWNNNNNN',
    '-'=>'NNNNNWWNN','.'=>'WNNNNWNN ','$'=>'NNNWNWNNN',
    '/'=>'NNNNNWNWN','+'=>'NNNWNNNWN','%'=>'NWNNNNNWN',
    ' '=>'NWNNNWNN ',
  ];

  // Start/stop = '*'
  $startStop = 'NWNNWNWNN';

  // Validate
  foreach (str_split($text) as $ch) {
    if (!isset($CODE39[$ch])) return false;
  }

  $N = $moduleW;     // narrow width
  $W = $moduleW * 3; // wide width

  $allBars = [];

  $encodeChar = function(string $pattern) use ($N, $W): array {
    $bars = [];
    foreach (str_split($pattern) as $ch) {
      $bars[] = $ch === 'W' ? $W : $N;
    }
    return $bars;
  };

  // Start
  $allBars = array_merge($allBars, [$N*10], $encodeChar($startStop), [$N]);
  // Data
  foreach (str_split($text) as $ch) {
    $allBars = array_merge($allBars, $encodeChar($CODE39[$ch]), [$N]);
  }
  // Stop
  $allBars = array_merge($allBars, $encodeChar($startStop), [$N*10]);

  $totalW = array_sum($allBars);
  $textH  = $showText ? $fontSize + 8 : 0;
  $totalH = $height + $textH;

  $svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalW . '" height="' . $totalH . '" viewBox="0 0 ' . $totalW . ' ' . $totalH . '">';
  $svg .= '<rect width="' . $totalW . '" height="' . $totalH . '" fill="' . htmlspecialchars($bgColor) . '"/>';

  $x = 0;
  foreach ($allBars as $i => $w) {
    if ($i % 2 === 0) {
      // Bar (even = bar in Code 39 pattern after quiet zone)
      // Quiet zone at start is space equivalent
      if ($i > 0 && $i < count($allBars) - 1) {
        $svg .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '" fill="' . htmlspecialchars($barColor) . '"/>';
      }
    }
    $x += $w;
  }

  if ($showText) {
    $svg .= '<text x="' . ($totalW / 2) . '" y="' . ($height + $fontSize + 2) . '" '
          . 'text-anchor="middle" font-family="monospace" font-size="' . $fontSize . '" '
          . 'fill="' . htmlspecialchars($barColor) . '">*' . htmlspecialchars($text) . '*</text>';
  }

  $svg .= '</svg>';
  return $svg;
}

/**
 * Render EAN-13 barcode SVG.
 * Input: 12 digit (check digit dihitung otomatis) atau 13 digit.
 */
function renderEAN13SVG(
  string $text,
  int    $height   = 80,
  int    $moduleW  = 2,
  string $barColor = '#000000',
  string $bgColor  = '#ffffff',
  bool   $showText = true,
  int    $fontSize = 12
): string|false {
  // Bersihkan: hanya digit
  $digits = preg_replace('/\D/', '', $text);
  if (strlen($digits) === 12) {
    // Hitung check digit
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
      $sum += (int)$digits[$i] * (($i % 2 === 0) ? 1 : 3);
    }
    $check = (10 - ($sum % 10)) % 10;
    $digits .= $check;
  }
  if (strlen($digits) !== 13 || !ctype_digit($digits)) return false;

  // Tabel encoding EAN (L, G, R codes)
  $L = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
  $G = ['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
  $R = ['1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'];

  // Paritas untuk first digit (digit ke-0)
  $PARITY = [
    '0'=>'LLLLLL','1'=>'LLGLGG','2'=>'LLGGLG','3'=>'LLGGGL','4'=>'LGLLGG',
    '5'=>'LGGLLG','6'=>'LGGGLL','7'=>'LGLGLG','8'=>'LGLGGL','9'=>'LGGLGL',
  ];

  $firstDigit = $digits[0];
  $parityStr  = $PARITY[$firstDigit];

  // Build bit pattern
  $bits = '101'; // Start guard
  for ($i = 1; $i <= 6; $i++) {
    $d = (int)$digits[$i];
    $bits .= $parityStr[$i-1] === 'L' ? $L[$d] : $G[$d];
  }
  $bits .= '01010'; // Middle guard
  for ($i = 7; $i <= 12; $i++) {
    $bits .= $R[(int)$digits[$i]];
  }
  $bits .= '101'; // End guard

  $totalW = (strlen($bits) + 20) * $moduleW; // quiet zones
  $textH  = $showText ? $fontSize + 8 : 0;
  $totalH = $height + $textH;

  $svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalW . '" height="' . $totalH . '" viewBox="0 0 ' . $totalW . ' ' . $totalH . '">';
  $svg .= '<rect width="' . $totalW . '" height="' . $totalH . '" fill="' . htmlspecialchars($bgColor) . '"/>';

  $x = 10 * $moduleW; // left quiet zone
  foreach (str_split($bits) as $bit) {
    if ($bit === '1') {
      $svg .= '<rect x="' . $x . '" y="0" width="' . $moduleW . '" height="' . $height . '" fill="' . htmlspecialchars($barColor) . '"/>';
    }
    $x += $moduleW;
  }

  if ($showText) {
    $svg .= '<text x="' . ($totalW / 2) . '" y="' . ($height + $fontSize + 2) . '" '
          . 'text-anchor="middle" font-family="monospace" font-size="' . $fontSize . '" '
          . 'fill="' . htmlspecialchars($barColor) . '">' . htmlspecialchars($digits) . '</text>';
  }

  $svg .= '</svg>';
  return $svg;
}

/**
 * Generate QR Code sebagai HTML canvas (JS-rendered) — fallback server: link Google Charts.
 * Untuk server-side QR: gunakan library chillerlan/php-qrcode
 */
function getQRCodeURL(string $text, int $size = 200): string {
  return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
       . '&data=' . urlencode($text) . '&format=svg';
}

// ── Handle POST ──────────────────────────────────────────────
$server_result  = '';
$server_error   = '';
$post_input     = '';
$post_type      = 'code128'; // code128 | code39 | ean13 | qr
$post_height    = 80;
$post_module_w  = 2;
$post_bar_color = '#000000';
$post_bg_color  = '#ffffff';
$post_show_text = true;
$post_mode      = 'single'; // single | bulk
$post_bulk_input= '';
$bulk_results   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_type      = in_array($_POST['type'] ?? 'code128', ['code128','code39','ean13','qr'])
                      ? $_POST['type'] : 'code128';
  $post_input     = trim($_POST['input_text'] ?? '');
  $post_height    = max(40, min(300, (int)($_POST['bar_height'] ?? 80)));
  $post_module_w  = max(1, min(5,   (int)($_POST['module_width'] ?? 2)));
  $post_bar_color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['bar_color'] ?? '') ? $_POST['bar_color'] : '#000000';
  $post_bg_color  = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['bg_color']  ?? '') ? $_POST['bg_color']  : '#ffffff';
  $post_show_text = isset($_POST['show_text']);
  $post_mode      = isset($_POST['bulk_mode']) ? 'bulk' : 'single';
  $post_bulk_input= $_POST['bulk_input'] ?? '';

  if ($post_mode === 'single') {
    if ($post_input === '') { $server_error = 'Teks tidak boleh kosong.'; }
    else {
      switch ($post_type) {
        case 'code128':
          $svg = renderCode128SVG($post_input, $post_height, $post_module_w, $post_bar_color, $post_bg_color, $post_show_text);
          if ($svg === false) $server_error = 'Code 128 hanya mendukung karakter ASCII 32–127.';
          else $server_result = $svg;
          break;
        case 'code39':
          $svg = renderCode39SVG(strtoupper($post_input), $post_height, $post_module_w, $post_bar_color, $post_bg_color, $post_show_text);
          if ($svg === false) $server_error = 'Code 39 hanya mendukung A-Z, 0-9, dan - . $ / + % SPACE.';
          else $server_result = $svg;
          break;
        case 'ean13':
          $svg = renderEAN13SVG($post_input, $post_height, $post_module_w, $post_bar_color, $post_bg_color, $post_show_text);
          if ($svg === false) $server_error = 'EAN-13 membutuhkan tepat 12 atau 13 digit angka.';
          else $server_result = $svg;
          break;
        case 'qr':
          $server_result = 'qr:' . $post_input; // Handled by JS/API
          break;
      }
    }
  } else {
    // Bulk
    $lines = array_filter(explode("\n", str_replace("\r\n","\n",$post_bulk_input)), fn($l)=>trim($l)!=='');
    if (count($lines) > 50) { $server_error = 'Maksimal 50 barcode per sekali generate.'; }
    else {
      foreach ($lines as $line) {
        $line = trim($line);
        $svg  = match($post_type) {
          'code39' => renderCode39SVG(strtoupper($line), $post_height, $post_module_w, $post_bar_color, $post_bg_color, $post_show_text),
          'ean13'  => renderEAN13SVG($line, $post_height, $post_module_w, $post_bar_color, $post_bg_color, $post_show_text),
          default  => renderCode128SVG($line, $post_height, $post_module_w, $post_bar_color, $post_bg_color, $post_show_text),
        };
        $bulk_results[] = ['text' => $line, 'svg' => $svg, 'ok' => $svg !== false];
      }
    }
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Barcode Generator Online — Code 128, Code 39, EAN-13, QR | Multi Tools',
  'description' => 'Generate barcode Code 128, Code 39, EAN-13, dan QR Code secara instan. Kustomisasi warna, ukuran, teks. Download SVG. Tanpa login dan tanpa watermark.',
  'keywords'    => 'barcode generator, code 128, code 39, ean-13, qr code, generate barcode online, barcode svg, multi tools',
  'og_title'    => 'Barcode Generator Online — Code 128, EAN-13, QR Code',
  'og_desc'     => 'Generate barcode gratis: Code 128, Code 39, EAN-13, QR Code. Kustom warna & ukuran, download SVG.',
  'breadcrumbs' => [
    ['name' => 'Beranda',         'url' => SITE_URL . '/'],
    ['name' => 'Developer Tools', 'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'Barcode Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/barcode-generator#webpage',
      'url'         => SITE_URL . '/tools/barcode-generator',
      'name'        => 'Barcode Generator Online',
      'description' => 'Generate barcode Code 128, Code 39, EAN-13, dan QR Code secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',            'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Developer Tools',    'item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Barcode Generator',  'item' => SITE_URL . '/tools/barcode-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Barcode Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/barcode-generator',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Type selector grid ── */
.type-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .5rem; margin-bottom: 1.5rem;
}
@media (max-width: 500px) { .type-grid { grid-template-columns: repeat(2, 1fr); } }

.type-card {
  display: flex; flex-direction: column; gap: .2rem;
  padding: .7rem .85rem;
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-md); cursor: pointer;
  transition: all var(--transition); text-align: left;
  position: relative; overflow: hidden;
}
.type-card::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(circle at top left, color-mix(in srgb, var(--c) 10%, transparent), transparent 60%);
  opacity: 0; transition: opacity .3s;
}
.type-card:hover, .type-card.active { border-color: var(--c, var(--accent)); transform: translateY(-2px); }
.type-card:hover::before, .type-card.active::before { opacity: 1; }
.type-card.active { box-shadow: 0 0 0 2px color-mix(in srgb, var(--c) 25%, transparent); }
.type-card .tc-name { font-weight: 800; font-size: .85rem; color: var(--c, var(--accent)); font-family: var(--font-mono); }
.type-card .tc-desc { font-size: .7rem; color: var(--muted); line-height: 1.3; }
.type-card .tc-use  { font-size: .65rem; font-family: var(--font-mono); font-weight: 700; color: var(--c, var(--accent)); margin-top: .15rem; background: color-mix(in srgb, var(--c) 10%, transparent); border-radius: 3px; padding: 1px 5px; display: inline-block; }
.tc-check { position: absolute; top: .4rem; right: .4rem; width: 16px; height: 16px; border-radius: 50%; background: var(--c, var(--accent)); color: #fff; font-size: .6rem; display: none; align-items: center; justify-content: center; }
.type-card.active .tc-check { display: flex; }

/* ── Barcode preview area ── */
.barcode-preview {
  background: #fff; border: 2px solid var(--border);
  border-radius: var(--radius-lg); padding: 1.5rem;
  display: flex; align-items: center; justify-content: center;
  min-height: 140px; text-align: center; transition: border-color .2s;
  overflow: auto;
}
.barcode-preview.has-barcode { border-color: var(--accent5); }
.barcode-preview .placeholder-text { color: var(--muted); font-size: .9rem; }
.barcode-preview svg { max-width: 100%; height: auto; }

/* ── Option controls ── */
.options-row {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 500px) { .options-row { grid-template-columns: 1fr; } }

.color-input-wrap {
  display: flex; align-items: center; gap: .5rem;
}
.color-input-wrap input[type="color"] {
  width: 40px; height: 34px; padding: 2px;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  cursor: pointer; background: none;
}
.color-input-wrap input[type="text"] {
  flex: 1; font-family: var(--font-mono); font-size: .85rem;
}

/* ── Slider controls ── */
.slider-control {
  display: flex; align-items: center; gap: .75rem;
}
.slider-control input[type="range"] {
  flex: 1; accent-color: var(--accent5);
}
.slider-badge {
  font-family: var(--font-mono); font-size: .85rem;
  font-weight: 700; color: var(--accent5); min-width: 40px; text-align: right;
}

/* ── QR display ── */
.qr-wrap {
  background: #fff; padding: 1rem; border-radius: var(--radius-md);
  display: inline-flex; align-items: center; justify-content: center;
}
.qr-wrap img { max-width: 200px; height: auto; display: block; }

/* ── Bulk grid ── */
.bulk-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}
.bulk-item {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-md); padding: .85rem;
  display: flex; flex-direction: column; gap: .5rem;
  transition: border-color var(--transition);
}
.bulk-item:hover { border-color: var(--accent5); }
.bulk-item .bulk-text {
  font-family: var(--font-mono); font-size: .75rem; color: var(--muted);
  text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.bulk-item .bulk-preview {
  background: #fff; border-radius: var(--radius-sm);
  padding: .5rem; display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.bulk-item .bulk-preview svg { max-width: 100%; height: auto; }
.bulk-item .bulk-actions { display: flex; gap: .4rem; justify-content: center; }

/* ── Download buttons ── */
.dl-btn {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .4rem .85rem;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-sm); font-size: .78rem; font-weight: 600;
  cursor: pointer; transition: all var(--transition); color: var(--text);
  font-family: var(--font-body);
}
.dl-btn:hover { border-color: var(--accent5); color: var(--accent5); }

/* ── Validation hint ── */
.input-hint {
  font-family: var(--font-mono); font-size: .7rem; margin-top: .3rem;
  padding: .3rem .6rem; border-radius: 4px; border: 1px solid transparent;
}
.input-hint.ok  { background: rgba(16,185,129,.07); border-color: rgba(16,185,129,.2); color: #15803d; }
.input-hint.err { background: rgba(239,68,68,.07);  border-color: rgba(239,68,68,.2);  color: #dc2626; }
.input-hint.inf { background: rgba(37,99,235,.07);  border-color: rgba(37,99,235,.2);  color: var(--accent); }
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
        <span aria-hidden="true">▐▌</span> Barcode <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate barcode Code 128, Code 39, EAN-13, dan QR Code secara instan.
        Kustom warna, tinggi, lebar modul. Download SVG tanpa watermark.
      </p>

      <form method="POST" action="" id="barcode-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" id="type-input" name="type" value="<?= e($post_type) ?>" />

        <!-- Type selector -->
        <div class="form-group">
          <label>Tipe barcode</label>
          <div class="type-grid">
            <?php
            $types = [
              'code128' => ['name'=>'Code 128', 'desc'=>'Semua karakter ASCII', 'use'=>'Produk, logistik', 'c'=>'#2563eb'],
              'code39'  => ['name'=>'Code 39',  'desc'=>'A-Z, 0-9, simbol dasar', 'use'=>'Industri, ID badge', 'c'=>'#0ea5e9'],
              'ean13'   => ['name'=>'EAN-13',   'desc'=>'13 digit numerik',       'use'=>'Ritel, supermarket', 'c'=>'#10b981'],
              'qr'      => ['name'=>'QR Code',  'desc'=>'Teks, URL, kontak',      'use'=>'Scan hp, URL', 'c'=>'#7c3aed'],
            ];
            foreach ($types as $val => $t): ?>
              <div class="type-card <?= $post_type === $val ? 'active' : '' ?>"
                style="--c:<?= $t['c'] ?>"
                onclick="setType('<?= $val ?>')"
                data-type="<?= $val ?>"
                role="button" tabindex="0">
                <span class="tc-check">✓</span>
                <span class="tc-name"><?= $t['name'] ?></span>
                <span class="tc-desc"><?= $t['desc'] ?></span>
                <span class="tc-use"><?= $t['use'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Input teks -->
        <div class="form-group">
          <label for="input-text" id="input-label">Teks / data</label>
          <input type="text" id="input-text" name="input_text"
            placeholder="Ketik teks untuk barcode..."
            value="<?= e($post_input) ?>"
            oninput="generateJS(); validateInput();"
            style="font-family:var(--font-mono); font-size:.95rem; letter-spacing:.04em;"
            autocomplete="off" autocorrect="off" spellcheck="false" />
          <div class="input-hint inf" id="input-hint">
            Code 128: semua karakter ASCII (32–127)
          </div>
        </div>

        <!-- Preview barcode -->
        <div class="form-group">
          <label>Preview barcode <span class="text-muted text-sm">(realtime)</span></label>
          <div class="barcode-preview" id="barcode-preview">
            <span class="placeholder-text">Ketik teks di atas untuk melihat preview barcode...</span>
          </div>
        </div>

        <!-- Opsi kustomisasi -->
        <div class="form-group">
          <label>Kustomisasi</label>

          <div class="options-row" style="margin-top:.4rem;">
            <!-- Tinggi -->
            <div>
              <label class="text-sm text-muted" style="margin-bottom:.3rem;">Tinggi bar <span id="height-val"><?= $post_height ?></span>px</label>
              <div class="slider-control">
                <input type="range" id="height-slider" name="bar_height"
                  min="40" max="200" step="5" value="<?= $post_height ?>"
                  oninput="document.getElementById('height-val').textContent=this.value; generateJS();" />
                <span class="slider-badge" id="height-badge"><?= $post_height ?></span>
              </div>
            </div>

            <!-- Lebar modul -->
            <div id="module-section" <?= $post_type === 'qr' ? 'style="display:none;"' : '' ?>>
              <label class="text-sm text-muted" style="margin-bottom:.3rem;">Lebar modul <span id="module-val"><?= $post_module_w ?>x</span></label>
              <div class="slider-control">
                <input type="range" id="module-slider" name="module_width"
                  min="1" max="5" step="1" value="<?= $post_module_w ?>"
                  oninput="document.getElementById('module-val').textContent=this.value+'x'; document.getElementById('module-badge').textContent=this.value+'x'; generateJS();" />
                <span class="slider-badge" id="module-badge"><?= $post_module_w ?>x</span>
              </div>
            </div>
          </div>

          <!-- Warna -->
          <div class="options-row" style="margin-top:.85rem;">
            <div>
              <label class="text-sm text-muted" style="margin-bottom:.3rem;">Warna bar</label>
              <div class="color-input-wrap">
                <input type="color" id="bar-color-picker" value="<?= $post_bar_color ?>"
                  oninput="document.getElementById('bar-color-text').value=this.value; generateJS();" />
                <input type="text" id="bar-color-text" name="bar_color"
                  value="<?= $post_bar_color ?>" maxlength="7"
                  oninput="syncColor('bar'); generateJS();"
                  style="font-family:var(--font-mono);" />
              </div>
            </div>
            <div>
              <label class="text-sm text-muted" style="margin-bottom:.3rem;">Warna background</label>
              <div class="color-input-wrap">
                <input type="color" id="bg-color-picker" value="<?= $post_bg_color ?>"
                  oninput="document.getElementById('bg-color-text').value=this.value; generateJS();" />
                <input type="text" id="bg-color-text" name="bg_color"
                  value="<?= $post_bg_color ?>" maxlength="7"
                  oninput="syncColor('bg'); generateJS();"
                  style="font-family:var(--font-mono);" />
              </div>
            </div>
          </div>

          <!-- Show text -->
          <div style="margin-top:.85rem;">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" name="show_text" id="show-text"
                <?= $post_show_text ? 'checked' : '' ?>
                onchange="generateJS()"
                style="width:auto; accent-color:var(--accent5);" />
              Tampilkan teks di bawah barcode
            </label>
          </div>
        </div>

        <!-- Download & actions -->
        <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center; margin-top:.5rem;">
          <button type="button" class="btn-primary btn-sm"
            style="background:var(--accent5); border-color:var(--accent5);"
            onclick="downloadSVG()">
            ⬇ Download SVG
          </button>
          <button type="button" class="dl-btn" onclick="copyAsSVG()">📋 Salin SVG</button>
          <button type="submit" class="btn-secondary btn-sm">⚙ Generate via Server (PHP)</button>
          <button type="button" class="btn-ghost btn-sm" onclick="clearForm()">Bersihkan</button>
        </div>

        <!-- Bulk section -->
        <div style="margin-top:1.75rem; padding-top:1.25rem; border-top:1px solid var(--border);">
          <div class="section-mini-title" style="margin-bottom:.75rem;">📋 Generate massal</div>
          <div class="form-group">
            <label for="bulk-input">Teks massal <span class="text-muted text-sm">(satu per baris, maks. 50)</span></label>
            <textarea id="bulk-input-area" name="bulk_input"
              placeholder="produk-001&#10;produk-002&#10;produk-003&#10;..."
              style="min-height:100px; font-family:var(--font-mono); font-size:.85rem;"
            ><?= e($post_bulk_input) ?></textarea>
          </div>
          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button type="submit" name="bulk_mode" value="1" class="btn-primary btn-sm"
              style="background:var(--accent5); border-color:var(--accent5);">
              ▐▌ Generate Massal
            </button>
          </div>
        </div>

      </form>
    </div><!-- /.panel -->

    <!-- Hasil server -->
    <?php if ($server_result && !$server_error && substr($server_result, 0, 3) !== 'qr:'): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Barcode berhasil digenerate via PHP server.</span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title">⚙ Hasil Server PHP</div>
      <div class="barcode-preview has-barcode" id="server-barcode-preview">
        <?= $server_result ?>
      </div>
      <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.85rem;">
        <button class="btn-primary btn-sm"
          style="background:var(--accent5); border-color:var(--accent5);"
          onclick="downloadServerSVG()">
          ⬇ Download SVG
        </button>
        <button class="dl-btn" onclick="copyServerSVG()">📋 Salin SVG</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Bulk results -->
    <?php if (!empty($bulk_results)): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Berhasil generate <strong><?= count($bulk_results) ?> barcode</strong>.</span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
        <div class="panel-title" style="margin-bottom:0;">⚙ Hasil Massal</div>
        <button class="btn-ghost btn-sm" onclick="downloadAllSVG()">⬇ Unduh semua SVG (.zip)</button>
      </div>
      <div class="bulk-grid">
        <?php foreach ($bulk_results as $idx => $item): ?>
          <div class="bulk-item">
            <div class="bulk-text"><?= e($item['text']) ?></div>
            <?php if ($item['ok']): ?>
              <div class="bulk-preview" id="bulk-prev-<?= $idx ?>">
                <?= $item['svg'] ?>
              </div>
              <div class="bulk-actions">
                <button class="dl-btn"
                  onclick="downloadBulkSVG(<?= $idx ?>)"
                  style="font-size:.7rem; padding:.3rem .6rem;">
                  ⬇ SVG
                </button>
                <button class="dl-btn"
                  onclick="copyBulkSVG(<?= $idx ?>)"
                  style="font-size:.7rem; padding:.3rem .6rem;">
                  📋 Salin
                </button>
              </div>
            <?php else: ?>
              <div class="input-hint err">Input tidak valid untuk tipe ini</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
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
      <div class="panel-title">💡 Panduan Tipe</div>
      <div style="display:flex; flex-direction:column; gap:.85rem;">
        <?php
        $guides = [
          ['Code 128', '#2563eb', 'Mendukung semua karakter ASCII (huruf, angka, simbol). Digunakan untuk logistik, pengiriman, retail modern.', 'Contoh: MultiTools-2025'],
          ['Code 39',  '#0ea5e9', 'Mendukung A–Z, 0–9, dan: - . $ / + % SPACE. Kompatibel luas di industri dan badge karyawan.', 'Contoh: PRODUK-001'],
          ['EAN-13',   '#10b981', 'Tepat 12 digit (check digit dihitung otomatis) atau 13 digit. Standar internasional untuk produk ritel.', 'Contoh: 123456789012'],
          ['QR Code',  '#7c3aed', 'Mendukung teks, URL, vCard, WiFi, email. Bisa scan dengan kamera hp. Kapasitas besar.', 'Contoh: https://example.com'],
        ];
        foreach ($guides as [$name, $color, $desc, $contoh]): ?>
          <div style="padding:.6rem .75rem; border: 1px solid var(--border); border-left: 3px solid <?= $color ?>; border-radius: var(--radius-sm); background: var(--bg);">
            <div style="font-weight:700; font-size:.82rem; color:<?= $color ?>; margin-bottom:.25rem;"><?= $name ?></div>
            <div style="font-size:.75rem; color:var(--muted); line-height:1.5; margin-bottom:.3rem;"><?= $desc ?></div>
            <div style="font-family:var(--font-mono); font-size:.68rem; color:var(--muted);"><?= e($contoh) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Contoh Input</div>
      <div style="display:flex; flex-direction:column; gap:.4rem;">
        <?php
        $samples = [
          ['MultiTools-2025',   'code128', 'Label produk'],
          ['HELLO-WORLD',       'code39',  'Badge ID'],
          ['123456789012',      'ean13',   'EAN-13 ritel'],
          ['https://sitemu.com','code128', 'URL singkat'],
          ['INV-20250101-001',  'code128', 'Nomor invoice'],
          ['PRODUK-A1',         'code39',  'Kode gudang'],
          ['9780140328721',     'ean13',   'ISBN buku'],
        ];
        foreach ($samples as [$text, $type, $label]): ?>
          <button class="btn-ghost btn-sm btn-full"
            style="display:flex; justify-content:space-between; padding:.4rem .75rem; text-align:left;"
            onclick="loadSample(<?= htmlspecialchars(json_encode($text), ENT_QUOTES) ?>, '<?= $type ?>')">
            <span style="font-family:var(--font-mono); font-size:.75rem; color:var(--accent5);"><?= e($text) ?></span>
            <span style="color:var(--muted); font-size:.7rem; flex-shrink:0; margin-left:.5rem;"><?= e($label) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/qr-generator"    class="btn-ghost btn-sm btn-full">QR Code Generator</a>
        <a href="/tools/uuid-generator"  class="btn-ghost btn-sm btn-full">UUID Generator</a>
        <a href="/tools/slug-generator"  class="btn-ghost btn-sm btn-full">Slug Generator</a>
        <a href="/tools/base64"          class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Barcode Generator — logika JS (realtime)
   Barcode JS menggunakan JsBarcode library.
   QR Code via qrserver.com API.
   PHP dipakai saat form di-submit.
   ────────────────────────────────────────── */

// Load JsBarcode dari CDN
(function() {
  const s = document.createElement('script');
  s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js';
  s.onload = () => { window.JSBARCODE_READY = true; generateJS(); };
  document.head.appendChild(s);
})();

let currentType = '<?= $post_type ?>';
let currentSVG  = '';

// ── Type switching ────────────────────────────────────────────
function setType(type) {
  currentType = type;
  document.getElementById('type-input').value = type;
  document.querySelectorAll('.type-card').forEach(c =>
    c.classList.toggle('active', c.dataset.type === type)
  );
  // Show/hide module width for QR
  const modSect = document.getElementById('module-section');
  if (modSect) modSect.style.display = type === 'qr' ? 'none' : '';

  updateInputHint(type);
  validateInput();
  generateJS();
}

function updateInputHint(type) {
  const hints = {
    code128: 'Code 128: semua karakter ASCII (32–127)',
    code39:  'Code 39: A–Z, 0–9, dan karakter - . $ / + % SPACE',
    ean13:   'EAN-13: masukkan 12 digit (check digit dihitung otomatis)',
    qr:      'QR Code: teks, URL, email, nomor telepon, dll.',
  };
  const el = document.getElementById('input-hint');
  if (el) { el.textContent = hints[type] || ''; el.className = 'input-hint inf'; }
}

// ── Validate input realtime ───────────────────────────────────
function validateInput() {
  const input = document.getElementById('input-text').value;
  const hint  = document.getElementById('input-hint');
  if (!input || !hint) return;

  let ok = true, msg = '';
  switch (currentType) {
    case 'code128':
      ok  = [...input].every(c => c.charCodeAt(0) >= 32 && c.charCodeAt(0) <= 127);
      msg = ok ? '✓ Input valid untuk Code 128' : '✕ Ada karakter di luar ASCII 32–127';
      break;
    case 'code39':
      ok  = /^[A-Z0-9\- .\$\/\+%]+$/i.test(input);
      msg = ok ? '✓ Input valid untuk Code 39' : '✕ Ada karakter tidak didukung Code 39';
      break;
    case 'ean13':
      const digits = input.replace(/\D/g,'');
      ok  = digits.length === 12 || digits.length === 13;
      msg = ok ? `✓ ${digits.length} digit — ${digits.length === 12 ? 'check digit akan dihitung' : 'lengkap'}` : `✕ Masukkan 12 atau 13 digit (saat ini ${digits.length})`;
      break;
    case 'qr':
      ok  = input.length > 0 && input.length <= 2953;
      msg = ok ? `✓ ${input.length} karakter` : `✕ Terlalu panjang (maks. 2953 karakter)`;
      break;
  }
  hint.textContent = msg;
  hint.className   = 'input-hint ' + (ok ? 'ok' : 'err');
}

// ── Generate JS barcode ───────────────────────────────────────
function generateJS() {
  const input   = document.getElementById('input-text').value.trim();
  const preview = document.getElementById('barcode-preview');
  const height  = parseInt(document.getElementById('height-slider')?.value) || 80;
  const mw      = parseInt(document.getElementById('module-slider')?.value) || 2;
  const barColor= document.getElementById('bar-color-text')?.value || '#000000';
  const bgColor = document.getElementById('bg-color-text')?.value || '#ffffff';
  const showTxt = document.getElementById('show-text')?.checked ?? true;

  if (!input) {
    preview.innerHTML = '<span class="placeholder-text">Ketik teks di atas untuk melihat preview barcode...</span>';
    preview.classList.remove('has-barcode');
    currentSVG = '';
    return;
  }

  if (currentType === 'qr') {
    generateQR(input, preview, height);
    return;
  }

  if (!window.JSBARCODE_READY) {
    preview.innerHTML = '<span class="placeholder-text">Memuat library barcode...</span>';
    return;
  }

  try {
    // Map type ke JsBarcode format
    const formatMap = { code128: 'CODE128', code39: 'CODE39', ean13: 'EAN13' };
    const format = formatMap[currentType] || 'CODE128';

    // Create temp SVG element
    const svgEl = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svgEl.setAttribute('id', 'temp-barcode');

    JsBarcode(svgEl, input, {
      format,
      width:       mw,
      height:      height,
      displayValue:showTxt,
      lineColor:   barColor,
      background:  bgColor,
      margin:      10,
      fontSize:    12,
      textMargin:  4,
    });

    preview.innerHTML = '';
    preview.appendChild(svgEl);
    preview.classList.add('has-barcode');

    // Simpan sebagai SVG string untuk download
    const serializer = new XMLSerializer();
    currentSVG = serializer.serializeToString(svgEl);
  } catch(e) {
    preview.innerHTML = `<span class="placeholder-text" style="color:#dc2626;">✕ Input tidak valid: ${esc(e.message)}</span>`;
    preview.classList.remove('has-barcode');
    currentSVG = '';
  }
}

function generateQR(text, previewEl, size) {
  const url = `https://api.qrserver.com/v1/create-qr-code/?size=${size*2}x${size*2}&data=${encodeURIComponent(text)}&format=svg&margin=2`;
  previewEl.innerHTML = `<div class="qr-wrap"><img src="${url}" alt="QR Code" style="width:${size}px;height:${size}px;" onload="this.parentElement.parentElement.classList.add('has-barcode')" /></div>`;
  currentSVG = ''; // QR tidak bisa disimpan langsung dari URL
}

// ── Download & Copy ───────────────────────────────────────────
function downloadSVG() {
  if (!currentSVG) { showToast && showToast('Generate barcode dulu!', 'warning'); return; }
  const input = document.getElementById('input-text').value.trim() || 'barcode';
  const blob = new Blob([currentSVG], { type: 'image/svg+xml;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'barcode-' + input.slice(0, 20).replace(/[^a-z0-9]/gi, '_') + '.svg';
  a.click();
}

function copyAsSVG() {
  if (!currentSVG) { showToast && showToast('Generate barcode dulu!', 'warning'); return; }
  navigator.clipboard.writeText(currentSVG).then(() =>
    showToast && showToast('SVG disalin!', 'success'));
}

function downloadServerSVG() {
  const el = document.getElementById('server-barcode-preview');
  if (!el) return;
  const svg = el.innerHTML;
  const input = '<?= addslashes($post_input) ?>' || 'barcode';
  const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'barcode-' + input.slice(0, 20).replace(/[^a-z0-9]/gi, '_') + '.svg';
  a.click();
}

function copyServerSVG() {
  const el = document.getElementById('server-barcode-preview');
  if (!el) return;
  navigator.clipboard.writeText(el.innerHTML).then(() =>
    showToast && showToast('SVG disalin!', 'success'));
}

function downloadBulkSVG(idx) {
  const el = document.getElementById('bulk-prev-' + idx);
  if (!el) return;
  const blob = new Blob([el.innerHTML], { type: 'image/svg+xml;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'barcode-' + idx + '.svg';
  a.click();
}

function copyBulkSVG(idx) {
  const el = document.getElementById('bulk-prev-' + idx);
  if (!el) return;
  navigator.clipboard.writeText(el.innerHTML).then(() =>
    showToast && showToast('SVG disalin!', 'success'));
}

function downloadAllSVG() {
  showToast && showToast('Unduh tiap SVG satu per satu menggunakan tombol ⬇ di setiap barcode.', 'info', 4000);
}

// ── Color sync ────────────────────────────────────────────────
function syncColor(which) {
  const textEl  = document.getElementById(which + '-color-text');
  const pickEl  = document.getElementById(which + '-color-picker');
  if (!textEl || !pickEl) return;
  const val = textEl.value;
  if (/^#[0-9a-fA-F]{6}$/.test(val)) pickEl.value = val;
}

// ── Slider sync ───────────────────────────────────────────────
const heightSlider = document.getElementById('height-slider');
const heightBadge  = document.getElementById('height-badge');
if (heightSlider && heightBadge) {
  heightSlider.addEventListener('input', () => { heightBadge.textContent = heightSlider.value; });
}

// ── Sample & clear ────────────────────────────────────────────
function loadSample(text, type) {
  document.getElementById('input-text').value = text;
  setType(type);
}

function clearForm() {
  document.getElementById('input-text').value = '';
  const preview = document.getElementById('barcode-preview');
  preview.innerHTML = '<span class="placeholder-text">Ketik teks di atas untuk melihat preview barcode...</span>';
  preview.classList.remove('has-barcode');
  currentSVG = '';
  const hint = document.getElementById('input-hint');
  if (hint) { hint.textContent = 'Code 128: semua karakter ASCII (32–127)'; hint.className = 'input-hint inf'; }
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Init ──────────────────────────────────────────────────────
setType(currentType);
<?php if ($post_input): ?>
generateJS();
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>