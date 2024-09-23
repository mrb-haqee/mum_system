<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
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

    $sqlUpdate = $db->prepare('SELECT * from menu where idMenu=?');
    $sqlUpdate->execute([$idMenu]);
    $dataUpdate = $sqlUpdate->fetch();

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

?>
    <form id="formMenu">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idMenu" value="<?= $idMenu ?>">
        <input type="hidden" name="flag" value="<?= $flag ?>">

        <div class="row">
            <div class="form-group col-md-3">
                <label><i class="fa fa-list"></i> Index</label>
                <input type="text" class="form-control" id="indexSort" name="indexSort" placeholder="Index" value="<?= $dataUpdate['indexSort'] ?? '' ?>">
            </div>
            <div class="form-group col-md-9">
                <label><i class="fa fa-list"></i> Menu</label>
                <input type="text" class="form-control" id="namaMenu" name="namaMenu" placeholder="Menu" value="<?= $dataUpdate['namaMenu'] ?? '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label><i class="fa fa-info-circle"></i> Font Awesome</label>
            <div class="input-group">
                <input type="text" class="form-control" id="iconClass" name="iconClass" placeholder="Icon (Font Awesome Class)" value="<?= $dataUpdate['iconClass'] ?? '' ?>">
                <div class="input-group-append">
                    <span class="input-group-text">
                        <i class="<?= $dataUpdate['iconClass'] ?? 'fas fa-spinner fa-spin' ?>" id="iconSample"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-primary" onclick="prosesMenu()">
                <i class="fa fa-save pr-2"></i> <strong>SIMPAN</strong>
            </button>
        </div>

    </form>
<?php
}
?>