<?php

namespace WeDevs\DokanMigrator\Integrations\Wcfm;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WC_order;
use WC_Order_Item_Shipping;
use WeDevs\DokanMigrator\Abstracts\OrderMigration;
use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * Order migration class.
 *
 * @since 1.0.0
 */
class OrderMigrator extends OrderMigration {

    /**
     * Class constructor.
     *
     * @since DOKAN_PRO_SINCE
     *
     * @param \WP_Post|\stdClass $order
     */
    public function __construct( \WP_Post $order ) {
        $this->order_id = $order->ID;
        $this->order    = wc_get_order( $this->order_id );

        add_filter( 'dokan_shipping_method', [ $this, 'split_parent_order_shipping' ], 10, 3 );
    }

    /**
     * Create sub order if needed
     *
     * @since 1.0.0
     *
     * @param int $seller_id
     * @param array $seller_products
     *
     * @return WC_Order
     */
    public function create_sub_order_if_needed( $seller_id, $seller_products, $parent_order_id ) {
        /**
         * Before creating sub order, we need to convert the meta key according to
         * Dokan meta key for order items as those data will be parsed while creating
         * sub orders.
         */
        $this->map_shipping_method_item_meta();
        dokan()->order->create_sub_order( $this->order, $seller_id, $seller_products );

        $res = get_posts(
            array(
                'numberposts' => 1,
                'post_status' => 'any',
                'post_type'   => 'shop_order',
                'post_parent' => $this->order->get_id(),
                'meta_key'    => '_dokan_vendor_id', // phpcs:ignore WordPress.DB.SlowDBQuery
                'meta_value'  => $seller_id, // phpcs:ignore WordPress.DB.SlowDBQuery
            )
        );
        $created_suborder = reset( $res );

        return wc_get_order( $created_suborder->ID );
    }

