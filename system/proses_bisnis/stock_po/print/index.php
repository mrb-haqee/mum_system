<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiqrcode.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";
include_once "{$constant('BASE_URL_PHP')}/{$constant('MAIN_DIR')}/fungsinavigasi.php";

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
    header('location:' . BASE_URL_HTML . '/?flagNotif=gagal');
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    extract($_GET, EXTR_SKIP);

    if (isset($param)) {
        $result = decryptURLParam($param);
        $kodePO = $result['kode'];
    }

    $dataUpdate = statementWrapper(
        DML_SELECT_ALL,
        'SELECT stock_po.*, vendor.nama as namaVendor, vendor.alamat, vendor.noTelp, stock_po_detail.*, stock_po_pembayaran.*, barang.*
        FROM stock_po
        INNER JOIN stock_po_detail ON stock_po.kodePO = stock_po_detail.kodePO
        INNER JOIN stock_po_pembayaran ON stock_po.kodePO = stock_po_pembayaran.kodePO
        INNER JOIN barang ON stock_po_detail.idInventory = barang.idBarang
        LEFT JOIN vendor ON stock_po.kodeVendor = vendor.kodeVendor
        WHERE stock_po.kodePO=?',
        [$kodePO]
    );

    [
        'namaVendor' => $namaVendor,
        'alamat' => $alamat,
        'noTelp' => $noTelp,
        'diskon' => $discount,
        'persentaseDiskon' => $persentaseDiskon,
        'persentasePpn' => $persentasePpn,
        'ppn' => $ppn,
        'metodeBayar' => $metodePembayaran,
        'nomorSP' => $nomorSP,
        'grandTotal' => $grandTotal
    ] = $dataUpdate[0]


