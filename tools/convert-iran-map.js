// تبدیل GeoJSON استان‌های ایران به داده SVG برای افزونه مشتریار
// ورودی: geoBoundaries-IRN-ADM1.json — خروجی: ../assets/js/iran-paths.js
const fs = require("fs");
const path = require("path");

const geo = require(path.join(__dirname, "geoBoundaries-IRN-ADM1.json"));

// نگاشت نام انگلیسی geoBoundaries به کد استان ووکامرس و نام فارسی
const MAP = {
  "Tehran": ["THR", "تهران"],
  "Alborz": ["ALZ", "البرز"],
  "East Azerbaijan": ["EAST", "آذربایجان شرقی"],
  "West Azerbaijan": ["WEST", "آذربایجان غربی"],
  "Ardabil": ["ADL", "اردبیل"],
  "Isfahan": ["ESF", "اصفهان"],
  "Ilam": ["ILM", "ایلام"],
  "Bushehr": ["BHR", "بوشهر"],
  "Chaharmahal and Bakhtiari": ["CHB", "چهارمحال و بختیاری"],
  "South Khorasan": ["SKH", "خراسان جنوبی"],
  "Razavi Khorasan": ["RKH", "خراسان رضوی"],
  "North Khorasan": ["MKD", "خراسان شمالی"],
  "Khuzestan": ["KHZ", "خوزستان"],
  "Zanjan": ["ZJN", "زنجان"],
  "Semnan": ["SMN", "سمنان"],
  "Sistan and Baluchestan": ["SBK", "سیستان و بلوچستان"],
  "Fars": ["FAR", "فارس"],
  "Qazvin": ["QAZ", "قزوین"],
  "Qom": ["QHM", "قم"],
  "Kurdistan": ["KRD", "کردستان"],
  "Kerman": ["KRN", "کرمان"],
  "Kermanshah": ["KRH", "کرمانشاه"],
  "Kohgiluyeh and Boyer-Ahmad": ["KBD", "کهگیلویه و بویراحمد"],
  "Golestan": ["GLS", "گلستان"],
  "Gilan": ["GIL", "گیلان"],
  "Lorestan": ["LRS", "لرستان"],
  "Mazandaran": ["MDL", "مازندران"],
  "Markazi": ["MKZ", "مرکزی"],
  "Hormozgan": ["HRZ", "هرمزگان"],
  "Hamadan": ["HMD", "همدان"],
  "Yazd": ["YZD", "یزد"],
};

function rings(feature) {
  const c = feature.geometry.coordinates;
  return feature.geometry.type === "Polygon" ? [c[0]] : c.map((p) => p[0]);
}

// جمع‌آوری استان‌ها (مازندران تکراری: بزرگ‌ترین حلقه)
const byCode = new Map();
for (const f of geo.features) {
  const m = MAP[f.properties.shapeName.trim()];
  if (!m) { console.error("بدون نگاشت:", f.properties.shapeName); continue; }
  const [id, fa] = m;
  const best = rings(f).reduce((a, b) => (b.length > a.length ? b : a));
  if (!byCode.has(id) || byCode.get(id).length < best.length) byCode.set(id, { fa, ring: best });
}

// محدوده جغرافیایی
let minLon = Infinity, maxLon = -Infinity, minLat = Infinity, maxLat = -Infinity;
for (const v of byCode.values())
  for (const [lon, lat] of v.ring) {
    minLon = Math.min(minLon, lon); maxLon = Math.max(maxLon, lon);
    minLat = Math.min(minLat, lat); maxLat = Math.max(maxLat, lat);
  }

const W = 1000, H = 880, PAD = 20;
const lonSpan = maxLon - minLon, latSpan = maxLat - minLat;
const cosMid = Math.cos((((minLat + maxLat) / 2) * Math.PI) / 180);
const sx = (W - 2 * PAD) / (lonSpan * cosMid), sy = (H - 2 * PAD) / latSpan;
const s = Math.min(sx, sy);
const ox = PAD, oy = PAD + Math.max(0, (H - 2 * PAD - latSpan * s) / 2);
const px = (lon) => +(ox + (lon - minLon) * cosMid * s).toFixed(1);
const py = (lat) => +(oy + (maxLat - lat) * s).toFixed(1);

// ساده‌سازی: هر حلقه حداکثر ~۱۴۰ نقطه
function decimate(ring, maxPts) {
  if (ring.length <= maxPts) return ring;
  const step = Math.ceil(ring.length / maxPts);
  const out = [];
  for (let i = 0; i < ring.length; i += step) out.push(ring[i]);
  return out;
}

const provinces = [];
for (const [id, v] of byCode) {
  const pts = decimate(v.ring, 140);
  const d =
    "M" +
    pts.map(([lon, lat]) => `${px(lon)},${py(lat)}`).join("L") +
    "Z";
  provinces.push({ id, fa: v.fa, d });
}

const out = `/* داده نقشه SVG استان‌های ایران — تولیدشده با tools/convert-iran-map.js */
/* global window */
window.MOSHTARYAR_IRAN_PATHS = ${JSON.stringify(provinces)};
`;
const dst = path.join(__dirname, "..", "assets", "js", "iran-paths.js");
fs.mkdirSync(path.dirname(dst), { recursive: true });
fs.writeFileSync(dst, out);
console.log(`تولید شد: ${provinces.length} استان → ${dst} (${(fs.statSync(dst).size / 1024).toFixed(1)} KB)`);
