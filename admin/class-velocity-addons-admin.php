<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://velocitydeveloper.com
 * @since      1.0.0
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/admin
 * @author     Velocity <bantuanvelocity@gmail.com>
 */
class Velocity_Addons_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/velocity-addons-admin.css', array(), $this->version, 'all');

		// Enqueue Chart.js
		wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	private function resolve_velocity_settings_page($page)
	{
		if ($page !== 'admin_velocity_addons' || !isset($_GET['sub'])) {
			return $page;
		}

		$sub_map = array(
			'general' => 'velocity_general_settings',
			'fitur' => 'velocity_feature_settings',
			'captcha' => 'velocity_captcha_settings',
			'maintenance' => 'velocity_maintenance_settings',
			'license' => 'velocity_license_settings',
			'security' => 'velocity_security_settings',
			'auto-resize' => 'velocity_auto_resize_settings',
			'seo' => 'velocity_seo_settings',
			'whatsapp' => 'velocity_floating_whatsapp',
			'whatsapp-style' => 'velocity_floating_whatsapp_style',
			'script' => 'velocity_snippet_settings',
			'body' => 'velocity_snippet_body_settings',
			'footer' => 'velocity_snippet_footer_settings',
			'import-artikel' => 'velocity_news_settings',
			'statistics' => 'velocity_statistics',
			'shortcode' => 'velocity_statistics_shortcode',
			'optimasi' => 'velocity_optimize_db',
			'1-click-setup' => 'velocity_one_click_setup',
		);

		$sub = sanitize_key(wp_unslash($_GET['sub']));
		return isset($sub_map[$sub]) ? $sub_map[$sub] : $page;
	}

	public function enqueue_scripts()
	{
		$admin_script_path = plugin_dir_path(__FILE__) . 'js/velocity-addons-admin.js';
		$admin_script_ver  = file_exists($admin_script_path) ? (string) filemtime($admin_script_path) : $this->version;

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/velocity-addons-admin.js', array('jquery'), $admin_script_ver, false);

		$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
		$resolved_page = $this->resolve_velocity_settings_page($page);

		if ($this->is_velocity_settings_page($resolved_page)) {
			$settings_handle = 'velocity-addons-settings-bridge';
			$alpine_handle   = 'alpinejs';
			$settings_path   = plugin_dir_path(__FILE__) . 'js/velocity-addons-settings.js';
			$settings_ver    = file_exists($settings_path) ? (string) filemtime($settings_path) : $this->version;

			wp_enqueue_script(
				$settings_handle,
				plugin_dir_url(__FILE__) . 'js/velocity-addons-settings.js',
				array(),
				$settings_ver,
				true
			);
			wp_localize_script(
				$settings_handle,
				'velocitySettingsConfig',
				array(
					'restBase' => esc_url_raw(rest_url('velocity-addons/v1')),
					'nonce'    => wp_create_nonce('wp_rest'),
					'page'     => $resolved_page,
				)
			);
			wp_script_add_data($settings_handle, 'defer', true);

			wp_enqueue_script(
				$alpine_handle,
				'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js',
				array($settings_handle),
				'3.14.8',
				true
			);
			wp_script_add_data($alpine_handle, 'defer', true);
		}

		if ($this->is_velocity_rest_action_page($resolved_page)) {
			$actions_handle = 'velocity-addons-admin-actions';
			$actions_path   = plugin_dir_path(__FILE__) . 'js/velocity-addons-admin-actions.js';
			$actions_ver    = file_exists($actions_path) ? (string) filemtime($actions_path) : $this->version;

			wp_enqueue_script(
				$actions_handle,
				plugin_dir_url(__FILE__) . 'js/velocity-addons-admin-actions.js',
				array(),
				$actions_ver,
				true
			);
			wp_localize_script(
				$actions_handle,
				'velocitySettingsConfig',
				array(
					'restBase' => esc_url_raw(rest_url('velocity-addons/v1')),
					'nonce'    => wp_create_nonce('wp_rest'),
					'page'     => $resolved_page,
				)
			);
			wp_script_add_data($actions_handle, 'defer', true);
		}

		$snippet_subs = array('script', 'body', 'footer');
		$is_snippet_page = in_array($page, array('velocity_snippet_settings', 'velocity_snippet_body_settings', 'velocity_snippet_footer_settings'), true)
			|| ($page === 'admin_velocity_addons' && isset($_GET['sub']) && in_array(sanitize_key(wp_unslash($_GET['sub'])), $snippet_subs, true));

		if ($is_snippet_page) {
			if (function_exists('wp_enqueue_code_editor')) {
				$settings = function_exists('wp_code_editor_settings')
					? wp_code_editor_settings(array(
						'type'       => 'htmlmixed',
						'codemirror' => array(
							'indentUnit'    => 2,
							'tabSize'       => 2,
							'lineWrapping'  => true,
							'lineNumbers'   => true,
							'autoCloseTags' => true,
							'matchTags'     => array('bothTags' => true),
						),
					))
					: array(
						'type'       => 'htmlmixed',
						'codemirror' => array(
							'indentUnit'    => 2,
							'tabSize'       => 2,
							'lineWrapping'  => true,
							'lineNumbers'   => true,
							'autoCloseTags' => true,
							'matchTags'     => array('bothTags' => true),
						),
					);
				wp_enqueue_code_editor($settings);
				wp_add_inline_script(
					'code-editor',
					'jQuery(function(){var ids=["#header_snippet","#body_snippet","#footer_snippet"];ids.forEach(function(sel){var el=document.querySelector(sel);if(el&&window.wp&&wp.codeEditor){var ed=wp.codeEditor.initialize(el,' . wp_json_encode($settings) . ');if(ed&&ed.codemirror){ed.codemirror.on("change",function(){el.value=ed.codemirror.getValue();});}}});});'
				);
			}
		}

		if ($page == 'admin_velocity_addons') {
			wp_enqueue_script('chartjs-scripts', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), $this->version, false);
			wp_enqueue_script(array('jquery', 'jquery-ui-datepicker', 'jquery-ui-tooltip'));
		}
	}

	private function is_velocity_settings_page($page)
	{
		return in_array($page, array(
			'velocity_general_settings',
			'velocity_feature_settings',
			'velocity_captcha_settings',
			'velocity_maintenance_settings',
			'velocity_license_settings',
			'velocity_security_settings',
			'velocity_auto_resize_settings',
			'velocity_seo_settings',
			'velocity_floating_whatsapp',
			'velocity_floating_whatsapp_style',
			'velocity_snippet_settings',
			'velocity_snippet_body_settings',
			'velocity_snippet_footer_settings',
			'velocity_news_settings',
		), true);
	}

	private function is_velocity_rest_action_page($page)
	{
		return in_array($page, array(
			'velocity_statistics',
			'velocity_statistics_shortcode',
			'velocity_optimize_db',
			'velocity_one_click_setup',
		), true);
	}
}

