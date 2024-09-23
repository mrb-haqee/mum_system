<?php
include_once '../../../../../library/konfigurasi.php';
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
?>
    <div class="form-group">
        <label><i class="fa fa-file-medical"></i> Nomor Surat Sehat</label>
        <input type="text" name="kodeSurat" class="form-control" value="SMC/RAD/<?= date('Y/m') ?>">
    </div>
    <div class="form-group">
        <label for="idDokter"><i class="fas fa-user-md"></i> Dokter Radiologi</label><br>
        <select name="idDokterRadiologi" id="idDokterRadiologi" class="form-control selectpicker" data-live-search="true">
            <option value=""> Pilih Dokter </option>
            <?php
            $sqlDokter = $db->prepare(
                'SELECT 
                    pegawai.* 
                FROM 
                    pegawai 
                    INNER JOIN pegawai_jaspel ON pegawai.kodePegawai = pegawai_jaspel.kodePegawai
                WHERE 
                    pegawai.statusPegawai = ?
                    AND pegawai_jaspel.idJabatan IN (1)
                    '
            );
            $sqlDokter->execute([
                'Aktif'
            ]);
            $dataDokter = $sqlDokter->fetchAll();

            foreach ($dataDokter as $row) {
                ?>
                <option value="<?= $row['idPegawai'] ?>"> <?= $row['namaPegawai'] ?> </option>
                <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group">
        <label><i class="fa fa-calendar-check"></i> Hasil Bacaan</label>
        <textarea name="hasilPemeriksaan" id="hasilPemeriksaan" class="form-control" data-editor="active" placeholder="Hasil Bacaan"></textarea>
    </div>
<?php
}
?>