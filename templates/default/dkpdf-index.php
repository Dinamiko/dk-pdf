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
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
						<?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
                    </header>
                    <div class="entry-content">
						<?php the_content(); ?>
                    </div>
                </article>
			<?php endwhile; ?>
		<?php else : ?>
            <p>No posts found.</p>
		<?php endif; ?>

    </main>
</div>
</body>
</html>
