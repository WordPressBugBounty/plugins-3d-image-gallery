<?php
/**
 * Attribute Migration Utilities
 * Centralized functions for migrating block attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if (!function_exists('ig_sanitize_link_attributes')) {
    /**
     * Recursively drops any non-http(s) "link" value from block attributes
     * before they are JSON-encoded into data-attributes.
     *
     * Gallery/album items carry a free-form "link" field (set via the block's
     * own link control, but also reachable unfiltered through the block's
     * Code Editor), which the front end and editor both assign straight to
     * window.location.href. wp_kses leaves a javascript: URI alone because
     * it isn't markup, so without this it reaches the browser intact. This
     * walks the whole attributes tree rather than special-casing one known
     * path (e.g. gallery[].link), so every layout's "link" field is covered,
     * including nested ones like imagesData.images[].link.
     *
     * @param mixed $value Attributes array, or a nested value while recursing.
     * @return mixed Same shape, with unsafe "link" values removed.
     */
    function ig_sanitize_link_attributes($value) {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if ('link' === $key && is_string($item) && '' !== $item) {
                $safe = esc_url_raw($item, ['http', 'https']);
                $value[$key] = $safe !== '' ? $safe : '';
            } elseif (is_array($item)) {
                $value[$key] = ig_sanitize_link_attributes($item);
            }
        }

        return $value;
    }
}

if (!function_exists('ig_get_default_card_attributes')) {
    /**
     * Get default card attributes for styleOne
     * 
     * @return array Default card attributes
     */
    function ig_get_default_card_attributes() {
        return [
            'width' => [
                'desktop' => '18rem',
                'tablet' => '18rem',
                'mobile' => '18rem',
            ],
            'height' => [
                'desktop' => '250px',
                'tablet' => '250px',
                'mobile' => '250px',
            ],
            'gap' => [
                'desktop' => '2rem',
                'tablet' => '2rem',
                'mobile' => '2rem',
            ],
        ];
    }
}

if (!function_exists('ig_migrate_style_one_attributes')) {
    /**
     * Migrate attributes for styleOne blocks
     * Adds missing card attributes with defaults
     * 
     * @param array $attributes Block attributes
     * @return array Migrated attributes
     */
    function ig_migrate_style_one_attributes($attributes) {
        // Only migrate styleOne blocks
        if (!isset($attributes['styleSl']) || $attributes['styleSl'] !== 'styleOne') {
            return $attributes;
        }

        // Ensure styles array exists
        if (!isset($attributes['styles'])) {
            $attributes['styles'] = [];
        }

        // Ensure card object exists
        if (!isset($attributes['styles']['card'])) {
            $attributes['styles']['card'] = [];
        }

        // Get default card attributes
        $defaults = ig_get_default_card_attributes();

        // Migrate each card attribute
        foreach ($defaults as $key => $value) {
            if (!isset($attributes['styles']['card'][$key])) {
                $attributes['styles']['card'][$key] = $value;
            }
        }

        return $attributes;
    }
}

if (!function_exists('ig_get_seo_images')) {
    /**
     * Extract a flat, crawlable list of images from block attributes.
     *
     * The gallery is rendered client-side (React), so the server HTML contains
     * no <img> tags by default. This collects every image across the supported
     * styles so render.php can emit a crawlable <noscript> fallback and
     * schema.org structured data for SEO / Google Images.
     *
     * @param array $attributes Block attributes.
     * @return array List of ['url' => string, 'title' => string, 'caption' => string].
     */
    function ig_get_seo_images($attributes) {
        $images = array();

        if (!is_array($attributes)) {
            return $images;
        }

        $style = isset($attributes['styleSl']) ? $attributes['styleSl'] : 'styleDefault';

        if ('styleOne' === $style) {
            // Polaroid style: attributes.imagesData.images[] = { url, title, description }.
            $items = isset($attributes['imagesData']['images']) && is_array($attributes['imagesData']['images'])
                ? $attributes['imagesData']['images']
                : array();

            foreach ($items as $item) {
                if (empty($item['url']) || !is_string($item['url'])) {
                    continue;
                }
                $title   = isset($item['title']) ? (string) $item['title'] : '';
                $caption = isset($item['description']) && '' !== $item['description']
                    ? (string) $item['description']
                    : $title;
                $images[] = array(
                    'url'     => $item['url'],
                    'title'   => $title,
                    'caption' => $caption,
                );
            }

            return $images;
        }

        // Default style: attributes.gallery[] = { title, subtitle, images: [ url, ... ] }.
        $gallery = isset($attributes['gallery']) && is_array($attributes['gallery'])
            ? $attributes['gallery']
            : array();

        foreach ($gallery as $card) {
            $title    = isset($card['title']) ? (string) $card['title'] : '';
            $subtitle = isset($card['subtitle']) ? (string) $card['subtitle'] : '';
            $caption  = trim($title . ('' !== $subtitle ? ' - ' . $subtitle : ''));
            $urls     = isset($card['images']) && is_array($card['images']) ? $card['images'] : array();

            foreach ($urls as $url) {
                if (!is_string($url) || '' === $url) {
                    continue;
                }
                $images[] = array(
                    'url'     => $url,
                    'title'   => '' !== $title ? $title : $caption,
                    'caption' => $caption,
                );
            }
        }

        return $images;
    }
}

if (!function_exists('ig_render_seo_fallback')) {
    /**
     * Echo a crawlable <noscript> image fallback + schema.org ImageGallery
     * JSON-LD for the given block attributes. Outputs nothing when there are
     * no images (e.g. a freshly inserted, empty block).
     *
     * @param array $attributes Block attributes.
     * @return void
     */
    function ig_render_seo_fallback($attributes) {
        $images = ig_get_seo_images($attributes);

        if (empty($images)) {
            return;
        }

        // Visible-to-crawlers fallback for non-JS agents (social scrapers, Bing, etc.).
        echo '<noscript>';
        foreach ($images as $image) {
            $alt = '' !== $image['title'] ? $image['title'] : $image['caption'];
            printf(
                '<figure class="bigb-seo-image"><img src="%s" alt="%s" loading="lazy" />%s</figure>',
                esc_url($image['url']),
                esc_attr($alt),
                '' !== $image['caption']
                    ? '<figcaption>' . esc_html($image['caption']) . '</figcaption>'
                    : ''
            );
        }
        echo '</noscript>';

        // Structured data so search engines understand the gallery images.
        $ld = array(
            '@context' => 'https://schema.org',
            '@type'    => 'ImageGallery',
            'image'    => array(),
        );
        foreach ($images as $image) {
            $obj = array(
                '@type'      => 'ImageObject',
                'contentUrl' => $image['url'],
            );
            if ('' !== $image['title']) {
                $obj['name'] = $image['title'];
            }
            if ('' !== $image['caption']) {
                $obj['caption'] = $image['caption'];
            }
            $ld['image'][] = $obj;
        }

        echo '<script type="application/ld+json">'
            . wp_json_encode($ld)
            . '</script>';
    }
}

if (!function_exists('ig_needs_migration')) {
    /**
     * Check if attributes need migration
     * 
     * @param array $attributes Block attributes
     * @return bool True if migration needed
     */
    function ig_needs_migration($attributes) {
        if (!isset($attributes['styleSl']) || $attributes['styleSl'] !== 'styleOne') {
            return false;
        }

        if (!isset($attributes['styles']['card'])) {
            return true;
        }

        $defaults = ig_get_default_card_attributes();
        foreach (array_keys($defaults) as $key) {
            if (!isset($attributes['styles']['card'][$key])) {
                return true;
            }
        }

        return false;
    }
}
