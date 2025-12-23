<?php
declare(strict_types=1);

namespace Dinamiko\DKPDF\Admin;

class TemplateSetManager {

	private TemplateSetExtractor $extractor;

	public function __construct( TemplateSetExtractor $extractor ) {
		$this->extractor = $extractor;
	}

	/**
	 * Get the template sets directory path
	 *
	 * @return string
	 */
	private function getTemplateSetsDirectory(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/dkpdf-templates';
	}

	/**
	 * Get the directory path for a specific template set
	 *
	 * @param string $template_set_key Template set key
	 * @return string
	 */
	private function getTemplateSetDirectory( string $template_set_key ): string {
		return $this->getTemplateSetsDirectory() . '/' . $template_set_key;
	}

	/**
	 * Get all registered template sets
	 *
	 * @return array
	 */
	private function getTemplateSets(): array {
		return get_option( 'dkpdf_template_sets', array() );
	}

	/**
	 * Get metadata for a specific template set
	 *
	 * @param string $template_set_key Template set key
	 * @return array|null
	 */
	private function getTemplateSetMetadata( string $template_set_key ): ?array {
		$template_sets = $this->getTemplateSets();
		return $template_sets[ $template_set_key ] ?? null;
	}

	/**
	 * Get the currently selected template set
	 *
	 * @return string
	 */
	private function getSelectedTemplateSet(): string {
		$selected = get_option( 'dkpdf_selected_template', 'default/' );
		return rtrim( $selected, '/' );
	}

	/**
	 * Check if a template set is currently active
	 *
	 * @param string $template_set_key Template set key
	 * @return bool
	 */
	public function isActiveTemplateSet( string $template_set_key ): bool {
		return $this->getSelectedTemplateSet() === $template_set_key;
	}

	/**
	 * Generate a template set key from name
	 *
	 * @param string $name Template set name
	 * @return string
	 */
	private function generateTemplateSetKey( string $name ): string {
		// Convert to lowercase, replace spaces with hyphens, remove special characters
		$key = strtolower( $name );
		$key = preg_replace( '/[^a-z0-9\s\-_]/', '', $key );
		$key = preg_replace( '/[\s]+/', '-', $key );
		$key = trim( $key, '-_' );
		return $key;
	}

	/**
	 * List all available template sets
	 *
	 * @return array Array of template set objects
	 */
	public function listTemplateSets(): array {
		$template_sets = $this->getTemplateSets();
		$selected_template = $this->getSelectedTemplateSet();
		$result = array();

		foreach ( $template_sets as $key => $set ) {
			$result[] = array(
				'key' => $key,
				'name' => $set['name'],
				'description' => $set['description'] ?? '',
				'version' => $set['version'] ?? '1.0.0',
				'type' => $set['type'],
				'installed_date' => $set['installed_date'] ?? '',
				'selected' => $key === $selected_template,
			);
		}

		return $result;
	}

	/**
	 * Validate a template set file upload
	 *
	 * @param array $file WordPress file upload array
	 * @return true|string True on success, error message on failure
	 */
	private function validateTemplateSetFile( array $file ) {
		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return __( 'File upload failed. Please try again.', 'dkpdf' );
		}

		// Check file extension
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( $file_ext !== 'zip' ) {
			return __( 'Only ZIP files are supported.', 'dkpdf' );
		}

		// Check MIME type
		$allowed_mime_types = array( 'application/zip', 'application/x-zip-compressed', 'application/octet-stream' );
		$file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

		if ( ! in_array( $file['type'], $allowed_mime_types, true ) && $file_type['ext'] !== 'zip' ) {
			return __( 'Invalid file type. Only ZIP files are allowed.', 'dkpdf' );
		}

		// Check file size (max 10MB)
		$max_size = 10 * 1024 * 1024; // 10MB in bytes
		if ( $file['size'] > $max_size ) {
			return __( 'File size exceeds 10MB limit.', 'dkpdf' );
		}

