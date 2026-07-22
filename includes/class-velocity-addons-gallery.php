<?php

/**
 * Register Gallery settings in the WordPress admin panel
 *
 * @link       https://velocitydeveloper.com
 * @since      1.0.0
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 */

 // Cek apakah plugin aktif
if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

// Jalankan class hanya jika VD Gallery tidak aktif
if ( ! class_exists( 'Velocity_Addons_Gallery' ) && ! is_plugin_active( 'vd-gallery/vd-gallery.php' ) ) {
    class Velocity_Addons_Gallery {
        public function __construct() {

            $gallery_velocity = get_option('velocity_gallery','0');
            if($gallery_velocity !== '1')
            return false;

            $this->vdgallery_dependency();

            // Menambahkan post type gallery
            // Hook JS & CSS
            add_action('admin_enqueue_scripts', [$this, 'admin_vdgallery_enqueue']);
            add_action( 'wp_enqueue_scripts', [$this, 'vdgallery_scripts_enqueue'] );

            add_action('init', [$this, 'vdgallery_post_type']);
            add_action('admin_menu', [$this, 'vdgallery_global_options_menu']);
            add_action('add_meta_boxes', [$this, 'vdgallery_dependency']);
            add_action('admin_enqueue_scripts', [$this, 'media_upload']);
            add_filter( 'manage_vdgallery_posts_columns', [$this, 'set_custom_edit_vdgallery_columns'] );
            add_action( 'manage_vdgallery_posts_custom_column' , [$this, 'custom_vdgallery_column'], 10, 2 );
        }
        
        public function vdgallery_dependency() {
            /**
             * Register meta boxes.
             * vdgallery-meta
             */
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vdgallery-option-fields.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/vdgallery-metabox.php';
        }

        public function vdgallery_global_options_menu() {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vdgallery-option-fields.php';

            add_submenu_page(
                'edit.php?post_type=vdgallery',
                'Global Option',
                'Global Option',
                'manage_options',
                'vdgallery-global-option',
                [$this, 'vdgallery_global_options_page']
            );
        }

        public function vdgallery_global_options_page() {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vdgallery-option-fields.php';

            if (
                isset( $_POST['vdgallery_global_options_nonce'] )
                && wp_verify_nonce( $_POST['vdgallery_global_options_nonce'], 'vdgallery_global_options' )
                && current_user_can( 'manage_options' )
            ) {
                $posted_options = isset( $_POST['vdgallery-global-options'] ) && is_array( $_POST['vdgallery-global-options'] )
                    ? $_POST['vdgallery-global-options']
                    : array();

                update_option(
                    'vdgallery_global_options',
                    array(
                        'gallery'   => vdgallery_sanitize_options(
                            isset( $posted_options['gallery'] ) && is_array( $posted_options['gallery'] ) ? $posted_options['gallery'] : array(),
                            vdgallery_get_gallery_option_fields()
                        ),
                        'slideshow' => vdgallery_sanitize_options(
                            isset( $posted_options['slideshow'] ) && is_array( $posted_options['slideshow'] ) ? $posted_options['slideshow'] : array(),
                            vdgallery_get_slideshow_option_fields()
                        ),
                    )
                );

                echo '<div class="notice notice-success is-dismissible"><p>Global option VD Gallery berhasil disimpan.</p></div>';
            }

            $global_options = vdgallery_get_global_options();
            ?>
            <div class="wrap vdgallery-global-options-wrap">
                <h1>VD Gallery Global Option</h1>
                <form method="post">
                    <?php wp_nonce_field( 'vdgallery_global_options', 'vdgallery_global_options_nonce' ); ?>

                    <h2>Galeri Option</h2>
                    <table class="form-table vdgallery-option-table" role="presentation">
                        <?php vdgallery_render_option_fields( vdgallery_get_gallery_option_fields(), $global_options['gallery'], 'vdgallery-global-options[gallery]' ); ?>
                    </table>

                    <h2>Slideshow Option</h2>
                    <table class="form-table vdgallery-option-table" role="presentation">
                        <?php vdgallery_render_option_fields( vdgallery_get_slideshow_option_fields(), $global_options['slideshow'], 'vdgallery-global-options[slideshow]' ); ?>
                    </table>

                    <?php submit_button( 'Simpan Global Option' ); ?>
                </form>
            </div>
            <?php
        }

        /**
            * Register script to dashboard area.
            * vdgallery-script
            */
        public function admin_vdgallery_enqueue($hook) {
            wp_enqueue_script('admin-vdgallery-script', plugin_dir_url(dirname(__FILE__)) . 'admin/js/vdgallery-admin.js');
            wp_enqueue_style( 'admin-vdgallery-style', plugin_dir_url(dirname(__FILE__)) . 'admin/css/vdgallery-admin.css');
        }

        /**
         * Load plugin sources.
         */
        public function vdgallery_scripts_enqueue() {
            $gallery_css_path = plugin_dir_path(dirname(__FILE__)) . 'public/css/vd-gallery.css';
            $gallery_css_ver  = file_exists( $gallery_css_path ) ? filemtime( $gallery_css_path ) : VELOCITY_ADDONS_VERSION;

            //CSS
            wp_enqueue_style( 'flickity-styles', 'https://unpkg.com/flickity@2/dist/flickity.min.css', [], VELOCITY_ADDONS_VERSION, false );
            wp_enqueue_style( 'magnific-popup-styles', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.0.0/magnific-popup.min.css', [], VELOCITY_ADDONS_VERSION, false );
            wp_enqueue_style( 'vdgallery-styles', plugin_dir_url(dirname(__FILE__)) . 'public/css/vd-gallery.css', [], $gallery_css_ver, false );

            //JS
            wp_enqueue_script( 'flickity-script', 'https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js', [], VELOCITY_ADDONS_VERSION, true );
            wp_enqueue_script( 'magnific-popup-script', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.0.0/jquery.magnific-popup.min.js', [], VELOCITY_ADDONS_VERSION, true );
            wp_enqueue_script( 'vdgallery-script', plugin_dir_url(dirname(__FILE__)) . 'public/js/vd-gallery.js', [], VELOCITY_ADDONS_VERSION, true );
        }

        public function vdgallery_post_type()
        {
            register_post_type('vdgallery', [
                'labels' => [
                    'name' => 'VD Gallery',
                    'singular_name' => 'vdgallery',
                    'add_new' => 'Tambah Galeri Baru',
                    'add_new_item' => 'Tambah Galeri Baru',
                    'edit_item' => 'Edit Galeri',
                    'view_item' => 'Lihat Galeri',
                    'search_items' => 'Cari Galeri',
                    'not_found' => 'Tidak ditemukan',
                    'not_found_in_trash' => 'Tidak ada galeri di kotak sampah'
                ],
                'menu_icon' => 'dashicons-images-alt2',
                'public' => true,
                'exclude_from_search' => true,
                'show_in_admin_bar'   => false,
                'show_in_nav_menus'   => false,
                'publicly_queryable'  => false,
                'query_var'           => false,
                'supports' => ['title'],
        ]);
        }

        /**
         * Call file media Upload.
         * shortcode
         */
        public function media_upload() {
            global $post;

            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
            if(isset($post->ID)) {
                wp_enqueue_media(array(
                    'post' => $post->ID,
                ));
            }
        }

        /**
         * Custom columns vdgallery.
         */
        public function set_custom_edit_vdgallery_columns($columns) {
            $columns['sgaleri']     = __( 'Shortcode Galeri', 'vdgallery' );
            $columns['sslideshow']  = __( 'Shortcode Slideshow', 'vdgallery' );
            return $columns;
        }
        
        public function custom_vdgallery_column( $column, $post_id ) {
            switch ( $column ) {
                case 'sgaleri' :
                    echo '[vdgallery id="'.$post_id.'"]';
                    break;
                case 'sslideshow' :
                    echo '[vdgalleryslide id="'.$post_id.'"]';
                    break;
            }
        }

    }

    // Inisialisasi class Velocity_Addons_Gallery
    $velocity_gallery = new Velocity_Addons_Gallery();
} else {
    // Opsional: Log atau informasi jika plugin VD Gallery aktif
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-info"><p>VD Gallery aktif. Velocity_Addons_Gallery tidak dijalankan.</p></div>';
    } );
}
