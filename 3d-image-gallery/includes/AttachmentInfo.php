<?php
/**
 * Resolve a Media Library URL to its attachment (IG-004).
 *
 * The block's image pickers hand back only a URL, so the editor asks this
 * route which attachment (if any) the URL belongs to and stores the id, alt
 * text and natural size next to the URL. External URLs simply resolve to
 * nothing and keep working as plain URLs.
 *
 * GET bigb/v1/attachment-info?url=<image url>
 *   -> { attachmentId, alt, width, height, sizes: { name: { url, width, height } } }
 *   -> { attachmentId: 0 } when the URL is not a Media Library image.
 *
 * Read-only; requires `upload_files` (the same capability needed to pick
 * images in the block editor). Only attachments of type image are described,
 * and only the caller's own `read_post` access is honoured.
 *
 * @package image-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'igbAttachmentInfo' ) ) {

class igbAttachmentInfo {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'registerRoute' ] );
	}

	public function registerRoute() {
		register_rest_route(
			'bigb/v1',
			'/attachment-info',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => static function () {
					return current_user_can( 'upload_files' );
				},
				'args'                => [
					'url' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => static function ( $value ) {
							return is_string( $value ) && '' !== trim( $value ) && strlen( $value ) <= 2048;
						},
					],
				],
			]
		);
	}

	public function handle( WP_REST_Request $request ) {
		$url = (string) $request->get_param( 'url' );
		$id  = $url ? attachment_url_to_postid( $url ) : 0;

		if ( ! $id || 'attachment' !== get_post_type( $id ) || ! wp_attachment_is_image( $id ) || ! current_user_can( 'read_post', $id ) ) {
			return rest_ensure_response( [ 'attachmentId' => 0 ] );
		}

		$meta  = wp_get_attachment_metadata( $id );
		$meta  = is_array( $meta ) ? $meta : [];
		$sizes = [];

		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( array_keys( $meta['sizes'] ) as $name ) {
				$src = wp_get_attachment_image_src( $id, $name );
				if ( $src ) {
					$sizes[ sanitize_key( $name ) ] = [
						'url'    => esc_url_raw( $src[0] ),
						'width'  => (int) $src[1],
						'height' => (int) $src[2],
					];
				}
			}
		}

		return rest_ensure_response(
			[
				'attachmentId' => (int) $id,
				'alt'          => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'width'        => isset( $meta['width'] ) ? (int) $meta['width'] : 0,
				'height'       => isset( $meta['height'] ) ? (int) $meta['height'] : 0,
				'sizes'        => $sizes,
			]
		);
	}
}

new igbAttachmentInfo();
}