    /**
     * Converts the order item meta key according to
     * Dokan meta key as those data will be parsed
     * while creating sub orders.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function map_shipping_method_item_meta() {
        if ( ! $this->order instanceof WC_Order ) {
            return;
        }

        $shipping_methods = $this->order->get_shipping_methods();
        if ( empty( $shipping_methods ) ) {
            return;
        }

        foreach ( $shipping_methods as $method_item_id => $shipping_object ) {
            $seller_id = wc_get_order_item_meta( $method_item_id, 'vendor_id', true );

            if ( ! $seller_id ) {
                continue;
            }

            wc_update_order_item_meta(
                $method_item_id,
                'seller_id',
                wc_get_order_item_meta( $method_item_id, 'vendor_id', true )
            );
        }
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
     * @param WC_Order $child_order
     * @param integer $seller_id
     * @param boolean $from_suborder
     *
     * @return void
     */
    public function process_refund( $child_order, $seller_id, $from_suborder = true ) {
        // On complete Commission table update
        $refund_infos = $this->get_refund_requests( $seller_id, $this->order_id );

        if ( empty( $refund_infos ) ) {
            return;
        }

        foreach ( $refund_infos as $refund_info ) {
            $item_id           = absint( $refund_info->item_id );
            $vendor_id         = absint( $refund_info->vendor_id );
            $order_id          = absint( $refund_info->order_id );
            $commission_id     = absint( $refund_info->commission_id );
            $refunded_amount   = (float) $refund_info->refunded_amount;
            $refund_id         = (float) $refund_info->ID;
            $c_refunded_amount = $refunded_amount;
            $c_refunded_qty    = absint( $this->get_refund_meta( $refund_id, 'refunded_qty' ) );
            $refund_reason     = $refund_info->refund_reason;
            $is_partially_refunded = $refund_info->is_partially_refunded;

            // Create WC Refund Item
            if ( $order_id ) {
                $line_item = new \WC_Order_Item_Product( $item_id );

                if ( $c_refunded_qty && $line_item->get_quantity() === $c_refunded_qty ) {
                    $is_partially_refunded = false;
                }
                $is_refunded = ! $is_partially_refunded;

                $order = $child_order;

                // API Refund Check
                $api_refund = $refund_info->refund_status === 'completed' ? true : false;

                $restock_refunded_items = 'true';
                $refund_tax = $this->get_refund_meta( $refund_id, 'refunded_tax' );
                $refund_tax = maybe_unserialize( $refund_tax );

                $refund_tax_total = 0;

                if ( $is_refunded ) {
                    $product = $line_item->get_product();

                    if ( ! empty( $refund_tax ) && is_array( $refund_tax ) ) {
                        if ( isset( $refund_tax['total'] ) ) {
                            $refund_tax_total = $refund_tax['total'];
                        } else {
                            foreach ( $refund_tax as $refund_tax_id => $refund_tax_price ) {
                                $refund_tax_total += (float) $refund_tax_price;
                            }
                        }
                    }

                    // Item Shipping Refund
                    $vendor_shipping = $this->get_order_vendor_shipping();

                    $shipping_tax  = 0;
                    $shipping_cost = $shipping_tax;

                    if ( ! empty( $vendor_shipping[ $vendor_id ]['shipping_item_id'] ) ) {
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
                                $refunded_amount += NumberUtil::round( ( (float) $refund_shipping_tax_price / $package_qty ) * $line_item->get_quantity(), 2 );
                                $shipping_tax_refund[ $refund_shipping_tax_id ] = NumberUtil::round( ( (float) $refund_shipping_tax_price / $package_qty ) * $line_item->get_quantity(), 2 );
                            }
                        }

                        $shipping_cost = (float) NumberUtil::round( ( $vendor_shipping[ $vendor_id ]['shipping'] / $package_qty ) * $line_item->get_quantity(), 2 );
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

                // Here we are creating refund and syncing with dokan_refund table.
                try {
                    $line_items[ $item_id ] = array(
                        'refund_total' => $c_refunded_amount,
                        'refund_tax'   => $refund_tax,
                    );

                    $line_items[ $item_id ]['qty'] = $c_refunded_qty;

                    $wcfm_create_refund_args = array(
                        'amount'         => NumberUtil::round( $refunded_amount, 2 ),
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

                        $item_totals    = wp_json_encode( [ $item_id => $c_refunded_amount ] );
                        $c_refunded_qty = wp_json_encode( [ $item_id => $c_refunded_qty ] );

                        $data = [
                            'order_id'        => $child_order->get_id(),
                            'seller_id'       => $seller_id,
                            'refund_amount'   => NumberUtil::round( $refunded_amount, 2 ),
                            'refund_reason'   => $refund_reason,
                            'item_qtys'       => $c_refunded_qty,
                            'item_totals'     => $item_totals,
                            'item_tax_totals' => $refund_tax_total,
                            'restock_items'   => $restock_refunded_items,
                            'date'            => $refund_info->refund_paid_date,
                            'status'          => $refund_status,
                            'method'          => $this->order->get_payment_method(),
                        ];
                        $this->dokan_sync_refund_table( $data );
                    }
                } catch ( \Exception $e ) {
                    error_log( print_r( $e->getMessage(), 1 ) );
                }
            }
        }
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

            $shipping_vendor_id = (int) $order_item_shipping->get_meta( 'vendor_id', true );

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

    /**
     * Retrieves WCFM refund requests.
     *
     * @since 1.0.0
     *
     * @param int $vendor_id
     * @param int $order_id
     *
     * @return object[]|null
     */
    public function get_refund_requests( $vendor_id, $order_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_refund_request
                WHERE vendor_id = %d
                AND order_id = %d",
                [ $vendor_id, $order_id ]
            )
        );
    }

    /**
     * Retrieves WCFM refund meta data for a specific meta key.
     *
     * @since 1.0.0
     *
     * @param integer $refund_id
     * @param sting   $meta_key
     *
     * @return mixed
     */
    public function get_refund_meta( $refund_id, $meta_key ) {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `value`
                FROM `{$wpdb->prefix}wcfm_marketplace_refund_request_meta`
                WHERE `refund_id` = %d
                AND `key` = %s",
                [ $refund_id, $meta_key ]
            )
        );
    }

    /**
     * Split shipping amount for all vendors if wcfm processing an order as admin shipping.
     *
     * @since 1.0.0
     *
     * @param WC_Order_Item_Shipping $applied_shipping_method
     * @param int                    $order_id
     * @param WC_Order               $parent_order
     *
     * @return WC_Order_Item_Shipping
     */
    public function split_parent_order_shipping( $applied_shipping_method, $order_id, $parent_order ) {
        /**
         * Not empty means parent order has sub-order and this order is processed as vendor wise shipping in wcfm.
         * Or if parent order has no shipping methods the return it.
         */
        if ( ! empty( $applied_shipping_method ) || count( $parent_order->get_shipping_methods() ) < 1 ) {
            return $applied_shipping_method;
        }

        $applied_shipping_method = reset( $parent_order->get_shipping_methods() );
        $vendors                 = dokan_get_sellers_by( $parent_order->get_id() );

        // Here we are dividing the shipping and shipping-tax amount of parent order into the vendors suborders.
        $shipping_tax_amount = [
            'total' => [ $applied_shipping_method->get_total_tax() / count( $vendors ) ],
        ];
        $shipping_amount = $applied_shipping_method->get_total() / count( $vendors );

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
