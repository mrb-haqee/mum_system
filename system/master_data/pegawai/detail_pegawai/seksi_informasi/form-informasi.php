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

    $folder = 'pegawai';

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pegawai WHERE kodePegawai = ?',
        [$kodePegawai]
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Pegawai
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
            <form id="formPegawai">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodePegawai" value="<?= $kodePegawai ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">

                <div class="col-sm-12">
                    <div class="form-row">
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-user"></i> Nama Pegawai</label>
                            <input type="text" class="form-control" name="namaPegawai" value="<?= $dataUpdate['namaPegawai'] ?? '' ?>" placeholder="Input Nama Pegawai">
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-venus-mars"></i> Jenis Kelamin</label><br>
                            <select class="form-control selectpicker" id="jenisKelamin" name="jenisKelamin" style="width: 100%;">
                                <option value="">Pilih Jenis Kelamin</option>
                                <?php
                                $arrayJenisKelamin = array('Laki-laki', 'Perempuan');
                                for ($i = 0; $i < count($arrayJenisKelamin); $i++) {
                                    $selected = selected($arrayJenisKelamin[$i], $dataUpdate['jenisKelamin']);
                                ?>
                                    <option value="<?= $arrayJenisKelamin[$i] ?>" <?= $selected ?>>
                                        <?= $arrayJenisKelamin[$i] ?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-file"></i> NIK Pegawai</label>
                            <input type="text" class="form-control" name="NIKPegawai" value="<?= $dataUpdate['NIKPegawai'] ?? '' ?>" placeholder="Input NIK Pegawai">
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-money-check"></i> No Rekening Pegawai</label>
                            <input type="text" class="form-control" name="noRekening" value="<?= $dataUpdate['noRekening'] ?? '' ?>" placeholder="Input No Rekening Pegawai">
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-file"></i> NPWP Pegawai</label>
                            <input type="text" class="form-control" name="npwp" value="<?= $dataUpdate['npwp'] ?? '' ?>" placeholder="Input NPWP Pegawai">
                        </div>

                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-id-card"></i> Tempat Lahir</label>
                            <input type="text" class="form-control" name="tempatLahir" value="<?= $dataUpdate['tempatLahir'] ?? '' ?>" placeholder="Tempat Lahir Pegawai">
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-calendar"></i> Tanggal Lahir</label>
                            <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                <input type="text" class="form-control" id="ttlPegawai" name="ttlPegawai" placeholder="Klik untuk memilih tanggal" value="<?= $dataUpdate['ttlPegawai'] ?? '' ?>" autocomplete="off">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fas fa-user-tie"></i> Jabatan</label><br>
                            <select class="form-control selectpicker" id="idDepartemenPegawai" name="idDepartemenPegawai" data-live-search="true" onchange="selectPersentase()">
                                <option value="">Pilih Jabatan</option>
                                <?php
                                $dataDepartemen = statementWrapper(
                                    DML_SELECT_ALL,
                                    "SELECT * FROM departemen_pegawai WHERE statusDepartemen = ?",
                                    ['Aktif']
                                );

                                foreach ($dataDepartemen as $row) {
                                    $selected = selected($row['idDepartemenPegawai'], $dataUpdate['idDepartemenPegawai']);
                                ?>
                                    <option value="<?= $row['idDepartemenPegawai'] ?>" <?= $selected ?>>
                                        <?= $row['namaDepartemenPegawai'] ?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-phone"></i> No Telp Pegawai</label>
                            <input type="text" class="form-control" name="hpPegawai" value="<?= $dataUpdate['hpPegawai'] ?? '' ?>" placeholder="Input Telp Pegawai">
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-school"></i> Pendidikan Terakhir</label>
                            <input type="text" class="form-control" name="pendidikan" value="<?= $dataUpdate['pendidikan'] ?? '' ?>" placeholder="Pendidikan Terakhir Pegawai">
                        </div>
                        <div class="form-group col-sm-6">
                            <label><i class="fa fa-pray"></i> Agama</label><br>
                            <select class="form-control selectpicker" id="agama" name="agama" style="width: 100%;">
                                <option value="">Pilih Agama</option>
                                <?php
                                $arrayAgama = array('Islam', 'Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu', 'Lain-lain');
                                for ($i = 0; $i < count($arrayAgama); $i++) {
                                    $selected = selected($arrayAgama[$i], $dataUpdate['agama']);
                                ?>
                                    <option value="<?= $arrayAgama[$i] ?>" <?= $selected ?>>
                                        <?= $arrayAgama[$i] ?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label><i class="fa fa-calendar"></i> Tanggal Mulai Kerja </label>
                            <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                <input type="text" class="form-control" id="tglMulaiKerja" name="tglMulaiKerja" placeholder="Click to select a date!" value="<?= $dataUpdate['tglMulaiKerja'] ?? date('Y-m-d') ?>" autocomplete="off">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-sm-12">
                            <label><i class="fa fa-envelope"></i> Email</label>
                            <input type="text" class="form-control" id="email" name="email" placeholder="Email" value="<?= $dataUpdate['email'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-id-card"></i> Alamat</label>
                        <textarea class="form-control" name="alamatPegawai" placeholder="Input Alamat Pegawai"><?= $dataUpdate['alamatPegawai'] ?? '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-id-card"></i> Keterangan </label>
                        <textarea class="form-control" name="keterangan" placeholder="Input Keterangan"><?= $dataUpdate['keterangan'] ?? '' ?></textarea>
                    </div>
                    <!-- <div class="form-group">
                        <label for="fileTTDPegawai"><i class="fas fa-file-signature"></i> Tanda Tangan</label>
                        <div style="width: 300px; height:150px; margin-bottom: 1rem" class="bg-light rounded">
                            <canvas id="sign-pad-pegawai" <?= $attrFile; ?>></canvas>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger" onclick="clearPad($(this))">
                                <i class="fas fa-unlink pr-4"></i><strong>RESET</strong>
                            </button>
                            <button type="button" class="btn btn-success" onclick="savePad($(this))">
                                <i class="fas fa-upload pr-4"></i><strong>UPLOAD</strong>
                            </button>
                        </div>
                    </div> -->
                    <div class="form-group text-right">
                        <button type="button" class="btn btn-primary" onclick="prosesPegawai()">
                            <i class="fa fa-save pr-4"></i> <strong>SAVE</strong>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
<?php
}
?>