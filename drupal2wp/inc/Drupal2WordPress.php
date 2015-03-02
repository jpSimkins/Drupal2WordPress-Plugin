<?php

// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");

/**
 * Class Drupal2WordPress
 */
class Drupal2WordPress {

    /**
     * Plugin basename
     * @var string
     */
    private $basename = 'drupal2wp';

    /**
     * Plugin page hook
     * @var string
     */
    private $_pageHook;

    /**
     * Views Object
     * @var Drupal2WordPress_Views
     */
    private $_views;

    /**
     * Construct the plugin
     * Adds the admin menu
     */
    public function __construct() {
        // Only process if in admin
        if (is_admin()) {
            // Add admin menu
            add_action('admin_menu', array($this, 'addMenuItem'));
            // Use a session so we can store the db details (safer than a cookie)
            if (!session_id()) {
                session_start();
            }
            // Validate the iframe nonce
            if (isset($_GET['_drupal2wp_wpnonce']) && wp_verify_nonce($_GET['_drupal2wp_wpnonce'], 'drupal2wp_import_iframe')) {
                Drupal2WordPress_DrupalImporter::process();
            }
        }
    }

    /**
     * Adds the plugin menu item
     */
    function addMenuItem() {
        // Define menu
        $this->_pageHook = register_importer( 'drupal2wordpress', __('Drupal 2 WordPress', 'drupal2wp'), __('Import <strong>users, posts, pages, comments, media, categories, and tags</strong> from a Drupal database.', 'drupal2wp'),  array($this, 'init'));
        // Init the page view on load
        add_action( 'load-' . $this->_pageHook, array($this, 'init') );
    }

    /**
     * Initialize the plugin
     */
    function init() {
        // Initialize the view
        do_action('drupal2wp_init', $this);
        $this->_views = new Drupal2WordPress_Views($this->_pageHook);
        $this->_views->init();
        $this->_views->showPage();
    }

}