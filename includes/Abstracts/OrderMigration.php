<?php
namespace WeDevs\DokanMigrator\Abstracts;

// don't call the file directly
use stdClass;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstracts order migration class.
 *
 * @since 1.0.0
 */
abstract class OrderMigration {

    /**
     * Current order object instance.
     *
     * @var WC_Order
     */
    public $order = null;

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
     * @return WC_Order
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
     * Get seller by order/order id
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    abstract public function get_seller_by_order( $order_id );

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
     * @return stdClass|WC_Order[]
     */
    public function get_sub_orders() {
        return dokan()->order->get_child_orders( $this->order_id );
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
                $child->delete( true );
                $this->clear_dokan_vendor_balance_table( $child->get_id() );
                $this->clear_dokan_order_table( $child->get_id(), $child->get_user()->ID );
                $this->clear_dokan_refund_table( $child->get_id() );
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
        $order_total  = $order_obj->get_total();
        $net_amount   = $dokan_order_data['net_sale'];
        $created_date = ! empty( $dokan_order_data['commission_data'] ) ? reset( $dokan_order_data['commission_data'] )['created'] : dokan_current_datetime()->format( 'Y-m-d H:i:s' );

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
                'balance_date'  => dokan_current_datetime()->modify( $created_date )->format( 'Y-m-d H:i:s' ),
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
     * Runs the order migration process.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function process_migration() {
        $vendors = $this->get_seller_by_order( $this->order_id );

        $this->reset_sub_orders_if_needed();

        // Removing this action otherwise it will overwrite data id dokan_order table.
        remove_action( 'dokan_checkout_update_order_meta', 'dokan_sync_insert_order' );

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

        // Adding the hook again after updating dokan_order table for wcfm.
        add_action( 'dokan_checkout_update_order_meta', 'dokan_sync_insert_order' );
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
        if ( empty( $commission_data['commission_data'] ) ) {
            return;
        }

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
     * @param array $data
     *
     * @return void
     */
    public function dokan_sync_refund_table( $data ) {
        global $wpdb;

        $order_id        = $data['order_id'];
        $seller_id       = $data['seller_id'];
        $refund_amount   = $data['refund_amount'];
        $refund_reason   = $data['refund_reason'];
        $item_qtys       = $data['item_qtys'];
        $item_totals     = $data['item_totals'];
        $item_tax_totals = $data['item_tax_totals'];
        $restock_items   = $data['restock_items'];
        $date            = $data['date'];
        $status          = $data['status'];
        $method          = $data['method'];

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
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
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
     * @param WC_Order $order
     *
     * @return boolean
     */
    public function has_refunds( $order ) {
        return count( $order->get_refunds() ) > 0 ? true : false;
    }
}
