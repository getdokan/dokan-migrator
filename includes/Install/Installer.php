<?php

namespace WeDevs\DokanMigrator\Install;

/**
 * Dokan migrator installer class
 *
 * @since 1.0.0
 *
 * @author weDevs
 */
class Installer {

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->do_install();
    }

    /**
     * Adds installer data.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function do_install() {
        $this->add_version_info();
    }

    /**
     * Adds plugin installation time.
     *
     * @since 1.0.0
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
