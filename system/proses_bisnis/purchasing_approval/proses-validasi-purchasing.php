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

        if ($flag === 'update') {

            if ($statusPersetujuan == 'Reject') {

                $queries = [
                    'UPDATE 
                        stock_po 
                    SET 
                        keterangan = ?,
                        statusPersetujuan = ?,
                        statusPO = ?,
                        idUserEdit = ?
                    WHERE
                        kodePO = ?',
                    'DELETE FROM stock_pengiriman_detail
                    WHERE kodePengiriman IN (
                        SELECT stock_pengiriman.kodePengiriman 
                        FROM stock_pengiriman 
                        INNER JOIN stock_pengiriman_detail ON stock_pengiriman.kodePengiriman = stock_pengiriman_detail.kodePengiriman
                        WHERE stock_pengiriman.kodePO = ?
                    )',
                    'DELETE FROM stock_pengiriman WHERE kodePO = ?'
                ];

                $parameters = [
                    [
                        $keterangan,
                        $statusPersetujuan,
                        'Aktif',
                        $idUserAsli,
                        $kodePO
                    ],
                    [$kodePO],
                    [$kodePO]
                ];

                $status = multiStatementWrapper(
                    DML_UPDATE,
                    $queries,
                    $parameters
                );
            } else {
                $queries = [
                    // UPDATE status Persetujuan
                    'UPDATE 
                        stock_po 
                    SET 
                        keterangan = ?,
                        statusPersetujuan = ?,
                        statusPO = ?,
                        idUserEdit = ?
                    WHERE
                    kodePO = ?',
                    // INPUT DATA PENGIRIMAN
                    'INSERT INTO 
                        stock_pengiriman 
                    SET 
                        kodePengiriman = ?,
                        kodePO = ?,
                        statusFinalisasi = ?,
                        idUser = ?',
                    // INPUT DATA PENGIRIMAN DETAIL
                    'INSERT INTO 
                        stock_pengiriman_detail (idUser, kodePengiriman, qty, idPODetail, satuan, subTotal, hargaSatuan, tipeInventory, idInventory, persentaseDiskon, persentasePpn)
                    SELECT ? as idUSer, ? as kodePengiriman, spd.qty, spd.idPODetail, spd.satuan, spd.subTotal, spd.hargaSatuan, spd.tipeInventory, spd.idInventory, spp.persentaseDiskon, spp.persentasePpn
                    FROM stock_po_detail as spd
                    INNER JOIN stock_po_pembayaran as spp ON spd.kodePO = spp.kodePO
                    WHERE spd.kodePO = ?',
                ];

                $kodePengiriman = nomorUrut($db, 'stock_pengiriman', 1);

                $parameters = [
                    [
                        $keterangan,
                        $statusPersetujuan,
                        'Diproses',
                        $idUserAsli,
                        $kodePO
                    ],
                    [
                        $kodePengiriman,
                        $kodePO,
                        "Aktif",
                        $idUserAsli
                    ],
                    [
                        $idUserAsli,
                        $kodePengiriman,
                        $kodePO,
                    ]
                ];

                $status = multiStatementWrapper(
                    DML_UPDATE,
                    $queries,
                    $parameters
                );

                if ($status) {
                    updateNomorUrut($db, 'stock_pengiriman', 1);
                }
            }

            if ($status) {
                $pesan = 'Proses Update Purchasing Berhasil';
            } else {
                $pesan = 'Proses Update Purchasing Gagal';
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
        if (is_array($status)) {
            $status  = array_reduce($status, function ($carry, $item) {
                return $carry && $item;
            }, true);
        }
        $data = compact('status', 'pesan', 'more');
    }
}

echo json_encode($data);
