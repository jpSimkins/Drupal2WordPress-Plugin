Drupal2WordPress Plugin 
=======================

WordPress 3.x+ plugin for importing Drupal 7 (works for WordPress 4.x too)

This plugin was originally based off the repo: https://github.com/lirantal/Drupal2WordPress

As I worked with this plugin I simplified and improved many parts of the process. I also added a way to associate data points to make this more useful.

## Features
* WordPress plugin
* Import options
    - Choose from importing terms, content, media, comments, and/or users
        - comments and media can only be imported if content is
    - Associate WordPress taxonomies and post types to Drupal node types and vocabulary
* Cleans Drupal aliases
    - Outputs `.htaccess` rewrite rules for alias changes


## Import Options
* **Content (Drupal nodes)** 
    - You can associate Drupal node types to WordPress post types.
    - All nodes are imported with their original owner user id (if users are imported, otherwise you can choose the user ID to associate all content to), timestamp, published or unpublished state. 
    - With regards to SEO, Drupal's leading 'content/' prefix for any page is removed.
        - Drupal was more forgiving with malformed aliases (slugs), this plugin will clean the alias to a proper slug.
            - These changes are outputted for you to add to your .htaccess file to do proper redirects.
    - Comments on Content (up to 11 levels of threaded comments)
        - Only approved comments are imported due to the high level of spam which Drupal sites might endure (in Drupal this means all comments with status 1)
    - Images are automatically imported into the media manager.
        - img tags in content are also auto imported to the media manager and updated in the content.
            - To prevent stealing images from third-party sites, you are to add basic path presets to find and replace.
    - You can move content by title prefix to different `post_types`.
* **Terms**
    - You can associate Drupal vocabulary types to WordPress taxonomies.
    - _Categories_ 
        - WordPress requires that any blog post is associated with at least one category, and not just a tag, hence the script will create a default category (you get to decide what it is) and associate all of the content created into that category.
    - _Tags_
        - Note that WordPress does not support nested tags
            - This can be fixed by using a plugin
* **Users** 
    - Drupal's user id 0 (anonymous) and user id 1 (site admin) are ignored. 
    - User's basic information is migrated, such as username, e-mail and creation date. 
        - **Users are migrated with no password, which means in WordPress that they can't login and must reset their account details (this is due to security reasons).**
    - Adds default user meta

## Important Info

**This should go without saying but make sure to test this script locally before using on a production environment**

* The script will truncate (delete all records) for the options you select. 
    - This means original WordPress content will be lost, if you have any. (This is to keep the content IDs matching for all options)
        - If you want to keep your content, export it using: _Tools_ -> _Export_
        - When Drupal has been imported, you can import your old data by using: _Tools_ -> _Import_
* Install this plugin as any other plugin
    - Once activated, you will find the importer under _Tools_ -> _Import_ -> _Drupal 2 WordPress_
    
## Works Best With
- Custom Content Type Manager (https://wordpress.org/plugins/custom-content-type-manager/)
- Simple Taxonomy (https://wordpress.org/plugins/simple-taxonomy/)

## Suggested Workflow

This is the workflow I used to develop this plugin.
- Install and activate this plugin
- Install Custom Content Type Manager and activate (optional)
    - Basically you want to have your post_type's setup for the import process.
- Install Simple Taxonomy and activate (optional)
    - Basically you want to have your taxonomies setup for the import process.
- Create needed post types for import
- Create taxonomies for import
- Run this plugin to start the import process
- Select desired options
- Associate the taxonomies (optional)
- Associate the post types (optional)
- Import data
- Add any `.htaccess` edits if necessary
- Enjoy :)

## Todo
- [x] Add Drupal version selector to allow for 6 and 8 to be added
- [x] Allow for custom post type association
- [x] Allow for custom taxonomy association
- [x] Import media sources
- [x] Add hooks to allow for easy customization for unique setups
- [ ] Add multi-lingual import option (site I'm working on uses two languages so I have duplicate content as each post has two versions)
- [ ] L10n

## Known Issues
- [ ] Long titles do not return a valid slug. This is WordPress behavior.
- [ ] Rewrite is not perfect. Most installs this seems to work with no issues. Some installs (typically bad setups) have wrong rewrites.
- [ ] Duplicate tags are appened with `-DUP`. Only an issue if you have duplicate vocabulary

