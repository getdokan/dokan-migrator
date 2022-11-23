<?php
/**
 * Plugin Name: Dokan Migrator
 * Plugin URI: http://WeDevs.com/
 * Description: An e-commerce marketplace migration plugin for WordPress. Powered by WooCommerce and WeDevs.
 * Version: 1.0.0
 * Author: WeDevs
 * Author URI: https://WeDevs.com/
 * Domain Path: /languages/
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 3.0
 * WC tested up to: 6.2.0
 * Text Domain: dokan-migrator
 *
 * Copyright (c) 2022 WeDevs (email: info@WeDevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

! defined( 'ABSPATH' ) || exit;

/**
 * Dokan_Migrator class
 *
 * @class Dokan_Migrator The class that holds the entire Dokan_Migrator plugin
 *
 * @since DOKAN_MIG_SINCE
 */
final class Dokan_Migrator {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Instance of self
     *
     * @var Dokan_Migrator
     */
    private static $instance = null;

    /**
     * Holds various class instances
     *
     * @since DOKAN_MIG_SINCE
     *
     * @var array
     */
    private $container = [];

    /**
     * Class constructor.
     *
     * @since DOKAN_MIG_SINCE
     */
    private function __construct() {
        require_once __DIR__ . '/vendor/autoload.php';

        // Define constants.
        $this->define_constants();

        register_activation_hook( __FILE__, [ $this, 'activate' ] );

        // load the addon
        add_action( 'dokan_loaded', array( $this, 'plugin_init' ) );
    }

    /**
     * Initializes the class
     *
     * Checks for an existing instance
     * and if it doesn't find one, creates it.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return self
     */
    public static function init() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Define all dokan migrator constant
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function define_constants() {
        define( 'DOKAN_MIGRATOR_PLUGIN_VERSION', $this->version );
        define( 'DOKAN_MIGRATOR_FILE', __FILE__ );
        define( 'DOKAN_MIGRATOR_DIR', dirname( DOKAN_MIGRATOR_FILE ) );
        define( 'DOKAN_MIGRATOR_FILE_PATH', plugin_dir_path( DOKAN_MIGRATOR_FILE ) );
        define( 'DOKAN_MIGRATOR_TEMPLATE_PATH', DOKAN_MIGRATOR_FILE_PATH . 'templates/' );
        define( 'DOKAN_MIGRATOR_INC', DOKAN_MIGRATOR_DIR . '/includes' );
        define( 'DOKAN_MIGRATOR_PLUGIN_ASSETS_DRI', DOKAN_MIGRATOR_DIR . '/assets' );
        define( 'DOKAN_MIGRATOR_PLUGIN_ASSETS', plugins_url( 'assets', DOKAN_MIGRATOR_FILE ) );
    }

    /**
     * Executes on plugin activation.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function activate() {
        new WeDevs\DokanMigrator\Install\Installer();
    }

    /**
     * Init the plugin.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function plugin_init() {
        // Initialize the action hooks
        $this->init_classes();
    }

    /**
     * Load plugin classes.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    private function init_classes() {
        if ( is_admin() ) {
            $this->container['admin'] = new \WeDevs\DokanMigrator\Admin\Manager();
        }

        $this->container['migrator'] = new \WeDevs\DokanMigrator\Migrator\Manager();
        $this->container = apply_filters( 'dokan_migrator_get_class_container', $this->container );
    }

    /**
     * Magic getter to bypass referencing objects
     *
     * @since DOKAN_MIG_SINCE
     *
     * @param string $prop
     *
     * @return Class Instance
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }
    }
}

/**
 * Load Dokan migrator plugin.
 *
 * @since DOKAN_MIG_SINCE
 *
 * @return \Dokan_Migrator
 */
function dokan_migrator() {
    return Dokan_Migrator::init();
}

// Lets Go....
dokan_migrator();
