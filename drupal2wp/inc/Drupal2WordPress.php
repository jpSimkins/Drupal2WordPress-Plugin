<?php
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
        // Add admin menu
        add_action('admin_menu', array($this, 'addMenuItem'));
    }

    /**
     * Adds the plugin menu item
     */
    function addMenuItem() {
        // Use a session so we can store the db details (safer than a cookie)
        if (!session_id()) {
            session_start();
        }
        // Define menu
        $this->_pageHook = register_importer( 'drupal2wordpress', __('Drupal 2 WordPress', 'drupal2wp'), __('Import <strong>posts, pages, comments, categories, and tags</strong> from a Drupal database.', 'drupal2wp'),  array($this, 'init'));
        // Init the page view on load
        add_action( 'load-' . $this->_pageHook, array($this, 'init') );
    }

    /**
     * Initialize the plugin
     */
    function init() {
        // Initialize the view
        $this->_views = new Drupal2WordPress_Views($this->_pageHook);
        $this->_views->init();
        $this->_views->showPage();
    }

}