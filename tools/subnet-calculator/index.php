<?php
require '../../includes/config.php';
/**
 * Multi Tools — Subnet Calculator
 * Kalkulasi subnet IPv4: network address, broadcast, host range, dsb.
 * ============================================================ */

$seo = [
  'title'       => 'Subnet Calculator Online — Kalkulator Subnetting IPv4 | Multi Tools',
  'description' => 'Hitung subnet IPv4 secara instan: network address, broadcast address, range host, jumlah host, wildcard mask, dan binary mask. Dukung notasi CIDR dan subnet mask.',
  'keywords'    => 'subnet calculator, subnetting, cidr calculator, ip calculator, network address, broadcast address, wildcard mask, multi tools',
  'og_title'    => 'Subnet Calculator Online — Kalkulator Subnetting IPv4',
  'og_desc'     => 'Hitung subnet IPv4: network address, broadcast, host range, jumlah host, wildcard mask, dan binary. Mendukung CIDR dan subnet mask.',
  'breadcrumbs' => [
    ['name' => 'Beranda',    'url' => SITE_URL . '/'],
    ['name' => 'Dev Tools',  'url' => SITE_URL . '/tools?cat=dev'],
    ['name' => 'Subnet Calculator'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/subnet-calculator#webpage',
      'url'         => SITE_URL . '/tools/subnet-calculator',
      'name'        => 'Subnet Calculator Online',
      'description' => 'Hitung subnet IPv4: network address, broadcast, host range, jumlah host, wildcard mask.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',             'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dev Tools',           'item' => SITE_URL . '/tools?cat=dev'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Subnet Calculator',   'item' => SITE_URL . '/tools/subnet-calculator'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Subnet Calculator',
      'applicationCategory' => 'DeveloperApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/subnet-calculator',
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

    <!-- ── Input Panel ── -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="page-title">
        <span aria-hidden="true">🌐</span> Subnet <span>Calculator</span>
      </div>
      <p class="page-lead">
        Hitung informasi subnet IPv4 secara instan. Masukkan IP address dengan prefix CIDR atau subnet mask.
      </p>

      <div style="margin-top:1.5rem;">
        <!-- Mode toggle -->
        <div style="display:flex; gap:.5rem; margin-bottom:1.25rem; flex-wrap:wrap;">
          <button id="tab-cidr"   class="btn-primary  btn-sm" onclick="switchTab('cidr')">Notasi CIDR</button>
          <button id="tab-mask"   class="btn-ghost    btn-sm" onclick="switchTab('mask')">Subnet Mask</button>
          <button id="tab-split"  class="btn-ghost    btn-sm" onclick="switchTab('split')">Subnet Splitter</button>
        </div>

        <!-- ── Tab: CIDR ── -->
        <div id="panel-cidr">
          <div class="form-group">
            <label for="input-cidr">IP Address / CIDR</label>
            <input
              type="text"
              id="input-cidr"
              placeholder="Contoh: 192.168.1.0/24 atau 10.0.0.1/8"
              oninput="calcCIDR()"
              autocomplete="off"
              spellcheck="false"
            />
          </div>

          <!-- Quick presets -->
          <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem;">
            <span class="text-xs text-muted" style="align-self:center;">Contoh:</span>
            <?php
            $presets = [
              '192.168.1.0/24',
              '10.0.0.0/8',
              '172.16.0.0/12',
              '192.168.0.0/16',
              '10.10.10.0/28',
            ];
            foreach ($presets as $p):
            ?>
              <button class="badge accent" style="cursor:pointer;" onclick="usePreset('<?= $p ?>')"><?= $p ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ── Tab: Subnet Mask ── -->
        <div id="panel-mask" style="display:none;">
          <div class="form-row">
            <div class="form-group">
              <label for="input-ip-mask">IP Address</label>
              <input
                type="text"
                id="input-ip-mask"
                placeholder="Contoh: 192.168.1.0"
                oninput="calcMask()"
                autocomplete="off"
                spellcheck="false"
              />
            </div>
            <div class="form-group">
              <label for="input-subnet-mask">Subnet Mask</label>
              <input
                type="text"
                id="input-subnet-mask"
                placeholder="Contoh: 255.255.255.0"
                oninput="calcMask()"
                autocomplete="off"
                spellcheck="false"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="select-common-mask">Atau pilih mask umum:</label>
            <select id="select-common-mask" onchange="applyCommonMask()">
              <option value="">— Pilih Subnet Mask —</option>
              <option value="255.0.0.0">/8 — Class A (255.0.0.0)</option>
              <option value="255.128.0.0">/9 (255.128.0.0)</option>
              <option value="255.192.0.0">/10 (255.192.0.0)</option>
              <option value="255.224.0.0">/11 (255.224.0.0)</option>
              <option value="255.240.0.0">/12 (255.240.0.0)</option>
              <option value="255.248.0.0">/13 (255.248.0.0)</option>
              <option value="255.252.0.0">/14 (255.252.0.0)</option>
              <option value="255.254.0.0">/15 (255.254.0.0)</option>
              <option value="255.255.0.0">/16 — Class B (255.255.0.0)</option>
              <option value="255.255.128.0">/17 (255.255.128.0)</option>
              <option value="255.255.192.0">/18 (255.255.192.0)</option>
              <option value="255.255.224.0">/19 (255.255.224.0)</option>
              <option value="255.255.240.0">/20 (255.255.240.0)</option>
              <option value="255.255.248.0">/21 (255.255.248.0)</option>
              <option value="255.255.252.0">/22 (255.255.252.0)</option>
              <option value="255.255.254.0">/23 (255.255.254.0)</option>
              <option value="255.255.255.0">/24 — Class C (255.255.255.0)</option>
              <option value="255.255.255.128">/25 (255.255.255.128)</option>
              <option value="255.255.255.192">/26 (255.255.255.192)</option>
              <option value="255.255.255.224">/27 (255.255.255.224)</option>
              <option value="255.255.255.240">/28 (255.255.255.240)</option>
              <option value="255.255.255.248">/29 (255.255.255.248)</option>
              <option value="255.255.255.252">/30 (255.255.255.252)</option>
              <option value="255.255.255.254">/31 (255.255.255.254)</option>
              <option value="255.255.255.255">/32 — Host (255.255.255.255)</option>
            </select>
          </div>
        </div>

        <!-- ── Tab: Subnet Splitter ── -->
        <div id="panel-split" style="display:none;">
          <div class="form-row">
            <div class="form-group">
              <label for="input-split-network">Network (CIDR)</label>
              <input
                type="text"
                id="input-split-network"
                placeholder="Contoh: 192.168.0.0/24"
                autocomplete="off"
                spellcheck="false"
              />
            </div>
            <div class="form-group">
              <label for="input-split-prefix">Bagi menjadi prefix</label>
              <select id="input-split-prefix">
                <?php for ($i = 1; $i <= 32; $i++): ?>
                  <option value="<?= $i ?>" <?= $i === 26 ? 'selected' : '' ?>>/<?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <button class="btn-primary btn-sm" onclick="calcSplit()">Hitung Subnet</button>
        </div>
      </div>
    </div>

    <!-- ── Hasil ── -->
    <div id="result-area" style="display:none;">

      <!-- Summary stats -->
      <div class="stats" style="border-radius:var(--radius-lg); border:1px solid var(--border); margin-bottom:1.5rem; overflow:hidden;" id="result-stats">
      </div>

      <!-- Detail rows -->
      <div class="panel" style="margin-bottom:1.5rem;" id="result-detail">
      </div>

      <!-- Binary visualizer -->
      <div class="panel" id="result-binary" style="margin-bottom:1.5rem;">
        <div class="panel-title">📊 Visualisasi Binary</div>
        <div style="overflow-x:auto;">
          <table id="binary-table" style="width:100%; border-collapse:collapse; font-family:var(--font-mono); font-size:.75rem;"></table>
        </div>
      </div>

    </div>

    <!-- Subnet splitter result -->
    <div id="split-result-area" style="display:none;">
      <div class="panel">
        <div class="panel-title">📋 Daftar Subnet</div>
        <div style="overflow-x:auto;">
          <table id="split-table" style="width:100%; border-collapse:collapse; font-size:.82rem;"></table>
        </div>
      </div>
    </div>

  </div><!-- /konten utama -->

  <!-- ── Sidebar ── -->
  <aside>
    <div class="panel">
      <div class="panel-title">📖 Referensi CIDR</div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:.78rem; font-family:var(--font-mono);">
          <thead>
            <tr style="border-bottom:1px solid var(--border); color:var(--muted);">
              <th style="text-align:left; padding:.35rem .5rem; font-weight:700;">Prefix</th>
              <th style="text-align:left; padding:.35rem .5rem; font-weight:700;">Hosts</th>
              <th style="text-align:left; padding:.35rem .5rem; font-weight:700;">Mask</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $cidr_ref = [
              [8,  16777214, '255.0.0.0'],
              [16, 65534,    '255.255.0.0'],
              [24, 254,      '255.255.255.0'],
              [25, 126,      '255.255.255.128'],
              [26, 62,       '255.255.255.192'],
              [27, 30,       '255.255.255.224'],
              [28, 14,       '255.255.255.240'],
              [29, 6,        '255.255.255.248'],
              [30, 2,        '255.255.255.252'],
              [32, 1,        '255.255.255.255'],
            ];
            foreach ($cidr_ref as $row):
            ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:.35rem .5rem; color:var(--accent);">/<?= $row[0] ?></td>
                <td style="padding:.35rem .5rem;"><?= number_format($row[1]) ?></td>
                <td style="padding:.35rem .5rem; color:var(--muted);"><?= $row[2] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🏠 IP Private Range</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li><strong>Class A:</strong> 10.0.0.0/8</li>
        <li><strong>Class B:</strong> 172.16.0.0/12</li>
        <li><strong>Class C:</strong> 192.168.0.0/16</li>
        <li><strong>Loopback:</strong> 127.0.0.0/8</li>
        <li><strong>Link-local:</strong> 169.254.0.0/16</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">💡 Tips</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>Host usable = 2ⁿ − 2 (n = host bits)</li>
        <li>/31 dan /32 adalah pengecualian (point-to-point)</li>
        <li>Wildcard mask = kebalikan subnet mask</li>
        <li>Network address = IP AND mask</li>
        <li>Broadcast = Network OR wildcard</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/ip-lookup"          class="btn-ghost btn-sm btn-full">IP Lookup</a>
        <a href="/tools/dns-lookup"         class="btn-ghost btn-sm btn-full">DNS Lookup</a>
        <a href="/tools/timestamp-converter" class="btn-ghost btn-sm btn-full">Timestamp Converter</a>
        <a href="/tools/base64"             class="btn-ghost btn-sm btn-full">Base64 Encode/Decode</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<style>
/* ── Tabel hasil ── */
.result-table td, .result-table th {
  padding: .6rem .9rem;
  border-bottom: 1px solid var(--border);
  font-size: .85rem;
  vertical-align: middle;
}
.result-table th {
  font-size: .7rem;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--muted);
  font-family: var(--font-mono);
  font-weight: 700;
  background: var(--bg);
}
.result-table tr:last-child td { border-bottom: none; }
.result-table td:first-child {
  color: var(--muted);
  font-size: .78rem;
  font-family: var(--font-mono);
  letter-spacing: .04em;
  width: 180px;
  white-space: nowrap;
}
.result-table td:last-child {
  font-family: var(--font-mono);
  font-weight: 600;
}
.result-table .copy-btn {
  position: static;
  margin-left: .5rem;
  vertical-align: middle;
}

/* Binary table */
#binary-table th {
  padding: .35rem .5rem;
  text-align: center;
  font-size: .65rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--muted);
  font-family: var(--font-mono);
  background: var(--bg);
  border-bottom: 1px solid var(--border);
}
#binary-table td {
  padding: .4rem .3rem;
  text-align: center;
  font-family: var(--font-mono);
  font-size: .78rem;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
