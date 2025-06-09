<!DOCTYPE html>
<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url(get_bloginfo( 'stylesheet_url' )); ?>" media="all" />
    <title><?php wp_title('|', true, 'right'); ?></title>
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f9f9f9;
            font-size: 14px;
            padding: 20px;
        }

        /* Archive Header */
        .archive-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }

        .archive-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .archive-description {
            color: #666;
            font-style: italic;
            font-size: 16px;
        }

        /* Posts Container */
        .posts-container {
            display: block;
            width: 100%;
        }

        /* Post Item */
        .post-item {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            padding: 20px;
            border-left: 4px solid #3498db;
            page-break-inside: avoid;
        }

        .post-item:nth-child(3n+1) {
            border-left-color: #3498db;
        }

        .post-item:nth-child(3n+2) {
            border-left-color: #e74c3c;
        }

        .post-item:nth-child(3n+3) {
            border-left-color: #2ecc71;
        }

        .post-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .post-title a {
            text-decoration: none;
            color: #2c3e50;
        }

        .post-meta {
            font-size: 12px;
            color: #95a5a6;
            margin-bottom: 15px;
        }

        .post-thumbnail {
            margin-bottom: 15px;
            text-align: center;
        }

        .post-thumbnail img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .post-excerpt {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }

        .post-category {
            display: inline-block;
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: #666;
            margin-right: 5px;
        }

        .post-footer {
            margin-top: 15px;
            font-size: 12px;
            color: #7f8c8d;
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
        }

        /* Pagination */
        .pagination {
            margin-top: 30px;
            text-align: center;
        }

        /* No posts message */
        .no-posts {
            padding: 30px;
            text-align: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            font-style: italic;
            color: #95a5a6;
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
    <?php
    // Show an optional archive description
    $archive_description = get_the_archive_description();
    if ($archive_description) {
        echo '<div class="archive-description">' . $archive_description . '</div>';
    }
    ?>
</div>

<div class="posts-container">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('post-item'); ?>>
                <h2 class="post-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <div class="post-meta">
                    <?php echo get_the_date('F j, Y'); ?>
                    <?php if (get_the_category_list()) : ?>
                        | Categories: <?php echo get_the_category_list(', '); ?>
                    <?php endif; ?>
                </div>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail('medium'); ?>
                    </div>
                <?php endif; ?>

                <div class="post-excerpt">
                    <?php
                    if (has_excerpt()) {
                        the_excerpt();
                    } else {
                        echo wp_trim_words(get_the_content(), 40, '...');
                    }
                    ?>
                </div>

                <div class="post-footer">
                    <?php if (get_the_tag_list()) : ?>
                        Tags: <?php echo get_the_tag_list('', ', ', ''); ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endwhile; ?>

    <?php else : ?>
        <div class="no-posts">
            <p>No posts found.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
