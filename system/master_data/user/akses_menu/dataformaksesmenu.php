<?php
include_once '../../../../library/konfigurasiurl.php';
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
    $idSubMenuUpdate = '';

    extract($_POST, EXTR_SKIP);

    $sqlUser = $db->prepare(
        'SELECT 
            user.*
        FROM 
            user
        WHERE 
            idUser = ?'
    );
    $sqlUser->execute([$idUserAccount]);
    $dataUser = $sqlUser->fetch();

    $sqlUpdate = $db->prepare(
        'SELECT 
            * 
        FROM 
            user_detail
            INNER JOIN menu_sub on menu_sub.idSubMenu = user_detail.idSubMenu 
        WHERE 
            user_detail.idUserDetail=?'
    );
    $sqlUpdate->execute([$idUserDetail]);
    $dataUpdate = $sqlUpdate->fetch();

    if ($dataUpdate) {
        $flag = 'update';
        $idSubMenuUpdate = $dataUpdate['idSubMenu'];
    } else {
        $flag = 'tambah';
    }

?>
    <div class="modal-header bg-info">
        <h5 class="modal-title text-white">
            <i class="fas fa-user-shield text-light pr-4"></i> <strong>AKSES MENU " <?= $dataUser['namaLengkap']; ?> "</strong>
        </h5>
    </div>
    <div class="modal-body">
        <form id="formAksesMenu">
            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
            <input type="hidden" name="idUserDetail" value="<?= $idUserDetail ?>">
            <input type="hidden" name="flag" value="<?= $flag ?>">

            <input type="hidden" id="idUserAccount" name="idUserAccount" value="<?= $idUserAccount ?>">

            <input type="hidden" id="idSubMenuUpdate" value="<?= $idSubMenuUpdate ?>">

            <div class="form-row">
                <div class="form-group col-sm-6">
                    <label><i class="fa fa-user pr-2"></i> Nama Lengkap</label>
                    <input type="text" class="form-control" value="<?= $dataUser['namaLengkap'] ?>" readonly>
                </div>
                <div class="form-group col-sm-6">
                    <label><i class="fas fa-pen-square pr-2"></i> Username</label>
                    <input type="text" class="form-control" value="<?= $dataUser['userName'] ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    <label><i class="fa fa-list pr-2"></i> Menu</label>
                    <select id="idMenu" class="form-control selectpicker" onchange="dataDaftarSubMenuPerMenu('<?= $idUserAccount ?>')">
                        <option value="">Pilih Menu</option>
                        <?php
                        $dataMenu = $db->query('SELECT * from menu order by namaMenu');
                        foreach ($dataMenu as $row) {
                            $selected = selected($row['idMenu'], $dataUpdate['idMenu'])
                        ?>
                            <option value="<?= $row['idMenu'] ?>" <?= $selected ?>>
                                <?= $row['namaMenu'] ?>
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group col-sm-6" id="dataDaftarSubMenu">
                    <!-- disini tampil daftar submenu per menu yang dipilih di atas -->
                </div>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary" onclick="prosesAksesMenu()">
                    <i class="fa fa-save pr-2"></i> <strong>SIMPAN</strong>
                </button>
            </div>
        </form>

        <div class="alert alert-success" role="alert">
            <i class="fas fa-stream pr-4 text-light"></i> <strong>DAFTAR AKSES MENU</strong>
        </div>
        <div class="table-responsive" id="dataDaftarSubMenuUser">
            <!-- DISINI TAMPIL DAFTAR MENU YANG SUDAH DIPILIH -->
        </div>
    </div>
<?php
}
?>