#binary-table td.bit-label {
  text-align: left;
  padding-left: .75rem;
  color: var(--muted);
  font-size: .7rem;
  letter-spacing: .04em;
  min-width: 120px;
}
#binary-table td.bit-1 { color: var(--accent); font-weight: 700; }
#binary-table td.bit-0 { color: var(--muted); }
#binary-table td.bit-sep {
  color: var(--border);
  padding: 0 .1rem;
  font-size: .9rem;
}
#binary-table td.net-bit { background: rgba(37,99,235,.07); }
#binary-table td.host-bit { background: rgba(245,158,11,.06); }

/* Split table */
#split-table th {
  padding: .5rem .75rem;
  text-align: left;
  font-size: .7rem;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--muted);
  font-family: var(--font-mono);
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
#split-table td {
  padding: .5rem .75rem;
  font-family: var(--font-mono);
  font-size: .8rem;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
#split-table tr:last-child td { border-bottom: none; }
#split-table tr:hover td { background: rgba(37,99,235,.04); }
#split-table td:first-child {
  color: var(--muted);
  font-size: .75rem;
  text-align: center;
  width: 50px;
}
</style>

<script>
/* ============================================================
   Subnet Calculator — Logic
   ============================================================ */

/* ── Tab switching ── */
function switchTab(tab) {
  ['cidr','mask','split'].forEach(t => {
    document.getElementById('panel-' + t).style.display  = (t === tab) ? '' : 'none';
    const btn = document.getElementById('tab-' + t);
    btn.className = (t === tab) ? 'btn-primary btn-sm' : 'btn-ghost btn-sm';
  });
  document.getElementById('result-area').style.display       = 'none';
  document.getElementById('split-result-area').style.display = 'none';
}

