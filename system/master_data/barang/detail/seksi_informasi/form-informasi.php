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
include_once "{$constant('BASE_URL_PHP')}/library/fungsimum.php";

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
        'SELECT 
            *
        FROM 
            barang
        WHERE 
            kodeBarang = ?
        ',
        [$kodeBarang]
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Barang
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
            <form id="formBarang">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeBarang" value="<?= $kodeBarang ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><i class="fa fa-list"></i> Nama Barang</label>
                        <input type="text" class="form-control" id="namaBarang" name="namaBarang" placeholder="Nama Barang" value="<?= $dataUpdate['namaBarang'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-id-badge"></i> Kode Barang</label>
                        <input type="text" class="form-control" value="<?= $kodeBarang ?>" disabled>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label><i class="fa fa-list"></i> Golongan</label>
                        <input type="text" class="form-control" id="jenisBarang" name="jenisBarang" placeholder="Jenis Barang" value="<?= $dataUpdate['jenisBarang'] ?? '' ?>">
                    </div>
                    <div class="form-group col-sm-6">
                        <label><i class="fas fa-thumbtack"></i> Satuan</label>
                        <select id="satuanBarang" name="satuanBarang" class="form-control select2" style="width: 100%;">
                            <option value="">Pilih Satuan Barang</option>
                            <?php
                            foreach (satuanBarang() as $row) {
                                $selected = selected($row, $dataUpdate['satuanBarang'] ?? '');
                            ?>
                                <option value="<?= $row ?>" <?= $selected ?>><?= $row ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-list"></i> HARGA SATUAN </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="text" name="hargaBarang" id="hargaBarang" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['hargaBarang'] ?? 0) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-info" onclick="prosesBarang()">
                            <i class="fas fa-save pr-3"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-primary" onclick="prosesBarang()">
                            <i class="fas fa-save pr-3"></i> <strong>SIMPAN</strong>
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