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

    $dataPurchasing = statementWrapper(
        DML_SELECT_ALL,
        "SELECT 
            purchasing.*, vendor.nama as namaVendor, vendor.alamat, SUM(purchasing_detail.subTotal) as total_purchasing, COUNT(purchasing_detail.subTotal) as jumlah_barang
        FROM 
            purchasing
            INNER JOIN vendor on purchasing.kodeVendor = vendor.kodeVendor
            LEFT JOIN purchasing_detail ON purchasing.kodePurchasing = purchasing_detail.kodePurchasing
        WHERE 
            statusPurchasing = ? AND statusPersetujuan = ?
        GROUP BY purchasing.idPurchasing
        ",
        [
            'Aktif',
            $statusPersetujuan
        ]
    );
    foreach ($dataPurchasing as $row) {
?>
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
            <!--begin::Card-->
            <div class="card card-custom gutter-b card-stretch">
                <!--begin::Body-->
                <div class="card-body pt-4">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end">
                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Quick actions">
                            <a href="#" class="btn btn-clean btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="ki ki-bold-more-hor"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-md dropdown-menu-right">
                                <!--begin::Navigation-->
                                <ul class="navi navi-hover">
                                    <li class="navi-header font-weight-bold py-4">
                                        <span class="font-size-lg">Pilih Aksi</span>
                                        <i class="flaticon2-information icon-md text-muted" data-toggle="tooltip" data-placement="right" title="" data-original-title="Click to learn more..."></i>
                                    </li>
                                    <li class="navi-separator mb-3 opacity-70"></li>
                                    <li class="navi-item">
                                        <a href="#" class="navi-link text-center" onclick="getDetailPurchasing('<?= $row['kodePurchasing'] ?>')">
                                            <span class="navi-text">
                                                <span class="label label-xl label-inline label-light-primary w-100">DETAIL PURCHASING</span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="navi-item">
                                        <a href="#" class="navi-link text-center" onclick="prosesValidasiPurchasing('<?= $row['kodePurchasing'] ?>','Approve','<?= $tokenCSRF ?>')">
                                            <span class="navi-text">
                                                <span class="label label-xl label-inline label-light-success w-100">APPROVE</span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="navi-item">
                                        <a href="#" class="navi-link text-center" onclick="prosesValidasiPurchasing('<?= $row['kodePurchasing'] ?>','Reject','<?= $tokenCSRF ?>')">
                                            <span class="navi-text">
                                                <span class="label label-xl label-inline label-light-danger w-100">REJECT</span>
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                                <!--end::Navigation-->
                            </div>
                        </div>
                    </div>
                    <!--end::Toolbar-->
                    <!--begin::User-->
                    <div class="d-flex align-items-center mb-7">
                        <!--begin::Title-->
                        <div class="d-flex flex-column">
                            <a href="#" class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0"><?= $row['namaVendor'] ?></a>
                            <span class="text-muted font-weight-bold"><?= $row['alamat'] ?></span>
                        </div>
                        <!--end::Title-->
                    </div>
                    <!--end::User-->
                    <!--begin::Desc-->
                    <p class="mb-7">
                        <?= ($row['keterangan']) ? "<span class='label label-xl label-inline label-light-primary'>{$row['keterangan']}</span>"  : '<span class="label label-xl label-inline label-light-success">Tidak ada Keterangan</span>' ?>

                    </p>
                    <!--end::Desc-->
                    <!--begin::Info-->
                    <div class="mb-7">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-dark-75 font-weight-bolder mr-2">Jumlah Barang:</span>
                            <a href="#" class="text-muted text-hover-primary"><?= $row['jumlah_barang'] ?></a>
                        </div>
                        <div class="d-flex justify-content-between align-items-cente my-1">
                            <span class="text-dark-75 font-weight-bolder mr-2">Total Harga:</span>
                            <a href="#" class="text-muted text-hover-primary">Rp. <?= ubahToRp($row['total_purchasing']) ?></a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center my-1">
                            <span class="text-dark-75 font-weight-bolder mr-2">Tanggal Purchasing:</span>
                            <span class="text-muted font-weight-bold"><?= $row['tanggalPurchasing'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-dark-75 font-weight-bolder mr-2">Tanggal Update:</span>
                            <span class="text-muted font-weight-bold"><?= $row['tanggalUpdate'] ?></span>
                        </div>
                    </div>
                    <!--end::Info-->
                    <a href="#" class="btn btn-block btn-sm btn-light-danger font-weight-bolder text-uppercase py-4" onclick="getDetailPurchasing('<?= $row['kodePurchasing'] ?>')">
                        <i class="fas fa-tasks"></i> DETAIL PURCHASING
                    </a>
                </div>
                <!--end::Body-->
            </div>
            <!--end:: Card-->
        </div>
<?php

    }
}
?>