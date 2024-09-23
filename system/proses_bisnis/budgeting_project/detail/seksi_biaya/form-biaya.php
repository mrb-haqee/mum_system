<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
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

    $dataHeader = statementWrapper(
        DML_SELECT,
        'SELECT * FROM budgeting_project WHERE kodeBudgetingProject=?',
        [$kodeBudgetingProject]
    );


    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * FROM budgeting_project_biaya WHERE idBudgetingProjectBiaya=?',
        [$idBudgetingProjectBiaya]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Informasi Biaya
                    </span>
                    <span class="mt-3 font-weight-bold font-size-sm">
                        <?= PAGE_TITLE; ?>
                    </span>
                </h3>
            </div>
            <!-- END CARD TITLE -->
        </div>
        <!-- END CARD HEADER -->

        <!-- CARD BODY -->
        <div class="card-body">
            <?php if ($dataHeader): ?>
                <form id="formBiaya">
                    <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                    <input type="hidden" name="idBudgetingProjectBiaya" value="<?= $dataUpdate['idBudgetingProjectBiaya'] ?? '' ?>">
                    <input type="hidden" name="kodeBudgetingProject" value="<?= $kodeBudgetingProject ?>">
                    <input type="hidden" name="flag" value="<?= $flag ?>">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label><i class="fas fa-list"></i> NAMA ITEM </label>
                            <input type="text" class="form-control" id="namaItem" name="namaItem" placeholder="Nama Item" value="<?= $dataUpdate['namaItem'] ?? '' ?>">
                        </div>
                        <div class="form-group col-md-1">
                            <label><i class="fas fa-list"></i> QTY </label>
                            <div class="input-group">
                                <input type="text" name="qty" id="qty" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['qty'] ?? 0) ?>" onkeyup="showSubTotal()">
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label><i class="fas fa-list"></i> HARGA SATUAN </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" name="hargaSatuan" id="hargaSatuan" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['hargaSatuan'] ?? 0) ?>" onkeyup="showSubTotal()">
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label><i class="fas fa-list"></i> SUB TOTAL </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" name="subTotal" id="subTotal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['subTotal'] ?? 0) ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group col-md-1">
                            <label class="d-block"> &nbsp; </label>
                            <button type="button" class="btn btn-primary text-center" onclick="prosesBiaya()">
                                <i class="fa fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>MOHON ISI BAGIAN INFORMASI TERLEBIH DAHULU</strong>
                </div>
            <?php endif; ?>
            <div>
                <?php
                $dataPembelian = statementWrapper(
                    DML_SELECT_ALL,
                    "SELECT
                        *
                    FROM
                        budgeting_project_biaya
                    WHERE
                        kodeBudgetingProject = ?
                    ",
                    [$kodeBudgetingProject]
                );
                if (count($dataPembelian) > 0) {
                ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center align-middle" style="width: 5%;">NO</th>
                                <th class="text-center align-middle" style="width: 10%;">AKSI</th>
                                <th class="text-center align-middle" style="width: 35%;">ITEM</th>
                                <th class="text-center align-middle">QTY</th>
                                <th class="text-center align-middle">HARGA</th>
                                <th class="text-center align-middle">SUB TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $n = 1;
                            $total = 0;

                            foreach ($dataPembelian as $row) {

                            ?>
                                <tr>
                                    <td class="text-center align-middle"><?= $n ?></td>
                                    <td class="text-center">
                                        <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-cogs"></i>
                                        </button>
                                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                            <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="seksiFormBiaya('<?= $row['idBudgetingProjectBiaya'] ?>')">
                                                <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deleteBiaya('<?= $row['idBudgetingProjectBiaya'] ?>', '<?= $tokenCSRF ?>')">
                                                <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block font-weight-bold"><?= $row['namaItem'] ?></span>
                                        <span class="text-muted font-weight-bold">Item</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['qty']) ?></span>
                                        <span class="text-muted font-weight-bold">Qty</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['hargaSatuan']) ?></span>
                                        <span class="text-muted font-weight-bold">Harga</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['subTotal']) ?></span>
                                        <span class="text-muted font-weight-bold">Sub Total</span>
                                    </td>
                                </tr>
                            <?php
                                $n++;
                                $total += intval($row['subTotal']);
                            }
                            ?>
                            <tr>
                                <td colspan="5" class="text-right align-middle"><strong>TOTAL</strong></td>
                                <td class="text-right">Rp <?= ubahToRupiahDesimal($total); ?></td>
                                <input type="hidden" name="grandTotal" id="grandTotal" value="<?= $total ?>" form="formBiaya">
                            </tr>
                        </tbody>
                    </table>
                <?php
                } ?>
            </div>
        </div>
    </div>
<?php
}
?>