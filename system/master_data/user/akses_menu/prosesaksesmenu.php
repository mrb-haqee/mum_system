<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT idUser FROM user WHERE idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//MENGECEK APAKAH USER INI BERHAK MENGAKSES MENU INI
$sqlCekMenu = $db->prepare('SELECT * FROM user_detail 
  inner join menu_sub 
  on menu_sub.idSubMenu = user_detail.idSubMenu
  where user_detail.idUser = ?
  and namaFolder = ?');
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__)
]);
$dataCekMenu = $sqlCekMenu->fetch();


$tokenValid = hash_equals($tokenCSRF, $_POST['tokenCSRFForm']);

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Maaf, silahkan login terlebih dahulu!');
} else {

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    $data = [];

    try {
        if ($flag == 'aktivasiHak') {
            $hakAkses = selectStatement(
                'SELECT hakAkses FROM user_detail WHERE idUserDetail = ?',
                [$idUserDetail],
                'fetch'
            )['hakAkses'];

            $listHakAkses = json_decode($hakAkses, true);

            if (isset($listHakAkses[$tipeAkses])) {

                if ($statusHak === 'Active') {
                    $listHakAkses[$tipeAkses] = 'Non Active';
                } else if ($statusHak === 'Non Active') {
                    $listHakAkses[$tipeAkses] = 'Active';
                }

                $hakAkses = json_encode($listHakAkses);
                $status = insertStatement(
                    'UPDATE
                        user_detail
                    SET
                        hakAkses = ?
                    WHERE
                        idUserDetail = ?
                    ',
                    [$hakAkses, $idUserDetail]
                );

                if ($status) {
                    $pesan = 'Proses Aktivasi Hak Berhasil';
                } else {
                    $pesan = 'Proses Aktivasi Hak Gagal';
                }
            } else {
                $status = false;
                $pesan = 'Tipe Hak Tidak Valid';
            }
        } else if ($flag === 'aksesMenu') {

            if ($statusMenu === 'Active') {

                if ($type === 'widget') {
                    $status = insertStatement(
                        'INSERT INTO
                            user_dashboard
                        SET
                            idUser = ?,
                            widget = ?
                        ',
                        [$idUserAccount, $idItem]
                    );
                } else if ($type === 'menu') {
                    $formatAksi = selectStatement(
                        'SELECT formatAksi FROM menu_sub WHERE idSubMenu = ?',
                        [$idItem],
                        'fetch'
                    )['formatAksi'];

                    $listFormatAkses = json_decode($formatAksi, true);

                    $hakAkses = [];
                    foreach ($listFormatAkses as $akses) {
                        $hakAkses[$akses] = 'Active';
                    }

                    $hakAkses = json_encode($hakAkses);

                    $status = insertStatement(
                        'INSERT INTO
                            user_detail
                        SET
                            idUser = ?,
                            idSubMenu = ?,
                            hakAkses = ?
                        ',
                        [$idUserAccount, $idItem, $hakAkses]
                    );
                }
            } else if ($statusMenu === 'Non Active') {
                if ($type === 'widget') {
                    $status = updateStatement(
                        'DELETE FROM
                            user_dashboard
                        WHERE
                            idUser = ?
                            AND widget = ?
                        ',
                        [$idUserAccount, $idItem]
                    );
                } else if ($type === 'menu') {
                    $status = updateStatement(
                        'DELETE FROM
                            user_detail
                        WHERE
                            idUser = ?
                            AND idSubMenu = ?
                        ',
                        [$idUserAccount, $idItem]
                    );
                }
            }

            if ($status) {
                $pesan = 'Proses Pemberian Akses Berhasil';
            } else {
                $pesan = 'Proses Pemberian Akses Gagal';
            }
        } else {
            $status = false;
            $pesan = 'Proses Tidak Valid';
        }
    } catch (PDOException $e) {
        $data = array('status' => false, 'pesan' => 'Maaf, menu ini sudah ditambahkan!');
    } finally {
        $data = compact('status', 'pesan');
    }
}

echo json_encode($data);
