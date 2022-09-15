<?php

namespace WeDevs\DokanMigrator\Migrator;

use WeDevs\DokanMigrator\Ajax;

/**
 * Migrator class.
 * Migration process works in this class.
 *
 * @since 1.0.0
 */
class Manager {

    /**
     * Which import type is going to be migrated.
     *
     * @var string
     */
    private $import_type = 'vendor';

    /**
     * Get vendors starting from $offset( 1,2,3....,10,.......th ) vendor.
     *
     * @var integer
     */
    private $offset = 0;

    /**
     * Number of vendors to be migrated.
     *
     * @var integer
     */
    private $number = 10;

    /**
     * Total count of the data to be imported.
     *
     * @var integer
     */
    private $total_count = 0;

    /**
     * Total count of migrated data.
     *
     * @var integer
     */
    private $total_migrated = 0;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            new Ajax();
        }
    }

    /**
     * Sets import type.
     *
     * @since 1.0.0
     *
     * @param string $import_type
     *
     * @return void
     */
    protected function set_import_type( $import_type ) {
        $this->import_type = ! empty( $import_type ) ? $import_type : $this->import_type;
    }

    /**
     * Sets required data for execution.
     *
     * @since 1.0.0
     *
     * @param @param array{number:int,offset:int,total_count:int,total_migrated:int} $data
     *
     * @return void
     */
    protected function set_data( $data ) {
        $this->number         = ! empty( $data['number'] ) ? intval( $data['number'] ) : $this->number;
        $this->offset         = ! empty( $data['offset'] ) ? intval( $data['offset'] ) : $this->offset;
        $this->total_count    = ! empty( $data['total_count'] ) ? intval( $data['total_count'] ) : $this->total_count;
        $this->total_migrated = ! empty( $data['total_migrated'] ) ? intval( $data['total_migrated'] ) : $this->total_migrated;
    }

    /**
     * Get the total count of the data to be imported.
     *
     * @param string $import_type Import type- `vendor` or `order` or `withdraw`.
     *
     * @return void
     */
    public function get_total( $import_type, $migratable ) {
        $total_count = 0;

        $this->set_import_type( $import_type );

        $total_count         = call_user_func( [ $this->processor_class( $import_type ), 'get_total' ], $migratable );
        $old_migrated_status = self::get_migration_status( $import_type, $total_count );

        return [
            'total_count'         => $total_count,
            'old_migrated_status' => $old_migrated_status,
        ];
    }

    /**
     * Decide which import type is going to be migrated and run the migration process.
     *
     * @since 1.0.0
     *
     * @param string $import_type Type of the element being migrated. The values can be `vendor`, `order`, `withdraw`.
     * @param string $plugin Handle of the plugin which is being migrated
     * @param array{number:int,offset:int,total_count:int,total_migrated:int} $data
     *
     * @return void
     * @throws \Exception
     */
    public function migrate( $import_type, $plugin, $data ) {
        $this->set_import_type( $import_type );
        $this->set_data( $data );

        $processor = $this->processor_class( $import_type );

        $data = call_user_func( [ $processor, 'get_items' ], $plugin, $this->number, $this->offset );

        foreach ( $data as $value ) {
            dokan_migrator()::prevent_email_notification();
            $migrator = call_user_func( [ $processor, 'get_migration_class' ], $plugin );
            $migrator->process_migration( $value );
        }

        $args = [
            'migrated'       => count( $data ),
            'next'           => count( $data ) + $this->offset,
            'total_migrated' => count( $data ) + $this->total_migrated,
        ];

        $progress = ( $args['total_migrated'] * 100 ) / $this->total_count;

        if ( $progress < 100 ) {
            $this->update_migration_status( $args, $import_type );
        } else {
            delete_option( "dokan_migrator_{$import_type}_status" );
        }

        dokan_migrator()::reset_email_data();

        return $args;
    }

    /**
     * Retrives the class name of the migrator based on the import type.
     *
     * @since 1.0.0
     *
     * @param string $import_type
     *
     * @return string
     * @throws \Exception
     */
    public function processor_class( $import_type ) {
        if ( ! in_array( $import_type, [ 'vendor', 'order', 'withdraw' ], true ) ) {
            throw new \Exception( 'Invalid import type' );
        }

        $class = ucfirst( $import_type );
        return "\\WeDevs\\DokanMigrator\\Processors\\$class";
    }

    /**
     * Update successfully migration data into database option table based on vendor or order.
     *
     * @since 1.0.0
     *
     * @param array $data
     *
     * @return boolean True if success, false if not.
     */
    public static function update_migration_status( $data, $type = 'vendor' ) {
        $option_name = 'dokan_migrator_' . $type . '_status';
        update_option( 'dokan_migrator_last_migrated', $type );
        return update_option( $option_name, $data );
    }

    /**
     * Get the migration status of vendor or order.
     *
     * @since 1.0.0
     *
     * @return boolean
     */
    public static function get_migration_status( $type = 'vendor', $total_count = 0 ) {
        return get_option( 'dokan_migrator_' . $type . '_status' );
    }
}
