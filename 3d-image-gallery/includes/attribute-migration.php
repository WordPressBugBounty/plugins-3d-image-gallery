<?php
/**
 * Attribute Migration Utilities
 * Centralized functions for migrating block attributes
 */

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
