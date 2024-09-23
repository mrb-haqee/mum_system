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

    $sqlUpdate = $db->prepare('SELECT * from departemen_pegawai where idDepartemenPegawai=?');
    $sqlUpdate->execute([$idDepartemenPegawai]);
    $dataUpdate = $sqlUpdate->fetch();

?>
    <form id="formDepartemenPegawai">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idDepartemenPegawai" value="<?= $idDepartemenPegawai ?>">
        <input type="hidden" name="flag" value="<?= $flag ?>">

        <div class="form-group">
            <label><i class="fa fa-list"></i> Departemen Pegawai</label>
            <input type="text" class="form-control" id="namaDepartemenPegawai" name="namaDepartemenPegawai" placeholder="Departemen Pegawai" value="<?= $dataUpdate['namaDepartemenPegawai'] ?? '' ?>">
            <span id="labelDepartemenPegawai" class="form-text">
                <i class="fa fa-info-circle text-danger"></i> Tidak boleh kosong!
            </span>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-primary" onclick="prosesDepartemenPegawai()">
                <i class="fa fa-save"></i> Simpan
            </button>
        </div>

    </form>
<?php
}
?>