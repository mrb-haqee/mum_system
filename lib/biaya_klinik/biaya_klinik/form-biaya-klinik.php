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
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";

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

    $folderUploadFile = 'biaya';
    $fileProses = base64_encode('proses-biaya-klinik');

    extract($_POST, EXTR_SKIP);

    $idKlinikAsal = dekripsi($_SESSION['enc_idKlinik'], secretKey());

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT 
            *
        FROM 
            biaya_klinik
        WHERE 
            idBiayaKlinik = ?',
        [$idBiayaKlinik]
    );

    if ($dataUpdate) {
        $flag = 'update';
        $kodeBiaya = $dataUpdate['kodeBiaya'];
    } else {
        $flag = 'tambah';
        $kodeBiaya = nomorUrut($db, 'biaya_klinik', $idUserAsli);
    }

    $dataBiayaFile = statementWrapper(
        DML_SELECT_ALL,
        'SELECT * FROM uploaded_file WHERE noForm = ? AND folder = ? ',
        [
            $kodeBiaya,
            $folderUploadFile
        ]
    );

    // MENGAMBIL DATA FILE BERDASARKAN "name" HTML
    [
        'file' => [
            'imgNota' => $imgNota,
        ],
        'view' => [
            'imgNota' => $viewImgNota
        ],
        'button' => [
            'imgNota' => $buttonImgNota,
        ],
    ] = getFileData(
        $dataBiayaFile,
        [
            'imgNota',
        ]
    );

?>
    <div class="btn badge-secondary col-12" style="text-align: left;">
        <label><i class="fas fa-list "></i> <strong> KOP BIAYA</strong> </label>
    </div>
    <hr>
    <form id="formBiayaKlinik">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idBiayaKlinik" value="<?= $idBiayaKlinik ?>">
        <input type="hidden" name="idKlinik" value="<?= $idKlinikAsal ?>">
        <input type="hidden" name="kodeBiaya" id="kodeBiaya" value="<?= $kodeBiaya ?>">
        <input type="hidden" name="flag" id="flag" value="<?= $flag ?>">
        <div class="form-row">
            <div class="col-md-6">
                <div class="form-group ">
                    <label><i class="fas fa-user-tie"></i> KLINIK </label><br>
                    <select class="form-control selectpicker" id="idKlinikAsal" name="idKlinikAsal" data-live-search="true">
                        <?php
                        $dataKlinik = statementWrapper(
                            DML_SELECT_ALL,
                            "SELECT * FROM klinik WHERE idKlinik = ?",
                            [$idKlinikAsal]
                        );

                        foreach ($dataKlinik as $row) {
                            $selected = selected($row['idKlinik'], $dataUpdate['idKlinikAsal']);
                        ?>
                            <option value="<?= $row['idKlinik'] ?>" <?= $selected ?>>
                                <?= $row['nama'] ?>
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group ">
                    <label for="tglBiaya"><i class="fas fa-calendar-alt"></i> TANGGAL</label>
                    <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                        <input type="text" class="form-control" id="tglBiaya" name="tglBiaya" placeholder="Click to select a date!" value="<?= $dataUpdate['tglBiaya'] ?? date('Y-m-d') ?>" autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fa fa-calendar"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group ">
                    <label><i class="fas fa-user-tie"></i> KODE AKUNTING </label><br>
                    <select class="form-control selectpicker" id="kodeAkun" name="kodeAkun" data-live-search="true">
                        <option value="">Pilih Kode Akunting</option>
                        <?php
                        $dataKode = statementWrapper(
                            DML_SELECT_ALL,
                            "SELECT * FROM kode_akunting WHERE statusAkun = ?",
                            ['Aktif']
                        );

                        foreach ($dataKode as $row) {
                            $selected = selected($row['kodeAkun'], $dataUpdate['kodeAkun']);
                        ?>
                            <option value="<?= $row['kodeAkun'] ?>" <?= $selected ?>>
                                <?= $row['namaAkun'] ?> (<?= $row['kodeAkun'] ?>)
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group ">
                    <label><i class="fas fa-list"></i> NOMOR NOTA </label>
                    <input type="text" class="form-control" id="nomorNota" name="nomorNota" placeholder="Nomor Nota" value="<?= $dataUpdate['nomorNota'] ?? '' ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group" id="boxInputNota">
                    <label for="imgNota"><i class="fas fa-images"></i> Foto Nota</label>
                    <div class="row">
                        <div class="col-md-12 mb-2" data-box="imgNota">
                            <input type="file" class="dropify" name="imgNota" data-default-file="<?= count($imgNota) !== 0 ? base64_encode("{$constant('REL_PATH_FILE_UPLOAD_DIR')}{$constant('DIRECTORY_SEPARATOR')}{$imgNota['folder']}{$constant('DIRECTORY_SEPARATOR')}{$imgNota['fileName']}") : '' ?>" data-height="275" data-allowed-file-extensions="jpg jpeg png pdf" accept=".jpg,.jpeg,.png,.pdf" data-max-file-size="1M" tabindex="-1" />
                        </div>
                        <div class="col-md-12">
                            <div class="btn-group" data-name="imgNota" role="group">
                                <button type="button" class="<?= $buttonImgNota ?>" onclick="prosesFile($(this),'imgNota', 'uploadFile')" data-proses="<?= $fileProses ?>" data-no-form="<?= $kodeBiaya ?>" data-kode="<?= count($imgNota) !== 0 ? $imgNota['kodeFile']  : '' ?>" data-folder="<?= $folderUploadFile ?>" title="Finalisasi File" tabindex="-1">
                                    <i class="fas fa-upload"></i>
                                </button>
                                <a href="<?= $viewImgNota ?>" class="btn btn-info btn-preview" data-kode="<?= count($imgNota) !== 0 ? $imgNota['kodeFile']  : '' ?>" data-name="imgNota" title="Preview File" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-danger" onclick="prosesFile($(this),'imgNota', 'deleteFile')" data-proses="<?= $fileProses ?>" data-kode="<?= count($imgNota) !== 0 ? $imgNota['kodeFile']  : '' ?>" title="Delete File" tabindex="-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </form>
    <div class="btn badge-secondary col-12" style="text-align: left;">
        <label><i class="fas fa-list "></i> <strong>DETAIL BIAYA</strong> </label>
    </div>
    <hr>
    <div id="boxFormBiayaDetail">
    </div>
    <div class="form-group text-right">
        <button type="button" class="btn btn-primary text-center" onclick="prosesBiaya()">
            <i class="fa fa-save"></i> <strong>FINALISASI</strong>
        </button>
    </div>
<?php
}
?>