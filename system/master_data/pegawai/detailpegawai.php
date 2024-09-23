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

    extract($_POST, EXTR_SKIP);

    $detailInformasi = statementWrapper(
        DML_SELECT,
        'SELECT 
            *
        FROM 
            pegawai
        WHERE 
            idPegawai = ?
        ',
        [$idPegawai]
    );


    $detailPenghasilan = statementWrapper(
        DML_SELECT_ALL,
        'SELECT
            SUM(nominal) AS jumlah,
            jenisPenghasilan AS judul
        FROM
            pegawai_penghasilan
        WHERE
            kodePegawai = ? 
            AND status = ?
        GROUP BY
            jenisPenghasilan;
        ',
        [$detailInformasi['kodePegawai'], 'Aktif']
    );

    $dataJumlah = array_column($detailPenghasilan, 'jumlah');
    $dataJudul = array_column($detailPenghasilan, 'judul');

    $combine = array_combine($dataJudul, $dataJumlah);

?>
    <div class="row">
        <div class="col-lg-12">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td colspan="4"> <i class="fas fa-user-tie pr-5" style="font-size: 1rem;"></i><strong> INFORMASI PEGAWAI </strong></td>
                    </tr>
                    <tr>
                        <td style="width: 15%;">NIK PEGAWAI</td>
                        <td style="width:35%"><?= $detailInformasi['NIKPegawai']; ?></td>
                        <td style="width: 15%;">JENIS KELAMIN</td>
                        <td style="width: 35%;"><?= $detailInformasi['jenisKelamin']; ?></td>
                    </tr>
                    <tr>
                        <td> TEMPAT LAHIR</td>
                        <td><?= $detailInformasi['tempatLahir']; ?></td>
                        <td> TANGGAL LAHIR </td>
                        <td><?= ubahTanggalIndo($detailInformasi['ttlPegawai']); ?></td>
                    </tr>
                    <tr>
                        <td> AGAMA </td>
                        <td><?= $detailInformasi['agama']; ?></td>
                        <td> EMAIL </td>
                        <td><?= $detailInformasi['email']; ?></td>
                    </tr>
                    <tr>
                        <td> No Rekening </td>
                        <td><?= $detailInformasi['noRekening']; ?></td>
                        <td> NPWP </td>
                        <td><?= $detailInformasi['npwp']; ?></td>
                    </tr>
                    <tr>
                        <td> PENDIDIKAN </td>
                        <td><?= $detailInformasi['pendidikan']; ?></td>
                        <td> NO HP</td>
                        <td><?= $detailInformasi['hpPegawai']; ?></td>
                    </tr>
                    <tr>
                        <td> ALAMAT </td>
                        <td><?= $detailInformasi['alamatPegawai']; ?></td>
                        <td>TGL MULAI KERJA</td>
                        <td><?= $detailInformasi['tglMulaiKerja']?></td>
                    </tr>
                    <tr> 
                        <td>KETERANGAN</td>
                        <td colspan="3"><?=$detailInformasi['keterangan']?></td>
                    </tr> 
                </tbody>
            </table>
        </div>
    </div>

    <!-- <div class="row">
        
        <div class="col-lg-12">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td colspan="2"><i class="fas fa-money-bill-wave pr-5" style="font-size: 1rem;"></i><strong> DETAIL PENGHASILAN</strong></td>
                    </tr>
                    <?php
                    if ($detailPenghasilan) {
                    ?>
                        <tr>
                            <td>GAJI POKOK</td>
                            <td>Rp <?= ubahToRupiahDesimal($combine['Gaji Pokok'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <td>TOTAL TUNJANGAN</td>
                            <td>
                                Rp <?= ubahToRupiahDesimal($combine['Tunjangan'] ?? 0); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>TOTAL POTONGAN</td>
                            <td>
                                Rp <?= ubahToRupiahDesimal($combine['Potongan'] ?? 0); ?>
                            </td>
                        </tr>
                    <?php
                    } else {
                    ?>
                        <tr>
                            <td colspan="2" class="table-active"></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div> -->
<?php
}
