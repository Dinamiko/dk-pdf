<?php

if ( ! class_exists( 'DKPDF_Template_Loader' ) )  {

	class DKPDF_Template_Loader {

		protected $filter_prefix = 'dkpdf';
		protected $theme_template_directory = 'dkpdf';
		protected $plugin_directory = DKPDF_PLUGIN_DIR;
		protected $plugin_template_directory = 'templates';

		public function get_template_part( $slug, $name = null, $load = true ) {

			// Execute code for this part
			do_action( 'get_template_part_' . $slug, $slug, $name );

			$templates = $this->get_template_file_names( $slug, $name );

			return $this->locate_template( $templates, $load, false );

		}

		protected function get_template_file_names( $slug, $name ) {

			$templates = array();

			if ( isset( $name ) ) {

				$templates[] = $slug . '-' . $name . '.php';

			}

			$templates[] = $slug . '.php';

			return apply_filters( $this->filter_prefix . '_get_template_part', $templates, $slug, $name );

		}

		public function locate_template( $template_names, $load = false, $require_once = true ) {

			// No file found yet
			$located = false;

			// Remove empty entries
			$template_names = array_filter( (array) $template_names );
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

		protected function get_template_paths() {

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

		protected function get_templates_dir() {

			return trailingslashit( $this->plugin_directory ) . $this->plugin_template_directory;

		}
	}
}