<?php
/**
 * Plugin Name:			Pluton Custom Sidebar
 * Plugin URI:			https://plutonwp.com/extension/pluton-custom-sidebar/
 * Description:			Create an unlimited number of sidebars and assign unlimited number of widgets.
 * Version:				1.0.0
 * Author:				PlutonWP
 * Author URI:			https://plutonwp.com/
 * Requires at least:	4.0.0
 * Tested up to:		4.6
 *
 * Text Domain: pluton-custom-sidebar
 * Domain Path: /languages/
 *
 * @package Pluton_Custom_Sidebar
 * @category Core
 * @author PlutonWP
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main instance of Pluton_Custom_Sidebar to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Pluton_Custom_Sidebar
 */
function Pluton_Custom_Sidebar() {
	return Pluton_Custom_Sidebar::instance();
} // End Pluton_Custom_Sidebar()

Pluton_Custom_Sidebar();

/**
 * Main Pluton_Custom_Sidebar Class
 *
 * @class Pluton_Custom_Sidebar
 * @version	1.0.0
 * @since 1.0.0
 * @package	Pluton_Custom_Sidebar
 */
final class Pluton_Custom_Sidebar {

	protected $widget_areas	= array();
	protected $orig			= array();

	/**
	 * Pluton_Custom_Sidebar The single instance of Pluton_Custom_Sidebar.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct( $widget_areas = array() ) {
		$this->token 			= 'pluton-custom-sidebar';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'pcs_load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'pcs_setup' ) );
	}

	/**
	 * Main Pluton_Custom_Sidebar Instance
	 *
	 * Ensures only one instance of Pluton_Custom_Sidebar is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Pluton_Custom_Sidebar()
	 * @return Main Pluton_Custom_Sidebar instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function pcs_load_plugin_textdomain() {
		load_plugin_textdomain( 'pluton-custom-sidebar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if Pluton or a child theme using Pluton as a parent is active and the extension specific filter returns true.
	 * Child themes can disable this extension using the pluton_custom_sidebar filter
	 * @return void
	 */
	public function pcs_setup() {
		$theme = wp_get_theme();

		if ( 'Pluton' == $theme->name || 'pluton' == $theme->template && apply_filters( 'pluton_custom_sidebar', true ) ) {
			add_action( 'init', array( $this, 'register_sidebars' ) , 1000 );
			add_action( 'admin_print_scripts-widgets.php', array( $this, 'add_widget_box' ) );
			add_action( 'load-widgets.php', array( $this, 'add_widget_area' ), 100 );
			add_action( 'load-widgets.php', array( $this, 'scripts' ), 100 );
			add_action( 'admin_print_styles-widgets.php', array( $this, 'inline_css' ) );
			add_action( 'wp_ajax_pluton_delete_widget_area', array( $this, 'pluton_delete_widget_area' ) ); 
		} else {
			add_action( 'admin_notices', array( $this, 'pcs_install_pluton_notice' ) );
		}
	}

	/**
	 * Pluton install
	 * If the user activates the plugin while having a different parent theme active, prompt them to install Pluton.
	 * @since   1.0.0
	 * @return  void
	 */
	public function pcs_install_pluton_notice() {
		echo '<div class="notice is-dismissible updated">
				<p>' . esc_html__( 'Pluton Custom Sidebar requires that you use Pluton as your parent theme.', 'pluton-custom-sidebar' ) . ' <a href="https://plutonwp.com/">' . esc_html__( 'Install Pluton now', 'pluton-custom-sidebar' ) . '</a></p>
			</div>';
	}

