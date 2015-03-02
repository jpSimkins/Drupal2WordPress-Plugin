<?php

// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");

/**
 * Interface Drupal2WordPressDrupalVersionAdapterInterface
 * Interface for the Drupal Version Importer
 */
interface Drupal2WordPressDrupalVersionAdapterInterface {

    /**
     * Main method to get Post Types from Drupal
     * @return array
     */
    public function getPostTypes();

    /**
     * Main method to get User Roles from Drupal
     * @return array
     */
    public function getRoles();

    /**
     * Main method to get Terms from Drupal
     * @return array
     */
    public function getTerms();

    /**
     * Imports Terms from Drupal to WordPress
     * @return $this
     */
    public function importTerms();

    /**
     * Imports Content from Drupal to WordPress
     * @return $this
     */
    public function importContent();

    /**
     * Save post array to WordPress
     * Needed to allow other plugins to use this instead of them guessing
     * @return false|int False on failure, post_id on success
     */
    public function savePost2WP($post);

    /**
     * Imports Users from Drupal to WordPress
     * @return $this
     */
    public function importUsers();

    /**
     * Updates the terms count for the posts
     */
    public function updateTermsCount();

}
