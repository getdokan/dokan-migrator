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
    function __construct() {
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
        $this->verify_nonce( $_REQUEST );
        $import = ! empty( $_REQUEST['import'] ) ? sanitize_text_field( $_REQUEST['import'] ): 'vendor';
        $migratable = ! empty( $_REQUEST['migratable'] ) ? sanitize_text_field( $_REQUEST['migratable'] ): false;

        try {
            $data = dokan_migrator()->migrator->get_total( $import, $migratable );

            wp_send_json_success( array(
                'message' => __( 'Item count successfull.', 'dokan-migrator' ),
                'migrate' => $data,
            ) );
        } catch ( Exception $th) {
            wp_send_json_error( array(
                'message' => $th->getMessage(),
            ) );
        }
    }

    /**
     * Import the data of vendor or order.
     *
     * @return void
     */
    public function import() {
        $this->verify_nonce( $_REQUEST );

        $import         = ! empty( $_REQUEST['import'] ) ? sanitize_text_field( $_REQUEST['import'] )   : '';
        $number         = ! empty( $_REQUEST['number'] ) ? absint( $_REQUEST['number'] )                : 10;
        $offset         = ! empty( $_REQUEST['offset'] ) ? sanitize_text_field( $_REQUEST['offset'] )   : 0;
        $total_count    = ! empty( $_REQUEST['total_count'] ) ? absint( $_REQUEST['total_count'] )      : 0;
        $total_migrated = ! empty( $_REQUEST['total_migrated'] ) ? absint( $_REQUEST['total_migrated'] ): 0;
        $migratable     = ! empty( $_REQUEST['migratable'] ) ? sanitize_text_field( $_REQUEST['migratable'] ): false;

        try {
            $processed_data = dokan_migrator()->migrator->migrate( $import, $number, $offset, $total_count, $total_migrated, $migratable  );
            wp_send_json_success( array(
                'message' => __( 'Import successfull.', 'dokan-migrator' ),
                'process' => $processed_data,
            ) );
        } catch ( Exception $th) {
            wp_send_json_error( array(
                'message' => $th->getMessage(),
            ) );
        }
    }

    public function verify_nonce( $request ) {
        $nonce  = ! empty( $request['nonce'] ) ? sanitize_text_field( $request['nonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'dokan_migrator_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nonce verification failed!', 'dokan-migrator' ) ) );
        }
    }
}
