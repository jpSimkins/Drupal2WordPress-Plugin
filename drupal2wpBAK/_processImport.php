<?php

/**
 * Tells WordPress to load WordPress without the theme.
 * @var bool
 */
define('WP_USE_THEMES', false);

// Loads the WordPress Environment
$tmpRootPath = dirname(dirname(dirname(dirname( __FILE__ ))));
if (file_exists($tmpRootPath . '/wp-blog-header.php')) {
    require_once( $tmpRootPath . '/wp-blog-header.php' );
} else {
    require_once( $tmpRootPath . '/wordpress/wp-blog-header.php' );
}

if (isset($_GET['_wpnonce']) && check_admin_referer( 'drupal2wp_import_iframe')) {
    define( 'IFRAME_REQUEST', true );
    session_start();

    // Load the plugin
    require_once( dirname(__FILE__).'/drupal2wp.php' );

    Drupal2WordPress_DrupalImporter::process();

} else {
    die( _e('Invalid request', 'drupal2wp') );
}