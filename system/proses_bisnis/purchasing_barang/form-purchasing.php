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
        DML_SELECT,
        'SELECT * FROM purchasing WHERE kodePurchasing=?',
        [$kodePurchasing]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
        $kodePurchasing = nomorUrut($db, 'purchasing', $idUserAsli);
    }
?>

    <div class="container">
        <div id="kt_page_sticky_card" class="card card-custom card-sticky">
            <div class="card-header card-header-tabs-line">
                <div class="card-title">
                    <h3 class="card-label"><i class="fas fa-file-signature pr-5 text-dark"></i> <strong>FORM PURCHASING</strong></h3>
                </div>
            </div>
            <!-- CARD BODY -->
            <div class="card-body">
                <div class="tab-content">
                    <form id="formPurchasing">
                        <input type="hidden" id="flag" name="flag" value="<?= $flag ?>">
                        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                        <?php if ($dataUpdate['keterangan'] ?? ''): ?>
                            <div class="form-group">
                                <label><i class="fas fa-info-circle"></i></i> Informasi Perbaikan</label>
                                <textarea readonly class="form-control" placeholder="Informasi Perbaikan"><?= $dataUpdate['keterangan'] ?></textarea>
                            </div>
                        <?php endif ?>
                        <div class="form-group">
                            <label><i class="fa fa-id-badge"></i> Kode Purchasing</label>
                            <input type="text" class="form-control" id="kodePurchasing" name="kodePurchasing" value="<?= $kodePurchasing ?>" readonly>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-sm-4">
                                <label><i class="fa fa-calendar"></i> Tanggal </label>
                                <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                    <input type="text" class="form-control" id="tanggal" name="tanggal" placeholder="Tanggal Purchasing" value="<?= $dataUpdate['tanggalPurchasing'] ?? date('Y-m-d') ?>" autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="fa fa-calendar"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-sm-4">
                                <label><i class="fas fa-user-tie"></i> Vendor</label>
                                <select id="kodeVendor" name="kodeVendor" class="form-control selectpicker" data-live-search="true">
                                    <option value="">Pilih Vendor</option>
                                    <?php
                                    $opsi =  selectStatement('SELECT * FROM vendor WHERE jenisVendor = ?', ['Supplier']);
                                    foreach ($opsi as $row) {
                                        $selected = selected($row['kodeVendor'], $dataUpdate['kodeVendor']);
                                    ?>
                                        <option value="<?= $row['kodeVendor'] ?>" <?= $selected ?>><?= $row['nama'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label><i class="fas fa-money-bill-wave"></i></i> Metode Pembayaran</label>
                                <select id="metodePembayaran" name="metodePembayaran" class="form-control selectpicker" data-live-search="true">
                                    <option value="">Pilih Metode Pembayaran</option>
                                    <?php
                                    $opsi =  ['Tunai'];
                                    foreach ($opsi as $row) {
                                        $selected = selected($row, $dataUpdate['metodePembayaran']);
                                    ?>
                                        <option value="<?= $row ?>" <?= $selected ?>><?= $row ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>

                        </div>

                        <div class="form-group text-left">
                            <button type="button" class="btn btn-<?= ($flag == 'tambah') ? 'danger' : 'info' ?> text-center" onclick="prosesPurchasing()">
                                <i class="fa fa-save"></i> <strong>SIMPAN</strong>
                            </button>
                        </div>
                    </form>

                    <div class="btn badge-secondary col-12" style="text-align: left;">
                        <label><i class="fas fa-list "></i> <strong>DETAIL PURCHASING</strong> </label>
                    </div>
                    <hr>
                    <?php $data = selectStatement('SELECT * FROM purchasing WHERE kodePurchasing = ? AND statusPurchasing = ?', [$kodePurchasing, 'Aktif']);
                    if (empty($data)) {
                    ?>
                        <div class="alert alert-danger font-weight-bolder align-content-center" role="alert">
                            <i class="fas fa-info-circle text-light"></i> ISI FORM TERLEBIH DAHULU
                        </div>
                    <?php
                    } else {
                    ?>
                        <div id="boxPurchasingDetail">
                        </div>
                    <?php
                    } ?>
                </div>
            </div>
            <!-- END CARD BODY -->
        </div>
        <!-- END CARD -->
    </div>
<?php
}
?>