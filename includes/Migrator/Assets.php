<?php

namespace WeDevs\DokanMigrator\Migrator;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Assets class for dokan migrator.
 *
 * @since DOKAN_MIG_SINCE
 */
class Assets {

    /**
     * Assets class constructor.
     *
     * @since DOKAN_MIG_SINCE
     */
    public function __construct() {
        add_action( 'init', [ $this, 'setup_assets' ] );
    }

    /**
     * Sets up assets.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function setup_assets() {
        load_plugin_textdomain( 'dokan-migrator', false, DOKAN_MIGRATOR_DIR . '/languages' );
        $this->register_scripts();
        wp_set_script_translations( 'dokan-migrator-app', 'dokan-migrator', DOKAN_MIGRATOR_DIR . '/languages' );
    }

    /**
     * Registers scripts.
     *
     * @since DOKAN_MIG_SINCE
     *
     * @return void
     */
    public function register_scripts() {
        $asset = require_once DOKAN_MIGRATOR_PLUGIN_ASSETS_DRI . '/dist/index.asset.php';

        $scripts = apply_filters(
            'dokan_migrator_scripts',
            [
                [
                    'handle'    => 'dokan-migrator-script',
                    'src'       => DOKAN_MIGRATOR_PLUGIN_ASSETS . '/dist/index.js',
                    'deps'      => $asset['dependencies'],
                    'version'   => $asset['version'],
                    'in_footer' => true,
                ],
            ]
        );

        $styles = apply_filters(
            'dokan_migrator_styles',
            [
                [
                    'handle'  => 'dokan-migrator-style',
                    'src'     => DOKAN_MIGRATOR_PLUGIN_ASSETS . '/dist/index.css',
                    'deps'    => [],
                    'version' => $asset['version'],
                ],
            ]
        );

        foreach ( $scripts as $script ) {
            wp_register_script(
                $script['handle'],
                $script['src'],
                $script['deps'],
                $script['version'],
                $script['in_footer']
            );
        }

        foreach ( $styles as $style ) {
            wp_register_style(
                $style['handle'],
                $style['src'],
                $style['deps'],
                $style['version']
            );
        }
    }
}
