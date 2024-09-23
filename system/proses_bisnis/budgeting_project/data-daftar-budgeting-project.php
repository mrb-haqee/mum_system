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

    $parameter = [];
    $execute = ['Aktif'];

    if ($flag === 'daftar') {
        $parameter['nama'] = '';
    } else if ($flag === 'cari') {
        $parameter['nama'] = 'AND atasNama = ?';
        $execute[] = "%$search%";
    }

    $dataBudgetingProject = statementWrapper(
        DML_SELECT_ALL,
        "SELECT data1.*, data2.nominal, (
        data2.nominal - data3.totalBiaya
    ) as sisa_anggaran, data4.progres, data4.fileName, data4.folder, data4.kodeBudgetingProjectProgres
        FROM (
                SELECT bp.*, vendor.nama
                FROM
                    budgeting_project as bp
                    INNER JOIN vendor ON bp.kodeVendor = vendor.kodeVendor
                WHERE
                    bp.statusBudgetingProject = ?
            ) as data1
            left JOIN (
                SELECT bpa.kodeBudgetingProject, bpa.nominal
                FROM
                    budgeting_project_anggaran as bpa
            ) as data2 ON data1.kodeBudgetingProject = data2.kodeBudgetingProject
            LEFT JOIN (
                SELECT bpb.kodeBudgetingProject, SUM(bpb.subTotal) as totalBiaya
                FROM
                    budgeting_project_biaya as bpb 
            ) as data3 ON data1.kodeBudgetingProject = data3.kodeBudgetingProject
            LEFT JOIN (
                SELECT bpp1.*, uploaded_file.fileName, uploaded_file.folder
                FROM
                    budgeting_project_progres as bpp1
                    INNER JOIN (
                        SELECT
                            MAX(budgeting_project_progres.idBudgetingProjectProgres) as idMax
                        FROM
                            budgeting_project_progres
                        GROUP BY
                            budgeting_project_progres.kodeBudgetingProject
                    ) as bpp2 ON bpp1.idBudgetingProjectProgres=bpp2.idMax
                    INNER JOIN uploaded_file ON bpp1.kodeBudgetingProjectProgres=uploaded_file.noForm
            ) as data4 ON data1.kodeBudgetingProject = data4.kodeBudgetingProject",
        $execute
    );

    $collapseTargetID = '';

