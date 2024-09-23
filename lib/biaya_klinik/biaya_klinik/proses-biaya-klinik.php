<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsifile.php";

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
    $folder = 'biaya';

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {

        if ($flag === 'delete') {
            $dataKode = statementWrapper(
                DML_SELECT,
                "SELECT
                    kodeBiaya,
                    kodeFile
                FROM
                    biaya_klinik INNER JOIN uploaded_file ON biaya_klinik.kodeBiaya = uploaded_file.noForm
                WHERE
                    idBiayaKlinik = ?
                ",
                [$idBiayaKlinik]
            );
            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM biaya_klinik WHERE idBiayaKlinik = ?',
                [$idBiayaKlinik]
            );
            $statusDetail = statementWrapper(
                DML_DELETE,
                'DELETE FROM biaya_klinik_detail WHERE kodeBiaya = ?',
                [$dataKode['kodeBiaya']]
            );

            $kodeFile = $dataKode['kodeFile'];

            extract(
                delete_file(
                    // RESOURCE
                    compact(
                        'kodeFile',
                        'folder'
                    )
                )
            );

            if ($status) {
                $pesan = 'Proses Berhasil';
            } else {
                $pesan = 'Proses Gagal';
            }
            $more = [];
        }elseif ($flag === 'deleteDetail') {
            
            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM biaya_klinik_detail WHERE idBiayaKlinikDetail = ?',
                [$idBiayaKlinikDetail]
            );

            if ($status) {
                $pesan = 'Proses Berhasil';
            } else {
                $pesan = 'Proses Gagal';
            }
            $more = [];
        } else if ($flag === 'tambah') {

            $status = statementWrapper(
                DML_INSERT,
                'INSERT INTO 
                        biaya_klinik 
                    SET 
                        kodeBiaya = ?,
                        tglBiaya = ?, 
                        idKlinik = ?,
                        kodeAkun = ?,
                        nomorNota = ?,
                        grandTotal = ?, 
                        statusBiaya = ?,
                        idUser = ?
                    ',
                [
                    $kodeBiaya,
                    $tglBiaya,
                    $idKlinik,
                    $kodeAkun,
                    $nomorNota,
                    ubahToInt($grandTotal),
                    'Aktif',
                    $idUserAsli
                ]
            );

            if ($status) {
                updateNomorUrut($db, 'biaya_klinik', $idUserAsli);
                $pesan = 'Proses Transfer Berhasil';
            } else {
                $pesan = 'Proses Transfer Gagal';
            }
            $more = [];
        } else if ($flag === 'update') {

            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE
                    biaya_klinik
                SET 
                    tglBiaya = ?, 
                    kodeAkun = ?,
                    nomorNota = ?,
                    grandTotal = ?, 
                    idUser = ?
                WHERE
                    idBiayaKlinik = ?
                ',
                [
                    $tglBiaya,
                    $kodeAkun,
                    $nomorNota,
                    ubahToInt($grandTotal),
                    $idUserAsli,
                    $idBiayaKlinik
                ]
            );
            if ($status) {
                $pesan = 'Proses Update Berhasil';
            } else {
                $pesan = 'Proses Update Gagal';
            }
            $more = [];
        } else if ($flag === 'tambahDetail') {

            $status = statementWrapper(
                DML_INSERT,
                'INSERT INTO 
                        biaya_klinik_detail 
                    SET 
                        kodeBiaya = ?,
                        namaItem = ?, 
                        qty = ?,
                        hargaSatuan = ?,
                        subTotal = ?,
                        statusBiayaDetail = ?,
                        idUser = ?
                    ',
                [
                    $kodeBiaya,
                    $namaItem,
                    ubahToInt($qty),
                    ubahToInt($hargaSatuan),
                    ubahToInt($subTotal),
                    'Aktif',
                    $idUserAsli
                ]
            );

            if ($status) {
                $pesan = 'Proses Berhasil';
            } else {
                $pesan = 'Proses Gagal';
            }
            $more = [];
        } else if ($flag === 'updateDetail') {

            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE
                    biaya_klinik_detail
                SET 
                    namaItem = ?, 
                    qty = ?,
                    hargaSatuan = ?,
                    subTotal = ?,
                    idUser = ?
                WHERE
                    idBiayaKlinikDetail = ?
                ',
                [
                    $namaItem,
                    ubahToInt($qty),
                    ubahToInt($hargaSatuan),
                    ubahToInt($subTotal),
                    $idUserAsli,
                    $idBiayaKlinikDetail
                ]
            );
            if ($status) {
                $pesan = 'Proses Update Detail Berhasil';
            } else {
                $pesan = 'Proses Update Detail Gagal';
            }
            $more = [];
        } else if ($flag == 'uploadFile') {
            extract(
                upload_file(
                    // RESOURCE
                    compact(
                        'noForm',
                        'htmlName',
                        'folder'
                    ),
                    FILE_DEFAULT_ALLOWED_TYPE
                )
            );
        } else if ($flag == 'deleteFile') {
            extract(
                delete_file(
                    // RESOURCE
                    compact(
                        'kodeFile',
                        'folder'
                    )
                )
            );
        }
         else {
            $status = false;
            $pesan = 'Proses Tidak Valid';
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Input ke Database';
    } finally {
        $data = compact('status', 'pesan', 'more');
    }
}

echo json_encode($data);
