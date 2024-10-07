<?php
/*
Plugin Name: Tsearch
Description: AJAX search for posts, taxonomies, custom post types, and custom taxonomies.
Version: 1.0
Author: Innov8ion.tech
Author URI: https://innov8ion.tech
Plugin URI: https://innov8ion.tech/plugins
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: AJAX Search
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0 or higher
Tags: ajax, search, posts, taxonomies, custom post types
*/

// Enqueue scripts and styles
function ajax_search_enqueue_scripts() {
    // Only enqueue on the front end
    if (!is_admin()) {
        wp_enqueue_script('ajax-search-script', plugin_dir_url(__FILE__) . 'js/ajax-search.js', array('jquery'), '1.0', true);
        wp_localize_script('ajax-search-script', 'ajax_search_params', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_style('ajax-search-style', plugin_dir_url(__FILE__) . 'css/ajax-search.css', array(), '1.0', 'all'); // Added version number
    }
}
add_action('wp_enqueue_scripts', 'ajax_search_enqueue_scripts');

// Add settings page
function ajax_search_settings_page() {
    add_options_page('AJAX Search Settings', 'AJAX Search', 'manage_options', 'ajax-search-settings', 'ajax_search_settings_page_content');
}
add_action('admin_menu', 'ajax_search_settings_page');

// Settings page content
function ajax_search_settings_page_content() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if the nonce is set and unslash it
    if (isset($_POST['ajax_search_settings_nonce'])) {
        $nonce = wp_unslash($_POST['ajax_search_settings_nonce']); // Unsplash the nonce
        
        // Verify the nonce
        if (wp_verify_nonce($nonce, 'ajax_search_settings')) {
            // Sanitize and update options
            $post_types = isset($_POST['post_types']) ? wp_unslash($_POST['post_types']) : array();
            $taxonomies = isset($_POST['taxonomies']) ? wp_unslash($_POST['taxonomies']) : array();
            
            // Sanitize the post types and taxonomies before saving
            $post_types = array_map('sanitize_text_field', (array) $post_types);
            $taxonomies = array_map('sanitize_text_field', (array) $taxonomies);
            
            update_option('ajax_search_post_types', $post_types);
            update_option('ajax_search_taxonomies', $taxonomies);
        }
    }
    
    $post_types = get_post_types(array('public' => true), 'objects');
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    $selected_post_types = get_option('ajax_search_post_types', array());
    $selected_taxonomies = get_option('ajax_search_taxonomies', array());
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="" method="post">
            <?php wp_nonce_field('ajax_search_settings', 'ajax_search_settings_nonce'); ?>
            <h2>Select Post Types to Search</h2>
            <?php foreach ($post_types as $post_type): ?>
                <label>
                    <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>"
                        <?php checked(in_array($post_type->name, $selected_post_types)); ?>>
                    <?php echo esc_html($post_type->label); ?>
                </label><br>
            <?php endforeach; ?>
            
            <h2>Select Taxonomies to Search</h2>
            <?php foreach ($taxonomies as $taxonomy): ?>
                <label>
                    <input type="checkbox" name="taxonomies[]" value="<?php echo esc_attr($taxonomy->name); ?>"
                        <?php checked(in_array($taxonomy->name, $selected_taxonomies)); ?>>
                    <?php echo esc_html($taxonomy->label); ?>
                </label><br>
            <?php endforeach; ?>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// AJAX search function
function ajax_search() {
    // Check if the nonce is set and valid
    if (!isset($_POST['ajax_search_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ajax_search_nonce']), 'ajax_search')) {
        wp_send_json_error('Invalid nonce.');
        return;
    }

    // Check if the search_query is set and unslash it
    if (isset($_POST['search_query'])) {
        $search_query = wp_unslash($_POST['search_query']); // Unslash the search query
        $search_query = sanitize_text_field($search_query); // Sanitize the search query
    } else {
        wp_send_json_error('Search query is missing.');
        return;
    }

    $selected_post_types = get_option('ajax_search_post_types', array());
    $selected_taxonomies = get_option('ajax_search_taxonomies', array());
    
    $args = array(
        's' => $search_query,
        'post_type' => $selected_post_types,
        'posts_per_page' => 10
    );
    
    $query = new WP_Query($args);
    $results = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = array(
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'type' => 'post'
            );
        }
    }
    
    foreach ($selected_taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'name__like' => $search_query,
            'hide_empty' => false
        ));
        
        foreach ($terms as $term) {
            $results[] = array(
                'title' => $term->name,
                'permalink' => get_term_link($term),
                'type' => 'taxonomy'
            );
        }
    }
    
    wp_send_json($results);
}
add_action('wp_ajax_ajax_search', 'ajax_search');
add_action('wp_ajax_nopriv_ajax_search', 'ajax_search');

// Shortcode for search box
function ajax_search_shortcode() {
    ob_start();
    ?>
    <div class="ajax-search-container">
        <label for="ajax-search-input" class="screen-reader-text">Search</label>
        <input type="text" id="ajax-search-input" placeholder="Search..." aria-autocomplete="list" aria-controls="ajax-search-results">
        <div id="ajax-search-results" role="listbox" aria-label="Search results"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ajax_search', 'ajax_search_shortcode');
