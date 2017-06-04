<?php
/**
* dkpdf-index.php
* This template is used to display the content in the PDF
*
* Do not edit this template directly,
* copy this template and paste in your theme inside a directory named dkpdf
*/
?>

<html>
    <head>
      	<link type="text/css" rel="stylesheet" href="<?php echo get_bloginfo( 'stylesheet_url' ); ?>" media="all" />
      	<?php
      		$wp_head = get_option( 'dkpdf_print_wp_head', '' );
      		if( $wp_head == 'on' ) {
      			wp_head();
      		}
      	?>
      	<style type="text/css">
      		body {
      			background:#FFF;
      			font-size: 100%;
      		}
          /* fontawesome compatibility */
          .fa {
              font-family: fontawesome;
              display: inline-block;
              font: normal normal normal 14px/1 FontAwesome;
              font-size: inherit;
              text-rendering: auto;
              -webkit-font-smoothing: antialiased;
              -moz-osx-font-smoothing: grayscale;
              transform: translate(0, 0);
          }

			<?php
				// get pdf custom css option
				$css = get_option( 'dkpdf_pdf_custom_css', '' );
				echo $css;
			?>

		</style>

   	</head>

    <body>

	    <?php

	    global $post;
	    $pdf  = get_query_var( 'pdf' );
	    $post_type = get_post_type( $pdf );

	    // if is attachment
	    if( $post_type == 'attachment') { ?>

	    	<div style="width:100%;float:left;">

	    		<?php
	    			// image
	    			$image = wp_get_attachment_image( $post->ID, 'full' );

	    			if( $image ) {

	    				echo $image;

	    			}

	    		?>

	    		<?php
	    			// caption, description
		    		$thumb_img = get_post( get_post_thumbnail_id() );

		    		if( $thumb_img ) {

						echo '<p style="margin-top:30px;">Caption: ' . $thumb_img->post_excerpt .'</p>';
						echo '<p>Description: ' . $thumb_img->post_content .'</p>';

		    		}

	    		?>

	    		<?php
	    			// metadata
	    			$metadata =  wp_get_attachment_metadata( $post->ID );

	    			if( $metadata ) {

	    				$metadata_width = $metadata['width'];
	    				$metadata_height = $metadata['height'];
	    				$image_meta = $metadata['image_meta'];

	    				echo '<p style="margin-top:30px;">Dimensions: '. $metadata_width .' x '. $metadata_height .'</p>';

	    				// image metadata
	    				foreach ($image_meta as $key => $value) {

	    				 	echo $key. ': '. $value .'<br>';

	    				}

	    			}

	    		?>

	    	</div>

	    	<?php

	    } else {

  			$args = array(
  			 	'p' => $pdf,
  			 	'post_type' => $post_type,
  			 	'post_status' => 'publish'
  			);

		    $the_query = new WP_Query( apply_filters( 'dkpdf_query_args', $args ) );

		    if ( $the_query->have_posts() ) {

		    	while ( $the_query->have_posts() ) {
		    	    $the_query->the_post();
		    	    global $post;
		    	    ?>

		    	    <div class="dkpdf-content">

		    	    	<?php the_content(); ?>

		    		</div>

		    	<?php }

		    } else {

		    	echo 'no results';
		    }

		    wp_reset_postdata();

	    }

		?>

    </body>

</html>
