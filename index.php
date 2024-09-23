<?php

session_start();

include_once 'library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";

extract($_GET, EXTR_SKIP);
// $_SESSION['attempt'] = 2;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login Page | <?= PAGE_TITLE; ?></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="TempatKita Software">
    <meta name="robots" content="noindex, nofollow">

    <!-- CSS UTAMA HALAMAN LOGIN -->
    <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/global/plugins.bundle.css">
    <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/custom/prismjs/prismjs.bundle.css">
    <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/style.bundle.css">
    <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/pages/login/login-2.css">
    <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/style.bundle.css">
    <link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/custom/toastr/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <!-- END CSS UTAMA HALAMAN LOGIN -->

    <!-- ICON -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/media/logos/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/media/logos/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/media/logos/favicon-16x16.png">
    <link rel="manifest" href="assets/media/logos/site.webmanifest">
    <!-- END ICON -->

    <!-- CSS CUSTOM -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/custom_css/loader.css">
    <!-- END CSS CUSTOM -->
</head>

<!-- Loader Over Lay -->
<div class="overlay">
    <div class="overlay__inner">
        <div class="overlay__content">
            <span class="spinner"></span>
        </div>
    </div>
</div>
<!-- End Loader -->

<body>
    <div class="d-flex flex-column flex-root">
        <div class="login login-2 login-signin-on d-flex flex-column flex-lg-row flex-column-fluid bg-white">
            <div class="login-aside order-2 order-lg-1 d-flex flex-row-auto position-relative overflow-hidden">
                <div class="d-flex flex-column-fluid flex-column justify-content-between py-9 px-7 py-lg-13 px-lg-35">

                    <div class="d-flex flex-column-fluid flex-column flex-center">
                        <img src="assets/media/logos/apple-touch-icon.png" alt="Login Wallpaper" style="width: 50px;">
                        <img src="assets/media/misc/login-icon.png" alt="Login Wallpaper" style="width: 400px;">

                        <!-- <h2 class="font-weight-bolder text-dark font-size-h2 font-size-h1-lg">
                                        SYSTEM
                                    </h2> -->
                        <div class="login-form login-signin py-5">

                            <form class="form" novalidate="novalidate" id="formLogin">
                                <div class="form-group">

                                </div>
                                <div class="form-group">
                                    <label class="font-size-h6 font-weight-bolder text-dark pt-5">
                                        CABANG
                                    </label>
                                    <select name="cabang" id="cabang" class="py-5 px-4 form-control form-control-solid selectpicker">

                                        <?php
                                        $opsi = statementWrapper(
                                            DML_SELECT_ALL,
                                            'SELECT * FROM cabang WHERE status = ?',
                                            ['Aktif']
                                        );

                                        foreach ($opsi as $index => $cabang) {
                                        ?>
                                            <option value="<?= enkripsi($cabang['idCabang'], secretKey()) ?>"><?= $cabang['nama']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-size-h6 font-weight-bolder text-dark">
                                        Username
                                    </label>
                                    <input class="form-control form-control-solid h-auto py-7 px-6 rounded-lg" type="text" id="username" name="username" autocomplete="off" placeholder="Username">
                                </div>

                                <div class="form-group">
                                    <label class="font-size-h6 font-weight-bolder text-dark">
                                        Password
                                    </label>
                                    <input class="form-control form-control-solid h-auto py-7 px-6 rounded-lg" type="password" id="password" name="password" autocomplete="off" placeholder="Password">
                                </div>


                                <div class="text-center pt-2">
                                    <button type="button" class="btn btn-primary font-weight-bolder font-size-h6 px-8 py-4 my-3" data-toggle="tooltip" data-placement="right" title="" onclick="prosesLogin()" id="loginBtn">
                                        <strong>LOGIN</strong>
                                    </button>
                                </div>
                            </form>

                        </div>
                        <div>
                            <p style="opacity:.8;">Copyright &copy; 2024 <strong>TempatKita Software.</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-1 order-lg-2 d-flex align-items-center justify-content-center w-100 pb-0" style="background-image: url(https://www.margautama.com/wp-content/uploads/2020/02/office1.jpg); background-size:cover;" id="img-cover">

            </div>
        </div>
    </div>

    <!-- JS UTAMA HALAMAN LOGIN -->
    <script src="assets/custom_js/ktappsettings.js"></script>
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>
    <!-- END JS UTAMA HALAMAN LOGIN -->

    <!-- JS CUSTOM -->
    <script src="assets/plugins/custom/toastr/toastr.js"></script>
    <script src="assets/custom_js/validasiform.js"></script>
    <script src="script.js"></script>
    <!-- END JS CUSTOM -->
</body>

</html>