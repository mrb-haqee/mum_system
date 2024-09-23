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

    $dataInvoice = statementWrapper(
        DML_SELECT,
        'SELECT * FROM pasien_invoice_klinik WHERE kodeAntrian = ?',
        [$kodeAntrian]
    );

    if (isset($param)) {
        $result = [];
        parse_str(dekripsi(rawurldecode($param), secretKey()), $result);

        if (isset($result['src'])) {
            $mode = 'readonly';
        } else {
            $mode = 'input';
        }

?>
        <div class="card card-custom">
            <!-- CARD HEADER -->
            <div class="card-header">
                <!-- CARD TITLE -->
                <div class="card-title">
                    <h3 class="card-label">
                        <span class="card-label font-weight-bolder text-dark d-block">
                            <i class="fa fa-diagnoses text-dark"></i> Paket Laboratorium Yang Diberikan
                        </span>
                        <span class="mt-3 font-weight-bold font-size-sm">
                            <?= PAGE_TITLE; ?>
                        </span>
                    </h3>
                </div>
                <!-- END CARD TITLE -->
            </div>
            <!-- END CARD HEADER -->

            <!-- CARD BODY -->
            <div class="card-body">
                <?php
                if (true) {
                    if (!$dataInvoice && $mode === 'input') {
                ?>
                        <form id="formPaketLaboratorium">
                            <input type="hidden" name="tokenCSRFForm" value="<?= $tokenCSRF ?>">
                            <input type="hidden" name="kodeAntrian" value="<?= $kodeAntrian ?>">
                            <input type="hidden" name="jenisHarga" value="<?= $dataAntrian['jenisHarga'] ?>">
                            <input type="hidden" name="flag" value="tambah">

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label>Paket Laboratorium</label>
                                    <select name="idPaketLaboratorium" id="idPaketLaboratorium" class="form-control selectpicker" onchange="showHarga('paket_laboratorium');" data-live-search="true" style="width: 100%;">
                                        <option value=""> Pilih Paket Laboratorium</option>
                                        <?php
                                        $opsi = statementWrapper(
                                            DML_SELECT_ALL,
                                            'SELECT 
                                                * 
                                            FROM 
                                                paket_laboratorium 
                                            WHERE 
                                                statusPaketLaboratorium = ?
                                                ORDER BY nama',
                                            ['Aktif']
                                        );

                                        foreach ($opsi as $row) {
                                        ?>
                                            <option value="<?= $row['idPaketLaboratorium'] ?>"><?= $row['nama'] ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Harga</label>
                                    <input type="text" name="harga" id="hargaPaketLaboratorium" placeholder="Harga" data-format-rupiah="active" class="form-control" onkeyup="showSubTotal('#hargaPaketLaboratorium','#qtyPaketLaboratorium','#subTotalPaketLaboratorium');">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Qty</label>
                                    <input type="text" name="qty" id="qtyPaketLaboratorium" placeholder="Qty" class="form-control" data-format-rupiah="active" onkeyup="showSubTotal('#hargaPaketLaboratorium','#qtyPaketLaboratorium','#subTotalPaketLaboratorium');">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Subtotal</label>
                                    <input type="text" name="subTotal" id="subTotalPaketLaboratorium" placeholder="Sub Total" data-format-rupiah="active" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="button" class="btn btn-primary" onclick="prosesPaketLaboratorium()">
                                    <i class="fas fa-save pr-4"></i> <strong>SIMPAN</strong>
                                </button>
                            </div>
                        </form>
                        <hr>
                    <?php
                    }
                    ?>


                    <div style="overflow-x: auto;">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>AKSI</th>
                                    <th>PAKET LABORATORIUM</th>
                                    <th>QTY</th>
                                    <th class="text-center">HARGA</th>
                                    <th class="text-center">SUB TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                $data = statementWrapper(
                                    DML_SELECT_ALL,
                                    "SELECT 
                                        pasien_paket_laboratorium_klinik.*,
                                        paket_laboratorium.nama
                                    FROM 
                                        pasien_paket_laboratorium_klinik
                                        INNER JOIN paket_laboratorium ON paket_laboratorium.idPaketLaboratorium = pasien_paket_laboratorium_klinik.idPaketLaboratorium
                                    WHERE 
                                        pasien_paket_laboratorium_klinik.kodeAntrian = ?",
                                    [
                                        $kodeAntrian
                                    ]
                                );

                                $n = 1;
                                $grandTotal = 0;

                                foreach ($data as $row) {
                                ?>
                                    <tr>
                                        <td><?= $n ?></td>
                                        <td>
                                            <?php
                                            if (!$dataInvoice && $mode === 'input') {
                                            ?>
                                                <button type="button" class="btn btn-danger btn-md" onclick="konfirmasiBatal('<?= $row['idPaketLaboratoriumKlinik'] ?>','<?= $tokenCSRF ?>', 'paket_laboratorium')">
                                                    <i class="fa fa-trash pr-0"></i>
                                                </button>
                                            <?php
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="d-block font-weight-bold"><?= $row['nama'] ?></span>
                                            <span class="text-muted font-weight-bold">Nama Paket Laboratorium</span>
                                        </td>
                                        <td>
                                            <span class="d-block font-weight-bold"><?= ubahToRupiahDesimal($row['qty']) ?></span>
                                            <span class="text-muted font-weight-bold">Qty</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['harga']) ?></span>
                                            <span class="text-muted font-weight-bold">Harga</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="d-block font-weight-bold">Rp <?= ubahToRupiahDesimal($row['subTotal']) ?></span>
                                            <span class="text-muted font-weight-bold">Sub Total</span>
                                        </td>
                                    </tr>
                                <?php
                                    $grandTotal += $row['subTotal'];
                                    $n++;
                                }
                                ?>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                                    <td class="text-right"><strong><?= ubahToRupiahDesimal($grandTotal) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php
                } else {
                ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle pr-5 text-white"></i><strong>SILAHKAN LENGKAPI DATA PEMERIKSAAN PASIEN TERLEBIH DAHULU UNTUK MELANJUTKAN</strong>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
<?php
    }
}
?>