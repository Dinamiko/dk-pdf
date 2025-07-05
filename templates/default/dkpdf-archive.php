<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>"
          media="all"/>
    <style>
        a, code, ins, kbd, tt {background-color: transparent;}
        code {background-color: transparent;}
        ins {background-color: transparent;}
        kbd {background-color: transparent;}
        tt {background-color: transparent;}

        .posts-container {
            width: 100%;
            border-collapse: collapse;
        }
        .post-item {
            padding: 10px;
            vertical-align: top;
        }
        .post-thumbnail img {
            max-width: 100%;
            height: auto;
        }

        <?php
            $css = get_option( 'dkpdf_pdf_custom_css', '' );
            echo esc_attr($css);
        ?>

    </style>
</head>
<body>

<?php
// Get taxonomy display options
$taxonomy_display_options = get_option( 'dkpdf_taxonomy_display', array() );

// Ensure taxonomy_display_options is an array
if ( ! is_array( $taxonomy_display_options ) ) {
	$taxonomy_display_options = empty( $taxonomy_display_options ) ? array() : array( $taxonomy_display_options );
}

// Get layout columns option
$columns = intval( get_option( 'dkpdf_taxonomy_layout', '1' ) );
// Validate columns (must be between 1 and 4)
if ( $columns < 1 || $columns > 4 ) {
	$columns = 1; // default to 1 column
}

// Only display archive header if title is selected
if ( in_array( 'title', $taxonomy_display_options ) ) {
	?>
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
	<?php
}

// Only display archive description if description is selected
if ( in_array( 'description', $taxonomy_display_options ) && ( is_category() || is_tag() ) && term_description() ) {
	echo '<div class="archive-description">' . term_description() . '</div>';
}

// Initialize counter to track columns
$counter = 0;

// Start table-based layout
echo '<table class="posts-container">';

if ( have_posts() ) :
	// Start with a new row
	echo '<tr>';

	while ( have_posts() ) : the_post();
		// Start a new cell
		echo '<td class="post-item" width="' . ( 100 / $columns ) . '%">';

		// Display title if selected
		if ( in_array( 'title', $taxonomy_display_options ) ) {
			?>
            <h3 class="post-title">
                <a href="<?php the_permalink(); ?>" target="_blank">
					<?php the_title(); ?>
                </a>
            </h3>
			<?php
		}

		// Display post date if selected
		if ( in_array( 'post_date', $taxonomy_display_options ) ) {
			?>
            <div class="post-meta">
				<?php echo get_the_date( 'F j, Y' ); ?>
            </div>
			<?php
		}

		// Display thumbnail if selected
		if ( in_array( 'post_thumbnail', $taxonomy_display_options ) && has_post_thumbnail() ) {
			?>
            <div class="post-thumbnail">
				<?php the_post_thumbnail( 'thumbnail' ); ?>
            </div>
			<?php
		}

		// Display post excerpt if selected
		if ( in_array( 'post_excerpt', $taxonomy_display_options ) ) {
			?>
            <div class="post-excerpt">
				<?php echo wp_trim_words( get_the_excerpt(), 20, '...' ); ?>
            </div>
			<?php
		}

		// Close the cell
		echo '</td>';

		$counter ++;

		// If we've reached the number of columns, end this row and start a new one
		if ( $counter % $columns === 0 ) {
			echo '</tr><tr>';
		}
	endwhile;

	// Fill any remaining cells in the last row with empty cells
	$remaining = $columns - ( $counter % $columns );
	if ( $remaining > 0 && $remaining < $columns ) {
		for ( $i = 0; $i < $remaining; $i ++ ) {
			echo '<td width="' . ( 100 / $columns ) . '%"></td>';
		}
	}

	// Close the last row
	echo '</tr>';
endif;

// Close the table
echo '</table>';
?>

</body>
</html>
