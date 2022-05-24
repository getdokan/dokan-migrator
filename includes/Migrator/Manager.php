<?php

namespace Wedevs\DokanMigrator\Migrator;

use Wedevs\DokanMigrator\Handlers\OrderMigrationHandler;
use Wedevs\DokanMigrator\Handlers\VendorMigrationHandler;
use Wedevs\DokanMigrator\Handlers\WithdrawMigrationHandler;

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
    private $import = 'vendor';

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
     * Vendor handler class instance
     *
     * @var Class
     */
    private $vendor_handler = null;

    /**
     * Order handler class instance
     *
     * @var Class
     */
    private $order_handler = null;

    /**
     * Withdraw handler class instance
     *
     * @var Class
     */
    private $withdraw_handler = null;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->vendor_handler   = new VendorMigrationHandler();
        $this->order_handler    = new OrderMigrationHandler();
        $this->withdraw_handler = new WithdrawMigrationHandler();
    }

    /**
     * Get the total count of the data to be imported.
     *
     * @param string $import Import type vendor or order.
     *
     * @return void
     */
    public function get_total( $import = 'vendor', $migratable ) {
        $total_count = 0;

        switch ( $import ) {
            case 'vendor':
                $total_count = $this->vendor_handler->get_total( $migratable );
                break;

            case 'order':
                $total_count = $this->order_handler->get_total( $migratable );
                break;

            case 'withdraw':
                $total_count = $this->withdraw_handler->get_total( $migratable );
                break;
        }

        $old_migrated_status = self::get_migration_status( $import, $total_count );

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
     * @return void
     */
    public function migrate( $import, $number, $offset, $total_count, $total_migrated, $migratable ) {
        $this->import         = $import;
        $this->number         = $number;
        $this->offset         = $offset;
        $this->total_count    = $total_count;
        $this->total_migrated = $total_migrated;

        switch ( $import ) {
            case 'vendor':
                return $this->migrate_vendor( $migratable );

            case 'order':
                return $this->migrate_order( $migratable );

            case 'withdraw':
                return $this->migrate_withdraw( $migratable );

            default:
                throw new \Exception( 'Invalid import type' );
        }
    }

    /**
     * Migrate vendor data.
     *
     * @since 1.0.0
     *
     * @param string $migratable
     *
     * @return void
     */
    public function migrate_vendor( $migratable ) {
        $users = $this->vendor_handler->get_items( $migratable, $this->number, $this->offset );

        if ( ! empty( $users ) ) {
            foreach ( $users as $user ) {
                $vendor_migrator = $this->vendor_handler->get_migration_class( $migratable );
                $vendor_migrator->process_migration( $user );
            }

            $data = [
                'migrated'       => count( $users ),
                'next'           => count( $users ) + $this->offset,
                'total_migrated' => count( $users ) + $this->total_migrated,
            ];

            $progress = ( $data['total_migrated'] * 100 ) / $this->total_count;

            $progress >= 100 ? delete_option( 'dokan_migrator_' . $this->import . '_status' ) : $this->update_migration_status( $data, $this->import );

            return $data;
        } else {
            delete_option( 'dokan_migration_completed' );
            throw new \Exception( 'No vendors found to migrate to dokan.' );
        }
    }

    /**
     * Migrate order data.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function migrate_order( $migratable ) {
        $orders = $this->order_handler->get_items( $migratable, $this->number, $this->offset );

        if ( ! empty( $orders ) ) {
            foreach ( $orders as $order ) {
                $order_migrator = $this->order_handler->get_migration_class( $migratable );
                $order_migrator->process_migration( $order );
            }

            $data = [
                'migrated'       => count( $orders ),
                'next'           => count( $orders ) + $this->offset,
                'total_migrated' => count( $orders ) + $this->total_migrated,
            ];

            $progress = ( $data['total_migrated'] * 100 ) / $this->total_count;

            $progress >= 100 ? delete_option( 'dokan_migrator_' . $this->import . '_status' ) : $this->update_migration_status( $data, $this->import );

            return $data;
        } else {
            throw new \Exception( 'No orders found to migrate to dokan.' );
        }
    }

    /**
     * Migrate withdraw data.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function migrate_withdraw( $migratable ) {
        $withwraws = $this->withdraw_handler->get_items( $migratable, $this->number, $this->offset );

        0 === (int) $this->offset ? $this->remove_existing_withdraw_data() : '';

        if ( ! empty( $withwraws ) ) {
            foreach ( $withwraws as $withwraw ) {
                $withwraws_migrator = $this->withdraw_handler->get_migration_class( $migratable );

                $withwraws_migrator->set_withdraw_data( $withwraw );
                $withwraws_migrator->process_migration();
            }

            $data = [
                'migrated'       => count( $withwraws ),
                'next'           => count( $withwraws ) + $this->offset,
                'total_migrated' => count( $withwraws ) + $this->total_migrated,
            ];

            $progress = ( $data['total_migrated'] * 100 ) / $this->total_count;

            if ( $progress >= 100 ) {
                delete_option( 'dokan_migrator_' . $this->import . '_status' );
                delete_option( 'dokan_migrator_last_migrated' );
            } else {
                $this->update_migration_status( $data, $this->import );
            }
            update_option( 'dokan_migration_completed', 'yes' );

            return $data;
        } else {
            delete_option( 'dokan_migrator_last_migrated' );
            update_option( 'dokan_migration_completed', 'yes' );
            update_option( 'dokan_migration_success', 'yes' );
            throw new \Exception( 'No withdraws found to migrate to dokan.' );
        }
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

    /**
     * Remove old withdraw data from table.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function remove_existing_withdraw_data() {
        global $wpdb;

        $wpdb->query( "DELETE FROM {$wpdb->prefix}dokan_withdraw WHERE 1" );
    }
}
