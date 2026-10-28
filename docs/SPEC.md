# مشخصات فنی افزونه «مشتریار (MoshtariYar)» — نسخه ۱.۰.۰

> **محصول:** تحلیل‌گر مشتری ووکامرس با مدل RFM — کاملاً فارسی و راست‌چین
> **تاریخ سند:** ۲۰۲۶-۱۰-۲۷
> **مرجع الزامات فروشگاهی:** `docs/rtl-theme-rules.md` (قوانین راست‌چین) — همه بندهای این SPEC با آن سند سازگار نوشته شده‌اند.
> **وضعیت:** نسخه ۱.۰.۰ — سند اجرایی برای سه سازنده

---

## ۱. خلاصه محصول و دامنه

- **کاربر هدف:** صاحبان فروشگاه ووکامرس ایران. همه رابط‌ها فارسی و راست‌چین از ابتدا.
- **هسته محصول:** موتور RFM (Recency / Frequency / Monetary) که نتایج را در جدول اختصاصی ذخیره می‌کند تا صفحات ادمین سریع باشند.
- **خروجی‌ها:** داشبورد KPI + نمودار دونات سگمنت‌ها + نمودار میله‌ای ۱۲ ماه فروش، جدول مشتریان با جست‌وجو/فیلتر/مرتب‌سازی/صفحه‌بندی/CSV، نقشه حرارتی ۳۱ استان ایران، و ستون «سگمنت» در لیست مشتریان ووکامرس.
- **محدودیت‌های صریح محصول:**
  - هیچ فراخوانی شبکه خارجی، هیچ CDN، هیچ کتابخانه جاوااسکریپت بیرونی (نمودارها و نقشه با SVG/Javascript خام دست‌ساز).
  - هیچ اطلاعات تماس شخصی در کد، فایل راهنما یا دمو (الزام ماده ۱۸ راست‌چین — منبع: `rtl-theme-rules.md` بند ۳۸).
  - پشتیبانی Multisite لازم نیست (تک‌سایتی).
  - بدون تنظیمات پیچیده در نسخه ۱.۰.۰؛ سفارشی‌سازی فقط از طریق فیلترهای PHP (بخش ۹).

## ۲. الزامات محیط و سازگاری (اعلام دقیق — نه کلی‌گویی)

| مورد | الزام | یادداشت |
|---|---|---|
| WordPress | از ۶.۰ به بالا | بررسی نسخه در فعال‌سازی؛ در صورت پایین‌تر بودن: پیام فارسی + عدم فعال‌سازی |
| WooCommerce | از ۷.۰ به بالا | نبود ووکامرس = پیام ادمین + توقف بارگذاری بدون Fatal |
| PHP | از ۷.۴ به بالا | هدف آزمون تا PHP 8.3/8.4 در فاز QA (الگوی اعلام بازه دقیق — منبع: `rtl-theme-rules.md` بند ۵۲) |
| HPOS | سازگار کامل | اعلام با `FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true)` در هوک `before_woocommerce_init` |
| حافظه/سرور | در توضیحات محصول اعلام شود | memory_limit پیشنهادی ۱۲۸M+ (الزام ماده ۷ بند ۳ — منبع: `rtl-theme-rules.md` بند ۴۳) |

**هدر افزونه (moshtaryar.php):**

```php
/**
 * Plugin Name: مشتریار — تحلیل‌گر مشتری ووکامرس (RFM)
 * Description: تحلیل RFM مشتریان ووکامرس، سگمنت‌بندی فارسی، داشبورد مدیریتی و نقشه حرارتی استان‌های ایران.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * Text Domain: moshtaryar
 * Domain Path: /languages
 */
```

## ۳. اصول زبانی، راست‌چین و نمایش

1. **RTL از ابتدا:** تمام CSS با `direction: rtl` و چیدمان منطقی راست‌به‌چپ نوشته می‌شود (بدون هک `rtl: true` یا بازنویسی پس از تولید). منبع الزام: `rtl-theme-rules.md` بند ۴۶.
2. **رشته‌ها به فارسی در سورس:** متن پیش‌فرض رشته‌ها فارسی است و همگی با `__('...', 'moshtaryar')` / `esc_html__()` بسته‌بندی می‌شوند تا Text Domain معتبر بماند و در نسخه‌های بعد ترجمه‌پذیر باشد.
3. **اعداد فارسی:** هر عدد نمایشی از توابع `moshtaryar_fa_num()` / `moshtaryar_price()` عبور می‌کند (تبدیل ۰-۹ به ۰-۹ فارسی + جداکننده هزارگان). در CSV اعداد لاتین می‌مانند (بخش ۱۳).
4. **تاریخ شمسی:** همه تاریخ‌های نمایشی جلالی هستند؛ تبدیل با الگوریتم استاندارد جلالی داخل `includes/helpers.php` پیاده‌سازی می‌شود (بدون کتابخانه بیرونی). نام ماه‌ها: فروردین … اسفند. منبع الزام: `rtl-theme-rules.md` بند ۴۷.
5. **فونت:** Vazirmatn (مجوز OFL) به‌صورت محلی در `admin/fonts/` — فقط woff2، با `@font-face` در CSS؛ بارگذاری شبکه‌ای صفر. فونت جایگزین سیستم: Tahoma. **[نیازمند تأیید:** اگر ناشر فونت تجاری ایرانی را تأمین کند، در فاز بسته‌بندی جایگزین می‌شود.]
6. **جهت جریان زمان در نمودارها:** در نمودار میله‌ای، قدیمی‌ترین ماه در راست و جدیدترین در چپ (خوانش طبیعی RTL).

## ۴. اصول امنیت سراسری (خلاصه — جزئیات در بخش ۱۲)

