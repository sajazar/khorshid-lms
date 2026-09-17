<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Plugin {
    private static bool $booted = false;

    public static function activate(): void {
        Schema::install();
        self::ensure_storage();
        self::register_endpoints();
        flush_rewrite_rules();
    }

    public static function deactivate(): void { flush_rewrite_rules(); }

    public static function boot(): void {
        if ( self::$booted ) { return; }
        self::$booted = true;
        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
            add_action( 'admin_notices', static function (): void { echo '<div class="notice notice-error"><p>' . esc_html__( 'Khorshid LMS به PHP 8.1 یا بالاتر نیاز دارد.', 'khorshid-lms' ) . '</p></div>'; } );
            return;
        }
        Admin::hooks();
        Settings::hooks();
        Frontend::hooks();
        Rest::hooks();
        VideoStream::hooks();
        WooCommerce::hooks();
        CertificateManager::hooks();
        add_action( 'init', [ self::class, 'register_endpoints' ] );
        add_action( 'init', [ self::class, 'maybe_upgrade' ], 20 );
    }

    public static function maybe_upgrade(): void {
        if ( get_option( 'kh_lms_version' ) !== KH_LMS_VERSION ) { Schema::install(); }
    }

    public static function register_endpoints(): void { add_rewrite_endpoint( 'my-courses', EP_ROOT | EP_PAGES ); }

    private static function ensure_storage(): void {
        $upload = wp_upload_dir();
        $base = trailingslashit( $upload['basedir'] ) . 'khorshid-lms-storage';
        wp_mkdir_p( $base . '/certificates' );
        wp_mkdir_p( $base . '/courses' );
        wp_mkdir_p( $base . '/cache' );
        if ( ! file_exists( $base . '/index.php' ) ) { file_put_contents( $base . '/index.php', "<?php\n// Silence is golden.\n" ); }
        if ( ! file_exists( $base . '/.htaccess' ) ) { file_put_contents( $base . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); }
    }
}
