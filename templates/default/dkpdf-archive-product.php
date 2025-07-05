<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>"
          media="all"/>
    <style>
        a, code, ins, kbd, tt {background-color: transparent;}
        .screen-reader-text {
            display: none;
        }
        ins, [aria-hidden="true"] {
            background-color: transparent;
            background: none;
            font-weight: bold;
        }

        .posts-container {
            width: 100%;
            border-collapse: collapse;
        }
        .product-item {
            padding: 15px;
            vertical-align: top;
        }
        .product-thumbnail {
            text-align: center;
            margin-bottom: 10px;
        }
        .product-thumbnail img {
            max-width: 100%;
            height: auto;
        }
        .product-title {
            font-size: 16px;
            margin: 10px 0 5px;
            font-weight: bold;
            color: #333;
        }
        .product-price {
            font-size: 14px;
            color: #555;
            margin: 5px 0;
        }
        .sale-price {
            color: #cc0000;
        }
        .regular-price {
            text-decoration: line-through;
            color: #888;
            font-size: 12px;
        }
        .archive-header {
            margin-bottom: 20px;
        }
        .archive-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        .archive-description {
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
        }

        <?php
            $css = get_option( 'dkpdf_pdf_custom_css', '' );
            echo esc_attr($css);
        ?>
    </style>
</head>
<body>

<?php
// Get WooCommerce product display options for archives
$wc_archive_display_options = get_option('dkpdf_wc_archive_display', array());

// Ensure wc_archive_display_options is an array
if (!is_array($wc_archive_display_options)) {
    $wc_archive_display_options = empty($wc_archive_display_options) ? array() : array($wc_archive_display_options);
}

// Get layout columns option (will use taxonomy_layout as fallback until we create wc_archive_layout)
$columns = intval(get_option('dkpdf_wc_archive_layout', get_option('dkpdf_taxonomy_layout', '1')));
// Validate columns (must be between 1 and 4)
if ($columns < 1 || $columns > 4) {
    $columns = 2; // default to 2 columns for product display
}

// Display the archive header
if (is_shop()) {
    echo '<div class="archive-header">';
    // Check if WooCommerce function exists before calling it
    if (function_exists('wc_get_page_title')) {
        echo '<h2 class="archive-title">' . esc_html(wc_get_page_title()) . '</h2>';
    } else {
        echo '<h2 class="archive-title">' . esc_html__('Shop', 'dkpdf') . '</h2>';
    }
    echo '</div>';
} elseif (is_product_category() || is_product_tag()) {
    echo '<div class="archive-header">';
    echo '<h2 class="archive-title">' . esc_html(single_term_title('', false)) . '</h2>';

    // Display term description if it exists
    $term_description = term_description();
    if (!empty($term_description)) {
        echo '<div class="archive-description">' . $term_description . '</div>';
    }
    echo '</div>';
}

// Initialize counter to track columns
$counter = 0;

// Start table-based layout
echo '<table class="posts-container">';

if (have_posts()) :
    // Start with a new row
    echo '<tr>';

    while (have_posts()) : the_post();
        global $product;

        // Make sure we have a product
        if (!is_a($product, 'WC_Product') && function_exists('wc_get_product')) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product) {
            continue; // Skip if not a valid product
        }

        // Start a new cell
        echo '<td class="product-item" width="' . (100/$columns) . '%">';

        // Display product thumbnail ONLY if selected in wc_archive_display
        if (in_array('product_thumbnail', $wc_archive_display_options)) {
            if (has_post_thumbnail()) {
                echo '<div class="product-thumbnail">';
                $image_id = get_post_thumbnail_id();
                $image_url = wp_get_attachment_image_src($image_id, 'medium');
                if ($image_url) {
                    echo '<img src="' . esc_url($image_url[0]) . '" alt="' . esc_attr(get_the_title()) . '" />';
                }
                echo '</div>';
            }
        }

        // Display product title ONLY if selected in wc_archive_display
        if (in_array('title', $wc_archive_display_options)) {
            echo '<h3 class="product-title">' . get_the_title() . '</h3>';
        }

        // Display product price ONLY if selected in wc_archive_display
        if (in_array('price', $wc_archive_display_options)) {
            if ($price_html = $product->get_price_html()) {
                echo '<div class="product-price">' . $price_html . '</div>';
            }
        }

        // Display SKU ONLY if selected in wc_archive_display and SKU exists
        if (in_array('sku', $wc_archive_display_options) && $product->get_sku()) {
            echo '<div class="product-sku"><small>SKU: ' . esc_html($product->get_sku()) . '</small></div>';
        }

        // Close the cell
        echo '</td>';

        $counter++;

        // If we've reached the number of columns, end this row and start a new one
        if ($counter % $columns === 0) {
            echo '</tr><tr>';
        }
    endwhile;

    // Fill any remaining cells in the last row with empty cells
    $remaining = $columns - ($counter % $columns);
    if ($remaining > 0 && $remaining < $columns) {
        for ($i = 0; $i < $remaining; $i++) {
            echo '<td width="' . (100/$columns) . '%"></td>';
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
