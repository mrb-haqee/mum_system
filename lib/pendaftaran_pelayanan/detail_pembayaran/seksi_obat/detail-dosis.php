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

include_once "{$constant('BASE_URL_PHP')}{$constant('VENDOR_SATU_SEHAT_DIR')}/load.php";

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

    $dataInvoice = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_invoice_klinik WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    if (isset($param)) {
        $result = [];
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

        if (isset($result['src'])) {
            $mode = 'readonly';
        } else {
            $mode = 'input';
        }

        $dataObat = statementWrapper(
            DML_SELECT,
            'SELECT
                obat.*,
                obat_satusehat.*
            FROM
                obat
                INNER JOIN obat_satusehat ON obat.kodeObat = obat_satusehat.kodeObat
            WHERE
                obat.idObat = ?  
            ',
            [$idObat]
        );


        if ($dataObat) {
            $form = \SatuSehat\FHIR\Interoperabilitas\MedicationForm::invokeIfExist($dataObat['jenisObat']);

            $unit = (\SatuSehat\DataType\Other\Measurement::invokeIfExist($dataObat['unitDenominator'], 1))
                ?: (\SatuSehat\DataType\Other\DrugForm::invokeIfExist($dataObat['unitDenominator'], 1));
?>
            <h6 class="pb-3"><i class="fas fa-file-signature pr-4"></i><strong>DETAIL DOSIS</strong> <sup>( Wajib Satu Sehat )</sup></label></h6>
            <div class="row">
                <div class="form-group col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">FORM</span>
                        </div>
                        <input type="text" name="dosageForm" id="dosageForm" class="form-control" readonly value="<?= $dataObat['jenisObat'] ?>" tabindex="-1">
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">RATE</span>
                        </div>
                        <input type="text" name="dosageRate" id="dosageRate" placeholder="Rate" class="form-control" onkeyup="getDosageResult()" data-format-rupiah="active">
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                QTY
                            </span>
                        </div>
                        <input type="text" name="dosageQty" id="dosageQty" placeholder="Qty" class="form-control" onkeyup="getDosageResult()" data-format-rupiah="active">
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                UNIT
                            </span>
                        </div>
                        <input type="text" name="dosageUnit" id="dosageUnit" class="form-control" readonly value="<?= $unit ? $unit->display : '' ?>" tabindex="-1" data-code="<?= $unit ? $unit->code : '' ?>">
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="input-group">
                        <input type="text" name="dosageDuration" id="dosageDuration" placeholder="Duration" class="form-control" onkeyup="getDosageResult()" data-format-rupiah="active">
                        <div class="input-group-append">
                            <span class="input-group-text">
                                HARI
                            </span>
                        </div>
                    </div>
                </div>
                <div class="form-group col-md-9">
                    <input type="text" name="result" id="result" class="form-control" onkeyup="getDosageResult()" readonly>
                </div>
            </div>
            <?php
        } else {
            if ($idObat !== '') {
            ?>
                <div class="alert alert-danger mb-0" role="alert">
                    <i class="fas fa-exclamation-circle pr-5 text-white" style="font-size: 12px;"></i><strong>OBAT INI TIDAK DAPAT DISINKRONKAN DENGAN SATU SEHAT. MOHON ISI DETAIL LENGKAP OBAT DI MASTER DATA OBAT</strong>
                </div>
            <?php
            } else {
            ?>
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="fas fa-info-circle pr-5 text-white" style="font-size: 12px;"></i><strong>PILIH OBAT TERLEBIH DAHULU UNTUK MENAMPILKAN INPUTAN DOSIS</strong>
                </div>
<?php
            }
        }
    }
}
?>