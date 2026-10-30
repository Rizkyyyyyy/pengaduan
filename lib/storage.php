<?php
/**
 * Lapisan penyimpanan pengaduan.
 *
 * Halaman form hanya memanggil simpan_pengaduan($data) dan menerima nomor tiket.
 * Saat ini datanya disimpan langsung ke MySQL (STORAGE_MODE = 'db').
 * Nanti cukup ubah STORAGE_MODE menjadi 'api' dan lengkapi simpan_via_api()
 * agar data dikirim ke webservice backend — halaman form tidak perlu diubah.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
    }
    return $pdo;
}

/**
 * @param array $data keys: nama_lengkap, nik, no_wa, email, kategori, no_rekening,
 *                      kronologi, kronologi_lengkap, ip_address, user_agent
 * @return string nomor tiket
 */
function simpan_pengaduan(array $data): string
{
    return STORAGE_MODE === 'api' ? simpan_via_api($data) : simpan_via_db($data);
}

function simpan_via_db(array $data): string
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Disesuaikan ke nama tabel baru: chs_pengaduan
        $stmt = $pdo->prepare(
            'INSERT INTO chs_pengaduan
                (nama_lengkap, nik, no_wa, email, kategori, no_rekening, kronologi, kronologi_lengkap, ip_address, user_agent, created_by)
             VALUES
                (:nama_lengkap, :nik, :no_wa, :email, :kategori, :no_rekening, :kronologi, :kronologi_lengkap, :ip_address, :user_agent, :created_by)'
        );
        $stmt->execute([
            ':nama_lengkap'      => $data['nama_lengkap'],
            ':nik'               => $data['nik'],
            ':no_wa'             => $data['no_wa'],
            ':email'             => $data['email'] !== '' ? $data['email'] : null,
            ':kategori'          => $data['kategori'],
            ':no_rekening'       => $data['no_rekening'] !== '' ? $data['no_rekening'] : null,
            ':kronologi'         => $data['kronologi'],
            ':kronologi_lengkap' => $data['kronologi_lengkap'],
            ':ip_address'        => $data['ip_address'] ?? null,
            ':user_agent'        => isset($data['user_agent']) ? substr($data['user_agent'], 0, 255) : null,
            ':created_by'        => APP_USER,
        ]);

        // Nomor urut diambil dari AUTO_INCREMENT -> aman walau banyak yang submit bersamaan
        $id   = (int) $pdo->lastInsertId();
        $tiket = format_tiket($id);

        // Pengisian no_tiket bagian dari proses buat data, bukan perubahan petugas,
        // jadi date_modified / modified_by tetap NULL
        $upd = $pdo->prepare('UPDATE chs_pengaduan SET no_tiket = :tiket WHERE id = :id');
        $upd->execute([':tiket' => $tiket, ':id' => $id]);

        $pdo->commit();
        return $tiket;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/**
 * Kerangka untuk integrasi webservice nanti.
 * Asumsi: backend menerima JSON via POST dan membalas {"no_tiket": "2810-0001"}.
 * Sesuaikan endpoint, autentikasi, dan format respons dengan API yang sebenarnya.
 */
function simpan_via_api(array $data): string
{
    $ch = curl_init(API_BASE_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . API_TOKEN,
        ],
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Gagal mengirim ke webservice: HTTP ' . $status . ' ' . $err);
    }
    $json = json_decode($body, true);
    if (empty($json['no_tiket'])) {
        throw new RuntimeException('Respons webservice tidak berisi no_tiket.');
    }
    return (string) $json['no_tiket'];
}