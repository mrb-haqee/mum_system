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
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";

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

    $folderUploadFile = 'progres_budgeting';
    $fileProses = base64_encode('seksi_informasi/proses-informasi');

    extract($_POST, EXTR_SKIP);

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * 
        FROM budgeting_project_progres 
        WHERE kodeBudgetingProject=? AND budgeting_project_progres.statusBudgetingProjectProgres = ?
        ORDER BY idBudgetingProjectProgres DESC',
        [$kodeBudgetingProject, 'Aktif']
    );

    $dataTimeline = statementWrapper(
        DML_SELECT_ALL,
        'SELECT budgeting_project.`namaBudgetingProject`, budgeting_project_progres.*, uploaded_file.folder, uploaded_file.`fileName`
        FROM
            budgeting_project
            INNER JOIN budgeting_project_progres ON budgeting_project.`kodeBudgetingProject` = budgeting_project_progres.`kodeBudgetingProject`
            INNER JOIN uploaded_file on uploaded_file.`noForm` = budgeting_project_progres.`kodeBudgetingProjectProgres`
        WHERE
            budgeting_project_progres.kodeBudgetingProject= ? AND budgeting_project_progres.statusBudgetingProjectProgres = ?
        ORDER BY budgeting_project_progres.idBudgetingProjectProgres DESC',
        [$kodeBudgetingProject, "Aktif"]
    );

    $flag = 'tambah';

    if ($kodeBudgetingProjectProgres) {
        $kodeBudgetingProjectProgres = nomorUrut($db, 'budgeting_project_progres', $idUserAsli);
    }


    $dataBudgetingProjectFile = statementWrapper(
        DML_SELECT_ALL,
        'SELECT * FROM uploaded_file WHERE noForm = ? AND folder = ? ',
        [
            $kodeBudgetingProjectProgres,
            $folderUploadFile
        ]
    );


    // MENGAMBIL DATA FILE BERDASARKAN "name" HTML
    [
        'file' => [
            'imgNota' => $imgNota,
        ],
        'view' => [
            'imgNota' => $viewImgNota
        ],
        'button' => [
            'imgNota' => $buttonImgNota,
        ],
    ] = getFileData(
        $dataBudgetingProjectFile,
        [
            'imgNota',
        ]
    );


