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
		AND (
            menu_sub.namaFolder = ?
            OR 
            menu_sub.namaFolder = ?
        )
	'
);
$sqlCekMenu->execute([
    $idUserAsli,
    getMenuDirectory(BASE_URL_PHP, __DIR__),
    'rekam_medis'
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

    $dataAntrian = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_antrian WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_fee_oncall WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    if ($dataUpdate) {
        $referrer = $dataUpdate['referrer'];
    }

    switch ($referrer) {
        case 'Hotel':
?>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="idHotel">Hotel</label>
                    <select name="idHotel" id="idHotel" class="form-control selectpicker">
                        <option value="">Pilih Hotel</option>
                        <?php
                        $opsi = statementWrapper(
                            DML_SELECT_ALL,
                            'SELECT * FROM hotel WHERE statusHotel = ?',
                            ['Aktif']
                        );

                        foreach ($opsi as $index => $hotel) {
                            $selected = selected($hotel['idHotel'], $dataUpdate['idHotel'] ?? '');
                        ?>
                            <option value="<?= $hotel['idHotel'] ?>" <?= $selected; ?>><?= $hotel['namaHotel']; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="nominalFee">Nominal</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="text" class="form-control" name="nominal" id="nominal" value="<?= ubahToRupiahDesimal($dataUpdate['nominal'] ?? 0) ?>" data-format-rupiah="active">
                    </div>
                </div>
            </div>
        <?php
            break;
        case 'Guide':
        ?>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="nama">Nama</label>
                    <input type="text" class="form-control" name="nama" id="nama" value="<?= $dataUpdate['nama'] ?? '' ?>" placeholder="Nama Guide">
                </div>
                <div class="form-group col-md-6">
                    <label for="nominal">Nominal</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="text" class="form-control" name="nominal" id="nominal" value="<?= ubahToRupiahDesimal($dataUpdate['nominal'] ?? 0) ?>" data-format-rupiah="active">
                    </div>
                </div>
            </div>
<?php
            break;
        case 'Perorangan':

            break;
    }
}
