<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sitemap — Multi Tools</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <style>
    /* ── Sitemap Search ── */
    .sitemap-search-wrap {
      max-width: 520px;
      margin: 0 auto 2.5rem;
      position: relative;
    }
    .sitemap-search-wrap input {
      padding-left: 2.8rem;
      font-size: .95rem;
      border-radius: 99px;
      background: var(--surface);
    }
    .sitemap-search-icon {
      position: absolute; left: 1rem; top: 50%;
      transform: translateY(-50%);
      color: var(--muted); font-size: 1rem;
      pointer-events: none;
    }

    /* ── Category Section ── */
    .cat-section {
      margin-bottom: 3rem;
    }
    .cat-header {
      display: flex; align-items: center; gap: .75rem;
      margin-bottom: 1.25rem;
      padding-bottom: .75rem;
      border-bottom: 1px solid var(--border);
    }
    .cat-icon {
      width: 36px; height: 36px;
      border-radius: var(--radius-sm);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
      background: color-mix(in srgb, var(--c, var(--accent)) 12%, transparent);
      border: 1px solid color-mix(in srgb, var(--c, var(--accent)) 25%, transparent);
      flex-shrink: 0;
    }
    .cat-title {
      font-size: 1rem; font-weight: 800;
      letter-spacing: -.01em;
    }
    .cat-count {
      font-family: var(--font-mono);
      font-size: .65rem;
      color: var(--muted);
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 99px;
      padding: 2px 8px;
      margin-left: auto;
    }

    /* ── Tool List ── */
    .tool-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: .75rem;
    }
    .tool-link {
      display: flex; align-items: center; gap: .65rem;
      padding: .65rem .9rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text);
      text-decoration: none;
      font-size: .875rem; font-weight: 600;
      transition: border-color .15s, color .15s, background .15s, transform .15s;
      position: relative;
      overflow: hidden;
    }
    .tool-link:hover {
      border-color: var(--c, var(--accent));
      color: var(--c, var(--accent));
      transform: translateX(3px);
      background: color-mix(in srgb, var(--c, var(--accent)) 5%, var(--surface));
    }
    .tool-link .tl-emoji {
      font-size: 1rem; flex-shrink: 0;
    }
    .tool-link .tl-badge {
      margin-left: auto;
      font-size: .58rem;
      font-family: var(--font-mono);
      padding: 1px 5px;
      border-radius: 4px;
      border: 1px solid;
      flex-shrink: 0;
    }
    .tl-badge.new { color: var(--accent); border-color: var(--accent); }
    .tl-badge.hot { color: #ef4444; border-color: #ef4444; }
    .tl-badge.upd { color: var(--accent5); border-color: var(--accent5); }

    /* ── Page Links ── */
    .page-links {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: .75rem;
    }
    .page-link {
      display: flex; align-items: center; gap: .65rem;
      padding: .85rem 1rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text);
      text-decoration: none;
      font-size: .875rem; font-weight: 600;
      transition: border-color .15s, color .15s, transform .15s;
    }
    .page-link:hover {
      border-color: var(--accent);
      color: var(--accent);
      transform: translateY(-2px);
    }

    /* ── No result ── */
    .no-result {
      text-align: center;
      padding: 3rem;
      color: var(--muted);
      font-size: .9rem;
      display: none;
    }
    .no-result .big { font-size: 2.5rem; display: block; margin-bottom: .5rem; }

    /* ── Sticky jump nav ── */
    .jump-nav {
      display: flex; flex-wrap: wrap; gap: .5rem;
      margin-bottom: 2rem;
    }
    .jump-nav a {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .35rem .8rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 99px;
      font-size: .78rem; font-weight: 600;
      color: var(--muted);
      text-decoration: none;
      transition: all .15s;
    }
    .jump-nav a:hover {
      border-color: var(--accent);
      color: var(--accent);
      background: rgba(37,99,235,.05);
    }
  </style>
</head>
<body>

<a class="skip-link" href="#main">Skip to content</a>

