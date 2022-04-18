<?php

namespace Wedevs\DokanMigrator\Handlers;

use \WP_User_Query;

use Wedevs\DokanMigrator\Abstracts\Handler;
use Wedevs\DokanMigrator\Integrations\Wcfm\VendorMigrator as WcfmVendorMigrator;
use Wedevs\DokanMigrator\Integrations\YithMultiVendor\VendorMigrator as YithMultiVendorVendorMigrator;

class VendorMigrationHandler extends Handler {

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

        switch ($plugin) {
            case 'wcfmmarketplace':
                $total_count = count( get_users( array( 'role' => 'wcfm_vendor' ) ) );
                break;

            case 'yithvendors':
                return count( get_terms( [
                    'taxonomy'   => 'yith_shop_vendor',
                    'hide_empty' => false,
                ] ) );
                break;

            default:
                break;
        }

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
        $args = [
            'number' => $number,
            'offset' => $offset,
            'order'  => 'ASC'
        ];

        switch ($plugin) {
            case 'wcfmmarketplace':
                $args['role'] = 'wcfm_vendor';
                break;

            case 'yithvendors':
                $args['role'] = 'yith_vendor';
                break;

            default:
                return [];
                break;
        }

        $user_query = new WP_User_Query( $args );

        return $user_query->get_results();
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
                return new WcfmVendorMigrator();
                break;

            case 'yithvendors':
                return new YithMultiVendorVendorMigrator();
                break;

            default:
                break;
        }
    }
}
