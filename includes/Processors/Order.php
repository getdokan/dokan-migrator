<?php

namespace WeDevs\DokanMigrator\Processors;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanMigrator\Abstracts\Processor;
use WeDevs\DokanMigrator\Integrations\Wcfm\OrderMigrator as WcfmOrderMigrator;
use WeDevs\DokanMigrator\Integrations\WcVendors\OrderMigrator as WcVendorsOrderMigrator;

/**
 * Vendor migration handler class.
 *
 * @since 1.0.0
 */
class Order extends Processor {

    /**
     * Returns count of items vendor.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     *
     * @return integer
     */
    public static function get_total( $plugin ) {
        global $wpdb;

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $total = (int) $wpdb->get_var( "SELECT COUNT( DISTINCT order_id ) FROM {$wpdb->prefix}wcfm_marketplace_orders" );
                break;

            case 'wcvendors':
                $total = (int) dokan()->order->all(
                    [
                        'return' => 'count',
                        'parent' => 0,
                    ]
                );
                break;

            default:
                $total = 0;
        }

        return $total;
    }

    /**
     * Returns array of items vendor.
     *
     * @since 1.0.0
     *
     * @return \WC_Order[]
     *
     * @throws \Exception
     */
    public static function get_items( $plugin, $number, $offset, $paged ) {
        global $wpdb;

        $args = array(
            'order'  => 'ASC',
            'paged'  => $paged,
            'limit'  => $number,
            'parent' => 0,
        );

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $orders = [];
                $wcfm_orders = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT order_id FROM {$wpdb->prefix}wcfm_marketplace_orders LIMIT %d OFFSET %d", $number, $offset ), ARRAY_A );
                $wcfm_orders = array_map(
                    function ( $item ) {
                        return $item['order_id'];
                    },
                    $wcfm_orders
                );

                if ( ! empty( $wcfm_orders ) ) {
                    $orders = dokan()->order->all(
                        [
                            'include' => $wcfm_orders,
                        ]
                    );
                }
                break;

            case 'wcvendors':
                $orders = dokan()->order->all( $args );
                break;

            default:
                $orders = [];
        }

        if ( empty( $orders ) ) {
            self::throw_error();
        }

        return $orders;
    }

    /**
     * Return class to handle migration.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     * @param object $payload
     *
     * @return object|WcfmOrderMigrator
     * @throws \Exception
     */
    public static function get_migration_class( $plugin, $payload ) {
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return new WcfmOrderMigrator( $payload );

            case 'wcvendors':
                return new WcVendorsOrderMigrator( $payload );
        }

        throw new \Exception( __( 'Migrator class not found', 'dokan-migrator' ) );
    }

    /**
     * Throws error on empty data or unsupported plugin.
     *
     * @since 1.0.0
     *
     * @return void
     * @throws \Exception
     */
    public static function throw_error() {
        delete_option( 'dokan_migrator_last_migrated' );
        update_option( 'dokan_migration_completed', 'yes' );
        update_option( 'dokan_migration_success', 'yes' );
        throw new \Exception( __( 'No orders found to migrate to dokan.', 'dokan-migrator' ) );
    }
}
