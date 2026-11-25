<?php
/**
 * Plugin Name: WP Projects Managment
 * Description: Manage Projects information
 * Version: 0.0.1
 * Author: Sagar Parmar
 * Author uri: https://github.com/sagarparmargit
 * text domain: wp-projects
 */


if( !defined( "ABSPATH" ) ){
    EXIT;
}

/**
 * Define Plugin path
 */
if( !defined( "WPP_PLUGIN_PATH" ) ){
    define( "WPP_PLUGIN_PATH", plugin_dir_path(__FILE__) );
}

/**
 * Define Plugin URL
 */
if( !defined( "WPP_PLUGIN_URL" ) ){
    define( "WPP_PLUGIN_URL", plugin_dir_url( __FILE__ ) );
}

/**
 * Loads the plugin components.
 */
if( !class_exists( "WP_Project" ) ){
    class WP_Project{
        private static $instanse = null;

        /**
         * Loads plugin component files.
         */
        private function __construct()
        {
            $this->load_depandancy();
        }

        /**
         * Initializes the plugin once.
         */
        public static function load_instance(){
            if( self::$instanse == null ){
                new WP_Project();
            }
        }

        /**
         * Includes the post type, REST API, and frontend shortcode/AJAX components.
         */
        private function load_depandancy(){
            require_once WPP_PLUGIN_PATH."/includes/class.cpt.php";
            require_once WPP_PLUGIN_PATH."/includes/class.api.php";
            require_once WPP_PLUGIN_PATH."/public/class.ajax.php";
        }
    }
}

WP_Project::load_instance();
?>
