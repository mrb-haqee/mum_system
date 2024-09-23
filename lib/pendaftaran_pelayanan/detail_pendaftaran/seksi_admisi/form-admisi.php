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

    $sessionKlinik = dekripsi($enc_idKlinik, secretKey());

    extract($_POST, EXTR_SKIP);

    $dataPasien = statementWrapper(
        DML_SELECT,
        'SELECT 
            pasien_antrian.*,
            pasien_antrian_satusehat.* 
        FROM 
            pasien_antrian
            LEFT JOIN pasien_antrian_satusehat ON pasien_antrian.kodeAntrian = pasien_antrian_satusehat.kodeAntrian 
        WHERE 
            pasien_antrian.kodeAntrian = ?',
        [$kodeAntrian]
    );

    if ($dataPasien) {
        $flag = 'update';

        $deskripsiAlergi = $dataPasien['alergi'];

        $kodeAlergi = $dataPasien['kodeAlergi'] ?? '';
        $kategoriAlergi = $dataPasien['kategoriAlergi'];
    } else {
        $flag = 'tambah';

        [$deskripsiAlergi, $kodeAlergi, $kategoriAlergi] = statementWrapper(
            DML_SELECT,
            'SELECT 
                pasien_pemeriksaan_klinik.alergiObat,
                pasien_antrian_satusehat.kodeAlergi,
                pasien_antrian_satusehat.kategoriAlergi
            FROM 
                pasien_pemeriksaan_klinik 
                INNER JOIN pasien_antrian ON pasien_pemeriksaan_klinik.kodeAntrian = pasien_antrian.kodeAntrian 
                LEFT JOIN pasien_antrian_satusehat ON pasien_antrian.kodeAntrian = pasien_antrian_satusehat.kodeAntrian
            WHERE 
                pasien_antrian.kodeRM = ? 
                AND pasien_antrian.statusAntrian = ?
                ORDER BY pasien_pemeriksaan_klinik.idPemeriksaanKlinik DESC LIMIT 1',
            [$kodeRM, 'Aktif']
        );
    }

