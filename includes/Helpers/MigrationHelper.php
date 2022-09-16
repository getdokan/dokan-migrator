<?php

namespace WeDevs\DokanMigrator\Helpers;

/**
 * Dokan migrator helper class
 * This class holds the helper methods for dokan migration.
 *
 * @since DOKAN_MIG_SINCE
 */
class MigrationHelper {

    /**
     * Last migrated data, vendor/order/withdraw. and migratable plugin.
     *
     * @since DOKAN_MIG_SINCE
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
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public static function active_vendor_dashboard() {
        $all_plugins_to_deactivate = [];

        // Wcfm plugins
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-frontend-manager/wc_frontend_manager.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-multivendor-marketplace/wc-multivendor-marketplace.php';
        $all_plugins_to_deactivate[] = WP_PLUGIN_DIR . '/wc-multivendor-membership/wc-multivendor-membership.php';

        deactivate_plugins( $all_plugins_to_deactivate );

        delete_option( 'dokan_migration_completed' );

        wp_send_json_success( __( 'Dokan vendor dashboard activated.', 'dokan-migrator' ) );
    }

    /**
     * Get post by post name
     *
     * @since DOKAN_MIG_SINCE
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
     * @since DOKAN_MIG_SINCE
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

            default:
                break;
        }

        return $title;
    }

    /**
     * Returns migratable plugin name like: wcfm, wcvendors, wcmarketplace, .......
     *
     * @since DOKAN_MIG_SINCE
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

        return $is_marketplace;
    }
}
