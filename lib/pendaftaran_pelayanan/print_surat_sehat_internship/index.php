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
        [$kodeRM, $kodeAntrian, 'Surat Sehat Internship']
    );

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter, 'alamatPegawai' => $alamat, 'NPAIDI' => $NPAIDI] = getNamaPegawai($db, $dataUpdate['idDokter']);

    $kasir = getNamaPegawai($db, $dataCekUser['idPegawai'])['namaPegawai'];
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Surat Sehat Internship',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Surat_Sehat_Internship");


    generateQRValidator(
        $qr_text,
        $qr_filename
    );

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Surat Sehat Internship</title>
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
                                  <h4 style="text-decoration: underline !important;">SURAT KETERANGAN SEHAT FISIK DAN MENTAL UNTUK DAPAT MELAKSANAKAN PRAKTIK KEDOKTERAN
                                  </h4>
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
                                    <strong>Yang bertandatangan di bawah ini :</strong>
                                  </p>
                                  <table>
                                    <tr>
                                      <td>Nama</td>
                                      <td>: <?= $dokter ?></td>
                                    </tr>
                                    <tr>
                                      <td>NPA IDI</td>
                                      <td>: <?= $NPAIDI ?></td>
                                    </tr>
                                    <tr>
                                      <td>No SIP</td>
                                      <td>: <?= $noSIP ?></td>
                                    </tr>
                                    <tr>
                                      <td>Jabatan</td>
                                      <td class="align-top">: 
                                        Dokter Pemeriksa Kesehatan di IDI Cabang Denpasar 
                                        (Surat Keputusan MENKES RI, No. 2052/MENKES/PER/X/2011)
                                      </td>
                                    </tr>
                                  </table>

                                  <p>
                                    <br>
                                    <strong>Menerangkan bahwa dokter di bawah ini :</strong>
                                  </p>
                                  <table>
                                    <tr>
                                      <td>Nama</td>
                                      <td>: <?= $dataUpdate['namaPasien'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>Tempat / Tanggal Lahir</td>
                                      <td>: <?= $dataUpdate['tempatLahir'] ?> / <?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?></td>
                                    </tr>
                                    <tr>
                                      <td>Umur</td>
                                      <td>: <?= $umur['umur'] ?> Years</td>
                                    </tr>
                                    <tr>
                                      <td>Alamat</td>
                                      <td>: <?= $dataUpdate['alamat'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>Spesialisasi</td>
                                      <td>: <?= $dataSurat['spesialisasi'] ?></td>
                                    </tr>
                                    <tr>
                                      <td>Hasil Pemeriksaan</td>
                                      <td>: <?= $dataSurat['hasilPemeriksaan'] ?></td>
                                    </tr>
                                  </table>
                                </div>
                                
                                <div class="col-sm-12">
                                    <br>
                                    <b>Bali, <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></b><br>
                                    <span>Authorized By</span>
                                    <br>
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                    <br>
                                    <span class="text-underline"><?= $dokter ?></span>
                                    <br>
                                    <span> NPA IDI : <?= $NPAIDI ?></span>
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