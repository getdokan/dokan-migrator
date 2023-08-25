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
        return (int) dokan()->order->all( [ 'return' => 'count' ] );
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
    public static function get_items( $plugin, $number, $offset ) {
	    $args = array(
		    'order'  => 'ASC',
		    'paged'  => $offset + 1,
		    'limit'  => $number,
		    'parent' => 0,
	    );

	    $orders = dokan()->order->all( $args );

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
