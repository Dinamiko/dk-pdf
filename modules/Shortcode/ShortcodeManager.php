<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Shortcode;

use Dinamiko\DKPDF\Template\TemplateLoader;

class ShortcodeManager {

	private TemplateLoader $template_loader;

	public function __construct( TemplateLoader $template_loader ) {
		$this->template_loader = $template_loader;
	}

	public function init(): void {
		add_shortcode( 'dkpdf-button', array( $this, 'button_shortcode' ) );
		add_shortcode( 'dkpdf-remove', array( $this, 'remove_shortcode' ) );
		add_shortcode( 'dkpdf-pagebreak', array( $this, 'pagebreak_shortcode' ) );
		add_shortcode( 'dkpdf-columns', array( $this, 'columns_shortcode' ) );
		add_shortcode( 'dkpdf-columnbreak', array( $this, 'columnbreak_shortcode' ) );
	}

	/**
	 * [dkpdf-button]
	 * This shortcode is used to display DK PDF Button
	 * doesn't has attributes, uses settings from DK PDF Settings / PDF Button
	 */
	public function button_shortcode( array $atts, ?string $content = null ): string {
		ob_start();
		$this->template_loader->get_template_part( 'dkpdf-button' );
		return ob_get_clean();
	}

	/**
	 * [dkpdf-remove tag="gallery"]content to remove[/dkpdf-remove]
	 * This shortcode is used remove pieces of content in the generated PDF
	 */
	public function remove_shortcode( array $atts, ?string $content = null ): string {
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

	/**
	 * [dkpdf-pagebreak]
	 * Allows adding page breaks for sending content after this shortcode to the next page.
	 * Uses <pagebreak /> http://mpdf1.com/manual/index.php?tid=108
	 */
	public function pagebreak_shortcode( array $atts, ?string $content = null ): string {
		$pdf = get_query_var( 'pdf' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if( apply_filters( 'dkpdf_hide_button_isset', isset( $_POST['dkpdfg_action_create'] ) ) ) {
			if ( $pdf || apply_filters( 'dkpdf_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' )  ) {
				// phpcs:enable
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

	/**
	 * [dkpdf-columns]text[/dkpdf-columns]
	 * https://mpdf.github.io/what-else-can-i-do/columns.html
	 *
	 * <columns column-count="n" vAlign="justify" column-gap="n" />
	 * column-count = Number of columns. Anything less than 2 sets columns off. (Required)
	 * vAlign = Automatically adjusts height of columns to be equal if set to J or justify. Default Off. (Optional)
	 * gap = gap in mm between columns. Default 5. (Optional)
	 *
	 * <columnbreak /> <column_break /> or <newcolumn /> (synonymous) can be included to force a new column.
	 * (This will automatically disable any justification or readjustment of column heights.)
	 */
	public function columns_shortcode( array $atts, ?string $content = null ): string {
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

	/**
	 * [dkpdf-columnbreak] forces a new column
	 * @uses <columnbreak />
	 */
	public function columnbreak_shortcode( array $atts, ?string $content = null ): string {
		$pdf = get_query_var( 'pdf' );
		if( $pdf ) {
			return '<columnbreak />';
		}

		return '';
	}
}