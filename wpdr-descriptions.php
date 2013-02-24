<?
/*
Plugin Name: WP Document Revisions Descriptions
Plugin URI: http://github.com/madlandproject/wpdr-descriptions
Description: A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.
Version: 0.1
Author: Robin Lambell
Author URI: http://madlandproject.com
License: GPL3
*/


class WPDRDescriptions {

    // STATIC
    public static function activatePlugin(){

    }

    public static function deactivatePlugin(){

    }

    public static function uninstallPlugin(){

    }

    // INSTANCE
    public function __construct(){

        add_action('admin_enqueue_scripts', array($this, 'adminStyles'));
        add_action('admin_enqueue_scripts', array($this, 'adminScripts'));

        add_action('edit_form_after_editor', array($this, 'printDescriptionEditor'), 10);

        add_action('save_post', array($this, 'savePost'));


        // make descriptions searchable
        add_filter('posts_join', array($this, 'postsJoin'));
        add_filter('posts_where', array($this, 'postsWhere'));
        add_filter('posts_distinct', array($this, 'postsDistinct'));

        // Filters

    }

    public function adminStyles(){
        $screen = get_current_screen();
        if ( $screen->base === 'post' && $screen->post_type == 'document') {

            wp_enqueue_style('wpdrd-editor', plugins_url('/wpdr-descriptions/css/editor.css'));

        }
    }

    public function adminScripts(){
        $screen = get_current_screen();
        if ( $screen->base === 'post' && $screen->post_type == 'document') {

            wp_enqueue_script('wpdrd-editor', plugins_url('/wpdr-descriptions/js/wpdr-descriptions-editor.js'));

        }
    }

    public function printDescriptionEditor(){

        global $post_ID;

        $screen = get_current_screen();

        if ( $screen->base === 'post' && $screen->post_type == 'document') {
            // get current meta for post
            $currentDescription = get_post_meta($post_ID, 'wpdr_description', true);

            // var must be a string
            $currentDescription = (strlen($currentDescription) > 0) ? $currentDescription : '';

            wp_nonce_field('edit', 'wpdrdnonce');

            ?>
        <h3>Description</h3>
        <textarea name="wpdr-description-body" id="wpdr-description-body" rows="5"><?=$currentDescription?></textarea>
        <?

        }
    }

    public function savePost($postid){

        if (    !current_user_can('edit_post') ||
                !wp_verify_nonce($_POST['wpdrdnonce'], 'edit')) {
            return;
        }

        if ( !empty($_POST['wpdr-description-body']) ){

            $description = sanitize_text_field( $_POST['wpdr-description-body'] );

            update_post_meta($postid, 'wpdr_description', $description);

        }
    }

    public function postsJoin($join){

        if (is_search()){
            global $wpdb;

            $join .= " LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id ";
        }

        return($join);
    }

    public function postsWhere($where){
        if (is_search()){

            global $wpdb;
            global $wp_query;

            //wp_die(print_r($wp_query, true));
            $term = $wp_query->get('s');
            $where .= " OR ($wpdb->posts.post_type = 'document' AND {$wpdb->prefix}postmeta.meta_key = 'wpdr_description' AND {$wpdb->prefix}postmeta.meta_value LIKE '%{$term}%')";
        }

        return($where);
    }

    public function postsDistinct(){

        if (is_search()){
            return(' DISTINCT');
        }

    }
}

if (class_exists('Document_revisions')){
    $pluginInstance = new WPDRDescriptions();
}

// Activation and registration
register_activation_hook(__FILE__, 'WPDRDescriptions::activatePlugin' );
register_deactivation_hook(__FILE__, 'WPDRDescriptions::deactivatePlugin' );
register_uninstall_hook(__FILE__, 'WPDRDescriptions::uninstallPlugin' );