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
		AND (
            menu_sub.namaFolder = ?
            OR 
            menu_sub.namaFolder = ?
        )
	'
);
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__),
    'rekam_medis'
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

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {

        if ($flag === 'delete') {

            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM pasien_laboratorium_klinik WHERE idLaboratoriumKlinik = ?',
                [$id]
            );

            if ($status) {
                $pesan = 'Proses Delete Prosedur Laboratorium Berhasil';
            } else {
                $pesan = 'Proses Delete Prosedur Laboratorium Gagal';
            }
        } else if ($flag === 'tambah') {

            $harga = ubahToInt($harga);
            $subTotal = ubahToInt($subTotal);

            [$jenisHarga, $idAdmisi] = statementWrapper(
                DML_SELECT,
                'SELECT jenisHarga, idAdmisi FROM pasien_antrian WHERE kodeAntrian = ?',
                [$kodeAntrian]
            );

            if ($jenisHarga !== 'BPJS') {
                $statusBPJS = NULL;
            } else {
                $statusBPJS = 'Rekomendasi Dokter';
            }

            $idLaboratoriumRujukan = $idLaboratoriumRujukan ?? NULL;

            if (is_null($idLaboratoriumRujukan)) {
                $hargaHPP = 0;
            } else {
                $hargaHPP = statementWrapper(
                    DML_SELECT,
                    'SELECT 
                        laboratorium_rujukan_harga.nominal 
                    FROM 
                        laboratorium_rujukan_harga 
                        INNER JOIN laboratorium_rujukan ON laboratorium_rujukan_harga.kodeLaboratoriumRujukan = laboratorium_rujukan.kodeLaboratoriumRujukan
                    WHERE 
                        laboratorium_rujukan_harga.idProsedurLaboratorium = ? 
                        AND laboratorium_rujukan.idLaboratoriumRujukan = ?',
                    [$idProsedurLaboratorium, $idLaboratoriumRujukan]
                )['nominal'];
            }

            $subTotalHPP = $hargaHPP * $qty;

            $status = statementWrapper(
                DML_INSERT,
                'INSERT INTO 
                    pasien_laboratorium_klinik 
                SET 
                    kodeAntrian = ?,
                    idProsedurLaboratorium = ?,
                    idLaboratoriumRujukan = ?,
                    jenisHarga = ?,
                    idAdmisi = ?,
                    harga = ?,
                    qty = ?,
                    subTotal = ?,
                    subTotalHPP = ?,
                    statusBPJS = ?,
                    idUser = ?
                ',
                [
                    $kodeAntrian,
                    $idProsedurLaboratorium,
                    $idLaboratoriumRujukan,
                    $jenisHarga,
                    $idAdmisi,
                    $harga,
                    ubahToInt($qty),
                    $subTotal,
                    $subTotalHPP,
                    $statusBPJS,
                    $idUserAsli,
                ]
            );

            if ($status) {
                $pesan = 'Proses Tambah Prosedur Laboratorium Berhasil';
            } else {
                $pesan = 'Proses Tambah Prosedur Laboratorium Gagal';
            }
        } else {
            $status = false;
            $pesan = 'Proses Tidak Tersedia';
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Input ke Database';
    } finally {
        $data = compact('status', 'pesan');
    }
}

echo json_encode($data);
