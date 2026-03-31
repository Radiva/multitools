<?php
require '../../includes/config.php';
/**
 * Multi Tools — Text Diff Checker
 * Bandingkan dua teks dan tampilkan perbedaannya baris per baris.
 * Mendukung diff sisi server (PHP) dan realtime di browser (JS).
 * ============================================================ */

// ── Fungsi diff sisi server ──────────────────────────────────

/**
 * Hitung Longest Common Subsequence (LCS) antara dua array baris.
 * Mengembalikan array pasangan indeks [i, j] yang sama.
 */
function computeLCS(array $a, array $b): array {
  $m   = count($a);
  $n   = count($b);
  $dp  = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));

  for ($i = 1; $i <= $m; $i++) {
    for ($j = 1; $j <= $n; $j++) {
      if ($a[$i - 1] === $b[$j - 1]) {
        $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
      } else {
        $dp[$i][$j] = max($dp[$i - 1][$j], $dp[$i][$j - 1]);
      }
    }
  }

  // Backtrack untuk menemukan pasangan
  $pairs = [];
  $i = $m; $j = $n;
  while ($i > 0 && $j > 0) {
    if ($a[$i - 1] === $b[$j - 1]) {
      $pairs[] = [$i - 1, $j - 1];
      $i--; $j--;
    } elseif ($dp[$i - 1][$j] >= $dp[$i][$j - 1]) {
      $i--;
    } else {
      $j--;
    }
  }
  return array_reverse($pairs);
}

/**
 * Hasilkan diff baris: ['type' => 'equal'|'delete'|'insert', 'line' => string]
 */
function diffLines(string $textA, string $textB): array {
  $linesA = explode("\n", str_replace("\r\n", "\n", $textA));
  $linesB = explode("\n", str_replace("\r\n", "\n", $textB));

  $lcs    = computeLCS($linesA, $linesB);
  $result = [];

  $ia = 0; $ib = 0;
  foreach ($lcs as [$pa, $pb]) {
    while ($ia < $pa) { $result[] = ['type' => 'delete', 'line' => $linesA[$ia++]]; }
    while ($ib < $pb) { $result[] = ['type' => 'insert', 'line' => $linesB[$ib++]]; }
    $result[] = ['type' => 'equal', 'line' => $linesA[$ia++]]; $ib++;
  }
  while ($ia < count($linesA)) { $result[] = ['type' => 'delete', 'line' => $linesA[$ia++]]; }
  while ($ib < count($linesB)) { $result[] = ['type' => 'insert', 'line' => $linesB[$ib++]]; }

  return $result;
}

/**
 * Hitung statistik dari hasil diff.
 */
function diffStats(array $diff): array {
  $equal = $added = $removed = 0;
  foreach ($diff as $d) {
    match ($d['type']) {
      'equal'  => $equal++,
      'insert' => $added++,
      'delete' => $removed++,
    };
  }
  return ['equal' => $equal, 'added' => $added, 'removed' => $removed, 'total' => count($diff)];
}

