<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
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

    $dataMenu = statementWrapper(
        DML_SELECT,
        'SELECT * FROM menu WHERE idMenu = ?',
        [$idMenu]
    );

    $opsi = getDaftarHak();

?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fas fa-stream text-dark pr-3"></i> <strong>DAFTAR AKSES MENU "<?= $dataMenu['namaMenu']; ?>"</strong>
                    </span>
                    <span class="mt-3 font-weight-bold font-size-sm">
                        <?= PAGE_TITLE; ?>
                    </span>
                </h3>
            </div>
            <!-- END CARD TITLE -->
            <div class="card-toolbar">
                <button class="btn btn-outline-success btn-enable-all" type="button" onclick="prosesAksesMenu('menu', '<?= $idUserAsli ?>', '__ALL__<?= $idMenu ?>__', 'Active')" data-id="<?= $idMenu ?>"><i class="fas fa-check-double pr-4"></i><strong>ENABLE ALL</strong></button>
            </div>
        </div>
        <!-- END CARD HEADER -->

        <!-- CARD BODY -->
        <div class="card-body">
            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
            <div class="row">
                <?php
                if ($dataMenu) {

                    $dataSubMenu = selectStatement(
                        "SELECT
                        menu_sub.namaSubMenu,
                        menu_sub.idSubMenu,
                        menu_sub.formatAksi,
                        menu_terpilih.idUserDetail,
                        menu_terpilih.hakAkses
                    FROM
                        menu_sub
                        LEFT JOIN (
                            SELECT
                                user_detail.*
                            FROM
                                user_detail
                            WHERE
                                user_detail.idUser = ?
                        ) as menu_terpilih ON menu_sub.idSubMenu = menu_terpilih.idSubMenu
                    WHERE
                        menu_sub.idMenu = ?
                        ORDER BY menu_sub.idSubMenu
                    ",
                        [$idUserAkses, $idMenu]
                    );

                    foreach ($dataSubMenu as $index => $subMenu) {
                        $listHak = is_null($subMenu['hakAkses']) ? [] : json_decode($subMenu['hakAkses'], true);
                        $checked = !is_null($subMenu['idUserDetail']) ? 'checked' : '';
                ?>
                        <div class="col-xl-12">
                            <div class="card card-custom gutter-b card-sub-menu" style="height: 100px" data-id="<?= $subMenu['idSubMenu'] ?>">
                                <!--begin::Body-->
                                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="d-flex align-items-center" style="gap: 30px;">
                                        <div>
                                            <input class="check-menu" data-switch="true" type="checkbox" <?= $checked ?> data-on-color="success" data-off-color="dark" value="<?= $subMenu['idSubMenu'] ?>" data-id="<?= $subMenu['idSubMenu'] ?>" data-menu="<?= $idMenu ?>" data-user="<?= $idUserAkses ?>" data-type="menu" />
                                        </div>
                                        <div>
                                            <h3 class="font-weight-bolder"><?= $subMenu['namaSubMenu']; ?></h3>
                                            <div class="text-dark-50 font-size-lg mt-2 text-muted font-weight-bold">
                                                <?= $dataMenu['namaMenu']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" id="dropdownMenuButton" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-cogs pr-4"></i><strong>HAK AKTIVITAS</strong>
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="width: 250px;" id="boxTipe_<?= $subMenu['idSubMenu'] ?>">
                                            <ul class="navi navi-hover">
                                                <?php
                                                if (count($listHak) === 0) {
                                                ?>
                                                    <li class="navi-item">
                                                        <a class="navi-link" href="#" onclick="event.stopPropagation();event.preventDefault()">
                                                            <span class="navi-icon"><i class="fas fa-ban text-secondary"></i></span>
                                                            <span class="navi-text text-uppercase font-weight-bold">TIDAK TERSEDIA</span>
                                                            <span class="label label-light-dark font-weight-bold label-inline"><strong>NOT SET</strong></span>
                                                        </a>
                                                    </li>
                                                    <?php
                                                } else {
                                                    foreach ($listHak as $hak => $status) {
                                                        if (!in_array($hak, array_keys($opsi))) continue;

                                                        $color = '';
                                                        $icon = '';

                                                        if ($status === 'Active') {
                                                            $color = 'success';
                                                            $icon = 'fas fa-toggle-on';
                                                        } else if ($status === 'Non Active') {
                                                            $color = 'danger';
                                                            $icon = 'fas fa-toggle-off';
                                                        } else {
                                                            $color = 'secondary';
                                                            $icon = 'fas fa-exclamation-circle';
                                                        }
                                                    ?>
                                                        <li class="navi-item">
                                                            <a class="navi-link" href="#" onclick="event.preventDefault();prosesAktivasiHak($(this),'<?= $idUserAkses ?>','<?= $subMenu['idSubMenu'] ?>','<?= $subMenu['idUserDetail'] ?>','<?= $hak ?>')" data-status="<?= $status ?>">
                                                                <span class="navi-icon"><i class="<?= $icon ?> text-<?= $color ?>"></i></span>
                                                                <span class="navi-text text-uppercase font-weight-bold"><?= $opsi[$hak]; ?></span>
                                                                <span class="label label-light-<?= $color ?> font-weight-bold label-inline"><strong><?= $status; ?></strong></span>
                                                            </a>
                                                        </li>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Body-->
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                <?php
                } else {
                ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>MENU TIDAK DITEMUKAN</strong>
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