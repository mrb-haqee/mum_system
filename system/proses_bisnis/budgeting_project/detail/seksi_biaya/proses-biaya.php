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

        if ($flag == 'update') {
            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE 
                    budgeting_project_biaya
                SET 
                    namaItem=?,
                    qty=?,
                    hargaSatuan=?,
                    subTotal=?,
                    idUser=?
                WHERE 
                    idBudgetingProjectBiaya = ?',
                [
                    $namaItem,
                    ubahToInt($qty),
                    ubahToInt($hargaSatuan),
                    ubahToInt($subTotal),
                    $idUserAsli,
                    $idBudgetingProjectBiaya
                ]
            );

            if ($status) {
                $pesan = 'Proses Update Biaya Berhasil';
            } else {
                $pesan = 'Proses Update Biaya Gagal';
            }

            $more = [];
        } else if ($flag === 'tambah') {
            $status = statementWrapper(
                DML_UPDATE,
                'INSERT INTO 
                    budgeting_project_biaya
                SET 
                    kodeBudgetingProject = ?,
                    namaItem=?,
                    qty=?,
                    hargaSatuan=?,
                    subTotal=?,
                    idUser=?',
                [
                    $kodeBudgetingProject,
                    $namaItem,
                    ubahToInt($qty),
                    ubahToInt($hargaSatuan),
                    ubahToInt($subTotal),
                    $idUserAsli,
                ]
            );

            if ($status) {
                $pesan = 'Proses Tambah Biaya Berhasil';
            } else {
                $pesan = 'Proses Tambah Biaya Gagal';
            }
        } else if ($flag === 'delete') {
            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM budgeting_project_biaya WHERE idBudgetingProjectBiaya = ?',
                [$idBudgetingProjectBiaya]
            );

            if ($status) {
                $pesan = 'Proses Delete Biaya Berhasil';
            } else {
                $pesan = 'Proses Delete Biaya Gagal';
            }
        } else {
            $status = false;
            $pesan = 'Proses Tidak Terdaftar';
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Input ke Database';
    } finally {
        $data = compact('status', 'pesan');
    }
}

echo json_encode($data);
