<?php
include_once '../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('LIBRARY_DIR')}/fungsinomor.php";
include_once "{$constant('BASE_URL_PHP')}{$constant('MAIN_DIR')}/fungsinavigasi.php";

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

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !validateIP($_SESSION['IP_ADDR'])) {
    http_response_code(403);
    exit('Authentication Failed');
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        extract($_POST, EXTR_SKIP);
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

        $tokenValid = hash_equals($_SESSION['tokenCSRF'], $result['tokenCSRF'] ?? '');
        $nonceValid = hash_equals($_SESSION[$result['kodeFile'] . '_nonce'], $result['nonce'] ?? '');

        if (!$tokenValid || !$nonceValid) {
            $status = false;
            $pesan = 'Authentication Token Invalid';
        } else {
            if (file_exists($result['tmp'])) {
                $status = unlink($result['tmp']);

                if ($status) {
                    unset($_SESSION[$result['kodeFile'] . '_nonce']);

                    $status = true;
                    $pesan = 'Proses Berhasil';
                } else {
                    $status = false;
                    $pesan = 'File Tidak Ditemukan';
                }
            } else {
                $status = false;
                $pesan = 'File Tidak Ditemukan';
            }
        }

        echo json_encode([
            'status' => $status,
            'pesan' => $pesan
        ]);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        extract($_GET, EXTR_SKIP);

        if (!isset($param)) {
            http_response_code(400);
            exit('Bad Request');
        } else {
            parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

            $tokenValid = hash_equals($_SESSION['tokenCSRF'], $result['tokenCSRF'] ?? '');

            if (!$tokenValid) {
                http_response_code(403);
                exit('Authentication Token Invalid');
            }

            $kodeFile = $result['kodeFile'] ?? '';
        }

?>
        <!DOCTYPE html>
        <html lang="en">
        <!-- HEAD -->

        <head>
            <meta charset="utf-8">
            <title>Preview File | <?= PAGE_TITLE; ?></title>
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

        <body id="kt_body" class="header-fixed header-mobile-fixed page-loading">

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
                                        <button type="button" onclick="clearPreview()" class="btn btn-light-danger btn-sm">
                                            <i class="fa fa-arrow-left"></i> Back
                                        </button>
                                    </div>
                                    <!-- END TOPBAR USER -->

                                </div>
                                <!-- END TOPBAR -->
                            </div>
                        </div>
                        <!-- END HEADER -->

                        <!-- CONTENT -->
                        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

                            <!-- ENTRY -->
                            <div class="d-flex flex-column-fluid">
                                <div class="container d-flex justify-content-center align-items-center">
                                    <?php

                                    $file = statementWrapper(
                                        DML_SELECT,
                                        'SELECT * FROM uploaded_file WHERE kodeFile = ?',
                                        [$kodeFile]
                                    );

                                    if ($file) {

                                        $absPath = ABS_PATH_FILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['folder'] . DIRECTORY_SEPARATOR . $file['fileName'];
                                        $relPath = REL_PATH_FILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['folder'] . DIRECTORY_SEPARATOR . $file['fileName'];

                                        if (!isset($_SESSION[$kodeFile . '_nonce'])) {
                                            $fileNonce = bin2hex(random_bytes(8));
                                            $_SESSION[$file['kodeFile'] . '_nonce'] = $fileNonce;
                                        } else {
                                            $fileNonce = $_SESSION[$kodeFile . '_nonce'];
                                        }

                                        $tempFile = $file['kodeFile'] . $fileNonce . '.' . $file['ekstensi'];

                                        $tempAbsDir = BASE_URL_PHP . MAIN_DIR . '/preview/tmp/' . $tempFile;
                                        $tempRelDir = BASE_URL_HTML . MAIN_DIR . '/preview/tmp/' . $tempFile;

                                        if (file_exists($absPath) && !file_exists($tempAbsDir)) {
                                            $status = copy($absPath, $tempAbsDir);
                                        }

                                        switch ($file['ekstensi']) {
                                            case 'png':
                                            case 'jpeg':
                                            case 'jpg':
                                    ?>
                                                <img src="<?= $tempRelDir ?>" alt="Preview Image" width="1200">
                                            <?php
                                                break;
                                            case 'pdf':
                                            ?>
                                                <embed type="application/pdf" src="<?= $tempRelDir ?>" width="1400" height="750"></embed>
                                        <?php
                                                break;
                                        }

                                        $clear = rawurlencode(enkripsi(http_build_query([
                                            'kodeFile' => $kodeFile,
                                            'tokenCSRF' => $_SESSION['tokenCSRF'],
                                            'nonce' => $fileNonce,
                                            'tmp' => $tempAbsDir,
                                        ]), secretKey()))

                                        ?>
                                        <input type="hidden" name="clear" value="<?= $clear ?>">
                                    <?php
                                    } else {
                                    ?>
                                        <div class="container-fluid text-center">
                                            <img src="<?= BASE_URL_HTML ?>/assets/media/error/file-not-found.png" alt="">
                                            <br>
                                            <br>
                                            <h1><strong>RECORD FILE TIDAK DITEMUKAN</strong></h1>
                                        </div>
                                    <?php
                                    }

                                    ?>
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
            <!-- END JS KHUSUS HALAMAN INI -->
            <script>
                window.addEventListener('beforeunload', function(evt) {
                    evt.preventDefault();
                    evt.returnValue = '';
                })

                function clearPreview() {
                    const clear = $('input[name=clear]').val();
                    if (clear) {
                        $.ajax({
                            url: window.location.pathname,
                            method: 'POST',
                            data: {
                                param: clear
                            },
                            dataType: 'json',
                            success: function(data) {
                                history.back();
                            }
                        });
                    } else {
                        history.back();
                    }
                }
            </script>
        </body>
        <!-- END BODY -->

        </html>
<?php
    }
}
