=== Matnban ===
Contributors: webban
Tags: persian, farsi, typography, half-space, readability
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Persian writing assistant — ZWNJ (half-space) fixes, Arabic character cleanup, readability and a site-wide writing report. دستیار نگارش فارسی وردپرس.

== Description ==

Matnban is a Persian writing assistant for WordPress — built for Persian the way SEO plugins are built for keywords.

Note for international users: the plugin's admin interface is in Persian (Farsi).

Persian typography has rules that generic editors silently break: missing half-spaces (ZWNJ) in prefixed verbs, Arabic look-alike characters (Arabic yeh and kaf) that break search and spell-checkers, Arabic-Indic digits, wrong quotation marks, decorative kashida, and invisible direction marks pasted from messaging apps. Matnban fixes all of this — with one click, or automatically.

= Features =

* One-click correction in the post editor — 17 rules: ZWNJ for verb prefixes (mi, nami, bi) and suffixes (ha, tar, tarin), the ezafe ye, Arabic character and digit conversion to Persian, Persian quotes and punctuation, kashida removal, and cleanup of invisible direction marks (RLM/LRM) that break copied text
* Optional automatic correction on save, or on display (display mode never modifies the database)
* Persian readability panel in the post editor: sentence-length analysis and keyword density with Persian stemming — the focus keyphrase is read automatically from Yoast SEO or Rank Math when present
* Site-wide writing health report: scan all posts, list the ones with issues, export to CSV
* Guidance for Persian-friendly image filenames and alt text
* Every rule can be switched off individually; the plugin never touches HTML, code, shortcodes, links or English text

All processing happens locally on your site with PHP. This plugin sends no requests to any external service.

A premium version with bulk post fixing, a 30-day undo, an AI assistant (using your own API key) and a smart English slug generator is sold separately at https://webbanwp.ir/ — the code for those features is not included in this plugin.

= فارسی — معرفی کوتاه =

**متن‌بان** دستیار نگارش فارسی برای وردپرس است؛ همان‌طور که افزونه‌های سئو کارِ کلمهٔ کلیدی را انجام می‌دهند، متن‌بان کارِ نگارش فارسی را انجام می‌دهد.

نگارش فارسی قاعده‌هایی دارد که ویرایشگرهای عمومی بی‌صدا خرابش می‌کنند: نیم‌فاصلهٔ جاافتاده در فعل‌های پیشوندی («می رود»)، حروف عربیِ هم‌شکل با فارسی («ي»، «ك») که جست‌وجو و غلط‌یاب را از کار می‌اندازد، ارقام عربی («٤٥»)، گیومهٔ اشتباه، کشیدهٔ تزئینی، و نشانه‌های جهتِ نامرئی که از پیام‌رسان‌ها کپی می‌شوند. متن‌بان همهٔ این‌ها را اصلاح می‌کند — با یک کلیک، یا خودکار.

= امکانات =

* اصلاح یک‌کلیکی در ویرایشگر مطلب — ۱۷ قاعده: نیم‌فاصلهٔ «می/نمی»، «بی»، «ها»، «تر/ترین»، شناسهٔ فعل و یای میانجی؛ تبدیل حروف و ارقام عربی به فارسی؛ علائم نگارشی و گیومهٔ فارسی؛ حذف کشیده، فاصله‌های تکراری و نویسه‌های نامرئی
* اصلاح خودکار هنگام ذخیره (اختیاری) یا هنگام نمایش؛ حالت نمایش هیچ تغییری در دیتابیس نمی‌دهد
* پنل خوانایی فارسی در ویرایشگر: طول جمله، تنوع آغاز جمله‌ها و چگالی کلمهٔ کلیدی با ریشه‌یابی فارسی. کلمهٔ کلیدی به‌طور خودکار از Yoast SEO یا Rank Math خوانده می‌شود
* گزارش سلامت نگارش کل سایت: اسکن همهٔ مطالب، فهرست مطالب نیازمند توجه، و خروجی CSV
* راهنمای نام فایل و متن جانشین (alt) تصاویر برای فارسی
* هر قاعده جداگانه خاموش/روشن می‌شود؛ افزونه هرگز به HTML، کد، شورت‌کد، لینک و متن انگلیسی دست نمی‌زند

