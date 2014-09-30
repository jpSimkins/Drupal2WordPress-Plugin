<?php
/**
 * Plugin Name: Drupal 2 WordPress Page Tags Support
 * Description: Allows for tags to be used on Page content type
 * Version: 1.0
 * Author: Jeremy Simkins <jpSimkins@gmail.com>
 * Author URI: http://jeremysimkins.com
 * License: GPL2
 */



/**
 * Adds tag support to pages
 */
function drupal2wp_page_tag_support() {
    register_taxonomy_for_object_type('post_tag', 'page');
}

/**
 * Ensure all tags are included in queries
 * @param $wp_query
 */
function drupal2wp_include_tags($wp_query) {
    global $wp_query;
    if ($wp_query->get('tag')) $wp_query->set('post_type', 'any');
}

// tag hooks
add_action('init', 'drupal2wp_page_tag_support');
add_action('pre_get_posts', 'drupal2wp_include_tags');