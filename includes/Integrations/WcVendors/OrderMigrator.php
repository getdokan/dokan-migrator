<?php

namespace Wedevs\DokanMigrator\Integrations\WcVendors;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanMigrator\Abstracts\OrderMigration;
use WeDevs\DokanMigrator\Helpers\MigrationHelper;
use WC_Order;

/**
 * Order migration class.
 *
 * @since 1.1.0
 */
class OrderMigrator extends OrderMigration {

    /**
     * Class constructor.
     *
     * @since 1.1.0
     *
     * @param \WC_Order $order
     */
    public function __construct( \WC_Order $order ) {
        $this->order_id = $order->get_id();
        $this->order    = $order;
    }

    /**
     * Create sub order if needed.
     *
     * @since 1.1.0
     *
     * @param int   $seller_id
     * @param array $seller_products
     *
     * @return \WC_Order
     */
    public function create_sub_order_if_needed( $seller_id, $seller_products, $parent_order_id ) {
        /**
         * Before creating sub order, we need to convert the meta key according to
         * Dokan meta key for order items as those data will be parsed while creating
         * sub orders.
         */
        MigrationHelper::map_shipping_method_item_meta( $this->order );
        dokan()->order->create_sub_order( $this->order, $seller_id, $seller_products );

        $res = dokan()->order->all(
            [
                'limit'     => 1,
                'parent'    => $this->order->get_id(),
                'seller_id' => $seller_id,
            ]
        );

        /**
         * @var $created_suborder WC_Order|\WC_Order_Refund
         */
        $created_suborder = reset( $res );

        return $created_suborder;
    }

    /**
     * Delete sub orders if needed.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function reset_sub_orders_if_needed() {
        $this->reset_sub_orders();
    }

    /**
     * Gets order data from wc-vendors order table for dokan.
     *
     * @since 1.1.0
     *
     * @param int $parent_order_id
     * @param int $seller_id
     *
     * @return array
     */
    public function get_dokan_order_data( $parent_order_id, $seller_id ) {
        global $wpdb;

        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$wpdb->prefix}pv_commission commission
                WHERE commission.vendor_id = %d
                AND commission.order_id = %d",
                $seller_id,
                $parent_order_id
            )
        );

        $wc_order    = wc_get_order( $parent_order_id );
        $net_amount  = 0;
        $order_total = $wc_order->get_total();
        $commissions = [];

        if ( $wc_order->get_total_refunded() ) {
            $order_total = $order_total - $wc_order->get_total_refunded();
        }

        foreach ( $orders as $key => $order ) {
            $net_amount += $order->total_due + $order->total_shipping + $order->tax;

            $res_commission = [
                'type'             => 'percent',
                'fixed'            => '',
                'percentage'       => '',
                'item_id'          => '',
                'admin_commission' => 0,
                'product_id'       => $order->product_id,
                'created'          => $order->time,
            ];

            $non_zero_sub_total_amount = empty( $wc_order->get_subtotal() ) || $wc_order->get_subtotal() < 1 ? 1 : $wc_order->get_subtotal();

            $unit_commissin_rate_vendor = ( $order->total_due / $non_zero_sub_total_amount ) * 100;
            $unit_commissin_rate_admin  = 100 - $unit_commissin_rate_vendor;
            $new_admin_commissin        = ( $wc_order->get_subtotal() * $unit_commissin_rate_admin ) / 100;

            $res_commission['percentage']       = number_format( (float) $unit_commissin_rate_admin, 2, '.', '' );
            $res_commission['admin_commission'] = $new_admin_commissin;

            array_push( $commissions, $res_commission );
        }

        $admin_commission = 0;

        foreach ( $commissions as $com ) {
            $admin_commission += $com['admin_commission'];
        }

        $dokan_order_data = [
            'commission_data'         => $commissions,
            'order_total'             => $order_total,
            'net_sale'                => $net_amount,
            'admin_commission_amount' => $admin_commission,
        ];

        return $dokan_order_data;
    }

    /**
     * Process refund for a child order.
     *
     * @since 1.1.0
     *
     * @param \Wc_Order $child_order
     * @param integer   $seller_id
     * @param boolean   $from_suborder
     *
     * @return void
     */
    public function process_refund( $child_order, $seller_id, $from_suborder = true ) {
        global $wpdb;

        $order            = wc_get_order( $child_order->get_id() );
        $new_total_amount = $order->get_total() - $order->get_total_refunded();

        // insert into dokan sync table
        $wpdb->update(
            $wpdb->prefix . 'dokan_orders',
            [
                'order_total' => $new_total_amount,
            ],
            [
                'order_id' => $child_order->get_id(),
            ],
            [
                '%f',
            ]
        );
    }

    /**
     * Returns all sellers of an order.
     *
     * @since 1.1.0
     *
     * @param int $order_id
     *
     * @return array
     */
    public function get_seller_by_order( $order_id ) {
        return dokan_get_sellers_by( $order_id );
    }
}
