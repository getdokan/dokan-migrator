<?php

namespace Wedevs\DokanMigrator\Abstracts;

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
     * @param string $plugin
     *
     * @return array
     */
    abstract public static function get_items( $plugin, $number, $offset );

    /**
     * Return class to handle migration.
     *
     * @since 1.0.0
     *
     * @param string $plugin
     *
     * @return Class
     */
    abstract public static function get_migration_class( $plugin );
}
