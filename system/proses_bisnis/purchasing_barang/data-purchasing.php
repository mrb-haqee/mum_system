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

    $exRentang = explode(' - ', $rentang);

    if (isset($exRentang[0]) && isset($exRentang[1])) {
        [$tanggalAwal, $tanggalAkhir] = $exRentang;

        $dataPurchasing = statementWrapper(
            DML_SELECT_ALL,
            "SELECT 
                purchasing.*, vendor.nama as namaVendor, SUM(purchasing_detail.subTotal) as total_purchasing
            FROM 
                purchasing
                INNER JOIN vendor on purchasing.kodeVendor = vendor.kodeVendor
                LEFT JOIN purchasing_detail ON purchasing.kodePurchasing = purchasing_detail.kodePurchasing
            WHERE 
                purchasing.statusPersetujuan = ? AND (tanggalPurchasing BETWEEN ? AND ?) AND statusPurchasing = ?
            GROUP BY purchasing.idPurchasing
            ",
            [
                $statusPersetujuan,
                $tanggalAwal,
                $tanggalAkhir,
                'Aktif'
            ]
        );

        $bedge = ['Pending' => 'warning', 'Approve' => 'success', 'Reject' => 'danger'];
?>
        <div style="overflow-x: auto">
            <table class="table table-hover">
                <thead class="alert alert-danger">
                    <tr>
                        <th class="text-center" style="width: 5%;">NO</th>
                        <th class="text-center" style="width: 10%;">AKSI</th>
                        <th style="width: 30%;">TANGGAL PURCHASING</th>
                        <th style="width: 20%;">VENDOR</th>
                        <th style="width: 15%;">STATUS</th>
                        <th style="width: 30%;">TOTAL COST</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($dataPurchasing) {
                        $n = 1;
                        foreach ($dataPurchasing as $row) {
                            $query = encryptURLParam([
                                'kode' => $row['kodePurchasing'],
                            ]);
                    ?>
                            <tr>
                                <td>
                                    <strong><?= $n ?></strong>
                                </td>
                                <td class="text-center">
                                    <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                                        <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormPurchasing('<?= $row['kodePurchasing'] ?>'), EditBtn()">
                                            <i class="fas fa-edit"></i> <strong>EDIT</strong>
                                        </button>
                                        <?php if ($row['statusPersetujuan'] !== 'Approve'): ?>
                                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="deletePurchasing('<?= $row['kodePurchasing'] ?>', '<?= $tokenCSRF ?>')">
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
                                    <span class="d-block font-weight-bold"><?= tanggalTerbilang($row['tanggalPurchasing']) ?></span>
                                    <span class="text-muted"><strong><?= $row['kodePurchasing'] ?></strong></span>
                                </td>
                                <td>
                                    <span class="d-block font-weight-bold"><?= $row['namaVendor'] ?></span>
                                </td>
                                <td>
                                    <span class="text-muted d-block mb-1"><span class="badge badge-<?= $bedge[$row['statusPersetujuan']] ?>"><?= $row['statusPersetujuan'] ?></span< /span>
                                </td>
                                <td>
                                    <span class="d-block font-weight-bold">Rp. <?= ubahToRp($row['total_purchasing']) ?? 0 ?></span>
                                </td>
                            </tr>
                        <?php
                            $n++;
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="bg-secondary-o-80 text-center font-weight-bolder">
                                TIDAK ADA DATA PURCHASING
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
<?php
    }
}
?>