<?php
require '../../includes/config.php';
/**
 * Multi Tools — Slug Generator
 * Konversi judul / teks menjadi URL slug yang bersih dan SEO-friendly.
 * Mendukung berbagai bahasa, separator custom, dan generate massal.
 * ============================================================ */

// ── Tabel transliterasi karakter non-ASCII ───────────────────
const CHAR_MAP = [
  'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae',
  'ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i',
  'î'=>'i','ï'=>'i','ð'=>'d','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o',
  'õ'=>'o','ö'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
  'ý'=>'y','þ'=>'th','ß'=>'ss','ÿ'=>'y',
  'À'=>'a','Á'=>'a','Â'=>'a','Ã'=>'a','Ä'=>'a','Å'=>'a','Æ'=>'ae',
  'Ç'=>'c','È'=>'e','É'=>'e','Ê'=>'e','Ë'=>'e','Ì'=>'i','Í'=>'i',
  'Î'=>'i','Ï'=>'i','Ð'=>'d','Ñ'=>'n','Ò'=>'o','Ó'=>'o','Ô'=>'o',
  'Õ'=>'o','Ö'=>'o','Ø'=>'o','Ù'=>'u','Ú'=>'u','Û'=>'u','Ü'=>'u',
  'Ý'=>'y','Þ'=>'th','Ÿ'=>'y',
  '&'=>'and','@'=>'at','©'=>'c','®'=>'r','™'=>'tm',
  '–'=>'-','—'=>'-',"'"=>'','"'=>'','…'=>'...',
  '#'=>'','$'=>'','%'=>'','*'=>'','_'=>'-',
  '/'=>'-','\\'=>'-','|'=>'-','+'=>'and',
  '='=>'','~'=>'','`'=>'','^'=>'','°'=>'',
  '!'=>'','?'=>'','.'=>'',','=>'','('=>'',')'=>'',
];

/**
 * Konversi satu teks menjadi URL slug.
 */
function makeSlug(string $text, string $sep = '-', int $maxLen = 0): string {
  $slug = strtr($text, CHAR_MAP);
  $slug = mb_strtolower($slug, 'UTF-8');
  $slug = preg_replace('/[^a-z0-9\-]/', $sep, $slug);
  $escaped = preg_quote($sep, '/');
  $slug = preg_replace('/' . $escaped . '+/', $sep, $slug);
  $slug = trim($slug, $sep);
  if ($maxLen > 0 && mb_strlen($slug) > $maxLen) {
    $slug = mb_substr($slug, 0, $maxLen);
    $slug = trim($slug, $sep);
  }
  return $slug;
}

