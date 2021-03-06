<?php

/**
 * Register and render the admin metaboxes for GravityView
 */
class GravityView_Admin_Metaboxes {

	static $metaboxes_dir;

	/**
	 *
	 */
	function __construct() {

        self::$metaboxes_dir = GRAVITYVIEW_DIR . 'includes/admin/metaboxes/';

		include_once self::$metaboxes_dir . 'class-gravityview-metabox-tab.php';

		include_once self::$metaboxes_dir . 'class-gravityview-metabox-tabs.php';

		$this->initialize();

	}

	/**
	 * Add WordPress hooks
	 * @since 1.7.2
	 */
	function initialize() {

		// Make Yoast go down to the bottom please.
		add_filter('wpseo_metabox_prio', array( $this, '__return_low') );

		add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ));

		// Fix annoying 3rd party metabox behavior
		// Remove metaboxes. We need to run this twice for Genesis (9) and others (11). Default is 10.
		add_action( 'admin_menu' , array( $this, 'remove_other_metaboxes' ), 9 );
		add_action( 'admin_menu' , array( $this, 'remove_other_metaboxes' ), 11 );
		// Add them back in
		add_action( 'add_meta_boxes', array( $this, 'add_other_metaboxes' ), 20 );

		// information box
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_shortcode_hint' ) );

	}

	/**
	 * Return 'low' as the status for metabox priority
	 * @return string 'low'
	 */
	function __return_low() {
		return 'low';
	}

	function register_metaboxes() {
		global $post;

		// On Comment Edit, for example, $post isn't set.
		if( empty( $post ) || !is_object( $post ) || !isset( $post->ID ) ) {
			return;
		}

		// select data source for this view
		add_meta_box( 'gravityview_select_form', $this->get_data_source_header( $post->ID ), array( $this, 'render_data_source_metabox' ), 'gravityview', 'normal', 'high' );

		// select view type/template
		add_meta_box( 'gravityview_select_template', __( 'Choose a View Type', 'gravityview' ), array( $this, 'render_select_template_metabox' ), 'gravityview', 'normal', 'high' );

		// View Configuration box
		add_meta_box( 'gravityview_view_config', __( 'View Configuration', 'gravityview' ), array( $this, 'render_view_configuration_metabox' ), 'gravityview', 'normal', 'high' );

		$this->add_settings_metabox_tabs();

		// Other Settings box
		add_meta_box( 'gravityview_settings', __( 'View Settings', 'gravityview' ), array( $this, 'settings_metabox_render' ), 'gravityview', 'normal', 'core' );

	}

	/**
	 * Render the View Settings metabox
	 * @since 1.8
	 * @param WP_Post $post
	 */
	function settings_metabox_render( $post ) {

		/**
		 * @param WP_Post $post
		 */
		do_action( 'gravityview/metaboxes/before_render', $post );

		$metaboxes = GravityView_Metabox_Tabs::get_all();

		include self::$metaboxes_dir . 'views/gravityview-navigation.php';
		include self::$metaboxes_dir . 'views/gravityview-content.php';

		/**
		 * @param WP_Post $post
		 */
		do_action( 'gravityview/metaboxes/after_render', $post );
	}

	/**
	 * Add default tabs to the Settings metabox
	 * @since 1.8
	 */
	private function add_settings_metabox_tabs() {

		$metaboxes = array(
			array(
				'id' => 'template_settings',
				'title' => __( 'View Settings', 'gravityview' ),
				'file' => 'view-settings.php',
				'icon-class' => 'dashicons-admin-generic',
				'callback' => '',
				'callback_args' => '',
			),
			array(
				'id' => 'single_entry', // Use the same ID as View Settings for backward compatibility
				'title' => __( 'Single Entry', 'gravityview' ),
				'file' => 'single-entry.php',
				'icon-class' => 'dashicons-media-default',
				'callback' => '',
				'callback_args' => '',
			),
			array(
				'id' => 'sort_filter',
				'title' => __( 'Filter &amp; Sort', 'gravityview' ),
				'file' => 'sort-filter.php',
				'icon-class' => 'dashicons-sort',
				'callback' => '',
				'callback_args' => '',
			),
		);

		/**
		 * Modify the default settings metabox tabs
		 * @param array $metaboxes
		 * @since 1.8
		 */
		$metaboxes = apply_filters( 'gravityview/metaboxes/default', $metaboxes );

		foreach( $metaboxes as $m ) {

			$tab = new GravityView_Metabox_Tab( $m['id'], $m['title'], $m['file'], $m['icon-class'], $m['callback'], $m['callback_args'] );

			GravityView_Metabox_Tabs::add( $tab );

		}

		unset( $tab );

	}

	/**
	 * Generate the title for Data Source, which includes the Action Links once configured.
	 *
	 * @since 1.8
	 *
	 * @param int $post_id ID of the current post
	 *
	 * @return string "Data Source", plus links if any
	 */
	private function get_data_source_header( $post_id ) {

		//current value
		$current_form = gravityview_get_form_id( $post_id );

		$links = GravityView_Admin_Views::get_connected_form_links( $current_form, false );

		if( !empty( $links ) ) {
			$links = '<span class="alignright gv-form-links">'. $links .'</span>';
		}

		return __( 'Data Source', 'gravityview' ) . $links;
	}

	/**
	 * Render html for 'select form' metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_data_source_metabox( $post ) {

		if( !empty( $post->ID ) ) {
			$this->post_id = $post->ID;
		}

		include self::$metaboxes_dir . 'views/data-source.php';

	}

	/**
	 * Render html for 'select template' metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_select_template_metabox( $post ) {

		include self::$metaboxes_dir . 'views/select-template.php';
	}

	/**
	 * Generate the script tags necessary for the Gravity Forms Merge Tag picker to work.
	 *
	 * Creates
	 * @filter default text
	 * @action default text
	 * @param  mixed      $curr_form Form ID
	 * @return null|string     Merge tags html; NULL if $curr_form isn't defined.
	 */
	public static function render_merge_tags_scripts( $curr_form ) {

		if( empty( $curr_form )) {
			return NULL;
		}

		$form = gravityview_get_form( $curr_form );

		$get_id_backup = isset($_GET['id']) ? $_GET['id'] : NULL;

		if( isset( $form['id'] ) ) {
		    $form_script = 'var form = ' . GFCommon::json_encode($form) . ';';

		    // The `gf_vars()` method needs a $_GET[id] variable set with the form ID.
		    $_GET['id'] = $form['id'];

		} else {
		    $form_script = 'var form = new Form();';
		}

		$output = '<script type="text/javascript" data-gv-merge-tags="1">' . $form_script . "\n" . GFCommon::gf_vars(false) . '</script>';

		// Restore previous $_GET setting
		$_GET['id'] = $get_id_backup;

		return $output;
	}

	/**
	 * Render html for 'View Configuration' metabox
	 *
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	function render_view_configuration_metabox( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_view_configuration', 'gravityview_view_configuration_nonce' );

		// Selected Form
		$curr_form = gravityview_get_form_id( $post->ID );

		// Selected template
		$curr_template = gravityview_get_template_id( $post->ID );

		echo self::render_merge_tags_scripts( $curr_form );

		include self::$metaboxes_dir . 'views/view-configuration.php';
	}

	/**
	 * Render html View General Settings
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_view_settings_metabox( $post ) {

		// View template settings
		$current_settings = gravityview_get_template_settings( $post->ID );

		include self::$metaboxes_dir . 'views/view-settings.php';

	}



	/**
	 * Render shortcode hint in the Publish metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_shortcode_hint() {
		global $post;

		// Only show this on GravityView post types.
		if( false === gravityview_is_admin_page() ) { return; }

		// If the View hasn't been configured yet, don't show embed shortcode
		if( !gravityview_get_directory_fields( $post->ID ) ) { return; }

		include self::$metaboxes_dir . 'views/shortcode-hint.php';
	}

	/**
	 * Modify WooThemes metabox behavior
	 *
	 * Only show when the View has been configured.
	 *
	 * @return void
	 */
	function remove_other_metaboxes() {
		global $pagenow;

		$gv_page = gravityview_is_admin_page();

		// New View or Edit View page
		if($gv_page === 'single') {

			// Prevent the SEO from being checked. Eesh.
			add_filter( 'wpseo_use_page_analysis', '__return_false' );

			// Genesis - adds the metaboxes too high. Added back in below.
			remove_action( 'admin_menu', 'genesis_add_inpost_layout_box' );

			// New View page
			if($pagenow === 'post-new.php' ) {

				// WooThemes
				remove_meta_box( 'woothemes-settings', 'gravityview', 'normal' );

				// WordPress SEO Plugin
				add_filter( 'option_wpseo_titles', array( $this, 'hide_wordpress_seo_metabox' ) );
			}

		}

	}

	function add_other_metaboxes() {
		global $pagenow;

		if(!gravityview_is_admin_page()) { return; }

		// Genesis
		if(function_exists('genesis_inpost_layout_box') && $pagenow !== 'post-new.php') {
			// Add back in Genesis meta box
			add_meta_box( 'genesis_inpost_layout_box', __( 'Layout Settings', 'gravityview' ), 'genesis_inpost_layout_box', 'gravityview', 'advanced', 'default' );
		}
	}

	/**
	 * Modify the WordPress SEO plugin's metabox behavior
	 *
	 * Only show when the View has been configured.
	 * @param  array       $options WP SEO options array
	 * @return array               Modified
	 */
	function hide_wordpress_seo_metabox( $options = array() ) {

		$options['hideeditbox-gravityview'] = true;

		return $options;
	}

}

new GravityView_Admin_Metaboxes;
