<?php

namespace Wedevs\DokanMigrator;

use Exception;
use Wedevs\DokanMigrator\Helpers\MigrationHelper;

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
        add_action( 'wp_ajax_dokan_migrator_last_migrated', array( MigrationHelper::class, 'get_last_migrated' ) );
        add_action( 'wp_ajax_dokan_migrator_active_vendor_dashboard', array( MigrationHelper::class, 'active_vendor_dashboard' ) );
    }

    /**
     * Count the data of vendor or order.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function count() {
        $this->verify_nonce( $_REQUEST ); // phpcs:ignore

        $request = wp_unslash( $_REQUEST ); // phpcs:ignore

        $import = ! empty( $request['import'] ) ? sanitize_text_field( $request['import'] ) : 'vendor';
        $migratable = ! empty( $request['migratable'] ) ? sanitize_text_field( $request['migratable'] ) : false;

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
        $this->verify_nonce( $_REQUEST ); // phpcs:ignore

        $request = wp_unslash( $_REQUEST ); // phpcs:ignore

        $import         = ! empty( $request['import'] ) ? sanitize_text_field( $request['import'] ) : '';
        $number         = ! empty( $request['number'] ) ? absint( $request['number'] ) : 10;
        $offset         = ! empty( $request['offset'] ) ? sanitize_text_field( $request['offset'] ) : 0;
        $total_count    = ! empty( $request['total_count'] ) ? absint( $request['total_count'] ) : 0;
        $total_migrated = ! empty( $request['total_migrated'] ) ? absint( $request['total_migrated'] ) : 0;
        $migratable     = ! empty( $request['migratable'] ) ? sanitize_text_field( $request['migratable'] ) : false;

        try {
            $processed_data = dokan_migrator()->migrator->migrate( $import, $number, $offset, $total_count, $total_migrated, $migratable );
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
     * @since 1.0.0
     *
     * @param array $request
     *
     * @return void
     */
    public function verify_nonce( $request ) {
        $nonce = ! empty( $request['nonce'] ) ? sanitize_text_field( $request['nonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'dokan_migrator_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nonce verification failed!', 'dokan-migrator' ) ) );
        }
    }
}
