<?php

namespace WeDevs\DokanMigrator\Helpers;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan migrator helper class
 * This class holds the helper methods for dokan migration.
 *
 * @since 1.0.0
 */
class MigrationHelper {

    /**
     * Last migrated data, vendor/order/withdraw. and migratable plugin.
     *
     * @since 1.0.0
     *
     * @return array{last_migrated:string,migratable:string,migration_success:bool,set_title:string}
     */
    public static function get_last_migrated() {
        $last_migrated     = get_option( 'dokan_migrator_last_migrated', 'vendor' );
        $migration_success = get_option( 'dokan_migration_success', false );
        $migratable        = self::get_migratable_plugin();

        return array(
            'last_migrated'     => $last_migrated,
            'migratable'        => $migratable,
            'migration_success' => $migration_success,
            'set_title'         => self::get_migration_title( $migratable ),
        );
    }

    /**
     * Deactivate wcfm plugins.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function active_vendor_dashboard() {
        $all_plugins_to_deactivate = [];

        // Wcfm plugins.
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-frontend-manager/wc_frontend_manager.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-multivendor-marketplace/wc-multivendor-marketplace.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-multivendor-membership/wc-multivendor-membership.php';

        // Wc vendors plugins.
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-vendors-pro-simple-auctions/class-wcv-simple-auctions.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-vendors-pro/wcvendors-pro.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-vendors-woocommerce-bookings/wcv-woocommerce-bookings.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-vendors-woocommerce-subscriptions/wcv-wc-subscriptions.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-vendors/class-wc-vendors.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-vendors-membership/wc-vendors-membership.php';

        deactivate_plugins( $all_plugins_to_deactivate );

        delete_option( 'dokan_migration_completed' );
        /**
         * Deleting this option, because sometimes after migrating to dokan from other marketplace
         * dokan dashboard page conflicts with their pages so deleting this option will enable user
         * to re-create dokan pages from dokan tools page.
         */
        delete_option( 'dokan_pages_created' );

        wp_send_json_success( __( 'Dokan vendor dashboard activated.', 'dokan-migrator' ) );
    }

    /**
     * Get post by post name
     *
     * @since 1.0.0
     *
     * @param string $name
     * @param string $post_type
     *
     * @return \WP_Post|null
     */
    public static function get_post_by_name( $name, $post_type = 'page' ) {
        $query = new \WP_Query(
            array(
                'post_type' => $post_type,
                'name'      => $name,
            )
        );

        return $query->have_posts() ? reset( $query->posts ) : null;
    }

    /**
     * Get get migration title.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     *
     * @return string
     */
    public static function get_migration_title( $plugin ) {
        $title = __( 'Migrate to dokan', 'dokan-migrator' );

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $title = __( 'Migrate Wcfm To Dokan', 'dokan-migrator' );
                break;

            case 'wcvendors':
                $title = __( 'Migrate Wc Vendors To Dokan.', 'dokan-migrator' );
                break;

            default:
                break;
        }

        return $title;
    }

    /**
     * Returns migratable plugin name like: wcfm, wcvendors, wcmarketplace, .......
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_migratable_plugin() {
        $active_plugins = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }

        // WCfM Multivendor Marketplace Check
        $is_marketplace = ( in_array( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins, true ) || array_key_exists( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins ) || class_exists( 'WCFMmp' ) ) ? 'wcfmmarketplace' : false;

        // WC Vendors Check
        if ( ! $is_marketplace ) {
            $is_marketplace = ( in_array( 'wc-vendors/class-wc-vendors.php', $active_plugins, true ) || array_key_exists( 'wc-vendors/class-wc-vendors.php', $active_plugins ) || class_exists( 'WC_Vendors' ) ) ? 'wcvendors' : false;
        }

        return $is_marketplace;
    }


    /**
     * Converts the order item meta key according to
     * Dokan meta key as those data will be parsed
     * while creating sub orders.
     *
     * @param WC_Order $order
     * @param string   $map_by
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function map_shipping_method_item_meta( WC_Order $order, $map_by = 'vendor_id' ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $shipping_methods = $order->get_shipping_methods();
        if ( empty( $shipping_methods ) ) {
            return;
        }

        foreach ( $shipping_methods as $method_item_id => $shipping_object ) {
            $seller_id = wc_get_order_item_meta( $method_item_id, $map_by, true );

            if ( ! $seller_id ) {
                continue;
            }

            wc_update_order_item_meta(
                $method_item_id,
                'seller_id',
                wc_get_order_item_meta( $method_item_id, $map_by, true )
            );
        }
    }

    /**
     * Split shipping amount for all vendors if wcfm processing an order as admin shipping.
     *
     * @since 1.1.0
     *
     * @param WC_Order_Item_Shipping $applied_shipping_method
     * @param int                    $order_id
     * @param WC_Order               $parent_order
     *
     * @return WC_Order_Item_Shipping
     */
    public static function split_parent_order_shipping( $applied_shipping_method, $order_id, $parent_order ) {
        /**
         * Not empty means parent order has sub-order and this order is processed as vendor wise shipping in wcfm.
         * Or if parent order has no shipping methods the return it.
         */
        if ( ! empty( $applied_shipping_method ) || count( $parent_order->get_shipping_methods() ) < 1 ) {
            return $applied_shipping_method;
        }

        $applied_shipping_method = reset( $parent_order->get_shipping_methods() );
        $vendors                 = dokan_get_sellers_by( $parent_order->get_id() );
        $vendors_count           = empty( count( $vendors ) ) ? 1 : count( $vendors );

        // Here we are dividing the shipping and shipping-tax amount of parent order into the vendors suborders.
        $shipping_tax_amount = [
            'total' => [ $applied_shipping_method->get_total_tax() / $vendors_count ],
        ];
        $shipping_amount = $applied_shipping_method->get_total() / $vendors_count;

        // Generating the shipping for vendor.
        $item = new WC_Order_Item_Shipping();
        $item->set_props(
            array(
                'method_title' => $applied_shipping_method->get_name(),
                'method_id'    => $applied_shipping_method->get_method_id(),
                'total'        => $shipping_amount,
                'taxes'        => $shipping_tax_amount,
            )
        );

        $child_order = new WC_order( $order_id );
        $products    = $child_order->get_items();

        // Generating the shipping Item text.
        $text = '';
        foreach ( $products as $key => $product ) {
            $current_item = $product->get_data();
            $product_text = $current_item['name'] . ' &times; ' . $current_item['quantity'];

            if ( $key !== array_key_first( $products ) ) {
                $text .= ', ' . $product_text;
            }

            $text .= $product_text;
        }

        // Adding shipping item meta data.
        $item->add_meta_data( 'Items', $text );

        return $item;
    }
}
