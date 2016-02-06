<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* adds dashboard page 
*/
function dkpdf_welcome_screen_page(){
    add_dashboard_page('DK PDF Welcome', 'DK PDF Welcome', 'manage_options', 'dkpdf-welcome', 'dkpdf_welcome_page');
}

// output dkpdf-welcome dashboard page 
function dkpdf_welcome_page(){ ?>

    <div class="wrap">

      <h1>Welcome to DK PDF <?php echo DKPDF_VERSION;?></h1>
      <h2 style="font-size:140%;">What's new in this version:</h2>
      <ul>
        <li>
          <h3 style="margin-top:20px;">DK PDF admin menu</h3>
          <?php 
            $img1 = plugins_url( 'assets/images/dkpdf-admin-menu.jpg', DKPDF_PLUGIN_FILE );
          ?>
          <img style="margin-bottom:20px;width:100%;height:auto;"src="<?php echo $img1;?>">
        </li>
        <li>
          <h3 style="margin-top:20px;">PDF Setup tab for adjusting page orientation, font size and margins of the PDF</h3>
          <?php 
            $img2 = plugins_url( 'assets/images/dkpdf-setup-tab.jpg', DKPDF_PLUGIN_FILE );
          ?>
          <img style="margin-bottom:20px;width:100%;height:auto;"src="<?php echo $img2;?>">
        </li>
        <li>
          <h3 style="margin-top:20px;">[dkpdf-remove] shortcode for removing pieces of content in the generated PDF</h3></li>
          <p><a href="http://wp.dinamiko.com/demos/dkpdf/doc/dkpdf-remove-shortcode/" target="_blank">See more info here</a></p>
      </ul>

    </div>

<?php }

add_action('admin_menu', 'dkpdf_welcome_screen_page');

/**
* Fires when plugin is activated or upgraded
*/
function dkpdf_welcome_redirect( $plugin ) {

   if( $plugin == 'dk-pdf/dk-pdf.php' ) {

       wp_redirect( admin_url( 'index.php?page=dkpdf-welcome' ) );
       die();

   }
}

add_action( 'activated_plugin', 'dkpdf_welcome_redirect' );

/**
* removes dkpdf-welcome link in Dashboard submenu 
*/
function dkpdf_remove_menu_entry(){
    remove_submenu_page( 'index.php', 'dkpdf-welcome' );
}

add_action( 'admin_head', 'dkpdf_remove_menu_entry' );








