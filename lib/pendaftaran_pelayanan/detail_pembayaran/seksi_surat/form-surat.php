<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenissurat.php";
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
                            <i class="fa fa-diagnoses text-dark"></i> Surat Yang Diterbitkan
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
                if ($mode === 'input') {
                ?>
                    <form id="formSurat">
                        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                        <input type="hidden" name="kodeAntrian" value="<?= $kodeAntrian ?>">
                        <input type="hidden" name="kodeRM" value="<?= $kodeRM ?>">
                        <input type="hidden" name="flag" value="tambah">

                        <div class="form-row">
                            <div class="form-group col-sm-12">
                                <label><i class="fa fa-file-medical"></i> Jenis Surat</label>
                                <select name="jenisSurat" id="jenisSurat" class="form-control selectpicker" onchange="formDetailSurat()" data-live-search="true" style="width: 100%;">
                                    <?php
                                    $opsiSurat = getJenisSurat();

                                    foreach ($opsiSurat as $row) {
                                    ?>
                                        <option value="<?= $row ?>"> <?= $row ?> </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div id="formDetailSurat"></div>

                        <div class="form-group">
                            <button type="button" class="btn btn-danger" onclick="prosesSurat()">
                                <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                            </button>
                        </div>
                    </form>
                    <hr>
                <?php
                }
                ?>
                <div style="overflow-x: auto;">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>AKSI</th>
                                <th>NOMOR SURAT</th>
                                <th>JENIS SURAT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = $db->prepare('SELECT * FROM pasien_surat WHERE kodeAntrian = ?');
                            $sql->execute([
                                $kodeAntrian
                            ]);

                            $data = $sql->fetchAll();
                            $n = 1;
                            foreach ($data as $row) {
                            ?>
                                <tr>
                                    <td><?= $n ?></td>
                                    <td>
                                        <?php
                                        if ($mode === 'input') {
                                        ?>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="konfirmasiBatalSurat('<?= $row['idSurat'] ?>','<?= $tokenCSRF ?>')">
                                                <i class="fa fa-trash pr-0"></i>
                                            </button>
                                        <?php
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="d-block font-weight-bold"><?= $row['kodeSurat'] ?></span>
                                        <span class="text-muted font-weight-bold">Kode Surat</span>
                                    </td>
                                    <td>
                                        <span class="d-block font-weight-bold"><?= $row['jenisSurat'] ?></span>
                                        <span class="text-muted font-weight-bold">Jenis Surat</span>
                                    </td>
                                </tr>
                            <?php
                              $n++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
<?php
    }
}
?>