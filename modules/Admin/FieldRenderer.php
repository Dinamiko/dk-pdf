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
				$html = $this->render_text_field( $field, $data, $option_name );
				break;

			case 'password':
			case 'number':
			case 'hidden':
				$html = $this->render_input_field( $field, $data, $option_name );
				break;

			case 'text_secret':
				$html = $this->render_text_secret_field( $field, $option_name );
				break;

			case 'textarea_code':
				$html = $this->render_textarea_code_field( $field, $data, $option_name );
				break;

			case 'textarea':
				$html = $this->render_textarea_field( $field, $data, $option_name );
				break;

			case 'checkbox':
				$html = $this->render_checkbox_field( $field, $data, $option_name );
				break;

			case 'checkbox_multi':
				$html = $this->render_checkbox_multi_field( $field, $data, $option_name );
				break;

			case 'radio':
				$html = $this->render_radio_field( $field, $data, $option_name );
				break;

			case 'select':
				$html = $this->render_select_field( $field, $data, $option_name );
				break;

			case 'select_multi':
				$html = $this->render_select_multi_field( $field, $data, $option_name );
				break;

			case 'select2_multi':
				$html = $this->render_select2_multi_field( $field, $data, $option_name );
				break;

			case 'image':
				$html = $this->render_image_field( $field, $data, $option_name );
				break;

			case 'info_text':
				$html = $this->render_info_text_field( $field );
				break;

			case 'color':
				$html = $this->render_color_field( $field, $data, $option_name );
				break;
		}

		// Add field description
		$html .= $this->render_field_description( $field, $post );

		return $html;
	}

	/**
	 * Render text input field (text, url, email)
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_text_field( array $field, $data, string $option_name ): string {
		$attrs = $this->build_input_attributes(
			$field['id'],
			$option_name,
			'text',
			$data,
			array( 'placeholder' => $this->get_placeholder( $field ) )
		);
		return '<input ' . $attrs . ' />' . "\n";
	}

	/**
	 * Render input field with type (password, number, hidden)
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_input_field( array $field, $data, string $option_name ): string {
		$extra_attrs = array(
			'placeholder' => $this->get_placeholder( $field ),
		);

		// Add min/max for number fields
		if ( isset( $field['min'] ) ) {
			$extra_attrs['min'] = $field['min'];
		}
		if ( isset( $field['max'] ) ) {
			$extra_attrs['max'] = $field['max'];
		}

		$attrs = $this->build_input_attributes(
			$field['id'],
			$option_name,
			$field['type'],
			$data,
			$extra_attrs
		);
		return '<input ' . $attrs . ' />' . "\n";
	}

	/**
	 * Render text secret field (always shows empty value)
	 *
	 * @param array  $field       Field configuration
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_text_secret_field( array $field, string $option_name ): string {
		$attrs = $this->build_input_attributes(
			$field['id'],
			$option_name,
			'text',
			'', // Always empty for secret fields
			array( 'placeholder' => $this->get_placeholder( $field ) )
		);
		return '<input ' . $attrs . ' />' . "\n";
	}

	/**
	 * Render textarea with code editor
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_textarea_code_field( array $field, $data, string $option_name ): string {
		$attrs = $this->build_textarea_attributes(
			$option_name,
			$option_name,
			array( 'placeholder' => $this->get_placeholder( $field ) )
		);

		$html = '<div id="editor">' . esc_textarea( $data ) . '</div>' . "\n";
		$html .= '<textarea ' . $attrs . '>' . esc_textarea( $data ) . '</textarea>' . "\n";
		return $html;
	}

	/**
	 * Render textarea field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_textarea_field( array $field, $data, string $option_name ): string {
		$attrs = $this->build_textarea_attributes(
			$field['id'],
			$option_name,
			array( 'placeholder' => $this->get_placeholder( $field ) )
		);
		return '<textarea ' . $attrs . '>' . esc_textarea( $data ) . '</textarea><br/>' . "\n";
	}

	/**
	 * Render single checkbox field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_checkbox_field( array $field, $data, string $option_name ): string {
		$checked = ( $data && 'on' === $data ) ? 'checked="checked"' : '';
		return '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
	}

	/**
	 * Render multiple checkboxes field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value (array)
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_checkbox_multi_field( array $field, $data, string $option_name ): string {
		// Ensure data is an array
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$html = '';
		foreach ( $field['options'] as $k => $v ) {
			$checked = in_array( $k, $data, true );
			$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
		}
		return $html;
	}

	/**
	 * Render radio buttons field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_radio_field( array $field, $data, string $option_name ): string {
		$html = '';
		foreach ( $field['options'] as $k => $v ) {
			// Use loose comparison to handle int/string type differences
			$checked = ( (string) $k === (string) $data );
			$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
		}
		return $html;
	}

	/**
	 * Render select dropdown field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_select_field( array $field, $data, string $option_name ): string {
		$html = '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
		$html .= $this->build_select_options( $field['options'], $data, false );
		$html .= '</select> ';
		return $html;
	}

	/**
	 * Render multi-select dropdown field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value (array)
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_select_multi_field( array $field, $data, string $option_name ): string {
		$html = '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
		$html .= $this->build_select_options( $field['options'], $data, true );
		$html .= '</select> ';
		return $html;
	}

	/**
	 * Render Select2 multi-select with AJAX support
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value (array)
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_select2_multi_field( array $field, $data, string $option_name ): string {
		// Build AJAX data attributes
		$ajax_attributes = '';
		if ( isset( $field['ajax_action'] ) ) {
			$ajax_attributes .= ' data-ajax-action="' . esc_attr( $field['ajax_action'] ) . '"';
		}
		if ( isset( $field['post_type'] ) ) {
			$ajax_attributes .= ' data-post-type="' . esc_attr( $field['post_type'] ) . '"';
		}

		// Ensure data is an array for multi-select
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$html = '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple" class="dkpdf-select2-ajax"' . $ajax_attributes . '>';

		// Pre-populate with currently selected options only (for AJAX fields)
		foreach ( $field['options'] as $k => $v ) {
			if ( in_array( $k, $data, true ) ) {
				$html .= '<option ' . selected( true, true, false ) . ' value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';
			}
		}
		$html .= '</select> ';
		return $html;
	}

	/**
	 * Render image upload field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value (attachment ID)
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_image_field( array $field, $data, string $option_name ): string {
		$image_thumb = '';
		if ( $data ) {
			$image_thumb = wp_get_attachment_thumb_url( $data );
		}

		$html = '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
		$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image', 'dkpdf' ) . '" data-uploader_button_text="' . __( 'Use image', 'dkpdf' ) . '" class="image_upload_button button" value="' . __( 'Upload new image', 'dkpdf' ) . '" />' . "\n";
		$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __( 'Remove image', 'dkpdf' ) . '" />' . "\n";
		$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
		return $html;
	}

	/**
	 * Render info text field (display only)
	 *
	 * @param array $field Field configuration
	 * @return string Field HTML
	 */
	private function render_info_text_field( array $field ): string {
		$description = $field['description'] ?? '';
		$html = '<div class="info-text" style="padding: 10px; background-color: #f0f8ff; border: 1px solid #cce7ff; border-radius: 4px; color: #333;">';
		$html .= '<p style="margin: 0; font-style: italic;">' . esc_html( $description ) . '</p>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Render color picker field
	 *
	 * @param array  $field       Field configuration
	 * @param mixed  $data        Field value (color hex)
	 * @param string $option_name Option name
	 * @return string Field HTML
	 */
	private function render_color_field( array $field, $data, string $option_name ): string {
		$html = '<div class="color-picker" style="position:relative;">';
		$html .= '<input type="text" name="' . esc_attr( $option_name ) . '" class="color" value="' . esc_attr( $data ) . '" />';
		$html .= '<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>';
		$html .= '</div>';
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

	/**
	 * Build common HTML attributes for input fields
	 *
	 * @param string $id          Field ID
	 * @param string $name        Field name
	 * @param string $type        Input type
	 * @param mixed  $value       Field value
	 * @param array  $extra_attrs Additional attributes (e.g., placeholder, min, max)
	 * @return string HTML attributes string
	 */
	private function build_input_attributes( string $id, string $name, string $type, $value = '', array $extra_attrs = array() ): string {
		$attrs = array(
			'id'   => esc_attr( $id ),
			'type' => esc_attr( $type ),
			'name' => esc_attr( $name ),
		);

		// Add value attribute if provided
		if ( $value !== '' || $type === 'text' || $type === 'hidden' || $type === 'password' || $type === 'number' ) {
			$attrs['value'] = esc_attr( $value );
		}

		// Add extra attributes
		foreach ( $extra_attrs as $key => $val ) {
			if ( $val !== '' && $val !== null ) {
				$attrs[ $key ] = esc_attr( $val );
			}
		}

		// Build attribute string
		$attr_string = '';
		foreach ( $attrs as $key => $val ) {
			$attr_string .= $key . '="' . $val . '" ';
		}

		return trim( $attr_string );
	}

	/**
	 * Build HTML attributes for textarea fields
	 *
	 * @param string $id          Field ID
	 * @param string $name        Field name
	 * @param array  $extra_attrs Additional attributes (e.g., placeholder, rows, cols)
	 * @return string HTML attributes string
	 */
	private function build_textarea_attributes( string $id, string $name, array $extra_attrs = array() ): string {
		$attrs = array(
			'id'   => esc_attr( $id ),
			'name' => esc_attr( $name ),
			'rows' => $extra_attrs['rows'] ?? '5',
			'cols' => $extra_attrs['cols'] ?? '50',
		);

		// Add placeholder if provided
		if ( isset( $extra_attrs['placeholder'] ) && $extra_attrs['placeholder'] !== '' ) {
			$attrs['placeholder'] = esc_attr( $extra_attrs['placeholder'] );
		}

		// Build attribute string
		$attr_string = '';
		foreach ( $attrs as $key => $val ) {
			$attr_string .= $key . '="' . $val . '" ';
		}

		return trim( $attr_string );
	}

	/**
	 * Build HTML option elements for select fields
	 *
	 * @param array $options       Array of option key => label pairs
	 * @param mixed $selected_data Currently selected value(s)
	 * @param bool  $is_multi      Whether this is a multi-select
	 * @return string HTML option elements
	 */
	private function build_select_options( array $options, $selected_data, bool $is_multi = false ): string {
		$html = '';
		$selected_array = $is_multi && is_array( $selected_data ) ? $selected_data : array();

		foreach ( $options as $k => $v ) {
			if ( $is_multi ) {
				$is_selected = in_array( $k, $selected_array, true );
			} else {
				if ( is_array( $selected_data ) ) {
					$is_selected = in_array( $k, $selected_data, true );
				} else {
					$is_selected = ( (string) $k === (string) $selected_data );
				}
			}

			$html .= '<option ' . selected( $is_selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';
		}

		return $html;
	}

	/**
	 * Get placeholder value from field configuration
	 *
	 * @param array $field Field configuration
	 * @return string Placeholder text
	 */
	private function get_placeholder( array $field ): string {
		return $field['placeholder'] ?? '';
	}
}
