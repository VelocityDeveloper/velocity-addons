<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://velocitydeveloper.com
 * @since             1.0.6
 * @package           Velocity_Addons
 *
 * @wordpress-plugin
 * Plugin Name:       Velocity Addons
 * Plugin URI:        https://velocitydeveloper.com
 * Description:       Toolkit pengaturan, keamanan, SEO, optimasi, dan 1 Click Setup untuk WordPress.
 * Version:           2.1.9
 * Author:            Velocity
 * Author URI:        https://velocitydeveloper.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       velocity-addons
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('VELOCITY_ADDONS_VERSION', '2.1.9');
define('VELOCITY_ADDONS_DB_VERSION', VELOCITY_ADDONS_VERSION);
define('PLUGIN_DIR', plugin_dir_path(__DIR__));
define('PLUGIN_FILE', plugin_basename(__FILE__));
define('PLUGIN_BASE_NAME', plugin_basename(__DIR__));
define('VELOCITY_ADDONS_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
require_once plugin_dir_path(__FILE__) . 'includes/depracated.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-velocity-addons-activator.php
 */
function activate_velocity_addons()
{
    ob_start();
    try {
        require_once plugin_dir_path(__FILE__) . 'includes/class-velocity-addons-activator.php';
        Velocity_Addons_Activator::activate();
    } finally {
        $buffer = ob_get_clean();
        if (!empty($buffer) && function_exists('error_log')) {
            error_log('[velocity-addons] Activation output suppressed: ' . wp_strip_all_tags($buffer));
        }
    }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-velocity-addons-deactivator.php
 */
function deactivate_velocity_addons()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-velocity-addons-deactivator.php';
    Velocity_Addons_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_velocity_addons');
register_deactivation_hook(__FILE__, 'deactivate_velocity_addons');

/**
 * Ensure DB schema/setup runs after silent core/plugin updates.
 */
function velocity_addons_maybe_upgrade_after_update()
{
    if (! is_admin() || ! current_user_can('activate_plugins')) {
        return;
    }

    $installed_version = get_option('velocity_addons_db_version');
    if ($installed_version && version_compare($installed_version, VELOCITY_ADDONS_DB_VERSION, '>=')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-velocity-addons-activator.php';

    // Re-run activator to (re)generate tables, cron, etc.
    Velocity_Addons_Activator::activate();
}
add_action('admin_init', 'velocity_addons_maybe_upgrade_after_update', 5);

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-velocity-addons.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_velocity_addons()
{

    $plugin = new Velocity_Addons();
    $plugin->run();
}
run_velocity_addons();

// One-time additive import from legacy statistics (if site updated without re-activation)
add_action('admin_init', function () {
    if (get_option('velocity_addons_stats_legacy_added') || get_option('velocity_addons_stats_migrated')) return;
    // Run only for users who can manage options to avoid front-end overhead
    if (! current_user_can('manage_options')) return;
    require_once plugin_dir_path(__FILE__) . 'includes/class-velocity-addons-statistic-legacy.php';
    $migrator = new Velocity_Addons_Statistic_Legacy(null, false);
    ob_start();
    $migrator->run();
    $buffer = ob_get_clean();
    if (!empty($buffer) && function_exists('error_log')) {
        error_log('[velocity-addons] Legacy migrator output suppressed: ' . wp_strip_all_tags($buffer));
    }
});
