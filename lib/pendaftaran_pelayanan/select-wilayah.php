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


?>
    <div class="form-group col-sm-3" id="boxProvinsi">
        <label for="provinceCode"><i class="fas fa-map-marker-alt"></i> Provinsi <sup>( Wajib Satu Sehat )</sup></label>
        <select name="provinceCode" id="provinceCode" style="width:100%" class="select2 form-control" onchange="selectWilayah()">
            <option value="">Pilih Provinsi</option>
            <?php
            $provinces = statementWrapper(
                DML_SELECT_ALL,
                "SELECT * FROM wilayah WHERE jenis = 'Provinsi'",
                []
            );

            foreach ($provinces as $province) {
                $selected = selected($province['kodeResmi'], $provinceCode ?? '')
            ?>
                <option value="<?= $province['kodeResmi'] ?>" <?= $selected; ?>><?= $province['nama']; ?></option>
            <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-sm-3" id="boxRegency">
        <label for="regencyCode"><i class="fas fa-map-marker-alt"></i> Kabupaten / Kota <sup>( Wajib Satu Sehat )</sup></label>
        <select name="regencyCode" id="regencyCode" style="width:100%" class="select2 form-control" onchange="selectWilayah()">
            <option value="">Pilih Kabupaten / Kota</option>
            <?php
            $regencies = statementWrapper(
                DML_SELECT_ALL,
                "SELECT * FROM wilayah WHERE jenis = 'Kabupaten/Kota' AND parent = ?",
                [$provinceCode ?? '']
            );

            foreach ($regencies as $regency) {
                $selected = selected($regency['kodeResmi'], $regencyCode ?? '')
            ?>
                <option value="<?= $regency['kodeResmi'] ?>" <?= $selected; ?>><?= $regency['nama']; ?></option>
            <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-sm-3" id="boxDistrict">
        <label for="districtCode"><i class="fas fa-map-marker-alt"></i> Kecamatan <sup>( Wajib Satu Sehat )</sup></label>
        <select name="districtCode" id="districtCode" style="width:100%" class="select2 form-control" onchange="selectWilayah()">
            <option value="">Pilih Kecamatan</option>
            <?php
            $districts = statementWrapper(
                DML_SELECT_ALL,
                "SELECT * FROM wilayah WHERE jenis = 'Kecamatan' AND parent = ?",
                [$regencyCode ?? '']
            );

            foreach ($districts as $district) {
                $selected = selected($district['kodeResmi'], $districtCode ?? '')
            ?>
                <option value="<?= $district['kodeResmi'] ?>" <?= $selected; ?>><?= $district['nama']; ?></option>
            <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-sm-3" id="boxVillage">
        <label for="villageCode"><i class="fas fa-map-marker-alt"></i> Desa <sup>( Wajib Satu Sehat )</sup></label>
        <select name="villageCode" id="villageCode" style="width:100%" class="select2 form-control" onchange="selectWilayah()">
            <option value="">Pilih Desa</option>
            <?php
            $villages = statementWrapper(
                DML_SELECT_ALL,
                "SELECT * FROM wilayah WHERE jenis = 'Desa' AND parent = ?",
                [$districtCode ?? '']
            );

            foreach ($villages as $village) {
                $selected = selected($village['kodeResmi'], $villageCode ?? '')
            ?>
                <option value="<?= $village['kodeResmi'] ?>" <?= $selected; ?>><?= $village['nama']; ?></option>
            <?php
            }
            ?>
        </select>
    </div>

<?php
}
?>