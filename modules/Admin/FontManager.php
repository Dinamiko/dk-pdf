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
     * Returns structured font families or legacy array
     *
     * @return array
     */
    private function getCustomFontsList(): array {
        return get_option( 'dkpdf_custom_fonts', array() );
    }

    /**
     * Get structured font families
     * Returns the font families data structure
     *
     * @return array
     */
    private function getFontFamilies(): array {
        $custom_fonts = $this->getCustomFontsList();

        // Check if already in new format (associative array with family_name)
        if ( ! empty( $custom_fonts ) && ! isset( $custom_fonts[0] ) ) {
            // Verify it's the new format by checking for family_name in first entry
            $first_family = reset( $custom_fonts );
            if ( isset( $first_family['family_name'] ) ) {
                return $custom_fonts;
            }
        }

        // Return empty array if old format (migration will handle it)
        return array();
    }

    /**
     * Get a specific font family by its key
     *
     * @param string $font_key Font family key (lowercase)
     * @return array|null Family data or null if not found
     */
    private function getFamilyByKey( string $font_key ): ?array {
        $families = $this->getFontFamilies();
        return $families[ $font_key ] ?? null;
    }

    /**
     * Generate a font key from family name
     * Converts family name to lowercase and removes spaces
     *
     * @param string $family_name Family name
     * @return string Font key for mPDF
     */
    private function generateFontKey( string $family_name ): string {
        return strtolower( str_replace( ' ', '', $family_name ) );
    }

    /**
     * Extract family name from filename
     * Removes variant suffixes to get base family name
     *
     * @param string $filename Font filename (with or without .ttf extension)
     * @return string Family name
     */
    private function extractFamilyName( string $filename ): string {
        // Remove .ttf extension if present
        $name = preg_replace( '/\.ttf$/i', '', $filename );

        // Remove variant suffixes
        $patterns = array(
            '/-BoldItalic$/i',
            '/-BoldOblique$/i',
            '/-Bold$/i',
            '/-Italic$/i',
            '/-Oblique$/i',
            '/-Regular$/i',
            '/-BI$/i',
            '/-B$/i',
            '/-I$/i',
            '/-R$/i',
        );

        foreach ( $patterns as $pattern ) {
            $name = preg_replace( $pattern, '', $name );
        }

        return $name;
    }

    /**
     * Detect variant type from filename
     * Returns R, B, I, or BI
     *
     * @param string $filename Font filename (with or without .ttf extension)
     * @return string Variant code (R, B, I, BI)
     */
    private function detectVariant( string $filename ): string {
        // Remove .ttf extension if present
        $name = preg_replace( '/\.ttf$/i', '', $filename );

        // Check for BoldItalic first (must come before Bold and Italic)
        if ( preg_match( '/(BoldItalic|BoldOblique|-BI)$/i', $name ) ) {
            return 'BI';
        }

        // Check for Bold
        if ( preg_match( '/(Bold|-B)$/i', $name ) ) {
            return 'B';
        }

        // Check for Italic
        if ( preg_match( '/(Italic|Oblique|-I)$/i', $name ) ) {
            return 'I';
        }

        // Default to Regular
        return 'R';
    }

    /**
     * Migrate old font list format to font families structure
     * Runs automatically on first load if old format detected
     *
     * @return bool True if migration was performed, false if already migrated or no fonts
     */
    public function migrateToFontFamilies(): bool {
        $custom_fonts = $this->getCustomFontsList();

        // Check if already in new format or empty
        if ( empty( $custom_fonts ) ) {
            return false;
        }

        // Check if already migrated (new format has string keys with family_name)
        if ( ! isset( $custom_fonts[0] ) ) {
            $first_family = reset( $custom_fonts );
            if ( isset( $first_family['family_name'] ) ) {
                return false; // Already migrated
            }
        }

        // Create backup of old format
        update_option( 'dkpdf_custom_fonts_backup_v1', $custom_fonts );

        // Migrate to new format
        $new_format = array();
        $fonts_dir = $this->getFontsDirectory();
        $family_names_used = array();

        foreach ( $custom_fonts as $font_name ) {
            // Check if font file still exists
            $font_file = $fonts_dir . '/' . $font_name . '.ttf';
            if ( ! file_exists( $font_file ) ) {
                continue; // Skip missing files
            }

            // Extract family name from filename
            $family_name = $this->extractFamilyName( $font_name );
            $font_key = $this->generateFontKey( $family_name );

            // Handle duplicate family names
            if ( isset( $family_names_used[ $font_key ] ) ) {
                $counter = 2;
                while ( isset( $family_names_used[ $font_key . $counter ] ) ) {
                    $counter++;
                }
                $family_name .= ' ' . $counter;
                $font_key = $this->generateFontKey( $family_name );
            }

            $family_names_used[ $font_key ] = true;

            // Detect variant
            $variant = $this->detectVariant( $font_name );

            // Add to new format
            $new_format[ $font_key ] = array(
                'family_name' => $family_name,
                'variants' => array(
                    $variant => $font_name . '.ttf',
                ),
            );
        }

        // Save new format
        update_option( 'dkpdf_custom_fonts', $new_format );

        // Log migration
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'DK PDF: Migrated ' . count( $custom_fonts ) . ' fonts to family structure. Backup saved as dkpdf_custom_fonts_backup_v1.' );
        }

        return true;
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
        return get_option( 'dkpdf_default_font', 'DejaVuSans' );
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
     * Returns font families with variant information
     *
     * @return array Array of font family objects
     */
    public function listFonts(): array {
        $fonts_dir = $this->getFontsDirectory();
        $fonts     = array();

        if ( ! is_dir( $fonts_dir ) ) {
            return $fonts;
        }

        $selected_font = $this->getSelectedFont();
        $families = $this->getFontFamilies();

        // Add custom font families
        foreach ( $families as $font_key => $family ) {
            $variants_info = array(
                'R' => array( 'file' => null, 'exists' => false ),
                'B' => array( 'file' => null, 'exists' => false ),
                'I' => array( 'file' => null, 'exists' => false ),
                'BI' => array( 'file' => null, 'exists' => false ),
            );

            // Fill in existing variants
            foreach ( $family['variants'] as $variant_key => $variant_file ) {
                $variants_info[ $variant_key ] = array(
                    'file' => $variant_file,
                    'exists' => file_exists( $fonts_dir . '/' . $variant_file ),
                );
            }

            $is_complete = isset( $family['variants']['R'] );
            $is_selected = ( strtolower( $font_key ) === strtolower( $selected_font ) ) ||
                           ( strtolower( $family['family_name'] ) === strtolower( $selected_font ) );

            $fonts[] = array(
                'key'         => $font_key,
                'family_name' => $family['family_name'],
                'type'        => 'custom',
                'variants'    => $variants_info,
                'selected'    => $is_selected,
                'complete'    => $is_complete,
                'category'    => null,
            );
        }

        // Add core fonts (from filesystem but not in families)
        $font_files = glob( $fonts_dir . '/*.ttf' );
        if ( $font_files ) {
            foreach ( $font_files as $font_file ) {
                $font_name = basename( $font_file, '.ttf' );

                // Skip if this file belongs to a custom family
                $belongs_to_family = false;
                foreach ( $families as $family ) {
                    if ( in_array( basename( $font_file ), $family['variants'], true ) ) {
                        $belongs_to_family = true;
                        break;
                    }
                }

                if ( $belongs_to_family ) {
                    continue;
                }

                // This is a core font
                $font_key = strtolower( str_replace( ' ', '', $font_name ) );

                $variants_info = array(
                    'R' => array( 'file' => basename( $font_file ), 'exists' => true ),
                    'B' => array( 'file' => null, 'exists' => false ),
                    'I' => array( 'file' => null, 'exists' => false ),
                    'BI' => array( 'file' => null, 'exists' => false ),
                );

                $fonts[] = array(
                    'key'         => $font_key,
                    'family_name' => $font_name,
                    'type'        => 'core',
                    'variants'    => $variants_info,
                    'selected'    => $font_name === $selected_font || $font_key === strtolower( $selected_font ),
                    'complete'    => true,
                    'category'    => $this->getFontCategory( $font_name ),
                );
            }
        }

        // Sort fonts: custom fonts first, then core fonts, then alphabetically within each type
        usort( $fonts, function( $a, $b ) {
            if ( $a['type'] !== $b['type'] ) {
                return $a['type'] === 'custom' ? -1 : 1;
            }
            return strcmp( $a['family_name'], $b['family_name'] );
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
     * Upload a font file as part of a font family
     *
     * @param array $file WordPress file upload array
     * @param string $family_name Optional family name (auto-detected if empty)
     * @param string $variant Optional variant type (auto-detected if empty)
     * @return array Array with 'success' boolean and 'message' string
     */
    public function uploadFont( array $file, string $family_name = '', string $variant = '' ): array {
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

        // Auto-detect family name if not provided
        if ( empty( $family_name ) ) {
            $family_name = $this->extractFamilyName( $filename );
        }

        // Validate family name
        $family_name = trim( $family_name );
        if ( empty( $family_name ) || strlen( $family_name ) < 2 || strlen( $family_name ) > 50 ) {
            return array(
                'success' => false,
                'message' => __( 'Family name must be between 2 and 50 characters.', 'dkpdf' ),
            );
        }

        if ( ! preg_match( '/^[a-zA-Z0-9\s]+$/', $family_name ) ) {
            return array(
                'success' => false,
                'message' => __( 'Family name can only contain letters, numbers, and spaces.', 'dkpdf' ),
            );
        }

        // Auto-detect variant if not provided
        if ( empty( $variant ) ) {
            $variant = $this->detectVariant( $filename );
        }

        // Validate variant
        if ( ! in_array( $variant, array( 'R', 'B', 'I', 'BI' ), true ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid variant type. Must be R, B, I, or BI.', 'dkpdf' ),
            );
        }

        // Generate font key
        $font_key = $this->generateFontKey( $family_name );

        // Get existing family data
        $families = $this->getFontFamilies();
        $family = $families[ $font_key ] ?? null;

        // Check if variant already exists in this family
        if ( $family && isset( $family['variants'][ $variant ] ) ) {
            return array(
                'success' => false,
                'message' => sprintf(
                    /* translators: 1: variant name, 2: family name */
                    __( 'Variant "%1$s" already exists for family "%2$s".', 'dkpdf' ),
                    $variant,
                    $family_name
                ),
            );
        }

        // Create fonts directory if it doesn't exist
        $fonts_dir = $this->getFontsDirectory();
        if ( ! file_exists( $fonts_dir ) ) {
            wp_mkdir_p( $fonts_dir );
        }

        // Check if file already exists
        $target_path = $fonts_dir . '/' . $filename;
        if ( file_exists( $target_path ) ) {
            return array(
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: filename */
                    __( 'File "%s" already exists.', 'dkpdf' ),
                    $filename
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

        // Update family data
        if ( ! $family ) {
            // Create new family
            $family = array(
                'family_name' => $family_name,
                'variants' => array(),
            );
        }

        // Add variant to family
        $family['variants'][ $variant ] = $filename;

        // Update families array
        $families[ $font_key ] = $family;

        // Save to database
        update_option( 'dkpdf_custom_fonts', $families );

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: 1: variant name, 2: family name */
                __( 'Variant "%1$s" for family "%2$s" uploaded successfully.', 'dkpdf' ),
                $variant,
                $family_name
            ),
            'family' => array(
                'key' => $font_key,
                'family_name' => $family_name,
                'variant' => $variant,
                'file' => $filename,
            ),
        );
    }

    /**
     * Delete a font family or a specific variant
     *
     * @param string $font_key Font family key
     * @param string $variant Optional variant to delete (if empty, delete entire family)
     * @return array Array with 'success' boolean and 'message' string
     */
    public function deleteFont( string $font_key, string $variant = '' ): array {
        $families = $this->getFontFamilies();
        $family = $families[ $font_key ] ?? null;

        // Check if family exists
        if ( ! $family ) {
            return array(
                'success' => false,
                'message' => __( 'Font family not found.', 'dkpdf' ),
            );
        }

        // Check if family is currently selected
        $selected_font = $this->getSelectedFont();
        if ( strtolower( $font_key ) === strtolower( $selected_font ) || strtolower( $family['family_name'] ) === strtolower( $selected_font ) ) {
            return array(
                'success' => false,
                'message' => __( 'Cannot delete the currently selected font family. Please select a different font first.', 'dkpdf' ),
            );
        }

        $fonts_dir = $this->getFontsDirectory();

        // Delete specific variant
        if ( ! empty( $variant ) ) {
            // Check if variant exists
            if ( ! isset( $family['variants'][ $variant ] ) ) {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s: variant name */
                        __( 'Variant "%s" not found in this family.', 'dkpdf' ),
                        $variant
                    ),
                );
            }

            $font_file = $fonts_dir . '/' . $family['variants'][ $variant ];

            // Delete the file
            if ( file_exists( $font_file ) && ! unlink( $font_file ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'Failed to delete font file.', 'dkpdf' ),
                );
            }

            // Remove variant from family
            unset( $family['variants'][ $variant ] );

            // If no variants left, remove entire family
            if ( empty( $family['variants'] ) ) {
                unset( $families[ $font_key ] );
            } else {
                $families[ $font_key ] = $family;
            }

            // Save to database
            update_option( 'dkpdf_custom_fonts', $families );

            return array(
                'success' => true,
                'message' => sprintf(
                    /* translators: 1: variant name, 2: family name */
                    __( 'Variant "%1$s" deleted from family "%2$s" successfully.', 'dkpdf' ),
                    $variant,
                    $family['family_name']
                ),
            );
        }

        // Delete entire family
        foreach ( $family['variants'] as $variant_file ) {
            $font_file = $fonts_dir . '/' . $variant_file;
            if ( file_exists( $font_file ) ) {
                unlink( $font_file );
            }
        }

        // Remove family from database
        unset( $families[ $font_key ] );
        update_option( 'dkpdf_custom_fonts', $families );

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: %s: family name */
                __( 'Font family "%s" deleted successfully.', 'dkpdf' ),
                $family['family_name']
            ),
        );
    }
}
