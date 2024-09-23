<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasihakuser.php";
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

    $dataAkses = selectStatement(
        'SELECT hakAkses, idUserDetail, idSubMenu FROM user_detail WHERE idUser = ? AND idSubMenu = ?',
        [$idUserAccount, $idSubMenu],
        'fetch'
    );

    if ($dataAkses) {

        $opsi = getDaftarHak();
        $listHak = json_decode($dataAkses['hakAkses'], true);

?>
        <ul class="navi navi-hover">
            <?php
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
                    <a class="navi-link" href="#" onclick="event.preventDefault();prosesAktivasiHak($(this),'<?= $idUserAccount ?>','<?= $dataAkses['idSubMenu'] ?>','<?= $dataAkses['idUserDetail'] ?>','<?= $hak ?>')" data-status="<?= $status ?>">
                        <span class="navi-icon"><i class="<?= $icon ?> text-<?= $color ?>"></i></span>
                        <span class="navi-text text-uppercase font-weight-bold"><?= $opsi[$hak]; ?></span>
                        <span class="label label-light-<?= $color ?> font-weight-bold label-inline"><strong><?= $status; ?></strong></span>
                    </a>
                </li>
            <?php
            }
            ?>
        </ul>

    <?php
    } else {
    ?>
        <ul class="navi navi-hover">
            <li class="navi-item">
                <a class="navi-link" href="#" onclick="event.stopPropagation();event.preventDefault()">
                    <span class="navi-icon"><i class="fas fa-ban text-secondary"></i></span>
                    <span class="navi-text text-uppercase font-weight-bold">TIDAK TERSEDIA</span>
                    <span class="label label-light-dark font-weight-bold label-inline"><strong>NOT SET</strong></span>
                </a>
            </li>
        </ul>
<?php
    }
}
?>