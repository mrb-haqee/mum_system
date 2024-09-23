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

    $dataAntrian = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_antrian WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    $dataLaboratorium = statementWrapper(
        DML_SELECT,
        'SELECT * FROM prosedur_laboratorium WHERE idProsedurLaboratorium = ?',
        [$idProsedurLaboratorium]
    );

    if ($dataLaboratorium['tempatPenanganan'] === 'Rujukan') {
?>
        <label>Laboratorium Rujukan</label>
        <select name="idLaboratoriumRujukan" id="idLaboratoriumRujukan" class="form-control selectpicker" data-live-search="true" style="width: 100%;">
            <option value=""> Pilih Laboratorium Rujukan</option>
            <?php
            $opsi = statementWrapper(
                DML_SELECT_ALL,
                'SELECT 
                    * 
                FROM 
                    laboratorium_rujukan 
                WHERE 
                    statusLaboratoriumRujukan = ?
                    ',
                ['Aktif']
            );

            foreach ($opsi as $row) {
            ?>
                <option value="<?= $row['idLaboratoriumRujukan'] ?>"> <?= $row['nama'] ?> </option>
            <?php
            }
            ?>
        </select>
    <?php
    } else {
    ?>
        <label>Laboratorium Rujukan</label>
        <input type="text" class="form-control" disabled>
<?php
    }
}
?>