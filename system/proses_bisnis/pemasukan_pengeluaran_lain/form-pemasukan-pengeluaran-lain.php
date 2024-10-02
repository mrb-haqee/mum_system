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
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";

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

    // $idKlinikAsal = dekripsi($_SESSION['enc_idKlinik'], secretKey());

    $dataUpdate = statementWrapper(
        DML_SELECT,
        'SELECT 
            *
        FROM 
            pemasukan_pengeluaran_lain
        WHERE 
            idPemasukanPengeluaranLain = ?',
        [$idPemasukanPengeluaranLain]
    );

    if (isset($param)) {
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);
        $kodePemasukanPengeluaranLain = $result['kodePemasukanPengeluaranLain'];
    } else {
        $kodePemasukanPengeluaranLain = nomorUrut($db, 'pemasukan_pengeluaran_lain', $idUserAsli);
    }

    if ($dataUpdate) {
        $flag = 'update';
    } else {
        $flag = 'tambah';
    }

?>
    <form id="formPemasukanPengeluaranLain">
        <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
        <input type="hidden" name="idPemasukanPengeluaranLain" value="<?= $idPemasukanPengeluaranLain ?>">
        <input type="hidden" name="kodePemasukanPengeluaranLain" value="<?= $kodePemasukanPengeluaranLain ?>">
        <input type="hidden" name="flag" value="<?= $flag ?>">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label><i class="fa fa-list"></i> Tipe</label><br>
                <select class="form-control selectpicker" id="tipe" name="tipe" style="width: 100%;">
                    <option value="">Pilih Tipe</option>
                    <?php
                    $arraytipe = array('Pemasukan Lain', 'Pengeluaran Lain');
                    for ($i = 0; $i < count($arraytipe); $i++) {
                        $selected = selected($arraytipe[$i], $dataUpdate['tipe']);
                    ?>
                        <option value="<?= $arraytipe[$i] ?>" <?= $selected ?>>
                            <?= $arraytipe[$i] ?>
                        </option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="tanggal"><i class="fas fa-calendar-alt"></i> TANGGAL</label>
                <div class="input-group date" data-date-autoclose="true" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                    <input type="text" class="form-control" id="tanggal" name="tanggal" placeholder="Click to select a date!" value="<?= $dataUpdate['tanggal'] ?? date('Y-m-d') ?>" autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fa fa-calendar"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label><i class="fas fa-user-tie"></i><span id="namaAccount">KODE ACCOUNT</span></label><br>
            <select class="form-control selectpicker" id="idSubAccount" name="idSubAccount" data-live-search="true">
                <option value="">Pilih Akun</option>
                <?php
                $dataAccount = statementWrapper(
                    DML_SELECT_ALL,
                    "SELECT * FROM account WHERE 
                        statusAccount = ?",
                    ['Aktif']
                );

                $dataSubAccount = statementWrapper(
                    DML_SELECT_ALL,
                    "SELECT * FROM sub_account WHERE 
                        statusSubAccount = ?",
                    ['Aktif']
                );

                foreach ($dataAccount as $row) :
                ?>
                    <optgroup label="<?= $row['namaAccount'] . ' (' . $row["kode"] . ')' ?>">
                        <?php foreach ($dataSubAccount as $rowSub) {
                            $selected = selected((int)$rowSub['idSubAccount'], (int)$dataUpdate['idSubAccount']);

                            if ($row["kodeAccount"] == $rowSub["kodeAccount"]) { ?>
                                <option value="<?= $rowSub["idSubAccount"] ?>" <?= $selected ?>><?= $rowSub["namaSubAccount"] . ' (' . $rowSub["kodeSub"] . ')' ?></option>
                        <?php }
                        } ?>
                    </optgroup>
                <?php endforeach ?>
            </select>
        </div>

        <div class="row">
            <div class="form-group col-md-6">
                <label for="jenisRekening"><i class="fas fa-piggy-bank"></i> JENIS BANK</label>
                <select class="form-control selectpicker" id="jenisRekening" name="jenisRekening" style="width: 100%;" onchange="selectRekening()">
                    <option value="">Pilih Jenis Rekening</option>
                    <?php
                    $opsi = ['Bank', 'Petty Cash'];

                    foreach ($opsi as $row) {
                        $selected = selected($row, $dataUpdate['jenisRekening']);
                    ?>
                        <option value="<?= $row ?>" <?= $selected ?>>
                            <?= $row ?>
                        </option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-6" id="boxRekening">
            </div>
        </div>

        <div class="form-group">
            <label><i class="fas fa-money-bill" for="nominal"></i> NOMINAL</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Rp</span>
                </div>
                <input type="text" name="nominal" id="nominal" class="form-control" data-format-rupiah="active" value="<?= ubahToRp($dataUpdate['nominal'] ?? 0) ?>">
            </div>
        </div>

        <div class="form-group">
            <label><i class="fa fa-list"></i> KETERANGAN</label>
            <textarea class="form-control" rows="3" name="keterangan" placeholder="Input keterangan"><?= $dataUpdate['keterangan'] ?? '' ?></textarea>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-primary text-center" onclick="prosesPemasukanPengeluaranLain()">
                <i class="fas fa-save d-block mb-2 pr-0 fa-2x"></i> <strong>SAVE</strong>
            </button>
        </div>
    </form>
<?php
}
?>