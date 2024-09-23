<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT idUser, idPegawai FROM user WHERE idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//MENGECEK APAKAH USER INI BERHAK MENGAKSES MENU INI
$sqlCekMenu = $db->prepare(
    'SELECT 
		* 
	from 
		user_detail 
		INNER JOIN menu_sub ON menu_sub.idSubMenu = user_detail.idSubMenu
	WHERE
		user_detail.idUser = ?
		AND menu_sub.namaFolder = ?
	'
);
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__)
]);
$dataCekMenu = $sqlCekMenu->fetch();

$tokenValid = hash_equals($tokenCSRF, $_POST['tokenCSRFForm'] ?? '');
//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !validateIP($_SESSION['IP_ADDR']) || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Proses Authentikasi Gagal, Data Tidak Valid');
} else {

    $BASE_DIR = ABS_PATH_FILE_UPLOAD_DIR . '/cust-signature';

    $kodeAntrian = $_POST['kodeAntrian'];
    $jenisTTD = $_POST['jenisTTD'];

    $fileTTD = $_POST['fileTTD'];

    try {
        if (in_array($jenisTTD, ['Dokter', 'Pasien'], true)) {

            $column = 'fileTTD' . $jenisTTD;

            $sql = $db->prepare(
                'SELECT 
                    pasien_antrian.*,
                    pasien_invoice_klinik.*,
                    pasien.namaPasien,
                    dokter.namaPegawai as namaDokter
                FROM 
                    pasien_antrian
                    INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
                    INNER JOIN pegawai dokter ON pasien_antrian.idDokter = dokter.idPegawai 
                    LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
                    INNER JOIN pasien_invoice_klinik ON pasien_antrian.kodeAntrian = pasien_invoice_klinik.kodeAntrian 
                WHERE 
                    pasien_antrian.kodeAntrian = ?
                '
            );
            $sql->execute([
                $kodeAntrian
            ]);

            $dataPasien = $sql->fetch();

            if (!is_null($dataPasien[$column])) {
                if (file_exists("$BASE_DIR/{$dataPasien[$column]}")) {
                    $status = unlink("$BASE_DIR/{$dataPasien[$column]}");
                } else {
                    $status = true;
                }
            } else {
                $status = true;
            }

            if ($status) {

                [$mimeType, $encodedData] = explode(',', $fileTTD);
                $decodedData = base64_decode($encodedData);

                if ($jenisTTD === 'Dokter') {
                    $filename =  escapeFilename("{$dataPasien['kodeInvoice']}@{$jenisTTD}_{$dataPasien['namaDokter']}.png");
                } else {
                    $filename =  escapeFilename("{$dataPasien['kodeInvoice']}@{$jenisTTD}_{$dataPasien['namaPasien']}.png");
                }

                $filePath =  "$BASE_DIR/$filename";

                $result = file_put_contents($filePath, $decodedData);

                if ($result) {
                    $sql = $db->prepare('UPDATE pasien_invoice_klinik SET ' . $column . ' = ? WHERE kodeInvoice = ?');
                    $status = $sql->execute([$filename, $dataPasien['kodeInvoice']]);

                    if ($status) {
                        $pesan = 'Proses Tanda Tangan Berhasil';
                    } else {
                        $pesan = 'Proses Tanda Tangan Gagal';
                    }
                } else {
                    $pesan = 'Proses Simpan File Gagal';
                }
            } else {
                $pesan = 'Proses File Gagal';
            }
        } else {
            $pesan = 'Tipe TTD Tidak Valid';
        }
    } catch (Exception $e) {
        $status = false;
        $pesan = 'Maaf, terjadi kesalahan pada proses data!';
    } finally {
        $data = compact('status', 'pesan');
    }
}

echo json_encode($data);
