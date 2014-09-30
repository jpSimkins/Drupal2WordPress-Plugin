<?php

/**
 * Class Drupal2WordPress_DrupalImporter
 * Singleton
 */
class Drupal2WordPress_DrupalImporter {

    /**
     * Singleton Instance
     * @var Drupal2WordPress_DrupalImporter
     */
    private static $instance;

    /**
     * Druapl DB settings
     * @var array
     */
    private $dbSettings = array();

    /**
     * Import options
     * @var array
     */
    private $options = array();

    /**
     * Blog default category ID
     * @var int
     */
    private $blog_term_id;

    /**
     * Drupal DB instance
     * @var Drupal2WordPress_DrupalDB
     */
    private $_drupalDB;

    /**
     * Stores htaccess rewrite rules
     * @var array
     */
    private $_htaccessRewriteRules = array();

    public function __construct() {
        if (empty($_SESSION['druaplDB'])) {
            throw new Exception( __('Drupal database details are missing', 'drupal2wp'));
        }
        // Fetch data from Session
        $this->dbSettings = $_SESSION['druaplDB'];
        $this->options = $_SESSION['options'];
        // Connect to the DB
        $this->_drupalDB = new Drupal2WordPress_DrupalDB($this->dbSettings);
    }

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
     * Check the database connection
     * @return array|bool
     */
    public function check() {
        return $this->_drupalDB->check();
    }

    /**
     * Returns an array of available post types
     * @return array
     */
    public function getPostTypes() {
        $return = array();
        $drupalPostTypes = $this->_drupalDB->results("
            SELECT DISTINCT
              n.type,
              (
                SELECT
                  COUNT(n2.type) as total
                FROM ".$this->dbSettings['prefix']."node n2
                WHERE n2.type = n.type
              ) as total
            FROM ".$this->dbSettings['prefix']."node n
            ORDER BY n.type ASC
        ");
        foreach($drupalPostTypes as $dpt) {
            $return[$dpt['type']] = $dpt['total'];
        }
        return $return;
    }

    /**
     * Process the import
     */
    public static function process() {
        // Set a 30 minute import window
        ini_set('max_execution_time', 1800); // 30 minutes
        // Do import
        self::getInstance()
            ->_truncateWP()

            ->_importTerms()
            ->_importContent()
            ->_importUsers()

            ->_ouputHtaccessRedirects()

            ->_complete()
        ;
    }

    /**
     * Processes options to truncates the WordPress tables to allow for fresh content
     */
    private function _truncateWP() {
        global $wpdb;

        // Process options
        if (!empty($this->options['terms'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->comments}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->term_relationships}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->term_taxonomy}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->terms}");
        }

        if (!empty($this->options['content'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->postmeta}");
            $wpdb->query("TRUNCATE TABLE {$wpdb->posts}");
        }

        if (!empty($this->options['users'])) {
            // We keep the admin user
            $wpdb->query("DELETE FROM {$wpdb->users} WHERE ID > 1");
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE user_id > 1");
        }

        $wpdb->query("TRUNCATE TABLE ".$wpdb->links);

        print '<p><span style="color: green;">'.__('WordPress Tables Truncated', 'drupal2wp').'</span> - '.__('This is required as this ensures content IDs are synced across all systems', 'drupal2wp').'</p>';

        return $this; // maintain chaining
    }

    /**
     * Runs check and processes terms import
     */
    private function _importTerms() {
        if (!empty($this->options['terms'])) {
            $this->_importTags();
            $this->_importCategories();
        }
        return $this; // maintain chaining
    }

    /**
     * Runs check and processes content import
     */
    private function _importContent() {
        if (!empty($this->options['content'])) {
            $this->_importContentTypes();
            $this->_importTagsAndRelationships();
            $this->_updatePostType();
            $this->_updateURLALiasToSlug();
            $this->_importComments();
            // Update terms count
            if (!empty($this->options['terms'])) {
                $this->_updateTermsCount();
            }
        }
        return $this; // maintain chaining
    }

