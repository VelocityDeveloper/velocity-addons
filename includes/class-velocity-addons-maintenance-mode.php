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

class Velocity_Addons_Maintenance_Mode
{
    public function __construct()
    {
        if (get_option('maintenance_mode')) {
            // Jalankan di tahap template_redirect (front-end) agar tak ganggu bootstrap editor/admin
            add_action('template_redirect', [$this, 'check_maintenance_mode'], 0);
            add_action('admin_notices', [self::class, 'qc_maintenance']);
        }
    }

    public function check_maintenance_mode()
    {
        // 1) Selalu lolos untuk konteks non-frontend/editor
        if (is_admin()) return;
        if (wp_doing_ajax()) return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        if (is_customize_preview() || is_preview()) return;

        // 2) Lolos untuk user yang sedang kerja (editor/builder)
        if (is_user_logged_in() && current_user_can('edit_posts')) return;

        // 3) Jika mau whitelist halaman tertentu (tetap boleh), contoh 'myaccount'
        if (is_page('myaccount')) return;

        $opt     = get_option('maintenance_mode_data', []);
        $hd      = !empty($opt['header']) ? $opt['header'] : 'Maintenance Mode';
        $bd      = !empty($opt['body']) ? $opt['body'] : 'We are currently performing maintenance. Please check back later.';
        $bg_id   = !empty($opt['background']) ? absint($opt['background']) : 0;
        $bg_url  = $bg_id ? wp_get_attachment_image_url($bg_id, 'full') : '';
        $show_logo = !array_key_exists('show_logo', $opt) || !empty($opt['show_logo']);
        $logo_id   = !empty($opt['logo']) ? absint($opt['logo']) : 0;
        $logo_url  = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';

        $heading        = esc_html($hd);
        $body_content   = wpautop(wp_kses_post($bd));
        $shell_background = $bg_url
            ? "background-image: linear-gradient(rgba(255,255,255,.78), rgba(255,255,255,.88)), url('" . esc_url($bg_url) . "'); background-position: center; background-repeat: no-repeat; background-size: cover;"
            : "background: #ffffff;";

        // Set Headers for 503 Service Unavailable
        if (!headers_sent()) {
            header('HTTP/1.1 503 Service Unavailable');
            header('Status: 503 Service Unavailable');
            header('Retry-After: 3600');
        }
?>
        <!DOCTYPE html>
        <html lang="<?php echo get_locale(); ?>">

        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo $heading; ?> - <?php bloginfo('name'); ?></title>
            <style>
                * {
                    box-sizing: border-box;
                }

                :root {
                    --maintenance-text: #111111;
                    --maintenance-muted: #666666;
                    --maintenance-border: #e7e7e7;
                    --maintenance-button: #111111;
                    --maintenance-button-hover: #000000;
                }

                html,
                body {
                    min-height: 100%;
                }

                body {
                    margin: 0;
                    min-height: 100vh;
                    background: #ffffff;
                    color: var(--maintenance-text);
                    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .maintenance-shell {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 32px 20px;
                    <?php echo $shell_background; ?>
                }

                .maintenance-card {
                    width: 100%;
                    max-width: 720px;
                    text-align: center;
                    background: transparent;
                    border: 0;
                    border-radius: 0;
                    padding: 56px 40px;
                    box-shadow: none;
                }

                .maintenance-media {
                    width: min(32vw, 150px);
                    margin: 0 auto 24px;
                    aspect-ratio: 1;
                    display: grid;
                    place-items: center;
                }

                .maintenance-media .logo {
                    width: 78%;
                    height: auto;
                    overflow: visible;
                }

                .maintenance-media.has-custom-logo {
                    width: 100%;
                    height: auto;
                    aspect-ratio: auto;
                    margin: 0 0 24px;
                    padding-left: 0;
                    padding-right: 0;
                    display: flex;
                    justify-content: center;
                }

                .maintenance-media .custom-logo {
                    width: auto;
                    height: auto;
                    max-width: none;
                    max-height: none;
                    margin-left: 0;
                    margin-right: 0;
                    padding-left: 0;
                    padding-right: 0;
                }

                .maintenance-media .ring-outer,
                .maintenance-media .ring-inner,
                .maintenance-media .bolt-shadow,
                .maintenance-media .bolt-main {
                    transform-box: fill-box;
                    transform-origin: center;
                }

                .maintenance-media .ring-inner {
                    opacity: 0;
                    animation: inner-cycle 1.2s ease-out forwards;
                }

                .maintenance-media .ring-outer {
                    opacity: 0;
                    animation: outer-cycle 1.45s ease-out .12s forwards;
                }

                .maintenance-media .bolt-shadow,
                .maintenance-media .bolt-main {
                    transform-origin: center bottom;
                }

                .maintenance-media .bolt-shadow {
                    opacity: 0;
                    animation: shadow-cycle .56s cubic-bezier(.12, .84, .24, 1) .08s forwards;
                }

                .maintenance-media .bolt-main {
                    opacity: 0;
                    animation: main-cycle .56s cubic-bezier(.12, .84, .24, 1) .12s forwards;
                }

                .maintenance-media .speed-line {
                    fill: none;
                    stroke: #aefe22;
                    stroke-width: 1.15;
                    stroke-linecap: round;
                    stroke-dasharray: 18;
                    stroke-dashoffset: 18;
                    opacity: 0;
                    animation: speed-trail .42s linear .1s forwards;
                }

                .maintenance-media .speed-line.second {
                    animation-delay: 0.1s;
                }

                .maintenance-media .speed-line.third {
                    animation-delay: 0.16s;
                }

                .maintenance-card h1 {
                    margin: 0 0 14px;
                    color: var(--maintenance-text);
                    font-size: clamp(2rem, 4vw, 3.25rem);
                    line-height: 1.08;
                    letter-spacing: -0.04em;
                    font-weight: 800;
                }

                .maintenance-card .content {
                    max-width: 560px;
                    margin: 0 auto 28px;
                    color: var(--maintenance-muted);
                    font-size: 1.05rem;
                    line-height: 1.75;
                }

                .maintenance-card .content p {
                    margin: 0 0 1rem;
                }

                .maintenance-card .content p:last-child {
                    margin-bottom: 0;
                }

                .btn-reload {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 180px;
                    padding: 14px 24px;
                    border-radius: 999px;
                    border: 1px solid var(--maintenance-button);
                    background: var(--maintenance-button);
                    color: #ffffff;
                    text-decoration: none;
                    font-size: 0.95rem;
                    font-weight: 700;
                    letter-spacing: 0.01em;
                    transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
                }

                .btn-reload:hover {
                    background: var(--maintenance-button-hover);
                    border-color: var(--maintenance-button-hover);
                    color: #ffffff;
                    transform: translateY(-1px);
                }

                @keyframes inner-cycle {
                    0% {
                        opacity: 0;
                        transform: scale(0.3) rotate(-35deg);
                    }

                    72% {
                        opacity: 1;
                        transform: scale(1.08) rotate(2deg);
                    }

                    100% {
                        opacity: 1;
                        transform: scale(1);
                    }
                }

                @keyframes outer-cycle {

                    0%,
                    18% {
                        opacity: 0;
                        transform: scale(0.3) rotate(-35deg);
                    }

                    74% {
                        opacity: 1;
                        transform: scale(1.08) rotate(2deg);
                    }

                    100% {
                        opacity: 1;
                        transform: scale(1);
                    }
                }

                @keyframes shadow-cycle {

                    0%,
                    8% {
                        opacity: 0;
                        transform: translate(72px, -88px) scale(0.38, 0.72);
                    }

                    62% {
                        opacity: 1;
                        transform: translate(-2px, 3px) scale(1.03);
                    }

                    100% {
                        opacity: 1;
                        transform: translate(0) scale(1);
                    }
                }

                @keyframes main-cycle {

                    0%,
                    10% {
                        opacity: 0;
                        transform: translate(72px, -88px) scale(0.38, 0.72);
                    }

                    66% {
                        opacity: 1;
                        transform: translate(-2px, 3px) scale(1.03);
                        filter: drop-shadow(0 0 9px rgba(190, 255, 32, 0.95));
                    }

                    100% {
                        opacity: 1;
                        transform: translate(0) scale(1);
                        filter: none;
                    }
                }

                @keyframes speed-trail {

                    0%,
                    18% {
                        opacity: 0;
                        stroke-dashoffset: 18;
                    }

                    56% {
                        opacity: 0.9;
                    }

                    100% {
                        opacity: 0;
                        stroke-dashoffset: -18;
                    }
                }

                @keyframes float {

                    0%,
                    100% {
                        transform: translateY(0);
                    }

                    50% {
                        transform: translateY(-9px);
                    }
                }

                @media (max-width: 640px) {
                    .maintenance-shell {
                        padding: 20px 14px;
                    }

                    .maintenance-card {
                        padding: 36px 22px;
                    }

                    .maintenance-media {
                        width: min(42vw, 120px);
                        margin-bottom: 20px;
                    }
                }

                @media (prefers-reduced-motion: reduce) {

                    .maintenance-media .logo,
                    .maintenance-media .ring-outer,
                    .maintenance-media .ring-inner,
                    .maintenance-media .bolt-shadow,
                    .maintenance-media .bolt-main,
                    .maintenance-media .speed-line {
                        animation: none !important;
                    }
                }
            </style>
        </head>

        <body>
            <div class="maintenance-shell">
                <div class="maintenance-card">
                    <?php if ($show_logo) : ?>
                    <div class="maintenance-media<?php echo $logo_url ? ' has-custom-logo' : ''; ?>"<?php echo $logo_url ? '' : ' aria-hidden="true"'; ?>>
                        <?php if ($logo_url) : ?>
                        <img class="logo custom-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <?php else : ?>
                        <svg class="logo" viewBox="0 0 93.300247 107.21929" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="vd-blue" x1="82" y1="75" x2="113" y2="146" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#0b85ad" />
                                    <stop offset=".45" stop-color="#04759f" />
                                    <stop offset="1" stop-color="#061c42" />
                                </linearGradient>
                                <linearGradient id="vd-green" x1="120" y1="70" x2="76" y2="140" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#008c08" />
                                    <stop offset="1" stop-color="#0b8f00" />
                                </linearGradient>
                                <linearGradient id="vd-lime" x1="126" y1="52" x2="78" y2="140" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#c9ff19" />
                                    <stop offset=".48" stop-color="#adff1d" />
                                    <stop offset="1" stop-color="#59cf2e" />
                                </linearGradient>
                            </defs>
                            <g transform="translate(-49.06732 -47.742905)">
                                <path class="ring-outer" fill="url(#vd-blue)" d="M95.7177 61.661952a46.65 46.65 0 1 0 0 93.300248 46.65 46.65 0 0 0 0-93.300248Zm0 10.20041a37.05 36.45 0 1 1 0 72.899938 37.05 36.45 0 0 1 0-72.899938Z" />
                                <path class="ring-inner" fill="url(#vd-blue)" d="M84.317601 93.352764a27.55924 27.55924 0 1 0 0 55.118626 27.55924 27.55924 0 0 0 0-55.118626Zm0 6.026046a21.887887 21.533426 0 1 1 0 43.06683 21.887887 21.533426 0 0 1 0-43.06683Z" />
                                <path class="speed-line" d="m151 39-21 24" />
                                <path class="speed-line second" d="m157 53-18 20" />
                                <path class="speed-line third" d="m143 34-15 17" />
                                <path class="bolt-shadow" fill="url(#vd-green)" d="m102.0894 70.232492 39.6875-.52917-66.93958 70.643748Z" />
                                <path class="bolt-main" fill="url(#vd-lime)" d="m98.64982 56.209572 34.39583-8.466667-58.20833 92.604165Z" />
                            </g>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <h1><?php echo $heading; ?></h1>
                    <div class="content">
                        <?php echo $body_content; ?>
                    </div>
                    <a href="<?php echo esc_url(home_url()); ?>" class="btn-reload">Muat Ulang</a>
                </div>
            </div>
        </body>

        </html>
<?php
        exit();
    }

    public static function qc_maintenance() {}

    public static function qc_maintenance_list() {}

    public static function check_domain_extension()
    {
        ob_start();
        $site_url = get_site_url();
        $domain   = parse_url($site_url, PHP_URL_HOST);
        $domain_parts = explode('.', (string) $domain);
        $extension    = array_pop($domain_parts);
        $sub_extension = array_pop($domain_parts);
        $valid_extensions = ['go.id', 'desa.id', 'sch.id', 'ac.id'];

        if (in_array($sub_extension . '.' . $extension, $valid_extensions, true)) {
            echo '<p>Setting Desain By Velocity => Open New Tab. Linknya Di Warna Sesuai Background, Rata Kiri (Pojok), Saat Hover Jangan Icon Tangan Tapi Icon Panah Sprti Pada Saat Tanpa Hover.</p>';
        }
        echo '<p>Pastikan Copy Right Sesuai Tahun!</p>';
        return ob_get_clean();
    }

    public static function check_permalink_settings()
    {
        ob_start();
        $permalinks  = get_option('permalink_structure');
        $linksetting = admin_url('options-permalink.php');
        if (empty($permalinks) || $permalinks !== '/%category%/%postname%/') {
            echo '<p>Peringatan: Permalink belum disetting. Silakan setting <a href="' . $linksetting . '"><b>disini.</b></a></p>';
        }
        return ob_get_clean();
    }

    public static function check_site_icon()
    {
        ob_start();
        $site_icon  = get_site_icon_url();
        $linksetting = admin_url('options-general.php');
        if (empty($site_icon)) {
            echo '<p>Peringatan: Favicon belum disetting. Silakan setting <a href="' . $linksetting . '"><b>disini.</b></a></p>';
        }
        return ob_get_clean();
    }

    public static function check_recaptcha()
    {
        ob_start();
        $linksetting     = admin_url('admin.php?page=admin_velocity_addons');
        $check_recaptcha = get_option('captcha_velocity');
        $sitekey  = $check_recaptcha['sitekey']  ?? '';
        $secretkey = $check_recaptcha['secretkey'] ?? '';
        if (empty($sitekey) || empty($secretkey)) {
            echo '<p>Peringatan: Recaptcha belum disetting. Silakan setting <a href="' . $linksetting . '"><b>disini.</b></a></p>';
        }
        return ob_get_clean();
    }

    public static function check_seo()
    {
        ob_start();
        $linksetting  = admin_url('admin.php?page=velocity_seo_settings');
        $home_keywords = get_option('home_keywords');
        $share_image  = get_option('share_image');
        if (empty($home_keywords) || empty($share_image)) {
            echo '<p>Peringatan: SEO belum disetting. Silakan setting <a href="' . $linksetting . '"><b>disini.</b></a></p>';
        }
        return ob_get_clean();
    }

    public static function check_installed_plugins()
    {
        ob_start();
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins            = get_plugins();
        $auto_update_plugins = get_site_option('auto_update_plugins', []);
        $excluded_plugins = [
            'bb-ultimate-addon/bb-ultimate-addon.php',
            'velocity-toko/velocity-toko.php',
            'velocity-expedisi/velocity-expedisi.php',
            'velocity-donasi/velocity-donasi.php',
            'velocity-produk/velocity-produk.php',
            'velocity-addons/velocity-addons.php',
            'vd-gallery/vd-gallery.php',
            'velocity-toko-kursus/velocity-kursus.php',
            'custom-plugin/custom-plugin.php',
        ];
        foreach ($plugins as $plugin_file => $plugin_data) {
            if (!in_array($plugin_file, $excluded_plugins, true) && !in_array($plugin_file, $auto_update_plugins, true)) {
                echo '<p>' . esc_html($plugin_data['Name']) . ' belum diaktifkan untuk pembaruan otomatis.</p>';
            }
        }
        return ob_get_clean();
    }
}

// Init
$velocity_maintenance_mode = new Velocity_Addons_Maintenance_Mode();
