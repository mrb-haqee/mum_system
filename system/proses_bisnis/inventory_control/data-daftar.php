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

    $parameter = [];
    $execute = ['Aktif'];

    if ($flag === 'daftar') {
        $parameter['nama'] = '';
    } else if ($flag === 'cari') {
        $parameter['nama'] = 'AND atasNama = ?';
        $execute[] = "%$search%";
    }

    [$tglAwal, $tglAkhir] = explode(' - ', $periode);

    $exRentang = explode(' - ', $periode);

    if (isset($exRentang[0]) && isset($exRentang[1])) {
        [$tanggalAwal, $tanggalAkhir] = $exRentang;

        $query_parameter = [];

        if ($tipeInventory === 'Barang') {
            $query_parameter['switch'] = 'AND 0=1';
            $query_parameter['switch_barang'] = 'AND 1=1';
        } else {
            $query_parameter['switch'] = 'AND 1=1';
            $query_parameter['switch_barang'] = 'AND 0=1';
        }

        $SQL['Stock Awal']['barang'] = [
            "date_col" => "stock_awal.tanggal",
            "query" => "SELECT
                stock_awal.qty as inflow,
                0 as outflow,
                'Stock Awal' as type,
                CONCAT_WS('-', stock_awal.idInventory, stock_awal.tipeInventory, stock_awal.satuan) as identifier
            FROM
                stock_awal
            WHERE
                1 = 1
                AND stock_awal.tipeInventory = 'barang'
            ",
            "execute" => []
        ];

        $SQL['Penerimaan Stock']['barang'] = [
            "date_col" => "stock_sr.tanggal",
            "query" => "SELECT
                stock_sr_detail.qty as inflow,
                0 as outflow,
                'Penerimaan Barang' as type,
                CONCAT_WS('-', stock_sr_detail.idInventory, stock_sr_detail.tipeInventory, stock_sr_detail.satuan) as identifier
            FROM
                stock_sr_detail
                INNER JOIN stock_sr ON stock_sr_detail.kodeSR = stock_sr.kodeSR 
            WHERE
                stock_sr_detail.statusItem IN ('Diterima')
                AND stock_sr_detail.tipeInventory = 'barang'
            ",
            "execute" => []
        ];



        $SQL['Master Data Stock']['barang'] = [
            "date_col" => "",
            "query" => "SELECT
                'barang' as tipeInventory,
                barang.idBarang as idInventory,
                barang.kodeBarang as kodeInventory,
                barang.namaBarang,
                barang.satuanBarang,
                CONCAT_WS('-', barang.idBarang, 'barang', barang.satuanBarang) as identifier
            FROM
                barang
            WHERE
                statusBarang = 'Aktif'
            ",
            "execute" => []
        ];

        $stocks = statementWrapper(
            DML_SELECT_ALL,
            "SELECT
                stock_awal.stockAwal,
                data_pembelian.stockPembelian,
                inventory.*
            FROM
                (
                    {$SQL['Master Data Stock'][$tipeInventory]["query"]}
                ) inventory
                LEFT JOIN (
                    SELECT
                        SUM(inflow) - SUM(outflow) as stockAwal,
                        identifier
                    FROM
                    (
                        (
                            {$SQL['Stock Awal'][$tipeInventory]["query"]}
                            AND DATE({$SQL['Stock Awal'][$tipeInventory]['date_col']}) < ?
                        )
                        UNION ALL
                        (
                            {$SQL['Penerimaan Stock'][$tipeInventory]["query"]}
                            AND DATE({$SQL['Penerimaan Stock'][$tipeInventory]['date_col']}) < ?
                        )
                        
                    ) stock_awal
                    GROUP BY identifier
                ) stock_awal ON inventory.identifier = stock_awal.identifier
                LEFT JOIN (
                    SELECT
                        SUM(inflow) as stockPembelian,
                        identifier
                    FROM
                    (
                        (
                            {$SQL['Stock Awal'][$tipeInventory]["query"]}
                            AND ({$SQL['Stock Awal'][$tipeInventory]['date_col']} BETWEEN ? AND ?)
                        )
                        UNION ALL
                        (
                            {$SQL['Penerimaan Stock'][$tipeInventory]["query"]}
                            AND ({$SQL['Penerimaan Stock'][$tipeInventory]['date_col']} BETWEEN ? AND ?)
                        )
                    ) data_pembelian
                    GROUP BY identifier
                ) data_pembelian ON inventory.identifier = data_pembelian.identifier
            ",
            array_merge(
                // STOCK AWAL
                $SQL['Master Data Stock'][$tipeInventory]["execute"],
                $SQL['Stock Awal'][$tipeInventory]["execute"],
                [$tanggalAwal],
                $SQL['Penerimaan Stock'][$tipeInventory]["execute"],
                [$tanggalAwal],
                // PEMBELIAN
                $SQL['Stock Awal'][$tipeInventory]["execute"],
                [$tanggalAwal, $tanggalAkhir],
                $SQL['Penerimaan Stock'][$tipeInventory]["execute"],
                [$tanggalAwal, $tanggalAkhir],
            )
        );
    }
?>
    <table class="table table-hover table-bordered">
        <thead class="alert alert-danger">
            <tr>
                <th class="text-center align-middle" style="width: 5%;">NO</th>
                <th class="text-center align-middle" style="width: 10%;">INVENTORY</th>
                <th class="align-middle text-center" style="width: 15%;">STOCK AWAL</th>
                <th class="align-middle text-center" style="width: 15%;">PEMBELIAN</th>
                <th class="align-middle text-center" style="width: 10%;">PENGGUNAAN</th>
                <th class="align-middle text-center" style="width: 10%;">SISA</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($stocks):
                $n = 1;
                foreach ($stocks as $row) {
                    $sisa = floatval($row['stockAwal']) + floatval($row['stockPembelian'] ?? 0) - floatval($row['stockPenggunaan'] ?? 0);
            ?>
                    <tr>
                        <td class="text-center"><?= $n ?></td>
                        <td class="text-center">
                            <span class="d-block font-weight-bold"><?= $row['namaBarang'] ?> </span>
                            <span class="text-muted font-weight-bold"><?= $tipeInventory . " - {$row['satuanBarang']}"; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['stockAwal'] ?? 0); ?></span>
                            <span class="text-muted font-weight-bold">Stock Awal</span>
                        </td>
                        <td class="text-center">
                            <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['stockPembelian'] ?? 0); ?></span>
                            <span class="text-muted font-weight-bold">Pembelian</span>
                        </td>
                        <td class="text-center">
                            <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['stockPenggunaan'] ?? 0); ?></span>
                            <span class="text-muted font-weight-bold">Penggunaan</span>
                        </td>
                        <td class="text-center">
                            <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($sisa); ?></span>
                            <span class="text-muted font-weight-bold">Sisa</span>
                        </td>
                    </tr>
                <?php
                    $n++;
                }
            else: ?>
                <tr>
                    <td colspan="9" class="text-center table-active"><i class="fas fa-info-circle pr-5" style="font-size: 1rem;"></i><strong class="text-muted">DATA NOT FOUND</strong></td>
                </tr>
            <?php endif ?>
        </tbody>
    </table>
<?php
}
?>