<?php
require '../../includes/config.php';
/**
 * Multi Tools — Kalkulator BMI (Body Mass Index)
 * Hitung BMI, kategori berat badan, berat ideal, dan rekomendasi.
 * Mendukung satuan metrik (kg/cm) dan imperial (lb/ft-in).
 * ============================================================ */

// ── Konstanta BMI ─────────────────────────────────────────────

const BMI_CATEGORIES = [
  ['Sangat Kurus',     0,    16.0,  '#3b82f6', 'Kekurangan berat badan tingkat berat. Segera konsultasikan ke dokter.'],
  ['Kurus',           16.0,  18.5,  '#0ea5e9', 'Berat badan di bawah normal. Perlu peningkatan asupan nutrisi.'],
  ['Normal',          18.5,  24.9,  '#10b981', 'Berat badan ideal. Pertahankan pola makan dan olahraga sehat.'],
  ['Kelebihan',       24.9,  30.0,  '#f59e0b', 'Kelebihan berat badan. Kurangi kalori dan tingkatkan aktivitas fisik.'],
  ['Obesitas I',      30.0,  35.0,  '#ef4444', 'Obesitas kelas I. Konsultasikan program diet dengan dokter atau ahli gizi.'],
  ['Obesitas II',     35.0,  40.0,  '#dc2626', 'Obesitas kelas II. Risiko penyakit kardiovaskular meningkat signifikan.'],
  ['Obesitas III',    40.0, 999.0,  '#7f1d1d', 'Obesitas kelas III (morbid). Memerlukan intervensi medis segera.'],
];

const BMI_HEALTHY_MIN = 18.5;
const BMI_HEALTHY_MAX = 24.9;

/**
 * Hitung BMI dari berat (kg) dan tinggi (m).
 */
function calcBMI(float $weightKg, float $heightM): float {
  if ($heightM <= 0) return 0;
  return $weightKg / ($heightM * $heightM);
}

/**
 * Dapatkan kategori BMI.
 */
function getBMICategory(float $bmi): array {
  foreach (BMI_CATEGORIES as $cat) {
    [$label, $min, $max, $color, $desc] = $cat;
    if ($bmi >= $min && $bmi < $max) {
      return compact('label', 'min', 'max', 'color', 'desc');
    }
  }
  return ['label'=>'Unknown', 'min'=>0, 'max'=>0, 'color'=>'#6b7280', 'desc'=>''];
}

/**
 * Hitung rentang berat ideal (kg) untuk tinggi tertentu (m).
 */
function idealWeightRange(float $heightM): array {
  return [
    'min' => round(BMI_HEALTHY_MIN * $heightM * $heightM, 1),
    'max' => round(BMI_HEALTHY_MAX * $heightM * $heightM, 1),
  ];
}

/**
 * Perkiraan kebutuhan kalori harian (TDEE) dengan Mifflin-St Jeor.
 */
function calcTDEE(float $weightKg, float $heightCm, int $age, string $sex, string $activity): float {
  // BMR
  $bmr = $sex === 'male'
    ? 10 * $weightKg + 6.25 * $heightCm - 5 * $age + 5
    : 10 * $weightKg + 6.25 * $heightCm - 5 * $age - 161;

  // Activity multiplier
  $mult = match($activity) {
    'sedentary'  => 1.2,
    'light'      => 1.375,
    'moderate'   => 1.55,
    'active'     => 1.725,
    'very_active'=> 1.9,
    default      => 1.2,
  };

  return round($bmr * $mult);
}

/**
 * Konversi lbs ke kg.
 */
function lbsToKg(float $lbs): float { return $lbs * 0.453592; }

/**
 * Konversi feet+inches ke cm.
 */
function ftInToCm(float $ft, float $in): float { return ($ft * 12 + $in) * 2.54; }

// ── Handle POST ──────────────────────────────────────────────
$result        = null;
$server_error  = '';
$post_unit     = 'metric';
$post_weight   = '';
$post_height   = '';
$post_height_ft = '';
$post_height_in = '';
$post_weight_lbs = '';
$post_age      = '';
$post_sex      = 'male';
$post_activity = 'moderate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $post_unit      = $_POST['unit']      === 'imperial' ? 'imperial' : 'metric';
  $post_age       = trim($_POST['age']  ?? '');
  $post_sex       = in_array($_POST['sex'] ?? 'male', ['male','female']) ? $_POST['sex'] : 'male';
  $post_activity  = in_array($_POST['activity'] ?? 'moderate',
                    ['sedentary','light','moderate','active','very_active'])
                      ? $_POST['activity'] : 'moderate';

  // Parse input sesuai unit
  if ($post_unit === 'metric') {
    $post_weight = trim($_POST['weight_kg'] ?? '');
    $post_height = trim($_POST['height_cm'] ?? '');
    $weightKg    = (float)$post_weight;
    $heightCm    = (float)$post_height;
  } else {
    $post_weight_lbs = trim($_POST['weight_lbs'] ?? '');
    $post_height_ft  = trim($_POST['height_ft']  ?? '');
    $post_height_in  = trim($_POST['height_in']  ?? '0');
    $weightKg = lbsToKg((float)$post_weight_lbs);
    $heightCm = ftInToCm((float)$post_height_ft, (float)$post_height_in);
  }

  // Validasi
  if ($weightKg <= 0 || $weightKg > 500) {
    $server_error = 'Berat badan tidak valid (1–500 kg).';
  } elseif ($heightCm <= 0 || $heightCm > 300) {
    $server_error = 'Tinggi badan tidak valid (50–300 cm).';
  } else {
    $heightM = $heightCm / 100;
    $bmi     = calcBMI($weightKg, $heightM);
    $cat     = getBMICategory($bmi);
    $ideal   = idealWeightRange($heightM);
    $age     = max(1, min(120, (int)$post_age));
    $tdee    = $age > 0 ? calcTDEE($weightKg, $heightCm, $age, $post_sex, $post_activity) : null;

    // Selisih dari berat ideal
    $weightDiff = $weightKg - $ideal['max'];
    if ($weightKg >= $ideal['min'] && $weightKg <= $ideal['max']) {
      $weightDiff = 0;
    } elseif ($weightKg < $ideal['min']) {
      $weightDiff = $weightKg - $ideal['min'];
    }

    // Prime BMI (min 18.5 × height²)
    $primeBMI = round($bmi / 25, 2);

    $result = [
      'bmi'         => round($bmi, 1),
      'bmi_precise' => round($bmi, 2),
      'category'    => $cat,
      'weight_kg'   => round($weightKg, 1),
      'height_cm'   => round($heightCm, 1),
      'height_m'    => round($heightM, 2),
      'ideal_min'   => $ideal['min'],
      'ideal_max'   => $ideal['max'],
      'weight_diff' => round($weightDiff, 1),
      'prime_bmi'   => $primeBMI,
      'tdee'        => $tdee,
      'age'         => $age,
      'sex'         => $post_sex,
      'activity'    => $post_activity,
      'unit'        => $post_unit,
      // Imperial display
      'weight_lbs'  => round($weightKg / 0.453592, 1),
      'height_ft'   => (int)floor($heightCm / 30.48),
      'height_in'   => round(fmod($heightCm / 2.54, 12), 1),
    ];
  }
}

