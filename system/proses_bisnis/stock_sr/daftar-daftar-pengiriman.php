<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";
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

    $execute = [
        $statusPO
    ];
    $execute = array_merge($execute, explode(' - ', $periode));

    $dataPurchasing = statementWrapper(
        DML_SELECT_ALL,
        "SELECT stock_pengiriman.*, vendor.nama as namaVendor, vendor.alamat, stock_po.tanggal as tanggalPO, stock_po.*, 
        stock_pengiriman_detail.persentaseDiskon, stock_pengiriman_detail.persentasePpn, SUM(stock_pengiriman_detail.subTotal) as grandTotal
        FROM stock_pengiriman
        INNER JOIN stock_po ON stock_po.kodePO = stock_pengiriman.kodePO
        INNER JOIN stock_pengiriman_detail ON stock_pengiriman_detail.kodePengiriman = stock_pengiriman.kodePengiriman
        INNER JOIN vendor ON stock_po.kodeVendor = vendor.kodeVendor
        WHERE stock_po.statusPO = ? AND (stock_po.tanggal BETWEEN ? AND ?)
        GROUP BY stock_pengiriman.kodePengiriman
        ",
        $execute
    );

    $Labels = [
        'Diproses' => 'primary',
        'Diterima' => 'success',
    ];

?>
    <table class="table table-hover table-bordered">
        <thead class="alert alert-danger">
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 10%;">AKSI</th>
                <th style="width: 30%;">VENDOR</th>
                <th style="width: 10%;">Nomor SR</th>
                <th style="width: 10%;">TANGGAL PO</th>
                <th style="width: 10%;">STATUS PO</th>
                <th class="text-right" style="width: 30%;">TOTAL COST</th>
            </tr>
        </thead>
        <tbody>
            <?php

            if (!$dataPurchasing) {
            ?>
                <tr>
                    <td colspan="7" class="bg-secondary-o-80 text-center font-weight-bolder">
                        TIDAK ADA DATA PURCHASING
                    </td>
                </tr>
            <?php
                exit();
            }


            foreach ($dataPurchasing as $n => $row) {
                $query = encryptURLParam(
                    ['kode' => $row['kodePengiriman']]
                );
            ?>

                <tr>
                    <td class="text-center"><?= $n + 1 ?></td>
                    <td>
                        <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                            <a href="detail_pengiriman/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown">
                                <i class="fa fa-edit"></i> <strong>EDIT</strong>
                            </a>
                            <a href="print/?param=<?= $query ?>" target="_blank" class="btn btn-success btn-sm tombol-dropdown-last">
                                <i class="fas fa-file-pdf"></i> <strong>EXPORT PDF</strong>
                            </a>
                        </div>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['namaVendor'] ?></span>
                        <span class="text-muted font-weight-bold">Vendor</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['nomorSP'] ?></span>
                        <span class="text-muted font-weight-bold">Nomor SP</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['tanggalPO'] ?></span>
                        <span class="text-muted font-weight-bold">Tanggal</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold text-center"><span class="w-100 label label-<?= $Labels[$row['statusPO']] ?> label-pill label-inline mr-2"><?= $row['statusPO'] ?></span></span>
                        <span class="text-muted font-weight-bold text-center">Order</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold text-right">Rp. <?= ubahToRp($row['grandTotal']) ?></span>
                        <span class="d-block text-muted font-weight-bold text-right">Grand Total</span>
                    </td>
                </tr>

        <?php }
        } ?>
        </tbody>
    </table>