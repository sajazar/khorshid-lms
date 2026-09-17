<?php
/**
 * Plugin Name: Khorshid LMS
 * Plugin URI: https://github.com/sajazar/khorshid-lms
 * Description: Modular Persian-first LMS for WordPress and WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: khorshid-lms
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) exit;
define( 'KH_LMS_VERSION', '1.0.0' );
define( 'KH_LMS_FILE', __FILE__ );
define( 'KH_LMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'KH_LMS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( string $class ): void {
    $prefix = 'KhorshidLMS\\';
    if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) return;
    $relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
    $file = KH_LMS_DIR . 'includes/' . $relative . '.php';
    if ( is_readable( $file ) ) require_once $file;
} );

register_activation_hook( __FILE__, [ 'KhorshidLMS\\Core\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'KhorshidLMS\\Core\\Plugin', 'deactivate' ] );
add_action( 'plugins_loaded', static function (): void { \KhorshidLMS\Core\Plugin::boot(); } );
