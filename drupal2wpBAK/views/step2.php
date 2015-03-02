
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" novalidate="novalidate">
    <input type="hidden" name="page" value="drupal2wp">
    <input type="hidden" name="action" value="step2">
    <input type="hidden" name="step" value="2">
    <?php wp_nonce_field('drupal2wp-step2-nonce'); ?>
    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row" colspan="2">
                <h3 style="margin: 0;"><?php _e('Drupal Checks', 'drupal2wp'); ?></h3>
                <p class="description"><?php _e('Please verify the information is correct.', 'drupal2wp'); ?></p>
            </th>
        </tr>
        <tr>
            <th scope="row"><label for="drupal-db"><?php _e('Drupal DB Status', 'drupal2wp'); ?></label></th>
            <td><span style="color: green;"><?php _e('Connection Successful', 'drupal2wp'); ?></span></td>
        </tr>
        <tr>
            <th scope="row"><label for="drupal-db"><?php _e('Drupal DB Prefix', 'drupal2wp'); ?></label></th>
            <td><?php echo $TEMPLATE_VARS['drupalPrefix']; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="drupal-db"><?php _e('Drupal Version', 'drupal2wp'); ?></label></th>
            <td><?php echo $TEMPLATE_VARS['drupalVersion']; ?></td>
        </tr>
        <tr>
            <th scope="row" colspan="2">
                <h3 style="margin: 0;"><?php _e('Import Options', 'drupal2wp'); ?></h3>
                <p class="description"><?php _e('Controls the import process', 'drupal2wp'); ?></p>
            </th>
        </tr>
        <tr>
            <th scope="row"><label for="import-terms"><?php _e('Import Terms', 'drupal2wp'); ?></label></th>
            <td>
                <input name="options[terms]" type="checkbox" id="import-terms" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['terms']) && $_POST['options']['terms'] == '1') ? 'checked="checked"' : ''; ?>>
                <p class="description"><?php _e('Imports categories and tags', 'drupal2wp'); ?></p>
            </td>
        </tr>
        <tr class="terms">
            <th scope="row">&nbsp;</th>
            <td>
                <label for="default-category-name" style="display: block; font-weight: bold;"><?php _e('Default Category:', 'drupal2wp'); ?></label>
                <p class="description"><?php _e('This is the default category posts are assigned to. Required by WordPress.', 'drupal2wp'); ?></p>
                <label for="default-category-name" style="display: inline-block; width: 50px;"><?php _e('Name:', 'drupal2wp'); ?></label>&nbsp;<input name="options[default_category_name]" type="text" id="default-category-name" value="<?php echo isset($_POST['options']['default_category_name']) ? $_POST['options']['default_category_name'] : 'Blog'; ?>" class="medium-text">
                <br/>
                <label for="default-category-slug" style="display: inline-block; width: 50px;"><?php _e('Slug:', 'drupal2wp'); ?></label>&nbsp;<input name="options[default_category_slug]" type="text" id="default-category-slug" value="<?php echo isset($_POST['options']['default_category_slug']) ? $_POST['options']['default_category_slug'] : 'blog'; ?>" class="medium-text">


                <div id="term-associations-cage">
                    <label style="display: block; font-weight: bold; margin-top:20px;"><?php _e('Taxonomy Association:', 'drupal2wp'); ?></label>
                    <p class="description"><?php _e('This allows you to associate Drupal taxonomies to each available WordPress taxonomy.', 'drupal2wp'); ?></p>
                    <p class="description"><?php _e('Each box is the WordPress taxonomy. Each list shows the available Drupal taxonomies for association.', 'drupal2wp'); ?></p>
                    <p class="description"><strong><?php _e('Non-associated content will not be imported!', 'drupal2wp'); ?></strong></p>
                    <?php foreach($TEMPLATE_VARS['wpTerms'] as $wpPt) : ?>
                        <div id="term-associations-<?php echo $wpPt; ?>-cage" style="display: inline-block; width: 200px; vertical-align: top; margin: 4px;">
                            <h4 style="margin: 4px 0 4px 8px;"><?php echo ucwords( str_replace(array('-', '_'), ' ', $wpPt) ); ?></h4>
                            <ul class="term-associations" style="background-color: #fff;padding: 7px;border: 1px solid #CCC;border-radius: 6px;overflow: auto;max-height: 200px;margin: 0;">
                                <?php foreach($TEMPLATE_VARS['drupalTerms'] as $dPt=>$total) :
                                    $autoCheck = !isset($_POST['options']['associations']) ? (
                                        ('post' === $wpPt && 'article' === $dPt)
                                        ||
                                        ('page' === $wpPt && 'page' === $dPt)
                                    ) : false;
                                    ?>
                                    <li id="<?php echo $wpPt.'-'.$dPt; ?>-item" class="ca-<?php echo $dPt; ?>">
                                        <label>
                                            <input type="checkbox" id="<?php echo $wpPt.'__'.$dPt; ?>" name="options[term_associations][<?php echo $dPt; ?>]" value="<?php echo $wpPt; ?>" class="regular-checkbox" <?php echo ($autoCheck || isset($_POST['options']['term_associations'][$dPt]) && $_POST['options']['term_associations'][$dPt] == $wpPt ) ? 'checked="checked"' : ''; ?>>
                                            <?php echo $dPt.' ('.number_format($total).')'; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

            </td>
        </tr>
        <tr>
            <th scope="row"><label for="import-content"><?php _e('Import Content', 'drupal2wp'); ?></label></th>
            <td>
                <input name="options[content]" type="checkbox" id="import-content" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['content']) && $_POST['options']['content'] == '1') ? 'checked="checked"' : ''; ?>>
                <p class="description"><?php _e('Import post_types, comments, etc.', 'drupal2wp'); ?></p>
            </td>
        </tr>
        <tr class="content">
            <th scope="row">&nbsp;</th>
            <td>
                <label for="import-comments">
                    <input name="options[comments]" type="checkbox" id="import-comments" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['comments']) && $_POST['options']['comments'] == '1') ? 'checked="checked"' : ''; ?>>
                    <?php _e('Include Comments', 'drupal2wp'); ?>
                </label>
                <p class="description"><?php _e('This will import comments that are associated to your content.', 'drupal2wp'); ?></p>

                <div id="content-associations-cage">
                    <label style="display: block; font-weight: bold; margin-top:20px;"><?php _e('Node/Post Type Association:', 'drupal2wp'); ?></label>
                    <p class="description"><?php _e('This allows you to associate Drupal node types to each available WordPress post type.', 'drupal2wp'); ?></p>
                    <p class="description"><?php _e('Each box is the WordPress post type. Each list shows the available Drupal types for association.', 'drupal2wp'); ?></p>
                    <p class="description"><strong><?php _e('Non-associated content will not be imported!', 'drupal2wp'); ?></strong></p>
                    <?php foreach($TEMPLATE_VARS['wpPostTypes'] as $wpPt) : ?>
                        <div id="content-associations-<?php echo $wpPt; ?>-cage" style="display: inline-block; width: 200px; vertical-align: top; margin: 4px;">
                            <h4 style="margin: 4px 0 4px 8px;"><?php echo ucwords( str_replace(array('-', '_'), ' ', $wpPt) ); ?></h4>
                            <ul class="content-associations" style="background-color: #fff;padding: 7px;border: 1px solid #CCC;border-radius: 6px;overflow: auto;max-height: 200px;margin: 0;">
                                <?php foreach($TEMPLATE_VARS['drupalPostTypes'] as $dPt=>$total) :
                                    $autoCheck = !isset($_POST['options']['content_associations']) ? (
                                        ('post' === $wpPt && 'article' === $dPt)
                                        ||
                                        ('page' === $wpPt && 'page' === $dPt)
                                    ) : false;
                                    ?>
                                    <li id="<?php echo $wpPt.'-'.$dPt; ?>-item" class="ca-<?php echo $dPt; ?>">
                                        <label>
                                            <input type="checkbox" id="<?php echo $wpPt.'__'.$dPt; ?>" name="options[content_associations][<?php echo $dPt; ?>]" value="<?php echo $wpPt; ?>" class="regular-checkbox" <?php echo ($autoCheck || isset($_POST['options']['content_associations'][$dPt]) && $_POST['options']['content_associations'][$dPt] == $wpPt ) ? 'checked="checked"' : ''; ?>>
                                            <?php echo $dPt.' ('.number_format($total).')'; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="import-users"><?php _e('Import Users', 'drupal2wp'); ?></label></th>
            <td><input name="options[users]" type="checkbox" id="import-users" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['users']) && $_POST['options']['users'] == '1') ? 'checked="checked"' : ''; ?>></td>
        </tr>
        <tr class="users">
            <th scope="row"><label for="import-users"><?php _e('Associated Content to User ID', 'drupal2wp'); ?></label></th>
            <td>
                <input name="options[associate_content_user_id]" type="text" id="associate-content-user-id" value="<?php echo isset($_POST['options']['associate_content_user_id']) ? $_POST['options']['associate_content_user_id'] : '1'; ?>" class="medium-text">
                <p class="description"><?php _e('If not importing users, you need to associate content to a specific user ID. If no ID is entered, all content will be imported with the ID of the Drupal user (this is not ideal)', 'drupal2wp'); ?></p>
            </td>
        </tr>
        </tbody>
    </table>
    <p class="submit"><input type="submit" id="submit" class="button button-primary" value="<?php _e('Start Import', 'drupal2wp'); ?>"></p>
    <p class="description"><?php _e('Proceeding to the next step will start the import process. This can take a long time depending on the size of the Drupal database.', 'drupal2wp'); ?></p>
