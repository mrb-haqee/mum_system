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
        'SELECT * FROM tujuan_transfer_edc WHERE idTransferEDC=?',
        [$idTransferEDC]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

    $dataTujuanTransfer = statementWrapper(
        DML_SELECT,
        'SELECT * FROM tujuan_transfer WHERE kodeTujuanTransfer = ?',
        [$kodeTujuanTransfer]
    );


?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Mesin EDC
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

            <form id="formEDC">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeTujuanTransfer" value="<?= $kodeTujuanTransfer ?>">
                <input type="hidden" name="idTransferEDC" value="<?= $idTransferEDC ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60%;">MESIN</th>
                        <th class="text-center" style="width: 20%;">FEE (%)</th>
                        <th class="text-center" style="width: 20%;" colspan="2">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="text" class="form-control" id="namaMesin" name="namaMesin" placeholder="Nama Mesin" value="<?= $dataUpdate['namaMesin'] ?? '' ?>" form="formEDC">
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control" id="fee" name="fee" placeholder="Persentase Fee" value="<?= ubahToRupiahDesimal($dataUpdate['fee'] ?? 0) ?>" data-format-rupiah="active" form="formEDC">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center" colspan="2">
                            <?php
                            if ($flag === 'tambah') {
                            ?>
                                <button type="button" class="w-100 btn btn-success" onclick="prosesEDC()">
                                    <i class="fas fa-save pr-0"></i>
                                </button>
                            <?php
                            } else if ($flag === 'update') {
                            ?>
                                <button type="button" class="w-100 btn btn-info" onclick="prosesEDC()">
                                    <i class="fas fa-save pr-0"></i>
                                </button>
                            <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    $listMesin = statementWrapper(
                        DML_SELECT_ALL,
                        'SELECT * FROM tujuan_transfer_edc WHERE kodeTujuanTransfer = ? AND statusTransferEDC = ?',
                        [$kodeTujuanTransfer, 'Aktif']
                    );

                    foreach ($listMesin as $index => $mesin) {
                    ?>
                        <tr>
                            <td class="text-center">
                                <span class="d-block font-weight-bold"><?= $mesin['namaMesin'] ?></span>
                                <span class="text-muted font-weight-bold"><?= $dataTujuanTransfer['noReferensi']; ?> - <?= $dataTujuanTransfer['vendor']; ?> a.n. <?= $dataTujuanTransfer['atasNama']; ?></span>
                            </td>
                            <td class="align-middle text-center">
                                <strong><?= ubahToRupiahDesimal($mesin['fee']); ?> %</strong>
                            </td>
                            <td>
                                <button class="btn btn-sm w-100 btn-warning" type="button" onclick="seksiFormEDC('<?= $mesin['idTransferEDC'] ?>')"><i class="fas fa-edit pr-0"></i></button>
                            </td>
                            <td>
                                <button class="btn btn-sm w-100 btn-danger" type="button" onclick="deleteEDC('<?= $mesin['idTransferEDC'] ?>', '<?= $tokenCSRF ?>')"><i class="fas fa-trash pr-0"></i></button>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>

        </div>
    </div>
<?php
}
?>