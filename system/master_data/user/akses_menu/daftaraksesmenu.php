<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasihakuser.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidashboard.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT idUser from user where idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//MENGECEK APAKAH USER INI BERHAK MENGAKSES MENU INI
$sqlCekMenu = $db->prepare('SELECT * from user_detail 
  inner join menu_sub 
  on menu_sub.idSubMenu = user_detail.idSubMenu
  where user_detail.idUser = ?
  and namaFolder = ?');
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__)
]);
$dataCekMenu = $sqlCekMenu->fetch();

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu) {
    alertSessionExpForm();
} else {
    extract($_POST, EXTR_SKIP);

    $dataUser = selectStatement(
        'SELECT 
            user.*,
            pegawai.namaPegawai 
        FROM 
            user 
            INNER JOIN pegawai ON user.idPegawai = pegawai.idPegawai
        WHERE 
            user.idUser = ?
        ',
        [$idUserAccount],
        'fetch'
    );


?>
    <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
            <i class="fas fa-users text-light pr-4"></i> <strong>DAFTAR AKSES MENU "<?= $dataUser['userName']; ?>"</strong>
        </h5>
    </div>
    <div class="modal-body">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <table class="table table-hover">
            <tbody>
                <tr class="table-active">
                    <th colspan="3"><i class="fas fa-server pr-4 text-dark" style="font-size:1rem"></i><strong class="text-uppercase">DASHBOARD WIDGET</strong></th>
                </tr>
                <?php

                $config = configDashboard();

                $userDashboard = statementWrapper(
                    DML_SELECT_ALL,
                    'SELECT * FROM user_dashboard WHERE idUser = ?',
                    [$idUserAccount]
                );

                $listUserWidget = array_column($userDashboard, 'widget');

                foreach ($config as $index => $widget) {
                    $checked = in_array($index, $listUserWidget) ? 'checked' : '';
                ?>
                    <tr data-id="<?= $index ?>" class="row-input" data-user="<?= $idUserAccount ?>">
                        <td class="align-middle align-center" style="width: 20%;">
                            <input class="check-menu" data-switch="true" type="checkbox" <?= $checked ?> data-on-color="success" data-off-color="dark" value="<?= $index ?>" data-id="<?= $index ?>" data-user="<?= $idUserAccount ?>" data-type="widget" />
                        </td>
                        <td class="text-left align-middle" style="width: 35%;">
                            <span class="d-block font-weight-bold"><?= $widget['title'] ?></span>
                            <span class="text-muted font-weight-bold">Dashboard</span>
                        </td>
                        <td></td>
                    </tr>
                <?php
                }

                $i = 0;

                $dataMenu = selectStatement(
                    'SELECT
                        *
                    FROM
                        menu
                    WHERE
                        statusMenu = ?',
                    ['Aktif']
                );

                $opsi = getDaftarHak();

                foreach ($dataMenu as $row) {

                    $dataMenuSub = selectStatement(
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
                            ) menu_terpilih ON menu_sub.idSubMenu = menu_terpilih.idSubMenu
                        WHERE
                            menu_sub.idMenu = ?
                            ORDER BY menu_sub.idSubMenu
                        ",
                        [$idUserAccount, $row['idMenu']]
                    );

                ?>
                    <tr class="table-active">
                        <th colspan="3"><i class="fas fa-<?= $row['iconClass'] ?> pr-4 text-dark" style="font-size:1rem"></i><strong class="text-uppercase"><?= $row['namaMenu']; ?></strong></th>
                    </tr>
                    <?php
                    $n = 1;
                    foreach ($dataMenuSub as $index => $data) {

                        $listHak = is_null($data['hakAkses']) ? [] : json_decode($data['hakAkses'], true);
                        $checked = !is_null($data['idUserDetail']) ? 'checked' : '';
                    ?>
                        <tr data-id="<?= $data['idSubMenu'] ?>" class="row-input" data-user="<?= $idUserAccount ?>">
                            <td class="align-middle align-center" style="width: 20%;">
                                <input class="check-menu" data-switch="true" type="checkbox" <?= $checked ?> data-on-color="success" data-off-color="dark" value="<?= $data['idSubMenu'] ?>" data-id="<?= $data['idSubMenu'] ?>" data-user="<?= $idUserAccount ?>" data-type="menu" />
                            </td>
                            <td class="text-left align-middle" style="width: 35%;">
                                <span class="d-block font-weight-bold"><?= $data['namaSubMenu'] ?></span>
                                <span class="text-muted font-weight-bold"><?= $row['namaMenu']; ?></span>
                            </td>
                            <td class="align-middle align-left" style="width: 40%;">
                                <div class="dropdown">
                                    <button type="button" id="dropdownMenuButton" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs pr-4"></i><strong>HAK AKTIVITAS</strong>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="width: 250px;" id="boxTipe_<?= $data['idSubMenu'] ?>">
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
                                                        <a class="navi-link" href="#" onclick="event.preventDefault();prosesAktivasiHak($(this),'<?= $idUserAccount ?>','<?= $data['idSubMenu'] ?>','<?= $data['idUserDetail'] ?>','<?= $hak ?>')" data-status="<?= $status ?>">
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
                            </td>
                        </tr>
                <?php
                        $n++;
                    }
                    $i++;
                }
                ?>
            </tbody>
        </table>
    </div>
<?php
}
?>