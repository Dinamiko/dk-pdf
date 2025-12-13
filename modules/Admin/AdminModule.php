<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class AdminModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'admin.field_renderer'  => static fn() => new FieldRenderer(),
			'admin.field_validator' => static fn() => new FieldValidator(),
			'admin.font_downloader' => static fn() => new FontDownloader(),
			'admin.font_manager'    => static fn() => new FontManager(),
			'admin.settings'        => static fn( $container ) => new Settings(
				$container->get( 'admin.field_renderer' ),
				$container->get( 'admin.field_validator' ),
				$container->get( 'core.helper' )
			),
			'admin.metaboxes'       => static fn() => new MetaBoxes(),
		];
	}

	public function run( ContainerInterface $container ): bool {
		// Run version upgrade check on init (priority 10, before settings init at priority 20)
		add_action( 'init', function() {
			// Only run in admin context
			if ( is_admin() ) {
				$this->check_version_upgrade();
			}
		}, 10 );

		add_action( 'init', function() use ($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			$settings->init_settings();

			$metaboxes = $container->get( 'admin.metaboxes' );
			assert($metaboxes instanceof MetaBoxes);

			add_action( 'add_meta_boxes', function() use($metaboxes) {
				$metaboxes->meta_box_setup();
			});

			add_action( 'save_post', function( int $post_id ) use($metaboxes) {
				$metaboxes->meta_box_save($post_id);
			});
		}, 20 );

		add_action( 'admin_init', function() use ($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			$settings->register_settings();

			// Run font family migration if needed
			$fontManager = $container->get( 'admin.font_manager' );
			assert($fontManager instanceof FontManager);

			$fontManager->migrateToFontFamilies();
		} );

		add_action( 'admin_menu', function() use($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			$settings->add_menu_item();
		} );

		add_filter( 'plugin_action_links_' . plugin_basename( DKPDF_PLUGIN_FILE ), function($links) use($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			return $settings->add_settings_link($links);
		});

		// Register AJAX endpoint for Select2 custom fields search
		add_action( 'wp_ajax_dkpdf_get_custom_fields', function() use($container) {
			$helper = $container->get( 'core.helper' );
			assert($helper instanceof \Dinamiko\DKPDF\Core\Helper);

			$this->handle_custom_fields_ajax( $helper );
		});

		// Register AJAX endpoint for downloading fonts
		add_action( 'wp_ajax_dkpdf_download_fonts', function() use($container) {
			$fontDownloader = $container->get( 'admin.font_downloader' );
			assert($fontDownloader instanceof FontDownloader);

			$this->handle_download_fonts_ajax( $fontDownloader );
		});

		// Register AJAX endpoint for checking download progress
		add_action( 'wp_ajax_dkpdf_download_progress', function() use($container) {
			$fontDownloader = $container->get( 'admin.font_downloader' );
			assert($fontDownloader instanceof FontDownloader);

			$this->handle_download_progress_ajax( $fontDownloader );
		});

		// Register AJAX endpoint for checking fonts status
		add_action( 'wp_ajax_dkpdf_check_fonts_status', function() use($container) {
			$fontDownloader = $container->get( 'admin.font_downloader' );
			assert($fontDownloader instanceof FontDownloader);

			$this->handle_check_fonts_status_ajax( $fontDownloader );
		});

		// Register AJAX endpoint for uploading fonts
		add_action( 'wp_ajax_dkpdf_upload_font', function() use($container) {
			$fontManager = $container->get( 'admin.font_manager' );
			assert($fontManager instanceof FontManager);

			$this->handle_upload_font_ajax( $fontManager );
		});

		// Register AJAX endpoint for deleting fonts
		add_action( 'wp_ajax_dkpdf_delete_font', function() use($container) {
			$fontManager = $container->get( 'admin.font_manager' );
			assert($fontManager instanceof FontManager);

			$this->handle_delete_font_ajax( $fontManager );
		});

		// Register AJAX endpoint for listing fonts
		add_action( 'wp_ajax_dkpdf_list_fonts', function() use($container) {
			$fontManager = $container->get( 'admin.font_manager' );
			assert($fontManager instanceof FontManager);

			$this->handle_list_fonts_ajax( $fontManager );
		});

		return true;
	}

	/**
	 * Handle AJAX request for custom fields search
	 *
	 * @param \Dinamiko\DKPDF\Core\Helper $helper
	 * @return void
	 */
	private function handle_custom_fields_ajax( \Dinamiko\DKPDF\Core\Helper $helper ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_GET['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Get parameters
		$search_term = sanitize_text_field( $_GET['q'] ?? '' );
		$post_type = sanitize_text_field( $_GET['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			wp_send_json_error( 'Post type is required' );
		}

		// Get all custom fields for the post type
		$all_custom_fields = $helper->get_custom_fields_for_post_type( $post_type );

		// Filter fields based on search term
		$filtered_fields = array();
		foreach ( $all_custom_fields as $key => $label ) {
			if ( empty( $search_term ) || stripos( $label, $search_term ) !== false ) {
				$filtered_fields[] = array(
					'id' => $key,
					'text' => $label
				);
			}
		}

		wp_send_json_success( $filtered_fields );
	}

	/**
	 * Handle AJAX request for downloading fonts
	 *
	 * @param FontDownloader $fontDownloader
	 * @return void
	 */
	private function handle_download_fonts_ajax( FontDownloader $fontDownloader ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'dkpdf' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'dkpdf' ) ) );
		}

		// Get GitHub URL from request or use filter default
		$github_url = isset( $_POST['github_url'] ) ? esc_url_raw( $_POST['github_url'] ) : '';

		// Download fonts
		$result = $fontDownloader->downloadFonts( $github_url );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle AJAX request for checking download progress
	 *
	 * @param FontDownloader $fontDownloader
	 * @return void
	 */
	private function handle_download_progress_ajax( FontDownloader $fontDownloader ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'dkpdf' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'dkpdf' ) ) );
		}

		$progress = $fontDownloader->getDownloadProgress();

		wp_send_json_success( array( 'progress' => $progress ) );
	}

	/**
	 * Handle AJAX request for checking fonts status
	 *
	 * @param FontDownloader $fontDownloader
	 * @return void
	 */
	private function handle_check_fonts_status_ajax( FontDownloader $fontDownloader ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'dkpdf' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'dkpdf' ) ) );
		}

		$installed = $fontDownloader->areFontsInstalled();

		wp_send_json_success( array( 'installed' => $installed ) );
	}

	/**
	 * Handle AJAX request for uploading a font
	 *
	 * @param FontManager $fontManager
	 * @return void
	 */
	private function handle_upload_font_ajax( FontManager $fontManager ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'dkpdf' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'dkpdf' ) ) );
		}

		// Check if file was uploaded
		if ( empty( $_FILES['font_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded', 'dkpdf' ) ) );
		}

		// Get optional family name and variant parameters
		$family_name = sanitize_text_field( $_POST['family_name'] ?? '' );
		$variant = sanitize_text_field( $_POST['variant'] ?? '' );

		$result = $fontManager->uploadFont( $_FILES['font_file'], $family_name, $variant );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle AJAX request for deleting a font family or variant
	 *
	 * @param FontManager $fontManager
	 * @return void
	 */
	private function handle_delete_font_ajax( FontManager $fontManager ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'dkpdf' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'dkpdf' ) ) );
		}

		// Get font_key (required) and variant (optional)
		$font_key = sanitize_text_field( $_POST['font_key'] ?? $_POST['font_name'] ?? '' );
		if ( empty( $font_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Font key is required', 'dkpdf' ) ) );
		}

		$variant = sanitize_text_field( $_POST['variant'] ?? '' );

		$result = $fontManager->deleteFont( $font_key, $variant );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle AJAX request for listing fonts
	 *
	 * @param FontManager $fontManager
	 * @return void
	 */
	private function handle_list_fonts_ajax( FontManager $fontManager ): void {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dkpdf_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'dkpdf' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'dkpdf' ) ) );
		}

		$fonts = $fontManager->listFonts();

		wp_send_json_success( array( 'fonts' => $fonts ) );
	}

	/**
	 * Check for version upgrade and run necessary migrations
	 *
	 * @return void
	 */
	private function check_version_upgrade(): void {
		$current_version = DKPDF_VERSION;
		$installed_version = get_option( 'dkpdf_installed_version', '' );

		// If no installed version, this could be either:
		// - A new installation (no options exist)
		// - An upgrade from a version before version tracking
		if ( empty( $installed_version ) ) {
			// Check if this is an existing installation by looking for other options
			$existing_option = get_option( 'dkpdf_pdfbutton_text', null );

			if ( $existing_option !== null ) {
				// This is an upgrade from old version
				// Preserve legacy template if no template is set
				if ( get_option( 'dkpdf_selected_template', null ) === null ) {
					update_option( 'dkpdf_selected_template', '' );
				}
			}
		}

		// Update installed version
		if ( $installed_version !== $current_version ) {
			update_option( 'dkpdf_installed_version', $current_version );
		}
	}
}
