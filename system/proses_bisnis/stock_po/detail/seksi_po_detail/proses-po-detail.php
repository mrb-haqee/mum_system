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

    $folder = 'klinik';

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {

        if ($flag === 'delete') {

            $status = multiStatementWrapper(
                DML_UPDATE,
                [
                    'DELETE FROM stock_po_detail WHERE idPODetail = ?',
                    'UPDATE stock_po SET statusPersetujuan = ?'
                ],
                [
                    [$idPODetail],
                    ['Pending']
                ]
            );

            if ($status) {
                $pesan = 'Proses Delete Berhasil';
            } else {
                $pesan = 'Proses Delete Gagal';
            }

            $more = [];
        } else if ($flag == 'update') {
            $status = multiStatementWrapper(
                DML_UPDATE,
                [
                    // 
                    'UPDATE 
                        stock_po_detail 
                    SET 
                        idInventory = ?,
                        satuan = ?, 
                        qty = ?,
                        hargaSatuan = ?,
                        subTotal = ?,
                        StatusItem = ?,
                        idUserEdit = ?
                    WHERE kodePO = ? AND idPODetail = ?
                    ',

                    // 
                    'UPDATE stock_po SET statusPersetujuan = ?'
                ],
                [
                    [
                        $idInventory,
                        $satuan,
                        ubahToInt($qty),
                        ubahToInt($hargaSatuan),
                        ubahToInt($subTotal),
                        'Aktif',
                        $idUserAsli,
                        $kodePO,
                        $idPODetail,
                    ],
                    ['Pending']
                ]
            );

            if ($status) {
                $pesan = 'Proses Update Berhasil';
            } else {
                $pesan = 'Proses Update Gagal';
            }

            $more = [];
        } else if ($flag === 'tambah') {
            $status = multiStatementWrapper(
                DML_UPDATE,
                [
                    'INSERT INTO 
                        stock_po_detail 
                    SET 
                        kodePO = ?,
                        idInventory = ?, 
                        satuan = ?, 
                        qty = ?,
                        hargaSatuan = ?,
                        subTotal = ?,
                        StatusItem = ?,
                        idUser = ?,
                        tipeInventory =?
                    ',
                    'UPDATE stock_po SET statusPersetujuan = ?'
                ],
                [
                    [
                        $kodePO,
                        $idInventory,
                        $satuan,
                        ubahToInt($qty),
                        ubahToInt($hargaSatuan),
                        ubahToInt($subTotal),
                        'Aktif',
                        $idUserAsli,
                        'barang'
                    ],
                    ['Pending']
                ]
            );

            if (count(selectStatement('SELECT subTotal FROM stock_po_detail WHERE kodePO=?', [$kodePO])) == 1) {
                [
                    'persentaseDiskon' => $persentaseDiskon,
                    'persentasePpn' => $persentasePpn
                ] = selectStatement('SELECT persentaseDiskon, persentasePpn FROM stock_po_pembayaran WHERE kodePO=?', [$kodePO], 'fetch');

                $jumlah = floatval(ubahToInt($subTotal));
                $totalDiskon = $jumlah * $persentaseDiskon / 100;
                $totalPPN = ($jumlah - $totalDiskon) * $persentasePpn / 100;
                $grandTotal = $jumlah - $totalDiskon + $totalPPN;

                statementWrapper(
                    DML_UPDATE,
                    'UPDATE stock_po_pembayaran SET diskon=?, ppn=?, grandTotal=? WHERE kodePO=?',
                    [$totalDiskon, $totalPPN, $grandTotal, $kodePO]
                );
            };

            if ($status) {
                $pesan = 'Proses Tambah Barang Berhasil';
            } else {
                $pesan = 'Proses Tambah Barang Gagal';
            }

            $more = [];
        } else if ($flag == 'updateTotal') {

            $diskon = min(100, floatval($diskon));
            $ppn = min(100, floatval($ppn));
            $totalDiskon = $jumlah * $diskon / 100;
            $totalPPN = ($jumlah - $totalDiskon) * $ppn / 100;
            $grandTotal = $jumlah - $totalDiskon + $totalPPN;

            $status = multiStatementWrapper(
                DML_UPDATE,
                [
                    'UPDATE 
                        stock_po_pembayaran 
                    SET 
                        grandTotal = ?,
                        diskon = ?,
                        ppn = ?,
                        persentaseDiskon = ?,
                        persentasePpn = ?,
                        idUserEdit = ?
                    WHERE kodePO = ?
                    ',
                    'UPDATE stock_po SET statusPersetujuan = ?'
                ],
                [
                    [
                        ubahToInt($grandTotal),
                        ubahToInt($totalDiskon),
                        ubahToInt($totalPPN),
                        floatval($diskon),
                        floatval($ppn),
                        $idUserAsli,
                        $kodePO,
                    ],
                    ['Pending']
                ]
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
