<?php
/*
Plugin Name:  text
Description:  text
Version:      1.0
Author:       Nael Concescu
Author URI:   https://cv.nael.pro
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  text-domain
Domain Path:  /languages
*/

defined( 'ABSPATH' ) or die();

// Wrap plugin absolute path into a constant
define('SOME_PATH', plugin_dir_path( __FILE__ ) );
// Wrap plugin absolute path into a constant
define('SOME_URL', plugin_dir_url( __FILE__ ) );

if ( is_admin() ) {
	include SOME_PATH.'/admin/class-metabox.php';
	include SOME_PATH.'/admin/class-options-page.php';
}
include SOME_PATH.'/public/class-frontend.php';
include SOME_PATH.'/public/class-cron-actions.php';

register_activation_hook( __FILE__, [ 'SomeClass', 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ 'SomeAnotherClass', 'deactivate_plugin' ] );

/**
 * Activates internationalization feature for the plugin
 */
function some_plugin_textdomain(){
	load_plugin_textdomain('text-domain', false, basename( dirname( __FILE__ ) ).'/languages/');
}
add_action('plugins_loaded', 'some_plugin_textdomain');
