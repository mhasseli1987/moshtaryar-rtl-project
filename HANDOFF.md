# HANDOFF — افزونه «مشتریار» برای ادامه کار در کدکس

> این فایل برای انتقال کامل دانش پروژه به هر ایجنت/ابزار دیگر (مثلاً Codex) نوشته شده.
> تاریخ: ۲۰۲۶-۱۰-۲۹ | وضعیت: ساخت کامل، مانده فازهای بازبینی و مستندات و ارسال

## ۱. محصول چیست

**مشتریار (MoshtariYar)** — افزونه وردپرس/ووکامرس نسخه ۱.۰.۰: تحلیل‌گر مشتری با مدل RFM برای صاحبان فروشگاه ایران. قراره در مارکت **راست‌چین (rtl-theme.com)** به قیمت ~۵۹۰ هزار تومان فروخته شود.

- اسلاگ: `moshtaryar` | text domain: `moshtaryar` | پیشوند توابع: `moshtaryar_` | پیشوند کلاس‌ها: `Moshtaryar_`
- ثابت‌ها: `MY_VER`, `MY_FILE`, `MY_DIR`, `MY_URL`, `MY_DB_VERSION`
- فلسفه: ظاهر و متن‌ها نباید نشانه «ساخت هوش مصنوعی» داشته باشند؛ کد استاندارد وردپرس، متن فارسی طبیعی.

## ۲. قابلیت‌های طراحی‌شده (طبق docs/SPEC.md)

1. **موتور RFM**: امتیاز ۱-۵ برای Recency (روز از آخرین سفارش تکمیل‌شده)، Frequency، Monetary. ذخیره در جدول اختصاصی `moshtaryar_customers` + `moshtaryar_orders` (استان حمل هر سفارش). سازگار HPOS.
2. **۸ سگمنت فارسی**: قهرمان، وفادار، بالقوه وفادار، تازه‌وارد، نیازمند توجه، در خطر رفتن، خوابیده، از دست رفته.
3. **داشبورد**: ۴ کارت KPI (کل مشتریان، فعال ۳۰ روز، میانگین ارزش، نرخ تکرار)، دونات سگمنت‌ها، میله‌ای ۱۲ ماه.
4. **جدول مشتریان**: جست‌وجو/فیلتر سگمنت/مرتب‌سازی/صفحه‌بندی + خروجی CSV با BOM.
5. **نقشه حرارتی ایران**: SVG ۳۱ استان (`assets/js/iran-paths.js` از GeoJSON geoBoundaries با `tools/convert-iran-map.js` تولید شده)، سوییچ سفارش/درآمد، tooltip فارسی.
6. **بازمحاسبه**: cron شبانه + hook تغییر وضعیت سفارش + دکمه دستی با nonce.
7. یکپارچگی: ستون سگمنت در لیست مشتریان ووکامرس.

## ۳. وضعیت انجام کارها (ورک‌فلو ۱۱ فازه)

| فاز | وضعیت |
|---|---|
| ۱. پژوهش قوانین راست‌چین | ✅ `docs/rtl-theme-rules.md` — ۲۴ الزام؛ نام «مشتریار» آزاد است |
| ۲. معماری/SPEC | ✅ `docs/SPEC.md` — ۲۸ فایل برنامه‌ریزی |
| ۳. ساخت هسته RFM | ✅ انجام شد (ایجنت) |
| ۳. ساخت رابط مدیریت | ✅ انجام شد (ایجنت) |
| ۳. نقشه ایران | ✅ **خارج از ورک‌فلو دستی ساخته شد** (ایجنت‌ها سرریز حافظه خوردند): `class-map-report.php`، `map-tab.php`، `iran-map.js`، `iran-map.css`، `iran-paths.js` |
| ۴. یکپارچه‌سازی | ✅ ایجنت یکپارچه‌ساز |
| ۵. لینت PHP (C:/xampp/php/php.exe -l) و JS (node --check) | ✅ |
| ۶. بازبینی مستقل امنیت | ⏸ **متوقف شد** (توقف کاربر در ورک‌فلو + اتمام سهمیه پلن) |
| ۷. رفع یافته‌ها | ⏸ انجام نشده |
| ۸. بازبینی نهایی بی‌سابقه | ⏸ انجام نشده |
| ۹. مستندات/لیستینگ (`docs/RTL-PRODUCT-LISTING.md`, `readme.txt`, `docs/INSTALL-GUIDE.md`, `docs/CHANGELOG.md`, `docs/READY-REPORT.md`) | ⏸ انجام نشده |
| ۱۰. بازخوانی مستقل متن‌ها | ⏸ |
| ۱۱. تست روی وردپرس واقعی + اسکرین‌شات + دمو | ⏸ (بعد از تایید کاربر) |

