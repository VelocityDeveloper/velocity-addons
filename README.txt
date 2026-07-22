=== Velocity Addons ===
Contributors: velocitydeveloper
Donate link: https://velocitydeveloper.com
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 6.2
Stable tag: 2.1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Menonaktifkan komentar, menyembunyikan notifikasi, batasi login, maintenance mode, blokir akses, dan kontrol lebih pada WordPress.
== Description ==

Plugin "Velocity Addons" adalah sebuah plugin yang menyediakan berbagai fitur tambahan untuk mengatur dan meningkatkan pengalaman admin WordPress Anda. Plugin ini memberikan kontrol yang lebih besar atas beberapa aspek penting dalam pengelolaan situs WordPress Anda. Fitur-fitur yang disediakan oleh plugin ini antara lain:

== Installation ==

This section describes how to install the plugin and get it working.
1. Upload `velocity-addons.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Changelog ==

=2.1.7=
- VD Gallery: perbaiki caption di bawah gambar yang terpotong dengan spacing, line-height, dan wrapping teks yang konsisten.
- VD Gallery: tambahkan cache-busting stylesheet agar perubahan tampilan langsung dimuat oleh browser.

=2.1.3=
- Perbaiki tombol salin shortcode (`copyToClipboard`) pada halaman Shortcode di navigasi admin terpadu.
- Pastikan script aksi dimuat dengan konteks halaman yang benar pada halaman Statistik, Shortcode, dan Optimize Database.

=2.1.2=
- Perbaiki upload Gambar Share pada pengaturan SEO yang gagal membuka WordPress Media Library karena error `wp.media.frames`.
- Pastikan Media Library dimuat pada halaman SEO di navigasi admin terpadu.
- Perbaiki tombol Delete Gambar Share agar tidak membuka kembali pemilih media.
- Tambahkan cache-busting pada JavaScript admin agar perbaikan langsung dimuat setelah plugin diperbarui.

=2.0.3=
- VD Gallery: tambah Global Option untuk Galeri Option dan Slideshow Option pada submenu CPT.
- VD Gallery: refactor field option gallery/slideshow agar dikelola dari definisi array bersama untuk metabox, global option, dan shortcode.
- VD Gallery: tambah pengaturan aspect ratio, caption slideshow fleksibel, perbaikan pagination, dan perapian tampilan admin.

=2.0.0=
- Migrasi halaman pengaturan admin ke arsitektur Alpine + REST API agar penyimpanan dan interaksi berjalan tanpa reload penuh.
- Pengaturan keamanan: opsi "Disable REST API / JSON" dihapus untuk menghindari konflik dengan endpoint REST internal plugin.
- Refactor endpoint admin: endpoint aksi dipisah agar lebih modular dan mudah maintenance (settings, statistik, optimize DB).
- Halaman Statistik Pengunjung dan Optimize Database kini mendukung aksi tanpa reload (reset/run via REST).
- Halaman Duitku: tab "Pengaturan / Riwayat Invoice / Riwayat Callback" kini client-side (Alpine) tanpa reload.
- Halaman Captcha: provider switch kini realtime (google/image) untuk menampilkan field terkait tanpa refresh.
- Auto Resize Image: tambah opsi kualitas gambar (quality) dan format output (original/jpeg/webp/avif) dengan fallback otomatis jika format tidak didukung server.
- Fix Floating WhatsApp: nomor kontak kini bisa dikosongkan; sinkronisasi `nomor_whatsapp_contacts` dan opsi legacy `nomor_whatsapp` diperbaiki agar nilai lama tidak muncul lagi setelah simpan/refresh.
- Breaking changes:
- Opsi legacy "Disable REST API / JSON" tidak lagi tersedia.
- Alur settings admin kini mengandalkan stack REST + JS modern untuk pengalaman tanpa reload.

=1.8.3=
- SEO: og:image untuk halaman home/front dan archive pakai share image; jika kosong fallback ke gambar dari konten/featured image.

=1.8.2=
- Default fitur Optimize Database kini nonaktif sampai diaktifkan manual.
- Perbaikan kecil lainnya.

=1.8.1=
- Perbaikan fatal error statistik saat tabel belum tersedia (guard table_exists).

=1.8.0=
- Floating WhatsApp: dukung multi-kontak dengan opsi baru `nomor_whatsapp_contacts`, sinkronisasi nomor legacy, dan toggle UI baru (ikon X).
- Perapian UI/UX tombol floating (tidak bertabrakan dengan scroll-to-top, dropdown lebih rapi).
- Perubahan kecil lain untuk menjaga kompatibilitas dan kestabilan.

=1.7.14=
- Link sitemap ke sitemap.xml
- Captcha gambar
- Clean UI dashboard

=1.6.8=
- Perbaiki hitungan total (all time) agar tidak double count bulan berjalan: data bulan aktif hanya diambil dari daily stats, bulan lampau dari monthly stats.

=1.6.7=
- Tambah fitur Optimize Database: hapus revisions, auto-draft, trash, orphan meta/term, komentar spam/trash/pending lama, transients kedaluwarsa, cache oEmbed. Tampilkan jumlah dan estimasi ukuran per item.
- Opsi eksekusi: Hapus Terpilih atau Hapus Semua, dengan notifikasi hasil.

=1.6.6=
- Jalankan ulang routine aktivasi/statistik otomatis setelah update plugin tanpa perlu re-aktivasi manual.
- Simpan versi skema `velocity_addons_db_version` agar setup dijalankan sekali per rilis dan konsisten antar site.

=1.6.4=
- Import statistik legacy otomatis (additive) dengan baseline total kunjungan/pengunjung agar konsisten antar versi.
- Tambah tombol Reset Statistik pada halaman admin untuk mengosongkan semua data statistik & meta hit secara aman.
- Perapian UI statistik + perbaikan minor dokumentasi dan logika statistik.

=1.6.3=
- Update dokumentasi statistik: tambah keterangan dan contoh penggunaan parameter pengunjung online (with_online, label_online) di halaman admin dan dashboard.
- Peningkatan UI dokumentasi shortcode statistik.
- Minor copy update pada pengaturan Statistik.

=1.6.2=
- Penambahan fitur snippet
- Penambahan statistik pengunjung 
- Penambahan background image pada maintenance mode
- Perbaikan bug

=1.3.1=
- Fix bug Setting SEO Single title

=1.3.1=
- Tambah Fitur Autoupdate Versi Beta

=1.3.0=
- Tambah Fitur SEO di post/page
- Tambah shortcode post/page
- Hide badge count update

=1.2.15=
- Hapus Statistik

=1.2.14=
- Perbaikan Bug Recaptcha

=1.2.13=
- Perbaikan Floating Whatsapp

=1.2.12=
- Perbaikan Floating Whatsapp & Scroll Top

= 1.2.11 =
- Perbaikan bug list sub menu jika fitur non-aktif
- Tambah fitur scroll top & perbaikan Floating Whatsapp


= 1.2.0 =
- Perbaikan bug di versi sebelumnya
- Tambah Fitur License
- Tambah Fitur Floating Whatsapp 
- Perapian dan Pengelompokan Menu
- Tambah Dashboard Rangkuman Statistik, QC Check, dan Jumlah Page, Post, Media yang sudah diupload.

= 1.1.51 =
- Tambah fitur import artikel dari API Velocity
- Tambah fitur lisence (tahap pembuatan)
- Tambah fitur QC Checker saat Maintenance Mode

= 1.1.5 =
- Perbaikan bug recaptcha 'lost_password' & tampilan shortcode

= 1.1.4 =
- Perbaikan bug dan perapian tampilan

= 1.0.5 =
- Perbaikan bug block wp-admin

= 1.0.4 =
- Perbaikan bug Permalink
- Exclude halaman myaccount dari maintenance mode

= 1.0.3 =
add Classic Widget : Kembalikan pengelolaan widget ke tampilan klasik
Add Standar Editor TinyMCE
Add Remove Slug Category

= 1.0.2 =
Auto Update Plugin

= 1.0.1 =
Add Fully Disable Comment: Menonaktifkan fitur komentar di situs WordPress.
Add Hide Admin Notice: Menyembunyikan notifikasi admin di dashboard WordPress.
Add Limit Login Attempts: Membatasi jumlah percobaan login yang diperbolehkan.
Add Maintenance Mode: Menampilkan mode perawatan saat melakukan perbaikan atau pembaruan.
Add Disable XML-RPC: Memblokir akses XML-RPC pada situs WordPress.
Add Disable Rest API: Mematikan akses Rest API pada situs WordPress.
Add Disable Gutenberg: Mematikan editor Gutenberg dan menggunakan Classic Editor.
Add Whitelist Country: Membatasi akses ke halaman admin WordPress berdasarkan negara yang terdaftar.
Add Block WP Login: Memblokir akses ke halaman wp-login.php berdasarkan negara yang terdaftar.

= 1.0.0 =
Framework Kosong

