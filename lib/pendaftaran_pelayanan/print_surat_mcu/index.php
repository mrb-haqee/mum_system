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
            pasien_pemeriksaan_klinik.*,
            pasien_invoice_klinik.*,
            klinik.nama as namaKlinik,
            klinik.alamat as alamatKlinik,
            klinik.noTelp as noTelpKlinik,
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
        [$kodeRM, $kodeAntrian, 'Surat MCU']
    );

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Surat Radiologi',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Surat_MCU");


    generateQRValidator(
        $qr_text,
        $qr_filename
    );

    $gender = [
      'Laki-laki' => 'Male',
      'Perempuan' => 'Female'
    ];


?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Surat MCU</title>
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

            table tbody td p {
                margin-bottom: 0;
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
                                
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="row" id="sectionBody">
                                <div class="col-sm-12 text-center">
                                  <h4 style="text-decoration:underline !important;">MEDICAL CHECK UP EXAMINATION</h4>
                                  <p><strong>No. <?=$dataSurat['kodeSurat']?></strong></p>
                                </div>
                                <div class="col-sm-5">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td>MR Code</td>
                                                <td><?= $kodeRM ?></td>
                                            </tr>
                                            <tr>
                                                <td>Date</td>
                                                <td><?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></td>
                                            </tr>
                                            <tr>
                                                <td>Address</td>
                                                <td><?= $dataUpdate['alamatDomisili'] ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-sm-2"></div>
                                <div class="col-sm-5">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td>Patient</td>
                                                <td><?= $dataUpdate['namaPasien'] ?> (<?= $gender[$dataUpdate['jenisKelamin']] ?>)</td>
                                            </tr>
                                            
                                            <tr>
                                                <td>Phone Number</td>
                                                <td><?= wordwrap($dataUpdate['noTelp'],10,"<br>") ?></td>
                                            </tr>
                                            <tr>
                                                <td>Birthday / Age</td>
                                                <td><?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?> / <?= $umur['umur'] ?> Years</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-sm-12">
                                  <h5>I.  ANAMNESA</h5>
                                  <table>
                                    <tr>
                                      <td class="align-top" style="width:20%;">1. Recent Illness</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['deskripsiKeluhan'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">2. Past Medical History</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['historyOfRecentIllness'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">3. Family Medical History</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['familyMedicalHistory'] ?> </td>
                                    </tr>
                                  </table>
                                  <br>

                                  <h5>II. PHYSICAL EXAMINATION</h5>
                                  <table>
                                    <tr>
                                      <td class="align-top" style="width:20%;">1.  Level of Consciousness</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['levelOfConsiusness'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">2. Weight</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['beratBadan']??'-' ?> Kg</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">3. Height</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['tinggiBadan']??'-' ?> Cm</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%; text-align:right;">Body Mass Index (BMI)</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> 
                                        <?php
                                          $tinggiBadanBMI = $dataUpdate['tinggiBadan'] ?? 0;
                                          if($tinggiBadanBMI > 0){
                                            $BMI = $dataUpdate['beratBadan'] / (($dataUpdate['tinggiBadan']/100)^2);
                                          }
                    
                                          echo number_format($BMI,2);
                                          ?>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">4. Blood Pressure</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['bloodPressure'] ?> mmHg</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">5. Pulse</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['pulse'] ?> x/min</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">6. Respiration Rate</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['respiratoryRate'] ?> x/min</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">7. Temperature</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['temperature'] ?> &deg;C</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">8. Oxygen Saturation</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['o2Saturation'] ?> %</td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">9. Color Blindness</td>
                                      <td class="align-top" style="width:2%;">:</td>
                                      <td class="align-top"> <?= $dataUpdate['butaWarna'] ?></td>
                                    </tr>
                                  </table>
                                  <br>

                                  <h5>III. GENERAL STATE</h5>
                                  <table>
                                    <tr>
                                      <td class="align-top" style="width:20%;">1. General Appearance</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['generalAppearance'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">2. Head</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['head']??'-' ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">3. Eye</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['eye'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">4. ENT</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> 
                                        <?= $dataUpdate['ear'] ?><br> 
                                        <?= $dataUpdate['nose'] ?><br> 
                                        <?= $dataUpdate['throat'] ?><br>  
                                      </td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">5. Cor</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['cor'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">6. Pulmo</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['pulmo'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">7. Abdomen</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['abdomen'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">8. Extremities</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['extrimities'] ?></td>
                                    </tr>
                                    <tr>
                                      <td class="align-top" style="width:20%;">9. Status Lokalis</td>
                                      <td class="align-top">:</td>
                                      <td class="align-top"> <?= $dataUpdate['statusLokalis'] ?></td>
                                    </tr>
                                  </table>
                                  <br>

                                  <h5>IV. OTHER EXAMINATION</h5>
                                  <p>
                                    <?=$dataSurat['hasilPemeriksaan']?>
                                  </p>
                                </div>

                                <div class="col-sm-6 mt-5" style="page-break-inside: avoid;">
                                    <b>Bali, <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></b><br>
                                    <span class="d-block">Dokter</span>
                                    <?php
                                    if (is_null($ttdDokter)) {
                                    ?>
                                        <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                        <br>
                                        <!-- <div style="height: 100px;"></div> -->
                                    <?php
                                    } else {
                                    ?>
                                        <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                        <br>
                                        <!-- <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/pegawai/<?= $ttdDokter ?>"> -->
                                    <?php
                                    }
                                    ?>
                                    <span><?= $dokter ?></span>
                                    <br>
                                    <span>NO SIP : <?= $noSIP ?></span>
                                </div>
                                <div class="col-sm-6 mt-5 text-right">
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