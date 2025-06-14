<?php
global $post;
$pdf_footer_text            = sanitize_option( 'dkpdf_pdf_footer_text', get_option( 'dkpdf_pdf_footer_text' ) );
$pdf_footer_show_title      = sanitize_option( 'dkpdf_pdf_footer_show_title', get_option( 'dkpdf_pdf_footer_show_title' ) );
$pdf_footer_show_pagination = sanitize_option( 'dkpdf_pdf_footer_show_pagination', get_option( 'dkpdf_pdf_footer_show_pagination' ) );
?>

<?php
if ( $pdf_footer_text || $pdf_footer_show_pagination ) { ?>
    <div style="width:100%;float:left;padding-top:10px;">
        <div style="float:right;text-align:right;">
			<?php
			if ( $pdf_footer_text ) {
				echo wp_kses_post( $pdf_footer_text );
			}
			?>

			<?php
			if ( $post && $pdf_footer_show_title ) {
				echo wp_kses_post( get_the_title( $post->ID ) );
			}
			?>

			<?php
			if ( $pdf_footer_show_pagination ) {
				$pagination = apply_filters( 'dkpdf_footer_pagination', '| {PAGENO}' );
				echo esc_attr( $pagination );
			}
			?>
        </div>
    </div>

<?php } ?>



