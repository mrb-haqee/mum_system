<?php
include_once '../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenissurat.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasivalidator.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiqrcode.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/{$constant('MAIN_DIR')}/fungsinavigasi.php";

extract($_GET, EXTR_SKIP);

?>

<!DOCTYPE html>
<html lang="en">
<!-- HEAD -->

<head>
    <meta charset="utf-8">
    <title>QR Validator | <?= PAGE_TITLE; ?></title>
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

    <?php
    icon(BASE_URL_HTML);
    ?>
</head>
<!-- END HEAD -->

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

    <!-- HEADER MOBILE -->
    <div id="kt_header_mobile" class="header-mobile align-items-center header-mobile-fixed">
        <!-- LOGO -->
        <a href="<?= BASE_URL_HTML ?><?= MAIN_DIR ?>/">
            <img alt="Logo" src="<?= BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/logos/text-logo-white.png" style="width:35%;">
        </a>
        <!-- END LOGO -->

        <!-- TOOLBAR -->
        <div class="d-flex align-items-center">
        </div>
        <!-- END TOOLBAR -->
    </div>
    <!-- END MOBILE HEADER-->

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

                                    <h5 class="text-dark font-weight-bold my-1 mr-5">QR Validator</h5>

                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- END SUB HEADER -->

                    <!-- ENTRY -->
                    <div class="d-flex flex-column-fluid">
                        <div class="container bg-white rounded shadow p-5">
                            
                            <?php
                            $result = [];

                            if (isset($param)) {

                                $decrypted = dekripsi(rawurldecode($param), secretKey());

                                if ($decrypted) {
                                    parse_str($decrypted, $result);

                                    $validator = configValidator()[$result['jenisSurat']];

                                    if (file_exists($validator['path'])) {
                                        include_once $validator['path'];
                                        call_user_func($validator['init_function'], $validator['requirements'], $result);
                                    }
                                } else {
                                }
                            }
                            ?>
                        </div>
                    </div> <!-- END ENTRY -->
                </div>
                <!-- END CONTENT -->

                <!-- FOOTER -->
                <div class="footer bg-white py-4 d-flex flex-lg-column" id="kt_footer">
                    <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
                        <!-- COPYRIGHT -->
                        <div class="text-dark order-2 order-md-1">
                            <span class="text-muted font-weight-bold mr-2">2023 &copy;</span>
                            <a href="https://www.tempatkitasoftware.com/" target="_blank" class="text-dark-75 text-hover-primary">tempatKita Software</a>
                        </div>
                        <!-- END COPYRIGHT -->

                        <!-- NAV -->
                        <div class="nav nav-dark">
                            <a href="https://www.tempatkitasoftware.com/" target="_blank" class="nav-link pl-0 pr-5">About</a>
                            <a href="https://www.tempatkitasoftware.com/" target="_blank" class="nav-link pl-0 pr-5">Team</a>
                            <a href="https://www.tempatkitasoftware.com/" target="_blank" class="nav-link pl-0 pr-0">Contact</a>
                        </div>
                        <!-- END NAV -->
                    </div>
                </div>
                <!-- END FOOTER-->

            </div>
        </div>
    </div>

    <!-- JS UTAMA SEMUA HALAMAN -->
    <script src="<?= BASE_URL_HTML ?>/assets/custom_js/ktappsettings.js"></script>
    <script src="<?= BASE_URL_HTML ?>/assets/plugins/global/plugins.bundle.js"></script>
    <script src="<?= BASE_URL_HTML ?>/assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
    <script src="<?= BASE_URL_HTML ?>/assets/js/scripts.bundle.js"></script>
    <!-- END JS UTAMA SEMUA HALAMAN -->
    <script src="<?= BASE_URL_HTML ?>/assets/custom_js/validasiform.js"></script>
</body>
<!-- END BODY -->

</html>
<?php