- تمام کوئری‌های پارامتردار با `$wpdb->prepare()`؛ مقادیر `LIKE` با `$wpdb->esc_like()`.
- هر ورودی GET/POST: `sanitize_text_field` / `sanitize_email` / `absint`؛ نام ستون مرتب‌سازی و جهت آن فقط از **لیست مجاز** (هرگز ورودی کاربر مستقیم وارد SQL نمی‌شود).
- هر خروجی: `esc_html` / `esc_attr` / `esc_url` / `wp_json_encode`.
- هر کنش نوشتنی: `check_admin_referer()` + `current_user_can('manage_woocommerce')`.
- هر فایل PHP با گارد `if ( ! defined( 'ABSPATH' ) ) { exit; }` شروع می‌شود.
- صفر درخواست `wp_remote_*` در کل کدپایه.

## ۵. درخت فایل کامل

۲۸ فایل تولیدی (شمارش فایلCount این سند = همین ۲۸ فایل). دو سند `docs/SPEC.md` و `docs/rtl-theme-rules.md` از قبل موجودند و در شمارش نیستند؛ `docs/help.pdf` پس از اتمام کد و گرفتن اسکرین‌شات‌ها تولید می‌شود (الزام بسته‌بندی راست‌چین) و در شمارش سازندگان نیست.

```
moshtaryar/
├── moshtaryar.php                          ← فایل اصلی: هدر افزونه، ثابت‌ها (MY_VER، MY_FILE، MY_DIR، MY_URL)،
│                                             گارد پیش‌نیازها، بارگذاری includes، اعلام سازگاری HPOS، هوک‌های فعال‌سازی/غیرفعال‌سازی
├── uninstall.php                           ← پاک‌سازی کامل هنگام حذف: DROP دو جدول، حذف optionها، لغو cron (بخش ۱۴)
├── readme.txt                              ← readme استاندارد وردپرس به فارسی (توضیح، نصب، سوالات متداول)
├── CHANGELOG.md                            ← چنج‌لاگ فارسی از نسخه ۱.۰.۰ (الزام ماده ۱۸ راست‌چین)
├── docs/
│   ├── SPEC.md                             ← (موجود) همین سند
│   ├── rtl-theme-rules.md                  ← (موجود) الزامات راست‌چین
│   └── help.pdf                            ← راهنمای فارسی نصب/استفاده/بروزرسانی — تولید پس از کدنویسی، خارج از شمارش سازندگان
├── includes/                               ← هسته (منطق داده) — ساخته «سازنده هسته RFM»
│   ├── class-moshtaryar-plugin.php         ← بارگذار مرکزی (Singleton): نمونه‌سازی اجزا، چک ووکامرس، بارگذاری شرطی admin
│   ├── class-moshtaryar-install.php        ← ساخت جدول‌ها با dbDelta، optionهای پیش‌فرض، زمان‌بندی cron؛ لغو cron هنگام غیرفعال‌سازی
│   ├── class-moshtaryar-rfm-engine.php     ← موتور RFM: تجمیع سفارش‌ها (سازگار HPOS/کلاسیک)، محاسبه R/F/M، بازنویسی جدول مشتریان؛
│   │                                          recalc کامل، recalc تک‌مشتری، نگهداری جدول سفارش‌های آماری
│   ├── class-moshtaryar-segments.php       ← تعریف ۸ سگمنت: نامک، برچسب فارسی، رنگ، قواعد ارزیابی به‌ترتیب (بخش ۷-۴)
│   ├── class-moshtaryar-queries.php        ← کوئری‌های خواندنی: KPIها، توزیع سگمنت‌ها، روند ۱۲ ماه، جدول مشتریان (جست‌وجو/فیلتر/مرتب/صفحه‌بندی)
│   ├── class-moshtaryar-state-stats.php    ← تجمیع به تفکیک استان از جدول سفارش‌های آماری (تعداد سفارش / درآمد)
│   ├── class-moshtaryar-geo.php            ← داده ۳۱ استان (نامک ووکامرس + نام فارسی)، نرمال‌سازی shipping_state، سطل «نامشخص»
│   ├── class-moshtaryar-hooks.php          ← اتصال به ووکامرس: بازمحاسبه خودکار بعد از تغییر وضعیت سفارش؛ هوک‌های ستون/فیلتر لیست مشتریان ووکامرس
│   ├── class-moshtaryar-i18n.php           ← load_plugin_textdomain برای دامنه moshtaryar
│   └── helpers.php                         ← توابع کمکی: moshtaryar_fa_num، moshtaryar_price، تبدیل جلالی، فرمت‌دهی درصد
├── admin/                                  ← رابط مدیریت — ساخته «سازنده رابط مدیریت» و «سازنده نقشه ایران» (بخش ۱۶)
│   ├── class-moshtaryar-admin.php          ← منوی ادمین «مشتریار»، روتینگ صفحات، گارد capability، نمونه‌سازی کلاس‌های صفحه
│   ├── class-moshtaryar-admin-dashboard.php← صفحه داشبورد: ۴ کارت KPI، دونات سگمنت‌ها، میله‌ای ۱۲ ماه، دکمه بازمحاسبه
│   ├── class-moshtaryar-admin-customers.php← صفحه جدول مشتریان: جست‌وجو، فیلتر سگمنت، مرتب‌سازی، صفحه‌بندی، دکمه CSV
│   ├── class-moshtaryar-admin-actions.php  ← کنش‌های POST (admin-post): بازمحاسبه دستی و خروجی CSV — nonce + capability
│   ├── class-moshtaryar-csv-export.php     ← ساخت و استریم CSV با BOM UTF-8 (بخش ۱۳)
│   ├── class-moshtaryar-admin-map.php      ← [سازنده نقشه ایران] صفحه نقشه: SVG نقشه، سوییچ حالت، لجند، جدول استان‌ها
│   ├── css/moshtaryar-admin.css            ← [سازنده رابط مدیریت] استایل کامل ادمین — RTL از ابتدا، @font-face فونت محلی، استایل کارت‌ها/جدول/لجند
│   ├── js/moshtaryar-admin.js              ← [سازنده رابط مدیریت] رفتارهای عمومی: سوییچ‌ها، تایید پیش از بازمحاسبه، نمایش notice
│   ├── js/moshtaryar-charts.js             ← [سازنده رابط مدیریت] رندر SVG خام: نمودار دونات + نمودار میله‌ای، تولتیپ، اعداد فارسی
│   ├── js/moshtaryar-iran-map.js           ← [سازنده نقشه ایران] رندر نقشه حرارتی: طیف رنگی ۵ پله، تولتیپ استان، سوییچ سفارش/درآمد
│   ├── js/moshtaryar-iran-paths.js         ← [سازنده نقشه ایران] داده pathهای SVG ۳۱ استان + viewBox (بخش ۱۰-۳)
│   └── fonts/
│       ├── Vazirmatn-Regular.woff2         ← [سازنده رابط مدیریت] فونت محلی (بدون CDN)
│       └── Vazirmatn-Bold.woff2            ← [سازنده رابط مدیریت] فونت محلی — وزن ضخیم
└── languages/
    └── moshtaryar.pot                      ← [سازنده هسته RFM — گام پایانی] الگوی ترجمه؛ تولید بعد از تکمیل سه سازنده
```