/* ── IP helpers ── */
function ipToLong(ip) {
  const parts = ip.trim().split('.');
  if (parts.length !== 4) return null;
  let num = 0;
  for (let i = 0; i < 4; i++) {
    const n = parseInt(parts[i], 10);
    if (isNaN(n) || n < 0 || n > 255) return null;
    num = (num << 8) | n;
  }
  return num >>> 0; // unsigned
}

function longToIp(n) {
  return [
    (n >>> 24) & 0xff,
    (n >>> 16) & 0xff,
    (n >>>  8) & 0xff,
    (n >>>  0) & 0xff,
  ].join('.');
}

function prefixToMaskLong(prefix) {
  if (prefix === 0) return 0;
  return (0xffffffff << (32 - prefix)) >>> 0;
}

function maskLongToPrefix(mask) {
  let bits = 0;
  let m = mask >>> 0;
  while (m & 0x80000000) { bits++; m = (m << 1) >>> 0; }
  return bits;
}

function isValidMask(maskLong) {
  // Subnet mask must be contiguous 1s followed by 0s
  const inv = (~maskLong) >>> 0;
  return ((inv & (inv + 1)) === 0);
}

function ipToBinary(ip) {
  return ipToLong(ip).toString(2).padStart(32, '0');
}

function longToBinary(n) {
  return (n >>> 0).toString(2).padStart(32, '0');
}

