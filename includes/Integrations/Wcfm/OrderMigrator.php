<?php

namespace Wedevs\DokanMigrator\Integrations\Wcfm;

use Wedevs\DokanMigrator\Abstracts\OrderMigration;

/**
 * Order migration class.
 *
 * @since 1.0.0
 */
class OrderMigrator extends OrderMigration {

    /**
     * Create sub order if needed
     *
     * @since 1.0.0
     *
     * @param int $seller_id
     * @param array $seller_products
     *
     * @return \WC_Order
     */
    public function create_sub_order_if_needed( $seller_id, $seller_products, $parent_order_id ) {
        return $this->create_sub_order( $seller_id, $seller_products );
    }

    /**
     * Delete sub orders of needed.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function reset_sub_orders_if_needed() {
        $this->reset_sub_orders();
    }

    /**
     * Gets order data from wcfm order table for dokan.
     *
     * @since 1.0.0
     *
     * @param int $parent_order_id
     * @param int $seller_id
     *
     * @return array
     */
    public function get_dokan_order_data( $parent_order_id, $seller_id ) {
        global $wpdb;

        $orders      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_orders orders WHERE orders.vendor_id = %d AND orders.order_id = %d", $seller_id, $parent_order_id ) );
        $net_amount  = 0;
        $order_total = 0;
        $commissions = [];

        foreach ( $orders as $key => $order ) {
            $net_amount += $order->total_commission;

            $meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_orders_meta order_meta WHERE order_meta.order_commission_id = %d", $order->ID ) );

            foreach ( $meta_data as $data ) {
                $data->key === 'gross_sales_total' ? $order_total += $data->value : '';
            }

            $res_commission = [
                'type'             => 'percent',
                'fixed'            => '',
                'percentage'       => '',
                'item_id'          => $order->item_id,
                'admin_commission' => 0,
                'product_id'       => $order->product_id,
                'created'       => $order->created,
            ];

            $unit_commissin_rate_vendor = ( $order->commission_amount / $order->item_sub_total ) * 100;
            $unit_commissin_rate_admin  = 100 - $unit_commissin_rate_vendor;
            $new_admin_commissin        = ( $order->item_sub_total * $unit_commissin_rate_admin ) / 100;

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
     * @since 1.0.0
     *
     * @param \Wc_Order $child_order
     * @param integer $seller_id
     * @param boolean $from_suborder
     *
     * @return void
     */
    public function process_refund( $child_order, $seller_id, $from_suborder = true ) {
        global $wpdb;
        $refund_id = '';
        $status = 'completed';
        $refund_note = '';

        $vendor_id = 0;
        $order_id = 0;

        // On complete Commission table update
        $sql = 'SELECT * FROM ' . $wpdb->prefix . 'wcfm_marketplace_refund_request';
        $sql .= ' WHERE 1=1';
        $sql .= ' AND vendor_id = %d';
        $sql .= ' AND order_id = %d';
        $refund_infos = $wpdb->get_results( $wpdb->prepare( $sql, $seller_id, $this->order_id ) ); // phpcs:ignore

        if ( empty( $refund_infos ) ) {
            return;
        }

        foreach ( $refund_infos as $refund_info ) {
            $item_id           = absint( $refund_info->item_id );
            $vendor_id         = absint( $refund_info->vendor_id );
            $order_id          = absint( $refund_info->order_id );
            $commission_id     = absint( $refund_info->commission_id );
            $refunded_amount   = (float) $refund_info->refunded_amount;
            $c_refunded_amount = $refunded_amount;
            $c_refunded_qty    = absint( $this->wcfmmp_get_refund_meta( $refund_id, 'refunded_qty' ) );
            $refund_reason     = $refund_info->refund_reason;
            $is_partially_refunded = $refund_info->is_partially_refunded;
            $is_refunded = 0;

            // Create WC Refund Item
            if ( $order_id ) {
                $line_item = new \WC_Order_Item_Product( $item_id );

                $c_refunded_qty && $line_item->get_quantity() === $c_refunded_qty ? $is_partially_refunded = 0 : '';
                ! $is_partially_refunded ? $is_refunded = 1 : '';

                $order = $child_order;

                // API Refund Check
                $api_refund = $refund_info->refund_status === 'completed' ? true : false;

                $restock_refunded_items = 'true';
                $refund_tax = $this->wcfmmp_get_refund_meta( $refund_id, 'refunded_tax' );
                $refund_tax = maybe_unserialize( $refund_tax );

                $refund_tax_total = 0;

                if ( $is_refunded ) {
                    $product = $line_item->get_product();

                    if ( ! empty( $refund_tax ) && is_array( $refund_tax ) ) {
                        if ( isset( $refund_tax['total'] ) ) {
                            $refund_tax_total = $refund_tax['total'];
                        }
                        if ( ! empty( $refund_tax ) && is_array( $refund_tax ) ) {
                            foreach ( $refund_tax as $refund_tax_id => $refund_tax_price ) {
                                $refund_tax_total += (float) $refund_tax_price;
                            }
                        }
                    }

                    // Item Shipping Refund
                    $vendor_shipping = $this->get_order_vendor_shipping();

                    $shipping_tax  = 0;
                    $shipping_cost = $shipping_tax;

                    if ( ! empty( $vendor_shipping ) && isset( $vendor_shipping[ $vendor_id ] ) && $vendor_shipping[ $vendor_id ]['shipping_item_id'] ) {
                        $shipping_item_id = $vendor_shipping[ $vendor_id ]['shipping_item_id'];
                        $package_qty      = absint( $vendor_shipping[ $vendor_id ]['package_qty'] );

                        ! $package_qty ? $package_qty = $line_item->get_quantity() : '';

                        $shipping_item = new \WC_Order_Item_Shipping( $shipping_item_id );
                        $refund_shipping_tax = $shipping_item->get_taxes();
                        $shipping_tax_refund = array();

                        if ( ! empty( $refund_shipping_tax ) && is_array( $refund_shipping_tax ) ) {
                            if ( isset( $refund_shipping_tax['total'] ) ) {
                                $refund_shipping_tax = $refund_shipping_tax['total'];
                            }

                            foreach ( $refund_shipping_tax as $refund_shipping_tax_id => $refund_shipping_tax_price ) {
                                $refunded_amount += round( ( (float) $refund_shipping_tax_price / $package_qty ) * $line_item->get_quantity(), 2 );
                                $shipping_tax_refund[ $refund_shipping_tax_id ] = round( ( (float) $refund_shipping_tax_price / $package_qty ) * $line_item->get_quantity(), 2 );
                            }
                        }

                        $shipping_cost = (float) round( ( $vendor_shipping[ $vendor_id ]['shipping'] / $package_qty ) * $line_item->get_quantity(), 2 );
                        $refunded_amount += $shipping_cost;
                        $line_items[ $shipping_item_id ] = array(
                            'refund_total' => $shipping_cost,
                            'refund_tax'   => $shipping_tax_refund,
                        );
                    }
                }

                if ( ! empty( $refund_tax ) && is_array( $refund_tax ) ) {
                    foreach ( $refund_tax as $refund_tax_id => $refund_tax_price ) {
                        $refunded_amount += (float) $refund_tax_price;
                    }
                }

                try {
                    $line_items[ $item_id ] = array(
                        'refund_total' => $c_refunded_amount,
                        'refund_tax'   => $refund_tax,
                    );

                    $line_items[ $item_id ]['qty'] = $c_refunded_qty;

                    $wcfm_create_refund_args = array(
                        'amount'         => round( $refunded_amount, 2 ),
                        'reason'         => $refund_reason,
                        'order_id'       => $child_order->get_id(),
                        'line_items'     => $line_items,
                        'restock_items'  => $restock_refunded_items,
                    );

                    // Create the refund object.
                    $refund = '';
                    if ( $from_suborder ) {
                        $refund = wc_create_refund( $wcfm_create_refund_args );
                    }

                    if ( ( $from_suborder && ! is_wp_error( $refund ) || ! $from_suborder ) ) {
                        $refund_status = $refund_info->refund_status === 'completed' ? true : false;

                        $this->dokan_sync_refund_table(
                            $child_order->get_id(),
                            $seller_id,
                            round( $refunded_amount, 2 ),
                            $refund_reason,
                            $c_refunded_qty,
                            $c_refunded_amount,
                            $refund_tax_total,
                            $restock_refunded_items,
                            $refund_info->refund_paid_date,
                            $refund_status,
                            $this->order->get_payment_method()
                        );
                    }
                } catch ( \Exception $e ) {
                    error_log( print_r( $e->getMessage(), 1 ) );
                }
            }
        }
    }

    /**
     * Get wcfm refund meta data
     *
     * @since 1.0.0
     *
     * @param integer $refund_id
     * @param sting $key
     *
     * @return void
     */
    public function wcfmmp_get_refund_meta( $refund_id, $key ) {
global $wpdb;

$commission_meta = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT `value` FROM `{$wpdb->prefix}wcfm_marketplace_refund_request_meta`
                WHERE
                `refund_id` = %d
                    AND `key` = %s
                ",
        $refund_id,
        $key
    )
);

return $commission_meta;
	}

