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
<body style="margin: 0; padding: 0;">

<div style="margin-top: 0;">
    <h2 style="margin-top: 0;">
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

<table border="0" cellpadding="10" cellspacing="0" width="100%">
<?php
// Prepare posts array
$posts_array = array();
if (have_posts()) {
    while (have_posts()) {
        the_post();
        $posts_array[] = array(
            'title' => get_the_title(),
            'permalink' => get_the_permalink(),
            'date' => get_the_date('F j, Y'),
            'has_thumbnail' => has_post_thumbnail(),
            'thumbnail' => get_the_post_thumbnail('thumbnail'),
            'excerpt' => wp_trim_words(get_the_excerpt(), 20, '...')
        );
    }
}

$total_posts = count($posts_array);
$counter = 0;

// Process posts in pairs
while ($counter < $total_posts) {
    echo '<tr>';

    // Left column post
    echo '<td width="50%" valign="top" style="padding-right: 10px;">';
    if (isset($posts_array[$counter])) {
        $post = $posts_array[$counter];
        ?>
        <div style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 5px;">
                <a href="<?php echo $post['permalink']; ?>" target="_blank" style="text-decoration: none; color: #333;">
                    <?php echo $post['title']; ?>
                </a>
            </h3>

            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                <?php echo $post['date']; ?>
            </div>

            <div style="margin-bottom: 10px; width: 150px; height: 100px; overflow: hidden;">
                <?php
                if ($post['has_thumbnail']) {
                    echo $post['thumbnail'];
                } else {
                    echo '<div style="width: 150px; height: 100px; background-color: #f0f0f0; text-align: center; line-height: 100px; color: #888; border: 1px solid #ddd;">No Image</div>';
                }
                ?>
            </div>

            <div style="font-size: 14px; line-height: 1.4;">
                <?php echo $post['excerpt']; ?>
            </div>
        </div>
        <?php
    }
    echo '</td>';

    // Right column post
    echo '<td width="50%" valign="top" style="padding-left: 10px;">';
    if (isset($posts_array[$counter + 1])) {
        $post = $posts_array[$counter + 1];
        ?>
        <div style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 5px;">
                <a href="<?php echo $post['permalink']; ?>" target="_blank" style="text-decoration: none; color: #333;">
                    <?php echo $post['title']; ?>
                </a>
            </h3>

            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                <?php echo $post['date']; ?>
            </div>

            <div style="margin-bottom: 10px; width: 150px; height: 100px; overflow: hidden;">
                <?php
                if ($post['has_thumbnail']) {
                    echo $post['thumbnail'];
                } else {
                    echo '<div style="width: 150px; height: 100px; background-color: #f0f0f0; text-align: center; line-height: 100px; color: #888; border: 1px solid #ddd;">No Image</div>';
                }
                ?>
            </div>

            <div style="font-size: 14px; line-height: 1.4;">
                <?php echo $post['excerpt']; ?>
            </div>
        </div>
        <?php
    }
    echo '</td>';

    echo '</tr>';
    $counter += 2;
}
?>
</table>

</body>
</html>
