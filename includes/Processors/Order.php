<?php

namespace WeDevs\DokanMigrator\Processors;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WeDevs\DokanMigrator\Abstracts\Processor;
use WeDevs\DokanMigrator\Integrations\Wcfm\OrderMigrator as WcfmOrderMigrator;

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
			    return (int) $wpdb->get_var(
				    "SELECT COUNT(DISTINCT p.ID)
					FROM wp_posts p
					INNER JOIN {$wpdb->prefix}wcfm_marketplace_orders ON p.ID = {$wpdb->prefix}wcfm_marketplace_orders.order_id
					"
			    );

		    default:
			    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type='shop_order' AND post_parent=0" );
	    }
    }

    /**
     * Returns array of items vendor.
     *
     * @since 1.0.0
     *
     * @return array
     * @throws \Exception
     */
    public static function get_items( $plugin, $number, $offset ) {
		global $wpdb;
        $args = array(
            'post_type'      => 'shop_order',
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'post_status'    => 'any',
            'offset'         => $offset,
            'posts_per_page' => $number,
            'post_parent'    => 0,
        );

	    switch ( $plugin ) {
		    case 'wcfmmarketplace':
			    $orders = $wpdb->get_results(
				    "SELECT p.*
					FROM wp_posts p
					INNER JOIN {$wpdb->prefix}wcfm_marketplace_orders ON p.ID = {$wpdb->prefix}wcfm_marketplace_orders.order_id
					ORDER BY p.ID DESC
					LIMIT 3
					OFFSET 0
					"
			    );
				break;

		    default:
			    $orders = get_posts( $args );
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
     * @return object
     * @throws \Exception
     */
    public static function get_migration_class( $plugin, $payload ) {
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return new WcfmOrderMigrator( $payload );
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