<!-- NAVBAR -->
<nav>
  <a href="/" class="nav-logo">Multi<span class="dot">.</span>Tools</a>
  <span class="nav-separator"></span>
  <div class="nav-group" id="group-tools">
    <button class="nav-btn" onclick="toggleDropdown('group-tools')">
      Tools
      <svg class="chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="dropdown">
      <div class="dropdown-label">Utilitas</div>
      <a href="/tools/text"><span class="icon" style="background:#eff6ff">📝</span> Text Tools <span class="tag hot">HOT</span></a>
      <a href="/tools/image"><span class="icon" style="background:#f0fdf4">🖼️</span> Image Tools</a>
      <a href="/tools/convert"><span class="icon" style="background:#fefce8">🔄</span> Converter</a>
      <div class="dropdown-sep"></div>
      <a href="/tools/dev"><span class="icon" style="background:#faf5ff">💻</span> Dev Tools <span class="tag new">NEW</span></a>
    </div>
  </div>
  <div class="nav-group" id="group-pages">
    <button class="nav-btn" onclick="toggleDropdown('group-pages')">
      Halaman
      <svg class="chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="dropdown">
      <a href="/about"><span class="icon" style="background:#eff6ff">ℹ️</span> Tentang Kami</a>
      <a href="/sitemap"><span class="icon" style="background:#f0fdf4">🗺️</span> Sitemap</a>
      <a href="/request"><span class="icon" style="background:#fefce8">💡</span> Request Tools</a>
    </div>
  </div>
</nav>

<!-- BREADCRUMB -->
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="/">Beranda</a>
  <span class="sep">/</span>
  <span class="current">Sitemap</span>
</nav>

