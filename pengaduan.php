<?php
/** Langkah 2 — Detail pengaduan */
require_once __DIR__ . '/config.php';

if (empty($_SESSION['pelapor']['no_wa'])) {
    redirect('index.php');
}
$pelapor = $_SESSION['pelapor'];

[$errors, $old] = flash_get();

render_header('Detail Pengaduan', 2);
?>
<form method="post" action="submit.php" class="form-card" novalidate>
    <?= csrf_field() ?>

    <div class="form-card-head">
        <div>
            <h2>Detail Pengaduan</h2>
            <p>Lengkapi informasi produk dan uraian kejadian yang Anda adukan.</p>
        </div>
        <span class="req-legend"><span class="req">*</span> Wajib diisi</span>
    </div>

    <?php render_errors($errors); ?>

    <!-- A. Data pelapor (ringkasan dari langkah 1) -->
    <fieldset class="section">
        <legend><span class="section-no">A</span> Data Pelapor</legend>
        <div class="summary-box">
            <div class="summary-item">
                <span class="summary-label">Nama Lengkap</span>
                <span class="summary-value"><?= e($pelapor['nama_lengkap']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">NIK</span>
                <span class="summary-value mono"><?= e(format_nik($pelapor['nik'])) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Nomor WhatsApp</span>
                <span class="summary-value mono"><?= e(format_wa($pelapor['no_wa'] ?? '')) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Email</span>
                <span class="summary-value"><?= e($pelapor['email'] !== '' ? $pelapor['email'] : '—') ?></span>
            </div>
            <a href="index.php" class="summary-edit"><?= icon('edit') ?> Ubah</a>
        </div>
    </fieldset>

    <!-- B. Informasi produk -->
    <fieldset class="section">
        <legend><span class="section-no">B</span> Informasi Produk</legend>

        <div class="field field-kategori<?= err_class($errors, 'kategori') ?>" role="radiogroup" aria-labelledby="lbl-kategori">
            <label id="lbl-kategori">Pengaduan Terkait <span class="req">*</span></label>
            <div class="option-grid">
                <?php foreach (kategori_list() as $key => [$label, $desc, $ico]): ?>
                <label class="option">
                    <input type="radio" name="kategori" value="<?= e($key) ?>" required
                        <?= ($old['kategori'] ?? '') === $key ? 'checked' : '' ?>>
                    <span class="option-body">
                        <span class="option-icon"><?= icon($ico) ?></span>
                        <span class="option-title"><?= e($label) ?></span>
                        <span class="option-desc"><?= e($desc) ?></span>
                        <span class="option-check"><?= icon('check') ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <?= err_msg($errors, 'kategori') ?>
        </div>

        <div class="grid">
            <div class="field col-6<?= err_class($errors, 'no_rekening') ?>">
                <label for="no_rekening">Nomor Rekening <?= e(BANK_SHORT) ?> <span class="opt">Opsional</span></label>
                <input type="text" id="no_rekening" name="no_rekening" inputmode="numeric" maxlength="30"
                       value="<?= e($old['no_rekening'] ?? '') ?>" placeholder="Contoh: 0012345678"
                       class="mono" autocomplete="off"<?= err_aria($errors, 'no_rekening') ?>>
                <span class="hint">Nomor rekening tabungan, deposito, atau pinjaman terkait.</span>
                <?= err_msg($errors, 'no_rekening') ?>
            </div>
        </div>
    </fieldset>

    <!-- C. Uraian pengaduan -->
    <fieldset class="section">
        <legend><span class="section-no">C</span> Uraian Pengaduan</legend>

        <div class="grid">
            <div class="field col-12<?= err_class($errors, 'kronologi') ?>">
                <label for="kronologi">Ringkasan Pengaduan <span class="req">*</span></label>
                <input type="text" id="kronologi" name="kronologi" maxlength="255" required
                       value="<?= e($old['kronologi'] ?? '') ?>" placeholder="Contoh: Saldo tabungan berkurang tanpa ada transaksi"
                       <?= err_aria($errors, 'kronologi') ?>>
                <span class="hint">Pokok permasalahan dalam satu kalimat (maks. 255 karakter).</span>
                <?= err_msg($errors, 'kronologi') ?>
            </div>

            <div class="field col-12<?= err_class($errors, 'kronologi_lengkap') ?>">
                <label for="kronologi_lengkap">Kronologi Lengkap <span class="req">*</span></label>
                <textarea id="kronologi_lengkap" name="kronologi_lengkap" rows="8" maxlength="5000" required
                          placeholder="Uraikan secara berurutan:&#10;1. Tanggal dan waktu kejadian&#10;2. Lokasi / kantor / channel transaksi&#10;3. Nominal yang terkait (jika ada)&#10;4. Kronologi kejadian&#10;5. Harapan penyelesaian"
                          <?= err_aria($errors, 'kronologi_lengkap') ?>><?= e($old['kronologi_lengkap'] ?? '') ?></textarea>
                <div class="hint-row">
                    <span class="hint">Minimal 20 karakter.</span>
                    <span class="hint"><span id="counter">0</span> / 5.000</span>
                </div>
                <?= err_msg($errors, 'kronologi_lengkap') ?>
            </div>
        </div>
    </fieldset>

    <!-- D. Pernyataan -->
    <fieldset class="section">
        <legend><span class="section-no">D</span> Pernyataan</legend>
        <label class="consent<?= err_class($errors, 'persetujuan') ?>">
            <input type="checkbox" name="persetujuan" value="1" required <?= !empty($old['persetujuan']) ? 'checked' : '' ?>>
            <span>Saya menyatakan bahwa data dan informasi yang saya sampaikan adalah benar, serta menyetujui
                <?= e(BANK_NAME) ?> menggunakan data tersebut untuk keperluan penanganan pengaduan dan menghubungi saya bila diperlukan.</span>
        </label>
        <?= err_msg($errors, 'persetujuan') ?>
    </fieldset>

    <div class="form-actions">
        <a href="index.php" class="btn btn-secondary"><?= icon('arrow-l') ?> Kembali</a>
        <button type="submit" class="btn btn-primary" id="btn-submit">Kirim Pengaduan <?= icon('arrow-r') ?></button>
    </div>
</form>

<script>
(function () {
    var ta = document.getElementById('kronologi_lengkap'), c = document.getElementById('counter');
    function upd() { c.textContent = ta.value.length.toLocaleString('id-ID'); }
    ta.addEventListener('input', upd); upd();
    document.getElementById('no_rekening').addEventListener('input', function () {
        this.value = this.value.replace(/[^\d.\-]/g, '');
    });
    document.querySelector('form').addEventListener('submit', function () {
        var b = document.getElementById('btn-submit');
        setTimeout(function () { b.disabled = true; b.innerHTML = '<span class="spinner"></span> Mengirim...'; }, 0);
    });
})();
</script>
<?php render_footer(); ?>
