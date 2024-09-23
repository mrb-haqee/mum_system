<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
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
if (!$dataCekUser || !validateIP($_SESSION['IP_ADDR'])) {
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

        $kodeAntrian = $result['kodeAntrian'];
        $kodeRM = $result['kodeRM'];
    }

    $dataPasien = statementWrapper(
        DML_SELECT,
        'SELECT 
            * 
        FROM 
            pasien 
            INNER JOIN pasien_antrian ON pasien.kodeRM = pasien_antrian.kodeRM
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            LEFT JOIN pasien_antrian_satusehat ON pasien_antrian.kodeAntrian = pasien_antrian_satusehat.kodeAntrian
        WHERE 
            pasien_antrian.kodeAntrian=?',
        [$kodeAntrian]
    );

    $iconPasien = BASE_URL_HTML . '/assets/media/svg/avatars/001-boy.svg';
    if ($dataPasien['jenisKelamin'] == 'Perempuan') {
        $iconPasien = BASE_URL_HTML . '/assets/media/svg/avatars/018-girl-9.svg';
    }

    $umur = umur($dataPasien['tanggalLahir']);
?>

    <!DOCTYPE html>
    <html lang="en">
    <!-- HEAD -->

    <head>
        <meta charset="utf-8">
        <title>Detail <?= $dataCekMenu['namaSubMenu']; ?> | <?= PAGE_TITLE; ?></title>
        <meta name="description" content="Page with empty content">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="author" content="TempatKita Software">

        <!-- CSS UTAMA SEMUA HALAMAN -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/global/plugins.bundle.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/custom/prismjs/prismjs.bundle.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/style.bundle.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/header/base/light.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/header/menu/light.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/brand/dark.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/aside/dark.css">
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/custom_css/loader.css">
        <!-- END CSS UTAMA SEMUA HALAMAN -->

        <!-- CSS KHUSUS HALAMAN INI -->
        <link rel="stylesheet" href="css/custom.css">
        <!-- END CSS KHUSUS HALAMAN INI -->

        <?php
        icon(BASE_URL_HTML);
        ?>
    </head>
    <!--END HEAD -->

    <!-- LOADER -->
    <div class="overlay">
        <div class="overlay__inner">
            <div class="overlay__content">
                <span class="spinner"></span>
            </div>
        </div>
    </div>
    <!-- END LOADER -->

    <!-- BODY -->

    <body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed page-loading">

        <div class="d-flex flex-column flex-root">
            <div class="d-flex flex-row flex-column-fluid page">

                <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">
                    <!-- HEADER -->
                    <div id="kt_header" class="header header-fixed">
                        <div class="container-fluid d-flex align-items-stretch justify-content-between">

                            <div class="header-menu-wrapper header-menu-wrapper-left" id="kt_header_menu_wrapper">
                                <!-- HEADER MENU-->
                                <div class="header-menu header-menu-layout-default">
                                    <!-- HEADER NAV -->
                                    <ul class="menu-nav">
                                        <li class="menu-item menu-item-submenu">
                                            <img alt="Logo" src="<?= BASE_URL_HTML ?>/assets/media/logos/page-detail-icon.png">
                                        </li>
                                    </ul>
                                    <!-- END HEADER NAV -->
                                </div>
                                <!-- END HEADER MENU -->
                            </div>

                            <!-- TOPBAR -->
                            <div class="topbar">

                                <!-- TOPBAR USER -->
                                <div class="topbar-item">
                                    <?php
                                    if (isset($src)) {
                                        switch ($src) {
                                            case 'rm':
                                    ?>
                                                <a href="<?= BASE_URL_HTML ?><?= MAIN_DIR ?>/laporan/rekam_medis/?param=<?= rawurlencode($param) ?>" class="btn btn-light-primary btn-sm">
                                                    <i class="fa fa-arrow-left"></i> Back
                                                </a>
                                            <?php
                                                break;

                                            default:
                                            ?>
                                                <a href="../" class="btn btn-light-primary btn-sm">
                                                    <i class="fa fa-arrow-left"></i> Back
                                                </a>
                                        <?php
                                                break;
                                        }
                                    } else {
                                        ?>
                                        <a href="../" class="btn btn-light-primary btn-sm">
                                            <i class="fa fa-arrow-left"></i> Back
                                        </a>
                                    <?php
                                    }
                                    ?>
                                </div>
                                <!-- END TOPBAR USER -->

                            </div>
                            <!-- END TOPBAR -->
                        </div>
                    </div>
                    <!-- END HEADER -->

                    <!-- CONTENT -->
                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                        <!-- SUB HEADER -->
                        <div class="subheader py-2 py-lg-6 subheader-solid" id="kt_subheader">
                            <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">

                                <div class="d-flex align-items-center flex-wrap mr-1">
                                    <div class="d-flex align-items-baseline flex-wrap mr-5">

                                        <h5 class="text-dark font-weight-bold my-1 mr-5">Detail <?= $dataCekMenu['namaSubMenu']; ?></h5>

                                        <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                                            <li class="breadcrumb-item">
                                                <a href="#" class="text-muted"></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- END SUB HEADER -->

                        <!-- ENTRY -->
                        <div class="d-flex flex-column-fluid">
                            <div class="container-fluid">

                                <div class="row">
                                    <!-- IMPORTANT VARIABLE -->
                                    <input type="hidden" name="kodeAntrian" id="kodeAntrian" value="<?= $kodeAntrian ?>">
                                    <input type="hidden" name="kodeRM" id="kodeRM" value="<?= $kodeRM ?>">
                                    <input type="hidden" name="param" id="param" value="<?= $param ?>">

                                    <div class="col-sm-12 col-lg-2 mb-5">
                                        <div class="card card-custom">
                                            <div class="card-body">
                                                <button type="button" class="btn btn-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormPemeriksaan()">
                                                    <i class="fas fa-stethoscope d-block mb-2 pr-0 fa-2x"></i> <strong>PEMERIKSAAN PASIEN</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormFee()">
                                                    <i class="fas fa-hand-holding-usd d-block mb-2 pr-0 fa-2x"></i> <strong>FEE</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormTindakan()">
                                                    <i class="fas fa-screwdriver d-block mb-2 pr-0 fa-2x"></i> <strong>TINDAKAN</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormObat()">
                                                    <i class="fas fa-pills d-block mb-2 pr-0 fa-2x"></i> <strong>OBAT</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormAlkes()">
                                                    <i class="fas fa-syringe d-block mb-2 pr-0 fa-2x"></i> <strong>ALKES</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormPaketLaboratorium()">
                                                    <i class="fas fa-tags d-block mb-2 pr-0 fa-2x"></i> <strong>PAKET LAB</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormLaboratorium()">
                                                    <i class="fas fa-vials d-block mb-2 pr-0 fa-2x"></i> <strong>LABORATORIUM</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormEscort()">
                                                    <i class="fas fa-ambulance d-block mb-2 pr-0 fa-2x"></i> <strong>ESCORT</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormSurat()">
                                                    <i class="fas fa-file-signature d-block mb-2 pr-0 fa-2x"></i> <strong>SURAT - SURAT</strong>
                                                </button>
                                                <button type="button" class="btn btn-light-primary btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormPembayaran()">
                                                    <i class="fas fa-file-invoice-dollar d-block mb-2 pr-0 fa-2x"></i> <strong>BILLING</strong>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-lg-8 mb-5" id="formDetailPembayaran">
                                        <!-- FORM DETAIL PEMERIKSAAN -->
                                    </div>

                                    <div class="col-sm-12 col-lg-2">
                                        <!-- FORM DETAIL PASIEN -->
                                        <div class="card card-custom">
                                            <div class="card-body">
                                                <!-- PATIENT -->
                                                <div class="d-flex align-items-end mb-7">
                                                    <div class="d-flex align-items-center">
                                                        <!-- PATIENT IMAGE -->
                                                        <div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
                                                            <div class="symbol symbol-circle">
                                                                <img src="<?= $iconPasien ?>" alt="image" />
                                                            </div>
                                                        </div>
                                                        <!-- END PATIENT IMAGE -->
                                                        <!-- PATIENT NAME -->
                                                        <div class="d-flex flex-column justify-content-around">
                                                            <span class="text-muted d-block">Detail Pasien</span>
                                                            <span class="text-dark font-weight-bolder">
                                                                <?= $dataPasien['namaPasien'] ?>
                                                            </span>
                                                        </div>
                                                        <!-- END PATIENT NAME -->
                                                    </div>
                                                </div>
                                                <!-- END PATIENT -->
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <hr>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block">Tanggal Lahir</span>
                                                            <span class="text-dark font-weight-bolder mb-0">
                                                                <?= tanggalTerbilang($dataPasien['tanggalLahir']) ?> (<?= $umur['umur'] ?> Tahun)
                                                            </span>
                                                        </div>
                                                        <hr>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block">Tanggal Pendaftaran</span>
                                                            <span class="text-dark font-weight-bolder mb-0">
                                                                <?= tanggalTerbilang($dataPasien['tanggalPendaftaran']) ?>
                                                            </span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block">Admisi</span>
                                                            <span class="text-dark font-weight-bolder mb-0">
                                                                <?= $dataPasien['namaAdmisi'] ?>
                                                            </span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block">Jenis Pasien</span>
                                                            <span class="text-dark-75 font-weight-bolder"><?= $dataPasien['jenisHarga'] ?></span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block">Alergi</span>
                                                            <span class="text-dark-75 font-weight-bolder"><?= $dataPasien['alergi'] ?></span>
                                                        </div>

                                                        <div class="mb-2">
                                                            <span class="text-muted d-block">Keluhan</span>
                                                            <span class="text-dark-75 font-weight-bolder"><?= $dataPasien['keluhan'] ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-12">
                                                        <hr>
                                                        <strong class="d-block mb-2"><i class="fas fa-history pr-2" style="font-size: 12px;"></i> TIMESTAMP RECORD</strong>
                                                        <div class="container-fluid border rounded p-3 mb-3">
                                                            <span class="text-muted d-block">Kedatangan</span>
                                                            <span class="text-dark-75 font-weight-bolder"><?= $dataPasien['waktuKedatangan'] ?></span>
                                                        </div>
                                                        <div class="container-fluid border rounded p-3 mb-3">
                                                            <span class="text-muted d-block">Pemeriksaan</span>
                                                            <span class="text-dark-75 font-weight-bolder"><?= $dataPasien['waktuPemeriksaan'] ?></span>
                                                        </div>
                                                        <div class="container-fluid border rounded p-3 mb-3">
                                                            <span class="text-muted d-block">Kepulangan</span>
                                                            <span class="text-dark-75 font-weight-bolder"><?= $dataPasien['waktuKepulangan'] ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- END ENTRY -->
                    </div>
                    <!-- END CONTENT -->

                    <!-- FOOTER -->
                    <?php
                    footer(BASE_URL_HTML);
                    ?>
                    <!-- END FOOTER -->

                </div>
            </div>
        </div>

        <!-- USER PANEL -->
        <?php
        userPanel(BASE_URL_HTML, $db, $idUserAsli);
        ?>
        <!-- END USER PANEL-->

        <!-- JS UTAMA SEMUA HALAMAN -->
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/ktappsettings.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/plugins/global/plugins.bundle.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/plugins/custom/ckeditor/ckeditor-classic.bundle.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/js/scripts.bundle.js"></script>
        <!-- END JS UTAMA SEMUA HALAMAN -->

        <!-- JS KHUSUS HALAMAN INI -->
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/notifikasi.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/validasiform.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/rupiah.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/btn-tab.js"></script>
        <script src="js/seksi-pemeriksaan.js"></script>
        <script src="js/seksi-alkes.js"></script>
        <script src="js/seksi-obat.js"></script>
        <script src="js/seksi-tindakan.js"></script>
        <script src="js/seksi-paket-lab.js"></script>
        <script src="js/seksi-laboratorium.js"></script>
        <script src="js/seksi-fee.js"></script>
        <script src="js/seksi-escort.js"></script>
        <script src="js/seksi-surat.js"></script>
        <script src="js/seksi-pembayaran.js"></script>
        <script src="js/seksi-finalisasi.js"></script>
        <script src="js/section-init.js"></script>
        <!-- END JS KHUSUS HALAMAN INI -->
    </body>
    <!-- END BODY -->

    </html>
<?php
}
?>