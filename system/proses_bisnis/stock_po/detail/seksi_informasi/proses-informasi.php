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
        if ($statusPersetujuan == 'Approve') {
            $status = true;
            $pesan = 'Data tidak bisa dirubah karena Sudah di Approve';
            $more = [];
        } else {

            if ($flag == 'update') {
                $status = statementWrapper(
                    DML_UPDATE,
                    'UPDATE
                    stock_po
                SET 
                    kodeVendor = ?,
                    tanggal = ?, 
                    tanggalUpdate = ?, 
                    metodeBayar = ?, 
                    statusPersetujuan = ?,
                    statusPO = ?,
                    nomorSP = ?,
                    idUserEdit = ?
                WHERE
                    kodePO = ?
                ',
                    [
                        $kodeVendor,
                        $tanggal,
                        date('Y-m-d'),
                        $metodeBayar,
                        'Pending',
                        'Aktif',
                        $nomorSP,
                        $idUserAsli,
                        $kodePO,
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
                    DML_INSERT,
                    [
                        'INSERT INTO 
                        stock_po 
                    SET 
                        kodePO = ?,
                        kodeVendor = ?,
                        tanggal = ?, 
                        tanggalUpdate = ?, 
                        metodeBayar = ?, 
                        statusPersetujuan = ?,
                        statusPO = ?,
                        nomorSP = ?,
                        idUser = ?
                    ',
                        'INSERT INTO stock_po_pembayaran SET kodePO = ?'
                    ],
                    [
                        [
                            $kodePO,
                            $kodeVendor,
                            $tanggal,
                            $tanggal,
                            $metodeBayar,
                            'Pending',
                            'Aktif',
                            $nomorSP,
                            $idUserAsli
                        ],
                        [$kodePO]
                    ]
                );

                if ($status) {
                    $pesan = 'Proses Tambah PO Berhasil';
                } else {
                    $pesan = 'Proses Tambah PO Gagal';
                }

                $more = [];
            } else {
                $status = false;
                $pesan = 'Proses Tidak Terdaftar';
            }
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
