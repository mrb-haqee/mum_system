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
        'SELECT purchasing.*, vendor.nama as namaVendor, vendor.alamat, vendor.noTelp, purchasing_detail.*, barang.*
        FROM purchasing
        INNER JOIN purchasing_detail ON purchasing.kodePurchasing = purchasing_detail.kodePurchasing
        INNER JOIN barang ON purchasing_detail.idBarang = barang.idBarang
        LEFT JOIN vendor ON purchasing.kodeVendor = vendor.kodeVendor
        WHERE purchasing.kodePurchasing=?',
        [$kodePurchasing]
    );

    ['namaVendor' => $namaVendor, 'alamat' => $alamat, 'noTelp' => $noTelp, 'discount' => $discount, 'ppn' => $ppn, 'metodePembayaran' => $metodePembayaran] = $dataUpdate[0]

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
                    <td class="text-right"><?= $row['qty'] . ' ' . $row['satuanBarang'] ?></td>
                    <td class="text-right">Rp. <?= ubahToRupiahDesimal($row['hargaBarang']) ?></td>
                    <td class="text-right">Rp. <?= ubahToRupiahDesimal($row['subTotal']) ?></td>
                </tr>
            <?php $total += $row['subTotal'];
            endforeach ?>
        </tbody>
        <tr class="font-weight-bolder bg-secondary-o-80">
            <td colspan="4" class="text-right">Jumlah</td>
            <td class="text-right"><?= ubahToRupiahDesimal($total) ?></td>
        </tr>
        <?php $hargaDiscount = ($total * $discount) / 100; ?>
        <tr class="font-weight-bolder">
            <td colspan="4" class="text-right">Discount <?= ubahToRupiahDesimal($discount) ?>%</td>
            <td class="text-right">- Rp <?= ubahToRupiahDesimal($hargaDiscount); ?></td>
        </tr>
        <tr class="font-weight-bolder">
            <td colspan="4" class="text-right">Harga Setelah Discount </td>
            <td class="text-right ">Rp <?= ubahToRupiahDesimal($total - $hargaDiscount); ?></td>
        </tr>
        <?php $hargaPPN = ($hargaDiscount * $ppn) / 100; ?>
        <tr class="font-weight-bolder">
            <td colspan="4" class="text-right">PPN <?= ubahToRupiahDesimal($ppn) ?>%</td>
            <td class="text-right">Rp <?= ubahToRupiahDesimal($hargaPPN); ?></td>
        </tr>
        <tr class="font-weight-bolder bg-secondary-o-80">
            <td colspan="4" class="text-right">TOTAL </td>
            <td class="text-right ">Rp <?= ubahToRupiahDesimal($total - $hargaDiscount + $hargaPPN); ?></td>
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
                <td><?= $metodePembayaran ?></td>
            </tr>
        </tbody>
    </table>

    <div class="form-group">
        <label><i class="fas fa-info-circle"></i></i> Keterangan</label>
        <textarea id="keterangan" name="keterangan" class="form-control" placeholder="keterangan / informasi perbaikan / komentar" rows="5" form="formValidasiPurchasing"></textarea>
    </div>
    <div class="form-row justify-content-end">
        <div class="form-group text-right">
            <button type="button" class="btn btn-danger text-center mr-5" onclick="prosesValidasiPurchasing('<?= $kodePurchasing ?>','Reject','<?= $tokenCSRF ?>')">
                <i class="fas fa-times-circle"></i> <strong>REJECT</strong>
            </button>
        </div>
        <div class="form-group text-right">
            <button type="button" class="btn btn-success text-center" onclick="prosesValidasiPurchasing('<?= $kodePurchasing ?>','Approve','<?= $tokenCSRF ?>')">
                <i class="fas fa-signature"></i> <strong>APPROVE</strong>
            </button>
        </div>
    </div>

<?php
}
?>