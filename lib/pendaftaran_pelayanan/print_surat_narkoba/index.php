<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiqrcode.php";
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
            klinik.nama,
            klinik.alamat as alamatKlinik,
            klinik.noTelp as noTelpKlinik,
            klinik.email,
            pasien_pemeriksaan_klinik.waktuPemeriksaan,
            admisi.namaAdmisi
        FROM
            pasien_antrian
            INNER JOIN klinik ON pasien_antrian.idKlinik = klinik.idKlinik
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
            LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
        WHERE 
            pasien_antrian.kodeAntrian=?',
        [$kodeAntrian],
    );

    $waktuPeriksa = explode(" ", $dataUpdate['timeStamp'])[1] ?? '-';

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
        [$kodeRM, $kodeAntrian, 'Surat Narkoba']
    );

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter, 'alamatPegawai' => $alamat, 'noSTR' => $noSTR] = getNamaPegawai($db, $dataUpdate['idDokter']);

    $kasir = getNamaPegawai($db, $dataCekUser['idPegawai'])['namaPegawai'];
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Surat Narkoba',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Surat_Narkoba");


    generateQRValidator(
        $qr_text,
        $qr_filename
    );

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Surat Antigen</title>
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
                                <div class="col-md-12 text-center">
                                  <h4 style="text-decoration: underline !important;">CERTIFICATE OF DRUG TEST</h4>
                                  <p><strong>No. <?= $dataSurat['kodeSurat'] ?></strong></p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="row" id="sectionBody">
                                <div class="col-md-12">
                                  <p>
                                    <strong>The undersigned below,</strong>
                                  </p>
                                  <table>
                                    <tr>
                                      <td>Name</td>
                                      <td>: <?= $dokter ?></td>
                                    </tr>
                                    <tr>
                                      <td>No. STR</td>
                                      <td>: <?= $noSTR ?></td>
                                    </tr>
                                    <tr>
                                      <td>Position</td>
                                      <td>: General Practitioner</td>
                                    </tr>
                                    <tr>
                                      <td>Address</td>
                                      <td>: <?= $alamat ?></td>
                                    </tr>
                                  </table>

                                  <p>
                                    <strong>Certify that,</strong>
                                  </p>
                                  <table>
                                    <tr>
                                      <td>Name</td>
                                      <td>: <?= $dataUpdate['namaPasien'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>Place / Date of Birth</td>
                                      <td>: <?= $dataUpdate['tempatLahir'] ?> / <?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?></td>
                                    </tr>
                                    <tr>
                                      <td>Sex</td>
                                      <td>: <?= $dataUpdate['jenisKelamin'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>Age</td>
                                      <td>: <?= $umur['umur'] ?> Years</td>
                                    </tr>
                                    <tr>
                                      <td>Address</td>
                                      <td>: <?= $dataUpdate['alamat'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>Phone Number</td>
                                      <td>: <?= $dataUpdate['noTelp'] ?></td>
                                    </tr>
                                  </table>

                                  <p>
                                    This letter is used for <strong><?=$dataSurat['keperluan']?></strong>
                                    <br>
                                    A drug examination was carried out on the person concerned including a laboratory examination of urine using the immunochromatography method on <strong><?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></strong> with the results of drug elements as follows:
                                  </p>

                                  <table>
                                    <tr>
                                      <td>MOP (Morphine)</td>
                                      <td>: <?= $dataSurat['morphine'] ?></td>
                                      <td>BZO (Benzodiazepine)</td>
                                      <td>: <?= $dataSurat['benzodiazepine'] ?></td>
                                    </tr>
                                    </tr>
                                    <tr>
                                      <td>AMP (Amphetamine)</td>
                                      <td>: <?= $dataSurat['amphetamine'] ?></td>
                                      <td>MET (Methamphetamine)</td>
                                      <td>: <?= $dataSurat['methamphetamine'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>THC (Tetrahydrocannabinol)</td>
                                      <td>: <?= $dataSurat['tetrahydrocannabinol'] ?></td>
                                      <td>COC (Cocaine)</td>
                                      <td>: <?= $dataSurat['cocaine'] ?></td>
                                    </tr>
                                  </table>
                                  <p>This certificate is made to be used as needed by the person named above.</p>
                                </div>
                                
                                <div class="col-sm-12">
                                    <b>Bali, <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></b><br>
                                    <span>Authorized By</span>
                                    <br>
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                    <br>
                                    <span class="text-underline"><?= $dokter ?></span>
                                    <br>
                                    <span> No SIP : <?= $noSIP ?></span>
                                </div>

                            </div>
                            <div class="text-center" id="footerImg">
                                <?php
                                if ($fileFooter) {
                                ?>
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/klinik/<?= $fileFooter['fileName'] ?>" style="width: 30%;">
                                <?php
                                }
                                ?>
                            </div>
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