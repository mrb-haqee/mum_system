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
        'SELECT * FROM account WHERE kodeAccount=?',
        [$kodeAccount]
    );

    if ($dataUpdate) {
        $flag = 'update';
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Account
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

            <form id="formAccount">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeAccount" value="<?= $kodeAccount ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-file"></i> Kode Account </label>
                        <input type="text" class="form-control" id="kode" name="kode" placeholder="Kode Account" value="<?= $dataUpdate['kode'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-align-center"></i> Nama Account </label>
                        <input type="text" class="form-control" id="namaAccount" name="namaAccount" placeholder="Nama Account" value="<?= $dataUpdate['namaAccount'] ?? '' ?>">
                    </div>
                </div>


                <div class="form-group">
                    <?php
                    if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesAccount()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-info" onclick="prosesAccount()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    }
                    ?>
                </div>

            </form>
        </div>
    </div>

<?php
}
?>