</form>
<script type="text/javascript">
    jQuery(document).ready(function($){
        // Hide child options
        $(".terms, .content").hide();
        // Add parent child toggles
        $("#import-terms, #import-content, #import-users").on("change", function() {
            switch (this.id) {
                case "import-terms":
                    _drupal2wp_child_toggle(".terms", $(this).is(":checked"));
                    break;
                case "import-content":
                    _drupal2wp_child_toggle(".content", $(this).is(":checked"));
                    break;
                case "import-users":
                    _drupal2wp_child_toggle(".users", !$(this).is(":checked"));
                    break;
            }
        });
        // Add content-associations logic
        $(".content-associations input").on("change", function() {
            var _tmpData = this.id.split("__");
            var _selectedPT = _tmpData[0];
            var _drupalPT = _tmpData[1];
            var _isChecked = $(this).is(":checked");
            // Disable other wp post types from being able to use the selected drupal PT
            $("#content-associations-cage .content-associations .ca-"+_drupalPT).not("#"+_selectedPT+"-"+_drupalPT+"-item").each(function() {
                if (_isChecked) {
                    $(this).find("label").css("opacity",.7).find("input").prop("disabled", true);
                } else {
                    $(this).find("label").css("opacity", 1).find("input").prop("disabled", false);
                }
            });
        });
        // Add terms-associations logic
        $(".term-associations input").on("change", function() {
            var _tmpData = this.id.split("__");
            var _selectedPT = _tmpData[0];
            var _drupalPT = _tmpData[1];
            var _isChecked = $(this).is(":checked");
            // Disable other wp post types from being able to use the selected drupal PT
            $("#term-associations-cage .term-associations .ca-"+_drupalPT).not("#"+_selectedPT+"-"+_drupalPT+"-item").each(function() {
                if (_isChecked) {
                    $(this).find("label").css("opacity",.7).find("input").prop("disabled", true);
                } else {
                    $(this).find("label").css("opacity", 1).find("input").prop("disabled", false);
                }
            });
        });
        // Toggle change for selected import options
        $("#import-terms, #import-content").each(function() {
            if ($(this).is(":checked")) {
                $(this).change();
            }
        });
        // Toggle change for selected content-associations
        $("#content-associations-cage .content-associations li input:checked, #term-associations-cage .term-associations li input:checked").each(function() {
            $(this).change();
        });

        /**
         * Toggles option children
         * @param childIdentifier
         * @param isChecked
         * @private
         */
        function _drupal2wp_child_toggle(childIdentifier, isChecked) {
            if (isChecked) {
                $(childIdentifier).fadeIn();
            } else {
                $(childIdentifier).fadeOut();
            }
        }

    });
</script>