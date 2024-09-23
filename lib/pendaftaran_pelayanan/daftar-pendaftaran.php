<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";
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

    $kodeAntrian = nomorUrut($db, 'antrian_klinik', $idUserAsli);
    $dataAntrian = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_antrian WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    $rentang = date('Y-m-d - Y-m-d');
?>
    <div class="row">
        <div class="col-xl-2 mb-5">
            <div class="card card-custom">
                <div class="card-body">
                    <?php
                    $opsi = statementWrapper(
                        DML_SELECT_ALL,
                        "SELECT * FROM admisi WHERE statusAdmisi = ?",
                        ['Aktif']
                    );

                    foreach ($opsi as $index => $row) {

                        if (intval($row['idAdmisi']) === MCU_ID && $_SESSION['view_mcu'] === '__HIDE__') continue;

                        if ($index === 0) {
                            $classBtn = 'btn-primary';
                        } else {
                            $classBtn = 'btn-light-primary';
                        }

                    ?>
                        <button type="button" style="width: 100%; text-align: center;" class="btn <?= $classBtn ?> btn-admission-type-tab mb-2" onclick="dataPendaftaran(<?= $row['idAdmisi'] ?>)" data-id="<?= $row['idAdmisi'] ?>">
                            <i class="fas fa-user-md d-block mb-2 pr-0 fa-2x"></i> <strong class="text-uppercase"><?= $row['namaAdmisi']; ?></strong>
                        </button>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-xl-10">
            <!-- CARD -->
            <div id="kt_page_sticky_card" class="card card-custom card-sticky">
                <div class="card-header">
                    <!-- CARD TITLE -->
                    <div class="card-title">
                        <h3 class="card-label"><i class="fas fa-stream pr-5 text-dark"></i> <strong>HISTORY PENDAFTARAN</strong></h3>
                    </div>
                    <!-- END CARD TITLE -->
                    <div class="card-toolbar">
                        <?php
                        if ($dataAntrian) {
                            if ($dataAntrian['nomorAntrian'] === NULL && $dataAntrian['statusAntrian'] === 'Generated') {
                                $query = rawurlencode(enkripsi(http_build_query([
                                    'kodeAntrian' => $dataAntrian['kodeAntrian'],
                                    'kodeRM' => $dataAntrian['kodeRM'],
                                    'idAdmisi' => $dataAntrian['idAdmisi']
                                ]), secretKey()));
                        ?>
                                <a href="detail_pendaftaran/?param=<?= $query ?>" class="btn btn-warning"><i class="fas fa-file-signature pr-4"></i><strong>PENDAFTARAN BELUM TERFINALISASI</strong></a>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- CARD BODY -->
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="periodePendaftaran"><i class="fas fa-calendar-alt"></i> RENTANG</label>
                            <div class="input-group">
                                <input type="text" id="periodePendaftaran" class="form-control" data-date-range="true" onchange="dataPendaftaran()" value="<?= $rentang ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar-check-o"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="searchPasienDaftar"><i class="fas fa-user-md"></i> PASIEN</label>
                            <input type="text" id="searchPasienDaftar" class="form-control" placeholder="Cari Pasien..." value="<?= $search ?? '' ?>">
                        </div>
                    </div>
                    <div id="boxDataPendaftaran" style="overflow-x: auto;"></div>
                </div>
            </div>
        </div>
    </div>
<?php

}
?>