<?php

namespace WeDevs\DokanMigrator\Install;

/**
 * Dokan migrator installer class
 *
 * @since DOKAN_MIG_SINCE
 *
 * @author weDevs
 */
class Installer {

    /**
     * Class constructor.
     *
     * @since DOKAN_MIG_SINCE
     */
    public function __construct() {
        $this->do_install();
    }

    /**
     * Adds installer data.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function do_install() {
        $this->add_version_info();
    }

    /**
     * Adds plugin installation time.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return boolean
     */
    public function add_version_info() {
        if ( empty( get_option( 'dokan_migrator_installed_time' ) ) ) {
            $current_time = current_datetime()->getTimestamp();
            update_option( 'dokan_migrator_installed_time', $current_time );
        }

        update_option( 'dokan_migrator_plugin_version', DOKAN_MIGRATOR_PLUGIN_VERSION );
    }
}
