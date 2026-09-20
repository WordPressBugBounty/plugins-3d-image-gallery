<?php
/**
 * The Image Gallery post type, and the shortcode that places one.
 *
 * Every other block plugin in this family works this way -- Video Gallery,
 * Video Player, Text Typing -- so the shape here is deliberately theirs: one
 * gallery per post, a `[image_gallery id="12"]` shortcode, and a Shortcode
 * column in the list table you can click to copy.
 *
 * This does not replace putting the block straight into a page, and galleries
 * already built that way are untouched. It adds the thing the block alone
 * cannot do: reuse. A gallery built once and placed in a sidebar widget, a
 * page-builder row, a theme template or six different pages -- edited in one
 * place, changing everywhere.
 *
 * The post type carries `template` and `template_lock`, so the editing screen
 * IS the gallery block: inserted for you, not removable, nothing else on the
 * page. See BlockEditor.php for what that means when a site has switched the
 * block editor off, and why this file registers that guard alongside.
 *
 * @package image-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'igbPostType' ) ) {

class igbPostType {

	/**
	 * The post type, and the id half of the shortcode.
	 */
	const POST_TYPE = 'image-gallery';

	/**
	 * The block a gallery post is made of.
	 */
	const BLOCK = 'bigb/image-gallery';

	public function __construct() {
		add_action( 'init', [ $this, 'registerPostType' ] );
		add_shortcode( 'image_gallery', [ $this, 'shortcode' ] );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'manageColumns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'manageCustomColumns' ], 10, 2 );

		add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
	}

	/**
	 * Register the post type.
	 */
	public function registerPostType() {
		register_post_type(
			self::POST_TYPE,
			[
				'label'  => __( 'Image Gallery', 'image-gallery' ),
				'labels' => [
					'name'               => __( 'Image Gallery', 'image-gallery' ),
					'singular_name'      => __( 'Image Gallery', 'image-gallery' ),
					'add_new'            => __( 'Add New', 'image-gallery' ),
					'add_new_item'       => __( 'Add New Image Gallery', 'image-gallery' ),
					'edit_item'          => __( 'Edit Image Gallery', 'image-gallery' ),
					'new_item'           => __( 'New Image Gallery', 'image-gallery' ),
					'view_item'          => __( 'View Image Gallery', 'image-gallery' ),
					'view_items'         => __( 'View Image Gallery', 'image-gallery' ),
					'search_items'       => __( 'Search Image Gallery', 'image-gallery' ),
					'not_found'          => __( 'No Image Gallery found.', 'image-gallery' ),
					'not_found_in_trash' => __( 'No Image Gallery found in Trash.', 'image-gallery' ),
					'all_items'          => __( 'All Image Gallery', 'image-gallery' ),
					'archives'           => __( 'Image Gallery Archives', 'image-gallery' ),
					'item_published'     => __( 'Image Gallery Published', 'image-gallery' ),
					'item_updated'       => __( 'Image Gallery Updated', 'image-gallery' ),
				],
				'show_in_rest'       => true,
				'public'             => true,
				'menu_icon'          => 'dashicons-format-gallery',
				'menu_position'      => 25,
				// A gallery post is a container to be placed by shortcode, not a
				// page to be visited. Without this it would answer on its own URL
				// as a bare gallery with no theme around it, and land in search
				// results as a page nobody meant to publish.
				'publicly_queryable' => false,
				'has_archive'        => false,
				'template'           => [ [ self::BLOCK ] ],
				'template_lock'      => 'all',
			]
		);
	}

	/**
	 * `[image_gallery id="12"]`
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'image_gallery' );

		$post_id = absint( $atts['id'] );

		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return '';
		}

		if ( post_password_required( $post ) ) {
			return get_the_password_form( $post );
		}

		/*
		 * The shortcode obeys the post's status, which is the whole reason for
		 * the switch rather than a single `'publish' === $post->post_status`.
		 * A draft gallery placed on a live page must not show to visitors, but
		 * must show to the person editing it -- otherwise there is no way to
		 * preview one before publishing.
		 */
		switch ( $post->post_status ) {
			case 'publish':
				return $this->displayContent( $post );

			case 'private':
				return current_user_can( 'read_post', $post_id ) ? $this->displayContent( $post ) : '';

			case 'draft':
			case 'pending':
			case 'future':
				return current_user_can( 'edit_post', $post_id ) ? $this->displayContent( $post ) : '';

			default:
				return '';
		}
	}

	/**
	 * Render the gallery block a post holds.
	 *
	 * The block name is checked and `render_block()` does the work, so there is
	 * exactly one rendering path: whatever the block does in a page, it does
	 * here. Running the output through `wp_kses_post()` instead would be worse
	 * than useless -- render.php escapes everything it prints, and kses allows
	 * no `<script>` at all, so it would silently strip the JSON-LD that
	 * ig_render_seo_fallback() emits. A gallery placed by shortcode would then
	 * describe none of its images to search engines while the same gallery in a
	 * page described all of them.
	 *
	 * @param WP_Post $post Gallery post.
	 * @return string
	 */
	private function displayContent( $post ) {
		$blocks = parse_blocks( $post->post_content );

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && self::BLOCK === $block['blockName'] ) {
				return render_block( $block );
			}
		}

		return '';
	}

	/**
	 * Add the Shortcode column, before Date.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function manageColumns( $columns ) {
		// Unset and re-add so Shortcode sits before Date rather than after it.
		unset( $columns['date'] );

		$columns['shortcode'] = __( 'Shortcode', 'image-gallery' );
		$columns['date']      = __( 'Date', 'image-gallery' );

		return $columns;
	}

	/**
	 * Draw one Shortcode cell.
	 *
	 * @param string $column_name Column being drawn.
	 * @param int    $post_id     Post being drawn.
	 */
	public function manageCustomColumns( $column_name, $post_id ) {
		if ( 'shortcode' !== $column_name ) {
			return;
		}

		printf(
			'<div class="bPlAdminShortcode" id="bPlAdminShortcode-%1$s">
				<input value="%2$s" onclick="copyBPlAdminShortcode(\'%1$s\')" readonly>
				<span class="tooltip">%3$s</span>
			</div>',
			esc_attr( $post_id ),
			esc_attr( sprintf( '[image_gallery id=%d]', $post_id ) ),
			esc_html__( 'Copy To Clipboard', 'image-gallery' )
		);
	}

	/**
	 * The click-to-copy behaviour, on this post type's screens only.
	 */
	public function adminEnqueueScripts() {
		global $typenow;

		if ( self::POST_TYPE !== $typenow ) {
			return;
		}

		wp_enqueue_script( 'igb-admin-post', BIGB_DIR_URL . 'build/admin/post.js', [ 'wp-i18n' ], BIGB_PLUGIN_VERSION, true );
		wp_enqueue_style( 'igb-admin-post', BIGB_DIR_URL . 'build/admin/post.css', [], BIGB_PLUGIN_VERSION );
	}
}

new igbPostType();
}
