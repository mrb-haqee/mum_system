<?php

/**
 * Parameter requirements = ["kodeRM", "kodeAntrian", "jenisSurat" => 'Surat Sakit']
 */
function viewValidatorSuratSakit(array $requirements, array $params)
{
    foreach ($requirements as $requirement) {
        if (!in_array($requirement, array_keys($params))) return false;
    }

    if ($params['jenisSurat'] !== 'Surat Sakit') return false;

    $dataPasien = statementWrapper(
        DML_SELECT,
        "SELECT
            *
        FROM    
            pasien
        WHERE
            kodeRM = ?
        ",
        [$params['kodeRM']]
    );

    $dataAntrian = statementWrapper(
        DML_SELECT,
        "SELECT
            pasien_antrian.*,
            dokter.namaPegawai as namaDokter,
            klinik.nama as namaKlinik,
            perawat.namaPegawai as namaPerawat
        FROM
            pasien_antrian
            INNER JOIN pegawai dokter ON pasien_antrian.idDokter = dokter.idPegawai
            INNER JOIN pegawai perawat ON pasien_antrian.idPerawat = perawat.idPegawai
            INNER JOIN klinik ON pasien_antrian.idKlinik = klinik.idKlinik
        WHERE
            pasien_antrian.kodeAntrian = ?
        ",
        [$params['kodeAntrian']]
    );

    $dataSurat = statementWrapper(
        DML_SELECT,
        "SELECT
            *
        FROM
            pasien_surat
        WHERE
            kodeRM = ?
            AND kodeAntrian = ?
            AND jenisSurat = ?
        ",
        [$params['kodeRM'], $params['kodeAntrian'], $params['jenisSurat']]
    );

    $qr_filename = escapeFilename("QRVALIDATOR_{$params['kodeAntrian']}_Surat_Sakit");
?>
    <div style="width: 100%;" class="text-center mb-10 mb-lg-20">
        <img alt="Logo" src="<?= BASE_URL_HTML ?>/assets/media/misc/base-icon.png" style="width:35%">
        <h1 class="font-weight-bolder">CONFIDENTIAL SICK LETTER</h1>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-5 text-md-right text-center mb-4">
                <img src="<?= REL_PATH_FILE_UPLOAD_DIR ?>/qr_validator/<?= $qr_filename ?>.png" alt="">
            </div>
            <div class="col-md-7 text-lg-left text-md-left text-sm-center text-center mb-4" style="font-size: 1.2rem;">
                <p>Confidential Sick Letter for patient :</p>
                <span class="font-size-h1 font-weight-bolder"><?= $dataPasien['namaPasien']; ?></span>
                <p class="mt-5 mb-1">Issued at <strong><u><?= $dataAntrian['namaKlinik']; ?></u></strong></p>
                <p>on <strong><u><?= ubahTanggalIndo($dataAntrian['tanggalPendaftaran']); ?></u></strong> by <strong><u><?= $dataAntrian['namaDokter']; ?></u></strong></p>
            </div>
        </div>
    </div>
<?php
    return true;
}