// ── Nama aktivitas ────────────────────────────────────────────
function activityLabel(string $a): string {
  return match($a) {
    'sedentary'   => 'Tidak aktif (kerja meja, tanpa olahraga)',
    'light'       => 'Ringan (olahraga 1-3x/minggu)',
    'moderate'    => 'Sedang (olahraga 3-5x/minggu)',
    'active'      => 'Aktif (olahraga keras 6-7x/minggu)',
    'very_active' => 'Sangat aktif (atlet/pekerjaan fisik berat)',
    default       => $a,
  };
}

// ── SEO & Breadcrumb ─────────────────────────────────────────
$seo = [
  'title'       => 'Kalkulator BMI Online — Body Mass Index & Berat Ideal | Multi Tools',
  'description' => 'Hitung BMI (Body Mass Index), kategori berat badan, berat ideal, dan estimasi kebutuhan kalori (TDEE) secara gratis. Mendukung satuan metrik dan imperial.',
  'keywords'    => 'kalkulator bmi, bmi calculator, body mass index, berat ideal, kalkulator kalori, tdee, obesitas, indeks massa tubuh, multi tools',
  'og_title'    => 'Kalkulator BMI Online — Indeks Massa Tubuh & Berat Ideal',
  'og_desc'     => 'Hitung BMI, kategori berat badan, berat ideal, dan TDEE. Satuan metrik dan imperial.',
  'breadcrumbs' => [
    ['name' => 'Beranda',   'url' => SITE_URL . '/'],
    ['name' => 'Konversi',  'url' => SITE_URL . '/tools?cat=convert'],
    ['name' => 'Kalkulator BMI'],
  ],
  'schema' => [
    [
      '@type'       => 'WebPage',
      '@id'         => SITE_URL . '/tools/kalkulator-bmi#webpage',
      'url'         => SITE_URL . '/tools/kalkulator-bmi',
      'name'        => 'Kalkulator BMI Online',
      'description' => 'Hitung BMI, kategori berat badan, berat ideal, dan TDEE.',
      'isPartOf'    => ['@id' => SITE_URL . '/#website'],
      'inLanguage'  => 'id-ID',
      'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda',         'item' => SITE_URL . '/'],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Konversi',        'item' => SITE_URL . '/tools?cat=convert'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => 'Kalkulator BMI',  'item' => SITE_URL . '/tools/kalkulator-bmi'],
        ],
      ],
    ],
    [
      '@type'               => 'SoftwareApplication',
      'name'                => 'Kalkulator BMI',
      'applicationCategory' => 'HealthApplication',
      'operatingSystem'     => 'Web Browser',
      'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
      'url'                 => SITE_URL . '/tools/kalkulator-bmi',
    ],
  ],
];

require '../../includes/header.php';
?>

<style>
/* ── Unit toggle ── */
.unit-toggle {
  display: inline-flex;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1.5rem;
}
.unit-btn {
  padding: .5rem 1.25rem;
  background: var(--bg); border: none;
  font-family: var(--font-body); font-size: .88rem; font-weight: 600;
  color: var(--muted); cursor: pointer;
  transition: all var(--transition);
}
.unit-btn:not(:last-child) { border-right: 1px solid var(--border); }
.unit-btn.active { background: var(--accent5); color: #fff; }
.unit-btn:hover:not(.active) { background: var(--surface); color: var(--text); }

/* ── BMI gauge ── */
.bmi-gauge-wrap {
  position: relative;
  width: 100%; max-width: 420px;
  margin: 0 auto 1.5rem;
}
.bmi-gauge-svg { width: 100%; }
.bmi-needle-group { transition: transform .6s cubic-bezier(.34,1.56,.64,1); }

/* ── BMI result hero ── */
.bmi-hero {
  display: flex; flex-direction: column; align-items: center;
  padding: 1.5rem;
  background: var(--bg); border: 2px solid var(--border);
  border-radius: var(--radius-lg); text-align: center;
  transition: border-color .3s;
}
.bmi-hero.active { border-color: var(--result-color, var(--accent5)); }
.bmi-number {
  font-size: 3.5rem; font-weight: 800; letter-spacing: -.04em;
  line-height: 1; font-variant-numeric: tabular-nums;
  color: var(--result-color, var(--accent5));
  transition: color .3s;
}
.bmi-category-label {
  font-size: 1.1rem; font-weight: 700; margin-top: .4rem;
  color: var(--result-color, var(--text));
  transition: color .3s;
}
.bmi-desc { font-size: .82rem; color: var(--muted); margin-top: .35rem; max-width: 280px; line-height: 1.5; }

/* ── Category scale ── */
.bmi-scale {
  display: flex; height: 12px; border-radius: 99px; overflow: hidden;
  margin: 1.25rem 0 .4rem; position: relative;
}
.bmi-scale-seg {
  flex: 1; position: relative; cursor: default;
  transition: filter .2s;
}
.bmi-scale-seg:hover { filter: brightness(1.15); }
.bmi-scale-marker {
  position: absolute; top: -3px;
  width: 18px; height: 18px;
  border: 3px solid var(--surface);
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.25);
  transform: translateX(-50%);
  transition: left .6s cubic-bezier(.34,1.56,.64,1);
  z-index: 10;
}
.scale-labels {
  display: flex; justify-content: space-between;
  font-family: var(--font-mono); font-size: .65rem; color: var(--muted);
  margin-bottom: 1rem;
}

