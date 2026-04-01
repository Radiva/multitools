<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tentang Kami — Multi Tools</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <style>
    /* ── About Page Specific ── */
    .about-hero {
      padding: 5rem 2.5rem 3rem;
      max-width: var(--content-max);
      margin: 0 auto;
      position: relative;
    }
    .about-hero .eyebrow {
      font-family: var(--font-mono);
      font-size: .72rem; letter-spacing: .16em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 1.2rem;
      display: flex; align-items: center; gap: .6rem;
    }
    .about-hero .eyebrow::before {
      content: '';
      display: inline-block;
      width: 32px; height: 2px;
      background: var(--accent);
    }
    .about-hero h1 {
      font-size: clamp(2.5rem, 6vw, 4.5rem);
      font-weight: 800;
      letter-spacing: -.04em;
      line-height: .95;
      margin-bottom: 1.5rem;
    }
    .about-hero h1 em {
      font-style: normal;
      -webkit-text-fill-color: transparent;
      -webkit-text-stroke: 2px var(--accent);
    }
    .about-hero .lead {
      max-width: 560px;
      font-size: 1.05rem;
      color: var(--muted);
      line-height: 1.7;
    }

    /* ── Mission Block ── */
    .mission-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }
    @media (max-width: 700px) { .mission-grid { grid-template-columns: 1fr; } }

    .mission-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2rem;
      position: relative;
      overflow: hidden;
      transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .mission-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 48px rgba(0,0,0,.09);
    }
    .mission-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--c, var(--accent));
    }
    .mission-card .num {
      font-family: var(--font-mono);
      font-size: .65rem;
      letter-spacing: .12em;
      color: var(--muted);
      margin-bottom: 1rem;
    }
    .mission-card h3 {
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: -.02em;
      margin-bottom: .6rem;
    }
    .mission-card p {
      font-size: .875rem;
      color: var(--muted);
      line-height: 1.65;
    }

    /* ── Team ── */
    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
    }
    .team-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.75rem 1.5rem;
      text-align: center;
      transition: border-color .2s, transform .2s;
    }
    .team-card:hover {
      transform: translateY(-4px);
      border-color: var(--accent);
      box-shadow: 0 12px 32px rgba(37,99,235,.1);
    }
    .team-avatar {
      width: 64px; height: 64px;
      border-radius: 50%;
      margin: 0 auto 1rem;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.8rem;
      background: rgba(37,99,235,.08);
      border: 2px solid var(--border);
    }
    .team-name { font-weight: 800; font-size: .95rem; margin-bottom: .25rem; }
    .team-role {
      font-family: var(--font-mono);
      font-size: .7rem;
      color: var(--accent);
      letter-spacing: .06em;
    }

    /* ── Values ── */
    .values-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1rem;
    }
    .value-item {
      display: flex; gap: 1rem;
      align-items: flex-start;
      padding: 1.25rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      transition: border-color .2s;
    }
    .value-item:hover { border-color: var(--accent); }
    .value-icon {
      font-size: 1.4rem; flex-shrink: 0;
      width: 40px; height: 40px;
      display: flex; align-items: center; justify-content: center;
      background: rgba(37,99,235,.08);
      border-radius: var(--radius-sm);
    }
    .value-text h4 { font-size: .9rem; font-weight: 700; margin-bottom: .3rem; }
    .value-text p  { font-size: .8rem; color: var(--muted); line-height: 1.5; }

    /* ── Timeline ── */
    .timeline {
      position: relative;
      padding-left: 2rem;
    }
    .timeline::before {
      content: '';
      position: absolute; left: 0; top: 6px; bottom: 6px;
      width: 2px;
      background: linear-gradient(to bottom, var(--accent), transparent);
    }
    .timeline-item {
      position: relative;
      margin-bottom: 2rem;
      padding-left: 1.5rem;
    }
    .timeline-item::before {
      content: '';
      position: absolute; left: -2rem;
      top: 6px;
      width: 10px; height: 10px;
      border-radius: 50%;
      background: var(--accent);
      border: 2px solid var(--bg);
      box-shadow: 0 0 0 2px var(--accent);
    }
    .timeline-year {
      font-family: var(--font-mono);
      font-size: .7rem; color: var(--accent);
      letter-spacing: .1em;
      margin-bottom: .3rem;
    }
    .timeline-title { font-weight: 700; font-size: .95rem; margin-bottom: .2rem; }
    .timeline-desc  { font-size: .85rem; color: var(--muted); line-height: 1.55; }

    /* ── CTA Banner ── */
    .cta-banner {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 3rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .cta-banner::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 60% 80% at 50% 50%, rgba(37,99,235,.06), transparent);
      pointer-events: none;
    }
    .cta-banner h2 {
      font-size: 2rem; font-weight: 800;
      letter-spacing: -.03em;
      margin-bottom: .75rem;
    }
    .cta-banner p {
      color: var(--muted); margin-bottom: 1.75rem;
      max-width: 400px; margin-inline: auto;
      margin-bottom: 1.75rem;
    }
    .cta-banner .btn-row {
      display: flex; gap: 1rem;
      justify-content: center; flex-wrap: wrap;
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
      <div class="dropdown-label">Lainnya</div>
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
  <span class="current">Tentang Kami</span>
</nav>

<main id="main">

  <!-- HERO -->
  <div class="about-hero">
    <p class="eyebrow">Tentang Kami</p>
    <h1>Dibuat untuk <em>semua</em><br>orang.</h1>
    <p class="lead">Multi Tools adalah kumpulan alat digital gratis yang dirancang agar siapa pun bisa bekerja lebih cepat, lebih cerdas, tanpa harus membayar sepeser pun.</p>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="stat">
      <span class="stat-value" data-target="48">0</span>
      <span class="stat-label">Tools tersedia</span>
    </div>
    <div class="stat">
      <span class="stat-value" data-target="120000">0</span>
      <span class="stat-label">Pengguna aktif</span>
    </div>
    <div class="stat">
      <span class="stat-value" data-target="3200000">0</span>
      <span class="stat-label">Tugas diselesaikan</span>
    </div>
    <div class="stat">
      <span class="stat-value" data-target="99">0</span>
      <span class="stat-label">% Gratis selamanya</span>
    </div>
  </div>

  <!-- MISI -->
  <section class="section">
    <div class="section-header">
      <div>
        <h2 class="section-title">Misi <span>kami</span></h2>
        <p class="section-desc">Mengapa kami membangun ini.</p>
      </div>
    </div>
    <div class="mission-grid">
      <div class="mission-card" style="--c:#2563eb">
        <p class="num">01 / AKSESIBILITAS</p>
        <h3>Gratis untuk semua</h3>
        <p>Kami percaya bahwa alat produktivitas berkualitas tinggi seharusnya bisa diakses oleh siapa pun, tanpa perlu langganan atau login yang rumit.</p>
      </div>
      <div class="mission-card" style="--c:#0ea5e9">
        <p class="num">02 / PRIVASI</p>
        <h3>Data kamu, milik kamu</h3>
        <p>Sebagian besar tools kami bekerja langsung di browser kamu. Kami tidak menyimpan, menjual, atau menggunakan data kamu untuk keperluan apa pun.</p>
      </div>
      <div class="mission-card" style="--c:#7c3aed">
        <p class="num">03 / KECEPATAN</p>
        <h3>Tanpa hambatan</h3>
        <p>Tidak ada iklan pop-up. Tidak ada loading yang lambat. Buka, pakai, selesai. Sesederhana itu.</p>
      </div>
      <div class="mission-card" style="--c:#10b981">
        <p class="num">04 / KOMUNITAS</p>
        <h3>Dibangun bersama</h3>
        <p>Setiap fitur baru lahir dari saran pengguna. Kamu bisa request tool, laporkan bug, atau berkontribusi langsung ke proyek ini.</p>
      </div>
    </div>
  </section>

  <div class="divider"><span class="divider-text">Nilai-nilai kami</span></div>

  <!-- VALUES -->
  <section class="section">
    <div class="values-list">
      <div class="value-item">
        <div class="value-icon">⚡</div>
        <div class="value-text">
          <h4>Performa Pertama</h4>
          <p>Setiap halaman dioptimalkan agar berjalan cepat di koneksi lambat sekalipun.</p>
        </div>
      </div>
      <div class="value-item">
        <div class="value-icon">🔓</div>
        <div class="value-text">
          <h4>Open & Transparan</h4>
          <p>Tidak ada dark pattern. Tidak ada paywall tersembunyi. Semua jelas dari awal.</p>
        </div>
      </div>
      <div class="value-item">
        <div class="value-icon">🎯</div>
        <div class="value-text">
          <h4>Fokus & Sederhana</h4>
          <p>Setiap tool melakukan satu hal dengan sangat baik, bukan banyak hal dengan biasa-biasa.</p>
        </div>
      </div>
      <div class="value-item">
        <div class="value-icon">🌍</div>
        <div class="value-text">
          <h4>Inklusif</h4>
          <p>Tersedia dalam bahasa Indonesia, dirancang untuk pengguna lokal dan internasional.</p>
        </div>
      </div>
      <div class="value-item">
        <div class="value-icon">♿</div>
        <div class="value-text">
          <h4>Aksesibel</h4>
          <p>Kami mengikuti standar WCAG agar semua orang, termasuk pengguna disabilitas, bisa menggunakan tools kami.</p>
        </div>
      </div>
      <div class="value-item">
        <div class="value-icon">🔒</div>
        <div class="value-text">
          <h4>Aman</h4>
          <p>Semua koneksi terenkripsi. Tidak ada tracker. Tidak ada cookie pihak ketiga.</p>
        </div>
      </div>
    </div>
  </section>

  <div class="divider"><span class="divider-text">Tim kami</span></div>

  <!-- TEAM -->
  <section class="section">
    <div class="section-header">
      <div>
        <h2 class="section-title">Orang-orang di <span>balik layar</span></h2>
      </div>
    </div>
    <div class="team-grid">
      <div class="team-card">
        <div class="team-avatar">👨‍💻</div>
        <div class="team-name">Arif Setiawan</div>
        <div class="team-role">Founder & Lead Dev</div>
      </div>
      <div class="team-card">
        <div class="team-avatar">👩‍🎨</div>
        <div class="team-name">Sinta Maharani</div>
        <div class="team-role">UI / UX Designer</div>
      </div>
      <div class="team-card">
        <div class="team-avatar">👨‍🔬</div>
        <div class="team-name">Budi Pratama</div>
        <div class="team-role">Backend Engineer</div>
      </div>
      <div class="team-card">
        <div class="team-avatar">👩‍💼</div>
        <div class="team-name">Rina Kusuma</div>
        <div class="team-role">Community Manager</div>
      </div>
    </div>
  </section>

  <div class="divider"><span class="divider-text">Perjalanan</span></div>

  <!-- TIMELINE -->
  <section class="section">
    <div class="section-header">
      <div>
        <h2 class="section-title">Sejarah <span>singkat</span></h2>
      </div>
    </div>
    <div style="max-width:520px">
      <div class="timeline">
        <div class="timeline-item">
          <p class="timeline-year">JAN 2023</p>
          <p class="timeline-title">Proyek dimulai</p>
          <p class="timeline-desc">Multi Tools lahir dari frustrasi — terlalu banyak situs yang meminta daftar akun hanya untuk mengubah format file sederhana.</p>
        </div>
        <div class="timeline-item">
          <p class="timeline-year">JUN 2023</p>
          <p class="timeline-title">10 tools pertama diluncurkan</p>
          <p class="timeline-desc">Versi awal dengan tools text & image dasar. Respons komunitas luar biasa — 5.000 pengguna dalam minggu pertama.</p>
        </div>
        <div class="timeline-item">
          <p class="timeline-year">JAN 2024</p>
          <p class="timeline-title">Kategori Dev Tools hadir</p>
          <p class="timeline-desc">Berkat request dari komunitas developer, kami menambahkan tools khusus untuk kebutuhan pengembangan web dan aplikasi.</p>
        </div>
        <div class="timeline-item">
          <p class="timeline-year">2025 – Sekarang</p>
          <p class="timeline-title">48 tools & terus berkembang</p>
          <p class="timeline-desc">Lebih dari 120.000 pengguna aktif setiap bulan dan ratusan request tools baru menunggu untuk diimplementasikan.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="section" style="padding-top:0">
    <div class="cta-banner">
      <h2>Ada saran atau ide? 💡</h2>
      <p>Kami selalu membuka diri untuk masukan. Kamu bisa request tool baru atau melaporkan masalah langsung kepada kami.</p>
      <div class="btn-row">
        <a href="/request" class="btn-primary btn-lg">Request Tool Baru</a>
        <a href="/sitemap" class="btn-ghost btn-lg">Lihat Semua Tools</a>
      </div>
    </div>
  </section>

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
</body>
</html>