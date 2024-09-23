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

    $dataUpdateDetail = statementWrapper(
        DML_SELECT,
        'SELECT 
            *
        FROM 
            purchasing_detail
            INNER JOIN barang ON purchasing_detail.idBarang = barang.idBarang
        WHERE 
            purchasing_detail.idPurchasingDetail = ?',
        [$idPurchasingDetail]
    );

    $opsiBarang =  statementWrapper(DML_SELECT_ALL, 'SELECT * FROM barang', []);

    if ($dataUpdateDetail) {
        $flagDetail = 'updateDetail';
    } else {
        $flagDetail = 'tambahDetail';
    }


?>

    <form id="formPurchasingDetail">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idPurchasingDetail" value="<?= $idPurchasingDetail ?>">
        <input type="hidden" name="kodePurchasing" value="<?= $kodePurchasing ?>">
        <input type="hidden" name="flag" value="<?= $flagDetail ?>">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label><i class="fas fa-truck-loading"></i> NAMA BARANG </label>
                <select id="idBarang" name="idBarang" class="form-control selectpicker" data-live-search="true" onchange="showBarang(), showSubTotal()">
                    <option value="">Pilih Barang</option>
                    <?php
                    foreach ($opsiBarang as $row) {
                        $selected = selected($row['idBarang'], $dataUpdateDetail['idBarang']);
                    ?>
                        <option value="<?= $row['idBarang'] ?>" data-harga-barang='<?= $row['hargaBarang'] ?>' data-satuan-barang='<?= $row['satuanBarang'] ?>' <?= $selected ?>><?= $row['namaBarang'] ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label><i class="fas fa-list"></i> QTY </label>
                <div class="input-group">
                    <input type="text" name="qty" id="qty" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdateDetail['qty'] ?? 0) ?>" onkeyup="showSubTotal()">
                    <div class="input-group-prepend">
                        <span id="satuanBarang" class="input-group-text"><?= $dataUpdateDetail['satuanBarang'] ?? 'Satuan' ?></span>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-3">
                <label><i class="fas fa-list"></i> HARGA SATUAN </label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" id="hargaBarang" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdateDetail['hargaBarang'] ?? 0) ?>" readonly>
                </div>
            </div>
            <div class="form-group col-md-3">
                <label><i class="fas fa-list"></i> JUMLAH HARGA </label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" name="subTotal" id="subTotal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdateDetail['subTotal'] ?? 0) ?>" readonly>
                </div>
            </div>
            <div class="form-group col-md-1">
                <label class="d-block"> &nbsp; </label>
                <button type="button" class="btn  btn-<?= ($flagDetail == 'tambahDetail') ? 'danger' : 'info' ?> text-center" onclick="prosesPurchasingDetail()">
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
                purchasing_detail
                INNER JOIN barang on purchasing_detail.idBarang = barang.idBarang
            WHERE
                purchasing_detail.kodePurchasing = ?
            ",
            [$kodePurchasing]
        );
        if (count($dataPembelian) > 0) {
        ?>
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th class="text-center align-middle" style="width: 5%;">NO</th>
                        <th class="text-center align-middle" style="width: 10%;">AKSI</th>
                        <th class="text-center align-middle" style="width: 25%;">ITEM</th>
                        <th class="text-center align-middle">QTY</th>
                        <th class="text-center align-middle">HARGA SATUAN</th>
                        <th class="text-center align-middle">JUMLAH HARGA</th>
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
                                    <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormPurchasingDetail('<?= $row['idPurchasingDetail'] ?>')">
                                        <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deletePurchasingDetail('<?= $row['idPurchasingDetail'] ?>', '<?= $row['kodePurchasing'] ?>', '<?= $tokenCSRF ?>')">
                                        <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span class="d-block font-weight-bold"><?= $row['namaBarang'] ?></span>
                                <span class="text-muted font-weight-bold">Item</span>
                            </td>
                            <td class="text-right">
                                <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['qty']) . ' ' . $row['satuanBarang'] ?></span>
                                <span class="text-muted font-weight-bold">Qty</span>
                            </td>
                            <td class="text-right">
                                <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['hargaBarang']) ?></span>
                                <span class="text-muted font-weight-bold">Harga</span>
                            </td>
                            <td class="text-right">
                                <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['subTotal']) ?></span>
                                <span class="text-muted font-weight-bold">Jumlah Harga</span>
                            </td>
                        </tr>
                    <?php
                        $n++;
                        $total += intval($row['subTotal']);
                    }
                    ?>
                    <tr>
                        <td colspan="4"></td>
                        <td class="text-right align-middle"><strong>Jumlah</strong></td>
                        <td class="text-right">Rp <?= ubahToRupiahDesimal($total); ?></td>
                        <input type="hidden" name="grandTotal" id="grandTotal" value="<?= $total ?>" form="formPurchasing">
                    </tr>
                    <?php
                    $dataPurchasing = selectStatement('SELECT discount, ppn FROM purchasing WHERE kodePurchasing = ?', [$kodePurchasing], 'fetch');
                    $discount = $dataPurchasing['discount'] * $total / 100;
                    $ppn = ($total - $discount) * ubahToRupiahDesimal($dataPurchasing['ppn']) / 100;
                    if ($dataPurchasing): ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right align-middle"><strong>Discount <?= ubahToRupiahDesimal($dataPurchasing['discount']) ?>%</strong></td>
                            <td class="text-right">Rp <?= ubahToRupiahDesimal($discount); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right align-middle bg-secondary"><strong>Harga Setelah Discount </strong></td>
                            <td class="text-right font-weight-bolder bg-secondary">Rp <?= ubahToRupiahDesimal($total - $discount); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right align-middle"><strong>PPN <?= ubahToRupiahDesimal($dataPurchasing['ppn']) ?>%</strong></td>
                            <td class="text-right">Rp <?= ubahToRupiahDesimal($ppn); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right align-middle bg-secondary"><strong>TOTAL </strong></td>
                            <td class="text-right font-weight-bolder bg-secondary">Rp <?= ubahToRupiahDesimal($total - $discount + $ppn); ?></td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
            <form id="formPurchasingDiscountPPN">
                <input type="hidden" name="flag" value="updateDiscountPPN">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodePurchasing" value="<?= $kodePurchasing ?>">
                <div class="form-row justify-content-end">
                    <div class="form-group col-lg-2">
                        <label><i class="fas fa-list"></i> Discount </label>
                        <div class="input-group">
                            <input type="text" name="discount" id="discount" class="form-control" max="100" data-format-rupiah="active" value="<?= ubahToRp($dataPurchasing['discount'] ?? 0) ?>" onchange="prosesPurchasingDiscountPPN()">
                            <div class="input-group-prepend">
                                <span id="satuanBarang" class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-lg-2">
                        <label><i class="fas fa-list"></i> PPN </label>
                        <div class="input-group">
                            <input type="text" name="ppn" id="ppn" class="form-control" max="100" data-format-rupiah="active" value="<?= ubahToRp($dataPurchasing['ppn'] ?? 0) ?>" onchange="prosesPurchasingDiscountPPN()">
                            <div class="input-group-prepend">
                                <span id="satuanBarang" class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php
        } ?>
    </div>




<?php
}
?>