/* ── Hitung semua info subnet ── */
function calcSubnet(ipLong, prefix) {
  const maskLong      = prefixToMaskLong(prefix);
  const networkLong   = (ipLong & maskLong) >>> 0;
  const wildcardLong  = (~maskLong) >>> 0;
  const broadcastLong = (networkLong | wildcardLong) >>> 0;
  const totalHosts    = Math.pow(2, 32 - prefix);
  const usableHosts   = prefix <= 30 ? totalHosts - 2 : (prefix === 31 ? 2 : 1);
  const firstHost     = prefix <= 30 ? networkLong + 1 : networkLong;
  const lastHost      = prefix <= 30 ? broadcastLong - 1 : broadcastLong;

  const ipClass = (() => {
    const first = (ipLong >>> 24) & 0xff;
    if (first < 128)       return 'A';
    if (first < 192)       return 'B';
    if (first < 224)       return 'C';
    if (first < 240)       return 'D (Multicast)';
    return 'E (Reserved)';
  })();

  const isPrivate = (() => {
    const a = (ipLong >>> 24) & 0xff;
    const b = (ipLong >>> 16) & 0xff;
    if (a === 10) return true;
    if (a === 172 && b >= 16 && b <= 31) return true;
    if (a === 192 && b === 168) return true;
    if (a === 127) return true;
    if (a === 169 && b === 254) return true;
    return false;
  })();

  return {
    ip:          longToIp(ipLong),
    ipLong, prefix,
    mask:        longToIp(maskLong),
    maskLong,
    wildcard:    longToIp(wildcardLong),
    wildcardLong,
    network:     longToIp(networkLong),
    networkLong,
    broadcast:   longToIp(broadcastLong),
    broadcastLong,
    firstHost:   prefix <= 31 ? longToIp(firstHost) : longToIp(networkLong),
    lastHost:    prefix <= 31 ? longToIp(lastHost)  : longToIp(broadcastLong),
    totalHosts,
    usableHosts,
    ipClass,
    isPrivate,
    cidr:        longToIp(networkLong) + '/' + prefix,
  };
}

