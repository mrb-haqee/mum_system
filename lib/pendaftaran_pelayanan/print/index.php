<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasicurrency.php";
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

    ['namaPegawai' => $dokter, 'noSIP' => $noSIP, 'fileTTD' => $ttdDokter] = getNamaPegawai($db, $dataUpdate['idDokter']);
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

    $gender = [
        'Laki-laki' => 'Male',
        'Perempuan' => 'Female'
    ];

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
                                <div class="col-sm-5">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td>Patient</td>
                                                <td><?= $dataUpdate['namaPasien'] ?> (<?= $gender[$dataUpdate['jenisKelamin']] ?>)</td>
                                            </tr>
                                            <tr>
                                                <td>Birthday / Age</td>
                                                <td><?= formatTglEng($dataUpdate['tanggalLahir']) ?> / <?= $umur['umur'] ?> Years Old</td>
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
                                                <td>MR Code</td>
                                                <td><?= $kodeRM ?></td>
                                            </tr>
                                            <tr>
                                                <td>Date</td>
                                                <td><?= formatTglEng($dataUpdate['tanggalPendaftaran']) ?></td>
                                            </tr>
                                            <tr>
                                                <td>Phone Number</td>
                                                <td><?= $dataUpdate['noTelp'] ?></td>
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

                                    if ($daftarICD10) {
                                    ?>
                                        <div class="container-fluid border rounded py-3 px-4">
                                            <h5>DIAGNOSIS ICD - 10</h5>
                                            <hr>
                                            <ol>
                                                <?php

                                                foreach ($daftarICD10 as $index => $ICD10) {
                                                    switch ($index) {
                                                        case 0:
                                                            $color = 'info';
                                                            break;
                                                        case 1:
                                                            $color = 'success';
                                                            break;

                                                        default:
                                                            $color = 'warning';
                                                            break;
                                                    }
                                                ?>
                                                    <li>
                                                        <strong>(<?= $ICD10['kode'] ?>)</strong> <?= $ICD10['diagnosis']; ?>
                                                    </li>
                                                <?php
                                                }
                                                ?>
                                            </ol>
                                        </div>
                                    <?php
                                    }

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

                                    if ($diagnosisNonICD10) {
                                    ?>
                                        <div class="container-fluid border rounded py-3 px-4">
                                            <h5>DIAGNOSIS NON ICD - 10</h5>
                                            <hr>
                                            <?= $diagnosisNonICD10 ?? '-'; ?>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                    <hr>
                                    <?php
                                    $grandTotal = [
                                        'obat' => 0,
                                        'alkes' => 0,
                                        'tindakan' => 0,
                                        'prosedur_laboratorium' => 0,
                                        'escort' => 0,
                                    ];

                                    $grandTotalHPP = 0;

                                    ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width: 13%;">NO</th>
                                                <th>ITEM</th>
                                                <th>QTY</th>
                                                <th class="text-center">PRICE</th>
                                                <th class="text-center">SUB TOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php

                                            $n = 1;

                                            $data = statementWrapper(
                                                DML_SELECT_ALL,
                                                'SELECT 
                                                    pasien_tindakan_klinik.*,
                                                    tindakan.nama,
                                                    admisi.namaAdmisi
                                                FROM 
                                                    pasien_tindakan_klinik
                                                    LEFT JOIN admisi ON pasien_tindakan_klinik.idAdmisi = admisi.idAdmisi
                                                    INNER JOIN tindakan ON tindakan.idTindakan = pasien_tindakan_klinik.idTindakan
                                                WHERE 
                                                    pasien_tindakan_klinik.kodeAntrian = ?
                                                    AND pasien_tindakan_klinik.idPaketLaboratorium IS NULL
                                                ',
                                                [
                                                    $kodeAntrian
                                                ]
                                            );

                                            if ($data) {
                                            ?>
                                                <tr>
                                                    <td colspan="5" class=""><i class="fas fa-screwdriver text-dark pr-3" style="font-size: 1rem;"></i> <strong>MEDICAL PROCEDURES</strong></td>
                                                </tr>
                                                <?php

                                                foreach ($data as $row) {
                                                ?>
                                                    <tr>
                                                        <td class="align-middle"><?= $n ?></td>
                                                        <td class="align-middle"><?= $row['nama'] ?></td>
                                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                                        <td class="text-right">
                                                            <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                            <span class="text-muted font-weight-bold">

                                                            </span>
                                                        </td>
                                                        <td class="text-right align-middle">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                                    </tr>
                                                <?php
                                                    $grandTotal['tindakan'] += $row['subTotal'];

                                                    $n++;
                                                }
                                                ?>
                                                <tr>
                                                    <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                                                    <td class="text-right"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['tindakan']) ?></strong></td>
                                                </tr>
                                            <?php
                                            }

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
                                                <tr>
                                                    <td colspan="5" class=""><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>MEDICINES</strong></td>
                                                </tr>
                                                <?php


                                                foreach ($data as $row) {
                                                ?>
                                                    <tr>
                                                        <td class="align-middle"><?= $n ?></td>
                                                        <td class="align-middle"><?= $row['nama'] ?></td>
                                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                                        <td class="text-right">
                                                            <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                            <span class="text-muted font-weight-bold">

                                                            </span>
                                                        </td>
                                                        <td class="text-right align-middle">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                                    </tr>
                                                <?php
                                                    $grandTotal['obat'] += $row['subTotal'];
                                                    $grandTotalHPP += $row['subTotalHPP'];

                                                    $n++;
                                                }
                                                ?>
                                                <tr>
                                                    <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                                                    <td class="text-right"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['obat']) ?></strong></td>
                                                </tr>
                                            <?php
                                            }

                                            $data = statementWrapper(
                                                DML_SELECT_ALL,
                                                'SELECT 
                                                    pasien_alkes_klinik.*,
                                                    alkes.nama,
                                                    admisi.namaAdmisi
                                                FROM 
                                                    pasien_alkes_klinik
                                                    LEFT JOIN admisi ON pasien_alkes_klinik.idAdmisi = admisi.idAdmisi
                                                    INNER JOIN alkes ON alkes.idAlkes = pasien_alkes_klinik.idAlkes
                                                WHERE 
                                                    pasien_alkes_klinik.kodeAntrian = ?
                                                ',
                                                [
                                                    $kodeAntrian
                                                ]
                                            );

                                            $n = 1;

                                            if ($data) {

                                            ?>
                                                <tr>
                                                    <td colspan="5" class=""><i class="fas fa-syringe text-dark pr-3" style="font-size: 1rem;"></i> <strong>MEDICAL DISPOSABLES</strong></td>
                                                </tr>
                                                <?php

                                                foreach ($data as $row) {
                                                ?>
                                                    <tr>
                                                        <td class="align-middle"><?= $n ?></td>
                                                        <td class="align-middle"><?= $row['nama'] ?></td>
                                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                                        <td class="text-right">
                                                            <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                            <span class="text-muted font-weight-bold">

                                                            </span>
                                                        </td>
                                                        <td class="text-right align-middle">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                                    </tr>
                                                <?php
                                                    $grandTotal['alkes'] += $row['subTotal'];
                                                    $grandTotalHPP += $row['subTotalHPP'];

                                                    $n++;
                                                }
                                                ?>
                                                <tr>
                                                    <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                                                    <td class="text-right"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['alkes']) ?></strong></td>
                                                </tr>
                                            <?php
                                            }

                                            $data = statementWrapper(
                                                DML_SELECT_ALL,
                                                'SELECT 
                                                    pasien_paket_laboratorium_klinik.*,
                                                    paket_laboratorium.nama,
                                                    admisi.namaAdmisi
                                                FROM 
                                                    pasien_paket_laboratorium_klinik
                                                    LEFT JOIN admisi ON pasien_paket_laboratorium_klinik.idAdmisi = admisi.idAdmisi
                                                    INNER JOIN paket_laboratorium ON paket_laboratorium.idPaketLaboratorium = pasien_paket_laboratorium_klinik.idPaketLaboratorium
                                                WHERE 
                                                    pasien_paket_laboratorium_klinik.kodeAntrian = ?
                                                ',
                                                [
                                                    $kodeAntrian
                                                ]
                                            );

                                            $isTitleShow = false;

                                            $n = 1;

                                            if ($data) {
                                                $isTitleShow = true;
                                            ?>
                                                <tr>
                                                    <td colspan="5" class=""><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>LABORATORY PROCEDURES</strong></td>
                                                </tr>
                                                <?php

                                                foreach ($data as $row) {
                                                    $detail = statementWrapper(
                                                        DML_SELECT_ALL,
                                                        "SELECT
                                                            *
                                                        FROM
                                                        (
                                                            (
                                                                SELECT 
                                                                    prosedur_laboratorium.nama,
                                                                    'Prosedur Laboratorium' as tipe
                                                                FROM 
                                                                    pasien_laboratorium_klinik
                                                                    INNER JOIN prosedur_laboratorium ON prosedur_laboratorium.idProsedurLaboratorium = pasien_laboratorium_klinik.idProsedurLaboratorium
                                                                WHERE 
                                                                    pasien_laboratorium_klinik.kodeAntrian = ?
                                                                    AND pasien_laboratorium_klinik.idPaketLaboratorium = ?
                                                            )
                                                            UNION ALL
                                                            (
                                                                SELECT 
                                                                    tindakan.nama,
                                                                    'Tindakan' as tipe
                                                                FROM 
                                                                    pasien_tindakan_klinik
                                                                    INNER JOIN tindakan ON tindakan.idTindakan = pasien_tindakan_klinik.idTindakan
                                                                WHERE 
                                                                    pasien_tindakan_klinik.kodeAntrian = ?
                                                                    AND pasien_tindakan_klinik.idPaketLaboratorium = ?
                                                            )
                                                           
                                                        ) detail_paket_lab
                                                        ",
                                                        [
                                                            $kodeAntrian,
                                                            $row['idPaketLaboratorium'],

                                                            $kodeAntrian,
                                                            $row['idPaketLaboratorium']
                                                            
                                                            // $kodeAntrian,
                                                            // $row['idPaketLaboratorium'],
                                                            // 'Prosedur Laboratorium'
                                                        ]
                                                    );
                                                ?>
                                                    <tr>
                                                        <td class="align-middle"><strong><?= $n ?></strong></td>
                                                        <td class="align-middle"><strong><?= $row['nama'] ?></strong></td>
                                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                                        <td class="text-right">
                                                            <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                            <span class="text-muted font-weight-bold">

                                                            </span>
                                                        </td>
                                                        <td class="text-right align-middle">
                                                            Rp <?= ubahToRupiahDesimal($row['subTotal']) ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $grandTotal['prosedur_laboratorium'] += $row['subTotal'];

                                                    foreach ($detail as $key => $value) {
                                                    ?>
                                                        <tr>
                                                            <?php
                                                            if ($key === 0) {
                                                                $rowspan = count($detail) < 0 ? 1 : count($detail);
                                                            ?>
                                                                <td rowspan="<?= $rowspan ?>" class="table-active"></td>
                                                            <?php
                                                            }
                                                            ?>
                                                            <td class="align-middle" colspan="4"><?= $value['nama'] ?></td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    $n++;
                                                }
                                            }

                                            $data = statementWrapper(
                                                DML_SELECT_ALL,
                                                'SELECT 
                                                    pasien_laboratorium_klinik.*,
                                                    prosedur_laboratorium.nama,
                                                    admisi.namaAdmisi
                                                FROM 
                                                    pasien_laboratorium_klinik
                                                    LEFT JOIN admisi ON pasien_laboratorium_klinik.idAdmisi = admisi.idAdmisi
                                                    INNER JOIN prosedur_laboratorium ON prosedur_laboratorium.idProsedurLaboratorium = pasien_laboratorium_klinik.idProsedurLaboratorium
                                                WHERE 
                                                    pasien_laboratorium_klinik.kodeAntrian = ?
                                                    AND pasien_laboratorium_klinik.idPaketLaboratorium IS NULL
                                                ',
                                                [
                                                    $kodeAntrian
                                                ]
                                            );

                                            if ($data) {

                                                if (!$isTitleShow) {
                                                    ?>
                                                    <tr>
                                                        <td colspan="5" class=""><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>LABORATORY PROCEDURES</strong></td>
                                                    </tr>
                                                <?php
                                                }

                                                foreach ($data as $row) {
                                                ?>
                                                    <tr>
                                                        <td class="align-middle"><?= $n ?></td>
                                                        <td class="align-middle"><?= $row['nama'] ?></td>
                                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                                        <td class="text-right">
                                                            <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                            <span class="text-muted font-weight-bold">

                                                            </span>
                                                        </td>
                                                        <td class="text-right align-middle">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                                    </tr>
                                            <?php
                                                    $grandTotal['prosedur_laboratorium'] += $row['subTotal'];
                                                    $n++;
                                                }
                                            }

                                            ?>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                                                <td class="text-right"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['prosedur_laboratorium']) ?></strong></td>
                                            </tr>
                                            <?php


                                            $data = statementWrapper(
                                                DML_SELECT_ALL,
                                                'SELECT 
                                                    pasien_escort_klinik.*,
                                                    escort.nama,
                                                    admisi.namaAdmisi
                                                FROM 
                                                    pasien_escort_klinik
                                                    LEFT JOIN admisi ON pasien_escort_klinik.idAdmisi = admisi.idAdmisi
                                                    INNER JOIN escort ON escort.idEscort = pasien_escort_klinik.idEscort
                                                WHERE 
                                                    pasien_escort_klinik.kodeAntrian = ?
                                                ',
                                                [
                                                    $kodeAntrian
                                                ]
                                            );

                                            $n = 1;

                                            if ($data) {

                                            ?>
                                                <tr>
                                                    <td colspan="5" class=""><i class="fas fa-ambulance text-dark pr-3" style="font-size: 1rem;"></i> <strong>ESCORT</strong></td>
                                                </tr>
                                                <?php


                                                foreach ($data as $row) {
                                                ?>
                                                    <tr>
                                                        <td class="align-middle"><?= $n ?></td>
                                                        <td class="align-middle"><?= $row['nama'] ?></td>
                                                        <td class="align-middle"><?= $row['qty'] ?></td>
                                                        <td class="text-right">
                                                            <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                            <span class="text-muted font-weight-bold">

                                                            </span>
                                                        </td>
                                                        <td class="text-right align-middle">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                                    </tr>
                                                <?php
                                                    $grandTotal['escort'] += $row['subTotal'];
                                                    $n++;
                                                }
                                                ?>
                                                <tr>
                                                    <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                                                    <td class="text-right"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['escort']) ?></strong></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                            <tr>
                                                <td colspan="5" class=""><i class="fas fa-file-invoice-dollar text-dark pr-3" style="font-size: 1rem;"></i> <strong>PAYMENT</strong></td>
                                            </tr>
                                            <?php
                                            $sumGrandTotal = array_sum(array_values($grandTotal));
                                            ?>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>GRAND TOTAL</strong></td>
                                                <td class="text-right">
                                                    <strong>Rp <?= ubahToRupiahDesimal($sumGrandTotal) ?></strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-right align-middle"><strong>DISCOUNT</strong></td>
                                                <td class="text-right">
                                                    Rp <?= ubahToRupiahDesimal($dataUpdate['diskon'] ?? 0) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>PAYABLE</strong></td>
                                                <td class="text-right"><strong>Rp <?= ubahToRupiahDesimal($dataUpdate['payable'] ?? $sumGrandTotal) ?></td>
                                            </tr>
                                            <?php
                                            $payments = statementWrapper(
                                                DML_SELECT_ALL,
                                                'SELECT
                                                    *
                                                FROM
                                                    pasien_deposit
                                                WHERE
                                                    kodeAntrian = ?
                                                ORDER BY idPasienDeposit ASC
                                                ',
                                                [
                                                    $kodeAntrian
                                                ]
                                            );
                                            $currencies = getCurrencyList();
                                            if ($payments) {
                                                $totalTerbayar = 0;
                                            ?>
                                                <tr>
                                                    <th class="text-center">METODE</th>
                                                    <th colspan="3" class="text-center">DETAIL</th>
                                                    <th class="text-center">NOMINAL PAID</th>
                                                </tr>
                                                <?php
                                                foreach ($payments as $index => $payment) {
                                                    $detailCurrency = $currencies[$payment['currency']];

                                                    switch ($payment['metodePembayaran']) {
                                                        case 'Tunai':
                                                            $metode = 'Cash';
                                                            $detail = 'CASH PAYMENT';
                                                            break;
                                                        case 'Non Tunai':
                                                            // $metode = 'Debit / Credit Card';
                                                            $metode = is_null($payment['idTransferEDC']) ? 'Transfer' : 'Debit / Credit Card';
                                                            $detail = statementWrapper(
                                                                DML_SELECT,
                                                                "SELECT 
                                                                    CASE 
                                                                        WHEN pasien_deposit.idTransferEDC IS NULL THEN CONCAT('Transfer ', tujuan_transfer.vendor)
                                                                        ELSE CONCAT_WS(' ', tujuan_transfer_edc.namaMesin) 
                                                                    END as detail
                                                                FROM 
                                                                    pasien_deposit
                                                                    INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                                                    LEFT JOIN tujuan_transfer_edc ON pasien_deposit.idTransferEDC = tujuan_transfer_edc.idTransferEDC
                                                                WHERE 
                                                                    pasien_deposit.idPasienDeposit = ?",
                                                                [$payment['idPasienDeposit']]
                                                            )['detail'];
                                                            // $detail = statementWrapper(
                                                            //     DML_SELECT,
                                                            //     "SELECT CONCAT_WS(' ', noReferensi, vendor, 'a.n.', atasNama) as detail FROM tujuan_transfer WHERE kodeTujuanTransfer = ?",
                                                            //     [$payment['kodeReferensi']]
                                                            // )['detail'];
                                                    break;
                                                            break;
                                                        case 'Insurance':
                                                            $metode = 'Insurance';
                                                            $detail = statementWrapper(
                                                                DML_SELECT,
                                                                "SELECT nama as detail FROM asuransi WHERE kodeAsuransi = ?",
                                                                [$payment['kodeReferensi']]
                                                            )['detail'];
                                                            break;
                                                        case 'Klien':
                                                            $metode = 'Client';
                                                            $detail = statementWrapper(
                                                                DML_SELECT,
                                                                "SELECT CONCAT_WS(' ', NIKHotel,' ',namaHotel) as detail FROM hotel WHERE kodeHotel = ?",
                                                                [$payment['kodeReferensi']]
                                                            )['detail'];
                                                            break;

                                                        default:
                                                            $detail = 'UNIDENTIFIED PAYMENT';
                                                            break;
                                                    }
                                                ?>
                                                    <tr>
                                                        <td class="align-middle text-center"><?= $metode; ?></td>
                                                        <td colspan="3" class="align-middle text-center"><?= $detail; ?></td>
                                                        <td class="align-middle text-right">
                                                            <span class="d-block">
                                                                Rp <?= ubahToRupiahDesimal($payment['nominal']); ?>
                                                            </span>
                                                            <?php
                                                            if ($payment['currency'] !== CURRENCY_DEFAULT) {
                                                            ?>
                                                                <strong class="text-muted">
                                                                    <?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($payment['exchangeNominal']); ?> (<?= $payment['currency']; ?>)
                                                                </strong>
                                                            <?php
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-sm-12">
                                    <table style="width: 100%;">
                                        <tbody>
                                            <tr>
                                                <td style="width: 45%; text-align:center;"><strong>ATTENDING PHYSICIAN</strong></td>
                                                <td style="width: 10%; text-align:center;" rowspan="3">

                                                </td>
                                                <td style="width: 45%; text-align:center;">
                                                    <strong>
                                                        PAYABLE TO
                                                    </strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="height:100px;width: 45%; text-align:center;">
                                                    <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png" style="opacity:.8">
                                                </td>

                                                <td style="height:80px;width: 45%; text-align:center;">
                                                    <?php

                                                    if ($dataUpdate['fileTTDPasien'] === NULL) {
                                                        $attrFile = '';
                                                    } else {
                                                        $BASE_DIR = ABS_PATH_FILE_UPLOAD_DIR . '/cust-signature';

                                                        $filePath =  "$BASE_DIR/{$dataUpdate['fileTTDPasien']}";
                                                        $file = file_get_contents($filePath);

                                                        $attrFile = 'data-file="data:image/png;base64,' . base64_encode($file) . '"';
                                                    }
                                                    ?>
                                                    <canvas id="sign-pad-pasien" <?= $attrFile; ?>></canvas>
                                                    <div style="text-align:center">
                                                        <span class="badge badge-danger px-2 py-1" style="cursor:pointer" id="clear-sign-pad_Pasien">CLEAR</span>
                                                        <?php
                                                        if ($dataUpdate['fileTTDPasien'] === NULL) {
                                                            $btnClass = 'badge-primary';
                                                        } else {
                                                            $btnClass = 'badge-info';
                                                        }
                                                        ?>
                                                        <span class="badge <?= $btnClass ?> px-2 py-1" style="cursor:pointer" id="save-sign-pad_Pasien">SAVE</span>
                                                    </div>
                                                    <?php

                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 45%; text-align:center;">
                                                    <?= $dokter ?>
                                                    <br>
                                                    NO SIP : <?= $noSIP ?>
                                                </td>

                                                <td style="width: 45%; text-align:center;">
                                                    <?php
                                                    echo $dataUpdate['namaPasien'];
                                                    ?>
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
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js" integrity="sha256-uGyFpu2wVfZ4h/KOsoT+7NdggPAEU2vXx0oNPEYq3J0=" crossorigin="anonymous"></script>

        <script>
            window.onafterprint = function() {
                window.close();
            }

            const listSignPad = {
                Pasien: document.querySelector('#sign-pad-pasien')
            };


            Object.keys(listSignPad).forEach((jenisTTD) => {
                const padCanvas = listSignPad[jenisTTD];
                let signPad;

                if (padCanvas) {
                    signPad = new SignaturePad(padCanvas, {
                        dotSize: 0.5
                    });

                    const dataURI = padCanvas.dataset.file;

                    if (dataURI) {
                        signPad.fromDataURL(dataURI, {
                            ratio: 1
                        });
                    }

                    const clearBtn = document.querySelector('#clear-sign-pad_' + jenisTTD);
                    const saveBtn = document.querySelector('#save-sign-pad_' + jenisTTD);

                    clearBtn.addEventListener('click', function(e) {
                        signPad.clear();
                    })

                    saveBtn.addEventListener('click', async function(e) {
                        const file = signPad.toDataURL();

                        const tokenCSRFForm = $('#tokenCSRFForm').val();
                        const kodeAntrian = $('#kodeAntrian').val();

                        const formData = new FormData();

                        formData.append('fileTTD', file);
                        formData.append('tokenCSRFForm', tokenCSRFForm);
                        formData.append('kodeAntrian', kodeAntrian);
                        formData.append('jenisTTD', jenisTTD);

                        const response = await $.ajax({
                            url: 'proses-ttd.php',
                            method: 'POST',
                            enctype: 'multipart/form-data',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                        });

                        const {
                            status,
                            pesan
                        } = response;

                        if (status) {
                            saveBtn.classList.remove('badge-info', 'badge-primary', 'badge-success');

                            saveBtn.classList.add('badge-success');
                            saveBtn.textContent = 'TERSIMPAN';

                            setTimeout(() => {
                                saveBtn.classList.add('badge-info');
                                saveBtn.textContent = 'SAVE';
                            }, 2000);
                        } else {
                            alert(pesan);
                        }

                    })
                }
            });
        </script>
    </body>

    </html>
<?php
}
?>