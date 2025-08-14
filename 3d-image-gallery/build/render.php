<?php
$clientId = isset($attributes['cId']) ? $attributes['cId'] : wp_unique_id();
$id = 'bigbImageGallery-' . $clientId;

// Ensure $attributes is an array
$attributes = is_array($attributes) ? $attributes : [];

$json_attributes = wp_json_encode($attributes);

// Fallback in case wp_json_encode fails
if (false === $json_attributes) {
    $json_attributes = '{}';
}
?>

<div <?php echo get_block_wrapper_attributes(); ?> id="<?php echo esc_attr($id); ?>"
    data-attributes='<?php echo esc_attr($json_attributes); ?>'>
</div>