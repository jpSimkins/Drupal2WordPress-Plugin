=== Plugin Name ===
Contributors: SoN9ne
Tags: Drupal importer, Drupal 2 WordPress, Drupal 2 WP, drupal2wp
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows for importing a Drupal installation into WordPress

== Description ==

Allows for importing a Drupal installation into WordPress


== Installation ==

1. Upload `drupal2wp directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress`



== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).

== Changelog ==

= 1.0 =
* Initial Plugin

= 1.4 =
* Fixed missing comment join for comment data
* Improved user import for large set of users
* Removed old code
* Optimized hook placements
* Added `drupal2wp_errors` and `drupal2wp_htaccess_rewrite_rules` filters to append to these with plugins

= 1.4.1 =
* Added htaccess rewrite hook and incremented version

= 1.4.2 =
* Fixed database issue with media import using incorrect column name.
* Removed hardcoded domain name in step 2 form for media.
* Fixed output not showing until complete due to PHP Session.
* Optimized code to prevent any PHP notices for undefined vars.
* Fixed empty database password from being denied access
* Fixed security issue with some localhosts that prevent local domains from access to import media
* Merged pull requests: 7, 13, 14

== Hooks List ==

More will be added to improve custom installations

*Importer Filters*
* drupal2wp_max_execution_time - Set the max_execution_time for the script
* drupal2wp_drupal_post_types - modify Drupal post_types (post types are an array of post_type as the key and the total as the value)
* drupal2wp_drupal_roles - modify Drupal roles
* drupal2wp_drupal_terms - modify Drupal terms
* drupal2wp_modify_post_type - modify post_type (1 arg - $post)
* drupal2wp_modify_post - modify post array (1 arg - $post)
* drupal2wp_errors - Add errors to the complete page (1 arg - $errors) append to array and return array
* drupal2wp_htaccess_rewrite_rules - Add rewrite rules to the complete page (1 arg - $rewrites) append to array and return array

*Importer Hooks*
* Initialization
** drupal2wp_init - On initiation of the plugin (1 arg - $importObj)
** drupal2wp_3rd_party_options - Allows for third party options to be selected in step1 (1 arg - $thirdPartyOptions) append to array and return array
* Settings
** drupal2wp_fix_settings - Runs after the initial fix settings is complete (1 arg - $importObj)
* Truncate
** drupal2wp_importer_truncate - Runs after the initial truncate is complete (1 arg - $importObj)
* Terms
** drupal2wp_importer_terms - Runs after the initial terms are imported (1 arg - $importObj)
* Content
** Filter: drupal2wp_modify_post_type is called before drupal2wp_modify_post
** Filter: drupal2wp_modify_post is called before post is saved
** drupal2wp_content_after_post_insert - Runs after the post has been inserted (2 args - $post, $importObj)
** drupal2wp_after_import_content - Runs after the content has been imported (1 arg - $importObj) [Note: you will have to associate media/taxonomies on your own here]
* Users
** drupal2wp_after_import_users - Runs after the users have been imported (1 arg - $importObj)
* Complete
** drupal2wp_importer_complete - Show complete output before errors

*Form Hooks*
* drupal2wp_import_terms_form
* drupal2wp_import_content_form
* drupal2wp_import_media_form
* drupal2wp_import_users_form
* drupal2wp_import_users_unchecked_form

