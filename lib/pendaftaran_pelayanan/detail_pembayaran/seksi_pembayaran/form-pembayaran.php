<?php


include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenisharga.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasicurrency.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasimetodebayar.php";
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
		AND (
            menu_sub.namaFolder = ?
            OR 
            menu_sub.namaFolder = ?
        )
	'
);
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__),
    'rekam_medis'
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

    $cekEditable = statementWrapper(
        DML_SELECT,
        'SELECT aksesEditable FROM user WHERE idUser = ?',
        [$idUserAsli]
    )['aksesEditable'];

    extract($_POST, EXTR_SKIP);

    $dataPemeriksaan = statementWrapper(
        DML_SELECT,
        'SELECT 
            pasien_antrian.*,
            pasien_antrian_satusehat.waktuKepulangan,
            pasien.*,
            pasien_invoice_klinik.*,
            admisi.namaAdmisi,
            pasien_pemeriksaan_klinik.*
        FROM
            pasien_antrian
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
            LEFT JOIN pasien_antrian_satusehat ON pasien_antrian.kodeAntrian = pasien_antrian_satusehat.kodeAntrian
            LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
            LEFT JOIN pasien_invoice_klinik ON pasien_antrian.kodeAntrian = pasien_invoice_klinik.kodeAntrian 
        WHERE 
            pasien_antrian.kodeAntrian=?',
        [$kodeAntrian]
    );

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_invoice_klinik WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    if ($dataUpdate) {
        $flag = 'pembayaran';
    } else {
        $flag = 'finalisasi';
    }

    if (isset($param)) {
        $result = [];
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

        if (isset($result['src'])) {
            $mode = 'readonly';
        } else {
            $mode = 'input';
        }


        $listJenisHarga = getJenisHarga();
        $currencies = getCurrencyList();

?>
        <div class="card card-custom mb-5">
            <div class="card-header card-header-tabs-line">
                <div class="card-title">
                    <h3 class="card-label"><strong>DETAIL DIAGNOSIS</strong></h3>
                </div>
                <div class="card-toolbar">
                    <ul class="nav nav-tabs nav-bold nav-tabs-line">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#kt_tab_pane_1_3">
                                <span class="nav-icon"><i class="flaticon2-list-3"></i></span>
                                <span class="nav-text">ICD - 10</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#kt_tab_pane_2_3">
                                <span class="nav-icon"><i class="flaticon2-medical-records-1"></i></span>
                                <span class="nav-text">Non ICD - 10</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="kt_tab_pane_1_3" role="tabpanel" aria-labelledby="kt_tab_pane_1_3">
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

                        foreach ($daftarICD10 as $index => $ICD10) {
                            switch ($index) {
                                case 0:
                                    $color = 'info';
                                    break;

                                default:
                                    $color = 'warning';
                                    break;
                            }
                        ?>
                            <div class="d-flex align-items-center bg-light-<?= $color ?> rounded p-5 mb-3">
                                <!--begin::Icon-->
                                <span class="svg-icon svg-icon-<?= $color ?> mr-5">
                                    <span class="svg-icon svg-icon-lg"><!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/General/Attachment2.svg--><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <rect x="0" y="0" width="24" height="24"></rect>
                                                <path d="M11.7573593,15.2426407 L8.75735931,15.2426407 C8.20507456,15.2426407 7.75735931,15.6903559 7.75735931,16.2426407 C7.75735931,16.7949254 8.20507456,17.2426407 8.75735931,17.2426407 L11.7573593,17.2426407 L11.7573593,18.2426407 C11.7573593,19.3472102 10.8619288,20.2426407 9.75735931,20.2426407 L5.75735931,20.2426407 C4.65278981,20.2426407 3.75735931,19.3472102 3.75735931,18.2426407 L3.75735931,14.2426407 C3.75735931,13.1380712 4.65278981,12.2426407 5.75735931,12.2426407 L9.75735931,12.2426407 C10.8619288,12.2426407 11.7573593,13.1380712 11.7573593,14.2426407 L11.7573593,15.2426407 Z" fill="#000000" opacity="0.3" transform="translate(7.757359, 16.242641) rotate(-45.000000) translate(-7.757359, -16.242641) "></path>
                                                <path d="M12.2426407,8.75735931 L15.2426407,8.75735931 C15.7949254,8.75735931 16.2426407,8.30964406 16.2426407,7.75735931 C16.2426407,7.20507456 15.7949254,6.75735931 15.2426407,6.75735931 L12.2426407,6.75735931 L12.2426407,5.75735931 C12.2426407,4.65278981 13.1380712,3.75735931 14.2426407,3.75735931 L18.2426407,3.75735931 C19.3472102,3.75735931 20.2426407,4.65278981 20.2426407,5.75735931 L20.2426407,9.75735931 C20.2426407,10.8619288 19.3472102,11.7573593 18.2426407,11.7573593 L14.2426407,11.7573593 C13.1380712,11.7573593 12.2426407,10.8619288 12.2426407,9.75735931 L12.2426407,8.75735931 Z" fill="#000000" transform="translate(16.242641, 7.757359) rotate(-45.000000) translate(-16.242641, -7.757359) "></path>
                                                <path d="M5.89339828,3.42893219 C6.44568303,3.42893219 6.89339828,3.87664744 6.89339828,4.42893219 L6.89339828,6.42893219 C6.89339828,6.98121694 6.44568303,7.42893219 5.89339828,7.42893219 C5.34111353,7.42893219 4.89339828,6.98121694 4.89339828,6.42893219 L4.89339828,4.42893219 C4.89339828,3.87664744 5.34111353,3.42893219 5.89339828,3.42893219 Z M11.4289322,5.13603897 C11.8194565,5.52656326 11.8194565,6.15972824 11.4289322,6.55025253 L10.0147186,7.96446609 C9.62419433,8.35499039 8.99102936,8.35499039 8.60050506,7.96446609 C8.20998077,7.5739418 8.20998077,6.94077682 8.60050506,6.55025253 L10.0147186,5.13603897 C10.4052429,4.74551468 11.0384079,4.74551468 11.4289322,5.13603897 Z M0.600505063,5.13603897 C0.991029355,4.74551468 1.62419433,4.74551468 2.01471863,5.13603897 L3.42893219,6.55025253 C3.81945648,6.94077682 3.81945648,7.5739418 3.42893219,7.96446609 C3.0384079,8.35499039 2.40524292,8.35499039 2.01471863,7.96446609 L0.600505063,6.55025253 C0.209980772,6.15972824 0.209980772,5.52656326 0.600505063,5.13603897 Z" fill="#000000" opacity="0.3" transform="translate(6.014719, 5.843146) rotate(-45.000000) translate(-6.014719, -5.843146) "></path>
                                                <path d="M17.9142136,15.4497475 C18.4664983,15.4497475 18.9142136,15.8974627 18.9142136,16.4497475 L18.9142136,18.4497475 C18.9142136,19.0020322 18.4664983,19.4497475 17.9142136,19.4497475 C17.3619288,19.4497475 16.9142136,19.0020322 16.9142136,18.4497475 L16.9142136,16.4497475 C16.9142136,15.8974627 17.3619288,15.4497475 17.9142136,15.4497475 Z M23.4497475,17.1568542 C23.8402718,17.5473785 23.8402718,18.1805435 23.4497475,18.5710678 L22.0355339,19.9852814 C21.6450096,20.3758057 21.0118446,20.3758057 20.6213203,19.9852814 C20.2307961,19.5947571 20.2307961,18.9615921 20.6213203,18.5710678 L22.0355339,17.1568542 C22.4260582,16.76633 23.0592232,16.76633 23.4497475,17.1568542 Z M12.6213203,17.1568542 C13.0118446,16.76633 13.6450096,16.76633 14.0355339,17.1568542 L15.4497475,18.5710678 C15.8402718,18.9615921 15.8402718,19.5947571 15.4497475,19.9852814 C15.0592232,20.3758057 14.4260582,20.3758057 14.0355339,19.9852814 L12.6213203,18.5710678 C12.2307961,18.1805435 12.2307961,17.5473785 12.6213203,17.1568542 Z" fill="#000000" opacity="0.3" transform="translate(18.035534, 17.863961) scale(1, -1) rotate(45.000000) translate(-18.035534, -17.863961) "></path>
                                            </g>
                                        </svg><!--end::Svg Icon-->
                                    </span>
                                </span>
                                <!--end::Icon-->

                                <!--begin::Title-->
                                <div class="d-flex flex-column flex-grow-1 mr-2">
                                    <a href="#" class="font-weight-bold text-dark-75 font-size-lg mb-1"><strong>(<?= $ICD10['kode'] ?>)</strong> <?= $ICD10['diagnosis']; ?></a>
                                    <span class="text-muted font-weight-bold text-uppercase"><?= $ICD10['jenisUrutan']; ?></span>
                                </div>
                                <!--end::Title-->
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="kt_tab_pane_2_3" role="tabpanel" aria-labelledby="kt_tab_pane_2_3">
                        <?php
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
                            echo $diagnosisNonICD10;
                        } else {
                        ?>
                            <div class="container-fluid d-flex align-items-center justify-content-center" style="height:100px">
                                <div>
                                    <i class="text-muted" style="font-family: 'Times New Roman', Times, serif;">Diagnosis Non ICD - 10 Belum Dibuat...</i>
                                </div>
                            </div>
                        <?php
                        }

                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-custom">
            <!-- CARD HEADER -->
            <div class="card-header">
                <!-- CARD TITLE -->
                <div class="card-title">
                    <h3 class="card-label">
                        <span class="card-label font-weight-bolder text-dark d-block">
                            <i class="fas fa-file-invoice-dollar text-dark pr-2"></i> Pembayaran
                        </span>
                        <span class="mt-3 font-weight-bold font-size-sm">
                            <?= PAGE_TITLE; ?>
                        </span>
                    </h3>
                </div>
                <!-- END CARD TITLE -->
            </div>
            <!-- END CARD HEADER -->

            <!-- CARD BODY -->
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-12">
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
                        <div style="overflow-x: auto;">
                            <table class="table table-bordered">
                                <thead class="alert alert-danger">
                                    <tr>
                                        <th>NO</th>
                                        <th>ITEM</th>
                                        <th>QTY</th>
                                        <th class="text-center" colspan="2">HARGA</th>
                                        <th class="text-center" colspan="2">SUB TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="bg-secondary"><i class="fas fa-pills text-dark pr-3" style="font-size: 1rem;"></i> <strong>OBAT</strong></td>
                                    </tr>
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

                                    foreach ($data as $row) {
                                    ?>
                                        <tr>
                                            <td class="align-middle"><?= $n ?></td>
                                            <td class="align-middle"><?= $row['nama'] ?></td>
                                            <td class="align-middle"><?= $row['qty'] ?></td>
                                            <td class="text-right">
                                                <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                <span class="text-muted font-weight-bold">
                                                    <?php
                                                    if ($row['bebasBiaya'] === 'Ya') {
                                                        echo 'Bebas Biaya';
                                                    } else {
                                                        echo "{$row['jenisHarga']} - " . ($row['namaAdmisi'] ?? $dataPemeriksaan['namaAdmisi']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width: 5%;" class="text-center align-middle">
                                                <?php
                                                if ($flag === 'finalisasi') {
                                                ?>
                                                    <div class="dropdown dropdown-inline">
                                                        <button type="button" class="btn btn-hover-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="flaticon2-gear"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" style="width: 230px; overflow: hidden; overflow-y: auto; max-height: calc(100vh - 500px);">
                                                            <?php
                                                            $detailHarga = statementWrapper(
                                                                DML_SELECT_ALL,
                                                                'SELECT 
                                                                    obat_harga.idObatHarga,
                                                                    obat_harga.nominal,
                                                                    obat_harga.jenisHarga,
                                                                    admisi.namaAdmisi 
                                                                FROM 
                                                                    obat_harga
                                                                    INNER JOIN admisi ON obat_harga.idAdmisi = admisi.idAdmisi
                                                                    INNER JOIN obat ON obat_harga.kodeObat = obat.kodeObat
                                                                WHERE 
                                                                    obat.idObat = ?
                                                                ',
                                                                [$row['idObat']]
                                                            );

                                                            foreach ($detailHarga as $index => $harga) {
                                                            ?>
                                                                <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('obat','<?= $tokenCSRF ?>','<?= $row['idObatKlinik'] ?>', '<?= $harga['idObatHarga'] ?>')">
                                                                    <div style="width: 100%;">
                                                                        <div class="d-block font-weight-bold text-right">Rp <?= ubahToRupiahDesimal($harga['nominal']) ?></div>
                                                                        <div class="text-muted font-weight-bold text-right"><?= $harga['jenisHarga']; ?> - <?= $harga['namaAdmisi']; ?></div>
                                                                    </div>
                                                                </a>
                                                            <?php
                                                            }
                                                            ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('obat','<?= $tokenCSRF ?>','<?= $row['idObatKlinik'] ?>', '__FREE__')">
                                                                <div style="width: 100%;">
                                                                    <div class="d-block font-weight-bold text-right">Rp 0</div>
                                                                    <div class="text-muted font-weight-bold text-right">Bebas Biaya</div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right align-middle" colspan="2">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                        </tr>
                                    <?php
                                        $grandTotal['obat'] += $row['subTotal'];
                                        $grandTotalHPP += $row['subTotalHPP'];

                                        $n++;
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>TOTAL</strong></td>
                                        <td class="text-right" colspan="2"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['obat']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="bg-secondary"><i class="fas fa-syringe text-dark pr-3" style="font-size: 1rem;"></i> <strong>ALKES</strong></td>
                                    </tr>
                                    <?php

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

                                    foreach ($data as $row) {
                                    ?>
                                        <tr>
                                            <td class="align-middle"><?= $n ?></td>
                                            <td class="align-middle"><?= $row['nama'] ?></td>
                                            <td class="align-middle"><?= $row['qty'] ?></td>
                                            <td class="text-right">
                                                <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                <span class="text-muted font-weight-bold">
                                                    <?php
                                                    if ($row['bebasBiaya'] === 'Ya') {
                                                        echo 'Bebas Biaya';
                                                    } else {
                                                        echo "{$row['jenisHarga']} - " . ($row['namaAdmisi'] ?? $dataPemeriksaan['namaAdmisi']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width: 5%;" class="text-center align-middle">
                                                <?php
                                                if ($flag === 'finalisasi') {
                                                ?>
                                                    <div class="dropdown dropdown-inline">
                                                        <button type="button" class="btn btn-hover-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="flaticon2-gear"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" style="width: 230px; overflow: hidden; overflow-y: auto; max-height: calc(100vh - 500px);">
                                                            <?php
                                                            $detailHarga = statementWrapper(
                                                                DML_SELECT_ALL,
                                                                'SELECT 
                                                                    alkes_harga.idAlkesHarga,
                                                                    alkes_harga.nominal,
                                                                    alkes_harga.jenisHarga,
                                                                    admisi.namaAdmisi 
                                                                FROM 
                                                                    alkes_harga
                                                                    INNER JOIN admisi ON alkes_harga.idAdmisi = admisi.idAdmisi
                                                                    INNER JOIN alkes ON alkes_harga.kodeAlkes = alkes.kodeAlkes
                                                                WHERE 
                                                                    alkes.idAlkes = ?
                                                                ',
                                                                [$row['idAlkes']]
                                                            );

                                                            foreach ($detailHarga as $index => $harga) {
                                                            ?>
                                                                <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('alkes','<?= $tokenCSRF ?>','<?= $row['idAlkesKlinik'] ?>', '<?= $harga['idAlkesHarga'] ?>')">
                                                                    <div style="width: 100%;">
                                                                        <div class="d-block font-weight-bold text-right">Rp <?= ubahToRupiahDesimal($harga['nominal']) ?></div>
                                                                        <div class="text-muted font-weight-bold text-right"><?= $harga['jenisHarga']; ?> - <?= $harga['namaAdmisi']; ?></div>
                                                                    </div>
                                                                </a>
                                                            <?php
                                                            }
                                                            ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('alkes','<?= $tokenCSRF ?>','<?= $row['idAlkesKlinik'] ?>', '__FREE__')">
                                                                <div style="width: 100%;">
                                                                    <div class="d-block font-weight-bold text-right">Rp 0</div>
                                                                    <div class="text-muted font-weight-bold text-right">Bebas Biaya</div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right align-middle" colspan="2">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                        </tr>
                                    <?php
                                        $grandTotal['alkes'] += $row['subTotal'];
                                        $grandTotalHPP += $row['subTotalHPP'];

                                        $n++;
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>TOTAL</strong></td>
                                        <td class="text-right" colspan="2"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['alkes']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="bg-secondary"><i class="fas fa-screwdriver text-dark pr-3" style="font-size: 1rem;"></i> <strong>TINDAKAN</strong></td>
                                    </tr>
                                    <?php

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

                                    foreach ($data as $row) {
                                    ?>
                                        <tr>
                                            <td class="align-middle"><?= $n ?></td>
                                            <td class="align-middle"><?= $row['nama'] ?></td>
                                            <td class="align-middle"><?= $row['qty'] ?></td>
                                            <td class="text-right">
                                                <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                <span class="text-muted font-weight-bold">
                                                    <?php
                                                    if ($row['bebasBiaya'] === 'Ya') {
                                                        echo 'Bebas Biaya';
                                                    } else {
                                                        echo "{$row['jenisHarga']} - " . ($row['namaAdmisi'] ?? $dataPemeriksaan['namaAdmisi']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width: 5%;" class="text-center align-middle">
                                                <?php
                                                if ($flag === 'finalisasi') {
                                                ?>
                                                    <div class="dropdown dropdown-inline">
                                                        <button type="button" class="btn btn-hover-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="flaticon2-gear"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" style="width: 230px; overflow: hidden; overflow-y: auto; max-height: calc(100vh - 500px);">
                                                            <?php
                                                            $detailHarga = statementWrapper(
                                                                DML_SELECT_ALL,
                                                                'SELECT 
                                                                    tindakan_harga.idTindakanHarga,
                                                                    tindakan_harga.nominal,
                                                                    tindakan_harga.jenisHarga,
                                                                    admisi.namaAdmisi 
                                                                FROM 
                                                                    tindakan_harga
                                                                    INNER JOIN admisi ON tindakan_harga.idAdmisi = admisi.idAdmisi
                                                                    INNER JOIN tindakan ON tindakan_harga.kodeTindakan = tindakan.kodeTindakan
                                                                WHERE 
                                                                    tindakan.idTindakan = ?
                                                                ',
                                                                [$row['idTindakan']]
                                                            );

                                                            foreach ($detailHarga as $index => $harga) {
                                                            ?>
                                                                <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('tindakan','<?= $tokenCSRF ?>','<?= $row['idTindakanKlinik'] ?>', '<?= $harga['idTindakanHarga'] ?>')">
                                                                    <div style="width: 100%;">
                                                                        <div class="d-block font-weight-bold text-right">Rp <?= ubahToRupiahDesimal($harga['nominal']) ?></div>
                                                                        <div class="text-muted font-weight-bold text-right"><?= $harga['jenisHarga']; ?> - <?= $harga['namaAdmisi']; ?></div>
                                                                    </div>
                                                                </a>
                                                            <?php
                                                            }
                                                            ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('tindakan','<?= $tokenCSRF ?>','<?= $row['idTindakanKlinik'] ?>', '__FREE__')">
                                                                <div style="width: 100%;">
                                                                    <div class="d-block font-weight-bold text-right">Rp 0</div>
                                                                    <div class="text-muted font-weight-bold text-right">Bebas Biaya</div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right align-middle" colspan="2">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                        </tr>
                                    <?php
                                        $grandTotal['tindakan'] += $row['subTotal'];

                                        $n++;
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>TOTAL</strong></td>
                                        <td class="text-right" colspan="2"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['tindakan']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="bg-secondary"><i class="fas fa-vials text-dark pr-3" style="font-size: 1rem;"></i> <strong>PROSEDUR LABORATORIUM</strong></td>
                                    </tr>
                                    <?php
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

                                    $n = 1;

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
                                                $row['idPaketLaboratorium'],
                                            ]
                                        );
                                        // $detail = statementWrapper(
                                        //     DML_SELECT_ALL,
                                        //     "SELECT
                                        //         *
                                        //     FROM
                                        //     (
                                        //         (
                                        //             SELECT 
                                        //                 prosedur_laboratorium.nama,
                                        //                 'Prosedur Laboratorium' as tipe
                                        //             FROM 
                                        //                 pasien_laboratorium_klinik
                                        //                 INNER JOIN prosedur_laboratorium ON prosedur_laboratorium.idProsedurLaboratorium = pasien_laboratorium_klinik.idProsedurLaboratorium
                                        //             WHERE 
                                        //                 pasien_laboratorium_klinik.kodeAntrian = ?
                                        //                 AND pasien_laboratorium_klinik.idPaketLaboratorium = ?
                                        //         )
                                        //         UNION ALL
                                        //         (
                                        //             SELECT 
                                        //                 tindakan.nama,
                                        //                 'Tindakan' as tipe
                                        //             FROM 
                                        //                 pasien_tindakan_klinik
                                        //                 INNER JOIN tindakan ON tindakan.idTindakan = pasien_tindakan_klinik.idTindakan
                                        //             WHERE 
                                        //                 pasien_tindakan_klinik.kodeAntrian = ?
                                        //                 AND pasien_tindakan_klinik.idPaketLaboratorium = ?
                                        //         )
                                        //         UNION ALL
                                        //         (
                                        //             SELECT 
                                        //                     prosedur_laboratorium.nama,
                                        //                     'Prosedur Laboratorium' as tipe
                                        //               FROM 
                                        //                     pasien_paket_laboratorium_klinik
                                        //               INNER JOIN 
                                        //               		paket_laboratorium 
                                        //               ON 
                                        //               		pasien_paket_laboratorium_klinik.idPaketLaboratorium = paket_laboratorium.idPaketLaboratorium
                                        //               INNER JOIN 
                                        //               		paket_laboratorium_detail
                                        //               ON
                                        //               		paket_laboratorium_detail.kodePaketLaboratorium = paket_laboratorium.kodePaketLaboratorium
                                        //               INNER JOIN 
                                        //               		prosedur_laboratorium
                                        //               ON
                                        //               		paket_laboratorium_detail.idItem = prosedur_laboratorium.idProsedurLaboratorium
                                        //               WHERE 
                                        //                     pasien_paket_laboratorium_klinik.kodeAntrian = ?
                                        //               AND pasien_paket_laboratorium_klinik.idPaketLaboratorium = ?
                                        //               AND paket_laboratorium_detail.tipeItem = ?
                                        //         )
                                        //     ) detail_paket_lab
                                        //     ",
                                        //     [
                                        //         $kodeAntrian,
                                        //         $row['idPaketLaboratorium'],

                                        //         $kodeAntrian,
                                        //         $row['idPaketLaboratorium'],
                                                
                                        //         $kodeAntrian,
                                        //         $row['idPaketLaboratorium'],
                                        //         'Prosedur Laboratorium'
                                        //     ]
                                        // );
                                    ?>
                                        <tr>
                                            <td class="align-middle" colspan="2"><strong><?= $row['nama'] ?></strong></td>
                                            <td class="align-middle"><?= $row['qty'] ?></td>
                                            <td class="text-right" colspan="2">
                                                <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                <span class="text-muted font-weight-bold">
                                                    <?php
                                                    if ($row['bebasBiaya'] === 'Ya') {
                                                        echo 'Bebas Biaya';
                                                    } else {
                                                        echo "{$row['jenisHarga']} - " . ($row['namaAdmisi'] ?? $dataPemeriksaan['namaAdmisi']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="text-right align-middle" colspan="2">
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
                                                <td class="align-middle" colspan="6">
                                                    <span class="d-block">
                                                        <?= $value['nama'] ?>
                                                    </span>
                                                    <span class="text-muted font-weight-bold"><?= $value['tipe']; ?></span>
                                                </td>
                                            </tr>
                                        <?php
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

                                    $n = 1;

                                    foreach ($data as $row) {
                                        ?>
                                        <tr>
                                            <td class="align-middle"><?= $n ?></td>
                                            <td class="align-middle"><?= $row['nama'] ?></td>
                                            <td class="align-middle"><?= $row['qty'] ?></td>
                                            <td class="text-right">
                                                <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                <span class="text-muted font-weight-bold">
                                                    <?php
                                                    if ($row['bebasBiaya'] === 'Ya') {
                                                        echo 'Bebas Biaya';
                                                    } else {
                                                        echo "{$row['jenisHarga']} - " . ($row['namaAdmisi'] ?? $dataPemeriksaan['namaAdmisi']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width: 5%;" class="text-center align-middle">
                                                <?php
                                                if ($flag === 'finalisasi') {
                                                ?>
                                                    <div class="dropdown dropdown-inline">
                                                        <button type="button" class="btn btn-hover-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="flaticon2-gear"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" style="width: 230px; overflow: hidden; overflow-y: auto; max-height: calc(100vh - 500px);">
                                                            <?php
                                                            $detailHarga = statementWrapper(
                                                                DML_SELECT_ALL,
                                                                'SELECT 
                                                                    prosedur_laboratorium_harga.idProsedurLaboratoriumHarga,
                                                                    prosedur_laboratorium_harga.nominal,
                                                                    prosedur_laboratorium_harga.jenisHarga,
                                                                    admisi.namaAdmisi 
                                                                FROM 
                                                                    prosedur_laboratorium_harga
                                                                    INNER JOIN admisi ON prosedur_laboratorium_harga.idAdmisi = admisi.idAdmisi
                                                                    INNER JOIN prosedur_laboratorium ON prosedur_laboratorium_harga.kodeProsedurLaboratorium = prosedur_laboratorium.kodeProsedurLaboratorium
                                                                WHERE 
                                                                    prosedur_laboratorium.idProsedurLaboratorium = ?
                                                                ',
                                                                [$row['idProsedurLaboratorium']]
                                                            );

                                                            foreach ($detailHarga as $index => $harga) {
                                                            ?>
                                                                <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('prosedur_laboratorium','<?= $tokenCSRF ?>','<?= $row['idLaboratoriumKlinik'] ?>', '<?= $harga['idProsedurLaboratoriumHarga'] ?>')">
                                                                    <div style="width: 100%;">
                                                                        <div class="d-block font-weight-bold text-right">Rp <?= ubahToRupiahDesimal($harga['nominal']) ?></div>
                                                                        <div class="text-muted font-weight-bold text-right"><?= $harga['jenisHarga']; ?> - <?= $harga['namaAdmisi']; ?></div>
                                                                    </div>
                                                                </a>
                                                            <?php
                                                            }
                                                            ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('prosedur_laboratorium','<?= $tokenCSRF ?>','<?= $row['idLaboratoriumKlinik'] ?>', '__FREE__')">
                                                                <div style="width: 100%;">
                                                                    <div class="d-block font-weight-bold text-right">Rp 0</div>
                                                                    <div class="text-muted font-weight-bold text-right">Bebas Biaya</div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right align-middle" colspan="2">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                        </tr>
                                    <?php
                                        $grandTotal['prosedur_laboratorium'] += $row['subTotal'];
                                        $n++;
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>TOTAL</strong></td>
                                        <td class="text-right" colspan="2"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['prosedur_laboratorium']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="bg-secondary"><i class="fas fa-ambulance text-dark pr-3" style="font-size: 1rem;"></i> <strong>ESCORT</strong></td>
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

                                    foreach ($data as $row) {
                                    ?>
                                        <tr>
                                            <td class="align-middle"><?= $n ?></td>
                                            <td class="align-middle"><?= $row['nama'] ?></td>
                                            <td class="align-middle"><?= $row['qty'] ?></td>
                                            <td class="text-right">
                                                <span class="d-block font-weight-bold">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['harga']) ?></span>
                                                <span class="text-muted font-weight-bold">
                                                    <?php
                                                    if ($row['bebasBiaya'] === 'Ya') {
                                                        echo 'Bebas Biaya';
                                                    } else {
                                                        echo "{$row['jenisHarga']} - " . ($row['namaAdmisi'] ?? $dataPemeriksaan['namaAdmisi']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width: 5%;" class="text-center align-middle">
                                                <?php
                                                if ($flag === 'finalisasi') {
                                                ?>
                                                    <div class="dropdown dropdown-inline">
                                                        <button type="button" class="btn btn-hover-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="flaticon2-gear"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" style="width: 230px; overflow: hidden; overflow-y: auto; max-height: calc(100vh - 500px);">
                                                            <?php
                                                            $detailHarga = statementWrapper(
                                                                DML_SELECT_ALL,
                                                                'SELECT 
                                                                escort_harga.idEscortHarga,
                                                                escort_harga.nominal,
                                                                escort_harga.jenisHarga,
                                                                admisi.namaAdmisi 
                                                            FROM 
                                                                escort_harga
                                                                INNER JOIN admisi ON escort_harga.idAdmisi = admisi.idAdmisi
                                                                INNER JOIN escort ON escort_harga.kodeEscort = escort.kodeEscort
                                                            WHERE 
                                                                escort.idEscort = ?
                                                            ',
                                                                [$row['idEscort']]
                                                            );

                                                            foreach ($detailHarga as $index => $harga) {
                                                            ?>
                                                                <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('escort','<?= $tokenCSRF ?>','<?= $row['idEscortKlinik'] ?>', '<?= $harga['idEscortHarga'] ?>')">
                                                                    <div style="width: 100%;">
                                                                        <div class="d-block font-weight-bold text-right">Rp <?= ubahToRupiahDesimal($harga['nominal']) ?></div>
                                                                        <div class="text-muted font-weight-bold text-right"><?= $harga['jenisHarga']; ?> - <?= $harga['namaAdmisi']; ?></div>
                                                                    </div>
                                                                </a>
                                                            <?php
                                                            }
                                                            ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault();changeHarga('escort','<?= $tokenCSRF ?>','<?= $row['idEscortKlinik'] ?>', '__FREE__')">
                                                                <div style="width: 100%;">
                                                                    <div class="d-block font-weight-bold text-right">Rp 0</div>
                                                                    <div class="text-muted font-weight-bold text-right">Bebas Biaya</div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right align-middle" colspan="2">Rp <?= $row['bebasBiaya'] === 'Ya' ? 0 : ubahToRupiahDesimal($row['subTotal']) ?></td>
                                        </tr>
                                    <?php
                                        $grandTotal['escort'] += $row['subTotal'];
                                        $n++;
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>TOTAL</strong></td>
                                        <td class="text-right" colspan="2"><strong>Rp <?= ubahToRupiahDesimal($grandTotal['escort']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="bg-secondary"><i class="fas fa-file-invoice-dollar text-dark pr-3" style="font-size: 1rem;"></i> <strong>PAYMENT</strong></td>
                                    </tr>
                                    <?php

                                    $sumGrandTotal = array_sum(array_values($grandTotal));
                                    $payable = $dataPemeriksaan['payable'] ?? $sumGrandTotal;

                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>GRAND TOTAL</strong></td>
                                        <td class="text-right" colspan="2">
                                            <strong>Rp <?= ubahToRupiahDesimal($sumGrandTotal) ?></strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-right align-middle"><strong>DISCOUNT</strong></td>
                                        <td>
                                            <!-- <div class="input-group">
                                        <input type="text" name="persentaseDiskon" id="persentaseDiskon" value="<?= ubahToRupiahDesimal($dataPemeriksaan['diskon'] ?? 0) ?>" class="form-control text-right" data-format-rupiah="active">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-percentage"></i>
                                            </span>
                                        </div>
                                    </div> -->
                                        </td>
                                        <td class="text-right">
                                            <?php
                                            if ($flag === 'finalisasi') {
                                            ?>
                                                <input type="text" name="diskon" id="diskon" value="0" class="form-control text-right" data-format-rupiah="active" onkeyup="getPayable()" form="formFinalisasiPembayaran">
                                            <?php
                                            } else if ($flag === 'pembayaran') {
                                                echo 'Rp ' . ubahToRupiahDesimal($dataUpdate['diskon']);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-right align-middle"><strong>PAYABLE</strong></td>
                                        <td class="text-right" colspan="2"><strong id="payableText">Rp <?= ubahToRupiahDesimal($payable) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-right align-middle"><strong>TANGGAL PEMBAYARAN</strong></td>
                                        <td class="text-right" colspan="2">
                                            <?php
                                            if ($flag === 'finalisasi') {
                                            ?>
                                                <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                                    <input type="text" class="form-control text-right" id="tglPembayaran" name="tglPembayaran" placeholder="Click to select a date!" value="<?= date('Y-m-d') ?>" autocomplete="off" form="formFinalisasiPembayaran">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button">
                                                            <i class="fa fa-calendar"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php
                                            } else if ($flag === 'pembayaran') {
                                                echo ubahTanggalIndo($dataUpdate['tglPembayaran']);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="row w-100 mx-0">
                                <div class="col-md-8">
                                    <?php
                                    $query = rawurlencode(enkripsi(http_build_query([
                                        'kodeAntrian' => $kodeAntrian,
                                        'kodeRM' => $kodeRM,
                                    ]), secretKey()));

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

                                    if ($flag === 'finalisasi') {
                                    ?>
                                        <form id="formFinalisasiPembayaran">
                                            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                                            <input type="hidden" name="kodeRM" value="<?= $kodeRM ?>">
                                            <input type="hidden" name="flag" value="<?= $flag ?>">
                                            <input type="hidden" name="kodeAntrian" value="<?= $kodeAntrian ?>">
                                            <input type="hidden" name="grandTotal" value="<?= $sumGrandTotal ?>">
                                            <input type="hidden" name="grandTotalHPP" value="<?= $grandTotalHPP ?>">
                                            <input type="hidden" name="payable" value="<?= $payable ?>">
                                        </form>

                                        <button class="btn btn-success w-100 text-center" type="button" onclick="prosesPembayaran()"><i class="fas fa-check-double pr-5"></i><strong>FINALISASI DATA</strong></button>
                                        <?php
                                    } else {
                                        if ($dataPemeriksaan['statusPembayaran'] === 'Belum Bayar' || $cekEditable === 'Aktif') {
                                        ?>
                                            <button class="btn btn-danger w-100 text-center" type="button" onclick="bukaFinalisasiInvoice('<?= $dataUpdate['kodeInvoice'] ?>','<?= $dataUpdate['kodeAntrian'] ?>','<?= $tokenCSRF ?>')"><i class="fas fa-lock-open pr-5"></i><strong>BUKA FINALISASI</strong></button>
                                        <?php
                                        } else {
                                        ?>
                                            <button class="btn btn-secondary w-100 text-center" type="button"></i><strong>FINAL</strong></button>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="col-md-4">
                                    <?php
                                    if ($dataPemeriksaan['statusPembayaran'] === 'Sudah Bayar') {
                                    ?>
                                        <a href="<?= BASE_URL_HTML ?><?= MAIN_DIR ?>/proses_bisnis/pendaftaran_pelayanan/print_kecil/?param=<?= $query ?>" class="btn btn-primary" target="_blank">
                                            <i class="fa fa-print"></i> <strong>PRINT INVOICE KECIL</strong>
                                        </a>
                                        <a href="<?= BASE_URL_HTML ?><?= MAIN_DIR ?>/proses_bisnis/pendaftaran_pelayanan/print/?param=<?= $query ?>" class="btn btn-info" target="_blank">
                                            <i class="fa fa-print"></i> <strong>PRINT INVOICE</strong>
                                        </a>
                                    <?php
                                    } else {
                                    ?>
                                        <a href="<?= BASE_URL_HTML ?><?= MAIN_DIR ?>/proses_bisnis/pendaftaran_pelayanan/print/?param=<?= $query ?>" class="btn btn-warning w-100" target="_blank">
                                            <i class="fa fa-print"></i> <strong>PRINT QUOTATION</strong>
                                        </a>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <?php
                    if ($flag === 'pembayaran') {
                    ?>
                        <div class="col-md-6">
                            <?php

                            if ($payments) {
                                $totalTerbayar = 0;
                            ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">AKSI</th>
                                            <th class="text-center">METODE</th>
                                            <th class="text-center">DETAIL</th>
                                            <th class="text-center">NOMINAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                                <td class="align-middle text-center">
                                                    <?php
                                                    if ($index + 1 === count($payments) && $dataPemeriksaan['statusPembayaran'] === 'Belum Bayar') {
                                                    ?>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="deletePembayaran('<?= $payment['idPasienDeposit'] ?>', '<?= $tokenCSRF ?>')"><i class="fas fa-trash pr-0"></i></button>
                                                    <?php
                                                    }
                                                    ?>
                                                </td>
                                               <td class="align-middle text-center">
                                                    <span class="d-block">
                                                        <?= $metode; ?>
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center"><?= $detail; ?></td>
                                                <td class="align-middle text-right">
                                                    <span class="d-block">
                                                        Rp <?= ubahToRupiahDesimal($payment['nominal']); ?> (<?= CURRENCY_DEFAULT; ?>)
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
                                            $totalTerbayar += intval($payment['nominal']);
                                        }

                                        $sisaBayar = $payable - $totalTerbayar;
                                        ?>
                                    </tbody>
                                </table>
                            <?php
                            } else {
                                $sisaBayar = $payable;
                            }
                            ?>
                            <div class="form-group">
                                <label for="sisaBayar"><i class="fas fa-history"></i> WAKTU KEPULANGAN</label>
                                <div class="input-group date" id="waktuKepulangan" data-target-input="nearest" data-value="<?= date_create($dataPemeriksaan['waktuKepulangan'] ?? date('Y-m-d H:i:s'))->format('m/d/Y H:i:s') ?>">
                                    <div class="input-group-prepend ">
                                        <?php
                                        if (is_null($dataPemeriksaan['waktuKepulangan'])) {
                                        ?>
                                            <div class="input-group-text btn btn-success btn-sm" onclick="setWaktuKepulangan()">
                                                <i class="fas fa-sync-alt pr-0" style="font-size: 14px;"></i>
                                            </div>
                                        <?php
                                        } else {
                                        ?>
                                            <div class="input-group-text btn btn-info btn-sm" onclick="setWaktuKepulangan()">
                                                <i class="fas fa-sync-alt pr-0" style="font-size: 14px;"></i>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    <input type="text" class="form-control datetimepicker-input" name="waktuKepulangan" value="<?= date_create($dataPemeriksaan['waktuKepulangan'] ?? date('Y-m-d H:i:s'))->format('m/d/Y H:i:s') ?>" placeholder="Select date & time" data-target="#waktuKepulangan" />
                                    <div class="input-group-append" data-target="#waktuKepulangan" data-toggle="datetimepicker">
                                        <span class="input-group-text">
                                            <i class="ki ki-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form id="formFinalisasiPembayaran">
                                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                                <input type="hidden" name="kodeRM" value="<?= $kodeRM ?>">
                                <input type="hidden" name="flag" value="<?= $flag ?>">
                                <input type="hidden" name="kodeAntrian" value="<?= $kodeAntrian ?>">
                                <input type="hidden" name="kodeInvoice" value="<?= $dataUpdate['kodeInvoice'] ?>">
                                <input type="hidden" name="tglPembayaran" value="<?= $dataUpdate['tglPembayaran'] ?>">
                                <input type="hidden" name="payable" value="<?= cekDesimal($payable) ?>">
                                <input type="hidden" name="sisaBayar" value="<?= intval($sisaBayar) ?>">



                                <div class="form-group">
                                    <label for="sisaBayar"><i class="fas fa-money-bill-wave"></i> SISA BAYAR</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                Rp
                                            </span>
                                        </div>
                                        <input type="text" class="form-control text-right" id="sisaBayar" placeholder="Sisa Bayar" value="<?= ubahToRupiahDesimal($sisaBayar) ?>" disabled>
                                    </div>
                                </div>
                                <?php


                                if (is_null($sisaBayar) || $dataPemeriksaan['statusPembayaran'] === 'Belum Bayar') {
                                ?>
                                    <div class="form-group">
                                        <label for="metodePembayaran"><i class="fas fa-id-card"></i> METODE PEMBAYARAN</label>
                                        <select name="metodePembayaran" id="metodePembayaran" class="form-control selectpicker" data-live-search="true" onchange="selectDetailPembayaran()">
                                            <option value="">Pilih Metode Pembayaran</option>
                                            <?php

                                            $opsi = getMetodePembayaran();

                                            foreach ($opsi as $row) {
                                            ?>
                                                <option value="<?= $row ?>"> <?= $row ?> </option>
                                            <?php

                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div id="boxDetailFinalisasiPembayaran">
                                    </div>
                                <?php
                                }
                                ?>
                                <div class="form-group text-right">

                                    <?php
                                    if ($dataPemeriksaan['statusPembayaran'] === 'Belum Bayar' && $mode === 'input') {
                                    ?>
                                        <button type="button" class="btn btn-danger" onclick="prosesPembayaran()">
                                            <i class="fas fa-cash-register pr-4"></i> <strong>PAY</strong>
                                        </button>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </form>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
<?php
    }
}
?>