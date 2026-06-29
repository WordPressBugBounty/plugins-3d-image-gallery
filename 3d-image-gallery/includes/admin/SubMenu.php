<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

class igbSubMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
		add_action( 'wp_ajax_igbSaveUninstallOption', [ $this, 'handleUninstallOption' ] );
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
					'isPremium' => false,
					'hasPro' => false,
					'adminUrl' => admin_url(),
					'nonce' => wp_create_nonce('igb_activation_nonce'),
					'licenseActiveNonce' => wp_create_nonce( 'igb_activation_nonce' ),
					'uninstallNonce' => wp_create_nonce('igb_activation_nonce'),
					'deleteDataOnUninstall' => (bool) get_option( 'igb_delete_data_on_uninstall', false ),
				] ) ); ?>'
			></div>
		<?php }

	function handleUninstallOption() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'igb_activation_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'image-gallery' ) ] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'image-gallery' ) ] );
		}

		$enabled = isset( $_POST['enabled'] ) && ( 'true' === $_POST['enabled'] || true === $_POST['enabled'] || 1 == $_POST['enabled'] );
		update_option( 'igb_delete_data_on_uninstall', $enabled );

		wp_send_json_success( [
			'enabled' => $enabled,
			'message' => __( 'Setting saved successfully.', 'image-gallery' ),
		] );
	}



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