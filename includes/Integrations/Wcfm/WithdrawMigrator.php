<?php

namespace WeDevs\DokanMigrator\Integrations\Wcfm;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WeDevs\DokanMigrator\Abstracts\WithdrawMigration;

/**
 * Formats vendor data for migration to Dokan.
 *
 * @since DOKAN_MIG_SINCE
 */
class WithdrawMigrator extends WithdrawMigration {

    /**
     * Current withdraw data.
     *
     * @var object
     */
    private $withdraw = '';

    /**
     * Current withdraw metadata.
     *
     * @var array
     */
    private $meta_data = '';

    /**
     * Current withdraw id.
     *
     * @var int
     */
    private $withdraw_id = '';

    /**
     * Class constructor.
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
        $this->withdraw = $withdraw_data;
        $this->withdraw_id = $withdraw_data->ID;

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
        return ! empty( $this->withdraw->withdraw_amount ) ? $this->withdraw->withdraw_amount : '';
    }

    /**
     * Returns withdraw created date.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_created_date() {
        return ! empty( $this->withdraw->created ) ? $this->withdraw->created : '';
    }

    /**
     * Returns withdraw status.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_status() {
        $dokan_withdraw_status = array(
            'completed' => 1,
            'requested' => 0,
            'cancelled' => 2,
        );
        $withdraw_status = ! empty( $this->withdraw->withdraw_status ) ? $this->withdraw->withdraw_status : '';

        return $dokan_withdraw_status[ $withdraw_status ];
    }

    /**
     * Returns withdraw payment method.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_payment_method() {
        return ! empty( $this->withdraw->payment_method ) ? $this->withdraw->payment_method : '';
    }

    /**
     * Returns withdraw note
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_withdraw_note() {
        return ! empty( $this->withdraw->withdraw_note ) ? $this->withdraw->withdraw_note : '';
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
        $withdraw_paid_date = ! empty( $this->withdraw->withdraw_paid_date ) ? $this->withdraw->withdraw_paid_date : '';

        $dokan_details                       = $this->meta_data;
        $dokan_details['email']              = get_userdata( $this->get_vendor_id() )->user_email;
        $dokan_details['order_ids']          = $order_ids;
        $dokan_details['commission_ids']     = $commission_ids;
        $dokan_details['withdraw_charges']   = $withdraw_charges;
        $dokan_details['withdraw_mode']      = $withdraw_mode;
        $dokan_details['is_auto_withdrawal'] = $is_auto_withdrawal;
        $dokan_details['withdraw_paid_date'] = $withdraw_paid_date;

        return maybe_serialize( $dokan_details );
    }

    /**
     * Returns withdraw ip.
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
        global $wpdb;

        $meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_withdraw_request_meta WHERE withdraw_id = %d", $this->withdraw_id ) );

        $result = [];

        foreach ( $meta_data as $key => $value ) {
            $result[ $value->key ] = $value->value;
        }

        return $result;
    }
}