// ── Handle POST ──────────────────────────────────────────────
$server_results = [];
$server_error   = '';
$post_input     = '';
$post_sep       = '-';
$post_maxlen    = 0;
$post_prefix    = '';
$post_suffix    = '';
$post_bulk      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_input  = $_POST['input_text'] ?? '';
  $post_sep    = in_array($_POST['separator'] ?? '-', ['-','_','.','/' ])
                   ? $_POST['separator'] : '-';
  $post_maxlen = max(0, min(200, (int)($_POST['max_length'] ?? 0)));
  $post_prefix = preg_replace('/[^a-z0-9\-_]/', '', mb_strtolower($_POST['prefix'] ?? ''));
  $post_suffix = preg_replace('/[^a-z0-9\-_]/', '', mb_strtolower($_POST['suffix'] ?? ''));
  $post_bulk   = isset($_POST['bulk_mode']) && $_POST['bulk_mode'] === '1';

  if (trim($post_input) === '') {
    $server_error = 'Teks input tidak boleh kosong.';
  } else {
    $build = function(string $line) use ($post_sep, $post_maxlen, $post_prefix, $post_suffix): string {
      $slug = makeSlug(trim($line), $post_sep, $post_maxlen);
      if ($post_prefix) $slug = $post_prefix . $post_sep . ltrim($slug, $post_sep);
      if ($post_suffix) $slug = rtrim($slug, $post_sep) . $post_sep . $post_suffix;
      return trim($slug, $post_sep);
    };

    if ($post_bulk) {
      $lines = array_filter(
        explode("\n", str_replace("\r\n", "\n", $post_input)),
        fn($l) => trim($l) !== ''
      );
      foreach ($lines as $line) {
        $server_results[] = ['input' => trim($line), 'slug' => $build($line)];
      }
    } else {
      $server_results[] = ['input' => trim($post_input), 'slug' => $build($post_input)];
    }
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Slug Generator Online — Buat URL Slug SEO-Friendly | Multi Tools',
  'description' => 'Konversi judul atau teks menjadi URL slug yang bersih dan SEO-friendly secara instan. Dukung separator custom, prefix/suffix, panjang maksimal, dan mode massal.',
  'keywords'    => 'slug generator, url slug, seo friendly url, permalink generator, slugify, buat slug, slug otomatis, multi tools',
  'og_title'    => 'Slug Generator Online — URL Slug SEO-Friendly Instan',
  'og_desc'     => 'Buat URL slug dari judul atau teks secara instan. Custom separator, prefix, suffix, max length, dan bulk mode.',
  'breadcrumbs' => [
    ['name' => 'Beranda',        'url' => SITE_URL . '/'],
    ['name' => 'Text Tools',     'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Slug Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/slug-generator#webpage',
      'url'         => SITE_URL . '/tools/slug-generator',
      'name'        => 'Slug Generator Online',
      'description' => 'Konversi judul atau teks menjadi URL slug yang bersih dan SEO-friendly.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',        'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',      'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Slug Generator', 'item' => SITE_URL . '/tools/slug-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Slug Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/slug-generator',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
.slug-output-single {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .65rem .9rem;
  font-family: var(--font-mono);
  font-size: .9rem;
  color: var(--accent);
  word-break: break-all;
  min-height: 44px;
}
.slug-output-single .slug-val { flex: 1; }
.slug-output-single .slug-len {
  font-size: .7rem;
  color: var(--muted);
  white-space: nowrap;
}
.url-preview {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .6rem .9rem;
  font-family: var(--font-mono);
  font-size: .8rem;
  word-break: break-all;
  line-height: 1.5;
}
.url-preview .url-base { color: var(--muted); }
.url-preview .url-slug  { color: var(--accent); font-weight: 700; }
.bulk-table-wrap {
  max-height: 340px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.bulk-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .82rem;
}
.bulk-table th {
  position: sticky; top: 0;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: .5rem .9rem;
  text-align: left;
  font-family: var(--font-mono);
  font-size: .7rem;
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
.bulk-table tr:hover td { background: rgba(37,99,235,.04); }
.bulk-table .td-slug { font-family: var(--font-mono); color: var(--accent); word-break: break-all; }
.bulk-table .td-input { color: var(--muted); }
.bulk-table .td-copy { white-space: nowrap; text-align: right; }
.bulk-copy-btn {
  padding: .2rem .55rem;
  font-size: .68rem;
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
.sep-pills { display: flex; gap: .4rem; flex-wrap: wrap; }
.sep-pill {
  padding: .3rem .75rem;
  border: 1px solid var(--border);
  border-radius: 99px;
  font-family: var(--font-mono);
  font-size: .8rem;
  cursor: pointer;
  background: var(--surface);
  color: var(--muted);
  transition: all var(--transition);
}
.sep-pill.active, .sep-pill:hover {
  border-color: var(--accent);
  color: var(--accent);
  background: rgba(37,99,235,.07);
}
.dup-warn { color: var(--accent4); font-size: .7rem; margin-left: .4rem; }
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
        <span aria-hidden="true">🔗</span> Slug <span>Generator</span>
      </div>
      <p class="page-lead">
        Konversi judul atau teks menjadi URL slug yang bersih dan SEO-friendly secara instan.
        Mendukung karakter non-ASCII, separator custom, prefix/suffix, dan mode massal.
      </p>

      <form method="POST" action="" id="slug-form" style="margin-top:1.5rem;" novalidate>

        <!-- Mode toggle -->
        <div class="form-group">
          <label>Mode</label>
          <div style="display:flex; gap:1rem;">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="radio" name="mode_toggle" value="single" id="mode-single"
                <?= !$post_bulk ? 'checked' : '' ?>
                onchange="toggleMode('single')"
                style="width:auto; accent-color:var(--accent);" />
              Satu judul
            </label>
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="radio" name="mode_toggle" value="bulk" id="mode-bulk"
                <?= $post_bulk ? 'checked' : '' ?>
                onchange="toggleMode('bulk')"
                style="width:auto; accent-color:var(--accent);" />
              Massal (satu baris = satu slug)
            </label>
          </div>
          <input type="hidden" id="bulk_mode" name="bulk_mode" value="<?= $post_bulk ? '1' : '' ?>" />
        </div>

        <!-- Input -->
        <div class="form-group">
          <label for="input-text" id="input-label">Judul / teks</label>
          <textarea
            id="input-text"
            name="input_text"
            placeholder="Contoh: Cara Membuat Website dengan PHP dan JavaScript"
            oninput="generateJS()"
            style="min-height:100px;"
          ><?= e($post_input) ?></textarea>
          <div id="input-hint" class="text-xs text-muted" style="margin-top:.3rem;">
            Mode satu judul aktif — ketik judul artikel, nama produk, kategori, dll.
          </div>
        </div>

        <!-- Separator -->
        <div class="form-group">
          <label>Separator (pemisah kata)</label>
          <div class="sep-pills" id="sep-pills">
            <?php
            $seps = ['-' => 'Hyphen  —', '_' => 'Underscore _', '.' => 'Dot .', '/' => 'Slash /'];
            foreach ($seps as $val => $lbl):
            ?>
              <button type="button"
                class="sep-pill <?= $post_sep === $val ? 'active' : '' ?>"
                onclick="setSep('<?= $val ?>')"
                data-sep="<?= $val ?>">
                <?= $lbl ?>
              </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="separator" name="separator" value="<?= e($post_sep) ?>" />
        </div>

        <!-- Prefix & Suffix -->
        <div class="form-row">
          <div class="form-group">
            <label for="prefix">Prefix <span class="text-muted text-sm">(opsional)</span></label>
            <input type="text" id="prefix" name="prefix"
              placeholder="Contoh: blog, 2025"
              value="<?= e($post_prefix) ?>"
              oninput="generateJS()"
              maxlength="40" />
          </div>
          <div class="form-group">
            <label for="suffix">Suffix <span class="text-muted text-sm">(opsional)</span></label>
            <input type="text" id="suffix" name="suffix"
              placeholder="Contoh: id, v2"
              value="<?= e($post_suffix) ?>"
              oninput="generateJS()"
              maxlength="40" />
          </div>
        </div>

        <div class="form-group">
          <label for="max_length">Panjang maksimal slug <span class="text-muted text-sm">(0 = tidak dibatasi)</span></label>
          <input type="number" id="max_length" name="max_length"
            min="0" max="200"
            value="<?= (int)$post_maxlen ?>"
            oninput="generateJS()"
            style="max-width:160px;" />
        </div>

        <!-- Output: mode tunggal -->
        <div id="output-single" <?= $post_bulk ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>Slug yang dihasilkan</label>
            <div class="slug-output-single" id="slug-output" aria-live="polite">
              <span class="slug-val" id="slug-val">—</span>
              <span class="slug-len" id="slug-len"></span>
            </div>
          </div>

          <div class="form-group">
            <label>Preview URL</label>
            <div class="url-preview">
              <span class="url-base">https://sitemu.com/blog/</span><span class="url-slug" id="url-slug-preview">—</span>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
            <button type="button" class="btn-primary btn-sm" onclick="copySingle()">
              📋 Salin Slug
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="copyUrl()">
              🌐 Salin URL lengkap
            </button>
            <button type="submit" class="btn-secondary btn-sm">
              ⚙ Generate via Server (PHP)
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">
              Bersihkan
            </button>
          </div>
        </div>

        <!-- Output: mode massal -->
        <div id="output-bulk" <?= !$post_bulk ? 'style="display:none;"' : '' ?>>
          <div class="form-group">
            <label>Hasil slug massal</label>
            <div class="bulk-table-wrap" id="bulk-table-wrap">
              <div style="padding:1.5rem; text-align:center; color:var(--muted); font-size:.85rem;">
                Ketik beberapa judul (satu per baris) untuk melihat hasilnya.
              </div>
            </div>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
            <button type="button" class="btn-primary btn-sm" onclick="copyAllSlugs()">
              📋 Salin semua slug
            </button>
            <button type="submit" class="btn-secondary btn-sm">
              ⚙ Generate via Server (PHP)
            </button>
            <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">
              Bersihkan
            </button>
          </div>
        </div>

      </form>
    </div><!-- /.panel -->

    <?php if (!empty($server_results)): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Berhasil generate <strong><?= count($server_results) ?> slug</strong> via PHP server.</span>
    </div>

    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title">⚙ Hasil Server PHP</div>
      <?php if (count($server_results) === 1): ?>
        <div class="form-group">
          <label>Slug</label>
          <div class="copy-wrap">
            <div class="result-box success" id="server-slug-out"><?= e($server_results[0]['slug']) ?></div>
            <button class="copy-btn" data-copy-target="server-slug-out">SALIN</button>
          </div>
        </div>
        <div class="form-group">
          <label>Preview URL</label>
          <div class="url-preview">
            <span class="url-base">https://sitemu.com/blog/</span>
            <span class="url-slug"><?= e($server_results[0]['slug']) ?></span>
          </div>
        </div>
      <?php else: ?>
        <div class="bulk-table-wrap">
          <table class="bulk-table">
            <thead>
              <tr><th>#</th><th>Input</th><th>Slug</th><th>Len</th><th></th></tr>
            </thead>
            <tbody>
              <?php
              $seen_slugs = [];
              foreach ($server_results as $idx => $row):
                $is_dup = in_array($row['slug'], $seen_slugs) && $row['slug'] !== '';
                if ($row['slug'] !== '') $seen_slugs[] = $row['slug'];
              ?>
              <tr>
                <td class="text-muted text-xs"><?= $idx + 1 ?></td>
                <td class="td-input"><?= e($row['input']) ?></td>
                <td class="td-slug">
                  <?= e($row['slug']) ?>
                  <?php if ($is_dup): ?>
                    <span class="dup-warn" title="Slug duplikat">⚠ duplikat</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted text-xs"><?= mb_strlen($row['slug']) ?></td>
                <td class="td-copy">
                  <button class="bulk-copy-btn"
                    onclick="copyText(<?= htmlspecialchars(json_encode($row['slug']), ENT_QUOTES) ?>, this)">
                    SALIN
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
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
      <div class="panel-title">💡 Tips SEO</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Gunakan <strong>tanda hubung</strong> (-) — standar Google</li>
        <li>Slug ideal: <strong>3–5 kata</strong> kunci utama</li>
        <li>Hindari kata sambung yang tidak perlu</li>
        <li>Gunakan <strong>max length ≤ 75</strong> karakter</li>
        <li>Tambah <strong>prefix tahun</strong> untuk konten berita</li>
        <li>Karakter non-ASCII (é, ñ, ü) dikonversi otomatis</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Contoh Input</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $samples = [
          'Cara Membuat Website dengan PHP & JavaScript',
          'Panduan SEO 2025 untuk Pemula — Lengkap!',
          'Rekomendasi Laptop Gaming Terbaik di Bawah 10 Juta',
          'Résumé & Portfolio: Désign Créatif',
          '10 Tips Meningkatkan Produktivitas Kerja Remote',
          'Node.js vs PHP: Mana yang Lebih Baik?',
        ];
        foreach ($samples as $s): ?>
          <button
            class="btn-ghost btn-sm btn-full"
            type="button"
            onclick="loadSample(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
            style="text-align:left; white-space:normal; height:auto; padding:.45rem .9rem;">
            <?= e($s) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📖 Aturan Slug</div>
      <div style="display:flex; flex-direction:column; gap:.4rem; font-size:.78rem;">
        <?php
        $rules = [
          ['✓', '#16a34a', 'Huruf kecil semua'],
          ['✓', '#16a34a', 'Angka 0–9 dipertahankan'],
          ['✓', '#16a34a', 'Spasi → separator'],
          ['✓', '#16a34a', 'Karakter non-ASCII ditransliterasi'],
          ['✕', '#dc2626', 'Tidak ada spasi'],
          ['✕', '#dc2626', 'Tidak ada karakter khusus'],
          ['✕', '#dc2626', 'Tidak ada huruf kapital'],
          ['✕', '#dc2626', 'Tidak ada separator berulang'],
        ];
        foreach ($rules as [$sym, $color, $text]): ?>
          <div style="display:flex; gap:.5rem; align-items:flex-start;">
            <span style="color:<?= $color ?>; font-weight:700; flex-shrink:0;"><?= $sym ?></span>
            <span style="color:var(--muted);"><?= e($text) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/case-converter"    class="btn-ghost btn-sm btn-full">Case Converter</a>
        <a href="/tools/word-counter"      class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/remove-duplicates" class="btn-ghost btn-sm btn-full">Remove Duplicates</a>
        <a href="/tools/lorem-ipsum"       class="btn-ghost btn-sm btn-full">Lorem Ipsum Generator</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Slug Generator — logika JS (realtime)
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

const CHAR_MAP_JS = {
  'à':'a','á':'a','â':'a','ã':'a','ä':'a','å':'a','æ':'ae',
  'ç':'c','è':'e','é':'e','ê':'e','ë':'e','ì':'i','í':'i',
  'î':'i','ï':'i','ñ':'n','ò':'o','ó':'o','ô':'o',
  'õ':'o','ö':'o','ø':'o','ù':'u','ú':'u','û':'u','ü':'u',
  'ý':'y','ß':'ss','ÿ':'y',
  'À':'a','Á':'a','Â':'a','Ä':'a','Å':'a','Æ':'ae',
  'Ç':'c','È':'e','É':'e','Ê':'e','Ñ':'n','Ö':'o','Ø':'o',
  'Ü':'u','Ý':'y',
  '&':'and','@':'at','\u2013':'-','\u2014':'-',
  '\u2018':'','\u2019':'','\u201c':'','\u201d':'',
  '_':'-','/':'-','|':'-','+':'and',
};

function slugify(text, sep, maxLen) {
  if (!text) return '';
  sep    = sep    || '-';
  maxLen = maxLen || 0;

  let slug = text.split('').map(c => CHAR_MAP_JS[c] !== undefined ? CHAR_MAP_JS[c] : c).join('');
  slug = slug.toLowerCase();
  slug = slug.replace(/[^a-z0-9]/g, sep);

  const sepEsc = sep.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  slug = slug.replace(new RegExp(sepEsc + '+', 'g'), sep);
  slug = slug.replace(new RegExp('^' + sepEsc + '+|' + sepEsc + '+$', 'g'), '');

  if (maxLen > 0 && slug.length > maxLen) {
    slug = slug.slice(0, maxLen).replace(new RegExp(sepEsc + '+$'), '');
  }
  return slug;
}

function buildSlug(text) {
  const sep    = document.getElementById('separator').value || '-';
  const maxLen = parseInt(document.getElementById('max_length').value) || 0;
  const pRaw   = document.getElementById('prefix').value.trim();
  const sRaw   = document.getElementById('suffix').value.trim();
  const prefix = slugify(pRaw, sep);
  const suffix = slugify(sRaw, sep);

  let slug = slugify(text, sep, maxLen);

  const sepEsc = sep.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  if (prefix) slug = prefix + sep + slug.replace(new RegExp('^' + sepEsc + '+'), '');
  if (suffix) slug = slug.replace(new RegExp(sepEsc + '+$'), '') + sep + suffix;
  slug = slug.replace(new RegExp('^' + sepEsc + '+|' + sepEsc + '+$', 'g'), '');

  return slug;
}

let currentMode = <?= $post_bulk ? "'bulk'" : "'single'" ?>;

function toggleMode(mode) {
  currentMode = mode;
  document.getElementById('bulk_mode').value = mode === 'bulk' ? '1' : '';

  const isBulk = mode === 'bulk';
  document.getElementById('output-single').style.display = isBulk ? 'none' : 'block';
  document.getElementById('output-bulk').style.display   = isBulk ? 'block' : 'none';

  const ta    = document.getElementById('input-text');
  const hint  = document.getElementById('input-hint');
  const label = document.getElementById('input-label');

  if (isBulk) {
    ta.placeholder     = 'Satu judul per baris...\n\nCara Membuat Website PHP\nPanduan SEO 2025\nRekomendasi Laptop Gaming';
    ta.style.minHeight = '180px';
    hint.textContent   = 'Mode massal — setiap baris dikonversi menjadi satu slug.';
    label.textContent  = 'Judul / teks (satu per baris)';
  } else {
    ta.placeholder     = 'Contoh: Cara Membuat Website dengan PHP dan JavaScript';
    ta.style.minHeight = '100px';
    hint.textContent   = 'Mode satu judul aktif — ketik judul artikel, nama produk, kategori, dll.';
    label.textContent  = 'Judul / teks';
  }
  generateJS();
}

function generateJS() {
  currentMode === 'bulk' ? generateBulk() : generateSingle();
}

function generateSingle() {
  const text = document.getElementById('input-text').value.trim();
  const slug = buildSlug(text);

  document.getElementById('slug-val').textContent         = slug || '—';
  document.getElementById('slug-len').textContent         = slug ? slug.length + ' karakter' : '';
  document.getElementById('url-slug-preview').textContent = slug || '—';
}

function generateBulk() {
  const raw  = document.getElementById('input-text').value;
  const lines = raw.split('\n').filter(l => l.trim() !== '');
  const wrap  = document.getElementById('bulk-table-wrap');

  if (!lines.length) {
    wrap.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--muted);font-size:.85rem;">Ketik beberapa judul (satu per baris) untuk melihat hasilnya.</div>';
    return;
  }

  const seen = new Set();
  const rows = lines.map((line, i) => {
    const slug  = buildSlug(line.trim());
    const isDup = slug && seen.has(slug);
    if (slug) seen.add(slug);
    const safeSlug = slug.replace(/\\/g, '\\\\').replace(/'/g, "\\'");

    return `<tr>
      <td class="text-muted text-xs">${i + 1}</td>
      <td class="td-input">${esc(line.trim())}</td>
      <td class="td-slug">${slug ? esc(slug) : '<span style="color:var(--muted)">—</span>'}${isDup ? '<span class="dup-warn">⚠ duplikat</span>' : ''}</td>
      <td class="text-muted text-xs">${slug.length}</td>
      <td class="td-copy">
        <button class="bulk-copy-btn" onclick="copyText('${safeSlug}', this)" ${!slug ? 'disabled' : ''}>SALIN</button>
      </td>
    </tr>`;
  }).join('');

  wrap.innerHTML = `<table class="bulk-table">
    <thead><tr><th>#</th><th>Input</th><th>Slug</th><th>Len</th><th></th></tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

function setSep(val) {
  document.getElementById('separator').value = val;
  document.querySelectorAll('.sep-pill').forEach(el => {
    el.classList.toggle('active', el.dataset.sep === val);
  });
  generateJS();
}

function copySingle() {
  const slug = document.getElementById('slug-val').textContent;
  if (!slug || slug === '—') return;
  copyText(slug);
}

function copyUrl() {
  const slug = document.getElementById('slug-val').textContent;
  if (!slug || slug === '—') return;
  copyText('https://sitemu.com/blog/' + slug);
}

function copyAllSlugs() {
  const rows  = document.querySelectorAll('#bulk-table-wrap .td-slug');
  const slugs = Array.from(rows)
    .map(td => td.textContent.replace('⚠ duplikat', '').trim())
    .filter(s => s && s !== '—');
  if (!slugs.length) return;
  copyText(slugs.join('\n'));
}

function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    if (btn) {
      const orig = btn.textContent;
      btn.textContent = '✓';
      btn.style.cssText = 'background:var(--accent5);border-color:var(--accent5);color:#fff;';
      setTimeout(() => {
        btn.textContent  = orig;
        btn.style.cssText = '';
      }, 1500);
    }
  });
}

function clearAll() {
  document.getElementById('input-text').value = '';
  document.getElementById('prefix').value     = '';
  document.getElementById('suffix').value     = '';
  document.getElementById('max_length').value = '0';
  generateJS();
}

function loadSample(text) {
  document.getElementById('mode-single').checked = true;
  toggleMode('single');
  document.getElementById('input-text').value = text;
  generateJS();
}

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init: set separator pill aktif & generate
(function() {
  const sep = document.getElementById('separator').value || '-';
  document.querySelectorAll('.sep-pill').forEach(el => {
    el.classList.toggle('active', el.dataset.sep === sep);
  });
  generateJS();
})();
</script>

<?php require '../../includes/footer.php'; ?>