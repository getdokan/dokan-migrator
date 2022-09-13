<?php

namespace Wedevs\DokanMigrator\Processors;

use \WP_User_Query;

use Wedevs\DokanMigrator\Abstracts\Processor;
use Wedevs\DokanMigrator\Integrations\Wcfm\VendorMigrator as WcfmVendorMigrator;

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
        $args   = [
            'number' => $number,
            'offset' => $offset,
            'order'  => 'ASC',
        ];

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $args['role'] = 'wcfm_vendor';
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
     * @return Class
     */
    public static function get_migration_class( $plugin ) {
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return new WcfmVendorMigrator();

            default:
                break;
        }
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
        throw new \Exception( 'No vendors found to migrate to dokan.' );
    }
}