<?php

namespace Wedevs\DokanMigrator\Abstracts;

abstract class VendorMigration {

    /**
     * Vendor data.
     *
     * @since 1.0.0
     *
     * @var \WP_User
     */
    public $vendor = null;

    /**
     * Vendor id.
     *
     * @since 1.0.0
     *
     * @var integer
     */
    public $vendor_id = 0;

    /**
     * Vendor meta data.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $meta_data = array();

    /**
     * Returns store name
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_store_name();

    /**
     * Returns store description
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_store_biography();

    /**
     * Returns is vendor has selling capability.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_enable_selling();

    /**
     * Returns geo location address
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_geo_address();

    /**
     * Returns vendor location latitude.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_geo_lat();

    /**
     * Returns vendor location longitude.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_geo_long();

    /**
     * Returns applied commission in an vendor.
     *
     * @since 1.0.0
     *
     * @return array dokan_admin_percentage, dokan_admin_percentage_type, dokan_admin_additional_fee
     */
    abstract public function get_commission();

    /**
     * Returns vendor social data.
     *
     * @since 1.0.0
     *
     * @param array $default
     *
     * @return array
     */
    abstract public function get_social( $default );

    /**
     * Returns vendor payment array.
     *
     * @since 1.0.0
     *
     * @param array $default
     *
     * @return array
     */
    abstract public function get_payment( $default );

    /**
     * Returns vendor phone number.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_phone( $default );

    /**
     * Returns if email show in store or not.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_is_show_email( $default);

    /**
     * Returns  vendor address.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_address( $default );

    /**
     * Returns vendor location.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_location( $default );

    /**
     * Returns banner id.
     *
     * @since 1.0.0
     *
     * @param int $default
     *
     * @return int
     */
    abstract public function get_banner( $default );

    /**
     * Returns applied commission in an vendor.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return int
     */
    abstract public function get_icon( $default );

    /**
     * Returns vendor gravatar.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return int
     */
    abstract public function get_gravatar( $default );

    /**
     * Returns if show more p tab.
     *
     * @since 1.0.0
     *
     * @param mixed $default
     *
     * @return string
     */
    abstract public function get_show_more_ptab( $default );

    /**
     * Returns applied commission in an vendor.
     *
     * @since 1.0.0
     *
     * @param int $default
     *
     * @return int
     */
    abstract public function get_sore_ppp( $default );

    /**
     * Returns applied commission in an vendor.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_is_enabled_tnc( $default );

    /**
     * Returns terms and comdition.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_store_tnc( $default );

    /**
     * Returns if min discount.
     *
     * @since 1.0.0
     *
     * @param string $default
     *
     * @return string
     */
    abstract public function get_show_min_order_discount( $default );

    /**
     * Returns store seo.
     *
     * @since 1.0.0
     *
     * @param array $default
     *
     * @return array
     */
    abstract public function get_store_seo( $default );

    /**
     * Returns dokan vendor capability.
     *
     * @return string
     */
    public function get_capability() {
        return 'seller';
    }

    /**
     * Returns vendor is featured or not.
     *
     * @return string
     */
    public function get_featured() {
        return 'no';
    }

    /**
     * Returns vendor product publish permission.
     *
     * @return string
     */
    public function get_publishing() {
        return 'yes';
    }

    /**
     * Returns geo public.
     *
     * @return integer.
     */
    public function get_geo_public() {
        return 1;
    }

    /**
     * Dokan profile settings array.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_profile_settings() {
        $dokan_profile_settings = [
            'store_name'              => $this->get_store_name(),
            'vendor_biography'        => $this->get_store_biography(),
            'social'                  => $this->get_social( [] ),
            'payment'                 => $this->get_payment(
                array(
                    'paypal' => [ 'email' ],
                    'bank'   => [],
                )
            ),
            'phone'                   => $this->get_phone( '' ),
            'show_email'              => $this->get_is_show_email( 'no' ),
            'address'                 => $this->get_address( [] ),
            'location'                => $this->get_location( [] ),
            'banner'                  => $this->get_banner( '' ),
            'icon'                    => $this->get_icon( '' ),
            'gravatar'                => $this->get_gravatar( '' ),
            'show_more_ptab'          => $this->get_show_more_ptab( 'yes' ),
            'store_ppp'               => $this->get_sore_ppp( 10 ),
            'enable_tnc'              => $this->get_is_enabled_tnc( 'off' ),
            'store_tnc'               => wp_strip_all_tags( $this->get_store_tnc( '' ) ),
            'show_min_order_discount' => $this->get_show_min_order_discount( 'no' ),
            'store_seo'               => $this->get_store_seo( [] ),
            'dokan_store_time'        => [],
        ];

        return $dokan_profile_settings;
    }

    /**
     * Get specific user meta data.
     *
     * @since 1.0.0
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function get_val( $key, $default = '' ) {
        return isset( $this->meta_data[ $key ] ) ? reset( $this->meta_data[ $key ] ) : $default;
    }

    /**
     * Runs vendors migration process.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function process_migration( \WP_User $vendor ) {
        $this->vendor = $vendor;
        $this->meta_data = get_user_meta( $vendor->ID );
        $this->vendor_id = $vendor->ID;

        update_user_meta( $this->vendor_id, 'dokan_enable_selling', $this->get_enable_selling() );
        update_user_meta( $this->vendor_id, 'dokan_feature_seller', $this->get_featured() );
        update_user_meta( $this->vendor_id, 'dokan_publishing', $this->get_publishing() );
        update_user_meta( $this->vendor_id, 'dokan_profile_settings', $this->get_profile_settings() );
        update_user_meta( $this->vendor_id, 'dokan_store_name', $this->get_store_name() );
        update_user_meta( $this->vendor_id, 'dokan_geo_address', $this->get_geo_address() );
        update_user_meta( $this->vendor_id, 'dokan_geo_latitude', $this->get_geo_lat() );
        update_user_meta( $this->vendor_id, 'dokan_geo_longitude', $this->get_geo_long() );
        update_user_meta( $this->vendor_id, 'dokan_geo_public', $this->get_geo_public() );

        $vendor_commission = $this->get_commission();
        update_user_meta( $this->vendor_id, 'dokan_admin_percentage', $vendor_commission['dokan_admin_percentage'] );
        update_user_meta( $this->vendor_id, 'dokan_admin_percentage_type', $vendor_commission['dokan_admin_percentage_type'] );
        update_user_meta( $this->vendor_id, 'dokan_admin_additional_fee', $vendor_commission['dokan_admin_additional_fee'] );

        $this->vendor->add_role( $this->get_capability() );
    }
}
