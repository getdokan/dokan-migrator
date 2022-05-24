<?php

namespace Wedevs\DokanMigrator\Admin;

/**
 * Dokan Migrator Menu Class
 *
 * @since 1.0.0
 */
class Menu {

    /**
     * Class constructor
     *
     * @since 1.0.0
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
        $migration_page = add_menu_page(
            __( 'Dokan Migrator', 'dokan-migrator' ),
            __( 'Dokan Migrator', 'dokan-migrator' ),
            'manage_options',
            'dokan-migrator',
            [ $this, 'migration_page' ],
            'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><g fill="#9EA3A8" fill-rule="nonzero"><path d="M2.565 1.909s10.352-.665 10.352 7.231-2.735 9.481-5.134 9.994c0 0 9.974 2.125 9.974-8.995S5.035.624 2.565 1.91z"/><path d="M10.982 15.353s-.999 3.07-4.018 3.461c-3.02.39-3.582-1.062-5.688-.962 0 0-.171-1.441 1.529-1.644 1.7-.202 4.885.193 7.004-.991 0 0 1.253-.582 1.499-.862l-.326.998z"/><path d="M2.407 2.465V15.74a3.29 3.29 0 01.32-.056 18.803 18.803 0 011.794-.083c.624 0 1.306-.022 1.987-.078v-4.465c0-1.485-.107-3.001 0-4.484.116-.895.782-1.66 1.73-1.988.759-.25 1.602-.2 2.316.135-3.414-2.24-7.25-2.284-8.147-2.255z"/></g></svg>' ) // phpcs:ignore
        );

        // Add migration script and styles.
        add_action( $migration_page, [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Enqueue migration script and styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        $asset = require_once DOKAN_MIGRATOR_PLUGIN_ASSETS_DRI . '/dist/index.asset.php';

        wp_enqueue_script(
            'dokan-migrator-script',
            DOKAN_MIGRATOR_PLUGIN_ASSETS . '/dist/index.js',
            $asset['dependencies'],
			$asset['version'],
            true
        );
        wp_enqueue_style(
            'dokan-migrator-style',
            DOKAN_MIGRATOR_PLUGIN_ASSETS . '/dist/index.css',
            [],
            $asset['version']
        );

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
    }
}
