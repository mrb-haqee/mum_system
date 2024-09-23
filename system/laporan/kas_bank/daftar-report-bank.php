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

    $exRentang = explode(' - ', $periode);

    if (isset($exRentang[0]) && isset($exRentang[1])) {

        function array_flat_recursive(...$list)
        {
            $output = [];

            foreach ($list as $value) {
                if (is_array($value)) {
                    $output = array_merge($output, array_flat_recursive(...$value));
                } else {
                    $output[] = $value;
                }
            }

            return $output;
        };

        [$tanggalAwal, $tanggalAkhir] = $exRentang;

        // Debet Pemasukan lain
        $debet['Pemasukan Lain'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pemasukan_pengeluaran_lain.nominal) AS debet
            FROM
                pemasukan_pengeluaran_lain
                INNER JOIN sub_account ON pemasukan_pengeluaran_lain.idSubAccount = sub_account.idSubAccount
            WHERE
                pemasukan_pengeluaran_lain.tanggal < ?
                AND pemasukan_pengeluaran_lain.idRekening = ?
                AND pemasukan_pengeluaran_lain.tipe = 'Pemasukan Lain'
                AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
            ",
            [$tanggalAwal, $idRekening]
        );



        // Kredit Pengeluaran lain
        $kredit['Pengeluaran Lain'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(nominal) AS kredit
            FROM
                pemasukan_pengeluaran_lain
                LEFT JOIN sub_account ON pemasukan_pengeluaran_lain.idSubAccount = sub_account.idSubAccount
            WHERE
                pemasukan_pengeluaran_lain.tanggal < ?
                AND pemasukan_pengeluaran_lain.idRekening = ?
                AND pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
                AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
            ",
            [$tanggalAwal, $idRekening]
        );


        $saldo = array_sum(array_column($debet, 'debet')) - array_sum(array_column($kredit, 'kredit'));

        $data = statementWrapper(
            DML_SELECT_ALL,
            "SELECT
                *
            FROM
                (
                    (
                        SELECT
                            pemasukan_pengeluaran_lain.tanggal as tanggal,
                            CONCAT('Pemasukan Lain / ', COALESCE(account.namaAccount, 'null'), ' / ',COALESCE(sub_account.namaSubAccount, 'null')) as uraian,
                            pemasukan_pengeluaran_lain.nominal as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            pemasukan_pengeluaran_lain.keterangan,
                            pemasukan_pengeluaran_lain.timeStamp as timeStampInput
                        FROM
                            pemasukan_pengeluaran_lain
                            INNER JOIN sub_account ON pemasukan_pengeluaran_lain.idSubAccount = sub_account.idSubAccount
                            LEFT JOIN account ON account.kodeAccount = sub_account.kodeAccount
                        WHERE
                            (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
                            AND pemasukan_pengeluaran_lain.idRekening = ?
                            AND pemasukan_pengeluaran_lain.tipe = 'Pemasukan Lain'
                            AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
                    )
                    UNION ALL
                    (
                        SELECT
                            pemasukan_pengeluaran_lain.tanggal as tanggal,
                            CONCAT('Pengeluaran Lain / ', COALESCE(account.namaAccount, 'null'), ' / ',COALESCE(sub_account.namaSubAccount, 'null')) as uraian,
                            0 as debet,
                            pemasukan_pengeluaran_lain.nominal as kredit,
                            'kurang' as jenis,
                            pemasukan_pengeluaran_lain.keterangan as keterangan,
                            pemasukan_pengeluaran_lain.timeStamp as timeStampInput
                        FROM
                            pemasukan_pengeluaran_lain
                            LEFT JOIN sub_account ON pemasukan_pengeluaran_lain.idSubAccount = sub_account.idSubAccount
                            LEFT JOIN account ON account.kodeAccount = sub_account.kodeAccount
                        WHERE
                            (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
                            AND pemasukan_pengeluaran_lain.idRekening = ?
                            AND pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
                            AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
                    )    
                ) detail_tunai
                ORDER BY tanggal, timeStampInput
        ",
            array_flat_recursive(
                array_fill(
                    0,
                    2,
                    [
                        $tanggalAwal,
                        $tanggalAkhir,
                        $idRekening,
                    ]
                ),
            )
        );


?>
        <table class="table table-hover table-bordered">
            <thead class="alert alert-danger">
                <tr>
                    <th class="text-center align-middle" style="width: 5%;">NO</th>
                    <th class="text-center align-middle">TANGGAL</th>
                    <th class="text-center align-middle">URAIAN</th>
                    <th class="text-center align-middle">DEBET</th>
                    <th class="text-center align-middle">KREDIT</th>
                    <th class="text-center align-middle">SALDO</th>
                    <th class="text-center align-middle">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5"><strong>SALDO AWAL</strong></td>
                    <td style="text-align: right;"><?= ubahToRupiahDesimal($saldo, 2) ?></td>
                    <td></td>
                </tr>
                <?php
                $n = 1;

                $totalDebet = empty($data) ? 0 : array_sum(array_column($data, 'debet'));
                $totalKredit = empty($data) ? 0 : array_sum(array_column($data, 'kredit'));

                foreach ($data as $row) {
                    $saldo += floatval($row['debet']) - floatval($row['kredit']);
                ?>
                    <tr>
                        <td class="text-center align-middle"><?= $n ?> </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= ubahTanggalIndo($row['tanggal']) ?></span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['uraian'] ?></span>
                        </td>
                        <td>
                            <?php
                            if ($row['jenis'] === 'kurang') {
                            ?>
                                <span class="d-block font-weight-bold">-</span>
                            <?php
                            } else {
                            ?>
                                <span class="d-block font-weight-bold" style="text-align: right;"><?= ubahToRupiahDesimal($row['debet'], 2) ?></span>
                            <?php
                            } ?>
                        </td>
                        <td>
                            <?php
                            if ($row['jenis'] === 'tambah') {
                            ?>
                                <span class="d-block font-weight-bold">-</span>
                            <?php
                            } else {
                            ?>
                                <span class="d-block font-weight-bold" style="text-align: right;"><?= ubahToRupiahDesimal($row['kredit'], 2) ?></span>
                            <?php
                            } ?>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold" style="text-align: right;"><?= ubahToRupiahDesimal($saldo, 2) ?></span>
                        </td>
                        <td><?= $row['keterangan'] ?></td>
                    </tr>
                <?php
                    $n++;
                }
                ?>
                <tr>
                    <td colspan="3"> <strong>TOTAL</strong> </td>
                    <td style="text-align: right;"><?= ubahToRupiahDesimal($totalDebet, 2) ?></td>
                    <td style="text-align: right;"><?= ubahToRupiahDesimal($totalKredit, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
<?php
    }
}
?>