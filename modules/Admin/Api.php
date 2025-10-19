<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

class Api {

    /**
     * Save metabox fields
     * @param  integer $post_id Post ID
     * @return void
     */
    public function save_meta_boxes ( $post_id = 0 ) {
        if ( ! $post_id ) {
            return;
        }

        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check if this is a revision
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post_type = get_post_type( $post_id );
        if ( ! $post_type ) {
            return;
        }

        // Verify nonce - use post type specific nonce
        $nonce_field = $post_type . '_custom_fields_nonce';
        if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $post_type . '_save_custom_fields' ) ) {
            return;
        }

        // Check user permissions
        $post_type_object = get_post_type_object( $post_type );
        if ( ! $post_type_object ) {
            return;
        }

        if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
            return;
        }

        $fields = apply_filters( $post_type . '_custom_fields', array(), $post_type );

        if ( ! is_array( $fields ) || 0 === count( $fields ) ) {
            return;
        }

        foreach ( $fields as $field ) {
            if ( ! isset( $field['id'] ) || ! isset( $field['type'] ) ) {
                continue;
            }

            $field_id = $field['id'];

            // Handle multi-value fields (checkbox_multi, select_multi, select2_multi)
            if ( in_array( $field['type'], array( 'checkbox_multi', 'select_multi', 'select2_multi' ), true ) ) {
                if ( isset( $_POST[ $field_id ] ) && is_array( $_POST[ $field_id ] ) ) {
                    $sanitized_values = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $field_id ] ) );
                    update_post_meta( $post_id, $field_id, $sanitized_values );
                } else {
                    delete_post_meta( $post_id, $field_id );
                }
            } elseif ( isset( $_POST[ $field_id ] ) ) {
                $value = wp_unslash( $_POST[ $field_id ] );
                $sanitized_value = $this->validate_field( $value, $field['type'] );
                update_post_meta( $post_id, $field_id, $sanitized_value );
            } else {
                // Delete meta if field not present (handles unchecked checkboxes)
                delete_post_meta( $post_id, $field_id );
            }
        }
    }

	/**
	 * Generate HTML for displaying fields
	 * @param  array   $field Field data
	 * @param  boolean $echo  Whether to echo the field HTML or return it
	 * @return void
	 */
	public function display_field( $data = array(), $post = false, $echo = true ) {
		// Get field info
		if ( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		// Check dependency - if this field depends on another field and that field is empty, return empty
		if (isset($field['depends_on'])) {
			$dependency_value = get_option($field['depends_on']);
			if (empty($dependency_value)) {
				// Return empty if dependency value is not set
				if (!$echo) {
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
		$data = '';
		if ( $post ) {

			// Get saved field data
			$option_name .= $field['id'];
			$option = get_post_meta( $post->ID, $field['id'], true );

			// Get data to display in field
			if ( isset( $option ) ) {
				$data = $option;
			}

		} else {

			// Get saved option
			$option_name .= $field['id'];
			$option = get_option( $option_name );

			// Get data to display in field
			if ( isset( $option ) ) {
				$data = $option;
			}

		}

		// Show default data if no option saved and default is supplied
		if ( $data === false && isset( $field['default'] ) ) {
			$data = $field['default'];
		} elseif ( $data === false ) {
			// Set appropriate default based on field type
			if ( in_array( $field['type'], array( 'select_multi', 'checkbox_multi' ) ) ) {
				$data = array();
			} else {
				$data = '';
			}
		}

		$html = '';

		switch( $field['type'] ) {

			case 'text':
			case 'url':
			case 'email':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
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
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $data ) . '"' . $min . '' . $max . '/>' . "\n";
				break;

			case 'text_secret':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" />' . "\n";
				break;

			case 'textarea_code':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html .= '<div id="' . 'editor' . '">'. esc_textarea( $data ) .'</div>'. "\n";
				$html .= '<textarea id="' . esc_attr( $option_name ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $data ) . '</textarea>'. "\n";
				break;

			case 'textarea':
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $data ) . '</textarea><br/>'. "\n";
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
					if ( $k === $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( $k === $data ) {
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
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image' , 'dkpdf' ) . '" data-uploader_button_text="' . __( 'Use image' , 'dkpdf' ) . '" class="image_upload_button button" value="'. __( 'Upload new image' , 'dkpdf' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="'. __( 'Remove image' , 'dkpdf' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
				break;

			case 'info_text':
				$description = isset( $field['description'] ) ? $field['description'] : '';
				$html .= '<div class="info-text" style="padding: 10px; background-color: #f0f8ff; border: 1px solid #cce7ff; border-radius: 4px; color: #333;">';
				$html .= '<p style="margin: 0; font-style: italic;">' . esc_html( $description ) . '</p>';
				$html .= '</div>';
				break;

			case 'color':
				$html .= '<div class="color-picker" style="position:relative;">';
				$html .= '<input type="text" name="' . esc_attr( $option_name ) . '" class="color" value="' . esc_attr( $data ) . '" />';
				$html .= '<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>';
				$html .= '</div>';
				break;

		}

		switch( $field['type'] ) {

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

		if ( ! $echo ) {
			return $html;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;

	}

	/**
	 * Add meta box to the dashboard
	 * @param string $id            Unique ID for metabox
	 * @param string $title         Display title of metabox
	 * @param array  $post_types    Post types to which this metabox applies
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side')
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high')
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box ( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {

		// Get post type(s)
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		// Generate each metabox
		foreach ( $post_types as $post_type ) {
			add_meta_box( $id, $title, array( $this, 'meta_box_content' ), $post_type, $context, $priority, $callback_args );
		}
	}

	/**
	 * Display metabox content
	 * @param  object $post Post object
	 * @param  array  $args Arguments unique to this metabox
	 * @return void
	 */
	public function meta_box_content ( $post, $args ) {

		$fields = apply_filters( $post->post_type . '_custom_fields', array(), $post->post_type );

		if ( ! is_array( $fields ) || 0 === count( $fields ) ) {
			return;
		}

		echo '<div class="custom-field-panel">' . "\n";

		// Add nonce field for security
		$nonce_field = $post->post_type . '_custom_fields_nonce';
		$nonce_action = $post->post_type . '_save_custom_fields';
		wp_nonce_field( $nonce_action, $nonce_field );

		foreach ( $fields as $field ) {

			if ( ! isset( $field['metabox'] ) ) {
				continue;
			}

			if ( ! is_array( $field['metabox'] ) ) {
				$field['metabox'] = array( $field['metabox'] );
			}

			if ( in_array( $args['id'], $field['metabox'], true ) ) {
				$this->display_meta_box_field( $post, $field );
			}

		}

		echo '</div>' . "\n";

	}

	/**
	 * Dispay field in metabox
	 * @param object $post Post object
	 * @param  array  $field Field data
	 * @return void
	 */
	public function display_meta_box_field ( $post, $field = array()) {

		if ( ! is_array( $field ) || 0 === count( $field ) ) {
			return;
		}

		$field = '<p class="form-field"><label for="' . esc_attr($field['id']) . '">' . esc_attr($field['label']) . '</label>' . $this->display_field( $field, $post, false ) . '</p>' . "\n";

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $field;
	}

    /**
     * Validate form field
     * @param  mixed  $data Submitted value
     * @param  string $type Type of field to validate
     * @return mixed        Validated value
     */
    private function validate_field( $data = '', $type = 'text' ) {
        switch( $type ) {
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
