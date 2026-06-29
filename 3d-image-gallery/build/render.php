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

// Enqueue frontend assets
wp_enqueue_script('bigb-image-gallery-view');
?>

<div <?php echo get_block_wrapper_attributes(); ?> id="<?php echo esc_attr($id); ?>"
    data-attributes="<?php echo esc_attr($json_attributes); ?>">
</div>