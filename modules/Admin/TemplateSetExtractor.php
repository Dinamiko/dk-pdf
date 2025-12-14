<?php
declare(strict_types=1);

namespace Dinamiko\DKPDF\Admin;

class TemplateSetExtractor {

	/**
	 * Verify ZIP file integrity
	 *
	 * @param string $file_path Path to ZIP file
	 * @return bool True if valid ZIP file
	 */
	public function verifyZipFile( string $file_path ): bool {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		// Check file signature (ZIP files start with PK)
		$file_handle = fopen( $file_path, 'rb' );
		if ( ! $file_handle ) {
			return false;
		}

		$signature = fread( $file_handle, 2 );
		fclose( $file_handle );

		return $signature === 'PK';
	}

	/**
	 * Extract template set from ZIP file
	 *
	 * @param string $zip_path Path to ZIP file
	 * @param string $destination Destination directory
	 * @return bool|string True on success, error message on failure
	 */
	public function extractTemplateSet( string $zip_path, string $destination ) {
		global $wp_filesystem;

		// Load WordPress filesystem functions if not available
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize WordPress filesystem
		WP_Filesystem();

		// Use WordPress unzip_file function
		$result = unzip_file( $zip_path, $destination );

		if ( is_wp_error( $result ) ) {
			// Log error for debugging
			$error_message = $result->get_error_message();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'DK PDF Template Set Extraction Error: ' . $error_message );
			}
			return $error_message;
		}

		return true;
	}

	/**
	 * Parse template metadata from template.json file
	 *
	 * @param string $directory Template set directory
	 * @return array|null Array of metadata or null on failure
	 */
	public function parseTemplateMetadata( string $directory ): ?array {
		$metadata_file = trailingslashit( $directory ) . 'template.json';

		if ( ! file_exists( $metadata_file ) ) {
			return null;
		}

		$content = file_get_contents( $metadata_file );
		if ( $content === false ) {
			return null;
		}

		$metadata = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		// Validate required metadata fields
		$required_fields = array( 'name', 'version', 'author', 'required_files' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $metadata[ $field ] ) ) {
				return null;
			}
		}

		// Set defaults for optional fields
		$metadata['description'] = $metadata['description'] ?? '';
		$metadata['author_uri'] = $metadata['author_uri'] ?? '';
		$metadata['minimum_dkpdf_version'] = $metadata['minimum_dkpdf_version'] ?? '2.3.0';
		$metadata['tags'] = $metadata['tags'] ?? array();

		return $metadata;
	}

	/**
	 * Validate that all required files exist in the template set
	 *
	 * @param string $directory Template set directory
	 * @param array $required_files Array of required file names
	 * @return bool True if all required files exist
	 */
	public function validateRequiredFiles( string $directory, array $required_files ): bool {
		$directory = trailingslashit( $directory );

		foreach ( $required_files as $file ) {
			if ( ! file_exists( $directory . $file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Cleanup temporary files and directories
	 *
	 * @param string $temp_dir Temporary directory path
	 * @return bool True on success
	 */
	public function cleanupTemporaryFiles( string $temp_dir ): bool {
		if ( ! is_dir( $temp_dir ) ) {
			return false;
		}

		$files = array_diff( scandir( $temp_dir ), array( '.', '..' ) );

		foreach ( $files as $file ) {
			$path = $temp_dir . '/' . $file;
			if ( is_dir( $path ) ) {
				$this->cleanupTemporaryFiles( $path );
			} else {
				unlink( $path );
			}
		}

		return rmdir( $temp_dir );
	}
}
