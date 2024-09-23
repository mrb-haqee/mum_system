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


$tokenValid = hash_equals($tokenCSRF, $_POST['tokenCSRFForm'] ?? '');

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Maaf, silahkan login terlebih dahulu!');
} else {

    sanitizeInput($_POST, ['userName', 'password']);

    extract($_POST, EXTR_SKIP);

    try {

        if ($flag == 'delete') {
            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM user WHERE idUser = ?',
                [$idUserAccount]
            );

            if ($status) {
                $pesan = 'Proses Delete User Berhasil';
            } else {
                $pesan = 'Proses Delete User Gagal';
            }
        } else {

            $userNameInput = $_POST['userName'];
            $passInput = $_POST['password'];
            $idPegawaiInput = $_POST['idPegawai'];

            $aksesEditable = $aksesEditable ?? 'Non Aktif';

            $dataUser = selectStatement(
                'SELECT * FROM user WHERE idUser = ?',
                [$idUserAccount],
                'fetch'
            );

            if ($dataUser) {
                if ($userNameInput === $dataUser['userName']) {
                    $duplicate = false;
                } else {
                    $cekDuplicate = selectStatement(
                        'SELECT COUNT(*) as cek FROM user WHERE userName = ?',
                        [$userNameInput],
                        'fetch'
                    )['cek'];

                    if (intval($cekDuplicate) > 0) {
                        $duplicate = true;
                    } else {
                        $duplicate = false;
                    }
                }
            } else {
                $duplicate = false;
            }


            if ($duplicate === false) {

                if ($flag == 'update') {

                    if ($passInput === '') {
                        $sql = $db->prepare();
                        $status = $sql->execute();

                        $status = statementWrapper(
                            DML_UPDATE,
                            'UPDATE 
                                user 
                            SET 
                                userName = ?,
                                aksesEditable = ?
                            WHERE 
                                idUser = ?
                            ',
                            [
                                $userNameInput,
                                $aksesEditable,
                                $idUserAccount
                            ]
                        );

                        if ($status) {
                            $pesan = 'Proses Update User Berhasil';
                        } else {
                            $pesan = 'Proses Update User Gagal';
                        }
                    } else {
                        $passInput = password_hash($passInput, PASSWORD_DEFAULT);

                        $sql = $db->prepare(
                            'UPDATE 
                                user 
                            SET 
                                userName     = ?,
                                password     = ?,
                                aksesEditable = ?   
                            WHERE 
                                idUser = ?'
                        );
                        $status = $sql->execute([
                            $userNameInput,
                            $passInput,
                            $aksesEditable,
                            $idUserAccount
                        ]);
                    }

                    if ($status) {
                        $pesan = 'Proses Update User Berhasil';
                    } else {
                        $pesan = 'Proses Update User Gagal';
                    }
                } else {
                    $passInput = password_hash($passInput, PASSWORD_DEFAULT);

                    $status = statementWrapper(
                        DML_INSERT,
                        'INSERT INTO 
                            user 
                        SET 
                            userName=?,
                            password=?,
                            idPegawai = ?,
                            statusUser = ?,
                            idUserInput = ?
                        ',
                        [
                            $userNameInput,
                            $passInput,
                            $idPegawaiInput,
                            'Aktif',
                            $idUserAsli
                        ]
                    );

                    if ($status) {
                        $pesan = 'Proses Tambah User Berhasil';
                    } else {
                        $pesan = 'Proses Tambah User Gagal';
                    }
                }
            } else {
                $status = false;
                $pesan = 'User Name Telah Terpakai. Mohon Untuk Menggunakan Username Lain';
            }
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Masalah Saat Menginput Data Ke Database';
    } finally {
        $data = compact('status', 'pesan');
    }
}
echo json_encode($data);
