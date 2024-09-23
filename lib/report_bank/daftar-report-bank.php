<?php
include_once '../../../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasicurrency.php";
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

    $idKlinikAsal = dekripsi($_SESSION['enc_idKlinik'], secretKey());
    $exRentang = explode(' - ', $periode);

    if (isset($exRentang[0]) && isset($exRentang[1])) {

        function array_flat_recursive(...$list)
        {
            $output = [];

            foreach ($list as $value) {
                if (is_array($value)) {
                    $output = array_merge($output, array_flat_recursive(...$value));
                } else {
                    $output[] = $value;
                }
            }

            return $output;
        };

        [$tanggalAwal, $tanggalAkhir] = $exRentang;

        // Debet Setor Tunai
        $debet['Setor Tunai'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(transfer_penjualan_tunai.nominal) as debet
            FROM
                transfer_penjualan_tunai 
                INNER JOIN klinik ON transfer_penjualan_tunai.idKlinikAsal = klinik.idKlinik
            WHERE
                transfer_penjualan_tunai.tglTransfer < ?
                AND transfer_penjualan_tunai.idTujuan = ?
                AND transfer_penjualan_tunai.currency = ?
                AND transfer_penjualan_tunai.tipe = 'Bank'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        // Debet Pemasukan lain
        $debet['Pemasukan Lain'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pemasukan_pengeluaran_lain.nominal) AS debet
            FROM
                pemasukan_pengeluaran_lain
                INNER JOIN kode_akunting ON pemasukan_pengeluaran_lain.kodeAkun = kode_akunting.kodeAkun
            WHERE
                pemasukan_pengeluaran_lain.tanggal < ?
                AND pemasukan_pengeluaran_lain.idBank = ?
                AND pemasukan_pengeluaran_lain.currency = ?
                AND pemasukan_pengeluaran_lain.tipe = 'Pemasukan Lain'
                AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        // Debet Pembayaran Transfer
        $debet['Pembayaran Transfer'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM((pasien_deposit.nominal + COALESCE(total_kelebihan.total,0) - COALESCE(total_tipping.total,0))) AS debet
            FROM
                pasien_deposit
                INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pasien_invoice_klinik ON pasien_deposit.kodeInvoice = pasien_invoice_klinik.kodeInvoice
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pasien_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pasien_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pasien_deposit_tambahan.nominal) as total,
                        pasien_deposit_tambahan.kodeDeposit
                    FROM
                        pasien_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pasien_deposit_tambahan.jenis = 'Tipping'
                        AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pasien_deposit_tambahan.kodeDeposit
                ) total_tipping ON pasien_deposit.kodeDeposit = total_tipping.kodeDeposit
                INNER JOIN pasien_antrian ON pasien_deposit.kodeAntrian = pasien_antrian.kodeAntrian
            WHERE
                pasien_deposit.tanggal < ?
                AND pasien_antrian.statusAntrian = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pasien_deposit.currency = ?
                AND pasien_deposit.metodePembayaran = 'Non Tunai' 
                AND pasien_deposit.idTransferEDC IS NULL
            ",
            [$idBank, $tanggalAwal, $idBank,  $currency]
        );

        // Debet Pembayaran Transfer EDC
        $debet['Pembayaran Transfer EDC (= "-")'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pasien_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) AS debet
            FROM
                pasien_deposit
                INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pasien_invoice_klinik ON pasien_deposit.kodeInvoice = pasien_invoice_klinik.kodeInvoice
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pasien_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pasien_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pasien_deposit_tambahan.nominal) as total,
                        pasien_deposit_tambahan.kodeDeposit
                    FROM
                        pasien_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pasien_deposit_tambahan.jenis = 'Tipping'
                        AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pasien_deposit_tambahan.kodeDeposit
                ) total_tipping ON pasien_deposit.kodeDeposit = total_tipping.kodeDeposit
                INNER JOIN pasien_antrian ON pasien_deposit.kodeAntrian = pasien_antrian.kodeAntrian
            WHERE
                pasien_deposit.tanggal < ?
                AND pasien_antrian.statusAntrian = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pasien_deposit.currency = ?
                AND pasien_deposit.metodePembayaran = 'Non Tunai'
                AND pasien_deposit.idTransferEDC IS NOT NULL
                AND pasien_deposit.noBatch = '-'
            ",
            [$idBank, $tanggalAwal, $idBank,  $currency]
        );

        // Debet Pembayaran Transfer EDC
        $debet['Pembayaran Transfer EDC (!= "-")'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pasien_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) AS debet
            FROM
                pasien_deposit
                INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pasien_invoice_klinik ON pasien_deposit.kodeInvoice = pasien_invoice_klinik.kodeInvoice
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pasien_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pasien_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pasien_deposit_tambahan.nominal) as total,
                        pasien_deposit_tambahan.kodeDeposit
                    FROM
                        pasien_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pasien_deposit_tambahan.jenis = 'Tipping'
                        AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pasien_deposit_tambahan.kodeDeposit
                ) total_tipping ON pasien_deposit.kodeDeposit = total_tipping.kodeDeposit
                INNER JOIN pasien_antrian ON pasien_deposit.kodeAntrian = pasien_antrian.kodeAntrian
            WHERE
                pasien_deposit.tanggal < ?
                AND pasien_antrian.statusAntrian = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pasien_deposit.currency = ? 
                AND pasien_deposit.metodePembayaran = 'Non Tunai' 
                AND pasien_deposit.idTransferEDC IS NOT NULL
                AND pasien_deposit.noBatch != '-'
            ",
            [$idBank, $tanggalAwal, $idBank, $currency]
        );

        // Debet Pembayaran Infusion Transfer
        $debet['Pembayaran Infusion Transfer'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pelayanan_infusion_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet
            FROM
                pelayanan_infusion_deposit
                INNER JOIN pelayanan_infusion_pembayaran ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion_pembayaran.kodePelayananInfusion
                INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pelayanan_infusion ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion.kodePelayananInfusion
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pelayanan_infusion_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pelayanan_infusion_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pelayanan_infusion_deposit_tambahan.nominal) as total,
                        pelayanan_infusion_deposit_tambahan.kodeDeposit
                    FROM
                        pelayanan_infusion_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pelayanan_infusion_deposit_tambahan.jenis = 'Tipping'
                        AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pelayanan_infusion_deposit_tambahan.kodeDeposit
                ) total_tipping ON pelayanan_infusion_deposit.kodeDeposit = total_tipping.kodeDeposit
            WHERE
                pelayanan_infusion_deposit.tanggal < ?
                AND pelayanan_infusion.statusPelayananInfusion = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pelayanan_infusion_deposit.currency = ?
                AND pelayanan_infusion_deposit.idTransferEDC IS NULL
                AND pelayanan_infusion_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$idBank, $tanggalAwal, $idBank,  $currency]
        );

        // Debet Pembayaran Infusion Transfer EDC
        $debet['Pembayaran Infusion Transfer EDC (= "-")'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pelayanan_infusion_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet
            FROM
                pelayanan_infusion_deposit
                INNER JOIN pelayanan_infusion_pembayaran ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion_pembayaran.kodePelayananInfusion
                INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pelayanan_infusion ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion.kodePelayananInfusion
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pelayanan_infusion_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pelayanan_infusion_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pelayanan_infusion_deposit_tambahan.nominal) as total,
                        pelayanan_infusion_deposit_tambahan.kodeDeposit
                    FROM
                        pelayanan_infusion_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pelayanan_infusion_deposit_tambahan.jenis = 'Tipping'
                        AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pelayanan_infusion_deposit_tambahan.kodeDeposit
                ) total_tipping ON pelayanan_infusion_deposit.kodeDeposit = total_tipping.kodeDeposit
            WHERE
                pelayanan_infusion_deposit.tanggal < ?
                AND pelayanan_infusion.statusPelayananInfusion = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pelayanan_infusion_deposit.currency = ?
                AND pelayanan_infusion_deposit.idTransferEDC IS NOT NULL
                AND pelayanan_infusion_deposit.noBatch = '-'
                AND pelayanan_infusion_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$idBank, $tanggalAwal, $idBank,  $currency]
        );
        // Debet Pembayaran Infusion Transfer EDC
        $debet['Pembayaran Infusion Transfer EDC (!= "-")'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pelayanan_infusion_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet
            FROM
                pelayanan_infusion_deposit
                INNER JOIN pelayanan_infusion_pembayaran ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion_pembayaran.kodePelayananInfusion
                INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pelayanan_infusion ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion.kodePelayananInfusion
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pelayanan_infusion_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pelayanan_infusion_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pelayanan_infusion_deposit_tambahan.nominal) as total,
                        pelayanan_infusion_deposit_tambahan.kodeDeposit
                    FROM
                        pelayanan_infusion_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pelayanan_infusion_deposit_tambahan.jenis = 'Tipping'
                        AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pelayanan_infusion_deposit_tambahan.kodeDeposit
                ) total_tipping ON pelayanan_infusion_deposit.kodeDeposit = total_tipping.kodeDeposit
            WHERE
                pelayanan_infusion_deposit.tanggal < ?
                AND pelayanan_infusion.statusPelayananInfusion = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pelayanan_infusion_deposit.currency = ?
                AND pelayanan_infusion_deposit.idTransferEDC IS NOT NULL
                AND pelayanan_infusion_deposit.noBatch != '-'
                AND pelayanan_infusion_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$idBank, $tanggalAwal, $idBank,  $currency]
        );

        // Debet Pembayaran POS Obat Transfer
        $debet['Pembayaran POS Obat Transfer'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pos_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet
            FROM
                pos_deposit
                INNER JOIN pos_invoice ON pos_deposit.kodePOS = pos_invoice.kodePOS
                INNER JOIN tujuan_transfer ON pos_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pos ON pos_deposit.kodePOS = pos.kodePOS
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pos_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pos_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pos_deposit_tambahan.nominal) as total,
                        pos_deposit_tambahan.kodeDeposit
                    FROM
                        pos_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pos_deposit_tambahan.jenis = 'Tipping'
                        AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pos_deposit_tambahan.kodeDeposit
                ) total_tipping ON pos_deposit.kodeDeposit = total_tipping.kodeDeposit
            WHERE
                pos_deposit.tanggal < ?
                AND pos.statusPOS = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pos_deposit.currency = ?
                AND pos_deposit.idTransferEDC IS NULL
                AND pos_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$idBank, $tanggalAwal, $idBank,  $currency]
        );

        // Debet Pembayaran POS Obat Transfer EDC
        $debet['Pembayaran POS Obat Transfer EDC (= "-")'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pos_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet
            FROM
                pos_deposit
                INNER JOIN pos_invoice ON pos_deposit.kodePOS = pos_invoice.kodePOS
                INNER JOIN tujuan_transfer ON pos_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pos ON pos_deposit.kodePOS = pos.kodePOS
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pos_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pos_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pos_deposit_tambahan.nominal) as total,
                        pos_deposit_tambahan.kodeDeposit
                    FROM
                        pos_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pos_deposit_tambahan.jenis = 'Tipping'
                        AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pos_deposit_tambahan.kodeDeposit
                ) total_tipping ON pos_deposit.kodeDeposit = total_tipping.kodeDeposit
            WHERE
                pos_deposit.tanggal < ?
                AND pos.statusPOS = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pos_deposit.currency = ?
                AND pos_deposit.idTransferEDC IS NOT NULL
                AND pos_deposit.noBatch = '-'
                AND pos_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$idBank, $tanggalAwal, $idBank, $currency]
        );
        // Debet Pembayaran POS Obat Transfer EDC
        $debet['Pembayaran POS Obat Transfer EDC (!= "-")'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pos_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet
            FROM
                pos_deposit
                INNER JOIN pos_invoice ON pos_deposit.kodePOS = pos_invoice.kodePOS
                INNER JOIN tujuan_transfer ON pos_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pos ON pos_deposit.kodePOS = pos.kodePOS
                LEFT JOIN (
                    SELECT
                        SUM(nominal) as total,
                        kodeDeposit
                    FROM
                        pos_deposit_tambahan
                    WHERE
                        jenis = 'Kelebihan'
                    GROUP BY
                        kodeDeposit
                ) total_kelebihan ON pos_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                LEFT JOIN (
                    SELECT
                        SUM(pos_deposit_tambahan.nominal) as total,
                        pos_deposit_tambahan.kodeDeposit
                    FROM
                        pos_deposit_tambahan
                        INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                    WHERE
                        pos_deposit_tambahan.jenis = 'Tipping'
                        AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                        AND tujuan_transfer.idTujuanTransfer = ?
                    GROUP BY
                        pos_deposit_tambahan.kodeDeposit
                ) total_tipping ON pos_deposit.kodeDeposit = total_tipping.kodeDeposit
            WHERE
                pos_deposit.tanggal < ?
                AND pos.statusPOS = 'Aktif'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pos_deposit.currency = ?
                AND pos_deposit.idTransferEDC IS NOT NULL
                AND pos_deposit.noBatch != '-'
                AND pos_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$idBank, $tanggalAwal, $idBank, $currency]
        );

        // Debet Pembayaran Piutang Insurance
        $debet['Pembayaran Piutang Insurance'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_piutang_deposit.exchangeNominal) as debet
            FROM
                pembayaran_piutang_deposit
                INNER JOIN pembayaran_piutang_invoice ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang_invoice.kodePembayaranPiutang
                INNER JOIN tujuan_transfer ON pembayaran_piutang_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pembayaran_piutang ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang.kodePembayaranPiutang
                INNER JOIN asuransi ON pembayaran_piutang.kodeReferensi = asuransi.kodeAsuransi
            WHERE
                pembayaran_piutang_deposit.tanggal < ?
                AND pembayaran_piutang.statusPembayaranPiutang = 'Aktif'
                AND pembayaran_piutang.jenis = 'Insurance'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_piutang_deposit.currency = ?
                AND pembayaran_piutang_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        // Debet Pembayaran Piutang Insurance
        $debet['Pembayaran Piutang Klien'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_piutang_deposit.exchangeNominal) as debet
            FROM
                pembayaran_piutang_deposit
                INNER JOIN pembayaran_piutang_invoice ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang_invoice.kodePembayaranPiutang
                INNER JOIN tujuan_transfer ON pembayaran_piutang_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pembayaran_piutang ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang.kodePembayaranPiutang
                INNER JOIN hotel ON pembayaran_piutang.kodeReferensi = hotel.kodeHotel
            WHERE
                pembayaran_piutang_deposit.tanggal < ?
                AND pembayaran_piutang.statusPembayaranPiutang = 'Aktif'
                AND pembayaran_piutang.jenis = 'Klien'
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_piutang_deposit.currency = ?
                AND pembayaran_piutang_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );


        // Kredit Pengeluaran lain
        $kredit['Pengeluaran Lain'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(nominal) AS kredit
            FROM
                pemasukan_pengeluaran_lain
                LEFT JOIN kode_akunting ON pemasukan_pengeluaran_lain.kodeAkun = kode_akunting.kodeAkun
            WHERE
                pemasukan_pengeluaran_lain.tanggal < ?
                AND pemasukan_pengeluaran_lain.idBank = ?
                AND pemasukan_pengeluaran_lain.currency = ?
                AND pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
                AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        // Kredit Refund
        $kredit['Refund Pelayanan Umum'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pasien_deposit_tambahan.nominal) as kredit
            FROM
                pasien_deposit_tambahan
                INNER JOIN pasien_deposit ON pasien_deposit_tambahan.kodeDeposit = pasien_deposit.kodeDeposit
                INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
            WHERE
                pasien_deposit_tambahan.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pasien_deposit_tambahan.currency = ?
                AND pasien_deposit_tambahan.jenis = 'Refund'
                AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Refund Pelayanan Infusion'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pelayanan_infusion_deposit_tambahan.nominal) as kredit
            FROM
                pelayanan_infusion_deposit_tambahan
                INNER JOIN pelayanan_infusion_deposit ON pelayanan_infusion_deposit_tambahan.kodeDeposit = pelayanan_infusion_deposit.kodeDeposit
                INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
            WHERE
                pelayanan_infusion_deposit_tambahan.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pelayanan_infusion_deposit_tambahan.currency = ?
                AND pelayanan_infusion_deposit_tambahan.jenis = 'Refund'
                AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Refund POS'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pos_deposit_tambahan.nominal) as kredit
            FROM
                pos_deposit_tambahan
                INNER JOIN pos_deposit ON pos_deposit_tambahan.kodeDeposit = pos_deposit.kodeDeposit
                INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
            WHERE
                pos_deposit_tambahan.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pos_deposit_tambahan.currency = ?
                AND pos_deposit_tambahan.jenis = 'Refund'
                AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        // Kredit Pembayaran Hutang Laboratorium
        $kredit['Pembayaran Hutang Laboratorium'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_hutang.nominal) as kredit
            FROM
                pembayaran_hutang
                INNER JOIN laboratorium_rujukan ON pembayaran_hutang.kodeInstansi = laboratorium_rujukan.kodeLaboratoriumRujukan
                INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
            WHERE
                pembayaran_hutang.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_hutang.currency = ?
                AND pembayaran_hutang.type = 'Laboratorium Rujukan'
                AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Hutang Sampah Medis'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_hutang.nominal) as kredit
            FROM
                pembayaran_hutang
                INNER JOIN vendor ON pembayaran_hutang.kodeInstansi = vendor.kodeVendor
                INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
            WHERE
                pembayaran_hutang.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_hutang.currency = ?
                AND pembayaran_hutang.type = 'Sampah Medis'
                AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Hutang Fee Hotel'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_hutang.nominal) as kredit
            FROM
                pembayaran_hutang
                INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN hotel ON pembayaran_hutang.kodeInstansi = hotel.kodeHotel
            WHERE
                pembayaran_hutang.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_hutang.currency = ?
                AND pembayaran_hutang.type = 'Fee'
                AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Hutang Fee Pelayanan Umum'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_hutang.nominal) as kredit
            FROM
                pembayaran_hutang
                INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pasien_fee ON CONCAT_WS('-','Fee Pelayanan Umum', pasien_fee.idPasienFee) = pembayaran_hutang.kodeInstansi
            WHERE
                pembayaran_hutang.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_hutang.currency = ?
                AND pembayaran_hutang.type = 'Fee'
                AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Hutang Fee Pelayanan Infusion'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(pembayaran_hutang.nominal) as kredit
            FROM
                pembayaran_hutang
                INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN pelayanan_infusion_fee ON CONCAT_WS('-','Fee Pelayanan Infusion', pelayanan_infusion_fee.idPelayananInfusionFee) = pembayaran_hutang.kodeInstansi
            WHERE
                pembayaran_hutang.tanggal < ?
                AND tujuan_transfer.idTujuanTransfer = ?
                AND pembayaran_hutang.currency = ?
                AND pembayaran_hutang.type = 'Fee'
                AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Pembelian PO Stock'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(stock_po_deposit.nominal) as kredit
            FROM
                stock_po_deposit
                INNER JOIN stock_po_klinik ON stock_po_deposit.kodePO = stock_po_klinik.kodePO
                INNER JOIN tujuan_transfer ON stock_po_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN vendor ON stock_po_klinik.kodeVendor = vendor.kodeVendor
            WHERE
                (stock_po_deposit.tanggal < ?)
                AND tujuan_transfer.idTujuanTransfer = ?
                AND stock_po_deposit.currency = ?
                AND stock_po_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Pembelian Langsung Stock'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(stock_pembelian_langsung_deposit.nominal) as kredit
            FROM
                stock_pembelian_langsung_deposit
                INNER JOIN stock_pembelian_langsung ON stock_pembelian_langsung_deposit.kodePembelianLangsung = stock_pembelian_langsung.kodePembelianLangsung
                INNER JOIN tujuan_transfer ON stock_pembelian_langsung_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN vendor ON stock_pembelian_langsung.kodeVendor = vendor.kodeVendor
            WHERE
                (stock_pembelian_langsung_deposit.tanggal < ?)
                AND tujuan_transfer.idTujuanTransfer = ?
                AND stock_pembelian_langsung_deposit.currency = ?
                AND stock_pembelian_langsung_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Pembelian PO Asset'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(asset_po_deposit.nominal) as kredit
            FROM
                asset_po_deposit
                INNER JOIN asset_po_klinik ON asset_po_deposit.kodePO = asset_po_klinik.kodePO
                INNER JOIN tujuan_transfer ON asset_po_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN vendor ON asset_po_klinik.kodeVendor = vendor.kodeVendor
            WHERE
                (asset_po_deposit.tanggal < ?)
                AND tujuan_transfer.idTujuanTransfer = ?
                AND asset_po_deposit.currency = ?
                AND asset_po_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $kredit['Pembayaran Pembelian Langsung Asset'] = statementWrapper(
            DML_SELECT,
            "SELECT
                SUM(asset_pembelian_langsung_deposit.nominal) as kredit
            FROM
                asset_pembelian_langsung_deposit
                INNER JOIN asset_pembelian_langsung ON asset_pembelian_langsung_deposit.kodePembelianLangsung = asset_pembelian_langsung.kodePembelianLangsung
                INNER JOIN tujuan_transfer ON asset_pembelian_langsung_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                INNER JOIN vendor ON asset_pembelian_langsung.kodeVendor = vendor.kodeVendor
            WHERE
                (asset_pembelian_langsung_deposit.tanggal < ?)
                AND tujuan_transfer.idTujuanTransfer = ?
                AND asset_pembelian_langsung_deposit.currency = ?
                AND asset_pembelian_langsung_deposit.metodePembayaran = 'Non Tunai'
            ",
            [$tanggalAwal, $idBank, $currency]
        );

        $saldo = array_sum(array_column($debet, 'debet')) - array_sum(array_column($kredit, 'kredit'));

        $data = statementWrapper(
            DML_SELECT_ALL,
            "SELECT
                *
            FROM
                (
                    (
                        SELECT
                            transfer_penjualan_tunai.tglTransfer as tanggal,
                            CONCAT('Transfer Tunai ', klinik.nama ) as uraian,
                            transfer_penjualan_tunai.nominal as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            transfer_penjualan_tunai.keterangan,
                            transfer_penjualan_tunai.timeStamp as timeStampInput
                        FROM
                            transfer_penjualan_tunai 
                            INNER JOIN klinik ON transfer_penjualan_tunai.idKlinikAsal = klinik.idKlinik
                        WHERE
                            (transfer_penjualan_tunai.tglTransfer BETWEEN ? AND ?)
                            AND transfer_penjualan_tunai.idTujuan = ?
                            AND transfer_penjualan_tunai.currency = ?
                            AND transfer_penjualan_tunai.tipe = 'Bank'
                    )
                    UNION ALL
                    (
                        SELECT
                            pemasukan_pengeluaran_lain.tanggal as tanggal,
                            CONCAT('Pemasukan Lain / ', COALESCE(kode_akunting.namaAkun, CONCAT('(UNKNOWN ACCOUNT ', pemasukan_pengeluaran_lain.kodeAkun,' )'))) as uraian,
                            pemasukan_pengeluaran_lain.nominal as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            pemasukan_pengeluaran_lain.keterangan,
                            pemasukan_pengeluaran_lain.timeStamp as timeStampInput
                        FROM
                            pemasukan_pengeluaran_lain
                            INNER JOIN kode_akunting ON pemasukan_pengeluaran_lain.kodeAkun = kode_akunting.kodeAkun
                        WHERE
                            (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
                            AND pemasukan_pengeluaran_lain.idBank = ?
                            AND pemasukan_pengeluaran_lain.currency = ?
                            AND pemasukan_pengeluaran_lain.tipe = 'Pemasukan Lain'
                            AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
                    )
                    UNION ALL
                    (
                        SELECT
                            pasien_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Transfer ', pasien_deposit.kodeInvoice ) as uraian,
                            (pasien_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pasien_deposit.timeStamp as timeStampInput
                        FROM
                            pasien_deposit
                            INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pasien_invoice_klinik ON pasien_deposit.kodeInvoice = pasien_invoice_klinik.kodeInvoice
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pasien_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pasien_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pasien_deposit_tambahan.nominal) as total,
                                    pasien_deposit_tambahan.kodeDeposit
                                FROM
                                    pasien_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pasien_deposit_tambahan.jenis = 'Tipping'
                                    AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pasien_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pasien_deposit.kodeDeposit = total_tipping.kodeDeposit
                            INNER JOIN pasien_antrian ON pasien_deposit.kodeAntrian = pasien_antrian.kodeAntrian
                        WHERE
                            (pasien_deposit.tanggal BETWEEN ? AND ?)
                            AND pasien_antrian.statusAntrian = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pasien_deposit.currency = ?
                            AND pasien_deposit.metodePembayaran = 'Non Tunai' 
                            AND pasien_deposit.idTransferEDC IS NULL
                    )
                    UNION ALL
                    (
                        SELECT
                            pasien_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran EDC ',noBatch) as uraian,
                            SUM(pasien_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pasien_deposit.timeStamp as timeStampInput
                        FROM
                            pasien_deposit
                            INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pasien_invoice_klinik ON pasien_deposit.kodeInvoice = pasien_invoice_klinik.kodeInvoice
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pasien_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pasien_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pasien_deposit_tambahan.nominal) as total,
                                    pasien_deposit_tambahan.kodeDeposit
                                FROM
                                    pasien_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pasien_deposit_tambahan.jenis = 'Tipping'
                                    AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pasien_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pasien_deposit.kodeDeposit = total_tipping.kodeDeposit
                            INNER JOIN pasien_antrian ON pasien_deposit.kodeAntrian = pasien_antrian.kodeAntrian
                        WHERE
                            (pasien_deposit.tanggal BETWEEN ? AND ?)
                            AND pasien_antrian.statusAntrian = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pasien_deposit.currency = ?
                            AND pasien_deposit.metodePembayaran = 'Non Tunai'
                            AND pasien_deposit.idTransferEDC IS NOT NULL
                            AND pasien_deposit.noBatch = '-'
                        GROUP BY pasien_deposit.tanggal, pasien_deposit.idTransferEDC
                    )
                    UNION ALL
                    (
                        SELECT
                            pasien_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran EDC ',noBatch) as uraian,
                            SUM(pasien_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pasien_deposit.timeStamp as timeStampInput
                        FROM
                            pasien_deposit
                            INNER JOIN tujuan_transfer ON pasien_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pasien_invoice_klinik ON pasien_deposit.kodeInvoice = pasien_invoice_klinik.kodeInvoice
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pasien_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pasien_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pasien_deposit_tambahan.nominal) as total,
                                    pasien_deposit_tambahan.kodeDeposit
                                FROM
                                    pasien_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pasien_deposit_tambahan.jenis = 'Tipping'
                                    AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pasien_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pasien_deposit.kodeDeposit = total_tipping.kodeDeposit
                            INNER JOIN pasien_antrian ON pasien_deposit.kodeAntrian = pasien_antrian.kodeAntrian
                        WHERE
                            (pasien_deposit.tanggal BETWEEN ? AND ?)
                            AND pasien_antrian.statusAntrian = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pasien_deposit.currency = ? 
                            AND pasien_deposit.metodePembayaran = 'Non Tunai' 
                            AND pasien_deposit.idTransferEDC IS NOT NULL
                            AND pasien_deposit.noBatch != '-'
                        GROUP BY pasien_deposit.tanggal, pasien_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pelayanan_infusion_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Transfer Infusion ', pelayanan_infusion_pembayaran.kodeInvoice ) as uraian,
                            (pelayanan_infusion_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pelayanan_infusion_deposit.timeStamp as timeStampInput
                        FROM
                            pelayanan_infusion_deposit
                            INNER JOIN pelayanan_infusion_pembayaran ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion_pembayaran.kodePelayananInfusion
                            INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pelayanan_infusion ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion.kodePelayananInfusion
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pelayanan_infusion_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pelayanan_infusion_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pelayanan_infusion_deposit_tambahan.nominal) as total,
                                    pelayanan_infusion_deposit_tambahan.kodeDeposit
                                FROM
                                    pelayanan_infusion_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pelayanan_infusion_deposit_tambahan.jenis = 'Tipping'
                                    AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pelayanan_infusion_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pelayanan_infusion_deposit.kodeDeposit = total_tipping.kodeDeposit
                        WHERE
                            (pelayanan_infusion_deposit.tanggal BETWEEN ? AND ?)
                            AND pelayanan_infusion.statusPelayananInfusion = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pelayanan_infusion_deposit.currency = ?
                            AND pelayanan_infusion_deposit.idTransferEDC IS NULL
                            AND pelayanan_infusion_deposit.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pelayanan_infusion_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran EDC Infusion ', pelayanan_infusion_deposit.noBatch ) as uraian,
                            SUM(pelayanan_infusion_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pelayanan_infusion_deposit.timeStamp as timeStampInput
                        FROM
                            pelayanan_infusion_deposit
                            INNER JOIN pelayanan_infusion_pembayaran ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion_pembayaran.kodePelayananInfusion
                            INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pelayanan_infusion ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion.kodePelayananInfusion
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pelayanan_infusion_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pelayanan_infusion_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pelayanan_infusion_deposit_tambahan.nominal) as total,
                                    pelayanan_infusion_deposit_tambahan.kodeDeposit
                                FROM
                                    pelayanan_infusion_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pelayanan_infusion_deposit_tambahan.jenis = 'Tipping'
                                    AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pelayanan_infusion_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pelayanan_infusion_deposit.kodeDeposit = total_tipping.kodeDeposit
                        WHERE
                            (pelayanan_infusion_deposit.tanggal BETWEEN ? AND ?)
                            AND pelayanan_infusion.statusPelayananInfusion = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pelayanan_infusion_deposit.currency = ?
                            AND pelayanan_infusion_deposit.idTransferEDC IS NOT NULL
                            AND pelayanan_infusion_deposit.noBatch = '-'
                            AND pelayanan_infusion_deposit.metodePembayaran = 'Non Tunai'
                        GROUP BY pelayanan_infusion_deposit.tanggal, pelayanan_infusion_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pelayanan_infusion_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran EDC Infusion ', pelayanan_infusion_deposit.noBatch ) as uraian,
                            SUM(pelayanan_infusion_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pelayanan_infusion_deposit.timeStamp as timeStampInput
                        FROM
                            pelayanan_infusion_deposit
                            INNER JOIN pelayanan_infusion_pembayaran ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion_pembayaran.kodePelayananInfusion
                            INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pelayanan_infusion ON pelayanan_infusion_deposit.kodePelayananInfusion = pelayanan_infusion.kodePelayananInfusion
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pelayanan_infusion_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pelayanan_infusion_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pelayanan_infusion_deposit_tambahan.nominal) as total,
                                    pelayanan_infusion_deposit_tambahan.kodeDeposit
                                FROM
                                    pelayanan_infusion_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pelayanan_infusion_deposit_tambahan.jenis = 'Tipping'
                                    AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pelayanan_infusion_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pelayanan_infusion_deposit.kodeDeposit = total_tipping.kodeDeposit
                        WHERE
                            (pelayanan_infusion_deposit.tanggal BETWEEN ? AND ?)
                            AND pelayanan_infusion.statusPelayananInfusion = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pelayanan_infusion_deposit.currency = ?
                            AND pelayanan_infusion_deposit.idTransferEDC IS NOT NULL
                            AND pelayanan_infusion_deposit.noBatch != '-'
                            AND pelayanan_infusion_deposit.metodePembayaran = 'Non Tunai'
                        GROUP BY pelayanan_infusion_deposit.tanggal, pelayanan_infusion_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pos_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Transfer POS Obat ', pos_invoice.kodeInvoice ) as uraian,
                            (pos_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pos_deposit.timeStamp as timeStampInput
                        FROM
                            pos_deposit
                            INNER JOIN pos_invoice ON pos_deposit.kodePOS = pos_invoice.kodePOS
                            INNER JOIN tujuan_transfer ON pos_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pos ON pos_deposit.kodePOS = pos.kodePOS
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pos_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pos_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pos_deposit_tambahan.nominal) as total,
                                    pos_deposit_tambahan.kodeDeposit
                                FROM
                                    pos_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pos_deposit_tambahan.jenis = 'Tipping'
                                    AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pos_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pos_deposit.kodeDeposit = total_tipping.kodeDeposit
                        WHERE
                            (pos_deposit.tanggal BETWEEN ? AND ?)
                            AND pos.statusPOS = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pos_deposit.currency = ?
                            AND pos_deposit.idTransferEDC IS NULL
                            AND pos_deposit.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pos_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran EDC POS Obat ', pos_deposit.noBatch ) as uraian,
                            SUM(pos_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pos_deposit.timeStamp as timeStampInput
                        FROM
                            pos_deposit
                            INNER JOIN pos_invoice ON pos_deposit.kodePOS = pos_invoice.kodePOS
                            INNER JOIN tujuan_transfer ON pos_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pos ON pos_deposit.kodePOS = pos.kodePOS
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pos_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pos_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pos_deposit_tambahan.nominal) as total,
                                    pos_deposit_tambahan.kodeDeposit
                                FROM
                                    pos_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pos_deposit_tambahan.jenis = 'Tipping'
                                    AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pos_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pos_deposit.kodeDeposit = total_tipping.kodeDeposit
                        WHERE
                            (pos_deposit.tanggal BETWEEN ? AND ?)
                            AND pos.statusPOS = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pos_deposit.currency = ?
                            AND pos_deposit.idTransferEDC IS NOT NULL
                            AND pos_deposit.noBatch = '-'
                            AND pos_deposit.metodePembayaran = 'Non Tunai'
                        GROUP BY pos_deposit.tanggal, pos_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pos_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran EDC POS Obat ', pos_deposit.noBatch ) as uraian,
                            SUM(pos_deposit.nominal + COALESCE(total_kelebihan.total,0) + COALESCE(total_tipping.total,0)) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pos_deposit.timeStamp as timeStampInput
                        FROM
                            pos_deposit
                            INNER JOIN pos_invoice ON pos_deposit.kodePOS = pos_invoice.kodePOS
                            INNER JOIN tujuan_transfer ON pos_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pos ON pos_deposit.kodePOS = pos.kodePOS
                            LEFT JOIN (
                                SELECT
                                    SUM(nominal) as total,
                                    kodeDeposit
                                FROM
                                    pos_deposit_tambahan
                                WHERE
                                    jenis = 'Kelebihan'
                                GROUP BY
                                    kodeDeposit
                            ) total_kelebihan ON pos_deposit.kodeDeposit = total_kelebihan.kodeDeposit
                            LEFT JOIN (
                                SELECT
                                    SUM(pos_deposit_tambahan.nominal) as total,
                                    pos_deposit_tambahan.kodeDeposit
                                FROM
                                    pos_deposit_tambahan
                                    INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                                WHERE
                                    pos_deposit_tambahan.jenis = 'Tipping'
                                    AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                                    AND tujuan_transfer.idTujuanTransfer = ?
                                GROUP BY
                                    pos_deposit_tambahan.kodeDeposit
                            ) total_tipping ON pos_deposit.kodeDeposit = total_tipping.kodeDeposit
                        WHERE
                            (pos_deposit.tanggal BETWEEN ? AND ?)
                            AND pos.statusPOS = 'Aktif'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pos_deposit.currency = ?
                            AND pos_deposit.idTransferEDC IS NOT NULL
                            AND pos_deposit.noBatch != '-'
                            AND pos_deposit.metodePembayaran = 'Non Tunai'
                        GROUP BY pos_deposit.tanggal, pos_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_piutang_deposit.tanggal as tanggal,
                            CONCAT('Piutang Insurance ', asuransi.nama) as uraian,
                            SUM(pembayaran_piutang_deposit.exchangeNominal) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pembayaran_piutang_deposit.timeStamp as timeStampInput
                        FROM
                            pembayaran_piutang_deposit
                            INNER JOIN pembayaran_piutang_invoice ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang_invoice.kodePembayaranPiutang
                            INNER JOIN tujuan_transfer ON pembayaran_piutang_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pembayaran_piutang ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang.kodePembayaranPiutang
                            INNER JOIN asuransi ON pembayaran_piutang.kodeReferensi = asuransi.kodeAsuransi
                        WHERE
                            (pembayaran_piutang_deposit.tanggal BETWEEN ? AND ?)
                            AND pembayaran_piutang.statusPembayaranPiutang = 'Aktif'
                            AND pembayaran_piutang.jenis = 'Insurance'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_piutang_deposit.currency = ?
                            AND pembayaran_piutang_deposit.metodePembayaran = 'Non Tunai'
                        GROUP BY pembayaran_piutang_deposit.tanggal, pembayaran_piutang_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_piutang_deposit.tanggal as tanggal,
                            CONCAT('Piutang Klien ', hotel.namaHotel) as uraian,
                            SUM(pembayaran_piutang_deposit.exchangeNominal) as debet,
                            0 as kredit,
                            'tambah' as jenis,
                            '' as keterangan,
                            pembayaran_piutang_deposit.timeStamp as timeStampInput
                        FROM
                            pembayaran_piutang_deposit
                            INNER JOIN pembayaran_piutang_invoice ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang_invoice.kodePembayaranPiutang
                            INNER JOIN tujuan_transfer ON pembayaran_piutang_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pembayaran_piutang ON pembayaran_piutang_deposit.kodePembayaranPiutang = pembayaran_piutang.kodePembayaranPiutang
                            INNER JOIN hotel ON pembayaran_piutang.kodeReferensi = hotel.kodeHotel
                        WHERE
                            (pembayaran_piutang_deposit.tanggal BETWEEN ? AND ?)
                            AND pembayaran_piutang.statusPembayaranPiutang = 'Aktif'
                            AND pembayaran_piutang.jenis = 'Klien'
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_piutang_deposit.currency = ?
                            AND pembayaran_piutang_deposit.metodePembayaran = 'Non Tunai'
                        GROUP BY pembayaran_piutang_deposit.tanggal, pembayaran_piutang_deposit.noBatch
                    )
                    UNION ALL
                    (
                        SELECT
                            pemasukan_pengeluaran_lain.tanggal as tanggal,
                            CONCAT('Pengeluaran Lain / ', COALESCE(kode_akunting.namaAkun, CONCAT('(UNKNOWN ACCOUNT ', pemasukan_pengeluaran_lain.kodeAkun,' )'))) as uraian,
                            0 as debet,
                            pemasukan_pengeluaran_lain.nominal as kredit,
                            'kurang' as jenis,
                            pemasukan_pengeluaran_lain.keterangan as keterangan,
                            pemasukan_pengeluaran_lain.timeStamp as timeStampInput
                        FROM
                            pemasukan_pengeluaran_lain
                            LEFT JOIN kode_akunting ON pemasukan_pengeluaran_lain.kodeAkun = kode_akunting.kodeAkun
                        WHERE
                            (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
                            AND pemasukan_pengeluaran_lain.idBank = ?
                            AND pemasukan_pengeluaran_lain.currency = ?
                            AND pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
                            AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
                    )
                    UNION ALL
                    (
                        SELECT
                            pasien_deposit_tambahan.tanggal as tanggal,
                            CONCAT('Refund Invoice ', pasien_deposit.kodeInvoice) as uraian,
                            0 as debet,
                            pasien_deposit_tambahan.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pasien_deposit_tambahan.timeStamp as timeStampInput
                        FROM
                            pasien_deposit_tambahan
                            INNER JOIN pasien_deposit ON pasien_deposit_tambahan.kodeDeposit = pasien_deposit.kodeDeposit
                            INNER JOIN tujuan_transfer ON pasien_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                        WHERE
                            (pasien_deposit_tambahan.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pasien_deposit_tambahan.currency = ?
                            AND pasien_deposit_tambahan.jenis = 'Refund'
                            AND pasien_deposit_tambahan.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pelayanan_infusion_deposit_tambahan.tanggal as tanggal,
                            CONCAT('Refund Invoice ', pelayanan_infusion_deposit.kodeInvoice) as uraian,
                            0 as debet,
                            pelayanan_infusion_deposit_tambahan.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pelayanan_infusion_deposit_tambahan.timeStamp as timeStampInput
                        FROM
                            pelayanan_infusion_deposit_tambahan
                            INNER JOIN pelayanan_infusion_deposit ON pelayanan_infusion_deposit_tambahan.kodeDeposit = pelayanan_infusion_deposit.kodeDeposit
                            INNER JOIN tujuan_transfer ON pelayanan_infusion_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                        WHERE
                            (pelayanan_infusion_deposit_tambahan.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pelayanan_infusion_deposit_tambahan.currency = ?
                            AND pelayanan_infusion_deposit_tambahan.jenis = 'Refund'
                            AND pelayanan_infusion_deposit_tambahan.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pos_deposit_tambahan.tanggal as tanggal,
                            CONCAT('Refund Invoice ', pos_deposit.kodeInvoice) as uraian,
                            0 as debet,
                            pos_deposit_tambahan.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pos_deposit_tambahan.timeStamp as timeStampInput
                        FROM
                            pos_deposit_tambahan
                            INNER JOIN pos_deposit ON pos_deposit_tambahan.kodeDeposit = pos_deposit.kodeDeposit
                            INNER JOIN tujuan_transfer ON pos_deposit_tambahan.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                        WHERE
                            (pos_deposit_tambahan.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pos_deposit_tambahan.currency = ?
                            AND pos_deposit_tambahan.jenis = 'Refund'
                            AND pos_deposit_tambahan.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_hutang.tanggal as tanggal,
                            CONCAT('Pembayaran Hutang ', laboratorium_rujukan.nama) as uraian,
                            0 as debet,
                            pembayaran_hutang.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pembayaran_hutang.timeStamp as timeStampInput
                        FROM
                            pembayaran_hutang
                            INNER JOIN laboratorium_rujukan ON pembayaran_hutang.kodeInstansi = laboratorium_rujukan.kodeLaboratoriumRujukan
                            INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                        WHERE
                            (pembayaran_hutang.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_hutang.currency = ?
                            AND pembayaran_hutang.type = 'Laboratorium Rujukan'
                            AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_hutang.tanggal as tanggal,
                            CONCAT('Pembayaran Hutang ', vendor.nama) as uraian,
                            0 as debet,
                            pembayaran_hutang.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pembayaran_hutang.timeStamp as timeStampInput
                        FROM
                            pembayaran_hutang
                            INNER JOIN vendor ON pembayaran_hutang.kodeInstansi = vendor.kodeVendor
                            INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                        WHERE
                            (pembayaran_hutang.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_hutang.currency = ?
                            AND pembayaran_hutang.type = 'Sampah Medis'
                            AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_hutang.tanggal as tanggal,
                            CONCAT('Pembayaran Hutang ', vendor.nama) as uraian,
                            0 as debet,
                            pembayaran_hutang.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pembayaran_hutang.timeStamp as timeStampInput
                        FROM
                            pembayaran_hutang
                            INNER JOIN vendor ON pembayaran_hutang.kodeInstansi = vendor.kodeVendor
                            INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                        WHERE
                            (pembayaran_hutang.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_hutang.currency = ?
                            AND pembayaran_hutang.type = 'Stock'
                            AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_hutang.tanggal as tanggal,
                            CONCAT('Pembayaran Hutang ', hotel.namaHotel) as uraian,
                            0 as debet,
                            pembayaran_hutang.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pembayaran_hutang.timeStamp as timeStampInput
                        FROM
                            pembayaran_hutang
                            INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN hotel ON pembayaran_hutang.kodeInstansi = hotel.kodeHotel
                        WHERE
                            (pembayaran_hutang.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_hutang.currency = ?
                            AND pembayaran_hutang.type = 'Fee'
                            AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_hutang.tanggal as tanggal,
                            CONCAT('Pembayaran Hutang Fee ', pasien_fee.nama) as uraian,
                            0 as debet,
                            pembayaran_hutang.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pembayaran_hutang.timeStamp as timeStampInput
                        FROM
                            pembayaran_hutang
                            INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pasien_fee ON CONCAT_WS('-','Fee Pelayanan Umum', pasien_fee.idPasienFee) = pembayaran_hutang.kodeInstansi
                        WHERE
                            (pembayaran_hutang.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_hutang.currency = ?
                            AND pembayaran_hutang.type = 'Fee'
                            AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            pembayaran_hutang.tanggal as tanggal,
                            CONCAT('Pembayaran Hutang Fee ', pelayanan_infusion_fee.nama) as uraian,
                            0 as debet,
                            pembayaran_hutang.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            pembayaran_hutang.timeStamp as timeStampInput
                        FROM
                            pembayaran_hutang
                            INNER JOIN tujuan_transfer ON pembayaran_hutang.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN pelayanan_infusion_fee ON CONCAT_WS('-','Fee Pelayanan Infusion', pelayanan_infusion_fee.idPelayananInfusionFee) = pembayaran_hutang.kodeInstansi
                        WHERE
                            (pembayaran_hutang.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND pembayaran_hutang.currency = ?
                            AND pembayaran_hutang.type = 'Fee'
                            AND pembayaran_hutang.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            stock_po_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Pembelian PO Stock') as uraian,
                            0 as debet,
                            stock_po_deposit.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            stock_po_deposit.timeStamp as timeStampInput
                        FROM
                            stock_po_deposit
                            INNER JOIN stock_po_klinik ON stock_po_deposit.kodePO = stock_po_klinik.kodePO
                            INNER JOIN tujuan_transfer ON stock_po_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN vendor ON stock_po_klinik.kodeVendor = vendor.kodeVendor
                        WHERE
                            (stock_po_deposit.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND stock_po_deposit.currency = ?
                            AND stock_po_deposit.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            stock_pembelian_langsung_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Pembelian Langsung Stock') as uraian,
                            0 as debet,
                            stock_pembelian_langsung_deposit.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            stock_pembelian_langsung_deposit.timeStamp as timeStampInput
                        FROM
                            stock_pembelian_langsung_deposit
                            INNER JOIN stock_pembelian_langsung ON stock_pembelian_langsung_deposit.kodePembelianLangsung = stock_pembelian_langsung.kodePembelianLangsung
                            INNER JOIN tujuan_transfer ON stock_pembelian_langsung_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN vendor ON stock_pembelian_langsung.kodeVendor = vendor.kodeVendor
                        WHERE
                            (stock_pembelian_langsung_deposit.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND stock_pembelian_langsung_deposit.currency = ?
                            AND stock_pembelian_langsung_deposit.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            asset_po_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Pembelian PO Asset') as uraian,
                            0 as debet,
                            asset_po_deposit.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            asset_po_deposit.timeStamp as timeStampInput
                        FROM
                            asset_po_deposit
                            INNER JOIN asset_po_klinik ON asset_po_deposit.kodePO = asset_po_klinik.kodePO
                            INNER JOIN tujuan_transfer ON asset_po_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN vendor ON asset_po_klinik.kodeVendor = vendor.kodeVendor
                        WHERE
                            (asset_po_deposit.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND asset_po_deposit.currency = ?
                            AND asset_po_deposit.metodePembayaran = 'Non Tunai'
                    )
                    UNION ALL
                    (
                        SELECT
                            asset_pembelian_langsung_deposit.tanggal as tanggal,
                            CONCAT('Pembayaran Pembelian Langsung Asset') as uraian,
                            0 as debet,
                            asset_pembelian_langsung_deposit.nominal as kredit,
                            'kurang' as jenis,
                            '' as keterangan,
                            asset_pembelian_langsung_deposit.timeStamp as timeStampInput
                        FROM
                            asset_pembelian_langsung_deposit
                            INNER JOIN asset_pembelian_langsung ON asset_pembelian_langsung_deposit.kodePembelianLangsung = asset_pembelian_langsung.kodePembelianLangsung
                            INNER JOIN tujuan_transfer ON asset_pembelian_langsung_deposit.kodeReferensi = tujuan_transfer.kodeTujuanTransfer
                            INNER JOIN vendor ON asset_pembelian_langsung.kodeVendor = vendor.kodeVendor
                        WHERE
                            (asset_pembelian_langsung_deposit.tanggal BETWEEN ? AND ?)
                            AND tujuan_transfer.idTujuanTransfer = ?
                            AND asset_pembelian_langsung_deposit.currency = ?
                            AND asset_pembelian_langsung_deposit.metodePembayaran = 'Non Tunai'
                    )       
                ) detail_tunai
                ORDER BY tanggal, timeStampInput
        ",
            array_flat_recursive(
                array_fill(
                    0,
                    2,
                    [
                        $tanggalAwal,
                        $tanggalAkhir,
                        $idBank,
                        $currency,
                    ]
                ),
                array_fill(
                    0,
                    9,
                    [
                        $idBank,
                        $tanggalAwal,
                        $tanggalAkhir,
                        $idBank,
                        $currency,
                    ]
                ),
                array_fill(
                    0,
                    16,
                    [
                        $tanggalAwal,
                        $tanggalAkhir,
                        $idBank,
                        $currency,
                    ]
                ),
            )
        );


        $detailCurrency = getCurrencyList($currency);
?>
        <table class="table table-hover table-bordered">
            <thead class="alert alert-danger">
                <tr>
                    <th class="text-center align-middle" style="width: 5%;">NO</th>
                    <th class="text-center align-middle">TANGGAL</th>
                    <th class="text-center align-middle">URAIAN</th>
                    <th class="text-center align-middle">DEBET</th>
                    <th class="text-center align-middle">KREDIT</th>
                    <th class="text-center align-middle">SALDO</th>
                    <th class="text-center align-middle">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5"><strong>SALDO AWAL</strong></td>
                    <td style="text-align: right;"> <?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($saldo, 2) ?></td>
                    <td></td>
                </tr>
                <?php
                $n = 1;

                $totalDebet = empty($data) ? 0 : array_sum(array_column($data, 'debet'));
                $totalKredit = empty($data) ? 0 : array_sum(array_column($data, 'kredit'));

                foreach ($data as $row) {
                    $saldo += floatval($row['debet']) - floatval($row['kredit']);
                ?>
                    <tr>
                        <td class="text-center align-middle"><?= $n ?> </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= ubahTanggalIndo($row['tanggal']) ?></span>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold"><?= $row['uraian'] ?></span>
                        </td>
                        <td>
                            <?php
                            if ($row['jenis'] === 'kurang') {
                            ?>
                                <span class="d-block font-weight-bold">-</span>
                            <?php
                            } else {
                            ?>
                                <span class="d-block font-weight-bold" style="text-align: right;"><?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($row['debet'], 2) ?></span>
                            <?php
                            } ?>
                        </td>
                        <td>
                            <?php
                            if ($row['jenis'] === 'tambah') {
                            ?>
                                <span class="d-block font-weight-bold">-</span>
                            <?php
                            } else {
                            ?>
                                <span class="d-block font-weight-bold" style="text-align: right;"><?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($row['kredit'], 2) ?></span>
                            <?php
                            } ?>
                        </td>
                        <td>
                            <span class="d-block font-weight-bold" style="text-align: right;"><?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($saldo, 2) ?></span>
                        </td>
                        <td><?= $row['keterangan'] ?></td>
                    </tr>
                <?php
                    $n++;
                }
                ?>
                <tr>
                    <td colspan="3"> <strong>TOTAL</strong> </td>
                    <td style="text-align: right;"><?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($totalDebet, 2) ?></td>
                    <td style="text-align: right;"><?= $detailCurrency['symbol']; ?> <?= ubahToRupiahDesimal($totalKredit, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
<?php
    }
}
?>