<main id="main">

  <!-- HEADER -->
  <div class="section" style="padding-bottom:1rem">
    <div class="page-title">Peta <span>Situs</span></div>
    <p class="page-lead">Temukan semua halaman dan tools yang tersedia di Multi Tools. Gunakan pencarian untuk menemukan tool yang kamu butuhkan dengan cepat.</p>
  </div>

  <div class="section" style="padding-top:1.5rem">

    <!-- SEARCH -->
    <div class="sitemap-search-wrap">
      <span class="sitemap-search-icon">🔍</span>
      <input type="text" id="sitemapSearch" placeholder="Cari tool atau halaman…" autocomplete="off" />
    </div>

    <!-- JUMP NAV -->
    <div class="jump-nav" id="jumpNav">
      <a href="#cat-text">📝 Text</a>
      <a href="#cat-image">🖼️ Image</a>
      <a href="#cat-convert">🔄 Converter</a>
      <a href="#cat-dev">💻 Dev Tools</a>
      <a href="#cat-seo">📈 SEO</a>
      <a href="#cat-math">🔢 Math</a>
      <a href="#cat-pages">📄 Halaman</a>
    </div>

    <div id="noResult" class="no-result">
      <span class="big">🔭</span>
      Tidak ada hasil untuk "<span id="noResultTerm"></span>".<br>
      Coba kata kunci lain atau <a href="/request">request tool baru</a>.
    </div>

    <!-- TEXT TOOLS -->
    <div class="cat-section" id="cat-text" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#2563eb">📝</div>
        <span class="cat-title">Text Tools</span>
        <span class="cat-count">12 tools</span>
      </div>
      <div class="tool-list" style="--c:#2563eb">
        <a href="/tools/text/word-counter" class="tool-link"><span class="tl-emoji">🔢</span> Word Counter</a>
        <a href="/tools/text/case-converter" class="tool-link"><span class="tl-emoji">🔤</span> Case Converter <span class="tl-badge hot">HOT</span></a>
        <a href="/tools/text/lorem-ipsum" class="tool-link"><span class="tl-emoji">📜</span> Lorem Ipsum</a>
        <a href="/tools/text/remove-duplicate" class="tool-link"><span class="tl-emoji">🗑️</span> Remove Duplicate Lines</a>
        <a href="/tools/text/text-diff" class="tool-link"><span class="tl-emoji">↔️</span> Text Diff</a>
        <a href="/tools/text/markdown-preview" class="tool-link"><span class="tl-emoji">📋</span> Markdown Preview</a>
        <a href="/tools/text/slug-generator" class="tool-link"><span class="tl-emoji">🔗</span> Slug Generator</a>
        <a href="/tools/text/text-reverse" class="tool-link"><span class="tl-emoji">🔄</span> Text Reverse</a>
        <a href="/tools/text/char-counter" class="tool-link"><span class="tl-emoji">📊</span> Char Counter</a>
        <a href="/tools/text/text-encrypt" class="tool-link"><span class="tl-emoji">🔐</span> Text Encrypt <span class="tl-badge new">NEW</span></a>
        <a href="/tools/text/readability" class="tool-link"><span class="tl-emoji">📖</span> Readability Score</a>
        <a href="/tools/text/paraphrase" class="tool-link"><span class="tl-emoji">✍️</span> Paraphrase AI <span class="tl-badge new">NEW</span></a>
      </div>
    </div>

    <!-- IMAGE TOOLS -->
    <div class="cat-section" id="cat-image" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#0ea5e9">🖼️</div>
        <span class="cat-title">Image Tools</span>
        <span class="cat-count">9 tools</span>
      </div>
      <div class="tool-list" style="--c:#0ea5e9">
        <a href="/tools/image/compress" class="tool-link"><span class="tl-emoji">🗜️</span> Image Compress <span class="tl-badge hot">HOT</span></a>
        <a href="/tools/image/resize" class="tool-link"><span class="tl-emoji">↔️</span> Image Resize</a>
        <a href="/tools/image/crop" class="tool-link"><span class="tl-emoji">✂️</span> Image Crop</a>
        <a href="/tools/image/convert" class="tool-link"><span class="tl-emoji">🔄</span> Format Convert</a>
        <a href="/tools/image/remove-bg" class="tool-link"><span class="tl-emoji">🎭</span> Remove Background</a>
        <a href="/tools/image/watermark" class="tool-link"><span class="tl-emoji">💧</span> Add Watermark</a>
        <a href="/tools/image/exif" class="tool-link"><span class="tl-emoji">📷</span> EXIF Viewer</a>
        <a href="/tools/image/placeholder" class="tool-link"><span class="tl-emoji">📦</span> Placeholder Image</a>
        <a href="/tools/image/color-picker" class="tool-link"><span class="tl-emoji">🎨</span> Color Picker <span class="tl-badge new">NEW</span></a>
      </div>
    </div>

    <!-- CONVERTER -->
    <div class="cat-section" id="cat-convert" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#f59e0b">🔄</div>
        <span class="cat-title">Converter</span>
        <span class="cat-count">10 tools</span>
      </div>
      <div class="tool-list" style="--c:#f59e0b">
        <a href="/tools/convert/unit" class="tool-link"><span class="tl-emoji">📏</span> Unit Converter</a>
        <a href="/tools/convert/currency" class="tool-link"><span class="tl-emoji">💱</span> Currency</a>
        <a href="/tools/convert/timestamp" class="tool-link"><span class="tl-emoji">⏱️</span> Timestamp</a>
        <a href="/tools/convert/base64" class="tool-link"><span class="tl-emoji">🔡</span> Base64 Encode/Decode</a>
        <a href="/tools/convert/url-encode" class="tool-link"><span class="tl-emoji">🌐</span> URL Encode</a>
        <a href="/tools/convert/html-entity" class="tool-link"><span class="tl-emoji">🏷️</span> HTML Entities</a>
        <a href="/tools/convert/csv-json" class="tool-link"><span class="tl-emoji">📊</span> CSV ↔ JSON</a>
        <a href="/tools/convert/xml-json" class="tool-link"><span class="tl-emoji">📄</span> XML ↔ JSON <span class="tl-badge upd">UPD</span></a>
        <a href="/tools/convert/markdown-html" class="tool-link"><span class="tl-emoji">📝</span> Markdown → HTML</a>
        <a href="/tools/convert/color" class="tool-link"><span class="tl-emoji">🎨</span> Color Converter</a>
      </div>
    </div>

    <!-- DEV TOOLS -->
    <div class="cat-section" id="cat-dev" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#7c3aed">💻</div>
        <span class="cat-title">Dev Tools</span>
        <span class="cat-count">9 tools</span>
      </div>
      <div class="tool-list" style="--c:#7c3aed">
        <a href="/tools/dev/json-formatter" class="tool-link"><span class="tl-emoji">📋</span> JSON Formatter</a>
        <a href="/tools/dev/regex-tester" class="tool-link"><span class="tl-emoji">🔎</span> Regex Tester</a>
        <a href="/tools/dev/hash-generator" class="tool-link"><span class="tl-emoji">🔑</span> Hash Generator</a>
        <a href="/tools/dev/uuid" class="tool-link"><span class="tl-emoji">🆔</span> UUID Generator</a>
        <a href="/tools/dev/jwt-decoder" class="tool-link"><span class="tl-emoji">🛡️</span> JWT Decoder <span class="tl-badge new">NEW</span></a>
        <a href="/tools/dev/css-minify" class="tool-link"><span class="tl-emoji">🎨</span> CSS Minify</a>
        <a href="/tools/dev/js-minify" class="tool-link"><span class="tl-emoji">⚡</span> JS Minify</a>
        <a href="/tools/dev/cron-parser" class="tool-link"><span class="tl-emoji">⏰</span> Cron Parser</a>
        <a href="/tools/dev/ip-lookup" class="tool-link"><span class="tl-emoji">🌐</span> IP Lookup</a>
      </div>
    </div>

    <!-- SEO -->
    <div class="cat-section" id="cat-seo" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#10b981">📈</div>
        <span class="cat-title">SEO Tools</span>
        <span class="cat-count">5 tools</span>
      </div>
      <div class="tool-list" style="--c:#10b981">
        <a href="/tools/seo/meta-preview" class="tool-link"><span class="tl-emoji">👁️</span> Meta Preview</a>
        <a href="/tools/seo/og-preview" class="tool-link"><span class="tl-emoji">📢</span> OG Preview</a>
        <a href="/tools/seo/keyword-density" class="tool-link"><span class="tl-emoji">📊</span> Keyword Density</a>
        <a href="/tools/seo/robots-generator" class="tool-link"><span class="tl-emoji">🤖</span> Robots.txt Generator</a>
        <a href="/tools/seo/sitemap-gen" class="tool-link"><span class="tl-emoji">🗺️</span> Sitemap Generator <span class="tl-badge upd">UPD</span></a>
      </div>
    </div>

    <!-- MATH -->
    <div class="cat-section" id="cat-math" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#ef4444">🔢</div>
        <span class="cat-title">Math & Finance</span>
        <span class="cat-count">3 tools</span>
      </div>
      <div class="tool-list" style="--c:#ef4444">
        <a href="/tools/math/percentage" class="tool-link"><span class="tl-emoji">%</span> Percentage Calc</a>
        <a href="/tools/math/bmi" class="tool-link"><span class="tl-emoji">⚖️</span> BMI Calculator</a>
        <a href="/tools/math/loan" class="tool-link"><span class="tl-emoji">🏦</span> Loan Calculator <span class="tl-badge new">NEW</span></a>
      </div>
    </div>

    <!-- PAGES -->
    <div class="cat-section" id="cat-pages" data-cat>
      <div class="cat-header">
        <div class="cat-icon" style="--c:#64748b">📄</div>
        <span class="cat-title">Halaman</span>
        <span class="cat-count">5 halaman</span>
      </div>
      <div class="page-links">
        <a href="/" class="page-link">🏠 Beranda</a>
        <a href="/about" class="page-link">ℹ️ Tentang Kami</a>
        <a href="/sitemap" class="page-link">🗺️ Sitemap</a>
        <a href="/request" class="page-link">💡 Request Tools</a>
        <a href="/blog" class="page-link">✍️ Blog</a>
      </div>
    </div>

  </div><!-- /section -->
