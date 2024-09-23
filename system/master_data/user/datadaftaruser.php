<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsialert.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT idUser from user where idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//MENGECEK APAKAH USER INI BERHAK MENGAKSES MENU INI
$sqlCekMenu = $db->prepare('SELECT * from user_detail 
  inner join menu_sub 
  on menu_sub.idSubMenu = user_detail.idSubMenu
  where user_detail.idUser = ?
  and namaFolder = ?');
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__)
]);
$dataCekMenu = $sqlCekMenu->fetch();

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu) {
    alertSessionExpForm();
} else {
    extract($_POST, EXTR_SKIP);

    $execute = ['Aktif'];
    $parameter = [];

    if ($flag === 'daftar') {
        $parameter['nama'] = '';
    } else if ($flag === 'cari') {
        $parameter['nama'] = 'AND user.userName = ?';
        $execute[] = "%$kataKunciData%";
    }

    $dataUser = selectStatement(
        "SELECT
            user.*,
            pegawai.namaPegawai
        FROM
            user
            INNER JOIN pegawai ON user.idPegawai = pegawai.idPegawai
        WHERE
            user.statusUser = ?
            {$parameter['nama']}
        ",
        $execute
    );

?>
    <table class="table table-hover">
        <thead class="alert alert-primary th-user">
            <th style="width: 5%;">NO</th>
            <th style="width: 10%;">AKSI</th>
            <th>NAMA PEGAWAI</th>
            <th>USERNAME</th>
        </thead>
        <tbody>
            <?php
            $n = 1;
            foreach ($dataUser as $row) {
                $query = rawurlencode(enkripsi(http_build_query([
                    'idUser' => $row['idUser'],
                ]), secretKey()));
            ?>
                <tr>
                    <td><?= $n ?></td>
                    <td>
                        <button type="button" id="dropdownMenuButton" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-cogs"></i>
                        </button>
                        <div class="dropdown-menu menu-aksi" aria-labelledby="dropdownMenuButton">
                            <button type="button" class="btn btn-warning btn-sm tombol-dropdown" onclick="getFormUser('<?= $row['idUser'] ?>')">
                                <i class="fas fa-edit"></i> <strong>EDIT</strong>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm tombol-dropdown" onclick="konfirmasiBatalUser('<?= $row['idUser'] ?>', '<?= $tokenCSRF ?>')">
                                <i class="fas fa-trash"></i> <strong>HAPUS</strong>
                            </button>
                            <a href="detail/?param=<?= $query ?>" class="btn btn-primary btn-sm tombol-dropdown">
                                <i class="fas fa-user-cog"></i> <strong>AKSES MENU</strong>
                            </a>
                            <!-- <button type="button" class="btn btn-info btn-sm tombol-dropdown-last" onclick="daftarAksesMenu('<?= $row['idUser'] ?>')">
                                <i class="fa fa-list"></i> <strong>AKSES MENU</strong>
                            </button> -->
                        </div>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['namaPegawai'] ?></span>
                        <span class="text-muted font-weight-bold">Nama Pegawai</span>
                    </td>
                    <td>
                        <span class="d-block font-weight-bold"><?= $row['userName'] ?></span>
                        <span class="text-muted font-weight-bold">User Name</span>
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