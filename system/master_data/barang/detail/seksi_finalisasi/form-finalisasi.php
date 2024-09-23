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

    $sql = $db->prepare('SELECT * FROM barang WHERE kodeBarang = ?');
    $sql->execute([$kodeBarang]);

    $cekBarang = $sql->fetch();


?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Finalisasi
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
            <?php
            if ($cekBarang) {
                if ($cekBarang['statusBarang'] === 'Aktif') {
            ?>
                    <div class="alert alert-primary mb-0">
                        <div class="d-inline-flex">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-check-circle text-light p-5" style="font-size: 3rem;"></i>
                            </div>
                            <div class="ml-5" style="display: flex; flex-direction:column; justify-content:center; gap:5px">
                                <h3 class="mb-0"> <strong>FORM SUDAH DIFINALISASI</strong></h3>
                                <p class="mb-0">Form Sudah Tersimpan</p>
                            </div>
                        </div>
                    </div>
                <?php
                } else {
                ?>
                    <div class="row">
                        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                        <input type="hidden" name="kodeBarang" value="<?= $kodeBarang ?>">
                        <div class="col-md-10">
                            <div class="alert alert-success mb-0">
                                <div class="d-inline-flex">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-check-circle text-light p-5" style="font-size: 3rem;"></i>
                                    </div>
                                    <div class="ml-5" style="display: flex; flex-direction:column; justify-content:center; gap:5px">
                                        <h3 class="mb-0"> <strong>FORM SUDAH TERINPUT</strong></h3>
                                        <p class="mb-0">Silahkan Klik "Finalisasi" Untuk Menyimpan Perubahan Data</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-info w-100 h-100 text-center" type="button" onclick="prosesFinalisasi()">
                                <strong>FINALISASI</strong>
                            </button>
                        </div>
                    </div>
                <?php
                }
            } else {
                ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>MOHON ISI BAGIAN INFORMASI TERLEBIH DAHULU</strong>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
<?php
}
?>