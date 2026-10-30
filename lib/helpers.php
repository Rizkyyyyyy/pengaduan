<?php
/** Fungsi bantu umum & template tampilan */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* --------------------------------------------------------------- Flash */

/** $errors berbentuk ['nama_field' => 'pesan', '_form' => 'pesan umum'] */
function flash_set(array $errors, array $old = []): void
{
    $_SESSION['flash_errors'] = $errors;
    $_SESSION['flash_old']    = $old;
}

function flash_get(): array
{
    $errors = $_SESSION['flash_errors'] ?? [];
    $old    = $_SESSION['flash_old'] ?? [];
    unset($_SESSION['flash_errors'], $_SESSION['flash_old']);
    return [$errors, $old];
}

/** Class CSS untuk field yang error */
function err_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' has-error' : '';
}

/** Pesan error di bawah field */
function err_msg(array $errors, string $key): string
{
    if (!isset($errors[$key])) return '';
    return '<p class="field-error" id="err-' . e($key) . '">' . icon('alert') . e($errors[$key]) . '</p>';
}

/** Atribut aria untuk field yang error */
function err_aria(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

/* -------------------------------------------------------------- Data */

function clean_text(?string $value): string
{
    return trim(preg_replace('/\s+/u', ' ', (string) $value));
}

/** Daftar kategori pengaduan: key => [label, deskripsi, ikon] */
function kategori_list(): array
{
    return [
        'tabungan' => ['Tabungan', 'Saldo, mutasi, setoran, penarikan, buku tabungan', 'wallet'],
        'deposito' => ['Deposito', 'Pencairan, bunga, perpanjangan (ARO), bilyet', 'safe'],
        'kredit'   => ['Kredit', 'Angsuran, pelunasan, agunan, denda, restrukturisasi', 'card'],
        'umum'     => ['Umum', 'Pelayanan, petugas, fasilitas kantor, dan lainnya', 'chat'],
    ];
}

function kategori_label(string $key): string
{
    return kategori_list()[$key][0] ?? $key;
}

/** Format nomor tiket: DDMM-NNNN, contoh 2810-0001 */
function format_tiket(int $id, ?DateTimeInterface $tanggal = null): string
{
    $tanggal = $tanggal ?? new DateTime();
    return $tanggal->format('dm') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

/**
 * Normalisasi nomor WhatsApp ke format internasional tanpa "+": 6281234567890
 * Menerima input: 081234567890 / +62 812-3456-7890 / 6281234567890 / 81234567890
 */
function normalize_wa(?string $raw): string
{
    $d = preg_replace('/\D/', '', (string) $raw);
    if (strpos($d, '62') === 0)      return $d;
    if (strpos($d, '0') === 0)       return '62' . substr($d, 1);
    if (strpos($d, '8') === 0)       return '62' . $d;
    return $d;
}

/** Nomor WA Indonesia valid: 628 + 8–11 digit (setara 0812xxxxxxx, 10–13 digit) */
function valid_wa(string $wa): bool
{
    return (bool) preg_match('/^628\d{8,11}$/', $wa);
}

/** Tampilan nomor WA: +62 812-3456-7890 */
function format_wa(string $wa): string
{
    if (strpos($wa, '62') !== 0) return $wa;
    $n = substr($wa, 2);
    return '+62 ' . substr($n, 0, 3) . '-' . implode('-', str_split(substr($n, 3), 4));
}

/** Nomor WA disamarkan: +62 812-••••-7890 */
function mask_wa(string $wa): string
{
    if (strpos($wa, '62') !== 0 || strlen($wa) < 10) return $wa;
    $n = substr($wa, 2);
    return '+62 ' . substr($n, 0, 3) . '-' . str_repeat('•', strlen($n) - 7) . '-' . substr($n, -4);
}

/** NIK disamarkan untuk tampilan: 3322********7890 */
function mask_nik(string $nik): string
{
    return strlen($nik) === 16 ? substr($nik, 0, 4) . str_repeat('•', 8) . substr($nik, -4) : $nik;
}

/** NIK dikelompokkan agar mudah dibaca: 3322 0112 3456 7890 */
function format_nik(string $nik): string
{
    return trim(chunk_split($nik, 4, ' '));
}

function tanggal_indo(?DateTimeInterface $d = null): string
{
    $d = $d ?? new DateTime();
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
              'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y') . ', ' . $d->format('H.i') . ' WIB';
}

/* --------------------------------------------------------------- Icons */

function icon(string $name): string
{
    $p = [
        'phone'  => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'chat'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'lock'   => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'info'   => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'alert'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        'check'  => '<path d="M20 6 9 17l-5-5"/>',
        'wallet' => '<path d="M20 12V8H6a2 2 0 0 1 0-4h12v4"/><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'safe'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="12" r="4"/><path d="M12 8v1M12 15v1M8 12h1M15 12h1"/>',
        'card'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'doc'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'print'  => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'copy'   => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'arrow-r'=> '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'arrow-l'=> '<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'edit'   => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'user'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/* ------------------------------------------------------------ Layout */

function render_header(string $title, int $step = 0, string $subtitle = ''): void
{
    $logo = file_exists(__DIR__ . '/../' . LOGO_PATH)
        ? '<img src="' . e(LOGO_PATH) . '" alt="' . e(BANK_NAME) . '" class="logo-img">'
        : '<span class="logo-mark">AM</span>';
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> | <?= e(APP_NAME) ?> — <?= e(BANK_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="utility">
    <div class="wrap utility-inner">
        <span><?= icon('clock') ?> Layanan Pengaduan: <?= e(CONTACT_HOURS) ?></span>
        <span class="utility-right">
            <a href="tel:<?= e(preg_replace('/[^\d+]/', '', CONTACT_PHONE)) ?>"><?= icon('phone') ?> <?= e(CONTACT_PHONE) ?></a>
            <a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= icon('mail') ?> <?= e(CONTACT_EMAIL) ?></a>
        </span>
    </div>
</div>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?= e(WEBSITE_URL) ?>">
            <?= $logo ?>
            <span class="brand-text">
                <strong><?= e(BANK_NAME) ?></strong>
                <small>Bank Perekonomian Rakyat</small>
            </span>
        </a>
        <span class="secure-badge"><?= icon('lock') ?> Koneksi Aman</span>
    </div>
</header>

<section class="page-hero">
    <div class="wrap">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(WEBSITE_URL) ?>">Beranda</a><span>/</span>
            <span>Layanan Nasabah</span><span>/</span>
            <span aria-current="page">Pengaduan</span>
        </nav>
        <h1><?= e(APP_NAME) ?></h1>
        <p><?= e($subtitle ?: 'Sampaikan keluhan atau masukan Anda terkait produk dan layanan ' . BANK_SHORT . '. Setiap pengaduan dicatat dengan nomor tiket dan ditangani secara rahasia.') ?></p>
    </div>
</section>

<main class="wrap page-body">
    <?php if ($step > 0) render_stepper($step); ?>
    <div class="layout">
        <div class="content">
    <?php
}

function render_stepper(int $step): void
{
    $steps = [
        1 => ['Data Pelapor', 'Identitas diri'],
        2 => ['Detail Pengaduan', 'Produk & kronologi'],
        3 => ['Bukti Pengaduan', 'Nomor tiket'],
    ];
    echo '<ol class="stepper" aria-label="Tahapan pengaduan">';
    foreach ($steps as $n => [$label, $sub]) {
        $state = $n < $step ? 'done' : ($n === $step ? 'current' : '');
        echo '<li class="' . $state . '"' . ($n === $step ? ' aria-current="step"' : '') . '>';
        echo '<span class="step-dot">' . ($n < $step ? icon('check') : $n) . '</span>';
        echo '<span class="step-text"><strong>' . e($label) . '</strong><small>' . e($sub) . '</small></span>';
        echo '</li>';
    }
    echo '</ol>';
}

function render_sidebar(): void
{
    ?>
        <aside class="sidebar">
            <div class="side-card">
                <h3><?= icon('info') ?> Informasi Penting</h3>
                <ul class="side-list">
                    <li>Siapkan <strong>KTP</strong> untuk pengisian NIK.</li>
                    <li>Pengaduan diajukan oleh nasabah sendiri atau perwakilan nasabah.</li>
                    <li>Jelaskan kronologi selengkap mungkin: tanggal, lokasi, nominal, dan pihak yang terlibat.</li>
                    <li>Simpan <strong>nomor tiket</strong> untuk menanyakan perkembangan pengaduan.</li>
                </ul>
            </div>
            <div class="side-card">
                <h3><?= icon('clock') ?> Waktu Penanganan</h3>
                <p>Pengaduan ditindaklanjuti sesuai ketentuan Otoritas Jasa Keuangan (OJK) mengenai perlindungan konsumen sektor jasa keuangan.</p>
            </div>
            <div class="side-card side-contact">
                <h3><?= icon('phone') ?> Butuh Bantuan?</h3>
                <a href="tel:<?= e(preg_replace('/[^\d+]/', '', CONTACT_PHONE)) ?>"><?= icon('phone') ?><span><small>Telepon</small><?= e(CONTACT_PHONE) ?></span></a>
                <a href="https://wa.me/<?= e(preg_replace('/\D/', '', '62' . ltrim(preg_replace('/\D/', '', CONTACT_WA), '0'))) ?>" target="_blank" rel="noopener"><?= icon('chat') ?><span><small>WhatsApp</small><?= e(CONTACT_WA) ?></span></a>
                <a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= icon('mail') ?><span><small>Email</small><?= e(CONTACT_EMAIL) ?></span></a>
                <p class="muted"><?= e(CONTACT_HOURS) ?></p>
            </div>
            <div class="side-note">
                <?= icon('shield') ?>
                <span>Waspada penipuan. Petugas <?= e(BANK_SHORT) ?> <strong>tidak pernah</strong> meminta PIN, password, atau kode OTP Anda.</span>
            </div>
        </aside>
    <?php
}

function render_footer(bool $sidebar = true): void
{
    ?>
        </div>
        <?php if ($sidebar) render_sidebar(); ?>
    </div>
</main>
<script>
// Hilangkan tanda error saat field diperbaiki
document.addEventListener('input', clearErr); document.addEventListener('change', clearErr);
function clearErr(ev) {
    var f = ev.target.closest('.has-error'); if (!f) return;
    f.classList.remove('has-error'); ev.target.removeAttribute('aria-invalid');
    var m = f.querySelector('.field-error') || (f.nextElementSibling && f.nextElementSibling.classList.contains('field-error') ? f.nextElementSibling : null);
    if (m) m.remove();
}
</script>
<footer class="site-footer">
    <div class="wrap footer-inner">
        <div>
            <strong><?= e(BANK_NAME) ?></strong>
            <p><?= e(BANK_SHORT) ?> berizin dan diawasi oleh Otoritas Jasa Keuangan (OJK) serta merupakan peserta penjaminan Lembaga Penjamin Simpanan (LPS).</p>
        </div>
        <p class="copy">&copy; <?= date('Y') ?> <?= e(BANK_NAME) ?>. Hak cipta dilindungi.</p>
    </div>
</footer>
</body>
</html>
    <?php
}

/** Ringkasan error di atas form */
function render_errors(array $errors): void
{
    if (!$errors) return;
    $general = $errors['_form'] ?? null;
    $fields  = array_diff_key($errors, ['_form' => 1]);
    echo '<div class="alert alert-error" role="alert">' . icon('alert') . '<div>';
    if ($general) {
        echo '<strong>' . e($general) . '</strong>';
    }
    if ($fields) {
        echo '<strong>Terdapat ' . count($fields) . ' isian yang perlu diperbaiki.</strong>';
        echo '<span>Silakan periksa kembali kolom yang ditandai merah.</span>';
    }
    echo '</div></div>';
}
