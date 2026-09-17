<?php
/**
 * Plugin Name: Khorshid LMS
 * Plugin URI: https://github.com/sajazar/khorshid-lms
 * Description: Production-ready modular LMS for WordPress + WooCommerce.
 * Version: 1.2.3
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: khorshid-lms
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'KH_LMS_VERSION' ) ) { define( 'KH_LMS_VERSION', '1.2.3' ); }
if ( ! defined( 'KH_LMS_FILE' ) ) { define( 'KH_LMS_FILE', __FILE__ ); }
if ( ! defined( 'KH_LMS_DIR' ) ) { define( 'KH_LMS_DIR', plugin_dir_path( __FILE__ ) ); }
if ( ! defined( 'KH_LMS_URL' ) ) { define( 'KH_LMS_URL', plugin_dir_url( __FILE__ ) ); }

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
