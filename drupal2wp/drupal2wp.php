<?php
/**
 * Plugin Name: Drupal 2 WordPress
 * Description: Allows for importing a Drupal installation into WordPress
 * Version: 0.0.1
 * Author: Jeremy Simkins <jpSimkins@gmail.com>
 * Author URI: http://jeremysimkins.com
 * License: GPL2
 */

/*  Copyright 2014  Jeremy Simkins  (email : jpSimkins at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");
// Define constants
define('DRUPAL2WP_FILE', __FILE__);
define('DRUPAL2WP_VIEWS_DIRECTORY', plugin_dir_path(DRUPAL2WP_FILE).'views/');
define('DRUPAL2WP_DIRECTORY_URL', plugin_dir_url(DRUPAL2WP_FILE));
// Register Autoloader
spl_autoload_register('drupal2wp_autoloader');
// Add init action
add_action('plugins_loaded', 'drupal2wp_init');

/**
 * Autoloads the plugin resources
 * @param $class
 */
function drupal2wp_autoloader($class) {
    $tmpFile = plugin_dir_path(__FILE__) . 'inc/' . $class . '.php';
    if (file_exists($tmpFile)) {
        require_once($tmpFile);
    }
}

/**
 * Initiate the plugin object
 */
function drupal2wp_init() {
    global $drupal2wp;
    $drupal2wp = new Drupal2WordPress();
}
