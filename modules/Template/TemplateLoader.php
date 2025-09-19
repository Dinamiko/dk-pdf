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

		$selected_template = get_option( 'dkpdf_selected_template', '' );
		if ( ! empty( $selected_template ) ) {
			$this->template_subdirectories = array('default');
		}
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
		$template_paths = $this->get_template_paths();

		// Try to find a template file
		foreach ( $template_names as $template_name ) {
			// Trim off any slashes from the template name
			$template_name = ltrim( $template_name, '/' );

			// Try locating this template file by looping through the template paths
			foreach ( $template_paths as $template_path ) {
				if ( file_exists( $template_path . $template_name ) ) {
					$located = $template_path . $template_name;
					break 2;
				}
			}
		}

		if ( $load && $located ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

	protected function get_template_paths(): array {
		$theme_directory = trailingslashit( $this->theme_template_directory );

		$file_paths = array(
			10  => trailingslashit( get_template_directory() ) . $theme_directory,
			100 => $this->get_templates_dir(),
		);

		// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
		if ( is_child_theme() ) {
			$file_paths[1] = trailingslashit( get_stylesheet_directory() ) . $theme_directory;
		}

		$file_paths = apply_filters( $this->filter_prefix . '_template_paths', $file_paths );

		// sort the file paths based on priority
		ksort( $file_paths, SORT_NUMERIC );

		return array_map( 'trailingslashit', $file_paths );
	}

	protected function get_templates_dir(): string {
		return trailingslashit( $this->plugin_directory ) . $this->plugin_template_directory;
	}
}