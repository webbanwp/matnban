=== Matnban ===
Contributors: webban
Tags: persian, farsi, typography, half-space, readability
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Persian writing & typography assistant — one-click spelling and half-space (ZWNJ) correction, Arabic character cleanup and readability analysis.

== Description ==

Matnban is a Persian writing assistant for WordPress — built for Persian the way SEO plugins are built for keywords.

Persian typography has rules that generic editors silently break: missing half-spaces (ZWNJ) in prefixed verbs, Arabic look-alike characters (Arabic yeh and kaf) that break search and spell-checkers, Arabic-Indic digits, wrong quotation marks, decorative kashida, and invisible direction marks pasted from messaging apps. Matnban fixes all of this — with one click, or automatically.

= Features =

* One-click correction in the post editor — 16+ rules: ZWNJ for verb prefixes (mi, nami, bi) and suffixes (ha, tar, tarin), the ezafe ye, Arabic character and digit conversion to Persian, Persian quotes and punctuation, kashida removal, and cleanup of invisible direction marks (RLM/LRM) that break copied text
* Optional automatic correction on save, or on display (display mode never modifies the database)
* Persian readability panel in the post editor: sentence-length analysis and keyword density with Persian stemming — the focus keyphrase is read automatically from Yoast SEO or Rank Math when present
* Site-wide writing health report: scan all posts, list the ones with issues, export to CSV
* Guidance for Persian-friendly image filenames and alt text

All processing happens locally on your site with PHP. This plugin sends no requests to any external service.

A premium version with bulk post fixing, an AI assistant (using your own API key) and a smart English slug generator is sold separately at https://webbanwp.ir/ — the code for those features is not included in this plugin.

== Installation ==

1. Install from Plugins → Add New by searching for "Matnban", or upload the zip file.
2. Open Dashboard → Matnban and review the rules (the defaults follow standard Persian orthography).
3. In the post editor, open the Matnban panel and click the fix button.
4. For the site-wide report: Matnban → Site Report → Scan posts.

== Frequently Asked Questions ==

= Is my text stored or sent anywhere? =
No. All corrections run locally on your site with PHP, and this plugin makes no external requests.

= Does it conflict with Yoast SEO or Rank Math? =
No — it complements them. Matnban reads the focus keyphrase from either plugin and runs its Persian analysis on it.

= Will it break the half-spaces already in my content? =
No. Standard ZWNJ half-spaces are preserved; only incorrect forms (a prefix separated by a regular space) are converted to the correct half-space form.

= What is the difference between this and the premium version? =
This repository version is free and fully functional on its own: manual fixing, automatic fixing, readability analysis and the site report are all included. The premium version (a separate product sold at webbanwp.ir, whose code is not included here) adds bulk fixing, an AI assistant and smart English slugs.

== Screenshots ==

1. The Matnban panel in the post editor — one-click Persian writing correction and readability analysis
2. The site-wide writing health report
3. Writing rules with before/after examples

== Changelog ==

= 1.0.0 =
* Initial release on WordPress.org
* Conversion of invisible direction marks (RLM/LRM) misused in place of ZWNJ