## ۶. اسکیمای جدول‌ها (dbDelta-سازگار)

هر دو جدول با `$wpdb->get_charset_collate()` و تابع `dbDelta()` ساخته می‌شوند (فاصله دو اسپیس بعد از `PRIMARY KEY` و قواعد dbDelta رعایت شود). نسخه اسکیما در option با نام `moshtaryar_db_version` نگه داشته می‌شود و روتین ارتقا روی `plugins_loaded` چک می‌کند.

### ۶-۱. جدول مشتریان: `{$wpdb->prefix}moshtaryar_customers`

```sql
CREATE TABLE {$wpdb->prefix}moshtaryar_customers (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  billing_email VARCHAR(190) NOT NULL DEFAULT '',
  display_name VARCHAR(190) NOT NULL DEFAULT '',
  last_order_date DATETIME NULL DEFAULT NULL,
  recency_days SMALLINT UNSIGNED NULL DEFAULT NULL,
  order_count INT UNSIGNED NOT NULL DEFAULT 0,
  total_spent DECIMAL(26,8) NOT NULL DEFAULT 0,
  avg_order_value DECIMAL(26,8) NOT NULL DEFAULT 0,
  r_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  f_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  m_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  segment VARCHAR(32) NOT NULL DEFAULT '',
  segment_updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY identity (user_id, billing_email),
  KEY segment (segment),
  KEY last_order_date (last_order_date),
  KEY total_spent (total_spent),
  KEY order_count (order_count)
) $charset_collate;
```

- `user_id = 0` یعنی مهمان؛ هویت مهمان = `billing_email` (کوچک‌شده). یگانه‌بودن با کلید `identity` تضمین می‌شود.
- فقط مشتریان با **حداقل یک سفارش در وضعیت‌های شمارش‌شده** در این جدول ذخیره می‌شوند.
- `avg_order_value = total_spent / order_count` (صفر اگر order_count صفر — که طبق قانون بالا اتفاق نمی‌افتد).

### ۶-۲. جدول سفارش‌های آماری: `{$wpdb->prefix}moshtaryar_orders`

آینه سبک از سفارش‌های شمارش‌شده، برای داشبورد/روند/نقشه بدون فشار به جدول‌های ووکامرس:

```sql
CREATE TABLE {$wpdb->prefix}moshtaryar_orders (
  order_id BIGINT(20) UNSIGNED NOT NULL,
  customer_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  date_created DATETIME NULL DEFAULT NULL,
  total_amount DECIMAL(26,8) NOT NULL DEFAULT 0,
  shipping_state VARCHAR(20) NOT NULL DEFAULT '',
  PRIMARY KEY  (order_id),
  KEY customer_id (customer_id),
  KEY date_created (date_created),
  KEY shipping_state (shipping_state)
) $charset_collate;
```

- `date_created` به‌وقت محلی سایت (خروجی `current_time('mysql')` منشأ) تا گروه‌بندی ماهیانه با منطقه‌زمانی فروشگاه هم‌خوان باشد.
- `shipping_state` پس از نرمال‌سازی با `MoshtariYar_Geo::normalize_state()` ذخیره می‌شود (نامک ووکامرس مثل `THR`، یا `unknown`).

### ۶-۳. optionها

| کلید | مقدار |
|---|---|
| `moshtaryar_version` | 1.0.0 |
| `moshtaryar_db_version` | 1 |
| `moshtaryar_last_recalc` | timestamp آخرین بازمحاسبه کامل |
| `moshtaryar_m_thresholds` | آرایه ۴ آستانه پولی (بخش ۷-۳) |
| `moshtaryar_counted_statuses` | آرایه وضعیت‌های شمارش‌شده |

## ۷. موتور RFM

### ۷-۱. منبع داده و سازگاری HPOS

- در یک بازمحاسبه کامل، موتور نوع فروشگاه را با `wc_get_container()` / کلاس `Automattic\WooCommerce\Utilities\OrderUtil::hpos_enabled()` تشخیص می‌دهد:
  - **HPOS فعال:** تجمیع مستقیم از جدول‌های `wc_orders` و `wc_order_addresses` (ستون‌های `customer_id`، `billing_email`، `total_amount`، `date_created_gmt`، `shipping_state`).
  - **HPOS غیرفعال:** تجمیع از `wp_posts` (نوع `shop_order`) + `wp_postmeta` (`_customer_user`، `_billing_email`، `_order_total`، `_order_date`، `_shipping_state`).
- هر دو مسیر خروجی یکسان می‌دهند: آرایه‌ای از گروه‌های مشتری با `user_id`، `billing_email`، `display_name`، `last_order_date`، `order_count`، `total_spent`، و فهرست سفارش‌های هر مشتری (برای پر کردن `moshtaryar_orders`).
- پردازش دسته‌ای با `LIMIT/`آفست (پیش‌فرض ۵۰۰ سفارش در هر دسته) تا در فروشگاه‌های بزرگ از حافظه نشکند؛ `wp_raise_memory_limit('admin')` در بازمحاسبه دستی.

### ۷-۲. قواعد هم‌گروه‌سازی و ادغام

