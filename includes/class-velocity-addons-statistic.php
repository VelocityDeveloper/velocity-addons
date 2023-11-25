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

 class Velocity_Addons_Statistic {
    public function __construct() {

        // Inisialisasi sesi jika belum diinisialisasi
        if (session_id() === '') {
            session_start();
        }

        // Panggil fungsi untuk cek dan buat tabel database jika perlu
        $this->check_and_create_table();
        add_action('wp', array($this, 'record_page_visit'));    

        // Tambahkan submenu di dasbor admin
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Tambahkan shortcode
        add_shortcode('statistik_kunjungan', array($this, 'display_statistik_kunjungan'));
    }

    private function check_and_create_table() {
        global $wpdb;

        $version_db = get_option('version_db', 1);
        $current_version = 3.2;

        // Cek apakah versi database lebih kecil dari versi sekarang
        if ($version_db < $current_version) {
            // Nama tabel
            $table_name = $wpdb->prefix . 'vd_statistic';

            // SQL untuk membuat tabel
            $sql = "CREATE TABLE $table_name (
                id INT(11) NOT NULL AUTO_INCREMENT,
                sesi VARCHAR(255) NOT NULL,
                post_id INT(11) NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB";

            // Eksekusi query untuk membuat tabel
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            // Update versi database
            update_option('version_db', $current_version);
        }
    }

    public function record_page_visit() {
        // Mendapatkan data kunjungan
        global $post;
        $sesi = session_id();
        $post_id = $post->ID;
        $timestamp = current_time('mysql');

        // Memasukkan data kunjungan ke dalam tabel database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vd_statistic';

        $wpdb->insert(
            $table_name,
            array(
                'sesi' => $sesi,
                'post_id' => $post_id,
                'timestamp' => $timestamp,
            ),
            array(
                '%s',
                '%d',
                '%s',
            )
        );
    }
    public function add_admin_menu() {
        add_menu_page('Statistik', 'Statistik', 'manage_options', 'statistik-kunjungan', array($this, 'display_admin_page'));
    }

    public function display_admin_page() {
        // Tampilkan konten halaman admin di sini
        echo '<div class="wrap">';
        echo '<h1>Statistik Kunjungan</h1>';
        
        // Tampilkan tabel statistik
        echo '<table class="widefat striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Statistik</th>';
        echo '<th>Jumlah</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Tampilkan statistik kunjungan
        $today_unique_visitors = $this->get_today_unique_visitors();
        $today_visits = $this->get_today_visits();
        $unique_visitors = $this->get_unique_visitors();
        $total_visits = $this->get_total_visits();
        $online_visitors = $this->get_online_visitors();

        echo '<tr>';
        echo '<td>Pengunjung Hari Ini</td>';
        echo '<td>' . $today_unique_visitors . ' Pengunjung</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Kunjungan Hari Ini</td>';
        echo '<td>' . $today_visits . ' Kunjungan</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Total Pengunjung</td>';
        echo '<td>' . $unique_visitors . ' Pengunjung</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Total Kunjungan</td>';
        echo '<td>' . $total_visits . ' Kunjungan</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Pengunjung Online</td>';
        echo '<td>' . $online_visitors . ' Pengunjung</td>';
        echo '</tr>';

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

    }
    public function display_statistik_kunjungan() {
        ob_start(); // Mulai buffering output

        // Tampilkan list group statistik
        echo '<ul class="list-group">';
        
        // Tampilkan statistik kunjungan
        $today_unique_visitors = $this->get_today_unique_visitors();
        $today_visits = $this->get_today_visits();
        $unique_visitors = $this->get_unique_visitors();
        $total_visits = $this->get_total_visits();
        $online_visitors = $this->get_online_visitors();

        $stats = array(
            'Pengunjung Hari Ini' => $today_unique_visitors,
            'Kunjungan Hari Ini' => $today_visits,
            'Total Pengunjung' => $unique_visitors,
            'Total Kunjungan' => $total_visits,
            'Pengunjung Online' => $online_visitors,
        );

        foreach ($stats as $label => $value) {
            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
            echo $label;
            echo '<span class="badge bg-secondary rounded-pill">' . $value . '</span>';
            echo '</li>';
        }

        echo '</ul>';

        $output = ob_get_clean(); // Ambil output dari buffer
        return $output;
    }

    private function get_today_visits() {
        global $wpdb;
    
        // Nama tabel
        $table_name = $wpdb->prefix . 'vd_statistic';
    
        // Mendapatkan tanggal hari ini
        $today_date = date('Y-m-d');
    
        // Query untuk mendapatkan jumlah kunjungan hari ini
        $today_visits = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = %s",
                $today_date
            )
        );
    
        return $today_visits;
    }

    private function get_today_unique_visitors() {
        global $wpdb;
    
        // Nama tabel
        $table_name = $wpdb->prefix . 'vd_statistic';
    
        // Mendapatkan tanggal hari ini
        $today_date = date('Y-m-d');
    
        // Query untuk mendapatkan jumlah pengunjung unik hari ini
        $today_unique_visitors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT sesi) FROM $table_name WHERE DATE(timestamp) = %s",
                $today_date
            )
        );
    
        return $today_unique_visitors;
    }

    private function get_unique_visitors() {
        global $wpdb;
    
        // Nama tabel
        $table_name = $wpdb->prefix . 'vd_statistic';
    
        // Query untuk mendapatkan jumlah pengunjung unik
        $unique_visitors = $wpdb->get_var(
            "SELECT COUNT(DISTINCT sesi) FROM $table_name"
        );
    
        return $unique_visitors;
    }

    private function get_total_visits() {
        global $wpdb;
    
        // Nama tabel
        $table_name = $wpdb->prefix . 'vd_statistic';
    
        // Query untuk mendapatkan total kunjungan
        $total_visits = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name"
        );
    
        return $total_visits;
    }

    private function get_online_visitors() {
        global $wpdb;
    
        // Nama tabel
        $table_name = $wpdb->prefix . 'vd_statistic';
    
        // Interval waktu untuk dianggap sebagai "online" (dalam menit)
        $online_interval = 5; // Anda dapat mengganti sesuai kebutuhan
    
        // Waktu saat ini
        $current_time = current_time('mysql');
    
        // Waktu beberapa menit yang lalu
        $online_threshold = date('Y-m-d H:i:s', strtotime("-$online_interval minutes", strtotime($current_time)));
    
        // Query untuk mendapatkan jumlah pengunjung online
        $online_visitors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT sesi) FROM $table_name WHERE timestamp >= %s",
                $online_threshold
            )
        );
    
        return $online_visitors;
    }

}

$statistics_handler = new Velocity_Addons_Statistic();