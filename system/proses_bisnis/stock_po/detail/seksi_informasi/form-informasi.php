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
        'SELECT * FROM stock_po WHERE kodePO=?',
        [$kodePO]
    );

    $disabled = '';
    if ($dataUpdate) {
        $flag = 'update';
        $disabled  = ($dataUpdate['statusPersetujuan'] == 'Approve') ? 'disabled' : '';
    } else {
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Purchasing Order
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

            <form id="formPO">
                <input type="hidden" id="flag" name="flag" value="<?= $flag ?>">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="statusPersetujuan" value="<?= $dataUpdate['statusPersetujuan'] ?>">
                <input type="hidden" class="form-control" id="kodePO" name="kodePO" value="<?= $kodePO ?>" readonly>
                <?php if ($dataUpdate['keterangan'] ?? ''): ?>
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i></i> Informasi Perbaikan</label>
                        <div class="alert alert-danger" role="alert">
                            <?= $dataUpdate['keterangan'] ?>
                        </div>
                    </div>
                <?php endif ?>
                <div class="form-row">
                    <!-- <div class="form-group col-lg-6">
                        <label><i class="fa fa-id-badge"></i> Kode Purchasing</label>
                        <input type="text" class="form-control" id="kodePO" name="kodePO" value="<?= $kodePO ?>" readonly>
                    </div> -->
                    <div class="form-group col-lg-6">
                        <label><i class="fa fa-id-badge"></i> Nomor SP </label>
                        <input type="text" <?= $disabled ?? '' ?> class="form-control" id="nomorSP" name="nomorSP" value="<?= $dataUpdate['nomorSP'] ?? '' ?>" placeholder="nomor SP">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fa fa-calendar"></i> Tanggal </label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" <?= $disabled ?? '' ?> class="form-control" id="tanggal" name="tanggal" placeholder="Tanggal Purchasing" value="<?= $dataUpdate['tanggalPurchasing'] ?? date('Y-m-d') ?>" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-user-tie"></i> Vendor</label>
                        <select id="kodeVendor" <?= $disabled ?? '' ?> name="kodeVendor" class="form-control selectpicker" data-live-search="true">
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
                        <select id="metodeBayar" <?= $disabled ?? '' ?> name="metodeBayar" class="form-control selectpicker" data-live-search="true">
                            <option value="">Pilih Metode Pembayaran</option>
                            <?php
                            $opsi =  ['Tunai'];
                            foreach ($opsi as $row) {
                                $selected = selected($row, $dataUpdate['metodeBayar']);
                            ?>
                                <option value="<?= $row ?>" <?= $selected ?>><?= $row ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                </div>

                <?php if (!$disabled): ?>
                    <div class="form-group text-left">
                        <button type="button" class="btn btn-<?= ($flag == 'tambah') ? 'danger' : 'info' ?> text-center" onclick="prosesPO()">
                            <i class="fa fa-save"></i> <strong>SIMPAN</strong>
                        </button>
                    </div>
                <?php endif ?>
            </form>
        </div>
    </div>
<?php
}
?>