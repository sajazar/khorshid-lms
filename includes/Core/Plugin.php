<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {
    private static bool $booted = false;

    public static function activate(): void {
        Schema::install();
        self::ensure_storage();
        self::register_endpoints();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function boot(): void {
        if ( self::$booted ) {
            return;
        }

        self::$booted = true;

        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
            add_action(
                'admin_notices',
                static function (): void {
                    echo '<div class="notice notice-error"><p>افزونه Khorshid LMS نیاز به PHP 8.1 یا بالاتر دارد.</p></div>';
                }
            );
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
    }

    public static function register_endpoints(): void {
        add_rewrite_endpoint( 'my-courses', EP_ROOT | EP_PAGES );
    }

    private static function ensure_storage(): void {
        $upload_dir = wp_upload_dir();
        $base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'khorshid-lms-storage';

        wp_mkdir_p( $base_dir . '/certificates' );
        wp_mkdir_p( $base_dir . '/cache' );

        if ( ! file_exists( $base_dir . '/index.php' ) ) {
            file_put_contents( $base_dir . '/index.php', "<?php\n// Silence is golden.\n" );
        }

        if ( ! file_exists( $base_dir . '/.htaccess' ) ) {
            file_put_contents(
                $base_dir . '/.htaccess',
                "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
            );
        }
    }
}