**الزام مهم کارفرما:** انتشار فقط بعد از تایید کاربر. جواب‌ها کوتاه فارسی، ترتیب کلمات راست‌چین.

## ۴. قوانین راست‌چین (خلاصه — کامل در docs/rtl-theme-rules.md)

- امنیت کامل (nonce، capability `manage_woocommerce`، prepare، sanitize/escape، ABSPATH)
- **بدون فراخوانی شبکه/CDN/فونت گوگل** — همه assetها محلی (فونت Vazirmatn محلی طبق SPEC)
- RTL کامل، اعداد فارسی، تاریخ جلالی
- لایسنس دقیق و شفاف، بدون اطلاعات تماس شخصی در توضیحات
- لیستینگ: تصاویر، دمو، متن فارسی طبیعی، تگ‌ها

## ۵. نکات فنی مهم برای ادامه

- **کدهای استان‌ها**: استاندارد ووکامرس بدون پیشوند (THR، ALZ، EAST، WEST، …) — در `iran-paths.js` و `$provinces` در `class-map-report.php` هماهنگ شده. آبجکت JS نقشه: `window.MOSHTARYAR_IRAN_PATHS`؛ داده آماری: `window.moshtaryarMapData` (wp_localize_script، وابستگی enqueue: iran-paths → iran-map).
- فایل داده نقشه را با `node tools/convert-iran-map.js` بازتولید کن (منبع GeoJSON: geoBoundaries IRN ADM1 — دانلود از media.githubusercontent.com چون LFS است؛ فایل JSON بزرگ عمداً در گیت نیست).
- PHP روی این سیستم: `C:/xampp/php/php.exe` (روی PATH نیست).
- ابزار تست لینت: `php -l` همه فایل‌ها + `node --check` روی assets/js.
- یکپارچه‌ساز گزارش داد که کد ادمین از `MY_URL . 'assets/css|js/…'` استفاده می‌کند؛ ساختار منو: صفحه والد `moshtaryar` + زیرصفحه `moshtaryar-map`.
- ۴ مورد «نیازمند تصمیم» در SPEC علامت خورده (بخش پایانی SPEC) — مهم‌ترین: مسیر دقیق ستون سگمنت در WCAdmin.

## ۶. کارهای بعدی (به ترتیب)

1. فازهای ۶ تا ۱۰ ورک‌فلو (بازبینی امنیت → رفع → بازبینی نهایی → مستندات/لیستینگ).
2. تست واقعی روی وردپرس لوکال (استیجینگ XAMPP، پورت ۸۰۹۰ در پروژه ENZO موجود است) + اسکرین‌شات‌ها.
3. تکمیل لیستینگ و ارسال به راست‌چین — **فقط بعد از تایید کاربر**.

## ۷. ساختار فایل‌ها

```
moshtaryar/
├── moshtaryar.php                      ← سربرگ، defineها، لودر، declare HPOS
├── uninstall.php
├── readme.txt                          ← (باید ساخته شود — فاز ۹)
├── includes/
│   ├── class-rfm-engine.php  class-moshtaryar.php  class-activator.php
│   ├── class-cron.php  class-export.php  functions.php
│   └── admin/
│       ├── class-admin-pages.php  class-data-provider.php  class-map-report.php
│       └── views/  (dashboard.php، customers-table.php، map-tab.php)
├── assets/
│   ├── css/ (moshtaryar-admin.css، iran-map.css)
│   └── js/  (moshtaryar-admin.js، iran-map.js، iran-paths.js)
├── docs/
│   ├── SPEC.md  rtl-theme-rules.md
│   └── (RTL-PRODUCT-LISTING.md، INSTALL-GUIDE.md، CHANGELOG.md، READY-REPORT.md → فاز ۹)
└── tools/  (convert-iran-map.js + geoBoundaries JSON محلی — در گیت نیست)
```
