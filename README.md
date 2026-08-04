# Velocity Addons

Plugin "Velocity Addons" menyediakan toolkit pengaturan, keamanan, SEO, optimasi, dan 1 Click Setup untuk mempercepat konfigurasi WordPress.

## Installation

#### Install released version

- Download from release [GitHub](https://github.com/Velocity-Developer/velocity-addons/releases)
- Unzip the file and copy the contents to your plugins folder

## Development

- Clone from GitHub in your plugins folder and start development

```bash
  git clone https://github.com/Velocity-Developer/velocity-addons.git
```

- Install packages

```bash
  npm install
```

### Build

Untuk membuat package rilis (zip), gunakan :

```bash
  npm run build
```

## Captcha

### Shortcode

- Login form:

  ```text
  [velocity_recaptcha]
  ```

- Custom form (membidik form tertentu dengan CSS selector):

  ```text
  [velocity_captcha form=".testimoni-form"]
  ```

  Ganti `.testimoni-form` dengan selector form yang sesuai, misalnya:
  - `.form-kontak`
  - `#form-testimoni`

Jika atribut `form` tidak diisi, captcha akan otomatis mencari elemen `<form>` terdekat dari posisi shortcode.

## Fitur Utama

- **Disable Comment**  
  Nonaktifkan komentar di seluruh situs agar lebih bersih dan menghindari spam.

- **Hide Admin Notice**  
  Sembunyikan notifikasi/notices di dashboard admin yang mengganggu tampilan.

- **Disable Gutenberg & Standar Editor**  
  Aktifkan editor klasik dan nonaktifkan Gutenberg jika lebih nyaman dengan editor lama.

- **Limit Login Attempts**  
  Batasi percobaan login yang gagal untuk mencegah brute-force. Pengaturan dapat diakses di menu **Velocity Addons → Security**.

- **Block wp-login & Whitelist IP/Negara**  
  Blokir akses ke halaman `wp-login.php` dan hanya izinkan IP tertentu atau negara tertentu (misal Indonesia). Pengaturan di menu **Security**.

- **Maintenance Mode**  
  Aktifkan mode maintenance dengan tampilan halaman khusus saat situs dalam perbaikan. Pengaturan di menu **Velocity Addons → Maintenance Mode**.

- **Auto Resize Image**  
  Otomatis mengecilkan ukuran gambar yang diupload agar lebih hemat storage dan mempercepat loading. Pengaturan di menu **Velocity Addons → Auto Resize**.

- **Gallery & Slideshow**  
  Buat gallery atau slideshow gambar dengan shortcode:

  ```text
  [vdgallery id="123"]
  [vdgalleryslide id="123"]
  ```

  ID mengacu pada post gallery yang dibuat melalui metabox Velocity Gallery.

- **Breadcrumbs**  
  Tampilkan navigasi breadcrumbs di halaman menggunakan:

  ```text
  [vd-breadcrumbs]
  ```

- **Share Post**  
  Tambahkan tombol share sederhana di single post:

  ```text
  [velocity-sharepost]
  ```

  Dapat dikustomisasi melalui atribut seperti `title` dan `platforms`.
