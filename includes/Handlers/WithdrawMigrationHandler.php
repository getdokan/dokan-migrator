<?php

namespace Wedevs\DokanMigrator\Handlers;

use Wedevs\DokanMigrator\Abstracts\Handler;

use Wedevs\DokanMigrator\Integrations\Wcfm\WithdrawMigrator as WcfmWithdrawMigrator;

class WithdrawMigrationHandler extends Handler {

    /**
     * Returns count of items vendor.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     *
     * @return integer
     */
    public function get_total( $plugin ) {
        $total_count = 0;
        global $wpdb;

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_marketplace_withdraw_request" );
                break;

            default:
                break;
        }

        return $total_count;
    }

    /**
     * Returns array of items vendor.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_items( $plugin, $number, $offset ) {
        global $wpdb;

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_withdraw_request ORDER BY ID LIMIT %d OFFSET %d", $number, $offset ) );

            default:
                return [];
        }
    }

    /**
     * Return class to handle migration.
     *
     * @since 1.0.0
     *
     * @return Class
     */
    public function get_migration_class( $plugin ) {
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return new WcfmWithdrawMigrator();

            default:
                break;
        }
    }
}
