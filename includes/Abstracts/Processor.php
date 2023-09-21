<?php

namespace WeDevs\DokanMigrator\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * This class defines the methods for vendor, order and withdraw handler.
 *
 * @since 1.0.0
 */
abstract class Processor {

    /**
     * Returns count of items vendor or order or withdraw.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     *
     * @return integer
     */
    abstract public static function get_total( $plugin );

    /**
     * Returns array of items vendor or order or withdraw.
     *
     * @since 1.0.0
     *
     * @param string  $plugin
     * @param integer $number
     * @param integer $offset
     * @param integer $paged
     *
     * @return array
     */
    abstract public static function get_items( $plugin, $number, $offset, $paged );

    /**
     * Return class to handle migration.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     * @param object $payload
     *
     * @return object
     */
    abstract public static function get_migration_class( $plugin, $payload );
}
