<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>"
          media="all"/>
    <style>
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
                <header class="entry-header">
	                <?php if ( has_post_thumbnail() ) : ?>
                        <div class="post-thumbnail" style="height: 300px; width: 100%; background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>'); background-position: center; background-size: cover;"></div>
	                <?php endif; ?>
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>
                <div class="entry-content">
					<?php the_content(); ?>
                </div>
            </article>
		<?php
		endwhile;
	endif; ?>
</main>
</body>
</html>
