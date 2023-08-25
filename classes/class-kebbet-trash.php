<?php
/**
 * Create a custom post table accessible from Tools menu.
 *
 * @since 1.0.0
 *
 * @author Erik Betshammar
 * @package kebbet-global-trash
 */

namespace kebbet\global_trash;

const PAGE_SLUG           = 'kebbet-trash';
const DEFAULT_POST_NUMBER = 25;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Creates a custom table for all trashed post items, based on WP_List_Table.
 */
class Kebbet_Global_Trash_List extends \WP_List_Table {
	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular'          => __( 'Trash item', 'kebbet-global-trash' ), // Singular name of the listed records.
				'plural'            => 'kebbet-trash-items', //__( 'Trash items', 'kebbet-global-trash' ), // Plural name of the listed records.
				'ajax'              => false, // Does this table support ajax?
				'trash_action_name' => 'do_row_action_trash',
				'trash_nonce_name'  => 'nonce_do_action_trash',
				'delete_all_name'   => 'do_delete_all_items',
			)
		);
	}

	/**
	 * Retrieve trash item data from the database
	 *
	 * @global $wpdb
	 *
	 * @param int $per_page    Number of posts to get.
	 * @param int $page_number Which page.
	 * @param string|null $filter_data Data from filter.
	 *
	 * @return mixed
	 */
	public static function get_trash_items( $per_page = DEFAULT_POST_NUMBER, $page_number = 1, $filter_data = null ) {

		global $wpdb;

		$sql  = self::db_base_sql();
		$sql .= self::db_post_type( $filter_data );

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		if ( false !== $per_page ) {
			$sql .= " LIMIT $per_page";
			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		}

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	 * Returns markup for a notice.
	 *
	 * @param string $type Type of message.
	 * @param array  $args Mandatory options.
	 * @return void
	 */
	protected static function get_notice_message( $type, $args ) {
		$css_classes = 'notice notice-large';

		switch ( $type ) {
			case 'restore':
				$css_classes .= ' notice-success';
				$title       = get_the_title( $args['post_id'] );
				$message     = sprintf(
					__( '%s was restored to draft.', 'kebbet-global-trash' ),
					'<strong>' . $title . '</strong>'
				);
				break;

			case 'delete':
				if ( $args['has_deleted'] ) {
					$css_classes .= ' notice-success';
					$message      = __( 'One item deleted.', 'kebbet-global-trash' );
					if ( 1 !== $args['delete_count'] ) {
						$message = sprintf(
							__( '%s item deleted.', 'kebbet-global-trash' ),
							$args['delete_count']
						);
					}
				} else {
					$css_classes .= ' notice-warning';
					$message      = __( 'No items has been deleted.', 'kebbet-global-trash' );
				}
				break;
			
			default:
				break;
		}

		echo '<div class="' . esc_attr( $css_classes ) . '">' . $message . '</div>';
	}

	/**
	 * Custom SQL string for selecting posts of a post type in wp db.
	 *
	 * @param string $post_type The post type slug
	 * @return string
	 */
	protected static function db_post_type( $post_type ) {
		if ( $post_type ) {
			return " AND post_type = '" . esc_sql( $post_type ) . "'";
		}
		return '';
	}

	/**
	 * Custom SQL string base selection of posts.
	 *
	 * @global $wpdb
	 *
	 * @return string
	 */
	protected static function db_base_sql( $count = false ) {
		global $wpdb;
		$status = " WHERE post_status = 'trash'";
		if ( $count ) {
			return "SELECT COUNT(*) FROM $wpdb->posts " . $status;
		} else {
			return "SELECT * FROM $wpdb->posts " . $status;
		}
	}

	/**
	 * Delete a record.
	 *
	 * @param int $id Post ID.
	 */
	public static function delete_item( $id ) {
		wp_delete_post( $id, true );
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @global $wpdb
	 *
	 * @param string $filter_data Post type filter selection.
	 * @return intval
	 */
	public static function record_count( $filter_data ) {
		global $wpdb;

		$post_type_filter = self::db_post_type( $filter_data );
		$sql_base         = self::db_base_sql( true );
		$count            = $wpdb->get_var( $sql_base . $post_type_filter );

		return absint( $count );
	}

	/**
	 * Text displayed when no post data is available
	 */
	public function no_items() {
		_e( 'No trash items available.', 'kebbet-global-trash' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item Row item data.
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'actions':
			case 'post_type':
			case 'timestamp':
				return $item[$column_name];
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item Row item data.
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			esc_attr( $item['ID'] )
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item Row item data.
	 *
	 * @return string
	 */
	function column_name( $item ) {
		$title = __( 'Post has no title', 'kebbet-global-trash' );
		if ( isset( $item['post_title'] ) ) {
			if ( $item['post_title'] ) {
				$title = $item['post_title'];
			}
		}
		return '<strong>' . $title . '</strong>';
	}

	/**
	 * Method for time stamp column
	 *
	 * @param array $item Row item data.
	 *
	 * @return string
	 */
	function column_timestamp( $item ) {
		$trash_date = get_post_meta( $item['ID'], '_wp_trash_meta_time');
		$date_format = _x( 'Y-m-d', 'Date format for trash date', 'kebbet-global-trash' );
		$time_format = _x( 'H:i:s', 'Time format for trash date', 'kebbet-global-trash' );
		$datetime   = sprintf(
			/* translators: 1: date. 2: time. */
			__( '%1$s %2$s', 'kebbet-global-trash' ),
			'<span class="date">' . wp_date( $date_format, $trash_date['0'] ) . '</span>',
			'<span class="time">' . wp_date( $time_format, $trash_date['0'] ) . '</span>',
		);

		return $datetime;
	}

	/**
	 * Method for `post_type` column.
	 *
	 * @param array $item Row item data.
	 *
	 * @return string
	 */
	function column_post_type( $item ) {
		if ( ! isset( $item['post_type'] ) ) {
			return;
		}
		$post_type_object = get_post_type_object( $item['post_type'] );
		if ( is_wp_error( $post_type_object ) ) {
			return;
		}
		return $post_type_object->labels->singular_name;
	}

	/**
	 * Method for actions column.
	 *
	 * @param array $item Row item data.
	 *
	 * @return string
	 */
	function column_actions( $item ) {
		$delete_nonce   = wp_create_nonce( $this->_args['trash_nonce_name'] );
		$delete_action  = sprintf(
			'<a href="?page=%s&action=%s&%s=%s&_wpnonce=%s">%s</a>',
			PAGE_SLUG,
			$this->_args['trash_action_name'],
			'delete',
			absint( $item['ID'] ),
			$delete_nonce,
			_x( 'Delete permanently', 'row action label', 'kebbet-global-trash' )
		);

		$restore_url    = wp_nonce_url(
			admin_url(
				sprintf(
					get_post_type_object( $item['post_type'] )->_edit_link . '&amp;action=untrash',
					$item['ID']
				)
			),
			'untrash-post_' . $item['ID']
		);
		$restore_action = sprintf(
			'<a href="%s">%s</a>',
			$restore_url,
			_x( 'Restore', 'row action label', 'kebbet-global-trash' )
		);

		$actions['delete']  = $delete_action;
		$actions['restore'] = $restore_action;

		return $this->row_actions( $actions, true );
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'name'      => _x( 'Name', 'Column header', 'kebbet-global-trash' ),
			'timestamp' => _x( 'Trash date', 'Column header', 'kebbet-global-trash' ),
			'post_type' => _x( 'Post type', 'Column header', 'kebbet-global-trash' ),
			'actions'   => _x( 'Actions', 'Column header', 'kebbet-global-trash' ),
		);

		return $columns;
	}

	/**
	 * Class names for main table.
	 *
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'striped', PAGE_SLUG . '-table' );
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name'      => array( 'post_title', true ),
			'post_type' => array( 'post_type', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => _x( 'Delete', 'Bulk action', 'kebbet-global-trash' ),
		);

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/**
		 * Process bulk action
		 */
		$this->process_bulk_action();

		if ( ( isset( $_GET['untrashed'] ) && isset( $_GET['ids'] ) ) ) {
			$notice = self::get_notice_message( 'restore', array( 'post_id' => wp_unslash( trim( $_GET['ids'] ) ) ) );
			echo $notice;
		}

		$filter_data  = self::filter_selection( $_POST, true );
		$per_page     = $this->get_items_per_page( 'items_per_page', DEFAULT_POST_NUMBER );
		$current_page = self::get_pagenum();
		$total_items  = self::record_count( $filter_data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // We have to calculate the total number of items.
				'per_page'    => $per_page // We have to determine how many items to show on a page.
			)
		);

		$this->items = self::get_trash_items( $per_page, $current_page, $filter_data );
	}

	/**
	 * Process actions in table.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( $this->current_action() ) {
			$nonce                = esc_attr( wp_unslash( $_POST['_wpnonce'] ) );
			$nonce_verification_1 = wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] );
			$nonce_verification_2 = wp_verify_nonce( $nonce, $this->_args['trash_nonce_name'] );

			if ( ! ( $nonce_verification_1 || $nonce_verification_2 ) ) {
				die( __( 'Error: Nonce verification failed.', 'kebbet-global-trash' ) );
			}
		}

		// Detect when a bulk action is being triggered …
		switch ( $this->current_action() ) {
			case 'delete':
				$delete_item_id = $_GET['delete'];
				self::delete_item( absint( $delete_item_id ) );
				break;

			case 'restore':
				break;

			case 'delete_all':
				$real_action = '';
				if ( isset( $_POST['delete_all'] ) ) {
					$real_action = wp_unslash( $_POST['delete_all'] );
				}
				$delete_count = 0;
				switch ( $real_action ) {
					case $this->_args['delete_all_name']:
						$trash_items = self::get_trash_items( false, 1 );
						foreach ( $trash_items as $trash_item ) {
							self::delete_item( absint( $trash_item['ID'] ) );
							$has_deleted = true;
							$delete_count++;
						}
						break;

					case '';
						break;

					default:
						$trash_items = self::get_trash_items( false, 1, $real_action );
						foreach ( $trash_items as $trash_item ) {
							self::delete_item( absint( $trash_item['ID'] ) );
							$has_deleted = true;
							$delete_count++;
						}
						break;
				}
				$notice_args = array(
					'has_deleted'  => $has_deleted,
					'delete_count' => $delete_count,
				);
				$notice      = self::get_notice_message( 'delete', $notice_args );
				echo $notice;
				break;

			case $this->_args['trash_action_name']:
				if ( isset( $_GET['delete'] ) ) {
					$delete_item_id = absint( $_GET['delete'] );
					self::delete_item( absint( $delete_item_id ) );
				}
				break;

			default:
				break;
		}


		// If the delete bulk action is triggered.
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
			|| ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$has_deleted  = false;
			$delete_count = 0;

			$delete_ids = array();
			if ( isset( $_POST['bulk-delete'] ) ) {
				$delete_ids = esc_sql( $_POST['bulk-delete'] );
			}

			// Loop over the array of record IDs and delete them.
			if ( is_array( $delete_ids ) && ! empty( $delete_ids ) ) {
				foreach ( $delete_ids as $id ) {
					self::delete_item( absint( $id ) );
					$has_deleted = true;
					$delete_count++;
				}
			}
			$notice_args = array(
				'has_deleted'  => $has_deleted,
				'delete_count' => $delete_count,
			);
			$notice      = self::get_notice_message( 'delete', $notice_args );
			echo $notice;
		}
	}

	/**
	 * Returns the filter selection if set, and if to be returned depending on preload request or not.
	 *
	 * @param array   $data    $_POST array.
	 * @param boolean $prepare If this is a request from prepare function or not.
	 * @return string
	 */
	protected static function filter_selection( $data, $prepare = false ) {
		if ( ! ( isset( $data['filter_selection'] ) && ! empty( $data['filter_selection'] ) ) ) {
			return '';
		}

		/**
		 * The `delete_all` is set when deleting all posts of a post type, from a post type filtered view, 
		 * and to make sure all posts are displayed, do not filter the view.
		 */
		if ( true === $prepare && isset( $data['delete_all'] ) ) {
			return '';
		}

		if ( 'all' !== wp_unslash( trim( $data['filter_selection'] ) ) ) {
			return wp_unslash( trim( $data['filter_selection'] ) );
		}
		return '';
	}

	/**
	 * Extra filters per post types, and empty trash buttons, per post type.
	 *
	 * @param string $which The tablenav position, top or bottom.
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$trash_items = self::get_trash_items( false, 1 );
		if ( empty( $trash_items ) ) {
			return;
		}

		foreach ( $trash_items as $trash_item ) {
			$post_type = get_post_type( $trash_item['ID'] );

			if ( ! is_wp_error( $post_type ) ) {
				$post_type_object = get_post_type_object( $post_type );
			}
			if ( ! is_wp_error( $post_type_object ) ) {
				$post_type_data[$post_type_object->name] = $post_type_object->labels->name;
			}

		}

		if ( empty( $post_type_data ) ) {
			return;
		}

		$nav_markup  = '<div class="actions alignleft">';
		$nav_markup .= '<select name="filter_selection" id="filter_selection">';
		$nav_markup .= '<option value="all">' . __( 'All post types', 'kebbet-global-trash' ) . '</option>';

		// For marking the current selection in the dropdown.
		$filter_value      = self::filter_selection( $_POST );
		$trash_buttons     = array();
		$post_type_options = '';

		foreach ( $post_type_data as $slug => $label ) {
			$selected           = '';
			$trash_button_class = 'button';
			if ( $slug === $filter_value ) {
				$selected           .= ' selected';
				$trash_button_class .= ' button-primary';
			}
			$post_type_option   = '<option' . $selected . ' value="' . esc_attr( $slug ) . '">';
			$post_type_option  .= esc_html( $label );
			$post_type_option  .= '</option>';
			$post_type_options .= $post_type_option;

			$trash_all_label      = sprintf(
				/* translators: %s: post type label */
				__( 'Empty trash for %s', 'kebbet-global-trash' ),
				$label
			);
			$trash_buttons[$slug] = self::custom_submit_button(
				'delete_all',
				$trash_button_class,
				$trash_all_label,
				$slug
			);

		}

		$nav_markup .= $post_type_options;
		$nav_markup .= '</select>';
		$nav_markup .= get_submit_button(
			_x( 'Filter', 'verb, to filter', 'kebbet-global-trash' ),
			'button',
			'submit',
			false
		);
		$nav_markup .= '</div>';

		if ( 1 !== count( $post_type_data ) ) {
			echo $nav_markup;
		}

		echo '<div class="empty-trash-actions">';
		if ( isset( $trash_buttons[$filter_value] ) ) {
			echo $trash_buttons[$filter_value];
		} else {
			echo self::custom_submit_button(
				'delete_all',
				'button button-primary',
				__( 'Empty trash for all post types', 'kebbet-global-trash' ),
				$this->_args['delete_all_name']
			);
			if ( 1 !== count( $post_type_data ) ) {
				foreach ( $trash_buttons as $button ) {
					echo $button;
				}
			}
		}
		echo '</div>';
	}

	/**
	 * Returns a custom button element
	 *
	 * @param string $id    Button ID attribute value.
	 * @param string $class CSS classes separated with space.
	 * @param string $label Visible button label.
	 * @param string $slug  Action slug.
	 * @return string
	 */
	public function custom_submit_button( $id, $class, $label, $slug ) {
		$output  = '<button type="submit"';
		$output .= ' id="' . esc_attr( $id ) . '_' . esc_attr( $slug ) . '"';
		$output .= ' name="' . esc_attr( $id ) . '"';
		$output .= ' class="' . esc_attr( $class ) . '"';
		$output .= ' value="' . esc_attr( $slug ) . '"';
		$output .= '>';
		$output .= $label;
		$output .= '</button>';

		return $output;
	}

	/**
	 * Gets the current action.
	 *
	 * @return string
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) && isset( $_REQUEST['page'] ) ) {
			if ( PAGE_SLUG === wp_unslash( $_REQUEST['page'] ) ) {
				return 'delete_all';
			}
		}

		return parent::current_action();
	}
}
