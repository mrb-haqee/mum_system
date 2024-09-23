<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenisharga.php";
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
		AND (
            menu_sub.namaFolder = ?
            OR 
            menu_sub.namaFolder = ?
        )
	'
);
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__),
    'rekam_medis'
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


    if ($metode === 'EDC') {
        $defaultBatch = bin2hex(random_bytes(32));

        $listEDC = statementWrapper(
            DML_SELECT_ALL,
            'SELECT 
                * 
            FROM 
                tujuan_transfer_edc
            WHERE
                kodeTujuanTransfer = ?
                AND statusTransferEDC = ?
        ',
            [$kodeTujuanTransfer, 'Aktif'],
        );
?>
        <div class="form-group" id="boxEDC">
            <label for="idTransferEDC"><i class="fas fa-cash-register"></i> EDC</label>
            <select id="idTransferEDC" name="idTransferEDC" class="form-control selectpicker" data-live-search="true">
                <?php

                foreach ($listEDC as $EDC) {
                ?>
                    <option value="<?= $EDC['idTransferEDC'] ?>"><?= $EDC['namaMesin']; ?></option>
                <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group d-none" id="boxNoBatch">
            <label for="noBatch"><i class="fas fa-id-card"></i>NO. BATCH</label>
            <input type="text" class="form-control" name="noBatch" id="noBatch" placeholder="NO. Batch" value="<?= $defaultBatch ?>">
        </div>
<?php
    }
}
?>