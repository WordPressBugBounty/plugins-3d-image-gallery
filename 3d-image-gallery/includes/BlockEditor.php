<?php
/**
 * Keeping the block editor on for the Image Gallery post type.
 *
 * A gallery post is not a document with a block in it. PostType.php registers
 * it with `template => [['bigb/image-gallery']]` and `template_lock => 'all'`:
 * the editing screen IS that one block, inserted for you and not removable.
 *
 * So when a site has the block editor switched off, this screen does not fall
 * back to a simpler version of itself -- it falls back to an empty TinyMCE box
 * with a title above it and no way to build anything. Which reads, reasonably,
 * as a plugin that is broken or missing its options.
 *
 * That is a real support report, not a hypothetical: the same thing was
 * reported against Video Gallery by a site running Flatsome, and the fix there
 * is this fix. It comes from the Classic Editor plugin, one of the "Disable
 * Gutenberg" plugins, or a theme option. None of them is misbehaving -- they
 * were asked to switch the block editor off and they did.
 *
 * This says: not for this one post type. Posts, pages and everything else keep
 * whatever editor the site has chosen; only `image-gallery` is forced back,
 * because there the alternative is not a different editor but no editor at all.
 *
 * @package image-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'igbBlockEditor' ) ) {

class igbBlockEditor {

	/**
	 * Last word, deliberately -- the point is to run after whatever turned it off.
	 */
	const PRIORITY = 9999;

	public function __construct() {
		add_filter( 'use_block_editor_for_post_type', [ $this, 'forPostType' ], self::PRIORITY, 2 );
		add_filter( 'use_block_editor_for_post', [ $this, 'forPost' ], self::PRIORITY, 2 );

		// The Gutenberg feature plugin's own older filter.
		add_filter( 'gutenberg_can_edit_post_type', [ $this, 'forPostType' ], self::PRIORITY, 2 );

		// Answering the Classic Editor plugin directly is politer than only
		// overriding its result: it stops that plugin offering an "Edit
		// (Classic)" link on a screen where the classic editor cannot work.
		add_filter( 'classic_editor_enabled_editors_for_post_type', [ $this, 'classicEditorChoice' ], self::PRIORITY, 2 );

		add_action( 'admin_notices', [ $this, 'strandedNotice' ] );
	}

	/**
	 * Is the override switched on for this site?
	 *
	 * @return bool
	 */
	private function enabled() {
		/**
		 * Allow a site to stop this plugin insisting on the block editor.
		 *
		 * Switching it off leaves the gallery screen showing whatever editor the
		 * site is configured for, which for this post type means an empty box.
		 * There is a reason it defaults to true.
		 *
		 * @param bool $force Whether to force the block editor on.
		 */
		return (bool) apply_filters( 'igb_force_block_editor', true );
	}

	/**
	 * Force the block editor on, for this post type only.
	 *
	 * @param bool   $use       What has been decided so far.
	 * @param string $post_type Post type being decided.
	 * @return bool
	 */
	public function forPostType( $use, $post_type ) {
		if ( igbPostType::POST_TYPE !== $post_type || ! $this->enabled() ) {
			return $use;
		}

		return true;
	}

	/**
	 * The same, for the per-post decision.
	 *
	 * A separate filter, and it matters: the Classic Editor plugin in its "let
	 * users switch" mode leaves the post type alone and answers this one
	 * instead, off a `classic-editor` query argument. Overriding only the
	 * post-type filter would be undone by a link the user clicked.
	 *
	 * @param bool    $use  What has been decided so far.
	 * @param WP_Post $post Post being edited.
	 * @return bool
	 */
	public function forPost( $use, $post ) {
		if ( empty( $post->post_type ) || igbPostType::POST_TYPE !== $post->post_type || ! $this->enabled() ) {
			return $use;
		}

		return true;
	}

	/**
	 * Tell the Classic Editor plugin this post type is block-only.
	 *
	 * @param array  $editors   { classic_editor: bool, block_editor: bool }.
	 * @param string $post_type Post type being asked about.
	 * @return array
	 */
	public function classicEditorChoice( $editors, $post_type ) {
		if ( igbPostType::POST_TYPE !== $post_type || ! $this->enabled() ) {
			return $editors;
		}

		return [
			'classic_editor' => false,
			'block_editor'   => true,
		];
	}

	/**
	 * If the classic editor loads here anyway, say why.
	 *
	 * The filters above cover every well-behaved way of turning the block editor
	 * off. A plugin that takes the whole screen over through `replace_editor`
	 * cannot be argued with from here -- but the person staring at an empty box
	 * can at least be told what happened, rather than left to conclude the
	 * plugin does not work.
	 */
	public function strandedNotice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'post' !== $screen->base || igbPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Core sets this once the screen has decided. On the block editor there
		// is nothing to warn about.
		if ( ! empty( $screen->is_block_editor ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong></p><p>%s</p></div>',
			esc_html__( 'The gallery builder cannot load.', 'image-gallery' ),
			esc_html__( 'Image Gallery needs the WordPress block editor, and something on this site has switched it off for this screen — usually the Classic Editor plugin, a "Disable Gutenberg" plugin, or a theme option. The empty box below is the classic editor; the gallery builder cannot load into it. Switch the block editor back on for this site, or ask your developer to, and this screen will work.', 'image-gallery' )
		);
	}
}

new igbBlockEditor();
}
