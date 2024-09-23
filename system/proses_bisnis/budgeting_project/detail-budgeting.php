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

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    extract($_POST, EXTR_SKIP);

    $dataTim = statementWrapper(
        DML_SELECT_ALL,
        "SELECT 
            GROUP_CONCAT(CASE WHEN bpt.jabatan = 'Leader' THEN pegawai.namaPegawai END) AS Leader,
            GROUP_CONCAT(CASE WHEN bpt.jabatan = 'Anggota' THEN pegawai.namaPegawai END) AS Anggota,
            GROUP_CONCAT(CASE WHEN bpt.jabatan = 'Penanggung Jawab' THEN pegawai.namaPegawai END) AS PenanggungJawab
        FROM
            budgeting_project AS bp
            INNER JOIN budgeting_project_tim as bpt ON bp.kodeBudgetingProject = bpt.kodeBudgetingProject
            INNER JOIN pegawai ON bpt.kodePegawai = pegawai.kodePegawai
        WHERE
            bp.statusBudgetingProject = ?
            AND bpt.`statusBudgetingProjectTim`=?
            AND bp.idBudgetingProject = ?
        GROUP BY
            bp.kodeBudgetingProject;
        ",
        ['Aktif', 'Aktif', $idBudgetingProject]
    )[0];
    $dataBiaya = statementWrapper(
        DML_SELECT_ALL,
        "SELECT bpb.*
        FROM
            budgeting_project AS bp
            INNER JOIN budgeting_project_biaya as bpb ON bp.`kodeBudgetingProject` = bpb.`kodeBudgetingProject`
        WHERE
            bp.statusBudgetingProject = ?
            AND bp.idBudgetingProject = ?;",
        ['Aktif', $idBudgetingProject]
    );
    $jabatan = array('Leader', 'PenanggungJawab', 'Anggota');
?>
    <div class="row">
        <div class="col-lg-12">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td colspan="4"> <i class="fas fa-file-signature pr-5" style="font-size: 1rem;"></i><strong> INFORMASI BUDGETING </strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center font-weight-bolder bg-light">TIM</td>
                    </tr>
                    <?php
                    foreach ($jabatan as $j) {
                        if ($dataTim[$j]) {

                            if (strpos($dataTim[$j], ',') !== false) {
                    ?>
                                <tr>
                                    <td style="width: 20%;"> <?= $j ?> </td>
                                    <td colspan="3">
                                        <?php echo implode(', ', explode(',', $dataTim[$j]));; ?>
                                    </td>
                                </tr>

                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td style="width: 20%;"> <?= $j ?> </td>
                                    <td colspan="3"><?= $dataTim[$j] ?></td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td style="width: 20%;"> <?= $j ?> </td>
                                <td colspan="3">-</td>
                            </tr>
                    <?php
                        }
                    }
                    ?>

                    <tr>
                        <td colspan="4" class="text-center font-weight-bolder bg-light">BIAYA</td>
                    </tr>
                    <tr>
                        <td>Nama Barang</td>
                        <td style="width: 10%;" class="text-center font-weight-bold">Qty</td>
                        <td style="width: 30%;" class="text-center font-weight-bold">Harga Satuan</td>
                        <td style="width: 30%;" class="text-center font-weight-bold">Sub Total</td>
                    </tr>
                    <?php
                    $total = 0;
                    foreach ($dataBiaya as $row) :
                        $total += $row['subTotal'] ?>
                        <tr>
                            <td><?= $row['namaItem'] ?></td>
                            <td class="text-center"><?= $row['qty'] ?></td>
                            <td class="text-right">Rp. <?= ubahToRp($row['hargaSatuan']) ?></td>
                            <td class="text-right">Rp. <?= ubahToRp($row['subTotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr>
                        <td colspan="3" class="text-center font-weight-bold">Total</td>
                        <td style="width: 10%;" class="text-right">Rp. <?= ubahToRp($total) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<?php
}
