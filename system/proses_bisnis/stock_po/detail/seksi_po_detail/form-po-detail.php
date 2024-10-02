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
include_once "{$constant('BASE_URL_PHP')}/library/fungsimum.php";

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

    $dataUpdate = statementWrapper(
        DML_SELECT,
        "SELECT * FROM stock_po_detail
        INNER JOIN barang ON stock_po_detail.idInventory = barang.idBarang
        WHERE stock_po_detail.kodePO = ? AND stock_po_detail.tipeInventory = 'barang' AND stock_po_detail.idPODetail = ?",
        [$kodePO, $idPODetail]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }


    $disabled = '';
?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Detail Purchasing Order
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

            <?php $dataPO = selectStatement("SELECT * FROM stock_po WHERE kodePO = ?", [$kodePO], 'fetch');
            if (!$dataPO) : ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>MOHON ISI BAGIAN INFORMASI TERLEBIH DAHULU</strong>
                </div>
            <?php
                exit();
            endif;

            if ($dataPO['statusPersetujuan'] != 'Approve'):
            ?>

                <form id="formPODetail">
                    <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                    <input type="hidden" name="kodePO" value="<?= $kodePO ?>">
                    <input type="hidden" name="idPODetail" value="<?= $idPODetail ?>">
                    <input type="hidden" name="flag" value="<?= $flag ?>">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label><i class="fas fa-truck-loading"></i> NAMA BARANG </label>
                            <select id="idInventory" name="idInventory" class="form-control selectpicker" data-live-search="true" onchange="showBarang(), showSubTotal()">
                                <option value="">Pilih Barang</option>
                                <?php
                                $opsiBarang = selectStatement('SELECT * FROM barang', []);
                                foreach ($opsiBarang as $row) {
                                    $selected = selected($row['idBarang'], $dataUpdate['idInventory']);
                                ?>
                                    <option value="<?= $row['idBarang'] ?>" data-harga-satuan='<?= $row['hargaBarang'] ?>' data-satuan-barang='<?= $row['satuanBarang'] ?>' <?= $selected ?>><?= $row['namaBarang'] . ' (' . $row['satuanBarang'] . ')' ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label><i class="fas fa-list"></i> QTY </label>
                            <input type="text" name="qty" id="qty" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['qty'] ?? '') ?>" onkeyup="showSubTotal()">
                        </div>
                        <div class="form-group col-md-2">
                            <label><i class="fas fa-list"></i> SATUAN </label>
                            <input type="text" name="satuan" id="satuan" class="form-control" value="<?= $dataUpdate['satuan'] ?? '' ?>" readonly>
                        </div>
                        <!-- <div class="form-group col-md-2">
                            <label><i class="fas fa-list"></i> SATUAN </label>
                            <select id="satuan" name="satuan" class="form-control selectpicker" data-live-search="true" readonly>
                                <option value="">Pilih Satuan</option>
                                <?php
                                $opsiSatuan = satuanBarang();
                                foreach ($opsiSatuan as $row) {
                                    $selected = selected($row, $dataUpdate['satuan'])
                                ?>
                                    <option value="<?= $row ?>" <?= $selected ?>><?= $row ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div> -->
                        <div class="form-group col-md-2">
                            <label><i class="fas fa-list"></i> HARGA SATUAN </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" id="hargaSatuan" name="hargaSatuan" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['hargaSatuan'] ?? 0) ?>">
                            </div>
                        </div>

                        <div class="form-group col-md-2">
                            <label><i class="fas fa-list"></i> JUMLAH HARGA </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" name="subTotal" id="subTotal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['subTotal'] ?? 0) ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group col-md-1">
                            <label class="d-block"> &nbsp; </label>
                            <button type="button" class="btn  btn-<?= ($flag == 'tambah') ? 'danger' : 'info' ?> text-center" onclick="prosesPODetail()">
                                <i class="fa fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <!-- <hr class="mb-10"> -->
                <div class="separator separator-dashed mb-8"></div>
            <?php else:
                $disabled = 'disabled'; ?>
            <?php endif; ?>

            <div>
                <?php
                $dataPembelian = statementWrapper(
                    DML_SELECT_ALL,
                    "SELECT
                *
            FROM
                stock_po_detail
                INNER JOIN barang on stock_po_detail.idInventory = barang.idBarang
            WHERE
                stock_po_detail.kodePO = ? AND stock_po_detail.tipeInventory = 'barang'
            ",
                    [$kodePO]
                );
                if (count($dataPembelian) > 0) {
                ?>
                    <table class="table table-hover table-bordered">
                        <thead class="bg-danger text-white">
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
                                        <?php
                                        if (selectStatement('SELECT statusPersetujuan FROM stock_po WHERE kodePO = ?', [$kodePO], 'fetch')['statusPersetujuan'] != 'Approve'): ?>
                                            <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-cogs"></i>
                                            </button>
                                            <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                                <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="seksiFormPODetail('<?= $row['idPODetail'] ?>')">
                                                    <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deletePODetail('<?= $row['idPODetail'] ?>', '<?= $row['kodePO'] ?>', '<?= $tokenCSRF ?>')">
                                                    <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                                </button>
                                            </div>
                                        <?php endif; ?>
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
                                        <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['hargaSatuan']) ?></span>
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
                                <td class="text-right font-weight-bolder">Rp <?= ubahToRupiahDesimal($total); ?></td>
                                <input form="formPOPembayaran" type="hidden" id="jumlah" name="jumlah" value="<?= $total ?>">
                            </tr>
                            <?php
                            $dataPembayaran = statementWrapper(
                                DML_SELECT,
                                "SELECT * FROM stock_po_pembayaran
                                WHERE stock_po_pembayaran.kodePO = ?",
                                [$kodePO]
                            );
                            if ($dataPembayaran):
                            ?>
                                <tr>
                                    <td colspan="5" class="text-right">
                                        <div class="d-flex align-items-center font-weight-bolder justify-content-end">
                                            <p class="mb-0 mr-2">Discount</p>
                                            <input form="formPOPembayaran" type="text" <?= $disabled ?> id="diskon" name="diskon" class="form-control form-control-sm w-25 font-weight-bolder max-w-75px text-center" max="100"
                                                value="<?= $dataPembayaran['persentaseDiskon'] ?? 0 ?>" onchange="prosesPOPembayaran()">
                                            <span class="ml-2">%</span>
                                        </div>
                                    </td>
                                    <td class="text-right">Rp <?= ubahToRupiahDesimal($dataPembayaran['diskon']) ?? 0; ?><br>
                                        <span class="text-muted font-weight-bold">Total Discount</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right align-middle"><strong>Harga Setelah Discount </strong></td>
                                    <td class="text-right font-weight-bolder"> Rp <?= ubahToRupiahDesimal($total - $dataPembayaran['diskon']) ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right align-middle">
                                        <div class="d-flex align-items-center font-weight-bolder justify-content-end">
                                            <p class="mb-0 mr-2">PPN</p>
                                            <input form="formPOPembayaran" type="text" <?= $disabled ?> id="ppn" name="ppn" class="form-control form-control-sm w-25 font-weight-bolder max-w-75px text-center" max="100"
                                                value="<?= $dataPembayaran['persentasePpn'] ?? 0 ?>" onchange="prosesPOPembayaran()">
                                            <span class="ml-2">%</span>
                                        </div>
                                    </td>
                                    <td class="text-right">Rp <?= ubahToRupiahDesimal($dataPembayaran['ppn']) ?? 0; ?><br>
                                        <span class="text-muted font-weight-bold">Total PPN</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right align-middle"><strong>GRAND TOTAL</strong></td>
                                    <td class="text-right font-weight-bolder">Rp <?= ubahToRupiahDesimal($dataPembayaran['grandTotal']) ?? 0; ?></td>
                                </tr>
                                <form id="formPOPembayaran">
                                    <input form="formPOPembayaran" type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                                    <input form="formPOPembayaran" type="hidden" name="kodePO" value="<?= $kodePO ?>">
                                    <input form="formPOPembayaran" type="hidden" name="flag" value="updateTotal">
                                </form>

                            <?php endif ?>
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