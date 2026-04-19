<?php
require '../../includes/config.php';
/**
 * Multi Tools — Timestamp Converter
 * Konversi Unix timestamp ke tanggal/waktu dan sebaliknya.
 * ============================================================ */

$seo = [
  'title'       => 'Timestamp Converter Online — Konversi Unix Timestamp | Multi Tools',
  'description' => 'Konversi Unix timestamp ke tanggal dan waktu yang mudah dibaca, atau sebaliknya. Dukung berbagai format tanggal dan timezone. Tanpa login, langsung di browser.',
  'keywords'    => 'timestamp converter, unix timestamp, epoch converter, konversi timestamp, unix time, multi tools',
  'og_title'    => 'Timestamp Converter Online — Konversi Unix Timestamp',
  'og_desc'     => 'Konversi Unix timestamp ke tanggal/waktu yang mudah dibaca atau sebaliknya. Dukung berbagai format dan timezone.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Dev Tools',  'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'Timestamp Converter'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/timestamp-converter#webpage',
      'url'         => SITE_URL . '/tools/timestamp-converter',
      'name'        => 'Timestamp Converter Online',
      'description' => 'Konversi Unix timestamp ke tanggal/waktu dan sebaliknya.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',              'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dev Tools',            'item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Timestamp Converter',  'item' => SITE_URL . '/tools/timestamp-converter'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Timestamp Converter',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/timestamp-converter',
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

    <!-- ── Waktu Saat Ini ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="page-title">
        <span aria-hidden="true">🕐</span> Timestamp <span>Converter</span>
      </div>
      <p class="page-lead">
        Konversi Unix timestamp ke tanggal/waktu yang mudah dibaca, atau sebaliknya. Dukung berbagai format dan timezone.
      </p>

      <div style="margin-top:1.5rem; padding:1rem; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm);">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
          <div>
            <div class="text-xs text-muted text-mono" style="margin-bottom:.25rem;">UNIX TIMESTAMP SEKARANG</div>
            <div style="font-size:1.5rem; font-weight:800; letter-spacing:-.03em; font-family:var(--font-mono);" id="live-timestamp">—</div>
          </div>
          <div>
            <div class="text-xs text-muted text-mono" style="margin-bottom:.25rem;">WAKTU LOKAL SEKARANG</div>
            <div style="font-size:1rem; font-weight:600; font-family:var(--font-mono);" id="live-datetime">—</div>
          </div>
          <button class="btn-secondary btn-sm" onclick="useCurrentTimestamp()">
            ⟳ Gunakan Sekarang
          </button>
        </div>
      </div>
    </div>

    <!-- ── Timestamp → Tanggal ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="panel-title">🔢 Timestamp → Tanggal / Waktu</div>

      <div class="form-group">
        <label for="input-timestamp">Unix Timestamp (detik atau milidetik)</label>
        <div style="display:flex; gap:.75rem; align-items:stretch;">
          <input
            type="number"
            id="input-timestamp"
            placeholder="Contoh: 1704067200"
            oninput="convertTimestampToDate()"
            aria-describedby="ts-result-region"
            style="flex:1;"
          />
          <button class="btn-ghost btn-sm" onclick="useCurrentTimestamp()" style="white-space:nowrap;">
            Waktu Sekarang
          </button>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="ts-timezone">Timezone</label>
          <select id="ts-timezone" onchange="convertTimestampToDate()">
            <option value="local">Lokal (Browser)</option>
            <option value="UTC">UTC</option>
            <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
            <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
            <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
            <option value="America/New_York">America/New_York (EST)</option>
            <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
            <option value="Europe/London">Europe/London (GMT)</option>
            <option value="Europe/Paris">Europe/Paris (CET)</option>
            <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
            <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
            <option value="Australia/Sydney">Australia/Sydney (AEDT)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="ts-unit">Unit Timestamp</label>
          <select id="ts-unit" onchange="convertTimestampToDate()">
            <option value="auto">Otomatis (deteksi)</option>
            <option value="seconds">Detik (s)</option>
            <option value="milliseconds">Milidetik (ms)</option>
          </select>
        </div>
      </div>

      <!-- Hasil konversi -->
      <div id="ts-result-region" role="region" aria-live="polite" aria-label="Hasil konversi timestamp">
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:.75rem;" id="ts-results">
          <!-- Diisi oleh JS -->
          <div class="result-box" style="color:var(--muted); font-family:var(--font-body); font-size:.85rem;">
            Masukkan timestamp di atas untuk melihat hasil konversi.
          </div>
        </div>
      </div>
    </div>

    <!-- ── Tanggal → Timestamp ── -->
    <div class="panel">
      <div class="panel-title">📅 Tanggal / Waktu → Timestamp</div>

      <div class="form-row">
        <div class="form-group">
          <label for="input-date">Tanggal</label>
          <input type="date" id="input-date" oninput="convertDateToTimestamp()" />
        </div>
        <div class="form-group">
          <label for="input-time">Waktu</label>
          <input type="time" id="input-time" value="00:00:00" step="1" oninput="convertDateToTimestamp()" />
        </div>
      </div>

      <div class="form-group">
        <label for="date-timezone">Timezone</label>
        <select id="date-timezone" onchange="convertDateToTimestamp()">
          <option value="local">Lokal (Browser)</option>
          <option value="UTC">UTC</option>
          <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
          <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
          <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
          <option value="America/New_York">America/New_York (EST)</option>
          <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
          <option value="Europe/London">Europe/London (GMT)</option>
          <option value="Europe/Paris">Europe/Paris (CET)</option>
          <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
          <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
          <option value="Australia/Sydney">Australia/Sydney (AEDT)</option>
        </select>
      </div>

      <div id="date-result-region" role="region" aria-live="polite" aria-label="Hasil konversi tanggal">
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:.75rem;" id="date-results">
          <div class="result-box" style="color:var(--muted); font-family:var(--font-body); font-size:.85rem;">
            Pilih tanggal dan waktu di atas untuk melihat hasil.
          </div>
        </div>
      </div>

      <div style="margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap;">
        <button class="btn-ghost btn-sm" onclick="resetDateForm()">
          Bersihkan
        </button>
        <button class="btn-ghost btn-sm" onclick="useToday()">
          Gunakan Hari Ini
        </button>
      </div>
    </div>

  </div><!-- /konten utama -->

  <!-- ── Sidebar ── -->
  <aside>
    <div class="panel">
      <div class="panel-title">💡 Tips</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Unix timestamp dimulai dari <strong>1 Januari 1970 00:00:00 UTC</strong></li>
        <li>Timestamp dalam <strong>detik</strong> biasanya 10 digit</li>
        <li>Timestamp dalam <strong>milidetik</strong> biasanya 13 digit</li>
        <li>JavaScript menggunakan milidetik (<code>Date.now()</code>)</li>
        <li>PHP, Python, Unix menggunakan detik (<code>time()</code>)</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">📋 Format Output</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <div class="alert info" style="margin-bottom:0;">
          <span>🌐</span>
          <span class="text-sm">ISO 8601: <code>2024-01-01T00:00:00Z</code></span>
        </div>
        <div class="alert info" style="margin-bottom:0;">
          <span>📅</span>
          <span class="text-sm">RFC 2822: <code>Mon, 01 Jan 2024 00:00:00 +0000</code></span>
        </div>
        <div class="alert info" style="margin-bottom:0;">
          <span>🇮🇩</span>
          <span class="text-sm">Lokal ID: <code>1 Januari 2024, 00.00</code></span>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/base64"          class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
        <a href="/tools/json-formatter"  class="btn-ghost btn-sm btn-full">JSON Formatter</a>
        <a href="/tools/hash-generator"  class="btn-ghost btn-sm btn-full">Hash Generator</a>
        <a href="/tools/color-converter" class="btn-ghost btn-sm btn-full">Color Converter</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ============================================================
   Timestamp Converter — Logic
   ============================================================ */

/* ── Live clock ── */
function updateLiveClock() {
  const now = Date.now();
  const ts  = Math.floor(now / 1000);
  const el_ts = document.getElementById('live-timestamp');
  const el_dt = document.getElementById('live-datetime');
  if (el_ts) el_ts.textContent = ts;
  if (el_dt) el_dt.textContent = new Date(now).toLocaleString('id-ID', {
    dateStyle: 'long', timeStyle: 'medium'
  });
}
updateLiveClock();
setInterval(updateLiveClock, 1000);

/* ── Helper: parse timestamp ── */
function parseTimestamp(raw, unit) {
  const val = parseFloat(raw);
  if (isNaN(val)) return null;
  if (unit === 'milliseconds') return val;
  if (unit === 'seconds')      return val * 1000;
  // auto-detect: 13+ digit = ms
  return String(Math.abs(Math.floor(val))).length >= 13 ? val : val * 1000;
}

/* ── Helper: format date dalam berbagai cara ── */
function formatAll(ms, tzOption) {
  const d    = new Date(ms);
  const tz   = (tzOption === 'local' || !tzOption) ? undefined : tzOption;
  const opts = tz ? { timeZone: tz } : {};

  const fmtISO = () => {
    if (!tz) return d.toISOString();
    // ISO dengan timezone
    const parts = new Intl.DateTimeFormat('sv-SE', {
      ...opts,
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit', second: '2-digit',
      hour12: false,
    }).formatToParts(d);
    const get = type => parts.find(p => p.type === type)?.value || '00';
    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}:${get('second')}`;
  };

  const fmtRFC = () => d.toUTCString();

  const fmtLocaleID = () => d.toLocaleString('id-ID', {
    ...opts, dateStyle: 'full', timeStyle: 'long'
  });

  const fmtLocaleEN = () => d.toLocaleString('en-US', {
    ...opts, dateStyle: 'full', timeStyle: 'long'
  });

  const fmtRelative = () => {
    const diff  = Date.now() - ms;
    const abs   = Math.abs(diff);
    const past  = diff > 0;
    const sec   = Math.floor(abs / 1000);
    const min   = Math.floor(sec / 60);
    const hour  = Math.floor(min / 60);
    const day   = Math.floor(hour / 24);
    const month = Math.floor(day / 30);
    const year  = Math.floor(day / 365);
    let str;
    if (sec  < 60)   str = `${sec} detik`;
    else if (min < 60)   str = `${min} menit`;
    else if (hour < 24)  str = `${hour} jam`;
    else if (day < 30)   str = `${day} hari`;
    else if (month < 12) str = `${month} bulan`;
    else                 str = `${year} tahun`;
    return past ? `${str} yang lalu` : `${str} lagi`;
  };

  const fmtDay = () => d.toLocaleString('id-ID', {
    ...opts, weekday: 'long'
  });

  return {
    iso:       fmtISO(),
    rfc:       fmtRFC(),
    localeID:  fmtLocaleID(),
    localeEN:  fmtLocaleEN(),
    relative:  fmtRelative(),
    weekday:   fmtDay(),
    ms:        ms,
    seconds:   Math.floor(ms / 1000),
  };
}

/* ── Helper: buat card hasil ── */
function makeResultCard(label, value, copyable = true) {
  const id = 'res-' + Math.random().toString(36).slice(2, 8);
  return `
    <div>
      <div class="text-xs text-muted text-mono" style="margin-bottom:.35rem; letter-spacing:.05em;">${label}</div>
      <div class="copy-wrap">
        <div class="result-box" id="${id}" style="min-height:unset; padding:.65rem .9rem; font-size:.82rem;">${escHtml(String(value))}</div>
        ${copyable ? `<button class="copy-btn" onclick="copyToClipboard('${escAttr(String(value))}', this)">Salin</button>` : ''}
      </div>
    </div>
  `;
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escAttr(s) {
  return s.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

/* ── Timestamp → Date ── */
function convertTimestampToDate() {
  const raw     = document.getElementById('input-timestamp').value.trim();
  const unit    = document.getElementById('ts-unit').value;
  const tz      = document.getElementById('ts-timezone').value;
  const container = document.getElementById('ts-results');

  if (!raw) {
    container.innerHTML = `<div class="result-box" style="color:var(--muted); font-family:var(--font-body); font-size:.85rem;">Masukkan timestamp di atas untuk melihat hasil konversi.</div>`;
    return;
  }

  const ms = parseTimestamp(raw, unit);
  if (ms === null || isNaN(ms)) {
    container.innerHTML = `<div class="result-box error">❌ Timestamp tidak valid.</div>`;
    return;
  }

  const f = formatAll(ms, tz);

  container.innerHTML = `
    ${makeResultCard('Unix Timestamp (detik)', f.seconds)}
    ${makeResultCard('Unix Timestamp (milidetik)', f.ms)}
    ${makeResultCard('ISO 8601', f.iso)}
    ${makeResultCard('RFC 2822 / HTTP Date', f.rfc)}
    ${makeResultCard('Format Lokal (Indonesia)', f.localeID)}
    ${makeResultCard('Format Lokal (English)', f.localeEN)}
    ${makeResultCard('Hari dalam Seminggu', f.weekday, false)}
    ${makeResultCard('Relatif ke Sekarang', f.relative, false)}
  `;
}

/* ── Date → Timestamp ── */
function convertDateToTimestamp() {
  const dateVal = document.getElementById('input-date').value;
  const timeVal = document.getElementById('input-time').value || '00:00:00';
  const tz      = document.getElementById('date-timezone').value;
  const container = document.getElementById('date-results');

  if (!dateVal) {
    container.innerHTML = `<div class="result-box" style="color:var(--muted); font-family:var(--font-body); font-size:.85rem;">Pilih tanggal dan waktu di atas untuk melihat hasil.</div>`;
    return;
  }

  let ms;
  const dtStr = `${dateVal}T${timeVal}`;

  try {
    if (tz === 'local' || !tz) {
      ms = new Date(dtStr).getTime();
    } else if (tz === 'UTC') {
      ms = new Date(dtStr + 'Z').getTime();
    } else {
      // Gunakan Intl untuk timezone lain
      const tmpDate   = new Date(dtStr + 'Z'); // parse as UTC dulu
      const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: tz,
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false,
      });
      // Hitung offset dengan membandingkan UTC dan lokal tz
      const nowUtc = Date.now();
      const tzDate = new Date(nowUtc);
      const utcStr = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'UTC',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false,
      }).format(tzDate).replace(', ', 'T').replace(/(\d{2})\.(\d{2})\.(\d{2})$/, '$1:$2:$3');
      const tzStr = formatter.format(tzDate).replace(', ', 'T').replace(/(\d{2})\.(\d{2})\.(\d{2})$/, '$1:$2:$3');
      const offset = new Date(utcStr + 'Z').getTime() - new Date(tzStr + 'Z').getTime();
      ms = new Date(dtStr + 'Z').getTime() + offset;
    }
  } catch(err) {
    container.innerHTML = `<div class="result-box error">❌ Gagal memproses tanggal/timezone.</div>`;
    return;
  }

  if (isNaN(ms)) {
    container.innerHTML = `<div class="result-box error">❌ Tanggal tidak valid.</div>`;
    return;
  }

  const f = formatAll(ms, tz);

  container.innerHTML = `
    ${makeResultCard('Unix Timestamp (detik)', f.seconds)}
    ${makeResultCard('Unix Timestamp (milidetik)', f.ms)}
    ${makeResultCard('ISO 8601', f.iso)}
    ${makeResultCard('RFC 2822 / HTTP Date', f.rfc)}
    ${makeResultCard('Format Lokal (Indonesia)', f.localeID)}
    ${makeResultCard('Format Lokal (English)', f.localeEN)}
    ${makeResultCard('Hari dalam Seminggu', f.weekday, false)}
    ${makeResultCard('Relatif ke Sekarang', f.relative, false)}
  `;
}

/* ── Gunakan waktu sekarang ── */
function useCurrentTimestamp() {
  const ts = Math.floor(Date.now() / 1000);
  document.getElementById('input-timestamp').value = ts;
  document.getElementById('ts-unit').value = 'seconds';
  convertTimestampToDate();
}

/* ── Gunakan hari ini ── */
function useToday() {
  const now = new Date();
  const pad = n => String(n).padStart(2, '0');
  document.getElementById('input-date').value =
    `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
  document.getElementById('input-time').value =
    `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
  convertDateToTimestamp();
}

/* ── Reset form tanggal ── */
function resetDateForm() {
  document.getElementById('input-date').value = '';
  document.getElementById('input-time').value = '00:00:00';
  document.getElementById('date-results').innerHTML = `
    <div class="result-box" style="color:var(--muted); font-family:var(--font-body); font-size:.85rem;">
      Pilih tanggal dan waktu di atas untuk melihat hasil.
    </div>`;
}

/* ── Override copyToClipboard lokal jika main.js belum dimuat ── */
if (typeof copyToClipboard === 'undefined') {
  window.copyToClipboard = function(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
      const original = btn?.textContent;
      if (btn) {
        btn.textContent = '✓ Tersalin!';
        btn.style.color = 'var(--accent5)';
        setTimeout(() => { btn.textContent = original; btn.style.color = ''; }, 2000);
      }
    });
  };
}
</script>

<?php require '../../includes/footer.php'; ?>