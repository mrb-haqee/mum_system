<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
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

    $currencies = getCurrencyList();
    $detailCurrencyDefault = $currencies[CURRENCY_DEFAULT];

    $jumlahBayar = $sisaBayar;

    if (in_array($currency, array_keys($currencies))) {
        $detailCurrency = $currencies[$currency];

        $sisaBayarExc = getCurrentExchange(ubahToInt($sisaBayar), $currency);
        $jumlahBayarExc = getCurrentExchange(ubahToInt($jumlahBayar), $currency);

        if ($currency !== CURRENCY_DEFAULT) {
?>
            <div class="form-group" id="boxSisaBayarExc">
                <label for="sisaBayarExc"><i class="fas fa-cash-register"></i> SISA BAYAR (<?= $currency; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrency['symbol']; ?>
                        </span>
                    </div>
                    <input type="text" class="form-control text-right" id="sisaBayarExc" value="<?= ubahToRupiahDesimal(round($sisaBayarExc[$currency], 2)) ?>" data-format-rupiah="active" onkeyup="getKembalian()" disabled>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            &#x2245;
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group" id="boxJumlahBayarExc">
                <label for="jumlahBayarExc"><i class="fas fa-cash-register"></i> JUMLAH BAYAR (<?= $currency; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrency['symbol']; ?>
                        </span>
                    </div>
                    <?php
                    if ($metodePembayaran === 'Insurance') {
                    ?>
                        <input type="text" class="form-control text-right" name="jumlahBayarExc" id="jumlahBayarExc" value="<?= ubahToRupiahDesimal(round($jumlahBayarExc[$currency], 2)) ?>" data-format-rupiah="active" onkeyup="getExchangeValue()" readonly>
                    <?php
                    } else if ($metodePembayaran === 'Tunai') {
                    ?>
                        <input type="text" class="form-control text-right" name="jumlahBayarExc" id="jumlahBayarExc" value="<?= ubahToRupiahDesimal(round($jumlahBayarExc[$currency], 2)) ?>" data-format-rupiah="active" onkeyup="getExchangeValue()">
                    <?php
                    }
                    ?>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            &#x2245;
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group" id="boxJumlahBayar">
                <label for="jumlahBayar"><i class="fas fa-cash-register"></i> JUMLAH BAYAR (<?= CURRENCY_DEFAULT; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrencyDefault['symbol'] ?>
                        </span>
                    </div>

                    <input type="text" class="form-control text-right" name="jumlahBayar" id="jumlahBayar" value="<?= ubahToRupiahDesimal($jumlahBayar) ?>" data-format-rupiah="active" onkeyup="getKembalian()" readonly>

                </div>
            </div>
            <div class="form-group" id="boxKembalianExc">
                <label for="jumlahBayar"><i class="fas fa-hand-holding-usd"></i> <span id="selisihLabel">KEMBALIAN</span> (<?= $currency; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrencyDefault['symbol']; ?>
                        </span>
                    </div>
                    <input type="text" class="form-control text-right" name="selisihPembayaranExc" id="selisihPembayaranExc" value="0" readonly>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            &#x2245;
                        </span>
                    </div>
                </div>
            </div>
        <?php
        } else {
        ?>
            <div class="form-group" id="boxJumlahBayar">
                <label for="jumlahBayar"><i class="fas fa-cash-register"></i> JUMLAH BAYAR (<?= CURRENCY_DEFAULT; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrencyDefault['symbol'] ?>
                        </span>
                    </div>
                    <?php
                    if ($metodePembayaran === 'Insurance') {
                    ?>
                        <input type="text" class="form-control text-right" name="jumlahBayar" id="jumlahBayar" value="<?= ubahToRupiahDesimal($jumlahBayar) ?>" data-format-rupiah="active" onkeyup="getKembalian()" readonly>
                    <?php
                    } else if ($metodePembayaran === 'Tunai') {
                    ?>
                        <input type="text" class="form-control text-right" name="jumlahBayar" id="jumlahBayar" value="<?= ubahToRupiahDesimal($jumlahBayar) ?>" data-format-rupiah="active" onkeyup="getKembalian()">
                    <?php
                    }
                    ?>
                </div>
            </div>
<?php
        }
    }
}
?>