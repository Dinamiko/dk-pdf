<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\ButtonVisibility;

use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class ButtonVisibilityModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [];
	}

	public function run( ContainerInterface $container ): bool {
		// Register settings field
		add_filter( 'dkpdf_settings_fields', function ( array $settings ) {
			// Get WordPress roles options
			$roles_options = $this->get_wordpress_roles_options();

			// Find the position of the 'pdfbutton_action' field
			$fields = $settings['pdfbtn']['fields'];
			$action_index = null;

			foreach ( $fields as $index => $field ) {
				if ( $field['id'] === 'pdfbutton_action' ) {
					$action_index = $index;
					break;
				}
			}

			// Create the visibility by role field
			$new_field = array(
				'id'          => 'button_visibility_roles',
				'label'       => __( 'Visibility by role', 'dk-pdf' ),
				'description' => __( 'Select which user roles can see the PDF button. Select "All" to show to all users.', 'dk-pdf' ),
				'type'        => 'select_multi',
				'options'     => $roles_options,
				'default'     => array( 'all' ),
			);

			// Insert before 'pdfbutton_action' if found, otherwise append
			if ( $action_index !== null ) {
				array_splice( $settings['pdfbtn']['fields'], $action_index, 0, array( $new_field ) );
			} else {
				$settings['pdfbtn']['fields'][] = $new_field;
			}

			return $settings;
		} );

		// Enqueue admin assets on settings page to enhance with Select2
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Filter content to remove button for unauthorized users
		add_filter( 'the_content', array( $this, 'filter_button_by_role' ), 11 );

		// Filter archive description to remove button for unauthorized users
		add_filter( 'get_the_archive_description', array( $this, 'filter_button_by_role' ), 11 );

		// Filter shortcode output for unauthorized users
		add_filter( 'do_shortcode_tag', array( $this, 'filter_shortcode_by_role' ), 10, 4 );

		// Prevent PDF generation for unauthorized users
		add_action( 'wp', array( $this, 'check_pdf_access' ), 5 );

		return true;
	}

	/**
	 * Get WordPress roles as options array for select field
	 *
	 * @return array Associative array with 'all' option first, then role_key => role_name
	 */
	private function get_wordpress_roles_options(): array {
		// Start with "All" option
		$roles_options = array(
			'all' => __( 'All', 'dk-pdf' ),
		);

		// Get WordPress roles
		if ( ! function_exists( 'wp_roles' ) ) {
			require_once ABSPATH . 'wp-includes/capabilities.php';
		}

		$wp_roles = wp_roles();

		if ( $wp_roles && isset( $wp_roles->roles ) ) {
			foreach ( $wp_roles->roles as $role_key => $role_data ) {
				$roles_options[ $role_key ] = $role_data['name'];
			}
		}

		return apply_filters( 'dkpdf_button_visibility_roles_options', $roles_options );
	}

	/**
	 * Enqueue admin assets for button visibility feature
	 *
	 * @param string $hook The current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Only load on DK PDF settings page
		if ( $hook !== 'toplevel_page_dkpdf_settings' ) {
			return;
		}

		// Enqueue our CSS for dropdown positioning
		wp_enqueue_style(
			'dkpdf-button-visibility',
			DKPDF_PLUGIN_URL . 'modules/ButtonVisibility/assets/css/button-visibility.css',
			array( 'select2' ),
			'1.0.0'
		);

		// Enqueue our JavaScript to enhance the roles field with Select2
		wp_enqueue_script(
			'dkpdf-button-visibility-js',
			DKPDF_PLUGIN_URL . 'modules/ButtonVisibility/assets/js/button-visibility.js',
			array( 'jquery', 'select2' ),
			'1.0.6',
			true
		);
	}

	/**
	 * Check if current user can see the PDF button based on role settings
	 *
	 * @return bool True if user can see button, false otherwise
	 */
	private function user_can_see_button(): bool {
		// Get selected roles from settings (DK PDF adds 'dkpdf_' prefix to all options)
		$selected_roles = get_option( 'dkpdf_button_visibility_roles', array( 'all' ) );

		// If no roles selected or 'all' is selected, show to everyone
		if ( empty( $selected_roles ) || in_array( 'all', $selected_roles, true ) ) {
			return true;
		}

		// If user is not logged in, hide button
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Get current user
		$current_user = wp_get_current_user();

		// Check if user has no roles (edge case)
		if ( empty( $current_user->roles ) ) {
			return false;
		}

		// Check if any of the user's roles match the selected roles
		foreach ( $current_user->roles as $user_role ) {
			if ( in_array( $user_role, $selected_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filter content to remove PDF button for unauthorized users
	 *
	 * @param string $content The content to filter
	 * @return string Modified content
	 */
	public function filter_button_by_role( string $content ): string {
		// If user can see button, return content unchanged
		if ( $this->user_can_see_button() ) {
			return $content;
		}

		// Remove button HTML using regex
		// Pattern matches the entire dkpdf-button-container div
		$pattern = '/<div[^>]*class="[^"]*dkpdf-button-container[^"]*"[^>]*>.*?<\/div>/s';
		$content = preg_replace( $pattern, '', $content );

		return $content;
	}

	/**
	 * Filter shortcode output for unauthorized users
	 *
	 * @param string $output Shortcode output
	 * @param string $tag Shortcode tag name
	 * @param array $attr Shortcode attributes
	 * @param array $m The regex match array
	 * @return string Empty string if unauthorized, otherwise original output
	 */
	public function filter_shortcode_by_role( string $output, string $tag, $attr, $m ): string {
		// Only filter dkpdf-button shortcode
		if ( $tag !== 'dkpdf-button' ) {
			return $output;
		}

		// If user cannot see button, return empty string
		if ( ! $this->user_can_see_button() ) {
			return '';
		}

		// Return original output
		return $output;
	}

	/**
	 * Check if user has access to view PDF before generation
	 * Runs early on 'wp' action (priority 5) before PDF generator (priority 10)
	 *
	 * @return void
	 */
	public function check_pdf_access(): void {
		// Check if this is a PDF request
		$pdf = get_query_var( 'pdf' );
		if ( ! $pdf ) {
			return;
		}

		// Check if user has permission to view PDF
		if ( ! $this->user_can_see_button() ) {
			// Clean any output buffers
			while ( ob_get_level() ) {
				ob_end_clean();
			}

			// Show error message and exit
			wp_die(
				'<h1>' . esc_html__( 'Access Denied', 'dk-pdf' ) . '</h1>' .
				'<p>' . esc_html__( 'You do not have permission to view this PDF.', 'dk-pdf' ) . '</p>' .
				'<p><a href="' . esc_url( remove_query_arg( 'pdf' ) ) . '">' . esc_html__( 'Go back', 'dk-pdf' ) . '</a></p>',
				esc_html__( 'Access Denied', 'dk-pdf' ),
				array( 'response' => 403 )
			);
		}
	}
}
