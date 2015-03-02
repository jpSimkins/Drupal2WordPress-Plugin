<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" novalidate="novalidate">
    <input type="hidden" name="page" value="drupal2wp">
    <input type="hidden" name="action" value="step1">
    <input type="hidden" name="step" value="1">
    <?php wp_nonce_field('drupal2wp-step1-nonce', '_drupal2wp_nonce'); ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row" colspan="2">
                    <h3 style="margin: 0;"><?php _e('Drupal DB Settings', 'drupal2wp'); ?></h3>
                    <p class="description"><?php _e('Database settings to connect to the Drupal database.', 'drupal2wp'); ?></p>
                </th>
            </tr>
            <tr>
                <th scope="row"><label for="drupal-db-host"><?php _e('Database Host', 'drupal2wp'); ?></label></th>
                <td><input name="druaplDB[host]" type="text" id="drupal-db-host" value="<?php echo isset($_POST['druaplDB']['host']) ? $_POST['druaplDB']['host'] : 'localhost'; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="drupal-db-database"><?php _e('Database Name', 'drupal2wp'); ?></label></th>
                <td><input name="druaplDB[database]" type="text" id="drupal-db-name" value="<?php echo isset($_POST['druaplDB']['database']) ? $_POST['druaplDB']['database'] : ''; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="drupal-db-username"><?php _e('Database Username', 'drupal2wp'); ?></label></th>
                <td><input name="druaplDB[username]" type="text" id="drupal-db-username" value="<?php echo isset($_POST['druaplDB']['username']) ? $_POST['druaplDB']['username'] : ''; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="drupal-db-password"><?php _e('Database Password', 'drupal2wp'); ?></label></th>
                <td><input name="druaplDB[password]" type="password" id="drupal-db-password" value="<?php echo isset($_POST['password']['username']) ? $_POST['druaplDB']['password'] : ''; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="drupal-db-prefix"><?php _e('Database Prefix', 'drupal2wp'); ?></label></th>
                <td><input name="druaplDB[prefix]" type="text" id="drupal-db-prefix" value="<?php echo isset($_POST['druaplDB']['prefix']) ? $_POST['druaplDB']['prefix'] : ''; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="drupal-version"><?php _e('Drupal Version', 'drupal2wp'); ?></label></th>
                <td>
                    <select name="druaplDB[version]" id="drupal-version">
                        <option value="7" <?php echo (isset($_POST['druaplDB']['version']) && $_POST['druaplDB']['version'] == '7') ? 'selected="selected"' : 'selected="selected"'; ?>>7</option>
                    </select>
                </td>
            </tr>
            <?php
            $thirdPartyOptions = array();
            $thirdPartyOptions = apply_filters('drupal2wp_3rd_party_options', $thirdPartyOptions);
            if (!empty($thirdPartyOptions)) : ?>
            <tr>
                <th scope="row"><label for="drupal-version"><?php _e('Third-party Options', 'drupal2wp'); ?></label></th>
                <td>
                    <p class="description" style="margin-bottom: 10px;"><?php _e('Select the third-party options you would like to use during this import.', 'drupal2wp'); ?></p>
                    <?php foreach($thirdPartyOptions as $key=>$title) : ?>
                        <label style="display: block;">
                            <input type="checkbox" name="thirdPartyOptions[<?php esc_attr_e($key); ?>]" value="1" <?php echo (!empty($_POST['thirdPartyOptions'][$key])) ? 'checked="checked"' : ''; ?> />
                            &nbsp;
                            <?php echo $title; ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php do_action('drupal2wp_view_step1_in_form'); ?>
    <p class="submit"><input type="submit" id="submit" class="button button-primary" value="<?php _e('Proceed to Next Step', 'drupal2wp'); ?>"></p>
    <p class="description"><?php _e("Don't worry, nothing is being imported yet.", 'drupal2wp'); ?></p>
</form>
<?php do_action('drupal2wp_view_step1'); ?>
