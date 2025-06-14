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

<div class="archive-header">
    <h2 class="archive-title">
		<?php
		if ( is_category() ) {
			echo single_cat_title( '', false );
		} elseif ( is_tag() ) {
			echo single_tag_title( '', false );
		} elseif ( is_author() ) {
			the_post();
			echo 'Author: ' . get_the_author();
			rewind_posts();
		} elseif ( is_day() ) {
			echo 'Daily Archives: ' . get_the_date();
		} elseif ( is_month() ) {
			echo 'Monthly Archives: ' . get_the_date( 'F Y' );
		} elseif ( is_year() ) {
			echo 'Yearly Archives: ' . get_the_date( 'Y' );
		} else {
			echo 'Archives';
		}
		?>
    </h2>
</div>

<div class="posts-container">
	<?php if ( have_posts() ) :
		while ( have_posts() ) : the_post(); ?>
            <article class="post-item">
                <h3 class="post-title">
                    <a href="<?php the_permalink(); ?>" target="_blank">
						<?php the_title(); ?>
                    </a>
                </h3>

                <div class="post-meta">
					<?php echo get_the_date( 'F j, Y' ); ?>
                </div>

				<?php if ( has_post_thumbnail() ) : ?>
                    <div class="post-thumbnail">
						<?php
						the_post_thumbnail( 'thumbnail' );
						?>
                    </div>
				<?php endif; ?>

                <div class="post-excerpt">
					<?php
					echo wp_trim_words( get_the_excerpt(), 20, '...' );
					?>
                </div>
            </article>
		<?php
		endwhile; ?>
	<?php endif; ?>
</div>

</body>
</html>
