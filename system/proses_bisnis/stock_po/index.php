<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
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
    $rentang = date('Y-m-01') . ' - ' . date('Y-m-t');
?>

    <!DOCTYPE html>
    <html lang="en">
    <!-- HEAD -->

    <head>
        <meta charset="utf-8">
        <title><?= $dataCekMenu['namaSubMenu']; ?> | <?= PAGE_TITLE; ?></title>
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

    <body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

        <!-- HEADER MOBILE -->
        <?php
        headerMobile(BASE_URL_HTML);
        ?>
        <!-- END HEADER MOBILE -->

        <div class="d-flex flex-column flex-root">
            <div class="d-flex flex-row flex-column-fluid page">
                <!-- ASIDE-->
                <?php
                aside(BASE_URL_HTML, $db, $idUserAsli, $dataCekMenu['idMenu'], $dataCekMenu['idSubMenu']);
                ?>
                <!-- END ASIDE -->

                <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">
                    <!-- HEADER -->
                    <div id="kt_header" class="header header-fixed">
                        <div class="container-fluid d-flex align-items-stretch justify-content-between">

                            <div class="header-menu-wrapper header-menu-wrapper-left" id="kt_header_menu_wrapper">
                                <!-- HEADER MENU-->
                                <?php
                                headerMenu(BASE_URL_HTML);
                                ?>
                                <!-- END HEADER MENU -->
                            </div>

                            <!-- TOPBAR -->
                            <div class="topbar">
                                <!-- SEARCH -->
                                <!-- <div class="dropdown" id="kt_quick_search_toggle">

                                    <div class="topbar-item" data-toggle="dropdown" data-offset="10px,0px">
                                        <div class="btn btn-icon btn-clean btn-lg btn-dropdown mr-1">
                                            <span class="svg-icon svg-icon-xl svg-icon-primary">
                                                <i class="fas fa-search text-primary"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="dropdown-menu p-0 m-0 dropdown-menu-right dropdown-menu-anim-up dropdown-menu-lg">
                                        <div class="quick-search quick-search-dropdown">

                                            <div class="input-group quick-search-form">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <span class="svg-icon svg-icon-lg">
                                                            <i class="fas fa-search"></i>
                                                        </span>
                                                    </span>
                                                </div>
                                                <input type="text" id="kataKunciData" class="form-control" placeholder="Search...">
                                            </div>

                                        </div>
                                    </div>
                                </div> -->
                                <!-- END SEARCH -->

                                <!-- TOPBAR USER -->
                                <?php
                                topbarUser(BASE_URL_HTML, $db, $idUserAsli);
                                ?>
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

                                        <h5 class="text-dark font-weight-bold my-1 mr-5"><?= $dataCekMenu['namaSubMenu'] ?></h5>

                                        <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                                            <li class="breadcrumb-item">
                                                <a href="#" class="text-muted">General</a>
                                            </li>
                                            <li class="breadcrumb-item">
                                                <a href="#" class="text-muted"><?= $dataCekMenu['namaSubMenu'] ?></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- END SUB HEADER -->


                        <!-- ENTRY -->
                        <div class="d-flex flex-column-fluid">
                            <div class="container">
                                <!-- CARD -->
                                <div id="kt_page_sticky_card" class="card card-custom card-sticky">
                                    <div class="card-header">
                                        <!-- CARD TITLE -->
                                        <div class="card-title">
                                            <h3 class="card-label"><i class="fas fa-stream text-dark pr-4"></i> <strong>DAFTAR PO</strong></h3>
                                        </div>
                                        <!-- END CARD TITLE -->
                                        <!-- CARD TOOLBAR -->
                                        <div class="card-toolbar">
                                            <a href="detail/" class="btn btn-danger font-weight-bolder">
                                                <i class="fas fa-plus-circle pr-4"></i> <strong>PO BARU</strong>
                                            </a>
                                        </div>
                                        <!-- END CARD TOOLBAR -->
                                    </div>

                                    <!-- CARD BODY -->
                                    <div class="card-body">

                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="periodePO"><i class="fas fa-calendar-alt"></i> RENTANG</label>
                                                <div class="input-group">
                                                    <input type="text" id="periodePO" class="form-control" data-date-range="true" onchange="dataDaftarStockPO()" value="<?= $rentang ?>">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><i class="la la-calendar-check-o"></i></span>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="form-group col-md-3">
                                                <label><i class="fas fa-user-tie"></i> Vendor</label>
                                                <select id="kodeVendor" name="kodeVendor" class="form-control selectpicker" data-live-search="true" onchange="dataDaftarStockPO()">
                                                    <option value="Semua">Pilih Vendor</option>
                                                    <?php
                                                    $opsi =  selectStatement('SELECT * FROM vendor WHERE jenisVendor = ?', ['Supplier']);
                                                    foreach ($opsi as $row) {
                                                    ?>
                                                        <option value="<?= $row['kodeVendor'] ?>"><?= $row['nama'] ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label><i class="fas fa-user-tie"></i> APPROVAL </label>
                                                <select id="statusPersetujuan" name="statusPersetujuan" class="form-control selectpicker" data-live-search="true" onchange="dataDaftarStockPO()">
                                                    <option value="Semua">Semua</option>
                                                    <?php
                                                    $opsi =  array('Pending', 'Reject', 'Approve');
                                                    foreach ($opsi as $row) {
                                                    ?>
                                                        <option value="<?= $row ?>"><?= $row ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label><i class="fas fa-user-tie"></i> STATUS PO </label>
                                                <select id="statusPO" name="statusPO" class="form-control selectpicker" data-live-search="true" onchange="dataDaftarStockPO()">
                                                    <option value="Semua">Semua</option>
                                                    <?php
                                                    $opsi =  array('Diproses', 'Diterima');
                                                    foreach ($opsi as $row) {
                                                    ?>
                                                        <option value="<?= $row ?>"><?= $row ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- TABLE -->
                                        <div class="table-responsive" id="dataDaftarStockPO">
                                            <!-- DISINI TAMPIL DAFTAR HOTEL YANG SUDAH DIBUAT -->
                                        </div>
                                        <!-- END TABLE -->
                                    </div>
                                    <!-- END CARD BODY -->
                                </div>
                                <!-- END CARD -->
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
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/notifikasi.js"></script>
        <script src="<?= BASE_URL_HTML ?>/assets/custom_js/validasiform.js"></script>
        <script src="js/stock_po.js"></script>
        <!-- END JS KHUSUS HALAMAN INI -->
    </body>
    <!-- END BODY -->

    </html>
<?php
}
?>