<?php
include_once '../../../library/konfigurasi.php';
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

    $sqlUpdate  = $db->prepare('SELECT * from user where user.idUser = ?');
    $sqlUpdate->execute([$idUserAccount]);
    $dataUpdate = $sqlUpdate->fetch();

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

?>
    <form id="formUser">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idUserAccount" value="<?= $idUserAccount ?>">
        <input type="hidden" name="flag" value="<?= $flag ?>">


        <div class="form-group">
            <label for="idPegawai"><i class="fas fa-user-tie"></i> PEGAWAI</label>
            <select name="idPegawai" id="idPegawai" class="form-control selectpicker" data-live-search="true">
                <option value="">Pilih Pegawai</option>
                <?php
                $opsi = selectStatement('SELECT * FROM pegawai WHERE statusPegawai = ?', ['Aktif']);

                foreach ($opsi as $index => $pegawai) {
                    $selected = selected($pegawai['idPegawai'], $dataUpdate['idPegawai'] ?? '');
                ?>
                    <option value="<?= $pegawai['idPegawai'] ?>" <?= $selected; ?>><?= $pegawai['namaPegawai']; ?></option>
                <?php
                }
                ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label><i class="fas fa-user-circle"></i> USERNAME</label>
                <input type="text" class="form-control" id="userName" name="userName" placeholder="Username" value="<?= $dataUpdate['userName'] ?? '' ?>">
            </div>
            <div class="form-group col-md-6">
                <label><i class="fas fa-user-lock"></i> PASSWORD</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password">
            </div>
        </div>
        <div class="form-group">
            <label for="aksesEditable" class="col-form-label mr-3"><i class="fas fa-file-signature"></i> AKSES RUBAH DATA TERFINALISASI</label>
            <div>
                <input class="check-menu" data-switch="true" name="aksesEditable" id="aksesEditable" type="checkbox" <?= $dataUpdate['aksesEditable'] === 'Aktif' ? 'checked' : '' ?> data-on-color="success" data-off-color="dark" value="Aktif" />
            </div>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-primary" onclick="prosesUser()">
                <i class="fa fa-save pr-4"></i> <strong>SIMPAN</strong>
            </button>
        </div>
    </form>
<?php
}
?>