<?php

if( !defined( "ABSPATH" ) ){
    EXIT;
}

/**
 * Renders the Projects shortcode and serves its frontend AJAX requests.
 */
if( !class_exists( "WP_Project_Shortcode" ) ){
    class WP_Project_Shortcode{
        
        private static $instanse = null;

        /**
         * Registers the shortcode and public AJAX actions.
         */
        private function __construct()
        {
            add_shortcode( "projects", [$this, "render_shortcode"] );
            add_action( "wp_ajax_wpp_load_projects", [$this, "load_projects"] ); //for logged in
            add_action( "wp_ajax_nopriv_wpp_load_projects", [$this, "load_projects"] );
        }

        public static function load_instance(){
            if( self::$instanse == null ){
                self::$instanse = new WP_Project_Shortcode();
            }
        }

        /**
         * Builds the shortcode container and enqueues its frontend assets.
         *
         * @param array $atts Shortcode attributes, including the project status.
         * @return string Shortcode markup.
         */
        public function render_shortcode( $atts = [] ){
            $atts = shortcode_atts( [
                "status" => "all",
            ], $atts, "projects" );
            $status = sanitize_key( $atts["status"] );

            if( !in_array( $status, [ "all", "active", "completed" ], true ) ){
                return '<p class="wpp-projects__error">' .
                    esc_html__( "Invalid project status. Use all, active, or completed.", "wp-projects" ) .
                    '</p>';
            }

            $script_path = WPP_PLUGIN_PATH . "src/projects.js";
            $style_path = WPP_PLUGIN_PATH . "src/projects.css";
            wp_enqueue_style(
                "wpp-projects",
                WPP_PLUGIN_URL . "src/projects.css",
                [],
                file_exists( $style_path ) ? filemtime( $style_path ) : "1.0.0"
            );
            wp_enqueue_script(
                "wpp-projects",
                WPP_PLUGIN_URL . "src/projects.js",
                [ "jquery" ],
                file_exists( $script_path ) ? filemtime( $script_path ) : "1.0.0",
                true
            );

            wp_localize_script( "wpp-projects", "wppProjects", [
                "ajaxUrl" => admin_url( "admin-ajax.php" ),
                "nonce" => wp_create_nonce( "wpp_projects" ),
                "loading" => __( "Loading projects...", "wp-projects" ),
                "error" => __( "Unable to load projects. Please try again.", "wp-projects" ),
                "noProjects" => __( "No projects found.", "wp-projects" ),
                "clientName" => __( "Client Name", "wp-projects" ),
                "projectStatus" => __( "Project Status", "wp-projects" ),
                "budget" => __( "Budget", "wp-projects" ),
                "notSpecified" => __( "Not specified", "wp-projects" ),
                "perPage" => 10,
            ] );

            $instance_id = wp_unique_id( "wpp-projects-" );
            $load_more_label = [
                "all" => __( "Load More Projects", "wp-projects" ),
                "active" => __( "Load More Active Projects", "wp-projects" ),
                "completed" => __( "Load More Completed Projects", "wp-projects" ),
            ][$status];

            return sprintf(
                '<div id="%1$s" class="wpp-projects" data-status="%2$s"><div class="wpp-projects__results" aria-live="polite" aria-busy="true"></div><button type="button" class="wpp-projects__load-more">%3$s</button></div>',
                esc_attr( $instance_id ),
                esc_attr( $status ),
                esc_html( $load_more_label )
            );
        }

        /**
         * Returns one page of projects for the shortcode's AJAX request.
         */
        public function load_projects(){
            check_ajax_referer( "wpp_projects", "nonce" );

            $status = isset( $_POST["status"] )
                ? sanitize_key( wp_unslash( $_POST["status"] ) )
                : "all";
            $page = isset( $_POST["page"] ) ? absint( wp_unslash( $_POST["page"] ) ) : 1;
            $per_page = isset( $_POST["per_page"] ) ? absint( wp_unslash( $_POST["per_page"] ) ) : 10;

            $projects = WP_Project_CPT::get_projects( $status, $page, $per_page );

            if( is_wp_error( $projects ) ){

                $error_data = $projects->get_error_data();
                
                wp_send_json_error(
                    [ "message" => $projects->get_error_message() ],
                    isset( $error_data["status"] ) ? (int) $error_data["status"] : 400
                );
            }

            $project_data = [];

            foreach( $projects->posts as $project ){
                $project_data[] = [
                    "id" => (int) $project->ID,
                    "title" => get_the_title( $project ),
                    "url" => get_permalink( $project ),
                    "thumbnail" => get_the_post_thumbnail_url( $project->ID, "medium" ) ?: "",
                    "client_name" => get_post_meta( $project->ID, "client_name", true ),
                    "project_status" => get_post_meta( $project->ID, "project_status", true ),
                    "budget" => get_post_meta( $project->ID, "budget", true ),
                ];
            }

            wp_send_json_success( [
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
WP_Project_Shortcode::load_instance();
?>