?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>SURAT PESANAN SP. <?= $nomorSP ?></title>
        <!-- Google Font: Source Sans Pro -->
        <!-- <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> -->
        <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

        <style type="text/css">
            @media print {

                .newPage {
                    page-break-after: always;
                }

                #footerImg {
                    position: fixed;
                    bottom: 20px;
                }

            }

            .underline-full {
                position: relative;
                display: inline-block;
                width: 100%;
            }

            .underline-full::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                border-bottom: 1px solid black;
                /* Atur garis bawah */
            }

            /* table tbody td {
                padding: 6px 8px !important;
            } */

            /* #sectionHeader table tbody td {
                padding: 6px 8px !important;
                font-size: 14px !important;
            } */

            /* .english {
                font-size: 10px;
            } */
            table tbody tr {
                line-height: 1;
                /* Sesuaikan dengan jarak yang diinginkan */
            }

            body {
                font-size: 20px;
                font-family: "Comic Neue", cursive;
                font-weight: 700;
                font-style: normal;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="row d-flex justify-content-center" id="sectionHeader">
                <div class="col-12">
                    <h4 class="font-weight-bolder text-center"><u>SURAT PESANAN ( SP )</u></h4>
                    <h5 class="font-weight-bolder text-center">No. SP. <?= $nomorSP ?></h5>
                    <table class="table table-borderless mx-auto mt-5">
                        <tbody>
                            <tr>
                                <td style="width: 15%;">Kepada</td>
                                <td style="width: 2%;">:</td>
                                <td style="width: 33%;"><?= $namaVendor ?><br><?= $alamat ?></td>
                                <td style="width: 15%;">Dari</td>
                                <td style="width: 2%;">:</td>
                                <td style="width: 33%;">PT. Marga Utama Mandiri</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>Contac Person</td>
                                <td>:</td>
                                <td><?= $dataPegawai['namaPegawai'] ?></td>
                            </tr>
                            <tr>
                                <td>Fax. No</td>
                                <td>:</td>
                                <td><?= $noTelp ?></td>
                                <td>Fax. No</td>
                                <td>:</td>
                                <td>0361 - 432980</td>
                            </tr>
                            <tr>
                                <td colspan="6">Sesuai penawaran Bapak / Ibu / Saudara No. / Tgl : <br>
                                    dengan ini kami pesan barang - barang sebagai berikut </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row" id="sectionBody">

                <div class="col-12">

                    <table class="table table-borderless mb-5">
                        <thead class="table-bordered">
                            <tr class="text-center">
                                <th class="border-right" style="width: 1%;">No</th>
                                <th class="border-right" style="width: 29%;">NAMA BARANG</th>
                                <th class="border-right" style="width: 20%;">QTY</th>
                                <th class="border-right" style="width: 20%;">HARGA SATUAN</th>
                                <th class="border-right" style="width: 30%;">JUMLAH HARGA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0;
                            foreach ($dataUpdate as $index => $row) : ?>
                                <tr>
                                    <td class="border-right border-left text-center"><?= $index + 1 ?></td>
                                    <td class="border-right"><?= $row['namaBarang'] ?></td>
                                    <td class="text-right border-right"><?= $row['qty'] . ' ' . $row['satuanBarang'] ?></td>

                                    <!-- Mengatur Rp dan nominal dengan justify-content-between -->
                                    <td class="border-right d-flex justify-content-between">
                                        <span>Rp</span>
                                        <span class="text-right"><?= ubahToRupiahDesimal($row['hargaBarang']) ?>,00</span>
                                    </td>

                                    <?php if ($index + 1 !== count($dataUpdate)): ?>
                                        <td class="text-right border-right">
                                            <span class="d-flex justify-content-between">
                                                <span>Rp</span>
                                                <span class="text-right"><?= ubahToRupiahDesimal($row['subTotal']) ?>,00</span>
                                            </span>
                                        </td>
                                    <?php else: ?>
                                        <td class="text-right border-right">
                                            <span class="d-flex justify-content-between underline-full">
                                                <span>Rp</span>
                                                <span class="text-right"><?= ubahToRupiahDesimal($row['subTotal']) ?>,00</span>
                                            </span>
                                        </td>
                                    <?php endif ?>
                                </tr>

                            <?php $total += $row['subTotal'];
                            endforeach ?>
                            <tr>
                                <td class="border-right border-left"></td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                            </tr>
                            <tr class="font-weight-bolder bg-secondary-o-80">
                                <td class="border-right border-left"></td>
                                <td class="border-right">Jumlah</td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="text-right border-right d-flex justify-content-between "><?= formatNominal($total) ?></td>
                            </tr>
                            <tr class="font-weight-bolder">
                                <td class="border-right border-left"></td>
                                <td class="border-right">Discount <?= ubahToRupiahDesimal($persentaseDiskon) ?>%</td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="text-right border-right "><span class="d-flex justify-content-between underline-full"><?= formatNominal($discount) ?></span></td>
                            </tr>
                            <tr class="font-weight-bolder">
                                <td class="border-right border-left"></td>
                                <td class="border-right">Harga Setelah Discount </td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="text-right border-right d-flex justify-content-between "><?= formatNominal($total - $discount); ?></td>
                            </tr>
                            <tr class="font-weight-bolder">
                                <td class="border-right border-left"></td>
                                <td class="border-right">PPN <?= ubahToRupiahDesimal($persentasePpn) ?>%</td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="text-right border-right d-flex justify-content-between "><span class="d-flex justify-content-between underline-full"><?= formatNominal($ppn) ?></span></td>
                            </tr>
                            <tr class="font-weight-bolder bg-secondary-o-80">
                                <td class="border-right border-left"></td>
                                <td class="border-right">TOTAL </td>
                                <td class="border-right"></td>
                                <td class="border-right"></td>
                                <td class="text-right border-right d-flex justify-content-between"><?= formatNominal($grandTotal); ?></td>
                            </tr>
                            <tr>
                                <td class="border-right border-left border-bottom"></td>
                                <td class="border-right border-bottom"></td>
                                <td class="border-right border-bottom"></td>
                                <td class="border-right border-bottom"></td>
                                <td class="border-right border-bottom"></td>
                            </tr>
                        </tbody>

                    </table>
                    <div style="line-height: 1rem;" class="mb-7">
                        <p>Syarat Pesanan</p>
                        <p>1. Jangka Waktu Penyerahan Barang : Segera</p>
                        <p>2. pembayaran : <?= $metodePembayaran ?></p>
                    </div>
                    <br><br><br><br><br>
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td style="width: 10%;"></td>
                                <td style="width: 60%;"></td>
                                <td style="width: 30%;">Denpasar, <?= tanggalTerbilang(date('d-m-Y')) ?></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td style="width: 10%;">PURCHASING</td>
                                <td style="width: 60%;"></td>
                                <td style="width: 30%;"><u>I Made Suastika, SE</u><br>DIREKTUR</td>
                            </tr>

                        </tbody>
                    </table>

                </div>


            </div>

            <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js" integrity="sha256-uGyFpu2wVfZ4h/KOsoT+7NdggPAEU2vXx0oNPEYq3J0=" crossorigin="anonymous"></script>

            <script>
                window.onafterprint = function() {
                    // window.close();
                }
            </script>
    </body>

    </html>
<?php }
function formatNominal($nominal)
{
    $nominal = ubahToRupiahDesimal($nominal);
    return "<span>Rp</span><span>$nominal,00</span>";
}
?>