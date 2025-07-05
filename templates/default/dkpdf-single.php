<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>"
          media="all"/>
    <style>
        a, code, ins, kbd, tt {background-color: transparent;}

        <?php
            $css = get_option( 'dkpdf_pdf_custom_css', '' );
            echo esc_attr($css);
        ?>
    </style>
</head>
<body>
<main id="primary" class="site-main">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) : the_post();
			?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php
                $post_display_options = get_option( 'dkpdf_post_display', [] );
                if ( ! is_array( $post_display_options ) ) {
	                $post_display_options = [];
                }

                // Only display header if title or featured image are selected
                if (in_array('title', $post_display_options) || (in_array('featured_img', $post_display_options) && has_post_thumbnail())) {
                    ?>
                    <header class="entry-header">
                        <?php
                        // Display featured image if selected
                        if (in_array('featured_img', $post_display_options) && has_post_thumbnail()) :
                            ?>
                            <div class="post-thumbnail" style="height: 300px; width: 100%; background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>'); background-position: center; background-size: cover;"></div>
                        <?php endif; ?>

                        <?php
                        // Display title if selected
                        if (in_array('title', $post_display_options)) :
                            ?>
                            <h1 class="entry-title"><?php the_title(); ?></h1>
                        <?php endif; ?>
                    </header>
                    <?php
                }

                // Display post date if selected
                if (in_array('post_date', $post_display_options)) {
                    ?>
                    <div class="entry-meta">
                        <span class="posted-on">
                            <?php echo esc_html__('Posted on', 'dkpdf') . ' ' . get_the_date(); ?>
                            <?php
                            // Add author if selected in options
                            if (in_array('post_author', $post_display_options)) {
                                echo ' ' . esc_html__('by', 'dkpdf') . ' ' . get_the_author();
                            }
                            ?>
                        </span>
                    </div>
                    <?php
                }
                // Display only author if date is not selected but author is
                elseif (in_array('post_author', $post_display_options)) {
                    ?>
                    <div class="entry-meta">
                        <span class="posted-by">
                            <?php echo esc_html__('By', 'dkpdf') . ' ' . get_the_author(); ?>
                        </span>
                    </div>
                    <?php
                }

                // Only display the content if content option is selected
                if (in_array('content', $post_display_options)) {
                    ?>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                    <?php
                }
                ?>
            </article>
		<?php
		endwhile;
	endif; ?>
</main>
</body>
</html>
