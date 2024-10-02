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
    [$tanggalAwal, $tanggalAkhir] = $exRentang;



    $debet['Pemasukan Lain'] = statementWrapper(
        DML_SELECT,
        "SELECT
            SUM(nominal) as debet
        FROM
            pemasukan_pengeluaran_lain
            INNER JOIN petty_cash ON pemasukan_pengeluaran_lain.idRekening = petty_cash.idPettyCash 
        WHERE
            (pemasukan_pengeluaran_lain.tanggal < ?)
            AND pemasukan_pengeluaran_lain.tipe = ?
            AND pemasukan_pengeluaran_lain.jenisRekening = ?
        ",
        [$tanggalAwal, 'Pemasukan Lain', 'Petty Cash']
    );


    $debet['Testing'] = [
        'debet' => '3000'
    ];


    $kredit['Pengeluaran Lain'] = statementWrapper(
        DML_SELECT,
        "SELECT
            SUM(nominal) as kredit
        FROM
            pemasukan_pengeluaran_lain
            INNER JOIN petty_cash ON pemasukan_pengeluaran_lain.idRekening = petty_cash.idPettyCash 
        WHERE
            (pemasukan_pengeluaran_lain.tanggal < ?)
            AND pemasukan_pengeluaran_lain.tipe = ?
            AND pemasukan_pengeluaran_lain.jenisRekening = ?
        ",
        [$tanggalAwal, 'Pengeluaran Lain', 'Petty Cash']
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
                        biaya.idBiaya as id,
                        biaya.tglBiaya as tanggal,
                        CONCAT(sub_account.namaSubAccount, ' / ', biaya_detail.namaItem) as uraian,
                        0 as debet,
                        biaya.grandTotal as kredit,
                        'kurangBiaya' as jenis,
                        biaya.timeStamp as timestamp
                    FROM
                        biaya 
                        INNER JOIN biaya_detail ON biaya_detail.kodeBiaya = biaya.kodeBiaya
                        INNER JOIN sub_account ON biaya.idSubAccount = sub_account.idSubAccount
                    WHERE
                        (biaya.tglBiaya BETWEEN ? AND ?)
                    GROUP BY biaya_detail.kodeBiaya
                )
                UNION ALL
                (
                    SELECT
                        pemasukan_pengeluaran_lain.idPemasukanPengeluaranLain as id,
                        pemasukan_pengeluaran_lain.tanggal as tanggal,
                        pemasukan_pengeluaran_lain.keterangan as uraian,
                        0 as debet,
                        pemasukan_pengeluaran_lain.nominal as kredit,
                        'kurang' as jenis,
                        pemasukan_pengeluaran_lain.timeStamp as timestamp
                    FROM
                        pemasukan_pengeluaran_lain
                        INNER JOIN petty_cash ON pemasukan_pengeluaran_lain.idRekening = petty_cash.idPettyCash 
                    WHERE
                        (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
                        AND pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
                        AND pemasukan_pengeluaran_lain.jenisRekening = 'Petty Cash'
                )
                UNION ALL
                (
                    SELECT
                        pemasukan_pengeluaran_lain.idPemasukanPengeluaranLain as id,
                        pemasukan_pengeluaran_lain.tanggal as tanggal,
                        pemasukan_pengeluaran_lain.keterangan as uraian,
                        pemasukan_pengeluaran_lain.nominal as debet,
                        0 as kredit,
                        'tambah' as jenis,
                        pemasukan_pengeluaran_lain.timeStamp as timestamp
                    FROM
                        pemasukan_pengeluaran_lain
                        INNER JOIN petty_cash ON pemasukan_pengeluaran_lain.idRekening = petty_cash.idPettyCash 
                    WHERE
                        (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
                        AND pemasukan_pengeluaran_lain.tipe = 'Pemasukan Lain'
                        AND pemasukan_pengeluaran_lain.jenisRekening = 'Petty Cash'
                )
            ) detail_tunai
            ORDER BY tanggal, timestamp
        ",
        [
            $tanggalAwal,
            $tanggalAkhir,
            $tanggalAwal,
            $tanggalAkhir,
            $tanggalAwal,
            $tanggalAkhir,
        ]
    );


?> <table class="table table-hover table-bordered">
        <thead class="alert alert-danger">
            <tr>
                <th class="text-center align-middle" style="width: 5%;">NO</th>
                <th>AKSI</th>
                <th class="text-center align-middle">TANGGAL</th>
                <th class="text-center align-middle">URAIAN</th>
                <th class="text-center align-middle">DEBET</th>
                <th class="text-center align-middle">KREDIT</th>
                <th class="text-center align-middle">SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6"><strong>SALDO AWAL</strong></td>
                <td class="text-right"> Rp <?= ubahToRp($saldo) ?></td>
            </tr>
            <?php
            $n = 1;

            $saldoAwal = $saldo;
            $total = [
                'debet' => 0,
                'kredit' => 0,
            ];

            foreach ($data as $row) {
                $saldoTabel = $saldoAwal + $row['debet'] - $row['kredit'];
            ?>
                <tr>
                    <td class="text-center align-middle"><?= $n ?> </td>
                    <td>
                        <?php
                        if ($row['jenis'] === 'kurangBiaya') {
                        ?>
                            <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-cogs"></i>
                            </button>
                            <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormBiaya('<?= $row['id'] ?>')">
                                    <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deleteBiaya('<?= $row['id'] ?>', '<?= $tokenCSRF ?>')">
                                    <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                </button>
                            </div>
                        <?php
                        }
                        ?>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= ubahTanggalIndo($row['tanggal']) ?></span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['uraian'] ?></span>
                    </td>
                    <td class="text-right">
                        <?php if ($row['jenis'] === 'kurang') : ?>
                            <span>-</span>
                        <?php else: ?>
                            <span>Rp <?= ubahToRupiahDesimal($row['debet'] ?? '0') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php if ($row['jenis'] === 'tambah') : ?>
                            <span>-</span>
                        <?php else: ?>
                            <span>Rp <?= ubahToRupiahDesimal($row['kredit'] ?? '0') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($saldoTabel ?? '0') ?></span>
                    </td>
                </tr>
            <?php
                $n++;

                $total['debet'] += $row['debet'];
                $total['kredit'] += $row['kredit'];
            }
            ?>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right">Rp <?= ubahToRupiahDesimal($total['debet']); ?></td>
                <td class="text-right">- Rp <?= ubahToRupiahDesimal($total['kredit']); ?></td>
                <td class="text-right">Rp <?= ubahToRupiahDesimal($total['debet'] - $total['kredit']); ?></td>
            </tr>
        </tbody>
    </table>
<?php
}
?>