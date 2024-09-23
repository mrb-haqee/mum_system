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
        <label><i class="fa fa-file-medical"></i> Nomor Surat Rujukan</label>
        <input type="text" name="kodeSurat" class="form-control" value="SMC/SR/<?= date('Y/m') ?>">
    </div>
    <div class="form-group">
        <label><i class="fa fa-hospital"></i> Tujuan Rujukan</label>
        <select name="tujuanRujukan" id="tujuanRujukan" class="form-control selectpicker" data-live-search="true" style="width: 100%;">
            <option value=""> Pilih Tujuan Rujukan</option>
            <?php
            $opsi = statementWrapper(
                DML_SELECT_ALL,
                'SELECT 
                    * 
                FROM 
                    (
                        (
                            SELECT
                                laboratorium_rujukan.kodeLaboratoriumRujukan as kode,
                                laboratorium_rujukan.nama
                            FROM
                                laboratorium_rujukan
                            WHERE
                                laboratorium_rujukan.statusLaboratoriumRujukan = ?
                        )
                        UNION ALL
                        (
                            SELECT
                                faskes_rujukan.kodeFaskesRujukan as kode,
                                faskes_rujukan.nama
                            FROM
                                faskes_rujukan
                            WHERE
                                faskes_rujukan.statusFaskesRujukan = ?
                        )
                    ) data_tujuan_rujukan
                    ORDER BY nama',
                ['Aktif', 'Aktif']
            );

            foreach ($opsi as $row) {
            ?>
                <option value="<?= $row['nama'] ?>"> <?= $row['nama'] ?> </option>
            <?php
            }
            ?>
        </select>
    </div>
    <!--<div class="form-group">-->
    <!--    <label><i class="fa fa-address-book"></i> Alamat Di Bali</label>-->
    <!--    <input type="text" name="perusahaan" class="form-control" placeholder="Alamat di Bali">-->
    <!--</div>-->
    <div class="form-group">
        <label><i class="fa fa-calendar-check"></i> Other Examination</label>
        <textarea name="hasilPemeriksaan" id="hasilPemeriksaan" class="form-control" data-editor="active" placeholder="Other Examination"></textarea>
    </div>
<?php
}
?>