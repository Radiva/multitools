<?php
require '../../includes/config.php';
/**
 * Multi Tools — Lorem Ipsum Generator
 * Generate teks placeholder lorem ipsum: kata, kalimat, atau paragraf.
 * Mendukung generate sisi server (POST) dan realtime di browser (JS).
 * ============================================================ */

// ── Data lorem ipsum ──────────────────────────────────────────
const LOREM_WORDS = [
  'lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit',
  'sed','do','eiusmod','tempor','incididunt','ut','labore','et','dolore',
  'magna','aliqua','enim','ad','minim','veniam','quis','nostrud','exercitation',
  'ullamco','laboris','nisi','aliquip','ex','ea','commodo','consequat','duis',
  'aute','irure','in','reprehenderit','voluptate','velit','esse','cillum',
  'fugiat','nulla','pariatur','excepteur','sint','occaecat','cupidatat','non',
  'proident','sunt','culpa','qui','officia','deserunt','mollit','anim','id','est',
  'perspiciatis','unde','omnis','iste','natus','error','accusantium','doloremque',
  'laudantium','totam','rem','aperiam','eaque','ipsa','quae','ab','illo',
  'inventore','veritatis','quasi','architecto','beatae','vitae','dicta','explicabo',
  'nemo','ipsam','quia','voluptas','aspernatur','odit','fugit','consequuntur',
  'magni','dolores','eos','ratione','sequi','nesciunt','neque','porro',
  'quisquam','dolorem','adipisci','numquam','eius','modi','tempora','incidunt',
  'quaerat','sapiente','saepe','eveniet','voluptatem','repellendus','itaque',
  'earum','rerum','hic','tenetur','sapiente','delectus','reiciendis',
];

// Kalimat pembuka klasik
const LOREM_START = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

/**
 * Ambil kata acak dari pool.
 */
function randomWord(array &$pool): string {
  return $pool[array_rand($pool)];
}

/**
 * Generate satu kalimat (6–18 kata, diawali kapital, diakhiri titik).
 */
function makeSentence(array $pool, bool $startWithLorem = false): string {
  if ($startWithLorem) return LOREM_START;

  $len   = rand(6, 18);
  $words = [];
  for ($i = 0; $i < $len; $i++) {
    $words[] = randomWord($pool);
  }
  $words[0] = ucfirst($words[0]);

  // Sisipkan koma secara acak
  $commaAt = rand(2, max(2, $len - 3));
  if (isset($words[$commaAt])) {
    $words[$commaAt] .= ',';
  }

  $punct = ['.', '.', '.', '!', '?'][rand(0, 4)];
  return implode(' ', $words) . $punct;
}

/**
 * Generate satu paragraf (3–7 kalimat).
 */
function makeParagraph(array $pool, bool $firstParagraph = false): string {
  $count     = rand(3, 7);
  $sentences = [];
  for ($i = 0; $i < $count; $i++) {
    $sentences[] = makeSentence($pool, $firstParagraph && $i === 0);
  }
  return implode(' ', $sentences);
}

/**
 * Generate sejumlah kata acak.
 */
function generateWords(int $n, array $pool): string {
  $words    = [];
  $words[]  = 'Lorem';
  for ($i = 1; $i < $n; $i++) {
    $words[] = randomWord($pool);
  }
  return implode(' ', $words);
}

// ── Handle POST (generate sisi server) ──────────────────────
$server_result = '';
$server_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type   = in_array($_POST['type'] ?? '', ['words','sentences','paragraphs'])
              ? $_POST['type'] : 'paragraphs';
  $amount = max(1, min(100, (int)($_POST['amount'] ?? 5)));
  $html   = isset($_POST['html_tags']);
  $start  = isset($_POST['start_lorem']);

  $pool = LOREM_WORDS;

  switch ($type) {
    case 'words':
      $server_result = generateWords($amount, $pool);
      break;

    case 'sentences':
      $parts = [];
      for ($i = 0; $i < $amount; $i++) {
        $parts[] = makeSentence($pool, $start && $i === 0);
      }
      $server_result = implode(' ', $parts);
      break;

    case 'paragraphs':
    default:
      $parts = [];
      for ($i = 0; $i < $amount; $i++) {
        $p = makeParagraph($pool, $start && $i === 0);
        $parts[] = $html ? '<p>' . htmlspecialchars($p) . '</p>' : $p;
      }
      $server_result = implode($html ? "\n" : "\n\n", $parts);
      break;
  }
}

