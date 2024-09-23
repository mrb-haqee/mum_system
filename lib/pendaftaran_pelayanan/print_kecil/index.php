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

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Print Invoice</title>
        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap">
        <link rel="stylesheet" href="css/custom.css">
    </head>

    <body>
        <div class="container">
            <input type="hidden" id="tokenCSRFForm" value="<?= $tokenCSRF ?>">
            <input type="hidden" id="kodeAntrian" value="<?= $kodeAntrian ?>">

            <div id="sectionHeader">

                <div style="text-align: center;">
                    <img src="<?= BASE_URL_HTML ?>/assets/media/misc/login-icon.png" alt="Login Wallpaper" style="width:35%;">
                </div>

                <table>
                    <tbody>
                        <tr>
                            <td><strong>ID. INV </strong></td>
                            <td style="padding-right: .20rem; padding-left: .20rem;">:</td>
                            <td><?= $dataUpdate['statusPembayaran'] === 'Sudah Bayar' ? $dataUpdate['kodeInvoice'] : '<strong>== QUOTATION ==</strong>' ?></td>
                        </tr>
                        <tr>
                            <td><strong>ADM. DATE </strong></td>
                            <td style="padding-right: .20rem; padding-left: .20rem;">:</td>
                            <td><?= tanggalTerbilang($dataUpdate['tanggalPendaftaran']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>PATIENT </strong></td>
                            <td style="padding-right: .20rem; padding-left: .20rem;">:</td>
                            <td><?= wordwrap($dataUpdate['namaPasien'], 30, '<br>') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="sectionBody">
                <div class="col-sm-12">
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
                    <table>
                        <thead>
                            <tr>
                                <td style="border-style: none; padding:.5rem" colspan="3"></td>
                            </tr>
                            <tr>
                                <td style="border-style: dashed none;" colspan="3"></td>
                            </tr>
                            <tr>
                                <td style="border-style: none; padding:.5rem" colspan="3"></td>
                            </tr>
                            <tr>
                                <th>NO</th>
                                <th>ITEM</th>
                                <th style="text-align: right;">SUB TOTAL</th>
                            </tr>
                            <tr>
                                <td style="border-style: none; padding:.5rem" colspan="3"></td>
                            </tr>
                            <tr>
                                <td style="border-style: dashed none;" colspan="3"></td>
                            </tr>
                            <tr>
                                <td style="border-style: none; padding:.5rem" colspan="3"></td>
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
                                <tr>
                                    <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>MEDICINE</strong></td>
                                </tr>
                                <?php


                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td class="align-middle" style="vertical-align: top;"><?= $n ?></td>
                                        <td class="align-middle">
                                            <?= $row['nama'] ?>
                                            <br><?= $row['qty'] ?> &times;
                                            <span class="d-block font-weight-bold"><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold"></span>
                                        </td>
                                        <td style="vertical-align: top;text-align:right"><br><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                    </tr>
                                <?php
                                    $grandTotal['obat'] += $row['subTotal'];
                                    $grandTotalHPP += $row['subTotalHPP'];

                                    $n++;
                                }
                                ?>
                                <tr>
                                    <td colspan="2"><strong>SUBTOTAL</strong></td>
                                    <td style="text-align: right;"><strong><?= ubahToRupiahDesimal($grandTotal['obat']) ?></strong></td>
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
                                    <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-syringe text-dark pr-3" style="font-size: 1rem;"></i> <strong>MEDICAL DISPOSABLES</strong></td>
                                </tr>
                                <?php



                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td class="align-middle" style="vertical-align: top;"><?= $n ?></td>
                                        <td class="align-middle">
                                            <?= $row['nama'] ?>
                                            <br><?= $row['qty'] ?> &times;
                                            <span class="d-block font-weight-bold"><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold"></span>
                                        </td>
                                        <td style="vertical-align: top;text-align:right"><br><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                    </tr>
                                <?php
                                    $grandTotal['alkes'] += $row['subTotal'];
                                    $grandTotalHPP += $row['subTotalHPP'];

                                    $n++;
                                }
                                ?>
                                <tr>
                                    <td colspan="2"><strong>SUBTOTAL</strong></td>
                                    <td style="text-align: right;"><strong><?= ubahToRupiahDesimal($grandTotal['alkes']) ?></strong></td>
                                </tr>
                            <?php
                            }

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

                            $n = 1;

                            if ($data) {
                            ?>
                                <tr>
                                    <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-screwdriver text-dark pr-3" style="font-size: 1rem;"></i> <strong>MEDICAL PROCEDURES</strong></td>
                                </tr>
                                <?php


                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td class="align-middle" style="vertical-align: top;"><?= $n ?></td>
                                        <td class="align-middle">
                                            <?= $row['nama'] ?>
                                            <br><?= $row['qty'] ?> &times;
                                            <span class="d-block font-weight-bold"><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold"></span>
                                        </td>
                                        <td style="vertical-align: top;text-align:right"><br><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                    </tr>
                                <?php
                                    $grandTotal['tindakan'] += $row['subTotal'];

                                    $n++;
                                }
                                ?>
                                <tr>
                                    <td colspan="2"><strong>SUBTOTAL</strong></td>
                                    <td style="text-align: right;"><strong><?= ubahToRupiahDesimal($grandTotal['tindakan']) ?></strong></td>
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
                                    <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>LABORATORIUM PROCEDURE</strong></td>
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
                                            
                                        ]
                                    );
                                ?>
                                    <tr>
                                        <td class="align-middle" style="vertical-align: top;"><?= $n ?></td>
                                        <td class="align-middle">
                                            <?= $row['nama'] ?>
                                            <br><?= $row['qty'] ?> &times;
                                            <span class="d-block font-weight-bold"><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold"></span>
                                        </td>
                                        <td style="vertical-align: top;text-align:right"><br><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
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
                                                <td rowspan="<?= $rowspan ?>"></td>
                                            <?php
                                            }
                                            ?>
                                            <td class="align-middle" colspan="2"><i><?= $value['nama'] ?></i></td>
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
                                        <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>PROSEDUR LABORATORIUM</strong></td>
                                    </tr>
                                <?php
                                }

                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td class="align-middle" style="vertical-align: top;"><?= $n ?></td>
                                        <td class="align-middle">
                                            <?= $row['nama'] ?>
                                            <br><?= $row['qty'] ?> &times;
                                            <span class="d-block font-weight-bold"><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold"></span>
                                        </td>
                                        <td style="vertical-align: top;text-align:right"><br><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                    </tr>
                            <?php
                                    $grandTotal['prosedur_laboratorium'] += $row['subTotal'];
                                    $n++;
                                }
                            }

                            ?>
                            <tr>
                                <td colspan="2"><strong>SUBTOTAL</strong></td>
                                <td style="text-align: right;"><strong><?= ubahToRupiahDesimal($grandTotal['prosedur_laboratorium']) ?></strong></td>
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
                                    <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-ambulance text-dark pr-3" style="font-size: 1rem;"></i> <strong>ESCORT</strong></td>
                                </tr>
                                <?php


                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td class="align-middle" style="vertical-align: top;"><?= $n ?></td>
                                        <td class="align-middle">
                                            <?= $row['nama'] ?>
                                            <br><?= $row['qty'] ?> &times;
                                            <span class="d-block font-weight-bold"><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold"></span>
                                        </td>
                                        <td style="vertical-align: top;text-align:right"><br><?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                    </tr>
                                <?php
                                    $grandTotal['escort'] += $row['subTotal'];
                                    $n++;
                                }
                                ?>
                                <tr>
                                    <td colspan="2"><strong>SUBTOTAL</strong></td>
                                    <td style="text-align: right;"><strong><?= ubahToRupiahDesimal($grandTotal['escort']) ?></strong></td>
                                </tr>
                            <?php
                            }
                            ?>
                            <!--<tr>-->
                            <!--    <td colspan="3" class="" style="padding-top: 0.6rem; padding-bottom:0.35rem"><i class="fas fa-file-invoice-dollar text-dark pr-3" style="font-size: 1rem;"></i> <strong>PAYMENT</strong></td>-->
                            <!--</tr>-->
                            <tr>
                                <td style="border-style: none; padding:.5rem" colspan="3"></td>
                            </tr>
                            <tr>
                                <td style="border-style: dashed none;" colspan="3"></td>
                            </tr>
                            <tr>
                                <td style="border-style: none; padding:.5rem" colspan="3"></td>
                            </tr>
                            <?php
                            $sumGrandTotal = array_sum(array_values($grandTotal));
                            ?>
                            <tr>
                                <td colspan="2"><strong>GRAND TOTAL</strong></td>
                                <td style="text-align: right;">
                                    <strong><?= ubahToRupiahDesimal($sumGrandTotal) ?></strong>
                                </td>
                            </tr>
                            <?php
                            if (intval($dataUpdate['diskon'] ?? 0) > 0) {
                            ?>
                                <tr>
                                    <td colspan="2" class="text-right align-middle"><strong>DISCOUNT</strong></td>
                                    <td style="text-align: right;">
                                        <?= ubahToRupiahDesimal($dataUpdate['diskon'] ?? 0) ?>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                            <tr>
                                <td colspan="2"><strong>PAYABLE</strong></td>
                                <td style="text-align: right;"><strong><?= ubahToRupiahDesimal($dataUpdate['payable'] ?? $sumGrandTotal) ?></td>
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
                                foreach ($payments as $index => $payment) {
                                    $detailCurrency = $currencies[$payment['currency']];

                                    switch ($payment['metodePembayaran']) {
                                        case 'Tunai':
                                            $detail = 'PEMBAYARAN CASH';
                                            break;
                                        case 'Non Tunai':
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
                                            break;
                                        case 'Insurance':
                                            $detail = statementWrapper(
                                                DML_SELECT,
                                                "SELECT nama as detail FROM asuransi WHERE kodeAsuransi = ?",
                                                [$payment['kodeReferensi']]
                                            )['detail'];
                                            break;
                                        case 'Klien':
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
                                        <td colspan="2">
                                            <i><?= $payment['metodePembayaran']; ?></i>
                                            <br>
                                            <?= $detail; ?> 
                                        </td>
                                        <td style="text-align:right;"><?= ubahToRupiahDesimal($payment['nominal']); ?></td>
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
                                <td><strong>ATTENDING PHYSICIAN</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    <?= wordwrap($dokter, 30, '<br>'); ?>
                                    <br>
                                    <?= wordwrap($noSIP, 20, '<br>'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            if ($fileFooter) {
            ?>
                <div style="text-align: right;" id="footerImg">
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