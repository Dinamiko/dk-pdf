<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .archive-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .archive-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .post-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            page-break-inside: avoid;
        }
        .post-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .post-meta {
            font-size: 10px;
            color: #777;
            margin-bottom: 8px;
        }
        .post-excerpt {
            font-size: 12px;
        }
        .post-thumbnail img {
            max-width: 150px;
            max-height: 100px;
        }
    </style>
</head>
<body>

<div class="archive-header">
    <h1 class="archive-title">
        <?php
        if (is_category()) {
            echo single_cat_title('', false);
        } elseif (is_tag()) {
            echo single_tag_title('', false);
        } elseif (is_author()) {
            the_post();
            echo 'Author: ' . get_the_author();
            rewind_posts();
        } elseif (is_day()) {
            echo 'Daily Archives: ' . get_the_date();
        } elseif (is_month()) {
            echo 'Monthly Archives: ' . get_the_date('F Y');
        } elseif (is_year()) {
            echo 'Yearly Archives: ' . get_the_date('Y');
        } else {
            echo 'Archives';
        }
        ?>
    </h1>
</div>

<div class="posts-container">
    <?php if (have_posts()) :
        while (have_posts()) : the_post(); ?>
            <article class="post-item">
                <h2 class="post-title"><?php the_title(); ?></h2>

                <div class="post-meta">
                    <?php echo get_the_date('F j, Y'); ?>
                </div>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail">
                        <?php
                        the_post_thumbnail('thumbnail');
                        ?>
                    </div>
                <?php endif; ?>

                <div class="post-excerpt">
                    <?php
                    echo wp_trim_words(get_the_excerpt(), 20, '...');
                    ?>
                </div>
            </article>
    <?php
        endwhile; ?>


    <?php else : ?>
        <div class="no-posts">
            <p>No posts found.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
