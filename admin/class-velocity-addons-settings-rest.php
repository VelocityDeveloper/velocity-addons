<?php

/**
 * REST endpoints for Velocity Addons admin settings pages.
 *
 * @package Velocity_Addons
 * @subpackage Velocity_Addons/admin
 */

class Velocity_Addons_Admin_Settings_REST
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    private $namespace = 'velocity-addons/v1';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/settings/(?P<page>[a-z0-9_-]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_settings'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_settings'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/settings/general/reset',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'reset_general_settings'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/license/check',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'check_license'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/license/auto-activate',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'auto_activate_license'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/one-click-setup/run',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'run_one_click_setup'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/beaver-builder/templates',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_beaver_builder_templates'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/beaver-builder/import',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'import_beaver_builder_template'),
                    'permission_callback' => array($this, 'permissions_manage_options'),
                ),
            )
        );
    }

    public function permissions_manage_options()
    {
        return current_user_can('manage_options');
    }

    public function get_settings(WP_REST_Request $request)
    {
        $page       = sanitize_key((string) $request['page']);
        $definition = $this->get_page_definition($page);

        if (empty($definition)) {
            return new WP_Error(
                'velocity_invalid_settings_page',
                __('Unknown settings page.', 'velocity-addons'),
                array('status' => 404)
            );
        }

        return rest_ensure_response(
            array(
                'success'  => true,
                'page'     => $page,
                'settings' => $this->get_page_settings($definition),
            )
        );
    }

    public function update_settings(WP_REST_Request $request)
    {
        $page       = sanitize_key((string) $request['page']);
        $definition = $this->get_page_definition($page);

        if (empty($definition)) {
            return new WP_Error(
                'velocity_invalid_settings_page',
                __('Unknown settings page.', 'velocity-addons'),
                array('status' => 404)
            );
        }

        $payload = $request->get_json_params();
        if (! is_array($payload)) {
            $payload = $request->get_params();
        }
        $settings_payload = isset($payload['settings']) && is_array($payload['settings']) ? $payload['settings'] : $payload;

        foreach ($definition['options'] as $option_name => $schema) {
            if (!array_key_exists($option_name, $settings_payload)) {
                continue;
            }

            $incoming_value = $settings_payload[$option_name];
            $sanitized      = $this->prepare_option_for_save($option_name, $incoming_value, $schema);

            update_option($option_name, $sanitized);

            // Keep legacy single WhatsApp number synchronized with contact list.
            if ($option_name === 'nomor_whatsapp_contacts') {
                $first_number = '';
                if (is_array($sanitized) && isset($sanitized[0]) && is_array($sanitized[0])) {
                    $first_number = isset($sanitized[0]['number']) ? (string) $sanitized[0]['number'] : '';
                }
                update_option('nomor_whatsapp', $first_number);
            }
        }

        return rest_ensure_response(
            array(
                'success'  => true,
                'page'     => $page,
                'message'  => __('Settings saved successfully.', 'velocity-addons'),
                'settings' => $this->get_page_settings($definition),
            )
        );
    }

    public function reset_general_settings()
    {
        foreach ($this->get_general_defaults() as $key => $value) {
            update_option($key, $value);
        }

        return rest_ensure_response(
            array(
                'success'  => true,
                'message'  => __('General settings restored to defaults.', 'velocity-addons'),
                'settings' => $this->get_page_settings($this->get_page_definition('general')),
            )
        );
    }

    public function get_beaver_builder_templates(WP_REST_Request $request)
    {
        $type = sanitize_key((string) $request->get_param('type'));
        $allowed_types = array('header', 'content', 'footer', 'archive', 'single-post', 'single-page');

        if (!in_array($type, $allowed_types, true)) {
            return new WP_Error(
                'velocity_invalid_template_type',
                __('Tipe template tidak valid.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        $endpoint = apply_filters('velocity_addons_beaver_builder_templates_endpoint', '', $type, $request);
        if (!is_string($endpoint) || $endpoint === '') {
            return new WP_Error(
                'velocity_missing_beaver_builder_templates_endpoint',
                __('Endpoint Beaver Builder templates belum diset.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        $response = wp_remote_get(add_query_arg(array('type' => $type), $endpoint), array('timeout' => 20));
        if (is_wp_error($response)) {
            return new WP_Error(
                'velocity_beaver_builder_templates_request_failed',
                $response->get_error_message(),
                array('status' => 400)
            );
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($decoded)) {
            return new WP_Error(
                'velocity_beaver_builder_templates_invalid_response',
                __('Response endpoint Beaver Builder tidak valid.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        return rest_ensure_response($decoded);
    }

    public function import_beaver_builder_template(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $type = isset($payload['type']) ? sanitize_key((string) $payload['type']) : '';
        $design = isset($payload['design']) ? sanitize_text_field((string) $payload['design']) : '';
        $allowed_types = array('header', 'content', 'footer', 'archive', 'single-post', 'single-page');

        if (!in_array($type, $allowed_types, true)) {
            return new WP_Error(
                'velocity_invalid_template_type',
                __('Tipe template tidak valid.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        if ($design === '') {
            return new WP_Error(
                'velocity_beaver_builder_design_required',
                __('Desain wajib dipilih.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        $endpoint = apply_filters('velocity_addons_beaver_builder_import_endpoint', '', $type, $design, $payload, $request);
        if (!is_string($endpoint) || $endpoint === '') {
            return new WP_Error(
                'velocity_missing_beaver_builder_import_endpoint',
                __('Endpoint Beaver Builder import belum diset.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode($payload),
            )
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'velocity_beaver_builder_import_request_failed',
                $response->get_error_message(),
                array('status' => 400)
            );
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($decoded)) {
            return new WP_Error(
                'velocity_beaver_builder_import_invalid_response',
                __('Response import Beaver Builder tidak valid.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        return rest_ensure_response($decoded);
    }

    public function check_license(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (! is_array($payload)) {
            $payload = $request->get_params();
        }

        $license_key = '';
        if (isset($payload['license_key'])) {
            $license_key = sanitize_text_field((string) $payload['license_key']);
        }

        if ($license_key === '') {
            $saved = get_option('velocity_license', array());
            if (is_array($saved) && isset($saved['key'])) {
                $license_key = sanitize_text_field((string) $saved['key']);
            }
        }

        if ($license_key === '') {
            return new WP_Error(
                'velocity_license_required',
                __('Please enter a license key.', 'velocity-addons'),
                array('status' => 400)
            );
        }

        global $velocity_license;
        if (!($velocity_license instanceof Velocity_Addons_License)) {
            $velocity_license = new Velocity_Addons_License();
        }

        $result = $velocity_license->verify_license_key($license_key);

        if (empty($result['success'])) {
            return new WP_Error(
                'velocity_license_invalid',
                isset($result['message']) ? (string) $result['message'] : __('License check failed.', 'velocity-addons'),
                array(
                    'status'  => 400,
                    'details' => $result,
                )
            );
        }

        return rest_ensure_response(
            array(
                'success'  => true,
                'message'  => __('License verified.', 'velocity-addons'),
                'result'   => $result,
                'settings' => $this->get_page_settings($this->get_page_definition('license')),
            )
        );
    }

    public function auto_activate_license(WP_REST_Request $request)
    {
        $source = parse_url(get_site_url(), PHP_URL_HOST);

        // Debug logging
        error_log('[Velocity Addons] Auto-activate license request for source: ' . $source);

        // Force IPv4 resolution for both the direct license API and the relay.
        // The license server whitelists the WordPress server by IPv4, but
        // outbound requests may prefer IPv6 and get rejected.
        add_filter('http_api_curl', array($this, 'force_ipv4_for_velocity_api'), 10, 3);
        $attempts = array();
        $response = $this->request_auto_license($source, $attempts);
        remove_filter('http_api_curl', array($this, 'force_ipv4_for_velocity_api'), 10);

        if (is_wp_error($response)) {
            error_log('[Velocity Addons] Auto-activate license WP_Error: ' . $response->get_error_message());
            return new WP_Error(
                'velocity_auto_license_failed',
                $response->get_error_message(),
                array(
                    'status' => 400,
                    'attempted' => $attempts,
                    'resolved_via' => null,
                )
            );
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode((string) $body, true);

        // Debug logging
        error_log('[Velocity Addons] Auto-activate license HTTP code: ' . $http_code);
        error_log('[Velocity Addons] Auto-activate license response body: ' . $body);

        if ($http_code < 200 || $http_code >= 300 || !is_array($decoded)) {
            $message = is_array($decoded) && isset($decoded['message']) ? (string) $decoded['message'] : __('Auto license request failed.', 'velocity-addons');
            $attempt_labels = $this->format_auto_license_attempt_labels($attempts);

            if (!empty($attempt_labels)) {
                $message .= ' Attempts: ' . implode(' -> ', $attempt_labels);
            }

            error_log('[Velocity Addons] Auto-activate license failed: ' . $message . ' - details: ' . wp_json_encode($decoded));
            return new WP_Error(
                'velocity_auto_license_invalid',
                $message,
                array(
                    'status' => 400,
                    'details' => $decoded,
                    'attempted' => $attempts,
                    'resolved_via' => null,
                )
            );
        }

        $license_key = '';
        if (isset($decoded['data']['code']) && is_scalar($decoded['data']['code'])) {
            $license_key = sanitize_text_field((string) $decoded['data']['code']);
        } elseif (isset($decoded['data']['license']) && is_scalar($decoded['data']['license'])) {
            $license_key = sanitize_text_field((string) $decoded['data']['license']);
        } elseif (isset($decoded['license']) && is_scalar($decoded['license'])) {
            $license_key = sanitize_text_field((string) $decoded['license']);
        } elseif (isset($decoded['key']) && is_scalar($decoded['key'])) {
            $license_key = sanitize_text_field((string) $decoded['key']);
        }

        if ($license_key === '') {
            return new WP_Error(
                'velocity_auto_license_missing',
                __('License key not found from auto activate endpoint.', 'velocity-addons'),
                array(
                    'status' => 400,
                    'details' => $decoded,
                    'attempted' => $attempts,
                    'resolved_via' => null,
                )
            );
        }

        global $velocity_license;
        if (!($velocity_license instanceof Velocity_Addons_License)) {
            $velocity_license = new Velocity_Addons_License();
        }

        $result = $velocity_license->verify_license_key($license_key);
        if (empty($result['success'])) {
            return new WP_Error(
                'velocity_auto_license_verify_failed',
                isset($result['message']) ? (string) $result['message'] : __('Auto license verification failed.', 'velocity-addons'),
                array(
                    'status' => 400,
                    'details' => $result,
                    'attempted' => $attempts,
                    'resolved_via' => $this->resolve_auto_license_via($attempts, $http_code, $decoded),
                )
            );
        }

        return rest_ensure_response(
            array(
                'success'  => true,
                'message'  => __('License auto activated.', 'velocity-addons'),
                'key'      => $license_key,
                'result'   => $result,
                'settings' => $this->get_page_settings($this->get_page_definition('license')),
                'attempted' => $attempts,
                'resolved_via' => $this->resolve_auto_license_via($attempts, $http_code, $decoded),
            )
        );
    }

    public function run_one_click_setup(WP_REST_Request $request)
    {
        $logs = array();
        $logs[] = 'Mulai 1 Click setup';

        $params = $request->get_json_params();
        $tasks = isset($params['tasks']) && is_array($params['tasks']) ? $params['tasks'] : array();
        $run_permalink = !empty($tasks['permalink']);
        $run_timezone = !empty($tasks['timezone']);
        $run_datetime = !empty($tasks['datetime']);
        $run_media = !empty($tasks['media']);
        $run_admin_profile = !empty($tasks['admin_profile']);
        $run_standard_pages = !empty($tasks['standard_pages']);
        $run_primary_menu = !empty($tasks['primary_menu']);
        $run_privacy_policy = !empty($tasks['privacy_policy']);
        $run_home_seo = !empty($tasks['home_seo']);
        $run_share_image = !empty($tasks['share_image']);
        $run_remove_category_archive_title = !empty($tasks['remove_category_archive_title']);

        if (!$run_permalink && !$run_timezone && !$run_datetime && !$run_media && !$run_admin_profile && !$run_standard_pages && !$run_primary_menu && !$run_privacy_policy && !$run_home_seo && !$run_share_image && !$run_remove_category_archive_title) {
            $run_permalink = true;
            $run_timezone = true;
            $run_datetime = true;
            $run_media = true;
            $run_admin_profile = true;
            $run_standard_pages = true;
            $run_primary_menu = true;
            $run_privacy_policy = true;
            $run_home_seo = true;
            $run_share_image = true;
            $run_remove_category_archive_title = true;
        }

        $logs[] = 'Task permalink: ' . ($run_permalink ? 'ya' : 'tidak');
        $logs[] = 'Task timezone: ' . ($run_timezone ? 'ya' : 'tidak');
        $logs[] = 'Task datetime: ' . ($run_datetime ? 'ya' : 'tidak');
        $logs[] = 'Task media settings: ' . ($run_media ? 'ya' : 'tidak');
        $logs[] = 'Task admin profile: ' . ($run_admin_profile ? 'ya' : 'tidak');
        $logs[] = 'Task standard pages: ' . ($run_standard_pages ? 'ya' : 'tidak');
        $logs[] = 'Task primary menu: ' . ($run_primary_menu ? 'ya' : 'tidak');
        $logs[] = 'Task privacy policy: ' . ($run_privacy_policy ? 'ya' : 'tidak');
        $logs[] = 'Task home seo: ' . ($run_home_seo ? 'ya' : 'tidak');
        $logs[] = 'Task share image: ' . ($run_share_image ? 'ya' : 'tidak');
        $logs[] = 'Task remove category from archive title: ' . ($run_remove_category_archive_title ? 'ya' : 'tidak');

        $license = get_option('velocity_license', array());
        $license_key = is_array($license) && isset($license['key']) ? sanitize_text_field((string) $license['key']) : '';
        $logs[] = $license_key !== '' ? 'License key ditemukan' : 'License key kosong';

        if ($run_home_seo && $license_key === '') {
            return new WP_Error(
                'velocity_license_required',
                __('License Key diperlukan untuk Generate Home SEO.', 'velocity-addons'),
                array('status' => 400, 'logs' => $logs)
            );
        }

        $site_title = wp_strip_all_tags((string) get_bloginfo('name'));
        $site_description = wp_strip_all_tags((string) get_bloginfo('description'));
        $topic = trim($site_title . ' - ' . $site_description);
        $logs[] = 'Site title: ' . $site_title;
        $logs[] = 'Tagline: ' . $site_description;

        if ($topic === '' || $topic === '-') {
            $topic = $site_title !== '' ? $site_title : parse_url(get_site_url(), PHP_URL_HOST);
        }
        $logs[] = 'Topic request: ' . $topic;

        $ai_home = array();
        $using_api = false;
        if ($run_home_seo) {
            $ai_home = self::generate_ai_home_meta($license_key, $site_title, $site_description, $topic, $logs);
            $using_api = !empty($ai_home['using_api']);
        }

        $home_title = $site_title;
        $home_description = !empty($ai_home['description']) ? $ai_home['description'] : $site_description;
        if (function_exists('mb_substr')) {
            $home_description = trim(mb_substr($home_description, 0, 160));
        } else {
            $home_description = trim(substr($home_description, 0, 160));
        }
        $home_keywords = !empty($ai_home['keywords']) ? $ai_home['keywords'] : trim($site_title . ', ' . $site_description, ', ');

        $share_image = '';
        $timezone = '';
        $date_format = '';
        $time_format = '';
        $start_of_week = '';
        $standard_pages = '';
        $page_on_front = 0;
        $media_settings = '';
        $admin_profile = '';
        $primary_menu = '';
        $privacy_policy = '';
        $privacy_policy_id = 0;
        $remove_category_archive_title = '';
        if ($run_permalink) {
            update_option('permalink_structure', '/%category%/%postname%/');
            $logs[] = 'Option permalink_structure diupdate';
            global $wp_rewrite;
            if ($wp_rewrite instanceof WP_Rewrite) {
                $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
                $wp_rewrite->flush_rules();
                $logs[] = 'Rewrite rules di-flush';
            }
        } else {
            $logs[] = 'Skip permalink';
        }

        if ($run_timezone) {
            $timezone = 'Asia/Jakarta';
            update_option('timezone_string', $timezone);
            update_option('gmt_offset', 7);
            $logs[] = 'Timezone diset ke Asia/Jakarta';
        } else {
            $logs[] = 'Skip timezone';
        }

        if ($run_datetime) {
            $date_format = 'j F Y';
            $time_format = 'H:i';
            $start_of_week = '0';
            update_option('date_format', $date_format);
            update_option('time_format', $time_format);
            update_option('start_of_week', 0);
            $logs[] = 'Date format diset ke j F Y';
            $logs[] = 'Time format diset ke H:i';
            $logs[] = 'Hari mulai minggu diset ke Minggu';
        } else {
            $logs[] = 'Skip datetime';
        }

        if ($run_media) {
            update_option('thumbnail_size_w', 150);
            update_option('thumbnail_size_h', 150);
            update_option('thumbnail_crop', 0);
            update_option('medium_size_w', 300);
            update_option('medium_size_h', 300);
            update_option('large_size_w', 800);
            update_option('large_size_h', 800);
            $media_settings = 'Thumbnail 150×150 tanpa crop; Medium 300×300; Large 800×800';
            $logs[] = 'Media Settings diset: ' . $media_settings;
        } else {
            $logs[] = 'Skip media settings';
        }

        if ($run_admin_profile) {
            $current_user = wp_get_current_user();
            if ($current_user instanceof WP_User && $current_user->exists()) {
                $first_name = trim((string) get_user_meta($current_user->ID, 'first_name', true));
                if ($first_name === '') {
                    $updated_user = wp_update_user(
                        array(
                            'ID'           => $current_user->ID,
                            'first_name'   => 'Admin',
                            'display_name' => 'Admin',
                        )
                    );

                    if (is_wp_error($updated_user)) {
                        $admin_profile = 'gagal diperbarui: ' . $updated_user->get_error_message();
                        $logs[] = 'Admin profile ' . $admin_profile;
                    } else {
                        $admin_profile = 'First Name dan Display Name diset ke Admin';
                        $logs[] = 'Admin profile: ' . $admin_profile;
                    }
                } else {
                    $admin_profile = 'skip, First Name sudah terisi';
                    $logs[] = 'Admin profile: ' . $admin_profile;
                }
            } else {
                $admin_profile = 'skip, user saat ini tidak ditemukan';
                $logs[] = 'Admin profile: ' . $admin_profile;
            }
        } else {
            $logs[] = 'Skip admin profile';
        }

        if ($run_standard_pages) {
            $page_titles = array('Beranda', 'Tentang Kami', 'Layanan', 'Galeri', 'Kontak Kami');
            $prepared_pages = array();
            $home_page_id = 0;

            foreach ($page_titles as $page_title) {
                $page_result = $this->get_or_create_page_by_title($page_title);
                if (empty($page_result['id'])) {
                    $logs[] = 'Gagal siapkan page: ' . $page_title;
                    continue;
                }

                $page_id = (int) $page_result['id'];
                $prepared_pages[] = $page_title;
                $logs[] = sprintf(
                    'Page %s: %s (#%d)',
                    !empty($page_result['created']) ? 'dibuat' : 'sudah ada, skip',
                    $page_title,
                    $page_id
                );

                if ($page_title === 'Beranda') {
                    $home_page_id = $page_id;
                }
            }

            if (!empty($prepared_pages)) {
                $standard_pages = implode(', ', $prepared_pages);
            }

            if ($home_page_id > 0) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $home_page_id);
                $page_on_front = $home_page_id;
                $logs[] = 'Reading homepage diset ke Beranda (#' . $home_page_id . ')';
            } else {
                $logs[] = 'Reading homepage skip, page Beranda tidak ditemukan';
            }
        } else {
            $logs[] = 'Skip standard pages';
        }

        if ($run_primary_menu) {
            $menu_name = 'Menu Utama';
            $menu_object = wp_get_nav_menu_object($menu_name);
            if ($menu_object) {
                $menu_id = (int) $menu_object->term_id;
                $logs[] = 'Menu Utama sudah ada (#' . $menu_id . ')';
            } else {
                $menu_result = wp_create_nav_menu($menu_name);
                if (is_wp_error($menu_result)) {
                    $menu_id = 0;
                    $logs[] = 'Gagal membuat Menu Utama: ' . $menu_result->get_error_message();
                } else {
                    $menu_id = (int) $menu_result;
                    $logs[] = 'Menu Utama dibuat (#' . $menu_id . ')';
                }
            }

            if ($menu_id > 0) {
                $existing_items = wp_get_nav_menu_items($menu_id);
                $existing_page_ids = array();
                if (is_array($existing_items)) {
                    foreach ($existing_items as $existing_item) {
                        if ($existing_item instanceof WP_Post && $existing_item->type === 'post_type' && $existing_item->object === 'page') {
                            $existing_page_ids[] = (int) $existing_item->object_id;
                        }
                    }
                }

                $menu_page_titles = array('Beranda', 'Tentang Kami', 'Layanan', 'Galeri', 'Kontak Kami');
                $menu_position = 1;
                foreach ($menu_page_titles as $menu_page_title) {
                    $menu_page = get_page_by_path(sanitize_title($menu_page_title), OBJECT, 'page');
                    if (!($menu_page instanceof WP_Post)) {
                        $menu_page = get_page_by_title($menu_page_title, OBJECT, 'page');
                    }
                    if (!($menu_page instanceof WP_Post)) {
                        $logs[] = 'Item menu skip, page tidak ditemukan: ' . $menu_page_title;
                        $menu_position++;
                        continue;
                    }

                    if (in_array((int) $menu_page->ID, $existing_page_ids, true)) {
                        $logs[] = 'Item menu sudah ada, skip: ' . $menu_page_title;
                        $menu_position++;
                        continue;
                    }

                    $menu_item_result = wp_update_nav_menu_item(
                        $menu_id,
                        0,
                        array(
                            'menu-item-object-id' => (int) $menu_page->ID,
                            'menu-item-object'    => 'page',
                            'menu-item-type'      => 'post_type',
                            'menu-item-status'    => 'publish',
                            'menu-item-position'  => $menu_position,
                        )
                    );
                    if (is_wp_error($menu_item_result)) {
                        $logs[] = 'Gagal menambahkan item menu ' . $menu_page_title . ': ' . $menu_item_result->get_error_message();
                    } else {
                        $logs[] = 'Item menu ditambahkan: ' . $menu_page_title;
                    }
                    $menu_position++;
                }

                $menu_locations = get_theme_mod('nav_menu_locations', array());
                if (!is_array($menu_locations)) {
                    $menu_locations = array();
                }
                $menu_locations['primary'] = $menu_id;
                set_theme_mod('nav_menu_locations', $menu_locations);
                $primary_menu = 'Menu Utama (#' . $menu_id . ') dipasang ke primary';
                $logs[] = $primary_menu;
            } else {
                $primary_menu = 'gagal dibuat';
            }
        } else {
            $logs[] = 'Skip primary menu';
        }

        if ($run_privacy_policy) {
            $configured_privacy_id = (int) get_option('wp_page_for_privacy_policy');
            $privacy_page = $configured_privacy_id > 0 ? get_post($configured_privacy_id) : null;

            if (!($privacy_page instanceof WP_Post) || $privacy_page->post_type !== 'page') {
                $privacy_page = get_page_by_title('Kebijakan Privasi', OBJECT, 'page');
                if (!($privacy_page instanceof WP_Post)) {
                    $privacy_page = get_page_by_title('Privacy Policy', OBJECT, 'page');
                }
            }

            if ($privacy_page instanceof WP_Post) {
                $privacy_policy_id = (int) $privacy_page->ID;
                if ($privacy_page->post_status !== 'publish') {
                    $publish_result = wp_update_post(
                        array(
                            'ID'          => $privacy_policy_id,
                            'post_status' => 'publish',
                        ),
                        true
                    );
                    if (is_wp_error($publish_result)) {
                        $logs[] = 'Gagal publish Privacy Policy Page: ' . $publish_result->get_error_message();
                        $privacy_policy_id = 0;
                    } else {
                        $logs[] = 'Privacy Policy Page dipublish (#' . $privacy_policy_id . ')';
                    }
                } else {
                    $logs[] = 'Privacy Policy Page sudah publish (#' . $privacy_policy_id . ')';
                }
            } else {
                $create_privacy_result = wp_insert_post(
                    array(
                        'post_title'  => 'Kebijakan Privasi',
                        'post_type'   => 'page',
                        'post_status' => 'publish',
                    ),
                    true
                );
                if (is_wp_error($create_privacy_result)) {
                    $logs[] = 'Gagal membuat Kebijakan Privasi: ' . $create_privacy_result->get_error_message();
                } else {
                    $privacy_policy_id = (int) $create_privacy_result;
                    $logs[] = 'Halaman Kebijakan Privasi dibuat dan dipublish (#' . $privacy_policy_id . ')';
                }
            }

            if ($privacy_policy_id > 0) {
                update_option('wp_page_for_privacy_policy', $privacy_policy_id);
                $privacy_policy = get_the_title($privacy_policy_id) . ' (#' . $privacy_policy_id . ')';
                $logs[] = 'Privacy Policy Page ditetapkan: ' . $privacy_policy;
            } else {
                $privacy_policy = 'gagal disiapkan';
            }
        } else {
            $logs[] = 'Skip privacy policy';
        }

        if ($run_home_seo) {
            $logs[] = 'Home title disiapkan';
            $logs[] = 'Home description disiapkan' . ($using_api ? ' (dari AI)' : ' (lokal)');
            $logs[] = 'Home keywords disiapkan' . ($using_api ? ' (dari AI)' : ' (lokal)');
            update_option('home_title', $home_title);
            update_option('home_description', $home_description);
            update_option('home_keywords', $home_keywords);
            $logs[] = 'SEO home title tersimpan';
            $logs[] = 'SEO home description tersimpan';
            $logs[] = 'SEO home keywords tersimpan';
        } else {
            $logs[] = 'Skip home seo';
        }

        if ($run_share_image) {
            $custom_logo_id = (int) get_theme_mod('custom_logo');
            $share_image_source = '';
            if ($custom_logo_id > 0) {
                $share_image = wp_get_attachment_image_url($custom_logo_id, 'full');
                if (!empty($share_image)) {
                    $share_image_source = 'Site Logo';
                }
            }

            if (empty($share_image)) {
                $share_image = get_site_icon_url();
                if (!empty($share_image)) {
                    $share_image_source = 'favicon';
                }
            }

            if (!empty($share_image)) {
                update_option('share_image', esc_url_raw($share_image));
                $logs[] = 'Share image SEO tersimpan dari ' . $share_image_source;
            } else {
                $logs[] = 'Share image skip, Site Logo dan favicon tidak ada';
            }
        } else {
            $logs[] = 'Skip share image';
        }

        if ($run_remove_category_archive_title) {
            update_option('remove_category_archive_title_velocity', 1);
            $remove_category_archive_title = 'aktif';
            $logs[] = 'Remove Category from Archive Title diaktifkan';
        } else {
            $logs[] = 'Skip remove category from archive title';
        }

        $logs[] = 'Selesai';

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __('1 Click setup selesai.', 'velocity-addons'),
                'data'    => array(
                    'topic'            => $topic,
                    'home_title'       => $run_home_seo ? $home_title : '',
                    'home_description' => $run_home_seo ? $home_description : '',
                    'home_keywords'    => $run_home_seo ? $home_keywords : '',
                    'permalink'        => $run_permalink ? '/%category%/%postname%/' : '',
                    'timezone'         => $timezone,
                    'date_format'      => $date_format,
                    'time_format'      => $time_format,
                    'start_of_week'    => $start_of_week,
                    'media'            => $media_settings,
                    'admin_profile'    => $admin_profile,
                    'standard_pages'   => $standard_pages,
                    'page_on_front'    => $page_on_front,
                    'primary_menu'     => $primary_menu,
                    'privacy_policy'   => $privacy_policy,
                    'share_image'      => $share_image,
                    'remove_category_archive_title' => $remove_category_archive_title,
                ),
                'logs'    => $logs,
            )
        );
    }

    private function get_or_create_page_by_title($page_title)
    {
        $page_title = sanitize_text_field((string) $page_title);
        if ($page_title === '') {
            return array('id' => 0, 'created' => false);
        }

        $existing_page = get_page_by_title($page_title, OBJECT, 'page');
        if ($existing_page instanceof WP_Post) {
            return array(
                'id'      => (int) $existing_page->ID,
                'created' => false,
            );
        }

        $page_id = wp_insert_post(array(
            'post_title'   => $page_title,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_content' => '',
        ), true);

        if (is_wp_error($page_id)) {
            return array('id' => 0, 'created' => false);
        }

        return array(
            'id'      => (int) $page_id,
            'created' => true,
        );
    }

    private static function generate_ai_home_meta($license_key, $site_title, $site_description, $topic, &$logs)
    {
        $source = parse_url(get_site_url(), PHP_URL_HOST);
        $prompt = 'Buat SEO homepage website. Balas hanya JSON valid tanpa markdown dengan format {"description":"string","keywords":"keyword1, keyword2, keyword3"}. Description maksimal 160 karakter. Keywords maksimal 12 keyword, dipisahkan koma.';
        $content = "Judul website: {$site_title}\nDeskripsi website: {$site_description}";

        $fallback = array(
            'using_api'   => false,
            'description' => $site_description,
            'keywords'    => trim($site_title . ', ' . $site_description, ', '),
        );

        $logs[] = 'Kirim request ke AI chat untuk description + keyword';
        $response = wp_remote_post(
            'https://api.velocitydeveloper.co/api/v1/ai/chat',
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'license'      => $license_key,
                    'source'       => $source,
                ),
                'body'    => wp_json_encode(array(
                    'prompt'  => $prompt,
                    'content' => $content,
                )),
                'timeout' => 20,
            )
        );

        if (is_wp_error($response)) {
            $logs[] = 'AI meta gagal: ' . $response->get_error_message();
            return $fallback;
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $logs[] = 'HTTP code AI meta: ' . $http_code;

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($decoded)) {
            $logs[] = 'Response AI meta bukan JSON valid';
            return $fallback;
        }

        if ($http_code >= 400) {
            $error_message = '';
            if (isset($decoded['message']) && is_scalar($decoded['message'])) {
                $error_message = trim(wp_strip_all_tags((string) $decoded['message']));
                $logs[] = 'AI meta error: ' . $error_message;
            }

            $raw_error = wp_json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($raw_error) && $raw_error !== '') {
                $logs[] = 'Raw AI error response: ' . $raw_error;
            } elseif ($error_message !== '') {
                $logs[] = 'Raw AI error response: ' . $error_message;
            }

            $logs[] = 'AI meta HTTP error, fallback lokal';
            return $fallback;
        }

        $meta = array();
        $result = '';

        if (isset($decoded['description']) || isset($decoded['keywords'])) {
            $meta = $decoded;
            $logs[] = 'Pakai field AI meta: root';
        } elseif (isset($decoded['data']) && is_array($decoded['data']) && (isset($decoded['data']['description']) || isset($decoded['data']['keywords']))) {
            $meta = $decoded['data'];
            $logs[] = 'Pakai field AI meta: data';
        } else {
            if (isset($decoded['data']['content']) && is_scalar($decoded['data']['content'])) {
                $result = (string) $decoded['data']['content'];
                $logs[] = 'Pakai field AI meta: data.content';
            } elseif (isset($decoded['data']['text']) && is_scalar($decoded['data']['text'])) {
                $result = (string) $decoded['data']['text'];
                $logs[] = 'Pakai field AI meta: data.text';
            } elseif (isset($decoded['content']) && is_scalar($decoded['content'])) {
                $result = (string) $decoded['content'];
                $logs[] = 'Pakai field AI meta: content';
            } elseif (isset($decoded['message']) && is_scalar($decoded['message'])) {
                $result = (string) $decoded['message'];
                $logs[] = 'Pakai field AI meta: message';
                $logs[] = 'Raw AI message: ' . trim(wp_strip_all_tags($result));
            }

            $result = trim((string) $result);
            if ($result === '') {
                $logs[] = 'AI meta kosong, fallback lokal';
                return $fallback;
            }

            $json_start = strpos($result, '{');
            $json_end = strrpos($result, '}');
            if ($json_start !== false && $json_end !== false && $json_end >= $json_start) {
                $result = substr($result, $json_start, $json_end - $json_start + 1);
            }

            $meta = json_decode($result, true);
            if (!is_array($meta)) {
                $logs[] = 'AI meta tidak bisa diparse sebagai JSON, fallback lokal';
                return $fallback;
            }
        }

        $description = isset($meta['description']) && is_scalar($meta['description']) ? trim(wp_strip_all_tags((string) $meta['description'])) : '';
        $keywords = isset($meta['keywords']) && is_scalar($meta['keywords']) ? trim(wp_strip_all_tags((string) $meta['keywords'])) : '';

        if ($description === '') {
            $description = $fallback['description'];
        }
        if ($keywords === '') {
            $keywords = $fallback['keywords'];
        }

        return array(
            'using_api'   => true,
            'description' => $description,
            'keywords'    => $keywords,
        );
    }

    private function get_page_settings($definition)
    {
        $result = array();
        foreach ($definition['options'] as $option_name => $schema) {
            $stored = get_option($option_name, null);
            if ($stored === null || $stored === false) {
                $stored = $this->get_default_for_schema($schema);
            }
            $result[$option_name] = $this->sanitize_value($stored, $schema);
        }
        return $result;
    }

    private function prepare_option_for_save($option_name, $incoming_value, $schema)
    {
        if ($schema['type'] === 'object') {
            $existing = get_option($option_name, array());
            if (!is_array($existing)) {
                $existing = array();
            }
            if (!is_array($incoming_value)) {
                $incoming_value = array();
            }
            $incoming_value = $this->deep_merge($existing, $incoming_value);
        }

        $sanitized = $this->sanitize_value($incoming_value, $schema);

        if ($option_name === 'velocity_license' && is_array($sanitized)) {
            $current = get_option('velocity_license', array());
            if (!is_array($current)) {
                $current = array();
            }

            if (!isset($sanitized['status']) && isset($current['status'])) {
                $sanitized['status'] = $current['status'];
            }
            if (!isset($sanitized['expire_date']) && isset($current['expire_date'])) {
                $sanitized['expire_date'] = $current['expire_date'];
            }
            if (isset($sanitized['key']) && isset($current['key']) && $sanitized['key'] !== $current['key']) {
                $sanitized['status'] = 'pending';
            }
        }

        return $sanitized;
    }

    private function deep_merge($base, $incoming)
    {
        if (!is_array($base) || !is_array($incoming)) {
            return $incoming;
        }

        foreach ($incoming as $key => $value) {
            if (array_key_exists($key, $base) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->deep_merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function sanitize_value($value, $schema)
    {
        $type = isset($schema['type']) ? $schema['type'] : 'text';

        switch ($type) {
            case 'bool':
                return $this->to_bool_int($value);

            case 'int':
                $number = absint($value);
                if (isset($schema['min']) && $number < (int) $schema['min']) {
                    $number = (int) $schema['min'];
                }
                if (isset($schema['max']) && $number > (int) $schema['max']) {
                    $number = (int) $schema['max'];
                }
                return $number;

            case 'text':
                return sanitize_text_field((string) $value);

            case 'textarea':
                return sanitize_textarea_field((string) $value);

            case 'whatsapp_message':
                return $this->sanitize_whatsapp_message($value);

            case 'url':
                return esc_url_raw((string) $value);

            case 'select':
                $value   = sanitize_text_field((string) $value);
                $allowed = isset($schema['allowed']) && is_array($schema['allowed']) ? $schema['allowed'] : array();
                if (!empty($allowed) && !in_array($value, $allowed, true)) {
                    return (string) $this->get_default_for_schema($schema);
                }
                return $value;

            case 'string_array':
                return $this->sanitize_string_array($value, isset($schema['mode']) ? (string) $schema['mode'] : 'text');

            case 'post_types_array':
                return $this->sanitize_post_types_array($value);

            case 'snippet':
                return $this->sanitize_snippet($value);

            case 'whatsapp_contacts':
                return $this->sanitize_whatsapp_contacts($value);

            case 'object':
                $value      = is_array($value) ? $value : array();
                $properties = isset($schema['properties']) && is_array($schema['properties']) ? $schema['properties'] : array();
                $sanitized  = array();
                foreach ($properties as $prop_key => $prop_schema) {
                    $raw = array_key_exists($prop_key, $value) ? $value[$prop_key] : $this->get_default_for_schema($prop_schema);
                    $sanitized[$prop_key] = $this->sanitize_value($raw, $prop_schema);
                }
                return $sanitized;

            default:
                return sanitize_text_field((string) $value);
        }
    }

    private function to_bool_int($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, array('1', 'true', 'yes', 'on'), true) ? 1 : 0;
        }
        return 0;
    }

    private function sanitize_string_array($value, $mode = 'text')
    {
        if (!is_array($value)) {
            $value = array();
        }

        $clean = array();
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $item = (string) $item;
            if ($mode === 'key') {
                $item = sanitize_key($item);
            } else {
                $item = sanitize_text_field($item);
            }
            if ($item === '') {
                continue;
            }
            $clean[] = $item;
        }

        return array_values(array_unique($clean));
    }

    private function sanitize_post_types_array($value)
    {
        $selected = $this->sanitize_string_array($value, 'key');
        $all      = get_post_types(array(), 'names');
        $clean    = array_values(array_intersect($selected, $all));

        if (empty($clean)) {
            $clean = (array) $this->get_default_for_schema(array(
                'type'    => 'post_types_array',
                'default' => array('post', 'page'),
            ));
        }

        return $clean;
    }

    private function sanitize_snippet($value)
    {
        if (!is_string($value)) {
            return '';
        }

        $normalized = str_replace(array("\r\n", "\r"), "\n", $value);
        if (!current_user_can('unfiltered_html')) {
            return wp_kses_post($normalized);
        }

        return $normalized;
    }

    private function sanitize_whatsapp_contacts($value)
    {
        if (!is_array($value)) {
            $value = $value ? array(array('name' => '', 'number' => $value)) : array();
        }

        $clean = array();
        foreach ($value as $contact) {
            if (!is_array($contact)) {
                continue;
            }

            $name   = isset($contact['name']) ? sanitize_text_field((string) $contact['name']) : '';
            $number = isset($contact['number']) ? preg_replace('/[^0-9]/', '', (string) $contact['number']) : '';

            if ($number === '') {
                continue;
            }
            if (strpos($number, '0') === 0) {
                $number = substr_replace($number, '62', 0, 1);
            }

            $clean[] = array(
                'name'   => $name,
                'number' => $number,
            );
        }

        return array_values($clean);
    }

    private function sanitize_whatsapp_message($value)
    {
        if (class_exists('Velocity_Addons_Floating_Whatsapp') && method_exists('Velocity_Addons_Floating_Whatsapp', 'normalize_whatsapp_message')) {
            return Velocity_Addons_Floating_Whatsapp::normalize_whatsapp_message((string) $value);
        }

        $normalized = str_replace(array("\r\n", "\r"), "\n", (string) $value);
        $normalized = str_replace(array('\\r\\n', '\\n', '\\r'), "\n", $normalized);
        $normalized = str_ireplace(array('%0D%0A', '%0A', '%0D'), "\n", $normalized);

        return sanitize_textarea_field($normalized);
    }

    private function get_server_ip()
    {
        // Try to get the server's public IP
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }
        // Fallback: try to get from HTTP headers if behind proxy
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return null;
    }

    public function force_ipv4_for_velocity_api($handle, $parsed_args, $url)
    {
        $license_api_hosts = array_filter(array(
            function_exists('velocity_addons_license_api_host')
                ? velocity_addons_license_api_host('direct')
                : 'api.velocitydeveloper.co',
            function_exists('velocity_addons_license_api_host')
                ? velocity_addons_license_api_host('relay')
                : 'api.nglorok.com',
        ));

        foreach ($license_api_hosts as $license_api_host) {
            if ($license_api_host !== '' && stripos($url, $license_api_host) !== false) {
                curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                break;
            }
        }

        return $handle;
    }

    private function request_auto_license($source, &$attempts = array())
    {
        $attempts = array();

        $urls = array(
            function_exists('velocity_addons_license_api_url')
                ? velocity_addons_license_api_url('get-auto-license', 'direct')
                : 'https://api.velocitydeveloper.co/api/v1/get-auto-license',
            function_exists('velocity_addons_license_api_url')
                ? velocity_addons_license_api_url('get-auto-license', 'relay')
                : 'https://api.nglorok.com/api/v1/get-auto-license',
        );

        $urls = array_values(array_unique(array_filter($urls)));
        $last_response = null;

        foreach ($urls as $index => $url) {
            $label = strpos($url, 'nglorok.com') !== false ? 'relay' : 'direct';
            error_log('[Velocity Addons] Auto-activate attempt ' . ($index + 1) . ' (' . $label . '): ' . $url . ' | source=' . $source);

            $response = wp_remote_get(
                $url,
                array(
                    'headers' => array(
                        'source' => $source,
                    ),
                    'user-agent' => 'VelocityLicenseClient/1.0',
                    'timeout' => 20,
                )
            );

            if (is_wp_error($response)) {
                error_log('[Velocity Addons] Auto-activate attempt ' . ($index + 1) . ' (' . $label . ') WP_Error: ' . $response->get_error_message());
                $last_response = $response;
                continue;
            }

            $http_code = (int) wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode((string) $body, true);
            $response_headers = wp_remote_retrieve_headers($response);
            $normalized_headers = array();

            if ($response_headers instanceof \WpOrg\Requests\Utility\CaseInsensitiveDictionary) {
                $normalized_headers = $response_headers->getAll();
            } elseif (is_array($response_headers)) {
                $normalized_headers = $response_headers;
            }

            $attempts[] = array(
                'via' => $label,
                'url' => $url,
                'http_code' => $http_code,
                'success' => $http_code >= 200 && $http_code < 300 && is_array($decoded),
                'headers' => $normalized_headers,
            );

            error_log('[Velocity Addons] Auto-activate attempt ' . ($index + 1) . ' (' . $label . ') HTTP ' . $http_code . ' headers: ' . wp_json_encode($normalized_headers));
            error_log('[Velocity Addons] Auto-activate attempt ' . ($index + 1) . ' (' . $label . ') HTTP ' . $http_code . ' body: ' . $body);

            if ($http_code >= 200 && $http_code < 300 && is_array($decoded)) {
                error_log('[Velocity Addons] Auto-activate success via ' . $label . ' (HTTP ' . $http_code . ')');
                return $response;
            }

            $last_response = $response;
        }

        error_log('[Velocity Addons] Auto-activate fallback exhausted. Returning last response.');

        return $last_response;
    }

    private function resolve_auto_license_via($attempts, $http_code, $decoded)
    {
        if ($http_code < 200 || $http_code >= 300 || !is_array($decoded)) {
            return null;
        }

        if (!is_array($attempts)) {
            return null;
        }

        foreach (array_reverse($attempts) as $attempt) {
            if (!empty($attempt['success']) && !empty($attempt['via'])) {
                return (string) $attempt['via'];
            }
        }

        return null;
    }

    private function format_auto_license_attempt_labels($attempts)
    {
        if (!is_array($attempts)) {
            return array();
        }

        $labels = array();

        foreach ($attempts as $attempt) {
            if (empty($attempt['via'])) {
                continue;
            }

            $label = (string) $attempt['via'];
            if (isset($attempt['http_code'])) {
                $label .= ' (' . (int) $attempt['http_code'] . ')';
            }

            $labels[] = $label;
        }

        return $labels;
    }

    private function get_default_for_schema($schema)
    {
        if (isset($schema['default'])) {
            return $schema['default'];
        }

        $type = isset($schema['type']) ? $schema['type'] : 'text';
        if ($type === 'bool') {
            return 0;
        }
        if ($type === 'int') {
            return 0;
        }
        if ($type === 'string_array' || $type === 'post_types_array' || $type === 'whatsapp_contacts') {
            return array();
        }
        if ($type === 'object') {
            $defaults = array();
            if (isset($schema['properties']) && is_array($schema['properties'])) {
                foreach ($schema['properties'] as $key => $prop_schema) {
                    $defaults[$key] = $this->get_default_for_schema($prop_schema);
                }
            }
            return $defaults;
        }

        return '';
    }

    private function get_page_definition($page)
    {
        $definitions = $this->get_definitions();
        return isset($definitions[$page]) ? $definitions[$page] : null;
    }

    private function get_general_defaults()
    {
        return array(
            'fully_disable_comment'        => 1,
            'hide_admin_notice'            => 0,
            'disable_gutenberg'            => 0,
            'classic_widget_velocity'      => 1,
            'enable_xml_sitemap'           => 1,
            'seo_velocity'                 => 1,
            'statistik_velocity'           => 1,
            'floating_whatsapp'            => 1,
            'floating_scrollTop'           => 1,
            'remove_slug_category_velocity' => 0,
            'remove_category_archive_title_velocity' => 1,
            'news_generate'                => 1,
            'velocity_gallery'             => 0,
            'velocity_optimasi'            => 0,
        );
    }

    private function get_definitions()
    {
        return array(
            'general' => array(
                'options' => array(
                    'fully_disable_comment'         => array('type' => 'bool', 'default' => 1),
                    'hide_admin_notice'             => array('type' => 'bool', 'default' => 0),
                    'disable_gutenberg'             => array('type' => 'bool', 'default' => 0),
                    'classic_widget_velocity'       => array('type' => 'bool', 'default' => 1),
                    'remove_slug_category_velocity' => array('type' => 'bool', 'default' => 0),
                    'remove_category_archive_title_velocity' => array('type' => 'bool', 'default' => 1),
                    'enable_xml_sitemap'            => array('type' => 'bool', 'default' => 1),
                    'seo_velocity'                  => array('type' => 'bool', 'default' => 1),
                    'statistik_velocity'            => array('type' => 'bool', 'default' => 1),
                    'floating_whatsapp'             => array('type' => 'bool', 'default' => 1),
                    'floating_scrollTop'            => array('type' => 'bool', 'default' => 1),
                    'news_generate'                 => array('type' => 'bool', 'default' => 1),
                    'velocity_gallery'              => array('type' => 'bool', 'default' => 0),
                    'velocity_optimasi'             => array('type' => 'bool', 'default' => 0),
                ),
            ),
            'captcha' => array(
                'options' => array(
                    'captcha_velocity' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'provider'  => array('type' => 'select', 'allowed' => array('google', 'image'), 'default' => 'image'),
                            'aktif'     => array('type' => 'bool', 'default' => 1),
                            'difficulty' => array('type' => 'select', 'allowed' => array('easy', 'medium', 'hard'), 'default' => 'medium'),
                            'sitekey'   => array('type' => 'text', 'default' => ''),
                            'secretkey' => array('type' => 'text', 'default' => ''),
                        ),
                    ),
                ),
            ),
            'maintenance' => array(
                'options' => array(
                    'maintenance_mode' => array('type' => 'bool', 'default' => 1),
                    'maintenance_mode_data' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'header'     => array('type' => 'text', 'default' => 'Maintenance Mode'),
                            'body'       => array('type' => 'textarea', 'default' => 'We are currently performing maintenance. Please check back later.'),
                            'background' => array('type' => 'int', 'default' => 0),
                            'show_logo'  => array('type' => 'bool', 'default' => 1),
                            'logo'       => array('type' => 'int', 'default' => 0),
                        ),
                    ),
                ),
            ),
            'license' => array(
                'options' => array(
                    'velocity_license' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'key'         => array('type' => 'text', 'default' => ''),
                            'expire_date' => array('type' => 'text', 'default' => ''),
                            'status'      => array('type' => 'text', 'default' => ''),
                        ),
                    ),
                ),
            ),
            'security' => array(
                'options' => array(
                    'limit_login_attempts'      => array('type' => 'bool', 'default' => 1),
                    'disable_xmlrpc'            => array('type' => 'bool', 'default' => 1),
                    'block_wp_login'            => array('type' => 'bool', 'default' => 0),
                    'whitelist_block_wp_login'  => array('type' => 'text', 'default' => ''),
                    'whitelist_country'         => array('type' => 'text', 'default' => 'ID'),
                    'redirect_to'               => array('type' => 'text', 'default' => '127.0.0.1'),
                ),
            ),
            'auto_resize' => array(
                'options' => array(
                    'auto_resize_mode' => array('type' => 'bool', 'default' => 0),
                    'auto_resize_mode_data' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'maxwidth'  => array('type' => 'int', 'min' => 0, 'default' => 1200),
                            'maxheight' => array('type' => 'int', 'min' => 0, 'default' => 1200),
                            'quality'   => array('type' => 'int', 'min' => 10, 'max' => 100, 'default' => 90),
                            'output_format' => array('type' => 'select', 'allowed' => array('original', 'jpeg', 'webp', 'avif'), 'default' => 'original'),
                        ),
                    ),
                    'auto_resize_image_velocity' => array('type' => 'bool', 'default' => 0),
                ),
            ),
            'seo' => array(
                'options' => array(
                    'home_title'       => array('type' => 'text', 'default' => get_bloginfo('name')),
                    'home_description' => array('type' => 'textarea', 'default' => get_bloginfo('description')),
                    'home_keywords'    => array('type' => 'textarea', 'default' => ''),
                    'share_image'      => array('type' => 'url', 'default' => ''),
                    'seo_post_types'   => array('type' => 'post_types_array', 'default' => array('post', 'page')),
                ),
            ),
            'floating_whatsapp' => array(
                'options' => array(
                    'nomor_whatsapp_contacts' => array('type' => 'whatsapp_contacts', 'default' => array()),
                    'whatsapp_text'           => array('type' => 'text', 'default' => 'Butuh Bantuan?'),
                    'whatsapp_message'        => array('type' => 'whatsapp_message', 'default' => 'Hallo...'),
                    'whatsapp_position'       => array('type' => 'select', 'allowed' => array('right', 'left'), 'default' => 'right'),
                ),
            ),
            'snippet' => array(
                'options' => array(
                    'header_snippet' => array('type' => 'snippet', 'default' => ''),
                    'body_snippet'   => array('type' => 'snippet', 'default' => ''),
                    'footer_snippet' => array('type' => 'snippet', 'default' => ''),
                ),
            ),
        );
    }
}

$velocity_addons_admin_settings_rest = new Velocity_Addons_Admin_Settings_REST();
