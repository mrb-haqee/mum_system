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

    $execute = array_merge($execute, explode(' - ', $periode));

    $filters = [
        'vendor.kodeVendor' => $kodeVendor,
        'statusPersetujuan' => $statusPersetujuan,
        'statusPO' => $statusPO
    ];

    foreach ($filters as $key => $value) {
        if ($value !== 'Semua') {
            $parameter[$key] = "AND $key = ?";
            $execute[] = $value;
        }
    }

    $dataStockPO = statementWrapper(
        DML_SELECT_ALL,
        "SELECT stock_po.*, vendor.nama as namaVendor, stock_po_pembayaran.grandTotal 
        FROM stock_po 
        INNER JOIN stock_po_pembayaran ON stock_po_pembayaran.kodePO = stock_po.kodePO
        INNER JOIN vendor ON vendor.kodeVendor = stock_po.kodeVendor
        WHERE 
            statusFinalisasi = ?  
            AND ( stock_po.tanggal BETWEEN ? AND ? ) {$parameter['nama']} 
            " . implode('', $parameter) . " ORDER BY stock_po.tanggal DESC",
        $execute
    );

?>
    <table class="table table-hover table-bordered">
        <thead class="alert alert-danger">
            <tr>
                <th rowspan="2" class="text-center align-middle" style="width: 5%;">NO</th>
                <th rowspan="2" class="text-center align-middle" style="width: 10%;">AKSI</th>
                <th rowspan="2" class="align-middle text-center" style="width: 15%;">TANGGAL PURCHASING</th>
                <th rowspan="2" class="align-middle text-center" style="width: 15%;">NOMOR SP</th>
                <th rowspan="2" class="align-middle text-center" style="width: 10%;">VENDOR</th>
                <th rowspan="2" class="align-middle text-center" style="width: 10%;">PEMBAYARAN</th>
                <th colspan="2" class="align-middle text-center" style="width: 10%;">STATUS</th>
                <th rowspan="2" class="align-middle text-center" style="width: 20%;">TOTAL COST</th>
            </tr>
            <tr>
                <th class="align-middle text-center">Approval</th>
                <th class="align-middle text-center">PO</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($dataStockPO):
                $Labels = [
                    'Reject' => 'danger',
                    'Approve' => 'success',
                    'Pending' => 'warning',
                    'Diproses' => 'primary',
                    'Diterima' => 'info',
                    'Aktif' => 'secondary'
                ];
                $n = 1;
                foreach ($dataStockPO as $row) {
                    $query = rawurlencode(enkripsi(http_build_query([
                        'kode' => $row['kodePO'],
                    ]), secretKey()));
            ?>
                    <tr>
                        <td><?= $n ?></td>
                        <td>
                            <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-cogs"></i>
                            </button>
                            <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                <a href="detail/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown">
                                    <i class="fa fa-edit"></i> <strong>EDIT</strong>
                                </a>

                                <?php if ($row['statusPersetujuan'] !== 'Approve'): ?>
                                    <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiBatalStockPO('<?= $row['idPO'] ?>', '<?= $tokenCSRF ?>')">
                                        <i class="fas fa-trash"></i> <strong>DELETE</strong>
                                    </button>
                                <?php else: ?>
                                    <a href="print/?param=<?= $query ?>" target="_blank" class="btn btn-success btn-sm tombol-dropdown-last">
                                        <i class="fas fa-file-pdf"></i> <strong>EXPORT PDF</strong>
                                    </a>
                                <?php endif ?>
                            </div>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['tanggal'] ?></span>
                            <span class="text-muted font-weight-bold">Tanggal</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['nomorSP'] ?></span>
                            <span class="text-muted font-weight-bold">Nomor SP</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['namaVendor'] ?></span>
                            <span class="text-muted font-weight-bold">Vendor</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['metodeBayar'] ?></span>
                            <span class="text-muted font-weight-bold">Metode Bayar</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold text-center"><span class="w-100 label label-<?= $Labels[$row['statusPersetujuan']] ?> label-pill label-inline mr-2"><?= $row['statusPersetujuan'] ?></span></span>
                            <span class="text-muted font-weight-bold text-center">Approval</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold text-center"><span class="w-100 label label-<?= $Labels[$row['statusPO']] ?> label-pill label-inline mr-2"><?= $row['statusPO'] ?></span></span>
                            <span class="text-muted font-weight-bold text-center">Order</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold text-right">Rp. <?= ubahToRp($row['grandTotal']) ?></span>
                            <span class="d-block text-muted font-weight-bold text-right">Grand Total</span>
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