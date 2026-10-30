<?php
/** Proses simpan pengaduan */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['pelapor']['no_wa'])) {
    redirect('index.php');
}

$pelapor  = $_SESSION['pelapor'];
$kategori = $_POST['kategori'] ?? '';
$rekening = preg_replace('/[^\d.\-]/', '', $_POST['no_rekening'] ?? '');
$singkat  = clean_text($_POST['kronologi'] ?? '');
$lengkap  = trim(str_replace("\r\n", "\n", $_POST['kronologi_lengkap'] ?? ''));
$setuju   = !empty($_POST['persetujuan']);

$errors = [];
if (!csrf_check()) {
    $errors['_form'] = 'Sesi formulir telah berakhir. Silakan kirim ulang pengaduan Anda.';
}
if (!array_key_exists($kategori, kategori_list())) {
    $errors['kategori'] = 'Pilih salah satu jenis pengaduan.';
}
if ($rekening !== '' && (strlen($rekening) < 5 || strlen($rekening) > 30)) {
    $errors['no_rekening'] = 'Nomor rekening tidak valid (5–30 karakter).';
}
if ($singkat === '') {
    $errors['kronologi'] = 'Ringkasan pengaduan wajib diisi.';
} elseif (mb_strlen($singkat) > 255) {
    $errors['kronologi'] = 'Ringkasan pengaduan maksimal 255 karakter.';
}
if ($lengkap === '') {
    $errors['kronologi_lengkap'] = 'Kronologi lengkap wajib diisi.';
} elseif (mb_strlen($lengkap) < 20) {
    $errors['kronologi_lengkap'] = 'Kronologi lengkap minimal 20 karakter agar dapat kami pahami.';
} elseif (mb_strlen($lengkap) > 5000) {
    $errors['kronologi_lengkap'] = 'Kronologi lengkap maksimal 5.000 karakter.';
}
if (!$setuju) {
    $errors['persetujuan'] = 'Anda perlu menyetujui pernyataan ini untuk melanjutkan.';
}

$old = [
    'kategori' => $kategori, 'no_rekening' => $rekening,
    'kronologi' => $singkat, 'kronologi_lengkap' => $lengkap, 'persetujuan' => $setuju,
];

if ($errors) {
    flash_set($errors, $old);
    redirect('pengaduan.php');
}

try {
    $tiket = simpan_pengaduan([
        'nama_lengkap'      => $pelapor['nama_lengkap'],
        'nik'               => $pelapor['nik'],
        'no_wa'             => $pelapor['no_wa'],
        'email'             => $pelapor['email'] ?? '',
        'kategori'          => $kategori,
        'no_rekening'       => $rekening,
        'kronologi'         => $singkat,
        'kronologi_lengkap' => $lengkap,
        'ip_address'        => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
} catch (Throwable $ex) {
    error_log('[pengaduan] ' . $ex->getMessage());
    flash_set(['_form' => 'Mohon maaf, sistem sedang mengalami gangguan. Silakan coba beberapa saat lagi.'], $old);
    redirect('pengaduan.php');
}

// Data untuk halaman bukti, lalu bersihkan data form (cegah submit ganda)
$_SESSION['hasil'] = [
    'no_tiket'     => $tiket,
    'nama_lengkap' => $pelapor['nama_lengkap'],
    'nik_mask'     => mask_nik($pelapor['nik']),
    'wa_mask'      => mask_wa($pelapor['no_wa']),
    'kategori'     => kategori_label($kategori),
    'no_rekening'  => $rekening,
    'kronologi'    => $singkat,
    'waktu'        => tanggal_indo(),
];
unset($_SESSION['pelapor'], $_SESSION['csrf_token']);

redirect('sukses.php');
