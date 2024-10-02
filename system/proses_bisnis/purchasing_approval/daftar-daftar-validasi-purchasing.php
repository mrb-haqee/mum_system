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
        'Aktif',
        $statusPersetujuan
    ];
    $execute = array_merge($execute, explode(' - ', $periode));

    $dataPurchasing = statementWrapper(
        DML_SELECT_ALL,
        "SELECT stock_po.*, 
            vendor.nama as namaVendor, 
            vendor.alamat, 
            stock_po_pembayaran.grandTotal as total_purchasing, 
            stock_po_pembayaran.persentaseDiskon, 
            stock_po_pembayaran.persentasePpn,
            'Purchasing Order' as tipe
        FROM stock_po
        INNER JOIN stock_po_pembayaran 
            ON stock_po.kodePO = stock_po_pembayaran.kodePO
        INNER JOIN vendor 
            ON vendor.kodeVendor = stock_po.kodeVendor
        INNER JOIN (
            SELECT kodePO, MAX(GREATEST(timeStamp, timeStampEdit)) AS maxTimeStamp
            FROM stock_po_detail
            GROUP BY kodePO
        ) stock_po_detail_max 
            ON stock_po.kodePO = stock_po_detail_max.kodePO
        WHERE stock_po.statusFinalisasi = ? 
            AND stock_po.statusPersetujuan = ? 
            AND ( stock_po.tanggal BETWEEN ? AND ? )
        ORDER BY 
            GREATEST(stock_po_detail_max.maxTimeStamp, stock_po_pembayaran.timeStamp, stock_po_pembayaran.timeStampEdit, stock_po.timeStamp, stock_po.timeStampEdit) DESC


        ",
        $execute
    );

    $Labels = [
        'Reject' => 'danger',
        'Approve' => 'success',
        'Pending' => 'warning'
    ];
    if ($dataPurchasing) {
        foreach ($dataPurchasing as $index => $row) {
?>

            <div class="col-xl-6">
                <!--begin::Card-->
                <div class="card card-custom gutter-b card-stretch">
                    <!--begin::Body-->
                    <div class="card-body">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Info-->
                            <div class="d-flex flex-column mr-auto">
                                <!--begin: Title-->
                                <a href="#" class="card-title text-hover-primary font-weight-bolder font-size-h5 text-dark mb-1">
                                    <?= $row['namaVendor'] ?>
                                </a>
                                <span class="text-muted font-weight-bold">
                                    <?= $row['alamat'] ?>
                                </span>
                                <!--end::Title-->
                            </div>
                            <!--end::Info-->
                            <span class="label label-<?= $Labels[$row['statusPersetujuan']] ?> label-pill label-inline mr-2"><strong><?= $row['statusPersetujuan'] ?></strong></span>
                            <?php if ($index == 0): ?>
                                <span class="label label-info label-pill label-inline mr-2"><strong>new edited!</strong></span>
                            <?php endif ?>
                        </div>
                        <!--end::Section-->
                        <!--begin::Content-->
                        <div class="d-flex flex-wrap mt-8">
                            <div class="mr-5 d-flex flex-column mb-7">
                                <span class="d-block font-weight-bold mb-2">
                                    Tipe
                                </span>
                                <span class="btn btn-secondary btn-sm font-weight-bold btn-upper btn-text"><?= $row['tipe'] ?></span>
                            </div>
                            <div class="mr-5 d-flex flex-column mb-7">
                                <span class="d-block font-weight-bold mb-2">
                                    Nomor
                                </span>
                                <span class="btn btn-secondary btn-sm font-weight-bold btn-upper btn-text">No. SP.<?= $row['nomorSP'] ?></span>
                            </div>
                            <div class="mr-5 d-flex flex-column mb-7">
                                <span class="d-block font-weight-bold mb-2">
                                    Tanggal
                                </span>
                                <span class="btn btn-secondary btn-sm font-weight-bold btn-upper btn-text"><?= $row['tanggal'] ?></span>
                            </div>
                            <div class="mr-5 d-flex flex-column mb-7">
                                <span class="d-block font-weight-bold mb-2">
                                    Tanggal Update
                                </span>
                                <span class="btn btn-secondary btn-sm font-weight-bold btn-upper btn-text"><?= $row['tanggalUpdate'] ?></span>
                            </div>
                        </div>
                        <!--end::Content-->
                        <!--begin::Text-->
                        <p class="mb-7 mt-3">
                            <?= $row['keterangan'] ?? 'Tidak ada Keterangan' ?>
                        </p>
                        <!--end::Text-->
                        <!--begin::Blog-->
                        <div class="d-flex flex-wrap">
                            <!--begin: Item-->
                            <div class="mr-12 d-flex flex-column mb-7">
                                <span class="font-weight-bolder">GRAND TOTAL</span>
                                <span class="font-weight-bolder font-size-h5 pt-1"><span class="font-weight-bold text-dark-50">Rp. </span><?= ubahToRupiahDesimal($row['total_purchasing']) ?></span>
                            </div>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <div class="mr-12 d-flex flex-column mb-7">
                                <span class="font-weight-bolder">DISCOUNT</span>
                                <span class="font-weight-bolder font-size-h5 pt-1"><?= $row['persentaseDiskon'] ?><span class="font-weight-bold text-dark-50">%</span></span>
                            </div>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <div class="mr-12 d-flex flex-column mb-7">
                                <span class="font-weight-bolder">PPN</span>
                                <span class="font-weight-bolder font-size-h5 pt-1"><?= $row['persentasePpn'] ?><span class="font-weight-bold text-dark-50">%</span></span>
                            </div>
                            <!--end::Item-->

                        </div>
                        <!--end::Blog-->
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer d-flex align-items-center">
                        <?php if ($row['statusPersetujuan'] == 'Pending'): ?>
                            <button type="button" class="btn btn-light-primary text-uppercase font-weight-bolder w-50 mr-2" onclick="getDetailPurchasing('<?= $row['kodePO'] ?>')"><i class="fas fa-tasks"></i> details</button>
                            <button type="button" class="btn btn-light-success text-uppercase font-weight-bolder w-25 mr-2" onclick="prosesValidasiPurchasing('<?= $row['kodePO'] ?>','Approve','<?= $tokenCSRF ?>')"><i class="fas fa-signature"></i>approve</button>
                            <button type="button" class="btn btn-light-danger text-uppercase font-weight-bolder w-25 mr-2" onclick="prosesValidasiPurchasing('<?= $row['kodePO'] ?>','Reject','<?= $tokenCSRF ?>')"><i class="fas fa-times-circle"></i> reject</button>
                        <?php elseif ($row['statusPersetujuan'] == 'Approve'): ?>
                            <button type="button" class="btn btn-light-primary text-uppercase font-weight-bolder w-50 mr-2" onclick="getDetailPurchasing('<?= $row['kodePO'] ?>')"><i class="fas fa-tasks"></i> details</button>
                            <button type="button" class="btn btn-light-danger text-uppercase font-weight-bolder w-25 mr-2" onclick="prosesValidasiPurchasing('<?= $row['kodePO'] ?>','Reject','<?= $tokenCSRF ?>')"><i class="fas fa-times-circle"></i> reject</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-light-primary text-uppercase font-weight-bolder w-50 mr-2" onclick="getDetailPurchasing('<?= $row['kodePO'] ?>')"><i class="fas fa-tasks"></i> details</button>
                            <button type="button" class="btn btn-light-success text-uppercase font-weight-bolder w-25 mr-2" onclick="prosesValidasiPurchasing('<?= $row['kodePO'] ?>','Approve','<?= $tokenCSRF ?>')"><i class="fas fa-signature"></i>approve</button>
                        <?php endif; ?>
                    </div>
                    <!--end::Footer-->
                </div>
                <!--end::Card-->
            </div>
        <?php

        }
    } else {
        ?>
        <div class="alert alert-custom alert-light-danger fade show mb-5 w-100" role="alert">
            <div class="alert-icon"><i class="flaticon-warning"></i></div>
            <div class="alert-text">Data Not Found</div>
        </div>
<?php
    }
}
?>