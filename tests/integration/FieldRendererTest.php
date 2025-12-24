<?php
declare(strict_types=1);

namespace Dinamiko\DKPDF\Tests\Integration;

use Dinamiko\DKPDF\Admin\FieldRenderer;
use PHPUnit\Framework\TestCase;

class FieldRendererTest extends TestCase {

	private FieldRenderer $renderer;

	public function setUp(): void {
		parent::setUp();
		$this->renderer = new FieldRenderer();
	}

	public function tearDown(): void {
		// Clean up options after each test
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'test_%'" );
		wp_cache_flush();
		parent::tearDown();
	}

	// ===== TEXT FIELD TESTS =====

	public function test_text_field_renders_with_value(): void {
		update_option( 'test_text_field', 'Hello World' );

		$field = [
			'id'          => 'test_text_field',
			'type'        => 'text',
			'placeholder' => 'Enter text',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'id="test_text_field"', $html );
		$this->assertStringContainsString( 'name="test_text_field"', $html );
		$this->assertStringContainsString( 'value="Hello World"', $html );
		$this->assertStringContainsString( 'placeholder="Enter text"', $html );
	}

	public function test_text_field_renders_with_default_value(): void {
		$field = [
			'id'      => 'test_default_text',
			'type'    => 'text',
			'default' => 'Default Value',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'value="Default Value"', $html );
	}

	public function test_text_field_renders_empty_without_value(): void {
		$field = [
			'id'   => 'test_empty_text',
			'type' => 'text',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'value=""', $html );
	}

	// ===== URL AND EMAIL FIELD TESTS =====

	public function test_url_field_renders_correctly(): void {
		update_option( 'test_url_field', 'https://example.com' );

		$field = [
			'id'   => 'test_url_field',
			'type' => 'url',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'value="https://example.com"', $html );
	}

	public function test_email_field_renders_correctly(): void {
		update_option( 'test_email_field', 'test@example.com' );

		$field = [
			'id'   => 'test_email_field',
			'type' => 'email',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'value="test@example.com"', $html );
	}

	// ===== NUMBER FIELD TESTS =====

