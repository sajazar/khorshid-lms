<?php
/** Plugin Name: Khorshid LMS
 * Version: 1.3.1
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: khorshid-lms
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'KH_LMS_VERSION', '1.3.1' ); define( 'KH_LMS_FILE', __FILE__ ); define( 'KH_LMS_DIR', plugin_dir_path( __FILE__ ) ); define( 'KH_LMS_URL', plugin_dir_url( __FILE__ ) );
spl_autoload_register( static function ( string $class ): void { $prefix='KhorshidLMS\\'; if(0!==strncmp($class,$prefix,strlen($prefix)))return; $file=KH_LMS_DIR.'includes/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_readable($file))require_once $file; } );
register_activation_hook(__FILE__,['KhorshidLMS\\Core\\Plugin','activate']); register_deactivation_hook(__FILE__,['KhorshidLMS\\Core\\Plugin','deactivate']); add_action('plugins_loaded',static function():void{\KhorshidLMS\Core\Plugin::boot();});
