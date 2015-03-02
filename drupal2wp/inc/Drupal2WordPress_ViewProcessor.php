<?php

// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");

/**
 * Class Drupal2WordPress_ViewProcessor
 * Processes the steps of the plugin
 */
class Drupal2WordPress_ViewProcessor {
    /**
     * Plugin page hook
     * @var string
     */
    private $_pageHook;

    /**
     * Stores the next step of the process
     * @var int
     */
    private $nextStep = 1;

    /**
     * Stores any errors
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
     * Drupal DB instance
     * @var Drupal2WordPress_DrupalImporter
     */
    private $_drupalImporter;

    /**
     * Initiate the object
     * @param $pageHook
     */
    function __construct($pageHook) {
        $this->_pageHook = $pageHook;
    }

    /**
     * Returns the Drupal2WordPress_DrupalImporter instance
     * @return Drupal2WordPress_DrupalImporter
     */
    public function getImporterInstance() {
        return $this->_drupalImporter;
    }

    /**
     * Adds an error
     * @param $error
     */
    function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * Checks if errors are present
     * @return bool
     */
    function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Returns the errors
     * @return array
     */
    function getErrors() {
        return $this->errors;
    }

    /**
     * Adds an notice
     * @param $notice
     */
    function addNotice($notice) {
        $this->notice[] = $notice;
    }

    /**
     * Checks if notices are present
     * @return bool
     */
    function hasNotices() {
        return !empty($this->notice);
    }

    /**
     * Returns the notices
     * @return array
     */
    function getNotices() {
        return $this->notice;
    }

    /**
     * Adds a success message
     * @param $success
     */
    function addSuccess($success) {
        $this->success[] = $success;
    }

    /**
     * Checks if success messages are present
     * @return bool
     */
    function hasSuccess() {
        return !empty($this->success);
    }

    /**
     * Returns the errors
     * @return array
     */
    function getSuccess() {
        return $this->success;
    }

    /**
     * Decide how to process the request
     */
    public function init() {
        if (!empty($_POST)) {
            if (isset($_POST['step'])) {
                switch( (int) $_POST['step'] ) {
                    case 1:
                        $this->processStep1();
                        break;
                    case 2:
                        $this->processStep2();
                        break;
                }
            } else {
                throw new Exception( __('Invalid POST response.', 'drupal2wp') );
            }
        }
    }

    /**
     * Returns the next step of the process
     * @return int
     */
    function getStep() {
        return $this->nextStep;
    }

    /**
     * Processes Step1 data
     * Runs test to make sure the connection to the Drupal database is working
     */
    function processStep1() {
        // Only process with POST
        if (!empty($_POST)) {
            // Validate the nonce
            if (isset($_POST['_drupal2wp_nonce'])) {
                if (wp_verify_nonce($_POST['_drupal2wp_nonce'], 'drupal2wp-step1-nonce')) {
                    // Handle third-party options
                    $thirdPartyOptions = isset($_POST['thirdPartyOptions']) ? $_POST['thirdPartyOptions'] : array();
                    if (!empty($thirdPartyOptions)) {
                        // Save to session
                        $_SESSION['thirdPartyOptions'] = $thirdPartyOptions;
                    }
                    // Handle DB settings
                    $drupalDBData = isset($_POST['druaplDB']) ? $_POST['druaplDB'] : array();
                    if (!empty($drupalDBData)) {
                        // Save to session
                        $_SESSION['druaplDB'] = $drupalDBData;
                        // Init the Drupal importer
                        $this->_drupalImporter = new Drupal2WordPress_DrupalImporter();
                        // Check the DB connection
                        if (!$this->_drupalImporter->check()) {
                            throw new Exception('Failed to connect to the Drupal database.');
                        } else {
                            // Process step1
                            $this->nextStep = 2;
                        }
                    } else {
                        throw new Exception(__('No Drupal settings found.', 'drupal2wp'));
                    }
                } else {
                    throw new Exception( __('Form validation failed.', 'drupal2wp') );
                }
            } else {
                do_action('drupal2wp_view_step1_submit', $this);
            }
        }
    }

    /**
     * Processes step2
     * User confirms the settings, so we now do the import
     * @throws Exception
     */
    function processStep2() {
        // Only process with POST
        if (!empty($_POST)) {
            // Validate the nonce
            if (isset($_POST['_drupal2wp_nonce'])) {
                if (wp_verify_nonce($_POST['_drupal2wp_nonce'], 'drupal2wp-step2-nonce')) {
                    $importOptions = isset($_POST['options']) ? $_POST['options'] : array();
                    if (!empty($importOptions)) {
                        // Save to session
                        $_SESSION['options'] = $importOptions;
                        // Init the Drupal importer
                        $this->_drupalImporter = new Drupal2WordPress_DrupalImporter();
                        // Check the DB connection
                        if (!$this->_drupalImporter->check()) {
                            throw new Exception('Failed to connect to the Drupal database.');
                        } else {
                            // Process step2
                            $this->nextStep = 3;
                        }
                    } else {
                        // Process step2
                        $this->nextStep = 2;
                        throw new Exception(__('No import options selected.', 'drupal2wp'));
                    }
                } else {
                    throw new Exception(__('Form validation failed.', 'drupal2wp'));
                }
            } else {
                do_action('drupal2wp_view_step2_submit', $this);
            }
        }
    }

} 