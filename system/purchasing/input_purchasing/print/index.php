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

    extract($_POST, EXTR_SKIP);


    header("Content-type: application/pdf");
    header("Content-Disposition: attachment; filename=Daftar_Tunggakan_Per_" . date('m') . "_" . date('Y') . ".pdf");



?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Laporan Request Stock</title>
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
                font-size: 16px
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="row mb-4" id="sectionHeader">
                <div class="col-12">
                    <h4 class="mb-0 font-weight-bolder">LAPORAN DAFTAR STOCK</h4>
                    <div class="mb-3">THE MEDICAL PURCASHING</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 23%;">PERIODE</th>
                                <th style="width: 7%;">:</th>
                                <td style="width: 70%;">tgl awal s/d tgl akhir</td>
                            </tr>
                            <tr>
                                <th style="width: 23%;">INVENTORY</th>
                                <th style="width: 7%;">:</th>
                                <td style="width: 70%;">tipeInventory</td>
                            </tr>
                            <tr>
                                <th style="width: 23%;">KLINIK</th>
                                <th style="width: 7%;">:</th>
                                <td style="width: 70%;">klinik</td>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="row" id="sectionBody">

                <div class="col-12">

                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center align-middle" rowspan="2">NO</th>
                                <th class="text-center align-middle" rowspan="2">INVENTORY</th>
                                <th class="text-center align-middle" colspan="23">KLINIK</th>
                            </tr>
                            <tr>
                                <?php
                                foreach (range(5, 1) as $nama) {
                                ?>
                                    <td class="text-center align-middle">awdfaw</td>
                                <?php
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            foreach (range(5, 1) as $nama) {
                            ?>
                                <tr>
                                    <td>dawdaw</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>


            </div>

            <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js" integrity="sha256-uGyFpu2wVfZ4h/KOsoT+7NdggPAEU2vXx0oNPEYq3J0=" crossorigin="anonymous"></script>

            <script>
                window.onafterprint = function() {
                    window.close();
                }
            </script>
    </body>

    </html>
<?php } ?>