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
                    petty_cash
                SET 
                    namaPettyCash = ?,
                    idUser = ? 
                WHERE 
                    kodePettyCash = ?',
                [
                    $namaPettyCash,
                    $idUserAsli,
                    $kodePettyCash
                ]
            );

            if ($status) {
                $pesan = 'Proses Update Tujuan Transfer Berhasil';
            } else {
                $pesan = 'Proses Update Tujuan Transfer Gagal';
            }

            $more = [];
        } else if ($flag === 'tambah') {
            $status = statementWrapper(
                DML_UPDATE,
                'INSERT INTO 
                    petty_cash
                SET 
                    kodePettyCash = ?,
                    namaPettyCash=?,
                    statusPettyCash = ?,
                    idUser=?',
                [
                    $kodePettyCash,
                    $namaPettyCash,
                    'Generated',
                    $idUserAsli,
                ]
            );

            if ($status) {
                $pesan = 'Proses Tambah Tujuan Transfer Berhasil';
            } else {
                $pesan = 'Proses Tambah Tujuan Transfer Gagal';
            }

            $more = [];
        } else if ($flag == 'uploadFile') {

            $workingDir = "{$constant('ABS_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$folder}{$constant('DIRECTORY_SEPARATOR')}";
            $previewDir = "{$constant('REL_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$folder}{$constant('DIRECTORY_SEPARATOR')}";

            $cekFile = statementWrapper(
                DML_SELECT,
                'SELECT * FROM uploaded_file WHERE noForm = ? AND htmlName = ? AND folder = ?',
                [$noForm, $htmlName, $folder],
            );

            if ($cekFile) {

                $fileName = basename($cekFile['fileName']);
                $filePath = $workingDir . $cekFile['fileName'];

                if (file_exists($filePath)) {
                    $status = unlink($filePath);
                }

                $statusDelete = statementWrapper(
                    DML_DELETE,
                    'DELETE FROM uploaded_file WHERE kodeFile = ?',
                    [
                        $cekFile['kodeFile']
                    ]
                );
            }

            $file = $_FILES[$htmlName];
            $fileName = basename($file['name']);

            $filePath = $workingDir . $fileName;

            $status = true;

            $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileSize = intval($file['size']);

            // Tidak Boleh Lebih Dari 1 MB
            if ($fileSize > 1048576) {
                $status = false;
                $pesan = 'Ukuran File Melebihi Batas Maksimum 1 MB';

                $kodeFile = '';
            }
            // Ekstensi Tidak Boleh Selain PNG dan PDF
            else if (($fileType === 'png' || $fileType === 'jpg' || $fileType === 'jpeg') === false) {
                $status = false;
                $pesan = 'Hanya Menerima File Berekstensi .jpg, .jpeg, & .png';

                $kodeFile = '';
            } else {

                $status = move_uploaded_file($file['tmp_name'], $filePath);

                if ($status) {
                    do {
                        $kodeFile = md5(random_bytes(32));
                        $cekGroup = statementWrapper(DML_SELECT, 'SELECT COUNT(*) as cek FROM uploaded_file WHERE kodeFile = ?', [$kodeFile])['cek'];
                    } while (intval($cekGroup) > 0);

                    $status = statementWrapper(
                        DML_INSERT,
                        'INSERT INTO 
                            uploaded_file 
                        SET 
                            noForm = ?,
                            kodeFile = ?,
                            fileName = ?,
                            htmlName = ?,
                            folder = ?,
                            sizeFile = ?,
                            ekstensi = ?,
                            idUserInput = ?',
                        [
                            $noForm,
                            $kodeFile,
                            $fileName,
                            $htmlName,
                            $folder,
                            $fileSize,
                            $fileType,
                            $idUserAsli
                        ]
                    );

                    if ($status) {
                        $pesan = 'Proses Upload File Telah Berhasil';
                    } else {
                        $pesan = 'Proses Upload File Gagal';
                    }
                } else {
                    $pesan = 'Proses Upload File Gagal';
                }
            }

            $more = [
                'kodeFile' => $kodeFile,
                'previewPath' => base64_encode("{$previewDir}/{$fileName}")
            ];
        } else if ($flag == 'deleteFile') {

            $cekFile = statementWrapper(
                DML_SELECT,
                'SELECT * FROM uploaded_file WHERE kodeFile = ?',
                [$kodeFile]
            );

            if ($cekFile) {
                $filePath = "{$constant('ABS_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$folder}{$constant('DIRECTORY_SEPARATOR')}{$cekFile['fileName']}";

                if (file_exists($filePath)) {
                    $status = unlink($filePath);
                }

                $status = statementWrapper(
                    DML_DELETE,
                    'DELETE FROM uploaded_file WHERE kodeFile = ?',
                    [
                        $cekFile['kodeFile']
                    ]
                );

                if ($status) {
                    $pesan = 'File Berhasil Dihapus';
                } else {
                    $pesan = 'File Tidak Berhasil Dihapus';
                }
            } else {
                $status = false;
                $pesan = 'Proses Hapus Gagal. File Tidak Ditemukan !';
            }

            $more = [
                'kodeFile' => $kodeFile,
                'previewPath' => base64_encode("")
            ];
        } else {
            $status = false;
            $pesan = 'Proses Tidak Terdaftar';
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Input ke Database';
    } finally {
        $data = compact('status', 'pesan', 'more');
    }
}

echo json_encode($data);