1. گروه‌بندی اولیه: اگر `_customer_user` > 0 → کلید = `user_id`؛ وگرنه کلید = `billing_email` (کوچک‌شده + `sanitize_email`).
2. گذر دوم ادغام: هر گروه مهمان که ایمیلش با ایمیل یک کاربر ثبت‌شده برابر است، در گروه همان کاربر ادغام می‌شود (سفارش‌های مهمانِ پیش از ثبت‌نام هم شمرده می‌شوند) و ردیف مهمان حذف می‌شود.
3. «سفارش شمارش‌شده» = سفارشی که وضعیتش در فهرست زیر باشد (فیلترپذیر):

```php
apply_filters( 'moshtaryar_counted_statuses', array( 'wc-completed' ) );
```

4. استثنا: سفارش‌هایی که از وضعیت شمارش‌شده خارج شده‌اند (مثلاً بازپرداخت کامل → `wc-refunded`) با گذر شبانه حذف می‌شوند؛ در بازمحاسبه تک‌مشتری هم لحظه‌ای اصلاح می‌شوند.
5. بازپرداخت جزئی در نسخه ۱.۰.۰ کسر نمی‌شود (محدودیت مستند در راهنما).

### ۷-۳. امتیازدهی (فرمول)

هر بُعد امتیاز ۱ تا ۵ می‌گیرد. آستانه‌ها **قطعی و فیلترپذیر** هستند تا در هر فروشگاهی قابل تنظیم باشند.

**Recency — R:** روزهای سپری‌شده از آخرین سفارش شمارش‌شده: `recency_days = floor((current_time('timestamp') − آخرین تاریخ سفارش) / 86400)` (حداقل ۰).

| امتیاز R | روز از آخرین خرید |
|---|---|
| ۵ | ≤ ۳۰ |
| ۴ | ۳۱ تا ۹۰ |
| ۳ | ۹۱ تا ۱۸۰ |
| ۲ | ۱۸۱ تا ۳۶۵ |
| ۱ | > ۳۶۵ |

**Frequency — F:** تعداد سفارش‌های شمارش‌شده.

| امتیاز F | تعداد سفارش |
|---|---|
| ۵ | ≥ ۱۰ |
| ۴ | ۶ تا ۹ |
| ۳ | ۴ تا ۵ |
| ۲ | ۲ تا ۳ |
| ۱ | ۱ |

**Monetary — M:** مجموع مبلغ سفارش‌های شمارش‌شده (به واحد پایه فروشگاه).

| امتیاز M | مجموع خرید |
|---|---|
| ۵ | ≥ m4 |
| ۴ | m3 تا m4 |
| ۳ | m2 تا m3 |
| ۲ | m1 تا m2 |
| ۱ | < m1 |

آستانه‌های پولی پیش‌فرض (پیشنهادی — **[نیازمند تأیید نهایی محصول]**): `m1=1,000,000; m2=5,000,000; m3=20,000,000; m4=50,000,000` و از فیلتر خوانده می‌شود:

```php
apply_filters( 'moshtaryar_m_thresholds', get_option( 'moshtaryar_m_thresholds', array( 1000000, 5000000, 20000000, 50000000 ) ) );
```

آستانه‌های R و F هم با فیلترهای `moshtaryar_r_thresholds` (بروکر مرزهای روزی: `30, 90, 180, 365`) و `moshtaryar_f_thresholds` (مرزها: `2, 4, 6, 10`) قابل تغییرند.

### ۷-۴. سگمنت‌ها — ۸ گروه با برچسب فارسی

نامک‌های لاتین برای ذخیره در DB؛ برچسب و رنگ فقط برای نمایش:

| نامک | برچسب فارسی | رنگ پیشنهادی |
|---|---|---|
| `champions` | قهرمان | `#16a34a` |
| `loyal` | وفادار | `#0ea5e9` |
| `potential_loyal` | بالقوه وفادار | `#6366f1` |
| `new` | تازه‌وارد | `#22d3ee` |
| `needs_attention` | نیازمند توجه | `#f59e0b` |
| `at_risk` | در خطر رفتن | `#f97316` |
| `hibernating` | خوابیده | `#a855f7` |
| `lost` | از دست رفته | `#ef4444` |

**قواعد ارزیابی — به ترتیب، اولین قاعده منطبق برنده است** (پوشش کامل ۲۵ ترکیب R×F؛ M فقط در قاعده قهرمان نقش دارد):

| # | سگمنت | قاعده |
|---|---|---|
| ۱ | قهرمان | R ≥ 4 **و** F ≥ 4 **و** M ≥ 4 |
| ۲ | در خطر رفتن | R ≤ 2 **و** F ≥ 3 |
| ۳ | وفادار | F ≥ 4 |
| ۴ | بالقوه وفادار | R ≥ 4 **و** F = 3 |
| ۵ | تازه‌وارد | R ≥ 4 **و** F ≤ 2 |
| ۶ | نیازمند توجه | R = 3 |
| ۷ | خوابیده | F = 2 (منظور R ≤ 2 و F = 2؛ چون R ≤ 2 و F ≥ 3 در قاعده ۲ گرفته شده) |
| ۸ | از دست رفته | بقیه (R ≤ 2 و F = 1) |

منطق تضمین‌شده: R≥4 → قواعد ۱/۳/۴/۵؛ R=3 → ۶ (و F≥4 در ۳ به «وفادار» می‌رسد)؛ R≤2 → ۲/۷/۸. کل قواعد در یک آرایه قابل فیلترند:

```php
apply_filters( 'moshtaryar_segment_rules', $rules ); // هر عضو: ['segment' => نامک, 'test' => callable(R,F,M)]
apply_filters( 'moshtaryar_segment_labels', $labels ); // نامک => ['label' => برچسب فارسی, 'color' => رنگ]
```

### ۷-۵. زمان‌بندی و محرک‌های بازمحاسبه

