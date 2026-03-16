<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

 if (!function_exists('ig_fs')) {
        function ig_fs() {
            global $ig_fs;

            if (!isset($ig_fs)) {
                require_once BIGB_DIR_PATH . 'vendor/freemius-lite/start.php';

                $ig_fs = fs_lite_dynamic_init([
                    'id'                  => '19835',
					'slug'                => '3d-image-gallery',
                    '__FILE__'            => BIGB_DIR_PATH . 'index.php',
					'premium_slug'        => '3d-image-gallery-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_b2e7f3ea20771578177abd884c97d',
					'is_premium'          => false,
					'premium_suffix'      => 'Pro',
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'menu'                => array(
						'slug'           => '3d-image-gallery-dashboard',
						'first-path'     => 'tools.php?page=3d-image-gallery-dashboard',
						'support'        => false,
						'parent'         => array(
							'slug' => 'tools.php',
						),
					),
                ]); 
            }
            return $ig_fs;
        }

        ig_fs();
        do_action('ig_fs_loaded');
    }