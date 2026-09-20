<?php
/**
 * Bundled block patterns.
 *
 * The block arrives from the inserter as three albums with no photographs in
 * them — three empty cards — which is a long way from the album grid, photo
 * wall or portfolio people came here to build. These patterns are those
 * sections, ready made: insert one, swap the photographs, done.
 *
 * The placeholder photographs are drawn inline rather than fetched, so a
 * freshly inserted pattern renders instantly, cannot 404, and puts no outbound
 * request on the page before the user has chosen anything.
 *
 * @package image-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An inline placeholder photograph, 1200x800.
 *
 * A simple horizon: sky, sun and two overlapping hills. Enough to read as a
 * photograph at card size without pretending to be one.
 *
 * @param string $sky  Sky colour.
 * @param string $sun  Sun colour.
 * @param string $near Near hill colour.
 * @param string $far  Far hill colour.
 * @return string Data URI.
 */
function bigb_pattern_photo( $sky, $sun, $near, $far ) {
	return 'data:image/svg+xml;charset=utf-8,'
		. '%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'1200\' height=\'800\'%3E'
		. '%3Crect width=\'1200\' height=\'800\' fill=\'' . rawurlencode( $sky ) . '\'/%3E'
		. '%3Ccircle cx=\'940\' cy=\'190\' r=\'92\' fill=\'' . rawurlencode( $sun ) . '\'/%3E'
		. '%3Cpath d=\'M0 800 L430 300 L820 800 Z\' fill=\'' . rawurlencode( $far ) . '\'/%3E'
		. '%3Cpath d=\'M480 800 L900 380 L1200 800 Z\' fill=\'' . rawurlencode( $near ) . '\'/%3E'
		. '%3C/svg%3E';
}

/**
 * One album card.
 *
 * @param int    $id       Album id.
 * @param string $title    Album title.
 * @param string $subtitle Album subtitle.
 * @param array  $photos   List of photo data URIs.
 * @return array
 */
function bigb_pattern_album( $id, $title, $subtitle, $photos ) {
	return array(
		'id'        => $id,
		'title'     => $title,
		'subtitle'  => $subtitle,
		// The card and the popup both read this as a list of plain image URLs.
		'images'    => $photos,
		'colors'    => array(
			'color'  => '#fff',
			'bgType' => 'solid',
			'bg'     => '#505a64d9',
		),
		'btnColors' => array(
			'color'  => '#fff',
			'bgType' => 'solid',
			'bg'     => '#000a1480',
		),
	);
}

/**
 * One Polaroid frame.
 *
 * The Polaroid style does not read the album list at all — it reads
 * imagesData.images, a flat list of photographs, each its own frame on the
 * wall. Passing it albums leaves the wall empty.
 *
 * @param string $id    Photo id.
 * @param string $url   Photo URL.
 * @param string $title Caption written under the frame.
 * @param string $note  Second line under the caption.
 * @param string $date  Date shown under the caption.
 * @return array
 */
function bigb_pattern_polaroid( $id, $url, $title, $note, $date ) {
	return array(
		'id'          => $id,
		'url'         => $url,
		'title'       => $title,
		'description' => $note,
		'date'        => $date,
		'category'    => '',
		// Not read by the Polaroid wall itself — the frame is sized by
		// styles.card — but the item settings panel expects the keys.
		'width'       => array( 'desktop' => 800, 'tablet' => 600, 'mobile' => 400 ),
		'height'      => array( 'desktop' => 800, 'tablet' => 600, 'mobile' => 400 ),
	);
}

/**
 * Serialise the gallery block into block markup.
 *
 * The block renders from render.php and saves nothing between its delimiters.
 *
 * @param array $attrs Block attributes.
 * @return string
 */
