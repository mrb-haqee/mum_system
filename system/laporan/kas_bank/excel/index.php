<?php
include_once '../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasijenisharga.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
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

    extract($_POST, EXTR_SKIP);


    
?>

    <!DOCTYPE html>
    <html>

    <head>
        <title>Daftar Obat</title>
        <style>
            h3 {
                font-weight: bold;
                text-align: center;
                text-transform: uppercase;
            }

            table {
                border: 1px solid black;
                text-align: left;
                border-collapse: collapse;
                width: 100%
            }

            table thead th {
                border: 1px solid black;
                text-align: left;
            }

            table tbody td {
                border: 1px solid black;
                text-align: left;
            }
        </style>
    </head>
    <?php
    header("Content-type: application/vnd-ms-excel");
    header('Content-Disposition: attachment; filename=Daftar_Obat_Per_' . date('Y-m-d') . '.xls');

    ?>

    <body>
        <table style="width: 100%; border:none">
            <tr>
                <td colspan="9" style="text-align: center;"><strong>DAFTAR OBAT Per <?= ubahTanggalIndo(date('Y-m-d')); ?></strong></td>
            </tr>
        </table>
        <br>
        <table>
            <thead>
                <tr>
                    <th scope="col" rowspan="2">NO</th>
                    <th scope="col" rowspan="2">JENIS</th>
                    <th scope="col" rowspan="2">NAMA OBAT</th>
                    <th scope="col" rowspan="2">HARGA POKOK</th>
                    <?php
                    foreach (getJenisHarga() as $jenisHarga) {
                    ?>
                        <th colspan="4" style="text-align: center;"><?= $jenisHarga; ?></th>
                    <?php
                    }
                    ?>
                </tr>
                <tr>
                    <?php
                    foreach (getJenisHarga() as $jenisHarga) {
                        foreach ($dataAdmisi as $key => $value) {
                    ?>
                            <td scope="col" style="text-align: center;"><?= $value['namaAdmisi']; ?></td>
                    <?php
                        }
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($dataObat as $index => $obat) {
                ?>
                    <tr>
                        <td style="text-align:center"><?= $index + 1; ?></td>
                        <td style="text-align:center"><?= $obat['jenisObat'] ?></td>
                        <td style="text-align:center"><?= $obat['nama'] ?></td>
                        <td style="text-align:center"><?= ubahToRupiahDesimal($obat['hargaBeli']) ?></td>
                        <?php
                        foreach (getJenisHarga() as $jenisHarga) {
                            foreach ($dataAdmisi as $key => $value) {
                                $nominal = statementWrapper(
                                    DML_SELECT,
                                    'SELECT nominal FROM obat_harga WHERE kodeObat = ? AND idAdmisi = ? AND jenisHarga = ?',
                                    [$obat['kodeObat'], $value['idAdmisi'], $jenisHarga]
                                )['nominal'];
                        ?>
                                <td style="text-align: right;"><?= ubahToRupiahDesimal($nominal); ?></td>
                        <?php
                            }
                        }
                        ?>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

    </body>

    </html>
<?php
}
?>