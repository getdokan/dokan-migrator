<?php

namespace WeDevs\DokanMigrator\Admin;

use WeDevs\DokanMigrator\Helpers\MigrationHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan Migrator Manager Class
 *
 * @since DOKAN_MIG_SINCE
 */
class Manager {

    /**
     * Class constructor
     *
     * @since DOKAN_MIG_SINCE
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
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    protected function init_classes() {
        new Menu();
    }

    /**
     * Inits hooks.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    protected function hooks() {
        add_action( 'admin_notices', [ $this, 'show_dokan_dashboard_activate_notice' ] );
    }

    /**
     * Show activate vendor dashboard notice
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function show_dokan_dashboard_activate_notice() {
        if ( get_option( 'dokan_migration_completed', false ) ) {
            require_once DOKAN_MIGRATOR_TEMPLATE_PATH . 'template-active-vendor-dashboard.php';
        }

        $data = MigrationHelper::get_last_migrated();

        if ( 'yes' !== $data['migration_success'] ) {
            require_once DOKAN_MIGRATOR_TEMPLATE_PATH . 'template-alert-migrate-to-dokan.php';
        }
    }
}