// ── Handle POST ──────────────────────────────────────────────
$server_diff    = [];
$server_stats   = [];
$server_error   = '';
$post_text_a    = '';
$post_text_b    = '';
$post_ignore_ws = false;
$post_ignore_case = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_text_a      = $_POST['text_a']      ?? '';
  $post_text_b      = $_POST['text_b']      ?? '';
  $post_ignore_ws   = isset($_POST['ignore_whitespace']);
  $post_ignore_case = isset($_POST['ignore_case']);

  if ($post_text_a === '' && $post_text_b === '') {
    $server_error = 'Kedua teks tidak boleh kosong.';
  } else {
    $a = $post_text_a;
    $b = $post_text_b;

    if ($post_ignore_ws) {
      $a = preg_replace('/[ \t]+/', ' ', $a);
      $b = preg_replace('/[ \t]+/', ' ', $b);
    }
    if ($post_ignore_case) {
      $a = mb_strtolower($a);
      $b = mb_strtolower($b);
    }

    $server_diff  = diffLines($a, $b);
    $server_stats = diffStats($server_diff);
  }
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Text Diff Checker Online — Bandingkan Dua Teks | Multi Tools',
  'description' => 'Bandingkan dua teks dan temukan perbedaannya baris per baris secara realtime. Tampilan diff berwarna, statistik perubahan, dan opsi ignore case/whitespace.',
  'keywords'    => 'text diff, diff checker, bandingkan teks, perbedaan teks, compare text, diff online, text compare, multi tools',
  'og_title'    => 'Text Diff Checker Online — Bandingkan Dua Teks Instan',
  'og_desc'     => 'Temukan perbedaan dua teks baris per baris. Diff berwarna, statistik, ignore case dan whitespace.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Text Tools', 'url' => SITE_URL . '/tools?cat=text'],
    ['name' => 'Text Diff Checker'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/text-diff-checker#webpage',
      'url'         => SITE_URL . '/tools/text-diff-checker',
      'name'        => 'Text Diff Checker Online',
      'description' => 'Bandingkan dua teks dan tampilkan perbedaannya baris per baris.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',           'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Text Tools',         'item' => SITE_URL . '/tools?cat=text'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Text Diff Checker', 'item' => SITE_URL . '/tools/text-diff-checker'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Text Diff Checker',
      'applicationCategory' => 'UtilitiesApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/text-diff-checker',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Diff output ── */
.diff-output {
  font-family: var(--font-mono);
  font-size: .82rem;
  line-height: 1.65;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  background: var(--bg);
}
.diff-empty {
  padding: 2rem;
  text-align: center;
  color: var(--muted);
  font-size: .85rem;
}
.diff-line {
  display: flex;
  align-items: stretch;
  border-bottom: 1px solid transparent;
}
.diff-line:last-child { border-bottom: none; }

.diff-line.equal  { background: var(--bg); }
.diff-line.insert { background: #f0fdf4; border-color: #bbf7d0; }
.diff-line.delete { background: #fef2f2; border-color: #fecaca; }

.diff-gutter {
  flex-shrink: 0;
  width: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  font-weight: 700;
  font-family: var(--font-mono);
  border-right: 1px solid var(--border);
  user-select: none;
}
.diff-line.equal  .diff-gutter { color: var(--muted); }
.diff-line.insert .diff-gutter { color: #16a34a; background: #dcfce7; }
.diff-line.delete .diff-gutter { color: #dc2626; background: #fee2e2; }

.diff-lnum {
  flex-shrink: 0;
  width: 40px;
  padding: .3rem .5rem;
  text-align: right;
  color: var(--muted);
  font-size: .72rem;
  border-right: 1px solid var(--border);
  user-select: none;
  line-height: 1.65;
}
.diff-text {
  flex: 1;
  padding: .3rem .75rem;
  white-space: pre-wrap;
  word-break: break-all;
  color: var(--text);
}
.diff-line.insert .diff-text { color: #15803d; }
.diff-line.delete .diff-text { color: #b91c1c; text-decoration: line-through; text-decoration-color: #fca5a5; }

/* ── Legend ── */
.diff-legend {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  font-size: .78rem;
  margin-bottom: .75rem;
  font-family: var(--font-mono);
}
.legend-dot {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
}
.legend-dot::before {
  content: '';
  width: 10px; height: 10px;
  border-radius: 2px;
  display: inline-block;
}
.legend-dot.equal::before  { background: var(--bg);   border: 1px solid var(--border); }
.legend-dot.insert::before { background: #bbf7d0; }
.legend-dot.delete::before { background: #fecaca; }

/* ── Input kolom ── */
.diff-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 640px) {
  .diff-inputs { grid-template-columns: 1fr; }
}
.diff-inputs textarea { min-height: 200px; }

/* ── Statistik diff ── */
.diff-stat-added   { color: #16a34a; }
.diff-stat-removed { color: #dc2626; }
.diff-stat-equal   { color: var(--accent); }
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
        <span aria-hidden="true">🔍</span> Text Diff <span>Checker</span>
      </div>
      <p class="page-lead">
        Bandingkan dua teks dan temukan perbedaannya baris per baris secara realtime.
        Baris ditambah ditandai hijau, dihapus ditandai merah.
      </p>

      <form method="POST" action="" id="diff-form" style="margin-top:1.5rem;" novalidate>

        <!-- Dua textarea input berdampingan -->
        <div class="diff-inputs">
          <div class="form-group">
            <label for="text-a">Teks A <span class="text-muted text-sm">(asli / lama)</span></label>
            <textarea
              id="text-a"
              name="text_a"
              placeholder="Tempel teks pertama di sini..."
              oninput="diffJS()"
              aria-label="Teks A — asli"
            ><?= isset($post_text_a) ? e($post_text_a) : '' ?></textarea>
          </div>
          <div class="form-group">
            <label for="text-b">Teks B <span class="text-muted text-sm">(baru / revisi)</span></label>
            <textarea
              id="text-b"
              name="text_b"
              placeholder="Tempel teks kedua di sini..."
              oninput="diffJS()"
              aria-label="Teks B — revisi"
            ><?= isset($post_text_b) ? e($post_text_b) : '' ?></textarea>
          </div>
        </div>

        <!-- Opsi -->
        <div class="form-group">
          <label>Opsi perbandingan</label>
          <div style="display:flex; flex-wrap:wrap; gap:.75rem 1.5rem; margin-top:.25rem;">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="ignore_whitespace" name="ignore_whitespace"
                <?= $post_ignore_ws ? 'checked' : '' ?>
                onchange="diffJS()"
                style="width:auto; accent-color:var(--accent);" />
              Abaikan spasi berlebih
            </label>
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="ignore_case" name="ignore_case"
                <?= $post_ignore_case ? 'checked' : '' ?>
                onchange="diffJS()"
                style="width:auto; accent-color:var(--accent);" />
              Abaikan perbedaan huruf kapital
            </label>
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; color:var(--text); cursor:pointer;">
              <input type="checkbox" id="show_equal" name="show_equal" checked
                onchange="diffJS()"
                style="width:auto; accent-color:var(--accent);" />
              Tampilkan baris sama
            </label>
          </div>
        </div>

        <!-- Statistik diff -->
        <div id="result-stats" class="stats" role="region" aria-live="polite" aria-label="Statistik diff">
          <div class="stat">
            <span class="stat-value diff-stat-added" id="stat-added">0</span>
            <span class="stat-label">Baris ditambah</span>
          </div>
          <div class="stat">
            <span class="stat-value diff-stat-removed" id="stat-removed">0</span>
            <span class="stat-label">Baris dihapus</span>
          </div>
          <div class="stat">
            <span class="stat-value diff-stat-equal" id="stat-equal">0</span>
            <span class="stat-label">Baris sama</span>
          </div>
          <div class="stat">
            <span class="stat-value" id="stat-total">0</span>
            <span class="stat-label">Total baris</span>
          </div>
        </div>

        <!-- Tombol -->
        <div style="margin-top:1.25rem; display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
          <button type="submit" class="btn-primary btn-sm">
            ⚙ Bandingkan via Server (PHP)
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="swapTexts()">
            ⇄ Tukar A &amp; B
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="clearAll()">
            Bersihkan
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="copyDiff()">
            📋 Salin diff teks
          </button>
        </div>

      </form><!-- /#diff-form -->

      <!-- Legend -->
      <div style="margin-top:1.5rem;">
        <div class="diff-legend">
          <span class="legend-dot insert">Baris ditambah (B)</span>
          <span class="legend-dot delete">Baris dihapus (A)</span>
          <span class="legend-dot equal">Baris sama</span>
        </div>

        <!-- Hasil diff -->
        <div class="diff-output" id="diff-output" role="region" aria-live="polite" aria-label="Hasil perbandingan">
          <div class="diff-empty">
            Ketik atau tempel teks di kedua kolom untuk melihat perbedaannya.
          </div>
        </div>
      </div>

    </div><!-- /.panel -->

    <?php if (!empty($server_diff)): ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>
        Diff selesai via PHP:
        <strong class="diff-stat-added">+<?= (int)$server_stats['added'] ?> ditambah</strong>,
        <strong class="diff-stat-removed">-<?= (int)$server_stats['removed'] ?> dihapus</strong>,
        <?= (int)$server_stats['equal'] ?> baris sama.
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
        Diff berjalan realtime saat kamu mengetik. Gunakan server (PHP) untuk teks sangat panjang agar lebih akurat.
      </p>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><span style="color:#16a34a; font-weight:700;">+ Hijau</span> — baris baru di Teks B</li>
        <li><span style="color:#dc2626; font-weight:700;">- Merah</span> — baris lama di Teks A</li>
        <li>Baris sama ditampilkan abu-abu</li>
        <li><strong>Abaikan spasi</strong> — anggap "a  b" = "a b"</li>
        <li><strong>Abaikan kapital</strong> — anggap "Hello" = "hello"</li>
        <li>Gunakan <strong>⇄ Tukar</strong> untuk balik perspektif</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📋 Contoh Kasus</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <?php
        $examples = [
          [
            'label' => 'Revisi kalimat',
            'a'     => "Halo, nama saya Budi.\nSaya tinggal di Jakarta.\nSaya suka programming.",
            'b'     => "Halo, nama saya Budi Santoso.\nSaya tinggal di Bandung.\nSaya suka programming dan desain.",
          ],
          [
            'label' => 'Perubahan konfigurasi',
            'a'     => "DEBUG=false\nDB_HOST=localhost\nDB_PORT=3306\nAPP_ENV=production",
            'b'     => "DEBUG=true\nDB_HOST=db.server.com\nDB_PORT=5432\nDB_NAME=myapp\nAPP_ENV=development",
          ],
          [
            'label' => 'Daftar item',
            'a'     => "apel\njeruk\nmanggis\npepaya\nsemangka",
            'b'     => "apel\njeruk\nmanggis\nnanas\nsemangka\nstroberi",
          ],
        ];
        foreach ($examples as $ex): ?>
          <button
            class="btn-ghost btn-sm btn-full"
            type="button"
            onclick="loadExample(<?= htmlspecialchars(json_encode($ex['a']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ex['b']), ENT_QUOTES) ?>)"
            aria-label="Muat contoh: <?= e($ex['label']) ?>">
            <?= e($ex['label']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Hasil diff server (jika ada) -->
    <?php if (!empty($server_diff)): ?>
    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚙ Hasil Server PHP</div>
      <div class="diff-output" style="max-height:320px; overflow-y:auto;">
        <?php
        $ln_a = 1; $ln_b = 1;
        foreach ($server_diff as $d):
          $sym   = match($d['type']) { 'insert' => '+', 'delete' => '-', default => ' ' };
          $class = $d['type'];
          $lnum  = match($d['type']) {
            'insert' => $ln_b++,
            'delete' => $ln_a++,
            default  => (function() use (&$ln_a, &$ln_b) { $ln_a++; return $ln_b++; })(),
          };
        ?>
          <div class="diff-line <?= $class ?>">
            <span class="diff-gutter"><?= $sym ?></span>
            <span class="diff-lnum"><?= $lnum ?></span>
            <span class="diff-text"><?= e($d['line']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/word-counter"      class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/remove-duplicates" class="btn-ghost btn-sm btn-full">Remove Duplicates</a>
        <a href="/tools/case-converter"    class="btn-ghost btn-sm btn-full">Case Converter</a>
        <a href="/tools/text-cleaner"      class="btn-ghost btn-sm btn-full">Text Cleaner</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Text Diff Checker — algoritma LCS (JS)
   Realtime di browser. PHP untuk submit form.
   ────────────────────────────────────────── */

// ── LCS diff sederhana ──────────────────────────────────────
function lcs(a, b) {
  const m  = a.length, n = b.length;
  const dp = Array.from({ length: m + 1 }, () => new Array(n + 1).fill(0));

  for (let i = 1; i <= m; i++)
    for (let j = 1; j <= n; j++)
      dp[i][j] = a[i-1] === b[j-1]
        ? dp[i-1][j-1] + 1
        : Math.max(dp[i-1][j], dp[i][j-1]);

  // Backtrack
  const pairs = [];
  let i = m, j = n;
  while (i > 0 && j > 0) {
    if (a[i-1] === b[j-1]) { pairs.push([i-1, j-1]); i--; j--; }
    else if (dp[i-1][j] >= dp[i][j-1]) i--;
    else j--;
  }
  return pairs.reverse();
}

function computeDiff(linesA, linesB) {
  const pairs  = lcs(linesA, linesB);
  const result = [];
  let ia = 0, ib = 0;

  for (const [pa, pb] of pairs) {
    while (ia < pa) result.push({ type: 'delete', line: linesA[ia++] });
    while (ib < pb) result.push({ type: 'insert', line: linesB[ib++] });
    result.push({ type: 'equal', line: linesA[ia++] }); ib++;
  }
  while (ia < linesA.length) result.push({ type: 'delete', line: linesA[ia++] });
  while (ib < linesB.length) result.push({ type: 'insert', line: linesB[ib++] });

  return result;
}

// ── Escape HTML ─────────────────────────────────────────────
function esc(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Render diff ke DOM ──────────────────────────────────────
function renderDiff(diff, showEqual) {
  const wrap = document.getElementById('diff-output');

  if (!diff.length) {
    wrap.innerHTML = '<div class="diff-empty">Ketik atau tempel teks di kedua kolom untuk melihat perbedaannya.</div>';
    return;
  }

  const hasChanges = diff.some(d => d.type !== 'equal');
  if (!hasChanges) {
    wrap.innerHTML = '<div class="diff-empty" style="color:#16a34a;">✓ Kedua teks identik — tidak ada perbedaan.</div>';
    updateStats(0, 0, diff.length, diff.length);
    return;
  }

  let html   = '';
  let ln_a   = 1, ln_b = 1;
  let added  = 0, removed = 0, equal = 0;

  for (const d of diff) {
    const sym  = d.type === 'insert' ? '+' : d.type === 'delete' ? '−' : ' ';
    let lnum;

    if (d.type === 'delete')      { lnum = ln_a++;  removed++; }
    else if (d.type === 'insert') { lnum = ln_b++;  added++;   }
    else                          { lnum = ln_b++; ln_a++; equal++; }

    if (d.type === 'equal' && !showEqual) continue;

    html += `<div class="diff-line ${d.type}">
      <span class="diff-gutter">${sym}</span>
      <span class="diff-lnum">${lnum}</span>
      <span class="diff-text">${esc(d.line)}</span>
    </div>`;
  }

  wrap.innerHTML = html || '<div class="diff-empty">Semua baris sama tersembunyi.</div>';
  updateStats(added, removed, equal, diff.length);
}

function updateStats(added, removed, equal, total) {
  document.getElementById('stat-added').textContent   = added.toLocaleString('id');
  document.getElementById('stat-removed').textContent = removed.toLocaleString('id');
  document.getElementById('stat-equal').textContent   = equal.toLocaleString('id');
  document.getElementById('stat-total').textContent   = total.toLocaleString('id');
}

// ── Proses utama ─────────────────────────────────────────────
function diffJS() {
  let a = document.getElementById('text-a').value;
  let b = document.getElementById('text-b').value;

  const ignoreWS   = document.getElementById('ignore_whitespace').checked;
  const ignoreCase = document.getElementById('ignore_case').checked;
  const showEqual  = document.getElementById('show_equal').checked;

  if (!a && !b) {
    document.getElementById('diff-output').innerHTML =
      '<div class="diff-empty">Ketik atau tempel teks di kedua kolom untuk melihat perbedaannya.</div>';
    updateStats(0, 0, 0, 0);
    return;
  }

  if (ignoreWS) {
    a = a.replace(/[ \t]+/g, ' ');
    b = b.replace(/[ \t]+/g, ' ');
  }
  if (ignoreCase) {
    a = a.toLowerCase();
    b = b.toLowerCase();
  }

  const linesA = a.split('\n');
  const linesB = b.split('\n');

  // Batasi LCS untuk performa browser (maks 800 baris per sisi)
  if (linesA.length > 800 || linesB.length > 800) {
    document.getElementById('diff-output').innerHTML =
      '<div class="diff-empty" style="color:var(--accent4);">⚠ Teks terlalu panjang untuk diff realtime. Gunakan tombol "Bandingkan via Server (PHP)".</div>';
    return;
  }

  const diff = computeDiff(linesA, linesB);
  renderDiff(diff, showEqual);
}

// ── Utilitas ────────────────────────────────────────────────
function swapTexts() {
  const a = document.getElementById('text-a').value;
  const b = document.getElementById('text-b').value;
  document.getElementById('text-a').value = b;
  document.getElementById('text-b').value = a;
  diffJS();
}

function clearAll() {
  document.getElementById('text-a').value = '';
  document.getElementById('text-b').value = '';
  document.getElementById('diff-output').innerHTML =
    '<div class="diff-empty">Ketik atau tempel teks di kedua kolom untuk melihat perbedaannya.</div>';
  updateStats(0, 0, 0, 0);
}

function copyDiff() {
  const lines = document.querySelectorAll('#diff-output .diff-line');
  if (!lines.length) return;

  const text = Array.from(lines).map(row => {
    const sym  = row.querySelector('.diff-gutter')?.textContent?.trim() ?? ' ';
    const line = row.querySelector('.diff-text')?.textContent ?? '';
    return `${sym} ${line}`;
  }).join('\n');

  navigator.clipboard.writeText(text).then(() => {
    const btn = event.target;
    const orig = btn.textContent;
    btn.textContent = '✓ Tersalin!';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}

function loadExample(a, b) {
  document.getElementById('text-a').value = a;
  document.getElementById('text-b').value = b;
  diffJS();
}

// ── Init ────────────────────────────────────────────────────
<?php if (!empty($server_diff)): ?>
// Render hasil diff dari server ke tampilan JS
(function () {
  const diff = <?= json_encode(array_map(fn($d) => ['type' => $d['type'], 'line' => $d['line']], $server_diff)) ?>;
  renderDiff(diff, true);
})();
<?php else: ?>
diffJS();
<?php endif; ?>
</script>

<?php require '../../includes/footer.php'; ?>