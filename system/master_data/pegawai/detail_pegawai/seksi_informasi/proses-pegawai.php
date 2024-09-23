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

    sanitizeInput($_POST, ['fileTTD']);
    extract($_POST, EXTR_SKIP);

    $folder = 'pegawai';

    try {
        if ($flag === 'saveTTD') {

            // $fileName =  "fileTTD_{$kodePegawai}.png";

            // $BASE_DIR = ABS_PATH_FILE_UPLOAD_DIR . '/' . $folder;

            // if (file_exists("$BASE_DIR/$fileName")) {
            //     $status = unlink("$BASE_DIR/$fileName");
            // } else {
            //     $status = true;
            // }

            // if ($status) {

            //     [$mimeType, $encodedData] = explode(',', $fileTTD);
            //     $decodedData = base64_decode($encodedData);

            //     $filePath =  "$BASE_DIR/$fileName";

            //     $result = file_put_contents($filePath, $decodedData);

                // if ($result) {
                //     $status = statementWrapper(
                //         DML_UPDATE,
                //         'UPDATE
                //             pegawai
                //         SET
                //             fileTTD = ?
                //         WHERE
                //             kodePegawai = ?
                //         ',
                //         [$fileName, $kodePegawai]
                //     );

                //     if ($status) {
                //         $pesan = 'Proses Tanda Tangan Berhasil';
                //     } else {
                //         $pesan = 'Proses Tanda Tangan Gagal';
                //     }
                // } else {
                //     $pesan = 'Proses Simpan File Gagal';
                // }
            // } else {
            //     $pesan = 'Proses File Gagal';
            // }
        } else if ($flag == 'update') {
            $status = statementWrapper(
                DML_UPDATE,
                'UPDATE
                    pegawai 
                SET 
                    NIKPegawai=?,
                    namaPegawai=?,
                    jenisKelamin=?,
                    noRekening=?,
                    npwp=?,
                    ttlPegawai=?,
                    tempatLahir=?,
                    agama=?,
                    email=?,
                    pendidikan=?,
                    alamatPegawai=?,
                    idDepartemenPegawai=?,
                    hpPegawai=?,
                    tglMulaiKerja=?,
                    keterangan=?,
                    idUserEdit=?
                WHERE
                    kodePegawai=?
                ',
                [
                    $NIKPegawai,
                    $namaPegawai,
                    $jenisKelamin,
                    $noRekening,
                    $npwp,
                    $ttlPegawai,
                    $tempatLahir,
                    $agama,
                    $email,
                    $pendidikan,
                    $alamatPegawai,
                    $idDepartemenPegawai,
                    $hpPegawai,
                    $tglMulaiKerja,
                    $keterangan,
                    $idUserAsli,
                    $kodePegawai,
                ]
            );

            if ($status) {
                $pesan = 'Proses Update Pegawai Berhasil';
            } else {
                $pesan = 'Proses Update Pegawai Gagal';
            }
        } else if ($flag === 'tambah') {
            $status = statementWrapper(
                DML_INSERT,
                'INSERT INTO 
                    pegawai 
                SET 
                    kodePegawai=?,
                    NIKPegawai=?,
                    namaPegawai=?,
                    jenisKelamin=?,
                    noRekening=?,
                    npwp=?,
                    ttlPegawai=?,
                    tempatLahir=?,
                    agama=?,
                    email=?,
                    pendidikan=?,
                    alamatPegawai=?,
                    idDepartemenPegawai=?,
                    hpPegawai=?,
                    tglMulaiKerja =?,
                    keterangan = ?,
                    statusPegawai=?,
                    idUser=?',
                [
                    $kodePegawai,
                    $NIKPegawai,
                    $namaPegawai,
                    $jenisKelamin,
                    $noRekening,
                    $npwp,
                    $ttlPegawai,
                    $tempatLahir,
                    $agama,
                    $email,
                    $pendidikan,
                    $alamatPegawai,
                    $idDepartemenPegawai,
                    $hpPegawai,
                    $tglMulaiKerja,
                    $keterangan,
                    'Generated',
                    $idUserAsli
                ]
            );

            if ($status) {
                $pesan = 'Proses Tambah Pegawai Berhasil';
            } else {
                $pesan = 'Proses Tambah Pegawai Gagal';
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
