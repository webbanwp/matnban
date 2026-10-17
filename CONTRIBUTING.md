# Contributing to Matnban

Thank you for considering a contribution! Matnban improves Persian writing on WordPress — and the easiest way to help is adding a word to the Persian→English dictionary.

## 🌟 The one-minute contribution: add a dictionary word

The dictionary lives in [`includes/matnban-dictionary.php`](includes/matnban-dictionary.php) as a plain PHP array, and powers the English filenames and alt text generated for uploaded images (and the slug engine in Matnban Pro). Adding one line adds one word:

```php
'پادکست' => 'podcast',
```

How to submit:

1. Fork the repository.
2. Open `includes/matnban-dictionary.php` and press the **✏️ pencil** (Edit) button.
3. Add your word on its own line, in the right alphabetical group, using the correct Persian spelling (including نیم‌فاصله where the word needs it, e.g. `می‌فروشم`).
4. Commit with a message like: `dictionary: add podcast`
5. Open a Pull Request.

No PHP knowledge is needed. Every contributor is credited in the commit history of this repository.

## 💡 Suggesting a word, or a writing rule — without coding

Open a [Word / rule suggestion](https://github.com/webbanwp/matnban/issues/new/choose) issue and we will add it for you.

## 🐛 Reporting a bug

Please use the Bug report issue template and include:

- Plugin version, WordPress version, PHP version
- Steps to reproduce
- What you expected and what happened instead

## 🔧 Code contributions

- The plugin requires **WordPress 5.8+** and **PHP 8.1+**.
- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/). In this codebase that means in practice:
  - Escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`)
  - Use `$wpdb->prepare()` for every database query
  - Start every PHP file with the `ABSPATH` guard
- Keep one logical change per Pull Request.
- Run `php -l` on the files you changed. CI also runs a syntax check and a guard check on every Pull Request — both must pass.

## 📄 License

By contributing, you agree that your contribution is licensed under **GPLv2 or later**, the same as the plugin.

---

## فارسی

از همراهی شما سپاسگزاریم! متن‌بان نگارش فارسی را در وردپرس بهتر می‌کند — و آسان‌ترین راه کمک، افزودن یک واژه به واژه‌نامهٔ فارسی←انگلیسی است.

### مشارکت یک‌دقیقه‌ای: افزودن واژه

واژه‌نامه در فایل `includes/matnban-dictionary.php` یک آرایهٔ سادهٔ PHP است و نام فایل و متن جایگزین (alt) انگلیسیِ تصاویرِ بارگذاری‌شده را می‌سازد و موتور نامک نسخهٔ کامل را هم تغذیه می‌کند. افزودن یک خط = افزودن یک واژه. مراحل: مخزن را Fork کنید → فایل را باز کنید و دکمهٔ مداد (✏️) را بزنید → واژهٔ جدید را در گروه الفبایی درست و با املای صحیح (شامل نیم‌فاصله در جای درست) اضافه کنید → با پیامی مانند `dictionary: add podcast` کامیت کنید → Pull Request بفرستید. هیچ دانش PHP لازم نیست و نام شما در تاریخ مخزن ثبت می‌شود.

### پیشنهاد واژه یا قاعدهٔ نگارشی — بدون کدنویسی

از قالب «پیشنهاد واژه/قاعده» در [Issues](https://github.com/webbanwp/matnban/issues/new/choose) استفاده کنید؛ ما اضافه می‌کنیم.

### گزارش باگ

از قالب گزارش باگ استفاده کنید و نسخهٔ افزونه، نسخهٔ وردپرس و PHP، مراحل بازتولید، و نتیجهٔ موردانتظار در برابر نتیجهٔ واقعی را بنویسید.

### مشارکت کدی

- وردپرس ۵٫۸+ و PHP 8.1+ لازم است.
- استانداردهای کدنویسی وردپرس رعایت شود: escape در خروجی، `prepare` برای همهٔ کوئری‌ها، و گارد `ABSPATH` در ابتدای همهٔ فایل‌ها.
- هر Pull Request یک تغییر منطقی؛ `php -l` روی فایل‌های تغییر یافته اجرا کنید. تست CI روی هر Pull Request اجرا می‌شود و باید سبز شود.

### مجوز

با مشارکت، اثر شما نیز تحت GPLv2+ منتشر می‌شود.
