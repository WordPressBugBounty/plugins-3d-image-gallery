<?php
/**
 * Plugin Name: Image Gallery - Block
 * Description: Create and Display Photo Galleries.
 * Version: 2.3.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
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
	define('BIGB_PLUGIN_VERSION', isset($_SERVER['HTTP_HOST']) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '2.3.0');
	define('BIGB_DIR_URL', plugin_dir_url(__FILE__));
	define('BIGB_DIR_PATH', plugin_dir_path(__FILE__));
	// Freemius Lite SDK bootstrap.
	require_once BIGB_DIR_PATH . 'includes/fs-lite.php';

	function ig_IsPremium()
	{
		return false;
	}


	class BIGBImageGallery
	{
		function __construct()
		{
			add_action('init', [$this, 'onInit']);
			add_action('enqueue_block_editor_assets', [$this, 'igbEnqueueBlockEditorAssets']);
			add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
			add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
		}


		function defaultTitle( $title, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
				return sanitize_text_field( wp_unslash( $_GET['title'] ) );
			}
			return $title;
		}

		function defaultContent( $content, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
				return wp_kses_post( wp_unslash( $_GET['content'] ) );
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

		function igbEnqueueBlockEditorAssets()
		{
			wp_add_inline_script('bigb-image-gallery-editor-script', 'const igbpipecheck = false;', 'before');
		}
	}

	new BIGBImageGallery();

}

require_once BIGB_DIR_PATH . '/includes/admin/SubMenu.php';
require_once BIGB_DIR_PATH . '/includes/attribute-migration.php';