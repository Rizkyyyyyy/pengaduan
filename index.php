<?php
/** Langkah 1 — Data pelapor */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = clean_text($_POST['nama_lengkap'] ?? '');
    $nik   = preg_replace('/\D/', '', $_POST['nik'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_wa = normalize_wa($_POST['no_wa'] ?? '');

    $errors = [];
    if (!csrf_check()) {
        $errors['_form'] = 'Sesi formulir telah berakhir. Silakan ulangi pengisian.';
    }
    if ($nama === '') {
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';
    } elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 150) {
        $errors['nama_lengkap'] = 'Nama lengkap harus 3–150 karakter.';
    } elseif (!preg_match("/^[\p{L}\s.,'-]+$/u", $nama)) {
        $errors['nama_lengkap'] = 'Nama lengkap hanya boleh berisi huruf dan tanda baca nama (titik, koma, apostrof, tanda hubung).';
    }
    if ($nik === '') {
        $errors['nik'] = 'NIK wajib diisi.';
    } elseif (!preg_match('/^\d{16}$/', $nik)) {
        $errors['nik'] = 'NIK harus terdiri dari 16 digit angka sesuai KTP.';
    }
    if ($no_wa === '') {
        $errors['no_wa'] = 'Nomor WhatsApp wajib diisi.';
    } elseif (!valid_wa($no_wa)) {
        $errors['no_wa'] = 'Nomor WhatsApp tidak valid. Gunakan nomor seluler Indonesia, contoh: 0812 3456 7890.';
    }
    if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)) {
        $errors['email'] = 'Format alamat email tidak valid. Contoh: nama@domain.com';
    }

    if ($errors) {
        flash_set($errors, ['nama_lengkap' => $nama, 'nik' => $nik, 'no_wa' => $no_wa, 'email' => $email]);
        redirect('index.php');
    }

    $_SESSION['pelapor'] = ['nama_lengkap' => $nama, 'nik' => $nik, 'no_wa' => $no_wa, 'email' => $email];
    redirect('pengaduan.php');
}

[$errors, $old] = flash_get();
$old = $old ?: ($_SESSION['pelapor'] ?? []);

render_header('Data Pelapor', 1);
?>
<form method="post" action="index.php" class="form-card" novalidate>
    <?= csrf_field() ?>

    <div class="form-card-head">
        <div>
            <h2>Data Pelapor</h2>
            <p>Isi identitas Anda sesuai Kartu Tanda Penduduk (KTP).</p>
        </div>
        <span class="req-legend"><span class="req">*</span> Wajib diisi</span>
    </div>

    <?php render_errors($errors); ?>

    <fieldset class="section">
        <legend><span class="section-no">A</span> Identitas Pelapor</legend>

        <div class="grid">
            <div class="field col-12<?= err_class($errors, 'nama_lengkap') ?>">
                <label for="nama_lengkap">Nama Lengkap <span class="req">*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" maxlength="150" required
                       value="<?= e($old['nama_lengkap'] ?? '') ?>" placeholder="Masukkan nama lengkap sesuai KTP"
                       autocomplete="name"<?= err_aria($errors, 'nama_lengkap') ?>>
                <?= err_msg($errors, 'nama_lengkap') ?>
            </div>

            <div class="field col-6<?= err_class($errors, 'nik') ?>">
                <label for="nik">Nomor Induk Kependudukan (NIK) <span class="req">*</span></label>
                <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="19" required
                       value="<?= e(isset($old['nik']) ? format_nik($old['nik']) : '') ?>" placeholder="0000 0000 0000 0000"
                       class="mono" autocomplete="off"<?= err_aria($errors, 'nik') ?>>
                <span class="hint"><span id="nik-count">0</span>/16 digit</span>
                <?= err_msg($errors, 'nik') ?>
            </div>

            <div class="field col-6<?= err_class($errors, 'no_wa') ?>">
                <label for="no_wa">Nomor WhatsApp <span class="req">*</span></label>
                <div class="input-group">
                    <span class="input-prefix">+62</span>
                    <input type="tel" id="no_wa" name="no_wa" inputmode="numeric" maxlength="16" required
                           value="<?= e(!empty($old['no_wa']) ? (strpos($old['no_wa'], '62') === 0 ? substr($old['no_wa'], 2) : $old['no_wa']) : '') ?>"
                           placeholder="812 3456 7890" class="mono" autocomplete="tel-national"<?= err_aria($errors, 'no_wa') ?>>
                </div>
                <span class="hint">Nomor aktif untuk konfirmasi dan tindak lanjut pengaduan.</span>
                <?= err_msg($errors, 'no_wa') ?>
            </div>

            <div class="field col-12<?= err_class($errors, 'email') ?>">
                <label for="email">Alamat Email <span class="opt">Opsional</span></label>
                <input type="email" id="email" name="email" maxlength="150"
                       value="<?= e($old['email'] ?? '') ?>" placeholder="nama@domain.com"
                       autocomplete="email"<?= err_aria($errors, 'email') ?>>
                <span class="hint">Untuk menerima informasi perkembangan pengaduan.</span>
                <?= err_msg($errors, 'email') ?>
            </div>
        </div>
    </fieldset>

    <div class="privacy">
        <?= icon('lock') ?>
        <p>Data pribadi Anda dilindungi dan hanya digunakan untuk keperluan penanganan pengaduan, sesuai Undang-Undang Pelindungan Data Pribadi.</p>
    </div>

    <div class="form-actions">
        <span></span>
        <button type="submit" class="btn btn-primary">Selanjutnya <?= icon('arrow-r') ?></button>
    </div>
</form>

<script>
(function () {
    var nik = document.getElementById('nik'), cnt = document.getElementById('nik-count');
    function fmt() {
        var d = nik.value.replace(/\D/g, '').slice(0, 16);
        nik.value = d.replace(/(\d{4})(?=\d)/g, '$1 ');
        cnt.textContent = d.length;
    }
    nik.addEventListener('input', fmt); fmt();

    var wa = document.getElementById('no_wa');
    function fmtWa() {
        var d = wa.value.replace(/\D/g, '');
        if (d.indexOf('62') === 0) d = d.slice(2);
        if (d.indexOf('0') === 0) d = d.slice(1);
        d = d.slice(0, 13);
        wa.value = d.replace(/^(\d{3})(\d{1,4})?(\d{1,4})?(\d*)$/, function (m, a, b, c, r) {
            return [a, b, c, r].filter(Boolean).join(' ');
        });
    }
    wa.addEventListener('input', fmtWa); fmtWa();
})();
</script>
<?php render_footer(); ?>
