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
        'SELECT * FROM stock_awal
        INNER JOIN barang ON barang.idBarang = stock_awal.idInventory
        WHERE idStockAwal = ?',
        [$idStockAwal]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }
?>
    <form id="formStock">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idStockAwal" value="<?= $idStockAwal ?>">
        <input type="hidden" name="flag" value="<?= $flag ?>">

        <div class="form-row">
            <div class="form-group col-md-8">
                <label><i class="fas fa-clinic-medical"></i>Nama Barang</label>
                <select id="idInventory" name="idInventory" class="form-control select2" style="width:100%" onchange="showBarang()">
                    <option value="">Pilih Barang</option>
                    <?php
                    $opsi = statementWrapper(
                        DML_SELECT_ALL,
                        'SELECT * FROM barang',
                        []
                    );
                    foreach ($opsi as $row) {
                        $selected = selected($row['idBarang'], $dataUpdate['idBarang'] ?? $idBarang);
                    ?>
                        <option value="<?= $row['idBarang'] ?>" <?= $selected ?> data-satuan-barang='<?= $row['satuanBarang'] ?>'><?= $row['namaBarang'] . " ({$row['satuanBarang']})" ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label><i class="fas fa-list"></i> Satuan </label>
                <input type="text" name="satuan" id="satuan" class="form-control" disabled value="<?= $dataUpdate['satuan'] ?? "" ?>">
            </div>
            <div class="form-group col-md-2">
                <a class="btn btn-danger mt-md-8 w-100" href="/mum_system/system/master_data/barang/detail/?redirect=/mum_system/system/proses_bisnis/stock_awal_mum/"><i class="fa fa-plus-circle"></i> Barang</a>
            </div>
        </div>
        <div class="form-row">

            <div class="form-group col-md-4">
                <label><i class="fas fa-calendar-alt"></i>Tanggal</label>
                <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-orientation="bottom left">
                    <input type="text" class="form-control" id="tanggal" name="tanggal" placeholder="Click to select a date!" value="<?= $dataUpdate['tanggal'] ?? date('Y-m-d') ?>" autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fa fa-calendar"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group col-md-2">
                <label><i class="fas fa-list"></i> QTY </label>
                <input type="text" name="qty" id="qty" class="form-control" data-format-rupiah="active" value="<?= $dataUpdate['qty'] ?? 0 ?>">
            </div>

        </div>

        <div class="form-row">

            <div class="form-group">
                <?php
                if ($flag === 'tambah') {
                ?>
                    <button type="button" class="btn btn-danger" onclick="prosesStockAwalBarang()">
                        <i class="fas fa-save"></i> <strong>SIMPAN</strong>
                    </button>
                <?php
                } else if ($flag === 'update') {
                ?>
                    <button type="button" class="btn btn-info" onclick="prosesStockAwalBarang()">
                        <i class="fas fa-save"></i> <strong>UPDATE</strong>
                    </button>
                <?php
                }
                ?>
            </div>
    </form>
<?php
}
?>