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

include_once "{$constant('BASE_URL_PHP')}{$constant('VENDOR_SATU_SEHAT_DIR')}/load.php";

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


    $dataPasien = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien WHERE kodeRM = ?',
        [$kodeRM]
    );

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT
            pasien.*,
            pasien_satusehat.*
        FROM
            pasien
            LEFT JOIN pasien_satusehat ON pasien.kodeRM = pasien_satusehat.kodeRM
        WHERE
            pasien.kodeRM = ?',
        [$kodeRM]
    );

    $flag = 'update';
    $noID = $dataUpdate['noIdentitas'];

?>

    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Informasi Pasien
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
            <form id="formUpdateData">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        <label><i class="fa fa-id-badge"></i> Kode RM</label>
                        <input type="text" class="form-control" id="kodeRM" name="kodeRM" value="<?= $kodeRM ?>" readonly>
                    </div>
                    <div class="form-group col-sm-6">
                        <label><i class="fa fa-fingerprint"></i> No. ID <sup>( Wajib Satu Sehat )</sup></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="noIdentitas" name="noIdentitas" value="<?= $noID ?>" placeholder="Nomor Identitas">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-info-circle" id="icon-notif"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fa fa-user"></i> Nama Pasien</label>
                    <input type="text" class="form-control" id="namaPasien" value="<?= $dataUpdate['namaPasien'] ?>" name="namaPasien" placeholder="Nama Pasien">
                </div>

                <div class="form-row">
                    <div class="form-group col-sm-6">
                        <label><i class="fa fa-calendar"></i> Tgl Lahir </label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" class="form-control" id="tanggalLahir" value="<?= $dataUpdate['tanggalLahir'] ?>" name="tanggalLahir" placeholder="Tanggal Lahir" value="" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-6">
                        <label><i class="fa fa-city"></i> Tempat Lahir </label>
                        <input type="text" class="form-control" value="<?= $dataUpdate['tempatLahir'] ?>" name="tempatLahir" placeholder="Tempat Lahir">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-3">
                        <label><i class="fa fa-child"></i>Jumlah Saudara Kembar <sup>( Wajib Satu Sehat )</sup></label>
                        <input type="text" class="form-control" value="<?= $dataUpdate['multipleBirthInteger'] ?>" name="multipleBirthInteger" placeholder="Jumlah Saudara Kembar (Isi 0 bila tidak memiliki saudara kembar )" value="0" data-format-rupiah="active">
                    </div>
                    <div class="form-group col-sm-3">
                        <label><i class="fa fa-address-book"></i> Alamat ID </label>
                        <input type="text" class="form-control" value="<?= $dataUpdate['alamat'] ?>" name="alamat" placeholder="Alamat ID">
                    </div>
                    <div class="form-group col-sm-3">
                        <label><i class="fa fa-address-book"></i> Alamat Domisili <sup>( Wajib Satu Sehat )</sup></label>
                        <input type="text" class="form-control" value="<?= $dataUpdate['alamatDomisili'] ?>" name="alamatDomisili" placeholder="Alamat Domisili">
                    </div>
                    <div class="form-group col-sm-3">
                        <label><i class="fa fa-mail-bulk"></i>Kode Pos <sup>( Wajib Satu Sehat )</sup></label>
                        <input type="text" class="form-control" value="<?= $dataUpdate['postalCode'] ?>" name="postalCode" placeholder="Kode Pos">
                    </div>
                </div>
                <div class="form-row" id="boxWilayah">

                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        <label><i class="fa fa-phone"></i> No Telp <sup>( Wajib Satu Sehat )</sup></label>
                        <input type="text" class="form-control" value="<?= $dataUpdate['noTelp'] ?>" name="noTelp" placeholder="No Telp">
                    </div>
                    <div class="form-group col-sm-6">
                        <label><i class="fa fa-envelope"></i> Email <sup>( Wajib Satu Sehat )</sup></label>
                        <input type="text" class="form-control" id="email" value="<?= $dataUpdate['email'] ?>" name="email" placeholder="Email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-sm-3">
                        <label><i class="fa fa-venus-mars"></i> Kelamin </label>
                        <select id="jenisKelamin" name="jenisKelamin" class="form-control selectpicker" data-live-search="true">
                            <?php
                            $genders = ['Laki-laki', 'Perempuan'];
                            foreach ($genders as $gender) {
                                $selected = selected($gender, $dataUpdate['jenisKelamin'] ?? '')
                            ?>
                                <option value="<?= $gender ?>" <?= $selected; ?>><?= ucfirst($gender) ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group col-sm-3">
                        <label><i class="fas fa-user-friends"></i> Status Pernikahan <sup>( Wajib Satu Sehat )</sup></label>
                        <select id="maritalStatus" name="maritalStatus" class="form-control selectpicker" data-live-search="true">
                            <option value="">Pilih Status Pernikahan</option>
                            <?php
                            $maritals = \SatuSehat\DataType\Other\Marital::optionsFromStatic();
                            foreach ($maritals as $marital) {
                                $invoke = \SatuSehat\DataType\Other\Marital::invokeIfExist($marital['name']);
                                $selected = selected($marital['name'], $dataUpdate['maritalStatus'])
                            ?>
                                <option value="<?= $marital['name'] ?>" <?= $selected; ?>><?= $invoke->display ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-3">
                        <label><i class="fas fa-flag"></i> Kebangsaan </label>
                        <select id="kebangsaan" name="kebangsaan" class="form-control select2" data-live-search="true" style="width: 100%;">
                            <?php
                            $nationalities = [
                                'Lokal',
                                'Domestik',
                                'KITAS',
                                'Internasional'
                            ];
                            foreach ($nationalities as $nationality) {
                                $selected = selected($nationality, $dataUpdate['kebangsaan'])
                            ?>
                                <option value="<?= $nationality ?>" <?= $selected; ?>><?= $nationality ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-3">
                        <label><i class="fa fa-flag"></i> Bahasa Komunikasi <sup>( Wajib Satu Sehat )</sup></label>
                        <select id="language" name="language" class="form-control selectpicker" data-live-search="true">
                            <?php
                            $languages = \SatuSehat\DataType\Other\Language::optionsFromStatic();
                            foreach ($languages as $language) {
                                $invoke = call_user_func(\SatuSehat\DataType\Other\Language::class . '::' . $language['name']);
                                $selected = selected($language['name'], $dataUpdate['language'])
                            ?>
                                <option value="<?= $language['name'] ?>" <?= $selected; ?>><?= $invoke->display ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <?php
                    if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesInformasi()">
                            <i class="fas fa-save pr-4"></i> <strong>UPDATE</strong>
                        </button>
                    <?php
                    } else if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-primary" onclick="prosesInformasi()">
                            <i class="fas fa-clipboard-check pr-4"></i> <strong>DAFTAR</strong>
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