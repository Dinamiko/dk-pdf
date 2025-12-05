<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

use WP_Error;

class FontDownloader {
	private const TRANSIENT_KEY = 'dkpdf_font_download_progress';
	private const TRANSIENT_EXPIRATION = 300; // 5 minutes

	/**
	 * Check if fonts directory exists and contains fonts
	 */
	public function areFontsInstalled(): bool {
		// Check the flag that's set when core fonts are downloaded
		$flag = get_option( 'dkpdf_core_fonts_installed', null );

		// If flag is not set (null), check if core fonts exist (backward compatibility)
		if ( $flag === null ) {
			$fonts_dir = $this->getFontsDirectory();

			// Check for specific core font files (DejaVuSans is always in core fonts)
			$core_font_markers = array(
				$fonts_dir . '/DejaVuSans.ttf',
				$fonts_dir . '/DejaVuSansCondensed.ttf',
				$fonts_dir . '/DejaVuSerif.ttf'
			);

			// If at least 2 core font files exist, consider core fonts installed
			$found_count = 0;
			foreach ( $core_font_markers as $marker_file ) {
				if ( file_exists( $marker_file ) ) {
					$found_count++;
				}
			}

			if ( $found_count >= 2 ) {
				// Set the flag for future checks
				update_option( 'dkpdf_core_fonts_installed', true );
				return true;
			} else {
				// Set flag to false so we don't check files every time
				update_option( 'dkpdf_core_fonts_installed', false );
				return false;
			}
		}

		return (bool) $flag;
	}

	/**
	 * Get fonts directory path
	 */
	public function getFontsDirectory(): string {
		$upload_dir = wp_upload_dir();
		$fonts_dir = $upload_dir['basedir'] . '/dkpdf-fonts';

		return apply_filters( 'dkpdf_fonts_directory', $fonts_dir );
	}

