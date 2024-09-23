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

    if ($flagData == 'daftar') {
        $dataMenu = $db->query('SELECT * from menu order by namaMenu');
    } else {
        $kataKunciSql = '%' . $kataKunciData . '%';

        $sqlMenu = $db->prepare('SELECT * from menu 
      where namaMenu LIKE ?
      order by namaMenu');
        $sqlMenu->execute([
            $kataKunciSql
        ]);
        $dataMenu = $sqlMenu->fetchAll();
    }
?>
    <table class="table table-hover">
        <thead class="alert alert-primary">
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 10%;">Aksi</th>
                <th>Menu</th>
                <th>Icon</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $n = 1;
            foreach ($dataMenu as $row) {
            ?>
                <tr>
                    <td><?= $n ?></td>
                    <td>
                        <button type="button" id="dropdownMenuButton" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                            <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormMenu('<?= $row['idMenu'] ?>')">
                                <i class="fa fa-edit"></i> <strong>EDIT</strong>
                            </button>

                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown" onclick="konfirmasiBataMenu('<?= $row['idMenu'] ?>', '<?= $tokenCSRF ?>')">
                                <i class="fa fa-trash"></i> <strong>HAPUS</strong>
                            </button>

                            <button type="button" class="btn btn-info btn-sm tombol-dropdown-last tombolTambahSubMenu" onclick="getFormSubMenu('<?= $row['idMenu'] ?>')">
                                <i class="fa fa-bars"></i> <strong>SUBMENU</strong>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold">
                            <?= $row['namaMenu'] ?>
                        </span>
                        <span class="text-muted font-weight-bold">Menu</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold">
                            <i class="<?= $row['iconClass'] ?> text-dark-50 pr-4"></i> <i>(<?= $row['iconClass'] ?>)</i>
                        </span>
                        <span class="text-muted font-weight-bold">Icon</span>
                    </td>
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