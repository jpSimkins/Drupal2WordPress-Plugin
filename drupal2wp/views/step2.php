
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" novalidate="novalidate">
    <input type="hidden" name="page" value="drupal2wp">
    <input type="hidden" name="action" value="step2">
    <input type="hidden" name="step" value="2">
    <?php wp_nonce_field('drupal2wp-step2-nonce', '_drupal2wp_nonce'); ?>
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
                                    <li id="<?php echo $wpPt.'-'.$dPt; ?>-term" class="ca-<?php echo $dPt; ?>">
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

                <?php do_action('drupal2wp_import_terms_form'); ?>
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
                <div id="content-associations-cage">
                    <label style="display: block; font-weight: bold;"><?php _e('Node/Post Type Association:', 'drupal2wp'); ?></label>
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

                <?php $_assocByTitleCount = 0; ?>
                <div id="advanced-content" style="margin-top:20px; display: block;">
                    <label style="display: block; font-weight: bold;"><?php _e('Associate by Title Search:', 'drupal2wp'); ?></label>
                    <p class="description">
                        <?php _e('This will move content with any part of a title match to a specified WordPress post type. This is case-insensitive and the match can be any part of the title.', 'drupal2wp'); ?>
                    </p>
                    <a href="javascript:void(0);" id="add-title-assoc" title="<?php _e('Add Title Association', 'drupal2wp'); ?>" class="button-primary">+</a>

                    <?php if (!empty($_POST['options']['content_title_associations'])) :
                        foreach($_POST['options']['content_title_associations'] as $id=>$data) :
                            $_assocByTitleCount++; ?>
                            <div id="content-title-associations-<?php echo $_assocByTitleCount; ?>-cage" style="border: 1px solid #CCC; border-radius: 6px; background-color: white; padding: 6px; width: 554px; margin-top: 10px;">
                                <label style="font-weight: bold;">
                                    <?php _e('Title:', 'drupal2wp'); ?>
                                    <input name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][title]" type="text" id="content-title-associations-<?php echo $_assocByTitleCount; ?>" class="small" style="width: 192px;" value="<?php echo !empty($data['title']) ? $data['title'] : ''; ?>">
                                </label>
                                &nbsp;
                                <label style="font-weight: bold;">
                                    <?php _e('Associate To:', 'drupal2wp'); ?>
                                    <select name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][assoc]">
                                        <?php foreach($TEMPLATE_VARS['wpPostTypes'] as $wpPt) : ?>
                                            <option value="<?php echo $wpPt; ?>" <?php echo (!empty($data['assoc']) && $data['assoc'] == $wpPt) ? 'selected="selected"': ''; ?>><?php echo ucwords( str_replace(array('-', '_'), ' ', $wpPt) ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <div style="padding: 6px 6px 6px 14px;">
                                    <label>
                                        <input name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][remove_from_title]" type="checkbox" class="regular-checkbox" value="1" <?php echo (!empty($data['remove_from_title']) && $data['remove_from_title'] == 1) ? 'checked="checked"' : ''; ?>>
                                        <?php _e('Remove From Title', 'drupal2wp'); ?>
                                    </label>
                                    <span style="margin: 0 8px; font-weight: bold"><?php _e('-OR-', 'drupal2wp'); ?></span>
                                    <label>
                                        <?php _e('Replace With:', 'drupal2wp'); ?>
                                        <input name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][title_replace_with]" type="text" class="small" style="width: 192px;" value="<?php echo !empty($data['title_replace_with']) ? $data['title_replace_with'] : ''; ?>">
                                    </label>
                                </div>
                            </div>
                        <?php endforeach;
                    else:
                        $_assocByTitleCount++; ?>
                        <div id="content-title-associations-<?php echo $_assocByTitleCount; ?>-cage" style="border: 1px solid #CCC; border-radius: 6px; background-color: white; padding: 6px; width: 554px; margin-top: 10px;">
                            <label style="font-weight: bold;">
                                <?php _e('Title:', 'drupal2wp'); ?>
                                <input name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][title]" type="text" id="content-title-associations-<?php echo $_assocByTitleCount; ?>" class="small" style="width: 192px;" value="">
                            </label>
                                &nbsp;
                            <label style="font-weight: bold;">
                                <?php _e('Associate To:', 'drupal2wp'); ?>
                                <select name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][assoc]">
                                    <?php foreach($TEMPLATE_VARS['wpPostTypes'] as $wpPt) : ?>
                                        <option value="<?php echo $wpPt; ?>"><?php echo ucwords( str_replace(array('-', '_'), ' ', $wpPt) ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div style="padding: 6px 6px 6px 14px;">
                                <label>
                                    <input name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][remove_from_title]" type="checkbox" class="regular-checkbox" value="1">
                                    <?php _e('Remove From Title', 'drupal2wp'); ?>
                                </label>
                                <span style="margin: 0 8px; font-weight: bold"><?php _e('-OR-', 'drupal2wp'); ?></span>
                                <label>
                                    <?php _e('Replace With:', 'drupal2wp'); ?>
                                    <input name="options[content_title_associations][<?php echo $_assocByTitleCount; ?>][title_replace_with]" type="text" class="small" style="width: 192px;" value="">
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <div id="content-title-associations-TITLE_ASSOC_COUNT-cage" style="display:none; border: 1px solid #CCC; border-radius: 6px; background-color: white; padding: 6px; width: 554px; margin-top: 10px;">
                    <label style="font-weight: bold;">
                        <?php _e('Title:', 'drupal2wp'); ?>
                        <input name="options[content_title_associations][TITLE_ASSOC_COUNT][title]" type="text" id="content-title-associations-TITLE_ASSOC_COUNT" class="small" style="width: 192px;" value="" disabled="disabled">
                    </label>
                    &nbsp;
                    <label style="font-weight: bold;">
                        <?php _e('Associate To:', 'drupal2wp'); ?>
                        <select name="options[content_title_associations][TITLE_ASSOC_COUNT][assoc]" disabled="disabled">
                            <?php foreach($TEMPLATE_VARS['wpPostTypes'] as $wpPt) : ?>
                                <option value="<?php echo $wpPt; ?>"><?php echo ucwords( str_replace(array('-', '_'), ' ', $wpPt) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        &nbsp;
                        <a href="javascript:void(0);" id="remove-title-assoc" title="<?php _e('Remove Title Association', 'drupal2wp'); ?>" class="button-secondary remove-title-assoc">x</a>
                    </label>
                    <div style="padding: 6px 6px 6px 14px;">
                        <label>
                            <input name="options[content_title_associations][TITLE_ASSOC_COUNT][remove_from_title]" type="checkbox" class="regular-checkbox" value="1" disabled="disabled">
                            <?php _e('Remove From Title', 'drupal2wp'); ?>
                        </label>
                        <span style="margin: 0 8px; font-weight: bold"><?php _e('-OR-', 'drupal2wp'); ?></span>
                        <label>
                            <?php _e('Replace With:', 'drupal2wp'); ?>
                            <input name="options[content_title_associations][TITLE_ASSOC_COUNT][title_replace_with]" type="text" class="small" style="width: 192px;" value="" disabled="disabled">
                        </label>
                    </div>
                </div>


                <label for="import-comments" style="margin-top:20px; display: block;">
                    <input name="options[comments]" type="checkbox" id="import-comments" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['comments']) && $_POST['options']['comments'] == '1') ? 'checked="checked"' : ''; ?>>
                    <?php _e('Include Comments', 'drupal2wp'); ?>
                </label>
                <p class="description"><?php _e('This will import comments that are associated to your content.', 'drupal2wp'); ?></p>


                <label for="import-media" style="margin-top:20px; display: block;">
                    <input name="options[media]" type="checkbox" id="import-media" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['media']) && $_POST['options']['media'] == '1') ? 'checked="checked"' : ''; ?>>
                    <?php _e('Include Media', 'drupal2wp'); ?>
                </label>
                <p class="description"><?php _e('This will import media assets that are associated to your content.', 'drupal2wp'); ?></p>

                <?php do_action('drupal2wp_import_content_form'); ?>

                <div class="media">

                    <?php $_assocByTitleCount = 0; ?>
                    <label style="margin-top: 10px; display: block; font-weight: bold;"><?php _e('Import content images into the media manager', 'drupal2wp'); ?></label>
                    <p class="description"><?php _e('This will import img tags in the content into the media manager that matches the provided paths. This will update the image path to the new attachment location.', 'drupal2wp'); ?></p>
                    <p class="description"><?php _e('The find path should be exactly as it is in the src tag of the img tags. Relative URLs will use the Drupal Site URL for download.', 'drupal2wp'); ?></p>

                    <label for="drupal_url" style="margin-top:20px; margin-bottom:20px; display: block;">
                        <?php _e('Druapl Site URL:', 'drupal2wp'); ?>
                        &nbsp;
                        <input name="options[drupal_url]" type="text" id="drupal_url" class="large" style="width: 310px;" value="<?php echo !empty($_POST['options']['drupal_url']) ? $_POST['options']['drupal_url'] : ''; ?>" />
                        <p class="description"><?php _e('This is only used for relative pathing mapping below.', 'drupal2wp'); ?></p>
                    </label>

                    <a href="javascript:void(0);" id="add-content-media-import" title="<?php _e('Add Title Content Media import', 'drupal2wp'); ?>" class="button-primary">+</a>
                    <div id="content-media-import-cage">
                        <?php if (!empty($_POST['options']['content_media_import'])) :
                            foreach($_POST['options']['content_media_import'] as $id=>$data) :
                                $_assocByTitleCount++; ?>
                                <div id="content-media-import-<?php echo $_assocByTitleCount; ?>-cage" style="border: 1px solid #CCC; border-radius: 6px; background-color: white; padding: 6px; width: 400px; margin-top: 10px;">
                                    <label style="font-weight: bold;">
                                        <?php _e('Find:', 'drupal2wp'); ?>
                                        <input name="options[content_media_import][<?php echo $_assocByTitleCount; ?>]" type="text" id="content-media-import-<?php echo $_assocByTitleCount; ?>-find" class="small" style="width: 310px;" value="<?php echo !empty($data) ? $data : ''; ?>" />
                                        &nbsp;&nbsp;&nbsp;
                                        <a href="javascript:void(0);" title="<?php _e('Remove Title Association', 'drupal2wp'); ?>" class="button-secondary remove-content-img-import">x</a>
                                    </label>
                                </div>
                            <?php endforeach;
                        else:
                            $_assocByTitleCount++; ?>
                            <div id="content-media-import-<?php echo $_assocByTitleCount; ?>-cage" style="border: 1px solid #CCC; border-radius: 6px; background-color: white; padding: 6px; width: 400px; margin-top: 10px;">
                                <label style="font-weight: bold;">
                                    <?php _e('Find:', 'drupal2wp'); ?>
                                    <input name="options[content_media_import][<?php echo $_assocByTitleCount; ?>]" type="text" id="content-media-import-<?php echo $_assocByTitleCount; ?>-find" class="small" style="width: 310px;" value="" />
                                    &nbsp;&nbsp;&nbsp;
                                    <a href="javascript:void(0);" title="<?php _e('Remove Title Association', 'drupal2wp'); ?>" class="button-secondary remove-content-img-import">x</a>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div id="content-media-import-TITLE_ASSOC_COUNT-cage" style="display:none; border: 1px solid #CCC; border-radius: 6px; background-color: white; padding: 6px; width: 400px; margin-top: 10px;">
                        <label style="font-weight: bold;">
                            <?php _e('Find:', 'drupal2wp'); ?>
                            <input name="options[content_media_import][TITLE_ASSOC_COUNT]" type="text" id="content-media-import-TITLE_ASSOC_COUNT-find" class="small" style="width: 310px;" value="" disabled="disabled" />
                            &nbsp;&nbsp;&nbsp;
                            <a href="javascript:void(0);" title="<?php _e('Remove Title Association', 'drupal2wp'); ?>" class="button-secondary remove-content-img-import">x</a>
                        </label>
                    </div>

                    
                    <label style="margin-top: 20px; display: block; font-weight: bold;"><?php _e('Media asset fail-safe location', 'drupal2wp'); ?></label>
                    <p class="description"><?php _e('This is the path to replace for assets that fail to import into the media manager.', 'drupal2wp'); ?></p>
                    <label for="files-location" style="margin-top:20px; display: block;">
                        <?php _e('Drupal Files Directory URL:', 'drupal2wp'); ?>
                        <input name="options[files_location]" type="text" id="files-location" class="large" style="width: 300px;" value="<?php echo !empty($_POST['options']['files_location']) ? $_POST['options']['files_location'] : ''; ?>">
                        <?php
                        echo ' ';
                        printf( __('(ie: %s)', 'drupal2wp'), 'http://domain.com/sites/default/files/');
                        ?>
                    </label>
                    <p class="description"><?php _e('This is the URL location of the Drupal files directory.', 'drupal2wp'); ?></p>
                    <p class="description"><?php _e('This is to import the media associated to your content through views and is meant to be the current URL for the Drupal site. Attached media is imported into the Media Manager of WordPress.', 'drupal2wp'); ?></p>

                    <label for="wp-drupal-assets-url" style="margin-top:20px; display: block;">
                        <?php _e('WordPress Asset URL:', 'drupal2wp'); ?>
                        <input name="options[wp_drupal_assets_url]" type="text" id="wp-drupal-assets-url" class="large" style="width: 300px;" value="<?php echo !empty($_POST['options']['wp_drupal_assets_url']) ? $_POST['options']['wp_drupal_assets_url'] : '/wp-content/uploads/drupal/'; ?>">
                        <?php
                        echo ' ';
                        printf( __('(ie: %s)', 'drupal2wp'), '/wp-content/uploads/drupal/');
                        ?>
                    </label>
                    <p class="description"><?php _e('This is where the Drupal files are copied to your WordPress install.', 'drupal2wp'); ?></p>
                    <p class="description"><?php _e('This is used with Drupal Files Directory URL to find and replace the URLs to fix assets in the content that is not attached.', 'drupal2wp'); ?></p>

                    <?php do_action('drupal2wp_import_media_form'); ?>
                </div>

            </td>
        </tr>
        <tr>
            <th scope="row"><label for="import-users"><?php _e('Import Users', 'drupal2wp'); ?></label></th>
            <td><input name="options[users]" type="checkbox" id="import-users" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['users']) && $_POST['options']['users'] == '1') ? 'checked="checked"' : ''; ?>></td>
        </tr>
        <tr class="users">
            <th scope="row">&nbsp;</th>
            <td>
                <div id="user-role-associations-cage">
                    <label style="display: block; font-weight: bold;"><?php _e('User Roles Association:', 'drupal2wp'); ?></label>
                    <p class="description"><?php _e('This allows you to associate Drupal user roles to each available WordPress user role.', 'drupal2wp'); ?></p>
                    <p class="description"><?php _e('Each box is the WordPress user role. Each list shows the available Drupal user roles for association.', 'drupal2wp'); ?></p>
                    <p class="description"><strong><?php _e('Non-associated content will not be imported!', 'drupal2wp'); ?></strong></p>
                    <?php foreach($TEMPLATE_VARS['wpUserRoles'] as $wpUR=>$wpURData) : ?>
                        <div id="user-role-associations-<?php echo $wpUR; ?>-cage" style="display: inline-block; width: 200px; vertical-align: top; margin: 4px;">
                            <h4 style="margin: 4px 0 4px 8px;"><?php echo ucwords( str_replace(array('-', '_'), ' ', $wpURData['name']) ); ?></h4>
                            <ul class="user-role-associations" style="background-color: #fff;padding: 7px;border: 1px solid #CCC;border-radius: 6px;overflow: auto;max-height: 200px;margin: 0;">
                                <?php foreach($TEMPLATE_VARS['drupalUserRoles'] as $dUR=>$dURName) :
                                    $autoCheck = !isset($_POST['options']['user_roles_associations']) ? (
                                        ('administrator' === $wpUR && 'administrator' === $dURName)
                                        ||
                                        ('editor' === $wpUR && 'editor' === $dURName)
                                        ||
                                        ('subscriber' === $wpUR && 'subscriber' === $dURName)
                                    ) : false;
                                    ?>
                                    <li id="<?php echo $wpUR.'-'.$dUR; ?>-role" class="ca-<?php echo $dUR; ?>">
                                        <label>
                                            <input type="checkbox" id="<?php echo $wpUR.'__'.$dUR; ?>" name="options[user_roles_associations][<?php echo $dUR; ?>]" value="<?php echo $wpUR; ?>" class="regular-checkbox" <?php echo ($autoCheck || isset($_POST['options']['user_roles_associations'][$dUR]) && $_POST['options']['user_roles_associations'][$dUR] == $wpUR ) ? 'checked="checked"' : ''; ?>>
                                            <?php echo ucwords( str_replace('_', ' ', $dURName) ); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <label for="enabled-users-only" style="margin-top:20px; display: block;">
                    <input name="options[enabled_users_only]" type="checkbox" id="enabled-users-only" value="1" class="regular-checkbox" <?php echo (isset($_POST['options']['enabled_users_only']) && $_POST['options']['enabled_users_only'] == '1') ? 'checked="checked"' : ''; ?>>
                    <?php _e('Enabled Users Only', 'drupal2wp'); ?>
                </label>
                <p class="description"><?php _e('All users are imported by default. This will only import enabled users.', 'drupal2wp'); ?></p>


                <?php do_action('drupal2wp_import_users_form'); ?>
            </td>
        </tr>
        <tr class="users-unchecked">
            <th scope="row">&nbsp;</th>
            <td>
                <label for="import-users"><?php _e('Associated Content to User ID', 'drupal2wp'); ?>
                    <input name="options[associate_content_user_id]" type="text" id="associate-content-user-id" value="<?php echo isset($_POST['options']['associate_content_user_id']) ? $_POST['options']['associate_content_user_id'] : '1'; ?>" class="medium-text">
                </label>
                <p class="description"><?php _e('If not importing users, you need to associate content to a specific user ID. If no ID is entered, all content will be imported with the ID of the Drupal user (this is not ideal)', 'drupal2wp'); ?></p>

                <?php do_action('drupal2wp_import_users_unchecked_form'); ?>
            </td>
        </tr>
        </tbody>
    </table>
    <?php do_action('drupal2wp_view_step2_in_form'); ?>
    <p class="submit"><input type="submit" id="submit" class="button button-primary" value="<?php _e('Start Import', 'drupal2wp'); ?>"></p>
    <p class="description"><?php _e('Proceeding to the next step will start the import process. This can take a long time depending on the size of the Drupal database.', 'drupal2wp'); ?></p>
