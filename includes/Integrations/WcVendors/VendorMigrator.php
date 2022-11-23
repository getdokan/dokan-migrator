<?php

namespace Wedevs\DokanMigrator\Integrations\WcVendors;

! defined( 'ABSPATH' ) || exit;

use WP_User;
use WeDevs\DokanMigrator\Abstracts\VendorMigration;

/**
 * Formats vendor data for migration to Dokan.
 *
 * @since DOKAN_MIG_SINCE
 */
class VendorMigrator extends VendorMigration {

    /**
     * Class constructor
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param WP_User $vendor
     */
    public function __construct( WP_User $vendor ) {
        $this->vendor    = $vendor;
        $this->meta_data = get_user_meta( $vendor->ID );
        $this->vendor_id = $vendor->ID;
    }

    /**
     * Returns store name
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_store_name() {
        $store_name = $this->get_val( 'pv_shop_name' );

        if ( empty( $store_name ) ) {
            return $this->vendor->display_name;
        }

        return '';
    }

    /**
     * Returns store description
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_store_biography() {
        return $this->get_val( 'pv_seller_info' );
    }

    /**
     * Returns vendor selling capability.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_enable_selling() {
        if ( count( array_intersect( $this->vendor->roles, [ 'vendor', 'pending_vendor' ] ) ) ) {
            return 'yes';
        }

        return 'no';
    }

    /**
     * Returns geo location address.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_geo_address() {
        return $this->get_val( '_wcv_store_address1' );
    }

    /**
     * Returns vendor location latitude.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_geo_lat() {
        return $this->get_val( 'wcv_address_latitude' );
    }

    /**
     * Returns vendor location longitude.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return string
     */
    public function get_geo_long() {
        return $this->get_val( 'wcv_address_longitude' );
    }

    /**
     * Returns vendor social data.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return array
     */
    public function get_social( $default ) {
        $social = [
            'fb'        => $this->get_val( '_wcv_facebook_url' ),
            'twitter'   => $this->get_val( '_wcv_twitter_username' ),
            'pinterest' => $this->get_val( '_wcv_pinterest_url' ),
            'linkedin'  => $this->get_val( '_wcv_linkedin_url' ),
            'youtube'   => $this->get_val( '_wcv_youtube_url' ),
            'instagram' => $this->get_val( '_wcv_instagram_username' ),
            'flickr'    => '',
        ];
        return $social;
    }

    /**
     * Returns vendor payment data.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return array
     */
    public function get_payment( $default ) {
        return [
            'paypal' => [
                'email' => $this->get_val( 'pv_paypal' ),
            ],
            'bank'   => [
                'ac_name'        => $this->get_val( 'wcv_bank_account_name' ),
                'ac_number'      => $this->get_val( 'wcv_bank_account_number' ),
                'bank_name'      => $this->get_val( 'wcv_bank_name' ),
                'bank_addr'      => '',
                'routing_number' => $this->get_val( 'wcv_bank_routing_number' ),
                'iban'           => $this->get_val( 'wcv_bank_iban' ),
                'swift'          => $this->get_val( 'wcv_bank_bic_swift' ),
            ],
        ];
    }

    /**
     * Returns vendor phone number.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return string
     */
    public function get_phone( $default ) {
        return $this->get_val( '_wcv_store_phone' );
    }

    /**
     * Returns if show email in store.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return string
     */
    public function get_is_show_email( $default ) {
        return 'no';
    }

    /**
     * Returns vendor's address.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return array
     */
    public function get_address( $default ) {
        $address = [
            'street1' => $this->get_val( '_wcv_store_address1' ),
            'street2' => $this->get_val( '_wcv_store_address2' ),
            'city'    => $this->get_val( '_wcv_store_city' ),
            'zip'     => '',
            'country' => $this->get_val( '_wcv_store_country' ),
            'state'   => $this->get_val( '_wcv_store_state' ),
        ];

        return $address;
    }

    /**
     * Returns vendor location.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return string
     */
    public function get_location( $default ) {
        return '';
    }

    /**
     * Returns store banner id.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param int $default
     *
     * @return int
     */
    public function get_banner( $default ) {
        return $this->get_val( '_wcv_store_banner_id' );
    }


    /**
     * Returns vendor icon.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return int
     */
    public function get_icon( $default ) {
        return $this->get_val( '_wcv_store_icon_id' );
    }

    /**
     * Returns vendor gravatar.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return int
     */
    public function get_gravatar( $default ) {
        return '';
    }

    /**
     * Returns if show more p tab.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param mixed $default
     *
     * @return string
     */
    public function get_show_more_ptab( $default ) {
        return 'yes';
    }

    /**
     * Returns store product per page.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param int $default
     *
     * @return int
     */
    public function get_sore_ppp( $default ) {
        return get_option( 'wcvendors_products_per_page', 10 );
    }

    /**
     * Returns if terms and condition is enabled for store.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return string
     */
    public function get_is_enabled_tnc( $default ) {
        return 'off';
    }

    /**
     * Returns terms and conditions.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return string
     */
    public function get_store_tnc( $default ) {
        return wp_strip_all_tags( $this->get_val( 'wcv_policy_terms' ) );
    }

    /**
     * Returns min order discount.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $default
     *
     * @return string
     */
    public function get_show_min_order_discount( $default ) {
        return 'no';
    }

    /**
     * Returns store's seo.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param array $default
     *
     * @return array
     */
    public function get_store_seo( $default ) {
        $dokan_store_soe = [
            'dokan-seo-meta-title'    => $this->get_val( 'wcv_seo_title' ),
            'dokan-seo-meta-desc'     => $this->get_val( 'wcv_seo_meta_description' ),
            'dokan-seo-meta-keywords' => $this->get_val( 'wcv_seo_meta_keywords' ),
            'dokan-seo-og-title'      => '',
            'dokan-seo-og-desc'       => '',
            'dokan-seo-og-image'      => '',
            'dokan-seo-twitter-title' => $this->get_val( 'wcv_seo_twitter_title' ),
            'dokan-seo-twitter-desc'  => $this->get_val( 'wcv_seo_twitter_description' ),
            'dokan-seo-twitter-image' => $this->get_val( 'wcv_seo_twitter_image_id' ),
            'dokan-seo-fb-image'      => $this->get_val( 'wcv_seo_fb_image_id' ),
            'dokan-seo-fb-image'      => $this->get_val( 'wcv_seo_fb_image_id' ),
            'dokan-seo-fb-image'      => $this->get_val( 'wcv_seo_fb_image_id' ),
        ];

        return $dokan_store_soe;
    }

    /**
     * Returns commission for specific vendor.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function get_commission() {
        $dokan_commission = [
            'dokan_admin_percentage'      => '',
            'dokan_admin_percentage_type' => '',
            'dokan_admin_additional_fee'  => '',
        ];

        return $dokan_commission;
    }
}
