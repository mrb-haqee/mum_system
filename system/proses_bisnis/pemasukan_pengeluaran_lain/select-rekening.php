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


    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT 
            *
        FROM 
            pemasukan_pengeluaran_lain
        WHERE 
            idPemasukanPengeluaranLain = ?',
        [$idPemasukanPengeluaranLain]
    );

    if ($dataUpdate) {
        $jenisRekening = $dataUpdate['jenisRekening'];
    }

?>
    <label class="text-uppercase"><i class="fas fa-user-tie"></i> <?= $jenisRekening ?: 'REKENING'; ?></label>
    <select class="form-control selectpicker" id="idRekening" name="idRekening" data-live-search="true">
        <option value="">Pilih Tujuan</option>
        <?php
        $config = [
            'Bank' => statementWrapper(
                DML_SELECT_ALL,
                "SELECT CONCAT(vendor, ' / ', atasNama) as nama, idBank AS idRekening FROM bank WHERE statusBank = ?",
                ['Aktif']
            ),
            'Petty Cash' => statementWrapper(
                DML_SELECT_ALL,
                'SELECT namaPettyCash as nama, idPettyCash as idRekening FROM petty_cash WHERE statusPettyCash = ?',
                ['Aktif']
            )
        ];

        if (isset($config[$jenisRekening])) {
            foreach ($config[$jenisRekening] as $row) {
                $selected = selected($row['idRekening'], $dataUpdate['idRekening']);
        ?>
                <option value="<?= $row['idRekening'] ?>" <?= $selected ?>>
                    <?= $row['nama'] ?>
                </option>
        <?php
            }
        }
        ?>

    </select>
<?php
}
?>