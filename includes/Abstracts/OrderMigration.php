<?php
namespace Wedevs\DokanMigrator\Abstracts;

/**
 * Abstracts order migration class.
 *
 * @since 1.0.0
 */
abstract class OrderMigration {

    /**
     * Current order object instance.
     *
     * @var \WC_Order
     */
    public $order = '';

    /**
     * Current order object instance.
     *
     * @var int
     */
    public $order_id = '';

    /**
     * Process refund for a child order.
     *
     * @since 1.0.0
     *
     * @param integer $child_order
     * @param integer $seller_id
     * @param boolean $from_suborder
     *
     * @return void
     */
    abstract public function process_refund( $child_order, $seller_id, $from_suborder = true );

    /**
     * Gets order data for dokan.
     *
     * @since 1.0.0
     *
     * @param int $parent_order_id
     * @param int $seller_id
     *
     * @return array
     */
    abstract public function get_dokan_order_data( $parent_order_id, $seller_id );

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
    abstract public function create_sub_order_if_needed( $seller_id, $seller_products, $parent_order_id );

    /**
     * Delete sub orders of needed.
     *
     * @since 1.0.0
     *
     * @return void
     */
    abstract public function reset_sub_orders_if_needed();

    /**
     * Returns true if the order has sub orders.
     *
     * @since 1.0.0
     *
     * @return boolean
     */
    public function has_sub_order() {
        return $this->order->get_meta( 'has_sub_order' );
    }

