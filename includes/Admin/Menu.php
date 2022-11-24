<?php

namespace WeDevs\DokanMigrator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan Migrator Menu Class
 *
 * @since DOKAN_MIG_SINCE
 */
class Menu {

    /**
     * Class constructor
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * Register the admin menu.
     *
     * @return void
     */
    public function admin_menu() {
        add_submenu_page(
            'tools.php',
            __( 'Dokan Migrator', 'dokan-migrator' ),
            __( 'Dokan Migrator', 'dokan-migrator' ),
            'manage_options',
            'dokan-migrator',
            [ $this, 'migration_page' ]
        );
    }

    /**
     * Enqueue migration script and styles.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function enqueue_scripts() {
        $asset = require_once DOKAN_MIGRATOR_PLUGIN_ASSETS_DRI . '/dist/index.asset.php';

        wp_enqueue_script( 'dokan-migrator-script' );
        wp_enqueue_style( 'dokan-migrator-style' );

        wp_localize_script(
            'dokan-migrator-script',
            'dokan_migrator',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'dokan_migrator_nonce' ),
            ]
        );
    }

    /**
     * Display the plugin page.
     *
     * @return void
     */
    public function migration_page() {
        echo '<div id="dokan-migrator-app"></div>';

        // Add migration script and styles.
        $this->enqueue_scripts();
    }
}
