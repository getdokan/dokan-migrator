<?php

namespace WeDevs\DokanMigrator\Integrations\YithMultiVendor;

use WeDevs\DokanMigrator\Abstracts\OrderMigration;

/**
 * Order migration class.
 *
 * @since DOKAN_MIG_SINCE
 */
class OrderMigrator extends OrderMigration {

	/**
	 * Class constructor.
	 *
	 * @since DOKAN_MIG_SINCE
	 *
	 * @param \WP_Post $order
	 */
	public function __construct( \WP_Post $order ) {
		$this->order_id = $order->ID;
		$this->order    = wc_get_order( $this->order_id );
	}

    /**
     * Create sub order if needed
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param int $seller_id
     * @param array $seller_products
     *
     * @return \WC_Order
     */
    public function create_sub_order_if_needed( $seller_id, $seller_products, $parent_order_id ) {
        $args = array(
            'posts_per_page' => -1,
            'order'          => 'DESC',
            'post_parent'    => $parent_order_id,
            'post_type'      => 'shop_order',
        );

        $sub_orders = get_children( $args, ARRAY_A );

        $current_order = $parent_order_id;

        foreach ( $sub_orders as $id => $sub_order ) {
            if ( absint( $sub_order['post_author'] ) === $seller_id ) {
                $current_order = $id;
            }
        }

        $child_order = wc_get_order( $current_order );
        $parent_order = wc_get_order( $parent_order_id );

        $this->add_splited_shipping( $child_order, $parent_order );

        return $child_order;
    }

    /**
     * Spliting shipping amount and saving.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param \Wc_Order $child_order
     * @param \Wc_Order $parent_order
     *
     * @return void
     */
    public function add_splited_shipping( $order, $parent_order ) {
        $parent_order->get_shipping_methods();

        $shipping_methods = $parent_order->get_shipping_methods();
        $shipping_method  = '';
        if ( $shipping_methods ) {
            foreach ( $shipping_methods as $method_item_id => $shipping_object ) {
                $shipping_method = $shipping_object;
                    break;
            }
        }

        // bail out if no shipping methods found
        if ( ! $shipping_method ) {
            return;
        }

        if ( is_a( $shipping_method, 'WC_Order_Item_Shipping' ) ) {
            $item = new \WC_Order_Item_Shipping();
            $vendors = dokan_get_sellers_by( $parent_order->get_id() );
            $vendors = count( $vendors );

            $taxes = $shipping_method->get_taxes();
            $total = $shipping_method->get_total() / $vendors;

            foreach ( $taxes['total'] as $index => $tax ) {
                $taxes['total'][ $index ] = $tax / $vendors;
            }

            $item->set_props(
                array(
                    'method_title' => $shipping_method->get_name(),
                    'method_id'    => $shipping_method->get_method_id(),
                    'total'        => $total,
                    'taxes'        => $taxes,
                )
            );

            $metadata = $shipping_method->get_meta_data();

            if ( $metadata ) {
                foreach ( $metadata as $meta ) {
                    $item->add_meta_data( $meta->key, $meta->value );
                }
            }

            $order->add_item( $item );
            $order->set_shipping_total( $total );
            $order->calculate_totals();

            $order->save();
        }
    }

    /**
     * Delete sub orders of needed.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function reset_sub_orders_if_needed() {
        return '';
    }

    /**
     * Gets order data from wcfm order table for dokan.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param int $parent_order_id
     * @param int $seller_id
     *
     * @return array
     */
    public function get_dokan_order_data( $parent_order_id, $seller_id ) {
        global $wpdb;
        $wc_order = wc_get_order( $parent_order_id );

        $net_amount  = 0;
        $order_total = $wc_order->get_total();
        $commissions = [];
        $admin_commission = 0;

        if ( $wc_order->get_total_refunded() ) {
            $order_total = $order_total - $wc_order->get_total_refunded();
        }

        $args = array(
            'posts_per_page' => -1,
            'order'          => 'DESC',
            'post_parent'    => $parent_order_id,
            'post_type'      => 'shop_order',
        );

        $sub_orders = get_children( $args, ARRAY_A );

        foreach ( $sub_orders as $id => $sub_order ) {
            $orders = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}yith_vendors_commissions WHERE user_id = %d AND order_id=%d", $seller_id, $id ) );

            foreach ( $orders as $order ) {
                $net_amount += $order->amount - abs( $order->amount_refunded );

                $res_commission = [
                    'type'             => 'percent',
                    'fixed'            => '',
                    'percentage'       => '',
                    'item_id'          => '',
                    'admin_commission' => 0,
                    'product_id'       => $order->line_item_id,
                    'created'       => $order->last_edit_gmt,
                ];

                $unit_commissin_rate_admin  = 100 - ( $order->rate * 100 );
                $new_admin_commissin        = ( $wc_order->get_subtotal() * $unit_commissin_rate_admin ) / 100;

                $res_commission['percentage']       = $unit_commissin_rate_admin;
                $res_commission['admin_commission'] = $new_admin_commissin;

                array_push( $commissions, $res_commission );
            }
        }

        if ( count( $sub_orders ) === 1 ) {
            // update post type
            $sub_order = reset( $sub_orders );
            set_post_type( $sub_order['ID'], 'dep_yith_order' );
        }

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
     * @since DOKAN_MIG_SINCE
     *
     * @param \Wc_Order $child_order
     * @param integer $seller_id
     * @param boolean $from_suborder
     *
     * @return void
     */
    public function process_refund( $child_order, $seller_id, $from_suborder = true ) {
        global $wpdb;
        $order = wc_get_order( $child_order->get_id() );
        $new_total_amount = $order->get_total() - $order->get_total_refunded();

        // insert on dokan sync table

        $res = $wpdb->update(
            $wpdb->prefix . 'dokan_orders',
            array(
                'order_total'  => $new_total_amount,
            ),
            array(
                'order_id'     => $child_order->get_id(),
            ),
            array(
                '%f',
            )
        );
    }
}
