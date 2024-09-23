<?php

$FORM_TYPE = base64_encode('pemeriksaan-default');

$dataPemeriksaan = statementWrapper(
    DML_SELECT,
    'SELECT * FROM pasien_pemeriksaan_klinik WHERE kodeAntrian = ?',
    [$kodeAntrian]
);

if ($dataPemeriksaan) {
    $alergi = $dataPemeriksaan['alergiObat'];
    $keluhanUtama = $dataPemeriksaan['keluhanUtama'];

    $riwayatSakit = $dataPemeriksaan['historyOfRecentIllness'];
} else {
    [$alergi, $keluhanUtama] = statementWrapper(
        DML_SELECT,
        "SELECT alergi, keluhan FROM pasien_antrian WHERE kodeAntrian = ?",
        [$kodeAntrian]
    );

    [$riwayatKeluhan, $riwayatDeskripsiKeluhan] = statementWrapper(
        DML_SELECT,
        'SELECT 
            pasien_pemeriksaan_klinik.keluhanUtama, 
            pasien_pemeriksaan_klinik.deskripsiKeluhan 
        FROM 
            pasien_pemeriksaan_klinik
            INNER JOIN pasien_antrian ON pasien_pemeriksaan_klinik.kodeAntrian = pasien_antrian.kodeAntrian 
        WHERE 
            pasien_antrian.kodeRM = ?
        ORDER BY 
            pasien_pemeriksaan_klinik.idPemeriksaanKlinik DESC
        ',
        [$kodeRM]
    );

    if (is_null($riwayatKeluhan) && is_null($riwayatDeskripsiKeluhan)) {
        $riwayatSakit = '';
    } else {
        $riwayatSakit = "{$riwayatKeluhan}\n{$riwayatDeskripsiKeluhan}";
    }
}

$eye = cekVariabel(
    'Anemia ( / ), icterus ( / ), sunken eyes ( / ), pupil reflex ( / )',
    $dataPemeriksaan['eye']
);

$nose = cekVariabel(
    'hyperemic ( ), mucus ( )',
    $dataPemeriksaan['nose']
);

$throat = cekVariabel(
    'Hyperemic ( )',
    $dataPemeriksaan['throat']
);

$tonsil = cekVariabel(
    'T1/T1, hiperemic ( / )',
    $dataPemeriksaan['tonsil']
);

$cor = cekVariabel(
    'S1 S2 regular, gallop ( ), murmur ( )',
    $dataPemeriksaan['cor']
);

$pulmo = cekVariabel(
    'vesicular ( ), wheezing ( ), rhonchi ( )',
    $dataPemeriksaan['pulmo']
);

$abdomen = cekVariabel(
    'bowel sound ( ), Tenderness ( )',
    $dataPemeriksaan['abdomen']
);

