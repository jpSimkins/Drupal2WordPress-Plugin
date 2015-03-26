<?php

// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");

/**
 * Drupal Importer
 *
 */
class Drupal2WordPress_DrupalImporter {

    /**
     * Singleton Instance
     * @var Drupal2WordPressDrupalVersionAdapter
     */
    private static $instance;

    /**
     * @var Drupal2WordPressDrupalVersionAdapter
     */
    private $_drupalImporter;

    /**
     * Gets the singleton instance
     * @return Drupal2WordPress_DrupalImporter
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Returns the Drupal Importer Object
     * @return Drupal2WordPressDrupalVersionAdapter
     */
    public static function getImporter() {
        return self::getInstance()->_drupalImporter;
    }

    /**
     * Process the import
     */
    public static function process() {
        // Call init hook
        do_action('drupal2wp_init');
        // Get max Execution time
        $_maxExecutionTime = apply_filters('drupal2wp_max_execution_time', 1800); // 30 min default
        // Set max_execution_time
        ini_set('max_execution_time', $_maxExecutionTime);
        // Do import
        self::getImporter()
            ->_fixSettings()
            ->_truncateWP()
            ->_importTerms()
            ->_importContent()
            ->_importUsers()
            ->complete()
        ;

        flush_rewrite_rules();

        exit();
    }

    /**
     * Object constructor
     * @throws Exception
     */
    public function __construct() {
        if (empty($_SESSION['druaplDB'])) {
            throw new Exception( __('Drupal database details are missing', 'drupal2wp'));
        }
        // Set the Drupal version importer
        $this->_setDrupalVersionImporter();
    }

    /**
     * Sets the Drupal version importer
     * @throws Exception
     */
    private function _setDrupalVersionImporter() {
        if (isset($_SESSION['druaplDB']['version'])) {
            switch ($_SESSION['druaplDB']['version']) {
                case '7':
                    $this->_drupalImporter = new Drupal2WordPressDrupalImporter_7();
                    break;
                default:
                    throw new Exception( __('Invalid Drupal version for importing', 'drupal2wp'));
                    break;
            }
        } else {
            throw new Exception( __('Drupal version details are missing', 'drupal2wp'));
        }
    }

    /**
     * Check the database connection
     * @return array|bool
     */
    public function check() {
        return self::getImporter()->check();
    }

    /**
     * Returns the post types
     * @return array
     */
    public function getPostTypes() {
        return apply_filters('drupal2wp_drupal_post_types', self::getImporter()->getPostTypes(), $this->_drupalImporter);
//        return self::getImporter()->getPostTypes();
    }

    /**
     * Returns the roles
     * @return array
     */
    public function getRoles() {
        return apply_filters('drupal2wp_drupal_roles', self::getImporter()->getRoles(), $this->_drupalImporter);
//        return self::getImporter()->getRoles();
    }

    /**
     * Returns the terms
     * @return array
     */
    public function getTerms() {
        return apply_filters('drupal2wp_drupal_terms', self::getImporter()->getTerms(), $this->_drupalImporter);
//        return self::getImporter()->getTerms();
    }

}