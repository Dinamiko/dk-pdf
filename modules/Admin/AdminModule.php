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
			'admin.settings'        => static fn( $container ) => new Settings(
				$container->get( 'admin.field_renderer' ),
				$container->get( 'admin.field_validator' ),
				$container->get( 'core.helper' )
			),
			'admin.metaboxes'       => static fn() => new MetaBoxes(),
		];
	}

	public function run( ContainerInterface $container ): bool {
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
}
