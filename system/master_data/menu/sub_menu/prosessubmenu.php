<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasihakuser.php";

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

$tokenCSRFForm = $_POST['tokenCSRFForm'] ?? '';
$tokenValid = hash_equals($tokenCSRF, $tokenCSRFForm);

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Maaf, silahkan login terlebih dahulu!');
} else {

    extract($_POST, EXTR_SKIP);

    try {
        if ($flag == 'delete') {
            $sql = $db->prepare('DELETE FROM menu_sub where idSubMenu=?');
            $status = $sql->execute([$idSubMenu]);

            if ($status) {
                $data = array('status' => true, 'pesan' => 'Proses Delete Sub Menu Berhasil');
            } else {
                $data = array('status' => false, 'pesan' => 'Proses Delete Sub Menu Gagal');
            }
        } else if ($flag == 'update') {
            $formatAksi = $formatAksi ?? ['Edit', 'Delete'];

            $formatAksi = array_filter($formatAksi, function ($akses) {
                return in_array($akses, array_keys(getDaftarHak()));
            });

            $formatAkses = json_encode($formatAksi);

            $sql = $db->prepare(
                'UPDATE 
                    menu_sub 
                SET 
                    idMenu=?,
                    indexSort =?,
                    namaSubMenu=?,
                    namaFolder=?,
                    namaKelompok=?,
                    formatAksi=?,
                    idUser=?
                WHERE
                    idSubMenu=?'
            );
            $status = $sql->execute([
                $idMenu,
                $indexSort,
                $namaSubMenu,
                $namaFolder,
                $namaKelompok,
                $formatAkses,
                $idUserAsli,
                $idSubMenu
            ]);

            if ($status) {
                $data = array('status' => true, 'pesan' => 'Proses Update Sub Menu Berhasil');
            } else {
                $data = array('status' => false, 'pesan' => 'Proses Update Sub Menu Gagal');
            }
        } else {
            $formatAksi = $formatAksi ?? ['Edit', 'Delete'];

            $formatAksi = array_filter($formatAksi, function ($akses) {
                return in_array($akses, array_keys(getDaftarHak()));
            });

            $formatAkses = json_encode($formatAksi);

            $indexSort = selectStatement('SELECT MAX(indexSort) + 1 as nextIndex FROM menu_sub WHERE idMenu = ?', [$idMenu], 'fetch')['nextIndex'] ?? 1;

            $sql = $db->prepare(
                'INSERT INTO 
                    menu_sub 
                SET 
                    idMenu=?,
                    namaSubMenu=?,
                    indexSort=?,
                    namaFolder=?,
                    namaKelompok=?,
                    formatAksi=?,
                    idUser=?
                '
            );

            $status = $sql->execute([
                $idMenu,
                $namaSubMenu,
                $indexSort,
                $namaFolder,
                $namaKelompok,
                $formatAkses,
                $idUserAsli
            ]);

            if ($status) {
                $data = array('status' => true, 'pesan' => 'Proses Tambah Sub Menu Berhasil');
            } else {
                var_dump($sql->errorInfo());
                $data = array('status' => false, 'pesan' => 'Proses Tambah Sub Menu Gagal');
            }
        }
    } catch (PDOException $e) {
        $data = array('status' => false, 'pesan' => 'Maaf, nama submenu ini sudah terpakai!');
    }
}
echo json_encode($data);
