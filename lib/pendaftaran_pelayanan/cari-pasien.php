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

    $search = trim($search);

    if ($search !== '' && strlen($search) > 3) {

        $dataPasien = statementWrapper(
            DML_SELECT_ALL,
            'SELECT
                *
            FROM
                pasien
            WHERE
                kodeRM = ?
                OR noIdentitas = ?
                OR namaPasien LIKE ?
            ',
            [
                $search,
                $search,
                "%{$search}%",
            ]
        );

        if (count($dataPasien) > 0) {
?>
            <span class="text-primary"><strong>HASIL</strong> : (<strong><?= count($dataPasien); ?></strong>) Data Pasien Ditemukan.</span>
            <hr>
            <div style="min-height: 450px; overflow-y:auto">
                <?php
                foreach ($dataPasien as $index => $pasien) {
                    $query = rawurlencode(enkripsi(http_build_query([
                        'kodeRM' => $pasien['kodeRM'],
                        'idAdmisi' => $idAdmisi
                    ]), secretKey()));
                ?>
                    <div class="d-flex align-items-center mb-10 bg-light rounded p-5" style="overflow-y:auto;">
                        <!--begin::Symbol-->
                        <div class="symbol symbol-50 symbol-light-success mr-5">
                            <span class="symbol-label" style="width: 50px; height:50px;">
                                <?php
                                if ($pasien['jenisKelamin'] === 'Laki-laki') {
                                ?>
                                    <img src="<?= BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/svg/avatars/009-boy-4.svg" class="align-self-end" style="height:100%" alt="">
                                <?php
                                } else if ($pasien['jenisKelamin'] === 'Perempuan') {
                                ?>
                                    <img src="<?= BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/svg/avatars/010-girl-4.svg" class="align-self-end" style="height:100%" alt="">
                                <?php
                                } else {
                                ?>
                                    <img src="<?= BASE_URL_HTML ?><?= ASSETS_DIR ?>/media/svg/avatars/user-blank.svg" class="align-self-end" style="height:100%" alt="">
                                <?php
                                }
                                ?>
                            </span>
                        </div>
                        <!--end::Symbol-->

                        <!--begin::Text-->

                        <div class="d-flex flex-column flex-grow-1 font-weight-bold">
                            <a href="#" class="text-dark text-hover-secondary mb-1" style="font-size: 1.5rem;"><strong><?= highlightKeywords($search, $pasien['namaPasien']); ?></strong></a>
                            <div class="row mb-5">
                                <div class="col-xl-4 d-flex" style="gap: 10px;">
                                    <div style="width: 20px;">
                                        <i class="fas fa-birthday-cake" style="font-size:1rem;"></i>
                                    </div>
                                    <span class="text-muted"> <?= $pasien['tempatLahir']; ?>, <?= ubahTanggalIndo($pasien['tanggalLahir'], false); ?> <strong>(<?= umur($pasien['tanggalLahir'])['umur']; ?> Tahun)</strong></span>
                                </div>
                                <div class="col-xl-8 d-flex" style="gap: 10px;">
                                    <div style="width: 20px;">
                                        <i class="fas fa-map-marked" style="font-size:1rem;"></i>
                                    </div>
                                    <span class="text-muted"><?= $pasien['alamat']; ?></span>
                                </div>
                            </div>
                            <div class="d-flex" style="gap: 20px;">
                                <table class="table table-bordered" style="min-width:250px; max-width:300px">
                                    <tbody>
                                        <tr>
                                            <td class="py-2 bg-white"><strong>KODE RM</strong></td>
                                            <td class="py-2 bg-white"><strong>NO. IDENTITAS</strong></td>
                                            <td class="py-2 bg-white"><strong>ID SATU SEHAT</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 bg-white"><?= highlightKeywords($search, $pasien['kodeRM']); ?></td>
                                            <td class="py-2 bg-white"><?= highlightKeywords($search, $pasien['noIdentitas']); ?></td>
                                            <td class="py-2 bg-white"><?= highlightKeywords($search, $pasien['kodeUUIDSatuSehat'] ?? ''); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Text-->
                        <?php
                        $dataSatuSehat = statementWrapper(
                            DML_SELECT,
                            'SELECT * FROM pasien_satusehat WHERE kodeRM = ?',
                            [$pasien['kodeRM']]
                        );

                        if ($dataSatuSehat) {
                            if (is_null($pasien['kodeUUIDSatuSehat'])) {
                        ?>
                                 <button class="btn btn-success mr-2" onclick="syncSatuSehatPasien('<?= $pasien['kodeRM'] ?>','<?= $tokenCSRF ?>')" title="Sinkronisasi Satu Sehat"><i class="fas fa-sync-alt pr-0"></i></button> 
                            <?php
                            } else {
                            ?>
                                 <button class="btn btn-info mr-2" onclick="syncSatuSehatPasien('<?= $pasien['kodeRM'] ?>','<?= $tokenCSRF ?>')" title="Sinkronisasi Satu Sehat"><i class="fas fa-sync-alt pr-0"></i></button> 
                        <?php

                            }
                        }
                        ?>
                        <a class="btn btn-info" href="detail_pendaftaran/?param=<?= $query ?>"><i class="fas fa-location-arrow pr-0"></i></a>
                    </div>
                <?php
                }
                ?>
            </div>
        <?php
        } else {
        ?>
            <div class="d-flex align-items-center bg-light-danger rounded p-5 mb-9">
                <!--begin::Icon-->
                <span class="svg-icon svg-icon-danger mr-5">
                    <span class="svg-icon svg-icon-lg"><!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Communication/Group-chat.svg-->
                        <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <g id="Stockholm-icons-/-General-/-Hidden" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <rect id="bound" x="0" y="0" width="24" height="24"></rect>
                                <path d="M19.2078777,9.84836149 C20.3303823,11.0178941 21,12 21,12 C21,12 16.9090909,18 12,18 C11.6893441,18 11.3879033,17.9864845 11.0955026,17.9607365 L19.2078777,9.84836149 Z" id="Combined-Shape" fill="#000000" fill-rule="nonzero"></path>
                                <path d="M14.5051465,6.49485351 L12,9 C10.3431458,9 9,10.3431458 9,12 L5.52661464,15.4733854 C3.75006453,13.8334911 3,12 3,12 C3,12 5.45454545,6 12,6 C12.8665422,6 13.7075911,6.18695134 14.5051465,6.49485351 Z" id="Combined-Shape" fill="#000000" fill-rule="nonzero"></path>
                                <rect id="Rectangle" fill="#000000" opacity="0.3" transform="translate(12.524621, 12.424621) rotate(-45.000000) translate(-12.524621, -12.424621) " x="3.02462111" y="11.4246212" width="19" height="2"></rect>
                            </g>
                        </svg><!--end::Svg Icon-->
                    </span>
                </span>
                <!--end::Icon-->

                <!--begin::Title-->
                <div class="d-flex flex-column flex-grow-1 mr-2">
                    <a href="#" class="font-weight-bold text-dark-75 font-size-lg mb-1"><strong>PASIEN TIDAK DITEMUKAN</strong></a>
                    <span class="text-muted font-weight-bold">Silahkan Cari Pasien Dengan Kata Kunci Lain...</span>
                </div>
                <!--end::Title-->

                <!--begin::Lable-->
                <span class="font-weight-bolder text-danger py-1 font-size-lg"></span>
                <!--end::Lable-->
            </div>
<?php
        }
    }
}
?>