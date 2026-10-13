<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure $attributes is defined and is an array
$attributes = ( isset( $attributes ) && is_array( $attributes ) ) ? $attributes : array();

$clientId = isset( $attributes['cId'] ) ? $attributes['cId'] : wp_unique_id();
$id       = 'bigbImageGallery-' . $clientId;

// Migrate old blocks using centralized utility
$attributes = ig_migrate_style_one_attributes( $attributes );

$json_attributes = wp_json_encode($attributes);

// Fallback in case wp_json_encode fails
if (false === $json_attributes) {
    $json_attributes = '{}';
}

// Enqueue frontend assets
wp_enqueue_script('bigb-image-gallery-view');
?>

<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped by get_block_wrapper_attributes(). ?> id="<?php echo esc_attr($id); ?>"
    data-attributes="<?php echo esc_attr($json_attributes); ?>">
</div>
<?php
// Crawlable image fallback + structured data for SEO (the gallery itself is
// rendered client-side, so the wrapper above is empty in the server HTML).
ig_render_seo_fallback($attributes);
?>