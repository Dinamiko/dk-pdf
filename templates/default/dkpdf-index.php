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
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <?php
                    // Get post display options
                    $post_display_options = get_option('dkpdf_post_display', array());

                    // Only render elements that are specifically selected in post_display options

                    // Display title if selected
                    if (in_array('title', $post_display_options)) {
                        ?>
                        <header class="entry-header">
                            <?php the_title('<h2 class="entry-title">', '</h2>'); ?>
                        </header>
                        <?php
                    }

                    // Display post date if selected
                    if (in_array('post_date', $post_display_options)) {
                        ?>
                        <div class="entry-meta">
                            <span class="posted-on">
                                <?php echo esc_html__('Posted on', 'dkpdf') . ' ' . get_the_date(); ?>
                            </span>
                        </div>
                        <?php
                    }

                    // Display featured image if selected
                    if (in_array('featured_img', $post_display_options) && has_post_thumbnail()) {
                        ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail('large'); ?>
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
			<?php endwhile; ?>
		<?php else : ?>
            <p>No posts found.</p>
		<?php endif; ?>
    </main>
</div>
</body>
</html>
