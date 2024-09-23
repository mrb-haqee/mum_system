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

    $kodeRM = nomorUrut($db, 'rekam_medis', $idUserAsli);
    $noID = nomorUrut($db, 'ID', $idUserAsli);

    $cekID = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien WHERE noIdentitas = ?',
        [$noID]
    );

    if ($cekID) {
        updateNomorUrut($db, 'ID', $idUserAsli);
        $noID = nomorUrut($db, 'ID', $idUserAsli);
    }
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

                        if ($index === 0) {
                            $classBtn = 'btn-primary';
                        } else {
                            $classBtn = 'btn-light-primary';
                        }

                    ?>
                        <button type="button" style="width: 100%; text-align: center;" class="btn <?= $classBtn ?> btn-admission-type-tab mb-2" data-id="<?= $row['idAdmisi'] ?>">
                            <i class="fas fa-user-md d-block mb-2 pr-0 fa-2x"></i> <strong class="text-uppercase"><?= $row['namaAdmisi']; ?></strong>
                        </button>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-xl-10 mb-5">
            <div id="kt_page_sticky_card" class="card card-custom card-sticky">
                <div class="card-header card-header-tabs-line">
                    <!-- CARD TITLE -->
                    <div class="card-title">
                        <h3 class="card-label"><i class="fas fa-file-signature pr-5 text-dark"></i> <strong>FORM PENDAFTARAN</strong></h3>
                    </div>
                    <!-- END CARD TITLE -->
                    <div class="card-toolbar">
                        <ul class="nav nav-tabs nav-bold nav-tabs-line">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#boxFormPasienBaru">
                                    <span class="nav-icon"><i class="flaticon2-user-1"></i></span>
                                    <span class="nav-text"><strong>DAFTAR</strong></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#boxSearchPasien">
                                    <span class="nav-icon"><i class="flaticon2-magnifier-tool"></i></span>
                                    <span class="nav-text"><strong>CARI</strong></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- CARD BODY -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="boxFormPasienBaru" role="tabpanel" aria-labelledby="boxFormPasienBaru">

                            <form id="formPasienBaru">
                                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                                <input type="hidden" name="flag" value="daftar">
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
                                    <input type="text" class="form-control" id="namaPasien" name="namaPasien" placeholder="Nama Pasien">
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-sm-6">
                                        <label><i class="fa fa-calendar"></i> Tgl Lahir </label>
                                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                                            <input type="text" class="form-control" id="tanggalLahir" name="tanggalLahir" placeholder="Tanggal Lahir" value="" autocomplete="off">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button">
                                                    <i class="fa fa-calendar"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label><i class="fa fa-city"></i> Tempat Lahir </label>
                                        <input type="text" class="form-control" name="tempatLahir" placeholder="Tempat Lahir">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-sm-3">
                                        <label><i class="fa fa-child"></i>Jumlah Saudara Kembar <sup>( Wajib Satu Sehat )</sup></label>
                                        <input type="text" class="form-control" name="multipleBirthInteger" placeholder="Jumlah Saudara Kembar (Isi 0 bila tidak memiliki saudara kembar )" value="0" data-format-rupiah="active">
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label><i class="fa fa-address-book"></i> Alamat ID </label>
                                        <input type="text" class="form-control" name="alamat" placeholder="Alamat ID">
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label><i class="fa fa-address-book"></i> Alamat Domisili <sup>( Wajib Satu Sehat )</sup></label>
                                        <input type="text" class="form-control" name="alamatDomisili" placeholder="Alamat Domisili">
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label><i class="fa fa-mail-bulk"></i>Kode Pos <sup>( Wajib Satu Sehat )</sup></label>
                                        <input type="text" class="form-control" name="postalCode" placeholder="Kode Pos">
                                    </div>
                                </div>
                                <div class="form-row" id="boxWilayah">

                                </div>
                                <div class="form-row">
                                    <div class="form-group col-sm-6">
                                        <label><i class="fa fa-phone"></i> No Telp <sup>( Wajib Satu Sehat )</sup></label>
                                        <input type="text" class="form-control" name="noTelp" placeholder="No Telp">
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label><i class="fa fa-envelope"></i> Email <sup>( Wajib Satu Sehat )</sup></label>
                                        <input type="text" class="form-control" id="email" name="email" placeholder="Email">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-sm-3">
                                        <label><i class="fa fa-venus-mars"></i> Kelamin </label>
                                        <select id="jenisKelamin" name="jenisKelamin" class="form-control selectpicker" data-live-search="true">
                                            <?php
                                            $genders = ['Laki-laki', 'Perempuan'];
                                            foreach ($genders as $gender) {
                                            ?>
                                                <option value="<?= $gender ?>"><?= ucfirst($gender) ?></option>
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
                                            ?>
                                                <option value="<?= $marital['name'] ?>"><?= $invoke->display ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-3">
                                        <label><i class="fas fa-flag"></i> Kebangsaan </label>
                                        <select id="kebangsaan" name="kebangsaan" class="form-control select2" data-live-search="true" style="width: 100%;">
                                            <?php
                                            $opsiKebangsaan = [
                                                'Lokal',
                                                'Domestik',
                                                'KITAS',
                                                'Internasional'
                                            ];
                                            foreach ($opsiKebangsaan as $row) {
                                            ?>
                                                <option value="<?= $row ?>"><?= $row ?></option>
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
                                            ?>
                                                <option value="<?= $language['name'] ?>"><?= $invoke->display ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="button" class="btn btn-primary" onclick="prosesPasienBaru()">
                                        <i class="fas fa-clipboard-check pr-4"></i> <strong>DAFTAR</strong>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="boxSearchPasien" role="tabpanel" aria-labelledby="boxSearchPasien">
                            <div class="form-group">
                                <label for="searchPasienForm">CARI PASIEN</label>
                                <input type="text" class="form-control form-control-solid px-6 py-8" id="searchPasienForm" placeholder="Silahkan Ketik Kode RM, No. Identitas, No. BPJS, atau Nama Pasien (Minimal 4 Karakter)">
                            </div>
                            <div id="boxResultPasien">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END CARD BODY -->
            </div>
            <!-- END CARD -->
        </div>
    </div>
<?php
}
?>