		return true;
	}

	/**
	 * Validate template set structure
	 *
	 * @param string $directory Template set directory
	 * @return bool|string True on success, error message on failure
	 */
	public function validateTemplateSet( string $directory ) {
		// Parse metadata
		$metadata = $this->extractor->parseTemplateMetadata( $directory );
		if ( $metadata === null ) {
			return __( 'Invalid or missing template.json file.', 'dkpdf' );
		}

		// Check for subdirectories (should be flat structure)
		$files = scandir( $directory );
		foreach ( $files as $file ) {
			if ( $file === '.' || $file === '..' ) {
				continue;
			}
			$path = $directory . '/' . $file;
			if ( is_dir( $path ) ) {
				return __( 'Template set should not contain subdirectories.', 'dkpdf' );
			}
		}

		return true;
	}

	/**
	 * Upload a template set from a ZIP file
	 *
	 * @param array $file WordPress file upload array
	 * @return array Array with 'success' boolean and 'message' string
	 */
	public function uploadTemplateSet( array $file ): array {
		// Validate the file
		$validation = $this->validateTemplateSetFile( $file );
		if ( $validation !== true ) {
			return array(
				'success' => false,
				'message' => $validation,
			);
		}

		// Verify ZIP file
		if ( ! $this->extractor->verifyZipFile( $file['tmp_name'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid ZIP file.', 'dkpdf' ),
			);
		}

		// Create a temporary directory for extraction
		$temp_dir = get_temp_dir() . 'dkpdf-template-' . uniqid();
		wp_mkdir_p( $temp_dir );

		// Extract to temporary directory
		$extraction_result = $this->extractor->extractTemplateSet( $file['tmp_name'], $temp_dir );
		if ( $extraction_result !== true ) {
			$this->extractor->cleanupTemporaryFiles( $temp_dir );
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to extract template set: %s', 'dkpdf' ),
					$extraction_result
				),
			);
		}

		// Validate template set structure
		$validation = $this->validateTemplateSet( $temp_dir );
		if ( $validation !== true ) {
			$this->extractor->cleanupTemporaryFiles( $temp_dir );
			return array(
				'success' => false,
				'message' => $validation,
			);
		}

		// Parse metadata
		$metadata = $this->extractor->parseTemplateMetadata( $temp_dir );

		// Generate template set key
		$template_set_key = $this->generateTemplateSetKey( $metadata['name'] );

		// Check for duplicate keys
		$template_sets = $this->getTemplateSets();
		if ( isset( $template_sets[ $template_set_key ] ) ) {
			$this->extractor->cleanupTemporaryFiles( $temp_dir );
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: template set name */
					__( 'A template set with the name "%s" already exists.', 'dkpdf' ),
					$metadata['name']
				),
			);
		}

		// Create template sets directory if it doesn't exist
		$template_sets_dir = $this->getTemplateSetsDirectory();
		if ( ! file_exists( $template_sets_dir ) ) {
			wp_mkdir_p( $template_sets_dir );

			// Create .htaccess to prevent direct access
			$htaccess_file = $template_sets_dir . '/.htaccess';
			$htaccess_content = "<Files *.php>\n    Deny from all\n</Files>";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Move from temp directory to final location
		$final_dir = $this->getTemplateSetDirectory( $template_set_key );
		if ( file_exists( $final_dir ) ) {
			$this->extractor->cleanupTemporaryFiles( $temp_dir );
			return array(
				'success' => false,
				'message' => __( 'Template set directory already exists.', 'dkpdf' ),
			);
		}

		// Create final directory
		wp_mkdir_p( $final_dir );

		// Copy files from temp to final location
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$copy_result = copy_dir( $temp_dir, $final_dir );

		if ( is_wp_error( $copy_result ) ) {
			$this->extractor->cleanupTemporaryFiles( $temp_dir );
			$this->extractor->cleanupTemporaryFiles( $final_dir );
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to move template set to final location: %s', 'dkpdf' ),
					$copy_result->get_error_message()
				),
			);
		}

		// Clean up temp directory
		$this->extractor->cleanupTemporaryFiles( $temp_dir );

		// Set file permissions
		chmod( $final_dir, 0755 );
		$files = scandir( $final_dir );
		foreach ( $files as $file ) {
			if ( $file === '.' || $file === '..' ) {
				continue;
			}
			chmod( $final_dir . '/' . $file, 0644 );
		}

		// Add to template sets registry
		$template_sets[ $template_set_key ] = array(
			'name' => $metadata['name'],
			'description' => $metadata['description'],
			'version' => $metadata['version'],
			'type' => 'custom',
			'installed_date' => current_time( 'mysql' ),
		);

		update_option( 'dkpdf_template_sets', $template_sets );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: template set name */
				__( 'Template set "%s" uploaded successfully.', 'dkpdf' ),
				$metadata['name']
			),
			'template_set' => array(
				'key' => $template_set_key,
				'name' => $metadata['name'],
			),
		);
	}

	/**
	 * Delete a template set
	 *
	 * @param string $template_set_key Template set key
	 * @return array Array with 'success' boolean and 'message' string
	 */
	public function deleteTemplateSet( string $template_set_key ): array {
		$template_sets = $this->getTemplateSets();
		$template_set = $template_sets[ $template_set_key ] ?? null;

		// Check if template set exists
		if ( ! $template_set ) {
			return array(
				'success' => false,
				'message' => __( 'Template set not found.', 'dkpdf' ),
			);
		}

		// Prevent deletion of core templates
		if ( $template_set['type'] === 'core' ) {
			return array(
				'success' => false,
				'message' => __( 'Cannot delete core template sets.', 'dkpdf' ),
			);
		}

		// Check if template set is currently selected
		if ( $this->isActiveTemplateSet( $template_set_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Cannot delete the currently active template set. Please select a different template first.', 'dkpdf' ),
			);
		}

		// Delete template set directory
		$template_set_dir = $this->getTemplateSetDirectory( $template_set_key );
		if ( file_exists( $template_set_dir ) ) {
			if ( ! $this->extractor->cleanupTemporaryFiles( $template_set_dir ) ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to delete template set files.', 'dkpdf' ),
				);
			}
		}

		// Remove from registry
		unset( $template_sets[ $template_set_key ] );
		update_option( 'dkpdf_template_sets', $template_sets );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: template set name */
				__( 'Template set "%s" deleted successfully.', 'dkpdf' ),
				$template_set['name']
			),
		);
	}

	/**
	 * Migrate default template to template sets structure
	 *
	 * @return bool True if migration was performed
	 */
	public function migrateDefaultTemplate(): bool {
		// Check if migration already completed
		$migration_flag = get_option( 'dkpdf_template_sets_migrated', false );
		if ( $migration_flag ) {
			return false;
		}

		// Check current selected template
		$selected_template = get_option( 'dkpdf_selected_template', null );

		// Initialize template sets array
		$template_sets = array(
			'default' => array(
				'name' => __( 'Default Template', 'dkpdf' ),
				'description' => __( 'Built-in DK PDF template with basic styling', 'dkpdf' ),
				'version' => '1.0.0',
				'type' => 'core',
				'installed_date' => current_time( 'mysql' ),
			),
		);

		// Save template sets
		update_option( 'dkpdf_template_sets', $template_sets );

		// Normalize selected template to use trailing slash
		if ( $selected_template === null || $selected_template === '' ) {
			update_option( 'dkpdf_selected_template', 'default/' );
		} elseif ( substr( $selected_template, -1 ) !== '/' ) {
			update_option( 'dkpdf_selected_template', $selected_template . '/' );
		}

		// Set migration flag
		update_option( 'dkpdf_template_sets_migrated', true );

		// Log migration
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DK PDF: Migrated default template to template sets structure.' );
		}

		return true;
	}
}