class Velocity_Addons_Admin_Navigation
{
	public static function get_items()
	{
		$items = array(
			array(
				'page'     => 'admin_velocity_addons',
				'label'    => 'Dashboard',
				'children' => array(
					array(
						'page'  => 'admin_velocity_addons',
						'label' => 'Dashboard',
					),
					array(
						'page'    => 'velocity_seo_settings',
						'label'   => 'SEO',
						'enabled' => get_option('seo_velocity', '1') === '1',
					),
				),
			),
			array(
				'page'     => 'velocity_general_settings',
				'label'    => 'General',
				'children' => array(
					array(
						'page'  => 'velocity_general_settings',
						'label' => 'General',
					),
					array(
						'page'  => 'velocity_feature_settings',
						'label' => 'Fitur',
					),
					array(
						'page'  => 'velocity_auto_resize_settings',
						'label' => 'Auto Resize',
					),
				),
			),
			array(
				'page'     => 'velocity_security_settings',
				'label'    => 'Security',
				'children' => array(
					array(
						'page'  => 'velocity_captcha_settings',
						'label' => 'Captcha',
					),
					array(
						'page'  => 'velocity_maintenance_settings',
						'label' => 'Maintenance',
					),
				),
			),
			array(
				'page'     => 'velocity_snippet_settings',
				'label'    => 'Script',
				'children' => array(
					array(
						'page'  => 'velocity_snippet_settings',
						'label' => 'Script',
					),
					array(
						'page'  => 'velocity_snippet_body_settings',
						'label' => 'Body',
					),
					array(
						'page'  => 'velocity_snippet_footer_settings',
						'label' => 'Footer',
					),
				),
			),
			array(
				'page'     => 'velocity_floating_whatsapp',
				'label'    => 'WhatsApp',
				'enabled'  => get_option('floating_whatsapp', '1') === '1',
				'children' => array(
					array(
						'page'  => 'velocity_floating_whatsapp',
						'label' => 'General',
					),
					array(
						'page'  => 'velocity_floating_whatsapp_style',
						'label' => 'Style',
					),
				),
			),
			array(
				'page'     => 'velocity_statistics',
				'label'    => 'Statistics',
				'enabled'  => get_option('statistik_velocity', '1') === '1',
				'children' => array(
					array(
						'page'  => 'velocity_statistics',
						'label' => 'Statistic',
					),
					array(
						'page'  => 'velocity_statistics_shortcode',
						'label' => 'Shortcode',
					),
				),
			),
			array(
				'page'     => 'velocity_optimize_db',
				'label'    => 'Optimize',
				'children' => array(
					array(
						'page'  => 'velocity_optimize_db',
						'label' => 'Optimize Database',
					),
				),
			),
			array(
				'page'     => 'velocity_license_settings',
				'label'    => 'PRO',
				'badge'    => self::get_license_badge(),
				'children' => array(
					array(
						'page'  => 'velocity_license_settings',
						'label' => 'License',
					),
					array(
						'page'  => 'velocity_beaver_builder',
						'label' => 'Beaver Builder',
					),
					array(
						'page'  => 'velocity_one_click_setup',
						'label' => '1 Click setup',
					),
					array(
						'page'    => 'velocity_news_settings',
						'label'   => 'Import Artikel',
						'enabled' => get_option('news_generate', '1') === '1',
					),
				),
			),
		);

		return array_values(
			array_filter(
				$items,
				static function ($item) {
					return !isset($item['enabled']) || $item['enabled'];
				}
			)
		);
	}

