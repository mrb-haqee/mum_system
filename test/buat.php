<?php

include_once 'library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsiutilitas.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsinomor.php";
include_once "{$constant('BASE_URL_PHP')}/{$constant('MAIN_DIR')}/fungsinavigasi.php";


$AccountData = [
    ['01', 'KAS', 1],
    ['02', 'BANK', 1],
    ['03', 'PIUTANG', 1],
    ['04', 'BARANG', 1],
    ['05', 'INVENTARIS', 1],
    ['06', 'TANAH', 1],
    ['07', 'PENYUSUTAN', 1],
    ['08', 'UTANG', 1],
    ['09', 'PENGAMBILAN / SETORAN PRIBADI', 1],
    ['10', 'PAJAK-Penghasilan (PPh)', 1],
    ['11', 'PPh Pasal 4 Ayat 2 (Final)', 1],
    ['12', 'PPh Pasal 21', 1],
    ['13', 'Pajak Pertambahan Nilai (PPN)', 1],
    ['14', 'MODAL', 1],
    ['15', 'RUGI / LABA', 1],
    ['16', 'BIAYA LANGSUNG', 1],
    ['17', 'BIAYA TAK LANGSUNG', 1],
    ['18', 'Pendapatan Lain - lain', 1]
];

// Data untuk di- ke tabel sub_account
$SubAccountData = [
    ['02', '02.1', 'BPD Cabang Utama Denpasar', 1],
    ['02', '02.2', 'Bank Mandiri Cabang Denpasar - Gatot Subroto', 1],
    ['02', '02.3', 'Bank Danamon', 1],
    ['02', '02.4', 'Bank BRI', 1],
    ['02', '02.5', 'Bank BTPN', 1],
    ['02', '02.6', 'Bank Mandiri (Gaji)', 1],
    ['03', '03.1', 'Piutang Dagang / Proyek', 1],
    ['03', '03.2', 'Piutang Umum Jangka Panjang', 1],
    ['03', '03.3', 'Piutang Umum Jangka Pendek', 1],
    ['03', '03.4', 'Piutang Lainnya', 1],
    ['05', '05.1', 'Kantor / Gudang', 1],
    ['05', '05.2', 'Kendaraan Bermotor', 1],
    ['05', '05.3', 'Alat/Peralatan Kantor', 1],
    ['05', '05.4', 'Alat / Peralatan Kerja', 1],
    ['08', '08.1', 'Utang Dagang / Proyek', 1],
    ['08', '08.2', 'Utang Umum Jangka Pendek', 1],
    ['08', '08.3', 'Utang Bank', 1],
    ['08', '08.4', 'Utang Lainnya', 1],
    ['10', '10.1', 'PPh Pasal 22', 1],
    ['10', '10.2', 'PPh Pasal 23', 1],
    ['10', '10.3', 'PPh Pasal 25', 1],
    ['17', '17.1', 'Biaya Gaji', 1],
    ['17', '17.2', 'Biaya Administrasi Kantor', 1],
    ['17', '17.3', 'Biaya Listrik, Telepon & Air', 1],
    ['17', '17.4', 'Angkutan dan BBM', 1],
    ['17', '17.5', 'Biaya Pemasaran / Marketing', 1],
    ['17', '17.6.1', 'Spare Part & Bahan', 1],
    ['17', '17.6.2', 'Upah', 1],
    ['17', '17.7', 'Lain - Lain', 1]
];

foreach ($AccountData as $item) {
    $kodeAccount = nomorUrut($db, 'account', 1);
    $status = statementWrapper(
        DML_INSERT,
        'INSERT INTO 
        account
    SET 
        kode = ?,
        kodeAccount= ?,
        namaAccount=?,
        idUser = ?',
        [
            $item[0],
            $kodeAccount,
            $item[1],
            $item[2],
        ]
    );
    updateNomorUrut($db, 'account', 1);
}

foreach ($SubAccountData as $item) {


    $kodeAccount = selectStatement(
        'SELECT kodeAccount FROM account WHERE kode = ?',
        [$item[0]],
        'fetch'
    );


    $status = statementWrapper(
        DML_INSERT,
        'INSERT INTO 
        sub_account
    SET 
        kodeAccount = ?,
        kodeSub = ?,
        namaSubAccount=?,
        idUser = ?',
        [
            $kodeAccount[0],
            $item[1],
            $item[2],
            $item[3],
        ]
    );
}
