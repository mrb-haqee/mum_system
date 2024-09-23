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

    $dataHeader = statementWrapper(
        DML_SELECT,
        'SELECT * FROM budgeting_project WHERE kodeBudgetingProject=?',
        [$kodeBudgetingProject]
    );


    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * FROM budgeting_project_tim WHERE idBudgetingProjectTim=?',
        [$idBudgetingProjectTim]
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Tim
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

            <form id="formTim">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeBudgetingProject" value="<?= $kodeBudgetingProject ?>">
                <input type="hidden" name="idBudgetingProject" value="<?= $idBudgetingProject ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">
                <?php if ($dataHeader): ?>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-file"></i> Pegawai </label>
                            <select class="form-control selectpicker" id="kodePegawai" name="kodePegawai" data-live-search="true">
                                <option value="">Pilih Pegawai</option>
                                <?php
                                $dataPegawai = statementWrapper(
                                    DML_SELECT_ALL,
                                    "SELECT * FROM Pegawai WHERE 
                                    statusPegawai = ?",
                                    ['Aktif']
                                );

                                foreach ($dataPegawai as $row) :
                                    $selected = selected($row['kodePegawai'], $dataUpdate['kodePegawai']);
                                ?>
                                    <option value="<?= $row["kodePegawai"] ?>" <?= $selected ?>><?= $row["namaPegawai"] ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="jabatan"><i class="fas fa-piggy-bank"></i>Jenis Jabatan</label>
                            <select class="form-control selectpicker" id="jabatan" name="jabatan" style="width: 100%;">
                                <option value="">Pilih Jabatan</option>
                                <?php
                                $opsi = ['Leader', 'Penanggung Jawab', 'Anggota'];

                                foreach ($opsi as $row) {
                                    $selected = selected($row, $dataUpdate['jabatan']);

                                ?>
                                    <option value="<?= $row ?>" <?= $selected ?>>
                                        <?= $row ?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-1">
                            <label class="d-block"> &nbsp; </label>
                            <button type="button" class="btn btn-primary text-center" onclick="prosesTim()">
                                <i class="fa fa-save"></i>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>MOHON ISI BAGIAN INFORMASI TERLEBIH DAHULU</strong>
                    </div>
                <?php endif; ?>



            </form>

            <div>
                <?php
                $dataTim = statementWrapper(
                    DML_SELECT_ALL,
                    "SELECT
                        budgeting_project_tim.*, pegawai.namaPegawai
                    FROM
                        budgeting_project_tim
                        INNER JOIN pegawai ON budgeting_project_tim.kodePegawai = pegawai.kodePegawai
                    WHERE
                        budgeting_project_tim.kodeBudgetingProject = ? AND budgeting_project_tim.statusBudgetingProjectTim = ?
                    ",
                    [$kodeBudgetingProject, "Aktif"]
                );
                if (count($dataTim) > 0) {
                ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center align-middle" style="width: 5%;">NO</th>
                                <th class="text-center align-middle" style="width: 10%;">AKSI</th>
                                <th class="text-center align-middle" style="width: 45%;">NAMA</th>
                                <th class="text-center align-middle" style="width: 40%;">JABATAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = 1;
                            foreach ($dataTim as $row) {

                            ?>
                                <tr>
                                    <td class="text-center align-middle"><?= $n ?></td>
                                    <td class="text-center">
                                        <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-cogs"></i>
                                        </button>
                                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                            <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="seksiFormTim('<?= $row['idBudgetingProjectTim'] ?>')">
                                                <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deleteTim('<?= $row['idBudgetingProjectTim'] ?>', '<?= $tokenCSRF ?>')">
                                                <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block font-weight-bold"><?= $row['namaPegawai'] ?></span>
                                        <span class="text-muted font-weight-bold">Item</span>
                                    </td>
                                    <td>
                                        <span class="d-block font-weight-bold"><?= $row['jabatan'] ?></span>
                                        <span class="text-muted font-weight-bold">Jabatan Project</span>
                                    </td>
                                </tr>
                            <?php
                                $n++;
                            }
                            ?>

                        </tbody>
                    </table>
            <?php
                }
            } ?>
            </div>
        </div>
    </div>