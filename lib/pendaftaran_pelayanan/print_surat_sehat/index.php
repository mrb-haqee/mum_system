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
            pasien_pemeriksaan_klinik.*,
            pasien_invoice_klinik.*,
            klinik.nama,
            klinik.alamat as alamatKlinik,
            klinik.noTelp,
            klinik.email,
            admisi.namaAdmisi
        FROM
            pasien_antrian
            INNER JOIN klinik ON pasien_antrian.idKlinik = klinik.idKlinik
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
            LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
            LEFT JOIN pasien_invoice_klinik ON pasien_antrian.kodeAntrian = pasien_invoice_klinik.kodeAntrian 
        WHERE 
            pasien_antrian.kodeAntrian=?',
        [$kodeAntrian],
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
        [$kodeRM, $kodeAntrian, 'Surat Sehat']
    );

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);

    $kasir = getNamaPegawai($db, $dataCekUser['idPegawai'])['namaPegawai'];
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Surat Sehat',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Surat_Sehat");


    generateQRValidator(
        $qr_text,
        $qr_filename
    );

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Surat Sehat</title>
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
                                                <td>Tinggi Badan<br>
                                                    <span class="english">Body Height</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['tinggiBadan'] ?> cm</b></td>
                                            </tr>
                                            <tr>
                                                <td>Berat Badan<br>
                                                    <span class="english">Body Weight</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['beratBadan'] ?> kg</b></td>
                                            </tr>
                                            <tr>
                                                <td>Tekanan Darah<br>
                                                    <span class="english">Blood Pressure</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['bloodPressure'] ?> mmHg</b></td>
                                            </tr>
                                            <tr>
                                                <td>Nadi<br>
                                                    <span class="english">Pulse</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['pulse'] ?> x/min</b></td>
                                            </tr>
                                            <tr>
                                                <td>Suhu<br>
                                                    <span class="english">Temperature</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['temperature'] ?> &deg;C</b></td>
                                            </tr>
                                            <tr>
                                                <td>Laju Pernafasan<br>
                                                    <span class="english">Respiratory Rate</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['respiratoryRate'] ?> x/min</b></td>
                                            </tr>
                                            <tr>
                                                <td>Buta Warna<br>
                                                    <span class="english">Colour Blind</span>
                                                </td>
                                                <td>: <b><?= $dataUpdate['butaWarna'] ?></b></td>
                                            </tr>
                                            <tr>
                                                <td>Keperluan<br>
                                                    <span class="english">Usage</span>
                                                </td>
                                                <td>: <b><?= $dataSurat['keperluan'] ?></b></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p>Telah dilakukan pemeriksaan fisik dan tidak ditemukan keluhan ataupun tanda dan gejala suatu penyakit, untuk selanjutnya pada saat ini dinyatakan <b>SEHAT</b>.</p>
                                    <p class="english">We have conducted physical examination for this patient and there is no complaint or signs and symptoms of illness and it is now declared <b>HEALTHY</b>.</p>
                                </div>

                                <div class="col-sm-12">
                                    <b>Bali, <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></b><br>
                                    <span>Dokter Pemeriksa</span>
                                    <br>
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                    <br>
                                    <span class="text-underline"><?= $dokter ?></span>
                                    <br>
                                    <span> No SIP : <?= $noSIP ?></span>
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