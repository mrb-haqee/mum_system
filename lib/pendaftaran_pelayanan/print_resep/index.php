<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiqrcode.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";
include_once "{$constant('BASE_URL_PHP')}/{$constant('MAIN_DIR')}/fungsinavigasi.php";

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
    header('location:' . BASE_URL_HTML . '/?flagNotif=gagal');
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
        $kodeAntrian = $result['kodeAntrian'] ?? nomorUrut($db, 'antrian_klinik', $idUserAsli);
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

    ['namaPegawai' => $dokter,'noSIP' => $noSIP, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);
    $kasir = getNamaPegawai($db, $dataCekUser['idPegawai'])['namaPegawai'];
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Invoice',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Invoice");

    generateQRValidator(
        $qr_text,
        $qr_filename
    );

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Invoice</title>
        <!-- Google Font: Source Sans Pro -->
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

        <style type="text/css">
            @media print {
                .newPage {
                    page-break-after: always;
                }

                #save-sign-pad_Dokter,
                #clear-sign-pad_Dokter,
                #save-sign-pad_Pasien,
                #clear-sign-pad_Pasien {
                    display: none !important;
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

            /* #sectionBody table thead th {
                background-color: #4a171e;
                color: white;
            } */

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
            <input type="hidden" id="tokenCSRFForm" value="<?= $tokenCSRF ?>">
            <input type="hidden" id="kodeAntrian" value="<?= $kodeAntrian ?>">

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
                <div class="col-sm-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td>Patient</td>
                                <td>: <?= $dataUpdate['namaPasien'] ?></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td>: <?= $dataUpdate['alamat'] ?></td>
                            </tr>
                            <tr>
                                <td>Phone Number</td>
                                <td>: <?= $dataUpdate['noTelp'] ?></td>
                            </tr>
                            <tr>
                                <td>Birthday / Age</td>
                                <td>: <?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?> / <?= $umur['umur'] ?> Years</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row" id="sectionBody">
                <div class="col-sm-12">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>ITEM</th>
                                <th>QTY</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            $data = statementWrapper(
                                DML_SELECT_ALL,
                                'SELECT 
                                    pasien_obat_klinik.*,
                                    obat.nama,
                                    admisi.namaAdmisi
                                FROM 
                                    pasien_obat_klinik
                                    LEFT JOIN admisi ON pasien_obat_klinik.idAdmisi = admisi.idAdmisi
                                    INNER JOIN obat ON obat.idObat = pasien_obat_klinik.idObat
                                WHERE 
                                    pasien_obat_klinik.kodeAntrian = ?
                                ',
                                [
                                    $kodeAntrian
                                ]
                            );

                            $n = 1;

                            if ($data) {
                            ?>
                                <?php


                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td class="align-middle"><?= $n ?></td>
                                        <td class="align-middle">
                                          <?= $row['nama'] ?><br>
                                          <?= wordwrap($row['keteranganDosis'],50,'<br>') ?>    
                                        </td>
                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                        
                                    </tr>
                                <?php
                                  $n++;
                                }
                                ?>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-sm-12">
                    <table style="width: 100%;">
                        <tbody>
                            <tr>
                                <td style="width: 45%;"><strong>ATTENDING PHYSICIAN</strong></td>
                                
                            </tr>
                            <tr>
                                <td style="height:100px;width: 45%;">
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png" style="opacity:.8">
                                </td>

                                
                            </tr>
                            <tr>
                                <td style="width: 45%;">
                                <?= $dokter ?>
                                <br>
                                NO SIP : <?= $noSIP?>
                                </td>

                               
                            </tr>
                        </tbody>
                    </table>
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
        </div>

        <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>

        <script>
            window.onafterprint = function() {
                window.close();
            }
        </script>
    </body>

    </html>
<?php
}
?>