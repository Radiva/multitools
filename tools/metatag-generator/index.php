<?php
require '../../includes/config.php';
/**
 * Multi Tools — Meta Tag Generator
 * Generate meta tag HTML untuk SEO, Open Graph, Twitter Card, dsb.
 * ============================================================ */

$seo = [
  'title'       => 'Meta Tag Generator Online — Buat Meta Tag SEO, OG & Twitter Card | Multi Tools',
  'description' => 'Generate meta tag HTML lengkap untuk SEO, Open Graph (Facebook), Twitter Card, dan robots secara instan. Preview SERP Google dan kartu media sosial sebelum dipasang.',
  'keywords'    => 'meta tag generator, seo meta tag, open graph generator, twitter card generator, html meta tag, og tag, multi tools',
  'og_title'    => 'Meta Tag Generator Online — SEO, Open Graph & Twitter Card',
  'og_desc'     => 'Buat meta tag HTML untuk SEO, Open Graph, dan Twitter Card secara instan dengan preview SERP dan media sosial.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'SEO Tools',  'url' => SITE_URL . '/tools?cat=seo'],
    ['name' => 'Meta Tag Generator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/metatag-generator#webpage',
      'url'         => SITE_URL . '/tools/metatag-generator',
      'name'        => 'Meta Tag Generator Online',
      'description' => 'Generate meta tag HTML untuk SEO, Open Graph, Twitter Card secara instan.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',             'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'SEO Tools',           'item' => SITE_URL . '/tools?cat=seo'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Meta Tag Generator',  'item' => SITE_URL . '/tools/metatag-generator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Meta Tag Generator',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/metatag-generator',
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

  <!-- ── Konten Utama ── -->
  <div>

    <!-- Header -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="page-title">
        <span aria-hidden="true">🏷️</span> Meta Tag <span>Generator</span>
      </div>
      <p class="page-lead">
        Generate meta tag HTML lengkap untuk SEO, Open Graph, dan Twitter Card. Lihat preview SERP dan kartu media sosial secara realtime.
      </p>
    </div>

    <!-- ── Seksi: General / SEO ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="panel-title">🔍 General &amp; SEO</div>

      <div class="form-group">
        <label for="f-title">
          Page Title
          <span class="badge" id="badge-title" style="margin-left:.5rem;">0 / 60</span>
        </label>
        <input type="text" id="f-title"
          placeholder="Contoh: Cara Membuat Website — Tutorial Lengkap 2024"
          maxlength="120"
          oninput="update()" />
        <div class="text-xs text-muted" style="margin-top:.35rem;">
          Ideal: 50–60 karakter. Ditampilkan di tab browser dan hasil pencarian Google.
        </div>
      </div>

      <div class="form-group">
        <label for="f-desc">
          Meta Description
          <span class="badge" id="badge-desc" style="margin-left:.5rem;">0 / 160</span>
        </label>
        <textarea id="f-desc"
          placeholder="Deskripsi singkat halaman yang akan muncul di hasil pencarian Google..."
          maxlength="320"
          rows="3"
          oninput="update()"></textarea>
        <div class="text-xs text-muted" style="margin-top:.35rem;">
          Ideal: 120–160 karakter. Pengaruhi CTR di halaman hasil pencarian.
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-keywords">Keywords</label>
          <input type="text" id="f-keywords"
            placeholder="kata kunci, seo, website, tips"
            oninput="update()" />
          <div class="text-xs text-muted" style="margin-top:.35rem;">
            Pisahkan dengan koma. (Tidak lagi digunakan Google, tapi opsional.)
          </div>
        </div>
        <div class="form-group">
          <label for="f-author">Author</label>
          <input type="text" id="f-author"
            placeholder="Nama penulis atau perusahaan"
            oninput="update()" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-canonical">Canonical URL</label>
          <input type="url" id="f-canonical"
            placeholder="https://example.com/halaman-ini"
            oninput="update()" />
        </div>
        <div class="form-group">
          <label for="f-lang">Language</label>
          <select id="f-lang" onchange="update()">
            <option value="">— Pilih —</option>
            <option value="id" selected>id — Bahasa Indonesia</option>
            <option value="en">en — English</option>
            <option value="ms">ms — Bahasa Melayu</option>
            <option value="zh">zh — Chinese</option>
            <option value="ar">ar — Arabic</option>
            <option value="fr">fr — French</option>
            <option value="de">de — German</option>
            <option value="ja">ja — Japanese</option>
            <option value="ko">ko — Korean</option>
            <option value="pt">pt — Portuguese</option>
            <option value="es">es — Spanish</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-robots">Robots</label>
          <select id="f-robots" onchange="update()">
            <option value="index, follow" selected>index, follow (default)</option>
            <option value="noindex, follow">noindex, follow</option>
            <option value="index, nofollow">index, nofollow</option>
            <option value="noindex, nofollow">noindex, nofollow</option>
            <option value="noarchive">noarchive</option>
            <option value="nosnippet">nosnippet</option>
          </select>
        </div>
        <div class="form-group">
          <label for="f-viewport">Viewport</label>
          <select id="f-viewport" onchange="update()">
            <option value="width=device-width, initial-scale=1" selected>Responsive (default)</option>
            <option value="width=device-width, initial-scale=1, maximum-scale=1">Responsive, no zoom</option>
            <option value="width=1024">Fixed 1024px</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-theme-color">Theme Color</label>
          <div style="display:flex; gap:.5rem; align-items:center;">
            <input type="color" id="f-theme-color-picker" value="#2563eb"
              oninput="syncColor(this,'f-theme-color'); update()"
              style="width:40px; height:38px; padding:2px; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--bg); cursor:pointer;" />
            <input type="text" id="f-theme-color" value="#2563eb"
              placeholder="#2563eb"
              oninput="syncColor(this,'f-theme-color-picker'); update()"
              style="flex:1;" />
          </div>
        </div>
        <div class="form-group">
          <label for="f-charset">Charset</label>
          <select id="f-charset" onchange="update()">
            <option value="UTF-8" selected>UTF-8 (direkomendasikan)</option>
            <option value="ISO-8859-1">ISO-8859-1</option>
            <option value="Windows-1252">Windows-1252</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ── Seksi: Open Graph ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="panel-title">
        📘 Open Graph
        <span class="badge" style="margin-left:.5rem;">Facebook · LinkedIn · WhatsApp</span>
      </div>

      <div class="form-group">
        <label for="f-og-title">OG Title</label>
        <input type="text" id="f-og-title"
          placeholder="Sama dengan Page Title jika dikosongkan"
          oninput="update()" />
      </div>

      <div class="form-group">
        <label for="f-og-desc">
          OG Description
          <span class="badge" id="badge-og-desc" style="margin-left:.5rem;">0 / 200</span>
        </label>
        <textarea id="f-og-desc"
          placeholder="Deskripsi untuk pratinjau saat dibagikan di media sosial..."
          maxlength="400"
          rows="2"
          oninput="update()"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-og-url">OG URL</label>
          <input type="url" id="f-og-url"
            placeholder="https://example.com/halaman-ini"
            oninput="update()" />
        </div>
        <div class="form-group">
          <label for="f-og-type">OG Type</label>
          <select id="f-og-type" onchange="update()">
            <option value="website" selected>website</option>
            <option value="article">article</option>
            <option value="blog">blog</option>
            <option value="product">product</option>
            <option value="profile">profile</option>
            <option value="video.movie">video.movie</option>
            <option value="music.song">music.song</option>
            <option value="book">book</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-og-image">OG Image URL</label>
          <input type="url" id="f-og-image"
            placeholder="https://example.com/images/og.jpg"
            oninput="update()" />
          <div class="text-xs text-muted" style="margin-top:.35rem;">
            Ukuran ideal: 1200×630px. Format: JPG, PNG, atau WebP.
          </div>
        </div>
        <div class="form-group">
          <label for="f-og-site-name">OG Site Name</label>
          <input type="text" id="f-og-site-name"
            placeholder="Nama website kamu"
            oninput="update()" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-og-locale">OG Locale</label>
          <select id="f-og-locale" onchange="update()">
            <option value="id_ID" selected>id_ID — Bahasa Indonesia</option>
            <option value="en_US">en_US — English (US)</option>
            <option value="en_GB">en_GB — English (UK)</option>
            <option value="ms_MY">ms_MY — Bahasa Melayu</option>
            <option value="zh_CN">zh_CN — Chinese (Simplified)</option>
            <option value="zh_TW">zh_TW — Chinese (Traditional)</option>
            <option value="ja_JP">ja_JP — Japanese</option>
            <option value="ko_KR">ko_KR — Korean</option>
            <option value="fr_FR">fr_FR — French</option>
            <option value="de_DE">de_DE — German</option>
            <option value="es_ES">es_ES — Spanish</option>
            <option value="pt_BR">pt_BR — Portuguese (Brazil)</option>
            <option value="ar_AR">ar_AR — Arabic</option>
          </select>
        </div>
        <div class="form-group">
          <label for="f-og-image-alt">OG Image Alt</label>
          <input type="text" id="f-og-image-alt"
            placeholder="Deskripsi gambar (aksesibilitas)"
            oninput="update()" />
        </div>
      </div>
    </div>

    <!-- ── Seksi: Twitter Card ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="panel-title">
        🐦 Twitter Card
        <span class="badge" style="margin-left:.5rem;">X (Twitter)</span>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-tw-card">Card Type</label>
          <select id="f-tw-card" onchange="update()">
            <option value="summary_large_image" selected>summary_large_image (gambar besar)</option>
            <option value="summary">summary (gambar kecil)</option>
            <option value="app">app</option>
            <option value="player">player</option>
          </select>
        </div>
        <div class="form-group">
          <label for="f-tw-site">Twitter @site</label>
          <input type="text" id="f-tw-site"
            placeholder="@username_website"
            oninput="update()" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="f-tw-title">Twitter Title</label>
          <input type="text" id="f-tw-title"
            placeholder="Sama dengan OG Title jika dikosongkan"
            oninput="update()" />
        </div>
        <div class="form-group">
          <label for="f-tw-creator">Twitter @creator</label>
          <input type="text" id="f-tw-creator"
            placeholder="@username_penulis"
            oninput="update()" />
        </div>
      </div>

      <div class="form-group">
        <label for="f-tw-desc">Twitter Description</label>
        <textarea id="f-tw-desc"
          placeholder="Deskripsi untuk kartu Twitter..."
          maxlength="280"
          rows="2"
          oninput="update()"></textarea>
      </div>

      <div class="form-group">
        <label for="f-tw-image">Twitter Image URL</label>
        <input type="url" id="f-tw-image"
          placeholder="https://example.com/images/twitter.jpg"
          oninput="update()" />
        <div class="text-xs text-muted" style="margin-top:.35rem;">
          summary_large_image: minimal 300×157px, ideal 1200×628px. Ukuran maks: 5MB.
        </div>
      </div>
    </div>

    <!-- ── Preview SERP ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="panel-title">👁️ Preview</div>

      <div style="display:flex; gap:.5rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <button id="prev-btn-serp"   class="btn-primary  btn-sm" onclick="switchPreview('serp')">🔍 Google SERP</button>
        <button id="prev-btn-og"     class="btn-ghost    btn-sm" onclick="switchPreview('og')">📘 Open Graph</button>
        <button id="prev-btn-tw"     class="btn-ghost    btn-sm" onclick="switchPreview('tw')">🐦 Twitter Card</button>
      </div>

      <!-- SERP Preview -->
      <div id="prev-serp">
        <div style="background:#fff; border:1px solid #dfe1e5; border-radius:8px; padding:1.25rem 1.5rem; max-width:600px; font-family:'Arial',sans-serif;">
          <div style="font-size:.75rem; color:#4d5156; margin-bottom:.25rem; display:flex; align-items:center; gap:.5rem;">
            <span style="display:inline-block; width:16px; height:16px; background:#dfe1e5; border-radius:50%;"></span>
            <span id="pv-url" style="color:#4d5156;">https://example.com › halaman</span>
          </div>
          <div id="pv-title" style="font-size:1.1rem; color:#1a0dab; font-weight:normal; margin-bottom:.2rem; cursor:pointer; line-height:1.3;">
            Judul Halaman Kamu — Brand Name
          </div>
          <div id="pv-desc" style="font-size:.875rem; color:#4d5156; line-height:1.55;">
            Deskripsi meta halaman kamu akan tampil di sini. Pastikan panjangnya 120–160 karakter agar tidak terpotong di hasil pencarian Google.
          </div>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:1rem; flex-wrap:wrap;">
          <div class="text-xs text-muted">
            <span id="pv-title-len" class="badge">Title: 0 kar</span>
          </div>
          <div class="text-xs text-muted">
            <span id="pv-desc-len" class="badge">Desc: 0 kar</span>
          </div>
        </div>
      </div>

      <!-- OG Preview -->
      <div id="prev-og" style="display:none;">
        <div style="max-width:500px; border:1px solid var(--border); border-radius:8px; overflow:hidden; font-family:'Arial',sans-serif; background:#f0f2f5;">
          <div id="pv-og-image-wrap" style="background:#c8ccd0; height:260px; display:flex; align-items:center; justify-content:center; color:#8a8d91; font-size:.85rem;">
            <span>🖼️ Gambar OG (1200×630px)</span>
          </div>
          <div style="padding:1rem; background:#fff; border-top:1px solid var(--border);">
            <div id="pv-og-site" style="font-size:.7rem; text-transform:uppercase; color:#8a8d91; margin-bottom:.3rem; letter-spacing:.05em;">EXAMPLE.COM</div>
            <div id="pv-og-title" style="font-size:.95rem; font-weight:700; color:#050505; margin-bottom:.3rem; line-height:1.35;">Judul Halaman</div>
            <div id="pv-og-desc" style="font-size:.82rem; color:#606770; line-height:1.45;">Deskripsi halaman untuk Open Graph.</div>
          </div>
        </div>
      </div>

      <!-- Twitter Preview -->
      <div id="prev-tw" style="display:none;">
        <div style="max-width:500px; border:1px solid var(--border); border-radius:12px; overflow:hidden; font-family:'-apple-system','BlinkMacSystemFont','Segoe UI',sans-serif; background:#fff;">
          <div id="pv-tw-image-wrap" style="background:#cfd9de; height:250px; display:flex; align-items:center; justify-content:center; color:#536471; font-size:.85rem;">
            <span>🖼️ Twitter Image (1200×628px)</span>
          </div>
          <div style="padding:1rem; border-top:1px solid #eff3f4;">
            <div id="pv-tw-title" style="font-size:.95rem; font-weight:700; color:#0f1419; margin-bottom:.3rem; line-height:1.35;">Judul Halaman</div>
            <div id="pv-tw-desc" style="font-size:.85rem; color:#536471; line-height:1.45; margin-bottom:.5rem;">Deskripsi untuk Twitter Card.</div>
            <div id="pv-tw-site" style="font-size:.8rem; color:#536471; display:flex; align-items:center; gap:.35rem;">
              🔗 <span>example.com</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Output HTML ── -->
    <div class="panel">
      <div class="panel-title">
        📋 Output HTML
        <div style="margin-left:auto; display:flex; gap:.5rem;">
          <button class="btn-ghost btn-sm" onclick="copyAllTags()">📋 Salin Semua</button>
          <button class="btn-ghost btn-sm" onclick="resetAll()">🗑️ Reset</button>
        </div>
      </div>

      <!-- Tab output -->
      <div style="display:flex; gap:.4rem; margin-bottom:1rem; flex-wrap:wrap; border-bottom:1px solid var(--border); padding-bottom:.75rem;">
        <button id="out-btn-all"      class="btn-primary btn-sm" onclick="switchOutput('all')">Semua Tag</button>
        <button id="out-btn-general"  class="btn-ghost   btn-sm" onclick="switchOutput('general')">General</button>
        <button id="out-btn-og"       class="btn-ghost   btn-sm" onclick="switchOutput('og')">Open Graph</button>
        <button id="out-btn-twitter"  class="btn-ghost   btn-sm" onclick="switchOutput('twitter')">Twitter</button>
      </div>

      <div class="copy-wrap">
        <textarea id="output-tags" class="result-box"
          style="min-height:220px; white-space:pre; font-size:.78rem; line-height:1.7; resize:vertical;"
          readonly
          aria-label="Output meta tag HTML"></textarea>
        <button class="copy-btn" onclick="copyAllTags()">Salin</button>
      </div>

      <!-- Tag count -->
      <div style="margin-top:.75rem; display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
        <span id="tag-count" class="badge accent">0 tag</span>
        <span class="badge" id="badge-seo-score">SEO Score: —</span>
        <span class="text-xs text-muted" style="margin-left:auto;">
          Tempel kode ini di dalam <code>&lt;head&gt;</code> halaman HTML kamu.
        </span>
      </div>
    </div>

  </div><!-- /konten utama -->

  <!-- ── Sidebar ── -->
  <aside>

    <div class="panel">
      <div class="panel-title">📊 SEO Checklist</div>
      <div id="seo-checklist" style="display:flex; flex-direction:column; gap:.5rem;">
        <!-- Diisi oleh JS -->
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">💡 Tips Meta Tag</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Title ideal: <strong>50–60 karakter</strong></li>
        <li>Description ideal: <strong>120–160 karakter</strong></li>
        <li>OG Image: <strong>1200×630px</strong> (rasio 1.91:1)</li>
        <li>Twitter Image: <strong>1200×628px</strong> (maks 5MB)</li>
        <li>Gunakan <code>canonical</code> untuk cegah duplikat konten</li>
        <li>Setiap halaman harus punya title &amp; description unik</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">⚡ Template Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <button class="btn-ghost btn-sm btn-full" onclick="loadTemplate('blog')">📝 Blog Post</button>
        <button class="btn-ghost btn-sm btn-full" onclick="loadTemplate('product')">🛍️ Halaman Produk</button>
        <button class="btn-ghost btn-sm btn-full" onclick="loadTemplate('landing')">🚀 Landing Page</button>
        <button class="btn-ghost btn-sm btn-full" onclick="loadTemplate('portfolio')">🎨 Portfolio</button>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/robots-txt-generator" class="btn-ghost btn-sm btn-full">Robots.txt Generator</a>
        <a href="/tools/sitemap-generator"    class="btn-ghost btn-sm btn-full">Sitemap Generator</a>
        <a href="/tools/word-counter"         class="btn-ghost btn-sm btn-full">Word Counter</a>
        <a href="/tools/base64"               class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>

  </aside>

</div><!-- /.tool-layout -->

<style>
/* ── Checklist item ── */
.check-item {
  display: flex;
  align-items: flex-start;
  gap: .5rem;
  font-size: .8rem;
  padding: .4rem .5rem;
  border-radius: var(--radius-sm);
  transition: background var(--transition);
}
.check-item.pass  { color: var(--accent5); }
.check-item.fail  { color: var(--muted); }
.check-item.warn  { color: var(--accent4); }
.check-icon { font-size: .85rem; flex-shrink: 0; margin-top: .05rem; }
.check-text { line-height: 1.4; }
</style>

<script>
/* ============================================================
   Meta Tag Generator — Logic
   ============================================================ */

/* ── State ── */
let currentOutput  = 'all';
let currentPreview = 'serp';

/* ── Tab: output ── */
function switchOutput(tab) {
  currentOutput = tab;
  ['all','general','og','twitter'].forEach(t => {
    document.getElementById('out-btn-' + t).className =
      t === tab ? 'btn-primary btn-sm' : 'btn-ghost btn-sm';
  });
  renderOutput();
}

/* ── Tab: preview ── */
function switchPreview(tab) {
  currentPreview = tab;
  ['serp','og','tw'].forEach(t => {
    document.getElementById('prev-' + t).style.display = t === tab ? '' : 'none';
  });
  ['serp','og','tw'].forEach(t => {
    const btn = document.getElementById('prev-btn-' + t);
    const key = t === 'og' ? 'og' : t === 'tw' ? 'tw' : 'serp';
    btn.className = key === tab ? 'btn-primary btn-sm' : 'btn-ghost btn-sm';
  });
}

/* ── Sync color picker ↔ text ── */
function syncColor(src, targetId) {
  const target = document.getElementById(targetId);
  if (target && /^#[0-9a-fA-F]{3,6}$/.test(src.value)) {
    target.value = src.value;
  }
}

/* ── Baca semua nilai form ── */
function readFields() {
  const v = id => document.getElementById(id)?.value?.trim() || '';
  return {
    title:       v('f-title'),
    desc:        v('f-desc'),
    keywords:    v('f-keywords'),
    author:      v('f-author'),
    canonical:   v('f-canonical'),
    lang:        v('f-lang'),
    robots:      v('f-robots'),
    viewport:    v('f-viewport'),
    themeColor:  v('f-theme-color'),
    charset:     v('f-charset'),
    ogTitle:     v('f-og-title'),
    ogDesc:      v('f-og-desc'),
    ogUrl:       v('f-og-url'),
    ogType:      v('f-og-type'),
    ogImage:     v('f-og-image'),
    ogSiteName:  v('f-og-site-name'),
    ogLocale:    v('f-og-locale'),
    ogImageAlt:  v('f-og-image-alt'),
    twCard:      v('f-tw-card'),
    twSite:      v('f-tw-site'),
    twTitle:     v('f-tw-title'),
    twCreator:   v('f-tw-creator'),
    twDesc:      v('f-tw-desc'),
    twImage:     v('f-tw-image'),
  };
}

/* ── Generate tag sections ── */
function buildGeneral(f) {
  const lines = [];
  lines.push('<!-- ═══ General / SEO ═══ -->');
  if (f.charset)    lines.push(`<meta charset="${esc(f.charset)}">`);
  if (f.viewport)   lines.push(`<meta name="viewport" content="${esc(f.viewport)}">`);
  if (f.title)      lines.push(`<title>${esc(f.title)}</title>`);
  if (f.desc)       lines.push(`<meta name="description" content="${esc(f.desc)}">`);
  if (f.keywords)   lines.push(`<meta name="keywords" content="${esc(f.keywords)}">`);
  if (f.author)     lines.push(`<meta name="author" content="${esc(f.author)}">`);
  if (f.robots)     lines.push(`<meta name="robots" content="${esc(f.robots)}">`);
  if (f.themeColor) lines.push(`<meta name="theme-color" content="${esc(f.themeColor)}">`);
  if (f.lang)       lines.push(`<meta http-equiv="content-language" content="${esc(f.lang)}">`);
  if (f.canonical)  lines.push(`<link rel="canonical" href="${esc(f.canonical)}">`);
  return lines;
}

function buildOG(f) {
  const title = f.ogTitle || f.title;
  const desc  = f.ogDesc  || f.desc;
  const url   = f.ogUrl   || f.canonical;
  const lines = [];
  lines.push('<!-- ═══ Open Graph ═══ -->');
  if (f.ogType)     lines.push(`<meta property="og:type" content="${esc(f.ogType)}">`);
  if (title)        lines.push(`<meta property="og:title" content="${esc(title)}">`);
  if (desc)         lines.push(`<meta property="og:description" content="${esc(desc)}">`);
  if (url)          lines.push(`<meta property="og:url" content="${esc(url)}">`);
  if (f.ogSiteName) lines.push(`<meta property="og:site_name" content="${esc(f.ogSiteName)}">`);
  if (f.ogLocale)   lines.push(`<meta property="og:locale" content="${esc(f.ogLocale)}">`);
  if (f.ogImage) {
    lines.push(`<meta property="og:image" content="${esc(f.ogImage)}">`);
    lines.push(`<meta property="og:image:width" content="1200">`);
    lines.push(`<meta property="og:image:height" content="630">`);
    if (f.ogImageAlt) lines.push(`<meta property="og:image:alt" content="${esc(f.ogImageAlt)}">`);
  }
  return lines;
}

function buildTwitter(f) {
  const title = f.twTitle || f.ogTitle || f.title;
  const desc  = f.twDesc  || f.ogDesc  || f.desc;
  const image = f.twImage || f.ogImage;
  const lines = [];
  lines.push('<!-- ═══ Twitter Card ═══ -->');
  if (f.twCard)    lines.push(`<meta name="twitter:card" content="${esc(f.twCard)}">`);
  if (title)       lines.push(`<meta name="twitter:title" content="${esc(title)}">`);
  if (desc)        lines.push(`<meta name="twitter:description" content="${esc(desc)}">`);
  if (f.twSite)    lines.push(`<meta name="twitter:site" content="${esc(f.twSite)}">`);
  if (f.twCreator) lines.push(`<meta name="twitter:creator" content="${esc(f.twCreator)}">`);
  if (image)       lines.push(`<meta name="twitter:image" content="${esc(image)}">`);
  if (image && f.ogImageAlt) lines.push(`<meta name="twitter:image:alt" content="${esc(f.ogImageAlt)}">`);
  return lines;
}

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

/* ── Render output textarea ── */
function renderOutput() {
  const f = readFields();
  let lines = [];

  if (currentOutput === 'all' || currentOutput === 'general') {
    lines = lines.concat(buildGeneral(f));
    if (currentOutput === 'all') lines.push('');
  }
  if (currentOutput === 'all' || currentOutput === 'og') {
    lines = lines.concat(buildOG(f));
    if (currentOutput === 'all') lines.push('');
  }
  if (currentOutput === 'all' || currentOutput === 'twitter') {
    lines = lines.concat(buildTwitter(f));
  }

  const output = lines.join('\n');
  document.getElementById('output-tags').value = output;

  // Tag count (hitung baris yang berisi <meta atau <title atau <link)
  const count = (output.match(/<(meta|title|link)\b/gi) || []).length;
  document.getElementById('tag-count').textContent = count + ' tag';
}

/* ── Update badges karakter ── */
function updateBadges(f) {
  const setBadge = (id, len, max, warnAt) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = `${len} / ${max}`;
    el.className = 'badge ' + (len > max ? 'danger' : len > warnAt ? 'warning' : len > 0 ? 'success' : '');
  };
  setBadge('badge-title',   f.title.length,   60,  50);
  setBadge('badge-desc',    f.desc.length,    160, 120);
  setBadge('badge-og-desc', f.ogDesc.length,  200, 150);
}

/* ── Update preview SERP ── */
function updateSERPPreview(f) {
  const title = f.title || 'Judul Halaman Kamu — Brand Name';
  const desc  = f.desc  || 'Deskripsi meta halaman kamu akan tampil di sini.';
  const url   = f.canonical || f.ogUrl || 'https://example.com › halaman';

  document.getElementById('pv-title').textContent = title;
  document.getElementById('pv-desc').textContent  = desc;
  document.getElementById('pv-url').textContent   = url;

  const tLen = document.getElementById('pv-title-len');
  const dLen = document.getElementById('pv-desc-len');
  tLen.textContent = `Title: ${f.title.length} kar`;
  tLen.className   = 'badge ' + (f.title.length > 60 ? 'danger' : f.title.length > 50 ? 'warning' : f.title.length > 0 ? 'success' : '');
  dLen.textContent = `Desc: ${f.desc.length} kar`;
  dLen.className   = 'badge ' + (f.desc.length > 160 ? 'danger' : f.desc.length > 120 ? 'warning' : f.desc.length > 0 ? 'success' : '');
}

/* ── Update preview OG ── */
function updateOGPreview(f) {
  const title    = f.ogTitle || f.title || 'Judul Halaman';
  const desc     = f.ogDesc  || f.desc  || 'Deskripsi halaman untuk Open Graph.';
  const siteName = f.ogSiteName || (f.canonical ? new URL(f.canonical.startsWith('http') ? f.canonical : 'https://' + f.canonical).hostname.toUpperCase() : 'EXAMPLE.COM');

  document.getElementById('pv-og-title').textContent = title;
  document.getElementById('pv-og-desc').textContent  = desc;
  document.getElementById('pv-og-site').textContent  = siteName;

  const imgWrap = document.getElementById('pv-og-image-wrap');
  if (f.ogImage) {
    imgWrap.innerHTML = `<img src="${esc(f.ogImage)}" alt="${esc(f.ogImageAlt || title)}"
      style="width:100%; height:100%; object-fit:cover;"
      onerror="this.parentElement.innerHTML='<span style=\\'color:#8a8d91;font-size:.85rem;\\'>⚠️ Gambar tidak dapat dimuat</span>'" />`;
  } else {
    imgWrap.innerHTML = '<span>🖼️ Gambar OG (1200×630px)</span>';
  }
}

/* ── Update preview Twitter ── */
function updateTWPreview(f) {
  const title = f.twTitle || f.ogTitle || f.title || 'Judul Halaman';
  const desc  = f.twDesc  || f.ogDesc  || f.desc  || 'Deskripsi untuk Twitter Card.';
  const url   = f.ogUrl   || f.canonical || 'example.com';
  const image = f.twImage || f.ogImage;

  document.getElementById('pv-tw-title').textContent = title;
  document.getElementById('pv-tw-desc').textContent  = desc;
  document.getElementById('pv-tw-site').innerHTML    = `🔗 <span>${url}</span>`;

  const imgWrap = document.getElementById('pv-tw-image-wrap');
  if (image) {
    imgWrap.innerHTML = `<img src="${esc(image)}" alt="${esc(title)}"
      style="width:100%; height:100%; object-fit:cover;"
      onerror="this.parentElement.innerHTML='<span style=\\'color:#536471;font-size:.85rem;\\'>⚠️ Gambar tidak dapat dimuat</span>'" />`;
  } else {
    imgWrap.innerHTML = '<span>🖼️ Twitter Image (1200×628px)</span>';
  }
}

/* ── SEO Checklist ── */
function updateChecklist(f) {
  const checks = [
    { pass: f.title.length >= 10 && f.title.length <= 60, warn: f.title.length > 60, label: 'Title (10–60 karakter)' },
    { pass: f.desc.length >= 70  && f.desc.length <= 160, warn: f.desc.length > 160, label: 'Description (70–160 karakter)' },
    { pass: !!f.canonical,   warn: false, label: 'Canonical URL' },
    { pass: !!f.ogImage,     warn: false, label: 'OG Image tersedia' },
    { pass: !!(f.ogTitle || f.title), warn: false, label: 'OG Title tersedia' },
    { pass: !!(f.ogDesc  || f.desc),  warn: false, label: 'OG Description tersedia' },
    { pass: !!f.ogSiteName, warn: false, label: 'OG Site Name' },
    { pass: !!f.twCard,     warn: false, label: 'Twitter Card type' },
    { pass: !!f.twSite,     warn: false, label: 'Twitter @site username' },
    { pass: f.robots === 'index, follow' || f.robots === '', warn: false, label: 'Robots: index, follow' },
  ];

  const score = checks.filter(c => c.pass).length;
  const pct   = Math.round(score / checks.length * 100);
  const scoreEl = document.getElementById('badge-seo-score');
  scoreEl.textContent = `SEO Score: ${pct}%`;
  scoreEl.className   = 'badge ' + (pct >= 80 ? 'success' : pct >= 50 ? 'warning' : 'danger');

  const html = checks.map(c => {
    const cls  = c.warn ? 'warn' : c.pass ? 'pass' : 'fail';
    const icon = c.warn ? '⚠️' : c.pass ? '✅' : '○';
    return `<div class="check-item ${cls}">
      <span class="check-icon">${icon}</span>
      <span class="check-text">${c.label}</span>
    </div>`;
  }).join('');

  document.getElementById('seo-checklist').innerHTML = html;
}

/* ── Master update ── */
function update() {
  const f = readFields();
  updateBadges(f);
  updateSERPPreview(f);
  updateOGPreview(f);
  updateTWPreview(f);
  updateChecklist(f);
  renderOutput();
}

/* ── Copy semua tag ── */
function copyAllTags() {
  const text = document.getElementById('output-tags').value;
  if (typeof copyToClipboard === 'function') {
    const btn = document.querySelector('.copy-btn');
    copyToClipboard(text, btn);
  } else {
    navigator.clipboard.writeText(text).then(() => {
      if (typeof showToast === 'function') showToast('✓ Semua tag berhasil disalin!', 'success');
    });
  }
}

/* ── Reset ── */
function resetAll() {
  const ids = [
    'f-title','f-desc','f-keywords','f-author','f-canonical',
    'f-og-title','f-og-desc','f-og-url','f-og-image','f-og-site-name','f-og-image-alt',
    'f-tw-site','f-tw-title','f-tw-creator','f-tw-desc','f-tw-image',
  ];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('f-lang').value      = 'id';
  document.getElementById('f-robots').value    = 'index, follow';
  document.getElementById('f-viewport').value  = 'width=device-width, initial-scale=1';
  document.getElementById('f-charset').value   = 'UTF-8';
  document.getElementById('f-og-type').value   = 'website';
  document.getElementById('f-og-locale').value = 'id_ID';
  document.getElementById('f-tw-card').value   = 'summary_large_image';
  document.getElementById('f-theme-color').value        = '#2563eb';
  document.getElementById('f-theme-color-picker').value = '#2563eb';
  update();
}

/* ── Template cepat ── */
const templates = {
  blog: {
    title:      'Judul Artikel Blog Kamu yang Menarik | Nama Blog',
    desc:       'Ringkasan artikel blog yang informatif dan menarik bagi pembaca. Berisi poin-poin penting yang akan dibahas.',
    keywords:   'blog, artikel, tutorial, panduan',
    author:     'Nama Penulis',
    ogType:     'article',
    ogSiteName: 'Nama Blog Kamu',
    twCard:     'summary_large_image',
    robots:     'index, follow',
    ogLocale:   'id_ID',
    lang:       'id',
  },
  product: {
    title:      'Nama Produk — Deskripsi Singkat | Toko Kamu',
    desc:       'Beli Nama Produk terbaik dengan harga terjangkau. Gratis ongkir, garansi resmi, dan pengiriman cepat ke seluruh Indonesia.',
    keywords:   'produk, beli online, toko, harga terbaik',
    author:     'Nama Toko',
    ogType:     'product',
    ogSiteName: 'Nama Toko Kamu',
    twCard:     'summary_large_image',
    robots:     'index, follow',
    ogLocale:   'id_ID',
    lang:       'id',
  },
  landing: {
    title:      'Solusi Terbaik untuk [Problem] — Nama Produk',
    desc:       'Temukan cara mudah mengatasi [problem] dengan Nama Produk. Bergabung bersama 10.000+ pengguna yang sudah merasakan manfaatnya.',
    keywords:   'solusi, startup, saas, aplikasi',
    ogType:     'website',
    ogSiteName: 'Nama Produk',
    twCard:     'summary_large_image',
    robots:     'index, follow',
    ogLocale:   'id_ID',
    lang:       'id',
  },
  portfolio: {
    title:      'Nama Kamu — Frontend Developer & UI Designer',
    desc:       'Portfolio Nama Kamu: frontend developer dan UI designer berpengalaman. Spesialis React, Vue, dan desain antarmuka modern.',
    keywords:   'portfolio, developer, designer, web, freelance',
    author:     'Nama Kamu',
    ogType:     'profile',
    ogSiteName: 'Portfolio Nama Kamu',
    twCard:     'summary_large_image',
    robots:     'index, follow',
    ogLocale:   'id_ID',
    lang:       'id',
  },
};

function loadTemplate(key) {
  const t = templates[key];
  if (!t) return;
  const setVal = (id, val) => {
    const el = document.getElementById(id);
    if (el && val !== undefined) el.value = val;
  };
  setVal('f-title',      t.title);
  setVal('f-desc',       t.desc);
  setVal('f-keywords',   t.keywords || '');
  setVal('f-author',     t.author   || '');
  setVal('f-og-type',    t.ogType   || 'website');
  setVal('f-og-site-name', t.ogSiteName || '');
  setVal('f-tw-card',    t.twCard   || 'summary_large_image');
  setVal('f-robots',     t.robots   || 'index, follow');
  setVal('f-og-locale',  t.ogLocale || 'id_ID');
  setVal('f-lang',       t.lang     || 'id');
  update();
  if (typeof showToast === 'function') showToast(`Template "${key}" berhasil dimuat.`, 'success');
}

/* ── Fallback utilities ── */
if (typeof copyToClipboard === 'undefined') {
  window.copyToClipboard = function(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
      const orig = btn?.textContent;
      if (btn) {
        btn.textContent = '✓ Tersalin!';
        btn.style.color = 'var(--accent5)';
        setTimeout(() => { btn.textContent = orig; btn.style.color = ''; }, 2000);
      }
    });
  };
}
if (typeof showToast === 'undefined') {
  window.showToast = function(msg, type = 'info') {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;padding:.75rem 1.25rem;
      background:var(--surface);border:1px solid var(--border);border-radius:8px;
      font-size:.875rem;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,.12);
      z-index:9998;animation:fadeUp .3s forwards;color:var(--text);`;
    if (type === 'success') t.style.borderColor = 'var(--accent5)';
    if (type === 'error')   t.style.borderColor = '#ef4444';
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .3s'; setTimeout(()=>t.remove(),300); }, 3000);
  };
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => update());
</script>

<?php require '../../includes/footer.php'; ?>