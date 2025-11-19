<html>
<head>
    <?php if ( get_option( 'dkpdf_load_theme_css', 'on' ) === 'on' ) { ?>
        <link type="text/css" rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>"
              media="all"/>
    <?php } ?>
    <style>
        a, code, ins, kbd, tt {background-color: transparent;}

        .custom-fields-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .custom-fields-section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .custom-field-item {
            margin-bottom: 5px;
            line-height: 1.4;
        }

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

                    // Display custom fields if not using legacy template and fields are selected
                    $selected_template = get_option( 'dkpdf_selected_template', '' );
                    if ( ! empty( $selected_template ) ) {
                        $custom_fields_html = apply_filters( 'dkpdf_get_custom_fields_display', '', get_the_ID() );
                        if ( ! empty( $custom_fields_html ) ) {
                            echo $custom_fields_html;
                        }
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
