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
            <th scope="row"><label for="default-category-name"><?php _e('Default Category', 'drupal2wp'); ?></label></th>
            <td>
                <label for="default-category-name" style="display: inline-block; width: 50px;"><?php _e('Name:', 'drupal2wp'); ?></label>&nbsp;<input name="options[default_category_name]" type="text" id="default-category-name" value="<?php echo isset($_POST['options']['default_category_name']) ? $_POST['options']['default_category_name'] : 'Blog'; ?>" class="medium-text">
                <br/>
                <label for="default-category-slug" style="display: inline-block; width: 50px;"><?php _e('Slug:', 'drupal2wp'); ?></label>&nbsp;<input name="options[default_category_slug]" type="text" id="default-category-slug" value="<?php echo isset($_POST['options']['default_category_slug']) ? $_POST['options']['default_category_slug'] : 'blog'; ?>" class="medium-text">
                <p class="description"><?php _e('This is the default category posts are assigned to. Required by WordPress.', 'drupal2wp'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="import-posts"><?php _e('Import Content', 'drupal2wp'); ?></label></th>
            <td><input name="options[posts]" type="checkbox" id="import-posts" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['posts']) && $_POST['options']['posts'] == '1') ? 'checked="checked"' : ''; ?>></td>
        </tr>
        <tr class="posts">
            <th scope="row"><label for="import-comments"><?php _e('Include Comments', 'drupal2wp'); ?></label></th>
            <td>
                <input name="options[comments]" type="checkbox" id="import-posts" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['comments']) && $_POST['options']['comments'] == '1') ? 'checked="checked"' : ''; ?>>
                <p class="description"><?php _e('This will import comments that are associated to your content.', 'drupal2wp'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="import-users"><?php _e('Import Users', 'drupal2wp'); ?></label></th>
            <td><input name="options[users]" type="checkbox" id="import-users" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['users']) && $_POST['options']['users'] == '1') ? 'checked="checked"' : ''; ?>></td>
        </tr>
        </tbody>
    </table>
    <p class="submit"><input type="submit" id="submit" class="button button-primary" value="<?php _e('Start Import', 'drupal2wp'); ?>"></p>
    <p class="description"><?php _e('Proceeding to the next step will start the import process. This can take a long time depending on the size of the Drupal database.', 'drupal2wp'); ?></p>
</form>
<script type="text/javascript">
    jQuery(document).ready(function($){
        // Hide child options
        $(".terms, .posts").hide();
        // Add parent child toggles
        $("#import-terms, #import-posts").on("change", function() {
            switch (this.id) {
                case "import-terms":
                    _drupal2wp_child_toggle(".terms", $(this).is(":checked"));
                    break;
                case "import-posts":
                    _drupal2wp_child_toggle(".posts", $(this).is(":checked"));
                    break;
            }
        });

        function _drupal2wp_child_toggle(childIdentifier, isChecked) {
            if (isChecked) {
                $(childIdentifier).fadeIn();
            } else {
                $(childIdentifier).fadeOut();
            }
        }

    });
</script>