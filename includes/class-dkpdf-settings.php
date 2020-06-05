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
		add_filter( 'plugin_action_links_' . plugin_basename( DKPDF_PLUGIN_FILE ) , array( $this, 'add_settings_link' ) );

	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Adds DK PDF admin menu
	 * @return void
	 */
	public function add_menu_item () {

		// main menu
		$page = add_menu_page( __('DK PDF', 'dk-pdf'), __('DK PDF', 'dk-pdf'), 'manage_options', 'dkpdf' . '_settings',  array( $this, 'settings_page' ) );

		// Addons submenu
		add_submenu_page( 'dkpdf' . '_settings', __('Addons', 'dk-pdf'), __('Addons', 'dk-pdf'), 'manage_options', 'dkpdf-addons', array( $this, 'dkpdf_addons_screen' ));

		// support
		add_submenu_page( 'dkpdf' . '_settings', __('Support', 'dk-pdf'), __('Support', 'dk-pdf'), 'manage_options', 'dkpdf-support', array( $this, 'dkpdf_support_screen' ));

		// settings assets
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );

	}

	public function dkpdf_support_screen() { ?>

		<div class="wrap">
			<h2 style="float:left;width:100%;"><?php echo __('DK PDF Support', 'dk-pdf') ?></h2>

			<div class="dkpdf-item">
				<h3><?php echo __('Documentation', 'dk-pdf') ?></h3>
				<p><?php echo __('Everything you need to know for getting DK PDF up and running.', 'dk-pdf') ?></p>
				<p><a href="https://yordansoares.github.io/dk-pdf/" target="_blank"><?php echo __('Go to Documentation', 'dk-pdf') ?></a></p>
			</div>

			<div class="dkpdf-item">
				<h3><?php echo __('Support', 'dk-pdf') ?></h3>
				<p><?php echo __('Having trouble? don&#8217;t worry, create a ticket in the support forum.', 'dk-pdf') ?></p>
				<p><a href="https://wordpress.org/support/plugin/dk-pdf" target="_blank"><?php echo __('Go to Support', 'dk-pdf') ?></a></p>
			</div>
		</div>

		<?php do_action( 'dkpdf_after_support' );?>

	<?php }

	public function dkpdf_addons_screen() { ?>

		<div class="wrap">
			<h2><?php echo __('DK PDF Addons', 'dkpdf') ?></h2>

			<div class="dkpdf-item">
				<h3><?php echo __('DK PDF Generator', 'dkpdf') ?></h3>
				<p><?php echo __('Allows creating PDF documents with your selected WordPress content, also allows adding a Cover and a Table of contents.', 'dkpdf') ?></p>
				<p><a href="http://codecanyon.net/item/dk-pdf-generator/13530581" target="_blank"><?php echo __('Go to DK PDF Generator', 'dkpdf') ?></a></p>
			</div>

		</div>

	<?php }

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
		$settings_link = '<a href="admin.php?page=' . 'dkpdf' . '_settings">' . __( 'Settings', 'dk-pdf' ) . '</a>';
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
			'title'					=> __( 'PDF Button', 'dk-pdf' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'pdfbutton_text',
					'label'			=> __( 'Button text' , 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'text',
					'default'		=> 'PDF Button',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'pdfbutton_post_types',
					'label'			=> __( 'Post types to apply:', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'checkbox_multi',
					'options'		=> $post_types_arr,
					'default'		=> array()
				),
				array(
					'id' 			=> 'pdfbutton_action',
					'label'			=> __( 'Action', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'open' => __('Open PDF in new Window', 'dk-pdf'), 'download' => __('Download PDF directly', 'dk-pdf') ),
					'default'		=> 'open'
				),
				array(
					'id' 			=> 'pdfbutton_position',
					'label'			=> __( 'Position', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'shortcode' => __('Use shortcode', 'dk-pdf'), 'before' => __('Before content', 'dk-pdf'), 'after' => __('After content', 'dk-pdf') ),
					'default'		=> 'before'
				),
				array(
					'id' 			=> 'pdfbutton_align',
					'label'			=> __( 'Align', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'left' => __('Left', 'dk-pdf'), 'center' => __('Center', 'dk-pdf'), 'right' => __('Right', 'dk-pdf') ),
					'default'		=> 'right'
				),
			)
		);


		// pdf setup
		$settings['dkpdf_setup'] = array(
			'title'					=> __( 'PDF Setup', 'dk-pdf' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'page_orientation',
					'label'			=> __( 'Page orientation', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'radio',
					'options'		=> array( 'vertical' => 'Vertical', 'horizontal' => 'Horizontal' ),
					'default'		=> 'vertical'
				),
				array(
					'id' 			=> 'font_size',
					'label'			=> __( 'Font size', 'dk-pdf' ),
					'description'	=> __('In points (pt)', 'dk-pdf'),
					'type'			=> 'number',
					'default'		=> '12',
					'placeholder'	=> '12'
				),
				array(
					'id' 			=> 'margin_left',
					'label'			=> __( 'Margin left', 'dk-pdf' ),
					'description'	=> __('In points (pt)', 'dk-pdf'),
					'type'			=> 'number',
					'default'		=> '15',
					'placeholder'	=> '15'
				),
				array(
					'id' 			=> 'margin_right',
					'label'			=> __( 'Margin right', 'dk-pdf' ),
					'description'	=> __('In points (pt)', 'dk-pdf'),
					'type'			=> 'number',
					'default'		=> '15',
					'placeholder'	=> '15'
				),
				array(
					'id' 			=> 'margin_top',
					'label'			=> __( 'Margin top', 'dk-pdf' ),
					'description'	=> __('In points (pt)', 'dk-pdf'),
					'type'			=> 'number',
					'default'		=> '50',
					'placeholder'	=> '50'
				),
				array(
					'id' 			=> 'margin_bottom',
					'label'			=> __( 'Margin bottom', 'dk-pdf' ),
					'description'	=> __('In points (pt)', 'dk-pdf'),
					'type'			=> 'number',
					'default'		=> '30',
					'placeholder'	=> '30'
				),
				array(
					'id' 			=> 'margin_header',
					'label'			=> __( 'Margin header', 'dk-pdf' ),
					'description'	=> __('In points (pt)', 'dk-pdf'),
					'type'			=> 'number',
					'default'		=> '15',
					'placeholder'	=> '15'
				),
				array(
					'id' 			=> 'enable_protection',
					'label'			=> __( 'Enable PDF protection', 'dk-pdf' ),
					'description'	=> __( 'Encrypts PDF file and respects permissions given below', 'dk-pdf' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'grant_permissions',
					'label'			=> __( 'Protected PDF permissions', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'checkbox_multi',
					'options'		=> array( 'copy' => __('Copy', 'dk-pdf'), 'print' => __('Print', 'dk-pdf'), 'print-highres' => __('Print Highres', 'dk-pdf'), 'modify' => __('Modify', 'dk-pdf'), 'annot-forms' => __('Annot Forms', 'dk-pdf'), 'fill-forms' => __('Fill Forms', 'dk-pdf'), 'extract' => __('Extract', 'dk-pdf'), 'assemble' => __('Assemble', 'dk-pdf') ),
					'default'		=> array()
				),
				array(
					'id' 			=> 'keep_columns',
					'label'			=> __( 'Keep columns', 'dk-pdf' ),
					'description'	=> __('Columns will be written successively (dkpdf-columns shortcode). i.e. there will be no balancing of the length of columns.', 'dk-pdf'),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
			)
		);

		// header & footer settings
		$settings['pdf_header_footer'] = array(
			'title'	=> __( 'PDF Header & Footer', 'dk-pdf' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'pdf_header_image',
					'label'			=> __( 'Header logo' , 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'image',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'pdf_header_show_title',
					'label'			=> __( 'Header show title', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'pdf_header_show_pagination',
					'label'			=> __( 'Header show pagination', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'pdf_footer_text',
					'label'			=> __( 'Footer text' , 'dk-pdf' ),
					'description'	=> __( 'HTML tags: a, br, em, strong, hr, p, h1 to h4', 'dk-pdf' ),
					'type'			=> 'textarea',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'pdf_footer_show_title',
					'label'			=> __( 'Footer show title', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'pdf_footer_show_pagination',
					'label'			=> __( 'Footer show pagination', 'dk-pdf' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> ''
				),

			)
		);

		// style settings
		$settings['pdf_css'] = array(
			'title'	=> __( 'PDF CSS', 'dk-pdf' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'pdf_custom_css',
					'label'			=> __( 'PDF Custom CSS' , 'dk-pdf' ),
					'description'	=> __( '', 'dk-pdf' ),
					'type'			=> 'textarea_code',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'print_wp_head',
					'label'			=> __( 'Use current theme&#39;s CSS', 'dk-pdf' ),
					'description'	=> __( 'Includes the stylesheet from current theme, but is overridden by PDF Custom CSS and plugins adding its own stylesheets.', 'dk-pdf' ),
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

		if( isset( $_GET['settings-updated']) ) { ?>
		    <div id="message" class="updated">
		        <p><?php _e('Settings saved.', 'dkpdf');?></p>
		    </div>
		<?php }

		// Build page HTML
		$html = '<div class="wrap" id="' . 'dkpdf' . '_settings">' . "\n";
			$html .= '<h2>' . __( 'DK PDF Settings' , 'dk-pdf' ) . '</h2>' . "\n";

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dk-pdf' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dk-pdf' ), $this->parent->_version );
	} // End __wakeup()

}