/* ── Render hasil ── */
function renderResult(s) {
  document.getElementById('result-area').style.display       = '';
  document.getElementById('split-result-area').style.display = 'none';

  // Stats bar
  const stats = document.getElementById('result-stats');
  const fmtHosts = n => n >= 1e6 ? (n/1e6).toFixed(2)+'M' : n >= 1e3 ? (n/1e3).toFixed(1)+'K' : n.toString();
  stats.innerHTML = `
    <div class="stat">
      <span class="stat-value">/${s.prefix}</span>
      <span class="stat-label">CIDR Prefix</span>
    </div>
    <div class="stat">
      <span class="stat-value" style="font-size:1.35rem;">${fmtHosts(s.usableHosts)}</span>
      <span class="stat-label">Host Usable</span>
    </div>
    <div class="stat">
      <span class="stat-value" style="font-size:1.35rem;">${fmtHosts(s.totalHosts)}</span>
      <span class="stat-label">Total Alamat</span>
    </div>
    <div class="stat">
      <span class="stat-value" style="font-size:1.1rem;">${s.ipClass}</span>
      <span class="stat-label">Kelas IP</span>
    </div>
    <div class="stat" style="border-right:none;">
      <span class="stat-value" style="font-size:1.1rem;">${s.isPrivate ? '🔒 Private' : '🌐 Public'}</span>
      <span class="stat-label">Tipe IP</span>
    </div>
  `;

  // Detail table
  const detail = document.getElementById('result-detail');
  const row = (label, value) => `
    <tr>
      <td>${label}</td>
      <td>
        ${escHtml(value)}
        <button class="copy-btn" onclick="copyToClipboard('${escAttr(value)}', this)">Salin</button>
      </td>
    </tr>`;

  detail.innerHTML = `
    <div class="panel-title">🔍 Detail Subnet</div>
    <div style="overflow-x:auto;">
      <table class="result-table" style="width:100%; border-collapse:collapse;">
        <thead><tr><th>Parameter</th><th>Nilai</th></tr></thead>
        <tbody>
          ${row('IP Address', s.ip)}
          ${row('Network Address', s.network)}
          ${row('Broadcast Address', s.broadcast)}
          ${row('First Usable Host', s.firstHost)}
          ${row('Last Usable Host', s.lastHost)}
          ${row('Subnet Mask', s.mask)}
          ${row('Wildcard Mask', s.wildcard)}
          ${row('Prefix Length', '/' + s.prefix)}
          ${row('CIDR Notation', s.cidr)}
          ${row('IP Class', s.ipClass)}
          ${row('Total IP Address', s.totalHosts.toLocaleString('id-ID'))}
          ${row('Usable Host', s.usableHosts.toLocaleString('id-ID'))}
          ${row('Tipe', s.isPrivate ? 'Private' : 'Public')}
        </tbody>
      </table>
    </div>`;

  // Binary table
  const binIP   = longToBinary(s.ipLong);
  const binMask = longToBinary(s.maskLong);
  const binNet  = longToBinary(s.networkLong);
  const binBC   = longToBinary(s.broadcastLong);
  const binWild = longToBinary(s.wildcardLong);

  function renderBinRow(label, binStr, prefix) {
    let cells = `<td class="bit-label">${label}</td>`;
    for (let i = 0; i < 32; i++) {
      if (i > 0 && i % 8 === 0) {
        cells += `<td class="bit-sep">.</td>`;
      }
      const b       = binStr[i];
      const isNet   = i < prefix;
      const cls     = (b === '1' ? 'bit-1' : 'bit-0') + (isNet ? ' net-bit' : ' host-bit');
      cells += `<td class="${cls}">${b}</td>`;
    }
    return `<tr>${cells}</tr>`;
  }

  // Header row: octet labels
  let hdr = `<td class="bit-label"></td>`;
  for (let oct = 0; oct < 4; oct++) {
    if (oct > 0) hdr += `<th></th>`; // separator
    for (let b = 0; b < 8; b++) {
      hdr += `<th>${oct * 8 + b + 1}</th>`;
    }
  }

  document.getElementById('binary-table').innerHTML = `
    <thead><tr>${hdr}</tr></thead>
    <tbody>
      ${renderBinRow('IP Address', binIP, s.prefix)}
      ${renderBinRow('Subnet Mask', binMask, s.prefix)}
      ${renderBinRow('Wildcard Mask', binWild, s.prefix)}
      ${renderBinRow('Network Addr', binNet, s.prefix)}
      ${renderBinRow('Broadcast Addr', binBC, s.prefix)}
    </tbody>`;
}

