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
            admisi.namaAdmisi,
            pasien_antrian_satusehat.waktuKedatangan 
        FROM
            pasien_antrian
            INNER JOIN klinik ON pasien_antrian.idKlinik = klinik.idKlinik
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
            INNER JOIN pasien_antrian_satusehat ON pasien_antrian.kodeAntrian = pasien_antrian_satusehat.kodeAntrian
            LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
        WHERE 
            pasien_antrian.kodeAntrian=?',
        [$kodeAntrian],
    );

    $waktuPeriksa = explode(" ", $dataUpdate['waktuKedatangan']) ?? '-';

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
        [$kodeRM, $kodeAntrian, 'Surat Rapid Test Antigen']
    );

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);

    $kasir = getNamaPegawai($db, $dataCekUser['idPegawai'])['namaPegawai'];
    $umur = umur($dataUpdate['tanggalLahir']);

    $query = rawurlencode(enkripsi(http_build_query([
        'kodeRM' => $result['kodeRM'],
        'kodeAntrian' => $result['kodeAntrian'],
        'jenisSurat' => 'Surat Rapid Test Antigen',
    ]), secretKey()));

    $qr_text = 'https://' . preg_replace('/^www./', '', $_SERVER['SERVER_NAME']) . BASE_URL_HTML . '/qr_validator/?param=' . $query;
    $qr_filename = escapeFilename("QRVALIDATOR_{$result['kodeAntrian']}_Surat_Rapid_Test_Antigen");


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
                                                <td>: <?= $dataUpdate['kodeRM'] ?></td>
                                            </tr>
                                            <tr>
                                                <td>Date</td>
                                                <td>: <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></td>
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
                                                <td>: <?= $dataUpdate['namaPasien'] ?></td>
                                            </tr>
                                            <tr>
                                                <td>ID</td>
                                                <td>: <?= $dataUpdate['noIdentitas'] ?></td>
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
                                                <td>: <?= tanggalTerbilang($dataUpdate['tanggalLahir']) ?> / <?= $umur['umur'] ?> Tahun</td>
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
                                <div class="col-sm-6">
                                    <table>
                                        <tr>
                                            <td>ID Sampel (<span class="english"><i>Sample ID</i></span>)</td>
                                            <td>:</td>
                                            <td><?= $dataSurat['idSampel'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Sampel (<span class="english"><i>Sample</i></span>)</td>
                                            <td>:</td>
                                            <td><?= $dataSurat['sampel'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal Pemeriksaan (<span class="english"><i>Testing Date</i></span>)</td>
                                            <td>:</td>
                                            <td><?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?> (<span class="english"><i><?= tanggalTerbilangEng($dataUpdate['tanggalPendaftaran']) ?></i></span>)</td>
                                        </tr>
                                        <tr>
                                            <td>Waktu Pemeriksaan (<span class="english"><i>Testing Time</i></span>)</td>
                                            <td>:</td>
                                            <td><?= $waktuPeriksa[1] ?> WITA</td>
                                        </tr>
                                        <tr>
                                            <td>Lokasi Pemeriksaan (<span class="english"><i>Testing Location</i></span>)</td>
                                            <td>:</td>
                                            <td><?= $dataUpdate['nama'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Dokter Pemeriksa (<span class="english"><i>Attending Physician</i></span>)</td>
                                            <td>:</td>
                                            <td><?= $dokter ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-sm-12">
                                    <table class="table table-bordered text-center">
                                        <thead>
                                            <th>Jenis Pemeriksaan<br><span class="english">(<i>Test</i>)</span></th>
                                            <th>Hasil<br><span class="english">(<i>Result</i>)</span></th>
                                            <th>Nilai Rujukan<br><span class="english">(<i>Reference Value</i>)</span></th>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?= $dataSurat['jenisPemeriksaan'] ?></td>
                                                <td><b><?= $dataSurat['hasilPemeriksaan'] ?> (<i><?= ubahToEng($dataSurat['hasilPemeriksaan']) ?></i>)</b></td>
                                                <td><?= $dataSurat['nilaiRujukan'] ?> (<i><?= ubahToEng($dataSurat['nilaiRujukan']) ?></i>)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <strong>Interpretasi : <?= $dataSurat['hasilPemeriksaan'] ?></strong>
                                    <br>
                                    <span class="english"><strong><i>Interpretation : <?= ubahToEng($dataSurat['hasilPemeriksaan']) ?></i></strong></span>
                                    <br><br>
                                    <?php
                                    if ($dataSurat['hasilPemeriksaan'] == 'Positif') {
                                    ?>
                                        <strong>Saran (<span class="english"><i>Recommendation</i></span>) :</strong>
                                        <ol class="text-justify">
                                            <li>
                                                Pemeriksaan konfirmasi dengan pemeriksaan RT- PCR.<br>
                                                <span class="english"><i>Do confirmation examination by undergo RT- PCR examination.</i></span>
                                            </li>
                                            <li>
                                                Lakukan karantina atau isolasi sesuai dengan ketentuan pemerintah.<br>
                                                <span class="english"><i>Do self quarantine or self isolation in accordance with government regulations.</i></span>
                                            </li>
                                            <li>
                                                Menerapkan PHBS (Perilaku Hidup Bersih dan Sehat : mencuci tangan, menerapkanetika etika batuk, menggunakan masker saat sakit, menjaga stamina) dan jaga jarak.<br>
                                                <span class="english"><i>Applying PHBS (clean and healthy living behavior: washing hands, applying cough etiquette, wearing a mask when sick, maintaining stamina) and maintaining distance.</i></span>
                                            </li>
                                        </ol>
                                    <?php
                                    } else {
                                    ?>
                                        <strong>Catatan (<span class="english"><i>Note</i></span>) :</strong>
                                        <ol class="text-justify">
                                            <li>
                                                Hasil negatif tidak menyingkirkan kemungkinan terinfeksi <i>SARS-CoV-2</i> sehingga masih berisiko menularkan ke orang lain.<br>
                                                <span class="english"><i>Negative results do not rule out the possibility of being infected with SARS-CoV-2 so there is still a risk of transmitting it to other people.</i></span>
                                            </li>
                                            <li>
                                                Hasil negatif dapat terjadi pada kondisi kuantitas antigen pada spesimen dibawah level deteksi alat.<br>
                                                <span class="english"><i>Negative results can occur when the antigen quantity in the specimen is below the device detection level.</i></span>
                                            </li>
                                        </ol>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <div class="col-sm-6">
                                    <b>Bali, <?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></b><br>
                                    <span>Dokter Pemeriksa</span>
                                    <br>
                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png">
                                    <br>
                                    <span class="text-underline"><?= $dokter ?></span>
                                    <br>
                                    <span> No SIP : <?= $noSIP ?></span>
                                </div>

                                <!-- <div class="col-sm-12">
                    <br><b>NB:</b><br>
                    <ol>
                        <li>
                            Kementerian Kesehatan Republik Indonesia. 2020. Pedoman Pencegahan dan Pengendalian Coronavirus Disease (COVID-19), revisi 05.<br>
                            <span class="english"><i>Ministry of Health of the Republic of Indonesia. 2020. Guidelines for the Prevention and Control of Coronavirus Disease (COVID-19), revision 05.</i></span>
                        </li>
                        <li>
                            Panduan Tatalaksana Pemeriksaan Antigen Rapid Test SARS-CoV-2 Perhimpunan Dokter Spesialis Patologi Klinik dan Kedokteran Laboratorium Indonesia (PDS PatKLin).<br>
                            <span class="english"><i>Guidelines for the Management of the SARS-CoV-2 Antigen Rapid Test for the Association of Indonesian Clinical Pathology and Laboratory Medicine Specialists (PDS PatKLin).</i></span>
                        </li>
                    </ol>
                </div> -->

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