?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Informasi Progress Budgeting
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
                <input type="hidden" name="idBudgetingProjectProgres" value="<?= $dataUpdate['idBudgetingProjectProgres'] ?? '' ?>">
                <input type="hidden" name="kodeBudgetingProject" value="<?= $kodeBudgetingProject ?>">
                <input type="hidden" name="kodeBudgetingProjectProgres" value="<?= $kodeBudgetingProjectProgres ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">

                <div class="form-row">
                    <div class="col-md-5 mr-10">
                        <div class="form-group">
                            <label for="tanggal"><i class="fas fa-calendar-alt"></i> Tanggal Update</label>
                            <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                <input type="text" class="form-control" id="tanggalUpdate" name="tanggalUpdate" placeholder="Click to select a date!" value="<?= date('Y-m-d') ?>" autocomplete="off">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fa fa-id-card"></i> Progres </label>
                            <div class="input-group">
                                <input type="number" min="0" max="100" class="form-control" id="progres" name="progres" placeholder="Progres" value="<?= $dataUpdate['progres'] ?? '0' ?>" onchange="updateProgressBar()">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="progress mt-2"> <!-- Menggunakan margin-top untuk memberi jarak antara input dan progress bar -->
                                <div id="progres-bar" class="progress-bar bg-success" role="progressbar" style="width: <?= $dataUpdate['progres'] ?? '0' ?>%; text-align: center; color: white;" aria-valuenow="<?= $dataUpdate['progres'] ?? '0' ?>" aria-valuemin="0" aria-valuemax="100"><?= $dataUpdate['progres'] ?? '0' ?>%</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fa fa-id-card"></i> Keterangan </label>
                            <textarea class="form-control" name="keterangan" placeholder="Input Keterangan" rows="6"></textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" id="boxInputNota">
                            <label for="imgNota"><i class="fas fa-images"></i> Foto Progres Project</label>
                            <div class="row">
                                <div class="col-md-12 mb-2" data-box="imgNota">
                                    <input type="file" class="dropify" name="imgNota" data-default-file="<?= count($imgNota) !== 0 ? base64_encode("{$constant('REL_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$imgNota['folder']}{$constant('DIRECTORY_SEPARATOR')}{$imgNota['fileName']}") : '' ?>" data-height="275" data-allowed-file-extensions="jpg jpeg png pdf" accept=".jpg,.jpeg,.png,.pdf" data-max-file-size="1M" tabindex="-1" />
                                </div>
                                <div class="col-md-12">
                                    <div class="btn-group" data-name="imgNota" role="group">
                                        <button type="button" class="<?= $buttonImgNota ?>" onclick="prosesFile($(this),'imgNota', 'uploadFile')" data-proses="<?= $fileProses ?>" data-no-form="<?= $kodeBudgetingProjectProgres ?>" data-kode="<?= count($imgNota) !== 0 ? $imgNota['kodeFile']  : '' ?>" data-folder="<?= $folderUploadFile ?>" title="Finalisasi File" tabindex="-1">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                        <a href="<?= $viewImgNota ?>" class="btn btn-info btn-preview" data-kode="<?= count($imgNota) !== 0 ? $imgNota['kodeFile']  : '' ?>" data-name="imgNota" title="Preview File" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" onclick="prosesFile($(this),'imgNota', 'deleteFile')" data-proses="<?= $fileProses ?>" data-kode="<?= count($imgNota) !== 0 ? $imgNota['kodeFile']  : '' ?>" title="Delete File" tabindex="-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="form-row">
                    <?php
                    if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesBudgetingProjectProgres()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-info" onclick="prosesBudgetingProjectProgres()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    }
                    ?>
                </div>

            </form>

            <div class="separator separator-dashed my-10"></div>

            <div class="timeline timeline-3 w-50">
                <div class="timeline-items">
                    <?php foreach ($dataTimeline as $row): ?>
                        <div class="timeline-item">
                            <div class="timeline-media">
                                <i class="flaticon2-fast-next text-primary"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex">
                                    <!--begin: Pic-->
                                    <div class="flex-shrink-0 mr-7 mt-lg-0 mt-3 d-flex justify-content-center align-items-center">
                                        <div class="symbol symbol-50 symbol-lg-120">
                                            <img
                                                alt="Pic"
                                                src="<?= ($row['fileName']) ? REL_PATH_FILE_UPLOAD_DIR . '/' . $row['folder'] . '/' . $row['fileName'] : BASE_URL_HTML . '/assets/media/misc/default-kontraktor.png'; ?>"
                                                class="rounded mx-auto d-block"
                                                style="width: 100%;">
                                            <p class="mt-3 text-center font-weight-bolder">progres <?= $row['progres'] ?>%</p>

                                        </div>
                                    </div>
                                    <!--end: Pic-->

                                    <!--begin: Info-->
                                    <div class="flex-grow-1">
                                        <!--begin: Title-->
                                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                                            <div class="mr-3">
                                                <!--begin::Name-->
                                                <a class="d-flex align-items-center text-dark text-hover-primary font-size-h5 font-weight-bold mr-3">
                                                    <?= $row['namaBudgetingProject'] ?> <span class="label label-light-success font-weight-bolder label-inline ml-2"><?= ubahTanggalIndo($row['tanggalUpdate']) ?></span>
                                                </a>

                                            </div>
                                        </div>
                                        <!--end: Title-->

                                        <!--begin: Content-->
                                        <div class="d-flex align-items-center flex-wrap justify-content-between">
                                            <div class="flex-grow-1 font-weight-bold text-dark-50 py-5 py-lg-2 mr-5">
                                                <?= $row['keterangan'] ?> 
                                            </div>

                                            <div class="d-flex flex-wrap align-items-center py-2">
                                                <div class="flex-grow-1 flex-shrink-0 w-150px w-xl-300px mt-4 mt-sm-0 text-right">

                                                    <button class="btn btn-danger btn-sm" onclick="konfirmasiBatalBudgetingProjectProgres(<?= $row['idBudgetingProjectProgres'] ?>, '<?= $tokenCSRF ?>')"> <i class="far fa-trash-alt text-sm"></i> Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end: Content-->
                                    </div>
                                    <!--end: Info-->
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>


<?php
}
?>