همهٔ پردازش‌ها محلی و با PHP روی سایت خودتان انجام می‌شود؛ این افزونه هیچ درخواستی به بیرون نمی‌فرستد.

نسخهٔ حرفه‌ای (اصلاح گروهی کل آرشیو، بازگردانی ۳۰ روزه، دستیار هوش مصنوعی و نامک انگلیسی هوشمند) محصولی جداگانه در https://webbanwp.ir/ است و کد آن در این افزونه نیست.

== Installation ==

1. Install from Plugins → Add New by searching for "Matnban", or upload the zip file.
2. Open Dashboard → Matnban and review the rules (the defaults follow standard Persian orthography).
3. In the post editor, open the Matnban panel and click the fix button.
4. For the site-wide report: Matnban → Site Report → Scan posts.

= نصب به فارسی =

۱. از پیشخوان ← افزونه‌ها ← افزودن، «Matnban» را جست‌وجو و نصب کنید؛ یا فایل zip را بارگذاری کنید.
۲. به پیشخوان ← متن‌بان بروید و قاعده‌ها را مرور کنید (پیش‌فرض‌ها بر پایهٔ رسم‌الخط استاندارد فارسی است).
۳. در ویرایشگر مطلب، پنل متن‌بان را باز کنید و دکمهٔ اصلاح را بزنید.
۴. برای گزارش کل سایت: متن‌بان ← گزارش سایت ← اسکن مطالب.

== Frequently Asked Questions ==

= Is my text stored or sent anywhere? =
No. All corrections run locally on your site with PHP, and this plugin makes no external requests.

= Does it conflict with Yoast SEO or Rank Math? =
No — it complements them. Matnban reads the focus keyphrase from either plugin and runs its Persian analysis on it.

= Will it break the half-spaces already in my content? =
No. Standard ZWNJ half-spaces are preserved; only incorrect forms (a prefix separated by a regular space) are converted to the correct half-space form.

= What is the difference between this and the premium version? =
This repository version is free and fully functional on its own: manual fixing, automatic fixing, readability analysis and the site report are all included. The premium version (a separate product sold at webbanwp.ir, whose code is not included here) adds bulk fixing, a 30-day undo, an AI assistant and smart English slugs.

= متن من جایی ذخیره یا ارسال می‌شود؟ =
نه. همهٔ اصلاح‌ها با PHP روی سایت خودتان انجام می‌شود و افزونه هیچ درخواست بیرونی ندارد.

= با Yoast SEO یا Rank Math تداخل دارد؟ =
نه؛ مکمل آن‌هاست. کلمهٔ کلیدی را از هر کدام‌که نصب باشد می‌خواند و تحلیل فارسی را روی همان انجام می‌دهد.

= نیم‌فاصله‌های درست متن من خراب می‌شود؟ =
نه. نیم‌فاصله‌های استاندارد حفظ می‌شوند؛ فقط شکل‌های نادرست (پیشوندی که با فاصلهٔ معمولی جدا شده) به نیم‌فاصلهٔ درست تبدیل می‌شود.

= تفاوتش با نسخهٔ حرفه‌ای چیست؟ =
این نسخه رایگان و کامل است: اصلاح دستی، اصلاح خودکار، تحلیل خوانایی و گزارش سایت همه در آن هست. نسخهٔ حرفه‌ای (محصولی جداگانه در webbanwp.ir که کدش اینجا نیست) اصلاح گروهی، بازگردانی ۳۰ روزه، دستیار هوش مصنوعی و نامک انگلیسی هوشمند را اضافه می‌کند.

== Screenshots ==

1. The Matnban panel in the post editor — one-click Persian writing correction and readability analysis (پنل متن‌بان در ویرایشگر: اصلاح یک‌کلیکی و تحلیل خوانایی)
2. The site-wide writing health report (گزارش سلامت نگارش کل سایت)
3. Writing rules with before/after examples (قاعده‌های نگارشی با نمونهٔ پیش و پس از اصلاح)