$extrimities = cekVariabel(
    'warm ( / ), edema ( / ), warm ( / ), edema ( / )',
    $dataPemeriksaan['extrimities']
);
?>
<div class="card card-custom">
    <!-- CARD HEADER -->
    <div class="card-header">
        <!-- CARD TITLE -->
        <div class="card-title">
            <h3 class="card-label">
                <span class="card-label font-weight-bolder text-dark d-block">
                    <i class="fa fa-diagnoses text-dark"></i> Pemeriksaan Pasien
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
        <input type="hidden" value="<?= $FORM_TYPE ?>">

        <div class="form-row">
            <div class="form-group col-sm-6">
                <label>Keluhan Utama </label>
                <textarea type="text" disabled id="keluhanUtama" class="form-control" rows="6" placeholder="Keluhan Utama"><?= $keluhanUtama ?></textarea>
            </div>
            <div class="form-group col-sm-6">
                <label>Alergi </label>
                <textarea type="text" disabled id="alergiObat" class="form-control" rows="6" placeholder="Input Allergy"><?= $alergi; ?></textarea>
            </div>
        </div>
        <div class="form-group">
            <label>Deskripsi Keluhan Utama</label>
            <div class="border rounded w-100 p-3" style="min-height:200px; overflow-y:auto">
                <?= $dataPemeriksaan['deskripsiKeluhan']; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-12">
                <label>Riwayat Sakit</label>
                <div class="border rounded w-100 p-3" style="min-height:200px; overflow-y:auto">
                    <?= $dataPemeriksaan['historyOfRecentIllness']; ?>
                </div>
            </div>
            <div class="form-group col-sm-12">
                <label>Treatment So Far</label>
                <div class="border rounded w-100 p-3" style="min-height:200px; overflow-y:auto">
                    <?= $dataPemeriksaan['treatmentSoFar']; ?>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-sm-2">
                <label>TK</label>
                <input type="text" disabled class="form-control" placeholder="Tingkat Kesadaran" value="<?= $dataPemeriksaan['levelOfConsiusness'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-2">
                <label>RR</label>
                <input type="text" disabled class="form-control" placeholder="Respirasi" value="<?= $dataPemeriksaan['respiratoryRate'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-2">
                <label>TD</label>
                <input type="text" disabled class="form-control" placeholder="Tekanan Darah" value="<?= $dataPemeriksaan['bloodPressure'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-2">
                <label>Suhu</label>
                <input type="text" disabled class="form-control" placeholder="Suhu" value="<?= $dataPemeriksaan['temperature'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-2">
                <label>Nadi</label>
                <input type="text" disabled class="form-control" placeholder="Nadi" value="<?= $dataPemeriksaan['pulse'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-2">
                <label>Sat O2</label>
                <input type="text" disabled class="form-control" placeholder="Saturasi O2" value="<?= $dataPemeriksaan['o2Saturation'] ?? '' ?>">
            </div>
        </div>

        <div class="form-row">

            <div class="form-group col-sm-12">
                <label>Penampilan Umum</label>
                <input type="text" disabled class="form-control" placeholder="Penampilan Umum" value="<?= $dataPemeriksaan['generalAppearance'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-4">
                <label>Mata</label>
                <textarea disabled class="form-control"><?= $eye ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Telinga</label>
                <input type="text" disabled class="form-control" placeholder="Telinga" value="<?= $dataPemeriksaan['ear'] ?? '' ?>">
            </div>
            <div class="form-group col-sm-4">
                <label>Hidung</label>
                <textarea disabled class="form-control"><?= $nose ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Tenggorokan</label>
                <textarea disabled class="form-control"><?= $throat ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Tonsil</label>
                <textarea disabled class="form-control"><?= $tonsil ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Jantung</label>
                <textarea disabled class="form-control"><?= $cor ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Paru-paru</label>
                <textarea disabled class="form-control"><?= $pulmo ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Perut</label>
                <textarea disabled class="form-control"><?= $abdomen ?></textarea>
            </div>
            <div class="form-group col-sm-4">
                <label>Extrimitas</label>
                <textarea disabled class="form-control"><?= $extrimities ?></textarea>
            </div>
            <div class="form-group col-sm-12">
                <label>Status Lokalis</label>
                <textarea disabled class="form-control" placeholder="Status Lokalis"><?= $dataPemeriksaan['statusLokalis'] ?></textarea>
            </div>
            <div class="form-group col-sm-12">
                <label>Gula Darah / Asam Urat / Kolesterol</label>
                <input type="text" disabled class="form-control" placeholder="Random Blood Glucose" value="<?= $dataPemeriksaan['randomBloodGlucose'] ?>">
            </div>
            <div class="form-group col-sm-6">
                <label>Tinggi Badan</label>
                <input type="text" disabled class="form-control" placeholder="Tinggi Badan" value="<?= $dataPemeriksaan['tinggiBadan'] ?>">
            </div>
            <div class="form-group col-sm-6">
                <label>Berat Badan</label>
                <input type="text" disabled class="form-control" placeholder="Berat Badan" value="<?= $dataPemeriksaan['beratBadan'] ?>">
            </div>
        </div>

        <div class="form-row">

            <div class="form-group col-sm-12">
                <label>Manajemen dan Prosedur </label>
                <div class="border rounded w-100 p-3" style="min-height:200px; overflow-y:auto">
                    <?= $dataPemeriksaan['managementAndProcedure']; ?>
                </div>
            </div>
            <div class="form-group col-sm-12">
                <label>Pengobatan dan Dosis </label>
                <div class="border rounded w-100 p-3" style="min-height:200px; overflow-y:auto">
                    <?= $dataPemeriksaan['medicationAndDosage']; ?>
                </div>
            </div>
            <div class="form-group col-sm-12">
                <label>Rekomendasi</label>
                <div class="border rounded w-100 p-3" style="min-height:200px; overflow-y:auto">
                    <?= $dataPemeriksaan['recommendation']; ?>
                </div>
            </div>
            <div class="form-group col-sm-6">
                <label>Buta Warna</label>
                <input type="text" disabled class="form-control" placeholder="Buta Warna" value="<?= $dataPemeriksaan['butaWarna'] ?>">
            </div>
            <div class="form-group col-sm-6">
                <label>Fit To Fly</label>
                <input type="text" disabled class="form-control" placeholder="Fit To Fly" value="<?= $dataPemeriksaan['fitToFly'] ?>">
            </div>
        </div>
    </div>
</div>
<?php

?>