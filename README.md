# Matnban — Persian Writing & Typography Assistant for WordPress

**متن‌بان** — دستیار نگارش و سئوی محتوای فارسی وردپرس

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-21759b?logo=wordpress)](https://wordpress.org/plugins/matnban/)
[![Version](https://img.shields.io/badge/version-1.0.0-8ed557)](https://wordpress.org/plugins/matnban/)
[![License](https://img.shields.io/badge/license-GPL_v2-blue)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

Matnban (متن‌بان, lit. "text-keeper") is a WordPress plugin built specifically for **Persian (Farsi)** content. International SEO tools like Yoast and Rank Math are designed for English; their readability analysis is blind to the writing errors that matter most in Persian — half-spaces (ZWNJ), Arabic look-alike characters, Arabic-Indic digits, and punctuation that breaks RTL rendering. Matnban fills exactly that gap and works **alongside** your SEO plugin, not instead of it.

> ✅ Published in the official WordPress Plugin Directory and reviewed by the WordPress team: **[wordpress.org/plugins/matnban](https://wordpress.org/plugins/matnban/)**

## ✨ Features (Free version)

- **One-click Persian writing correction** — 16 configurable rules: ZWNJ half-space for می/نمی/ها/تر/ترین, Arabic ي/ك → Persian ی/ک, Arabic-Indic digits → Persian digits, Persian quotes «», punctuation spacing, kashida removal, invisible RLM/LRM marks copied from messengers, and more. HTML, Gutenberg blocks, code, shortcodes and links are never touched.
- **Persian readability analysis** in the editor (Gutenberg + Classic): 0–100 score with 9 readability checks and 6 keyphrase checks — each explained in Persian.
- **Persian-aware keyphrase counting** — reads the keyphrase from Yoast or Rank Math and counts word variants with Persian stemming: «کتاب», «کتاب‌ها», «کتابی» count as one.
- **Smart English slugs** for new posts: «آموزش سئو برای مبتدی‌ها» → `tutorial-seo-beginner` (3,000+ word dictionary + custom glossary). Existing slugs are never changed.
- **Persian image SEO** — transliterated, SEO-friendly image filenames + automatic Persian alt text.
- **Front-end correction mode** — display clean text without touching the database; cache-plugin friendly.
- **Word-like text alignment** — justify / right / center / left, applied safely.
- **100% offline & lock-free** — no external service calls, no license keys, no limits. You own it.

## 📦 Installation

From your WordPress dashboard: **Plugins ← Add New ← search "Matnban"** — or download from the [WordPress Plugin Directory](https://wordpress.org/plugins/matnban/) and upload the zip.

Requires WordPress 5.8+ and PHP 8.1+. Persian-language admin UI.

## 💎 Matnban Pro

The premium version (from the developer's site) adds bulk correction of your entire archive, an AI assistant (with your own API key), and the smart slug engine: **[webbanwp.ir/fileban/matnban](https://webbanwp.ir/fileban/matnban/)** — Persian support directly from the developer, lifetime 1.x updates, instant delivery.

## 🔗 Links

- **Plugin page (wp.org):** https://wordpress.org/plugins/matnban/
- **Live demo (try the correction engine):** https://webbanwp.ir/matnban/
- **Free online tool (no WordPress needed):** https://webbanwp.ir/wordfix/
- **Website (وب‌بان):** https://webbanwp.ir/
- **Telegram channel:** https://t.me/webbanwp

## 🤝 Contributing & Support

Bug reports and feature suggestions are welcome in [Issues](https://github.com/webbanwp/matnban/issues). Persian-language support is provided through the [website contact page](https://webbanwp.ir/contact-us/). Pull requests for the Persian rules dictionary are especially welcome.

## 📄 License

GPL v2 or later. See [LICENSE](LICENSE). The Persian rules engine and dictionaries are developed by [Webban](https://webbanwp.ir/).

---

# متن‌بان — دستیار نگارش و سئوی محتوای فارسی وردپرس

افزونه‌ای که دقیقاً برای **محتوای فارسی** ساخته شده است. Yoast و Rank Math برای انگلیسی طراحی شده‌اند؛ تحلیل خوانایی‌شان برای فارسی خاموش است و خطاهایی که بیشترین اثر را بر سئوی فارسی دارند — نیم‌فاصله، حروف عربی هم‌شکل، ارقام عربی، علائم نگارشی — را نمی‌بینند. متن‌بان همین خلأ را پر می‌کند و **کنار** افزونهٔ سئوی شما کار می‌کند؛ نه به‌جای آن.

> ✅ منتشرشده در مخزن رسمی وردپرس و بازبینی‌شده توسط تیم وردپرس: **[wordpress.org/plugins/matnban](https://wordpress.org/plugins/matnban/)**

## ✨ امکانات (نسخهٔ رایگان)

- **اصلاح نگارش فارسی با یک کلیک** — ۱۶ قاعدهٔ قابل تنظیم: نیم‌فاصلهٔ می/نمی/ها/تر/ترین، تبدیل ي و ك عربی، ارقام فارسی، گیومهٔ فارسی، فاصله‌گذاری علائم، حذف کشیده، نویسه‌های نامرئی RLM/LRM و… . به HTML، بلوک‌ها، کدها، شورت‌کدها و لینک‌ها هرگز دست نمی‌زند.
- **تحلیل خوانایی فارسی** داخل ویرایشگر (گوتنبرگ + کلاسیک): امتیاز ۰ تا ۱۰۰ با ۹ بررسی خوانایی و ۶ بررسی کلمهٔ کلیدی، همه با توضیح فارسی.
- **شمارش کلمهٔ کلیدی با ریشه‌یابی فارسی** — کلمهٔ کلیدی از Yoast/Rank Math خوانده می‌شود و «کتاب»، «کتاب‌ها»، «کتابی» یکی شمرده می‌شوند.
- **نامک انگلیسی هوشمند** برای مطالب جدید: «آموزش سئو برای مبتدی‌ها» ← `tutorial-seo-beginner` (واژه‌نامهٔ ۳۰۰۰+ واژه + واژه‌نامهٔ اختصاصی شما). نامک مطالب منتشرشده هرگز عوض نمی‌شود.
- **سئوی تصاویر فارسی** — نام فایل انگلیسیِ سئوشده + متن جایگزین خودکار.
- **اصلاح هنگام نمایش** — بدون تغییر دیتابیس؛ سازگار با افزونه‌های کش.
- **چینش متن مثل Word** — تراز دوطرفه، راست‌چین، وسط‌چین، چپ‌چین؛ فقط ظاهر، بدون دست‌زدن به ساختار.
- **صد در صد آفلاین و بدون قفل** — بدون اتصال به سرویس خارجی، بدون لایسنس، بدون محدودیت.

## 📦 نصب

از پیشخوان وردپرس: **افزونه‌ها ← افزودن ← جست‌وجوی «Matnban»** — یا دانلود از [مخزن رسمی](https://wordpress.org/plugins/matnban/).

وردپرس ۵.۸ به بالا · PHP 8.1 به بالا · رابط مدیریتی فارسی.

## 💎 نسخهٔ کامل

اصلاح گروهی کل آرشیو، دستیار هوش مصنوعی (با کلید API خودتان) و موتور کامل نامک هوشمند: **[webbanwp.ir/fileban/matnban](https://webbanwp.ir/fileban/matnban/)** — پشتیبانی فارسی مستقیم سازنده، به‌روزرسانی دائمی نسخه‌های ۱.x، تحویل آنی.

## 🔗 لینک‌ها

- صفحهٔ رسمی در مخزن: https://wordpress.org/plugins/matnban/
- دموی زندهٔ موتور اصلاح: https://webbanwp.ir/matnban/
- ابزار رایگان اصلاح نگارش (بدون وردپرس): https://webbanwp.ir/wordfix/
- وب‌سایت وب‌بان: https://webbanwp.ir/
- کانال تلگرام: https://t.me/webbanwp

## 🤝 مشارکت و پشتیبانی

گزارش باگ و پیشنهادها در [Issues](https://github.com/webbanwp/matnban/issues) خوش‌آمد است. پشتیبانی فارسی از طریق [صفحهٔ تماس سایت](https://webbanwp.ir/contact-us/). پول‌ریکوئست‌های واژه‌نامهٔ قواعد فارسی — از جمله واژه‌های منطقه‌ای و تخصصی — بیش از همه خوش‌آمد است.

## 📄 مجوز

GPL نسخهٔ ۲ یا بالاتر. موتور قواعد فارسی و واژه‌نامه‌ها توسعه‌یافتهٔ [وب‌بان](https://webbanwp.ir/) است.
