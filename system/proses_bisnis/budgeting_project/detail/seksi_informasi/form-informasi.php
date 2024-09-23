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

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * FROM budgeting_project WHERE kodeBudgetingProject=?',
        [$kodeBudgetingProject]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Informasi Budgeting Project
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

            <form id="formBudgetingProject">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeBudgetingProject" value="<?= $kodeBudgetingProject ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">



                <div class="form-row ">
                    <div class="form-group col-md-4">
                        <label for="tanggal"><i class="fas fa-calendar-alt"></i> Tanggal Awal Kontrak</label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" class="form-control" id="tanggalAwalKontrak" name="tanggalAwalKontrak" placeholder="Click to select a date!" value="<?= $dataUpdate['tanggalAwalKontrak'] ?? date('Y-m-d') ?>" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="tanggal"><i class="fas fa-calendar-alt"></i> Tanggal Akhir Kontrak</label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" class="form-control" id="tanggalAkhirKontrak" name="tanggalAkhirKontrak" placeholder="Click to select a date!" value="<?= $dataUpdate['tanggalAkhirKontrak'] ?? date('Y-m-d') ?>" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="tanggal"><i class="fas fa-calendar-alt"></i> Tanggal Pelaksanaan</label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" class="form-control" id="tanggalPelaksanaan" name="tanggalPelaksanaan" placeholder="Click to select a date!" value="<?= $dataUpdate['tanggalPelaksanaan'] ?? date('Y-m-d') ?>" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row ">
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-file"></i> Nama Budgeting Project </label>
                        <input type="text" class="form-control" id="namaBudgetingProject" name="namaBudgetingProject" placeholder="Nama Budgeting Project" value="<?= $dataUpdate['namaBudgetingProject'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-file"></i> Vendor </label>
                        <select class="form-control selectpicker" id="kodeVendor" name="kodeVendor" data-live-search="true">
                            <option value="">Pilih Vendor</option>
                            <?php
                            $dataVendor = statementWrapper(
                                DML_SELECT_ALL,
                                "SELECT * FROM vendor WHERE 
                                    statusVendor = ?",
                                ['Aktif']
                            );

                            foreach ($dataVendor as $row) :
                                $selected = selected($row['kodeVendor'], $dataUpdate['kodeVendor']);
                            ?>
                                <option value="<?= $row["kodeVendor"] ?>" <?= $selected ?>><?= $row["nama"] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-file"></i> Nama PIC </label>
                        <input type="text" class="form-control" id="namaPIC" name="namaPIC" placeholder="Nama PIC" value="<?= $dataUpdate['namaPIC'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-align-center"></i> No Telp PIC </label>
                        <input type="text" class="form-control" id="noTelpPIC" name="noTelpPIC" placeholder="No Tlpn PIC" value="<?= $dataUpdate['noTelpPIC'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa fa-id-card"></i> Keterangan </label>
                    <textarea class="form-control" name="keterangan" placeholder="Input Keterangan"><?= $dataUpdate['keterangan'] ?? '' ?></textarea>
                </div>


                <div class="form-group">
                    <?php
                    if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesBudgetingProject()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-info" onclick="prosesBudgetingProject()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    }
                    ?>
                </div>

            </form>
        </div>
    </div>
<?php
}
?>