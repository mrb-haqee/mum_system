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
    $arrayNotifikasi = array('flagNotif' => 'gagal', 'pesan' => 'Proses Authentikasi Gagal, Data Tidak Valid');
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {
        if ($flag == 'delete') {
            $sql = $db->prepare('UPDATE departemen_pegawai set 
        statusDepartemen=?,
        idUserEdit=?
        where idDepartemenPegawai=?');
            $hasil = $sql->execute([
                'batal',
                $idUserAsli,
                $idDepartemenPegawai
            ]);
        } else if ($flag == 'update') {
            $sql = $db->prepare('UPDATE departemen_pegawai set 
        namaDepartemenPegawai=?,
        idUserEdit =?
        where idDepartemenPegawai=?');
            $hasil = $sql->execute([
                $namaDepartemenPegawai,
                $idUserAsli,
                $idDepartemenPegawai
            ]);
        } else {
            $sql = $db->prepare('INSERT INTO departemen_pegawai set 
        namaDepartemenPegawai=?,
        idUser=?');
            $hasil = $sql->execute([
                $namaDepartemenPegawai,
                $idUserAsli
            ]);
        }

        if ($hasil) {
            $arrayNotifikasi = array('flagNotif' => 'sukses');
        } else {
            $arrayNotifikasi = array('flagNotif' => 'gagal', 'pesan' => 'Maaf, terjadi kesalahan pada proses data!');
        }
    } catch (PDOException $e) {
        $arrayNotifikasi = array('flagNotif' => 'gagal', 'pesan' => 'Maaf, terjadi kesalahan pada proses data!');
    }
}

echo json_encode($arrayNotifikasi);
