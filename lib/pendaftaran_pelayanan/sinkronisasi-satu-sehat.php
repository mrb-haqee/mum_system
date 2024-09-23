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

$tokenValid = hash_equals($tokenCSRF, $_POST['tokenCSRFForm'] ?? '');
//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !validateIP($_SESSION['IP_ADDR']) || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Proses Authentikasi Gagal, Data Tidak Valid');
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {
        if ($flag === 'syncPasien') {
            $dataPasien = statementWrapper(
                DML_SELECT,
                'SELECT 
                    pasien.*, 
                    pasien_satusehat.*, 
                    provinsi.nama as namaProvinsi, 
                    kabkot.nama as namaKabkot, 
                    kecamatan.nama as namaKecamatan, 
                    desa.nama as namaDesa
                FROM 
                    pasien 
                    INNER JOIN pasien_satusehat ON pasien.kodeRM = pasien_satusehat.kodeRM
                    LEFT JOIN wilayah provinsi ON pasien_satusehat.provinceCode = provinsi.kodeResmi
                    LEFT JOIN wilayah kabkot ON pasien_satusehat.regencyCode = kabkot.kodeResmi
                    LEFT JOIN wilayah kecamatan ON pasien_satusehat.districtCode = kecamatan.kodeResmi
                    LEFT JOIN wilayah desa ON pasien_satusehat.villageCode = desa.kodeResmi
                WHERE 
                    pasien.kodeRM = ?',
                [$kodeRM]
            );

            if ($dataPasien) {

                switch ($dataPasien['jenisKelamin']) {
                    case 'Laki-laki':
                        $gender = \SatuSehat\DataType\Other\Gender::MALE;
                        break;
                    case 'Perempuan':
                        $gender = \SatuSehat\DataType\Other\Gender::FEMALE;
                        break;
                }

                switch ($dataPasien['kebangsaan']) {
                    case 'Lokal':
                    case 'Domestik':
                        $citizenship = 'WNI';
                        break;
                    case 'KITAS':
                    case 'Internasional':
                        $citizenship = 'WNA';
                        break;
                }

                $syncHandler = new \SatuSehat\FHIR\Orientasi\Patient;
                $syncHandler->setMeta()
                    ->setIdentifierByNIK($dataPasien['noIdentitas'])
                    ->setActive()
                    ->setName(new \SatuSehat\DataType\General\HumanName($dataPasien['namaPasien']))
                    ->setTelecom(new \SatuSehat\DataType\General\ContactPoint('phone', 'mobile', $dataPasien['noTelp']))
                    ->setTelecom(new \SatuSehat\DataType\General\ContactPoint('email', 'mobile', $dataPasien['email']))
                    ->setGender($gender)
                    ->setBirthDate(new DateTime($dataPasien['tanggalLahir']))
                    ->setDeceased(false)
                    ->setAddress(new \SatuSehat\DataType\General\Address(
                        'home',
                        'both',
                        $dataPasien['alamatDomisili'] ?? '',
                        [
                            $dataPasien['alamatDomisili'] ?? ''
                        ],
                        $dataPasien['namaKabkot'] ?? '',
                        $dataPasien['namaKecamatan'] ?? '',
                        $dataPasien['namaProvinsi'] ?? '',
                        $dataPasien['postalCode'] ?? '',
                        'ID',
                        '',
                        [
                            [
                                "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                                "extension" => [
                                    [
                                        "url" => "province",
                                        "valueCode" => $dataPasien['provinceCode'] ?? ''
                                    ],
                                    [
                                        "url" => "city",
                                        "valueCode" => $dataPasien['regencyCode'] ?? ''
                                    ],
                                    [
                                        "url" => "district",
                                        "valueCode" => $dataPasien['districtCode'] ?? ''
                                    ],
                                    [
                                        "url" => "village",
                                        "valueCode" => $dataPasien['villageCode'] ?? ''
                                    ]
                                ]
                            ]
                        ]
                    ))
                    ->setMaritalStatus(\SatuSehat\DataType\Other\Marital::invokeIfExist($dataPasien['maritalStatus']))
                    ->setMultipleBirthInteger(intval($dataPasien['multipleBirthInteger']))
                    ->setCommunication(\SatuSehat\DataType\Other\Language::invokeIfExist($dataPasien['language']))
                    ->setExtension([
                        [
                            "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/citizenshipStatus",
                            "valueCode" => $citizenship
                        ]
                    ]);

                if (is_null($dataPasien['kodeUUIDSatuSehat'])) {
                    ['code' => $code, 'data' => $response] = $syncHandler->create();
                } else {
                    ['code' => $code, 'data' => $response] = $syncHandler->update($dataPasien['kodeUUIDSatuSehat']);
                }

                switch (intval($code)) {
                    case 200:
                    case 201:
                        $status = true;
                        $pesan = 'Proses Sinkronisasi Satu Sehat Berhasil';

                        statementWrapper(
                            DML_UPDATE,
                            'UPDATE
                                pasien
                            SET
                                kodeUUIDSatuSehat = ?,
                                idUser = ?
                            WHERE
                                kodeRM = ?
                            ',
                            [$response['data']['patient_id'], $idUserAsli, $kodeRM]
                        );

                        break;

                    default:
                        $status = false;
                        $pesan = 'Proses Sinkronisasi Satu Sehat Gagal';
                        break;
                }
            } else {
                $status = false;
                $pesan = 'Pasien Belum Memenuhi Prasyarat Sinkronisasi Satu Sehat';
            }
        } else if ($flag === 'bundle') {

            $bundleHandler = new \SatuSehat\FHIR\Interoperabilitas\Bundler;

            $cekPasien = statementWrapper(
                DML_SELECT,
                'SELECT 
                    pasien.*
                FROM 
                    pasien
                    INNER JOIN pasien_antrian ON pasien.kodeRM = pasien_antrian.kodeRM
                WHERE 
                    pasien_antrian.kodeAntrian = ?',
                [$kodeAntrian]
            );

            if (in_array($cekPasien['kebangsaan'], ['Lokal', 'Domestik'])) {

                ['code' => $code, 'data' => $response] = \SatuSehat\FHIR\Orientasi\Patient::getByNIK($cekPasien['noIdentitas']);

                if (intval($code) === 200) {
                    if (!empty($response['entry'])) {
                        $statusPasien = statementWrapper(
                            DML_UPDATE,
                            'UPDATE
                                pasien
                            SET
                                kodeUUIDSatuSehat = ?,
                                idUser = ?
                            WHERE
                                kodeRM = ?
                            ',
                            [$response['entry'][0]['resource']['id'], $idUserAsli, $cekPasien['kodeRM']]
                        );
                    } else {
                        $dataPasien = statementWrapper(
                            DML_SELECT,
                            'SELECT 
                                pasien.*,
                                pasien_satusehat.*, 
                                provinsi.nama as namaProvinsi, 
                                kabkot.nama as namaKabkot, 
                                kecamatan.nama as namaKecamatan, 
                                desa.nama as namaDesa
                            FROM 
                                pasien 
                                INNER JOIN pasien_satusehat ON pasien.kodeRM = pasien_satusehat.kodeRM
                                LEFT JOIN wilayah provinsi ON pasien_satusehat.provinceCode = provinsi.kodeResmi
                                LEFT JOIN wilayah kabkot ON pasien_satusehat.regencyCode = kabkot.kodeResmi
                                LEFT JOIN wilayah kecamatan ON pasien_satusehat.districtCode = kecamatan.kodeResmi
                                LEFT JOIN wilayah desa ON pasien_satusehat.villageCode = desa.kodeResmi
                            WHERE 
                                pasien.kodeRM = ?
                            ',
                            [$cekPasien['kodeRM']]
                        );

                        switch ($dataPasien['jenisKelamin']) {
                            case 'Laki-laki':
                                $gender = \SatuSehat\DataType\Other\Gender::MALE;
                                break;
                            case 'Perempuan':
                                $gender = \SatuSehat\DataType\Other\Gender::FEMALE;
                                break;
                        }

                        switch ($dataPasien['kebangsaan']) {
                            case 'Lokal':
                            case 'Domestik':
                                $citizenship = 'WNI';
                                break;
                            case 'KITAS':
                            case 'Internasional':
                                $citizenship = 'WNA';
                                break;
                        }

                        $syncHandler = new \SatuSehat\FHIR\Orientasi\Patient;
                        $syncHandler->setMeta()
                            ->setIdentifierByNIK($dataPasien['noIdentitas'])
                            ->setActive()
                            ->setName(new \SatuSehat\DataType\General\HumanName($dataPasien['namaPasien']))
                            ->setTelecom(new \SatuSehat\DataType\General\ContactPoint('phone', 'mobile', $dataPasien['noTelp']))
                            ->setTelecom(new \SatuSehat\DataType\General\ContactPoint('email', 'mobile', $dataPasien['email']))
                            ->setGender($gender)
                            ->setBirthDate(new DateTime($dataPasien['tanggalLahir']))
                            ->setDeceased(false)
                            ->setAddress(new \SatuSehat\DataType\General\Address(
                                'home',
                                'both',
                                $dataPasien['alamatDomisili'] ?? '',
                                [
                                    $dataPasien['alamatDomisili'] ?? ''
                                ],
                                $dataPasien['namaKabkot'] ?? '',
                                $dataPasien['namaKecamatan'] ?? '',
                                $dataPasien['namaProvinsi'] ?? '',
                                $dataPasien['postalCode'] ?? '',
                                'ID',
                                '',
                                [
                                    [
                                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                                        "extension" => [
                                            [
                                                "url" => "province",
                                                "valueCode" => $dataPasien['provinceCode'] ?? ''
                                            ],
                                            [
                                                "url" => "city",
                                                "valueCode" => $dataPasien['regencyCode'] ?? ''
                                            ],
                                            [
                                                "url" => "district",
                                                "valueCode" => $dataPasien['districtCode'] ?? ''
                                            ],
                                            [
                                                "url" => "village",
                                                "valueCode" => $dataPasien['villageCode'] ?? ''
                                            ]
                                        ]
                                    ]
                                ]
                            ))
                            ->setMaritalStatus(\SatuSehat\DataType\Other\Marital::invokeIfExist($dataPasien['maritalStatus']))
                            ->setMultipleBirthInteger(intval($dataPasien['multipleBirthInteger']))
                            ->setCommunication(\SatuSehat\DataType\Other\Language::invokeIfExist($dataPasien['language']))
                            ->setExtension([
                                [
                                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/citizenshipStatus",
                                    "valueCode" => $citizenship
                                ]
                            ]);

                        if (is_null($dataPasien['kodeUUIDSatuSehat'])) {
                            ['code' => $code, 'data' => $response] = $syncHandler->create();
                        } else {
                            ['code' => $code, 'data' => $response] = $syncHandler->update($dataPasien['kodeUUIDSatuSehat']);
                        }

                        switch (intval($code)) {
                            case 200:
                            case 201:

                                $statusPasien = statementWrapper(
                                    DML_UPDATE,
                                    'UPDATE
                                        pasien
                                    SET
                                        kodeUUIDSatuSehat = ?,
                                        idUser = ?
                                    WHERE
                                        kodeRM = ?
                                    ',
                                    [$response['data']['patient_id'], $idUserAsli, $kodeRM]
                                );

                                break;

                            default:
                                $statusPasien = null;
                                break;
                        }
                    }
                }

                $encounter = statementWrapper(
                    DML_SELECT,
                    'SELECT 
                        pasien_antrian.kodeAntrian,
                        pasien_antrian.keluhan,
                        pasien_antrian.tanggalPendaftaran,
                        pasien_antrian_satusehat.waktuKedatangan,
                        pasien_antrian_satusehat.waktuPemeriksaan,
                        pasien_antrian_satusehat.waktuKepulangan,
                        pasien.namaPasien,
                        pasien.kodeUUIDSatuSehat as uuidPasien,
                        dokter.kodeIHS as ihsDokter,
                        dokter.namaPegawai as namaDokter,
                        perawat.kodeIHS as ihsPerawat,
                        perawat.namaPegawai as namaPerawat,
                        admisi.kodeUUIDSatuSehat as uuidAdmisi,
                        admisi.idAdmisi,
                        admisi.namaAdmisi
                    FROM 
                        pasien_antrian
                        INNER JOIN pasien_antrian_satusehat ON pasien_antrian.kodeAntrian = pasien_antrian_satusehat.kodeAntrian
                        INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
                        INNER JOIN pegawai dokter ON pasien_antrian.idDokter = dokter.idPegawai
                        INNER JOIN pegawai perawat ON pasien_antrian.idPerawat = perawat.idPegawai
                        INNER JOIN admisi ON pasien_antrian.idAdmisi = admisi.idAdmisi
                    WHERE 
                        pasien_antrian.kodeAntrian = ?
                    ',
                    [$kodeAntrian]
                );

                $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\Encounter;
                $syncHandler->setIdentifier($encounter['kodeAntrian'])
                    ->setStatus(\SatuSehat\FHIR\Interoperabilitas\EncounterStatus::ARRIVED)
                    ->setClass(
                        intval($encounter['idAdmisi']) === 2
                            // UNTUK ADMISI "Poli Umum Oncall" 
                            ? \SatuSehat\FHIR\Interoperabilitas\EncounterClass::ONCALL()
                            // UNTUK SELAIN ADMISI "Poli Umum Oncall" 
                            : \SatuSehat\FHIR\Interoperabilitas\EncounterClass::VISIT()
                    )
                    ->setSubject($encounter['uuidPasien'], $encounter['namaPasien'])
                    ->setParticipant(\SatuSehat\FHIR\Interoperabilitas\EncounterParticipant::DOCTOR($encounter['ihsDokter'] ?? '', $encounter['namaDokter']))
                    ->setParticipant(\SatuSehat\FHIR\Interoperabilitas\EncounterParticipant::NURSE($encounter['ihsPerawat'] ?? '', $encounter['namaPerawat']))
                    ->setPeriod(new DateTime())
                    ->setLocation($encounter['uuidAdmisi'] ?? '', $encounter['namaAdmisi'])
                    ->setArriveHistory(new DateTime($encounter['waktuKedatangan']))
                    ->setInProgressHistory(new DateTime($encounter['waktuPemeriksaan']))
                    ->setFinishHistory(new DateTime($encounter['waktuKepulangan']))
                    ->setServiceProvider();

                $encounterTempUUID = "urn:uuid:" . genUUIDv4();
                $bundleHandler->setResource(
                    $encounterTempUUID,
                    'Encounter',
                    $syncHandler->getRawPayload(),
                    new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                        'pasien_antrian',
                        'kodeUUIDSatuSehat',
                        'kodeAntrian',
                        $kodeAntrian
                    )
                );

                $conditions = statementWrapper(
                    DML_SELECT_ALL,
                    'SELECT 
                        pasien_icd_10_klinik.*,
                        icd_10.kode,
                        icd_10.kodeSatuSehat,
                        icd_10.diagnosis 
                    FROM 
                        pasien_icd_10_klinik
                        INNER JOIN icd_10 ON pasien_icd_10_klinik.idICD10 = icd_10.idICD10 
                    WHERE 
                        pasien_icd_10_klinik.kodeAntrian = ?
                    ORDER BY 
                        pasien_icd_10_klinik.jenisUrutan',
                    [$kodeAntrian]
                );

                $allergies = statementWrapper(
                    DML_SELECT,
                    'SELECT 
                        COALESCE(alergi_pemeriksaan.code, alergi_antrian.code) as code,
                        COALESCE(alergi_pemeriksaan.deskripsi, alergi_antrian.deskripsi) as deskripsi
                    FROM 
                        pasien_pemeriksaan_klinik_satusehat 
                        INNER JOIN pasien_antrian_satusehat ON pasien_pemeriksaan_klinik_satusehat.kodeAntrian = pasien_antrian_satusehat.kodeAntrian
                        INNER JOIN alergi alergi_antrian ON pasien_antrian_satusehat.kodeAlergi = alergi_antrian.code
                        INNER JOIN alergi alergi_pemeriksaan ON pasien_pemeriksaan_klinik_satusehat.kodeAlergi = alergi_pemeriksaan.code
                    WHERE 
                        pasien_pemeriksaan_klinik_satusehat.kodeAntrian = ?',
                    [$kodeAntrian]
                );

                $observation = statementWrapper(
                    DML_SELECT,
                    'SELECT 
                        pasien_pemeriksaan_klinik.*,
                        pasien_pemeriksaan_klinik_satusehat.*
                    FROM 
                        pasien_pemeriksaan_klinik 
                        INNER JOIN pasien_pemeriksaan_klinik_satusehat ON pasien_pemeriksaan_klinik.kodeAntrian = pasien_pemeriksaan_klinik_satusehat.kodeAntrian 
                    WHERE 
                        pasien_pemeriksaan_klinik.kodeAntrian = ?',
                    [$kodeAntrian]
                );

                $medications = statementWrapper(
                    DML_SELECT_ALL,
                    'SELECT 
                    pasien_obat_klinik.*,
                    pasien_obat_klinik_satusehat.*,
                    obat.*,
                    obat_satusehat.*,
                    icd_10.*
                FROM 
                    pasien_obat_klinik
                    INNER JOIN pasien_obat_klinik_satusehat ON pasien_obat_klinik.idObatKlinik = pasien_obat_klinik_satusehat.idObatKlinik
                    INNER JOIN obat ON pasien_obat_klinik.idObat = obat.idObat 
                    INNER JOIN obat_satusehat ON obat.kodeObat = obat_satusehat.kodeObat
                    INNER JOIN pasien_icd_10_klinik ON pasien_icd_10_klinik.idPasienICD10 = pasien_obat_klinik_satusehat.idDiagnosisAcuan
                    INNER JOIN icd_10 ON pasien_icd_10_klinik.idICD10 = icd_10.idICD10
                WHERE 
                    pasien_obat_klinik.kodeAntrian = ?
                ',
                    [$kodeAntrian]
                );

                if ($allergies) {

                    $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\AllergyIntolerance;
                    $syncHandler->setIdentifier("{$encounter['kodeAntrian']}-ALG")
                        ->setClinicalStatus()
                        ->setVerificationStatus()
                        ->setCategory(\SatuSehat\FHIR\Interoperabilitas\AllergyIntoleranceCategory::constant($observation['kategoriAlergi'] ?? ''))
                        ->setCode(new \SatuSehat\FHIR\Interoperabilitas\AllergyIntoleranceCode($observation['kodeAlergi'] ?? '', $observation['deskripsi'] ?? ''), $observation['alergiObat'] ?? '')
                        ->setSubject($encounter['uuidPasien'] ?? '', $encounter['namaPasien'])
                        ->setEncounter($encounterTempUUID, "Kunjungan {$encounter['namaPasien']} tanggal " . ubahTanggalIndo($encounter['tanggalPendaftaran'], false))
                        ->setRecordedDate(new DateTime($encounter['waktuPemeriksaan']))
                        ->setRecorder($encounter['ihsDokter'] ?? '');

                    $allergyIntoleranceTempUUID = "urn:uuid:" . genUUIDv4();
                    $bundleHandler->setResource(
                        $allergyIntoleranceTempUUID,
                        'AllergyIntolerance',
                        $syncHandler->getRawPayload(),
                        new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                            'pasien_pemeriksaan_klinik_satusehat',
                            'kodeUUIDSatuSehatAllergy',
                            'kodeAntrian',
                            $kodeAntrian
                        )
                    );
                }

                $observationsTempUUID = [];

                $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\ObservationNadi;
                $syncHandler->setCategory()
                    ->setStatus()
                    ->setCode()
                    ->setSubject($encounter['uuidPasien'] ?? '')
                    ->setPerformer($encounter['ihsDokter'] ?? '')
                    ->setPerformer($encounter['ihsPerawat'] ?? '')
                    ->setEncounter($encounterTempUUID, "Pemeriksaaan Fisik Nadi {$encounter['namaPasien']} tanggal " . ubahTanggalIndo($encounter['tanggalPendaftaran'], false))
                    ->setValueQuantity(floatval($observation['pulse']))
                    ->setIssued(new DateTime($encounter['waktuPemeriksaan']));

                $observationsTempUUID[] = $observationNadiTempUUID = "urn:uuid:" . genUUIDv4();
                $bundleHandler->setResource(
                    $observationNadiTempUUID,
                    'Observation',
                    $syncHandler->getRawPayload(),
                    new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                        'pasien_pemeriksaan_klinik_satusehat',
                        'kodeUUIDSatuSehatNadi',
                        'kodeAntrian',
                        $kodeAntrian
                    )
                );

                $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\ObservationPernafasan;
                $syncHandler->setCategory()
                    ->setStatus()
                    ->setCode()
                    ->setSubject($encounter['uuidPasien'] ?? '')
                    ->setPerformer($encounter['ihsPerawat'] ?? '')
                    ->setEncounter($encounterTempUUID, "Pemeriksaaan Fisik Pernafasan {$encounter['namaPasien']} tanggal " . ubahTanggalIndo($encounter['tanggalPendaftaran'], false))
                    ->setValueQuantity(floatval($observation['respiratoryRate']))
                    ->setIssued(new DateTime($encounter['waktuPemeriksaan']));

                $observationsTempUUID[] = $observationPernafasanTempUUID = "urn:uuid:" . genUUIDv4();
                $bundleHandler->setResource(
                    $observationPernafasanTempUUID,
                    'Observation',
                    $syncHandler->getRawPayload(),
                    new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                        'pasien_pemeriksaan_klinik_satusehat',
                        'kodeUUIDSatuSehatPernafasan',
                        'kodeAntrian',
                        $kodeAntrian
                    )
                );

                $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\ObservationSistol;
                $syncHandler->setCategory()
                    ->setStatus()
                    ->setCode()
                    ->setSubject($encounter['uuidPasien'] ?? '')
                    ->setPerformer($encounter['ihsPerawat'] ?? '')
                    ->setEncounter($encounterTempUUID, "Pemeriksaaan Fisik Tekanan Darah (Sistol) {$encounter['namaPasien']} tanggal " . ubahTanggalIndo($encounter['tanggalPendaftaran'], false))
                    ->setValueQuantity(floatval($observation['sistol'] ?? ''))
                    ->setIssued(new DateTime($encounter['waktuPemeriksaan']));

                $observationsTempUUID[] = $observationSistolTempUUID = "urn:uuid:" . genUUIDv4();
                $bundleHandler->setResource(
                    $observationSistolTempUUID,
                    'Observation',
                    $syncHandler->getRawPayload(),
                    new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                        'pasien_pemeriksaan_klinik_satusehat',
                        'kodeUUIDSatuSehatSistol',
                        'kodeAntrian',
                        $kodeAntrian
                    )
                );

                $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\ObservationDiastol;
                $syncHandler->setCategory()
                    ->setStatus()
                    ->setCode()
                    ->setSubject($encounter['uuidPasien'] ?? '')
                    ->setPerformer($encounter['ihsPerawat'] ?? '')
                    ->setEncounter($encounterTempUUID, "Pemeriksaaan Fisik Tekanan Darah (Diastol) {$encounter['namaPasien']} tanggal " . ubahTanggalIndo($encounter['tanggalPendaftaran'], false))
                    ->setValueQuantity(floatval($observation['diastol']))
                    ->setIssued(new DateTime($encounter['waktuPemeriksaan']));

                $observationsTempUUID[] = $observationDiastolTempUUID = "urn:uuid:" . genUUIDv4();
                $bundleHandler->setResource(
                    $observationDiastolTempUUID,
                    'Observation',
                    $syncHandler->getRawPayload(),
                    new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                        'pasien_pemeriksaan_klinik_satusehat',
                        'kodeUUIDSatuSehatDiastol',
                        'kodeAntrian',
                        $kodeAntrian
                    )
                );

                $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\ObservationSuhu;
                $syncHandler->setCategory()
                    ->setStatus()
                    ->setCode()
                    ->setSubject($encounter['uuidPasien'] ?? '')
                    ->setPerformer($encounter['ihsPerawat'] ?? '')
                    ->setEncounter($encounterTempUUID, "Pemeriksaaan Fisik Suhu {$encounter['namaPasien']} tanggal " . ubahTanggalIndo($encounter['tanggalPendaftaran'], false))
                    ->setValueQuantity(floatval($observation['temperature']))
                    ->setIssued(new DateTime($encounter['waktuPemeriksaan']));

                $observationsTempUUID[] = $observationSuhuTempUUID = "urn:uuid:" . genUUIDv4();
                $bundleHandler->setResource(
                    $observationSuhuTempUUID,
                    'Observation',
                    $syncHandler->getRawPayload(),
                    new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                        'pasien_pemeriksaan_klinik_satusehat',
                        'kodeUUIDSatuSehatSuhu',
                        'kodeAntrian',
                        $kodeAntrian
                    )
                );

                $conditionsTempUUID = [];
                foreach ($conditions as $condition) {
                    $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\Condition;

                    $syncHandler->setClinicalStatus()
                        ->setCategory()
                        ->setCode(new \SatuSehat\DataType\Other\ICD10($condition['kodeSatuSehat'], $condition['diagnosis']))
                        ->setSubject($encounter['uuidPasien'] ?? '', $encounter['namaPasien'] ?? '')
                        ->setEncounter($encounterTempUUID)
                        ->setOnsetDateTime(new DateTime($encounter['waktuPemeriksaan']))
                        ->setRecordedDate(new DateTime($encounter['waktuPemeriksaan']));

                    $conditionsTempUUID[] = $conditionTempUUID = "urn:uuid:" . genUUIDv4();
                    $bundleHandler->setResource(
                        $conditionTempUUID,
                        'Condition',
                        $syncHandler->getRawPayload(),
                        new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                            'pasien_icd_10_klinik',
                            'kodeUUIDSatuSehat',
                            'idPasienICD10',
                            $condition['idPasienICD10']
                        )
                    );
                }

                $medicationsTempUUID = [];
                foreach ($medications as $medication) {
                    $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\Medication;

                    $identifierMedicationForRequest = "{$encounter['kodeAntrian']}-{$medication['idObat']}-RQ";

                    $syncHandler->setMeta()
                        ->setIdentifier($identifierMedicationForRequest)
                        ->setCode(
                            new \SatuSehat\FHIR\Interoperabilitas\MedicationItem(
                                $medication['kodeKFA'] ?? '',
                                $medication['nama']
                            )
                        )
                        ->setForm(\SatuSehat\FHIR\Interoperabilitas\MedicationForm::invokeIfExist($medication['dosageForm'], 1))
                        ->setStatus()
                        ->setManufacturer(SATU_SEHAT_ORG_ID)
                        ->setIngridient(
                            new \SatuSehat\FHIR\Interoperabilitas\MedicationIngridient(
                                new \SatuSehat\FHIR\Interoperabilitas\MedicationItem(
                                    $medication['kodeKFA'] ?? '',
                                    $medication['nama']
                                ),
                                true,
                                new \SatuSehat\FHIR\Interoperabilitas\MedicationStrength(
                                    \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                        $medication['unitNumerator'] ?? '',
                                        floatval($medication['qtyNumerator'] ?? ''),
                                    ),
                                    (
                                        \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                            $medication['unitDenominator'] ?? '',
                                            floatval($medication['qtyDenominator'] ?? ''),
                                        )
                                    ) ?: (
                                        \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                                            $medication['unitDenominator'] ?? '',
                                            floatval($medication['qtyDenominator'] ?? ''),
                                        )
                                    ),
                                )
                            )
                        )
                        ->setMedicationType(\SatuSehat\FHIR\Interoperabilitas\MedicationType::invokeIfExist(
                            $medication['jenisRacikan'] ?? ''
                        ));

                    $medicationForRequestTempUUID = "urn:uuid:" . genUUIDv4();
                    $bundleHandler->setResource(
                        $medicationForRequestTempUUID,
                        'Medication',
                        $syncHandler->getRawPayload(),
                        new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                            'pasien_obat_klinik',
                            'kodeUUIDSatuSehatMedicationForRequest',
                            'idObatKlinik',
                            $medication['idObatKlinik']
                        )
                    );

                    $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\MedicationRequest;

                    $dosageForm = (
                        \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                            $medication['unitDenominator'],
                            floatval($medication['dosageQty']),
                        )
                    ) ?: (
                        \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                            $medication['unitDenominator'],
                            floatval($medication['dosageQty']),
                        )
                    );

                    $syncHandler->setIdentifier("{$identifierMedicationForRequest}", ["{$identifierMedicationForRequest}-{$medication['idObatKlinik']}"])
                        ->setCategory()
                        ->setStatus()
                        ->setIntent()
                        ->setPriority()
                        ->setMedicationReference($medicationForRequestTempUUID, $medication['nama'])
                        ->setSubject($encounter['uuidPasien'], $encounter['namaPasien'])
                        ->setEncounter($encounterTempUUID)
                        ->setRequester($encounter['ihsDokter'], $encounter['namaDokter'])
                        ->setReasonCode(new \SatuSehat\DataType\Other\ICD10(
                            $medication['kodeSatuSehat'],
                            $medication['diagnosis']
                        ))
                        ->setDosageInstruction(
                            $medication['dosageText'],
                            $medication['keteranganDosis'],
                            intval($medication['dosageRate']),
                            \SatuSehat\DataType\Other\Measurement::DAY(1),
                            \SatuSehat\FHIR\Interoperabilitas\MedicationRoute::ORAL(),
                            (
                                \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            ) ?: (
                                \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            )
                        )
                        ->setDispenseRequest(
                            \SatuSehat\DataType\Other\Measurement::DAY(floatval(0), 'days'),
                            (
                                \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            ) ?: (
                                \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            ),
                            \SatuSehat\DataType\Other\Measurement::DAY(floatval($medication['dosageDuration']), 'days'),
                        );

                    $medicationRequestTempUUID = "urn:uuid:" . genUUIDv4();
                    $bundleHandler->setResource(
                        $medicationRequestTempUUID,
                        'MedicationRequest',
                        $syncHandler->getRawPayload(),
                        new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                            'pasien_obat_klinik',
                            'kodeUUIDSatuSehatMedicationRequest',
                            'idObatKlinik',
                            $medication['idObatKlinik']
                        )
                    );

                    $identifierMedicationForDispense = "{$encounter['kodeAntrian']}-{$medication['idObat']}-DP";
                    $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\Medication;

                    $syncHandler->setMeta()
                        ->setIdentifier($identifierMedicationForDispense)
                        ->setCode(
                            new \SatuSehat\FHIR\Interoperabilitas\MedicationItem(
                                $medication['kodeKFA'],
                                $medication['nama']
                            )
                        )
                        ->setForm(\SatuSehat\FHIR\Interoperabilitas\MedicationForm::invokeIfExist($medication['dosageForm'], 1))
                        ->setStatus()
                        ->setManufacturer(SATU_SEHAT_ORG_ID)
                        ->setIngridient(
                            new \SatuSehat\FHIR\Interoperabilitas\MedicationIngridient(
                                new \SatuSehat\FHIR\Interoperabilitas\MedicationItem(
                                    $medication['kodeKFA'],
                                    $medication['nama']
                                ),
                                true,
                                new \SatuSehat\FHIR\Interoperabilitas\MedicationStrength(
                                    \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                        $medication['unitNumerator'],
                                        floatval($medication['qtyNumerator']),
                                    ),
                                    (
                                        \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                            $medication['unitDenominator'],
                                            floatval($medication['qtyDenominator']),
                                        )
                                    ) ?: (
                                        \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                                            $medication['unitDenominator'],
                                            floatval($medication['qtyDenominator']),
                                        )
                                    ),
                                )
                            )
                        )
                        ->setMedicationType(\SatuSehat\FHIR\Interoperabilitas\MedicationType::invokeIfExist(
                            $medication['jenisRacikan']
                        ));


                    $medicationForDispenseTempUUID = "urn:uuid:" . genUUIDv4();
                    $bundleHandler->setResource(
                        $medicationForDispenseTempUUID,
                        'Medication',
                        $syncHandler->getRawPayload(),
                        new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                            'pasien_obat_klinik',
                            'kodeUUIDSatuSehatMedicationForDispense',
                            'idObatKlinik',
                            $medication['idObatKlinik']
                        )
                    );

                    $syncHandler = new \SatuSehat\FHIR\Interoperabilitas\MedicationDispense;

                    $syncHandler->setIdentifier("{$identifierMedicationForRequest}", ["{$identifierMedicationForRequest}-{$medication['idObatKlinik']}"])
                        ->setCategory()
                        ->setStatus()
                        ->setMedicationReference($medicationForDispenseTempUUID, $medication['nama'])
                        ->setSubject($encounter['uuidPasien'], $encounter['namaPasien'])
                        ->setContext($encounterTempUUID)
                        ->setAuthorizingPrescription($medicationRequestTempUUID)
                        ->setQuantity(
                            (
                                \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            ) ?: (
                                \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            )
                        )
                        ->setDaysSupply(\SatuSehat\DataType\Other\Measurement::DAY(floatval($medication['dosageDuration']), 'Day'))
                        ->setWhenPrepared(new DateTime($encounter['waktuKepulangan']))
                        ->setWhenHandedOver(new DateTime($encounter['waktuKepulangan']))
                        ->setDosageInstruction(
                            $medication['dosageText'],
                            $medication['keteranganDosis'],
                            intval($medication['dosageRate']),
                            \SatuSehat\DataType\Other\Measurement::DAY(1),
                            \SatuSehat\FHIR\Interoperabilitas\MedicationRoute::ORAL(),
                            (
                                \SatuSehat\DataType\Other\Measurement::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            ) ?: (
                                \SatuSehat\DataType\Other\DrugForm::invokeIfExist(
                                    $medication['unitDenominator'],
                                    floatval($medication['dosageQty']),
                                    $dosageForm->code
                                )
                            )
                        );

                    $medicationRequestTempUUID = "urn:uuid:" . genUUIDv4();
                    $bundleHandler->setResource(
                        $medicationRequestTempUUID,
                        'MedicationDispense',
                        $syncHandler->getRawPayload(),
                        new \SatuSehat\FHIR\Interoperabilitas\BundleConfigForUpdate(
                            'pasien_obat_klinik',
                            'kodeUUIDSatuSehatMedicationDispense',
                            'idObatKlinik',
                            $medication['idObatKlinik']
                        )
                    );
                }

                ['code' => $code, 'data' => $response] = $bundleHandler->create();

                switch (intval($code)) {
                    case 200:
                    case 201:
                        $status = true;
                        $pesan = 'Proses Sinkronisasi Satu Sehat Berhasil';

                        $configurations = $bundleHandler->getConfigForUpdate();
                        $update = [];

                        foreach ($response['entry'] as $index => $resource) {
                            ['resourceType' => $resourceType, 'config' => $config] = $configurations[$index];
                            $detail = $resource['response'];

                            if ($resourceType === $detail['resourceType']) {
                                $update["{$resourceType}|{$config->col_target}"] = statementWrapper(
                                    DML_UPDATE,
                                    "UPDATE
                                    {$config->table}
                                SET
                                    {$config->col_target} = ?
                                WHERE
                                    {$config->col_id} = ?
                                ",
                                    [$detail['resourceID'], $config->id]
                                );
                            }
                        }

                        break;

                    default:
                        $status = false;
                        $pesan = 'Proses Sinkronisasi Satu Sehat Gagal';
                        break;
                }
            } else {
                $status = false;
                $pesan = 'Pasien WNA Tidak Dapat Disinkronkan Ke Satu Sehat';
            }
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Sinkronisasi';
    } finally {
        $data = compact('status', 'pesan');
    }
}

echo json_encode($data);
