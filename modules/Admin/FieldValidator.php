<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

/**
 * Validates and sanitizes form field data
 */
class FieldValidator {

	/**
	 * Validate and sanitize field value
	 *
	 * @param mixed  $data Submitted value
	 * @param string $type Type of field to validate
	 * @return mixed Validated and sanitized value
	 */
	public function validate_field( $data = '', string $type = 'text' ) {
		switch ( $type ) {
			case 'text':
			case 'hidden':
			case 'text_secret':
				$data = sanitize_text_field( $data );
				break;

			case 'url':
				$data = esc_url_raw( $data );
				break;

			case 'email':
				$data = sanitize_email( $data );
				// Validate email format
				if ( ! is_email( $data ) ) {
					$data = '';
				}
				break;

			case 'number':
				$data = floatval( $data );
				break;

			case 'password':
				// Don't sanitize passwords to allow special characters
				// They should be hashed before storage anyway
				$data = trim( $data );
				break;

			case 'textarea':
			case 'textarea_code':
				$data = sanitize_textarea_field( $data );
				break;

			case 'checkbox':
				$data = ( 'on' === $data ) ? 'on' : '';
				break;

			case 'radio':
			case 'select':
				$data = sanitize_text_field( $data );
				break;

			case 'image':
				// Image fields store attachment IDs
				$data = absint( $data );
				break;

			case 'color':
				// Sanitize hex color
				$data = sanitize_hex_color( $data );
				break;

			default:
				// Default to text sanitization for unknown types
				$data = sanitize_text_field( $data );
				break;
		}

		return $data;
	}
}
