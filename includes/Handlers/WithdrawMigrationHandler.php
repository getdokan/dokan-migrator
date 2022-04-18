<?php

namespace Wedevs\DokanMigrator\Handlers;

use Wedevs\DokanMigrator\Abstracts\Handler;

use Wedevs\DokanMigrator\Integrations\Wcfm\WithdrawMigrator as WcfmWithdrawMigrator;
use Wedevs\DokanMigrator\Integrations\YithMultiVendor\WithdrawMigrator as YithMultiVendorWithdrawMigrator;

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

            case 'yithvendors':
                $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}yith_vendors_commissions WHERE status='paid' AND type='product'";
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

            case 'yithvendors':
                $sql = "SELECT * FROM {$wpdb->prefix}yith_vendors_commissions WHERE status='paid' AND type='product' ORDER BY id LIMIT %d OFFSET %d";
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
                return new WcfmWithdrawMigrator();;
                break;

            case 'yithvendors':
                return new YithMultiVendorWithdrawMigrator();
                break;

            default:
                break;
        }
    }
}