?>
    <div class="card card-custom">
        <!-- CARD HEADER -->
        <div class="card-header">
            <!-- CARD TITLE -->
            <div class="card-title">
                <h3 class="card-label">
                    <span class="card-label font-weight-bolder text-dark d-block">
                        <i class="fa fa-info-circle text-dark"></i> Informasi Admisi
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
            <form id="formAdmisi">
                <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                <input type="hidden" name="kodeAntrian" value="<?= $kodeAntrian ?>">
                <input type="hidden" name="flag" value="<?= $flag ?>">
                <input type="hidden" name="kodeRM" value="<?= $kodeRM ?>">
                <input type="hidden" name="idKlinik" value="<?= $sessionKlinik ?>">
                <input type="hidden" name="alamatBali" value="-">

                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fa fa-calendar"></i> Tanggal Pendaftaran</label>
                        <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input type="text" class="form-control" id="tanggalPendaftaran" name="tanggalPendaftaran" placeholder="Click to select a date!" value="<?= $dataPasien['tanggalPendaftaran'] ?? date('Y-m-d') ?>" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fa fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="idAdmisi"><i class="fas fa-hospital"></i> Admisi</label>
                        <select name="idAdmisi" id="idAdmisi" class="form-control selectpicker">
                            <option value=""> Pilih Admisi</option>
                            <?php
                            $opsi = statementWrapper(
                                DML_SELECT_ALL,
                                'SELECT * FROM admisi WHERE statusAdmisi = ?',
                                ['Aktif']
                            );
                            foreach ($opsi as $row) {
                                $selected = selected(intval($row['idAdmisi']), intval($dataPasien['idAdmisi'] ?? $paramAdmisi));
                            ?>
                                <option value="<?= $row['idAdmisi'] ?>" <?= $selected ?>> <?= $row['namaAdmisi'] ?> </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="jenisHarga"><i class="fas fa-dollar-sign"></i> Jenis Harga</label><br>
                        <select name="jenisHarga" id="jenisHarga" class="form-control selectpicker" data-live-search="true">
                            <option value=""> Pilih Jenis Harga </option>
                            <?php
                            $opsi = getJenisHarga();
                            foreach ($opsi as $row) {
                                $selected = selected($row, $dataPasien['jenisHarga'] ?? '');
                            ?>
                                <option value="<?= $row ?>" <?= $selected ?>> <?= $row ?> </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label for="idShift"><i class="fas fa-clock"></i> Shift</label>
                        <select name="idShift" id="idShift" class="form-control selectpicker">
                            <option value=""> Pilih Shift</option>
                            <?php
                            $opsi = statementWrapper(
                                DML_SELECT_ALL,
                                'SELECT * FROM shift WHERE statusShift = ?',
                                ['Aktif']
                            );
                            foreach ($opsi as $row) {
                                $selected = selected(intval($row['idShift']), intval($dataPasien['idShift']));
                            ?>
                                <option value="<?= $row['idShift'] ?>" <?= $selected ?>> <?= $row['shift'] ?> </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="idDokter"><i class="fas fa-user-md"></i> Dokter</label><br>
                        <select name="idDokter" id="idDokter" class="form-control selectpicker" data-live-search="true">
                            <option value=""> Pilih Dokter </option>
                            <?php
                            $sqlDokter = $db->prepare(
                                'SELECT 
                                    pegawai.* 
                                FROM 
                                    pegawai 
                                    INNER JOIN pegawai_jaspel ON pegawai.kodePegawai = pegawai_jaspel.kodePegawai
                                WHERE 
                                    pegawai.statusPegawai = ?
                                    AND pegawai_jaspel.idJabatan IN (1,3)
                                    '
                            );
                            $sqlDokter->execute([
                                'Aktif'
                            ]);
                            $dataDokter = $sqlDokter->fetchAll();

                            foreach ($dataDokter as $row) {
                                $selected = selected($row['idPegawai'], $dataPasien['idDokter']);
                            ?>
                                <option value="<?= $row['idPegawai'] ?>" <?= $selected ?>> <?= $row['namaPegawai'] ?> </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="idPerawat"><i class="fas fa-user-nurse"></i> Perawat</label><br>
                        <select name="idPerawat" id="idPerawat" class="form-control selectpicker" data-live-search="true">
                            <option value=""> Pilih Perawat </option>
                            <?php
                            $sqlPerawat = $db->prepare(
                                'SELECT 
                                    pegawai.* 
                                FROM 
                                    pegawai 
                                    INNER JOIN pegawai_jaspel ON pegawai.kodePegawai = pegawai_jaspel.kodePegawai
                                WHERE 
                                    pegawai.statusPegawai = ?
                                    AND pegawai_jaspel.idJabatan IN (2)
                                '
                            );
                            $sqlPerawat->execute([
                                'Aktif'
                            ]);
                            $dataPerawat = $sqlPerawat->fetchAll();

                            foreach ($dataPerawat as $row) {
                                $selected = selected($row['idPegawai'], $dataPasien['idPerawat']);
                            ?>
                                <option value="<?= $row['idPegawai'] ?>" <?= $selected ?>> <?= $row['namaPegawai'] ?> </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label><i class="fas fa-clock"></i>Waktu Kedatangan <sup>( Wajib Satu Sehat )</sup></label>
                        <div class="input-group date" id="waktuKedatangan" data-target-input="nearest" data-value="<?= date_create($dataPasien['waktuKedatangan'] ?? date('Y-m-d H:i:s'))->format('m/d/Y H:i:s') ?>">
                            <input type="text" class="form-control datetimepicker-input" name="waktuKedatangan" value="<?= date_create($dataPasien['waktuKedatangan'] ?? date('Y-m-d H:i:s'))->format('m/d/Y H:i:s') ?>" placeholder="Select date & time" data-target="#waktuKedatangan" />
                            <div class="input-group-append" data-target="#waktuKedatangan" data-toggle="datetimepicker">
                                <span class="input-group-text">
                                    <i class="ki ki-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label><i class="fas fa-cogs"></i>Kategori Alergi <sup>( Wajib Satu Sehat )</sup></label>
                        <select name="kategoriAlergi" id="kategoriAlergi" class="form-control select2" style="width: 100%;">
                            <option value="">-- TIDAK ADA ALERGI --</option>
                            <?php
                            $categories = \SatuSehat\FHIR\Interoperabilitas\AllergyIntoleranceCategory::optionsFromConstant();
                            foreach ($categories as $category) {
                                $selected = selected($category['name'], $dataPasien['kategoriAlergi'] ?? $kategoriAlergi);
                            ?>
                                <option value="<?= $category['name'] ?>" <?= $selected; ?>><?= $category['name']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label><i class="fas fa-id-badge"></i>Alergi (SNOMED-CT) <sup>( Wajib Satu Sehat )</sup></label>
                        <select name="kodeAlergi" id="kodeAlergi" class="form-control select2" style="width: 100%;">
                            <option value="">-- TIDAK ADA ALERGI --</option>
                            <?php
                            $allergies = statementWrapper(
                                DML_SELECT_ALL,
                                'SELECT * FROM alergi',
                                []
                            );

                            foreach ($allergies as $allergy) {
                                $selected = selected($allergy['code'], $dataPasien['kodeAlergi'] ?? $kodeAlergi);
                            ?>
                                <option value="<?= $allergy['code'] ?>" <?= $selected; ?>><?= $allergy['deskripsi']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label><i class="fas fa-biohazard"></i>Deskripsi Alergi</label>
                        <textarea type="text" name="alergi" id="alergi" class="form-control" placeholder="Input Alergi"><?= $deskripsiAlergi ?? '' ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-biohazard"></i>Keluhan</label>
                    <textarea type="text" name="keluhan" id="keluhan" class="form-control" placeholder="Input Keluhan"><?= $dataPasien['keluhan'] ?? '' ?></textarea>
                </div>

                <div class="form-group">
                    <?php
                    if ($flag === 'tambah') {
                    ?>
                        <button type="button" class="btn btn-success" onclick="prosesAdmisi()">
                            <i class="fa fa-save"></i> <strong>SIMPAN</strong>
                        </button>
                    <?php
                    } else if ($flag === 'update') {
                    ?>
                        <button type="button" class="btn btn-primary" onclick="prosesAdmisi()">
                            <i class="fa fa-save"></i> <strong>SIMPAN</strong>
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