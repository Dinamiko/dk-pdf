<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>"
          media="all"/>
    <style>
        a {background-color: transparent;}

        .product-container {
            font-family: Arial, sans-serif;
        }
        .product-header {
            margin-bottom: 20px;
        }
        .product-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 15px;
        }
        .product-image {
            margin-bottom: 20px;
            text-align: center;
        }
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        .product-description {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .product-meta {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .product-meta-item {
            margin-bottom: 5px;
        }

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
		    global $product;

		    // Make sure we have the product object
		    if (!is_a($product, 'WC_Product') && function_exists('wc_get_product')) {
		        $product = wc_get_product(get_the_ID());
		    }

		    // Get WooCommerce product display options
		    $wc_product_display_options = get_option('dkpdf_wc_product_display', array());

		    // Ensure wc_product_display_options is an array
		    if (!is_array($wc_product_display_options)) {
		        $wc_product_display_options = empty($wc_product_display_options) ? array() : array($wc_product_display_options);
		    }

		    if (!$product) {
		        // If not a product, just show regular content
		        ?>
		        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		            <header class="entry-header">
		                <h1 class="entry-title"><?php the_title(); ?></h1>
		            </header>
		            <div class="entry-content">
		                <?php the_content(); ?>
		            </div>
		        </article>
		        <?php
		    } else {
		        // This is a WooCommerce product
		        ?>
		        <article id="product-<?php the_ID(); ?>" <?php post_class('product-container'); ?>>
		            <header class="product-header">
		                <?php if (in_array('title', $wc_product_display_options)) : ?>
		                    <h1 class="product-title"><?php the_title(); ?></h1>
		                <?php endif; ?>

		                <?php if (in_array('price', $wc_product_display_options) && $product->get_price_html()) : ?>
		                    <div class="product-price">
		                        <?php echo $product->get_price_html(); ?>
		                    </div>
		                <?php endif; ?>
		            </header>

		            <?php if (in_array('product_img', $wc_product_display_options) && has_post_thumbnail()) : ?>
		                <div class="product-image">
		                    <?php
		                    $image_id = get_post_thumbnail_id();
		                    $image_url = wp_get_attachment_image_src($image_id, 'large');
		                    if ($image_url) {
		                        echo '<img src="' . esc_url($image_url[0]) . '" alt="' . esc_attr(get_the_title()) . '" />';
		                    }
		                    ?>
		                </div>
		            <?php endif; ?>

		            <?php if (in_array('description', $wc_product_display_options)) : ?>
		                <div class="product-description">
		                    <h2>Product Description</h2>
		                    <?php
		                    // First try to get short description
		                    $short_description = $product->get_short_description();
		                    if (!empty($short_description)) {
		                        echo '<div class="product-short-description">' . wpautop($short_description) . '</div>';
		                    }

		                    // Also include full description
		                    $description = $product->get_description();
		                    if (!empty($description)) {
		                        echo '<div class="product-full-description">' . wpautop($description) . '</div>';
		                    } elseif (empty($short_description)) {
		                        // Fallback to post content if both descriptions are empty
		                        the_content();
		                    }
		                    ?>
		                </div>
		            <?php endif; ?>

		            <div class="product-meta">
		                <?php if (in_array('sku', $wc_product_display_options) && $product->get_sku()) : ?>
		                    <div class="product-meta-item product-sku">
		                        <strong>SKU:</strong> <?php echo esc_html($product->get_sku()); ?>
		                    </div>
		                <?php endif; ?>

		                <?php if (in_array('categories', $wc_product_display_options)) : ?>
		                    <?php
		                    // Display categories
		                    $categories = get_the_terms($product->get_id(), 'product_cat');
		                    if ($categories && !is_wp_error($categories)) {
		                        $cat_names = array();
		                        foreach ($categories as $category) {
		                            $cat_names[] = $category->name;
		                        }
		                        echo '<div class="product-meta-item product-categories">';
		                        echo '<strong>Categories:</strong> ' . esc_html(implode(', ', $cat_names));
		                        echo '</div>';
		                    }
		                    ?>
		                <?php endif; ?>

		                <?php if (in_array('tags', $wc_product_display_options)) : ?>
		                    <?php
		                    // Display tags
		                    $tags = get_the_terms($product->get_id(), 'product_tag');
		                    if ($tags && !is_wp_error($tags)) {
		                        $tag_names = array();
		                        foreach ($tags as $tag) {
		                            $tag_names[] = $tag->name;
		                        }
		                        echo '<div class="product-meta-item product-tags">';
		                        echo '<strong>Tags:</strong> ' . esc_html(implode(', ', $tag_names));
		                        echo '</div>';
		                    }
		                    ?>
		                <?php endif; ?>
		            </div>
		        </article>
		        <?php
		    }
		endwhile;
	endif;
	?>
</main>
</body>
</html>
