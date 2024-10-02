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

    $dataUpdate = statementWrapper(
        DML_SELECT_ALL,
        'SELECT stock_po.*, vendor.nama as namaVendor, vendor.alamat, vendor.noTelp, stock_po_detail.*, stock_po_pembayaran.*, barang.*
        FROM stock_po
        INNER JOIN stock_po_detail ON stock_po.kodePO = stock_po_detail.kodePO
        INNER JOIN barang ON stock_po_detail.idInventory = barang.idBarang
        INNER JOIN stock_po_pembayaran ON stock_po.kodePO = stock_po_pembayaran.kodePO
        LEFT JOIN vendor ON stock_po.kodeVendor = vendor.kodeVendor
        WHERE stock_po.kodePO=?',
        [$kodePO]
    );

    [
        'namaVendor' => $namaVendor,
        'alamat' => $alamat,
        'noTelp' => $noTelp,
        'persentaseDiskon' => $persentaseDiskon,
        'persentasePpn' => $persentasePpn,
        'diskon' => $diskon,
        'ppn' => $ppn,
        'metodeBayar' => $metodeBayar,
        'grandTotal' => $grandTotal
    ] = $dataUpdate[0]

?>

    <table class="table table-borderless">
        <tbody>
            <tr>
                <td style="width: 10%;">Kepada</td>
                <td style="width: 2%;">:</td>
                <td style="width: 28%;"><?= $namaVendor ?><br><?= $alamat ?></td>
                <td style="width: 20%;">Dari</td>
                <td style="width: 2%;">&nbsp;:</td>
                <td style="width: 38%;">PT. Marga Utama Mandiri</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>Contac Person</td>
                <td>&nbsp;:</td>
                <td><?= $dataPegawai['namaPegawai'] ?></td>
            </tr>
            <tr>
                <td>Fax. No</td>
                <td>&nbsp;:</td>
                <td><?= $noTelp ?></td>
                <td>Fax. No</td>
                <td>&nbsp;:</td>
                <td>0361 - 432980</td>
            </tr>
        </tbody>
    </table>

    <table class="table table-bordered">
        <thead>
            <tr class="text-center">
                <th>No</th>
                <th>NAMA BARANG</th>
                <th>QTY</th>
                <th>HARGA SATUAN</th>
                <th>JUMLAH HARGA</th>
            </tr>
        </thead>
        <tbody>
            <?php $total = 0;
            foreach ($dataUpdate as $index => $row) : ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= $row['namaBarang'] ?></td>
                    <td class="text-right"><?= ubahToRp($row['qty']) . ' ' . $row['satuanBarang'] ?></td>
                    <td class="text-right">Rp. <?= ubahToRupiahDesimal($row['hargaBarang']) ?></td>
                    <td class="text-right">Rp. <?= ubahToRupiahDesimal($row['subTotal']) ?></td>
                </tr>
            <?php $total += $row['subTotal'];
            endforeach ?>
        </tbody>
        <tr class="font-weight-bolder bg-secondary-o-80">
            <td colspan="4" class="text-right">Jumlah</td>
            <td class="text-right">Rp. <?= ubahToRupiahDesimal($total) ?></td>
        </tr>
        <tr class="font-weight-bolder">
            <td colspan="4" class="text-right">diskon <?= $persentaseDiskon ?>%</td>
            <td class="text-right">- Rp <?= ubahToRupiahDesimal($diskon); ?></td>
        </tr>
        <tr class="font-weight-bolder">
            <td colspan="4" class="text-right">Harga Setelah diskon </td>
            <td class="text-right ">Rp <?= ubahToRupiahDesimal($total - $diskon); ?></td>
        </tr>
        <tr class="font-weight-bolder">
            <td colspan="4" class="text-right">PPN <?= $persentasePpn ?>%</td>
            <td class="text-right">Rp <?= ubahToRupiahDesimal($ppn); ?></td>
        </tr>
        <tr class="font-weight-bolder bg-secondary-o-80">
            <td colspan="4" class="text-right">TOTAL </td>
            <td class="text-right ">Rp <?= ubahToRupiahDesimal($grandTotal); ?></td>
        </tr>
    </table>

    <table class="table table-borderless">
        <tbody>
            <tr>
                <td colspan="4">Syarat Pemesanan</td>
            </tr>
            <tr>
                <td style="width: 1%;">1.</td>
                <td style="width: 35%;">Jangka Waktu Penyerahan Barang</td>
                <td style="width: 1%;">:</td>
                <td style="width: 63%;">Segera</td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Pembayaran</td>
                <td>:</td>
                <td><?= $metodeBayar ?></td>
            </tr>
        </tbody>
    </table>

    <div class="form-group">
        <label><i class="fas fa-info-circle"></i></i> Keterangan</label>
        <textarea id="keterangan" name="keterangan" class="form-control" placeholder="keterangan / informasi perbaikan / komentar" rows="5" form="formValidasiPurchasing"></textarea>
    </div>
    <div class="form-row justify-content-end">
        <div class="form-group text-right">
            <button type="button" class="btn btn-danger text-center mr-5" onclick="prosesValidasiPurchasing('<?= $kodePO ?>','Reject','<?= $tokenCSRF ?>')">
                <i class="fas fa-times-circle"></i> <strong>REJECT</strong>
            </button>
        </div>
        <div class="form-group text-right">
            <button type="button" class="btn btn-success text-center" onclick="prosesValidasiPurchasing('<?= $kodePO ?>','Approve','<?= $tokenCSRF ?>')">
                <i class="fas fa-signature"></i> <strong>APPROVE</strong>
            </button>
        </div>
    </div>

<?php
}
?>