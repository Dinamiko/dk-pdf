<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* displays pdf button
*/
function dkpdf_display_pdf_button( $content ) {

  // if is generated pdf don't show pdf button
  $pdf = get_query_var( 'pdf' );

  if( apply_filters( 'dkpdf_hide_button_isset', isset( $_POST['dkpdfg_action_create'] ) ) ) {

    if ( $pdf || apply_filters( 'dkpdf_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' )  ) {

        remove_shortcode('dkpdf-button');
        $content = str_replace( "[dkpdf-button]", "", $content );

        return $content;

    }

  } else {

    if ( $pdf ) {

        remove_shortcode('dkpdf-button');
        $content = str_replace( "[dkpdf-button]", "", $content );

        return $content;

    }

  }

  global $post;
  $post_type = get_post_type( $post->ID );

  $option_post_types = sanitize_option( 'dkpdf_pdfbutton_post_types', get_option( 'dkpdf_pdfbutton_post_types', array() ) );

  // TODO button checkboxes?
  if ( is_archive() || is_front_page() || is_home() ) { return $content; }

  // return content if not checked
  if( $option_post_types ) {

      if ( ! in_array( get_post_type( $post ), $option_post_types ) ) {

        return $content;

      }

  }

  if( $option_post_types ) {

      if ( in_array( get_post_type( $post ), $option_post_types ) ) {

        $c = $content;

        $pdfbutton_position = sanitize_option( 'dkpdf_pdfbutton_position', get_option( 'dkpdf_pdfbutton_position', 'before' ) );

        $template = new DKPDF_Template_Loader;

        if( $pdfbutton_position ) {

            if ( $pdfbutton_position == 'shortcode' ) {
              return $c;
            }

            if( $pdfbutton_position == 'before' ) {

              ob_start();

              $content = $template->get_template_part( 'dkpdf-button' );

              return ob_get_clean() . $c;


            } else if ( $pdfbutton_position == 'after' ) {

              ob_start();

              $content = $template->get_template_part( 'dkpdf-button' );

              return $c . ob_get_clean();

            }

        }

      }

  } else {

    return $content;

  }

}

add_filter( 'the_content', 'dkpdf_display_pdf_button' );

/**
* output the pdf
*/
function dkpdf_output_pdf( $query ) {

  $pdf = sanitize_text_field( get_query_var( 'pdf' ) );

  if( $pdf ) {

      include('mpdf60/mpdf.php');

      // page orientation
      $dkpdf_page_orientation = get_option( 'dkpdf_page_orientation', '' );

      if ( $dkpdf_page_orientation == 'horizontal') {

        $format = apply_filters( 'dkpdf_pdf_format', 'A4' ).'-L';

      } else {

        $format = apply_filters( 'dkpdf_pdf_format', 'A4' );

      }

      // font size
      $dkpdf_font_size = get_option( 'dkpdf_font_size', '12' );
      $dkpdf_font_family = '';

      // margins
      $dkpdf_margin_left = get_option( 'dkpdf_margin_left', '15' );
      $dkpdf_margin_right = get_option( 'dkpdf_margin_right', '15' );
      $dkpdf_margin_top = get_option( 'dkpdf_margin_top', '50' );
      $dkpdf_margin_bottom = get_option( 'dkpdf_margin_bottom', '30' );
      $dkpdf_margin_header = get_option( 'dkpdf_margin_header', '15' );

      // creating and setting the pdf
      $mpdf = new mPDF('utf-8', $format, $dkpdf_font_size, $dkpdf_font_family,
        $dkpdf_margin_left, $dkpdf_margin_right, $dkpdf_margin_top, $dkpdf_margin_bottom, $dkpdf_margin_header
      );

      // encrypts and sets the PDF document permissions
      // https://mpdf.github.io/reference/mpdf-functions/setprotection.html
      $enable_protection = get_option( 'dkpdf_enable_protection' );

      if( $enable_protection == 'on' ) {
        $grant_permissions = get_option( 'dkpdf_grant_permissions' );
        $mpdf->SetProtection( $grant_permissions );
      }

      // keep columns
      $keep_columns = get_option( 'dkpdf_keep_columns' );

      if( $keep_columns == 'on' ) {
        $mpdf->keepColumns = true;
      }

      /*
      // make chinese characters work in the pdf
      $mpdf->useAdobeCJK = true;
      $mpdf->autoScriptToLang = true;
      $mpdf->autoLangToFont = true;
      */

      // header
      $pdf_header_html = dkpdf_get_template( 'dkpdf-header' );
      $mpdf->SetHTMLHeader( $pdf_header_html );

      // footer
      $pdf_footer_html = dkpdf_get_template( 'dkpdf-footer' );
      $mpdf->SetHTMLFooter( $pdf_footer_html );

      $mpdf->WriteHTML( apply_filters( 'dkpdf_before_content', '' ) );
      $mpdf->WriteHTML( dkpdf_get_template( 'dkpdf-index' ) );
      $mpdf->WriteHTML( apply_filters( 'dkpdf_after_content', '' ) );

      // action to do (open or download)
      $pdfbutton_action = sanitize_option( 'dkpdf_pdfbutton_action', get_option( 'dkpdf_pdfbutton_action', 'open' ) );

      $title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );

      $mpdf->SetTitle( $title );
      $mpdf->SetAuthor( apply_filters( 'dkpdf_pdf_author', get_bloginfo( 'name' ) ) );

      global $post;

      if( $pdfbutton_action == 'open') {

        $mpdf->Output( $title.'.pdf', 'I' );

      } else {

        $mpdf->Output($title.'.pdf', 'D' );

      }

      exit;

  }

}