	/**
	 * Add the widget box inside a script
	 *
	 * @since 1.0.0
	 */
	public function add_widget_box() {
		$nonce = wp_create_nonce ( 'delete-pluton-widget_area-nonce' ); ?>
		  <script type="text/html" id="pluton-add-widget-template">
			<div id="pluton-add-widget" class="widgets-holder-wrap">
			 <div class="">
			  <input type="hidden" name="pluton-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
			  <div class="sidebar-name">
			   <h3><?php esc_html_e( 'Create Widget Area', 'pluton-custom-sidebar' ); ?> <span class="spinner"></span></h3>
			  </div>
			  <div class="sidebar-description">
				<form id="addWidgetAreaForm" action="" method="post">
				  <div class="widget-content">
					<input id="pluton-add-widget-input" name="pluton-add-widget-input" type="text" class="regular-text" title="<?php esc_attr_e( 'Name', 'pluton-custom-sidebar' ); ?>" placeholder="<?php esc_attr_e( 'Name', 'pluton-custom-sidebar' ); ?>" />
				  </div>
				  <div class="widget-control-actions">
					<div class="aligncenter">
					  <input class="addWidgetArea-button button-primary" type="submit" value="<?php esc_attr_e( 'Create Widget Area', 'pluton-custom-sidebar' ); ?>" />
					</div>
					<br class="clear">
				  </div>
				</form>
			  </div>
			 </div>
			</div>
		  </script>
		<?php
	}        

	/**
	 * Create new Widget Area
	 *
	 * @since 1.0.0
	 */
	public function add_widget_area() {
		if ( ! empty( $_POST['pluton-add-widget-input'] ) ) {
			$this->widget_areas = $this->get_widget_areas();
			array_push( $this->widget_areas, $this->check_widget_area_name( $_POST['pluton-add-widget-input'] ) );
			$this->save_widget_areas();
			wp_redirect( admin_url( 'widgets.php' ) );
			die();
		}
	}

	/**
	 * Before we create a new widget_area, verify it doesn't already exist. If it does, append a number to the name.
	 *
	 * @since 1.0.0
	 */
	public function check_widget_area_name( $name ) {
		if ( empty( $GLOBALS['wp_registered_widget_areas'] ) ) {
			return $name;
		}

		$taken = array();
		foreach ( $GLOBALS['wp_registered_widget_areas'] as $widget_area ) {
			$taken[] = $widget_area['name'];
		}

		$taken = array_merge( $taken, $this->widget_areas );

		if ( in_array( $name, $taken ) ) {
			$counter  = substr( $name, -1 );  
			$new_name = "";
			  
			if ( ! is_numeric( $counter ) ) {
				$new_name = $name . " 1";
			} else {
				$new_name = substr( $name, 0, -1 ) . ((int) $counter + 1);
			}

			$name = $this->check_widget_area_name( $new_name );
		}
		echo esc_html( $name );
		exit();
	}

	public function save_widget_areas() {
		set_theme_mod( 'widget_areas', array_unique( $this->widget_areas ) );
	}

	/**
	 * Register and display the custom widget_area areas we have set.
	 *
	 * @since 1.0.0
	 */
	public function register_sidebars() {

		// Get widget areas
		if ( empty( $this->widget_areas ) ) {
			$this->widget_areas = $this->get_widget_areas();
		}

		// Original widget areas is empty
		$this->orig = array();

		// Save widget areas
		if ( ! empty( $this->orig ) && $this->orig != $this->widget_areas ) {
			$this->widget_areas = array_unique( array_merge( $this->widget_areas, $this->orig ) );
			$this->save_widget_areas();
		}

		// Get tag element from theme mod for the sidebar widget title
		$tag = get_theme_mod( 'sidebar_headings', 'div' ) ? get_theme_mod( 'sidebar_headings', 'div' ) : 'div';
			 
		// If widget areas are defined add a sidebar area for each
		if ( is_array( $this->widget_areas ) ) {
			foreach ( array_unique( $this->widget_areas ) as $widget_area ) {
				$args = array(
					'id'			=> sanitize_key( $widget_area ),
					'name'			=> $widget_area,
					'class'			=> 'pluton-custom',
					'before_widget'	=> '<div class="sidebar-box %2$s clr">',
					'after_widget'	=> '</div>',
					'before_title'	=> '<'. $tag .' class="widget-title">',
					'after_title'	=> '</'. $tag .'>',
				);
				register_sidebar( $args );
			}
		}
	}

	/**
	 * Return the widget_areas array.
	 *
	 * @since 1.0.0
	 */
	public function get_widget_areas() {

		// If the single instance hasn't been set, set it now.
		if ( ! empty( $this->widget_areas ) ) {
			return $this->widget_areas;
		}

		// Get widget areas saved in theem mod
		$widget_areas = get_theme_mod( 'widget_areas' );

		// If theme mod isn't empty set to class widget area var
		if ( ! empty( $widget_areas ) && is_array( $widget_areas ) ) {
			$this->widget_areas = array_unique( array_merge( $this->widget_areas, $widget_areas ) );
		}

		// Return widget areas
		return $this->widget_areas;
	}