</main>

<!-- FOOTER -->
<footer>
  <span class="footer-logo">Multi<span class="dot">.</span>Tools</span>
  <span>© 2025 Multi Tools. Dibuat dengan ❤️ untuk semua orang.</span>
  <span>
    <a href="/about">Tentang</a> ·
    <a href="/sitemap">Sitemap</a> ·
    <a href="/request">Request</a>
  </span>
</footer>

<script src="assets/js/main.js" defer></script>
<script>
  // ── Sitemap Search ──
  const searchInput = document.getElementById('sitemapSearch');
  const noResult    = document.getElementById('noResult');
  const noResultTerm = document.getElementById('noResultTerm');
  const jumpNav     = document.getElementById('jumpNav');

  searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim().toLowerCase();
    let totalVisible = 0;

    document.querySelectorAll('[data-cat]').forEach(section => {
      const links = section.querySelectorAll('.tool-link, .page-link');
      let sectionVisible = 0;
      links.forEach(link => {
        const text = link.textContent.toLowerCase();
        const show = !q || text.includes(q);
        link.style.display = show ? '' : 'none';
        if (show) sectionVisible++;
      });
      section.style.display = sectionVisible > 0 ? '' : 'none';
      totalVisible += sectionVisible;
    });

    noResult.style.display = q && totalVisible === 0 ? 'block' : 'none';
    noResultTerm.textContent = q;
    jumpNav.style.display = q ? 'none' : '';
  });
</script>
</body>
</html>