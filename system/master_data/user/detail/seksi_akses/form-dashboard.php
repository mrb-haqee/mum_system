<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidashboard.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasihakuser.php";
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
    alertSessionExpForm();
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    extract($_POST, EXTR_SKIP);

?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fas fa-stream text-dark pr-3"></i> <strong>DAFTAR AKSES MENU "Dashboard"</strong>
                    </span>
                    <span class="mt-3 font-weight-bold font-size-sm">
                        <?= PAGE_TITLE; ?>
                    </span>
                </h3>
            </div>
            <!-- END CARD TITLE -->
            <div class="card-toolbar">
                <!-- <button class="btn btn-outline-success btn-enable-all" type="button" onclick="prosesAksesMenu('menu', '<?= $idUserAsli ?>', '__ALL__DASHBOARD__', 'Active')" data-id="DASHBOARD"><i class="fas fa-check-double pr-4"></i><strong>ENABLE ALL</strong></button> -->
            </div>
        </div>
        <!-- END CARD HEADER -->

        <!-- CARD BODY -->
        <div class="card-body">
            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
            <div class="row">
                <?php


                $config = configDashboard();

                $userDashboard = statementWrapper(
                    DML_SELECT_ALL,
                    'SELECT * FROM user_dashboard WHERE idUser = ?',
                    [$idUserAkses]
                );

                $listUserWidget = array_column($userDashboard, 'widget');

                foreach ($config as $index => $widget) {
                    $checked = in_array($index, $listUserWidget) ? 'checked' : '';

                ?>
                    <div class="col-xl-12">
                        <div class="card card-custom gutter-b card-sub-menu" style="height: 100px" data-id="<?= $index ?>">
                            <!--begin::Body-->
                            <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                                <div class="d-flex align-items-center" style="gap: 30px;">
                                    <div>
                                        <input class="check-menu" data-switch="true" type="checkbox" <?= $checked ?> data-on-color="success" data-off-color="dark" value="<?= $index ?>" data-id="<?= $index ?>" data-menu="__DASHBOARD__" data-user="<?= $idUserAkses ?>" data-type="widget" />
                                    </div>
                                    <div>
                                        <h3 class="font-weight-bolder"><?= $widget['title']; ?></h3>
                                        <div class="text-dark-50 font-size-lg mt-2 text-muted font-weight-bold">
                                            Dashboard
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-secondary" type="button">
                                    <!-- <i class="fas fa-ruler-horizontal pr-4"></i><strong><?= $widget['length']; ?> COL</strong> -->
                                </button>
                            </div>
                            <!--end::Body-->
                        </div>
                    </div>
                <?php

                }
                ?>

            </div>
        </div>
    </div>
<?php
}
?>