<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DKPDF_Settings {

	private static $_instance = null;
	public $parent = null;
	public $_token;
	public $base = '';
	public $settings = array();

	public function __construct ( $parent ) {

		$this->parent = $parent;

		$this->base = 'dkpdf_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		//add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , array( $this, 'add_settings_link' ) );
	
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'DK PDF Settings', 'dkpdf' ) , __( 'DK PDF Settings', 'dkpdf' ) , 'manage_options' , 'dkpdf' . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the dkpdf-admin-js script below
		wp_enqueue_style( 'farbtastic' );
    	wp_enqueue_script( 'farbtastic' );

    	// We're including the WP media scripts here because they're needed for the image upload field
    	// If you're not including an image upload then you can leave this function call out
    	wp_enqueue_media();

    	
    	wp_register_script( 'dkpdf' . '-settings-js', plugins_url( 'dk-pdf/assets/js/settings-admin.js' ), array( 'farbtastic', 'jquery' ), '1.0.0' );
    	wp_enqueue_script( 'dkpdf' . '-settings-js' );
    	
    	
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . 'dkpdf' . '_settings">' . __( 'Settings', 'dkpdf' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$post_types_arr = dkpdf_get_post_types();

		// pdf button settings
		$settings['pdfbtn'] = array(
			'title'					=> __( 'PDF Button', 'dkpdf' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'pdfbutton_text',
					'label'			=> __( 'Button text' , 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'text',
					'default'		=> 'PDF Button',
					'placeholder'	=> ''
				),				
				array(
					'id' 			=> 'pdfbutton_post_types',
					'label'			=> __( 'Post types to apply:', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'checkbox_multi',
					'options'		=> $post_types_arr,
					'default'		=> array()
				),
				array(
					'id' 			=> 'pdfbutton_action',
					'label'			=> __( 'Action', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'open' => 'Open PDF in new Window', 'download' => 'Download PDF directly' ),
					'default'		=> 'open'
				),
				array(
					'id' 			=> 'pdfbutton_position',
					'label'			=> __( 'Position', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'before' => 'Before content', 'after' => 'After content' ),
					'default'		=> 'before'
				),
				array(
					'id' 			=> 'pdfbutton_align',
					'label'			=> __( 'Align', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ),
					'default'		=> 'right'
				),
			)
		);

		// header & footer settings
		$settings['pdf_header_footer'] = array(
			'title'	=> __( 'PDF Header & Footer', 'dkpdf' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'pdf_header_image',
					'label'			=> __( 'Header logo' , 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'image',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'pdf_header_show_title',
					'label'			=> __( 'Header show title', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'pdf_header_show_pagination',
					'label'			=> __( 'Header show pagination', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'pdf_footer_text',
					'label'			=> __( 'Footer text' , 'dkpdf' ),
					'description'	=> __( 'HTML tags are allowed.', 'dkpdf' ),
					'type'			=> 'textarea',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'pdf_footer_show_title',
					'label'			=> __( 'Footer show title', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'pdf_footer_show_pagination',
					'label'			=> __( 'Footer show pagination', 'dkpdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),

			)
		);

		$settings = apply_filters( 'dkpdf' . '_settings_fields', $settings );

		return $settings;

	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), 'dkpdf' . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( 'dkpdf' . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), 'dkpdf' . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {

		// Build page HTML
		$html = '<div class="wrap" id="' . 'dkpdf' . '_settings">' . "\n";
			$html .= '<h2>' . __( 'DK PDF Settings' , 'dkpdf' ) . '</h2>' . "\n";

			$tab = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( 'dkpdf' . '_settings' );
				do_settings_sections( 'dkpdf' . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'dkpdf' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * Main DKPDF_Settings Instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}