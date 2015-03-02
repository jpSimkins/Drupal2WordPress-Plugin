<div class="wrap">
    <h2><?php _e('Drupal 2 WordPress', 'drupal2wp'); ?></h2>
    <p class="description"><?php _e('Imports Drupal content into WordPress', 'drupal2wp'); ?></p>
    <?php if (!empty($TEMPLATE_VARS['nag'])) : ?>
        <div class="update-nag" style="width: 98%; margin: 5px 0 15px;">
            <?php foreach($TEMPLATE_VARS['nag'] as $error) : ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <? endif; ?>
    <?php
    // Show any notifications
    if (!empty($TEMPLATE_VARS['errors'])) : ?>
        <div class="error">
            <?php foreach($TEMPLATE_VARS['errors'] as $error) : ?>
            <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <? endif;
    if (!empty($TEMPLATE_VARS['success'])) : ?>
        <div class="updated">
            <?php foreach($TEMPLATE_VARS['success'] as $error) : ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <? endif; ?>
    <hr/>
    <?php
    // load step view
    if ($TEMPLATE_VARS['stepView'] && file_exists($TEMPLATE_VARS['stepView'])) {
        include($TEMPLATE_VARS['stepView']);
    } else {
        _e('No view found for step', 'drupal2wp');
    }
    ?>
</div>