	/**
	 * Before we create a new widget_area, verify it doesn't already exist. If it does, append a number to the name.
	 *
	 * @since 1.0.0
	 */
	public function pluton_delete_widget_area() {
		// Check_ajax_referer('delete-pluton-widget_area-nonce');
		if ( ! empty( $_REQUEST['name'] ) ) {
			$name = strip_tags( ( stripslashes( $_REQUEST['name'] ) ) );
			$this->widget_areas = $this->get_widget_areas();
			$key = array_search($name, $this->widget_areas );
			if ( $key >= 0 ) {
				unset( $this->widget_areas[$key] );
				$this->save_widget_areas();
			}
			echo "widget_area-deleted";
		}
		die();
	}

	/**
	 * Enqueue JS for the customizer controls
	 *
	 * @since 1.0.0
	 */
	public function scripts() {

		// Load scripts
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'pluton-widget-areas', plugins_url( '/assets/js/main.min.js', __FILE__ ), array( 'jquery' ), null, true );

		// Get widgets
		$widgets = array();
		if ( ! empty( $this->widget_areas ) ) {
			foreach ( $this->widget_areas as $widget ) {
				$widgets[$widget] = 1;
			}
		}

		// Localize script
		wp_localize_script(
			'pluton-widget-areas',
			'plutonWidgetAreasLocalize',
			array(
				'count'   => count( $this->orig ),
				'delete'  => esc_html__( 'Delete', 'pluton-custom-sidebar' ),
				'confirm' => esc_html__( 'Confirm', 'pluton-custom-sidebar' ),
				'cancel'  => esc_html__( 'Cancel', 'pluton-custom-sidebar' ),
			)
		);
	}

	/**
	 * Adds inline CSS to style the widget form
	 *
	 * @since 1.0.0
	 */
	public function inline_css() { ?>

		<style type="text/css">
			body #pluton-add-widget h3 { text-align: center !important; padding: 15px 7px; font-size: 1.3em; margin-top: 5px; }
			body div#widgets-right .sidebar-pluton-custom .widgets-sortables { padding-bottom: 45px }
			body div#widgets-right .sidebar-pluton-custom.closed .widgets-sortables { padding-bottom: 0 }
			body .pluton-widget-area-footer { display: block; position: absolute; bottom: 0; left: 0; height: 40px; line-height: 40px; width: 100%; border-top: 1px solid #eee; }
			body .pluton-widget-area-footer > div { padding: 8px 8px 0 }
			body .pluton-widget-area-footer .pluton-widget-area-id { display: block; float: left; max-width: 48%; overflow: hidden; position: relative; top: -6px; }
			body .pluton-widget-area-footer .pluton-widget-area-buttons { float: right }
			body .pluton-widget-area-footer .description { padding: 0 !important; margin: 0 !important; }
			body div#widgets-right .sidebar-pluton-custom.closed .widgets-sortables .pluton-widget-area-footer { display: none }
			body .pluton-widget-area-footer .pluton-widget-area-delete { display: block; float: right; margin: 0; }
			body .pluton-widget-area-footer .pluton-widget-area-delete-confirm { display: none; float: right; margin: 0 5px 0 0; }
			body .pluton-widget-area-footer .pluton-widget-area-delete-cancel { display: none; float: right; margin: 0; }
			body .pluton-widget-area-delete-confirm:hover:before { color: red }
			body .pluton-widget-area-delete-confirm:hover { color: #000 }
			body .pluton-widget-area-delete:hover:before { color: #888 }
			body .activate_spinner { display: block !important; position: absolute; top: 10px; right: 4px; background-color: #ECECEC; }
			body #pluton-add-widget form { text-align: center }
			body #widget_area-pluton-custom,
			body #widget_area-pluton-custom h3 { position: relative }
			body #pluton-add-widget p { margin-top: 0 }
			body #pluton-add-widget { margin: 10px 0 0; position: relative; }
			body #pluton-add-widget-input { max-width: 95%; padding: 8px; margin-bottom: 14px; margin-top: 3px; text-align: center; }
		</style>

	<?php }

} // End Class
