<?php
/*
Plugin Name: Tsearch
Description: AJAX search for posts, taxonomies, custom post types, and custom taxonomies.
Version: 1.2
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
    wp_enqueue_script('ajax-search-script', plugin_dir_url(__FILE__) . 'js/ajax-search.js', array('jquery'), '1.0', true);
    wp_localize_script('ajax-search-script', 'ajax_search_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
    wp_enqueue_style('ajax-search-style', plugin_dir_url(__FILE__) . 'css/ajax-search.css');
}
add_action('wp_enqueue_scripts', 'ajax_search_enqueue_scripts');

// Add settings page
function ajax_search_add_admin_menu() {
    add_menu_page(
        'AJAX Search Settings',
        'AJAX Search',
        'manage_options',
        'ajax-search-settings',
        'ajax_search_settings_page_content',
        'dashicons-search',
        30
    );
}
add_action('admin_menu', 'ajax_search_add_admin_menu');

function ajax_search_register_settings() {
    register_setting('ajax_search_settings', 'ajax_search_post_types');
    register_setting('ajax_search_settings', 'ajax_search_taxonomies');
    register_setting('ajax_search_settings', 'ajax_search_custom_fields');
    register_setting('ajax_search_settings', 'ajax_search_page_id'); // New setting
    register_setting('ajax_search_settings', 'ajax_search_position'); // New setting for position
}
add_action('admin_init', 'ajax_search_register_settings');

// Settings page content
function ajax_search_settings_page_content() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    if (isset($_POST['ajax_search_settings_nonce']) && wp_verify_nonce($_POST['ajax_search_settings_nonce'], 'ajax_search_settings')) {
        update_option('ajax_search_post_types', isset($_POST['post_types']) ? $_POST['post_types'] : array());
        update_option('ajax_search_taxonomies', isset($_POST['taxonomies']) ? $_POST['taxonomies'] : array());
        update_option('ajax_search_custom_fields', isset($_POST['custom_fields']) ? $_POST['custom_fields'] : array());
        update_option('ajax_search_page_id', isset($_POST['search_page_id']) ? intval($_POST['search_page_id']) : 0);
        update_option('ajax_search_position', isset($_POST['search_position']) ? sanitize_text_field($_POST['search_position']) : 'before_content');
    }
    
    $post_types = get_post_types(array('public' => true), 'objects');
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    $selected_post_types = get_option('ajax_search_post_types', array());
    $selected_taxonomies = get_option('ajax_search_taxonomies', array());
    $selected_custom_fields = get_option('ajax_search_custom_fields', array());
    $selected_page_id = get_option('ajax_search_page_id', 0);
    $selected_position = get_option('ajax_search_position', 'before_content');

        // Get all pages for the dropdown
        $pages = get_pages();

    // Custom fields detection
    $custom_fields = array();
    $post_types_to_check = !empty($selected_post_types) ? $selected_post_types : array_keys(get_post_types(array('public' => true)));
    
    foreach ($post_types_to_check as $post_type) {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => 100,
            'post_status' => 'any',
            'fields' => 'ids',
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $post_custom_keys = get_post_custom_keys($post_id);
                if (!empty($post_custom_keys)) {
                    $custom_fields = array_merge($custom_fields, $post_custom_keys);
                }
            }
        }
        wp_reset_postdata();
    }

    // Add ACF fields if available
    if (function_exists('acf_get_field_groups')) {
        $field_groups = acf_get_field_groups();
        foreach ($field_groups as $field_group) {
            $fields = acf_get_fields($field_group);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $custom_fields[] = $field['name'];
                }
            }
        }
    }

    $custom_fields = array_unique($custom_fields);
    sort($custom_fields);
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="" method="post">
            <?php wp_nonce_field('ajax_search_settings', 'ajax_search_settings_nonce'); ?>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Search Bar Display Settings</h2>
                <p>Select a page to automatically display the search bar (optional):</p>
                <select name="search_page_id" style="max-width: 300px;">
                    <option value="0"><?php esc_html_e('-- Select a page --'); ?></option>
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($selected_page_id, $page->ID); ?>>
                            <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <p style="margin-top: 15px;">Select where to display the search bar:</p>
                <select name="search_position" style="max-width: 300px;">
                    <option value="before_content" <?php selected($selected_position, 'before_content'); ?>>
                        <?php esc_html_e('Before Content'); ?>
                    </option>
                    <option value="after_content" <?php selected($selected_position, 'after_content'); ?>>
                        <?php esc_html_e('After Content'); ?>
                    </option>
                </select>
                <p class="description">
                    Note: You can still use the shortcode [ajax_search] to display the search bar anywhere else.
                </p>
            </div>

            <div class="card">
                <h2>Select Post Types to Search</h2>
                <?php foreach ($post_types as $post_type): ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>"
                            <?php checked(in_array($post_type->name, $selected_post_types)); ?>>
                        <?php echo esc_html($post_type->label); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Select Taxonomies to Search</h2>
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" name="taxonomies[]" value="<?php echo esc_attr($taxonomy->name); ?>"
                            <?php checked(in_array($taxonomy->name, $selected_taxonomies)); ?>>
                        <?php echo esc_html($taxonomy->label); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Select Custom Fields to Search</h2>
                <?php if (empty($custom_fields)): ?>
                    <p>No custom fields found. Try selecting and saving post types first.</p>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd;">
                        <?php foreach ($custom_fields as $custom_field): ?>
                            <?php
                            // Skip WordPress internal fields
                            if (in_array($custom_field, array('_edit_lock', '_edit_last'))) {
                                continue;
                            }
                            ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="custom_fields[]" value="<?php echo esc_attr($custom_field); ?>"
                                    <?php checked(in_array($custom_field, $selected_custom_fields)); ?>>
                                <?php 
                                if (strpos($custom_field, '_') === 0) {
                                    echo esc_html($custom_field) . ' <span style="color: #666;">(Hidden field)</span>';
                                } else {
                                    echo esc_html($custom_field);
                                }
                                ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// New function to automatically display the search bar
function ajax_search_auto_display($content) {
    // Check if we're on a page and if it's the selected page
    if (!is_page()) {
        return $content;
    }

    $selected_page_id = get_option('ajax_search_page_id', 0);
    if (!$selected_page_id || $selected_page_id != get_the_ID()) {
        return $content;
    }

    $search_bar = ajax_search_shortcode();
    $position = get_option('ajax_search_position', 'before_content');

    // Handle different theme types (Classic and FSE)
    if (current_theme_supports('block-templates')) {
        // For FSE themes, we'll use block_content filter
        add_filter('render_block', function($block_content, $block) use ($search_bar, $position) {
            if ($block['blockName'] === 'core/post-content') {
                if ($position === 'before_content') {
                    return $search_bar . $block_content;
                } else {
                    return $block_content . $search_bar;
                }
            }
            return $block_content;
        }, 10, 2);
        return $content;
    } else {
        // For classic themes, we'll modify the content directly
        if ($position === 'before_content') {
            return $search_bar . $content;
        } else {
            return $content . $search_bar;
        }
    }
}
add_filter('the_content', 'ajax_search_auto_display');

// AJAX search function
function ajax_search() {
    $search_query = sanitize_text_field($_POST['search_query']);
    $selected_post_types = get_option('ajax_search_post_types', array());
    $selected_taxonomies = get_option('ajax_search_taxonomies', array());
    $selected_custom_fields = get_option('ajax_search_custom_fields', array());
    
    $args = array(
        's' => $search_query,
        'post_type' => $selected_post_types,
        'posts_per_page' => 10,
        'meta_query' => array('relation' => 'OR')
    );

    // Add custom fields to the meta query
    if (!empty($selected_custom_fields)) {
        foreach ($selected_custom_fields as $custom_field) {
            $args['meta_query'][] = array(
                'key' => $custom_field,
                'value' => $search_query,
                'compare' => 'LIKE'
            );
        }
    }
    
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
        wp_reset_postdata();
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
        <div class="search-input-container">
            <input type="text" id="ajax-search-input" placeholder="Search..." aria-autocomplete="list" aria-controls="ajax-search-results">
            <button id="microphone-button" aria-label="Voice Search">
                <img src="<?php echo esc_url(plugins_url('images/microphone-icon.png', __FILE__)); ?>" alt="Microphone Icon" />
            </button>
        </div>
        <div id="ajax-search-results" role="listbox" aria-label="Search results"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ajax_search', 'ajax_search_shortcode');
?>
