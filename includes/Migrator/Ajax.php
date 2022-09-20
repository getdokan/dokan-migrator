<?php

namespace WeDevs\DokanMigrator\Migrator;

use Exception;
use WeDevs\DokanMigrator\Helpers\MigrationHelper;

/**
 * Ajax request handler class.
 *
 * @since DOKAN_MIG_SINCE
 */
class Ajax {

    /**
     * Class constructor.
     *
     * @since DOKAN_MIG_SINCE
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_migrator_count_data', array( $this, 'count' ) );
        add_action( 'wp_ajax_dokan_migrator_import_data', array( $this, 'import' ) );
        add_action( 'wp_ajax_dokan_migrator_last_migrated', array( $this, 'get_last_migrated' ) );
        add_action( 'wp_ajax_dokan_migrator_active_vendor_dashboard', array( MigrationHelper::class, 'active_vendor_dashboard' ) );
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
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function count() {
        $this->verify_nonce();

        $import     = ! empty( $_POST['import'] ) ? sanitize_text_field( wp_unslash( $_POST['import'] ) ) : 'vendor'; // phpcs:ignore WordPress.Security.NonceVerification
        $migratable = ! empty( $_POST['migratable'] ) ? boolval( wp_unslash( $_POST['migratable'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

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
     * Verify nonce.
     *
     * @since DOKAN_MIG_SINCE
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
