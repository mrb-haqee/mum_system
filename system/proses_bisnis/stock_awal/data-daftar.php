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

    if ($flagData === 'daftar') {
        $parameter['nama'] = '';
    } else if ($flagData == "cari") {
        $parameter['nama'] = 'AND barang.namaBarang LIKE ?';
        $execute[] = "%$kataKunciData%";
    }

    $dataStockAwal = statementWrapper(
        DML_SELECT_ALL,
        "SELECT 
            * 
        FROM 
            stock_awal
            INNER JOIN barang ON barang.idBarang = stock_awal.idInventory
        where 
            stock_awal.tipeInventory = 'barang' AND barang.statusBarang = ?
            {$parameter['nama']}
        ",
        $execute
    );

?>
    <table class="table table-bordered">
        <thead class="alert alert-danger">
            <tr>
                <th class="align-middle text-center" rowspan="2" style="width: 5%;">NO</th>
                <th class="align-middle text-center" rowspan="2" style="width: 10%;">AKSI</th>
                <th class="align-middle text-center" rowspan="2">NAMA BARANG</th>
                <th class="align-middle text-center" rowspan="2">TANGGAL</th>
                <th class="align-middle text-center" colspan="2">DETAIL</th>
            </tr>
            <tr>
                <td class="align-middle text-center">QTY</td>
                <td class="align-middle text-center">SATUAN BARANG</td>
            </tr>
        </thead>
        <tbody>
            <?php
            $n = 1;
            foreach ($dataStockAwal as $row) {
            ?>
                <tr>
                    <td><?= $n ?></td>
                    <td>
                        <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                            <button type="button" class="btn btn-warning btn-sm tombol-dropdown tombolEdit" onclick="getFormStockAwalBarang('<?= $row['idStockAwal'] ?>')">
                                <i class="fa fa-edit"></i> <strong>EDIT</strong>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown" onclick="konfirmasiBatal('<?= $row['idStockAwal'] ?>', '<?= $tokenCSRF ?>')">
                                <i class="fa fa-trash"></i> <strong>HAPUS</strong>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['namaBarang'] ?></span>
                        <span class="text-muted font-weight-bold"><?= $row['jenisBarang'] ?></span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= ubahTanggalIndo($row['tanggal']) ?></span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['qty']) ?></span>
                        <span class="text-muted font-weight-bold">Qty</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['satuan'] ?></span>
                        <span class="text-muted font-weight-bold">Satuan</span>
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
?>