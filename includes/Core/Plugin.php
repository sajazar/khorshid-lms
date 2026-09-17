<?php
namespace KhorshidLMS\Core;
if ( ! defined( 'ABSPATH' ) ) exit;
final class Plugin {
    private static bool $booted = false;
    public static function activate(): void { Schema::install(); self::storage(); add_rewrite_endpoint( 'my-courses', EP_ROOT | EP_PAGES ); flush_rewrite_rules(); }
    public static function deactivate(): void { flush_rewrite_rules(); }
    private static function storage(): void {
        $upload = wp_upload_dir(); $dir = trailingslashit( $upload['basedir'] ) . 'khorshid-lms-storage';
        wp_mkdir_p( $dir . '/certificates' ); wp_mkdir_p( $dir . '/cache' );
        if ( ! file_exists( $dir . '/index.php' ) ) file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" );
        if ( ! file_exists( $dir . '/.htaccess' ) ) file_put_contents( $dir . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>Require all denied</IfModule>\n<IfModule !mod_authz_core.c>Deny from all</IfModule>\n" );
    }
    public static function boot(): void {
        if ( self::$booted ) return; self::$booted = true;
        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) return;
        Admin::hooks(); Frontend::hooks(); Rest::hooks(); WooCommerce::hooks();
        add_action( 'init', static fn() => add_rewrite_endpoint( 'my-courses', EP_ROOT | EP_PAGES ) );
    }
}
