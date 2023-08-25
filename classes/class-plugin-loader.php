<?php
/**
 * Load all plugin functionality.
 *
 * @since 1.0.0
 *
 * @author Erik Betshammar
 * @package kebbet-global-trash
 */

use const kebbet\global_trash\KEBBET_GLOBAL_TRASH_VERSION;
use const kebbet\global_trash\PAGE_SLUG;

require_once plugin_dir_path( __FILE__ ) . 'class-kebbet-trash.php';

/**
 * Init all the plugin features and table.
 */
class Plugin_Loader {

	// Class instance.
	static $instance;

	// Global trash WP_List_Table object.
	public $trash_items_obj;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
	}

	/**
	 * Remove custom query args on table reload after action.
	 *
	 * @param string $url Current page url.
	 * @return string
	 */
	public function remove_table_arg( $url ) {
		return remove_query_arg( array( 'action', 'delete', '_wpnonce' ) , $url );
	}

	/**
	 * Adds the plugin menu item, and all trash page stuff.
	 *
	 * @return void
	 */
	public function plugin_menu() {
		$hook = add_submenu_page(
			'tools.php',
			esc_html__( 'Global trash', 'kebbet-global-trash' ),
			esc_html__( 'Trash', 'kebbet-global-trash' ),
			'administrator',
			PAGE_SLUG,
			array( $this, 'plugin_settings_page' ),
			60
		);

		add_action( "load-$hook", array( $this, 'init_trash_page' ) );
	}

	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap"><?php
			echo '<h1 class="wp-heading-inline">' . __( 'Global trash', 'kebbet-global-trash' ). '</h1>';
			$number_of_posts = $this->trash_items_obj->record_count('');
			$form_class      = 'not-empty';
			if ( 0 === $number_of_posts ) {
				$form_class = 'empty';
			}
?>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post" class="<?php echo $form_class; ?>">
								<?php
								$this->trash_items_obj->prepare_items();
								$this->trash_items_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}

	/**
	 * Add filters and actions, and init the List object on page load.
	 *
	 * @return void
	 */
	public function init_trash_page() {
		add_filter( 'set_url_scheme', array( $this, 'remove_table_arg' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'table_styles' ) );

		$this->trash_items_obj = new \kebbet\global_trash\Kebbet_Global_Trash_List();
	}

	/**
	 * Adds style to the custom UI parts.
	 *
	 * @return void
	 */
	public function table_styles() {
		$handle   = PAGE_SLUG . '-css';
		$css_base = 'body.tools_page_' . PAGE_SLUG;

		$custom_css  = $css_base . ' .tablenav.top {
			height: auto;
			display: flex;
			gap: 0.5rem 1rem;
			flex-wrap: wrap;
			margin-bottom: .5rem;
		}';
		$custom_css .= $css_base . ' #poststuff {
			padding-top: 0;
		}';
		$custom_css .= $css_base . ' .empty .tablenav.top {
			display: none;
		}';
		$custom_css .= $css_base . ' .tablenav.top > * {
			padding: 0;
		}';
		$custom_css .= $css_base . ' .tablenav.top .clear {
			display:none;
		}';
		$custom_css .= $css_base . ' .tablenav.top .actions select {
			float: none;
		}';
		$custom_css .= $css_base . ' .tablenav.top #doaction {
			margin: 0;
		}';
		$custom_css .= $css_base . ' .tablenav.top .tablenav-pages {
			margin: 0;
			float: none;
		}';
		$custom_css .= $css_base . ' .empty-trash-actions {
			display: flex;
			gap: .5rem;
			flex-wrap: wrap;
			flex-grow: 1;
		}';
		$custom_css .= $css_base . ' .kebbet-trash-table tr th:nth-child(1) {
			width: 32px;
		}';
		$custom_css .= $css_base . ' .kebbet-trash-table tr th:nth-child(2) {
			width: calc(100% - 322px);
		}';
		$custom_css .= $css_base . ' .kebbet-trash-table tr th:nth-child(3) {
			width: 110px;
		}';
		$custom_css .= $css_base . ' .kebbet-trash-table tr th:nth-child(4) {
			width: 180px;
		}';

		wp_register_style( $handle, '', false, KEBBET_GLOBAL_TRASH_VERSION );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $custom_css );
	}

	/**
	 * Singleton instance
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

/**
 * Load the plugin.
 *
 * @return void
 */
function load_plugin() {
	Plugin_Loader::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\load_plugin' );
