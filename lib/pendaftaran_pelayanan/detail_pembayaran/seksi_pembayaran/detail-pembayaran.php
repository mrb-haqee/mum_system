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

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT
            pasien_invoice_klinik.*,
            admisi.namaAdmisi
        FROM
            pasien_antrian
            INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
            INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
            LEFT JOIN pasien_pemeriksaan_klinik ON pasien_antrian.kodeAntrian = pasien_pemeriksaan_klinik.kodeAntrian
            LEFT JOIN pasien_invoice_klinik ON pasien_antrian.kodeAntrian = pasien_invoice_klinik.kodeAntrian 
        WHERE 
            pasien_antrian.kodeAntrian=?',
        [$kodeAntrian]
    );

    $jumlahBayar = intval($sisaBayar);

    $currencies = getCurrencyList();
    $detailCurrencyDefault = $currencies[CURRENCY_DEFAULT];

    switch ($metodePembayaran) {
        case 'Tunai':
?>
            <div class="form-group" id="boxCurrency">
                <label for="currency"><i class="fas fa-money-check-alt"></i> CURRENCY</label>
                <select id="currency" name="currency" class="form-control select2" data-live-search="true" onchange="selectCurrency()" style="width: 100%;">
                    <?php
                    $listCurrency = getCurrencyList();

                    foreach ($listCurrency as $code => $currency) {
                        $selected = selected($code, 'IDR');
                    ?>
                        <option value="<?= $code ?>" <?= $selected; ?>><?= $currency['name']; ?> (<?= $code; ?>)</option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlahBayar"><i class="fas fa-hand-holding-usd"></i> <span id="selisihLabel">KEMBALIAN </span> (<?= CURRENCY_DEFAULT; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrencyDefault['symbol']; ?>
                        </span>
                    </div>
                    <input type="text" class="form-control text-right" name="selisihPembayaran" id="selisihPembayaran" value="0" readonly>
                </div>
            </div>
        <?php
            break;
        case 'Non Tunai':
        ?>
            <div class="form-group" id="boxTujuanTransfer">
                <label for="kodeTujuanTransfer"><i class="fas fa-money-check-alt"></i> TUJUAN TRANSFER</label>
                <select id="kodeTujuanTransfer" name="kodeTujuanTransfer" class="form-control selectpicker" data-live-search="true" onchange="selectMetode()">
                    <option value=""> Pilih Tujuan Transfer</option>
                    <?php
                    $dataTujuanTransfer = statementWrapper(
                        DML_SELECT_ALL,
                        'SELECT * FROM tujuan_transfer WHERE statusTujuanTransfer = ?',
                        ['Aktif']
                    );
                    foreach ($dataTujuanTransfer as $row) {
                        $selected = selected($row['kodeTujuanTransfer'], $dataUpdate['kodeTujuanTransfer'] ?? '');
                    ?>
                        <option value="<?= $row['kodeTujuanTransfer'] ?>" <?= $selected; ?>><?= $row['vendor'] ?> - <?= $row['noReferensi']; ?> a.n. <?= $row['atasNama'] ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlahBayar"><i class="fas fa-cash-register"></i> JUMLAH BAYAR</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= CURRENCY_DEFAULT; ?>
                        </span>
                    </div>
                    <input type="text" class="form-control text-right" name="jumlahBayar" id="jumlahBayar" value="<?= ubahToRupiahDesimal($jumlahBayar) ?>" data-format-rupiah="active" onkeyup="getKembalian()">
                </div>
            </div>
        <?php
            break;
        case 'Insurance':
        ?>
            <div class="form-group">
                <label for="kodeAsuransi"><i class="fas fa-house-damage"></i> INSURANCE</label>
                <select id="kodeAsuransi" name="kodeAsuransi" class="form-control selectpicker" data-live-search="true">
                    <option value=""> Pilih Asuransi</option>
                    <?php
                    $dataAsuransi = statementWrapper(
                        DML_SELECT_ALL,
                        'SELECT * FROM asuransi WHERE statusAsuransi = ?',
                        ['Aktif']
                    );
                    foreach ($dataAsuransi as $row) {
                        $selected = selected($row['kodeAsuransi'], $dataUpdate['kodeAsuransi'] ?? '');
                    ?>
                        <option value="<?= $row['kodeAsuransi'] ?>" <?= $selected; ?>><?= $row['nama'] ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group" id="boxCurrency">
                <label for="currency"><i class="fas fa-money-check-alt"></i> CURRENCY</label>
                <select id="currency" name="currency" class="form-control select2" data-live-search="true" onchange="selectCurrency()" style="width: 100%;">
                    <?php
                    $listCurrency = getCurrencyList();

                    foreach ($listCurrency as $code => $currency) {
                        $selected = selected($code, 'IDR');
                    ?>
                        <option value="<?= $code ?>" <?= $selected; ?>><?= $currency['name']; ?> (<?= $code; ?>)</option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlahBayar"><i class="fas fa-hand-holding-usd"></i> <span id="selisihLabel">KEMBALIAN </span> (<?= CURRENCY_DEFAULT; ?>)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <?= $detailCurrencyDefault['symbol']; ?>
                        </span>
                    </div>
                    <input type="text" class="form-control text-right" name="selisihPembayaran" id="selisihPembayaran" value="0" readonly>
                </div>
            </div>
        <?php
            break;
        case 'Klien':
        ?>
            <div class="form-group">
                <label for="kodeKlien"><i class="fas fa-house-damage"></i> HOTEL</label>
                <select id="kodeKlien" name="kodeKlien" class="form-control selectpicker" data-live-search="true">
                    <option value=""> Pilih Asuransi</option>
                    <?php
                    $dataAsuransi = statementWrapper(
                        DML_SELECT_ALL,
                        'SELECT * FROM hotel WHERE statusHotel = ?',
                        ['Aktif']
                    );
                    foreach ($dataAsuransi as $row) {
                        $selected = selected($row['kodeHotel'], $dataUpdate['kodeKlien'] ?? '');
                    ?>
                        <option value="<?= $row['kodeHotel'] ?>" <?= $selected; ?>><?= $row['namaHotel'] ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>

    <?php
            break;
        default:

            break;
    }
    ?>


<?php
}
?>