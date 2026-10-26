# Changelog

All notable changes to Matnban are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/) and the project follows [Semantic Versioning](https://semver.org/).

## [1.0.2] — 2026-10-26

### Fixed

- **URL protection:** with the "English digits to Persian" rule enabled, URLs in the text could be damaged or lost. Protected URLs are now stored with digit-free placeholder keys and survive even hundreds of links in one post.
- **HTML walker:** a `>` inside a quoted HTML attribute (e.g. `title="a > b"`) no longer breaks tag detection — attribute values are never rewritten.
- Turning the scheduled report off now fully cancels a scan already in progress (the continuation event and its progress data are cleared).

### Changed

- The readability analysis treats the Persian semicolon (؛) as a sentence boundary.
- The site report now also scans the post excerpt, so issues in excerpts are counted — matching what automatic fixing corrects.
- Persian media filenames are now transliterated for side-loaded files too (uploads made by other plugins and the REST API), not only direct uploads.

## [1.0.1] — 2026-10-20

### Fixed

- Three false positives that could corrupt correct text:
  - «خشک و تر» (and similar) is no longer joined into «خشک و‌تر» — the suffix rule now requires a word of at least two letters before «تر/ترین».
  - «گفت که ای کاش» is no longer changed to «گفت که‌ای کاش» — «که/چه/نه» before «ای/ام/اند» are treated as particles, not verb endings.
  - «این می را ببین» is no longer changed to «این می‌را ببین» — a bare «می» followed by «را» is a noun, not the verb prefix.

### Added

- Regression tests for all three cases plus two positive cases that must keep working.
- The readme is now bilingual (English + Persian).

## [1.0.0] — 2026-10-15

First official release, accepted into the WordPress.org plugin directory.

### Added

- One-click Persian writing correction in the post editor, with 17 toggleable rules:
  - Half-space (ZWNJ) for verb prefixes «می/نمی/بی» and suffixes «ها/تر/ترین», verb endings and the ezafe ye
  - Arabic look-alike characters (ي، ك، ة) and Arabic-Indic digits converted to Persian
  - Persian quotation marks «…», Persian punctuation (، ؛ ؟) and the Persian percent sign (٪)
  - Kashida removal and cleanup of invisible direction marks (RLM/LRM) pasted from messaging apps
- Optional automatic correction on save, or on display — display mode never modifies the database and is cache-plugin friendly
- Persian readability panel in the post editor: sentence-length analysis and keyword density with Persian stemming; the focus keyphrase is read automatically from Yoast SEO or Rank Math when present
- Persian-friendly image filenames and alt text: uploaded images get an English, SEO-friendly filename («عکس پروفایل کاربر» → `user-profile-photo.jpg`) and an automatic alt attribute, built on a 3,000+ word Persian→English dictionary
- Site-wide writing health report: scan all posts, list the ones with issues, export to CSV
- Weekly scheduled scan with an e-mail summary of the site's writing health

### Notes

- All processing happens locally on your server with PHP — the plugin makes no external requests.
- A premium version (bulk post fixing, an AI writing assistant with your own API key, and the smart English slug engine for new posts) is sold separately at [webbanwp.ir](https://webbanwp.ir/); its code is not included in this repository.