	/**
	 * Download and extract fonts from GitHub
	 *
	 * @param string $github_url GitHub repository ZIP URL
	 * @return array Result array with success status and message
	 */
	public function downloadFonts( string $github_url ): array {
		global $wp_filesystem;

		// Initialize WordPress filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Validate GitHub URL
		if ( empty( $github_url ) ) {
			$github_url = apply_filters( 'dkpdf_fonts_github_url', 'https://github.com/Dinamiko/mpdf-ttfonts/archive/refs/heads/main.zip' );
		}

		$github_url = esc_url_raw( $github_url );

		if ( empty( $github_url ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid GitHub URL', 'dkpdf' )
			);
		}

		do_action( 'dkpdf_before_fonts_download', $github_url );

		// Set initial progress
		$this->updateProgress( 0 );

		// Get fonts directory
		$fonts_dir = $this->getFontsDirectory();

		// Ensure uploads directory exists
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Upload directory error: %s', 'dkpdf' ), $upload_dir['error'] )
			);
		}

		// Create fonts directory if it doesn't exist
		if ( ! file_exists( $fonts_dir ) ) {
			wp_mkdir_p( $fonts_dir );

			// Verify directory was created
			if ( ! file_exists( $fonts_dir ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						__( 'Failed to create fonts directory at %s. Please check directory permissions.', 'dkpdf' ),
						$fonts_dir
					)
				);
			}
		}

		// Check if directory is writable
		if ( ! is_writable( $fonts_dir ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Fonts directory is not writable: %s. Please check permissions.', 'dkpdf' ),
					$fonts_dir
				)
			);
		}

		// Download progress update
		$this->updateProgress( 10 );

		// Download the ZIP file
		$temp_file = download_url( $github_url );

		if ( is_wp_error( $temp_file ) ) {
			do_action( 'dkpdf_fonts_download_error', $temp_file->get_error_message() );
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Failed to download fonts: %s. You can manually upload fonts to: %s', 'dkpdf' ),
					$temp_file->get_error_message(),
					$fonts_dir
				),
				'fonts_directory' => $fonts_dir
			);
		}

		// Download complete
		$this->updateProgress( 50 );

		// Verify ZIP file
		if ( ! $this->verifyZipFile( $temp_file ) ) {
			unlink( $temp_file );
			do_action( 'dkpdf_fonts_download_error', 'Invalid ZIP file' );
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Downloaded file is not a valid ZIP archive. You can manually upload fonts to: %s', 'dkpdf' ),
					$fonts_dir
				),
				'fonts_directory' => $fonts_dir
			);
		}

		// Extract fonts
		$this->updateProgress( 60 );

		$extract_result = $this->extractFonts( $temp_file, $fonts_dir );

		// Delete ZIP file after extraction
		if ( apply_filters( 'dkpdf_fonts_delete_zip_after_extract', true ) ) {
			unlink( $temp_file );
		}

		if ( ! $extract_result ) {
			do_action( 'dkpdf_fonts_download_error', 'Extraction failed' );
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Failed to extract fonts from archive. You can manually upload fonts to: %s', 'dkpdf' ),
					$fonts_dir
				),
				'fonts_directory' => $fonts_dir
			);
		}

		// Complete
		$this->updateProgress( 100 );

		// Clear progress transient
		delete_transient( self::TRANSIENT_KEY );

		// Set flag to indicate core fonts are installed
		update_option( 'dkpdf_core_fonts_installed', true );

		do_action( 'dkpdf_after_fonts_download', $fonts_dir );

		return array(
			'success' => true,
			'message' => __( 'Fonts downloaded successfully', 'dkpdf' ),
			'fonts_directory' => $fonts_dir
		);
	}

	/**
	 * Get current download progress (0-100)
	 */
	public function getDownloadProgress(): int {
		$progress = get_transient( self::TRANSIENT_KEY );
		return $progress !== false ? (int) $progress : 0;
	}

	/**
	 * Update download progress
	 *
	 * @param int $progress Progress percentage (0-100)
	 */
	private function updateProgress( int $progress ): void {
		set_transient( self::TRANSIENT_KEY, $progress, self::TRANSIENT_EXPIRATION );
	}

	/**
	 * Verify ZIP file integrity
	 *
	 * @param string $file_path Path to ZIP file
	 * @return bool True if valid ZIP file
	 */
	private function verifyZipFile( string $file_path ): bool {
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
	 * Extract ZIP file to fonts directory
	 *
	 * @param string $zip_path Path to ZIP file
	 * @param string $destination Destination directory
	 * @return bool True on success
	 */
	private function extractFonts( string $zip_path, string $destination ): bool {
		// Use WordPress unzip_file function
		$result = unzip_file( $zip_path, $destination );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		$this->updateProgress( 80 );

		// GitHub ZIP files are extracted into a subdirectory
		// We need to move fonts from subdirectory to main fonts directory
		$extracted_dirs = glob( $destination . '/*', GLOB_ONLYDIR );

		if ( ! empty( $extracted_dirs ) ) {
			$source_dir = $extracted_dirs[0];

			// Move all .ttf files from subdirectory to main directory
			$font_files = glob( $source_dir . '/*.ttf' );

			if ( ! empty( $font_files ) ) {
				foreach ( $font_files as $font_file ) {
					$filename = basename( $font_file );
					$dest_file = $destination . '/' . $filename;

					// Move file
					rename( $font_file, $dest_file );
				}

				// Remove empty subdirectory and any remaining files
				$this->removeDirectory( $source_dir );
			}
		}

		$this->updateProgress( 90 );

		return true;
	}

	/**
	 * Recursively remove directory and its contents
	 *
	 * @param string $dir Directory path
	 * @return bool True on success
	 */
	private function removeDirectory( string $dir ): bool {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );

		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			is_dir( $path ) ? $this->removeDirectory( $path ) : unlink( $path );
		}

		return rmdir( $dir );
	}
}
