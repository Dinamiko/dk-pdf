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