	private static function get_license_badge()
	{
		$opt = get_option('velocity_license');
		$status = is_array($opt) && isset($opt['status']) ? strtolower(trim((string) $opt['status'])) : '';
		$is_active = in_array($status, array('active', 'activated', 'valid', '1', 'true', '200'), true);
		$label = $is_active ? 'Active' : 'Inactive';
		$bg = $is_active ? '#16a34a' : '#dc2626';

		return array(
			'label' => $label,
			'bg'    => $bg,
		);
	}

	private static function get_parent_page_slug($label)
	{
		if ($label === 'Security') {
			return 'velocity_security_settings';
		}

		if ($label === 'Dashboard') {
			return 'admin_velocity_addons';
		}

		if ($label === 'General') {
			return 'velocity_general_settings';
		}

		return '';
	}

	private static function get_admin_velocity_addons_sub_map()
	{
		return array(
			'admin_velocity_addons' => 'dashboard',
			'velocity_auto_resize_settings' => 'auto-resize',
			'velocity_seo_settings' => 'seo',
			'velocity_general_settings' => 'general',
			'velocity_feature_settings' => 'fitur',
			'velocity_security_settings' => 'security',
			'velocity_captcha_settings' => 'captcha',
			'velocity_maintenance_settings' => 'maintenance',
			'velocity_snippet_settings' => 'script',
			'velocity_snippet_body_settings' => 'body',
			'velocity_snippet_footer_settings' => 'footer',
			'velocity_floating_whatsapp' => 'whatsapp',
			'velocity_floating_whatsapp_style' => 'whatsapp-style',
			'velocity_statistics' => 'statistics',
			'velocity_statistics_shortcode' => 'shortcode',
			'velocity_optimize_db' => 'optimasi',
			'velocity_license_settings' => 'license',
			'velocity_beaver_builder' => 'beaver-builder',
			'velocity_one_click_setup' => '1-click-setup',
			'velocity_news_settings' => 'import-artikel',
		);
	}

