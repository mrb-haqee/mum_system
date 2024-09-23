-- Active: 1725330860668@@127.0.0.1@3306@masterdb

USE ptmargautamamandiri;

CREATE TABLE `account` (
    `idAccount` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeAccount` varchar(255) NOT NULL UNIQUE,
    `kode` varchar(255) NOT NULL UNIQUE,
    `namaAccount` varchar(255) NOT NULL,
    `statusAccount` varchar(255) DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `sub_account` (
    `idSubAccount` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeAccount` varchar(255) NOT NULL,
    `kodeSub` varchar(255) NOT NULL UNIQUE,
    `namaSubAccount` varchar(255) NOT NULL,
    `statusSubAccount` varchar(255) DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `bank` (
    `idBank` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeBank` varchar(255) NOT NULL UNIQUE,
    `atasNama` varchar(255) NOT NULL,
    `nomerRekening` varchar(255) NOT NULL,
    `vendor` varchar(255) NOT NULL,
    `statusBank` varchar(255) DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `petty_cash` (
    `idPettyCash` bigint PRIMARY KEY AUTO_INCREMENT,
    `kodePettyCash` varchar(255) NOT NULL,
    `tipe` varchar(100) NOT NULL,
    `namaPettyCash` varchar(255) NOT NULL,
    `statusPettyCash` varchar(100) NOT NULL DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `pemasukan_pengeluaran_lain` (
    `idPemasukanPengeluaranLain` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodePemasukanPengeluaranLain` varchar(255) NOT NULL UNIQUE,
    `kodeSub` varchar(255) NOT NULL,
    `tipe` varchar(255) NOT NULL,
    `jenisRekening` varchar(255) NOT NULL,
    `idRekening` bigint(20),
    `nominal` bigint(20),
    `keterangan` varchar(255) NOT NULL,
    `tanggal` DATE NOT NULL,
    `statusPemasukanPengeluaranLain` varchar(255) DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `biaya` (
    `idBiaya` bigint PRIMARY KEY AUTO_INCREMENT,
    `kodeBiaya` varchar(255) UNIQUE NOT NULL,
    `tglBiaya` date NOT NULL,
    `kodeAccount` varchar(100) NOT NULL,
    `nomorNota` varchar(255) NOT NULL,
    `grandTotal` decimal(15, 0) NOT NULL,
    `statusBiaya` varchar(100) NOT NULL,
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `biaya_detail` (
    `idBiayaDetail` bigint PRIMARY KEY AUTO_INCREMENT,
    `kodeBiaya` varchar(255) NOT NULL,
    `namaItem` varchar(255) NOT NULL,
    `qty` int NOT NULL,
    `hargaSatuan` decimal(15, 0) NOT NULL,
    `subTotal` decimal(15, 0) NOT NULL,
    `statusBiayaDetail` varchar(100) NOT NULL,
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `vendor` (
    `idVendor` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeVendor` varchar(100) NOT NULL,
    `jenisVendor` varchar(50) NOT NULL,
    `contactPerson` varchar(255) NOT NULL,
    `noTelpCP` varchar(30) NOT NULL,
    `nama` varchar(255) NOT NULL,
    `alamat` text NOT NULL,
    `noTelp` varchar(255) NOT NULL,
    `bank` varchar(50) NOT NULL,
    `noRekening` varchar(50) NOT NULL,
    `atasNama` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `informasi` text NOT NULL,
    `statusVendor` varchar(100) NOT NULL DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `budgeting_project` (
    `idBudgetingProject` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeBudgetingProject` varchar(100) NOT NULL UNIQUE,
    `tanggalAwalKontrak` date NOT NULL,
    `tanggalAkhirKontrak` date NOT NULL,
    `tanggalPelaksanaan` date NOT NULL,
    `kodeVendor` varchar(100) NOT NULL,
    `namaPIC` varchar(255) NOT NULL,
    `noTelpPIC` varchar(30) NOT NULL,
    `keterangan` text NOT NULL,
    `statusBudgetingProject` varchar(100) NOT NULL DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `budgeting_project_tim` (
    `idBudgetingProjectTim` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeBudgetingProject` varchar(100) NOT NULL,
    `kodePegawai` varchar(100) NOT NULL,
    `jabatan` varchar(100) NOT NULL,
    `statusBudgetingProjectTim` varchar(100) NOT NULL DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `budgeting_project_anggaran` (
    `idBudgetingProjectAnggaran` bigint(20) PRIMARY KEY AUTO_INCREMENT,
    `kodeBudgetingProject` varchar(100) NOT NULL,
    `nominal` varchar(100) NOT NULL,
    `statusBudgetingProjectAnggaran` varchar(100) NOT NULL DEFAULT 'Aktif',
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `budgeting_project_biaya` (
    `idBudgetingProjectBiaya` bigint PRIMARY KEY AUTO_INCREMENT,
    `kodeBudgetingProject` varchar(100) NOT NULL,
    `namaItem` varchar(255) NOT NULL,
    `qty` int NOT NULL,
    `hargaSatuan` decimal(15, 0) NOT NULL,
    `subTotal` decimal(15, 0) NOT NULL,
    `statusBudgetingProjectBiaya` varchar(100) NOT NULL,
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `budgeting_project_progres` (
    `idBudgetingProjectProgres` bigint PRIMARY KEY AUTO_INCREMENT,
    `kodeBudgetingProject` varchar(100) NOT NULL,
    `tanggalUpdate` date NOT NULL,
    `progres` bigint(20) NOT NULL,
    `keterangan` text NOT NULL,
    `statusBudgetingProjectProgres` varchar(100) NOT NULL,
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE `barang` (
    `idBarang` bigint PRIMARY KEY AUTO_INCREMENT,
    `kodeBarang` varchar(100) NOT NULL UNIQUE,
    `namaBarang` varchar(100) NOT NULL,
    `jenisBarang` varchar(100) NOT NULL,
    `satuanBarang` varchar(100) NOT NULL,
    `hargaBarang` decimal(15, 0) NOT NULL,
    `statusBarang` varchar(100) NOT NULL,
    `idUser` bigint(20) NOT NULL,
    `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
    `idUserEdit` bigint(20) DEFAULT NULL,
    `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = latin1;