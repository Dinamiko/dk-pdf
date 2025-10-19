<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

/**
 * Renders form fields for admin settings
 */
class FieldRenderer {

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param array $data Field data with optional prefix
	 * @param mixed $post Post object or false for options
	 * @param bool  $echo Whether to echo the field HTML or return it
	 * @return string|void HTML string if $echo is false, void otherwise
	 */
	public function display_field( array $data = array(), $post = false, bool $echo = true ) {
		// Get field info
		if ( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		// Check dependency - if this field depends on another field and that field is empty, return empty
		if ( isset( $field['depends_on'] ) ) {
			$dependency_value = get_option( $field['depends_on'] );
			if ( empty( $dependency_value ) ) {
				// Return empty if dependency value is not set
				if ( ! $echo ) {
					return '';
				}
				return;
			}
		}

		// Check for prefix on option name
		$option_name = '';
		if ( isset( $data['prefix'] ) ) {
			$option_name = $data['prefix'];
		}

		// Get saved data
		$field_data = '';
		if ( $post ) {

			// Get saved field data
			$option_name .= $field['id'];
			$option = get_post_meta( $post->ID, $field['id'], true );

			// Get data to display in field
			if ( isset( $option ) ) {
				$field_data = $option;
			}
		} else {

			// Get saved option
			$option_name .= $field['id'];
			$option = get_option( $option_name );

			// Get data to display in field
			if ( isset( $option ) ) {
				$field_data = $option;
			}
		}

		// Show default data if no option saved and default is supplied
		if ( $field_data === false && isset( $field['default'] ) ) {
			$field_data = $field['default'];
		} elseif ( $field_data === false ) {
			// Set appropriate default based on field type
			if ( in_array( $field['type'], array( 'select_multi', 'checkbox_multi' ), true ) ) {
				$field_data = array();
			} else {
				$field_data = '';
			}
		}

		$html = $this->render_field_html( $field, $field_data, $option_name, $post );

		if ( ! $echo ) {
			return $html;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	/**
	 * Render the HTML for a specific field type
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field data/value
	 * @param string $option_name Option name for the field
	 * @param mixed  $post        Post object or false
	 * @return string Field HTML
	 */
	private function render_field_html( array $field, $data, string $option_name, $post ): string {
		$html = '';

		switch ( $field['type'] ) {

			case 'text':
			case 'url':
			case 'email':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html       .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
				break;

			case 'password':
			case 'number':
			case 'hidden':
				$min = '';
				if ( isset( $field['min'] ) ) {
					$min = ' min="' . esc_attr( $field['min'] ) . '"';
				}

				$max = '';
				if ( isset( $field['max'] ) ) {
					$max = ' max="' . esc_attr( $field['max'] ) . '"';
				}
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html       .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $data ) . '"' . $min . '' . $max . '/>' . "\n";
				break;

			case 'text_secret':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html       .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" />' . "\n";
				break;

			case 'textarea_code':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html       .= '<div id="' . 'editor' . '">' . esc_textarea( $data ) . '</div>' . "\n";
				$html       .= '<textarea id="' . esc_attr( $option_name ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $data ) . '</textarea>' . "\n";
				break;

			case 'textarea':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html       .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $data ) . '</textarea><br/>' . "\n";
				break;

			case 'checkbox':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
				break;

			case 'checkbox_multi':
				// Ensure data is an array
				if ( ! is_array( $data ) ) {
					$data = array();
				}
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, $data, true ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					// TODO strict comparison does not work here: $k is int (1) while $data is string ("1")
					if ( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					// TODO strict comparison does not work here: $k is int (1) while $data is string ("1")
					if ( $k == $data ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';

				// Ensure data is an array for multi-select
				if ( ! is_array( $data ) ) {
					$data = array();
				}

				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, $data, true ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				break;

			case 'select2_multi':
				// Add Select2 attributes for AJAX functionality
				$ajax_attributes = '';
				if ( isset( $field['ajax_action'] ) ) {
					$ajax_attributes .= ' data-ajax-action="' . esc_attr( $field['ajax_action'] ) . '"';
				}
				if ( isset( $field['post_type'] ) ) {
					$ajax_attributes .= ' data-post-type="' . esc_attr( $field['post_type'] ) . '"';
				}

				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple" class="dkpdf-select2-ajax"' . $ajax_attributes . '>';

				// Ensure data is an array for multi-select
				if ( ! is_array( $data ) ) {
					$data = array();
				}

				// Pre-populate with currently selected options
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, $data, true ) ) {
						$selected = true;
						$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
					}
				}
				$html .= '</select> ';
				break;

			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image', 'dkpdf' ) . '" data-uploader_button_text="' . __( 'Use image', 'dkpdf' ) . '" class="image_upload_button button" value="' . __( 'Upload new image', 'dkpdf' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __( 'Remove image', 'dkpdf' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
				break;

			case 'info_text':
				$description = isset( $field['description'] ) ? $field['description'] : '';
				$html       .= '<div class="info-text" style="padding: 10px; background-color: #f0f8ff; border: 1px solid #cce7ff; border-radius: 4px; color: #333;">';
				$html       .= '<p style="margin: 0; font-style: italic;">' . esc_html( $description ) . '</p>';
				$html       .= '</div>';
				break;

			case 'color':
				$html .= '<div class="color-picker" style="position:relative;">';
				$html .= '<input type="text" name="' . esc_attr( $option_name ) . '" class="color" value="' . esc_attr( $data ) . '" />';
				$html .= '<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>';
				$html .= '</div>';
				break;

		}

		// Add field description
		$html .= $this->render_field_description( $field, $post );

		return $html;
	}

	/**
	 * Render field description
	 *
	 * @param array $field Field configuration
	 * @param mixed $post  Post object or false
	 * @return string Description HTML
	 */
	private function render_field_description( array $field, $post ): string {
		$html = '';

		switch ( $field['type'] ) {

			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				if ( isset( $field['description'] ) && $field['description'] !== '' ) {
					$html .= '<br/><span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
				}
				break;

			default:
				if ( ! $post ) {
					$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
				}

				if ( isset( $field['description'] ) && $field['description'] !== '' ) {
					$html .= '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>' . "\n";
				}

				if ( ! $post ) {
					$html .= '</label>' . "\n";
				}
				break;
		}

		return $html;
	}
}
