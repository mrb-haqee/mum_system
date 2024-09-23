<?php

function icon($BASE_URL_HTML)
{
?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $BASE_URL_HTML ?>/assets/media/logos/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $BASE_URL_HTML ?>/assets/media/logos/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $BASE_URL_HTML ?>/assets/media/logos/favicon-16x16.png">
    <link rel="manifest" href="<?= $BASE_URL_HTML ?>/assets/media/logos/site.webmanifest">
<?php
}

function headerMobile($BASE_URL_HTML)
{
?>
    <div id="kt_header_mobile" class="header-mobile align-items-center header-mobile-fixed">
        <!-- LOGO -->
        <a href="<?= $BASE_URL_HTML ?><?= MAIN_DIR ?>/">
            <img alt="Logo" src="<?= $BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/logos/sidebar-icon.png" style="width:60%;">
        </a>
        <!-- END LOGO -->

        <!-- TOOLBAR -->
        <div class="d-flex align-items-center">
            <!-- ASIDE MOBILE TOGGLE -->
            <button class="btn p-0 burger-icon burger-icon-left" id="kt_aside_mobile_toggle">
                <span></span>
            </button>
            <!-- END ASIDE MOBILE TOGGLE -->
            <!-- HEADER MENU MOBILE TOGGLE -->
            <button class="btn p-0 burger-icon ml-4" id="kt_header_mobile_toggle">
                <span></span>
            </button>
            <!-- END HEADER MENU MOBILE TOGGLE -->
            <!-- TOPBAR MOBILE TOGGLE -->
            <button class="btn btn-hover-text-primary p-0 ml-2" id="kt_header_mobile_topbar_toggle">
                <span class="svg-icon svg-icon-xl">
                    <i class="fa fa-user"></i>
                </span>
            </button>
            <!-- END TOPBAR MOBILE TOGGLE -->
        </div>
        <!-- END TOOLBAR -->
    </div>
<?php
}

