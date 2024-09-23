<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/middleware.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenisharga.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasicurrency.php";
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
        )
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
        'SELECT * FROM pemasukan_pengeluaran_lain WHERE idPemasukanPengeluaranLain = ?',
        [$idPemasukanPengeluaranLain]
    );

    if ($dataUpdate) {
        $jenisRekening = $dataUpdate['jenisRekening'];
        $idBank = $dataUpdate['idBank'];
    }

    $currencies = getCurrencyList();

?>
    <div class="form-group col-md-6">
        <label for="currency"><i class="fas fa-money-check-alt"></i> CURRENCY</label>
        <select id="currency" name="currency" class="form-control select2" style="width: 100%;">
            <?php
            $listCurrency = getCurrencyList();

            foreach ($listCurrency as $code => $currency) {
                $selected = selected($code, CURRENCY_DEFAULT);
            ?>
                <option value="<?= $code ?>" <?= $selected; ?>><?= $currency['name']; ?> (<?= $code; ?>)</option>
            <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label><i class="fas fa-money-bill"></i> NOMINAL</label>
        <input type="text" name="nominal" id="nominal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['nominal'] ?? 0) ?>">
    </div>
<?php
}
?>