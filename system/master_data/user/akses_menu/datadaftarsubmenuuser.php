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
    extract($_POST, EXTR_SKIP);

    $sqlMenuUser = $db->prepare(
        'SELECT 
            * 
        FROM 
            user_detail
            INNER JOIN menu_sub ON menu_sub.idSubMenu = user_detail.idSubMenu
            INNER JOIN menu ON menu.idMenu = menu_sub.idMenu 
        WHERE 
            user_detail.idUser = ?
            ORDER BY namaMenu, namaSubMenu'
    );

    $sqlMenuUser->execute([$idUserAccount]);
    $dataMenuUser = $sqlMenuUser->fetchAll();

?>
    <table class="table table-hover">
        <thead class="alert alert-success">
            <th style="width: 5%;">No</th>
            <th style="width: 10%;">Aksi</th>
            <th>Menu</th>
            <th>Sub Menu</th>
        </thead>
        <tbody>
            <?php
            $n = 1;
            foreach ($dataMenuUser as $row) {
            ?>
                <tr>
                    <td><?= $n ?></td>
                    <td>
                        <button type="button" id="dropdownMenuButton" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi-akses-menu" aria-labelledby="dropdownMenuButton">
                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiBatalAksesMenu('<?= $idUserAccount ?>','<?= $row['idUserDetail'] ?>', '<?= $tokenCSRF ?>')">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        </div>
                    </td>
                    <td><?= $row['namaMenu'] ?></td>
                    <td><?= $row['namaSubMenu'] ?></td>
                </tr>
            <?php
                $n++;
            }
            ?>
        </tbody>
    </table>
<?php
}
?>