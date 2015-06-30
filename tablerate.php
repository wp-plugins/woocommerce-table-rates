<?php
/**
 *
 * Plugin Name: WooCommerce - Premium Table Rate Shipping
 * Plugin URI: http://wp-ronin.com
 * Description: Plugin for fixed rate shipping depending upon the cart amount in WooCommerce.
 * Version: 1.2
 * Author: WP-Ronin.com
 * Author URI: http://wp-ronin.com
 * License: GPL2
 * Text Domain:     wpr-ptr-shipping
 * 
 * @package         WooCommerce - Premium Table Rate Shipping
 * @author          Ryan PLetcher
 * @copyright       Copyright (c) 2015
 *
 * IMPORTANT! Ensure that you make the following adjustments
 * before releasing your extension:
 *
 * - Replace all instances of plugin-name with the name of your plugin.
 *   By WordPress coding standards, the folder name, plugin file name,
 *   and text domain should all match. For the purposes of standardization,
 *   the folder name, plugin file name, and text domain are all the
 *   lowercase form of the actual plugin name, replacing spaces with
 *   hyphens.
 *
 * - Replace all instances of Plugin_Name with the name of your plugin.
 *   For the purposes of standardization, the camel case form of the plugin
 *   name, replacing spaces with underscores, is used to define classes
 *   in your extension.
 *
 * - Replace all instances of PLUGINNAME with the name of your plugin.
 *   For the purposes of standardization, the uppercase form of the plugin
 *   name, removing spaces, is used to define plugin constants.
 *
 * - Replace all instances of Plugin Name with the actual name of your
 *   plugin. This really doesn't need to be anywhere other than in the
 *   EDD Licensing call in the hooks method.
 *
 * - Find all instances of @todo in the plugin and update the relevant
 *   areas as necessary.
 *
 * - All functions that are not class methods MUST be prefixed with the
 *   plugin name, replacing spaces with underscores. NOT PREFIXING YOUR
 *   FUNCTIONS CAN CAUSE PLUGIN CONFLICTS!
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WPR_PremiumTableRate' ) ) {

    /**
     * Main WPR_PremiumTableRate class
     *
     * @since       1.0.0
     */
    class WPR_PremiumTableRate {

        /**
         * @var         WPR_PremiumTableRate $instance The one true WPR_PremiumTableRate
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true WPR_PremiumTableRate
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new WPR_PremiumTableRate();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'WPR_PTR_VER', '1.2.0' );

            // Plugin path
            define( 'WPR_PTR_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'WPR_PTR_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once WPR_PTR_DIR . 'includes/scripts.php';
            require_once WPR_PTR_DIR . 'includes/functions.php';

            require_once WPR_PTR_DIR . 'admin/admin.php';
            require_once WPR_PTR_DIR . 'admin/shipping.php';

            /**
             * @todo        The following files are not included in the boilerplate, but
             *              the referenced locations are listed for the purpose of ensuring
             *              path standardization in EDD extensions. Uncomment any that are
             *              relevant to your extension, and remove the rest.
             */
            // require_once WPR_PTR_DIR . 'includes/shortcodes.php';
            // require_once WPR_PTR_DIR . 'includes/widgets.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         *
         * @todo        The hooks listed in this section are a guideline, and
         *              may or may not be relevant to your particular extension.
         *              Please remove any unnecessary lines, and refer to the
         *              WordPress codex and EDD documentation for additional
         *              information on the included hooks.
         *
         *              This method should be used to add any filters or actions
         *              that are necessary to the core of your extension only.
         *              Hooks that are relevant to meta boxes, widgets and
         *              the like can be placed in their respective files.
         *
         *              IMPORTANT! If you are releasing your extension as a
         *              commercial extension in the EDD store, DO NOT remove
         *              the license check!
         */
        private function hooks() {
            // Register settings
            //add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

            // Setup shipping method
            add_filter( 'woocommerce_shipping_methods', 'rp_ptr_add_rate' );
            add_action( 'plugins_loaded', 'rp_ptr_shipping', 0 );


            // Table rate admin area
            add_action( 'init', 'rp_ptr_create_post_type', 10 );
            add_filter( 'manage_edit-rp_ptr_shipping_columns', 'rp_ptr_add_columns' );
            add_action( 'manage_rp_ptr_shipping_posts_custom_column', 'rp_ptr_custom_columns', 2 );
            add_action( 'add_meta_boxes', 'rp_ptr_meta_boxes' );
            add_action( 'save_post', 'rp_ptr_save_table', 1, 2 ); // save the custom fields





            // Handle licensing
            // @todo        Replace the Plugin Name and Your Name with your data
            //if( class_exists( 'EDD_License' ) ) {
            //    $license = new EDD_License( __FILE__, 'Plugin Name', WPR_PTR_VER, 'Your Name' );
            //}
        }




        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = WPR_PTR_DIR . '/languages/';
            $lang_dir = apply_filters( 'wpr_ptr_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'wpr-ptr-shipping' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'wpr-ptr-shipping', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/wpr-ptr-shipping/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/wpr-ptr-shipping/ folder
                load_textdomain( 'wpr-ptr-shipping', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/wpr-ptr-shipping/languages/ folder
                load_textdomain( 'wpr-ptr-shipping', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'wpr-ptr-shipping', false, $lang_dir );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'wpr_ptr_settings',
                    'name'  => '<strong>' . __( 'Plugin Name Settings', 'wpr-ptr-shipping' ) . '</strong>',
                    'desc'  => __( 'Configure Plugin Name Settings', 'wpr-ptr-shipping' ),
                    'type'  => 'header',
                )
            );

            return array_merge( $settings, $new_settings );
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true WPR_PremiumTableRate
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \WPR_PremiumTableRate The one true WPR_PremiumTableRate
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function wpr_ptr_load() {
    if ( class_exists( 'WC_Shipping_Method' ) )
        return WPR_PremiumTableRate::instance();
}
add_action( 'plugins_loaded', 'wpr_ptr_load' );
