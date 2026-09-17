# Khorshid LMS

Professional WordPress + WooCommerce LMS plugin with modular architecture, secure video handling, curriculum builder, and user enrollment tracking.

## Features
- Modern admin dashboard and curriculum builder
- Single-page course creation form with inline chapters and lessons
- WooCommerce product-to-course mapping
- User enrollment tracking and access checks
- Progress recording and completion handling
- Short-lived secure video token flow
- RTL-first UI, responsive design, WordPress-safe escaping and nonce checks

## Installation
1. Download or clone this repository.
2. Upload the plugin ZIP to WordPress.
3. Activate the plugin.
4. Open `مدیریت دوره‌ها -> افزودن دوره جدید`.
5. Add course title, chapter structure and lessons directly in the same screen.
6. Connect a WooCommerce product to a known course via the product meta field `شناسه دوره LMS`.

## Shortcodes
- `[kh_lms_course id="123"]`
- `[kh_lms_my_courses]`

## Security notes
The plugin hides the origin video URL from the frontend and relies on short-lived access tokens with server-side validation.
Absolute DRM protection is not possible with a browser-only solution; premium streaming/CDN protection remains recommended for production-grade media delivery.

## Upgrade / cleanup
Uninstall cleanup only runs when the option `kh_lms_delete_data` is set to `1`.

## Project structure
- `khorshid-lms.php` — plugin bootstrap
- `includes/Core` — modular plugin classes
- `assets/css` — admin and frontend styling
- `assets/js` — admin builder and frontend behavior
- `uninstall.php` — optional cleanup logic
