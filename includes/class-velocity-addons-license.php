<?php
class Velocity_Addons_License
{
    private $api_url;
    private $option_name;

    public function __construct()
    {
        $this->api_url = function_exists('velocity_addons_license_api_url')
            ? velocity_addons_license_api_url('license')
            : 'https://api.nglorok.com/api/v1/license';
        $this->option_name = 'velocity_license';

        // Schedule a weekly check for license status
        if (!wp_next_scheduled('check_license_status')) {
            wp_schedule_event(time(), 'weekly', 'check_license_status');
        }

        add_action('check_license_status', array($this, 'check_license'));
        add_action('wp_ajax_check_license', array($this, 'ajax_check_license'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function headers_api()
    {
        $opt = get_option($this->option_name);
        $license_key = is_array($opt) && isset($opt['key']) ? $opt['key'] : '';
        return [
            'Content-Type' => 'application/json',
            'license' => $license_key,
            'source' => parse_url(get_site_url(), PHP_URL_HOST),
        ];
    }

    private function send_request($license_key)
    {
        $timeout  = max(5, (int) apply_filters('velocity_addons_license_timeout', 20));
        $attempts = max(1, (int) apply_filters('velocity_addons_license_attempts', 2));
        $retry_delay_ms = max(0, (int) apply_filters('velocity_addons_license_retry_delay_ms', 400));

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'license' => $license_key,
                'source' => parse_url(get_site_url(), PHP_URL_HOST),
            ),
            'body' => array(
                'wp_version'  => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'velocity_addons_version' => (defined('VELOCITY_ADDONS_VERSION') ? VELOCITY_ADDONS_VERSION : 'unknown'),
            ),
            'timeout'     => $timeout,
            'redirection' => 3,
            'sslverify'   => true,
            'user-agent'  => 'Velocity-Addons/' . (defined('VELOCITY_ADDONS_VERSION') ? VELOCITY_ADDONS_VERSION : 'unknown'),
        );

        $response = null;
        $last_error_message = '';

        for ($i = 1; $i <= $attempts; $i++) {
            $response = wp_remote_get($this->api_url, $args);
            if (!is_wp_error($response)) {
                break;
            }

            $last_error_message = $response->get_error_message();
            error_log('License request error (attempt ' . $i . '/' . $attempts . '): ' . $last_error_message);

            if ($i < $attempts && $retry_delay_ms > 0) {
                usleep($retry_delay_ms * 1000);
            }
        }

        if (is_wp_error($response)) {
            $message = $last_error_message ?: $response->get_error_message();
            if (stripos($message, 'cURL error 28') !== false) {
                $message = 'Koneksi ke server lisensi timeout. Silakan coba lagi beberapa saat.';
            }

            return array(
                'status'  => false,
                'message' => $message,
            );
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            $body_message = '';
            $decoded_error = json_decode((string) wp_remote_retrieve_body($response), true);
            if (is_array($decoded_error) && isset($decoded_error['message']) && is_scalar($decoded_error['message'])) {
                $body_message = (string) $decoded_error['message'];
            }
            $message = $body_message !== '' ? $body_message : ('License server returned HTTP ' . $http_code . '.');
            error_log('License request HTTP error: ' . $message);

            return array(
                'status'  => false,
                'message' => $message,
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return array(
                'status'  => false,
                'message' => 'Invalid response from license server.',
            );
        }

        return $data;
    }

    private function store_license_data($license_key, $status, $expire_date)
    {
        // Save license data in WordPress options
        $data = [
            'key' => $license_key,
            'expire_date' => $expire_date,
            'status' => $status,
        ];
        update_option($this->option_name, $data);
    }

    public function check_license()
    {
        $opt = get_option($this->option_name);
        $license_key = is_array($opt) && isset($opt['key']) ? $opt['key'] : '';
        if (empty($license_key)) {
            $this->handle_license_error('License Key is required');
            return;
        }
        $response = $this->send_request($license_key);

        if (is_array($response)) {
            $status = isset($response['status']) ? $response['status'] : false;
            if ($status !== 200 && $status !== true) {
                $message = isset($response['message']) ? $response['message'] : 'License check failed.';
                $this->handle_license_error($message);
            }
        } else {
            $this->handle_server_error();
        }
    }

    public function ajax_check_license()
    {

        // Check if license key is provided
        if (isset($_POST['license_key'])) {
            $license_key = sanitize_text_field($_POST['license_key']);
            $result = $this->verify_license_key($license_key);

            if (!empty($result['success'])) {
                // Success response
                wp_send_json_success($result['response']);
            } else {
                // Error response
                wp_send_json_error(isset($result['message']) ? $result['message'] : 'Server not reachable');
            }
        }
        wp_die(); // Required to properly terminate AJAX requests
    }

    public function verify_license_key($license_key)
    {
        $license_key = sanitize_text_field((string) $license_key);

        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => 'License Key is required',
            );
        }

        $response = $this->send_request($license_key);

        if ($response && isset($response['status']) && $response['status']) {
            $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
            $status = isset($data['status']) ? $data['status'] : '';
            $expire = isset($data['exp']) ? $data['exp'] : '';
            $this->store_license_data($license_key, $status, $expire);

            return array(
                'success'  => true,
                'message'  => isset($response['message']) ? $response['message'] : 'License verified.',
                'response' => $response,
            );
        }

        if (is_array($response) && isset($response['data']['message'])) {
            $message = $response['data']['message'];
            $this->handle_license_error($message);
        } elseif (is_array($response) && isset($response['message'])) {
            $message = (string) $response['message'];
            $this->handle_license_error($message);
        } else {
            $message = 'Server not reachable';
            $this->handle_server_error();
        }

        delete_option($this->option_name);

        return array(
            'success'  => false,
            'message'  => $message,
            'response' => $response,
        );
    }

    public function activate_license($license_key)
    {
        $result = $this->verify_license_key($license_key);
        if (!empty($result['success'])) {
            return $result['response'];
        }
        return false;
    }

    private function handle_license_error($message)
    {
        $this->add_settings_error_safe('license_activation', 'license_error', $message, 'error');
        error_log('License Check Error: ' . $message);
    }

    private function handle_server_error()
    {
        $this->add_settings_error_safe('license_activation', 'server_error', 'Tidak dapat menghubungi server. Silakan coba lagi nanti.', 'error');
        error_log('License Check Error: Server not reachable.');
    }

    private function add_settings_error_safe($setting, $code, $message, $type = 'error')
    {
        if (!function_exists('add_settings_error')) {
            $template_file = ABSPATH . 'wp-admin/includes/template.php';
            if (file_exists($template_file)) {
                require_once $template_file;
            }
        }

        if (function_exists('add_settings_error')) {
            add_settings_error($setting, $code, $message, $type);
        }
    }

    public function enqueue_scripts()
    {
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        // Enqueue your custom script
        // wp_enqueue_script('license-check', plugin_dir_url(__FILE__) . 'js/license-check.js', array('jquery'), null, true);
        // Localize script to pass the AJAX URL
        // wp_localize_script('license-check', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }
}

// Initialize the class
$velocity_license = new Velocity_Addons_License();
