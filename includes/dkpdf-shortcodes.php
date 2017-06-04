<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* [dkpdf-button]
* This shortcode is used to display DK PDF Button
* doesn't has attributes, uses settings from DK PDF Settings / PDF Button
*/
function dkpdf_button_shortcode( $atts, $content = null ) {

	$template = new DKPDF_Template_Loader;

	ob_start();

	$template->get_template_part( 'dkpdf-button' );

	return ob_get_clean();

}

add_shortcode( 'dkpdf-button', 'dkpdf_button_shortcode' );

/**
* [dkpdf-remove tag="gallery"]content to remove[/dkpdf-remove]
* This shortcode is used remove pieces of content in the generated PDF
* @return string
*/
function dkpdf_remove_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'tag' => ''
		), $atts );
		$pdf = get_query_var( 'pdf' );
		$tag = sanitize_text_field( $atts['tag'] );
		if( $tag !== '' && $pdf )  {
				remove_shortcode( $tag );
				add_shortcode( $tag, '__return_false' );
				return do_shortcode( $content );
		} else if( $pdf ) {
				return '';
		}

		return do_shortcode( $content );
}
add_shortcode( 'dkpdf-remove', 'dkpdf_remove_shortcode' );

/**
* [dkpdf-pagebreak]
* Allows adding page breaks for sending content after this shortcode to the next page.
* Uses <pagebreak /> http://mpdf1.com/manual/index.php?tid=108
* @return string
*/
function dkpdf_pagebreak_shortcode( $atts, $content = null ) {

	$pdf = get_query_var( 'pdf' );

  	if( apply_filters( 'dkpdf_hide_button_isset', isset( $_POST['dkpdfg_action_create'] ) ) ) {
    	if ( $pdf || apply_filters( 'dkpdf_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' )  ) {

			$output = '<pagebreak />';

		} else {

			$output = '';

		}

	} else {

		if( $pdf ) {

			$output = '<pagebreak />';

		} else {

			$output = '';

		}

	}

	return $output;

}
add_shortcode( 'dkpdf-pagebreak', 'dkpdf_pagebreak_shortcode' );

/**
 * [dkpdf-columns]text[/dkpdf-columns]
 * https://mpdf.github.io/what-else-can-i-do/columns.html
 *
 * <columns column-count=”n” vAlign=”justify” column-gap=”n” />
 * column-count = Number of columns. Anything less than 2 sets columns off. (Required)
 * vAlign = Automatically adjusts height of columns to be equal if set to J or justify. Default Off. (Optional)
 * gap = gap in mm between columns. Default 5. (Optional)
 *
 * <columnbreak /> <column_break /> or <newcolumn /> (synonymous) can be included to force a new column.
 * (This will automatically disable any justification or readjustment of column heights.)
 */
function dkpdf_columns_shortcode( $atts, $content = null ) {

	$atts = shortcode_atts( array(
		'columns' => '2',
		'equal-columns' => 'false',
		'gap' => '10'
	), $atts );

	$pdf = get_query_var( 'pdf' );

	if( $pdf ) {
		$columns = sanitize_text_field( $atts['columns'] );
		$equal_columns = sanitize_text_field( $atts['equal-columns'] );
		$vAlign = $equal_columns == 'true' ? 'vAlign="justify"' : '';
		$gap = sanitize_text_field( $atts['gap'] );
		return '<columns column-count="'.$columns.'" '.$vAlign.' column-gap="'.$gap.'" />'.do_shortcode( $content ).'<columns column-count="1">';
	} else {
		remove_shortcode( 'dkpdf-columnbreak' );
		add_shortcode( 'dkpdf-columnbreak', '__return_false' );
		return do_shortcode( $content );
	}

}
add_shortcode( 'dkpdf-columns', 'dkpdf_columns_shortcode' );

/**
* [dkpdf-columnbreak] forces a new column
* @uses <columnbreak />
*/
function dkpdf_columnbreak_shortcode( $atts, $content = null ) {
	$pdf = get_query_var( 'pdf' );
	if( $pdf ) {
		return '<columnbreak />';
	}
}
add_shortcode( 'dkpdf-columnbreak', 'dkpdf_columnbreak_shortcode' );
