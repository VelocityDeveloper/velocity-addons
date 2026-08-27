<?php

/**
 * REST endpoints for remote control from CRM (new-vdnet).
 *
 * All endpoints require HMAC-SHA256 signed requests via
 * Velocity_Addons_Remote_Auth::verify().
 *
 * @package Velocity_Addons
 * @subpackage Velocity_Addons/admin
 */

defined('ABSPATH') || exit;

class Velocity_Addons_Remote_Control_REST
{
    /** @var string */
    private $namespace = 'velocity-addons/v1/remote';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    // -----------------------------------------------------------------------
    // Route registration
    // -----------------------------------------------------------------------

    public function register_routes()
    {
        // --- Key exchange (one-time, uses relay key) ---
        register_rest_route($this->namespace, '/key-exchange', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'key_exchange'),
            'permission_callback' => '__return_true',
        ));

        // --- Status / heartbeat ---
        register_rest_route($this->namespace, '/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_status'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        // --- WordPress core update ---
        register_rest_route($this->namespace, '/wp/update', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'update_wp_core'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        // --- Theme management ---
        register_rest_route($this->namespace, '/themes', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'list_themes'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/themes/install', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'install_theme'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/themes/update', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'update_theme'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/themes/activate', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'activate_theme'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        // --- Plugin management ---
        register_rest_route($this->namespace, '/plugins', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'list_plugins'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/plugins/install', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'install_plugin'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/plugins/update', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'update_plugin'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/plugins/activate', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'activate_plugin'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/plugins/deactivate', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'deactivate_plugin'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/plugins/delete', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'delete_plugin'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        // --- Bulk operations ---
        register_rest_route($this->namespace, '/bulk/update-all', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'update_all'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        // --- Settings read/write ---
        register_rest_route($this->namespace, '/settings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_settings'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        register_rest_route($this->namespace, '/settings', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'update_settings'),
            'permission_callback' => array($this, 'verify_remote_auth'),
        ));

        // --- Remote secret management (from WP admin) ---
        register_rest_route($this->namespace, '/secret', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_secret_status'),
            'permission_callback' => array($this, 'permissions_manage_options'),
        ));

        register_rest_route($this->namespace, '/secret/rotate', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'rotate_secret'),
            'permission_callback' => array($this, 'permissions_manage_options'),
        ));
    }

    // -----------------------------------------------------------------------
    // Permission callbacks
    // -----------------------------------------------------------------------

    public function permissions_manage_options()
    {
        return current_user_can('manage_options');
    }

    public function verify_remote_auth()
    {
        $result = Velocity_Addons_Remote_Auth::verify();
        if (is_wp_error($result)) {
            return $result;
        }
        return true;
    }

    // -----------------------------------------------------------------------
    // Key exchange (one-time setup)
    // -----------------------------------------------------------------------

    /**
     * POST /remote/key-exchange
     *
     * Body: { "relay_key": "...", "site_domain": "..." }
     *
     * Uses the relay key (same one used for WP install) to establish
     * a dedicated remote control secret. This endpoint is only available
     * when no remote secret is configured yet.
     */
    public function key_exchange(WP_REST_Request $request)
    {
        // Only allow when remote secret is NOT yet configured
        if (Velocity_Addons_Remote_Auth::is_configured()) {
            return new WP_Error(
                'velocity_remote_already_configured',
                'Remote control is already configured. Use rotate endpoint instead.',
                array('status' => 409)
            );
        }

        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $relay_key = isset($payload['relay_key']) ? sanitize_text_field((string) $payload['relay_key']) : '';

        // Verify relay key (same mechanism used during WP install)
        $expected_relay_key = defined('VELOCITY_RELAY_KEY') ? VELOCITY_RELAY_KEY : '';
        if ($expected_relay_key === '' || !hash_equals($expected_relay_key, $relay_key)) {
            return new WP_Error(
                'velocity_remote_invalid_relay_key',
                'Invalid relay key.',
                array('status' => 401)
            );
        }

        // Generate and store the remote secret
        $new_secret = Velocity_Addons_Remote_Auth::rotate_secret();

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Remote control secret generated. Store this securely — it cannot be retrieved again.',
            'secret'  => $new_secret,
        ));
    }

    // -----------------------------------------------------------------------
    // Status / heartbeat
    // -----------------------------------------------------------------------

    public function get_status()
    {
        $wp_version = get_bloginfo('version');
        $theme      = wp_get_theme();
        $plugins    = get_plugins();
        $active     = get_option('active_plugins', array());

        $plugin_updates  = get_site_transient('update_plugins');
        $theme_updates   = get_site_transient('update_themes');
        $core_updates    = get_site_transient('update_core');

        $pending_plugin_updates = array();
        if (is_object($plugin_updates) && !empty($plugin_updates->response)) {
            foreach ($plugin_updates->response as $file => $data) {
                $pending_plugin_updates[] = array(
                    'file'        => $file,
                    'new_version' => isset($data->new_version) ? $data->new_version : '',
                );
            }
        }

        $pending_theme_updates = array();
        if (is_object($theme_updates) && !empty($theme_updates->response)) {
            foreach ($theme_updates->response as $slug => $data) {
                $pending_theme_updates[] = array(
                    'slug'        => $slug,
                    'new_version' => isset($data['new_version']) ? $data['new_version'] : '',
                );
            }
        }

        $wp_update_available = false;
        $wp_latest_version = '';
        if (is_object($core_updates) && !empty($core_updates->updates)) {
            foreach ($core_updates->updates as $update) {
                if (isset($update->response) && $update->response === 'upgrade') {
                    $wp_update_available = true;
                    $wp_latest_version = isset($update->version) ? $update->version : '';
                    break;
                }
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'data'    => array(
                'wp_version'              => $wp_version,
                'wp_update_available'     => $wp_update_available,
                'wp_latest_version'       => $wp_latest_version,
                'php_version'             => PHP_VERSION,
                'active_theme'            => array(
                    'name'    => $theme->get('Name'),
                    'version' => $theme->get('Version'),
                    'slug'    => $theme->get_stylesheet(),
                ),
                'total_plugins'           => count($plugins),
                'active_plugins'          => count($active),
                'pending_plugin_updates'  => $pending_plugin_updates,
                'pending_theme_updates'   => $pending_theme_updates,
                'velocity_addons_version' => defined('VELOCITY_ADDONS_VERSION') ? VELOCITY_ADDONS_VERSION : '',
                'site_url'                => site_url(),
                'home_url'                => home_url(),
                'server_time'             => current_time('mysql'),
            ),
        ));
    }

    // -----------------------------------------------------------------------
    // WordPress core update
    // -----------------------------------------------------------------------

    public function update_wp_core()
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';

        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-includes/update.php';
        }

        // Check for updates first
        $current = get_bloginfo('version');
        wp_version_check();
        $core_updates = get_site_transient('update_core');

        $has_update = false;
        if (is_object($core_updates) && !empty($core_updates->updates)) {
            foreach ($core_updates->updates as $update) {
                if (isset($update->response) && $update->response === 'upgrade') {
                    $has_update = true;
                    break;
                }
            }
        }

        if (!$has_update) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'WordPress is already up to date.',
                'version' => $current,
            ));
        }

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Core_Upgrader($skin);
        $result   = $upgrader->upgrade();

        if (is_wp_error($result)) {
            return new WP_Error(
                'velocity_remote_wp_update_failed',
                $result->get_error_message(),
                array('status' => 500)
            );
        }

        return rest_ensure_response(array(
            'success'      => true,
            'message'      => 'WordPress updated successfully.',
            'old_version'  => $current,
            'new_version'  => get_bloginfo('version'),
        ));
    }

    // -----------------------------------------------------------------------
    // Theme management
    // -----------------------------------------------------------------------

    public function list_themes()
    {
        $themes = wp_get_themes();
        $active_slug = get_option('stylesheet');
        $result = array();

        foreach ($themes as $slug => $theme) {
            $result[] = array(
                'slug'         => $slug,
                'name'         => $theme->get('Name'),
                'version'      => $theme->get('Version'),
                'author'       => $theme->get('Author'),
                'active'       => ($slug === $active_slug),
                'parent_theme' => $theme->parent() ? $theme->parent()->get('Stylesheet') : '',
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'themes'  => $result,
        ));
    }

    public function install_theme(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $slug = isset($payload['slug']) ? sanitize_text_field((string) $payload['slug']) : '';
        $source = isset($payload['source']) ? esc_url_raw((string) $payload['source']) : '';

        if ($slug === '' && $source === '') {
            return new WP_Error('velocity_remote_theme_required', 'Either slug or source URL is required.', array('status' => 400));
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/theme-install.php';

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Theme_Upgrader($skin);

        if ($source !== '') {
            $result = $upgrader->install($source);
        } else {
            $result = $upgrader->install($slug);
        }

        if (is_wp_error($result)) {
            return new WP_Error('velocity_remote_theme_install_failed', $result->get_error_message(), array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Theme installed successfully.',
            'slug'    => $slug,
        ));
    }

    public function update_theme(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $slug = isset($payload['slug']) ? sanitize_text_field((string) $payload['slug']) : '';

        if ($slug === '') {
            return new WP_Error('velocity_remote_theme_slug_required', 'Theme slug is required.', array('status' => 400));
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        if (!function_exists('wp_update_themes')) {
            require_once ABSPATH . 'wp-includes/update.php';
        }
        wp_update_themes();

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Theme_Upgrader($skin);
        $result   = $upgrader->upgrade($slug);

        if (is_wp_error($result)) {
            return new WP_Error('velocity_remote_theme_update_failed', $result->get_error_message(), array('status' => 500));
        }

        $theme = wp_get_theme($slug);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Theme updated successfully.',
            'slug'    => $slug,
            'version' => $theme->get('Version'),
        ));
    }

    public function activate_theme(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $slug = isset($payload['slug']) ? sanitize_text_field((string) $payload['slug']) : '';

        if ($slug === '') {
            return new WP_Error('velocity_remote_theme_slug_required', 'Theme slug is required.', array('status' => 400));
        }

        $theme = wp_get_theme($slug);
        if (!$theme->exists()) {
            return new WP_Error('velocity_remote_theme_not_found', 'Theme not found.', array('status' => 404));
        }

        switch_theme($slug);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Theme activated successfully.',
            'slug'    => $slug,
        ));
    }

    // -----------------------------------------------------------------------
    // Plugin management
    // -----------------------------------------------------------------------

    public function list_plugins()
    {
        $all_plugins    = get_plugins();
        $active_plugins = get_option('active_plugins', array());
        $update_data    = get_site_transient('update_plugins');
        $result         = array();

        foreach ($all_plugins as $file => $data) {
            $update_info = null;
            if (is_object($update_data) && !empty($update_data->response) && isset($update_data->response[$file])) {
                $u = $update_data->response[$file];
                $update_info = array(
                    'new_version' => isset($u->new_version) ? $u->new_version : '',
                    'package'     => isset($u->package) ? $u->package : '',
                );
            }

            $result[] = array(
                'file'           => $file,
                'name'           => $data['Name'],
                'slug'           => $data['PluginURI'] ? basename(dirname($file)) : '',
                'version'        => $data['Version'],
                'author'         => $data['Author'],
                'active'         => in_array($file, $active_plugins, true),
                'update'         => $update_info,
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'plugins' => $result,
        ));
    }

    public function install_plugin(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $slug   = isset($payload['slug']) ? sanitize_text_field((string) $payload['slug']) : '';
        $source = isset($payload['source']) ? esc_url_raw((string) $payload['source']) : '';
        $activate = !empty($payload['activate']);

        if ($slug === '' && $source === '') {
            return new WP_Error('velocity_remote_plugin_required', 'Either slug or source URL is required.', array('status' => 400));
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);

        $package = $source !== '' ? $source : $slug;
        $result  = $upgrader->install($package);

        if (is_wp_error($result)) {
            return new WP_Error('velocity_remote_plugin_install_failed', $result->get_error_message(), array('status' => 500));
        }

        $installed_file = '';
        if ($slug !== '') {
            // Try to find the installed plugin file
            wp_clean_plugins_cache(true);
            $all = get_plugins();
            foreach ($all as $file => $data) {
                if (0 === strpos($file, $slug . '/')) {
                    $installed_file = $file;
                    break;
                }
            }
        }

        if ($activate && $installed_file !== '') {
            activate_plugin($installed_file);
        }

        return rest_ensure_response(array(
            'success'      => true,
            'message'      => 'Plugin installed successfully.',
            'slug'         => $slug,
            'plugin_file'  => $installed_file,
            'activated'    => $activate && $installed_file !== '',
        ));
    }

    public function update_plugin(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $file = isset($payload['file']) ? sanitize_text_field((string) $payload['file']) : '';

        if ($file === '') {
            return new WP_Error('velocity_remote_plugin_file_required', 'Plugin file path is required.', array('status' => 400));
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-includes/update.php';
        }
        wp_update_plugins();

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result   = $upgrader->upgrade($file);

        if (is_wp_error($result)) {
            return new WP_Error('velocity_remote_plugin_update_failed', $result->get_error_message(), array('status' => 500));
        }

        wp_clean_plugins_cache(true);
        $all = get_plugins();

        $version = '';
        if (isset($all[$file])) {
            $version = $all[$file]['Version'];
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Plugin updated successfully.',
            'file'    => $file,
            'version' => $version,
        ));
    }

    public function activate_plugin(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $file = isset($payload['file']) ? sanitize_text_field((string) $payload['file']) : '';

        if ($file === '') {
            return new WP_Error('velocity_remote_plugin_file_required', 'Plugin file path is required.', array('status' => 400));
        }

        if (!file_exists(WP_PLUGIN_DIR . '/' . $file)) {
            return new WP_Error('velocity_remote_plugin_not_found', 'Plugin file not found.', array('status' => 404));
        }

        $result = activate_plugin($file);
        if (is_wp_error($result)) {
            return new WP_Error('velocity_remote_plugin_activate_failed', $result->get_error_message(), array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Plugin activated successfully.',
            'file'    => $file,
        ));
    }

    public function deactivate_plugin(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $file = isset($payload['file']) ? sanitize_text_field((string) $payload['file']) : '';

        if ($file === '') {
            return new WP_Error('velocity_remote_plugin_file_required', 'Plugin file path is required.', array('status' => 400));
        }

        // Prevent deactivating velocity-addons itself
        if ($file === 'velocity-addons/velocity-addons.php') {
            return new WP_Error('velocity_remote_cannot_deactivate_self', 'Cannot deactivate velocity-addons via remote control.', array('status' => 403));
        }

        deactivate_plugins($file);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Plugin deactivated successfully.',
            'file'    => $file,
        ));
    }

    public function delete_plugin(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $file = isset($payload['file']) ? sanitize_text_field((string) $payload['file']) : '';

        if ($file === '') {
            return new WP_Error('velocity_remote_plugin_file_required', 'Plugin file path is required.', array('status' => 400));
        }

        // Prevent deleting velocity-addons itself
        if ($file === 'velocity-addons/velocity-addons.php') {
            return new WP_Error('velocity_remote_cannot_delete_self', 'Cannot delete velocity-addons via remote control.', array('status' => 403));
        }

        // Deactivate first if active
        if (is_plugin_active($file)) {
            deactivate_plugins($file);
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result   = $upgrader->delete_plugin($file);

        if (is_wp_error($result)) {
            return new WP_Error('velocity_remote_plugin_delete_failed', $result->get_error_message(), array('status' => 500));
        }

        wp_clean_plugins_cache(true);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Plugin deleted successfully.',
            'file'    => $file,
        ));
    }

    // -----------------------------------------------------------------------
    // Bulk operations
    // -----------------------------------------------------------------------

    public function update_all()
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-includes/update.php';
        }
        wp_update_plugins();
        wp_update_themes();

        $results = array(
            'plugins' => array(),
            'themes'  => array(),
        );

        // Update plugins
        $plugin_updates = get_site_transient('update_plugins');
        if (is_object($plugin_updates) && !empty($plugin_updates->response)) {
            foreach ($plugin_updates->response as $file => $data) {
                $skin     = new Automatic_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                $result   = $upgrader->upgrade($file);

                $results['plugins'][] = array(
                    'file'    => $file,
                    'success' => !is_wp_error($result) && $result,
                    'error'   => is_wp_error($result) ? $result->get_error_message() : '',
                );
            }
        }

        // Update themes
        $theme_updates = get_site_transient('update_themes');
        if (is_object($theme_updates) && !empty($theme_updates->response)) {
            foreach ($theme_updates->response as $slug => $data) {
                $skin     = new Automatic_Upgrader_Skin();
                $upgrader = new Theme_Upgrader($skin);
                $result   = $upgrader->upgrade($slug);

                $results['themes'][] = array(
                    'slug'    => $slug,
                    'success' => !is_wp_error($result) && $result,
                    'error'   => is_wp_error($result) ? $result->get_error_message() : '',
                );
            }
        }

        wp_clean_plugins_cache(true);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Bulk update completed.',
            'results' => $results,
        ));
    }

    // -----------------------------------------------------------------------
    // Settings read/write
    // -----------------------------------------------------------------------

    public function get_settings(WP_REST_Request $request)
    {
        $keys_param = $request->get_param('keys');
        $keys = array();

        if (is_string($keys_param) && $keys_param !== '') {
            $keys = array_map('trim', explode(',', $keys_param));
        }

        // Whitelist of options safe to read remotely
        $allowed = $this->get_allowed_options();

        if (empty($keys)) {
            $keys = array_keys($allowed);
        }

        $settings = array();
        foreach ($keys as $key) {
            $key = sanitize_key($key);
            if (!isset($allowed[$key])) {
                continue;
            }
            $settings[$key] = get_option($key, $allowed[$key]);
        }

        return rest_ensure_response(array(
            'success'  => true,
            'settings' => $settings,
        ));
    }

    public function update_settings(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $settings = isset($payload['settings']) && is_array($payload['settings']) ? $payload['settings'] : $payload;
        $allowed  = $this->get_allowed_options();
        $updated  = array();

        foreach ($settings as $key => $value) {
            $key = sanitize_key($key);
            if (!isset($allowed[$key])) {
                continue;
            }
            update_option($key, $value);
            $updated[] = $key;
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Settings updated.',
            'updated' => $updated,
        ));
    }

    /**
     * Whitelist of options that can be read/written remotely.
     *
     * @return array
     */
    private function get_allowed_options()
    {
        return array(
            // General
            'seo_velocity'              => '1',
            'statistik_velocity'        => '1',
            'floating_whatsapp'         => '1',
            'news_generate'             => '1',
            'hide_admin_notice'         => '1',
            'disable_comments'          => '1',
            'disable_xmlrpc'            => '1',
            'disable_rest_api'          => '0',
            'disable_gutenberg'         => '0',
            'remove_slug_category'      => '0',
            'limit_login'               => '0',
            'limit_login_attempts'      => '5',
            'limit_login_lockout_time'  => '30',
            // Maintenance
            'maintenance_mode'          => '0',
            'maintenance_message'       => '',
            // WhatsApp
            'nomor_whatsapp'            => '',
            'whatsapp_position'         => 'right',
            'scrolltotop_position'      => 'right',
            // SEO
            'seo_title'                 => '',
            'seo_description'           => '',
            'seo_keywords'              => '',
            // Snippet
            'header_snippet'            => '',
            'body_snippet'              => '',
            'footer_snippet'            => '',
        );
    }

    // -----------------------------------------------------------------------
    // Secret management (WP admin only)
    // -----------------------------------------------------------------------

    public function get_secret_status()
    {
        return rest_ensure_response(array(
            'success'     => true,
            'configured'  => Velocity_Addons_Remote_Auth::is_configured(),
        ));
    }

    public function rotate_secret()
    {
        $new_secret = Velocity_Addons_Remote_Auth::rotate_secret();

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Remote secret rotated. Update the CRM with the new secret.',
            'secret'  => $new_secret,
        ));
    }
}
