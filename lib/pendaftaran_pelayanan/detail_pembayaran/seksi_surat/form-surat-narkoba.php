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

    function selectNarkoba($label, $name){
      $opsi = [
        'Negative (-)',
        'Positive (+)'
      ];

      ?>
      <label><i class="fa fa-hospital"></i> <?= $label ?></label>
      <select name="<?= $name ?>" id="<?= $name ?>" class="form-control selectpicker" data-live-search="true" style="width: 100%;">
          <?php
          foreach ($opsi as $row) {
          ?>
            <option value="<?= $row ?>"> <?= $row ?> </option>
          <?php
          }
          ?>
      </select>
      <?php
    }

?>
    <div class="form-group">
        <label><i class="fa fa-file-medical"></i> Nomor Surat Narkoba</label>
        <input type="text" name="kodeSurat" class="form-control" value="SMC/NB/<?= date('Y/m') ?>">
    </div>
    <div class="form-row">
      <div class="form-group col-sm-4">
        <?php
        selectNarkoba('Morphine', 'morphine');
        ?>
      </div>
      <div class="form-group col-sm-4">
        <?php
        selectNarkoba('Amphetamine', 'amphetamine');
        ?>
      </div>
      <div class="form-group col-sm-4">
        <?php
        selectNarkoba('Tetrahydrocannabinol', 'tetrahydrocannabinol');
        ?>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-sm-4">
        <?php
        selectNarkoba('Benzodiazepine', 'benzodiazepine');
        ?>
      </div>
      <div class="form-group col-sm-4">
        <?php
        selectNarkoba('Methamphetamine', 'methamphetamine');
        ?>
      </div>
      <div class="form-group col-sm-4">
        <?php
        selectNarkoba('Cocaine', 'cocaine');
        ?>
      </div>
    </div>
    <div class="form-group">
      <label><i class="fa fa-calendar-check"></i> Keperluan</label>
      <input type="text" name="keperluan" class="form-control" placeholder="Keperluan">
    </div>
<?php
}
?>