| محرک | سازوکار |
|---|---|
| شبانه | cron رویداد `moshtaryar_daily_recalc`، روزی ساعت ۰۲:۰۰ به وقت سایت، ثبت در فعال‌سازی با `wp_schedule_event()` + گارد تکرار نبودن؛ اجرا → `MoshtariYar_RFM_Engine::full_recalc()` |
| بعد از هر سفارش شمارش‌شده | هوک `woocommerce_order_status_changed` (از یا به وضعیت شمارش‌شده) → `MoshtariYar_RFM_Engine::recalc_customer( $order )` فقط برای همان مشتری + به‌روزرسانی سطر همان سفارش در `moshtaryar_orders` |
| دستی | دکمه «بازمحاسبه کامل» در داشبورد → `admin_post_moshtaryar_recalc` (بخش ۱۲) → بازمحاسبه کامل + ریدایرکت با پیام نتیجه |

پس از هر بازمحاسبه: `moshtaryar_last_recalc` به‌روز می‌شود و اکشن‌های `moshtaryar_after_full_recalc` / `moshtaryar_after_customer_recalc` فراخوانی می‌شوند.

## ۸. تعریف KPIها و کوئری‌های گزارشی

| KPI | تعریف دقیق | منبع |
|---|---|---|
| تعداد کل مشتریان | `COUNT(*)` از `moshtaryar_customers` | جدول اختصاصی |
| فعال ۳۰ روز اخیر | `COUNT(*) WHERE last_order_date >= NOW(زمان سایت) − 30 DAY` | جدول اختصاصی |
| میانگین ارزش مشتری | `AVG(total_spent)` | جدول اختصاصی |
| نرخ تکرار خرید | `COUNT(order_count ≥ 2) ÷ COUNT(*) × ۱۰۰` — درصد با یک رقم اعشار | جدول اختصاصی |

- **توزیع سگمنت‌ها:** `GROUP BY segment` با ترتیب ثابت بخش ۷-۴.
- **روند ۱۲ ماه فروش:** از `moshtaryar_orders`؛ `GROUP BY` سال/ماه ۱۲ ماه منتهی به امروز؛ برچسب هر ماه جلالی (مثلاً «مهر ۱۴۰۵»).
- **جدول مشتریان:** جست‌وجو (LIKE روی `display_name` و `billing_email` با `esc_like` + `prepare`)، فیلتر سگمنت، مرتب‌سازی فقط روی ستون‌های مجاز (`display_name`، `last_order_date`، `order_count`، `total_spent`، `avg_order_value`)، صفحه‌بندی ۲۰ ردیفی (فیلترپذیر با `moshtaryar_customers_per_page`)، و شمارش کل برای صفحه‌بندی.

## ۹. لیست کامل هوک‌ها و فیلترها

### ۹-۱. اکشن‌های مصرفی وردپرس/ووکامرس

| هوک | محل | کارکرد |
|---|---|---|
| `plugins_loaded` (پیش‌فرض) | `moshtaryar.php` | نمونه‌سازی `MoshtariYar_Plugin`، چک ووکامرس |
| `before_woocommerce_init` | `moshtaryar.php` | اعلام سازگاری HPOS |
| `register_activation_hook` | `moshtaryar.php` | `MoshtariYar_Install::activate()` |
| `register_deactivation_hook` | `moshtaryar.php` | لغو cron |
| `woocommerce_order_status_changed` | `class-moshtaryar-hooks.php` | بازمحاسبه تک‌مشتری |
| `admin_menu` | `class-moshtaryar-admin.php` | ثبت منو |
| `admin_enqueue_scripts` | هر کلاس صفحه | بارگذاری دارایی فقط روی صفحه خودش |
| `admin_post_moshtaryar_recalc` | `class-moshtaryar-admin-actions.php` | کنش بازمحاسبه دستی |
| `admin_post_moshtaryar_export_csv` | `class-moshtaryar-admin-actions.php` | کنش خروجی CSV |
| هوک‌های ستون/فیلتر لیست مشتریان ووکامرس | `class-moshtaryar-hooks.php` | **[نیازمند تأیید — بخش ۱۱]** |

### ۹-۲. رویداد cron و اکشن‌های داخلی افزونه

- رویداد cron: `moshtaryar_daily_recalc`
- اکشن‌ها: `moshtaryar_after_full_recalc`، `moshtaryar_after_customer_recalc( $customer_id )`

### ۹-۳. فیلترهای داخلی افزونه

`moshtaryar_counted_statuses` · `moshtaryar_r_thresholds` · `moshtaryar_f_thresholds` · `moshtaryar_m_thresholds` · `moshtaryar_segment_rules` · `moshtaryar_segment_labels` · `moshtaryar_customers_per_page` · `moshtaryar_customers_sortable_columns` · `moshtaryar_csv_columns` · `moshtaryar_kpis` · `moshtaryar_monthly_sales` · `moshtaryar_map_data` · `moshtaryar_unknown_state_label`

## ۱۰. ساختار صفحات ادمین

منو: **منوی سطح بالا «مشتریار»** با آیکن SVG دست‌ساز (data URI داخلی، بدون فونت‌آیکن خارجی)، `capability = manage_woocommerce`، slug صفحات: `moshtaryar-dashboard`، `moshtaryar-customers`، `moshtaryar-map`.

### ۱۰-۱. داشبورد (`moshtaryar-dashboard`)

```
┌──────────────────────────────────────────────────────────────┐
│ مشتریار — داشبورد          آخرین بازمحاسبه: ۲۶ مهر ۱۴۰۵   [بازمحاسبه کامل] │
├────────────┬────────────┬────────────┬──────────────────────┤
│ کل مشتریان │ فعال ۳۰ روز│ میانگین ارزش مشتری │ نرخ تکرار خرید │
├────────────┴────────────┴────────────┴──────────────────────┤
│ [کارت دونات: توزیع سگمنت‌ها + لجند با تعداد/درصد]  [کارت میله‌ای: فروش ۱۲ ماه] │
└──────────────────────────────────────────────────────────────┘
```

- دونات: SVG رسم‌شده در `moshtaryar-charts.js` از توزیع سگمنت‌ها (رنگ‌ها از بخش ۷-۴)؛ در مرکز عدد کل مشتریان؛ لجند: برچسب فارسی + تعداد + درصد (اعداد فارسی).
- میله‌ای: ۱۲ میله، برچسب جلالی ماه‌ها، تولتیپ با مبلغ فرمت‌شده (`moshtaryar_price`)؛ هاور با رویدادهای DOM، بدون کتابخانه.
- دکمه بازمحاسبه: فرم POST به `admin-post.php` با nonce؛ پیش از ارسال تأیید با `confirm()` فارسی (بدون هیچ کتابخانه UI).

