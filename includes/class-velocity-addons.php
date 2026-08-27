<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://velocitydeveloper.com
 * @since      1.0.0
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 * @author     Velocity <bantuanvelocity@gmail.com>
 */
class Velocity_Addons
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Velocity_Addons_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('VELOCITY_ADDONS_VERSION')) {
            $this->version = VELOCITY_ADDONS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'velocity-addons';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        //Floating whatsapp
        add_action('wp_footer', [$this, 'add_floating_whatsapp']);
    }

    public function add_floating_whatsapp()
    {
        $whatsapp_position    = get_option('whatsapp_position', 'right');
        $scrolltotop_position = get_option('scrolltotop_position', 'right');
        echo '<div class="floating-footer float-wa-' . $whatsapp_position . ' float-scrolltop-' . $scrolltotop_position . '" data-wa-position="' . esc_attr($whatsapp_position) . '" data-scrolltop-position="' . esc_attr($scrolltotop_position) . '">';
        Velocity_Addons_Floating_Whatsapp::justg_footer_whatsapp();
        Velocity_Addons_Floating_Whatsapp::add_floating_scrolltop();
        echo '</div>';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Velocity_Addons_Loader. Orchestrates the hooks of the plugin.
     * - Velocity_Addons_i18n. Defines internationalization functionality.
     * - Velocity_Addons_Admin. Defines all hooks for the admin area.
     * - Velocity_Addons_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-i18n.php';

        /**
         * Berisi Class untuk mematikan fungsi komentar di wordpress.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-disable-comments.php';

        /**
         * Berisi Class untuk mematikan semua notice di wp-admin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-hide-admin-notice.php';

        /**
         * Berisi Class untuk membatasi gagal login.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-limit-login-attempts.php';

        /**
         * Berisi Class untuk fungsi maintenance mode.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-maintenance-mode.php';

        /**
         * Berisi Class untuk disable XML RPC.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-disable-xmlrpc.php';

        /**
         * Berisi Class untuk disable REST API.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-disable-rest-api.php';

        /**
         * Berisi Class untuk disable visual editor.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-disable-gutenberg.php';

        /**
         * Berisi Class untuk block akses ke wp-admin berdasarkan kode negara.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-block-wp-login.php';

        /**
         * Berisi Class untuk auto update plugin
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-auto-updater.php';

        /**
         * Berisi Class untuk Classic Widget
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-classic-widget.php';

        /**
         * Berisi Class untuk standar Editor
         */
        // require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-standar-editor.php';

        /**
         * Berisi Class untuk hapus slug category
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-remove-slug-category.php';

        /**
         * Berisi Class untuk handle statistic
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-statistic.php';

        /**
         * Berisi Class untuk handle seo
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-seo.php';

        /**
         * Berisi Class untuk handle sitemap
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-sitemap.php';

        /**
         * Berisi Class untuk handle shortcode
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-shortcode.php';

        /**
         * Berisi Class untuk handle auto resize
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-auto-resize-image.php';

        /**
         * Berisi Class untuk handle captcha
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-captcha.php';

        /**
         * Class untuk menambah option page generate post
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-option-news-generate.php';

        /**
         * Class untuk menambah option gallery
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-gallery.php';

        /**
         * Class untuk menambah option optimasi
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-optimasi.php';

        /**
         * Class untuk menambah lisensi cecker
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-license.php';

        /**
         * Class untuk autentikasi remote control dari CRM
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-remote-auth.php';

        /**
         * Class untuk menambah option page untuk Floating Whatsapp
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-floating-whatsapp.php';

        /**
         * Class untuk menambah option page untuk Dashboard Menu
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-dashboard.php';

        /**
         * Class untuk menambah post duplicator di WordPress admin panel.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-post-duplicator.php';

        /**
         * Class untuk memasukkan kode snippet di WordPress admin panel.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-velocity-addons-snippet.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-velocity-addons-admin.php';

        /**
         * Class untuk menambah option page untuk Admin Option
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-velocity-option-page.php';

        /**
         * REST endpoints for settings pages in admin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-velocity-addons-settings-rest.php';

        /**
         * REST endpoints for statistics actions in admin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-velocity-addons-statistics-rest.php';

        /**
         * REST endpoints for optimize database actions in admin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-velocity-addons-optimize-rest.php';

        /**
         * REST endpoints for remote control from CRM (new-vdnet).
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-velocity-addons-remote-control-rest.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-velocity-addons-public.php';

        $this->loader = new Velocity_Addons_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Velocity_Addons_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Velocity_Addons_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Velocity_Addons_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Velocity_Addons_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Velocity_Addons_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
