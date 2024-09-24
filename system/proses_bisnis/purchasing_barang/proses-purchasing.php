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

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {

        if ($flag === 'delete') {
            $status = statementWrapper(
                DML_UPDATE,
                "UPDATE
                    purchasing
                SET 
                    statusPurchasing = ?
                WHERE
                    kodePurchasing = ?
                ",
                ['Non Aktif', $kodePurchasing]
            );
            if ($status) {
                $status = statementWrapper(
                    DML_DELETE,
                    'DELETE FROM purchasing_detail WHERE kodePurchasing = ?',
                    [$kodePurchasing]
                );
            }

            if ($status) {
                $pesan = 'Proses Berhasil';
            } else {
                $pesan = 'Proses Gagal';
            }
            $more = [];
        } elseif ($flag === 'deleteDetail') {

            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM purchasing_detail WHERE idPurchasingDetail = ?',
                [$idPurchasingDetail]
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
                        purchasing 
                    SET 
                        kodePurchasing = ?,
                        kodeVendor = ?,
                        tanggalPurchasing = ?, 
                        tanggalUpdate = ?, 
                        metodePembayaran = ?, 
                        statusPersetujuan = ?,
                        statusPurchasing = ?,
                        idUser = ?
                    ',
                [
                    $kodePurchasing,
                    $kodeVendor,
                    $tanggal,
                    $tanggal,
                    $metodePembayaran,
                    'Pending',
                    'Aktif',
                    $idUserAsli
                ]
            );

            if ($status) {
                updateNomorUrut($db, 'purchasing', $idUserAsli);
                $pesan = 'Proses Transfer Berhasil';
            } else {
                $pesan = 'Proses Transfer Gagal';
            }
            $more = [];
        } else if ($flag === 'update') {

            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE
                    purchasing
                SET 
                    kodeVendor = ?,
                    tanggalPurchasing = ?, 
                    tanggalUpdate = ?, 
                    metodePembayaran = ?, 
                    statusPersetujuan = ?,
                    idUserEdit = ?
                WHERE
                    kodePurchasing = ?
                ',
                [
                    $kodeVendor,
                    $tanggal,
                    date('Y-m-d'),
                    $metodePembayaran,
                    'Pending',
                    $idUserAsli,
                    $kodePurchasing,
                ]
            );
            if ($status) {
                $pesan = 'Proses Update Berhasil';
            } else {
                $pesan = 'Proses Update Gagal';
            }
            $more = [];
        } else if ($flag === 'updateDiscountPPN') {
            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE
                    purchasing
                SET 
                    discount = ?, 
                    ppn = ?,
                    idUserEdit = ?
                WHERE
                    kodePurchasing = ?
                ',
                [
                    ubahToInt(($discount > 100) ? 100 : $discount),
                    ubahToInt(($ppn > 100) ? 100 : $ppn),
                    $idUserAsli,
                    $kodePurchasing,
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
                        purchasing_detail 
                    SET 
                        kodePurchasing = ?,
                        idBarang = ?, 
                        qty = ?,
                        subTotal = ?,
                        statusPurchasingDetail = ?,
                        idUser = ?
                    ',
                [
                    $kodePurchasing,
                    $idBarang,
                    ubahToInt($qty),
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
                    purchasing_detail
                SET 
                    idBarang = ?, 
                    qty = ?,
                    subTotal = ?,
                    idUserEdit = ?
                WHERE
                    idPurchasingDetail = ?
                ',
                [
                    $idBarang,
                    ubahToInt($qty),
                    ubahToInt($subTotal),
                    $idUserAsli,
                    $idPurchasingDetail
                ]
            );
            if ($status) {
                $pesan = 'Proses Update Detail Berhasil';
            } else {
                $pesan = 'Proses Update Detail Gagal';
            }
            $more = [];
        } else {
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
