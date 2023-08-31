<?php
/**
 * Plugin Name:       Kebbet plugins - Global trash for a site
 * Plugin URI:        https://github.com/kebbet/kebbet-global-trash
 * Description:       Adds a tools page with a global trash list, to handle all trashed post items.
 * Version:           1.0.1
 * Network:           true
 * Author:            Erik Betshammar
 * Author URI:        https://verkan.se
 * Requires at least: 6.2
 * Tested up to:      6.3
 * Requires PHP:      7.4
 * Update URI:        false
 * Text domain:       kebbet-global-trash
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @author Erik Betshammar
 * @package kebbet-global-trash
 */

namespace kebbet\global_trash;

const KEBBET_GLOBAL_TRASH_VERSION = '1.0.1';
/**
 * Hook into the 'init' action
 */
function init() {
	load_textdomain();
}
add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Load plugin textdomain.
 */
function load_textdomain() {
	load_plugin_textdomain( 'kebbet-global-trash', false, dirname( plugin_basename( __FILE__ ) ) . '/assets/languages' );
}

require_once plugin_dir_path( __FILE__ ) . 'classes/class-plugin-loader.php';
