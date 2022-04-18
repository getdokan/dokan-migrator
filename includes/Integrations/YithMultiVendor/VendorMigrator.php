<?php

namespace Wedevs\DokanMigrator\Integrations\YithMultiVendor;

use Wedevs\DokanMigrator\Abstracts\VendorMigration;

/**
 * Formats vendor data for migration to Dokan.
 *
 * @since 1.0.0
 */
class VendorMigrator extends VendorMigration {

    /**
     * Returns vendor data from term, term taxonomy and term meta table.
     *
     * @since 1.0.0
     *
     * @param string $key
     *
     * @return string
     */
    public function get_vendor_data_from_terms_table( $key ) {
        return get_term_meta( $this->get_val( 'yith_product_vendor_owner' ), $key, true );
    }

    /**
     * Returns store name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_store_name() {
        $store_name = $this->get_val( 'nickname' );
        empty( $store_name ) ? $store_name = $this->vendor->user_nicename : '';
        return $store_name;
    }

    /**
     * Returns store description
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_store_biography() {
        return term_description( $this->get_val( 'yith_product_vendor_owner' ) );
    }

    /**
     * Returns is vendor has selling capability.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_enable_selling() {
        return $this->get_vendor_data_from_terms_table('enable_selling');
    }

    /**
     * Returns geo location address
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_geo_address() {
        return $this->get_vendor_data_from_terms_table('location');
    }

    /**
     * Returns vendor location latitude.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_geo_lat() {
        return '';
    }

    /**
     * Returns vendor location longitude.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_geo_long() {
        return '';
    }

    /**
     * Returns vendor social data.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_social($default) {
        return $this->get_vendor_data_from_terms_table('socials');
    }

    /**
     * Returns vendor payment data.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_payment($default) {
        return [
            'paypal' => [
                'email' => $this->get_vendor_data_from_terms_table('paypal_email')
            ],
            'bank'   => [
                'ac_name'        => '',
                'ac_number'      => '',
                'bank_name'      => '',
                'bank_addr'      => '',
                'routing_number' => '',
                'iban'           => $this->get_vendor_data_from_terms_table('bank_account'),
                'swift'          => '',
            ],
        ];
    }

    /**
     * Returns vendor phone number.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    public function get_phone($default) {
        return $this->get_vendor_data_from_terms_table('telephone');
    }

    /**
     * Returns if email show in store or not.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    public function get_is_show_email($default) {
        return 'no';
    }

    /**
     * Returns  vendor address.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return array
     */
    public function get_address($default) {
        $address = [
            'street1' => $this->get_vendor_data_from_terms_table('location'),
            'street2' => '',
            'city'    => '',
            'zip'     => '',
            'country' => '',
            'state'   => '',
        ];

        return $address;
    }

    /**
     * Returns vendor location.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    public function get_location($default) {
        return [];
    }

    /**
     * Returns banner id.
     *
     * @since 1.0.0
     *
     * @param int $default
     *
     * @return int
     */
    public function get_banner($default) {
        return $this->get_vendor_data_from_terms_table('header_image');
    }


    /**
     * Returns applied commission in an vendor.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return int
     */
    public function get_icon($default) {
        return '';
    }

    /**
     * Returns vendor gravatar.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return int
     */
    public function get_gravatar($default) {
        return $this->get_vendor_data_from_terms_table('avatar');
    }

    /**
     * Returns if show more p tab.
     *
     * @since 1.0.0
     *
     * @param mixed $default
     *
     * @return string
     */
    public function get_show_more_ptab($default) {
        return 'yes';
    }

    /**
     * Returns store product per page.
     *
     * @since 1.0.0
     *
     * @param int $default
     *
     * @return int
     */
    public function get_sore_ppp($default) {
        return 10;
    }

    /**
     * Returns applied commission in an vendor.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    public function get_is_enabled_tnc($default) {
        return 'off';
    }

    /**
     * Returns terms and comdition.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    public function get_store_tnc($default) {
        return wp_strip_all_tags( $this->get_vendor_data_from_terms_table('avatar') );
    }

    /**
     * Returns if min discount.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    public function get_show_min_order_discount($default) {
        return 'no';
    }

    /**
     * Returns store seo.
     *
     * @since 1.0.0
     *
     * @param array $default
     *
     * @return array
     */
    public function get_store_seo($default) {
        $dokan_store_soe = [
            'dokan-seo-meta-title'    => '',
            'dokan-seo-meta-desc'     => '',
            'dokan-seo-meta-keywords' => '',
            'dokan-seo-og-title'      => '',
            'dokan-seo-og-desc'       => '',
            'dokan-seo-og-image'      => '',
            'dokan-seo-twitter-title' => '',
            'dokan-seo-twitter-desc'  => '',
            'dokan-seo-twitter-image' => '',
            'dokan-seo-fb-image'      => '',
            'dokan-seo-fb-image'      => '',
            'dokan-seo-fb-image'      => '',
        ];

        return $dokan_store_soe;
    }

    /**
     * Returns commission for specific vendor.
     *
     * @return void
     */
    public function get_commission() {
        $dokan_commission = [
            'dokan_admin_percentage'      => $this->get_vendor_data_from_terms_table('commission'),
            'dokan_admin_percentage_type' => 'percentage',
            'dokan_admin_additional_fee'  =>  $this->get_vendor_data_from_terms_table('commission'),
        ];

        return $dokan_commission;
    }
}