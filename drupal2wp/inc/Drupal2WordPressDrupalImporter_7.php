<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 10/24/2014
 * Time: 3:12 PM
 */

class Drupal2WordPressDrupalImporter_7 extends Drupal2WordPressDrupalVersionAdapter {

    /**
     * Returns an array of available Drupal post types
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
     * Returns an array of available Drupal roles
     * @return array
     */
    public function getRoles() {
        $return = array();
        $drupalPostTypes = $this->_drupalDB->results("
            SELECT DISTINCT
              r.rid,
              r.name
            FROM ".$this->dbSettings['prefix']."role r
            ORDER BY r.weight ASC
        ");
        foreach($drupalPostTypes as $dpt) {
            $return[$dpt['rid']] = $dpt['name'];
        }
        return $return;
    }

    /**
     * Returns an array of available Drupal terms (vocabulary)
     * @return array
     */
    public function getTerms() {
        $return = array();
        $drupalPostTypes = $this->_drupalDB->results("
            SELECT DISTINCT
              v.machine_name,
              (
                SELECT
                  COUNT(d.vid) as total
                FROM ".$this->dbSettings['prefix']."taxonomy_term_data d
                WHERE d.vid = v.vid
              ) as total
            FROM ".$this->dbSettings['prefix']."taxonomy_vocabulary v
            ORDER BY v.name ASC
        ");
        foreach($drupalPostTypes as $dpt) {
            $return[$dpt['machine_name']] = $dpt['total'];
        }
        return $return;
    }

    /**
     * Runs check and processes terms import
     */
    public function importTerms() {
        if (!empty($this->options['terms'])) {
            $this->_importTags();
            $this->_importCategories();
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
        $drupal_tags = $this->_drupalDB->results("
            SELECT DISTINCT
                d.tid,
                d.name,
                a.alias
            FROM ".$this->dbSettings['prefix']."taxonomy_term_data d
                LEFT JOIN ".$this->dbSettings['prefix']."url_alias a ON (REPLACE(a.source,'taxonomy/term/','') = d.tid)
            ORDER BY d.tid ASC
            ");
        $homeURL = get_home_url();
        $_tmpTaxonomies = array();
        $_fixTaxonomies = array();
        if ($drupal_tags) {
            // First import our taxonomies
            foreach($drupal_tags as $dt) {
                $dt['name'] = trim($dt['name']);
                $dt['alias'] = trim($dt['alias']);
                // Attempt to create Drupal alias
                $dt['alias'] = $this->sanitizeAlias($dt['alias']);
                // Create WordPress slug
                $newSlug = $this->convertToWordPressSlug($dt['name']);
                // Import the new term
                $result = $wpdb->insert(
                    $wpdb->terms,
                    array(
                        'term_id' => $dt['tid'],
                        'name' => $dt['name'],
                        'slug' => $newSlug
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s'
                    )
                );
                // See if we have a failure
                if ($result === false) {
                    // This is mainly due to matching slugs, attempt with a temp slug to be fixed when done with the rest
                    $newSlug .= $this->_duplicateSlugSuffix;
                    $result = $wpdb->insert(
                        $wpdb->terms,
                        array(
                            'term_id' => $dt['tid'],
                            'name' => $dt['name'],
                            'slug' => $newSlug
                        ),
                        array(
                            '%d',
                            '%s',
                            '%s'
                        )
                    );
                    // See if we have a failure
                    if ($result === false) {
                        $this->errors['terms'][] = $wpdb->last_error;
                        continue;
                    } else {
                        // Should save this for fixing later
                        $_fixTaxonomies[] = $dt;
                        $this->errors['duplicate_taxonomies'][] = sprintf( __('ID: %d. Taxonomy slug is suffixed with %s (%s)', 'drupal2wp'), $dt['tid'], $this->_duplicateSlugSuffix, $newSlug);
                    }
                }
                $_tmpTaxonomies[$dt['tid']] = $dt;
            }
            // Update WP term_taxonomy table (associations)
            $drupal_taxonomy = $this->_drupalDB->results(
                "SELECT DISTINCT
                    d.tid AS term_id,
                    v.machine_name AS post_tag,
                    d.description AS description,
                    h.parent AS parent
                FROM ".$this->dbSettings['prefix']."taxonomy_term_data d
                    INNER JOIN ".$this->dbSettings['prefix']."taxonomy_term_hierarchy h ON (d.tid = h.tid)
                    INNER JOIN ".$this->dbSettings['prefix']."taxonomy_vocabulary v ON (v.vid = d.vid)
                ORDER BY 'term_id' ASC
                ");
            if ($drupal_taxonomy) {
                foreach($drupal_taxonomy as $dt) {
                    // Get taxonomy association
                    $dt['post_tag'] = $this->parseTerms($dt['post_tag']);
                    // Skip empty post_tags
                    if (empty($dt['post_tag'])) {
                        continue;
                    }
                    // Import term taxonomy
                    $result = $wpdb->insert(
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
                    // Check for error
                    if ($result === false) {
                        $this->errors['term_taxonomy'][] = $wpdb->last_error;
                        continue;
                    }
                    $_tmpTaxonomies[$dt['term_id']] = array_merge($_tmpTaxonomies[$dt['term_id']], $dt);
                }
            }

            // @todo fix duplicate taxonomies

            // @todo find issue breaking tags and throwing errors
            // Could be due to matching to different taxonomies? Check that out

            // Build mod rewrite list
            if (!empty($_tmpTaxonomies)) {
                foreach ($_tmpTaxonomies as $dt) {
                    $dt['name'] = trim($dt['name']);
                    $dt['alias'] = trim($dt['alias']);
                    // Attempt to create Drupal alias
                    $dt['alias'] = $this->sanitizeAlias($dt['alias']);
                    // Get term
                    $tmpTerm = get_term_by('id', (int)$dt['tid'], $dt['post_tag']);
                    // If there was an error, continue to the next term.
                    if (!$tmpTerm) {
                        $this->errors['get_term_by_id'][] = sprintf(__('Could not load the term by ID: %d', 'drupal2wp'), $dt['tid']);
                        continue;
                    }
                    // Get term URI
                    $tmpTermURI = get_term_link($tmpTerm);
                    // If there was an error, continue to the next term.
                    if (is_wp_error($tmpTermURI)) {
                        $this->errors['get_term_link'][] = sprintf(__('Could not get the term link for ID: %d. Returned error: %s', 'drupal2wp'), $dt['tid'], $tmpTermURI->get_error_message());
                        continue;
                    }
                    $tmpTermURI = str_replace($homeURL, '', $tmpTermURI);
                    // Add to rewrite rules if different
                    if ($tmpTermURI !== false && $dt['alias'] && trim($tmpTermURI, '/') != trim($dt['alias'], '/')) {
                        $this->_htaccessRewriteRules[$dt['alias']] = $tmpTermURI;
                    }
                }
            }

            print '<p><span style="color: green;">'.__('Tags Imported', 'drupal2wp').'</span> - '.__('Also optimized slugs per WordPress specifications', 'drupal2wp').'</p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Tags Failed to Import', 'drupal2wp').'</span></p>';
        }
        ob_flush(); flush(); // Output

        $drupal_tags = $drupal_taxonomy = NULL;
        unset($drupal_tags, $drupal_taxonomy);
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
                'slug' => !empty($this->options['default_category_slug']) ? $this->options['default_category_slug'] : 'blog'
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
        ob_flush(); flush(); // Output

    }

    /**
     * Runs check and processes content import
     */
    public function importContent() {
        if (!empty($this->options['content'])) {
            $this->_importContentTypes();
            $this->_importTagsAndRelationships();
            $this->_updatePostType();
            $this->_importComments();
            $this->_importMedia();
        }
        return $this; // maintain chaining
    }

    /**
     * Imports content types that were selected in step 2
     * @return $this
     */
    private function _importContentTypes() {
        global $wpdb;

        // Make sure we have content types to import
        if (empty($this->options['content_associations'])) {
            return false;
        }

        // Get all post from Drupal and add it into WordPress
        $sqlQuery = apply_filters( 'drupal2wp_query_nodes',
        	"SELECT
              DISTINCT n.nid AS id,
              n.uid AS post_author,
              FROM_UNIXTIME(n.created) AS post_date,
              r.body_value AS post_content,
              n.title AS post_title,
              r.body_summary AS post_excerpt,
              n.type AS post_type,
              IF(n.status = 1, 'publish', 'draft') AS post_status,
              a.alias
          FROM ".$this->dbSettings['prefix']."node n
            LEFT JOIN ".$this->dbSettings['prefix']."field_data_body r ON (r.entity_id = n.nid)
            LEFT JOIN ".$this->dbSettings['prefix']."url_alias a ON (REPLACE(a.source,'node/','') = n.nid)
            ORDER BY n.nid" );
        $drupalContent = $this->_drupalDB->results($sqlQuery);
        if (!empty($drupalContent)) {
            // Save each post
            foreach($drupalContent as $dp) {
                $this->savePost2WP($dp);
            }
            // Miscellaneous clean-up.
            // There may be some extraneous blank spaces in your Drupal posts; use these queries
            // or other similar ones to strip out the undesirable tags.
            $wpdb->query("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content,'<p> </p>','');");
            $wpdb->query("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content,'<p class=\"italic\"> </p>','');");

            print '<p><span style="color: green;">'.__('Content Imported', 'drupal2wp').'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.__('Content Failed to Import', 'drupal2wp').' - '.__('All failed', 'drupal2wp').'</span></p>';
        }
        ob_flush(); flush(); // Output
    }

    /**
     * Saves the Drupal post array to WordPress
     * This is set to allow plugins to call this method to easily add post content
     * @param array $dp
     * @return bool|int
     */
    public function savePost2WP($dp) {
        global $wpdb;

        // Make sure the post type has been selected to be imported
        if (!isset($this->options['content_associations'][$dp['post_type']])) {
            return false;
        }

        // First check for title
        $post_type = $this->parseContentTitleForPostType($dp['post_title']);
        if (!$post_type) {
            // No title match, get the drupal association
            $post_type = $this->parsePostType($dp['post_type']);
        }
        // Filter to adjust post_type
        $post_type = apply_filters('drupal2wp_modify_post_type', $post_type);
        // If no post type we can't use it
        if (!$post_type) {
            return false;
        }
        $dp['post_title'] = trim($dp['post_title']);
        // Fix author
        $dp['post_author'] = (empty($this->options['users']) && !empty($this->options['associate_content_user_id'])) ? $this->options['associate_content_user_id'] : $dp['post_author'];
        // Fix slug
        $dp['post_name'] = $this->convertToWordPressSlug($dp['post_title']);
        // Add post type to post so other plugins can check against it
        $dp['post_type'] = $post_type;
        // Filter to modify post
        $dp = apply_filters('drupal2wp_modify_post', $dp);
        // Use tmp date to set post date if none is set
        $tmpDate = date('Y-m-d H:i:s');
        // Make sure we have all our vars
        $dp = array_merge(array(
            'id' => '', // empty by default to insert a new ID
            'post_author' => 1,
            'post_date' => $tmpDate,
            'post_date_gmt' => get_gmt_from_date( $tmpDate ),
            'post_content' => '',
            'post_title' => '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'post_name' => '',
            'post_parent' => 0,
            'comment_status' => $this->default_comment_status,
            'ping_status' => $this->default_ping_status
        ), $dp);
        // Insert into WordPress
        $result = $wpdb->insert(
            $wpdb->posts,
            array(
                'id' => $dp['id'],
                'post_author' => $dp['post_author'],
                'post_date' => $dp['post_date'],
                'post_date_gmt' => $dp['post_date_gmt'],
                'post_content' => ''.$dp['post_content'],
                'post_title' => $dp['post_title'],
                'post_excerpt' => ''.$dp['post_excerpt'],
                'post_type' => $dp['post_type'],
                'post_status' => $dp['post_status'],
                'post_name' => $dp['post_name'],
                'post_parent' => $dp['post_parent'],
                'comment_status' => $dp['comment_status'],
                'ping_status' => $dp['ping_status']
            ),
            array(
                '%d', // id
                '%s', // post_author
                '%s', // post_date
                '%s', // post_date_gmt
                '%s', // post_content
                '%s', // post_title
                '%s', // post_excerpt
                '%s', // post_type
                '%s', // post_status
                '%s', // post_name
                '%d', // post_parent
                '%s', // comment_status
                '%s' // ping_status
            )
        );
        // Make sure post has been saved
        if ($result !== false) {
            $_postID = $wpdb->insert_id;
            // Add to rewrite rules if different
            $newSlug = in_array($dp['post_type'], array('post','page')) ? get_permalink($_postID, false, true) : get_post_permalink($_postID);
            $newSlug = parse_url($newSlug);
            $newSlug = $newSlug['path'];
            if (!empty($dp['alias']) && $newSlug !== false && $dp['alias'] && trim($newSlug, '/') != trim($dp['alias'], '/')) {
                $this->_htaccessRewriteRules[$dp['alias']] = $newSlug;
            }
            // Attach post_type post to the blog_term_id ONLY if a post
            if ('post' === $post_type) {
                $wpdb->insert(
                    $wpdb->term_relationships,
                    array(
                        'object_id' => $_postID,
                        'term_taxonomy_id' => $this->blog_term_id
                    ),
                    array(
                        '%d',
                        '%d'
                    )
                );
            }
            // NOTE: DO NOT IMPORT MEDIA HERE! IT WILL BREAK POST IDs!
            // Hook for any after insert actions
            do_action('drupal2wp_content_after_post_insert', $dp, $this);
            // return the post ID
            return $wpdb->insert_id;
        } else {
            $wpdb->print_error();
            $this->errors['import_content'][] = sprintf( __('ID: %d', 'drupal2wp'), $dp['id']);
        }
        return false;
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
                // Get the WordPress term ID
                $wordpress_term_tax = $wpdb->get_var("
                  SELECT DISTINCT
                    term_taxonomy.term_taxonomy_id
                  FROM {$wpdb->term_taxonomy} term_taxonomy
                  WHERE (term_taxonomy.term_id = ".$dpt['tid'].")");

                if (NULL === $wordpress_term_tax && isset($this->duplicateTaxonomyAssociations[$dpt['tid']])) {
                    $wordpress_term_tax = $this->duplicateTaxonomyAssociations[$dpt['tid']];
                }

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

            $this->updateTermsCount(); // must be done last

        } else {
            print '<p><span style="color: maroon;">'.__('Tags & Content Relationships Failed to Import', 'drupal2wp').' - '.__('All failed', 'drupal2wp').'</span></p>';
        }
        ob_flush(); flush(); // Output
    }

    /**
     * Updates the tag count for WordPress
     */
    public function updateTermsCount() {
        global $wpdb;

        // Ensure we are importing this data
        if (empty($this->options['terms'])) {
            return false;
        }

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
        ob_flush(); flush(); // Output
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
        ob_flush(); flush(); // Output
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
        ob_flush(); flush(); // Output
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
                FROM_UNIXTIME(c.created) AS comment_date,
                f.comment_body_value AS comment_content
            FROM ".$this->dbSettings['prefix']."comment c
                LEFT JOIN ".$this->dbSettings['prefix']."field_data_comment_body f ON (c.cid = f.entity_id)
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
                        '%s',
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
        ob_flush(); flush(); // Output
    }

    /**
     * Imports content media
     */
    private function _importMedia() {
        global $wpdb;
        if (!empty($this->options['media'])) {
            // Fetch all imported content
            $importedPosts = $wpdb->get_results("SELECT ID FROM $wpdb->posts");
            if ($importedPosts) {
                // loop over IDs and import media
                foreach($importedPosts as $post) {
                    $this->importMediaForPostID($post->ID);
                }
                print '<p><span style="color: green;">'.__('Media Imported', 'drupal2wp').'</span></p>';
            } else {
                print '<p><span>'.__('No Media was Found for Import', 'drupal2wp').'</span></p>';
            }
            ob_flush(); flush(); // Output
        }
    }

    /**
     * Imports media for a post ID
     * @param int $postID
     * @return bool
     */
    private function importMediaForPostID($postID) {
        if (empty($this->options['files_location'])) {
            $this->errors['import_media'][] = __('No Drupal files path defined to import media.', 'drupal2wp');
            return false;
        }
        // Fix file path
        $this->options['files_location'] = trim($this->options['files_location'], '/').'/';
        // Fetch media for post ID
        $postMedia = $this->_drupalDB->results("
            SELECT
                u.fid,
                m.uri,
                m.filename
            FROM ".$this->dbSettings['prefix']."file_usage u
                LEFT JOIN ".$this->dbSettings['prefix']."file_managed m ON (m.fid = u.fid)
            WHERE u.type = 'node'
             AND u.id = {$postID}
            ");
        if (!empty($postMedia)) {
//            $mediaCount = count($postMedia);
            foreach ($postMedia as $pMedia) {
                // Replace Drupal public:// with URL
                $file = str_replace('public://', $this->options['files_location'], $pMedia['uri']);
                $attachmentID = self::addFileToMediaManager($postID, $file, '', $pMedia['filename'], $this->errors['import_media']);
                if (!is_wp_error($attachmentID)) {
                    set_post_thumbnail($postID, $attachmentID);
                } else {
                    $this->errors['import_media'][] = sprintf( __('Failed to import media file: %s - %s', 'drupal2wp'), $pMedia['filename'], $attachmentID->get_error_message());
                }
            }
        }
    }

    /**
     * Imports user data
     * @return $this
     */
    public function importUsers() {
        if (!empty($this->options['users'])) {
            // Get total of users
            $totalUsers = $this->_drupalDB->row("
                SELECT COUNT(1) as total
                FROM ".$this->dbSettings['prefix']."users u
                WHERE u.uid > 1
                ".(!empty($this->options['enabled_users_only']) ? 'AND u.status = 1' : '')."
                ORDER BY u.uid
            ");
            if (!empty($totalUsers) && !empty($totalUsers['total'])) {
                $importLimit = 5000; // Temp fix for large set of users @todo make this an option
                for($i=0; $i<=$totalUsers['total']; $i+=$importLimit) {
                    // Modify to get the
                    $this->_importUsersData($i, $importLimit);
                }
                print '<p><span style="color: green;">'.sprintf(__('Users Import Complete (%d imported)', 'drupal2wp'), $totalUsers['total']).'</span></p>';
                ob_flush(); flush(); // Output
            }
        }
        return $this; // maintain chaining
    }

    /**
     * Imports essential user data to WP
     * Admin is skipped and uses the WP admin instead
     * Passwords are left blank, so users will need to reset their passwords
     */
    private function _importUsersData($offset, $importLimit) {
        global $wpdb;
        $dUsers = $this->_drupalDB->results("
            SELECT
                u.uid,
                u.name,
                u.pass,
                u.mail,
                FROM_UNIXTIME(u.created) AS created,
                u.access,
                u.status,
                (SELECT GROUP_CONCAT(rid) FROM ".$this->dbSettings['prefix']."users_roles ur WHERE ur.uid = u.uid) as rid
            FROM ".$this->dbSettings['prefix']."users u
            WHERE u.uid > 1
            ".(!empty($this->options['enabled_users_only']) ? 'AND u.status = 1' : '')."
            ORDER BY u.uid
            LIMIT {$offset},{$importLimit}
        ");
        if (!empty($dUsers)) {
            // Make sure we have the function needed here
            if ( !function_exists('get_editable_roles') ) {
                require_once( ABSPATH . '/wp-admin/includes/user.php' );
            }
            // Fetch WordPress user roles
            $this->_wpUserRoles = get_editable_roles();
            // Loop over our users
            foreach($dUsers as $dUser) {
                // Check the user role
                $wpUserRole = $this->processUserRole($dUser['rid']);
                // Only process if we have a user role
                if (!$wpUserRole) {
                    continue;
                }
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
                // Get user level for legacy support
                // Check this way to allow for customized roles to import properly
                if (isset($this->_wpUserRoles[$wpUserRole])) {
                    if (isset($this->_wpUserRoles[$wpUserRole]['level_10']) && $this->_wpUserRoles[$wpUserRole]['level_10'] == '1') {
                        $userLevel = 10;
                        $showAdminBarOnFront = 'true';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_9']) && $this->_wpUserRoles[$wpUserRole]['level_9'] == '1') {
                        $userLevel = 9;
                        $showAdminBarOnFront = 'true';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_8']) && $this->_wpUserRoles[$wpUserRole]['level_8'] == '1') {
                        $userLevel = 8;
                        $showAdminBarOnFront = 'true';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_7']) && $this->_wpUserRoles[$wpUserRole]['level_7'] == '1') {
                        $userLevel = 7;
                        $showAdminBarOnFront = 'true';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_6']) && $this->_wpUserRoles[$wpUserRole]['level_6'] == '1') {
                        $userLevel = 6;
                        $showAdminBarOnFront = 'false';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_5']) && $this->_wpUserRoles[$wpUserRole]['level_5'] == '1') {
                        $userLevel = 5;
                        $showAdminBarOnFront = 'false';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_4']) && $this->_wpUserRoles[$wpUserRole]['level_4'] == '1') {
                        $userLevel = 4;
                        $showAdminBarOnFront = 'false';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_3']) && $this->_wpUserRoles[$wpUserRole]['level_3'] == '1') {
                        $userLevel = 3;
                        $showAdminBarOnFront = 'false';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_2']) && $this->_wpUserRoles[$wpUserRole]['level_2'] == '1') {
                        $userLevel = 2;
                        $showAdminBarOnFront = 'false';
                    } else if (isset($this->_wpUserRoles[$wpUserRole]['level_1']) && $this->_wpUserRoles[$wpUserRole]['level_1'] == '1') {
                        $userLevel = 1;
                        $showAdminBarOnFront = 'false';
                    } else {
                        $userLevel = 0;
                        $showAdminBarOnFront = 'false';
                    }
                } else {
                    $userLevel = 0;
                    $showAdminBarOnFront = 'false';
                }
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
                            ('%s','%s','%s'),
                            ('%s','%s','%s')
                        ",
                        array(
                            $dUser['uid'], 'wp_capabilities', 'a:1:{s:'.strlen($wpUserRole).':"'.$wpUserRole.'";b:1;}',
                            $dUser['uid'], 'wp_user_level', $userLevel,
                            $dUser['uid'], 'nickname', $dUser['name'],
                            $dUser['uid'], 'first_name', '',
                            $dUser['uid'], 'last_name', '',
                            $dUser['uid'], 'description', '',
                            $dUser['uid'], 'rich_editing', 'true',
                            $dUser['uid'], 'comment_shortcuts', 'false',
                            $dUser['uid'], 'admin_color', 'fresh',
                            $dUser['uid'], 'use_ssl', '0',
                            $dUser['uid'], 'show_admin_bar_front', $showAdminBarOnFront
                        )
                    ));
                }
            }
//            print '<p><span style="color: green;">'.sprintf(__('Users Imported: %d - %d', 'drupal2wp'), $offset, $offset+$importLimit).'</span></p>';
        } else {
            print '<p><span style="color: maroon;">'.sprintf(__('Failed to import users: %d - %d', 'drupal2wp'), $offset, $offset+$importLimit).'</span></p>';
        }
        ob_flush(); flush(); // Output
    }

}