function bigb_pattern_block( $attrs ) {
	return '<!-- wp:bigb/image-gallery ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * A centred core heading.
 *
 * @param string $text  Heading text.
 * @param int    $level Heading level.
 * @return string
 */
function bigb_pattern_heading( $text, $level = 2 ) {
	$level = (int) $level;

	return '<!-- wp:heading {"textAlign":"center","level":' . $level . '} -->'
		. '<h' . $level . ' class="wp-block-heading has-text-align-center">' . esc_html( $text ) . '</h' . $level . '>'
		. '<!-- /wp:heading -->';
}

/**
 * A centred core paragraph.
 *
 * @param string $text Paragraph text.
 * @return string
 */
function bigb_pattern_paragraph( $text ) {
	return '<!-- wp:paragraph {"align":"center"} -->'
		. '<p class="has-text-align-center">' . esc_html( $text ) . '</p>'
		. '<!-- /wp:paragraph -->';
}

/**
 * A vertical spacer.
 *
 * @param int $px Height in pixels.
 * @return string
 */
function bigb_pattern_spacer( $px = 32 ) {
	$px = (int) $px;

	return '<!-- wp:spacer {"height":"' . $px . 'px"} -->'
		. '<div style="height:' . $px . 'px" aria-hidden="true" class="wp-block-spacer"></div>'
		. '<!-- /wp:spacer -->';
}

/**
 * Wrap blocks in a group.
 *
 * @param string $inner Inner block markup.
 * @param string $align full, wide or empty.
 * @return string
 */
function bigb_pattern_group( $inner, $align = '' ) {
	$attrs   = $align ? '{"align":"' . $align . '","layout":{"type":"constrained"}}' : '{"layout":{"type":"constrained"}}';
	$classes = $align ? 'wp-block-group align' . $align : 'wp-block-group';

	return '<!-- wp:group ' . $attrs . ' -->'
		. '<div class="' . esc_attr( $classes ) . '">' . $inner . '</div>'
		. '<!-- /wp:group -->';
}

/**
 * Register the pattern category and the patterns themselves.
 *
 * @return void
 */
function bigb_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	$category = 'image-gallery';

	register_block_pattern_category(
		$category,
		array( 'label' => __( 'Image Galleries', 'image-gallery' ) )
	);

	// A small stock of placeholder photographs, warm to cool.
	$dawn   = bigb_pattern_photo( '#f2c894', '#f0876a', '#7d5a63', '#a97d78' );
	$noon   = bigb_pattern_photo( '#8ec9e8', '#fff2cc', '#3f7a5a', '#6ba377' );
	$dusk   = bigb_pattern_photo( '#5b6b9e', '#f6b6a0', '#2b3355', '#3f4a75' );
	$night  = bigb_pattern_photo( '#1a2138', '#c7d4f0', '#0d1220', '#161d33' );
	$forest = bigb_pattern_photo( '#cfe3d2', '#f7f2d8', '#2f5741', '#4b7a5c' );
	$sand   = bigb_pattern_photo( '#f0e0c4', '#e8b04f', '#9b7a4f', '#c19a66' );

	$patterns = array();

	/* Album grid ---------------------------------------------------------- */
	$patterns['album-grid'] = array(
		'title'       => __( 'Photo album grid', 'image-gallery' ),
		'description' => __( 'Three albums in a row, each opening into its own set of photographs. The classic layout for a photography or travel page.', 'image-gallery' ),
		'keywords'    => array( 'album', 'grid', 'photography', 'travel' ),
		'content'     => bigb_pattern_group(
			bigb_pattern_heading( __( 'Recent work', 'image-gallery' ) )
			. bigb_pattern_paragraph( __( 'Three collections from the past year. Open any album to see the full set.', 'image-gallery' ) )
			. bigb_pattern_spacer( 24 )
			. bigb_pattern_block(
				array(
					'styleSl'    => 'styleDefault',
					'gallery'    => array(
						bigb_pattern_album( 1, __( 'Coastline', 'image-gallery' ), __( '18 photographs · Spring', 'image-gallery' ), array( $dawn, $noon, $dusk ) ),
						bigb_pattern_album( 2, __( 'Highlands', 'image-gallery' ), __( '24 photographs · Summer', 'image-gallery' ), array( $forest, $noon, $sand ) ),
						bigb_pattern_album( 3, __( 'After dark', 'image-gallery' ), __( '11 photographs · Autumn', 'image-gallery' ), array( $night, $dusk, $dawn ) ),
					),
					'columns'    => array( 'desktop' => 3, 'tablet' => 2, 'mobile' => 1 ),
					'itemHeight' => '420px',
					'columnGap'  => '24px',
					'rowGap'     => '32px',
					'background' => array( 'type' => 'solid', 'color' => 'transparent' ),
					'align'      => 'wide',
				)
			),
			'wide'
		),
	);

	/* Polaroid wall -------------------------------------------------------- */
	$patterns['polaroid-wall'] = array(
		'title'       => __( 'Polaroid photo wall', 'image-gallery' ),
		'description' => __( 'Six tilted Polaroid frames on a wall, each with a caption and a date, for a personal, scrapbook or team page.', 'image-gallery' ),
		'keywords'    => array( 'polaroid', 'wall', 'scrapbook', 'team', 'personal' ),
		'content'     => bigb_pattern_group(
			bigb_pattern_heading( __( 'A year on the wall', 'image-gallery' ) )
			. bigb_pattern_paragraph( __( 'Six frames from the last twelve months. Click any one to open it full size.', 'image-gallery' ) )
			. bigb_pattern_spacer( 24 )
			. bigb_pattern_block(
				array(
					'styleSl'    => 'styleOne',
					// The Polaroid wall renders imagesData.images, not the
					// album list the default style uses.
					'imagesData' => array(
						'id'       => 'polaroid',
						'name'     => __( 'Polaroid Style', 'image-gallery' ),
						'showDate' => true,
						'images'   => array(
							bigb_pattern_polaroid( 'p1', $night, __( 'Cold light', 'image-gallery' ), __( 'The river before sunrise', 'image-gallery' ), '2026-01-14' ),
							bigb_pattern_polaroid( 'p2', $dawn, __( 'First warm week', 'image-gallery' ), __( 'Walked out at six for this one', 'image-gallery' ), '2026-04-02' ),
							bigb_pattern_polaroid( 'p3', $forest, __( 'Under the pines', 'image-gallery' ), __( 'Two hours in, no phone signal', 'image-gallery' ), '2026-05-23' ),
							bigb_pattern_polaroid( 'p4', $noon, __( 'Too bright to shoot', 'image-gallery' ), __( 'Shot it anyway', 'image-gallery' ), '2026-07-08' ),
							bigb_pattern_polaroid( 'p5', $sand, __( 'Everything gold', 'image-gallery' ), __( 'The last warm afternoon', 'image-gallery' ), '2026-10-19' ),
							bigb_pattern_polaroid( 'p6', $dusk, __( 'Blue hour', 'image-gallery' ), __( 'Twenty minutes, then gone', 'image-gallery' ), '2026-11-30' ),
						),
					),
					/*
					 * The Polaroid wall takes its layout from styles, not from
					 * the top-level columns / gap attributes: with styles.columns
					 * unset the grid gets repeat(undefined, ...) and collapses
					 * into a single column.
					 */
					'styles'     => array(
						'columns'     => array( 'desktop' => 3, 'tablet' => 2, 'mobile' => 1 ),
						'card'        => array(
							'width'  => array( 'desktop' => '17rem', 'tablet' => '16rem', 'mobile' => '100%' ),
							'height' => array( 'desktop' => '230px', 'tablet' => '210px', 'mobile' => '240px' ),
							'gap'    => array( 'desktop' => '2.5rem', 'tablet' => '2rem', 'mobile' => '1.5rem' ),
						),
						// The frames are tilted, so the wall needs room at the
						// edges or the corners clip against the group.
						'container'   => array(
							'padding' => array( 'desktop' => '32px 16px', 'tablet' => '28px 12px', 'mobile' => '24px 8px' ),
						),
						'shadow'      => array(
							array( 'hOffset' => '0px', 'vOffset' => '10px', 'blur' => '24px', 'spreed' => '-8px', 'color' => 'rgba(0,0,0,0.22)', 'isInset' => false ),
						),
						'hoverShadow' => array(
							array( 'hOffset' => '0px', 'vOffset' => '18px', 'blur' => '38px', 'spreed' => '-10px', 'color' => 'rgba(0,0,0,0.30)', 'isInset' => false ),
						),
						/*
						 * The caption sits on the frame's white card, so its
						 * colour cannot be left to the theme — on a dark theme
						 * it would inherit white and disappear.
						 */
						'title'       => array( 'color' => '#16181a' ),
						'description' => array( 'color' => '#5b6167' ),
						'date'        => array( 'color' => '#8a9096' ),
					),
					'align'      => 'wide',
				)
			),
			'wide'
		),
	);

	/* Portfolio ------------------------------------------------------------ */
	$patterns['portfolio-showcase'] = array(
		'title'       => __( 'Portfolio showcase', 'image-gallery' ),
		'description' => __( 'Two large cards for a studio or agency, where each project gets room to breathe.', 'image-gallery' ),
		'keywords'    => array( 'portfolio', 'studio', 'agency', 'projects', 'case study' ),
		'content'     => bigb_pattern_group(
			bigb_pattern_heading( __( 'Selected projects', 'image-gallery' ) )
			. bigb_pattern_spacer( 24 )
			. bigb_pattern_block(
				array(
					'styleSl'      => 'styleDefault',
					'gallery'      => array(
						bigb_pattern_album( 1, __( 'Northwind', 'image-gallery' ), __( 'Brand identity · 2026', 'image-gallery' ), array( $dusk, $night, $dawn ) ),
						bigb_pattern_album( 2, __( 'Meridian', 'image-gallery' ), __( 'Packaging · 2025', 'image-gallery' ), array( $sand, $forest, $noon ) ),
					),
					'columns'      => array( 'desktop' => 2, 'tablet' => 1, 'mobile' => 1 ),
					'itemHeight'   => '520px',
					'columnGap'    => '32px',
					'rowGap'       => '32px',
					'titleTypo'    => array( 'fontSize' => array( 'desktop' => 40, 'tablet' => 34, 'mobile' => 28 ) ),
					'subtitleTypo' => array( 'fontSize' => array( 'desktop' => 18, 'tablet' => 16, 'mobile' => 15 ) ),
					'background'   => array( 'type' => 'solid', 'color' => 'transparent' ),
					'align'        => 'wide',
				)
			),
			'wide'
		),
	);

	/* Product lookbook ----------------------------------------------------- */
	$patterns['product-lookbook'] = array(
		'title'       => __( 'Product lookbook', 'image-gallery' ),
		'description' => __( 'A denser four-column set with smaller cards, for a shop lookbook or catalogue page.', 'image-gallery' ),
		'keywords'    => array( 'lookbook', 'shop', 'product', 'catalogue', 'ecommerce' ),
		'content'     => bigb_pattern_group(
			bigb_pattern_heading( __( 'Autumn lookbook', 'image-gallery' ) )
			. bigb_pattern_paragraph( __( 'Four ranges, photographed on location. Tap a card for the full set.', 'image-gallery' ) )
			. bigb_pattern_spacer( 24 )
			. bigb_pattern_block(
				array(
					'styleSl'      => 'styleDefault',
					'gallery'      => array(
						bigb_pattern_album( 1, __( 'Outerwear', 'image-gallery' ), __( '9 pieces', 'image-gallery' ), array( $dusk, $night ) ),
						bigb_pattern_album( 2, __( 'Knitwear', 'image-gallery' ), __( '12 pieces', 'image-gallery' ), array( $sand, $dawn ) ),
						bigb_pattern_album( 3, __( 'Denim', 'image-gallery' ), __( '7 pieces', 'image-gallery' ), array( $noon, $forest ) ),
						bigb_pattern_album( 4, __( 'Accessories', 'image-gallery' ), __( '15 pieces', 'image-gallery' ), array( $forest, $sand ) ),
					),
					'columns'      => array( 'desktop' => 4, 'tablet' => 2, 'mobile' => 1 ),
					'itemHeight'   => '300px',
					'columnGap'    => '16px',
					'rowGap'       => '20px',
					'titleTypo'    => array( 'fontSize' => array( 'desktop' => 26, 'tablet' => 22, 'mobile' => 20 ) ),
					'subtitleTypo' => array( 'fontSize' => array( 'desktop' => 15, 'tablet' => 14, 'mobile' => 14 ) ),
					'background'   => array( 'type' => 'solid', 'color' => 'transparent' ),
					'align'        => 'wide',
				)
			),
			'wide'
		),
	);

	/*
	 * Pattern search matches title, description and keywords — not the category
	 * label. Someone who has just read "Image Galleries" in the category list
	 * and types it into the search box would otherwise get nothing back.
	 */
	$base_keywords = array(
		__( 'gallery', 'image-gallery' ),
		__( 'image gallery', 'image-gallery' ),
		__( 'image galleries', 'image-gallery' ),
		__( 'photo gallery', 'image-gallery' ),
	);

	foreach ( $patterns as $slug => $pattern ) {
		register_block_pattern(
			'image-gallery/' . $slug,
			array(
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => array( $category ),
				'keywords'    => array_values( array_unique( array_merge( $base_keywords, $pattern['keywords'] ) ) ),
				'content'     => $pattern['content'],
			)
		);
	}
}

/**
 * Late on init: register_block_pattern() silently drops a pattern whose blocks
 * are not registered yet, and the block registers on init at the default
 * priority.
 */
add_action( 'init', 'bigb_register_patterns', 20 );
