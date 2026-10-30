<?php
/** Langkah 3 — Bukti pengaduan */
require_once __DIR__ . '/config.php';

if (empty($_SESSION['hasil'])) {
    redirect('index.php');
}
$h = $_SESSION['hasil'];

render_header('Bukti Pengaduan', 3);
?>
<div class="form-card receipt">
    <div class="receipt-status">
        <span class="status-icon"><?= icon('check') ?></span>
        <div>
            <h2>Pengaduan Berhasil Dikirim</h2>
            <p>Terima kasih, pengaduan Anda telah kami terima dan akan segera ditindaklanjuti oleh petugas kami.</p>
        </div>
    </div>

    <div class="ticket-box">
        <div>
            <span class="summary-label">Nomor Tiket Pengaduan</span>
            <strong class="ticket-no" id="tiket"><?= e($h['no_tiket']) ?></strong>
        </div>
        <button type="button" class="btn btn-secondary btn-sm no-print" id="btn-copy"><?= icon('copy') ?> <span>Salin</span></button>
    </div>

    <table class="receipt-table">
        <tr><th>Tanggal Pengaduan</th><td><?= e($h['waktu']) ?></td></tr>
        <tr><th>Nama Pelapor</th><td><?= e($h['nama_lengkap']) ?></td></tr>
        <tr><th>NIK</th><td class="mono"><?= e($h['nik_mask']) ?></td></tr>
        <tr><th>Nomor WhatsApp</th><td class="mono"><?= e($h['wa_mask']) ?></td></tr>
        <tr><th>Pengaduan Terkait</th><td><?= e($h['kategori']) ?></td></tr>
        <?php if ($h['no_rekening'] !== ''): ?>
        <tr><th>Nomor Rekening</th><td class="mono"><?= e($h['no_rekening']) ?></td></tr>
        <?php endif; ?>
        <tr><th>Ringkasan</th><td><?= e($h['kronologi']) ?></td></tr>
        <tr><th>Status</th><td><span class="badge">Diterima</span></td></tr>
    </table>

    <div class="privacy">
        <?= icon('info') ?>
        <p>Simpan atau cetak bukti ini. Sebutkan <strong>nomor tiket</strong> saat menghubungi <?= e(BANK_SHORT) ?> untuk menanyakan perkembangan pengaduan Anda.</p>
    </div>

    <div class="form-actions no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print()"><?= icon('print') ?> Cetak Bukti</button>
        <a href="index.php" class="btn btn-primary">Buat Pengaduan Baru</a>
    </div>
</div>

<script>
document.getElementById('btn-copy').addEventListener('click', function () {
    var btn = this, t = document.getElementById('tiket').textContent;
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () {
        btn.querySelector('span').textContent = 'Tersalin';
        setTimeout(function () { btn.querySelector('span').textContent = 'Salin'; }, 1800);
    });
});
</script>
<?php render_footer(); ?>
