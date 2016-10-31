<?php 
/**
* dkpdf-header.php
* This template is used to display content in PDF Header
*
* Do not edit this template directly, 
* copy this template and paste in your theme inside a directory named dkpdf 
*/ 
?>

<?php 
  	global $post;
  	$pdf_header_image = sanitize_option( 'dkpdf_pdf_header_image', get_option( 'dkpdf_pdf_header_image' ) );
    $pdf_header_image_attachment = wp_get_attachment_image_src( $pdf_header_image, 'full' );
    $pdf_header_show_title = sanitize_option( 'dkpdf_pdf_header_show_title', get_option( 'dkpdf_pdf_header_show_title' ) );
    $pdf_header_show_pagination = sanitize_option( 'dkpdf_pdf_header_show_pagination', get_option( 'dkpdf_pdf_header_show_pagination' ) );
?>

<?php
	// only enter here if any of the settings exists
	if( $pdf_header_image || $pdf_header_show_title || $pdf_header_show_pagination ) { ?>

		<div style="width:100%;float:left;">

			<?php
				// check if Header logo exists
				if( $pdf_header_image_attachment ) { ?>

					<div style="width:20%;float:left;">
						<img style="width:auto;height:55px;" src="<?php echo $pdf_header_image_attachment[0];?>">
					</div>

				<?php } 

			?>

			<div style="width:75%;float:right;text-align:right;height:35px;padding-top:20px;">

				<?php
					// check if Header show title is checked
					if ( $pdf_header_show_title ) {

						echo apply_filters( 'dkpdf_header_title', get_the_title( $post->ID ) );

					} 

				?>

				<?php
					// check if Header show pagination is checked
					if ( $pdf_header_show_pagination ) {

						echo apply_filters( 'dkpdf_header_pagination', '| {PAGENO}' );

					} 

				?>

			</div>

		</div>

	<?php }

?>