add_action( 'wp', 'dkpdf_output_pdf' );

/**
* returs a template
* @param string template name
*/
function dkpdf_get_template( $template_name ) {

    $template = new DKPDF_Template_Loader;

    ob_start();
    $template->get_template_part( $template_name );
    return ob_get_clean();

}

/**
* returns an array of active post, page, attachment and custom post types
* @return array
*/
function dkpdf_get_post_types() {

    $args = array(
       'public'   => true,
       '_builtin' => false
    );

    $post_types = get_post_types( $args );
    $post_arr = array( 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' );

    foreach ( $post_types  as $post_type ) {

      $arr = array( $post_type => $post_type );
      $post_arr += $arr;

    }

    $post_arr = apply_filters( 'dkpdf' . '_posts_arr', $post_arr );

    return $post_arr;

}

/**
* set query_vars
*/
function dkpdf_set_query_vars( $query_vars ) {

  $query_vars[] = 'pdf';

  return $query_vars;

}

add_filter( 'query_vars', 'dkpdf_set_query_vars' );

/**
* sanitizes dkpdf options
*/
function dkpdf_sanitize_options() {

    add_filter( 'pre_update_option_dkpdf_pdfbutton_text', 'dkpdf_update_field_dkpdf_pdfbutton_text', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdfbutton_post_types', 'dkpdf_update_field_dkpdf_pdfbutton_post_types', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdfbutton_action', 'dkpdf_update_field_dkpdf_pdfbutton_action', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdfbutton_position', 'dkpdf_update_field_dkpdf_pdfbutton_position', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdfbutton_align', 'dkpdf_update_field_dkpdf_pdfbutton_align', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_page_orientation', 'dkpdf_update_field_dkpdf_page_orientation', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_font_size', 'dkpdf_update_field_dkpdf_font_size', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_margin_left', 'dkpdf_update_field_dkpdf_margin_left', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_margin_right', 'dkpdf_update_field_dkpdf_margin_right', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_margin_top', 'dkpdf_update_field_dkpdf_margin_top', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_margin_bottom', 'dkpdf_update_field_dkpdf_margin_bottom', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_margin_header', 'dkpdf_update_field_dkpdf_margin_header', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_header_image', 'dkpdf_update_field_dkpdf_pdf_header_image', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_header_show_title', 'dkpdf_update_field_dkpdf_pdf_header_show_title', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_header_show_pagination', 'dkpdf_update_field_dkpdf_pdf_header_show_pagination', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_footer_text', 'dkpdf_update_field_dkpdf_pdf_footer_text', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_footer_show_title', 'dkpdf_update_field_dkpdf_pdf_footer_show_title', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_footer_show_pagination', 'dkpdf_update_field_dkpdf_pdf_footer_show_pagination', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_pdf_custom_css', 'dkpdf_update_field_dkpdf_pdf_custom_css', 10, 2 );
    add_filter( 'pre_update_option_dkpdf_print_wp_head', 'dkpdf_update_field_dkpdf_print_wp_head', 10, 2 );


}

add_action( 'init', 'dkpdf_sanitize_options' );

/**
* sanitizes dkpdf_pdfbutton_text option
*/
function dkpdf_update_field_dkpdf_pdfbutton_text( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdfbutton_post_types option
*/
function dkpdf_update_field_dkpdf_pdfbutton_post_types( $new_value, $old_value ) {
    // TODO sanitize_text_field doesn't work
    //$new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdfbutton_action option
*/
function dkpdf_update_field_dkpdf_pdfbutton_action( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdfbutton_position option
*/
function dkpdf_update_field_dkpdf_pdfbutton_position( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdfbutton_align option
*/
function dkpdf_update_field_dkpdf_pdfbutton_align( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_page_orientation option
*/
function dkpdf_update_field_dkpdf_page_orientation( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_font_size option
*/
function dkpdf_update_field_dkpdf_font_size( $new_value, $old_value ) {
    $new_value = intval( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_margin_left option
*/
function dkpdf_update_field_dkpdf_margin_left( $new_value, $old_value ) {
    $new_value = intval( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_margin_right option
*/
function dkpdf_update_field_dkpdf_margin_right( $new_value, $old_value ) {
    $new_value = intval( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_margin_top option
*/
function dkpdf_update_field_dkpdf_margin_top( $new_value, $old_value ) {
    $new_value = intval( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_margin_bottom option
*/
function dkpdf_update_field_dkpdf_margin_bottom( $new_value, $old_value ) {
    $new_value = intval( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_margin_header option
*/
function dkpdf_update_field_dkpdf_margin_header( $new_value, $old_value ) {
    $new_value = intval( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdf_header_image option
*/
function dkpdf_update_field_dkpdf_pdf_header_image( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdf_header_show_title option
*/
function dkpdf_update_field_dkpdf_pdf_header_show_title( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdf_header_show_pagination option
*/
function dkpdf_update_field_dkpdf_pdf_header_show_pagination( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdf_footer_text option
*/
function dkpdf_update_field_dkpdf_pdf_footer_text( $new_value, $old_value ) {

    $arr = array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'br' => array(),
        'em' => array(),
        'strong' => array(),
        'hr' => array(),
        'p' => array(
           'title' => array(),
           'class' => array(),
           'style' => array()
        ),
        'h1' => array(
           'title' => array(),
           'class' => array(),
           'style' => array()
        ),
        'h2' => array(
           'title' => array(),
           'class' => array(),
           'style' => array()
        ),
        'h3' => array(
           'title' => array(),
           'class' => array(),
           'style' => array()
        ),
        'h4' => array(
           'title' => array(),
           'class' => array(),
           'style' => array()
        ),
        'div' => array(
           'title' => array(),
           'class' => array(),
           'style' => array()
        )
    );

    $new_value = wp_kses( $new_value, $arr );
    return $new_value;

}

/**
* sanitizes dkpdf_pdf_header_show_pagination option
*/
function dkpdf_update_field_dkpdf_pdf_footer_show_title( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdf_header_show_pagination option
*/
function dkpdf_update_field_dkpdf_pdf_footer_show_pagination( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
* sanitizes dkpdf_pdf_custom_css option
*/
function dkpdf_update_field_dkpdf_pdf_custom_css( $new_value, $old_value ) {
    $new_value = wp_filter_nohtml_kses( $new_value );
    $new_value = str_replace('\"', '"', $new_value);
    $new_value = str_replace("\'", "'", $new_value);
    return $new_value;
}

/**
* sanitizes dkpdf_print_wp_head option
*/
function dkpdf_update_field_dkpdf_print_wp_head( $new_value, $old_value ) {
    $new_value = sanitize_text_field( $new_value );
    return $new_value;
}