?>
    <?php foreach ($dataBudgetingProject as $index => $row) :
        $query = encryptURLParam([
            'kode' => $row['kodeBudgetingProject'],
            'kodeProgres' => $row['kodeBudgetingProjectProgres']
        ]);

        $result = decryptURLParam($query);
        // print_r($result);

        $collapseTarget = 'budgeting_' . ($index + 1);

        $collapseShow = '';

        if ($collapseTargetID === $collapseTarget) {
            $collapseShow = 'show';
        }

    ?>
        <div class="accordion accordion-solid accordion-toggle-plus mb-5" id="pegawaiAccordion">
            <div class="card card-custom gutter-b">
                <div class="card-body">
                    <div class="d-flex">
                        <!--begin: Pic-->
                        <div class="flex-shrink-0 mr-7 mt-lg-0 mt-3 d-flex justify-content-center align-items-center">
                            <div class="symbol symbol-50 symbol-lg-120">
                                <img
                                    alt="Pic"
                                    src="<?= ($row['fileName']) ? REL_PATH_FILE_UPLOAD_DIR . '/' . $row['folder'] . '/' . $row['fileName'] : BASE_URL_HTML . '/assets/media/misc/default-kontraktor.png'; ?>"
                                    class="rounded mx-auto d-block"
                                    style="width: 100%;">
                            </div>
                        </div>
                        <!--end: Pic-->

                        <!--begin: Info-->
                        <div class="flex-grow-1">
                            <!--begin: Title-->
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div class="mr-3">
                                    <!--begin::Name-->
                                    <a href="#" class="d-flex align-items-center text-dark text-hover-primary font-size-h5 font-weight-bold mr-3">
                                        <?= $row['nama'] ?> - <?= $row['namaBudgetingProject'] ?> <i class="flaticon2-correct text-success icon-md ml-2"></i>
                                    </a>
                                    <!--end::Name-->

                                    <!--begin::Contacts-->
                                    <div class="d-flex flex-wrap my-2">
                                        <span href="#" class="text-muted font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                            <i class="fas fa-money-bill-wave mr-1"></i> <?= ubahToRupiahDesimal($row['nominal'])  ?>
                                        </span>
                                        <span href="#" class="text-muted font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                            <i class="fas fa-user-tie mr-1"></i> <?= $row['namaPIC'] ?>
                                        </span>
                                        <span href="#" class="text-muted font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                            <i class="fas fa-phone-alt mr-1"></i> <?= $row['noTelpPIC'] ?>
                                        </span>

                                    </div>
                                    <!--end::Contacts-->
                                </div>
                                <div class="my-lg-0 my-1">
                                    <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                        <a href="detail/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown">
                                            <i class="fa fa-edit"></i> <strong>EDIT</strong>
                                        </a>

                                        <a href="detail_progres/?param=<?= $query ?>" class="btn btn-info btn-sm tombol-dropdown">
                                            <i class="fas fa-tasks"></i> <strong>PROGRESS</strong>
                                        </a>

                                        <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiBatalBudgetingProject('<?= $row['idBudgetingProject'] ?>', '<?= $tokenCSRF ?>')">
                                            <i class="fa fa-trash"></i> <strong>HAPUS</strong>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!--end: Title-->

                            <!--begin: Content-->
                            <div class="d-flex align-items-center flex-wrap justify-content-between">
                                <div class="flex-grow-1 font-weight-bold text-dark-50 py-5 py-lg-2 mr-5">
                                    <?= $row['keterangan'] ?>
                                </div>

                                <div class="d-flex flex-wrap align-items-center py-2">
                                    <div class="d-flex align-items-center mr-10">
                                        <div class="mr-6">
                                            <div class="font-weight-bold mb-2">Kontrak Awal</div>
                                            <span class="btn btn-sm btn-text btn-light-primary text-uppercase font-weight-bold"><?= ubahTanggalIndo($row['tanggalAwalKontrak']) ?></span>
                                        </div>
                                        <div class="mr-6">
                                            <div class="font-weight-bold mb-2">Kontrak Akhir</div>
                                            <span class="btn btn-sm btn-text btn-light-danger text-uppercase font-weight-bold"><?= ubahTanggalIndo($row['tanggalAkhirKontrak']) ?></span>
                                        </div>
                                        <div class="">
                                            <div class="font-weight-bold mb-2">Sisa Anggaran</div>
                                            <span class="btn btn-sm btn-text btn-light-success
                                        font-weight-bold">Rp. <?= ubahToRp($row['sisa_anggaran']) ?? ubahToRupiahDesimal($row['nominal']) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 flex-shrink-0 w-150px w-xl-300px mt-4 mt-sm-0">
                                        <span class="font-weight-bold">Progress</span>
                                        <div class="progress progress-xs mt-2 mb-2">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $row['progres'] ?? 0 ?>%;" aria-valuenow="<?= $row['progres'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="font-weight-bolder text-dark"><?= $row['progres'] ?? 0 ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <!--end: Content-->
                        </div>
                        <!--end: Info-->
                    </div>

                    <div class="separator separator-solid my-7"></div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title collapsed" data-toggle="collapse" data-target="#<?= $collapseTarget ?>" onclick="getDetailBudgeting('<?= $row['idBudgetingProject'] ?>', '#<?= $collapseTarget ?>')">
                                <i class="fas fa-stream" style="font-size: 1rem;"></i> <strong>DETAIL BUDGETING</strong>
                            </div>
                        </div>
                        <div id="<?= $collapseTarget ?>" class="collapse <?= $collapseShow ?> mt-5" data-parent="">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-grow" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach;
} ?>