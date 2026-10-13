<?php
/**
 * Plugin Name: Image Gallery Block
 * Description: Create and display photo gallery/photo album
 * Version: 2.4.1
 * Requires PHP: 7.4
 * Requires at least: 6.5
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: image-gallery
 * @fs_premium_only /vendor/freemius, /includes/fs.php
 * @fs_free_only /vendor/freemius-lite, /includes/fs-lite.php
 */

// ABS PATH
if (! defined('ABSPATH')) {
	exit;
}

if ( function_exists( 'ig_fs' ) ) {
    ig_fs()->set_basename( true, __FILE__ );
} else {
	// Constant
    define('BIGB_PLUGIN_VERSION', (
        isset($_SERVER['HTTP_HOST'])
        && 'localhost' === strtok(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])), ':')
    ) ? time() : '2.4.1');
    define('BIGB_DIR_URL', plugin_dir_url(__FILE__));
    define('BIGB_DIR_PATH', plugin_dir_path(__FILE__));
	// Freemius Lite SDK bootstrap.
	require_once BIGB_DIR_PATH . 'includes/fs-lite.php';

	class BIGBImageGallery
	{
		function __construct()
		{
			add_action('init', [$this, 'onInit']);
			add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
			add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
			add_filter( 'block_type_metadata', [ $this, 'versionBlockAssets' ] );

			// Redirect to the Help & Demos page on first activation.
			register_activation_hook( __FILE__, [ $this, 'onActivation' ] );
			add_action( 'admin_init', [ $this, 'maybeRedirectAfterActivation' ] );
		}

		/**
		 * Set a flag so we know a redirect is needed on the next admin page load.
		 */
		function onActivation() {
			update_option( 'bigb_activation_redirect', true );
		}

		/**
		 * Redirect to the Help & Demos dashboard page once after activation.
		 */
		function maybeRedirectAfterActivation() {
			if ( ! get_option( 'bigb_activation_redirect', false ) ) {
				return;
			}

			delete_option( 'bigb_activation_redirect' );

			// Don't redirect on bulk activate or WP-CLI.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$activate_multi = isset( $_GET['activate-multi'] ) ? sanitize_text_field( wp_unslash( $_GET['activate-multi'] ) ) : '';
			if ( wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) || ! empty( $activate_multi ) ) {
				return;
			}

			// The Freemius opt-in screen hides this plugin's menu until it is answered, so the page below
			// would say "not allowed". Let Freemius show its opt-in first; it opens the first page after.
			if ( function_exists( 'ig_fs' ) && ig_fs()->is_activation_mode() ) {
				return;
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=image-gallery&page=3d-image-gallery-dashboard' ) );
			exit;
		}

		// block.json's own "version" is what register_block_style_handle()
		// falls back to for cache-busting once SCRIPT_DEBUG is off (the
		// normal state on a real site), so keep it pinned to the plugin's
		// actual version rather than whatever was last hand-edited there.
		function versionBlockAssets( $metadata ) {
			if ( isset( $metadata['name'] ) && 0 === strpos( $metadata['name'], 'bigb/' ) ) {
				$metadata['version'] = BIGB_PLUGIN_VERSION;
			}
			return $metadata;
		}


		function defaultTitle( $title, $post ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only prefill of a new, unsaved page; nothing is stored until the user saves.
			if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
				return sanitize_text_field( wp_unslash( $_GET['title'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			return $title;
		}

		function defaultContent( $content, $post ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only prefill of a new, unsaved page; nothing is stored until the user saves.
			if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
				return wp_kses_post( wp_unslash( $_GET['content'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			return $content;
		}

		function onInit()
		{
			register_block_type(__DIR__ . '/build');

			// Register frontend scripts for conditional loading
			$build_path = plugin_dir_path(__FILE__) . 'build/';
			$build_url = plugin_dir_url(__FILE__) . 'build/';

			// Core View
			if (file_exists($build_path . 'view.asset.php')) {
				$asset_file = include $build_path . 'view.asset.php';
				wp_register_script(
					'bigb-image-gallery-view',
					$build_url . 'view.js',
					$asset_file['dependencies'],
					$asset_file['version'],
					true
				);
			}
		}
	}

	new BIGBImageGallery();

}

require_once BIGB_DIR_PATH . '/includes/PostType.php';
require_once BIGB_DIR_PATH . '/includes/BlockEditor.php';
require_once BIGB_DIR_PATH . '/includes/admin/SubMenu.php';
require_once BIGB_DIR_PATH . '/includes/attribute-migration.php';

// Ready-made sections for the inserter.
require_once BIGB_DIR_PATH . '/includes/patterns.php';