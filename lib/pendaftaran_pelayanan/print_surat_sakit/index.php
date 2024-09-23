<?php

include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiqrcode.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";

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

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !validateIP($_SESSION['IP_ADDR'])) {
    alertSessionExpForm();
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    extract($_GET, EXTR_SKIP);

    if (isset($param)) {
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

        $kodeRM = $result['kodeRM'];
        $kodeAntrian = $result['kodeAntrian'];
    }

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT 
            pasien_antrian.*,
            pasien.*,
            pasien.alamat as alamatPasien,
            pasien_pemeriksaan_klinik.*,
            pasien_invoice_klinik.*,
            klinik.nama,
            klinik.alamat,
            klinik.noTelp,
            klinik.email,
            admisi.namaAdmisi,
            icd_10.diagnosis as diagnosisICD10
        FROM
            pasien_antrian
            INNER JOIN klinik ON pasien_antrian.idKlinik = klinik.idKlinik
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
            LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
            LEFT JOIN pasien_invoice_klinik ON pasien_antrian.kodeAntrian = pasien_invoice_klinik.kodeAntrian
            LEFT JOIN (
                SELECT
                    *
                FROM
                    pasien_icd_10_klinik
                WHERE
                    jenisUrutan = ?
            ) pasien_icd_10_klinik ON pasien_antrian.kodeAntrian = pasien_icd_10_klinik.kodeAntrian
            LEFT JOIN icd_10 ON pasien_icd_10_klinik.idICD10 = icd_10.idICD10
        WHERE 
            pasien_antrian.kodeAntrian=?
            ',
        ['Primary', $kodeAntrian],
    );

    $fileKop = statementWrapper(
        DML_SELECT,
        'SELECT
            uploaded_file.*
        FROM
            klinik
            INNER JOIN uploaded_file ON klinik.kodeKlinik = uploaded_file.noForm
        WHERE
            klinik.idKlinik = ?
            AND uploaded_file.htmlName = ?
        ',
        [$dataUpdate['idKlinik'], 'imgKopKlinik']
    );

    $fileFooter = statementWrapper(
        DML_SELECT,
        'SELECT
            uploaded_file.*
        FROM
            klinik
            INNER JOIN uploaded_file ON klinik.kodeKlinik = uploaded_file.noForm
        WHERE
            klinik.idKlinik = ?
            AND uploaded_file.htmlName = ?
        ',
        [$dataUpdate['idKlinik'], 'imgFooterKlinik']
    );

    $dataSurat = statementWrapper(
        DML_SELECT,
        "SELECT
            *
        FROM
            pasien_surat
        WHERE
            kodeRM = ?
            AND kodeAntrian = ?
            AND jenisSurat = ?
        ",
        [$kodeRM, $kodeAntrian, 'Surat Sakit']
    );

    ['noSIP' => $noSIP, 'namaPegawai' => $dokter, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);

    $kasir = getNamaPegawai($db, $dataCekUser['idPegawai'])['namaPegawai'];
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Surat Sakit',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Surat_Sakit");

    generateQRValidator(
        $qr_text,
        $qr_filename
    );

    $durasiIstirahat = selisihTanggal($dataSurat['tanggalAwal'], $dataSurat['tanggalAkhir']) + 1;

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Surat Sakit</title>
        <!-- Google Font: Source Sans Pro -->
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

        <style type="text/css">
            @media print {
                .newPage {
                    page-break-after: always;
                }

                #footerImg {
                    position: fixed;
                    bottom: 20px;
                }
            }

            table tbody td {
                padding: 6px 8px !important;
            }

            #sectionHeader table tbody td {
                padding: 6px 8px !important;
                font-size: 14px !important;
            }

            .english {
                font-size: 10px;
            }

            body {
                font-size: 16px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <table>
                <thead>
                    <tr>
                        <td>
                            <div class="row" id="sectionHeader">
                                <!-- <div class="col-sm-6 d-flex align-items-center justify-content-start" style="height: 150px;">
                    <div class="text-left">
                        <h3 style="color : #4a1717" class="mb-0"><strong>MEDICAL RESUME</strong></h3>
                        <strong style="color : #4a1717; opacity: .5"><?= $dataUpdate['namaKlinik']; ?></strong>
                    </div>
                </div> -->
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <?php
                                        if ($fileKop) {
                                        ?>
                                            <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/klinik/<?= $fileKop['fileName'] ?>" style="width:100%">
                                        <?php
                                        }
                                        ?>

                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="row" id="sectionBody">
                                <div class="col-sm-12 text-center">
                                    <h3>SURAT KETERANGAN SAKIT</h3>
                                    <p><i>MEDICAL CERTIFICATE</i></p>
                                    <h4><b><?= $dataSurat['kodeSurat'] ?></b></h4>
                                </div>

                                <div class="col-sm-12">
                                    <p>Yang bertanda tangan di bawah ini, dokter Sanur Medical Clinic menerangkan dengan sesungguhnya bahwa :<br>
                                        <span class="english">
                                            The undersigned, Medical Doctor of Sanur Medical Clinic, certify that the examination of :
                                        </span>
                                    </p>
                                </div>

                                <div class="col-sm-12">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td style="width: 20%;">Nama<br>
                                                    <span class="english">Name</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['namaPasien'] ?></b></td>
                                            </tr>
                                            <tr>
                                                <td>NIK<br>
                                                    <span class="english">Passport No</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['noIdentitas'] ?></b></td>
                                            </tr>
                                            <tr>
                                                <td>Tempat / Tanggal Lahir<br>
                                                    <span class="english">Place / D.O.B</span>
                                                </td>
                                                <td>
                                                    : <b><?= $dataUpdate['tempatLahir'] ?> / <?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?></b><br>
                                                    : <span class="english"><?= $dataUpdate['tempatLahir'] ?> / <?= tanggalTerbilangEng($dataUpdate['tanggalLahir']) ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jenis Kelamin<br>
                                                    <span class="english">Sex</span>
                                                </td>
                                                <td>
                                                    : <b><?= $dataUpdate['jenisKelamin'] ?></b><br>
                                                    : <span class="english"><?= ubahToEng($dataUpdate['jenisKelamin']) ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Alamat Lengkap<br>
                                                    <span class="english">Address in Bali</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['alamatDomisili'] ?></b></td>
                                            </tr>
                                            <tr>
                                                <td>Keluhan<br>
                                                    <span class="english">Complaint</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['keluhanUtama'] ?></b></td>
                                            </tr>
                                            <tr>
                                                <td>Diagnosis<br>
                                                    <span class="english">Diagnose</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['diagnosisICD10'] ?></b></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p>
                                        Telah dilakukan pemeriksaan fisik dan memerlukan istirahat selama <b><?= $durasiIstirahat ?></b> hari karena sakit, terhitung tanggal <b><?= tanggalTerbilang($dataSurat['tanggalAwal']) ?></b> sampai dengan tanggal <b><?= tanggalTerbilang($dataSurat['tanggalAkhir']) ?></b>.<br>
                                        <span class="english">
                                            We have conducted physical examination for this patient and would need medical attention for <b><?= $durasiIstirahat ?></b> days barring complication start from date <b><?= tanggalTerbilangEng($dataSurat['tanggalAwal']) ?></b> till date <b><?= tanggalTerbilangEng($dataSurat['tanggalAkhir']) ?></b>.
                                        </span>
                                    </p>
                                    <p>Harap yang berkepentingan maklum adanya. <br>
                                        <span class="english">
                                            Please be informed.
                                        </span>
                                    </p>
                                </div>

                                <div class="col-sm-12">
                                    <b>Bali, <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></b><br>
                                    <span>Dokter Pemeriksa</span>
                                    <br>
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                    <br>
                                    <span class="text-underline"><?= $dokter ?></span>
                                    <br>
                                    <strong>SIP : <?= $noSIP ?></strong>
                                </div>

                            </div>
                            <?php
                            if ($fileFooter) {
                            ?>
                                <div class="text-center" id="footerImg">
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/klinik/<?= $fileFooter['fileName'] ?>" style="width: 30%;">
                                </div>
                            <?php
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    </body>

    </html>
<?php
}
?>