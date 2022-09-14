<?php
/**
 * Plugin Name: Dokan Migrator
 * Plugin URI: http://wedevs.com/
 * Description: An e-commerce marketplace migration plugin for WordPress. Powered by WooCommerce and weDevs.
 * Version: 1.0.0
 * Author: weDevs
 * Author URI: https://wedevs.com/
 * Domain Path: /languages/
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 3.0
 * WC tested up to: 6.2.0
 * Text Domain: dokan-migrator
 *
 * Copyright (c) 2016 weDevs (email: info@wedevs.com). All rights reserved.
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

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dokan_Migrator class
 *
 * @class Dokan_Migrator The class that holds the entire Dokan_Migrator plugin
 *
 * @since 1.0.0
 */
final class Dokan_Migrator {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Dokan email classes.
     *
     * @var string
     */
    public static $email_class = [];

    /**
     * Dokan email templates.
     *
     * @var string
     */
    public static $template = [];

    /**
     * Dokan email actions.
     *
     * @var string
     */
    public static $actions = [];

    /**
     * Instance of self
     *
     * @var Dokan_Migrator
     */
    private static $instance = null;

    /**
     * Holds various class instances
     *
     * @since 1.0.0
     *
     * @var array
     */
    private $container = [];

    /**
     * Class constructor.
     */
    public function __construct() {
        require_once __DIR__ . '/vendor/autoload.php';

        // Define constants.
        $this->define_constants();

        // load the addon
        add_action( 'dokan_loaded', array( $this, 'plugin_init' ) );
    }

    /**
     * Initializes the WeDevs_Dokan() class
     *
     * Checks for an existing WeDevs_WeDevs_Dokan() instance
     * and if it doesn't find one, creates it.
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
     * @since 1.0.0
     *
     * @return void
     */
    public function define_constants() {
        define( 'DOKAN_MIGRATOR_PLUGIN_VERSION', $this->version );
        define( 'DOKAN_MIGRATOR_FILE', __FILE__ );
        define( 'DOKAN_MIGRATOR_DIR', dirname( DOKAN_MIGRATOR_FILE ) );
        define( 'DOKAN_MIGRATOR_INC', DOKAN_MIGRATOR_DIR . '/includes' );
        define( 'DOKAN_MIGRATOR_PLUGIN_ASSETS_DRI', DOKAN_MIGRATOR_DIR . '/assets' );
        define( 'DOKAN_MIGRATOR_PLUGIN_ASSETS', plugins_url( 'assets', DOKAN_MIGRATOR_FILE ) );
    }

    /**
     * Init the plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function plugin_init() {
        // Initialize the action hooks
        $this->init_classes();

        $this->show_admin_notice();
    }

    /**
     * Show needed admin notice.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function show_admin_notice() {
        add_action( 'admin_notices', [ $this, 'show_dokan_dashboard_activate_notice' ] );
    }

    /**
     * Show activate vendor dashboard notice
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function show_dokan_dashboard_activate_notice() {
        if ( get_option( 'dokan_migration_completed', false ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'templates/template-active-vendor-dashboard.php';
        }
    }

    /**
     * Preventing email notifications from dokan and woocommerce.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function prevent_email_notification() {
        add_filter(
            'woocommerce_email_classes',
            function ( $data ) {
                self::$email_class = $data;
                return [];
            },
            35
        );
        add_filter(
            'woocommerce_template_directory',
            function ( $data ) {
                self::$template = $data;
                return [];
            },
            15
        );
        add_filter(
            'woocommerce_email_actions',
            function ( $data ) {
                self::$actions = $data;
                return [];
            }
        );
    }

    /**
     * Resting email classes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function reset_email_data() {
        add_filter(
            'woocommerce_email_classes',
            function ( $data ) {
                return self::$email_class;
            },
            35
        );
        add_filter(
            'woocommerce_template_directory',
            function ( $data ) {
                return self::$template;
            },
            15
        );
        add_filter(
            'woocommerce_email_actions',
            function ( $data ) {
                return self::$actions;
            }
        );
    }

    /**
     * Load plugin classes.
     *
     * @return void
     */
    private function init_classes() {
        if ( is_admin() ) {
            new \Wedevs\DokanMigrator\Admin\Menu();
        }

        $this->container['migrator'] = new \Wedevs\DokanMigrator\Migrator\Manager();
        $this->container = apply_filters( 'dokan_migrator_get_class_container', $this->container );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            new \Wedevs\DokanMigrator\Ajax();
        }
    }

    /**
     * Magic getter to bypass referencing objects
     *
     * @since 1.0.0
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
 * @return \Dokan_Migrator
 */
function dokan_migrator() {
    return Dokan_Migrator::init();
}

// Lets Go....
dokan_migrator();
