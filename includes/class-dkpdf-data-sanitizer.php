<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DKPDF_Data_Sanitizer {

	public function __construct() {
		add_action( 'init', array( $this, 'init_sanitization' ) );
	}

	/**
	 * Initialize all sanitization filters
	 */
	public function init_sanitization() {
		$this->register_text_field_sanitization();
		$this->register_int_field_sanitization();
		$this->register_special_field_sanitization();
		$this->register_array_field_sanitization();
	}

	/**
	 * Register sanitization for text fields
	 */
	private function register_text_field_sanitization() {
		$text_fields = array(
			'pdfbutton_text',
			'pdfbutton_action',
			'pdfbutton_position',
			'pdfbutton_align',
			'page_orientation',
			'pdf_header_image',
			'pdf_header_show_title',
			'pdf_header_show_pagination',
			'pdf_footer_show_title',
			'pdf_footer_show_pagination',
			'print_wp_head'
		);

		foreach ( $text_fields as $field ) {
			add_filter( "pre_update_option_dkpdf_{$field}", array( $this, 'sanitize_text_field' ), 10, 2 );
		}
	}

	/**
	 * Register sanitization for integer fields
	 */
	private function register_int_field_sanitization() {
		$int_fields = array(
			'font_size',
			'margin_left',
			'margin_right',
			'margin_top',
			'margin_bottom',
			'margin_header'
		);

		foreach ( $int_fields as $field ) {
			add_filter( "pre_update_option_dkpdf_{$field}", array( $this, 'sanitize_int_field' ), 10, 2 );
		}
	}

	/**
	 * Register sanitization for special fields that need custom handling
	 */
	private function register_special_field_sanitization() {
		add_filter( 'pre_update_option_dkpdf_pdf_footer_text', array( $this, 'sanitize_footer_text' ), 10, 2 );
		add_filter( 'pre_update_option_dkpdf_pdf_custom_css', array( $this, 'sanitize_custom_css' ), 10, 2 );
	}

	/**
	 * Register sanitization for array fields
	 */
	private function register_array_field_sanitization() {
		add_filter( 'pre_update_option_dkpdf_pdfbutton_post_types', array( $this, 'sanitize_array_field' ), 10, 2 );
	}

	/**
	 * Sanitize text fields
	 *
	 * @param mixed $new_value The new value
	 * @param mixed $old_value The old value
	 * @return string Sanitized text
	 */
	public function sanitize_text_field( $new_value, $old_value = null ) {
		return sanitize_text_field( $new_value );
	}

	/**
	 * Sanitize integer fields
	 *
	 * @param mixed $new_value The new value
	 * @param mixed $old_value The old value
	 * @return int Sanitized integer
	 */
	public function sanitize_int_field( $new_value, $old_value = null ) {
		return intval( $new_value );
	}

	/**
	 * Sanitize footer text allowing limited HTML tags
	 *
	 * @param mixed $new_value The new value
	 * @param mixed $old_value The old value
	 * @return string Sanitized footer text
	 */
	public function sanitize_footer_text( $new_value, $old_value = null ) {
		$allowed_html = array(
			'a'      => array( 'href' => array(), 'title' => array(), 'class' => array(), 'style' => array() ),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'hr'     => array(),
			'p'      => array( 'title' => array(), 'class' => array(), 'style' => array() ),
			'h1'     => array( 'title' => array(), 'class' => array(), 'style' => array() ),
			'h2'     => array( 'title' => array(), 'class' => array(), 'style' => array() ),
			'h3'     => array( 'title' => array(), 'class' => array(), 'style' => array() ),
			'h4'     => array( 'title' => array(), 'class' => array(), 'style' => array() ),
			'div'    => array( 'title' => array(), 'class' => array(), 'style' => array() )
		);

		return wp_kses( $new_value, $allowed_html );
	}

	/**
	 * Sanitize custom CSS
	 *
	 * @param mixed $new_value The new value
	 * @param mixed $old_value The old value
	 * @return string Sanitized CSS
	 */
	public function sanitize_custom_css( $new_value, $old_value = null ) {
		$new_value = wp_filter_nohtml_kses( $new_value );
		$new_value = str_replace( '\"', '"', $new_value );
		$new_value = str_replace( "\'", "'", $new_value );

		return $new_value;
	}

	/**
	 * Sanitize array fields
	 *
	 * @param mixed $new_value The new value
	 * @param mixed $old_value The old value
	 * @return array Sanitized array
	 */
	public function sanitize_array_field( $new_value, $old_value = null ) {
		return is_array( $new_value ) ? $new_value : array();
	}
}