/* ── Hitung dari CIDR ── */
function calcCIDR() {
  const val = document.getElementById('input-cidr').value.trim();
  if (!val) {
    document.getElementById('result-area').style.display = 'none';
    return;
  }

  let ip, prefix;
  if (val.includes('/')) {
    const parts = val.split('/');
    ip     = parts[0].trim();
    prefix = parseInt(parts[1], 10);
  } else {
    ip     = val;
    prefix = 24; // default
  }

  const ipLong = ipToLong(ip);
  if (ipLong === null || isNaN(prefix) || prefix < 0 || prefix > 32) {
    document.getElementById('result-area').style.display = 'none';
    return;
  }

  renderResult(calcSubnet(ipLong, prefix));
}

/* ── Hitung dari Mask ── */
function calcMask() {
  const ip   = document.getElementById('input-ip-mask').value.trim();
  const mask = document.getElementById('input-subnet-mask').value.trim();
  if (!ip || !mask) {
    document.getElementById('result-area').style.display = 'none';
    return;
  }

  const ipLong   = ipToLong(ip);
  const maskLong = ipToLong(mask);
  if (ipLong === null || maskLong === null || !isValidMask(maskLong)) {
    document.getElementById('result-area').style.display = 'none';
    return;
  }

  const prefix = maskLongToPrefix(maskLong);
  renderResult(calcSubnet(ipLong, prefix));
}

function applyCommonMask() {
  const val = document.getElementById('select-common-mask').value;
  if (val) {
    document.getElementById('input-subnet-mask').value = val;
    calcMask();
  }
}

