<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasicurrency.php";
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

    $sessionKlinik = dekripsi($_SESSION['enc_idKlinik'], secretKey());

    extract($_POST, EXTR_SKIP);

    $exRentang = explode(' - ', $periode);

    if (isset($exRentang[0]) && isset($exRentang[1])) {

        [$tanggalAwal, $tanggalAkhir] = $exRentang;

        $execute = array_merge($exRentang, $exRentang);

        $data = statementWrapper(
            DML_SELECT_ALL,
            "SELECT
                *
            FROM
            (
                (
                    SELECT
                        COALESCE(kode_akunting.namaAkun, CONCAT('UNKNOWN ACCOUNT ', pemasukan_pengeluaran_lain.kodeAkun)) as namaAkun,
                        pemasukan_pengeluaran_lain.*,
                        CONCAT(tujuan_transfer.vendor, ' / ', tujuan_transfer.atasNama) as nama
                    FROM
                        pemasukan_pengeluaran_lain
                        LEFT JOIN kode_akunting ON pemasukan_pengeluaran_lain.kodeAkun = kode_akunting.kodeAkun
                        INNER JOIN tujuan_transfer ON pemasukan_pengeluaran_lain.idBank = tujuan_transfer.idTujuanTransfer
                    WHERE
                        ( pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ? )
                        AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
                )
                UNION ALL
                (
                    SELECT
                        COALESCE(kode_akunting.namaAkun, CONCAT('UNKNOWN ACCOUNT ', pemasukan_pengeluaran_lain.kodeAkun)) as namaAkun,
                        pemasukan_pengeluaran_lain.*,
                        petty_cash.namaPettyCash as nama
                    FROM
                        pemasukan_pengeluaran_lain
                        LEFT JOIN kode_akunting ON pemasukan_pengeluaran_lain.kodeAkun = kode_akunting.kodeAkun
                        INNER JOIN petty_cash ON pemasukan_pengeluaran_lain.idBank = petty_cash.idPettyCash
                    WHERE
                        ( pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ? )
                        AND pemasukan_pengeluaran_lain.jenisRekening = 'Petty Cash'
                )
            ) data_transaksi
            ORDER BY tanggal
            ",
            $execute
        );

?>
        <table class="table table-hover table-bordered">
            <thead class="alert alert-danger">
                <tr>
                    <th class="text-center align-middle" style="width: 5%;">NO</th>
                    <th class="text-center align-middle" style="width: 10%;">AKSI</th>
                    <th class="text-center align-middle">TIPE</th>
                    <th class="text-center align-middle">TANGGAL</th>
                    <th class="text-center align-middle">BANK</th>
                    <th class="text-center align-middle">AKUN</th>
                    <th class="text-center align-middle">NOMINAL</th>
                    <th class="text-center align-middle">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $n = 1;
                foreach ($data as $row) {
                    $detailCurrency = getCurrencyList($row['currency']);
                ?>
                    <tr>
                        <td class="text-center align-middle"><?= $n ?> </td>
                        <td class="text-center">
                            <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-cogs"></i>
                            </button>
                            <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormPemasukanPengeluaranLain('<?= $row['idPemasukanPengeluaranLain'] ?>')">
                                    <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm tombol-dropdown" onclick="deletePemasukanPengeluaranLain('<?= $row['idPemasukanPengeluaranLain'] ?>', '<?= $tokenCSRF ?>')">
                                    <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['tipe'] ?></span>
                            <span class="text-muted font-weight-bold">Tipe</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= ubahTanggalIndo($row['tanggal']) ?></span>
                            <span class="text-muted font-weight-bold">Tanggal</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['nama'] ?></span>
                            <span class="text-muted font-weight-bold"><?= $row['jenisRekening']; ?></span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['namaAkun'] ?></span>
                            <span class="text-muted font-weight-bold">Akun</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($row['nominal'], 2) ?></span>
                            <span class="text-muted font-weight-bold">Nominal</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['keterangan'] ?></span>
                            <span class="text-muted font-weight-bold">Keterangan</span>
                        </td>
                    </tr>
                <?php
                    $n++;
                }
                ?>
            </tbody>
        </table>
<?php
    }
}
?>