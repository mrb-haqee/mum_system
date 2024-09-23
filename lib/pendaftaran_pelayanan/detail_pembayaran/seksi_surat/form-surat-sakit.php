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
?>
    <div class="form-group">
        <label><i class="fa fa-file-medical"></i> Nomor Surat Sakit</label>
        <input type="text" name="kodeSurat" class="form-control" value="SMC/SKT/<?= date('Y/m') ?>">
    </div>
    <h5>Rekomendasi Istirahat</h5>
    <hr>
    <div class="form-row">
        <div class="form-group col-sm-6">
            <label><i class="fa fa-calendar"></i> Tanggal Awal</label>
            <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                <input type="text" class="form-control" id="tanggalAwal" name="tanggalAwal" placeholder="Click to select a date!" value="<?= date('Y-m-d') ?>" autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fa fa-calendar"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-group col-sm-6">
            <label><i class="fa fa-calendar"></i> Tanggal Akhir</label>
            <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                <input type="text" class="form-control" id="tanggalAkhir" name="tanggalAkhir" placeholder="Click to select a date!" value="<?= date('Y-m-d') ?>" autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fa fa-calendar"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label><i class="fa fa-hotel"></i> Perusahaan Tempat Bekerja</label>
        <input type="text" name="perusahaan" class="form-control" placeholder="Perusahaan">
    </div>
<?php
}
?>