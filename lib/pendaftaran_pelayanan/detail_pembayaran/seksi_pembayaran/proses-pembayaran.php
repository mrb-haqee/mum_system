<?php
include_once '../../../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasicurrency.php";
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

$tokenValid = hash_equals($tokenCSRF, $_POST['tokenCSRFForm'] ?? '');
//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser || !$dataCekMenu || !validateIP($_SESSION['IP_ADDR']) || !$tokenValid) {
    $data = array('status' => false, 'pesan' => 'Proses Authentikasi Gagal, Data Tidak Valid');
} else {

    $dataPegawai = selectStatement(
        'SELECT pegawai.* FROM pegawai WHERE idPegawai = ?',
        [$dataCekUser['idPegawai']],
        'fetch'
    );

    sanitizeInput($_POST);
    extract($_POST, EXTR_SKIP);

    try {
        if ($flag === 'deletePembayaran') {
            $status = statementWrapper(
                DML_DELETE,
                "DELETE FROM
                    pasien_deposit
                WHERE
                    idPasienDeposit = ?
                ",
                [
                    $idPasienDeposit,
                ]
            );

            if ($status) {
                $pesan = 'Proses Delete Pembayaran Berhasil';
            } else {
                $pesan = 'Proses Delete Pembayaran Gagal';
            }
        } else if ($flag === 'setWaktuKepulangan') {
            $status = statementWrapper(
                DML_UPDATE,
                "UPDATE
                    pasien_antrian_satusehat
                SET
                    waktuKepulangan = ?,
                    idUser = ?
                WHERE
                    kodeAntrian = ?
                ",
                [
                    date_create($waktuKepulangan)->format('Y-m-d H:i:s'),
                    $idUserAsli,
                    $kodeAntrian
                ]
            );

            if ($status) {
                $pesan = 'Proses Set Waktu Kepulangan Berhasil';
            } else {
                $pesan = 'Proses Set Waktu Kepulangan Gagal';
            }
        } else if ($flag === 'pembayaran') {

            $idAdmisi = statementWrapper(
                DML_SELECT,
                'SELECT idAdmisi FROM pasien_antrian WHERE kodeAntrian = ?',
                [$kodeAntrian]
            )['idAdmisi'];

            $tanggal = $tglPembayaran;

            if ($metodePembayaran === 'Insurance') {
                $nominal = ubahToInt($sisaBayar);
                $kodeReferensi = $kodeAsuransi;

                $idTransferEDC = NULL;
                $noBatch = NULL;
                $fee = 0;

                if ($currency === CURRENCY_DEFAULT) {
                    $exchangeNominal = $nominal;
                    $exchangeDateTime = NULL;
                } else {
                    $exchangeNominal = ubahToInt($jumlahBayarExc);
                    $exchangeDateTime = date('Y-m-d H:i:s');
                }

                $statusPembayaran = 'Sudah Bayar';
                $kembalian = 0;
            } else if ($metodePembayaran === 'Klien') {
                $nominal = ubahToInt($sisaBayar);
                $kodeReferensi = $kodeKlien;

                $idTransferEDC = NULL;
                $noBatch = NULL;
                $fee = 0;

                $currency = CURRENCY_DEFAULT;
                $exchangeNominal = $nominal;
                $exchangeDateTime = NULL;

                $statusPembayaran = 'Sudah Bayar';
                $kembalian = 0;
            } else if ($metodePembayaran === 'Non Tunai') {
                $kodeReferensi = $kodeTujuanTransfer;

                $currency = CURRENCY_DEFAULT;
                $exchangeDateTime = NULL;

                if ($metode === 'Transfer') {
                    $idTransferEDC = NULL;
                    $noBatch = NULL;

                    $fee = 0;
                } else {
                    $fee = statementWrapper(
                        DML_SELECT,
                        'SELECT fee FROM tujuan_transfer_edc WHERE idTransferEDC = ?',
                        [$idTransferEDC]
                    )['fee'];
                }

                if (ubahToInt($jumlahBayar) >= ubahToInt($sisaBayar)) {
                    $statusPembayaran = 'Sudah Bayar';
                    $kembalian = ubahToInt($jumlahBayar) - ubahToInt($sisaBayar);

                    $nominal = ubahToInt($sisaBayar);
                } else {
                    $nominal = ubahToInt($jumlahBayar);

                    $statusPembayaran = 'Belum Bayar';
                    $kembalian = 0;
                }

                $exchangeNominal = $nominal;
            } else if ($metodePembayaran === 'Tunai') {
                $kodeReferensi = '__TUNAI__';

                $idTransferEDC = NULL;
                $noBatch = NULL;
                $fee = 0;

                if (ubahToInt($jumlahBayar) >= ubahToInt($sisaBayar)) {
                    $statusPembayaran = 'Sudah Bayar';
                    $kembalian = ubahToInt($jumlahBayar) - ubahToInt($sisaBayar);

                    $nominal = ubahToInt($sisaBayar);
                } else {
                    $statusPembayaran = 'Belum Bayar';
                    $kembalian = 0;

                    $nominal = ubahToInt($jumlahBayar);
                }

                if ($currency === CURRENCY_DEFAULT) {
                    $exchangeNominal = $nominal;
                    $exchangeDateTime = NULL;
                } else {
                    $exchangeNominal = ubahToInt($jumlahBayarExc);
                    $exchangeDateTime = date('Y-m-d H:i:s');
                }
            }

            $detailCurrency = getCurrencyList($currency);

            if ($detailCurrency) {

                $status = statementWrapper(
                    DML_INSERT,
                    "INSERT INTO
                        pasien_deposit
                    SET
                        kodeAntrian = ?,
                        kodeInvoice = ?,
                        tanggal = ?,
                        kodeReferensi = ?,
                        currency = ?,
                        exchangeDateTime = ?,
                        nominal = ?,
                        exchangeNominal = ?,
                        kembalian = ?,
                        metodePembayaran = ?,
                        idTransferEDC = ?,
                        fee = ?,
                        noBatch = ?,
                        statusDeposit = ?,
                        idUser = ?
                    ON DUPLICATE KEY 
                    UPDATE
                        nominal = nominal + ?,
                        exchangeNominal = exchangeNominal + ?,
                        kembalian = kembalian + ?
                    ",
                    [
                        $kodeAntrian,
                        $kodeInvoice,
                        $tanggal,
                        $kodeReferensi,
                        $currency,
                        $exchangeDateTime,
                        $nominal,
                        $exchangeNominal,
                        $kembalian,
                        $metodePembayaran,
                        $idTransferEDC,
                        $fee,
                        $noBatch,
                        'Aktif',
                        $idUserAsli,
                        $nominal,
                        $exchangeNominal,
                        $kembalian
                    ]
                );

                if (intval($idAdmisi) === 11) {
                    $statusBayar = statementWrapper(
                        DML_UPDATE,
                        'UPDATE
                            pasien_antrian
                        SET
                            statusPembayaran = ?,
                            statusPelayanan = ?
                        WHERE
                            kodeAntrian = ?
                        ',
                        [
                            $statusPembayaran,
                            'Sudah Dilayani',
                            $kodeAntrian,
                        ]
                    );
                } else {
                    $statusBayar = statementWrapper(
                        DML_UPDATE,
                        'UPDATE
                            pasien_antrian
                        SET
                            statusPembayaran = ?
                        WHERE
                            kodeAntrian = ?
                        ',
                        [
                            $statusPembayaran,
                            $kodeAntrian,
                        ]
                    );
                }

                if ($status) {
                    $pesan = 'Proses Pembayaran Berhasil';
                } else {
                    $pesan = 'Proses Pembayaran Gagal';
                }
            } else {
                $status = false;
                $pesan = 'Currency Tidak Terdaftar';
            }
        } else if ($flag === 'bukaFinalisasi') {

            $status = statementWrapper(
                DML_DELETE,
                "DELETE FROM
                    pasien_invoice_klinik
                WHERE
                    kodeInvoice = ?
                ",
                [
                    $kodeInvoice
                ]
            );

            if ($status) {
                $statusDeposit = statementWrapper(
                    DML_DELETE,
                    "DELETE FROM
                        pasien_deposit
                    WHERE
                        kodeInvoice = ?
                    ",
                    [
                        $kodeInvoice,
                    ]
                );
                $statusUpdate = statementWrapper(
                    DML_UPDATE,
                    'UPDATE
                        pasien_antrian
                    SET
                        statusPembayaran = ?
                    WHERE
                        kodeAntrian = ?
                    ',
                    [
                        'Belum Bayar',
                        $kodeAntrian,
                    ]
                );

                $pesan = 'Proses Buka Finalisasi Berhasil';
            } else {
                $pesan = 'Proses Buka Finalisasi Gagal';
            }
        } else if ($flag === 'finalisasi') {

            $kodeInvoice = nomorUrut($db, 'invoice_klinik', $idUserAsli);

            $status = statementWrapper(
                DML_INSERT,
                "INSERT INTO
                    pasien_invoice_klinik
                SET
                    kodeAntrian = ?,
                    kodeRM = ?,
                    kodeInvoice = ?,
                    grandTotal = ?,
                    grandTotalHPP = ?,
                    diskon = ?,
                    VAT = ?,
                    payable = ?,
                    tglPembayaran = ?,
                    statusPelunasan = ?,
                    tanggalPelunasan = ?,
                    idUser = ?
                ",
                [
                    $kodeAntrian,
                    $kodeRM,
                    $kodeInvoice,
                    $grandTotal,
                    $grandTotalHPP,
                    ubahToInt($diskon),
                    NULL,
                    $payable,
                    $tglPembayaran,
                    'Aktif',
                    NULL,
                    $idUserAsli
                ]
            );

            if ($status) {
                updateNomorUrut($db, 'invoice_klinik');
                $pesan = 'Proses Pembuatan Invoice Berhasil';
            } else {
                $pesan = 'Proses Pembuatan Invoice Gagal';
            }
        } else if ($flag === 'change') {
            $listDetailItem = [
                'obat' => [
                    'tabel_klinik' => 'pasien_obat_klinik',
                    'col_alias_klinik' => 'ObatKlinik',
                    'tabel_harga' => 'obat_harga',
                    'tabel_item' => 'obat',
                    'col_alias_item' => 'Obat',
                ],
                'alkes' => [
                    'tabel_klinik' => 'pasien_alkes_klinik',
                    'col_alias_klinik' => 'AlkesKlinik',
                    'tabel_harga' => 'alkes_harga',
                    'tabel_item' => 'alkes',
                    'col_alias_item' => 'Alkes',
                ],
                'tindakan' => [
                    'tabel_klinik' => 'pasien_tindakan_klinik',
                    'col_alias_klinik' => 'TindakanKlinik',
                    'tabel_harga' => 'tindakan_harga',
                    'tabel_item' => 'tindakan',
                    'col_alias_item' => 'Tindakan',
                ],
                'prosedur_laboratorium' => [
                    'tabel_klinik' => 'pasien_laboratorium_klinik',
                    'col_alias_klinik' => 'LaboratoriumKlinik',
                    'tabel_harga' => 'prosedur_laboratorium_harga',
                    'tabel_item' => 'prosedur_laboratorium',
                    'col_alias_item' => 'ProsedurLaboratorium',
                ],
                'escort' => [
                    'tabel_klinik' => 'pasien_escort_klinik',
                    'col_alias_klinik' => 'EscortKlinik',
                    'tabel_harga' => 'escort_harga',
                    'tabel_item' => 'escort',
                    'col_alias_item' => 'Escort',
                ],
            ];

            if (isset($listDetailItem[$jenisItem])) {
                $detail = $listDetailItem[$jenisItem];

                if ($idHarga === '__FREE__') {
                    $status = statementWrapper(
                        DML_UPDATE,
                        "UPDATE
                            {$detail['tabel_klinik']}
                        SET
                            subTotal = 0,
                            bebasBiaya = ?
                        WHERE
                            id{$detail['col_alias_klinik']} = ?
                        ",
                        ['Ya', $idItemKlinik]
                    );
                } else {
                    $dataItem = statementWrapper(
                        DML_SELECT,
                        "SELECT 
                            {$detail['tabel_harga']}.*, 
                            {$detail['tabel_item']}.* 
                        FROM 
                            {$detail['tabel_item']}
                            INNER JOIN {$detail['tabel_harga']} ON {$detail['tabel_item']}.kode{$detail['col_alias_item']} = {$detail['tabel_harga']}.kode{$detail['col_alias_item']}
                        WHERE
                            {$detail['tabel_harga']}.id{$detail['col_alias_item']}Harga = ?
                        ",
                        [$idHarga]
                    );

                    $status = statementWrapper(
                        DML_UPDATE,
                        "UPDATE
                            {$detail['tabel_klinik']}
                        SET
                            harga = ?,
                            subTotal = ? * qty,
                            jenisHarga = ?,
                            idAdmisi = ?,
                            bebasBiaya = ?
                        WHERE
                            id{$detail['col_alias_klinik']} = ?
                        ",
                        [$dataItem['nominal'], $dataItem['nominal'], $dataItem['jenisHarga'], $dataItem['idAdmisi'], 'Tidak', $idItemKlinik]
                    );
                }

                if ($status) {
                    $pesan = 'Proses Ganti Harga Berhasil';
                } else {
                    $pesan = 'Proses Ganti Harga Gagal';
                }
            } else {
                $status = false;
                $pesan = 'Item Tidak Tersedia';
            }
        } else {
            $status = false;
            $pesan = 'Proses Tidak Tersedia';
        }
    } catch (PDOException $e) {
        $status = false;
        $pesan = 'Terdapat Kesalahan Dalam Proses Input ke Database';
    } finally {
        $data = compact('status', 'pesan');
    }
}

echo json_encode($data);