    /**
     * Rename vendor shipping for an order
     *
     * @since 1.0.0
     *
     * @param object $order
     *
     * @return array
     */
    public function get_order_vendor_shipping() {
        $shipping_items = $this->order->get_items( 'shipping' );

        foreach ( $shipping_items as $shipping_item_id => $shipping_item ) {
            $order_item_shipping = new \WC_Order_Item_Shipping( $shipping_item_id );
            $shipping_vendor_id  = $order_item_shipping->get_meta( 'vendor_id', true );

            ! $shipping_vendor_id ? $shipping_vendor_id = 0 : '';

            $refunded_shipping_amount = $this->order->get_total_refunded_for_item( $shipping_item_id, 'shipping' );
            $refunded_shipping_tax = 0;

            if ( $order_item_shipping->get_taxes() ) {
                $order_taxes = $this->order->get_taxes();
                foreach ( $order_taxes as $tax_item ) {
                    $tax_item_id = $tax_item->get_rate_id();
                    $refunded_shipping_tax += $this->order->get_tax_refunded_for_item( $shipping_item_id, $tax_item_id, 'shipping' );
                }
            }

            $vendor_shipping[ $shipping_vendor_id ] = array(
                'shipping'         => $order_item_shipping->get_total(),
                'shipping_tax'     => $order_item_shipping->get_total_tax(),
                'package_qty'      => $order_item_shipping->get_meta( 'package_qty', true ),
                'shipping_item_id' => $shipping_item_id,
                'refunded_amount'  => $refunded_shipping_amount,
                'refunded_tax'     => $refunded_shipping_tax,
            );
        }

        return $vendor_shipping;
    }
}
