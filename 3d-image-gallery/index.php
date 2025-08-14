<?php

/**
 * Plugin Name: Image Gallery - Block
 * Description: Create and Display Photo Galleries.
 * Version: 2.0.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: image-gallery
 * @fs_premium_only/freemius
 * @fs_free_only/freemius-lite
 */
// ABS PATH
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'ig_fs' ) ) {
    register_activation_hook( __FILE__, function () {
        if ( is_plugin_active( '3d-image-gallery/index.php' ) ) {
            deactivate_plugins( '3d-image-gallery/index.php' );
        }
        if ( is_plugin_active( '3d-image-gallery-pro/index.php' ) ) {
            deactivate_plugins( '3d-image-gallery-pro/index.php' );
        }
    } );
} else {
    /**
     * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
     * `function_exists` CALL ABOVE TO PROPERLY WORK.
     */
    // Constant
    define( 'BIGB_PLUGIN_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.7' ) );
    define( 'BIGB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'BIGB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'BIGB_HAS_PRO', file_exists( dirname( __FILE__ ) . '/freemius/start.php' ) );
    // ... Freemius integration snippet ...
    if ( !function_exists( 'ig_fs' ) ) {
        // Create a helper function for easy SDK access.
        function ig_fs() {
            global $ig_fs;
            if ( !isset( $ig_fs ) ) {
                if ( BIGB_HAS_PRO ) {
                    require_once dirname( __FILE__ ) . '/freemius/start.php';
                } else {
                    require_once dirname( __FILE__ ) . '/freemius-lite/start.php';
                }
                // Include Freemius SDK.
                $ig_Config = array(
                    'id'                  => '19835',
                    'slug'                => '3d-image-gallery',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_b2e7f3ea20771578177abd884c97d',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'trial'               => array(
                        'days'               => 7,
                        'is_require_payment' => false,
                    ),
                    'menu'                => array(
                        'slug'       => '3d-image-gallery-dashboard',
                        'first-path' => 'tools.php?page=3d-image-gallery-dashboard#/welcome',
                        'support'    => false,
                        'parent'     => array(
                            'slug' => 'tools.php',
                        ),
                    ),
                );
                $ig_fs = ( BIGB_HAS_PRO ? fs_dynamic_init( $ig_Config ) : fs_lite_dynamic_init( $ig_Config ) );
            }
            return $ig_fs;
        }

        // Init Freemius.
        ig_fs();
        // Signal that SDK was initiated.
        do_action( 'ig_fs_loaded' );
    }
    function ig_IsPremium() {
        return ( BIGB_HAS_PRO ? ig_fs()->can_use_premium_code() : false );
    }

    // ... Your plugin's main file logic ...
    class BIGBImageGallery {
        function __construct() {
            add_action( 'init', [$this, 'onInit'] );
            add_action( 'wp_ajax_ig_PremiumChecker', [$this, 'ig_PremiumChecker'] );
            add_action( 'wp_ajax_nopriv_ig_PremiumChecker', [$this, 'ig_PremiumChecker'] );
            add_action( 'admin_init', [$this, 'registerSettings'] );
            add_action( 'rest_api_init', [$this, 'registerSettings'] );
        }

        function ig_PremiumChecker() {
            $nonce = sanitize_text_field( $_POST['_wpnonce'] ?? null );
            if ( !wp_verify_nonce( $nonce, 'wp_ajax' ) ) {
                wp_send_json_error( 'Invalid Request' );
            }
            wp_send_json_success( [
                'isPipe' => ig_IsPremium(),
            ] );
        }

        function registerSettings() {
            register_setting( 'ig_Utils', 'ig_Utils', [
                'show_in_rest'      => [
                    'name'   => 'ig_Utils',
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
                'type'              => 'string',
                'default'           => wp_json_encode( [
                    'nonce' => wp_create_nonce( 'wp_ajax' ),
                ] ),
                'sanitize_callback' => 'sanitize_text_field',
            ] );
        }

        function onInit() {
            register_block_type( __DIR__ . '/build' );
        }

    }

    new BIGBImageGallery();
}
require_once BIGB_DIR_PATH . '/inc/adminMenu.php';
require_once BIGB_DIR_PATH . '/inc/upgradePage.php';