	public function test_number_field_renders_with_min_max(): void {
		update_option( 'test_number_field', '42' );

		$field = [
			'id'   => 'test_number_field',
			'type' => 'number',
			'min'  => '0',
			'max'  => '100',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="number"', $html );
		$this->assertStringContainsString( 'value="42"', $html );
		$this->assertStringContainsString( 'min="0"', $html );
		$this->assertStringContainsString( 'max="100"', $html );
	}

	// ===== PASSWORD FIELD TESTS =====

	public function test_password_field_renders_correctly(): void {
		update_option( 'test_password_field', 'secret123' );

		$field = [
			'id'   => 'test_password_field',
			'type' => 'password',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="password"', $html );
		$this->assertStringContainsString( 'value="secret123"', $html );
	}

	// ===== HIDDEN FIELD TESTS =====

	public function test_hidden_field_renders_correctly(): void {
		update_option( 'test_hidden_field', 'hidden_value' );

		$field = [
			'id'   => 'test_hidden_field',
			'type' => 'hidden',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertStringContainsString( 'value="hidden_value"', $html );
	}

	// ===== TEXTAREA TESTS =====

	public function test_textarea_field_renders_with_value(): void {
		update_option( 'test_textarea_field', "Line 1\nLine 2\nLine 3" );

		$field = [
			'id'          => 'test_textarea_field',
			'type'        => 'textarea',
			'placeholder' => 'Enter description',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<textarea', $html );
		$this->assertStringContainsString( 'id="test_textarea_field"', $html );
		$this->assertStringContainsString( 'name="test_textarea_field"', $html );
		$this->assertStringContainsString( "Line 1\nLine 2\nLine 3", $html );
		$this->assertStringContainsString( 'placeholder="Enter description"', $html );
	}

	// ===== TEXTAREA CODE TESTS =====

	public function test_textarea_code_field_renders_with_code(): void {
		update_option( 'test_textarea_code', '<div>HTML Code</div>' );

		$field = [
			'id'   => 'test_textarea_code',
			'type' => 'textarea_code',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<textarea', $html );
		$this->assertStringContainsString( 'id="test_textarea_code"', $html );
		$this->assertStringContainsString( '&lt;div&gt;HTML Code&lt;/div&gt;', $html );
		$this->assertStringContainsString( '<div id="editor">', $html );
	}

	// ===== CHECKBOX TESTS =====

	public function test_checkbox_field_renders_checked(): void {
		update_option( 'test_checkbox_field', 'on' );

		$field = [
			'id'   => 'test_checkbox_field',
			'type' => 'checkbox',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'checked="checked"', $html );
	}

	public function test_checkbox_field_renders_unchecked(): void {
		$field = [
			'id'   => 'test_checkbox_unchecked',
			'type' => 'checkbox',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		// The field still contains the word "checked" in the label "for" attribute
		$this->assertStringNotContainsString( 'checked="checked"', $html );
	}

	// ===== CHECKBOX MULTI TESTS =====

	public function test_checkbox_multi_field_renders_with_selections(): void {
		update_option( 'test_checkbox_multi', [ 'option1', 'option3' ] );

		$field = [
			'id'      => 'test_checkbox_multi',
			'type'    => 'checkbox_multi',
			'options' => [
				'option1' => 'Option 1',
				'option2' => 'Option 2',
				'option3' => 'Option 3',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'name="test_checkbox_multi[]"', $html );
		$this->assertStringContainsString( 'value="option1"', $html );
		$this->assertStringContainsString( 'value="option2"', $html );
		$this->assertStringContainsString( 'value="option3"', $html );
		$this->assertStringContainsString( 'Option 1', $html );
		$this->assertStringContainsString( 'Option 2', $html );
		$this->assertStringContainsString( 'Option 3', $html );

		// Check that option1 and option3 are checked (checked appears before value in output)
		$this->assertStringContainsString( "checked='checked' name=\"test_checkbox_multi[]\" value=\"option1\"", $html );
		$this->assertStringContainsString( "checked='checked' name=\"test_checkbox_multi[]\" value=\"option3\"", $html );
		// Verify option2 is not checked (no checked attribute before its value)
		$this->assertStringNotContainsString( "checked='checked' name=\"test_checkbox_multi[]\" value=\"option2\"", $html );
	}

	public function test_checkbox_multi_field_handles_empty_array(): void {
		$field = [
			'id'      => 'test_checkbox_multi_empty',
			'type'    => 'checkbox_multi',
			'options' => [
				'opt1' => 'Option 1',
				'opt2' => 'Option 2',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringNotContainsString( 'checked', $html );
	}

	// ===== RADIO TESTS =====

	public function test_radio_field_renders_with_string_selection(): void {
		update_option( 'test_radio_field', '2' );

		$field = [
			'id'      => 'test_radio_field',
			'type'    => 'radio',
			'options' => [
				1 => 'Option 1',
				2 => 'Option 2',
				3 => 'Option 3',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="radio"', $html );
		$this->assertStringContainsString( 'name="test_radio_field"', $html );
		$this->assertStringContainsString( 'value="1"', $html );
		$this->assertStringContainsString( 'value="2"', $html );
		$this->assertStringContainsString( 'value="3"', $html );

		// Check that option 2 is selected (checked appears before value in output)
		$this->assertStringContainsString( "checked='checked' name=\"test_radio_field\" value=\"2\"", $html );
	}

	public function test_radio_field_renders_with_int_selection(): void {
		update_option( 'test_radio_int', 1 );

		$field = [
			'id'      => 'test_radio_int',
			'type'    => 'radio',
			'options' => [
				1 => 'First',
				2 => 'Second',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		// Check that option 1 is selected
		$this->assertStringContainsString( "checked='checked' name=\"test_radio_int\" value=\"1\"", $html );
	}

	// ===== SELECT TESTS =====

	public function test_select_field_renders_with_string_selection(): void {
		update_option( 'test_select_field', '2' );

		$field = [
			'id'      => 'test_select_field',
			'type'    => 'select',
			'options' => [
				1 => 'First Option',
				2 => 'Second Option',
				3 => 'Third Option',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'name="test_select_field"', $html );
		$this->assertStringContainsString( 'id="test_select_field"', $html );
		$this->assertStringContainsString( '<option', $html );
		$this->assertStringContainsString( 'value="1"', $html );
		$this->assertStringContainsString( 'value="2"', $html );
		$this->assertStringContainsString( 'value="3"', $html );

		// Check that option 2 is selected (selected appears before value in output)
		$this->assertStringContainsString( "selected='selected' value=\"2\"", $html );
	}

	// ===== SELECT MULTI TESTS =====

	public function test_select_multi_field_renders_with_selections(): void {
		update_option( 'test_select_multi', [ 'opt1', 'opt3' ] );

		$field = [
			'id'      => 'test_select_multi',
			'type'    => 'select_multi',
			'options' => [
				'opt1' => 'Option 1',
				'opt2' => 'Option 2',
				'opt3' => 'Option 3',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'multiple="multiple"', $html );
		$this->assertStringContainsString( 'name="test_select_multi[]"', $html );

		// Check that opt1 and opt3 are selected (selected appears before value in output)
		$this->assertStringContainsString( "selected='selected' value=\"opt1\"", $html );
		$this->assertStringContainsString( "selected='selected' value=\"opt3\"", $html );
		// Verify opt2 is not selected
		$this->assertStringNotContainsString( "selected='selected' value=\"opt2\"", $html );
	}

	public function test_select_multi_field_handles_empty_array(): void {
		$field = [
			'id'      => 'test_select_multi_empty',
			'type'    => 'select_multi',
			'options' => [
				'a' => 'A',
				'b' => 'B',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'multiple="multiple"', $html );
		$this->assertStringNotContainsString( 'selected', $html );
	}

	// ===== SELECT2 MULTI TESTS =====

	public function test_select2_multi_field_renders_with_ajax_attributes(): void {
		update_option( 'test_select2_multi', [ '123', '456' ] );

		$field = [
			'id'          => 'test_select2_multi',
			'type'        => 'select2_multi',
			'ajax_action' => 'search_posts',
			'post_type'   => 'post',
			'options'     => [
				'123' => 'Post 123',
				'456' => 'Post 456',
				'789' => 'Post 789',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'multiple="multiple"', $html );
		$this->assertStringContainsString( 'class="dkpdf-select2-ajax"', $html );
		$this->assertStringContainsString( 'data-ajax-action="search_posts"', $html );
		$this->assertStringContainsString( 'data-post-type="post"', $html );

		// OBSERVABLE BEHAVIOR: Select2 AJAX fields render an empty select element
		// Options are loaded dynamically via AJAX, not rendered in HTML
		// The logic only renders options that are in the selected data AND also exist in field options
		// In this case, the intersection check (in_array($k, $data)) only renders matching selected items
		// But since we're checking the options array keys, nothing renders if the check fails
		$this->assertStringContainsString( '</select>', $html );
	}

	// ===== IMAGE FIELD TESTS =====

	public function test_image_field_renders_upload_button_when_empty(): void {
		$field = [
			'id'   => 'test_image_field',
			'type' => 'image',
		];

		$html = $this->renderer->display_field( $field, false, false );

		// Should contain upload button
		$this->assertStringContainsString( 'class="image_upload_button button"', $html );
		$this->assertStringContainsString( 'id="test_image_field_button"', $html );

		// Should contain hidden input field
		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertStringContainsString( 'name="test_image_field"', $html );
		$this->assertStringContainsString( 'value=""', $html );

		// Should NOT contain preview or delete button when empty
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'class="image_delete_button"', $html );
	}

	public function test_image_field_renders_full_interface_with_image(): void {
		// Create a mock attachment ID
		$attachment_id = 123;
		update_option( 'test_image_field', (string) $attachment_id );

		$field = [
			'id'   => 'test_image_field',
			'type' => 'image',
		];

		$html = $this->renderer->display_field( $field, false, false );

		// Should contain image preview
		$this->assertStringContainsString( '<img', $html );
		$this->assertStringContainsString( 'class="image_preview"', $html );
		$this->assertStringContainsString( 'id="test_image_field_preview"', $html );

		// Should contain upload button
		$this->assertStringContainsString( 'class="image_upload_button button"', $html );

		// Should contain delete button
		$this->assertStringContainsString( 'class="image_delete_button button"', $html );

		// Should contain hidden input with value
		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertStringContainsString( 'name="test_image_field"', $html );
		$this->assertStringContainsString( 'value="123"', $html );
	}

	// ===== INFO TEXT TESTS =====

	public function test_info_text_field_renders_description(): void {
		$field = [
			'id'          => 'test_info_text',
			'type'        => 'info_text',
			'description' => 'This is an informational message',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<div class="info-text"', $html );
		$this->assertStringContainsString( 'This is an informational message', $html );
	}

	// ===== COLOR FIELD TESTS =====

	public function test_color_field_renders_color_picker(): void {
		update_option( 'test_color_field', '#ff0000' );

		$field = [
			'id'   => 'test_color_field',
			'type' => 'color',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<div class="color-picker"', $html );
		$this->assertStringContainsString( 'class="color"', $html );
		$this->assertStringContainsString( 'value="#ff0000"', $html );
		$this->assertStringContainsString( 'class="colorpicker"', $html );
	}

	// ===== DESCRIPTION TESTS =====

	public function test_field_description_renders_for_text_field(): void {
		$field = [
			'id'          => 'test_with_description',
			'type'        => 'text',
			'description' => 'This is a helpful description',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<label', $html );
		$this->assertStringContainsString( '<span class="description">', $html );
		$this->assertStringContainsString( 'This is a helpful description', $html );
	}

	public function test_field_description_renders_for_checkbox_multi(): void {
		$field = [
			'id'          => 'test_checkbox_multi_desc',
			'type'        => 'checkbox_multi',
			'description' => 'Select multiple options',
			'options'     => [
				'a' => 'A',
				'b' => 'B',
			],
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( '<br/><span class="description">', $html );
		$this->assertStringContainsString( 'Select multiple options', $html );
	}

	// ===== DEPENDENCY TESTS =====

	public function test_field_with_dependency_renders_when_dependency_met(): void {
		update_option( 'test_dependency_source', 'value' );

		$field = [
			'id'         => 'test_dependent_field',
			'type'       => 'text',
			'depends_on' => 'test_dependency_source',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'id="test_dependent_field"', $html );
	}

	public function test_field_with_dependency_returns_empty_when_dependency_not_met(): void {
		// Don't set the dependency option

		$field = [
			'id'         => 'test_dependent_field_empty',
			'type'       => 'text',
			'depends_on' => 'test_dependency_source_missing',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertEmpty( $html );
	}

	// ===== POST META TESTS =====

	public function test_field_renders_with_post_meta(): void {
		$post_id = wp_insert_post( [
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'publish',
		] );

		update_post_meta( $post_id, 'test_post_meta_field', 'Meta Value' );

		$field = [
			'id'   => 'test_post_meta_field',
			'type' => 'text',
		];

		$post = get_post( $post_id );
		$html = $this->renderer->display_field( $field, $post, false );

		$this->assertStringContainsString( 'value="Meta Value"', $html );

		// Cleanup
		wp_delete_post( $post_id, true );
	}

	public function test_field_renders_with_post_meta_default(): void {
		$post_id = wp_insert_post( [
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'publish',
		] );

		$field = [
			'id'      => 'test_post_meta_default',
			'type'    => 'text',
			'default' => 'Default Meta Value',
		];

		$post = get_post( $post_id );
		$html = $this->renderer->display_field( $field, $post, false );

		// OBSERVABLE BEHAVIOR: When post meta doesn't exist, get_post_meta returns empty string
		// not false, so the default value logic doesn't trigger for post meta fields
		// This is the actual behavior - defaults only work for options, not post meta
		$this->assertStringContainsString( 'value=""', $html );

		// Cleanup
		wp_delete_post( $post_id, true );
	}

	// ===== PREFIX TESTS =====

	public function test_field_renders_with_prefix(): void {
		update_option( 'prefix_test_field', 'Prefixed Value' );

		$data = [
			'prefix' => 'prefix_',
			'field'  => [
				'id'   => 'test_field',
				'type' => 'text',
			],
		];

		$html = $this->renderer->display_field( $data, false, false );

		$this->assertStringContainsString( 'name="prefix_test_field"', $html );
		$this->assertStringContainsString( 'value="Prefixed Value"', $html );
	}

	// ===== ECHO VS RETURN TESTS =====

	public function test_display_field_echoes_by_default(): void {
		$field = [
			'id'   => 'test_echo_field',
			'type' => 'text',
		];

		ob_start();
		$result = $this->renderer->display_field( $field, false, true );
		$output = ob_get_clean();

		$this->assertNull( $result );
		$this->assertStringContainsString( 'type="text"', $output );
	}

	public function test_display_field_returns_when_echo_false(): void {
		$field = [
			'id'   => 'test_return_field',
			'type' => 'text',
		];

		ob_start();
		$result = $this->renderer->display_field( $field, false, false );
		$output = ob_get_clean();

		$this->assertIsString( $result );
		$this->assertStringContainsString( 'type="text"', $result );
		$this->assertEmpty( $output );
	}

	// ===== HTML ESCAPING TESTS =====

	public function test_field_escapes_html_in_values(): void {
		update_option( 'test_escape_field', '<script>alert("xss")</script>' );

		$field = [
			'id'   => 'test_escape_field',
			'type' => 'text',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function test_textarea_escapes_html_in_values(): void {
		update_option( 'test_escape_textarea', '<script>alert("xss")</script>' );

		$field = [
			'id'   => 'test_escape_textarea',
			'type' => 'textarea',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	// ===== TEXT SECRET FIELD TESTS =====

	public function test_text_secret_field_renders_empty_value(): void {
		update_option( 'test_secret_field', 'secret_password' );

		$field = [
			'id'          => 'test_secret_field',
			'type'        => 'text_secret',
			'placeholder' => 'Enter secret',
		];

		$html = $this->renderer->display_field( $field, false, false );

		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'value=""', $html );
		$this->assertStringNotContainsString( 'secret_password', $html );
	}
}
