<?php

namespace Wedevs\DokanMigrator\Integrations\WcVendors;

! defined( 'ABSPATH' ) || exit;

use WeDevs\DokanMigrator\Abstracts\WithdrawMigration;

/**
 * Formats withdraw data for migration to Dokan.
 *
 * @since DOKAN_MIG_SINCE
 */
class WithdrawMigrator extends WithdrawMigration {

    /**
     * Current withdraw data.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @var object
     */
    private $withdraw = '';

    /**
     * Current withdraw metadata.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @var array
     */
    private $meta_data = '';

    /**
     * Current withdraw id.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @var int
     */
    private $withdraw_id = '';

    /**
     * Class constructor.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param object $withdraw
     */
    public function __construct( $withdraw ) {
        $this->set_withdraw_data( $withdraw );
    }

    /**
     * Sets single withdraw item data.
     *
     * @since DOKAN_MIG_SINCE
     */
    public function set_withdraw_data( $withdraw_data ) {
        $this->withdraw    = $withdraw_data;
        $this->withdraw_id = ! empty( $withdraw_data->ID ) ? $withdraw_data->ID : $withdraw_data->id;

        $this->meta_data = $this->get_withdraw_meta_data();
    }

    /**
     * Returns vendor id.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return int
     */
    public function get_vendor_id() {
        return ! empty( $this->withdraw->vendor_id ) ? $this->withdraw->vendor_id : '';
    }

    /**
     * Returns withdraw amount.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return int|float
     */
    public function get_withdraw_amount() {
        return $this->withdraw->total_due + $this->withdraw->total_shipping + $this->withdraw->tax;
    }

    /**
     * Returns withdraw created date.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_created_date() {
        return ! empty( $this->withdraw->time ) ? $this->withdraw->time : '';
    }

    /**
     * Returns withdraw status.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_status() {
        return 1;
    }

    /**
     * Returns withdraw payment method.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_payment_method() {
        return '';
    }

    /**
     * Returns withdraw note
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_note() {
        return 'Made by dokan migrator.';
    }

    /**
     * Returns withdraw details.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_details() {
        $order_ids          = ! empty( $this->withdraw->order_ids ) ? $this->withdraw->order_ids : '';
        $commission_ids     = ! empty( $this->withdraw->commission_ids ) ? $this->withdraw->commission_ids : '';
        $withdraw_charges   = ! empty( $this->withdraw->withdraw_charges ) ? $this->withdraw->withdraw_charges : '';
        $withdraw_mode      = ! empty( $this->withdraw->withdraw_mode ) ? $this->withdraw->withdraw_mode : '';
        $is_auto_withdrawal = ! empty( $this->withdraw->is_auto_withdrawal ) ? $this->withdraw->is_auto_withdrawal : '';
        $withdraw_paid_date = ! empty( $this->withdraw->time ) ? $this->withdraw->time : '';
        $vendor_id          = ! empty( $this->withdraw->vendor_id ) ? $this->withdraw->vendor_id : '';
        $product_id         = ! empty( $this->withdraw->product_id ) ? $this->withdraw->product_id : '';
        $qty                = ! empty( $this->withdraw->qty ) ? $this->withdraw->qty : '';

        $dokan_details                       = $this->meta_data;
        $dokan_details['email']              = get_userdata( $this->get_vendor_id() )->user_email;
        $dokan_details['order_ids']          = $order_ids;
        $dokan_details['commission_ids']     = $commission_ids;
        $dokan_details['withdraw_charges']   = $withdraw_charges;
        $dokan_details['withdraw_mode']      = $withdraw_mode;
        $dokan_details['is_auto_withdrawal'] = $is_auto_withdrawal;
        $dokan_details['withdraw_paid_date'] = $withdraw_paid_date;
        $dokan_details['vendor_id']          = $vendor_id;
        $dokan_details['product_id']         = $product_id;
        $dokan_details['qty']                = $qty;

        return maybe_serialize( $dokan_details );
    }

    /**
     * Returns withdraw user ip.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_ip() {
        return '';
    }

    /**
     * Gets the withdraw meta data.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return array
     */
    public function get_withdraw_meta_data() {
        return [];
    }
}
