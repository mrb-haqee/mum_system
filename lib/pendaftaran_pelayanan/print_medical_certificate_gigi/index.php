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

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Resume Medis',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Resume_Medis");


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
        <title>Print Medical Report</title>
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
                                                <td><?= $dataUpdate['alamat'] ?></td>
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
                                                <td><?= $dataUpdate['namaPasien'] ?></td>
                                            </tr>
                                            <tr>
                                                <td>Gender</td>
                                                <td><?= $gender[$dataUpdate['jenisKelamin']] ?></td>
                                            </tr>
                                            <tr>
                                                <td>Phone Number</td>
                                                <td><?= $dataUpdate['noTelp'] ?></td>
                                            </tr>
                                            <tr>
                                                <td>Birthday / Age</td>
                                                <td><?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?> / <?= $umur['umur'] ?> Years</td>
                                            </tr>
                                        </tbody>
                                    </table>
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
                                    <?php
                                    $dataOralUpdate = statementWrapper(
                                        DML_SELECT,
                                        'SELECT * FROM pasien_pemeriksaan_dental_oral WHERE kodeAntrian = ?',
                                        [$kodeAntrian]
                                    );

                                    $dataSupportingUpdate = statementWrapper(
                                        DML_SELECT,
                                        'SELECT * FROM pasien_pemeriksaan_dental_supporting WHERE kodeAntrian = ?',
                                        [$kodeAntrian]
                                    );

                                    $dataVitalityUpdate = statementWrapper(
                                        DML_SELECT,
                                        'SELECT * FROM pasien_pemeriksaan_dental_vitality WHERE kodeAntrian = ?',
                                        [$kodeAntrian]
                                    );

                                    function tampilPemeriksaan($judul, $value, $notes, $tipe){
                                      ?>
                                      <tr>
                                        <td><?=$judul?></td>
                                        <td><?=$value?></td>
                                        <?php
                                        if($tipe == 1){
                                          ?>
                                          <td><?=$notes?></td>
                                          <?php
                                        }
                                        ?>
                                        
                                      </tr>
                                      <?php
                                    }
                                    ?>
                                    <h5><u>ORAL</u></h5>
                                    <table class="table table-bordered">
                                      <thead>
                                        <tr>
                                          <th>Examination</th>
                                          <th>Value</th>
                                          <th>Note</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php
                                        tampilPemeriksaan('Debris', $dataOralUpdate['debrisOption'], $dataOralUpdate['debrisNote'], 1);
                                        tampilPemeriksaan('Plaque', $dataOralUpdate['plaqueOption'], $dataOralUpdate['plaqueNote'], 1);
                                        tampilPemeriksaan('Calculus', $dataOralUpdate['calculusOption'], $dataOralUpdate['calculusNote'], 1);
                                        tampilPemeriksaan('Gingiva', $dataOralUpdate['gingivaOption'], $dataOralUpdate['gingivaNote'], 1);
                                        tampilPemeriksaan('Mucosae', $dataOralUpdate['mucosaeOption'], $dataOralUpdate['mucosaeNote'], 1);
                                        tampilPemeriksaan('Jaw Relation', $dataOralUpdate['jawRelationOption'], $dataOralUpdate['jawRelationNote'], 1);
                                        ?>
                                      </tbody>
                                    </table>

                                    <h5><u>SUPPORTING</u></h5>
                                    <table class="table table-bordered">
                                      <thead>
                                        <tr>
                                          <th>Examination</th>
                                          <th>Value</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php
                                        tampilPemeriksaan('Percussion', $dataSupportingUpdate['percussion'], '', 2);
                                        tampilPemeriksaan('Pressure', $dataSupportingUpdate['pressure'], '', 2);
                                        tampilPemeriksaan('Palpation', $dataSupportingUpdate['palpation'], '', 2);
                                        tampilPemeriksaan('Mobility', $dataSupportingUpdate['mobility'], '', 2);
                                        ?>
                                      </tbody>
                                    </table>
                                    <h5><u>VITALITY</u></h5>
                                    <table class="table table-bordered">
                                      <thead>
                                        <tr>
                                          <th>Examination</th>
                                          <th>Value</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php
                                        tampilPemeriksaan('Tooth', $dataVitalityUpdate['tooth'], '', 2);
                                        tampilPemeriksaan('Probe Test', $dataVitalityUpdate['probeTest'], '', 2);
                                        tampilPemeriksaan('Heat', $dataVitalityUpdate['heat'], '', 2);
                                        tampilPemeriksaan('Cold', $dataVitalityUpdate['cold'], '', 2);
                                        ?>
                                      </tbody>
                                    </table>
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <p>
                                    <h5><u>DIAGNOSIS ICD - 10</u></h5>
                                    </p>
                                    <ol>
                                        <?php
                                        $daftarICD10 = statementWrapper(
                                            DML_SELECT_ALL,
                                            'SELECT 
                                                pasien_icd_10_klinik.*,
                                                icd_10.diagnosis,
                                                icd_10.kode
                                            FROM 
                                                pasien_icd_10_klinik
                                                INNER JOIN icd_10 ON pasien_icd_10_klinik.idICD10 = icd_10.idICD10
                                            WHERE 
                                                pasien_icd_10_klinik.kodeAntrian = ? 
                                                ORDER BY pasien_icd_10_klinik.idPasienICD10 ASC',
                                                            [$kodeAntrian]
                                                        );

                                                        $diagnosisNonICD10 = statementWrapper(
                                                            DML_SELECT,
                                                            'SELECT 
                                                pasien_non_icd_10_klinik.diagnosis
                                            FROM 
                                                pasien_non_icd_10_klinik
                                            WHERE 
                                                pasien_non_icd_10_klinik.kodeAntrian = ? ',
                                                            [$kodeAntrian]
                                                        )['diagnosis'];

                                        foreach ($daftarICD10 as $index => $ICD10) {
                                        ?>
                                            <li>
                                                <strong>(<?= $ICD10['kode'] ?>)</strong> <?= $ICD10['diagnosis']; ?>
                                            </li>
                                        <?php
                                        }
                                        ?>
                                    </ol>
                                    <p>
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