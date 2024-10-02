<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";

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

    if ($flag === 'update') {
        $kodeBiaya = $kodeBiaya;
    } else {
        $kodeBiaya = nomorUrut($db, 'Biaya', $idUserAsli);
    }

    $dataUpdateDetail = statementWrapper(
        DML_SELECT,
        'SELECT 
            *
        FROM 
            biaya_detail
        WHERE 
            biaya_detail.idBiayaDetail = ?',
        [$idBiayaDetail]
    );

    if ($dataUpdateDetail) {
        $flagDetail = 'updateDetail';
    } else {
        $flagDetail = 'tambahDetail';
    }

?>
    <form id="formBiayaDetail">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idBiayaDetail" value="<?= $idBiayaDetail ?>">
        <input type="hidden" name="kodeBiaya" value="<?= $kodeBiaya ?>">
        <input type="hidden" name="flag" value="<?= $flagDetail ?>">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label><i class="fas fa-list"></i> NAMA ITEM </label>
                <input type="text" class="form-control" id="namaItem" name="namaItem" placeholder="Nama Item" value="<?= $dataUpdateDetail['namaItem'] ?? '' ?>">
            </div>
            <div class="form-group col-md-1">
                <label><i class="fas fa-list"></i> QTY </label>
                <div class="input-group">
                    <input type="text" name="qty" id="qty" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdateDetail['qty'] ?? 0) ?>" onkeyup="showSubTotal()">
                </div>
            </div>
            <div class="form-group col-md-3">
                <label><i class="fas fa-list"></i> HARGA SATUAN </label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" name="hargaSatuan" id="hargaSatuan" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdateDetail['hargaBarang'] ?? 0) ?>" onkeyup="showSubTotal()">
                </div>
            </div>
            <div class="form-group col-md-3">
                <label><i class="fas fa-list"></i> SUB TOTAL </label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" name="subTotal" id="subTotal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdateDetail['subTotal'] ?? 0) ?>" readonly>
                </div>
            </div>
            <div class="form-group col-md-1">
                <label class="d-block"> &nbsp; </label>
                <button type="button" class="btn btn-primary text-center" onclick="prosesBiayaDetail()">
                    <i class="fa fa-save"></i>
                </button>
            </div>
        </div>
    </form>

    <div>
        <?php
        $dataPembelian = statementWrapper(
            DML_SELECT_ALL,
            "SELECT
                *
            FROM
                biaya_detail
            WHERE
                kodeBiaya = ?
            ",
            [$kodeBiaya]
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
                                    <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormBiayaDetail('<?= $row['idBiayaDetail'] ?>')">
                                        <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deleteBiayaDetail('<?= $row['idBiayaDetail'] ?>','<?= $row['kodeBiaya'] ?>', '<?= $tokenCSRF ?>')">
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

<?php
}
?>