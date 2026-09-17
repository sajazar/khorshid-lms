# Khorshid LMS

Professional modular LMS plugin for WordPress 6+ / PHP 8.1+ with WooCommerce integration.

## Current production baseline
- Custom relational database tables with indexes and activation migrations.
- RTL-first admin course builder: chapters and lessons are created **inside the Add/Edit Course screen**, not as separate menus.
- Shortcodes: `[kh_lms_course id="123"]` and `[kh_lms_my_courses]`.
- WooCommerce product-to-course mapping and access grant/revoke on paid, completed and refunded orders.
- Authenticated short-lived video tokens; original source URLs are never sent to frontend code.
- Server-side progress endpoint with completion threshold.
- Protected storage bootstrap and opt-in uninstall cleanup.
- Namespaced autoloaded PHP classes, escaping, nonces, capability checks and prepared SQL.

## Installation
1. Download the repository as ZIP and upload it in WordPress Plugins.
2. Activate it.
3. Open **مدیریت دوره‌ها → افزودن دوره** and build the complete curriculum in one screen.
4. For paid courses, put the course ID in the WooCommerce product field **شناسه دوره LMS**.

## Important video-security limitation
The plugin hides the origin URL and applies authorization, expiry and user binding. A browser that is allowed to play a video can technically capture its media bytes; absolute download prevention requires DRM such as Widevine/FairPlay/PlayReady and a compatible external provider. For high-volume video, use an HLS/CDN or signed-origin provider rather than proxying large files through PHP.

## Safe cleanup
By default uninstall preserves data. To deliberately remove plugin tables, define the option `kh_lms_delete_data` as `1` before uninstalling.

## Roadmap
The code is intentionally modular for certificates, HLS providers, quizzes, attachments, bulk actions and migrations. These should be added with schema migrations rather than storing operational data in post meta.
