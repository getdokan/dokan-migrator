<?php

namespace WeDevs\DokanMigrator\Processors;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WeDevs\DokanMigrator\Abstracts\Processor;
use WeDevs\DokanMigrator\Integrations\Wcfm\OrderMigrator as WcfmOrderMigrator;

class Order extends Processor {

    /**
     * Returns count of items vendor.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $plugin
     *
     * @return integer
     */
    public static function get_total( $plugin ) {
        global $wpdb;

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type='shop_order' AND post_parent=0" );
    }

    /**
     * Returns array of items vendor.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return array
     * @throws \Exception
     */
    public static function get_items( $plugin, $number, $offset ) {
        $args = array(
            'post_type'      => 'shop_order',
            'orderby'        => 'ASC',
            'post_status'    => 'any',
            'offset'         => $offset,
            'posts_per_page' => $number,
            'post_parent'    => 0,
        );

        $orders = get_posts( $args );

        if ( empty( $orders ) ) {
            self::throw_error();
        }

        return $orders;
    }

    /**
     * Return class to handle migration.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return Class
     */
    public static function get_migration_class( $plugin ) {
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return new WcfmOrderMigrator();

            default:
                break;
        }
    }

    /**
     * Throws error on empty data or unsupported plugin.
     *
     * @since DOKAN_MIG_SINCE
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