    /**
     * Imports user data
     * @return $this
     */
    private function _importUsers() {
        if (!empty($this->options['users'])) {
            $this->_importUsersData();
        }
        return $this; // maintain chaining
    }


    /**
     * Imports tags
     * @return $this
     */
    private function _importTags() {
        global $wpdb;

        // Imports all terms from Drupal
        $drupal_tags = $this->_drupalDB->results(
            "SELECT DISTINCT
                d.tid,
                d.name,
                REPLACE(LOWER(d.name), ' ', '-') AS slug
            FROM ".$this->dbSettings['prefix']."taxonomy_term_data d
            ORDER BY d.tid ASC
            ");
        if ($drupal_tags) {
            foreach($drupal_tags as $dt) {
                // Fix diacritic slug
//                $dt['slug'] = iconv("UTF-8", "ISO-8859-1//IGNORE", $dt['slug']);
                // Replace _ to -
                $dt['slug'] = str_replace('_', '-', $dt['slug']);
                // remove junk from slugs
                $dt['slug'] = $this->_convertToWordPressSlug($dt['slug']);
                // Insert the new term
                $wpdb->insert(
                    $wpdb->terms,
                    array(
                        'term_id' => $dt['tid'],
                        'name' => $dt['name'],
                        'slug' => $dt['slug']
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s'
                    )
                );
            }

            // Update WP term_taxonomy table
            $drupal_taxonomy = $this->_drupalDB->results(
                "SELECT DISTINCT
                    d.tid AS term_id,
                    'post_tag' AS post_tag,
                    d.description AS description,
                    h.parent AS parent
                FROM ".$this->dbSettings['prefix']."taxonomy_term_data d
                    INNER JOIN ".$this->dbSettings['prefix']."taxonomy_term_hierarchy h ON (d.tid = h.tid)
                ORDER BY 'term_id' ASC
                ");
            if ($drupal_taxonomy) {
                foreach($drupal_taxonomy as $dt) {
                    $wpdb->insert(
                        $wpdb->term_taxonomy,
                        array(
                            'term_id' => $dt['term_id'],
                            'taxonomy' => $dt['post_tag'],
                            'description' => $dt['description'],
                            'parent' => $dt['parent'],
                        ),
                        array(
                            '%d',
                            '%s',
                            '%s',
                            '%d'
                        )
                    );
                }
            }
            print '<p><span style="color: green;">'.__('Tags Imported', 'drupal2wp').'</span> - '.__('Also optimized slugs per WordPress specifications', 'drupal2wp').'</p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Tags Failed to Import', 'drupal2wp').'</span></p>';
        }

        $drupal_tags = $drupal_taxonomy = NULL;
        unset($drupal_tags, $drupal_taxonomy);
    }

    /**
     * Converts the Drupal alias to a WordPress slug
     * @param string $drupalAlias
     * @return string
     */
    private function _convertToWordPressSlug($drupalAlias) {
        // @todo use regex
        $drupalAlias = str_replace(
            array(
                '"',
                "'",
                '!',
                '.',
            ),
            '',
            $drupalAlias
        );
        return strtolower($drupalAlias);
    }

    /**
     * Creates a single category to assign content to
     * @return $this
     */
    private function _importCategories() {
        global $wpdb;

        $wpdb->insert(
            $wpdb->terms,
            array(
                'name' => !empty($this->options['default_category_name']) ? $this->options['default_category_name'] : 'Blog',
                'slug' => !empty($this->options['default_category_slug']) ? $this->options['default_category_slug'] : 'blog',
            ),
            array(
                '%s',
                '%s'
            )
        );

        // Then query to get this entry so we can attach it to content we create
        $this->blog_term_id = $wpdb->insert_id;
        if ($this->blog_term_id) {
            $wpdb->insert(
                $wpdb->term_taxonomy,
                array(
                    'term_id' => $this->blog_term_id,
                    'taxonomy' => 'category'
                ),
                array(
                    '%d',
                    '%s'
                )
            );
            if ($wpdb->insert_id) {
                print '<p><span style="color: green;">'.__('Categories Imported', 'drupal2wp').'</span></p>';
            } else {
                print '<p><span style="color: maroon;">'.__('Categories Failed to Import', 'drupal2wp').' - '.__('Attachment failed', 'drupal2wp').'</span></p>';
            }
        } else {
            print '<p><span style="color: maroon;">'.__('Categories Failed to Import', 'drupal2wp').' - '.__('All failed', 'drupal2wp').'</span></p>';
        }

    }

    /**
     * Updates the tag count for WordPress
     * @return $this
     */
    private function _updateTermsCount() {
        global $wpdb;

        //Count the total tags
        $result = $wpdb->query("
            UPDATE {$wpdb->term_taxonomy} tt
              SET `count` = (
                SELECT COUNT(tr.object_id)
                FROM {$wpdb->term_relationships} tr
                WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
            )
        ");
        if ($result !== false) {
            print '<p><span style="color: green;">'.__('Tag Count Updated', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Tag Count Failed to Update', 'drupal2wp').'</span></p>';
        }

    }

    /**
     * Imports content types that were selected in step 2
     * @return $this
     */
    private function _importContentTypes() {
        global $wpdb;

        // Get all post from Drupal and add it into WordPress
        $drupalContent = $this->_drupalDB->results("
          SELECT
              DISTINCT n.nid AS id,
              n.uid AS post_author,
              FROM_UNIXTIME(n.created) AS post_date,
              r.body_value AS post_content,
              n.title AS post_title,
              r.body_summary AS post_excerpt,
              n.type AS post_type,
              IF(n.status = 1, 'publish', 'draft') AS post_status
          FROM ".$this->dbSettings['prefix']."node n
            LEFT JOIN ".$this->dbSettings['prefix']."field_data_body r ON (r.entity_id = n.nid)
        ");
        if (!empty($drupalContent)) {
            foreach($drupalContent as $dp) {
                // Fetch the new post type
                $post_type = $this->_parsePostType($dp['post_type']);

                // @todo add hook here to adjust post_type

                if (!$post_type) {
                    continue;
                }

                // Fix author
                $dp['post_author'] = (empty($this->options['users']) && !empty($this->options['associate_content_user_id'])) ? $this->options['associate_content_user_id'] : $dp['post_author'];

                // Fix media paths
                // @todo make this work on post meta properly (perhaps move resources to it's own directory in the uploads folder
                $dp['post_content'] = str_replace('/sites/default/files/', '/wp-content/uploads/', $dp['post_content']);

                // @todo add hook here to adjust $dp (the post)

                // Insert into WordPress
                $wpdb->insert(
                    $wpdb->posts,
                    array(
                        'id' => $dp['id'],
                        'post_author' => $dp['post_author'],
                        'post_date' => $dp['post_date'],
                        'post_date_gmt' => $dp['post_date'],
                        'post_content' => $dp['post_content'],
                        'post_title' => $dp['post_title'],
                        'post_excerpt' => $dp['post_excerpt'],
                        'post_type' => $post_type,
                        'post_status' => $dp['post_status']
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    )
                );

                // Attach post_type post to the blog_term_id
                if ('post' === $post_type) {
                    $wpdb->insert(
                        $wpdb->term_relationships,
                        array(
                            'object_id' => $dp['id'],
                            'term_taxonomy_id' => $this->blog_term_id
                        ),
                        array(
                            '%d',
                            '%d'
                        )
                    );
                }

                // @todo add an array of errors to identify what content was not imported

                // @todo add hook here for after insert callbacks $dp (hand the post as the data may be useful)

            }


            print '<p><span style="color: green;">'.__('Content Imported', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Content Failed to Import', 'drupal2wp').' - '.__('All failed', 'drupal2wp').'</span></p>';
        }

    }

    /**
     * Return the proper post type for Drupal content
     * @param $drupalPostType
     * @return string
     */
    private function _parsePostType($drupalPostType) {
        if (isset($this->options['associations'][$drupalPostType])) {
            return $this->options['associations'][$drupalPostType];
        }
        return ''; // Force empty as default so we can skip this
    }

    /**
     * Imports tags and content relationships
     * @return $this
     */
    private function _importTagsAndRelationships() {
        global $wpdb;

        // Ensure we are importing this data
        if (empty($this->options['terms']) || empty($this->options['content'])) {
            return false;
        }

        //Add relationship for post and tags
        $drupal_post_tags = $this->_drupalDB->results("
          SELECT DISTINCT
            node.nid,
            taxonomy_term_data.tid
          FROM (".$this->dbSettings['prefix']."taxonomy_index taxonomy_index
            INNER JOIN ".$this->dbSettings['prefix']."taxonomy_term_data taxonomy_term_data ON (taxonomy_index.tid = taxonomy_term_data.tid))
            INNER JOIN ".$this->dbSettings['prefix']."node node ON (node.nid = taxonomy_index.nid)
        ");
        if (!empty($drupal_post_tags)) {
            foreach($drupal_post_tags as $dpt) {
                $wordpress_term_tax = $wpdb->get_var("
                  SELECT DISTINCT
                    term_taxonomy.term_taxonomy_id
                  FROM {$wpdb->term_taxonomy} term_taxonomy
                  WHERE (term_taxonomy.term_id = ".$dpt['tid'].")");

                // Attach all content to terms/tags
                $wpdb->insert(
                    $wpdb->term_relationships,
                    array(
                        'object_id' => $dpt['nid'],
                        'term_taxonomy_id' => $wordpress_term_tax
                    ),
                    array(
                        '%d',
                        '%d'
                    )
                );
            }

            print '<p><span style="color: green;">'.__('Tags & Content Relationships Imported', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Tags & Content Relationships Failed to Import', 'drupal2wp').' - '.__('All failed', 'drupal2wp').'</span></p>';
        }

    }

    /**
     * Update post type
     * @return $this
     */
    private function _updatePostType() {
        global $wpdb;

        //Update the post type for WordPress
        $result = $wpdb->update(
            $wpdb->posts,
            array(
                'post_type' => 'post'
            ),
            array( 'post_type' => !empty($this->options['default_category_slug']) ? $this->options['default_category_slug'] : 'blog' ),
            array( '%s' ),
            array( '%s' )
        );

        if ($result !== false) {
            print '<p><span style="color: green;">'.__('Posted Type Updated', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Posted Type Failed to Update', 'drupal2wp').'</span></p>';
        }

    }


    /**
     * Imports Drupal URL alias to WP slug
     * Follows WP recommendations for slugs
     */
    private function _updateURLALiasToSlug() {
        global $wpdb;

        //Get the url alias from drupal and use it for the Post Slug
        $drupal_url = $this->_drupalDB->results("
          SELECT
            url_alias.source,
            url_alias.alias
          FROM ".$this->dbSettings['prefix']."url_alias
          WHERE (url_alias.source LIKE 'node%')
        ");
        foreach($drupal_url as $du) {
            // Fix slug
            $newSlug = str_replace('content/', '', $du['alias']);
            // Fix diacritic string
//            $newSlug = iconv("UTF-8", "ISO-8859-1//IGNORE", $newSlug);
            // Add to rewrite rules if different
            if ($newSlug != $du['alias']) {
                $this->_htaccessRewriteRules[$du['alias']] = $newSlug;
            }
            // Add to WP
            $wpdb->update(
                $wpdb->posts,
                array(
                    'post_name' => $newSlug
                ),
                array( 'ID' => str_replace('node/','',$du['source']) ),
                array( '%s' ),
                array( '%d' )
            );
        }
        print '<p><span style="color: green;">'.__('URL Alias to Slug Updated', 'drupal2wp').'</span></p>';
    }

    /**
     * Imports comments for content
     * This requires content to be selected to process
     * @return $this
     */
    private function _importComments() {
        if (!empty($this->options['comments'])) {
            $this->_importCommentsData();
            $this->_importCommentsCount();
        }
        return $this; // maintain chaining
    }

    /**
     * Imports comments data to WP
     */
    private function _importCommentsData() {
        $this->_processCommentsLevel();
        print '<p><span style="color: green;">'.__('Comments Imported', 'drupal2wp').'</span></p>';
    }

    /**
     * Walks the comments to a fixed max level (11)
     * @param int $commentID
     * @param int $level
     */
    private function _processCommentsLevel($commentID=0, $level=0) {
        global $wpdb;
        // Stop at 11 levels deep
        if ($level > 11) {
            return;
        }
        // Fetch comment ID level of comments
        $dComments = $this->_drupalDB->results(
            "SELECT DISTINCT
                c.cid AS comment_ID,
                c.nid AS comment_post_ID,
                c.uid AS user_id,
                c.name AS comment_author,
                c.mail AS comment_author_email,
                c.homepage AS comment_author_url,
                c.hostname AS comment_author_IP,
                FROM_UNIXTIME(c.created) AS comment_date
            FROM ".$this->dbSettings['prefix']."comment c
            WHERE c.pid = {$commentID}
                AND c.status = 1
            ");
        if (!empty($dComments)) {
            // Set flag to check importing users (this way we can associate comments to the users)
            $importingUsers = !empty($this->options['users']);
            foreach ($dComments as $comment) {
                // Add comment to WP
                $wpdb->insert(
                    $wpdb->comments,
                    array(
                        'comment_ID' => $comment['comment_ID'],
                        'comment_post_ID' => $comment['comment_post_ID'],
                        'comment_author' => $comment['comment_author'],
                        'comment_author_email' => $comment['comment_author_email'],
                        'comment_author_url' => $comment['comment_author_url'],
                        'comment_author_IP' => $comment['comment_author_IP'],
                        'comment_date' => $comment['comment_date'],
                        'comment_date_gmt' => $comment['comment_date'],
                        'comment_content' => $comment['comment_content'],
                        'comment_approved' => '1',
                        'comment_parent' => $commentID,
                        'user_id' => $importingUsers ? $comment['user_id'] : 0
                    ),
                    array(
                        '%d',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d'
                    )
                );
                // Process any nested comments
                $this->_processCommentsLevel($comment['comment_ID'], ++$level);
            }
        }
    }

    /**
     * Updates comment count for content
     */
    private function _importCommentsCount() {
        global $wpdb;
        //Update Comment Counts in Wordpress
        $result = $wpdb->query("
          UPDATE {$wpdb->posts}
            SET comment_count = (
              SELECT COUNT(comment_post_id)
              FROM {$wpdb->comments}
              WHERE {$wpdb->posts}.id = {$wpdb->comments}.comment_post_id
            )
        ");
        if ($result !== false) {
            print '<p><span style="color: green;">'.__('Content Comments Count Updated', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Content Comments Count Failed to Update', 'drupal2wp').'</span></p>';
        }
    }

    /**
     * Imports essential user data to WP
     * Admin is skipped and uses the WP admin instead
     * Passwords are left blank, so users will need to reset their passwords
     */
    private function _importUsersData() {
        global $wpdb;

        $dUsers = $this->_drupalDB->results(
            "SELECT
                u.uid,
                u.name,
                u.pass,
                u.mail,
                FROM_UNIXTIME(u.created) AS created,
                u.access
            FROM ".$this->dbSettings['prefix']."users u
            WHERE u.uid != 1
                AND u.uid != 0
            ");
        if (!empty($dUsers)) {
            foreach($dUsers as $dUser) {
                // Add user, make email login. Empty password to force lost password
                $wpdb->insert(
                    $wpdb->users,
                    array(
                        'ID' => $dUser['uid'],
                        'user_login' => $dUser['mail'],
                        'user_pass' => '',
                        'user_nicename' => $dUser['name'],
                        'user_email' => $dUser['mail'],
                        'user_registered' => $dUser['created'],
                        'display_name' => $dUser['name']
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    )
                );
                // Add user meta
                if ($wpdb->insert_id !== false) {
                    $wpdb->query($wpdb->prepare(
                        "
                        INSERT INTO {$wpdb->usermeta}
                            (`user_id`, `meta_key`, `meta_value`)
                        VALUES
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s'),
                            ('%s','%s','%s')
                        ",
                        array(
                            $dUser['uid'], 'wp_capabilities', 'a:1:{s:10:"subscriber";b:1;}',
                            $dUser['uid'], 'nickname', $dUser['name'],
                            $dUser['uid'], 'first_name', '',
                            $dUser['uid'], 'last_name', '',
                            $dUser['uid'], 'description', '',
                            $dUser['uid'], 'rich_editing', 'true',
                            $dUser['uid'], 'comment_shortcuts', 'false',
                            $dUser['uid'], 'admin_color', 'fresh',
                            $dUser['uid'], 'use_ssl', '0',
                            $dUser['uid'], 'show_admin_bar_front', 'false'
                        )
                    ));
                }
            }
            print '<p><span style="color: green;">'.__('Users Imported', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Users failed to Import', 'drupal2wp').'</span></p>';
        }
    }


















    /**
     * Saves image to the media manager
     * use: set_post_thumbnail($post_id, $attached_id); to attach the post thumbnail
     * @param int $post_id
     * @param string $file
     * @param string $desc
     * @return int false on error
     */
    function addFileToMediaManager($post_id, $file, $desc = '') {
        // Download file to temp location
        $tmp = download_url($file);
        // Set variables for storage
        // fix file filename for query strings
        $file_array = array();
        preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $file, $matches);
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $tmp;
        // If error storing temporarily, unlink
        if (is_wp_error($tmp)) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';
        }
        // do the validation and storage stuff
        $attached_id = media_handle_sideload($file_array, $post_id, $desc);
        // If error storing permanently, unlink
        if (is_wp_error($attached_id)) {
            @unlink($file_array['tmp_name']);
            wp_delete_post($post_id, true);
            return false;
        }
        return $attached_id;
    }













    /**
     * Shows the htaccess edits for rewrite rules
     */
    private function _ouputHtaccessRedirects() {
        if (!empty($this->_htaccessRewriteRules)) {
            echo '<hr/>';
            echo '<h3>'.__('Add this to your .htaccess file to have proper 301 redirects', 'drupal2wp').'</h3>';
            echo '<textarea class="large-text code" readonly="readonly" style="width: 98%; min-height: 200px;">';
            foreach($this->_htaccessRewriteRules as $oldURI=>$newURI) {
                echo 'RewriteRule '.$oldURI.' '.$newURI.' [R=301,L]'.PHP_EOL;
            }
            echo '</textarea>';
        } else {
            print '<p>'.__('No rewrites are necessary.', 'drupal2wp').'</p>';
        }
        return $this; // maintain chaining
    }

    /**
     * Finalizes the import process
     */
    private function _complete() {
        // Remove Session data
        unset($_SESSION['druaplDB'], $_SESSION['options']);
        // Output message
        print '<p><span style="color: green; font-size: 2em;">'.__('Import Complete!', 'drupal2wp').'</span></p>';
        print '<p>'.__('Drupal pages typically have tags associated to them; WordPress does not. If you want to add tag support to pages, enable the second plugin with this one: Drupal 2 WordPress Page Tags Support.', 'drupal2wp').'</p>';
        print '<p>'.__('For security reasons, please disable and remove this plugin as the import process is complete.', 'drupal2wp').'</p>';
    }

}