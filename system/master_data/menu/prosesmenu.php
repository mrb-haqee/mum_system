<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT idUser from user where idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//MENGECEK APAKAH USER INI BERHAK MENGAKSES MENU INI
$sqlCekMenu = $db->prepare('SELECT * from user_detail 
  inner join menu_sub 
  on menu_sub.idSubMenu = user_detail.idSubMenu
  where user_detail.idUser = ?
  and namaFolder = ?');
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__)
]);
$dataCekMenu = $sqlCekMenu->fetch();

$tokenCSRFForm = $_POST['tokenCSRFForm'];
$tokenValid = hash_equals($tokenCSRF, $tokenCSRFForm);

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Maaf, silahkan login terlebih dahulu!');
} else {

    extract($_POST, EXTR_SKIP);

    try {
        if ($flag == 'delete') {
            $sql = $db->prepare('DELETE FROM menu where idMenu=?');
            $hasil = $sql->execute([$idMenu]);

            if ($hasil) {
                $data = array('status' => true, 'pesan' => 'Proses Delete Menu Berhasil');
            } else {
                $data = array('status' => false, 'pesan' => 'Proses Delete Menu Tidak Berhasil');
            }
        } else if ($flag == 'update') {
            $sql = $db->prepare(
                'UPDATE 
                    menu 
                SET 
                    namaMenu=?,
                    indexSort=?,
                    iconClass=?,
                    idUser=?
                WHERE idMenu=?'
            );
            $hasil = $sql->execute([
                $namaMenu,
                $indexSort,
                $iconClass,
                $idUserAsli,
                $idMenu
            ]);

            if ($hasil) {
                $data = array('status' => true, 'pesan' => 'Proses Update Menu Berhasil');
            } else {
                $data = array('status' => false, 'pesan' => 'Proses Update Menu Gagal');
            }
        } else {

            $sql = $db->prepare('INSERT INTO menu SET 
                namaMenu=?,
                indexSort= ?,
                iconClass=?,
                statusMenu=?,
                idUser=?');
            $hasil = $sql->execute([
                $namaMenu,
                $indexSort,
                $iconClass,
                'Aktif',
                $idUserAsli
            ]);

            if ($hasil) {
                $data = array('status' => true, 'pesan' => 'Proses Tambah Menu Berhasil');
            } else {
                var_dump($sql->errorInfo());
                $data = array('status' => false, 'pesan' => 'Proses Tambah Menu Gagal');
            }
        }
    } catch (PDOException $e) {
        $data = array('status' => false, 'pesan' => 'Maaf, nama menu ini sudah terpakai!');
    }
}

echo json_encode($data);
