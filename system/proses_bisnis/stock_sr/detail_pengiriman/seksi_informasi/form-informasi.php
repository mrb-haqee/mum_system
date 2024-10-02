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

    $ceksr = selectStatement('SELECT * FROM stock_sr WHERE kodeSR = ?', [$kodeSR]);
    if ($ceksr) {
        $dataUpdate = statementWrapper(
            DML_SELECT_ALL,
            'SELECT stock_sr.*, STOCK_PO.kodePO, stock_sr_detail.*,barang.namaBarang, stock_po_detail.qty as qtyPO, stock_po.kodePO
            FROM stock_sr
            INNER JOIN stock_po ON stock_po.kodePO = stock_sr.kodePO
            INNER JOIN stock_sr_detail ON stock_sr_detail.kodeSR = stock_sr.kodeSR
            INNER JOIN stock_po_detail ON stock_sr_detail.idPODetail = stock_po_detail.idPODetail
            INNER JOIN barang ON stock_sr_detail.idInventory = barang.idBarang
            WHERE stock_sr.kodeSR = ?',
            [$kodeSR]
        );

        [
            'nomorBM' => $nomorBM,
            'nomorFaktur' => $nomorFaktur,
            'kodePO' => $kodePO,
        ] = $dataUpdate[0];

        $flag = 'update';
    } else {
        $dataUpdate = statementWrapper(
            DML_SELECT_ALL,
            'SELECT stock_pengiriman.*, stock_pengiriman_detail.*, barang.namaBarang, stock_po_detail.qty as qtyPO, stock_po.kodePO, stock_po_detail.idPODetail
            FROM stock_pengiriman
            INNER JOIN stock_po ON stock_po.kodePO = stock_pengiriman.kodePO
            INNER JOIN stock_pengiriman_detail ON stock_pengiriman_detail.kodePengiriman = stock_pengiriman.kodePengiriman
            INNER JOIN stock_po_detail ON stock_pengiriman_detail.idPODetail = stock_po_detail.idPODetail
            INNER JOIN barang ON stock_pengiriman_detail.idInventory = barang.idBarang
            WHERE stock_pengiriman.kodePengiriman = ?',
            [$kodePengiriman]
        );

        [
            'persentaseDiskon' => $persentaseDiskon,
            'persentasePpn' => $persentasePpn,
            'kodePO' => $kodePO,
        ] = $dataUpdate[0];
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Service Recipt
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

            <form id="formSR">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodePengiriman" value="<?= $kodePengiriman ?>">
                <input type="hidden" name="kodeSR" value="<?= $kodeSR ?>">
                <input type="hidden" name="kodePO" value="<?= $kodePO ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fa fa-calendar"></i> Tanggal </label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" class="form-control" id="tanggal" name="tanggal" placeholder="Tanggal Purchasing" value="2024-09-30" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-lg-4">
                        <label><i class="fa fa-id-badge"></i> Nomor BM </label>
                        <input type="text" class="form-control" id="nomorBM" name="nomorBM" value="<?= $nomorBM ?? '' ?>" placeholder="nomor BM">
                    </div>
                    <div class="form-group col-lg-4">
                        <label><i class="fa fa-id-badge"></i> Nomor Faktur </label>
                        <input type="text" class="form-control" id="nomorFaktur" name="nomorFaktur" value="<?= $nomorFaktur ?? '' ?>" placeholder="nomor Faktur">
                    </div>
                </div>
            </form>

            <table class="table table-bordered table-responsive-md">
                <thead>
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 20%;">NAMA BARANG</th>
                        <th style="width: 10%;">QTY PO</th>
                        <th style="min-width: 100px;">QTY</th>
                        <th style="width: 10%;">SATUAN</th>
                        <th style="min-width: 150px;">HARGA SATUAN</th>
                        <th style="min-width: 150px;">JUMLAH HARGA</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if ($flag == "tambah"): ?>
                        <?php foreach ($dataUpdate as $index => $row) :
                            $total = isset($total) ? $total : 0; ?>
                            <tr>
                                <input type="hidden" form="formSR" name="item-<?= $index ?>-idInventory" value="<?= $row['idInventory'] ?>">
                                <input type="hidden" form="formSR" name="item-<?= $index ?>-idPODetail" value="<?= $row['idPODetail'] ?>">
                                <input type="hidden" form="formSR" name="item-<?= $index ?>-satuan" value="<?= $row['satuan'] ?>">
                                <td class="text-center"><?= $index + 1 ?></td>
                                <td><?= $row['namaBarang'] ?></td>
                                <td class="text-right align-middle"><?= ubahToRp($row['qtyPO']) ?></td>
                                <td class="text-right align-middle">
                                    <input form="formSR" type="text" id="qty<?= $index ?>" name="item-<?= $index ?>-qty" data-format-rupiah="active" class="form-control form-control-sm text-left" max="100" value="<?= ubahToRupiahDesimal($row['qty']) ?>" onchange="showSubTotal(<?= $index ?>)">
                                </td>
                                <td class="text-right align-middle"><?= $row['satuan'] ?></td>
                                <td class="text-right align-middle">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input form="formSR" readonly type="text" data-format-rupiah="active" id="hargaSatuan<?= $index ?>" name="item-<?= $index ?>-hargaSatuan" class="form-control form-control-sm text-right" max="100" value="<?= ubahToRupiahDesimal(getHargaGrandTotal($row['hargaSatuan'], $persentaseDiskon, $persentasePpn)) ?>">
                                    </div>
                                </td>
                                <td class="text-right align-middle">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input form="formSR" readonly type="text" data-format-rupiah="active" id="subTotal<?= $index ?>" name="item-<?= $index ?>-subTotal" class="form-control form-control-sm text-right" max="100" value="<?= ubahToRupiahDesimal(getHargaGrandTotal($row['subTotal'], $persentaseDiskon, $persentasePpn)) ?>">
                                    </div>
                                </td>
                            </tr>
                        <?php $total += $row['subTotal'];
                        endforeach ?>
                    <?php else: ?>
                        <?php foreach ($dataUpdate as $index => $row) :
                            $total = isset($total) ? $total : 0; ?>
                            <tr>
                                <td class="text-center"><?= $index + 1 ?></td>
                                <td><?= $row['namaBarang'] ?></td>
                                <td class="text-right align-middle"><?= ubahToRp($row['qtyPO']) ?></td>
                                <td class="text-right align-middle">
                                    <input type="text" id="qty<?= $index ?>" data-format-rupiah="active" class="form-control form-control-sm text-left" max="100" value="<?= ubahToRupiahDesimal($row['qty']) ?>" onchange="showSubTotal(<?= $index ?>), prosesUpdateSRDetail(<?= $index ?>)">
                                    <input type="hidden" id="idSRDetail<?= $index ?>" value="<?= $row['idSRDetail'] ?>">
                                </td>
                                <td class="text-right align-middle"><?= $row['satuan'] ?></td>
                                <td class="text-right align-middle">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input readonly type="text" data-format-rupiah="active" id="hargaSatuan<?= $index ?>" class="form-control form-control-sm text-right" max="100" value="<?= ubahToRupiahDesimal($row['hargaSatuan']) ?>">
                                    </div>
                                </td>
                                <td class="text-right align-middle">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input readonly type="text" data-format-rupiah="active" id="subTotal<?= $index ?>" class="form-control form-control-sm text-right" max="100" value="<?= ubahToRupiahDesimal($row['subTotal']) ?>">
                                    </div>
                                </td>
                            </tr>
                        <?php $total += ubahToInt($row['subTotal']);
                        endforeach ?>
                    <?php endif; ?>
                </tbody>
                <!-- <tr class="font-weight-bolder bg-secondary-o-80">
                    <td colspan="6" class="text-right">TOTAL</td>
                    <td class="text-right">Rp. <?= ubahToRupiahDesimal($total) ?></td>
                </tr> -->
                <tr>
                    <td colspan="7">
                        <?php if ($flag == 'tambah'): ?>
                            <button type="button" class="btn btn-success text-center w-100" onclick="prosesSR()">
                                <i class="fas fa-box"></i> <strong>TERIMA</strong>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-info text-center w-100" onclick="prosesSR()">
                                <i class="fas fa-box"></i> <strong>SIMPAN</strong>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>


        </div>
    </div>
<?php
}
?>