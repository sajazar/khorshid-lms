<?php
namespace KhorshidLMS\Core;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Plugin {
    private static bool $booted = false;
    public static function activate(): void { Schema::install(); self::storage(); add_rewrite_endpoint( 'my-courses', EP_ROOT | EP_PAGES ); flush_rewrite_rules(); }
    public static function deactivate(): void { flush_rewrite_rules(); }
    public static function boot(): void {
        if ( self::$booted ) { return; } self::$booted = true;
        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) { return; }
        Admin::hooks(); Settings::hooks(); Frontend::hooks(); FrontendAccess::hooks(); Rest::hooks(); VideoStream::hooks(); WooCommerce::hooks(); CertificateManager::hooks(); add_action( 'init', [ self::class, 'maybe_upgrade' ], 20 );
    }
    public static function maybe_upgrade(): void { if ( get_option( 'kh_lms_version' ) !== KH_LMS_VERSION ) { Schema::install(); } }
    private static function storage(): void { $u=wp_upload_dir(); $d=trailingslashit($u['basedir']).'khorshid-lms-storage'; foreach(['certificates','courses','cache'] as $x){wp_mkdir_p($d.'/'.$x);} if(!file_exists($d.'/index.php'))file_put_contents($d.'/index.php',"<?php\n"); if(!file_exists($d.'/.htaccess'))file_put_contents($d.'/.htaccess',"Options -Indexes\n<IfModule mod_authz_core.c>Require all denied</IfModule>\n<IfModule !mod_authz_core.c>Deny from all</IfModule>\n"); }
}
