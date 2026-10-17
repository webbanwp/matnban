# Changelog

All notable changes to Matnban are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/) and the project follows [Semantic Versioning](https://semver.org/).

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
