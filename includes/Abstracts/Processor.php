<?php

namespace WeDevs\DokanMigrator\Abstracts;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * This class defines the methods for vendor, order and withdraw handler.
 *
 * @since DOKAN_MIG_SINCE
 */
abstract class Processor {

    /**
     * Returns count of items vendor or order or withdraw.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $plugin
     *
     * @return integer
     */
    abstract public static function get_total( $plugin );

    /**
     * Returns array of items vendor or order or withdraw.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $plugin
     *
     * @return array
     */
    abstract public static function get_items( $plugin, $number, $offset );

    /**
     * Return class to handle migration.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $plugin
     *
     * @return Class
     */
    abstract public static function get_migration_class( $plugin );
}