### ۱۰-۲. جدول مشتریان (`moshtaryar-customers`)

- نوار ابزار: جعبه جست‌وجو (GET)، منوی کشویی «همه سگمنت‌ها + ۸ سگمنت»، دکمه «خروجی CSV» (همان فیلترهای فعلی، بدون صفحه‌بندی).
- ستون‌ها: نام، ایمیل، سگمنت (بج رنگی)، آخرین خرید (جلالی)، تعداد سفارش، مجموع خرید، میانگین سبد.
- مرتب‌سازی: کلیک روی سرستون‌های مجاز؛ جهت برعکس‌شونده؛ همه با GET و اسکیل‌پای whitelist.
- صفحه‌بندی: «صفحه ۲ از ۳۷» با اعداد فارسی؛ لینک‌های قبلی/بعدی.
- هر سطر: لینک به پروفایل کاربر وردپرس (`user-edit.php?user_id=…`) برای مشتریان ثبت‌شده.

### ۱۰-۳. نقشه ایران (`moshtaryar-map`)

- نوار ابزار: سوییچ دوحالته «تعداد سفارش ↔ درآمد» (دکمه‌های رادیویی، بدون JS فریم‌ورک).
- کارت نقشه: SVG داخل `viewBox` ثابت؛ هر استان یک `<path>` با `data-province="THR"`؛ رنگ از طیف ۵ پله‌ای تک‌رنگ (پیشنهادی: از `#fef3c7` روشن تا `#991b1c` تیره) بر اساس مقدار استان نسبت به بیشینه.
- تولتیپ: نام فارسی استان + مقدار فرمت‌شده — محتوا با escape کامل (`textContent`، نه innerHTML).
- ستون کنار: لجند طیف + فهرست مرتب استان‌ها (اعداد فارسی)؛ استان‌های خارج از ۳۱ مورد در سطل «نامشخص» (برچسب فیلترپذیر `moshtaryar_unknown_state_label`).
- **قرارداد داده (از `class-moshtaryar-state-stats.php`):**

```php
// پاس داده‌شده با wp_localize_script به اسکریپت moshtaryar-iran-map:
[
  'provinces' => [ ['code' => 'THR', 'name_fa' => 'تهران', 'orders' => 120, 'revenue' => 45000000.0], /* ... */ ],
  'metric'    => 'orders', // یا 'revenue'
  'i18n'      => [ 'orders' => 'سفارش', 'revenue' => 'درآمد', 'unknown' => 'نامشخص' ],
]
```

- **الزام داده جغرافیایی [نیازمند تصمیم توسط سازنده نقشه]:** pathهای ۳۱ استان در فاز ساخت به‌صورت آفلاین از یک منبع GeoJSON با لایسنس آزاد به SVG تبدیل و داخل `moshtaryar-iran-paths.js` ذخیره می‌شوند (`viewBox` واحد مثلاً `0 0 1000 900`). در زمان اجرا هیچ واکشی شبکه‌ای مجاز نیست. مسیرها باید برای هر ۳۱ استان کامل و بدون تداخل باشند؛ هم‌ترازی کد استان‌های فایل با نامک‌های `class-moshtaryar-geo.php` الزامی است.

### ۱۰-۴. نکته صریح درباره «کد استان‌ها»

کد دوحرفی استان‌ها **در سورس هاردکد نمی‌شود**؛ فهرست رسمی استان‌های ایران از خود ووکامرس خوانده می‌شود (`WC()->countries->get_states('IR')`) و `MoshtariYar_Geo` فقط نام فارسی هر نامک را نگه می‌دارد. اگر ووکامرس نامک ناشناخته‌ای فرستاد → سطل `unknown`. این کار از جعل/کهنگی کدها جلوگیری می‌کند.

## ۱۱. یکپارچگی با لیست مشتریان ووکامرس — [نیازمند تأیید در فاز پیاده‌سازی]

الزام محصول: ستون «سگمنت» + فیلتر سگمنت در لیست مشتریان ووکامرس.

- **توجه:** از WooCommerce 7 به بعد صفحه مشتریان به رابط React (WCAdmin، مسیر `admin.php?page=wc-admin&path=/customers`) منتقل شده و مسیر افزودن ستون با آن متفاوت از صفحه کلاسیک است.
- **تصمیم الزامی پیاده‌سازی (در سند علامت خورده، نه حدس‌زده‌شده):** سازنده هسته RFM در شروع کار، منبع WooCommerce 7+ (فایل‌های `includes/admin/class-wc-admin-customers.php` و فیلترهای JS ثبت‌شده WCAdmin) را بررسی و یکی از دو مسیر را قطعی می‌کند:
  1. اگر فیلتر PHP ستون در نسخه هدف معتبر بود (`manage_shop_customer_columns` — نامی که باید با منبع تطبیق داده شود) → افزودن ستون + فیلتر کشویی سگمنت همان مسیر.
  2. در غیر این صورت (WCAdmin/React) → پیاده‌سازی با فیلتر JS رسمی WCAdmin؛ و اگر هیچ فیلتر رسمی برای افزودن ستون وجود نداشت، **این قابلیت در همان نسخه ۱.۰.۰ حذف نمی‌شود بلکه به حداقل معتبر می‌رسد:** نمایش سگمنت در صفحه پروفایل کاربر + فیلتر سگمنت در صفحات خود افزونه، و محدودیت در توضیحات محصول صادقانه اعلام می‌شود (الزام «تطابق توضیحات با محصول» — منبع: `rtl-theme-rules.md` بند ۹۱).
- هر تصمیمی که گرفته شود باید در `CHANGELOG.md` و توضیحات محصول دقیقاً همان باشد.

## ۱۲. استانداردهای امنیتی هر لایه

