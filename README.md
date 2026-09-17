# Khorshid LMS

## Important update for v1.2.2
This release fixes the fatal `Class "KhorshidLMS\\Core\\WooCommerce" not found` error. The WooCommerce integration is now a soft dependency: the plugin remains loadable when WooCommerce is disabled or unavailable.

### Upgrade steps
1. Deactivate the old Khorshid LMS copy. If the WordPress admin is inaccessible, rename `wp-content/plugins/khorshid-lms-main` temporarily using hosting File Manager.
2. Delete the old plugin directory.
3. Download the current repository ZIP from the green **Code** button and ensure the extracted folder contains `khorshid-lms.php` at its root.
4. Install and activate the new copy.
5. If WooCommerce is installed, verify it is active before testing paid-course access.

### Log interpretation
The `yith-woocommerce-ajax-navigation` and `emperor` notices/warnings shown in the supplied log belong to those plugins/theme, not Khorshid LMS. They should be updated or reported to their respective authors. They do not explain the LMS fatal error.