    /**
     * Returns parent orders child orders.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_sub_orders() {
        $args = array(
            'post_parent' => $this->order_id,
            'post_type'   => 'shop_order',
            'numberposts' => -1,
            'post_status' => 'any',
        );

        return get_children( $args );
    }

    /**
     * Removes all sub orders of a parent order.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function reset_sub_orders() {
        if ( $this->has_sub_order() ) {
            foreach ( $this->get_sub_orders() as $child ) {
                wp_delete_post( $child->ID, true );
                $this->clear_dokan_vendor_balance_table( $child->ID );
                $this->clear_dokan_order_table( $child->ID, $child->post_author );
                $this->clear_dokan_refund_table( $child->ID );
            }
        }
    }

    /**
     * Removes existing data from dokan order table.
     *
     * @since 1.0.0
     *
     * @param int $order_id
     * @param int $seller_id
     *
     * @return void
     */
    public function clear_dokan_order_table( $order_id, $seller_id ) {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'dokan_orders', array(
				'order_id'  => $order_id,
				'seller_id' => $seller_id,
            )
        );
    }

    /**
     * Removes existing data from dokan order table.
     *
     * @since 1.0.0
     *
     * @param int $order_id
     * @param int $seller_id
     *
     * @return void
     */
    public function clear_dokan_vendor_balance_table( $order_id ) {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'dokan_vendor_balance', array(
				'trn_id'   => $order_id,
				'trn_type' => 'dokan_orders',
            )
        );
    }

    /**
     * Removes existing data from dokan refund table.
     *
     * @since 1.0.0
     *
     * @param int $order_id
     * @param int $seller_id
     *
     * @return void
     */
    public function clear_dokan_refund_table( $order_id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'dokan_refund', array( 'order_id' => $order_id ) );
    }

    /**
     * Updates dokan order table with new order id.
     *
     * @since 1.0.0
     *
     * @param array $dokan_order_data
     * @param int $sub_order_id
     * @param int $seller_id
     * @param Object $order_obj
     *
     * @return void
     */
    public function sync_dokan_order_table( $dokan_order_data, $sub_order_id, $seller_id, $order_obj ) {
        $order_total = $order_obj->get_total();
        $net_amount = $dokan_order_data['net_sale'];
        $created_date = reset( $dokan_order_data['commission_data'] )['created'];

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'dokan_orders',
            array(
                'order_id'     => $sub_order_id,
                'seller_id'    => $seller_id,
                'order_total'  => $order_total,
                'net_amount'   => $net_amount,
                'order_status' => 'wc-' . $order_obj->get_status(),
            ),
            array(
                '%d',
                '%d',
                '%f',
                '%f',
                '%s',
            )
        );

        $wpdb->insert(
            $wpdb->prefix . 'dokan_vendor_balance',
            array(
                'vendor_id'     => $seller_id,
                'trn_id'        => $sub_order_id,
                'trn_type'      => 'dokan_orders',
                'perticulars'   => 'New order',
                'debit'         => $net_amount,
                'credit'        => 0,
                'status'        => 'wc-' . $order_obj->get_status(),
                'trn_date'      => $created_date,
                'balance_date'  => gmdate( 'Y-m-d h:i:s', strtotime( $created_date ) ),
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%s',
                '%f',
                '%f',
                '%s',
                '%s',
                '%s',
            )
        );
    }

    /**
     * Creates sub orders for each seller.
     *
     * @since 1.0.0
     *
     * @param int $seller_id
     * @param array $seller_products
     *
     * @return \WC_Order
     */
    public function create_sub_order( $seller_id, $seller_products ) {
        $bill_ship = array(
            'billing_country',
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_email',
            'billing_phone',
            'shipping_country',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_state',
            'shipping_postcode',
        );

        try {
            $sub_order = new \WC_Order();

            // save billing and shipping address
            foreach ( $bill_ship as $key ) {
                if ( is_callable( array( $sub_order, "set_{$key}" ) ) ) {
                    $sub_order->{"set_{$key}"}( $this->order->{"get_{$key}"}() );
                }
            }

            // now insert line items
            dokan()->order->create_line_items( $sub_order, $seller_products );

            // do shipping
            $this->create_shipping( $sub_order, $this->order );

            // do tax
            dokan()->order->create_taxes( $sub_order, $this->order, $seller_products );

            // add coupons if any
            // ! Coupon has dependency on Dokan-pro plugin so if it's not installed coupon code will be skipped.
            dokan()->order->create_coupons( $sub_order, $this->order, $seller_products );

            // save other details
            $sub_order->set_created_via( 'dokan' );
            $sub_order->set_cart_hash( $this->order->get_cart_hash() );
            $sub_order->set_customer_id( $this->order->get_customer_id() );
            $sub_order->set_currency( $this->order->get_currency() );
            $sub_order->set_prices_include_tax( $this->order->get_prices_include_tax() );
            $sub_order->set_customer_ip_address( $this->order->get_customer_ip_address() );
            $sub_order->set_customer_user_agent( $this->order->get_customer_user_agent() );
            $sub_order->set_customer_note( $this->order->get_customer_note() );
            $sub_order->set_payment_method( $this->order->get_payment_method() );
            $sub_order->set_payment_method_title( $this->order->get_payment_method_title() );
            $sub_order->update_meta_data( '_dokan_vendor_id', $seller_id );

            // finally, let the order re-calculate itself and save
            $sub_order->calculate_totals();

            $sub_order->set_status( $this->order->get_status() );
            $sub_order->set_parent_id( $this->order->get_id() );

            $order_id = $sub_order->save();

            // update total_sales count for sub-order
            wc_update_total_sales_counts( $order_id );

            return $sub_order;
        } catch ( \Exception $e ) {
            return new \WP_Error( 'dokan-suborder-error', $e->getMessage() );
        }
    }

    /**
     * Create shipping for a sub-order if neccessary
     *
     * @param \WC_Order $order
     * @param \WC_Order $parent_order
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_shipping( $order, $parent_order ) {
        // Get all shipping methods for parent order
        $shipping_methods = $parent_order->get_shipping_methods();
        $order_seller_id  = absint( dokan_get_seller_id_by_order( $order->get_id() ) );

        $applied_shipping_method = '';

        if ( $shipping_methods ) {
            foreach ( $shipping_methods as $method_item_id => $shipping_object ) {
                $shipping_seller_id = absint( wc_get_order_item_meta( $method_item_id, 'vendor_id', true ) );

                if ( $order_seller_id === $shipping_seller_id ) {
                    $applied_shipping_method = $shipping_object;
                    break;
                }
            }
        }

        $shipping_method = apply_filters( 'dokan_shipping_method', $applied_shipping_method, $order->get_id(), $parent_order );

        // bail out if no shipping methods found
        if ( ! $shipping_method ) {
            return;
        }

        if ( is_a( $shipping_method, 'WC_Order_Item_Shipping' ) ) {
            $item = new \WC_Order_Item_Shipping();

            $item->set_props(
                array(
					'method_title' => $shipping_method->get_name(),
					'method_id'    => $shipping_method->get_method_id(),
					'total'        => $shipping_method->get_total(),
					'taxes'        => $shipping_method->get_taxes(),
                )
            );

            $metadata = $shipping_method->get_meta_data();

            if ( $metadata ) {
                foreach ( $metadata as $meta ) {
                    $item->add_meta_data( $meta->key, $meta->value );
                }
            }

            $order->add_item( $item );
            $order->set_shipping_total( $shipping_method->get_total() );
            $order->save();
        }
    }

    /**
     * Runs the order migration process.
     *
     * @param \WP_Post $order
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function process_migration( \WP_Post $order ) {
        $this->order_id = $order->ID;
        $this->order    = wc_get_order( $order->ID );
        $vendors        = dokan_get_sellers_by( $this->order_id );

        $this->reset_sub_orders_if_needed();

        // If we've only ONE seller update the order meta or else create a new order for each seller.
        if ( count( $vendors ) === 1 ) {
            $temp      = array_keys( $vendors );
            $seller_id = reset( $temp );

            $this->order->update_meta_data( '_dokan_vendor_id', $seller_id );
            $this->order->save();

            $dokan_order_data = $this->get_dokan_order_data( $this->order_id, $seller_id );
            $this->update_commission_applied_data_in_order( $this->order, $dokan_order_data );

            $this->clear_dokan_order_table( $this->order_id, $seller_id );
            $this->clear_dokan_vendor_balance_table( $this->order_id );
            $this->clear_dokan_refund_table( $this->order_id );

            $this->sync_dokan_order_table( $dokan_order_data, $this->order_id, $seller_id, $this->order );
            $this->process_refund( $this->order, $seller_id, false );
        } else {
            // flag it as it has a suborder
            $this->order->update_meta_data( 'has_sub_order', true );
            $this->order->save();

            foreach ( $vendors as $seller_id => $seller_products ) {
                $dokan_order_data = $this->get_dokan_order_data( $this->order_id, $seller_id );
                $order = $this->create_sub_order_if_needed( $seller_id, $seller_products, $this->order_id );

                $this->update_commission_applied_data_in_order( $order, $dokan_order_data );

                $this->sync_dokan_order_table( $dokan_order_data, $order->get_id(), $seller_id, $order );
                $this->process_refund( $order, $seller_id );
            }
        }

        dokan_migrator()::reset_email_data();
    }

    /**
     * Update commission applied data in order item.
     *
     * @param \Wc_Order $order
     * @param array $commission_data
     *
     * @return void
     */
    public function update_commission_applied_data_in_order( $order, $commission_data ) {
        $commission = reset( $commission_data['commission_data'] );
        foreach ( $order->get_items() as $item_id => $item ) {
            wc_add_order_item_meta( $item_id, '_dokan_commission_rate', $commission['fixed'] );
            wc_add_order_item_meta( $item_id, '_dokan_commission_type', $commission['type'] );
            wc_add_order_item_meta( $item_id, '_dokan_additional_fee', $commission['percentage'] );
        }
    }

    /**
     * Updates dokan refund table.
     *
     * @since 1.0.0
     *
     * @param array $dokan_order_data
     * @param int $sub_order_id
     * @param int $seller_id
     * @param Object $order_obj
     *
     * @return void
     */
    public function dokan_sync_refund_table( $order_id, $seller_id, $refund_amount, $refund_reason, $item_qtys, $item_totals, $item_tax_totals, $restock_items, $date, $status, $method ) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'dokan_refund',
            array(
                'order_id'        => $order_id,
                'seller_id'       => $seller_id,
                'refund_amount'   => $refund_amount,
                'refund_reason'   => $refund_reason,
                'item_qtys'       => $item_qtys,
                'item_totals'     => $item_totals,
                'item_tax_totals' => $item_tax_totals,
                'restock_items'   => $restock_items,
                'date'            => $date,
                'status'          => $status,
                'method'          => $method,
            ),
            array(
                '%d',
                '%d',
                '%f',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
            )
        );

        // update the order table with new refund amount
        $order_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->dokan_orders WHERE order_id = %d",
                $order_id
            )
        );

        if ( isset( $order_data->order_total, $order_data->net_amount ) ) {
            $new_total_amount = $order_data->order_total - round( $refund_amount, 2 );

            $wpdb->update(
                $wpdb->dokan_orders,
                [
                    'order_total' => $new_total_amount,
                ],
                [
                    'order_id' => $order_id,
                ],
                [
                    '%f',
                ],
                [
                    '%d',
                ]
            );
        }
    }

    /**
     * Checks and returns if an order has refund.
     *
     * @since 1.0.0
     *
     * @param \WC_Order $order
     *
     * @return boolean
     */
    public function has_refunds( $order ) {
        return count( $order->get_refunds() ) > 0 ? true : false;
    }
}
