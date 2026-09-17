<?php
/**
 * Plugin Name: Khorshid LMS
 * Plugin URI: https://github.com/sajazar/khorshid-lms
 * Description: Production-ready modular LMS for WordPress + WooCommerce.
 * Version: 1.2.1
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: khorshid-lms
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'KH_LMS_VERSION', '1.2.1' );
define( 'KH_LMS_FILE', __FILE__ );
define( 'KH_LMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'KH_LMS_URL', plugin_dir_url( __FILE__ ) );

// Explicitly load the bootstrap class before registering lifecycle hooks.
$kh_lms_plugin_class = KH_LMS_DIR . 'includes/Core/Plugin.php';
if ( is_readable( $kh_lms_plugin_class ) ) {
    require_once $kh_lms_plugin_class;
}

spl_autoload_register( static function ( string $class ): void {
    $prefix = 'KhorshidLMS\\';
    if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) { return; }
    $relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
    $file = KH_LMS_DIR . 'includes/' . $relative . '.php';
    if ( is_readable( $file ) ) { require_once $file; }
} );

register_activation_hook( __FILE__, [ 'KhorshidLMS\\Core\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'KhorshidLMS\\Core\\Plugin', 'deactivate' ] );
add_action( 'plugins_loaded', static function (): void { \KhorshidLMS\Core\Plugin::boot(); } );