</form>
<?php do_action('drupal2wp_view_step2'); ?>
<script type="text/javascript">
    jQuery(document).ready(function($){
        var _assocByTitleCount = <?php echo $_assocByTitleCount; ?>;
        // Hide child options
        $(".terms, .content, .media, .users").hide();
        // Add parent child toggles
        $("#import-terms, #import-content, #import-users, #import-media").on("change", function() {
            switch (this.id) {
                case "import-terms":
                    _drupal2wp_child_toggle(".terms", $(this).is(":checked"));
                    break;
                case "import-content":
                    _drupal2wp_child_toggle(".content", $(this).is(":checked"));
                    break;
                case "import-users":
                    var _isChecked = $(this).is(":checked");
                    _drupal2wp_child_toggle(".users-unchecked", !_isChecked);
                    _drupal2wp_child_toggle(".users", _isChecked);
                    break;
                case "import-media":
                    _drupal2wp_child_toggle(".media", $(this).is(":checked"));
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
            $("#term-associations-cage .term-associations .ca-"+_drupalPT).not("#"+_selectedPT+"-"+_drupalPT+"-term").each(function() {
                if (_isChecked) {
                    $(this).find("label").css("opacity",.7).find("input").prop("disabled", true);
                } else {
                    $(this).find("label").css("opacity", 1).find("input").prop("disabled", false);
                }
            });
        });
        // Add user-role-associations logic
        $(".user-role-associations input").on("change", function() {
            var _tmpData = this.id.split("__");
            var _selectedWPRole = _tmpData[0];
            var _drupalPT = _tmpData[1];
            var _isChecked = $(this).is(":checked");
            // Disable other wp post types from being able to use the selected drupal PT
            $("#user-role-associations-cage .user-role-associations .ca-"+_drupalPT).not("#"+_selectedWPRole+"-"+_drupalPT+"-role").each(function() {
                if (_isChecked) {
                    $(this).find("label").css("opacity",.7).find("input").prop("disabled", true);
                } else {
                    $(this).find("label").css("opacity", 1).find("input").prop("disabled", false);
                }
            });
        });
        // Toggle change for selected import options
        $("#import-terms, #import-content, #import-users").each(function() {
            if ($(this).is(":checked")) {
                $(this).change();
            }
        });
        // Toggle change for selected content-associations
        $("#content-associations-cage .content-associations li input:checked, #term-associations-cage .term-associations li input:checked, #user-role-associations-cage .user-role-associations li input:checked").each(function() {
            $(this).change();
        });
        // Add click event to add more associate by title options
        $("#add-title-assoc").on("click", function(e){
            e.preventDefault();
            // Make sure the last title association has a value
            if (!$("#advanced-content > div:last-child").find("label:first-child input[type='text']").val()) {
                return false;
            }
            // Build the new div
            var tmpHTML = $("#content-title-associations-TITLE_ASSOC_COUNT-cage").clone();
            ++_assocByTitleCount;
            // Fix ID
            tmpHTML.attr('id', 'content-title-associations-'+_assocByTitleCount+'-cage');
            // Remove display none
            tmpHTML.css('display', 'block');
            // Fix
            tmpHTML.find('*').not('option').each(function() {
                // Fix ID
                var tmp = $(this).attr('id');
                if (tmp) {
                    $(this).attr('id', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // Fix name
                tmp = $(this).attr('name');
                if (tmp) {
                    $(this).attr('name', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // Fix list
                tmp = $(this).attr('list');
                if (tmp) {
                    $(this).attr('list', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // Fix for
                tmp = $(this).attr('for');
                if (tmp) {
                    $(this).attr('for', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // remove disabled
                if ($(this).is(":input")) {
                    $(this).prop("disabled", false);
                }
            });
            // Remove add toggle
            tmpHTML.find('#add-title-assoc').remove();
            // Add to cage
            $("#advanced-content").append(tmpHTML);
        });
        // Remove title association event
        $("#advanced-content").on("click", ".remove-title-assoc", function(e) {
            e.preventDefault();
            var cage = $(this).closest("div");
            $(cage).slideUp(function() {
                $(this).remove();
            });
        });


        // Add click event to add more content img import options
        $("#add-content-media-import").on("click", function(e){
            e.preventDefault();
            // Make sure the last title association has a value
            var _tmp = $("#content-media-import-cage > div:last-child").find("label:first-child input[type='text']").val();
            if (undefined !== _tmp && !_tmp) {
                return false;
            }
            // Build the new div
            var tmpHTML = $("#content-media-import-TITLE_ASSOC_COUNT-cage").clone();
            ++_assocByTitleCount;
            // Fix ID
            tmpHTML.attr('id', 'content-media-import-'+_assocByTitleCount+'-cage');
            // Remove display none
            tmpHTML.css('display', 'block');
            // Fix
            tmpHTML.find('*').not('option').each(function() {
                // Fix ID
                var tmp = $(this).attr('id');
                if (tmp) {
                    $(this).attr('id', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // Fix name
                tmp = $(this).attr('name');
                if (tmp) {
                    $(this).attr('name', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // Fix list
                tmp = $(this).attr('list');
                if (tmp) {
                    $(this).attr('list', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // Fix for
                tmp = $(this).attr('for');
                if (tmp) {
                    $(this).attr('for', tmp.replace('TITLE_ASSOC_COUNT', _assocByTitleCount));
                }
                // remove disabled
                if ($(this).is(":input")) {
                    $(this).prop("disabled", false);
                }
            });
            // Remove add toggle
            tmpHTML.find('#add-title-assoc').remove();
            // Add to cage
            $("#content-media-import-cage").append(tmpHTML);
        });
        // Hide replace on import
        // Remove content img import
        $("#content-media-import-cage").on("click", ".remove-content-img-import", function(e) {
            e.preventDefault();
            var cage = $(this).closest("div");
            $(cage).slideUp(function() {
                $(this).remove();
            });
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