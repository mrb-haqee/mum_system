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
        $result = decryptURLParam($param);
        $kodeBudgetingProject = $result['kode'];

        if (isset($result['kodeProgres'])) {
            $kodeBudgetingProjectProgres = $result['kodeProgres'];
        } else {
            $kodeBudgetingProjectProgres = nomorUrut($db, 'budgeting_project_progres', $idUserAsli);
        }
    }

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
        <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/custom/dropify/css/dropify.min.css">
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
                                            <img alt="Logo" src="<?= BASE_URL_HTML ?>/assets/media/logos/page-detail-icon.png" style="widt;">
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
                                    <a href="../" class="btn btn-light-danger btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back
                                    </a>
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

                                        <h5 class="text-dark font-weight-bold my-1 mr-5">Detail Progres <?= $dataCekMenu['namaSubMenu']; ?></h5>

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
                                    <input type="hidden" name="kodeBudgetingProject" id="kodeBudgetingProject" value="<?= $kodeBudgetingProject ?>">
                                    <input type="hidden" name="kodeBudgetingProjectProgres" id="kodeBudgetingProjectProgres" value="<?= $kodeBudgetingProjectProgres ?>">

                                    <div class="col-sm-12 col-lg-2 mb-5">
                                        <div class="card card-custom">
                                            <div class="card-body">
                                                <button type="button" class="btn btn-danger btn-seksi-informasi-tab text-center mb-2" style="width:100%;" onclick="seksiFormInformasi()">
                                                    <i class="fas fa-money-check-alt d-block mb-2 pr-0 fa-2x"></i> <strong>INFORMASI PROGRES</strong>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-lg-10" id="formDetailBudgetingProject">
                                        <!-- FORM DETAIL HOTEL -->
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
        <script src="<?= BASE_URL_HTML ?>/assets/js/scripts.bundle.js"></script>
        <!-- END JS UTAMA SEMUA HALAMAN -->

        <!-- JS KHUSUS HALAMAN INI -->

        <script src="<?= BASE_URL_HTML ?>/assets/plugins/custom/dropify/js/dropify.min.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/notifikasi.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/validasiform.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/rupiah.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/btn-tab.js"></script>
        <script src="js/seksi-informasi.js"></script>
        <script src="js/section-init.js"></script>
        <!-- END JS KHUSUS HALAMAN INI -->
    </body>
    <!-- END BODY -->

    </html>
<?php
}
?>