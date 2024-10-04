<?php
/**
 * Plugin Name: Image Gallery - Block
 * Description: Create and display photo gallery/photo album
 * Version: 1.0.7
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: image-gallery
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

// Constant
define( 'BIGB_PLUGIN_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.7' );
define( 'BIGB_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'BIGB_DIR_PATH', plugin_dir_path( __FILE__ ) );

class BIGBImageGallery{
	function __construct(){
		add_action( 'init', [$this, 'onInit'] );
	}

	function onInit() {
		wp_register_style( 'bigb-image-gallery-style', BIGB_DIR_URL . 'dist/style.css', [], BIGB_PLUGIN_VERSION );
		wp_register_style( 'bigb-image-gallery-editor-style', BIGB_DIR_URL . 'dist/editor.css', [ 'bigb-image-gallery-style' ], BIGB_PLUGIN_VERSION );

		register_block_type( __DIR__, [
			'editor_style'		=> 'bigb-image-gallery-editor-style',
			'render_callback'	=> [$this, 'render']
		] ); // Register Block

		wp_set_script_translations( 'bigb-image-gallery-editor-script', 'image-gallery', BIGB_DIR_PATH . 'languages' );
	}

	function render( $attributes ){
		extract( $attributes );

		wp_enqueue_style( 'bigb-image-gallery-style' );
		wp_enqueue_script( 'bigb-image-gallery-script', BIGB_DIR_URL . 'dist/script.js', [ 'react', 'react-dom' ], false );
		wp_set_script_translations( 'bigb-image-gallery-script', 'image-gallery', BIGB_DIR_PATH . 'languages' );

		$className = $className ?? '';
		$blockClassName = "wp-block-bigb-image-gallery $className align$align";

		ob_start(); ?>
		<div class='<?php echo esc_attr( $blockClassName ); ?>' id='bigbImageGallery-<?php echo esc_attr( $cId ) ?>' data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'></div>

		<?php return ob_get_clean();
	} // Render
}
new BIGBImageGallery;