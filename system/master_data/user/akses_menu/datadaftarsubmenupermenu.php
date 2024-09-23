<?php
include_once '../../../../library/konfigurasiurl.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";

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

    $dataSubMenu = selectStatement(
        'SELECT 
            * 
        FROM 
            menu_sub 
        WHERE
            idMenu = ? 
            AND idSubMenu NOT IN (SELECT idSubMenu FROM user_detail WHERE idUser = ?)
            ',
        [$idMenu, $idUserAccount]
    );

?>
    <label><i class="fa fa-list pr-2"></i> Sub Menu</label>
    <select id="idSubMenu" name="idSubMenu" class="form-control selectpicker" data-live-search="true">
        <option value="">Pilih Submenu</option>
        <?php
        foreach ($dataSubMenu as $row) {
            $selected = selected($row['idSubMenu'], $idUserDetail);
        ?>
            <option value="<?= $row['idSubMenu'] ?>" <?= $selected ?>>
                <?= $row['namaSubMenu'] ?>
            </option>
        <?php
        }
        ?>
    </select>
<?php
}
?>