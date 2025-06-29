<?php
global $post;
$pdf_header_image            = sanitize_option( 'dkpdf_pdf_header_image', get_option( 'dkpdf_pdf_header_image' ) );
$pdf_header_image_attachment = wp_get_attachment_image_src( $pdf_header_image, 'full' );
$pdf_header_show_title       = sanitize_option( 'dkpdf_pdf_header_show_title', get_option( 'dkpdf_pdf_header_show_title' ) );
$pdf_header_show_pagination  = sanitize_option( 'dkpdf_pdf_header_show_pagination', get_option( 'dkpdf_pdf_header_show_pagination' ) );
?>

<?php
if ( $pdf_header_image || $pdf_header_show_title || $pdf_header_show_pagination ) { ?>
    <div style="width:100%;float:left;">
		<?php
		if ( $pdf_header_image_attachment ) { ?>
            <div style="width:20%;float:left;">
                <img style="width:auto;height:55px;" src="<?php echo esc_url( $pdf_header_image_attachment[0] ); ?>">
            </div>
		<?php } ?>

        <div style="width:75%;float:right;text-align:right;height:35px;padding-top:20px;">
			<?php
			if ( $post && $pdf_header_show_title ) {
				$header_title = apply_filters( 'dkpdf_header_title', get_the_title( $post->ID ) );
				echo wp_kses_post( $header_title );
			}
			?>

			<?php
			if ( $pdf_header_show_pagination ) {
				$pagination = apply_filters( 'dkpdf_header_pagination', '| {PAGENO}' );
				echo esc_attr( $pagination );
			}
			?>
        </div>
    </div>
<?php } ?>



