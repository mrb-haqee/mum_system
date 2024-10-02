<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";

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
        if ($flag === 'tambah') {

            $countItem = count(array_filter(array_keys($_POST), function ($key) {
                return preg_match('/^item-\d+-qty$/', $key);
            }));

            $queryDetail = '';
            $value = '(?,?,?,?,?,?,?,?,?)';
            $parameterDetail = [];


            for ($i = 0; $i < $countItem; $i++) {
                if ($i == $countItem - 1) {
                    $queryDetail .=  $value;
                } else {
                    $queryDetail .= $value . ',';
                }

                $parameterDetail = array_merge(
                    $parameterDetail,
                    [
                        $kodeSR,
                        $_POST["item-{$i}-idInventory"],
                        ubahToInt($_POST["item-{$i}-qty"]),
                        $_POST["item-{$i}-satuan"],
                        ubahToInt($_POST["item-{$i}-hargaSatuan"]),
                        ubahToInt($_POST["item-{$i}-subTotal"]),
                        'Diterima',
                        ubahToInt($_POST["item-{$i}-idPODetail"]),
                        $idUserAsli
                    ]
                );
            }

            $query = [
                // STOCK SERVICE RECIPT
                'INSERT INTO stock_sr
                SET kodeSR = ?, kodePO = ?, nomorBM = ?, nomorFaktur = ?, tanggal = ?, idUser = ?, statusFinalisasi = ?',

                // STOCK SERVICE RECIPT DETAIL
                'INSERT INTO stock_sr_detail (kodeSR, idInventory, qty, satuan, hargaSatuan, subTotal, statusItem, idPODetail, idUser) VALUES ' . $queryDetail,

                // STOCK SERVICE RECIPT DETAIL
                'UPDATE stock_po SET statusPO = ?, idUserEdit = ?'

            ];

            $execute = [
                [$kodeSR, $kodePO, $nomorBM, $nomorFaktur, $tanggal, $idUserAsli, 'Aktif'],
                $parameterDetail,
                ['Diterima', $idUserAsli]
            ];

            $status = multiStatementWrapper(DML_INSERT, $query, $execute);

            if ($status) {
                updateNomorUrut($db, 'stock_sr', $idUserAsli);
                $pesan = 'Proses Penerimaan Barang Berhasil';
            } else {
                $pesan = 'Proses Penerimaan Barang Gagal';
            }

            $more = [];
        } else if ($flag === 'update') {
            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE stock_sr
                SET 
                    tanggal = ?,
                    nomorBM = ?,
                    nomorFaktur = ?,
                    idUserEdit = ?
                WHERE 
                    kodeSR =? AND kodePO = ?',
                [$tanggal, $nomorBM, $nomorFaktur, $idUserAsli, $kodeSR, $kodePO]
            );

            if ($status) {
                $pesan = 'Proses Update Berhasil';
            } else {
                $pesan = 'Proses Update Gagal';
            }

            $more = [];
        } else if ($flag === 'updateDetail') {


            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE stock_sr_detail
                SET 
                    qty = ?,
                    hargaSatuan = ?,
                    subTotal = ?,
                    idUserEdit = ?
                WHERE 
                    idSRDetail =?',
                [ubahToInt($qty), ubahToInt($hargaSatuan), ubahToInt($subTotal), $idUserAsli, $idSRDetail]
            );

            if ($status) {
                $pesan = 'Proses Update Berhasil';
            } else {
                $pesan = 'Proses Update Gagal';
            }

            $more = [];
        } else {
            $status = false;
            $pesan = 'Proses Tidak Terdaftar';
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Input ke Database';
    } finally {
        if (is_array($status)) {
            $status  = array_reduce($status, function ($carry, $item) {
                return $carry && $item;
            }, true);
        }
        $data = compact('status', 'pesan', 'more');
    }
}

echo json_encode($data);
