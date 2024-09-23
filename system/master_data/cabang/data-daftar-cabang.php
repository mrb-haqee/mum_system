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
        $parameter['nama'] = 'AND nama = ?';
        $execute[] = "%$search%";
    }

    $dataCabang = statementWrapper(
        DML_SELECT_ALL,
        "SELECT * FROM cabang WHERE status = ? {$parameter['nama']}",
        $execute
    );
?>
    <table class="table table-hover">
        <thead class="alert alert-danger">
            <tr>
                <th>NO</th>
                <th>AKSI</th>
                <th>Cabang</th>
                <th>ALAMAT</th>
                <th>NO. TELP</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $n = 1;
            foreach ($dataCabang as $row) {
                $query = rawurlencode(enkripsi(http_build_query([
                    'kode' => $row['kodeCabang'],
                ]), secretKey()));
            ?>
                <tr>
                    <td><?= $n ?></td>
                    <td>
                        <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                            <a href="detail_cabang/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown">
                                <i class="fa fa-edit"></i> <strong>EDIT</strong>
                            </a>

                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiBatalCabang('<?= $row['idCabang'] ?>', '<?= $tokenCSRF ?>')">
                                <i class="fa fa-trash"></i> <strong>HAPUS</strong>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['nama'] ?></span>
                        <span class="text-muted font-weight-bold">Nama Cabang</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['alamat'] ?></span>
                        <span class="text-muted font-weight-bold">Alamat Cabang</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['noTelp'] ?></span>
                        <span class="text-muted font-weight-bold">No. Telp Cabang</span>
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