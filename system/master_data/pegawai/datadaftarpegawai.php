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

    if ($flagData == 'daftar') {
        $parameter['nama'] = '';
    } else if ($flag === 'search') {
        $parameter['nama'] = 'AND pegawai.namaPegawai LIKE ?';
        $execute[] = "%$search%";
    }

    $collapseTargetID = '';

    $dataPegawai = statementWrapper(
        DML_SELECT_ALL,
        "SELECT 
            pegawai.*,
            departemen_pegawai.namaDepartemenPegawai 
        FROM 
            pegawai
            INNER JOIN departemen_pegawai ON departemen_pegawai.idDepartemenPegawai = pegawai.idDepartemenPegawai
        WHERE 
            pegawai.statusPegawai = ? 
            {$parameter['nama']}
            ORDER BY pegawai.NIKPegawai",
        $execute
    );
    $n = 1;

    foreach ($dataPegawai as $index => $row) {
        $query = rawurlencode(enkripsi(http_build_query([
            'kode' => $row['kodePegawai'],
        ]), secretKey()));
?>
        <div class="accordion accordion-solid accordion-toggle-plus mb-5" id="pegawaiAccordion">
            <?php

            $iconPegawai = BASE_URL_HTML . '/assets/media/svg/avatars/001-boy.svg';

            if ($row['jenisKelamin'] === 'Perempuan') {
                $iconPegawai = BASE_URL_HTML . '/assets/media/svg/avatars/018-girl-9.svg';
            }

            // ACCORDION TARGET
            $collapseTarget = 'pegawai_' . ($index + 1);

            $collapseShow = '';

            if ($collapseTargetID === $collapseTarget) {
                $collapseShow = 'show';
            }
            ?>
            <!-- CARD -->
            <div class="card card-custom gutter-b">
                <div class="card-body">
                    <!-- TOP -->
                    <div class="d-flex">
                        <!-- GENDER PIC -->
                        <div class="flex-shrink-0 mr-7">
                            <div class="symbol symbol-50 symbol-lg-120">
                                <img src="<?= $iconPegawai ?>">
                            </div>
                        </div>
                        <!-- END GENDER PIC -->
                        <!-- PATIENT INFO -->
                        <div class="flex-grow-1">
                            <!-- PATIENT PERSONAL INFO -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap mt-2">
                                <!-- PATIENT NAME & CONTACT -->
                                <div class="mr-3">
                                    <!-- PATIENT NAME -->
                                    <span class="d-flex align-items-center text-dark font-size-h5 font-weight-bold mr-3"><?= $row['namaPegawai'] ?> <i class="flaticon2-correct text-success icon-md ml-2"></i></span>
                                    <!-- END PATIENT NAME -->
                                    <!-- PATIENT CONTACT -->
                                    <div class="d-flex flex-wrap my-2">
                                        <span class="text-muted font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2"><i class="fas fa-id-card"></i> <?= $row['NIKPegawai'] ?></span>
                                        <span class="text-muted font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2"><i class="fas fa-birthday-cake"></i> <?= $row['tempatLahir'] ?>, <?= tanggalTerbilang($row['ttlPegawai']) ?></span>
                                        <a href="#" class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2"><i class="fas fa-address-book"></i> <?= $row['alamatPegawai'] ?></a>
                                    </div>
                                    <!-- END PATIENT CONTACT -->
                                </div>
                                <a class="btn btn-warning" href="detail_pegawai/?param=<?= $query ?>"><i class="fas fa-cog pr-0"></i></a>
                                <!-- END PATIENT NAME & CONTACT -->
                            </div>
                            <!-- END PATIENT PERSONAL INFO -->
                            <br>
                            <div>
                                <?php
                                if (is_null($row['namaDepartemenPegawai'])) {
                                ?>
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-user-tie pr-5"></i><strong class="text-uppercase">NOT SET</strong>
                                    </button>
                                <?php
                                } else {
                                ?>
                                    <button class="btn btn-outline-success" type="button">
                                        <i class="fas fa-user-tie pr-5"></i><strong class="text-uppercase"><?= $row['namaDepartemenPegawai']; ?></strong>
                                    </button>
                                <?php
                                }
                                ?>
                            </div>
                            <!-- END LATEST PRIMARY DIAGNOSIS -->
                        </div>
                        <!-- END PATIENT INFO -->
                    </div>
                    <!-- END TOP -->

                    <!-- SEPARATOR -->
                    <div class="separator separator-solid my-7"></div>
                    <!-- END SEPARATOR -->

                    <!-- BOTTOM -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title collapsed" data-toggle="collapse" data-target="#<?= $collapseTarget ?>" onclick="getDetailPegawai('<?= $row['idPegawai'] ?>', '#<?= $collapseTarget ?>')">
                                <i class="fas fa-stream" style="font-size: 1rem;"></i> <strong>DATA DIRI</strong>
                            </div>
                        </div>
                        <div id="<?= $collapseTarget ?>" class="collapse <?= $collapseShow ?> mt-5" data-parent="#pegawaiAccordion">

                        </div>
                    </div>
                    <!-- END BOTTOM -->
                </div>
            </div>
            <!-- END CARD -->
        </div>
<?php
        $n++;
    }
}
?>