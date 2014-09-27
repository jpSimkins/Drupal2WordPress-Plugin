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
        $this->_pageHook = add_management_page(__('Drupal 2 WordPress', 'drupal2wp'), __('Drupal 2 WordPress', 'drupal2wp'), 'import', $this->basename, array($this, 'initMenuPage'));
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
    }

    /**
     * Initializes the view for the plugin page
     */
    function initMenuPage() {
        $this->_views->showPage();
    }

}