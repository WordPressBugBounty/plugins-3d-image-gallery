<?php
/**
 * Responsive image sources for gallery images (IG-006).
 *
 * The gallery is drawn in the browser from the block's attributes, so the
 * browser never sees WordPress's image APIs. This adds a `responsive` map to
 * the attributes handed to the front end:
 *
 *   responsive[ <image url> ] = [ 'srcset' => '<url> 300w, <url> 768w, ...' ]
 *
 * The scripts then add `srcset`/`sizes` to the <img>. It is computed here, on
 * render, so galleries saved long ago get it without being re-saved, and
 * nothing extra is stored in post content.
 *
 * Safety rules:
 *  - Only Media Library images of THIS site qualify (resolved with the stored
 *    attachment id when the editor recorded one, otherwise by URL).
 *  - Only when the stored URL is the attachment's full-size file. A cropped
 *    size (e.g. "thumbnail") has a different aspect ratio than the sizes
 *    WordPress lists, so adding a srcset to it could swap the crop.
 *  - External URLs and anything unrecognised are left exactly as they were.
 *  - Results (including "not eligible") are cached per URL; the cache is
 *    invalidated whenever an attachment is edited, deleted or re-generated.
 *  - At most IGB_RESPONSIVE_MAX_IMAGES images per render are examined.
 *
 * @package image-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'IGB_RESPONSIVE_MAX_IMAGES' ) ) {
	define( 'IGB_RESPONSIVE_MAX_IMAGES', 200 );
}

if ( ! function_exists( 'ig_responsive_cache_version' ) ) {

	function ig_responsive_cache_version() {
		$ver = get_option( 'igb_responsive_ver', '' );
		return $ver ? $ver : '1';
	}

	function ig_responsive_bump_cache() {
		update_option( 'igb_responsive_ver', (string) time(), false );
	}

	add_action( 'edit_attachment', 'ig_responsive_bump_cache' );
	add_action( 'delete_attachment', 'ig_responsive_bump_cache' );
	add_filter(
		'wp_update_attachment_metadata',
		static function ( $data ) {
			ig_responsive_bump_cache();
			return $data;
		}
	);
}

if ( ! function_exists( 'ig_responsive_source_for' ) ) {

	/**
	 * srcset for one image URL, or '' when the image is not eligible.
	 *
	 * @param string $url           Image URL as stored in the gallery.
	 * @param int    $attachment_id Attachment id recorded by the editor, 0 if unknown.
	 * @return string
	 */
	function ig_responsive_source_for( $url, $attachment_id = 0 ) {
		if ( ! is_string( $url ) || ! preg_match( '#^https?://#i', $url ) || strlen( $url ) > 2048 ) {
			return '';
		}

		$key    = 'igb_rs_' . md5( $url . '|' . (int) $attachment_id . '|' . ig_responsive_cache_version() );
		$cached = wp_cache_get( $key, 'image-gallery' );
		if ( false === $cached ) {
			$cached = get_transient( $key );
		}
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$srcset = '';
		$id     = absint( $attachment_id );

		if ( ! $id ) {
			$id = (int) attachment_url_to_postid( $url );
		}

		if ( $id && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) ) {
			$full = wp_get_attachment_url( $id );
			// Compare without query string / fragment; only the full-size file qualifies.
			$strip = static function ( $u ) {
				return is_string( $u ) ? strtok( $u, '?#' ) : '';
			};
			if ( $full && $strip( $full ) === $strip( $url ) ) {
				$set    = wp_get_attachment_image_srcset( $id, 'full' );
				$srcset = is_string( $set ) ? $set : '';
			}
		}

		wp_cache_set( $key, $srcset, 'image-gallery', 12 * HOUR_IN_SECONDS );
		set_transient( $key, $srcset, 12 * HOUR_IN_SECONDS );

		return $srcset;
	}
}

if ( ! function_exists( 'ig_add_responsive_sources' ) ) {

	/**
	 * Add the `responsive` map to block attributes (render-time only).
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	function ig_add_responsive_sources( $attributes ) {
		if ( ! is_array( $attributes ) ) {
			return $attributes;
		}

		$candidates = array(); // url => attachment id (0 if unknown).
		$style      = isset( $attributes['styleSl'] ) ? $attributes['styleSl'] : 'styleDefault';

		if ( 'styleDefault' === $style ) {
			$gallery = isset( $attributes['gallery'] ) && is_array( $attributes['gallery'] ) ? $attributes['gallery'] : array();
			foreach ( $gallery as $group ) {
				if ( ! is_array( $group ) || empty( $group['images'] ) || ! is_array( $group['images'] ) ) {
					continue;
				}
				$metas = isset( $group['imageMeta'] ) && is_array( $group['imageMeta'] ) ? $group['imageMeta'] : array();
				foreach ( $group['images'] as $i => $url ) {
					if ( is_string( $url ) && '' !== $url && ! isset( $candidates[ $url ] ) ) {
						$meta               = isset( $metas[ $i ] ) && is_array( $metas[ $i ] ) ? $metas[ $i ] : array();
						$candidates[ $url ] = isset( $meta['attachmentId'] ) ? absint( $meta['attachmentId'] ) : 0;
					}
				}
			}
		} else {
			$items = isset( $attributes['imagesData']['images'] ) && is_array( $attributes['imagesData']['images'] )
				? $attributes['imagesData']['images']
				: array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && ! empty( $item['url'] ) && is_string( $item['url'] ) && ! isset( $candidates[ $item['url'] ] ) ) {
					$candidates[ $item['url'] ] = isset( $item['attachmentId'] ) ? absint( $item['attachmentId'] ) : 0;
				}
			}
		}

		if ( empty( $candidates ) ) {
			return $attributes;
		}

		$map = array();
		foreach ( array_slice( $candidates, 0, IGB_RESPONSIVE_MAX_IMAGES, true ) as $url => $id ) {
			$srcset = ig_responsive_source_for( $url, $id );
			if ( '' !== $srcset ) {
				$map[ $url ] = array( 'srcset' => $srcset );
			}
		}

		if ( ! empty( $map ) ) {
			$attributes['responsive'] = $map;
		}

		return $attributes;
	}
}
