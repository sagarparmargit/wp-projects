<?php

if( !defined( "ABSPATH" ) ){
    EXIT;
}

/**
 * Registers the Projects post type and provides the shared project query.
 */
if( !class_exists( "WP_Project_CPT" ) ){
    class WP_Project_CPT{
        
        private static $instanse = null;

        /**
         * Hooks post type registration into WordPress initialization.
         */
        private function __construct()
        {
            add_action( "init", [$this, "register_post_type"] );
        }

        /**
         * Initializes the post type component.
         */
        public static function load_instance(){
            if( self::$instanse == null ){
                new WP_Project_CPT();
            }
        }

        /**
         * Registers the public Projects post type.
         */
        public function register_post_type(){
            register_post_type( "projects", array(
                "labels" => array(
                    "name" => __("Projects", "wp-projects"),
                    "singular_name" => __("Project", "wp-projects"),
                ),
                "has_archive" => true,
                "public" => true,
                "supports" => ["editor", "thumbnail", "title"]
            ) );
        }

        /**
         * Queries published projects, optionally filtering by ACF project status.
         *
         * @param string $status   One of all, active, or completed.
         * @param int    $page     One-based page number.
         * @param int    $per_page Number of projects per page (maximum 100).
         * @return WP_Query|WP_Error
         */
        public static function get_projects( $status = "all", $page = 1, $per_page = 10 ){
            if( !in_array( $status, [ "all", "active", "completed" ], true ) ){
                return new WP_Error(
                    "invalid_project_status",
                    __( "Invalid project status.", "wp-projects" ),
                    [ "status" => 400 ]
                );
            }

            $page = absint( $page );
            $per_page = absint( $per_page );
            if( $page < 1 || $per_page < 1 || $per_page > 100 ){
                return new WP_Error(
                    "invalid_project_pagination",
                    __( "Page must be positive and per_page must be between 1 and 100.", "wp-projects" ),
                    [ "status" => 400 ]
                );
            }

            $query_args = [
                "post_type" => "projects",
                "post_status" => "publish",
                "posts_per_page" => $per_page,
                "paged" => $page,
            ];

            if( "active" === $status ){
                $query_args["meta_query"] = [
                    [
                        "key" => "project_status",
                        "value" => "Active",
                        "compare" => "=",
                    ],
                ];
            }

            if( "completed" === $status ){
                $query_args["meta_query"] = [
                    [
                        "key" => "project_status",
                        "value" => "Completed",
                        "compare" => "=",
                    ],
                ];
            }

            return new WP_Query( $query_args );
        }
    }
}
WP_Project_CPT::load_instance();
?>