/* ── Result cards ── */
.result-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: .75rem; margin-top: 1.25rem;
}
.result-card {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-md); padding: .85rem 1rem;
  display: flex; flex-direction: column; gap: .2rem;
  transition: border-color var(--transition), box-shadow var(--transition);
}
.result-card:hover { border-color: var(--accent5); box-shadow: 0 4px 16px rgba(16,185,129,.1); }
.result-card .rc-val {
  font-size: 1.25rem; font-weight: 800; letter-spacing: -.03em;
  color: var(--accent5); font-variant-numeric: tabular-nums;
}
.result-card .rc-unit { font-size: .68rem; font-weight: 400; color: var(--muted); }
.result-card .rc-lbl  { font-size: .72rem; color: var(--muted); font-family: var(--font-mono); margin-top: .1rem; }

/* ── Calorie breakdown ── */
.calorie-goals {
  display: flex; flex-direction: column; gap: .4rem; margin-top: .75rem;
}
.calorie-row {
  display: flex; align-items: center; gap: .75rem;
  padding: .55rem .85rem;
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.calorie-row:hover { border-color: var(--accent5); }
.calorie-row .cr-goal { font-size: .8rem; color: var(--muted); flex: 1; }
.calorie-row .cr-val  { font-family: var(--font-mono); font-weight: 700; font-size: .9rem; color: var(--accent5); }
.calorie-row .cr-badge {
  font-size: .62rem; font-family: var(--font-mono); font-weight: 700;
  padding: 1px 5px; border-radius: 4px; flex-shrink: 0;
}
.cr-badge.lose   { background: rgba(239,68,68,.12);  color: #dc2626; }
.cr-badge.maintain { background: rgba(16,185,129,.12); color: #15803d; }
.cr-badge.gain   { background: rgba(37,99,235,.12);  color: var(--accent); }

/* ── BMI table ── */
.bmi-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.bmi-table th {
  padding: .45rem .75rem; border-bottom: 1px solid var(--border);
  text-align: left; font-family: var(--font-mono); font-size: .68rem;
  letter-spacing: .06em; text-transform: uppercase; color: var(--muted); font-weight: 700;
}
.bmi-table td { padding: .42rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.bmi-table tr:last-child td { border-bottom: none; }
.bmi-table .cat-dot {
  display: inline-block; width: 10px; height: 10px;
  border-radius: 50%; margin-right: .4rem; flex-shrink: 0;
}
.bmi-table tr.highlight td { background: rgba(16,185,129,.07); font-weight: 700; }

/* ── Input slider ── */
.slider-input-row {
  display: flex; align-items: center; gap: .75rem; margin-top: .35rem;
}
.slider-input-row input[type="range"] {
  flex: 1; accent-color: var(--accent5);
}
.slider-num {
  font-family: var(--font-mono); font-weight: 800; font-size: 1rem;
  color: var(--accent5); min-width: 52px; text-align: right;
}
.num-input-sm {
  max-width: 100px !important;
  font-family: var(--font-mono) !important;
  font-weight: 700 !important;
}

/* ── Live indicator ── */
.live-dot {
  display: inline-flex; align-items: center; gap: .35rem;
  font-family: var(--font-mono); font-size: .68rem; font-weight: 700;
  color: var(--accent5);
}
.live-dot::before {
  content: ''; width: 7px; height: 7px; border-radius: 50%;
  background: var(--accent5);
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── Healthy range indicator ── */
.ideal-range-bar {
  height: 6px; background: var(--border); border-radius: 99px; overflow: hidden;
  margin-top: .5rem; position: relative;
}
.ideal-range-fill {
  position: absolute; top: 0; height: 100%;
  background: var(--accent5); border-radius: 99px;
  transition: left .4s, width .4s;
}
.ideal-range-marker {
  position: absolute; top: -4px; width: 14px; height: 14px;
  background: var(--text); border-radius: 50%; border: 2px solid var(--surface);
  box-shadow: 0 1px 4px rgba(0,0,0,.2);
  transform: translateX(-50%);
  transition: left .4s;
}
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
        <span aria-hidden="true">⚖️</span> Kalkulator <span>BMI</span>
      </div>
      <p class="page-lead">
        Hitung Indeks Massa Tubuh (BMI), kategori berat badan, rentang berat ideal,
        dan estimasi kebutuhan kalori harian (TDEE) secara instan.
      </p>

      <!-- Unit toggle -->
      <div>
        <div class="unit-toggle" role="group" aria-label="Pilih satuan">
          <button type="button" class="unit-btn <?= $post_unit === 'metric' ? 'active' : '' ?>"
            id="btn-metric" onclick="setUnit('metric')">
            📏 Metrik (kg/cm)
          </button>
          <button type="button" class="unit-btn <?= $post_unit === 'imperial' ? 'active' : '' ?>"
            id="btn-imperial" onclick="setUnit('imperial')">
            🇺🇸 Imperial (lb/ft)
          </button>
        </div>
      </div>

      <form method="POST" action="" id="bmi-form" novalidate>
        <input type="hidden" id="unit-input" name="unit" value="<?= e($post_unit) ?>" />

        <!-- BMI Result Hero (realtime) -->
        <div class="bmi-hero" id="bmi-hero" style="margin-bottom:1.5rem;">
          <div class="bmi-number" id="bmi-number">—</div>
          <div class="bmi-category-label" id="bmi-cat-label">Masukkan data di bawah</div>
          <div class="bmi-desc" id="bmi-desc">BMI = Berat (kg) ÷ Tinggi² (m)</div>
          <div style="margin-top:.5rem;"><span class="live-dot">Realtime</span></div>
        </div>

        <!-- Scale bar -->
        <div>
          <div class="bmi-scale" id="bmi-scale">
            <?php
            $catColors = ['#3b82f6','#0ea5e9','#10b981','#f59e0b','#ef4444','#dc2626','#7f1d1d'];
            $catWidths = [10, 14, 20, 17, 15, 13, 11]; // proportional width %
            foreach ($catColors as $i => $color): ?>
              <div class="bmi-scale-seg" style="background:<?= $color ?>; flex:<?= $catWidths[$i] ?>;"
                title="<?= BMI_CATEGORIES[$i][0] ?> (<?= BMI_CATEGORIES[$i][1] ?>–<?= BMI_CATEGORIES[$i][2] === 999.0 ? '40+' : BMI_CATEGORIES[$i][2] ?>)">
              </div>
            <?php endforeach; ?>
            <div class="bmi-scale-marker" id="scale-marker" style="left:35%;"></div>
          </div>
          <div class="scale-labels">
            <span>16</span><span>18.5</span><span>25</span><span>30</span><span>35</span><span>40+</span>
          </div>
        </div>

        <!-- Input fields -->
        <div class="form-row" style="margin-bottom:1rem;">

          <!-- Berat badan -->
          <div class="form-group">
            <label id="weight-label">Berat badan (kg)</label>

            <!-- Metric weight -->
            <div id="weight-metric">
              <div class="slider-input-row">
                <input type="range" id="weight-slider" min="20" max="200" step="0.5"
                  value="<?= $post_weight ?: 70 ?>"
                  oninput="syncWeight(this.value); calcJS();" />
                <span class="slider-num" id="weight-badge"><?= $post_weight ?: 70 ?></span>
              </div>
              <input type="number" class="num-input-sm" id="weight-kg" name="weight_kg"
                min="20" max="300" step="0.1"
                value="<?= $post_weight ?: 70 ?>"
                oninput="syncWeightInput(this.value)"
                style="margin-top:.35rem;" />
            </div>

            <!-- Imperial weight -->
            <div id="weight-imperial" style="display:none;">
              <div class="slider-input-row">
                <input type="range" id="weight-lbs-slider" min="44" max="440" step="1"
                  value="<?= $post_weight_lbs ?: 154 ?>"
                  oninput="document.getElementById('weight-lbs').value=this.value;
                           document.getElementById('weight-lbs-badge').textContent=this.value; calcJS();" />
                <span class="slider-num" id="weight-lbs-badge"><?= $post_weight_lbs ?: 154 ?></span>
              </div>
              <input type="number" class="num-input-sm" id="weight-lbs" name="weight_lbs"
                min="44" max="660" step="0.1"
                value="<?= $post_weight_lbs ?: 154 ?>"
                oninput="document.getElementById('weight-lbs-slider').value=this.value;
                         document.getElementById('weight-lbs-badge').textContent=this.value; calcJS();"
                style="margin-top:.35rem;" />
            </div>
          </div>

          <!-- Tinggi badan -->
          <div class="form-group">
            <label id="height-label">Tinggi badan (cm)</label>

            <!-- Metric height -->
            <div id="height-metric">
              <div class="slider-input-row">
                <input type="range" id="height-slider" min="100" max="250" step="0.5"
                  value="<?= $post_height ?: 170 ?>"
                  oninput="syncHeight(this.value); calcJS();" />
                <span class="slider-num" id="height-badge"><?= $post_height ?: 170 ?></span>
              </div>
              <input type="number" class="num-input-sm" id="height-cm" name="height_cm"
                min="50" max="300" step="0.1"
                value="<?= $post_height ?: 170 ?>"
                oninput="syncHeightInput(this.value)"
                style="margin-top:.35rem;" />
            </div>

            <!-- Imperial height -->
            <div id="height-imperial" style="display:none;">
              <div class="form-row" style="margin-top:.35rem;">
                <div>
                  <label class="text-xs text-muted" style="margin-bottom:.25rem;">Feet</label>
                  <input type="number" class="num-input-sm" id="height-ft" name="height_ft"
                    min="3" max="9" step="1" value="<?= $post_height_ft ?: 5 ?>"
                    oninput="calcJS()" />
                </div>
                <div>
                  <label class="text-xs text-muted" style="margin-bottom:.25rem;">Inches</label>
                  <input type="number" class="num-input-sm" id="height-in" name="height_in"
                    min="0" max="11" step="0.5" value="<?= $post_height_in ?: 7 ?>"
                    oninput="calcJS()" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Usia & Jenis kelamin (untuk TDEE) -->
        <div class="form-row">
          <div class="form-group">
            <label for="age-input">Usia <span class="text-muted text-sm">(untuk kalori)</span></label>
            <input type="number" id="age-input" name="age"
              min="1" max="120" step="1"
              value="<?= e($post_age ?: 25) ?>"
              oninput="calcJS()"
              style="max-width:100px;" />
          </div>
          <div class="form-group">
            <label for="sex-select">Jenis kelamin</label>
            <select id="sex-select" name="sex" onchange="calcJS()">
              <option value="male"   <?= $post_sex === 'male'   ? 'selected' : '' ?>>Pria</option>
              <option value="female" <?= $post_sex === 'female' ? 'selected' : '' ?>>Wanita</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="activity-select">Level aktivitas</label>
          <select id="activity-select" name="activity" onchange="calcJS()">
            <option value="sedentary"   <?= $post_activity === 'sedentary'   ? 'selected' : '' ?>>Tidak aktif — kerja meja, tanpa olahraga</option>
            <option value="light"       <?= $post_activity === 'light'       ? 'selected' : '' ?>>Ringan — olahraga 1–3x/minggu</option>
            <option value="moderate"    <?= $post_activity === 'moderate'    ? 'selected' : '' ?>>Sedang — olahraga 3–5x/minggu</option>
            <option value="active"      <?= $post_activity === 'active'      ? 'selected' : '' ?>>Aktif — olahraga keras 6–7x/minggu</option>
            <option value="very_active" <?= $post_activity === 'very_active' ? 'selected' : '' ?>>Sangat aktif — atlet/pekerjaan fisik berat</option>
          </select>
        </div>

        <!-- Hasil realtime -->
        <div id="result-section" style="display:none;">

          <!-- Result cards -->
          <div class="result-grid" id="result-cards">
            <div class="result-card">
              <div><span class="rc-val" id="rc-bmi">—</span></div>
              <div class="rc-lbl">BMI Kamu</div>
            </div>
            <div class="result-card">
              <div><span class="rc-val" id="rc-ideal-min">—</span> <span class="rc-unit">–</span> <span class="rc-val" id="rc-ideal-max">—</span> <span class="rc-unit">kg</span></div>
              <div class="rc-lbl">Berat ideal</div>
            </div>
            <div class="result-card">
              <div><span class="rc-val" id="rc-diff">—</span> <span class="rc-unit" id="rc-diff-unit">kg</span></div>
              <div class="rc-lbl" id="rc-diff-lbl">Selisih</div>
            </div>
            <div class="result-card">
              <div><span class="rc-val" id="rc-prime">—</span></div>
              <div class="rc-lbl">Prime BMI</div>
            </div>
            <div class="result-card">
              <div><span class="rc-val" id="rc-tdee">—</span> <span class="rc-unit">kkal</span></div>
              <div class="rc-lbl">TDEE / hari</div>
            </div>
            <div class="result-card">
              <div><span class="rc-val" id="rc-conv">—</span></div>
              <div class="rc-lbl" id="rc-conv-lbl">Berat (lbs)</div>
            </div>
          </div>

          <!-- Calorie goals -->
          <div style="margin-top:1.25rem;">
            <div class="section-mini-title" style="margin-bottom:.5rem;">🔥 Target Kalori Harian</div>
            <div class="calorie-goals" id="calorie-goals">
              <div class="calorie-row">
                <span class="cr-goal">Turunkan berat badan (defisit 500 kkal)</span>
                <span class="cr-val" id="cal-lose">—</span>
                <span class="cr-badge lose">Defisit</span>
              </div>
              <div class="calorie-row">
                <span class="cr-goal">Pertahankan berat badan</span>
                <span class="cr-val" id="cal-maintain">—</span>
                <span class="cr-badge maintain">Maintenance</span>
              </div>
              <div class="calorie-row">
                <span class="cr-goal">Tambah berat badan (surplus 500 kkal)</span>
                <span class="cr-val" id="cal-gain">—</span>
                <span class="cr-badge gain">Surplus</span>
              </div>
            </div>
          </div>

          <!-- Healthy weight bar -->
          <div style="margin-top:1.25rem;">
            <div class="section-mini-title" style="margin-bottom:.4rem;">⚖️ Posisi berat badanmu</div>
            <div style="position:relative; height:24px; margin-bottom:.35rem;">
              <div class="ideal-range-bar" style="position:absolute; inset:0; height:100%;">
                <div class="ideal-range-fill" id="ideal-fill" style="left:25%; width:50%;"></div>
              </div>
              <div class="ideal-range-marker" id="ideal-marker" style="left:50%;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; font-family:var(--font-mono); font-size:.68rem; color:var(--muted);">
              <span id="ideal-range-label-min">—</span>
              <span style="color:var(--accent5); font-weight:700;">Rentang Ideal</span>
              <span id="ideal-range-label-max">—</span>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1.25rem;">
          <button type="submit" class="btn-primary btn-sm"
            style="background:var(--accent5); border-color:var(--accent5);">
            ⚖️ Hitung via Server (PHP)
          </button>
          <button type="button" class="btn-ghost btn-sm" onclick="resetForm()">Reset</button>
        </div>

      </form>
    </div><!-- /.panel -->

    <!-- Hasil server -->
    <?php if ($result && !$server_error): ?>
    <?php $r = $result; $cat = $r['category']; ?>
    <div class="alert success" style="margin-top:1rem;" role="alert">
      <span>✓</span>
      <span>
        BMI <strong><?= $r['bmi'] ?></strong> —
        <strong style="color:<?= $cat['color'] ?>;"><?= e($cat['label']) ?></strong>
      </span>
    </div>
    <div class="panel" style="margin-top:1rem;">
      <div class="panel-title" style="border-bottom:3px solid <?= $cat['color'] ?>; padding-bottom:.6rem;">
        ⚙ Hasil Detail — BMI <?= $r['bmi'] ?>
      </div>

      <!-- Hero server result -->
      <div class="bmi-hero active" style="--result-color:<?= $cat['color'] ?>; margin:1rem 0;">
        <div class="bmi-number" style="color:<?= $cat['color'] ?>;"><?= $r['bmi'] ?></div>
        <div class="bmi-category-label" style="color:<?= $cat['color'] ?>;"><?= e($cat['label']) ?></div>
        <div class="bmi-desc"><?= e($cat['desc']) ?></div>
      </div>

      <!-- Detail cards -->
      <div class="result-grid">
        <?php
        $cards = [
          [$r['bmi_precise'],                 '',       'BMI Tepat'],
          [$r['ideal_min'] . '–' . $r['ideal_max'], 'kg', 'Berat Ideal'],
          [abs($r['weight_diff']),             'kg',    $r['weight_diff'] == 0 ? 'Di rentang ideal' : ($r['weight_diff'] > 0 ? 'Kelebihan' : 'Kekurangan')],
          [$r['prime_bmi'],                    '',      'Prime BMI'],
          [$r['height_cm'],                    'cm',    'Tinggi'],
          [$r['weight_kg'],                    'kg',    'Berat'],
        ];
        if ($r['tdee']): $cards[] = [$r['tdee'], 'kkal', 'TDEE / Hari']; endif;
        foreach ($cards as [$val, $unit, $lbl]): ?>
          <div class="result-card">
            <div><span class="rc-val"><?= $val ?></span><?= $unit ? ' <span class="rc-unit">' . e($unit) . '</span>' : '' ?></div>
            <div class="rc-lbl"><?= e($lbl) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Imperial display -->
      <div style="margin-top:1rem; font-size:.82rem; color:var(--muted);">
        Setara: <strong><?= $r['weight_lbs'] ?> lbs</strong> ·
        <strong><?= $r['height_ft'] ?>ft <?= $r['height_in'] ?>in</strong>
        (<?= $r['height_cm'] ?> cm)
      </div>

      <!-- Calorie recommendations -->
      <?php if ($r['tdee']): ?>
      <div style="margin-top:1.25rem; border-top:1px solid var(--border); padding-top:1rem;">
        <div class="section-mini-title" style="margin-bottom:.5rem;">
          🔥 Rekomendasi Kalori
          <span class="badge" style="margin-left:.4rem;"><?= e(ucfirst($r['sex'] === 'male' ? 'Pria' : 'Wanita')) ?> · <?= $r['age'] ?> thn · TDEE <?= number_format($r['tdee']) ?> kkal</span>
        </div>
        <div class="calorie-goals">
          <div class="calorie-row">
            <span class="cr-goal">Turunkan berat badan (defisit 500 kkal)</span>
            <span class="cr-val"><?= number_format($r['tdee'] - 500) ?> kkal</span>
            <span class="cr-badge lose">Defisit</span>
          </div>
          <div class="calorie-row">
            <span class="cr-goal">Pertahankan berat badan</span>
            <span class="cr-val"><?= number_format($r['tdee']) ?> kkal</span>
            <span class="cr-badge maintain">Maintenance</span>
          </div>
          <div class="calorie-row">
            <span class="cr-goal">Tambah berat badan (surplus 500 kkal)</span>
            <span class="cr-val"><?= number_format($r['tdee'] + 500) ?> kkal</span>
            <span class="cr-badge gain">Surplus</span>
          </div>
        </div>
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
    <!-- Tabel kategori BMI -->
    <div class="panel">
      <div class="panel-title">📊 Kategori BMI</div>
      <table class="bmi-table">
        <thead>
          <tr>
            <th>Kategori</th>
            <th>BMI</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (BMI_CATEGORIES as $cat): ?>
          <tr <?= ($result && $result['category']['label'] === $cat[0]) ? 'class="highlight"' : '' ?>>
            <td>
              <span class="cat-dot" style="background:<?= $cat[3] ?>;"></span>
              <?= e($cat[0]) ?>
            </td>
            <td style="font-family:var(--font-mono); font-size:.75rem;">
              <?= $cat[1] ?>–<?= $cat[2] === 999.0 ? '40+' : $cat[2] ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="text-xs text-muted" style="margin-top:.6rem; line-height:1.6;">
        * Klasifikasi WHO untuk orang dewasa. Untuk anak-anak dan remaja, gunakan grafik pertumbuhan CDC/WHO.
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">💡 Tentang BMI</div>
      <ul class="text-sm text-muted" style="padding-left:1.2rem; line-height:2.2;">
        <li>BMI = Berat(kg) ÷ Tinggi²(m)</li>
        <li>Dikembangkan oleh Adolphe Quetelet (1832)</li>
        <li>Tidak membedakan lemak vs otot</li>
        <li>Rentang sehat: <strong>18.5 – 24.9</strong></li>
        <li>BMI Asia: normal 18.5–22.9</li>
        <li>Konsultasikan dengan dokter untuk penilaian lengkap</li>
      </ul>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🎯 Contoh Cepat</div>
      <div style="display:flex; flex-direction:column; gap:.4rem;">
        <?php
        $examples = [
          [50, 160, 'Ringan & Pendek'],
          [70, 170, 'Rata-rata Pria'],
          [55, 160, 'Rata-rata Wanita'],
          [90, 175, 'Kelebihan Berat'],
          [120, 170, 'Obesitas'],
        ];
        foreach ($examples as [$w, $h, $lbl]): ?>
          <button class="btn-ghost btn-sm btn-full"
            style="display:flex; justify-content:space-between; padding:.4rem .75rem;"
            onclick="loadExample(<?= $w ?>, <?= $h ?>)">
            <span style="font-size:.8rem;"><?= e($lbl) ?></span>
            <span style="font-family:var(--font-mono); font-size:.72rem; color:var(--muted);">
              <?= $w ?>kg · <?= $h ?>cm
            </span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel" style="margin-top:1.25rem;">
      <div class="panel-title">🔗 Tools Terkait</div>
      <div style="display:flex; flex-direction:column; gap:.5rem;">
        <a href="/tools/kalkulator-umur"   class="btn-ghost btn-sm btn-full">Kalkulator Umur</a>
        <a href="/tools/unit-converter"    class="btn-ghost btn-sm btn-full">Unit Converter</a>
        <a href="/tools/kalkulator-persen" class="btn-ghost btn-sm btn-full">Kalkulator Persen</a>
      </div>
    </div>
  </aside>

</div><!-- /.tool-layout -->

<script>
/* ──────────────────────────────────────────
   Kalkulator BMI — logika JS (realtime)
   PHP dipakai saat form di-submit (server).
   ────────────────────────────────────────── */

const BMI_CATS = [
  { label:'Sangat Kurus', min:0,    max:16.0,  color:'#3b82f6', desc:'Kekurangan berat badan tingkat berat. Segera konsultasikan ke dokter.' },
  { label:'Kurus',        min:16.0, max:18.5,  color:'#0ea5e9', desc:'Berat badan di bawah normal. Perlu peningkatan asupan nutrisi.' },
  { label:'Normal',       min:18.5, max:25.0,  color:'#10b981', desc:'Berat badan ideal. Pertahankan pola makan dan olahraga sehat.' },
  { label:'Kelebihan',    min:25.0, max:30.0,  color:'#f59e0b', desc:'Kelebihan berat badan. Kurangi kalori dan tingkatkan aktivitas fisik.' },
  { label:'Obesitas I',   min:30.0, max:35.0,  color:'#ef4444', desc:'Obesitas kelas I. Konsultasikan program diet dengan dokter.' },
  { label:'Obesitas II',  min:35.0, max:40.0,  color:'#dc2626', desc:'Obesitas kelas II. Risiko penyakit kardiovaskular meningkat.' },
  { label:'Obesitas III', min:40.0, max:999,   color:'#7f1d1d', desc:'Obesitas kelas III (morbid). Memerlukan intervensi medis segera.' },
];

// Faktor aktivitas Mifflin-St Jeor
const ACTIVITY_MULT = {
  sedentary: 1.2, light: 1.375, moderate: 1.55, active: 1.725, very_active: 1.9,
};

let currentUnit = '<?= $post_unit ?>';

// ── Unit switching ────────────────────────────────────────────
function setUnit(unit) {
  currentUnit = unit;
  document.getElementById('unit-input').value = unit;
  document.getElementById('btn-metric').classList.toggle('active', unit === 'metric');
  document.getElementById('btn-imperial').classList.toggle('active', unit === 'imperial');

  // Show/hide fields
  ['weight-metric','height-metric'].forEach(id =>
    document.getElementById(id).style.display = unit === 'metric' ? '' : 'none');
  ['weight-imperial','height-imperial'].forEach(id =>
    document.getElementById(id).style.display = unit === 'imperial' ? '' : 'none');

  // Update labels
  document.getElementById('weight-label').textContent = unit === 'metric' ? 'Berat badan (kg)' : 'Berat badan (lbs)';
  document.getElementById('height-label').textContent = unit === 'metric' ? 'Tinggi badan (cm)' : 'Tinggi badan (ft/in)';
  document.getElementById('rc-conv-lbl').textContent  = unit === 'metric' ? 'Berat (lbs)' : 'Berat (kg)';

  calcJS();
}

// ── Slider sync ───────────────────────────────────────────────
function syncWeight(val) {
  document.getElementById('weight-kg').value    = val;
  document.getElementById('weight-badge').textContent = val;
}
function syncWeightInput(val) {
  document.getElementById('weight-slider').value = Math.min(200, Math.max(20, val));
  document.getElementById('weight-badge').textContent = val;
  calcJS();
}
function syncHeight(val) {
  document.getElementById('height-cm').value    = val;
  document.getElementById('height-badge').textContent = val;
}
function syncHeightInput(val) {
  document.getElementById('height-slider').value = Math.min(250, Math.max(100, val));
  document.getElementById('height-badge').textContent = val;
  calcJS();
}

// ── Get current weight/height in kg/cm ───────────────────────
function getInputs() {
  if (currentUnit === 'metric') {
    return {
      weightKg: parseFloat(document.getElementById('weight-kg').value) || 0,
      heightCm: parseFloat(document.getElementById('height-cm').value) || 0,
    };
  } else {
    const lbs = parseFloat(document.getElementById('weight-lbs').value) || 0;
    const ft  = parseFloat(document.getElementById('height-ft').value) || 0;
    const inc = parseFloat(document.getElementById('height-in').value) || 0;
    return {
      weightKg: lbs * 0.453592,
      heightCm: (ft * 12 + inc) * 2.54,
    };
  }
}

// ── Main calculation ──────────────────────────────────────────
function calcJS() {
  const { weightKg, heightCm } = getInputs();
  const bmiNum    = document.getElementById('bmi-number');
  const bmiCat    = document.getElementById('bmi-cat-label');
  const bmiDesc   = document.getElementById('bmi-desc');
  const bmiHero   = document.getElementById('bmi-hero');
  const resSect   = document.getElementById('result-section');

  if (!weightKg || !heightCm || weightKg <= 0 || heightCm <= 0) {
    bmiNum.textContent = '—';
    bmiCat.textContent = 'Masukkan data di bawah';
    bmiDesc.textContent = 'BMI = Berat (kg) ÷ Tinggi² (m)';
    bmiHero.classList.remove('active');
    bmiHero.style.setProperty('--result-color', 'var(--accent5)');
    resSect.style.display = 'none';
    updateScaleMarker(null);
    return;
  }

  const heightM = heightCm / 100;
  const bmi     = weightKg / (heightM * heightM);
  const cat     = getBMICategory(bmi);
  const idealMin = parseFloat((18.5 * heightM * heightM).toFixed(1));
  const idealMax = parseFloat((24.9 * heightM * heightM).toFixed(1));
  const weightDiff = weightKg < idealMin ? weightKg - idealMin
                    : weightKg > idealMax ? weightKg - idealMax : 0;
  const primeBMI  = (bmi / 25).toFixed(2);

  // TDEE
  const age      = parseInt(document.getElementById('age-input').value) || 25;
  const sex      = document.getElementById('sex-select').value;
  const activity = document.getElementById('activity-select').value;
  const bmr = sex === 'male'
    ? 10 * weightKg + 6.25 * heightCm - 5 * age + 5
    : 10 * weightKg + 6.25 * heightCm - 5 * age - 161;
  const tdee = Math.round(bmr * (ACTIVITY_MULT[activity] || 1.55));

  // Update hero
  bmiNum.textContent  = bmi.toFixed(1);
  bmiCat.textContent  = cat.label;
  bmiDesc.textContent = cat.desc;
  bmiHero.classList.add('active');
  bmiHero.style.setProperty('--result-color', cat.color);
  bmiNum.style.color  = cat.color;
  bmiCat.style.color  = cat.color;

  // Update result cards
  document.getElementById('rc-bmi').textContent         = bmi.toFixed(1);
  document.getElementById('rc-ideal-min').textContent   = idealMin;
  document.getElementById('rc-ideal-max').textContent   = idealMax;
  document.getElementById('rc-diff').textContent        = Math.abs(weightDiff).toFixed(1);
  document.getElementById('rc-diff-unit').textContent   = 'kg';
  document.getElementById('rc-diff-lbl').textContent    =
    weightDiff === 0 ? '✓ Di rentang ideal' : (weightDiff > 0 ? 'Kelebihan berat' : 'Kekurangan berat');
  document.getElementById('rc-prime').textContent       = primeBMI;
  document.getElementById('rc-tdee').textContent        = tdee.toLocaleString('id');
  document.getElementById('rc-conv').textContent        =
    currentUnit === 'metric'
      ? (weightKg / 0.453592).toFixed(1) + ' lbs'
      : weightKg.toFixed(1) + ' kg';

  // Calorie goals
  document.getElementById('cal-lose').textContent     = Math.max(1200, tdee - 500).toLocaleString('id') + ' kkal';
  document.getElementById('cal-maintain').textContent = tdee.toLocaleString('id') + ' kkal';
  document.getElementById('cal-gain').textContent     = (tdee + 500).toLocaleString('id') + ' kkal';

  // Ideal range bar
  const minKg = 20, maxKg = 150;
  const idealMinPct = ((idealMin - minKg) / (maxKg - minKg)) * 100;
  const idealMaxPct = ((idealMax - minKg) / (maxKg - minKg)) * 100;
  const curPct      = Math.max(0, Math.min(100, ((weightKg - minKg) / (maxKg - minKg)) * 100));
  document.getElementById('ideal-fill').style.left      = idealMinPct + '%';
  document.getElementById('ideal-fill').style.width     = (idealMaxPct - idealMinPct) + '%';
  document.getElementById('ideal-marker').style.left    = curPct + '%';
  document.getElementById('ideal-range-label-min').textContent = idealMin + ' kg';
  document.getElementById('ideal-range-label-max').textContent = idealMax + ' kg';

  resSect.style.display = '';
  updateScaleMarker(bmi);
  updateBMITable(cat.label);
}

// ── BMI category ──────────────────────────────────────────────
function getBMICategory(bmi) {
  for (const c of BMI_CATS) {
    if (bmi >= c.min && bmi < c.max) return c;
  }
  return BMI_CATS[BMI_CATS.length - 1];
}

// ── Scale marker ──────────────────────────────────────────────
function updateScaleMarker(bmi) {
  const marker = document.getElementById('scale-marker');
  if (!marker) return;
  if (bmi === null) { marker.style.left = '0%'; return; }
  // Map BMI 16–40+ to 0–100%
  const pct = Math.max(0, Math.min(100, ((bmi - 16) / (40 - 16)) * 100));
  marker.style.left = pct + '%';
  marker.style.background = getBMICategory(bmi).color;
}

function updateBMITable(activeLabel) {
  document.querySelectorAll('.bmi-table tbody tr').forEach((tr, i) => {
    tr.classList.toggle('highlight', BMI_CATS[i]?.label === activeLabel);
  });
}

// ── Utilities ─────────────────────────────────────────────────
function loadExample(weightKg, heightCm) {
  setUnit('metric');
  document.getElementById('weight-kg').value     = weightKg;
  document.getElementById('weight-slider').value = Math.min(200, weightKg);
  document.getElementById('weight-badge').textContent = weightKg;
  document.getElementById('height-cm').value     = heightCm;
  document.getElementById('height-slider').value = heightCm;
  document.getElementById('height-badge').textContent = heightCm;
  calcJS();
}

function resetForm() {
  setUnit('metric');
  document.getElementById('weight-kg').value     = 70;
  document.getElementById('weight-slider').value = 70;
  document.getElementById('weight-badge').textContent = 70;
  document.getElementById('height-cm').value     = 170;
  document.getElementById('height-slider').value = 170;
  document.getElementById('height-badge').textContent = 170;
  document.getElementById('age-input').value     = 25;
  document.getElementById('sex-select').value    = 'male';
  document.getElementById('activity-select').value = 'moderate';
  calcJS();
}

// ── Init ──────────────────────────────────────────────────────
setUnit(currentUnit);
calcJS();
</script>

<?php require '../../includes/footer.php'; ?>