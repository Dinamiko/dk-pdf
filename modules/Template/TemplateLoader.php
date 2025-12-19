<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Template;

class TemplateLoader {

	protected string $filter_prefix = 'dkpdf';
	protected string $theme_template_directory = 'dkpdf';
	protected string $plugin_directory;
	protected string $plugin_template_directory = 'templates';
	protected array $template_subdirectories = array();

	public function __construct() {
		$this->plugin_directory = DKPDF_PLUGIN_DIR;

		// No subdirectories needed - template directory is determined by get_templates_dir()
		// which returns the correct path for both core and custom template sets
		$this->template_subdirectories = array();
	}

	public function get_template_part( string $slug, ?string $name = null, bool $load = true ): string {
		// Execute code for this part
		do_action( 'get_template_part_' . $slug, $slug, $name );

		$templates = $this->get_template_file_names( $slug, $name );

		return $this->locate_template( $templates, $load, false );
	}

	protected function get_template_file_names( string $slug, ?string $name ): array {
		$templates = array();

		if ( isset( $name ) ) {
			$templates[] = $slug . '-' . $name . '.php';
		}
		$templates[] = $slug . '.php';

		// Add subdirectory template file names
		foreach ( $this->template_subdirectories as $subdirectory ) {
			if ( isset( $name ) ) {
				$templates[] = $subdirectory . '/' . $slug . '-' . $name . '.php';
			}
			$templates[] = $subdirectory . '/' . $slug . '.php';
		}

		return apply_filters( $this->filter_prefix . '_get_template_part', $templates, $slug, $name );
	}

	public function locate_template( array $template_names, bool $load = false, bool $require_once = true ): string {
		// No file found yet
		$located = '';

		// Remove empty entries
		$template_names = array_filter( $template_names );

		// Check if this is a button template (frontend only, not PDF generation)
		$is_button_template = false;
		foreach ( $template_names as $template_name ) {
			if ( strpos( $template_name, 'dkpdf-button' ) !== false ) {
				$is_button_template = true;
				break;
			}
		}

		$template_paths = $this->get_template_paths( $is_button_template );

		// Try to find a template file
		foreach ( $template_names as $template_name ) {
			// Trim off any slashes from the template name
			$template_name = ltrim( $template_name, '/' );

			// Try locating this template file by looping through the template paths
			foreach ( $template_paths as $template_path ) {
				if ( file_exists( $template_path . $template_name ) ) {
					$located = $template_path . $template_name;

					// Debug logging
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! $is_button_template ) {
						error_log( sprintf(
							'DK PDF: Located template "%s" at: %s',
							$template_name,
							$located
						) );
					}

					break 2;
				}
			}
		}

		if ( $load && $located ) {
			load_template( $located, $require_once );
		} elseif ( ! $located && defined( 'WP_DEBUG' ) && WP_DEBUG && ! $is_button_template ) {
			error_log( sprintf(
				'DK PDF: Template not found. Searched for: %s in paths: %s',
				implode( ', ', $template_names ),
				implode( ', ', $template_paths )
			) );
		}

		return $located;
	}

	protected function get_template_paths( bool $is_button_template = false ): array {
		$theme_directory = trailingslashit( $this->theme_template_directory );

		$file_paths = array(
			10  => trailingslashit( get_template_directory() ) . $theme_directory,
			100 => $is_button_template ? $this->get_plugin_templates_dir() : $this->get_templates_dir(),
		);

		// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
		if ( is_child_theme() ) {
			$file_paths[1] = trailingslashit( get_stylesheet_directory() ) . $theme_directory;
		}

		$file_paths = apply_filters( $this->filter_prefix . '_template_paths', $file_paths, $is_button_template );

		// sort the file paths based on priority
		ksort( $file_paths, SORT_NUMERIC );

		return array_map( 'trailingslashit', $file_paths );
	}

	protected function get_templates_dir(): string {
		$selected_template = get_option( 'dkpdf_selected_template', 'default/' );
		$template_sets = get_option( 'dkpdf_template_sets', array() );
		$template_key = rtrim( $selected_template, '/' );

		// Check if custom template set
		if ( isset( $template_sets[ $template_key ] ) && $template_sets[ $template_key ]['type'] === 'custom' ) {
			$upload_dir = wp_upload_dir();
			$custom_dir = $upload_dir['basedir'] . '/dkpdf-templates/' . $template_key;

			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'DK PDF: Using custom template set "%s" from path: %s (exists: %s)',
					$template_key,
					$custom_dir,
					file_exists( $custom_dir ) ? 'yes' : 'no'
				) );
			}

			return $custom_dir;
		}

		// Return plugin templates for core templates (append subdirectory for core templates)
		$plugin_dir = trailingslashit( $this->get_plugin_templates_dir() ) . $template_key;

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'DK PDF: Using core template from path: %s',
				$plugin_dir
			) );
		}

		return $plugin_dir;
	}

	/**
	 * Get the plugin's default templates directory
	 * Always returns the plugin directory, regardless of selected template set
	 *
	 * @return string
	 */
	protected function get_plugin_templates_dir(): string {
		return trailingslashit( $this->plugin_directory ) . $this->plugin_template_directory;
	}
}