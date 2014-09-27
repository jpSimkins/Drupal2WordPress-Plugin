<h3 style="margin: 0;"><?php _e('Import in Progress', 'drupal2wp'); ?></h3>
<p class="description"><?php _e('This can take a long time depending on the size of the Drupal database.', 'drupal2wp'); ?></p>

<p><?php _e('Import status will be outputted below. If any errors occur, try again. If the errors persist then the plugin will need to be adjusted.', 'drupal2wp'); ?></p>

<?php do_action('drupal2wp_import_iframe'); ?>