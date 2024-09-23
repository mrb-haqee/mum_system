<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenisharga.php";
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

    $dataPenghasilan = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pegawai WHERE kodePegawai = ?',
        [$kodePegawai]
    );

?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-diagnoses text-dark"></i> Penghasilan Pegawai
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
            if ($dataPenghasilan) {

                $dataUpdate = statementWrapper(
                    DML_SELECT,
                    'SELECT * FROM pegawai_penghasilan WHERE idPenghasilanPegawai = ? ',
                    [$idPenghasilanPegawai]
                );

                if ($dataUpdate) {
                    $flag = 'update';
                } else {
                    $flag = 'tambah';
                }

                $daftarPenghasilan = statementWrapper(
                    DML_SELECT_ALL,
                    'SELECT * FROM pegawai_penghasilan WHERE kodePegawai = ?  AND status = ? ORDER BY jenisPenghasilan',
                    [$kodePegawai, 'Aktif']
                );
            ?>

                <div class="card card-custom mb-5" data-card="true">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="card-label"><i class="fas fa-money-bill pr-4"></i><strong class="text-uppercase">PENGHASILAN </strong></h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>
                    <div class="card-body">
                        <form id="formPenghasilan">
                            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                            <input type="hidden" name="kodePegawai" value="<?= $kodePegawai ?>">
                            <input type="hidden" name="idPenghasilanPegawai" value="<?= $idPenghasilanPegawai ?>">
                            <input type="hidden" name="flag" value="<?= $flag ?>">
                        </form>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>JENIS PENGHASILAN</th>
                                    <th>NAMA PENGHASILAN</th>
                                    <th>NOMINAL</th>
                                    <th>AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="jenisPenghasilan" id="jenisPenghasilan" form="formPenghasilan" class="selectpicker form-control">
                                            <option value="">Pilih Jenis Penghasilan</option>
                                            <?php
                                            $opsi = ['Gaji Pokok', 'Tunjangan', 'Potongan'];

                                            foreach ($opsi as $data) {
                                                $selected = selected($data, $dataUpdate['jenisPenghasilan'] ?? '');
                                            ?>
                                                <option value="<?= $data ?>" <?= $selected; ?>><?= $data; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                            </div>
                                            <input type="text" name="namaPenghasilan" id="namaPenghasilan" class="form-control" value="<?= $dataUpdate['namaPenghasilan'] ?? '' ?>" form="formPenghasilan">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="text" name="nominal" id="nominal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['nominal'] ?? 0) ?>" form="formPenghasilan">
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        if ($flag === 'tambah') {
                                        ?>
                                            <button type="button" class="btn btn-success w-100" onclick="prosesPenghasilan()">
                                                <strong>SIMPAN</strong>
                                            </button>

                                        <?php
                                        } else if ($flag === 'update') {
                                        ?>
                                            <button type="button" class="btn btn-info w-100" onclick="prosesPenghasilan()">
                                                <strong>SIMPAN</strong>
                                            </button>

                                        <?php

                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php


                                foreach ($daftarPenghasilan as $penghasilan) {
                                    if (intval($idPenghasilanPegawai) === intval($penghasilan['idPenghasilanPegawai'])) {
                                        $editColor = 'btn-info';
                                    } else {
                                        $editColor = 'btn-warning';
                                    }
                                ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong>
                                                <?= $penghasilan['jenisPenghasilan'] ?>
                                            </strong>
                                        </td>
                                        <td class="align-middle">
                                            <strong>
                                                <?= $penghasilan['namaPenghasilan'] ?>
                                            </strong>
                                        </td>
                                        <td class="align-middle">Rp <?= ubahToRp($penghasilan['nominal']) ?></td>
                                        <td class="align-middle">
                                            <button type="button" class="btn <?= $editColor ?> btn-sm" onclick="seksiFormPenghasilan('<?= $penghasilan['idPenghasilanPegawai'] ?>')">
                                                <i class="fa fa-edit pr-0"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="konfirmasiBatalPenghasilan('<?= $penghasilan['idPenghasilanPegawai'] ?>', '<?= $tokenCSRF ?>')">
                                                <i class="fa fa-trash pr-0"></i>
                                            </button>
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

                ?>
            <?php
            } else {
            ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>MOHON ISI BAGIAN INFORMASI TERLEBIH DAHULU</strong>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
<?php
}
?>