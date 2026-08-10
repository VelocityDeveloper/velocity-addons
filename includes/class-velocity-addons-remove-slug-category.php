<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://velocitydeveloper.com
 * @since      1.0.0
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 * @author     Velocity <bantuanvelocity@gmail.com>
 */

 class Velocity_Addons_Remove_Slug_Category {
    public function __construct() {
        add_filter('post_type_link', array($this, 'remove_category_slug'), 10, 2);
        add_filter('get_the_archive_title', array($this, 'remove_category_from_archive_title'));
        add_action('init', array($this, 'flush_rewrite_rules'));
    }

    public function remove_category_slug($post_link, $post) {
        $remove_slug_category = get_option('remove_slug_category_velocity');

        if ($remove_slug_category && 'post' === $post->post_type && 'publish' === $post->post_status) {
            $categories = get_the_category($post->ID);

            if ($categories) {
                $category_slug = $categories[0]->slug;
                $post_link = str_replace('/category/' . $category_slug, '/', $post_link);
            }
        }

        return $post_link;
    }

    public function remove_category_from_archive_title($title) {
        $remove_category_archive_title = get_option('remove_category_archive_title_velocity', '1');

        if ($remove_category_archive_title && is_category()) {
            return single_cat_title('', false);
        }

        return $title;
    }

    public function flush_rewrite_rules() {
        $remove_slug_category = get_option('remove_slug_category_velocity');

        if ($remove_slug_category) {
            flush_rewrite_rules();
        }
    }
}

// Inisialisasi class Velocity_Addons_Remove_Slug_Category
$Velocity_Addons_Remove_Slug_Category = new Velocity_Addons_Remove_Slug_Category();
