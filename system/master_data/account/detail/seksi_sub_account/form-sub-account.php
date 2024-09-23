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
        'SELECT * FROM 
                sub_account
            WHERE 
                idSubAccount = ?',
        [$idSubAccount]
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Sub Account
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

            <form id="formSubAccount">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeAccount" value="<?= $kodeAccount ?>">
                <input type="hidden" name="idSubAccount" value="<?= $idSubAccount ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-file"></i> Kode Sub Account </label>
                        <input type="text" class="form-control" id="kodeSub" name="kodeSub" placeholder="Kode Account" value="<?= $dataUpdate['kodeSub'] ?? "" ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-align-center"></i> Nama Account </label>
                        <input type="text" class="form-control" id="namaAccount" name="namaSubAccount" placeholder="Nama Account" value="<?= $dataUpdate['namaSubAccount'] ?? "" ?>">
                    </div>

                </div>


                <div class="form-group">
                    <?php
                    if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesSubAccount()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-info" onclick="prosesSubAccount()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    }
                    ?>
                </div>

            </form>

            <table class="table table-hover">
                <thead class="alert alert-danger">
                    <tr>
                        <th style="width: 5%;">NO</th>
                        <th style="width: 15%;">AKSI</th>
                        <th style="width: 20%;">KODE ACCOUNT</th>
                        <th style="width: 60%;">NAMA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $n = 1;
                    $listSubAccount = statementWrapper(
                        DML_SELECT_ALL,
                        'SELECT * FROM 
                            sub_account
                        WHERE 
                            kodeAccount = ? AND statusSubAccount = ?',
                        [$kodeAccount, 'Aktif']
                    );
                    if ($listSubAccount) {
                        foreach ($listSubAccount as $row) { ?>

                            <tr>
                                <td><?= $n ?></td>
                                <td>
                                    <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu menu-aksi tombol-dropdown" aria-labelledby="dropdownMenuButton">
                                        <!-- <a href="detail/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown"> -->
                                        <button class="btn btn-warning btn-sm tombol-dropdown" onclick="seksiFormSubAccount('<?= $row['idSubAccount'] ?>')">
                                            <i class="fa fa-edit"></i> <strong>EDIT</strong>
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiHapusSubAccount('<?= $row['idSubAccount'] ?>', '<?= $tokenCSRF ?>')">
                                            <i class="fa fa-trash"></i> <strong>HAPUS</strong>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-weight-bold"><?= $row['kodeSub'] ?></span>
                                    <span class="text-muted font-weight-bold">Kode</span>
                                </td>
                                <td>
                                    <span class="d-block font-weight-bold"><?= $row['namaSubAccount'] ?></span>
                                    <span class="text-muted font-weight-bold">Nama</span>
                                </td>

                            </tr>
                        <?php $n++;
                        }
                    } else {
                        ?>
                        <!-- <tr>
                            <td colspan="4" class="text-center">No Data</td>
                        </tr> -->
                    <?php
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>