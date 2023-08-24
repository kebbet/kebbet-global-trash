<?php
/**
 * Plugin Name:       Kebbet plugins - Global trash for a site
 * Plugin URI:        https://github.com/kebbet/kebbet-global-trash
 * Description:       Adds a global trash to handle all items in trash.
 * Author:            Erik Betshammar
 * Version:           1.0.0
 * Network:           true
 * Author URI:        https://verkan.se
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Update URI:        false
 * Text domain:       kebbet-global-trash
 *
 * @author Erik Betshammar
 * @package kebbet-global-trash
 */

namespace kebbet\global_trash;

const KEBBET_GLOBAL_TRASH_VERSION = '1.0.0';
/**
 * Hook into the 'init' action
 */
function init() {
	load_textdomain();
}
add_action( 'init', __NAMESPACE__ . '\init', 0 );

/**
 * Load plugin textdomain.
 */
function load_textdomain() {
	load_plugin_textdomain( 'kebbet-global-trash', false, dirname( plugin_basename( __FILE__ ) ) . '/assets/languages' );
}

require_once plugin_dir_path( __FILE__ ) . 'classes/class-plugin-loader.php';
