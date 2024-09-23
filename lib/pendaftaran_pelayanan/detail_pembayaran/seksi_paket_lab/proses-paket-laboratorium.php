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

            $detail = statementWrapper(
                DML_SELECT,
                'SELECT * FROM pasien_paket_laboratorium_klinik WHERE idPaketLaboratoriumKlinik = ?',
                [$id]
            );

            $status = statementWrapper(
                DML_DELETE,
                'DELETE FROM pasien_paket_laboratorium_klinik WHERE idPaketLaboratoriumKlinik = ?',
                [$id]
            );


            if ($status) {
                $statusDetail = statementWrapper(
                    DML_DELETE,
                    'DELETE FROM pasien_laboratorium_klinik WHERE idPaketLaboratorium = ? AND kodeAntrian = ?',
                    [$detail['idPaketLaboratorium'], $detail['kodeAntrian']]
                );
                $statusDetail = statementWrapper(
                    DML_DELETE,
                    'DELETE FROM pasien_tindakan_klinik WHERE idPaketLaboratorium = ? AND kodeAntrian = ?',
                    [$detail['idPaketLaboratorium'], $detail['kodeAntrian']]
                );
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

            $status = statementWrapper(
                DML_INSERT,
                'INSERT INTO 
                    pasien_paket_laboratorium_klinik 
                SET 
                    kodeAntrian = ?,
                    idPaketLaboratorium = ?,
                    jenisHarga = ?,
                    idAdmisi = ?,
                    harga = ?,
                    qty = ?,
                    subTotal = ?,
                    statusBPJS = ?,
                    idUser = ?
                ',
                [
                    $kodeAntrian,
                    $idPaketLaboratorium,
                    $jenisHarga,
                    $idAdmisi,
                    $harga,
                    ubahToInt($qty),
                    $subTotal,
                    $statusBPJS,
                    $idUserAsli,
                ]
            );

            if ($status) {

               $statusDetail = statementWrapper(
                    DML_INSERT,
                    "INSERT INTO
                        pasien_laboratorium_klinik
                        (
                            kodeAntrian,
                            idProsedurLaboratorium,
                            idPaketLaboratorium,
                            idLaboratoriumRujukan,
                            jenisHarga,
                            idAdmisi,
                            statusBPJS,
                            harga,
                            qty,
                            subTotal,
                            subTotalHPP,
                            bebasBiaya
                        )
                    SELECT
                        ? as kodeAntrian,
                        paket_laboratorium_detail.idItem,
                        paket_laboratorium.idPaketLaboratorium,
                        paket_laboratorium_detail.idLaboratoriumRujukan,
                        ? as jenisHarga,
                        ? as idAdmisi,
                        ? as statusBPJS,
                        0 as harga,
                        ? as qty,
                        0 as subTotal,
                        (? * COALESCE(laboratorium_rujukan_harga.nominal,0)) as subTotalHPP,
                        'Tidak' as bebasBiaya
                    FROM
                        paket_laboratorium_detail
                        LEFT JOIN laboratorium_rujukan ON paket_laboratorium_detail.idLaboratoriumRujukan = laboratorium_rujukan.idLaboratoriumRujukan
                        INNER JOIN prosedur_laboratorium ON paket_laboratorium_detail.idItem = prosedur_laboratorium.idProsedurLaboratorium
                        INNER JOIN paket_laboratorium ON paket_laboratorium.kodePaketLaboratorium = paket_laboratorium_detail.kodePaketLaboratorium
                        LEFT JOIN laboratorium_rujukan_harga ON CONCAT_WS('-', paket_laboratorium_detail.idItem, laboratorium_rujukan.kodeLaboratoriumRujukan) = CONCAT_WS('-',laboratorium_rujukan_harga.idProsedurLaboratorium, laboratorium_rujukan_harga.kodeLaboratoriumRujukan)
                    WHERE
                        paket_laboratorium.idPaketLaboratorium = ?
                        AND paket_laboratorium_detail.tipeItem = 'Prosedur Laboratorium'
                    ",
                    [
                        $kodeAntrian,
                        $jenisHarga,
                        $idAdmisi,
                        $statusBPJS,
                        $qty,
                        $qty,
                        $idPaketLaboratorium
                    ]
                );

                $statusDetail = statementWrapper(
                    DML_INSERT,
                    "INSERT INTO
                        pasien_tindakan_klinik
                        (
                            kodeAntrian,
                            idTindakan,
                            idPaketLaboratorium,
                            jenisHarga,
                            idAdmisi,
                            statusBPJS,
                            harga,
                            qty,
                            subTotal,
                            bebasBiaya
                        )
                    SELECT
                        ? as kodeAntrian,
                        paket_laboratorium_detail.idItem,
                        paket_laboratorium.idPaketLaboratorium,
                        ? as jenisHarga,
                        ? as idAdmisi,
                        ? as statusBPJS,
                        0 as harga,
                        ? as qty,
                        0 as subTotal,
                        'Tidak' as bebasBiaya
                    FROM
                        paket_laboratorium_detail
                        INNER JOIN tindakan ON paket_laboratorium_detail.idItem = tindakan.idTindakan
                        INNER JOIN paket_laboratorium ON paket_laboratorium.kodePaketLaboratorium = paket_laboratorium_detail.kodePaketLaboratorium
                    WHERE
                        paket_laboratorium.idPaketLaboratorium = ?
                        AND paket_laboratorium_detail.tipeItem = 'Tindakan'
                    ",
                    [
                        $kodeAntrian,
                        $jenisHarga,
                        $idAdmisi,
                        $statusBPJS,
                        $qty,
                        $idPaketLaboratorium
                    ]
                );
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
