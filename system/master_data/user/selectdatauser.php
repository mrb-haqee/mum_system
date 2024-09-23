<?php
include_once '../../../library/konfigurasiurl.php';
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

    $sqlUpdate  = $db->prepare(
        'SELECT 
            user.*,
            user_kasir.idKasir
        FROM 
            user
            LEFT JOIN user_kasir ON user.idUser = user_kasir.idUser
        WHERE 
            user.idUser = ?'
    );
    $sqlUpdate->execute([$idUserAccount]);
    $dataUpdate = $sqlUpdate->fetch();

    if ($dataUpdate) {
        $idPegawaiInput = $dataUpdate['idPegawai'];
    }

    $dataPegawai = selectStatement(
        'SELECT
            *
        FROM
            pegawai
        WHERE
            idPegawai = ?
        ',
        [$idPegawaiInput],
        'fetch'
    );

    if (intval($dataPegawai['idJabatan']) === 2) {
?>
        <div class="form-group col-md-6">
            <label><i class="fas fa-id-badge"></i> NAMA LENGKAP</label>
            <input type="text" id="namaLengkap" name="namaLengkap" class="form-control" placeholder="Nama Lengkap" value="<?= ($dataUpdate['namaLengkap'] ?? $dataPegawai['namaPegawai']) ?? '' ?>">
        </div>
        <div class="form-group col-md-6">
            <label for="idKasir"><i class="fas fa-user-tie"></i> KASIR</label>
            <select name="idKasir" id="idKasir" class="form-control selectpicker">
                <option value="">Pilih Kasir</option>
                <?php
                $opsi = selectStatement('SELECT * FROM cabang_kasir WHERE statusKasir = ? AND idCabang = ?', ['oke', $dataPegawai['idCabang']]);
                foreach ($opsi as $index => $kasir) {
                    $selected = selected($kasir['idKasir'], $dataUpdate['idKasir'] ?? '');
                ?>
                    <option value="<?= $kasir['idKasir'] ?>" <?= $selected; ?>><?= $kasir['namaKasir']; ?></option>
                <?php
                }
                ?>
            </select>
        </div>
    <?php
    } else {
    ?>
        <div class="form-group col-md-12">
            <label><i class="fas fa-id-badge"></i> NAMA LENGKAP</label>
            <input type="text" id="namaLengkap" name="namaLengkap" class="form-control" placeholder="Nama Lengkap" value="<?= ($dataUpdate['namaLengkap'] ?? $dataPegawai['namaPegawai']) ?? '' ?>">
        </div>
<?php
    }
}
?>