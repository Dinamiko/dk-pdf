<?php

declare(strict_types=1);

namespace Dinamiko\DKPDF\Admin;

/**
 * Font Manager Service
 *
 * Handles font upload, deletion, and listing operations
 */
class FontManager {

    /**
     * Get the fonts directory path
     *
     * @return string
     */
    private function getFontsDirectory(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/dkpdf-fonts';
    }

    /**
     * Get the fonts directory URL
     *
     * @return string
     */
    private function getFontsDirectoryUrl(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/dkpdf-fonts';
    }

    /**
     * Get list of custom uploaded fonts
     *
     * @return array
     */
    private function getCustomFontsList(): array {
        return get_option( 'dkpdf_custom_fonts', array() );
    }

    /**
     * Add a font to the custom fonts list
     *
     * @param string $font_name Font name (without extension)
     * @return void
     */
    private function addToCustomFontsList( string $font_name ): void {
        $custom_fonts = $this->getCustomFontsList();

        if ( ! in_array( $font_name, $custom_fonts, true ) ) {
            $custom_fonts[] = $font_name;
            update_option( 'dkpdf_custom_fonts', $custom_fonts );
        }
    }

    /**
     * Remove a font from the custom fonts list
     *
     * @param string $font_name Font name (without extension)
     * @return void
     */
    private function removeFromCustomFontsList( string $font_name ): void {
        $custom_fonts = $this->getCustomFontsList();
        $key = array_search( $font_name, $custom_fonts, true );

        if ( $key !== false ) {
            unset( $custom_fonts[ $key ] );
            update_option( 'dkpdf_custom_fonts', array_values( $custom_fonts ) );
        }
    }

    /**
     * Get the currently selected font
     *
     * @return string
     */
    private function getSelectedFont(): string {
        return get_option( 'dkpdf_font_downloader', 'DejaVuSans' );
    }

    /**
     * Check if a font is currently selected
     *
     * @param string $font_name Font name (without extension)
     * @return bool
     */
    public function isSelectedFont( string $font_name ): bool {
        return $this->getSelectedFont() === $font_name;
    }

    /**
     * Check if a font is a core font (downloaded from GitHub)
     * Core fonts are those NOT in the custom uploaded fonts list
     *
     * @param string $font_name Font name (without extension)
     * @return bool
     */
    private function isCoreFont( string $font_name ): bool {
        $custom_fonts = $this->getCustomFontsList();
        return ! in_array( $font_name, $custom_fonts, true );
    }

    /**
     * Get the category for a font (only for core fonts)
     *
     * @param string $font_name Font name (without extension)
     * @return string|null Category name or null for custom fonts
     */
    private function getFontCategory( string $font_name ): ?string {
        // Normalize font name for comparison (remove hyphens, spaces, and lowercase)
        $font_lower = strtolower( str_replace( array( '-', ' ' ), '', $font_name ) );

        // Unicode fonts
        $unicode_fonts = array(
            'dejavusans',
            'dejavusanscondensed',
            'dejavusansmono',
            'dejavuserif',
            'dejavuserifcondensed',
            'freesans',
            'freeserif',
            'freemono',
            'mph2bdamase',
        );

        // Indic fonts
        $indic_fonts = array(
            'lohitkannada',
            'pothana2000',
        );

        // Arabic fonts
        $arabic_fonts = array(
            'xbriyaz',
            'lateef',
            'kfgqpcuthmantahanaskh',
        );

        // Chinese, Japanese, Korean fonts
        $cjk_fonts = array(
            'sunexta',
            'unbatang',
        );

        // Check category using exact match
        if ( in_array( $font_lower, $unicode_fonts, true ) ) {
            return 'Unicode';
        }

        if ( in_array( $font_lower, $indic_fonts, true ) ) {
            return 'Indic';
        }

        if ( in_array( $font_lower, $arabic_fonts, true ) ) {
            return 'Arabic';
        }

        if ( in_array( $font_lower, $cjk_fonts, true ) ) {
            return 'CJK';
        }

        // Fallback: Check using partial matches for common font families
        if ( strpos( $font_lower, 'dejavu' ) !== false || strpos( $font_lower, 'free' ) !== false ) {
            return 'Unicode';
        }

        if ( strpos( $font_lower, 'lohit' ) !== false || strpos( $font_lower, 'pothana' ) !== false ) {
            return 'Indic';
        }

        if ( strpos( $font_lower, 'riyaz' ) !== false || strpos( $font_lower, 'lateef' ) !== false || strpos( $font_lower, 'kfgqpc' ) !== false ) {
            return 'Arabic';
        }

        if ( strpos( $font_lower, 'sun' ) !== false || strpos( $font_lower, 'unbatang' ) !== false ) {
            return 'CJK';
        }

        // No specific category - will just show "Core" badge
        return null;
    }

