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
        <label><i class="fa fa-file-medical"></i> Nomor Surat Antigen</label>
        <input type="text" name="kodeSurat" class="form-control" value="SMC/ATG/<?= date('Y/m') ?>">
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            <label><i class="fa fa-calendar-check"></i> ID Sampel</label>
            <input type="text" class="form-control" name="idSampel" placeholder="ID Sampel">
        </div>
        <div class="form-group col-sm-6">
            <label><i class="fa fa-head-side-mask"></i> Sampel</label>
            <select name="sampel" id="sampel" class="form-control selectpicker" data-live-search="true" style="width: 100%;">
                <?php
                $opsiSampel = [
                    'Nasofaring'
                ];

                foreach ($opsiSampel as $row) {
                ?>
                    <option value="<?= $row ?>"> <?= $row ?> </option>
                <?php
                }
                ?>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            <label><i class="fa fa-dna"></i> Nilai Rujukan</label>
            <input type="text" class="form-control" name="nilaiRujukan" value="Negatif" readonly>
        </div>
        <div class="form-group col-sm-6">
            <label><i class="fa fa-dna"></i> Jenis Pemeriksaan</label>
            <input type="text" class="form-control" name="jenisPemeriksaan" value="SARS-CoV-2 Antigen" readonly>
        </div>
    </div>
    <div class="form-group">
        <label><i class="fa fa-head-side-mask"></i> Hasil Pemeriksaan</label>
        <select name="hasilPemeriksaan" id="hasilPemeriksaan" class="form-control selectpicker" data-live-search="true" style="width: 100%;">
            <?php
            $opsiPemeriksaan = [
                'Negatif',
                'Positif',
            ];

            foreach ($opsiPemeriksaan as $row) {
            ?>
                <option value="<?= $row ?>"> <?= $row ?> </option>
            <?php
            }
            ?>
        </select>
    </div>
<?php
}
?>