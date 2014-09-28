<?php

/**
 * Class Drupal2WordPress_Views
 * Handles the views for the plugin
 */
class Drupal2WordPress_Views {

    /**
     * Plugin page hook
     * @var string
     */
    private $_pageHook;

    /**
     * View Processor
     * @var Drupal2WordPress_ViewProcessor
     */
    private $_processor;

    /**
     * Current step we are working on
     * @var int
     */
    private $_step = 1;

    /**
     * Error messages
     * @var array
     */
    private $errors = array();

    /**
     * Success messages
     * @var array
     */
    private $success = array();

    /**
     * Notice messages
     * @var array
     */
    private $notice = array();

    /**
     * Drupal version to import
     * @var int
     */
    private $_drupalVersion;

    /**
     * Construct the object
     * @param $pageHook
     */
    public function __construct($pageHook) {
        $this->_pageHook = $pageHook;
    }

    /**
     * Initialize the view
     */
    public function init() {
        $this->_processRequest();
    }

    /**
     * Shows the page
     */
    public function showPage() {
        $this->_showMenuPage();
    }

    /**
     * Processes the request
     */
    private function _processRequest() {
        try {
            // Define and initialize processor
            $this->_processor = new Drupal2WordPress_ViewProcessor($this->_pageHook);
            $this->_processor->init();
            // Fetch step
            $this->_step = $this->_processor->getStep();
            // Set Drupal Version
            $this->_drupalVersion = $_SESSION['druaplDB']['version'];
        } catch(Exception $e) {
            // Make sure we stay on the correct step
            $this->_step = $this->_processor->getStep();
            // Add error
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * Shows the menu page content after permission check
     */
    private function _showMenuPage() {
        if (!current_user_can('manage_options')) {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        try {
            $TEMPLATE_VARS = array(
                'errors' => $this->errors,
                'success' => $this->success,
                'nag' => $this->notice,
                'stepView' => DRUPAL2WP_VIEWS_DIRECTORY."step1.php"
            );
            // Show view
            switch($this->_step) {
                case 3:
                    add_action('drupal2wp_import_iframe', array($this, 'displayImportIframe'));
                    break;
                case 2:
                    // Adding template vars
                    $TEMPLATE_VARS['drupalVersion'] = $this->_drupalVersion;
                    $TEMPLATE_VARS['drupalPrefix'] = $_SESSION['druaplDB']['prefix'];
                    break;
                default:
                    $this->_step = 1;
                    break;
            }
            $this->_loadPage($TEMPLATE_VARS);
        } catch(Exception $e) {
            // Make sure we stay on the correct step
            $this->_step = $this->_processor->getStep();
            // Add error
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * Loads the page
     * @param $TEMPLATE_VARS
     * @throws Exception
     */
    private function _loadPage($TEMPLATE_VARS) {
        $TEMPLATE_VARS['stepView'] = DRUPAL2WP_VIEWS_DIRECTORY."step{$this->_step}.php";
        $_file = DRUPAL2WP_VIEWS_DIRECTORY."body.php";
        if (file_exists($_file)) {
            include($_file);
        } else {
            throw new Exception(__('Invalid Step for importing', 'drupal2wp'));
        }
    }

    /**
     * Displays the import iframe
     */
    public function displayImportIframe() {
        // Display whatever it is you want to show
        echo '<iframe src="'.wp_nonce_url( DRUPAL2WP_DIRECTORY_URL.'_processImport.php', 'drupal2wp_import_iframe' ).'" width="100%" height="1000px" frameBorder="0">Browser does not support iframes.</iframe>';
    }
}