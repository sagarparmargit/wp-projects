<?php

if( !defined( "ABSPATH" ) ){
    EXIT;
}

/**
 * Registers the public Projects REST endpoint.
 */
if( !class_exists( "WP_Project_API" ) ){
    class WP_Project_API{
        
        private static $instanse = null;

        /**
         * Hooks REST route registration into the REST API lifecycle.
         */
        private function __construct()
        {
            add_action( "rest_api_init", [$this, "register_api"] );
        }

        /**
         * Initializes the API component.
         */
        public static function load_instance(){
            if( self::$instanse == null ){
                new WP_Project_API();
            }
        }

        /**
         * Registers GET /wp-json/wp-projects/v1/projects and its query arguments.
         */
        public function register_api(){
            register_rest_route( "wp-projects/v1", "/projects", [
                "methods" => WP_REST_Server::READABLE,
                "callback" => [ $this, "get_projects" ],
                "permission_callback" => "__return_true",
                "args" => [
                    "status" => [
                        "default" => "all",
                        "enum" => [ "all", "active", "completed" ],
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                    "page" => [
                        "default" => 1,
                        "type" => "integer",
                        "minimum" => 1,
                    ],
                    "per_page" => [
                        "default" => 10,
                        "type" => "integer",
                        "minimum" => 1,
                        "maximum" => 100,
                    ],
                ],
            ] );
        }

        /**
         * Returns paginated project details, including the configured ACF fields.
         *
         * @param WP_REST_Request $request REST request and query parameters.
         * @return WP_REST_Response|WP_Error
         */
        public function get_projects( $request ){
            $projects = WP_Project_CPT::get_projects(
                $request->get_param( "status" ),
                $request->get_param( "page" ),
                $request->get_param( "per_page" )
            );

            if( is_wp_error( $projects ) ){
                return $projects;
            }

            $project_data = [];
            foreach( $projects->posts as $project ){
                $project_data[] = [
                    "id" => $project->ID,
                    "title" => get_the_title( $project ),
                    "url" => get_permalink( $project ),
                    "thumbnail" => get_the_post_thumbnail_url( $project->ID, "medium" ) ?: "",
                    "client_name" => get_post_meta( $project->ID, "client_name", true ),
                    "project_status" => get_post_meta( $project->ID, "project_status", true ),
                    "budget" => get_post_meta( $project->ID, "budget", true ),
                ];
            }

            return rest_ensure_response( [
                "projects" => $project_data,
                "pagination" => [
                    "current_page" => max( 1, (int) $projects->get( "paged" ) ),
                    "per_page" => (int) $projects->get( "posts_per_page" ),
                    "total_projects" => (int) $projects->found_posts,
                    "total_pages" => (int) $projects->max_num_pages,
                ],
            ] );
        }

    }
}
WP_Project_API::load_instance();
?>