<?php
/**
 * Konfigurasi aplikasi Pengaduan Nasabah - BPR Arto Moro
 * Sesuaikan kredensial database dengan server lokal Anda.
 */

define('DB_HOST', '34.101.244.111');
define('DB_PORT', '3306');
define('DB_NAME', 'amapps_dev');
define('DB_USER', 'amapps_dev');
define('DB_PASS', 'ArmorJ4ya');

// Nama tabel di database
define('DB_TABLE', 'chs_pengaduan');

define('APP_NAME', 'Layanan Pengaduan Nasabah');
define('BANK_NAME', 'PT. BPR Arto Moro');
define('BANK_SHORT', 'BPR Arto Moro');

// Nilai created_by untuk pengaduan yang masuk dari website publik
define('APP_USER', 'WEB_PENGADUAN');

// Logo: taruh file logo di assets/logo.png (opsional). Jika tidak ada, tampil inisial "AM".
define('LOGO_PATH', 'assets/logo.png');

// Kontak layanan (tampil di header & sidebar)
define('CONTACT_PHONE', '024 - 76606993');
define('CONTACT_WA', '+62 813-2789-0199');
define('CONTACT_EMAIL', 'pengaduan@bprartomoro.co.id');
define('CONTACT_HOURS', 'Senin–Jumat, 08.00–16.00 WIB');
define('WEBSITE_URL', 'https://www.bprartomoro.co.id');

// 'db'  = simpan langsung ke MySQL (mode lokal sekarang)
// 'api' = kirim ke webservice backend (nanti) — lihat lib/storage.php
define('STORAGE_MODE', 'db');
define('API_BASE_URL', 'https://api.contoh-domain.local/pengaduan'); // placeholder
define('API_TOKEN', '');                                             // placeholder

date_default_timezone_set('Asia/Jakarta');

// Session yang lebih aman
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/storage.php';