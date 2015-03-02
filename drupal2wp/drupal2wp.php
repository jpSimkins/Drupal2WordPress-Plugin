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
// initialize the plugin
add_action('init', 'drupal2wp_init', 50); // set to 50 to make sure all other plugins initialize first

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

function debug($data, $return=false, $echo=false, $showCalledFrom=true) {
    if (!$return) {
        wp_die( _debugCalledFrom(1, __FUNCTION__, $showCalledFrom).'<pre>'.
            ((is_array($data) || is_object($data)) ? print_r($data, true) : $data).
            '</pre>');
    } else {
        if ($echo) {
            echo _debugCalledFrom(1, __FUNCTION__, $showCalledFrom).'<pre>'.
                ((is_array($data) || is_object($data)) ? print_r($data, true) : $data).
                '</pre>'.PHP_EOL;
        } else {
            return _debugCalledFrom(1, __FUNCTION__, $showCalledFrom).'<pre>'.
            ((is_array($data) || is_object($data)) ? print_r($data, true) : $data).
            '</pre>'.PHP_EOL;
        }
    }
}

/**
 * Locate where this was called
 * used to find the line and file for debug
 * @param int $level - Levels up from call
 * @param string $methodName - use __FUNCTION__ to show the current method name (this overrides this method name)
 * @param bool $showCalledFrom
 * @return string
 */
function _debugCalledFrom( $level = 1, $methodName=__FUNCTION__, $showCalledFrom = true ) {
    if ($showCalledFrom) {
        // Set vars
        $parentLevel = $level+1;
        $trace = debug_backtrace();
        $file   = defined('SITE_ABSPATH') ? str_replace(SITE_ABSPATH, '', $trace[$level]['file']) : str_replace(ABSPATH, '', $trace[$level]['file']);
        $line   = $trace[$level]['line'];
        $object = !empty($trace[$level]['object']) ? $trace[$level]['object'] : '';

        // Load proper data
        if (is_object($object)) {
            $class = get_class($object);
            $class = !empty($trace[$level]['class']) ? $trace[$level]['class'] : '';
            $type = !empty($trace[$level]['type']) ? $trace[$level]['type'] : '';
            $function = !empty($trace[$level]['function']) ? $trace[$level]['function'] : '';
        } else {
            $class = !empty($trace[$parentLevel]['class']) ? $trace[$parentLevel]['class'] : '';
            $type = !empty($trace[$parentLevel]['type']) ? $trace[$parentLevel]['type'] : '';
            $function = !empty($trace[$parentLevel]['function']) ? $trace[$parentLevel]['function'] : '';
        }

        // Return proper string
        if ($class) {
            return '<hr />'.sprintf( '%s - Called at: line %d in %s%s%s [%s]', $methodName, $line, $class, $type, $function, $file).'<hr />';
        } else {
            return '<hr />'.sprintf( '%s - Called at: line %d [%s]', $methodName, $line, $file).'<hr />';
        }
    }

}