function aside($BASE_URL_HTML, $db, $idUserAsli, $idMenuAktif, $idSubMenuAktif)
{

    //MENCARI DETAIL DARI USER YANG LOGIN
    $sqlUser = $db->prepare('SELECT user.*,pegawai.namaPegawai FROM user INNER JOIN pegawai ON user.idPegawai = pegawai.idPegawai WHERE user.idUser = ?');
    $sqlUser->execute([$idUserAsli]);
    $dataUser = $sqlUser->fetch();

    $classUserAktif = '';

    if ($idMenuAktif == 0) {
        $classUserAktif = 'menu-item-active';
    }
?>

    <div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto" id="kt_aside">
        <!-- BRAND -->
        <div class="brand flex-column-auto" id="kt_brand">
            <!-- LOGO -->
            <a href="<?= $BASE_URL_HTML ?><?= MAIN_DIR ?>/" class="brand-logo">
                <img alt="Logo" src="<?= $BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/logos/sidebar-icon.png" style="width:90%;">
            </a>
            <!-- END LOGO-->

            <button class="brand-toggle btn btn-sm px-0" id="kt_aside_toggle">
                <span class="svg-icon svg-icon svg-icon-xl">
                    <i class="fas fa-angle-double-left"></i>
                </span>
            </button>

        </div>
        <!-- END BRAND -->

        <!-- ASIDE MENU -->
        <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
            <div id="kt_aside_menu" class="aside-menu my-4" data-menu-vertical="1" data-menu-scroll="1" data-menu-dropdown-timeout="500">
                <!-- MENU NAV -->
                <ul class="menu-nav">
                    <li class="menu-item <?= $classUserAktif ?>" aria-haspopup="true">
                        <a href="<?= $BASE_URL_HTML ?><?= MAIN_DIR ?>/" class="menu-link">
                            <i class="menu-icon flaticon-user"></i>
                            <span class="menu-text"><?= $dataUser['namaPegawai'] ?></span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <h4 class="menu-text">Menu</h4>
                        <i class="menu-icon ki ki-bold-more-hor icon-md"></i>
                    </li>

                    <?php
                    //MENCARI DAFTAR MENU UNTUK USER YANG LOGIN
                    $sqlMenu = $db->prepare(
                        'SELECT 
                            * 
                        FROM 
                            user_detail
                            INNER JOIN menu_sub ON menu_sub.idSubMenu = user_detail.idSubMenu
                            INNER JOIN menu ON menu.idMenu = menu_sub.idMenu
                        WHERE
                            user_detail.idUser = ?
                            GROUP BY menu_sub.idMenu
                            ORDER BY menu.indexSort'
                    );
                    $sqlMenu->execute([$idUserAsli]);
                    $dataMenu = $sqlMenu->fetchAll();


                    foreach ($dataMenu as $rowMenu) {
                        //MENCARI MENU YANG AKTIF
                        $classAktifMenu = '';
                        if ($idMenuAktif == $rowMenu['idMenu']) {
                            $classAktifMenu = 'menu-item-open menu-item-here';
                        }
                    ?>
                        <li class="menu-item menu-item-submenu <?= $classAktifMenu ?>" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <i class="menu-icon <?= $rowMenu['iconClass'] ?>"></i>
                                <span class="menu-text"><?= $rowMenu['namaMenu'] ?></span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text"><?= $rowMenu['namaMenu'] ?></span>
                                        </span>
                                    </li>
                                    <?php
                                    $sqlSubMenu = $db->prepare(
                                        'SELECT 
                                            * 
                                        FROM 
                                            user_detail
                                            INNER JOIN menu_sub ON menu_sub.idSubMenu = user_detail.idSubMenu
                                        WHERE 
                                            user_detail.idUser=?
                                            AND menu_sub.idMenu=?
                                            ORDER BY menu_sub.indexSort'
                                    );
                                    $sqlSubMenu->execute([
                                        $idUserAsli,
                                        $rowMenu['idMenu']
                                    ]);
                                    $dataSubMenu = $sqlSubMenu->fetchAll();
                                    foreach ($dataSubMenu as $row) {
                                        //MENCARI SUB MENU YANG AKTIF
                                        $classAktifSubMenu = '';
                                        if ($idSubMenuAktif == $row['idSubMenu']) {
                                            $classAktifSubMenu = 'menu-item-active';
                                        }
                                    ?>
                                        <li class="menu-item <?= $classAktifSubMenu ?>" aria-haspopup="true">
                                            <a href="<?= $BASE_URL_HTML ?><?= MAIN_DIR ?>/<?= $rowMenu['namaKelompok'] ?>/<?= $row['namaFolder'] ?>" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot">
                                                    <span></span>
                                                </i>
                                                <span class="menu-text"><?= $row['namaSubMenu'] ?></span>
                                            </a>
                                        </li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                        </li>
                    <?php
                    }
                    ?>
                </ul>
                <!-- END MENU NAV -->
            </div>
        </div>
        <!-- END ASIDE MENU -->
    </div>
<?php
}

function headerMenu($BASE_URL_HTML)
{
    //$idCabang = dekripsi($_SESSION['enc_idCabang'], secretKey());
    $idCabang = '1';

    $dataCabang = statementWrapper(
        DML_SELECT,
        "SELECT * FROM cabang WHERE idCabang = ?",
        [$idCabang]
    );
?>
    <div id="kt_header_menu" class="header-menu header-menu-mobile header-menu-layout-default">
        <!-- HEADER NAV -->
        <ul class="menu-nav">
            <li class="menu-item menu-item-submenu menu-item-active" data-menu-toggle="click" aria-haspopup="true">
                <a href="javascript:;" class="menu-link menu-toggle">
                    <span class="menu-text"><i class="fas fa-hospital pr-4"></i><strong>CABANG <?= $dataCabang['nama']; ?></strong></span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="menu-submenu menu-submenu-fixed menu-submenu-left" style="width:1000px">
                    <div class="menu-subnav">
                        <ul class="menu-content">
                            <li class="menu-item">
                                <h3 class="menu-heading menu-toggle">
                                    <i class="menu-bullet menu-bullet-dot">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">GANTI CABANG</span>
                                    <i class="menu-arrow"></i>
                                </h3>
                                <ul class="menu-inner">
                                    <li class="menu-item" aria-haspopup="true">
                                        <a href="javascript:;" class="menu-link">
                                            <i class="menu-bullet menu-bullet-line">
                                                <span></span>
                                            </i>
                                            <span class="menu-text">COMING SOON</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </li>
        </ul>
        <!-- END HEADER NAV -->
    </div>
<?php
}

function topbarUser($BASE_URL_HTML, $db, $idUserAsli)
{
    //MENCARI DETAIL USER YANG LOGIN
    $sqlUser = $db->prepare('SELECT * FROM user WHERE user.idUser = ?');
    $sqlUser->execute([$idUserAsli]);
    $dataUser = $sqlUser->fetch();

?>
    <div class="topbar-item">
        <div class="btn btn-icon btn-icon-mobile w-auto btn-clean d-flex align-items-center btn-lg px-2" id="kt_quick_user_toggle">
            <span class="text-muted font-weight-bold font-size-base d-none d-md-inline mr-1">Hi,</span>
            <span class="text-dark-50 font-weight-bolder font-size-base d-none d-md-inline mr-3"><?= $dataUser['userName'] ?></span>
            <span class="symbol symbol-lg-35 symbol-25 symbol-light-success">
                <span class="symbol-label font-size-h5 font-weight-bold text-upercase"><?= $dataUser['userName'][0] ?></span>
            </span>
        </div>
    </div>
<?php
}

function userPanel($BASE_URL_HTML, $db, $idUserAsli)
{
    //MENCARI DETAIL DARI USER YANG LOGIN
    $sqlUser = $db->prepare('SELECT user.*,pegawai.namaPegawai FROM user INNER JOIN pegawai ON user.idPegawai = pegawai.idPegawai WHERE user.idUser = ?');
    $sqlUser->execute([$idUserAsli]);
    $dataUser = $sqlUser->fetch();
?>
    <div id="kt_quick_user" class="offcanvas offcanvas-right p-10">
        <!-- HEADER -->
        <div class="offcanvas-header d-flex align-items-center justify-content-between pb-5">
            <h3 class="font-weight-bold m-0">User Profile
                <small class="text-muted font-size-sm ml-2">User Profile</small>
            </h3>
            <a href="#" class="btn btn-xs btn-icon btn-light btn-hover-primary" id="kt_quick_user_close">
                <i class="ki ki-close icon-xs text-muted"></i>
            </a>
        </div>
        <!-- END HEADER -->

        <!-- CONTENT -->
        <div class="offcanvas-content pr-5 mr-n5">
            <!-- HEADER -->
            <div class="d-flex align-items-center mt-5">
                <div class="symbol symbol-100 mr-5">
                    <div class="symbol-label" style="background-image:url('<?= $BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/users/user1.jpg')"></div>
                    <i class="symbol-badge bg-success"></i>
                </div>
                <div class="d-flex flex-column">
                    <a href="#" class="font-weight-bold font-size-h5 text-dark-75 text-hover-primary"><?= $dataUser['namaPegawai'] ?></a>
                    <div class="text-muted mt-1">Kasir</div>
                    <div class="navi mt-2">
                        <!-- <a href="#" class="navi-item">
                            <span class="navi-link p-0 pb-2">
                                <span class="navi-icon mr-1">
                                    <span class="svg-icon svg-icon-lg svg-icon-primary">
                                        <i class="fas fa-mail-bulk"></i>
                                    </span>
                                </span>
                                <span class="navi-text text-muted text-hover-primary">jm@softplus.com</span>
                            </span>
                        </a> -->
                        <a href="<?= $BASE_URL_HTML ?>/library/proseslogout.php" class="btn btn-sm btn-light-primary font-weight-bolder py-2 px-5">Sign Out</a>
                    </div>
                </div>
            </div>
            <!-- END HEADER -->

            <div class="separator separator-dashed my-7"></div>

            <!-- NOTIFICATIONS-->
            <div>
                <h5 class="mb-5">Recent Notifications</h5>
                <!-- NOTIFICATION ITEM -->
                <div class="d-flex align-items-center bg-light-warning rounded p-5 gutter-b">
                    <span class="svg-icon svg-icon-warning mr-5">
                        <span class="svg-icon svg-icon-lg">
                            <i class="fa fa-info-circle text-warning"></i>
                        </span>
                    </span>
                    <div class="d-flex flex-column flex-grow-1 mr-2">
                        <a href="#" class="font-weight-normal text-dark-75 text-hover-primary font-size-lg mb-1">Another purpose persuade</a>
                        <span class="text-muted font-size-sm">Due in 2 Days</span>
                    </div>
                    <span class="font-weight-bolder text-warning py-1 font-size-lg">+28%</span>
                </div>
                <!-- END NOTIFICATION ITEM -->
            </div>
            <!-- END NOTIFICATION -->
        </div>
        <!-- END CONTENT -->
    </div>
<?php
}

function footer($BASE_URL_HTML)
{
?>
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
<?php
}

?>