/* ── Subnet Splitter ── */
function calcSplit() {
  const netVal  = document.getElementById('input-split-network').value.trim();
  const newPfx  = parseInt(document.getElementById('input-split-prefix').value, 10);

  if (!netVal || isNaN(newPfx)) return;

  let ip, prefix;
  if (netVal.includes('/')) {
    const parts = netVal.split('/');
    ip     = parts[0].trim();
    prefix = parseInt(parts[1], 10);
  } else {
    ip = netVal; prefix = 24;
  }

  const ipLong = ipToLong(ip);
  if (ipLong === null || isNaN(prefix) || newPfx <= prefix || newPfx > 32) {
    showToast('Prefix baru harus lebih besar dari prefix network asal.', 'error');
    return;
  }

  const maskLong    = prefixToMaskLong(prefix);
  const networkLong = (ipLong & maskLong) >>> 0;
  const subnetSize  = Math.pow(2, 32 - newPfx);
  const totalSubnets = Math.pow(2, newPfx - prefix);

  const maxShow = 256;
  const limited = totalSubnets > maxShow;

  document.getElementById('split-result-area').style.display = '';
  document.getElementById('result-area').style.display       = 'none';

  let rows = '';
  const showCount = Math.min(totalSubnets, maxShow);
  for (let i = 0; i < showCount; i++) {
    const netStart    = (networkLong + i * subnetSize) >>> 0;
    const netEnd      = (netStart + subnetSize - 1) >>> 0;
    const firstHost   = newPfx <= 30 ? netStart + 1 : netStart;
    const lastHost    = newPfx <= 30 ? netEnd - 1   : netEnd;
    const usable      = newPfx <= 30 ? subnetSize - 2 : (newPfx === 31 ? 2 : 1);
    rows += `
      <tr>
        <td>${i + 1}</td>
        <td>${longToIp(netStart)}/${newPfx}</td>
        <td>${longToIp(netStart)}</td>
        <td>${longToIp(netEnd)}</td>
        <td>${newPfx <= 30 ? longToIp(firstHost) + ' – ' + longToIp(lastHost) : longToIp(netStart)}</td>
        <td style="text-align:right;">${usable.toLocaleString('id-ID')}</td>
      </tr>`;
  }

  document.getElementById('split-table').innerHTML = `
    <thead>
      <tr>
        <th>#</th>
        <th>Subnet (CIDR)</th>
        <th>Network</th>
        <th>Broadcast</th>
        <th>Host Range</th>
        <th style="text-align:right;">Usable</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
    ${limited ? `<tfoot><tr><td colspan="6" style="padding:.75rem; text-align:center; color:var(--muted); font-size:.8rem; font-family:var(--font-mono);">
      ... dan ${(totalSubnets - maxShow).toLocaleString('id-ID')} subnet lainnya (total: ${totalSubnets.toLocaleString('id-ID')})
    </td></tr></tfoot>` : ''}
  `;

  showToast(`${Math.min(totalSubnets, maxShow)} dari ${totalSubnets.toLocaleString('id-ID')} subnet ditampilkan.`, 'success');
}

/* ── Preset ── */
function usePreset(cidr) {
  document.getElementById('input-cidr').value = cidr;
  calcCIDR();
}

/* ── Utilities ── */
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escAttr(s) {
  return String(s).replace(/'/g,"\\'").replace(/"/g,'&quot;');
}

/* Fallback copyToClipboard jika main.js belum tersedia */
if (typeof copyToClipboard === 'undefined') {
  window.copyToClipboard = function(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
      const orig = btn?.textContent;
      if (btn) {
        btn.textContent = '✓';
        btn.style.color = 'var(--accent5)';
        setTimeout(() => { btn.textContent = orig; btn.style.color = ''; }, 2000);
      }
    });
  };
}

/* Fallback showToast */
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

/* Auto-hitung saat load jika ada nilai default */
document.addEventListener('DOMContentLoaded', () => {
  // Set default date input hari ini pada tab mask
  const ipInput = document.getElementById('input-cidr');
  if (ipInput && !ipInput.value) ipInput.value = '';
});
</script>

<?php require '../../includes/footer.php'; ?>