    /**
     * List all available fonts with metadata
     *
     * @return array Array of font objects with name, type, and selected status
     */
    public function listFonts(): array {
        $fonts_dir = $this->getFontsDirectory();
        $fonts     = array();

        if ( ! is_dir( $fonts_dir ) ) {
            return $fonts;
        }

        $font_files    = glob( $fonts_dir . '/*.ttf' );
        $selected_font = $this->getSelectedFont();

        if ( ! $font_files ) {
            return $fonts;
        }

        foreach ( $font_files as $font_file ) {
            $font_name = basename( $font_file, '.ttf' );
            $is_core   = $this->isCoreFont( $font_name );

            $fonts[] = array(
                'name'     => $font_name,
                'type'     => $is_core ? 'core' : 'custom',
                'category' => $is_core ? $this->getFontCategory( $font_name ) : null,
                'selected' => $font_name === $selected_font,
                'file'     => basename( $font_file ),
            );
        }

        // Sort fonts: selected first, then custom fonts, then core fonts, then alphabetically
        usort( $fonts, function( $a, $b ) {
            if ( $a['selected'] !== $b['selected'] ) {
                return $b['selected'] ? 1 : -1;
            }
            if ( $a['type'] !== $b['type'] ) {
                return $a['type'] === 'custom' ? -1 : 1;
            }
            return strcmp( $a['name'], $b['name'] );
        });

        return $fonts;
    }

    /**
     * Validate a font file
     *
     * @param array $file WordPress file upload array
     * @return true|string True on success, error message on failure
     */
    private function validateFontFile( array $file ) {
        // Check for upload errors
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return __( 'File upload failed. Please try again.', 'dkpdf' );
        }

        // Check file extension
        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( $file_ext !== 'ttf' ) {
            return __( 'Only TTF font files are supported.', 'dkpdf' );
        }

        // Check MIME type
        $allowed_mime_types = array( 'application/x-font-ttf', 'font/ttf', 'application/octet-stream' );
        $file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

        if ( ! in_array( $file['type'], $allowed_mime_types, true ) && $file_type['ext'] !== 'ttf' ) {
            return __( 'Invalid file type. Only TTF fonts are allowed.', 'dkpdf' );
        }

        // Check file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ( $file['size'] > $max_size ) {
            return __( 'File size exceeds 5MB limit.', 'dkpdf' );
        }

        return true;
    }

    /**
     * Upload a font file
     *
     * @param array $file WordPress file upload array
     * @return array Array with 'success' boolean and 'message' string
     */
    public function uploadFont( array $file ): array {
        // Validate the file
        $validation = $this->validateFontFile( $file );
        if ( $validation !== true ) {
            return array(
                'success' => false,
                'message' => $validation,
            );
        }

        // Sanitize filename and normalize extension to lowercase
        $filename = sanitize_file_name( $file['name'] );
        $filename = preg_replace( '/\.ttf$/i', '.ttf', $filename );
        $font_name = basename( $filename, '.ttf' );

        // Create fonts directory if it doesn't exist
        $fonts_dir = $this->getFontsDirectory();
        if ( ! file_exists( $fonts_dir ) ) {
            wp_mkdir_p( $fonts_dir );
        }

        // Check if font already exists
        $target_path = $fonts_dir . '/' . $filename;
        if ( file_exists( $target_path ) ) {
            return array(
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: font name */
                    __( 'Font "%s" already exists.', 'dkpdf' ),
                    $font_name
                ),
            );
        }

        // Move uploaded file to fonts directory
        if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to save font file.', 'dkpdf' ),
            );
        }

        // Add to custom fonts list to track it as a custom uploaded font
        $this->addToCustomFontsList( $font_name );

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: %s: font name */
                __( 'Font "%s" uploaded successfully.', 'dkpdf' ),
                $font_name
            ),
            'font'    => array(
                'name'     => $font_name,
                'type'     => 'custom',
                'selected' => false,
                'file'     => $filename,
            ),
        );
    }

    /**
     * Delete a font file
     *
     * @param string $font_name Font name (without extension)
     * @return array Array with 'success' boolean and 'message' string
     */
    public function deleteFont( string $font_name ): array {
        // Check if font is currently selected
        if ( $this->isSelectedFont( $font_name ) ) {
            return array(
                'success' => false,
                'message' => __( 'Cannot delete the currently selected font. Please select a different font first.', 'dkpdf' ),
            );
        }

        $fonts_dir = $this->getFontsDirectory();
        $font_file = $fonts_dir . '/' . $font_name . '.ttf';

        // Check if file exists
        if ( ! file_exists( $font_file ) ) {
            return array(
                'success' => false,
                'message' => __( 'Font file not found.', 'dkpdf' ),
            );
        }

        // Delete the file
        if ( ! unlink( $font_file ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to delete font file.', 'dkpdf' ),
            );
        }

        // Remove from custom fonts list if it was a custom uploaded font
        $this->removeFromCustomFontsList( $font_name );

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: %s: font name */
                __( 'Font "%s" deleted successfully.', 'dkpdf' ),
                $font_name
            ),
        );
    }
}
