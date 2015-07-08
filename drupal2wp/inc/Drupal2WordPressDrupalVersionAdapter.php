<?php

// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");

/**
 * Class Drupal2WordPressDrupalVersionAdapter
 */
abstract class Drupal2WordPressDrupalVersionAdapter implements Drupal2WordPressDrupalVersionAdapterInterface {

    /**
     * Singleton Instance
     * @var Drupal2WordPressDrupalVersionAdapter
     */
    private static $instance;

    /**
     * Druapl DB settings
     * @var array
     */
    public $dbSettings = array();

    /**
     * Import options
     * @var array
     */
    public $options = array();

    /**
     * Blog default category ID
     * @var int
     */
    protected $blog_term_id;

    /**
     * Drupal DB instance
     * @var Drupal2WordPress_DrupalDB
     */
    protected $_drupalDB;

    /**
     * Version of Drupal
     * @var int
     */
    protected $_drupalVersion;

    /**
     * Appends to duplicate slugs to fix taxonomy imports
     * @var string
     */
    protected $_duplicateSlugSuffix = '-DUP';

    /**
     * Stores htaccess rewrite rules
     * @var array
     */
    protected $_htaccessRewriteRules = array();

    /**
     * WordPress User Roles
     * @var array
     */
    protected $_wpUserRoles = array();

    /**
     * Stores any errors
     * @var array
     */
    public $errors = array();

    /**
     * Stores associations for duplicate taxonomies
     * @var array
     */
    public $duplicateTaxonomyAssociations = array();

    /**
     * WordPress default comment status
     * @var string open|closed
     */
    protected $default_comment_status = 'open';

    /**
     * WordPress default ping status
     * @var string open|closed
     */
    protected $default_ping_status = 'open';

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
     * Constructs the importer
     * @throws Exception
     */
    public function __construct() {
        if (empty($_SESSION['druaplDB'])) {
            throw new Exception( __('Drupal database details are missing', 'drupal2wp'));
        }
        // Fetch data from Session
        $this->dbSettings = $_SESSION['druaplDB'];
        $this->options = $_SESSION['options'];
        $this->_drupalVersion = $this->dbSettings['version'];
        $this->default_comment_status = get_option('default_comment_status', $this->default_comment_status);
        $this->default_ping_status = get_option('default_ping_status', $this->default_ping_status);

        // Include files necessary for media import
        require_once(ABSPATH.'wp-admin/includes/file.php');
        require_once(ABSPATH.'wp-admin/includes/media.php');
        require_once(ABSPATH.'wp-admin/includes/image.php');

        // Connect to the DB
        $this->_drupalDB = new Drupal2WordPress_DrupalDB($this->dbSettings);
        // Do init action
        do_action('drupal2wp_importer_init', $this);
    }

    /**
     * Check the database connection
     * @return array|bool
     */
    public function check() {
        return $this->_drupalDB->check();
    }

    /**
     * Fix settings
     */
    public function _fixSettings() {
        if (!empty($this->options['files_location'])) {
            $this->options['files_location'] = rtrim($this->options['files_location'], '/').'/';
        }
        if (!empty($this->options['wp_drupal_assets_url'])) {
            $this->options['wp_drupal_assets_url'] = rtrim($this->options['wp_drupal_assets_url'], '/').'/';
        }
        do_action('drupal2wp_fix_settings', $this);
        return $this; // maintain chaining
    }

    /**
     * Processes options to truncates the WordPress tables to allow for fresh content
     */
    public function _truncateWP() {
        global $wpdb;

        // Process options
        if (!empty($this->options['terms'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->term_relationships}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->term_taxonomy}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->terms}");
        }

        if (!empty($this->options['content'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->comments}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->postmeta}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->posts}");
        }

        if (!empty($this->options['users'])) {
            // We keep the admin user
            $wpdb->query("DELETE FROM {$wpdb->users} WHERE ID > 1");
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE user_id > 1");
        }

