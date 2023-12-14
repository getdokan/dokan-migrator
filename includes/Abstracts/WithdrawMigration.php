<?php

namespace WeDevs\DokanMigrator\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Withdraw abstract class.
 *
 * @since 1.0.0
 */
abstract class WithdrawMigration {

    /**
     * Sets single withdraw item data.
     *
     * @since 1.0.0
     *
     * @param object $withdraw_data
     */
    abstract public function set_withdraw_data( $withdraw_data );

    /**
     * Returns vendor id.
     *
     * @since 1.0.0
     *
     * @return int
     */
    abstract public function get_vendor_id();

    /**
     * Returns withdraw amount.
     *
     * @since 1.0.0
     *
     * @return int|float
     */
    abstract public function get_withdraw_amount();

    /**
     * Returns withdraw created date.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_withdraw_created_date();

    /**
     * Returns withdraw status.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_withdraw_status();

    /**
     * Returns withdraw payment method.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_withdraw_payment_method();

    /**
     * Returns withdraw note
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_withdraw_note();

    /**
     * Returns withdraw details.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_withdraw_details();

    /**
     * Returns withdraw ip.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_withdraw_ip();

    /**
     * Runs the withdraw migration process.
     *
     * @since 1.0.0
     *
     * @param Object $withdraw
     *
     * @return void
     */
    public function process_migration() {
        global $wpdb;

        $dokan_columns = [
            'user_id' => $this->get_vendor_id(),
            'amount'  => $this->get_withdraw_amount(),
            'date'    => $this->get_withdraw_created_date(),
            'status'  => $this->get_withdraw_status(),
            'method'  => $this->get_withdraw_payment_method(),
            'note'    => $this->get_withdraw_note(),
            'details' => $this->get_withdraw_details(),
            'ip'      => $this->get_withdraw_ip(),
        ];
        $dokan_columns_format = [
            '%d',
            '%f',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
        ];

        $wpdb->insert(
            $wpdb->prefix . 'dokan_withdraw',
            $dokan_columns,
            $dokan_columns_format
        );

        $lastid = $wpdb->insert_id;

        if ( absint( $this->get_withdraw_status() ) ) {
            $this->sync_dokan_vendor_balance_table( $this->get_vendor_id(), $lastid, $this->get_withdraw_amount(), $this->get_withdraw_created_date() );
        }
    }

    /**
     * Updates dokan vendor balance table for withdraw.
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
    public function sync_dokan_vendor_balance_table( $seller_id, $order_id, $amount, $created_date ) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'dokan_vendor_balance',
            array(
                'vendor_id'    => $seller_id,
                'trn_id'       => $order_id,
                'trn_type'     => 'dokan_withdraw',
                'perticulars'  => 'Approve withdraw request',
                'debit'        => 0,
                'credit'       => $amount,
                'status'       => 'approved',
                'trn_date'     => gmdate( 'Y-m-d h: i: s', strtotime( $created_date ) ),
                'balance_date' => gmdate( 'Y-m-d h:i:s', strtotime( $created_date ) ),
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
}
