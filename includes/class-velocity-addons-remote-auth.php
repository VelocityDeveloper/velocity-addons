<?php

/**
 * Authentication middleware for remote control requests from CRM (new-vdnet).
 *
 * Uses HMAC-SHA256 signed requests with shared secret, timestamp freshness,
 * nonce uniqueness, and rate limiting.
 *
 * @package Velocity_Addons
 */

defined('ABSPATH') || exit;

class Velocity_Addons_Remote_Auth
{
    /** @var string Option name that stores the remote secret key. */
    const OPTION_KEY = 'velocity_remote_secret';

    /** @var int Maximum age of a request in seconds (5 minutes). */
    const MAX_TIMESTAMP_AGE = 300;

    /** @var int Maximum requests per minute per IP. */
    const RATE_LIMIT = 60;

    /**
     * Verify an incoming remote control request.
     *
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public static function verify()
    {
        $secret = get_option(self::OPTION_KEY, '');
        if (!is_string($secret) || $secret === '') {
            return new WP_Error(
                'velocity_remote_not_configured',
                'Remote control is not configured on this site.',
                array('status' => 503)
            );
        }

        // 1. Rate limiting
        $rate_error = self::check_rate_limit();
        if (is_wp_error($rate_error)) {
            return $rate_error;
        }

        // 2. Extract headers
        $signature = isset($_SERVER['HTTP_X_REMOTE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REMOTE_SIGNATURE'])) : '';
        $timestamp = isset($_SERVER['HTTP_X_REMOTE_TIMESTAMP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REMOTE_TIMESTAMP'])) : '';
        $nonce     = isset($_SERVER['HTTP_X_REMOTE_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REMOTE_NONCE'])) : '';

        if ($signature === '' || $timestamp === '' || $nonce === '') {
            return new WP_Error(
                'velocity_remote_missing_headers',
                'Missing required authentication headers.',
                array('status' => 401)
            );
        }

        // 3. Timestamp freshness (prevent replay attacks)
        $now = time();
        $ts  = (int) $timestamp;

        if ($ts <= 0 || abs($now - $ts) > self::MAX_TIMESTAMP_AGE) {
            return new WP_Error(
                'velocity_remote_expired',
                'Request timestamp is too old or in the future.',
                array('status' => 401)
            );
        }

        // 4. Nonce uniqueness (prevent replay within window)
        $nonce_key = 'velocity_remote_nonce_' . md5($nonce);
        if (get_transient($nonce_key) !== false) {
            return new WP_Error(
                'velocity_remote_nonce_used',
                'This nonce has already been used.',
                array('status' => 401)
            );
        }
        // Store nonce for the remaining timestamp window
        set_transient($nonce_key, 1, self::MAX_TIMESTAMP_AGE);

        // 5. Reconstruct expected signature
        $body = file_get_contents('php://input');
        $payload = $timestamp . '.' . $nonce . '.' . $body;
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            return new WP_Error(
                'velocity_remote_invalid_signature',
                'Invalid request signature.',
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Simple rate limiting using transients.
     *
     * @return true|WP_Error
     */
    private static function check_rate_limit()
    {
        $ip = self::get_client_ip();
        $key = 'velocity_remote_rate_' . md5($ip);
        $count = (int) get_transient($key);

        if ($count >= self::RATE_LIMIT) {
            return new WP_Error(
                'velocity_remote_rate_limited',
                'Rate limit exceeded. Try again later.',
                array('status' => 429)
            );
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS);

        return true;
    }

    /**
     * Get the remote secret key.
     *
     * @return string
     */
    public static function get_secret()
    {
        return (string) get_option(self::OPTION_KEY, '');
    }

    /**
     * Generate and store a new remote secret key.
     *
     * @return string The new secret key.
     */
    public static function rotate_secret()
    {
        $new_secret = self::generate_secret();
        update_option(self::OPTION_KEY, $new_secret);

        // Invalidate all existing nonces by changing the prefix
        // (nonces will naturally expire via transient TTL)

        return $new_secret;
    }

    /**
     * Set the remote secret key (used during initial setup).
     *
     * @param string $secret The secret to store.
     * @return void
     */
    public static function set_secret($secret)
    {
        update_option(self::OPTION_KEY, sanitize_text_field($secret));
    }

    /**
     * Check if remote control is configured.
     *
     * @return bool
     */
    public static function is_configured()
    {
        $secret = get_option(self::OPTION_KEY, '');
        return is_string($secret) && $secret !== '';
    }

    /**
     * Generate a cryptographically secure random secret.
     *
     * @return string
     */
    private static function generate_secret()
    {
        if (function_exists('wp_generate_password')) {
            return wp_generate_password(64, true, true);
        }
        return bin2hex(random_bytes(32));
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    private static function get_client_ip()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            return trim((string) $ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP']));
        }
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    }
}
