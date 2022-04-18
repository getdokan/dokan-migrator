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
        $total_count = 0;
        global $wpdb;
        $sql = '';

        switch ($plugin) {
            case 'wcfmmarketplace':
                $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_marketplace_withdraw_request";
                break;

            case 'wcvendors':
                $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}pv_commission WHERE status='paid'";
                break;

            default:
                break;
        }

        $sql_prepared = $wpdb->prepare( $sql );
        $total_count  = (int) $wpdb->get_var( $sql_prepared );

        return $total_count;
    }

    /**
     * Returns array of items vendor.
     *
     * @since 1.0.0
     *
     * @return array
     */
    function get_items( $plugin, $number, $offset ) {
        global $wpdb;
        $sql = '';

        switch ($plugin) {
            case 'wcfmmarketplace':
                $sql = "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_withdraw_request ORDER BY ID LIMIT %d OFFSET %d";
                break;

            case 'wcvendors':
                $sql = "SELECT * FROM {$wpdb->prefix}pv_commission WHERE status='paid' ORDER BY id LIMIT %d OFFSET %d";
                break;

            default:
                return [];
                break;
        }

        $prepared_sql = $wpdb->prepare( $sql, $number, $offset );
        return $wpdb->get_results( $prepared_sql );
    }

    /**
     * Return class to handle migration.
     *
     * @since 1.0.0
     *
     * @return Class
     */
    function get_migration_class($plugin){
        switch ($plugin) {
            case 'wcfmmarketplace':
                return new WcfmWithdrawMigrator();
                break;

            case 'wcvendors':
                return new WcVendorsWithdrawMigrator();
                break;

            default:
                break;
        }
    }
}
