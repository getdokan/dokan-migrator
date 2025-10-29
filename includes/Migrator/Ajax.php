<?php

namespace WeDevs\DokanMigrator\Migrator;

defined( 'ABSPATH' ) || exit;

use Exception;
use WeDevs\DokanMigrator\Helpers\MigrationHelper;

/**
 * Ajax request handler class.
 *
 * @since 1.0.0
 */
class Ajax {

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_migrator_count_data', array( $this, 'count' ) );
        add_action( 'wp_ajax_dokan_migrator_import_data', array( $this, 'import' ) );
        add_action( 'wp_ajax_dokan_migrator_last_migrated', array( $this, 'get_last_migrated' ) );
        add_action( 'wp_ajax_dokan_migrator_active_vendor_dashboard', array( MigrationHelper::class, 'active_vendor_dashboard' ) );
        // Handle UI request to reset and restart migration
        add_action( 'wp_ajax_reset_and_restart_migration', array( $this, 'reset_and_restart_migration' ) );
        // Save selected steps preference
        add_action( 'wp_ajax_dokan_migrator_set_selected_steps', array( $this, 'set_selected_steps' ) );
        // Persist overall completion so success survives reload
        add_action( 'wp_ajax_dokan_migrator_mark_completed', array( $this, 'mark_completed' ) );
    }

    /**
     * Returns
     *
     * @return void
     */
    public function get_last_migrated() {
        wp_send_json_success( MigrationHelper::get_last_migrated() );
    }

    /**
     * Count the data of vendor or order.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function count() {
        $this->verify_nonce();

        $import     = ! empty( $_POST['import'] ) ? sanitize_text_field( wp_unslash( $_POST['import'] ) ) : 'vendor'; // phpcs:ignore WordPress.Security.NonceVerification
        $migratable = ! empty( $_POST['migratable'] ) ? sanitize_text_field( wp_unslash( $_POST['migratable'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

        try {
            $data = dokan_migrator()->migrator->get_total( $import, $migratable );

            wp_send_json_success(
                array(
                    'message' => __( 'Item count successfull.', 'dokan-migrator' ),
                    'migrate' => $data,
                )
            );
        } catch ( Exception $th ) {
            wp_send_json_error(
                array(
                    'message' => $th->getMessage(),
                )
            );
        }
    }

    /**
     * Import the data of vendor or order.
     *
     * @return void
     */
    public function import() {
        $this->verify_nonce();

        $import     = ! empty( $_REQUEST['import'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['import'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $migratable = ! empty( $_REQUEST['migratable'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['migratable'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $args       = [
            'number'         => ! empty( $_REQUEST['number'] ) ? absint( $_REQUEST['number'] ) : 10, // phpcs:ignore WordPress.Security.NonceVerification
            'offset'         => ! empty( $_REQUEST['offset'] ) ? absint( $_REQUEST['offset'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
            'paged'          => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
            'total_count'    => ! empty( $_REQUEST['total_count'] ) ? absint( $_REQUEST['total_count'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
            'total_migrated' => ! empty( $_REQUEST['total_migrated'] ) ? absint( $_REQUEST['total_migrated'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
        ];

        try {
            $processed_data = dokan_migrator()->migrator->migrate( $import, $migratable, $args );
            wp_send_json_success(
                array(
                    'message' => __( 'Import successfull.', 'dokan-migrator' ),
                    'process' => $processed_data,
                )
            );
        } catch ( Exception $th ) {
            wp_send_json_error(
                array(
                    'message' => $th->getMessage(),
                )
            );
        }
    }

    /**
     * Handle reset and restart migration request.
     * Deletes all plugin options related to migration state.
     *
     * @since 1.1.3
     *
     * @return void
     */
    public function reset_and_restart_migration() {
        $this->verify_nonce();

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'dokan-migrator' ) ] );
        }

        // List of options saved by this plugin that affect migration state.
        $options = [
            'dokan_migrator_last_migrated',
            'dokan_migrator_vendor_status',
            'dokan_migrator_order_status',
            'dokan_migrator_withdraw_status',
            'dokan_migration_completed',
            'dokan_migration_success',
            'dokan_migrator_selected_steps',
        ];

        $deleted = [];
        foreach ( $options as $opt ) {
            $deleted[$opt] = delete_option( $opt );
        }

        // Delete all plugin options related to migrator state, including selected steps as per latest requirement.

        // Also clear installer metadata to fully reset plugin-saved options as per instruction.
        $meta_options = [
            'dokan_migrator_installed_time',
            'dokan_migrator_plugin_version',
        ];
        foreach ( $meta_options as $opt ) {
            delete_option( $opt );
        }

        wp_send_json_success(
            [
                'message' => __( 'Migration has been reset. You can re-run the migration now.', 'dokan-migrator' ),
                'deleted' => $deleted,
            ]
        );
    }

    /**
     * Save user-selected migration steps in an option.
     *
     * @since 1.1.3
     *
     * @return void
     */
    public function set_selected_steps() {
        $this->verify_nonce();

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'dokan-migrator' ) ] );
        }

        $raw = ! empty( $_POST['steps'] ) ? wp_unslash( $_POST['steps'] ) : '';
        if ( is_string( $raw ) ) {
            $decoded = json_decode( $raw, true );
        } else {
            $decoded = $raw; // Allow array form if sent that way.
        }

        $defaults = [ 'vendor' => true, 'order' => true, 'withdraw' => true ];
        $steps    = is_array( $decoded ) ? array_merge( $defaults, $decoded ) : $defaults;

        // Sanitize to booleans only for known keys
        $clean = [];
        foreach ( $defaults as $key => $def ) {
            $val        = isset( $steps[ $key ] ) ? $steps[ $key ] : $def;
            $clean[$key] = (bool) filter_var( $val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
            if ( is_null( $clean[$key] ) ) {
                $clean[$key] = (bool) $val;
            }
        }

        update_option( 'dokan_migrator_selected_steps', $clean );

        wp_send_json_success( [ 'message' => __( 'Selected steps saved.', 'dokan-migrator' ), 'selected_steps' => $clean ] );
    }

    /**
     * Mark overall migration as completed so success persists across reloads.
     *
     * @since 1.1.4
     *
     * @return void
     */
    public function mark_completed() {
        $this->verify_nonce();

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'dokan-migrator' ) ] );
        }

        $final_step = ! empty( $_POST['final_step'] ) ? sanitize_text_field( wp_unslash( $_POST['final_step'] ) ) : '';
        if ( in_array( $final_step, [ 'vendor', 'order', 'withdraw' ], true ) ) {
            update_option( 'dokan_migrator_last_migrated', $final_step );
        }

        update_option( 'dokan_migration_success', 'yes' );

        // Clear any in-progress status options to avoid auto-resume flags lingering
        delete_option( 'dokan_migrator_vendor_status' );
        delete_option( 'dokan_migrator_order_status' );
        delete_option( 'dokan_migrator_withdraw_status' );

        wp_send_json_success( [ 'message' => __( 'Migration marked as completed.', 'dokan-migrator' ) ] );
    }

    /**
     * Verify nonce.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function verify_nonce() {
        $nonce = ! empty( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'dokan_migrator_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nonce verification failed!', 'dokan-migrator' ) ) );
        }
    }
}
