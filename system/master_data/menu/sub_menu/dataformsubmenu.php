<?php

include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasihakuser.php";
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

    $dataMenu = selectStatement(
        'SELECT * FROM menu WHERE idMenu = ?',
        [$idMenu],
        'fetch'
    );

    $sqlUpdate = $db->prepare('SELECT * from menu_sub where idSubMenu=?');
    $sqlUpdate->execute([$idSubMenu]);
    $dataUpdate = $sqlUpdate->fetch();

    if ($dataUpdate) {
        $flag = 'update';
        $formatAkses = json_decode($dataUpdate['formatAksi'], true);
    } else {
        $flag = 'tambah';
        $formatAkses = [];
    }

?>
    <div class="modal-header bg-info">
        <h5 class="modal-title text-white">
            <i class="fas fa-stream text-light pr-4"></i><strong>SUB MENU " <?= $dataMenu['namaMenu']; ?> "</strong>
        </h5>
    </div>
    <div class="modal-body">
        <form id="formSubMenu">
            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
            <input type="hidden" name="idSubMenu" value="<?= $idSubMenu ?>">
            <input type="hidden" name="flag" value="<?= $flag ?>">

            <input type="hidden" id="idMenu" name="idMenu" value="<?= $idMenu ?>">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label><i class="fas fa-list"></i> Index </label>
                    <input type="text" class="form-control" id="indexSort" name="indexSort" placeholder="Index" value="<?= $dataUpdate['indexSort'] ?? '' ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label><i class="fas fa-list"></i> Submenu</label>
                    <input type="text" class="form-control" id="namaSubMenu" name="namaSubMenu" placeholder="Nama Sub Menu" value="<?= $dataUpdate['namaSubMenu'] ?? '' ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label><i class="fas fa-folder"></i> Group</label>
                    <select name="namaKelompok" id="namaKelompok" class="form-control selectpicker">
                        <?php
                        $opsi = [
                            'Master Data' => 'master_data',
                            'Laporan' => 'laporan',
                            'Proses Bisnis' => 'proses_bisnis',
                            'Purchasing' => 'purchasing',

                        ];

                        foreach ($opsi as $nama => $dir) {
                            $selected = selected($dir, $dataUpdate['namaKelompok'] ?? '');
                        ?>
                            <option value="<?= $dir ?>" <?= $selected; ?>> <?= $nama; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label><i class="fas fa-folder"></i> Nama Folder</label>
                    <input type="text" class="form-control" id="namaFolder" name="namaFolder" placeholder="Nama Folder" value="<?= $dataUpdate['namaFolder'] ?? '' ?>">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Format Akses</label>
                <select name="formatAksi[]" id="formatAksi" class="form-control selectpicker" multiple="multiple">
                    <?php
                    $opsi = getDaftarHak();

                    foreach ($opsi as $akses => $title) {
                        $selected = in_array($akses, $formatAkses) ? 'selected' : '';
                    ?>
                        <option value="<?= $akses ?>" <?= $selected; ?> data-content="<span class='badge badge-info'><?= $title; ?></span>"> <?= $title; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary" onclick="prosesSubMenu()">
                    <i class="fa fa-save pr-2"></i> <strong>SIMPAN</strong>
                </button>
            </div>
        </form>

        <div class="alert alert-success" role="alert">
            <i class="fas fa-list text-light pr-3"></i> <strong>DAFTAR SUBMENU</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="alert alert-success">
                    <th style="width: 5%;">No</th>
                    <th style="width: 10%;">Aksi</th>
                    <th>Submenu</th>
                    <th>Nama Folder</th>
                    <th>Index</th>
                </thead>
                <tbody>
                    <?php
                    $sqlSubMenu = $db->prepare('SELECT * FROM menu_sub 
                        WHERE idMenu=? 
                        ORDER BY indexSort');
                    $sqlSubMenu->execute([$idMenu]);
                    $dataSubMenu = $sqlSubMenu->fetchAll();

                    $n = 1;
                    foreach ($dataSubMenu as $row) {
                    ?>
                        <tr>
                            <td><?= $n ?></td>
                            <td>
                                <button type="button" id="dropdownMenuButton" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-cogs"></i>
                                </button>
                                <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                    <button type="button" class="btn btn-warning btn-sm tombol-dropdown " onclick="getFormSubMenu('<?= $idMenu ?>', '<?= $row['idSubMenu'] ?>')">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiBatalSubMenu('<?= $idMenu ?>','<?= $row['idSubMenu'] ?>', '<?= $tokenCSRF ?>')">
                                        <i class="fa fa-trash"></i> Hapus
                                    </button>
                                </div>
                            </td>
                            <td><?= $row['namaSubMenu'] ?></td>
                            <td><?= $row['namaFolder'] ?></td>
                            <td><?= $row['indexSort'] ?></td>
                        </tr>
                    <?php
                        $n++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
<?php
}
?>