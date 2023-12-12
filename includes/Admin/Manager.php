<?php

namespace WeDevs\DokanMigrator\Admin;

use WeDevs\DokanMigrator\Helpers\MigrationHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan Migrator Manager Class
 *
 * @since 1.0.0
 */
class Manager {

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        $this->init_classes();
        $this->hooks();
    }

    /**
     * Inits class for admin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function init_classes() {
        new Menu();
    }

    /**
     * Inits hooks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function hooks() {
        add_action( 'admin_notices', [ $this, 'show_dokan_dashboard_activate_notice' ] );
    }

    /**
     * Show activate vendor dashboard notice
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function show_dokan_dashboard_activate_notice() {
        if ( get_option( 'dokan_migration_completed', false ) ) {
            require_once DOKAN_MIGRATOR_TEMPLATE_PATH . 'template-active-vendor-dashboard.php';
        }

        $data = MigrationHelper::get_last_migrated();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( 'yes' !== $data['migration_success'] && ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'dokan-migrator' ) ) {
            require_once DOKAN_MIGRATOR_TEMPLATE_PATH . 'template-alert-migrate-to-dokan.php';
        }
    }
}