== Changelog ==

= 1.0.2 =
* Fixed a URL-protection bug: with the "English digits to Persian" rule enabled, URLs in the text could be damaged or lost. Protected URLs are now stored with digit-free placeholder keys.
* Fixed the HTML walker: a `>` character inside a quoted HTML attribute (e.g. `title="a > b"`) no longer breaks tag detection — attribute values are never touched.
* The readability analysis now treats the Persian semicolon (؛) as a sentence boundary.
* The site report now also scans the post excerpt — issues in excerpts are counted, matching what automatic fixing corrects.
* Turning the scheduled report off now fully cancels a scan in progress (the continuation event and its progress data are cleared).
* Persian media filenames are now also transliterated for side-loaded files (uploads made by plugins and the REST API), not only direct uploads.

= 1.0.2 (فارسی) =
* رفع اشکال محافظت از نشانی‌ها: با قاعدهٔ «تبدیل ارقام انگلیسی به فارسی»، ممکن بود نشانی‌های داخل متن خراب یا حذف شوند. کلید محافظت از URLها دیگر رقم ندارد.
* رفع اشکال پیمایشگر HTML: نویسهٔ > داخل ویژگی کوتیشن‌دار (مانند title="a > b") دیگر باعث شکستن تشخیص تگ نمی‌شود و مقدار ویژگی‌ها هرگز تغییر نمی‌کند.
* تحلیل خوانایی حالا نقطه‌ویرگول فارسی (؛) را هم پایان جمله می‌داند.
* گزارش سایت حالا چکیدهٔ مطلب را هم بررسی می‌کند — خطاهای چکیده هم شمرده می‌شوند، هماهنگ با چیزی که اصلاح خودکار تغییر می‌دهد.
* خاموش کردن گزارش زمان‌بندی‌شده حالا اسکنِ در جریان را کامل لغو می‌کند (رویداد «ادامه» و داده‌های پیشرفت آن پاک می‌شود).
* نام فایل‌های فارسی رسانه در آپلودهای جانبی (side-load از افزونه‌ها و REST API) هم به انگلیسی خوانا تبدیل می‌شود، نه فقط آپلود مستقیم.

= 1.0.1 =
* Fixed three false positives that could corrupt correct text:
  * "خشک و تر" (and similar) is no longer joined into "خشک و‌تر" — the suffix rule now requires a word of at least two letters before "تر/ترین".
  * "گفت که ای کاش" is no longer changed to "گفت که‌ای کاش" — "که/چه/نه" before "ای/ام/اند" are treated as particles, not verb endings.
  * "این می را ببین" is no longer changed to "این می‌را ببین" — a bare "می" followed by "را" is a noun, not the verb prefix.
* Added regression tests for all three cases plus two positive cases that must keep working.
* Readme is now bilingual (English + Persian).

= 1.0.1 (فارسی) =
* رفع سه خطای تشخیص که می‌توانست متن درست را تغییر دهد: «خشک و تر»، «گفت که ای کاش» و «این می را ببین» دیگر اصلاح نمی‌شوند.
* افزودن آزمون رگرسیون برای همان سه مورد، به‌همراه دو نمونهٔ درست که باید همچنان اصلاح شوند.
* راهنمای افزونه (readme) دوزبانه شد: انگلیسی + فارسی.

= 1.0.0 =
* Initial release on WordPress.org
* Conversion of invisible direction marks (RLM/LRM) misused in place of ZWNJ

== Upgrade Notice ==

= 1.0.2 =
Important fix: URLs could be damaged when the "English digits to Persian" rule was enabled — update recommended. — رفع مهم: با قاعدهٔ «تبدیل ارقام انگلیسی» ممکن بود نشانی‌ها خراب شوند؛ بروزرسانی توصیه می‌شود.

= 1.0.1 =
Fixes three false positives that could alter correct text. Recommended update. — رفع سه خطای تشخیص که ممکن بود متن درست را تغییر دهد؛ به‌روزرسانی توصیه می‌شود.