// ── Breadcrumb & SEO ─────────────────────────────────────────
$seo = [
  'title'       => 'Lorem Ipsum Generator Online — Teks Placeholder Instan | Multi Tools',
  'description' => 'Generate teks lorem ipsum placeholder secara instan. Pilih jumlah kata, kalimat, atau paragraf. Tersedia opsi tag HTML dan klasik.',
  'keywords'    => 'lorem ipsum generator, teks placeholder, dummy text, lorem ipsum online, generate paragraf, multi tools',
  'og_title'    => 'Lorem Ipsum Generator Online — Teks Placeholder Instan',
  'og_desc'     => 'Generate teks lorem ipsum: kata, kalimat, atau paragraf. Langsung di browser atau via server.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Text Tools', 'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Lorem Ipsum Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/lorem-ipsum-generator#webpage',
      'url'         => SITE_URL . '/tools/lorem-ipsum-generator',
      'name'        => 'Lorem Ipsum Generator Online',
      'description' => 'Generate teks lorem ipsum placeholder secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',                 'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',               'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Lorem Ipsum Generator',   'item' => SITE_URL . '/tools/lorem-ipsum-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Lorem Ipsum Generator',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/lorem-ipsum-generator',
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
        <span aria-hidden="true">📄</span> Lorem Ipsum <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate teks placeholder lorem ipsum secara instan — pilih jumlah kata,
        kalimat, atau paragraf, lalu salin hasilnya.
      </p>

      <!-- ── Form generate ── -->
      <form method="POST" action="" id="lorem-form" style="margin-top:1.5rem;" novalidate>

        <div class="form-row">
          <!-- Tipe -->
          <div class="form-group">
            <label for="type">Tipe teks</label>
            <select id="type" name="type" onchange="updateLabel(); generateJS();" aria-label="Pilih tipe teks">
              <option value="paragraphs" <?= ($_POST['type'] ?? '') === 'paragraphs' ? 'selected' : '' ?>>Paragraf</option>
              <option value="sentences"  <?= ($_POST['type'] ?? '') === 'sentences'  ? 'selected' : '' ?>>Kalimat</option>
              <option value="words"      <?= ($_POST['type'] ?? '') === 'words'      ? 'selected' : '' ?>>Kata</option>
            </select>
          </div>

          <!-- Jumlah -->
          <div class="form-group">
            <label for="amount">Jumlah <span id="type-label">paragraf</span></label>
            <input
              type="number"
              id="amount"
              name="amount"
              min="1" max="100"
              value="<?= (int)($_POST['amount'] ?? 5) ?>"
              oninput="generateJS()"
              aria-label="Jumlah teks yang di-generate"
            />
          </div>
        </div>

        <!-- Opsi tambahan -->
        <div class="form-group">
          <label>Opsi</label>
          <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:.25rem;">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="start_lorem" name="start_lorem"
                <?= isset($_POST['start_lorem']) ? 'checked' : 'checked' ?>
                onchange="generateJS()"
                style="width:auto; accent-color:var(--accent);" />
              Mulai dengan "Lorem ipsum..."
            </label>
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="html_tags" name="html_tags"
                <?= isset($_POST['html_tags']) ? 'checked' : '' ?>
                onchange="generateJS()"
                style="width:auto; accent-color:var(--accent);" />
              Bungkus dengan tag <code>&lt;p&gt;</code>
            </label>
          </div>
        </div>

        <!-- Output -->
        <div class="form-group">
          <label id="output-label">Hasil lorem ipsum</label>
          <div class="copy-wrap">
            <textarea
              id="output-text"
              name="output-text"
              readonly
              placeholder="Hasil akan muncul di sini secara otomatis..."
              aria-live="polite"
              aria-label="Hasil lorem ipsum"
              style="min-height:260px;"
            ><?php if ($server_result): ?><?= e($server_result) ?><?php endif; ?></textarea>
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

        <!-- Statistik hasil -->
        <div id="result-stats" class="stats" role="region" aria-live="polite" aria-label="Statistik hasil">
          <div class="stat">
            <span class="stat-value" id="stat-words">0</span>
            <span class="stat-label">Kata</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-chars">0</span>
            <span class="stat-label">Karakter</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-sentences">0</span>
            <span class="stat-label">Kalimat</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-paragraphs">0</span>
            <span class="stat-label">Paragraf</span>
          </div>
        </div>

        <!-- Tombol aksi -->
        <div style="margin-top:1.25rem; display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
          <button type="button" class="btn-primary btn-sm" onclick="generateJS()">
            ↻ Generate Ulang
          </button>
          <button type="submit" class="btn-secondary btn-sm">
            ⚙ Generate via Server (PHP)
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="clearOutput()">
            Bersihkan
          </button>
        </div>

      </form><!-- /.lorem-form -->
    </div><!-- /.panel -->

    <?php if ($server_result): ?>
    <!-- Alert konfirmasi hasil server -->
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>Teks berhasil di-generate via PHP server.</span>
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
        Teks lorem ipsum digunakan sebagai <em>placeholder</em> dalam desain dan pengembangan web sebelum konten asli tersedia.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Gunakan <strong>Paragraf</strong> untuk mock-up artikel</li>
        <li>Gunakan <strong>Kalimat</strong> untuk caption atau label</li>
        <li>Gunakan <strong>Kata</strong> untuk judul atau tombol</li>
        <li>Aktifkan <strong>&lt;p&gt;</strong> jika langsung ditempel ke HTML</li>
        <li>Batas maksimal: <strong>100</strong> per generate</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Generate Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $presets = [
          ['label' => '1 paragraf',    'type' => 'paragraphs', 'amount' => 1],
          ['label' => '3 paragraf',    'type' => 'paragraphs', 'amount' => 3],
          ['label' => '5 paragraf',    'type' => 'paragraphs', 'amount' => 5],
          ['label' => '10 kalimat',    'type' => 'sentences',  'amount' => 10],
          ['label' => '50 kata',       'type' => 'words',      'amount' => 50],
          ['label' => '100 kata',      'type' => 'words',      'amount' => 100],
        ];
        foreach ($presets as $p): ?>
          <button
            class="btn-ghost btn-sm btn-full"
            type="button"
            onclick="quickGenerate('<?= e($p['type']) ?>', <?= (int)$p['amount'] ?>)"
            aria-label="Generate <?= e($p['label']) ?>">
            <?= e($p['label']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/word-counter"   class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/case-converter" class="btn-ghost btn-sm btn-full">Case Converter</a>
        <a href="/tools/text-cleaner"   class="btn-ghost btn-sm btn-full">Text Cleaner</a>
        <a href="/tools/slug-generator" class="btn-ghost btn-sm btn-full">Slug Generator</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Lorem Ipsum Generator — logika JS
   Generate realtime di sisi klien.
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

const WORDS = [
  'lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit',
  'sed','do','eiusmod','tempor','incididunt','ut','labore','et','dolore',
  'magna','aliqua','enim','ad','minim','veniam','quis','nostrud','exercitation',
  'ullamco','laboris','nisi','aliquip','ex','ea','commodo','consequat','duis',
  'aute','irure','in','reprehenderit','voluptate','velit','esse','cillum',
  'fugiat','nulla','pariatur','excepteur','sint','occaecat','cupidatat','non',
  'proident','sunt','culpa','qui','officia','deserunt','mollit','anim','id','est',
  'perspiciatis','unde','omnis','iste','natus','error','accusantium','doloremque',
  'laudantium','totam','rem','aperiam','eaque','ipsa','quae','ab','illo',
  'inventore','veritatis','quasi','architecto','beatae','vitae','dicta','explicabo',
  'nemo','ipsam','quia','voluptas','aspernatur','odit','fugit','consequuntur',
  'magni','dolores','eos','ratione','sequi','nesciunt','neque','porro',
  'quisquam','dolorem','adipisci','numquam','eius','modi','tempora','incidunt',
  'quaerat','sapiente','saepe','eveniet','voluptatem','repellendus','itaque',
  'earum','rerum','hic','tenetur','delectus','reiciendis','voluptatibus',
];

const LOREM_START = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

function rnd(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function randomWord() {
  return WORDS[Math.floor(Math.random() * WORDS.length)];
}

function makeSentence(first = false) {
  if (first) return LOREM_START;

  const len   = rnd(6, 18);
  const words = Array.from({ length: len }, (_, i) => randomWord());
  words[0]    = words[0].charAt(0).toUpperCase() + words[0].slice(1);

  // Sisipkan koma acak
  const commaAt = rnd(2, Math.max(2, len - 3));
  if (words[commaAt]) words[commaAt] += ',';

  const puncts = ['.', '.', '.', '!', '?'];
  return words.join(' ') + puncts[Math.floor(Math.random() * puncts.length)];
}

function makeParagraph(firstParagraph = false) {
  const count = rnd(3, 7);
  const sentences = Array.from({ length: count }, (_, i) =>
    makeSentence(firstParagraph && i === 0)
  );
  return sentences.join(' ');
}

function generateWords(n) {
  const words = ['Lorem'];
  for (let i = 1; i < n; i++) words.push(randomWord());
  return words.join(' ');
}

function generateJS() {
  const type       = document.getElementById('type').value;
  const amount     = Math.max(1, Math.min(100, parseInt(document.getElementById('amount').value) || 5));
  const startLorem = document.getElementById('start_lorem').checked;
  const htmlTags   = document.getElementById('html_tags').checked;

  let result = '';

  if (type === 'words') {
    result = generateWords(amount);

  } else if (type === 'sentences') {
    const parts = Array.from({ length: amount }, (_, i) => makeSentence(startLorem && i === 0));
    result = parts.join(' ');

  } else {
    // paragraphs
    const parts = Array.from({ length: amount }, (_, i) => {
      const p = makeParagraph(startLorem && i === 0);
      return htmlTags ? `<p>${p}</p>` : p;
    });
    result = parts.join(htmlTags ? '\n' : '\n\n');
  }

  document.getElementById('output-text').value = result;
  updateStats(result);
}

function updateStats(text) {
  const words      = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
  const chars      = text.length;
  const sentences  = text.trim() === '' ? 0 : (text.match(/[.!?]+/g) || []).length;
  const paragraphs = text.trim() === '' ? 0 :
    text.split(/\n\s*\n/).filter(p => p.trim()).length || (text.trim() ? 1 : 0);

  document.getElementById('stat-words').textContent      = words.toLocaleString('id');
  document.getElementById('stat-chars').textContent      = chars.toLocaleString('id');
  document.getElementById('stat-sentences').textContent  = sentences.toLocaleString('id');
  document.getElementById('stat-paragraphs').textContent = paragraphs.toLocaleString('id');
}

function updateLabel() {
  const map = { paragraphs: 'paragraf', sentences: 'kalimat', words: 'kata' };
  document.getElementById('type-label').textContent = map[document.getElementById('type').value] || 'paragraf';
}

function quickGenerate(type, amount) {
  document.getElementById('type').value   = type;
  document.getElementById('amount').value = amount;
  updateLabel();
  generateJS();
}

function clearOutput() {
  document.getElementById('output-text').value = '';
  updateStats('');
}

// Jalankan otomatis saat halaman pertama kali dimuat
// (kecuali sudah ada hasil dari server PHP)
<?php if (!$server_result): ?>
generateJS();
<?php else: ?>
updateStats(document.getElementById('output-text').value);
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>