	private static function get_admin_velocity_addons_page_map()
	{
		return array_flip(self::get_admin_velocity_addons_sub_map());
	}

	private static function get_menu_url($page)
	{
		$sub_map = self::get_admin_velocity_addons_sub_map();
		if (isset($sub_map[$page])) {
			return admin_url('admin.php?page=admin_velocity_addons&sub=' . $sub_map[$page]);
		}

		return admin_url('admin.php?page=' . $page);
	}

	public static function render($current_page = '')
	{
		if ($current_page === '') {
			$current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
		}

		if ($current_page === 'admin_velocity_addons' && isset($_GET['sub'])) {
			$sub = sanitize_key(wp_unslash($_GET['sub']));
			$page_map = self::get_admin_velocity_addons_page_map();
			if (isset($page_map[$sub])) {
				$current_page = $page_map[$sub];
			}
		}

		$items = self::get_items();
		if (empty($items) || $current_page === '') {
			return;
		}

		$subnav_parent = null;
		$subnav_children = array();

		echo '<div class="velocity-topnav">';
		echo '<div class="velocity-topnav__brand">Velocity Addons</div>';
		echo '<nav class="velocity-topnav__links" aria-label="Velocity Addons Navigation">';

		foreach ($items as $item) {
			if (empty($item['page']) || empty($item['label'])) {
				continue;
			}

			$children = !empty($item['children']) && is_array($item['children']) ? $item['children'] : array();
			$active_child = false;
			foreach ($children as $child) {
				if (!empty($child['page']) && $child['page'] === $current_page) {
					$active_child = true;
					break;
				}
			}

			$is_active = $item['page'] === $current_page || $active_child;
			if ($active_child || ($item['page'] === $current_page && !empty($children))) {
				$subnav_parent = $item['label'];
				$subnav_children = $children;
			}

			echo '<a class="velocity-topnav__link' . ($is_active ? ' is-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=' . $item['page'])) . '">' . esc_html($item['label']);
			if (!empty($item['badge'])) {
				$badge = $item['badge'];
				$bstyle = 'display:inline-flex;align-items:center;background:' . (isset($badge['bg']) ? esc_attr($badge['bg']) : '#555') . ';color:#fff;border-radius:9999px;font-size:10px;font-weight:700;line-height:1;padding:3px 7px;margin-left:8px;text-transform:uppercase;letter-spacing:.5px;';
				echo '<span class="velocity-topnav__badge" style="' . esc_attr($bstyle) . '">' . esc_html(isset($badge['label']) ? $badge['label'] : '') . '</span>';
			}
			echo '</a>';
		}

		echo '</nav>';
		echo '</div>';

		if (!empty($subnav_children)) {
			$parent_page_slug = self::get_parent_page_slug($subnav_parent);
			echo '<div class="velocity-subnav">';
			echo '<div class="velocity-subnav__title">' . esc_html($subnav_parent) . '</div>';
			echo '<nav class="velocity-subnav__links" aria-label="' . esc_attr($subnav_parent . ' Navigation') . '">';
			if ($parent_page_slug !== '' && (empty($subnav_children[0]['page']) || $subnav_children[0]['page'] !== $parent_page_slug)) {
				echo '<a class="velocity-subnav__link' . ($current_page === $parent_page_slug ? ' is-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=' . $parent_page_slug)) . '">' . esc_html($subnav_parent) . '</a>';
			}
			foreach ($subnav_children as $child) {
				if (empty($child['page']) || empty($child['label'])) {
					continue;
				}
				if (isset($child['enabled']) && !$child['enabled']) {
					continue;
				}
				echo '<a class="velocity-subnav__link' . ($child['page'] === $current_page ? ' is-active' : '') . '" href="' . esc_url(self::get_menu_url($child['page'])) . '">' . esc_html($child['label']) . '</a>';
			}
			echo '</nav>';
			echo '</div>';
		}
	}
}
