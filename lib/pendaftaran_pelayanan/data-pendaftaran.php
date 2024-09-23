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

    $aksesEditable = selectStatement(
        'SELECT aksesEditable FROM user WHERE idUser = ?',
        [$idUserAsli],
        'fetch'
    )['aksesEditable'];

    extract($_POST, EXTR_SKIP);

    $exRentang = explode(' - ', $rentang);

    $sessionKlinik = dekripsi($enc_idKlinik, secretKey());

    if (isset($exRentang[0]) && isset($exRentang[1])) {
        [$tanggalAwal, $tanggalAkhir] = $exRentang;

        $execute = [
            'Aktif',
            $sessionKlinik,
            $idAdmisi
        ];

        if ($flag === 'daftar') {
            $parameter['search'] = 'AND (pasien_antrian.tanggalPendaftaran BETWEEN ? AND ?)';
            $execute = array_merge($execute, [$tanggalAwal, $tanggalAkhir]);
        } else {
            $parameter['search'] = 'AND pasien.namaPasien LIKE ?';
            $execute[] = "%$search%";
        }

        if ($_SESSION['view_cash_payment'] === '__HIDE__') {
            $parameter['hide_cash'] = 'AND check_cash_payment.kodeAntrian IS NULL';
        } else {
            $parameter['hide_cash'] = '';
        }

        $dataPendaftaran = statementWrapper(
            DML_SELECT_ALL,
            "SELECT 
                pasien_antrian.*, 
                pasien.*,
                pasien_antrian.kodeUUIDSatuSehat as uuidAntrian,
                pasien.kodeUUIDSatuSehat as uuidPasien,
                dokter.namaPegawai as namaDokter, 
                perawat.namaPegawai as namaPerawat 
            FROM 
                pasien_antrian 
                INNER JOIN pasien ON pasien_antrian.kodeRM = pasien.kodeRM
                INNER JOIN pegawai dokter ON pasien_antrian.idDokter = dokter.idPegawai
                INNER JOIN pegawai perawat ON pasien_antrian.idPerawat = perawat.idPegawai
                LEFT JOIN (
                    SELECT
                        COUNT(pasien_deposit.idPasienDeposit) as count,
                        pasien_deposit.kodeAntrian
                    FROM
                        pasien_deposit
                    WHERE
                        pasien_deposit.statusDeposit = 'Aktif'
                        AND pasien_deposit.metodePembayaran = 'Tunai'
                        GROUP BY pasien_deposit.kodeAntrian
                ) check_cash_payment ON pasien_antrian.kodeAntrian = check_cash_payment.kodeAntrian 
            WHERE 
                pasien_antrian.statusAntrian = ?
                AND pasien_antrian.idKlinik = ?
                AND pasien_antrian.idAdmisi = ?
                {$parameter['hide_cash']}
                {$parameter['search']}
            ",
            $execute
        );
?>
        <div style="overflow-x: auto">
            <table class="table table-hover">
                <thead class="alert alert-primary">
                    <tr>
                        <th class="text-center" style="width: 5%;">NO</th>
                        <th class="text-center" style="width: 15%;">AKSI</th>
                        <th style="width: 20%;">NAMA PASIEN</th>
                        <th style="width: 15%;">TANGGAL PENDAFTARAN</th>
                        <th style="width: 30%;">TENAGA MEDIS</th>
                        <th style="width: 15%;">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $n = 1;
                    foreach ($dataPendaftaran as $row) {
                        $query = encryptURLParam([
                            'kodeAntrian' => $row['kodeAntrian'],
                            'kodeRM' => $row['kodeRM'],
                            'idAdmisi' => $idAdmisi
                        ]);

                        $detailSurat = statementWrapper(
                            DML_SELECT_ALL,
                            'SELECT * FROM pasien_surat WHERE kodeAntrian = ?',
                            [$row['kodeAntrian']]
                        );
                    ?>
                        <tr>
                            <td>
                                <button class="btn btn-info btn-sm d-block w-100" type="button">
                                    <strong><?= $row['nomorAntrian'] ?></strong>
                                </button>
                            </td>
                            <td class="text-center">
                                <?php
                                $NIKSatusehat = validateNIK($row['noIdentitas']);
                                
                                if (in_array($row['kebangsaan'], ['Lokal', 'Domestik']) && $row['statusPelayanan'] === 'Sudah Dilayani' && $row['statusPembayaran'] === 'Sudah Bayar' && $NIKSatusehat == true) {
                                    if (is_null($row['uuidAntrian'])) {
                                ?>
                                        <button class="btn btn-success btn-sm" title="Sinkronisasi Satu Sehat" type="button" onclick="syncSatuSehatBundle($(this),'<?= $row['kodeAntrian'] ?>','<?= $tokenCSRF ?>')"><i class="fas fa-sync-alt pr-0"></i></button>
                                    <?php
                                    } else {
                                    ?>
                                        <button class="btn btn-info btn-sm" title="Sinkronisasi Satu Sehat" type="button" onclick="syncSatuSehatBundle($(this),'<?= $row['kodeAntrian'] ?>','<?= $tokenCSRF ?>')"><i class="fas fa-sync-alt pr-0"></i></button>
                                <?php
                                    }
                                }
                                ?>
                                <button type="button" id="dropdownMenuButton" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-cogs"></i>
                                </button>
                                <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                    <a href="detail_pendaftaran/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown">
                                        <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                    </a>
                                    <a href="detail_pembayaran/?param=<?= $query ?>" class="btn btn-primary btn-sm tombol-dropdown">
                                        <i class="fas fa-cash-register"></i> <strong>BILLING</strong>
                                    </a>
                                    <?php
                                    $listSurat = [
                                        'Surat Sehat' => 'print_surat_sehat',
                                        'Surat Sakit' => 'print_surat_sakit',
                                        'Surat Rujukan' => 'print_surat_rujukan',
                                        'Surat Rapid Test Antigen' => 'print_surat_antigen',
                                        'Surat MCU' => 'print_surat_mcu',
                                        'Surat Narkoba' => 'print_surat_narkoba',
                                        'Surat Radiologi' => 'print_surat_radiologi',
                                        'Surat Sehat Internship' => 'print_surat_sehat_internship',
                                        'Surat Pengantar Lab' => 'print_surat_lab',
                                    ];

                                    foreach ($detailSurat as $index => $surat) {
                                        if ($row['statusPelayanan'] === 'Sudah Dilayani' || $surat['jenisSurat'] === 'Surat Rapid Test Antigen') {
                                            if (in_array($surat['jenisSurat'], array_keys($listSurat))) {
                                    ?>
                                                <a href="<?= $listSurat[$surat['jenisSurat']] ?>/?param=<?= $query ?>" class="btn btn-success btn-sm tombol-dropdown" target="_blank">
                                                    <i class="fa fa-notes-medical"></i> <strong class="text-uppercase"><?= $surat['jenisSurat']; ?></strong>
                                                </a>
                                        <?php
                                            }
                                        }
                                    }

                                    if ($row['statusPelayanan'] === 'Sudah Dilayani') {
                                        ?>
                                        <a href="print_resep/?param=<?= $query ?>" class="btn btn-info btn-sm tombol-dropdown" target="_blank">
                                            <i class="fa fa-file-signature"></i> <strong>RESEP ELEKTRONIK</strong>
                                        </a>
                                        <a href="print_medical_certificate/?param=<?= $query ?>" class="btn btn-success btn-sm tombol-dropdown" target="_blank">
                                            <i class="fa fa-file-signature"></i> <strong>RESUME MEDIS</strong>
                                        </a>
                                        <a href="print_medical_certificate_gigi/?param=<?= $query ?>" class="btn btn-primary btn-sm tombol-dropdown" target="_blank">
                                            <i class="fa fa-file-signature"></i> <strong>RESUME MEDIS GIGI</strong>
                                        </a>
                                    <?php
                                    }

                                    if ($row['statusPelayanan'] === 'Belum Dilayani' || $row['statusPembayaran'] === 'Belum Bayar') {
                                    ?>
                                        <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiBatalPendaftaran('<?= $row['kodeAntrian'] ?>', '<?= $tokenCSRF ?>')">
                                            <i class="fas fa-ban"></i> <strong>VOID</strong>
                                        </button>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="d-block font-weight-bold"><?= wordwrap($row['namaPasien'], 50, '<br>', true) ?></span>
                                <span class="text-muted"><strong>Alergi</strong> :<br> <?= wordwrap($row['alergi'], 50, '<br>', true) ?></span>
                            </td>
                            <td>
                                <span class="d-block font-weight-bold"><?= tanggalTerbilang($row['tanggalPendaftaran']) ?></span>
                                <span class="text-muted"><strong><?= $row['kodeAntrian'] ?></strong></span>
                            </td>
                            <td>
                                <span class="text-muted d-block mb-1"><span class="badge badge-success mr-2" style="width: 50px;">DOC</span><span><?= wordwrap($row['namaDokter'], 50, '<br>', true) ?></span> </span>
                                <span class="text-muted d-block"><span class="badge badge-danger mr-2" style="width: 50px;">NRS</span><span><?= wordwrap($row['namaPerawat'], 50, '<br>', true) ?></span> </span>
                            </td>
                            <td>
                                <?= wordwrap($row['statusPelayanan'], 50, '<br>', true) ?><br>
                                <?= wordwrap($row['statusPembayaran'], 50, '<br>', true) ?>
                            </td>
                        </tr>
                    <?php
                        $n++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
<?php
    }
}
?>