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

    $folderUploadFile = 'cabang';
    $fileProses = base64_encode('seksi_informasi/proses-cabang');

    extract($_POST, EXTR_SKIP);

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * from cabang where kodeCabang=?',
        [$kodeCabang]
    );

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

    $dataCabangFile = statementWrapper(
        DML_SELECT_ALL,
        'SELECT * FROM uploaded_file WHERE noForm = ? AND folder = ? ',
        [
            $kodeCabang,
            $folderUploadFile
        ]
    );

    // MENGAMBIL DATA FILE BERDASARKAN "name" HTML
    [
        'file' => [
            'imgKopCabang' => $imgKopCabang,
            'imgFooterCabang' => $imgFooterCabang,
        ],
        'button' => [
            'imgKopCabang' => $buttonImgKopCabang,
            'imgFooterCabang' => $buttonImgFooterCabang
        ],
    ] = getFileData(
        $dataCabangFile,
        [
            'imgKopCabang',
            'imgFooterCabang',
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
                        <i class="fa fa-info-circle text-dark"></i> Informasi Cabang
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

            <form id="formCabang">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeCabang" value="<?= $kodeCabang ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label><i class="fas fa-align-center"></i> Nama Cabang</label>
                        <input type="text" class="form-control" id="nama" name="nama" placeholder="Cabang" value="<?= $dataUpdate['nama'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-envelope"></i> Badan Usaha </label>
                        <input type="text" class="form-control" id="badanUsaha" name="badanUsaha" placeholder="Badan Usaha" value="<?= $dataUpdate['badanUsaha'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-phone-alt"></i> Telepon</label>
                        <input type="text" class="form-control" id="noTelp" name="noTelp" placeholder="No Telp" value="<?= $dataUpdate['noTelp'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-phone-alt"></i> Hotline</label>
                        <input type="text" class="form-control" id="hotline" name="hotline" placeholder="Hotline" value="<?= $dataUpdate['hotline'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="Email" value="<?= $dataUpdate['email'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-envelope"></i> Website </label>
                        <input type="text" class="form-control" id="website" name="website" placeholder="Website" value="<?= $dataUpdate['website'] ?? '' ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label><i class="fas fa-envelope"></i> Facebook</label>
                        <input type="text" class="form-control" id="facebook" name="facebook" placeholder="Facebook" value="<?= $dataUpdate['facebook'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label><i class="fas fa-map-marked-alt"></i> Alamat</label>
                        <input type="text" class="form-control" id="alamat" name="alamat" placeholder="Alamat" value="<?= $dataUpdate['alamat'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label><i class="fas fa-align-center"></i> Informasi</label>
                        <textarea name="informasi" class="form-control" rows="4"><?= $dataUpdate['informasi'] ?? '' ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-6 form-group" id="boxInputImgKopCabang">
                        <label for="imgKopCabang"><i class="fas fa-images"></i> Kop Print</label>
                        <div class="row">
                            <div class="col-md-12 mb-2" data-box="imgKopCabang">
                                <input type="file" class="dropify" name="imgKopCabang" data-default-file="<?= count($imgKopCabang) !== 0 ? base64_encode("{$constant('REL_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$imgKopCabang['folder']}{$constant('DIRECTORY_SEPARATOR')}{$imgKopCabang['fileName']}") : '' ?>" data-height="275" data-allowed-file-extensions="jpg jpeg png" data-max-file-size="1M" tabindex="-1" />
                            </div>
                            <div class="col-md-12">
                                <div class="btn-group" data-name="imgKopCabang" role="group">
                                    <button type="button" class="<?= $buttonImgKopCabang ?>" onclick="prosesFile($(this),'imgKopCabang', 'uploadFile')" data-proses="<?= $fileProses ?>" data-no-form="<?= $kodeCabang ?>" data-kode="<?= count($imgKopCabang) !== 0 ? $imgKopCabang['kodeFile']  : '' ?>" data-folder="<?= $folderUploadFile ?>" title="Finalisasi File" tabindex="-1">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-preview" data-kode="<?= count($imgKopCabang) !== 0 ? $imgKopCabang['kodeFile']  : '' ?>" data-name="imgKopCabang" title="Preview File" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="prosesFile($(this),'imgKopCabang', 'deleteFile')" data-proses="<?= $fileProses ?>" data-kode="<?= count($imgKopCabang) !== 0 ? $imgKopCabang['kodeFile']  : '' ?>" title="Delete File" tabindex="-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 form-group" id="boxInputImgFooterCabang">
                        <label for="imgFooterCabang"><i class="fas fa-images"></i> Footer Print</label>
                        <div class="row">
                            <div class="col-md-12 mb-2" data-box="imgFooterCabang">
                                <input type="file" class="dropify" name="imgFooterCabang" data-default-file="<?= count($imgFooterCabang) !== 0 ? base64_encode("{$constant('REL_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$imgFooterCabang['folder']}{$constant('DIRECTORY_SEPARATOR')}{$imgFooterCabang['fileName']}") : '' ?>" data-height="275" data-allowed-file-extensions="jpg jpeg png" data-max-file-size="1M" tabindex="-1" />
                            </div>
                            <div class="col-md-12">
                                <div class="btn-group" data-name="imgFooterCabang" role="group">
                                    <button type="button" class="<?= $buttonImgFooterCabang ?>" onclick="prosesFile($(this),'imgFooterCabang', 'uploadFile')" data-proses="<?= $fileProses ?>" data-no-form="<?= $kodeCabang ?>" data-kode="<?= count($imgFooterCabang) !== 0 ? $imgFooterCabang['kodeFile']  : '' ?>" data-folder="<?= $folderUploadFile ?>" title="Finalisasi File" tabindex="-1">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-preview" data-kode="<?= count($imgFooterCabang) !== 0 ? $imgFooterCabang['kodeFile']  : '' ?>" data-name="imgFooterCabang" title="Preview File" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="prosesFile($(this),'imgFooterCabang', 'deleteFile')" data-proses="<?= $fileProses ?>" data-kode="<?= count($imgFooterCabang) !== 0 ? $imgFooterCabang['kodeFile']  : '' ?>" title="Delete File" tabindex="-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesCabang()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-info" onclick="prosesCabang()">
                            <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
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