| لایه | الزامات اجباری |
|---|---|
| **دسترسی/مجوز** | ثبت منو با `manage_woocommerce`؛ در **هر** کنش و هندلر: `current_user_can('manage_woocommerce')` پیش از هر کار؛ صفحات با callback گارد capability |
| **CSRF** | هر فرم/دکمه اکشن: `wp_nonce_field('moshtaryar_action', '_mynonce')` و سمت سرور `check_admin_referer('moshtaryar_action')`؛ نام اکشن nonce برای بازمحاسبه و CSV مجزا |
| **ورودی** | `absint` برای شناسه‌ها/صفحه؛ `sanitize_text_field` برای جست‌وجو؛ `sanitize_email` برای ایمیل؛ `key($input)` آرایه سفید مرتب‌سازی؛ هیچ ورودی خام به SQL |
| **SQL** | همه کوئری‌ها `$wpdb->prepare` با `%d/%s/%f`؛ `LIKE` با `$wpdb->esc_like()`؛ `ORDER BY` فقط از whitelist ثابت در کد؛ `LIMIT` با `%d` |
| **خروجی** | متن‌ها `esc_html`/`esc_html__`؛ ویژگی‌ها `esc_attr`؛ لینک‌ها `esc_url`؛ داده به JS فقط با `wp_localize_script`/`wp_json_encode`؛ تولتیپ نقشه با `textContent` |
| **فایل/بارگذاری مستقیم** | گارد `defined('ABSPATH') || exit` در ابتدای هر فایل PHP؛ فایل‌های JS/CSS قابل دسترسی مستقیم و بی‌خطر طراحی می‌شوند |
| **شبکه** | صفر تابع `wp_remote_*`، صفر CDN، صفر iframe بیرونی — کل دارایی‌ها محلی |
| **CSV/فایل** | خروجی CSV فقط با هدرهای Content-Type/Disposition صریح و پس از nonce+capability؛ هیچ مسیر فایل از ورودی کاربر |
| **uninstall** | گارد `WP_UNINSTALL_PLUGIN` (بخش ۱۴) |

## ۱۳. خروجی CSV

- مسیر: `admin_post_moshtaryar_export_csv` → `MoshtariYar_CSV_Export::stream( $filters )` با همان جست‌وجو/فیلتر سگمنتِ صفحه (کل نتایج، نه فقط صفحه فعلی).
- هدرها: `Content-Type: text/csv; charset=utf-8` و `Content-Disposition: attachment; filename="moshtaryar-customers-YYYY-MM-DD.csv"`؛ ابتدای جریان **BOM UTF-8** (`\xEF\xBB\xBF`) برای نمایش درست فارسی در Excel.
- ستون‌ها (فیلترپذیر `moshtaryar_csv_columns`): نام، ایمیل، سگمنت (برچسب فارسی)، آخرین خرید (جلالی، فرمت متنی)، تعداد سفارش، مجموع خرید، میانگین سبد.
- اعداد در CSV **لاتین** می‌مانند (سازگاری با فرمول‌های Excel)؛ تنها متن‌های فارسی و تاریخ جلالی‌اند. سطرها با `fputcsv` و نقل‌قول استاندارد.

## ۱۴. uninstall.php و پاک‌سازی

```php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
global $wpdb;
// ۱) DROP TABLE IF EXISTS {$wpdb->prefix}moshtaryar_orders; و moshtaryar_customers
// ۲) delete_option برای پنج کلید بخش ۶-۳
// ۳) wp_clear_scheduled_hook( 'moshtaryar_daily_recalc' );
// بدون پشتیبانی multisite: فقط سایت جاری پاک می‌شود.
```

هیچ اثری از افزونه (جدول، آپشن، رویداد cron، transient) نباید باقی بماند — الزام «سبز بودن» محصول.

## ۱۵. قرارداد بین سازندگان (API داخلی)

این امضاها **ثابت** هستند؛ سازنده رابط/نقشه فقط این‌ها را صدا می‌زند و سازنده هسته دقیقاً همین‌ها را می‌سازد:

```php
// MoshtariYar_Queries
get_kpis(): array{total:int, active_30d:int, avg_value:float, repeat_rate:float}
get_segment_distribution(): array<int, array{segment:string, count:int}>  // با ترتیب ثابت بخش ۷-۴
get_monthly_sales( int $months = 12 ): array<int, array{label:string, total:float}> // label جلالی
get_customers( array $args ): array{rows:array, total:int, pages:int}
  // $args: search:string, segment:string, orderby:string, order:'asc'|'desc', paged:int

// MoshtariYar_State_Stats
get_provinces( string $metric ): array<int, array{code:string, name_fa:string, value:float}>
  // $metric: 'orders' یا 'revenue' — شامل سطر unknown

// MoshtariYar_RFM_Engine
full_recalc(): array{customers:int, orders:int, seconds:float}
recalc_customer( int $order_id ): void  // از روی سفارش، مشتری همان سفارش را بازمحاسبه می‌کند

// helpers.php (توابع سراسری)
moshtaryar_fa_num( $number ): string
moshtaryar_price( float $amount ): string        // fa_num + نماد ارز از ووکامرس
moshtaryar_fa_date( string|int $datetime, string $format = 'Y/m/d' ): string // جلالی
```

ثابت‌ها: `MY_VER`, `MY_FILE`, `MY_DIR`, `MY_URL` در `moshtaryar.php`. کلاس‌های admin فقط از `includes/` صدا می‌زنند؛ هیچ کوئری مستقیم `$wpdb` در فایل‌های `admin/` مجاز نیست (جداسازی کامل منطق و نمایش).

## ۱۶. تقسیم کار — سه سازنده، بدون هم‌پوشانی فایل

هر فایل دقیقاً به یک سازنده تعلق دارد. ترتیب پیشنهادی ساخت: **هسته RFM اول** (قرارداد بخش ۱۵ ثابت می‌شود)، سپس رابط مدیریت و نقشه ایران **موازی**، در پایان تولید `.pot` و تلفیق/QA.

### ۱۶-۱. سازنده هسته RFM — ۱۵ فایل

