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
		) );

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

		// Add responsive CSS for Select2
		wp_add_inline_style( 'select2', '
			/* Prevent horizontal overflow on settings page */
			#dkpdf_settings {
				overflow-x: hidden !important;
			}

			/* Constrain the wrapper and form */
			#dkpdf_settings .wrap {
				max-width: 100% !important;
				overflow-x: hidden !important;
			}

			#dkpdf_settings form {
				max-width: 100% !important;
				overflow-x: hidden !important;
			}

			/* Force form table to respect container width */
			#dkpdf_settings .form-table {
				width: 100% !important;
				max-width: 100% !important;
				box-sizing: border-box !important;
				margin: 0 !important;
			}

			#dkpdf_settings .form-table th,
			#dkpdf_settings .form-table td {
				box-sizing: border-box !important;
				padding-right: 0 !important;
			}

			/* Container responsive rules */
			.select2-container.select2-container--default {
				max-width: 100% !important;
				min-width: 0 !important;
				box-sizing: border-box !important;
				margin: 0 !important;
			}

			/* Selection box responsive rules */
			.select2-container--default .select2-selection--multiple {
				max-width: 100% !important;
				min-width: 0 !important;
				min-height: 28px !important;
				box-sizing: border-box !important;
				overflow: hidden !important;
				flex-wrap: wrap !important;
				display: flex !important;
				align-items: flex-start !important;
				padding: 2px 6px !important;
			}

			/* Selected items responsive */
			.select2-container--default .select2-selection--multiple .select2-selection__choice {
				max-width: 100% !important;
				min-width: 0 !important;
				flex-shrink: 1 !important;
				word-wrap: break-word !important;
				word-break: break-word !important;
				white-space: normal !important;
				overflow: hidden !important;
				text-overflow: ellipsis !important;
				box-sizing: border-box !important;
				padding-left: 25px !important;
				padding-right: 5px !important;
			}

			/* Input field responsive */
			.select2-container--default .select2-selection--multiple .select2-search__field {
				min-width: 50px !important;
				max-width: 100% !important;
				flex-grow: 1 !important;
			}

			/* Dropdown responsive - constrain both container and dropdown */
			.select2-container--open {
				max-width: 100% !important;
				box-sizing: border-box !important;
			}

			.select2-dropdown {
				max-width: 100vw !important;
				box-sizing: border-box !important;
				left: 0 !important;
				right: auto !important;
			}

			/* Constrain dropdown width when attached to body */
			body > .select2-container--open .select2-dropdown {
				max-width: calc(100vw - 180px) !important;
				box-sizing: border-box !important;
			}

			/* Constrain search field in dropdown */
			.select2-search--dropdown {
				max-width: 100% !important;
				box-sizing: border-box !important;
			}

			.select2-search--dropdown .select2-search__field {
				max-width: 100% !important;
				box-sizing: border-box !important;
			}

			/* Constrain results */
			.select2-results {
				max-width: 100% !important;
				overflow-x: hidden !important;
				box-sizing: border-box !important;
			}

			.select2-results__options {
				max-width: 100% !important;
				overflow-x: hidden !important;
			}

			.select2-results__option {
				max-width: 100% !important;
				overflow-wrap: break-word !important;
				word-wrap: break-word !important;
				white-space: normal !important;
			}

			/* Mobile specific rules */
			@media (max-width: 782px) {
				.select2-container--default .select2-selection--multiple {
					min-height: 32px !important;
					padding: 3px 8px !important;
				}
				.select2-container--default .select2-selection--multiple .select2-selection__choice {
					padding: 3px 6px !important;
					margin: 1px !important;
					font-size: 14px !important;
				}
				.select2-container--default .select2-selection--multiple .select2-search__field {
					min-height: 28px !important;
					font-size: 16px !important;
				}

				/* More aggressive constraints on mobile */
				body > .select2-container--open .select2-dropdown {
					max-width: calc(100vw - 40px) !important;
				}
			}
		' );

		wp_register_script( 'dkpdf' . '-settings-js', plugins_url( 'dk-pdf/assets/js/settings-admin.js' ), array(
			'farbtastic',
			'jquery',
			'select2'
		), '1.0.0' );
		wp_enqueue_script( 'dkpdf' . '-settings-js' );

		// Localize script for AJAX
		wp_localize_script( 'dkpdf' . '-settings-js', 'dkpdf_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'dkpdf_ajax_nonce' )
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
					'label'       => __( 'Post types to apply:', 'dkpdf' ),
					'description' => '',
					'type'        => 'checkbox_multi',
					'options'     => $post_types_arr,
					'default'     => array()
				),
				array(
					'id'          => 'pdfbutton_taxonomies',
					'label'       => __( 'Taxonomies to apply:', 'dkpdf' ),
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
				__( 'Select a set of PDF templates, by default the Legacy set (the templates in the root of templates folder) is selected. All templates can be %1$soverridden%2$s in your theme or child theme.', 'dkpdf' ),
				'<a href="https://dinamiko.dev/docs/how-to-use-dk-pdf-templates-in-your-theme/" target="_blank">',
				'</a>'
			),
			'fields'      => array(
				array(
					'id'          => 'selected_template',
					'label'       => __( 'PDF Template Sets', 'dkpdf' ),
					'description' => '',
					'type'        => 'select',
					'options'     => array( '' => 'Legacy', 'default/' => 'Default' ),
					'default'     => array(),
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
				'label'       => __( 'WC archive display', 'dkpdf' ),
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
				'label'       => __( 'WC archive columns', 'dkpdf' ),
				'description' => '',
				'type'        => 'select',
				'options'     => array( '1' => '1 Column', '2' => '2 Columns', '3' => '3 Columns', '4' => '4 Columns' ),
				'default'     => '1',
				'depends_on'  => 'dkpdf_selected_template',
			);
		}

		// Custom Fields settings - only show when not using legacy templates
		$selected_template = get_option( 'dkpdf_selected_template' );
		$selected_post_types = get_option( 'dkpdf_pdfbutton_post_types', array() );

		if ( ! empty( $selected_template ) ) {
			$custom_fields_settings = array(
				'title'       => __( 'Custom Fields', 'dkpdf' ),
				'description' => __( 'Select custom fields to include in PDF for each post type selected in post types to apply.', 'dkpdf' ),
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
						'description' => __( 'Click to select custom fields or type to search. Selected fields will be displayed in the PDF.', 'dkpdf' ),
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
						$dependency_value = get_option( $field['depends_on'] );
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
