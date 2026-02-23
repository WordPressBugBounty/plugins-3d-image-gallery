<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

class igbSubMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
	}

	function adminMenu(){
		add_submenu_page(
			'tools.php',
			__('Image Gallery', 'image-gallery'),
			__('Image Gallery', 'image-gallery'),
			'manage_options',
			'3d-image-gallery-dashboard',
            [$this, 'renderDashboard'],
		);
	}

    function renderDashboard(){ ?>
			<div
				id='igbDashboard'
				data-info='<?php echo esc_attr( wp_json_encode( [
					'version' => BIGB_PLUGIN_VERSION,
					'isPremium' => ig_IsPremium(),
					'hasPro' => BIGB_HAS_PRO,
					'nonce' => wp_create_nonce('igb_activation_nonce'),
					'licenseActiveNonce' => wp_create_nonce( 'igb_activation_nonce' ),
				] ) ); ?>'
			></div>
		<?php }



    function adminEnqueueScripts($hook)
    {
        if ('tools_page_3d-image-gallery-dashboard' === $hook) {
            wp_enqueue_style('ig-admin-style', BIGB_DIR_URL . 'build/admin/dashboard.css', false, BIGB_PLUGIN_VERSION);
            wp_enqueue_script('ig-admin-script', BIGB_DIR_URL . 'build/admin/dashboard.js', ['react', 'react-dom', 'wp-data', "wp-api", "wp-util", "wp-i18n"], BIGB_PLUGIN_VERSION, true);

                wp_localize_script('ig-admin-script', 'igAdmin', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('igb_activation_nonce'),
                ]);
            }
        }
}
new igbSubMenu();