مالک همه منطق داده، بوت‌استرپ و بسته‌بندی کد:

| # | فایل |
|---|---|
| ۱ | `moshtaryar.php` |
| ۲ | `uninstall.php` |
| ۳ | `readme.txt` |
| ۴ | `CHANGELOG.md` |
| ۵ | `includes/class-moshtaryar-plugin.php` |
| ۶ | `includes/class-moshtaryar-install.php` |
| ۷ | `includes/class-moshtaryar-rfm-engine.php` |
| ۸ | `includes/class-moshtaryar-segments.php` |
| ۹ | `includes/class-moshtaryar-queries.php` |
| ۱۰ | `includes/class-moshtaryar-state-stats.php` |
| ۱۱ | `includes/class-moshtaryar-geo.php` |
| ۱۲ | `includes/class-moshtaryar-hooks.php` |
| ۱۳ | `includes/class-moshtaryar-i18n.php` |
| ۱۴ | `includes/helpers.php` |
| ۱۵ | `languages/moshtaryar.pot` (گام پایانی — بعد از تکمیل دو سازنده دیگر) |

همچنین مسئول تعیین تکلیف بخش ۱۱ (ستون لیست مشتریان ووکامرس) چون هوک آن در فایل اوست.

### ۱۶-۲. سازنده رابط مدیریت — ۱۰ فایل

مالک منو، دو صفحه داشبورد و جدول مشتریان، CSV و تمام استایل/رفتار عمومی:

| # | فایل |
|---|---|
| ۱ | `admin/class-moshtaryar-admin.php` |
| ۲ | `admin/class-moshtaryar-admin-dashboard.php` |
| ۳ | `admin/class-moshtaryar-admin-customers.php` |
| ۴ | `admin/class-moshtaryar-admin-actions.php` |
| ۵ | `admin/class-moshtaryar-csv-export.php` |
| ۶ | `admin/css/moshtaryar-admin.css` |
| ۷ | `admin/js/moshtaryar-admin.js` |
| ۸ | `admin/js/moshtaryar-charts.js` |
| ۹ | `admin/fonts/Vazirmatn-Regular.woff2` |
| ۱۰ | `admin/fonts/Vazirmatn-Bold.woff2` |

این سازنده فقط از API بخش ۱۵ استفاده می‌کند؛ کلاس صفحه نقشه را صدا نمی‌زند و فایل نقشه را دست نمی‌زند.

### ۱۶-۳. سازنده نقشه ایران — ۳ فایل

مالک صفحه نقشه و دو فایل جاوااسکریپت نقشه (شامل تولید آفلاین pathهای ۳۱ استان — الزام بخش ۱۰-۳):

| # | فایل |
|---|---|
| ۱ | `admin/class-moshtaryar-admin-map.php` |
| ۲ | `admin/js/moshtaryar-iran-map.js` |
| ۳ | `admin/js/moshtaryar-iran-paths.js` |

داده نقشه فقط از قرارداد `get_provinces()` (بخش ۱۰-۳) می‌آید؛ این سازنده هیچ جدولی را مستقیم کوئری نمی‌کند.

### ۱۶-۴. جمع

۱۵ + ۱۰ + ۳ = **۲۸ فایل** — هیچ فایلی به دو سازنده تعلق ندارد و هیچ فایلی بدون مالک نیست.

## ۱۷. چک‌لیست QA پیش از ارسال (برگرفته از `rtl-theme-rules.md`)

- [ ] نصب و تست کامل در محیط استاندارد وردپرس + ووکامرس پیش‌فرض، قبل از ارسال و قبل از هر بروزرسانی (ماده ۹).
- [ ] آزمون روی PHP 7.4 و PHP 8.2/8.3 (بدون deprecated/fatal) — بازه اعلامی بخش ۲ را واقعی می‌کند.
- [ ] آزمون HPOS فعال و غیرفعال + بازمحاسبه کامل/تک‌مشتری/دستی/شبانه.
- [ ] اعداد همه‌جا فارسی، تاریخ‌ها همه‌جا جلالی، چیدمان همه‌جا RTL؛ ریسپانسیو موبایل/تبلت.
- [ ] اسکن امنیتی: کوئری‌های prepare، nonceها، capabilityها، گارد ABSPATH — هیچ ورودی خام.
- [ ] `uninstall.php` کامل پاک می‌کند (حذف افزونه → صفر جدول/آپشن/کرون).
- [ ] اسکن پکیج در VirusTotal قبل از ارسال (الزام استاندارد راست‌چین).
- [ ] فایل zip بدون پوشه ریشه اضافه (پوشه `moshtaryar` مستقیم داخل zip) + درج `help.pdf` فارسی و راهنمای بروزرسانی در بسته.
- [ ] هیچ اطلاعات تماس شخصی/لینک بیرونی در کد، راهنما و دمو (ماده ۱۸).
- [ ] چنج‌لاگ فارسی از ۱.۰.۰ در `CHANGELOG.md` + درج در توضیحات محصول؛ اعلام دقیق پیش‌نیازها (PHP، حافظه، ووکامرس) در توضیحات.
- [ ] کاور ۲۱۰۰×۱۰۴۰ و آیکن ۳۲۰×۳۲۰ طبق `cover-icon` راست‌چین (خارج از دامنه کد).

## ۱۸. موارد باز / نیازمند تصمیم (خلاصه صادقانه)

| # | موضوع | وضعیت |
|---|---|---|
| ۱ | مسیر دقیق ستون «سگمنت» در لیست مشتریان ووکامرس (بخش ۱۱) | نیازمند تأیید با منبع WooCommerce 7+ توسط سازنده هسته |
| ۲ | pathهای SVG ۳۱ استان (بخش ۱۰-۳) | ساخت آفلاین توسط سازنده نقشه از GeoJSON لایسنس‌آزاد؛ مختصات در این سند نیست |
| ۳ | آستانه‌های پولی M و رنگ‌ها | پیشنهادی؛ تأیید نهایی محصول |
| ۴ | فونت نهایی (Vazirmatn پیشنهادی) | نیازمند تأیید ناشر در صورت انتخاب فونت تجاری |
