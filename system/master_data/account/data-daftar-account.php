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


    $hakAkses = json_decode($dataCekMenu['hakAkses'], true);

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
    } else if ($flag == "cari") {
        $parameter['nama'] = 'AND namaAccount LIKE ?';
        $execute[] = "%$kataKunciData%";
    }

    $dataAccount = statementWrapper(
        DML_SELECT_ALL,
        "SELECT 
            * 
        FROM 
            account 
        where 
            statusAccount = ?
            {$parameter['nama']}
        ",
        $execute
    );
?>
    <table class="table table-hover">
        <thead class="alert alert-danger">
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 15%;">AKSI</th>
                <th style="width: 20%;">KODE ACCOUNT</th>
                <th style="width: 60%;">NAMA</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $n = 1;
            foreach ($dataAccount as $row) {
                $query = rawurlencode(enkripsi(http_build_query([
                    'kodeAccount' => $row['kodeAccount'],
                ]), secretKey()));
                $daftaraSubAccount = statementWrapper(
                    DML_SELECT_ALL,
                    'SELECT * FROM sub_account WHERE statusSubAccount = ? AND kodeAccount = ?',
                    ['Aktif', $row['kodeAccount']]
                );
            ?>
                <tr>
                    <td><?= $n ?></td>
                    <td>
                        <?php if ($daftaraSubAccount) { ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="getSubAccount('<?= $row['kode'] ?>')"><i class="fas fa-list-alt pr-0"></i></button>
                        <?php } else {
                        ?>
                            <button type="button" class="btn btn-secondary btn-sm" disabled><i class="fas fa-list-alt pr-0"></i></button>
                        <?php
                        } ?>
                        <button type="button" id="dropdownMenuButton" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                            <a href="detail/?param=<?= $query ?>" class="btn btn-warning btn-sm tombol-dropdown">
                                <i class="fa fa-edit"></i> <strong>EDIT</strong>
                            </a>

                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown-last" onclick="konfirmasiHapusAccount('<?= $row['idAccount'] ?>', '<?= $tokenCSRF ?>')">
                                <i class="fa fa-trash"></i> <strong>HAPUS</strong>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['kode'] ?></span>
                        <span class="text-muted font-weight-bold">Kode</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['namaAccount'] ?></span>
                        <span class="text-muted font-weight-bold">Nama</span>
                    </td>

                </tr>
                <?php
                $m = 1;
                foreach ($daftaraSubAccount as $rowSub) {
                ?>
                    <tr data-id="<?= $row['kode'] ?>" class="d-none">

                        <td><?= $n . "." . $m ?></td>
                        <td>

                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $rowSub['kodeSub'] ?></span>
                            <span class="text-muted font-weight-bold">Kode</span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $rowSub['namaSubAccount'] ?></span>
                            <span class="text-muted font-weight-bold">Nama</span>
                        </td>
                    </tr>

            <?php
                    $m++;
                }
                $n++;
            }
            ?>
        </tbody>
    </table>
<?php
}
?>