<?php
$clientId = isset($attributes['cId']) ? $attributes['cId'] : wp_unique_id();
$id = 'bigbImageGallery-' . $clientId;

// Ensure $attributes is an array
$attributes = is_array($attributes) ? $attributes : [];

// Migrate old blocks using centralized utility
$attributes = ig_migrate_style_one_attributes($attributes);

$json_attributes = wp_json_encode($attributes);

// Fallback in case wp_json_encode fails
if (false === $json_attributes) {
    $json_attributes = '{}';
}

$styleSl = isset($attributes['styleSl']) ? $attributes['styleSl'] : 'styleDefault';

// Enqueue assets based on style
if ($styleSl === 'styleSeven') {
    wp_enqueue_script('bigb-image-gallery-view-swiper');
    if (file_exists(plugin_dir_path(__DIR__) . 'build/view-swiper.css')) {
        wp_enqueue_style('bigb-image-gallery-view-swiper-style', plugin_dir_url(__DIR__) . 'build/view-swiper.css', [], '1.0.0');
    }
} elseif ($styleSl === 'styleFive') {
    wp_enqueue_script('bigb-image-gallery-view-gsap');
    if (file_exists(plugin_dir_path(__DIR__) . 'build/view-gsap.css')) {
        wp_enqueue_style('bigb-image-gallery-view-gsap-style', plugin_dir_url(__DIR__) . 'build/view-gsap.css', [], '1.0.0');
    }
} else {
    wp_enqueue_script('bigb-image-gallery-view');
    // view.css is likely loaded by block.json, but if we want to be explicit or if we remove it from block.json:
    // wp_enqueue_style('bigb-image-gallery-view-style', plugin_dir_url(__DIR__) . 'build/view.css', [], '1.0.0');
}
?>

<div <?php echo get_block_wrapper_attributes(); ?> id="<?php echo esc_attr($id); ?>"
    data-attributes="<?php echo esc_attr($json_attributes); ?>"
    data-pipecheck="<?php echo esc_attr(ig_IsPremium()); ?>">
</div>