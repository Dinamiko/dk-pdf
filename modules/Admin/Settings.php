<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

use Dinamiko\DKPDF\Core\Helper;

class Settings {
	public string $base = '';
	public array $settings = array();
	private FieldRenderer $field_renderer;
	private FieldValidator $field_validator;
	private Helper $helper;

	public function __construct( FieldRenderer $field_renderer, FieldValidator $field_validator, Helper $helper ) {
		$this->base            = 'dkpdf_';
		$this->field_renderer  = $field_renderer;
		$this->field_validator = $field_validator;
		$this->helper          = $helper;
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Adds DK PDF admin menu
	 * @return void
	 */
	public function add_menu_item() {
		// main menu
		$page = add_menu_page( 'DK PDF', 'DK PDF', 'manage_options', 'dkpdf' . '_settings', array(
			$this,
			'settings_page'
		), 'dashicons-pdf' );

		// settings assets
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );

	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the dkpdf-admin-js script below
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

		// We're including the WP media scripts here because they're needed for the image upload field
		// If you're not including an image upload then you can leave this function call out
		wp_enqueue_media();

		// Enqueue Select2 for enhanced dropdowns
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0' );

		// Enqueue admin CSS
		wp_enqueue_style( 'dkpdf-admin-css', plugins_url( 'dk-pdf/build/admin-style.css' ), array(), '1.0.0' );

		$settings_asset_file = include plugin_dir_path( dirname( __DIR__ ) ) . 'build/admin-settings.asset.php';
		$settings_dependencies = isset( $settings_asset_file['dependencies'] ) ? array_merge( array( 'farbtastic', 'select2' ), $settings_asset_file['dependencies'] ) : array( 'farbtastic', 'select2' );
		$settings_version = isset( $settings_asset_file['version'] ) ? $settings_asset_file['version'] : '1.0.0';

		wp_register_script( 'dkpdf' . '-settings-js', plugins_url( 'dk-pdf/build/admin-settings.js' ), $settings_dependencies, $settings_version, true );
		wp_enqueue_script( 'dkpdf' . '-settings-js' );

		// Enqueue Font Manager script
		$font_manager_asset_file = include plugin_dir_path( dirname( __DIR__ ) ) . 'build/admin-font-manager.asset.php';
		$font_manager_dependencies = isset( $font_manager_asset_file['dependencies'] ) ? $font_manager_asset_file['dependencies'] : array();
		$font_manager_version = isset( $font_manager_asset_file['version'] ) ? $font_manager_asset_file['version'] : '1.0.0';

		wp_register_script( 'dkpdf-font-manager-js', plugins_url( 'dk-pdf/build/admin-font-manager.js' ), $font_manager_dependencies, $font_manager_version, true );
		wp_enqueue_script( 'dkpdf-font-manager-js' );

		// Localize script for AJAX
		wp_localize_script( 'dkpdf' . '-settings-js', 'dkpdf_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'dkpdf_ajax_nonce' ),
			'i18n' => array(
				'manage_fonts' => __( 'Manage Fonts', 'dkpdf' ),
				'close' => __( 'Close', 'dkpdf' ),
				'upload_font' => __( 'Upload Font', 'dkpdf' ),
				'uploading' => __( 'Uploading...', 'dkpdf' ),
				'loading' => __( 'Loading', 'dkpdf' ),
				'delete' => __( 'Delete', 'dkpdf' ),
				'active' => __( 'Active', 'dkpdf' ),
				'core' => __( 'Core', 'dkpdf' ),
				'custom' => __( 'Custom', 'dkpdf' ),
				'no_fonts' => __( 'No fonts available.', 'dkpdf' ),
				'only_ttf_files' => __( 'Only TTF font files are supported.', 'dkpdf' ),
				'upload_failed' => __( 'Failed to upload font.', 'dkpdf' ),
				'delete_failed' => __( 'Failed to delete font.', 'dkpdf' ),
				'error_loading_fonts' => __( 'Failed to load fonts.', 'dkpdf' ),
				'cannot_delete_active' => __( 'Cannot delete the currently selected font', 'dkpdf' ),
				'confirm_delete_core' => __( 'Are you sure you want to delete the core font "%s"? You can reinstall it later using the "Install Core Fonts" button.', 'dkpdf' ),
				'confirm_delete_custom' => __( 'Are you sure you want to delete the custom font "%s"? This action cannot be undone.', 'dkpdf' ),
			)
		) );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param array $links Existing links
	 *
	 * @return array        Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=' . 'dkpdf' . '_settings">' . __( 'Settings', 'dkpdf' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Get default value for a field by field ID
	 *
	 * @param string $field_id The field ID (with or without prefix)
	 * @return mixed The default value or empty string if not found
	 */
	private function get_field_default_value( string $field_id ) {
		// Remove prefix if present
		$field_id_without_prefix = str_replace( $this->base, '', $field_id );

		foreach ( $this->settings as $section ) {
			if ( ! isset( $section['fields'] ) ) {
				continue;
			}

			foreach ( $section['fields'] as $field ) {
				if ( ! isset( $field['id'] ) ) {
					continue;
				}

				// Check both with and without prefix
				if ( $field['id'] === $field_id || $field['id'] === $field_id_without_prefix ) {
					return $field['default'] ?? '';
				}
			}
		}

		return '';
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		$post_types_arr = $this->helper->get_post_types();
		$taxonomies_arr = $this->helper->get_taxonomies();

		// pdf button settings
		$settings['pdfbtn'] = array(
			'title'       => __( 'PDF Button', 'dkpdf' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'pdfbutton_text',
					'label'       => __( 'Button text', 'dkpdf' ),
					'description' => '',
					'type'        => 'text',
					'default'     => 'PDF Button',
					'placeholder' => ''
				),
				array(
					'id'          => 'pdfbutton_post_types',
					'label'       => __( 'Post types to apply', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox_multi',
					'options'     => $post_types_arr,
					'default'     => array()
				),
				array(
					'id'          => 'pdfbutton_taxonomies',
					'label'       => __( 'Taxonomies to apply', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox_multi',
					'options'     => $taxonomies_arr,
					'default'     => array(),
					'depends_on'  => 'dkpdf_selected_template',
				),
				array(
					'id'          => 'pdfbutton_action',
					'label'       => __( 'Action', 'dkpdf' ),
					'description' => '',
					'type'        => 'radio',
					'options'     => array( 'open' => 'Open PDF in new Window', 'download' => 'Download PDF directly' ),
					'default'     => 'open'
				),
				array(
					'id'          => 'pdfbutton_position',
					'label'       => __( 'Position', 'dkpdf' ),
					'description' => '',
					'type'        => 'radio',
					'options'     => array(
						'shortcode' => 'Use shortcode',
						'before'    => 'Before content',
						'after'     => 'After content'
					),
					'default'     => 'before'
				),
				array(
					'id'          => 'pdfbutton_align',
					'label'       => __( 'Align', 'dkpdf' ),
					'description' => '',
					'type'        => 'radio',
					'options'     => array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ),
					'default'     => 'right'
				),
			)
		);

		// pdf setup
		$settings['dkpdf_setup'] = array(
			'title'       => __( 'PDF Setup', 'dkpdfg' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'load_theme_css',
					'label'       => __( 'Load theme CSS in PDF', 'dkpdf' ),
					'description' => __( 'Include the current theme stylesheet in PDF templates, when enabled it overrides default font.', 'dkpdf' ),
					'type'        => 'checkbox',
					'default'     => 'on'
				),
				array(
					'id'          => 'default_font',
					'label'       => __( 'Default font', 'dkpdf' ),
					'description' => '',
					'type'        => 'font_selector',
					'default'     => 'DejaVuSans'
				),
				array(
					'id'          => 'core_fonts_installer',
					'label'       => __( 'Core fonts', 'dkpdf' ),
					'description' => sprintf(
						__( 'Recommended for Arabic, Hebrew, Indic and CJK languages support. Fonts will be downloaded from %s repository.', 'dkpdf' ),
						'<a href="https://github.com/Dinamiko/mpdf-ttfonts" target="_blank" rel="noopener noreferrer">mpdf-ttfonts GitHub</a>'
					),
					'type'        => 'core_fonts_installer',
					'default'     => ''
				),
				array(
					'id'          => 'auto_language_detection',
					'label'       => __( 'Auto language detection', 'dkpdf' ),
					'description' => __( 'Automatically detect text language and use appropriate fonts. Works best with core fonts installed.', 'dkpdf' ),
					'type'        => 'checkbox',
					'default'     => ''
				),
				array(
					'id'          => 'enable_rtl',
					'label'       => __( 'Enable RTL', 'dkpdf' ),
					'description' => __( 'Enable right-to-left document direction, this affects text alignment, page layout, and table ordering.', 'dkpdf' ),
					'type'        => 'checkbox',
					'default'     => ''
				),
				array(
					'id'          => 'custom_fonts_manager',
					'label'       => __( 'Custom fonts', 'dkpdf' ),
					'description' => __( 'Upload and manage your own TTF font families.', 'dkpdf' ),
					'type'        => 'custom_fonts_manager',
					'default'     => ''
				),
				array(
					'id'          => 'page_orientation',
					'label'       => __( 'Page orientation', 'dkpdfg' ),
					'description' => '',
					'type'        => 'radio',
					'options'     => array( 'vertical' => 'Vertical', 'horizontal' => 'Horizontal' ),
					'default'     => 'vertical'
				),
				array(
					'id'          => 'font_size',
					'label'       => __( 'Font size', 'dkpdfg' ),
					'description' => 'In points (pt)',
					'type'        => 'number',
					'default'     => '12',
					'placeholder' => '12'
				),
				array(
					'id'          => 'margin_left',
					'label'       => __( 'Margin left', 'dkpdfg' ),
					'description' => 'In points (pt)',
					'type'        => 'number',
					'default'     => '15',
					'placeholder' => '15'
				),
				array(
					'id'          => 'margin_right',
					'label'       => __( 'Margin right', 'dkpdfg' ),
					'description' => 'In points (pt)',
					'type'        => 'number',
					'default'     => '15',
					'placeholder' => '15'
				),
				array(
					'id'          => 'margin_top',
					'label'       => __( 'Margin top', 'dkpdfg' ),
					'description' => 'In points (pt)',
					'type'        => 'number',
					'default'     => '50',
					'placeholder' => '50'
				),
				array(
					'id'          => 'margin_bottom',
					'label'       => __( 'Margin bottom', 'dkpdfg' ),
					'description' => 'In points (pt)',
					'type'        => 'number',
					'default'     => '30',
					'placeholder' => '30'
				),
				array(
					'id'          => 'margin_header',
					'label'       => __( 'Margin header', 'dkpdfg' ),
					'description' => 'In points (pt)',
					'type'        => 'number',
					'default'     => '15',
					'placeholder' => '15'
				),
				array(
					'id'          => 'enable_protection',
					'label'       => __( 'Enable PDF protection', 'dkpdf' ),
					'description' => __( 'Encrypts PDF file and respects permissions given below', 'dkpdf' ),
					'type'        => 'checkbox',
					'default'     => ''
				),
				array(
					'id'          => 'grant_permissions',
					'label'       => __( 'Protected PDF permissions', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox_multi',
					'options'     => array(
						'copy'          => 'Copy',
						'print'         => 'Print',
						'print-highres' => 'Print Highres',
						'modify'        => 'Modify',
						'annot-forms'   => 'Annot Forms',
						'fill-forms'    => 'Fill Forms',
						'extract'       => 'Extract',
						'assemble'      => 'Assemble'
					),
					'default'     => array()
				),
				array(
					'id'          => 'keep_columns',
					'label'       => __( 'Keep columns', 'dkpdf' ),
					'description' => 'Columns will be written successively (dkpdf-columns shortcode). i.e. there will be no balancing of the length of columns.',
					'type'        => 'checkbox',
					'default'     => ''
				),
			)
		);

		// header & footer settings
		$settings['pdf_header_footer'] = array(
			'title'       => __( 'PDF Header & Footer', 'dkpdf' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'pdf_header_image',
					'label'       => __( 'Header logo', 'dkpdf' ),
					'description' => '',
					'type'        => 'image',
					'default'     => '',
					'placeholder' => ''
				),
				array(
					'id'          => 'pdf_header_show_title',
					'label'       => __( 'Header show title', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox',
					'default'     => ''
				),
				array(
					'id'          => 'pdf_header_show_pagination',
					'label'       => __( 'Header show pagination', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox',
					'default'     => ''
				),
				array(
					'id'          => 'pdf_footer_text',
					'label'       => __( 'Footer text', 'dkpdf' ),
					'description' => __( 'HTML tags: a, br, em, strong, hr, p, h1 to h4', 'dkpdf' ),
					'type'        => 'textarea',
					'default'     => '',
					'placeholder' => ''
				),
				array(
					'id'          => 'pdf_footer_show_title',
					'label'       => __( 'Footer show title', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox',
					'default'     => ''
				),
				array(
					'id'          => 'pdf_footer_show_pagination',
					'label'       => __( 'Footer show pagination', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox',
					'default'     => ''
				),

			)
		);

		// style settings
		$settings['pdf_css'] = array(
			'title'       => __( 'PDF CSS', 'dkpdf' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'pdf_custom_css',
					'label'       => __( 'PDF Custom CSS', 'dkpdf' ),
					'description' => '',
					'type'        => 'textarea_code',
					'default'     => '',
					'placeholder' => ''
				),
				array(
					'id'          => 'print_wp_head',
					'label'       => __( 'Use current theme\'s CSS', 'dkpdf' ),
					'description' => __( 'Includes the stylesheet from current theme, but is overridden by PDF Custom CSS and plugins adding its own stylesheets.', 'dkpdf' ),
					'type'        => 'checkbox',
					'default'     => ''
				),
			)
		);

		// PDF Templates
		$settings['pdf_templates'] = array(
			'title'       => __( 'PDF Templates', 'dkpdf' ),
			'description' => sprintf(
				__( 'All templates can be %1$soverridden%2$s in your theme or child theme.', 'dkpdf' ),
				'<a href="https://dinamiko.dev/docs/how-to-use-dk-pdf-templates-in-your-theme/" target="_blank">',
				'</a>'
			),
			'fields'      => array(
				array(
					'id'          => 'selected_template',
					'label'       => __( 'PDF template sets', 'dkpdf' ),
					'description' => '',
					'type'        => 'select',
					'options'     => array( '' => 'Legacy', 'default/' => 'Default' ),
					'default'     => 'default/',
				),
				array(
					'id'          => 'post_display',
					'label'       => __( 'Post display', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox_multi',
					'options'     => [
						'title'      => 'Title',
						'content'      => 'Content',
						'post_author'      => 'Post author',
						'post_date'      => 'Post date',
						'featured_img' => 'Featured image',
					],
					'default'     => array(),
					'depends_on'  => 'dkpdf_selected_template',
				),
				array(
					'id'          => 'taxonomy_display',
					'label'       => __( 'Taxonomy display', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox_multi',
					'options'     => [
						'title'      => 'Title',
						'description'      => 'Description',
						'post_date' => 'Post date',
						'post_excerpt' => 'Post excerpt',
						'post_thumbnail' => 'Post thumbnail',
					],
					'default'     => array(),
					'depends_on'  => 'dkpdf_selected_template',
				),
				array(
					'id'          => 'taxonomy_layout',
					'label'       => __( 'Taxonomy columns', 'dkpdf' ),
					'description' => '',
					'type'        => 'select',
					'options'     => array( '1' => '1 Column', '2' => '2 Columns', '3' => '3 Columns', '4' => '4 Columns' ),
					'default'     => '1',
					'depends_on'  => 'dkpdf_selected_template',
				),
				array(
					'id'          => 'taxonomy_posts_per_page',
					'label'       => __( 'Taxonomy max. items', 'dkpdf' ),
					'description' => '',
					'type'        => 'number',
					'default'     => '100',
					'placeholder' => '100',
					'min'         => '1',
					'max'         => '1000',
					'depends_on'  => 'dkpdf_selected_template',
				),
			)
		);

		if (class_exists('WooCommerce')) {
			$settings['pdf_templates']['fields'][] = array(
				'id'          => 'wc_product_display',
				'label'       => __( 'WC product display', 'dkpdf' ),
				'description' => '',
				'type'        => 'checkbox_multi',
				'options'     => [
					'title'      => 'Title',
					'description'      => 'Description',
					'price'      => 'Price',
					'product_img' => 'Product image',
					'sku' => 'SKU',
					'categories' => 'Categories',
					'tags' => 'Tags',
				],
				'default'     => array(),
				'depends_on'  => 'dkpdf_selected_template',
			);
			$settings['pdf_templates']['fields'][] = array(
				'id'          => 'wc_archive_display',
				'label'       => __( 'WC archive / shop display', 'dkpdf' ),
				'description' => '',
				'type'        => 'checkbox_multi',
				'options'     => [
					'title'      => 'Title',
					'price'      => 'Price',
					'product_thumbnail' => 'Product thumbnail',
					'sku' => 'SKU',
				],
				'default'     => array(),
				'depends_on'  => 'dkpdf_selected_template',
			);
			$settings['pdf_templates']['fields'][] = array(
				'id'          => 'wc_archive_layout',
				'label'       => __( 'WC archive / shop columns', 'dkpdf' ),
				'description' => '',
				'type'        => 'select',
				'options'     => array( '1' => '1 Column', '2' => '2 Columns', '3' => '3 Columns', '4' => '4 Columns' ),
				'default'     => '1',
				'depends_on'  => 'dkpdf_selected_template',
			);
            $settings['pdf_templates']['fields'][] = array(
                    'id'          => 'wc_archive_posts_per_page',
                    'label'       => __( 'WooCommerce archive / shop max. items', 'dkpdf' ),
                    'description' => '',
                    'type'        => 'number',
                    'default'     => '100',
                    'placeholder' => '100',
                    'min'         => '1',
                    'max'         => '1000',
                    'depends_on'  => 'dkpdf_selected_template',
            );
		}

		// Custom Fields settings - only show when not using legacy templates
		$selected_template = get_option( 'dkpdf_selected_template', 'default/' );
		$selected_post_types = get_option( 'dkpdf_pdfbutton_post_types', array() );

		if ( ! empty( $selected_template ) ) {
			$custom_fields_settings = array(
				'title'       => __( 'Custom Fields', 'dkpdf' ),
				'description' => __( 'Select custom fields to include in PDF for each post type selected in PDF Button / Post types to apply.', 'dkpdf' ),
				'fields'      => array()
			);

			// Add a field for each selected post type
			if ( ! empty( $selected_post_types ) ) {
				foreach ( $selected_post_types as $post_type ) {
					$custom_fields = $this->helper->get_custom_fields_for_post_type( $post_type );

					// Always create a field, even if no custom fields exist yet
					$field_options = array( '' => __( '-- Select Custom Field --', 'dkpdf' ) );
					if ( ! empty( $custom_fields ) ) {
						$field_options = array_merge( $field_options, $custom_fields );
					}

					$custom_fields_settings['fields'][] = array(
						'id'          => 'custom_fields_' . $post_type,
						'label'       => sprintf( __( '%s', 'dkpdf' ), ucfirst( $post_type ) ),
						'type'        => 'select2_multi',
						'options'     => $field_options,
						'default'     => array(),
						'depends_on'  => 'dkpdf_selected_template',
						'ajax_action' => 'dkpdf_get_custom_fields',
						'post_type'   => $post_type,
					);
				}
			}

			// Always add the custom fields section (even when empty)
			$settings['custom_fields'] = $custom_fields_settings;
		}

		return apply_filters( 'dkpdf_settings_fields', $settings );
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			// Check posted/selected tab
			$current_section = '';
			// phpcs:ignore
			$tab = sanitize_text_field( wp_unslash( $_POST['tab'] ?? '' ) );
			if ( $tab ) {
				$current_section = $tab;
			} else {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$get_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ?? '' ) );
				if ( $get_tab ) {
					$current_section = $get_tab;
				}
			}

			foreach ( $this->settings as $section => $data ) {
				if ( $current_section && $current_section != $section ) {
					continue;
				}

				// Add section to page
				add_settings_section( $section, $data['title'], array(
					$this,
					'settings_section'
				), 'dkpdf_settings' );

				foreach ( $data['fields'] as $field ) {
					// Check dependency before registering the field
					$should_register = true;
					if ( isset( $field['depends_on'] ) ) {
						$default_value = $this->get_field_default_value( $field['depends_on'] );
						$dependency_value = get_option( $field['depends_on'], $default_value );
						if ( empty( $dependency_value ) ) {
							$should_register = false;
						}
					}

					// Only register and add the field if dependency is satisfied
					if ( $should_register ) {
						// Get sanitization callback based on field type
						$sanitize_callback = $this->get_sanitize_callback( $field );

						// Register field with sanitization
						$option_name = $this->base . $field['id'];
						register_setting( 'dkpdf' . '_settings', $option_name, array(
							'sanitize_callback' => $sanitize_callback,
						) );

						// Add field to page
						add_settings_field( $field['id'], $field['label'], array(
							$this->field_renderer,
							'display_field'
						), 'dkpdf' . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
					}
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p> ' . wp_kses_post( $this->settings[ $section['id'] ]['description'] ) . '</p>' . "\n";

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) ) { ?>
			<div id="message" class="updated">
				<p><?php esc_html_e( 'Settings saved.', 'dkpdf' ); ?></p>
			</div>
		<?php }

		// Build page HTML
		$html = '<div class="wrap" id="' . 'dkpdf' . '_settings">' . "\n";
		$html .= '<h2>' . __( 'DK PDF Settings', 'dkpdf' ) . '</h2>' . "\n";

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab2 = sanitize_text_field( wp_unslash( $_GET['tab'] ?? '' ) );
		if ( $tab2 ) {
			$tab .= $tab2;
		}

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $tab2 ) ) {
					if ( 0 == $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $tab2 ) && $section == $tab2 ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link
				$tab_link = esc_attr( add_query_arg( array( 'tab' => $section ) ) );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++ $c;
			}

			// Add right-aligned links
			$html .= '<span class="dkpdf-nav-links">';
			$html .= '<a href="https://dinamiko.dev/docs-categories/dk-pdf-documentation/" target="_blank"><i class="fa fa-file-o"></i> Documentation</a> | <a href="https://wordpress.org/support/plugin/dk-pdf/" target="_blank"><i class="fa fa-comment-o"></i> Support</a> | <a href="https://dinamiko.dev/dk-pdf-custom-services/" target="_blank"><i class="fa fa-star-o"></i> Need Customization?</a>';

			$html .= '</h2>' . "\n";
		}

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Get settings fields
		ob_start();
		settings_fields( 'dkpdf_settings' );
		do_settings_sections( 'dkpdf_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'dkpdf' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";

		$html .= '</div>' . "\n";

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	/**
	 * Get sanitization callback for a field based on its type
	 *
	 * @param array $field Field configuration
	 * @return callable|string Sanitization callback
	 */
	private function get_sanitize_callback( array $field ) {
		// If field has explicit callback, use it
		if ( isset( $field['callback'] ) && is_callable( $field['callback'] ) ) {
			return $field['callback'];
		}

		// Get field type
		$type = $field['type'] ?? 'text';

		// Return closure that uses FieldValidator
		return function ( $value ) use ( $type ) {
			return $this->field_validator->validate_field( $value, $type );
		};
	}

}
