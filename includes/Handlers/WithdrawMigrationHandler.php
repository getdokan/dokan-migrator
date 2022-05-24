<?php

namespace Wedevs\DokanMigrator\Handlers;

use Wedevs\DokanMigrator\Abstracts\Handler;

use Wedevs\DokanMigrator\Integrations\Wcfm\WithdrawMigrator as WcfmWithdrawMigrator;
use Wedevs\DokanMigrator\Integrations\WcVendors\WithdrawMigrator as WcVendorsWithdrawMigrator;

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
        global $wpdb;

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_marketplace_withdraw_request" );

            case 'wcvendors':
                return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pv_commission WHERE status='paid'" );

            default:
                return 0;
        }
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

            case 'wcvendors':
                return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pv_commission WHERE status='paid' ORDER BY id LIMIT %d OFFSET %d", $number, $offset ) );

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

            case 'wcvendors':
                return new WcVendorsWithdrawMigrator();

            default:
                break;
        }
    }
}
