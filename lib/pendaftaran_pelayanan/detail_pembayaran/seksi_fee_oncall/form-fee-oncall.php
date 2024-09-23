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

    $dataFeeOncall = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_fee_oncall WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    if ($dataFeeOncall) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

    if (isset($param)) {
        $result = [];
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

        if (isset($result['src'])) {
            $mode = 'readonly';
        } else {
            $mode = 'input';
        }

?>
        <div class="card card-custom">
            <!-- CARD HEADER -->
            <div class="card-header">
                <!-- CARD TITLE -->
                <div class="card-title">
                    <h3 class="card-label">
                        <span class="card-label font-weight-bolder text-dark d-block">
                            <i class="fa fa-diagnoses text-dark"></i> Fee Oncall
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
                if (true) {
                ?>
                    <form id="formFeeOncall">
                        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                        <input type="hidden" name="kodeAntrian" value="<?= $kodeAntrian ?>">
                        <input type="hidden" name="jenisHarga" value="<?= $dataAntrian['jenisHarga'] ?>">
                        <input type="hidden" name="flag" value="<?= $flag ?>">

                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Referrer</label>

                                <select name="referrer" id="referrer" class="form-control selectpicker" onchange="selectReferrer()" data-live-search="true" style="width: 100%;">
                                    <?php
                                    if ($mode === 'input') {
                                    ?>
                                        <option value=""> Pilih Referrer</option>
                                    <?php
                                        $opsi = ['Hotel', 'Guide', 'Perorangan'];
                                    } else {
                                        $opsi = $dataFeeOncall['referrer'] ? [$dataFeeOncall['referrer']] : ['', 'Hotel', 'Guide', 'Perorangan'];
                                    }

                                    foreach ($opsi as $row) {
                                        $selected = selected($row, $dataFeeOncall['referrer'] ?? '');
                                    ?>
                                        <option value="<?= $row ?>" <?= $selected; ?>> <?= $row === '' ? 'Pilih Referrer' : $row ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>

                            </div>
                        </div>
                        <div id="boxSelectReferrer">

                        </div>
                        <?php
                        if ($mode === 'input') {
                        ?>
                            <div class="form-group">
                                <button type="button" class="btn btn-danger" onclick="prosesFeeOncall()">
                                    <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                                </button>
                            </div>
                        <?php
                        }
                        ?>
                    </form>

                <?php
                } else {
                ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>SILAHKAN LENGKAPI DATA PEMERIKSAAN PASIEN TERLEBIH DAHULU UNTUK MELANJUTKAN</strong>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
<?php
    }
}
?>