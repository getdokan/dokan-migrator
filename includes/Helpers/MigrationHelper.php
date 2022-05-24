<?php

namespace Wedevs\DokanMigrator\Helpers;

/**
 * Dokan migrator helper class
 * This class holds the helper methods for dokan migration.
 *
 * @since 1.0.0
 */
class MigrationHelper {

    /**
     * Last migrated data, vendor/order/withdraw. and migratable plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function get_last_migrated() {
        $last_migrated     = get_option( 'dokan_migrator_last_migrated', 'vendor' );
        $migration_success = get_option( 'dokan_migration_success', false );
        $migratable        = self::get_migratable_plugin();

        wp_send_json_success(
            array(
                'last_migrated'     => $last_migrated,
                'migratable'        => $migratable,
                'migration_success' => $migration_success,
                'set_title'         => self::get_migration_title( $migratable ),
            )
        );
    }

    /**
     * Deactivate wcfm plugins.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function active_vendor_dashboard() {
        $all_plugins_to_deactivate = [];

        // Wcfm plugins
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-frontend-manager/wc_frontend_manager.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-multivendor-marketplace/wc-multivendor-marketplace.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-multivendor-membership/wc-multivendor-membership.php';

        // Yith multi vendor plugins
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/yith-frontend-manager-for-woocommerce-premium/init.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/yith-woocommerce-multi-vendor-premium/init.php';

        deactivate_plugins( $all_plugins_to_deactivate );

        self::reset_dokan_pages();

        delete_option( 'dokan_migration_completed' );

        wp_send_json_success( __( 'Dokan vendor dashboard activated.', 'dokan-migrator' ) );
    }

    /**
     * Remove and create some pages that can override dokan pages.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function reset_dokan_pages() {
        $args = [
            'name'   => __( 'Page Installation', 'dokan-migrator' ),
            'desc'   => __( 'Triggering fron dokan migrator', 'dokan-migrator' ),
            'button' => __( 'Install Dokan Pages', 'dokan-migrator' ),
            'action' => 'create_pages',
        ];

        $pages_to_delete = [ 'vendor_dashboard', 'vendors', 'shop_settings', 'dashboard', 'store-listing' ];

        foreach ( $pages_to_delete as $page ) {
            $page_obj = self::get_post_by_name( $page );

            $page_obj !== null ? wp_delete_post( $page_obj->ID ) : '';
        }

        self::create_dokan_pages();
    }

    /**
     * Create dokan pages.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function create_dokan_pages() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        $pages = array(
            array(
                'post_title' => __( 'Dashboard', 'dokan-migrator' ),
                'slug'       => 'dashboard',
                'page_id'    => 'dashboard',
                'content'    => '[dokan-dashboard]',
            ),
            array(
                'post_title' => __( 'Store List', 'dokan-migrator' ),
                'slug'       => 'store-listing',
                'page_id'    => 'store_listing',
                'content'    => '[dokan-stores]',
            ),
        );

        $dokan_pages = array();

        $old_pages = get_option( 'dokan_pages', [] );

        foreach ( $pages as $page ) {
            if ( in_array( $page['page_id'], array_keys( $old_pages ), true ) ) {
                $dokan_pages[ $page['page_id'] ] = $old_pages[ $page['page_id'] ];
                continue;
            }

            $page_id = wp_insert_post(
                array(
                    'post_title'     => $page['post_title'],
                    'post_name'      => $page['slug'],
                    'post_content'   => $page['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed',
                )
            );
            $dokan_pages[ $page['page_id'] ] = $page_id;
        }

        update_option( 'dokan_pages', $dokan_pages );
        flush_rewrite_rules();

        update_option( 'dokan_pages_created', 1 );
    }

    /**
     * Get post by post name
     *
     * @since 1.0.0
     *
     * @param string $name
     * @param string $post_type
     *
     * @return \Wp_Post
     */
    public static function get_post_by_name( $name, $post_type = 'page' ) {
        $query = new \WP_Query(
            array(
                'post_type' => $post_type,
                'name'      => $name,
            )
        );

        return $query->have_posts() ? reset( $query->posts ) : null;
    }

    /**
     * Get get migration title.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     *
     * @return string
     */
    public static function get_migration_title( $plugin ) {
        $title = __( 'Migrate to dokan', 'dokan-migrator' );

        switch ( $plugin ) {
            case 'wcfmmarketplace':
                $title = __( 'Migrate Wcfm To Dokan.', 'dokan-migrator' );
                break;

            case 'yithvendors':
                $title = __( 'Migrate YITH WooCommerce Multi Vendor To Dokan.', 'dokan-migrator' );
                break;

            default:
                break;
        }

        return $title;
    }

    /**
     * Returns migratable plugin name like: wcfm, wcvendors, wcmarketplace, .......
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_migratable_plugin() {
        $active_plugins = (array) get_option( 'active_plugins', array() );

        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }

        // WCfM Multivendor Marketplace Check
        $is_marketplace = ( in_array( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins, true ) || array_key_exists( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins ) || class_exists( 'WCFMmp' ) ) ? 'wcfmmarketplace' : false;

        // YITH multi vendor marketplace Check
        if ( ! $is_marketplace ) {
            $is_marketplace = ( in_array( 'yith-woocommerce-multi-vendor-premium/init.php', $active_plugins, true ) || array_key_exists( 'yith-woocommerce-multi-vendor-premium/init.php', $active_plugins ) || class_exists( 'YITH_Vendors' ) ) ? 'yithvendors' : false;
        }

        return $is_marketplace;
    }
}
