<?php

namespace WeDevs\DokanMigrator\Processors;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use \WP_User_Query;
use WeDevs\DokanMigrator\Abstracts\Processor;
use WeDevs\DokanMigrator\Integrations\Wcfm\VendorMigrator as WcfmVendorMigrator;

/**
 * Vendor migration handler class.
 *
 * @since DOKAN_MIG_SINCE
 */
class Vendor extends Processor {

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
     * @since DOKAN_MIG_SINCE
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
     * @since DOKAN_MIG_SINCE
     *
     * @param string $plugin
     * @param object $payload
     *
     * @return object
     */
    public static function get_migration_class( $plugin, $payload ) {
        switch ( $plugin ) {
            case 'wcfmmarketplace':
                return new WcfmVendorMigrator( $payload );

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
        delete_option( 'dokan_migration_completed' );
        throw new \Exception( __( 'No vendors found to migrate to dokan.', 'dokan-migrator' ) );
    }
}
