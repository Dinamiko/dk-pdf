<?php
/**
* dkpdf-footer.php
* This template is used to display content in PDF Footer
*
* Do not edit this template directly,
* copy this template and paste in your theme inside a directory named dkpdf
*/
?>

<?php
	global $post;
	$pdf_footer_text = sanitize_option( 'dkpdf_pdf_footer_text', get_option( 'dkpdf_pdf_footer_text' ) );
	$pdf_footer_show_title = sanitize_option( 'dkpdf_pdf_footer_show_title', get_option( 'dkpdf_pdf_footer_show_title' ) );
	$pdf_footer_show_pagination = sanitize_option( 'dkpdf_pdf_footer_show_pagination', get_option( 'dkpdf_pdf_footer_show_pagination' ) );
?>

<?php
	// only enter here if any of the settings exists
	if( $pdf_footer_text || $pdf_footer_show_pagination ) { ?>

	    <div style="width:100%;float:left;padding-top:10px;">
		    <div style="float:right;text-align:right;">

				<?php
					// check if Footer show title exists
					if ( $pdf_footer_text ) {

						echo wp_kses_post($pdf_footer_text);

					}

				?>

				<?php
					// check if Footer show title is checked
					if ( $post && $pdf_footer_show_title ) {

						echo wp_kses_post(get_the_title( $post->ID ));

					}

				?>

				<?php
					// check if Footer show pagination is checked
					if ( $pdf_footer_show_pagination ) {

                        $pagination = apply_filters('dkpdf_footer_pagination', '| {PAGENO}');

                        echo esc_attr($pagination);
					}

				?>

		    </div>
	    </div>

	<?php }

?>