//        $wpdb->query("TRUNCATE TABLE ".$wpdb->links);

        do_action('drupal2wp_importer_truncate', $this);

        print '<p><span style="color: green;">'.__('WordPress Tables Truncated', 'drupal2wp').'</span> - '.__('This ensures content/user IDs are synced across all systems', 'drupal2wp').'</p>';
        ob_flush(); flush(); // Output

        return $this; // maintain chaining
    }

    /**
     * Runs check and processes terms import
     */
    public function _importTerms() {
        if (!empty($this->options['terms'])) {
            $this->importTerms();
        }
        do_action('drupal2wp_after_import_terms', $this);
        return $this; // maintain chaining
    }

    /**
     * Runs check and processes content import
     */
    public function _importContent() {
        if (!empty($this->options['content'])) {
            $this->importContent();
            // Add to action
            add_action('drupal2wp_after_import_content', array($this, 'fixContentMedia'), 50); // Do this after the content 50 forces it to be last
        }
        do_action('drupal2wp_after_import_content', $this);
        return $this; // maintain chaining
    }

    /**
     * Imports user data
     * @return $this
     */
    public function _importUsers() {
        if (!empty($this->options['users'])) {
            $this->importUsers();
        }
        do_action('drupal2wp_after_import_users', $this);
        return $this; // maintain chaining
    }

    /**
     * Return the proper term type for Drupal taxonomies
     * @param string $drupalTaxonomy
     * @return string
     */
    public function parseTerms($drupalTaxonomy) {
        if (isset($this->options['term_associations'][$drupalTaxonomy])) {
            return $this->options['term_associations'][$drupalTaxonomy];
        }
        return ''; // Force empty as default so we can skip this
    }

    /**
     * Return the proper post type for content title associations
     * @param string $contentTitle
     * @return string
     */
    public function parseContentTitleForPostType(&$contentTitle) {
        if (isset($this->options['content_title_associations'])) {
            foreach($this->options['content_title_associations'] as $titleAssociationData) {
                // Check if title contains string
                if ($contentTitle && $titleAssociationData['title'] && strpos( strtolower($contentTitle), strtolower($titleAssociationData['title']) ) !== false) {
                    // See if we are altering the title
                    if (!empty($titleAssociationData['remove_from_title'])) {
                        $contentTitle = trim( str_ireplace($titleAssociationData['title'], '', $contentTitle) );
                    } else if (!empty($titleAssociationData['title_replace_with'])) {
                        $contentTitle = trim( str_ireplace($titleAssociationData['title'], $titleAssociationData['title_replace_with'], $contentTitle) );
                    }
                    // Return post type
                    return $titleAssociationData['assoc'];
                }
            }
        }
        return ''; // Force empty as default so we can skip this
    }

    /**
     * Return the proper post type for Drupal content
     * @param string $drupalPostType
     * @return string
     */
    public function parsePostType($drupalPostType) {
        if (isset($this->options['content_associations'][$drupalPostType])) {
            return $this->options['content_associations'][$drupalPostType];
        }
        return ''; // Force empty as default so we can skip this
    }

    /**
     * Converts the Drupal alias to a WordPress slug
     * @param string $drupalAlias
     * @return string
     */
    public function convertToWordPressSlug($drupalAlias) {
        // Use WordPress function to do this
        $drupalAlias = sanitize_title( $drupalAlias);
        return strtolower($drupalAlias);
    }

    /**
     * Sanitize alias (or tag slug)
     * Not to be confused with _convertToWordPressSlug
     * This cleans the string to attempt to recreate the actual alias used in Drupal
     * @param string $drupalAlias
     * @return string
     */
    public function sanitizeAlias($drupalAlias) {
        // @todo use regex
        // Fix diacritic slug
        $drupalAlias = str_replace('_', '-', $drupalAlias);
        $drupalAlias = str_replace(
            array(
                '"',
                "'",
                '!',
                '.',
                '&nbsp;',
                " ",
                "\x0B",
                "\0",
                "\r",
                "\n",
                "\t"
            ),
            '',
            $drupalAlias
        );
        $drupalAlias = preg_replace('/\s+/', '', $drupalAlias);
        return strtolower($drupalAlias);
    }

    /**
     * Fixes the assets URL for the content
     * @param string $content
     * @return string
     */
    public function fixAssetPathing($content) {
        if (empty($this->options['files_location']) && empty($this->options['wp_drupal_assets_url'])) {
            $content = str_replace($this->options['files_location'], $this->options['wp_drupal_assets_url'], $content);
        }
        return $content;
    }


    /**
     * Fixes post content media
     */
    public function fixContentMedia() {
        global $wpdb;

        // Fetch our posts and fix our media
        $posts = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'revision');");
        foreach ($posts as $post) {
            // Are we fixing any content paths?
            if (!empty($this->options['content_media_import'])) {
                // Process content media import
                $_contentImgTags = array();
                if (!empty($this->options['content_media_import'])) {
                    preg_match_all('/<img[^>]+>/i', $post->post_content, $_contentImgTags);
                }
                // Fix content import media
                if (!empty($_contentImgTags[0])) {
                    $i = 0;
                    foreach ($_contentImgTags[0] as $img_tag) {
                        $imgs = array();
                        preg_match_all('/(alt|title|src)="([^"]*)"/i', $img_tag, $imgs[$img_tag]);
                        if (!empty($imgs)) {
                            foreach ($imgs as $img => $data) {
                                $_key = array_search('src', $data[1]);
                                if (false !== $_key && !empty($data[2][$_key])) {
                                    $originalSrc = $data[2][$_key];
                                    // Make sure we want to replace this
                                    foreach ($this->options['content_media_import'] as $url) {
                                        if ( !empty($url) && 0 === strpos($originalSrc, $url)) {
                                            // Check if original src is relative (use tmp so we don't alter the original src for replace later)
                                            $tmp = $originalSrc;
                                            if (!empty($this->options['drupal_url'])) {
                                                $parsed = parse_url($originalSrc);
                                                if (empty($parsed['scheme'])) {
                                                    $tmp = rtrim($this->options['drupal_url'], '/') . '/' . ltrim($originalSrc, '/');
                                                }
                                            }
                                            // Get attachment
                                            $_attachmentID = self::addFileToMediaManager($post->ID, $tmp, '', '', $this->errors['content_media']);
                                            // Update to the new attachment src
                                            if (!is_wp_error($_attachmentID)) {
                                                if (!has_post_thumbnail($post->ID) && 1 === ++$i) {
                                                    // This is in case there is no featured thumbnail, if it has one it will replace this
                                                    set_post_thumbnail($post->ID, $_attachmentID);
                                                    // Remove from content (perhaps first occurrence only?)
                                                    $post->post_content = str_replace($img_tag, '', $post->post_content);
                                                } else {
                                                    // Replace image path
                                                    $newSrc = wp_get_attachment_url($_attachmentID);
                                                    $post->post_content = str_replace($originalSrc, $newSrc, $post->post_content);
                                                }
                                            } else {
                                                // Failed to import, now we are just fixing the path
                                                if (empty($this->options['files_location']) && empty($this->options['wp_drupal_assets_url'])) {
                                                    $post->post_content = str_replace($originalSrc, $this->options['wp_drupal_assets_url'], $post->post_content);
                                                    $this->errors['content_media'][] = sprintf( __('Failed to import content media file: %s. Updated path to point to %s. - %s', 'drupal2wp'), $originalSrc, $this->options['wp_drupal_assets_url'], $_attachmentID->get_error_message());
                                                } else {
                                                    $this->errors['content_media'][] = sprintf( __('Failed to import content media file: %s - %s', 'drupal2wp'), $originalSrc, $_attachmentID->get_error_message());
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // Fix media paths (fail-safe if imports are missed)
            $post->post_content = $this->fixAssetPathing($post->post_content);
            // Update the post into the database (do not use wp_update_post as this adds revisions)
            $result = $wpdb->update(
                $wpdb->posts,
                array(
                    'post_content' => $post->post_content,	// string
                ),
                array( 'ID' => $post->ID ),
                array(
                    '%s'	// post_content
                ),
                array( '%d' )
            );
            if (false === $result) {
                $this->errors['content_media'][] = sprintf( __('Failed to update content for post ID: %d', 'drupal2wp'), $post->ID);
            }
        }
        return $this; // maintain chaining
    }

    /**
     * Saves image to the media manager
     * use: set_post_thumbnail($post_id, $attached_id); to attach the post thumbnail
     * @param int $post_id
     * @param string $file
     * @param string $desc
     * @param string $originalFileName
     * @return int|false on error
     */
    public static function addFileToMediaManager($post_id, $file, $desc = '', $originalFileName='', &$error=array()) {
        // Fix drupal file names
        $tmpFileNameArray = explode('/', $file);
        $tmpFileName = array_pop($tmpFileNameArray);
        // Attempt first with most likely file path
        $tmpFileNameClean = rawurlencode($tmpFileName);
        $file = implode('/', $tmpFileNameArray).'/'.$tmpFileNameClean;
        $file_array = self::downloadFile($file);
        if (is_wp_error($file_array)) {
            // Try again but this time remove whitespaces
            $tmpFileNameClean = str_replace(' ', '', $tmpFileName);
            $file = implode('/', $tmpFileNameArray).'/'.$tmpFileNameClean;
            $file_array = self::downloadFile($file);
            if (is_wp_error($file_array)) {
                // Last attempt with original file name
                $file = implode('/', $tmpFileNameArray).'/'.$originalFileName;
                $file_array = self::downloadFile($file);
                if (is_wp_error($file_array)) {
                    return $file_array;
                }
            }
        }
        // Do the validation and storage stuff
        $attachmentID = media_handle_sideload($file_array, $post_id, $desc);
        // If error storing permanently, unlink
        if (is_wp_error($attachmentID)) {
            @unlink($file_array['tmp_name']);
            $error[] = sprintf( __('Failed to load media file: %s - %s', 'drupal2wp'), $file, $attachmentID->get_error_message());
            return false;
        }
        // @todo check to see if I need to unlink the tmp file
        return $attachmentID;
    }

    /**
     * Attempts to download a file
     * @param string $file
     * @return array|WP_Error
     */
    public static function downloadFile($file) {
        $tmp = download_url($file);
        if (!is_wp_error($tmp)) {
            if (preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png|pdf)/i', $file, $matches)) {
                return array(
                    'name' => basename($matches[0]),
                    'tmp_name' => $tmp
                );
            } else {
                // Attempt to get the extension
                $_tmpExtension = pathinfo($file, PATHINFO_EXTENSION);
                $tmp = new WP_Error('drupal2wp_invalid_extension', sprintf( __('Invalid extension (%s) for media file.', 'drupal2wp'), $_tmpExtension));
            }
        }
        return $tmp;
    }

    /**
     * Process the Drupal user role to a WordPress user role
     * This takes the multiple user roles from Drupal and assigns the highest user role for WordPress roles
     * @param $drupalRoleID
     * @return string
     */
    public function processUserRole($drupalRoleID) {
        $tmpRoles = explode(',', $drupalRoleID);
        // Find the user roles this user is associated to
        $userRoles = array();
        foreach($tmpRoles as $tmpRole) {
            if (isset($this->options['user_roles_associations'][$tmpRole])) {
                $userRoles[] = $this->options['user_roles_associations'][$tmpRole];
            }
        }
        // Now find the highest role
        if (!empty($userRoles)) {
            $userRole = '';
            $_currentRoleCount = 0;
            // Check the cap count (this assumes the higher capability count count is a higher user role)
            foreach($userRoles as $role) {
                $capCount = count($this->_wpUserRoles[$role]['capabilities']);
                if ($capCount > $_currentRoleCount) {
                    $_currentRoleCount = $capCount;
                    $userRole = $role;
                }
            }
            return $userRole;
        }
        return ''; // Force empty as default so we can skip this
    }

    /**
     * Finalizes the import process
     */
    public function complete() {
        // Combine errors
        $this->errors = apply_filters('drupal2wp_errors', $this->errors);
        // Output message
        echo '<hr/>';
        echo '<p><span style="color: green; font-size: 2em;">'.__('Import Complete!', 'drupal2wp').'</span></p>';
        echo '<p>'.__('Some Drupal setups have pages with tags associated to them; WordPress does not. If you want to add tag support to pages and not using the Custom Content Type Manager plugin, enable the second plugin: Drupal 2 WordPress Page Tags Support.', 'drupal2wp').'</p>';
        echo '<p>'.__('For security reasons, please disable and/or remove this plugin as the import process is complete.', 'drupal2wp').'</p>';
        $this->outputHtaccessRedirects();
        // Do complete callback before output of errors
        do_action('drupal2wp_importer_complete', $this);
        // Show errors
        if (!empty($this->errors)) {
            echo '<hr/>';
            $eCount = $this->countErrors();
            echo '<h3 style="margin: 8px 0;">'.sprintf( _n( '%d Import Issue', '%d Import Issues', $eCount, 'drupal2wp' ), $eCount ).'</h3>';
            echo '<p class="description">'.__('These issues may be minimal to the import process. Please verify the issues.', 'drupal2wp').'</p>';
            echo '<ul>';
            foreach($this->errors as $errorID=>$errors) {
                if (is_array($errors)) {
                    $errorTotal = count($errors);
                    echo '<li>';
                    echo '<h4 style="margin: 8px 0;">'.ucwords( str_replace('_', ' ', $errorID) ).' - ('.sprintf( _n('%d Error Found', '%d Errors Found', $errorTotal, 'drupal2wp'), $errorTotal).')</h4>';
                    echo '<ul>';
                    foreach($errors as $err) {
                        echo '<li>'.$err.'</li>';
                    }
                    echo '</ul>';
                    echo '</li>';
                } else {
                    echo '<li>'.$errors.'</li>';
                }
            }
            echo '</ul>';
            ob_flush(); flush(); // Output
            // Flush the rewrite rules to use correct permalinks
            flush_rewrite_rules(true);
            // Remove Session data
            unset($_SESSION['druaplDB'], $_SESSION['options']);
        }
    }

    /**
     * Shows the .htaccess edits for rewrite rules
     */
    public function outputHtaccessRedirects() {
        // Combine htaccess rewrites
        $this->_htaccessRewriteRules = apply_filters('drupal2wp_htaccess_rewrite_rules', $this->_htaccessRewriteRules);
        if (!empty($this->_htaccessRewriteRules)) {
            echo '<hr/>';
            echo '<h3>'.sprintf( __('Add this to your .htaccess file to have proper 301 redirects (%d total)', 'drupal2wp'), count($this->_htaccessRewriteRules)).'</h3>';
            echo '<p class="description">'.__('This is only the rewrite rules, you will have to make sure you have rewrite enabled.', 'drupal2wp').'</p>';
            echo '<textarea class="large-text code" readonly="readonly" style="width: 98%; min-height: 200px;">';
            foreach($this->_htaccessRewriteRules as $oldURI=>$newURI) {
                echo 'RewriteRule '.$oldURI.' '.$newURI.' [R=301,L]'.PHP_EOL;
            }
            echo '</textarea>';
        } else {
            echo '<p>'.__('No rewrites are necessary.', 'drupal2wp').'</p>';
        }
        ob_flush(); flush(); // Output
        return $this; // maintain chaining
    }

    /**
     * Returns a count of the errors
     * @return int
     */
    public function countErrors() {
        if (!empty($this->errors)) {
            $i=0;
            foreach($this->errors as $errorGroup) {
                $i += count($errorGroup);
            }
            return $i;
        }
        return 0;
    }

    /**
     * Returns the DB object
     * @return Drupal2WordPress_DrupalDB
     */
    public function drupalDB() {
        return $this->_drupalDB;
    }

    public function getDrupalVersion() {
        return $this->_drupalVersion;
    }

}
