<?php

namespace WeDevs\DokanMigrator\Processors;

defined( 'ABSPATH' ) || exit;

use \WP_User_Query;
use WeDevs\DokanMigrator\Abstracts\Processor;
use WeDevs\DokanMigrator\Integrations\Wcfm\VendorMigrator as WcfmVendorMigrator;
use WeDevs\DokanMigrator\Integrations\WcVendors\VendorMigrator as WcVendorsVendorMigrator;

/**
 * Vendor migration handler class.
 *
 * @since 1.0.0
 */
class Vendor extends Processor {

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
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return count( get_users( array( 'role' => 'wcfm_vendor' ) ) );

            case 'wcvendors':
                return count( get_users( array( 'role__in' => [ 'vendor', 'pending_vendor' ] ) ) );

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
     * @throws \Exception
     */
    public static function get_items( $plugin, $number, $offset ) {
        $args = [
            'number' => $number,
            'offset' => $offset,
            'order'  => 'ASC',
        ];

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $args['role'] = 'wcfm_vendor';
                break;

            case 'wcvendors':
                $args['role__in'] = [ 'vendor', 'pending_vendor' ];
                break;

            default:
                self::throw_error();
        }

        $user_query = new WP_User_Query( $args );
        $vendors    = $user_query->get_results();

        if ( empty( $vendors ) ) {
            self::throw_error();
        }

        return $vendors;
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
                return new WcfmVendorMigrator( $payload );

            case 'wcvendors':
                return new WcVendorsVendorMigrator( $payload );
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
        delete_option( 'dokan_migration_completed' );
        throw new \Exception( __( 'No vendors found to migrate to dokan.', 'dokan-migrator' ) );
    }
}
