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
			if ( $pdf_footer_show_title ) {
				$footer_title = '';

                if ( function_exists( 'is_shop' ) && is_shop() ) {
                    $shop_page_id = get_option( 'woocommerce_shop_page_id' );
                    $footer_title = $shop_page_id ? get_the_title( $shop_page_id ) : __( 'Shop', 'dkpdf' );
                }
                elseif ( is_tax() || is_category() ) {
                    $queried_object = get_queried_object();
                    $footer_title = $queried_object ? $queried_object->name : '';
                }
                elseif ( $post ) {
                    $footer_title = get_the_title( $post->ID );
                }

				$footer_title = apply_filters( 'dkpdf_footer_title', $footer_title );
				echo wp_kses_post( $footer_title );
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



