-- MySQL dump 10.13  Distrib 8.0.33, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: masterdb
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account`
--

DROP TABLE IF EXISTS `account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account` (
  `idAccount` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeAccount` varchar(255) NOT NULL,
  `kode` varchar(255) NOT NULL,
  `namaAccount` varchar(255) NOT NULL,
  `statusAccount` varchar(255) DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idAccount`),
  UNIQUE KEY `kodeAccount` (`kodeAccount`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account`
--

/*!40000 ALTER TABLE `account` DISABLE KEYS */;
INSERT INTO `account` VALUES (1,'MUM/account/1/000000019','01','KAS','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(2,'MUM/account/1/000000020','02','BANK','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(3,'MUM/account/1/000000021','03','PIUTANG','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(4,'MUM/account/1/000000022','04','BARANG','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(5,'MUM/account/1/000000023','05','INVENTARIS','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(6,'MUM/account/1/000000024','06','TANAH','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(7,'MUM/account/1/000000025','07','PENYUSUTAN','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(8,'MUM/account/1/000000026','08','UTANG','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(9,'MUM/account/1/000000027','09','PENGAMBILAN / SETORAN PRIBADI','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(10,'MUM/account/1/000000028','10','PAJAK-Penghasilan (PPh)','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(11,'MUM/account/1/000000029','11','PPh Pasal 4 Ayat 2 (Final)','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(12,'MUM/account/1/000000030','12','PPh Pasal 21','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(13,'MUM/account/1/000000031','13','Pajak Pertambahan Nilai (PPN)','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(14,'MUM/account/1/000000032','14','MODAL','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(15,'MUM/account/1/000000033','15','RUGI / LABA','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(16,'MUM/account/1/000000034','16','BIAYA LANGSUNG','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(17,'MUM/account/1/000000035','17','BIAYA TAK LANGSUNG','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(18,'MUM/account/1/000000036','18','Pendapatan Lain - lain','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(19,'MUM/account/1/000000037','111','test','Non Aktif',1,'2024-09-04 07:44:16',NULL,'2024-09-07 00:58:55');
/*!40000 ALTER TABLE `account` ENABLE KEYS */;

--
-- Table structure for table `bank`
--

DROP TABLE IF EXISTS `bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank` (
  `idBank` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBank` varchar(255) NOT NULL,
  `atasNama` varchar(255) NOT NULL,
  `noRekening` varchar(255) NOT NULL,
  `vendor` varchar(255) NOT NULL,
  `statusBank` varchar(255) DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBank`),
  UNIQUE KEY `kodeBank` (`kodeBank`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank`
--

/*!40000 ALTER TABLE `bank` DISABLE KEYS */;
INSERT INTO `bank` VALUES (3,'MUM/bank/1/000000002','test1','123','Mandiri','Aktif',1,'2024-09-06 02:09:24',NULL,'2024-09-06 02:09:26'),(4,'MUM/bank/1/000000003','test2','456','BCA','Aktif',1,'2024-09-06 02:09:41',NULL,'2024-09-06 02:09:43');
/*!40000 ALTER TABLE `bank` ENABLE KEYS */;

--
-- Table structure for table `barang`
--

DROP TABLE IF EXISTS `barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang` (
  `idBarang` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBarang` varchar(100) NOT NULL,
  `namaBarang` varchar(100) NOT NULL,
  `jenisBarang` varchar(100) NOT NULL,
  `satuanBarang` varchar(100) NOT NULL,
  `hargaBarang` decimal(15,0) NOT NULL,
  `statusBarang` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBarang`),
  UNIQUE KEY `kodeBarang` (`kodeBarang`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang`
--

/*!40000 ALTER TABLE `barang` DISABLE KEYS */;
INSERT INTO `barang` VALUES (1,'MUM/inventory_barang/1/000000001','Besi','Jenis 1','Batang',1000000,'Aktif',1,'2024-09-20 06:30:27',NULL,'2024-09-20 06:30:29');
/*!40000 ALTER TABLE `barang` ENABLE KEYS */;

--
-- Table structure for table `biaya`
--

DROP TABLE IF EXISTS `biaya`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `biaya` (
  `idBiaya` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBiaya` varchar(255) NOT NULL,
  `tglBiaya` date NOT NULL,
  `idSubAccount` varchar(100) NOT NULL,
  `nomorNota` varchar(255) NOT NULL,
  `grandTotal` decimal(15,0) NOT NULL,
  `statusBiaya` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBiaya`),
  UNIQUE KEY `kodeBiaya` (`kodeBiaya`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biaya`
--

/*!40000 ALTER TABLE `biaya` DISABLE KEYS */;
INSERT INTO `biaya` VALUES (1,'MUM/biaya/1/000000001','2024-09-09','1','ewadwaddaw',1440766490,'Aktif',1,'2024-09-09 02:17:45',NULL,'2024-09-09 02:17:45'),(2,'MUM/biaya/1/000000002','2024-09-10','1','DAWFSAF',541,'Aktif',1,'2024-09-10 03:55:29',NULL,'2024-09-10 03:55:29');
/*!40000 ALTER TABLE `biaya` ENABLE KEYS */;

--
-- Table structure for table `biaya_detail`
--

DROP TABLE IF EXISTS `biaya_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `biaya_detail` (
  `idBiayaDetail` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBiaya` varchar(255) NOT NULL,
  `namaItem` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `hargaSatuan` decimal(15,0) NOT NULL,
  `subTotal` decimal(15,0) NOT NULL,
  `statusBiayaDetail` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBiayaDetail`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biaya_detail`
--

/*!40000 ALTER TABLE `biaya_detail` DISABLE KEYS */;
INSERT INTO `biaya_detail` VALUES (1,'MUM/biaya/1/000000001','awdawf',34,23145,786930,'Aktif',1,'2024-09-09 02:17:38',NULL,'2024-09-09 02:17:38'),(2,'MUM/biaya/1/000000001','awfawdafwea',340,4235234,1439979560,'Aktif',1,'2024-09-09 02:17:44',NULL,'2024-09-09 02:17:44'),(3,'MUM/biaya/1/000000002','sad',2314,234,541,'Aktif',1,'2024-09-10 03:55:23',NULL,'2024-09-10 03:55:23');
/*!40000 ALTER TABLE `biaya_detail` ENABLE KEYS */;

--
-- Table structure for table `budgeting_project`
--

DROP TABLE IF EXISTS `budgeting_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgeting_project` (
  `idBudgetingProject` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBudgetingProject` varchar(100) NOT NULL,
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
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `namaBudgetingProject` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idBudgetingProject`),
  UNIQUE KEY `kodeBudgetingProject` (`kodeBudgetingProject`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgeting_project`
--

/*!40000 ALTER TABLE `budgeting_project` DISABLE KEYS */;
INSERT INTO `budgeting_project` VALUES (1,'MUM/budgeting_project/1/000000001','2024-09-01','2024-11-08','2024-09-11','MUM/vendor/1/000000001','Nama PIC TEST','08911111111','Lorem ipsum dolor sit amet consectetur, adipisicing elit. Nisi, alias quas quidem ratione dolores unde totam harum facilis beatae perspiciatis consequatur! Numquam, sint? Eos illo, provident quas libero at animi!','Aktif',1,'2024-09-10 02:29:08',NULL,'2024-09-12 06:34:00','Proyek A'),(2,'MUM/budgeting_project/1/000000002','2024-08-25','2024-11-23','2024-09-12','MUM/vendor/1/000000001','Nama PIC TEST','08911111111','test','Aktif',1,'2024-09-10 02:31:53',NULL,'2024-09-12 06:34:00','Proyek B'),(3,'MUM/budgeting_project/1/000000003','2024-09-12','2024-09-12','2024-09-12','MUM/vendor/1/000000001','awd','awd','awd','Non Aktif',1,'2024-09-12 05:41:58',NULL,'2024-09-12 06:34:00','Proyek C');
/*!40000 ALTER TABLE `budgeting_project` ENABLE KEYS */;

--
-- Table structure for table `budgeting_project_anggaran`
--

DROP TABLE IF EXISTS `budgeting_project_anggaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgeting_project_anggaran` (
  `idBudgetingProjectAnggaran` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBudgetingProject` varchar(100) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `statusBudgetingProjectAnggaran` varchar(100) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBudgetingProjectAnggaran`),
  KEY `kodeBudgetingProject` (`kodeBudgetingProject`),
  CONSTRAINT `budgeting_project_anggaran_ibfk_1` FOREIGN KEY (`kodeBudgetingProject`) REFERENCES `budgeting_project` (`kodeBudgetingProject`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgeting_project_anggaran`
--

/*!40000 ALTER TABLE `budgeting_project_anggaran` DISABLE KEYS */;
INSERT INTO `budgeting_project_anggaran` VALUES (1,'MUM/budgeting_project/1/000000001',2000000000.00,'Aktif',1,'2024-09-10 02:29:32',NULL,'2024-09-12 03:36:07'),(2,'MUM/budgeting_project/1/000000002',3000000000.00,'Aktif',1,'2024-09-10 02:32:03',NULL,'2024-09-12 03:14:02');
/*!40000 ALTER TABLE `budgeting_project_anggaran` ENABLE KEYS */;

--
-- Table structure for table `budgeting_project_biaya`
--

DROP TABLE IF EXISTS `budgeting_project_biaya`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgeting_project_biaya` (
  `idBudgetingProjectBiaya` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBudgetingProject` varchar(100) NOT NULL,
  `namaItem` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `hargaSatuan` decimal(15,0) NOT NULL,
  `subTotal` decimal(15,0) NOT NULL,
  `statusBudgetingProjectBiaya` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBudgetingProjectBiaya`),
  KEY `kodeBudgetingProject` (`kodeBudgetingProject`),
  CONSTRAINT `budgeting_project_biaya_ibfk_1` FOREIGN KEY (`kodeBudgetingProject`) REFERENCES `budgeting_project` (`kodeBudgetingProject`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgeting_project_biaya`
--

/*!40000 ALTER TABLE `budgeting_project_biaya` DISABLE KEYS */;
INSERT INTO `budgeting_project_biaya` VALUES (1,'MUM/budgeting_project/1/000000001','Besi',321,400000,128400000,'',1,'2024-09-10 02:29:49',NULL,'2024-09-10 02:29:49'),(2,'MUM/budgeting_project/1/000000002','Besi',67,6575,440525,'',1,'2024-09-10 02:32:10',NULL,'2024-09-10 02:32:10'),(3,'MUM/budgeting_project/1/000000001','Kayu',234,23523,5504382,'',1,'2024-09-12 04:54:46',NULL,'2024-09-12 04:54:46');
/*!40000 ALTER TABLE `budgeting_project_biaya` ENABLE KEYS */;

--
-- Table structure for table `budgeting_project_progres`
--

DROP TABLE IF EXISTS `budgeting_project_progres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgeting_project_progres` (
  `idBudgetingProjectProgres` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBudgetingProject` varchar(100) NOT NULL,
  `tanggalUpdate` date NOT NULL,
  `progres` bigint(20) NOT NULL,
  `keterangan` text NOT NULL,
  `statusBudgetingProjectProgres` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kodeBudgetingProjectProgres` varchar(100) NOT NULL,
  PRIMARY KEY (`idBudgetingProjectProgres`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgeting_project_progres`
--

/*!40000 ALTER TABLE `budgeting_project_progres` DISABLE KEYS */;
INSERT INTO `budgeting_project_progres` VALUES (15,'MUM/budgeting_project/1/000000001','2024-09-13',22,'update progres ke 22%','Non Aktif',1,'2024-09-13 05:36:18',NULL,'2024-09-13 06:57:16','MUM/budgeting_project_progres/1/000000006'),(16,'MUM/budgeting_project/1/000000001','2024-09-14',55,'udpate progres yang ke 55%','Non Aktif',1,'2024-09-13 05:36:52',NULL,'2024-09-13 06:51:23','MUM/budgeting_project_progres/1/000000007'),(17,'MUM/budgeting_project/1/000000001','2024-09-13',55,'update progres yang ke 55%','Non Aktif',1,'2024-09-13 06:57:00',NULL,'2024-09-13 06:57:07','MUM/budgeting_project_progres/1/000000008'),(18,'MUM/budgeting_project/1/000000001','2024-09-13',22,'c','Aktif',1,'2024-09-13 06:57:44',NULL,'2024-09-13 06:57:44','MUM/budgeting_project_progres/1/000000009'),(19,'MUM/budgeting_project/1/000000001','2024-09-13',22,'progress ke 66%','Non Aktif',1,'2024-09-13 06:58:40',NULL,'2024-09-13 06:59:15','MUM/budgeting_project_progres/1/000000010'),(20,'MUM/budgeting_project/1/000000001','2024-09-13',66,'dawdfawdawd','Aktif',1,'2024-09-13 06:59:32',NULL,'2024-09-13 06:59:32','MUM/budgeting_project_progres/1/000000011');
/*!40000 ALTER TABLE `budgeting_project_progres` ENABLE KEYS */;

--
-- Table structure for table `budgeting_project_tim`
--

DROP TABLE IF EXISTS `budgeting_project_tim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgeting_project_tim` (
  `idBudgetingProjectTim` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeBudgetingProject` varchar(100) NOT NULL,
  `kodePegawai` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `statusBudgetingProjectTim` varchar(100) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idBudgetingProjectTim`),
  KEY `kodeBudgetingProject` (`kodeBudgetingProject`),
  CONSTRAINT `budgeting_project_tim_ibfk_1` FOREIGN KEY (`kodeBudgetingProject`) REFERENCES `budgeting_project` (`kodeBudgetingProject`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgeting_project_tim`
--

/*!40000 ALTER TABLE `budgeting_project_tim` DISABLE KEYS */;
INSERT INTO `budgeting_project_tim` VALUES (15,'MUM/budgeting_project/1/000000001','NT/pegawai/1/000000003','Leader','Non Aktif',1,'2024-09-10 02:29:17',NULL,'2024-09-12 07:11:56'),(16,'MUM/budgeting_project/1/000000002','NT/pegawai/1/000000004','Leader','Aktif',1,'2024-09-10 02:31:58',NULL,'2024-09-10 02:31:58'),(17,'MUM/budgeting_project/1/000000001','NT/pegawai/1/000000004','Anggota','Aktif',1,'2024-09-12 06:59:54',NULL,'2024-09-12 06:59:54'),(18,'MUM/budgeting_project/1/000000001','NT/pegawai/1/000000004','Penanggung Jawab','Aktif',1,'2024-09-12 06:59:59',NULL,'2024-09-12 06:59:59'),(19,'MUM/budgeting_project/1/000000001','NT/pegawai/1/000000003','Anggota','Aktif',1,'2024-09-12 07:02:50',NULL,'2024-09-12 07:02:50');
/*!40000 ALTER TABLE `budgeting_project_tim` ENABLE KEYS */;

--
-- Table structure for table `cabang`
--

DROP TABLE IF EXISTS `cabang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cabang` (
  `idCabang` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeCabang` varchar(100) NOT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `noTelp` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `informasi` text DEFAULT NULL,
  `badanUsaha` varchar(255) DEFAULT NULL,
  `website` varchar(255) NOT NULL,
  `facebook` varchar(255) NOT NULL,
  `hotline` varchar(255) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) DEFAULT NULL,
  `timeStamp` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idCabang`),
  UNIQUE KEY `kodeCabang` (`kodeCabang`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cabang`
--

/*!40000 ALTER TABLE `cabang` DISABLE KEYS */;
INSERT INTO `cabang` VALUES (1,'MUM/cabang/1/000000001','PT. MARGA UTAMA MANDIRI','Jl. Nangka - Graha Permai No.1, Dangin Puri Kaja, Kec. Denpasar Utara, Kota Denpasar, Bali 80115','(+62)361432980','info@margautamamandiri.com','INI ADALAH INFORMASI ','PT. MARGA UTAMA MANDIRI','www.margautama.com','marga-utama-mandiri-pt','(+62)82225436560','Aktif',1,'2024-09-03 15:58:41',NULL,'2024-09-03 07:58:41');
/*!40000 ALTER TABLE `cabang` ENABLE KEYS */;

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client` (
  `idClient` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kodeClient` varchar(100) NOT NULL,
  `noIdentitas` varchar(100) NOT NULL,
  `salutation` varchar(100) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `noTelp` varchar(100) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `jenisKelamin` varchar(50) NOT NULL,
  `negara` varchar(100) DEFAULT NULL,
  `npwp` varchar(100) NOT NULL,
  `statusUsia` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `idUser` bigint(20) unsigned NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) unsigned DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idClient`),
  UNIQUE KEY `kodeClient` (`kodeClient`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=23049 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client`
--

/*!40000 ALTER TABLE `client` DISABLE KEYS */;
INSERT INTO `client` VALUES (23011,'NT/client/1/000000001','5171098678','','tegar saputra uya','','081890789','jln raya galunggung no.9 jawa timur','Laki-laki','Indonesia','77777','Adult','rahayu@gmail.com',1,'2024-01-09 03:20:27',1,'2024-01-20 07:54:12'),(23012,'NT/client/1/000000002','51710986789','','boby saputra','','9078965','Jalan Buluh indah No.12','Perempuan','Jerman','','','testting@gmail.com',1,'2024-01-09 03:24:11',NULL,'2024-01-09 03:24:11'),(23013,'NT/client/1/000000003','517109867823','','bapaknya BOBY','','08198078976','Jalan Buluh indah No.12','Laki-laki','Jerman','7777','Adult','test@gmail.com',1,'2024-01-11 14:26:49',1,'2024-01-20 07:54:40'),(23014,'NT/client/1/000000004','123456','','Mr. Testing Lengkap','','087861628330','NICKTOURS, Jl. Danau Tamblingan','Laki-laki','Denmark','','','dennish@nicktours.com',1,'2024-01-15 02:42:24',1,'2024-01-15 04:28:57'),(23015,'NT/client/1/000000005','125948','','dennish','','087861628330','NICKTOURS, Jl. Danau Tamblingan','Laki-laki','Indonesia','','','dennish@nicktours.com',1,'2024-01-15 02:48:26',NULL,'2024-01-15 02:48:26'),(23016,'NT/client/1/000000006','123456343434','','Putu Tes','','545452121','NICKTOURS, Jl. Prof. Dr. Ida Bagus Mantra No. 99X,  Lingk. Jayakerta, Sukawati','Laki-laki','indonesia','','','akoi@rocketmail.com',1,'2024-01-15 02:53:38',NULL,'2024-01-15 02:53:38'),(23017,'NT/client/1/000000007','5484545454','','Mrs. Testing','','221515151','Denmark','Perempuan','Denmark','','','test@gmail.com',1,'2024-01-15 04:26:46',NULL,'2024-01-15 04:26:46'),(23018,'NT/client/1/000000008','125468456565','','Mr. Jensen','','+451256874444','Denmark','Laki-laki','Denmark','','','test@gmail.com',1,'2024-01-15 04:56:15',NULL,'2024-01-15 04:56:15'),(23019,'NT/client/1/000000009','-','','bayi Baru Lahir','','890786','MATARAM','Laki-laki','Indonesia','389765','Infant','bayi@gmail.com',1,'2024-01-18 09:05:30',NULL,'2024-01-18 09:05:30'),(23020,'NT/client/1/000000010','','','Mr. Den Testing','','+45125687464','Denmark','Laki-laki','Denmark','1253.25542.25.25','Adult','test@mail.com',1,'2024-01-22 02:58:13',NULL,'2024-01-22 02:58:13'),(23023,'NT/client/1/000000011','232asdads','','Mrs. Din Testing','','087861628330','Denmark','Perempuan','Denmark','1253.25542.25.25','Adult','dennish@nicktours.com',1,'2024-01-22 03:00:21',NULL,'2024-01-22 03:00:21'),(23032,'NT/client/1/000000012','3333','','Mr. Torben, Henrik','','-','-','Laki-laki','Denmark','-','Adult','-',1,'2024-01-25 08:33:04',NULL,'2024-01-25 08:33:04'),(23033,'NT/client/1/000000013','23456','','client coba','','9087','JL. GUNUNG AGUNG IISB(Semilasari Barat) no.112xxx','Laki-laki','Jerman','568977','Adult','rahayu@gmail.com',1,'2024-01-26 01:25:45',NULL,'2024-01-26 01:25:45'),(23034,'NT/client/1/000000014','12222','','Ms. Anika Lenovo','','1','-','Perempuan','Holland','1','Adult','1',1,'2024-01-26 03:31:36',NULL,'2024-01-26 03:31:36'),(23035,'NT/client/1/000000015','343434','','Mr. Laki Lenovo','','-','-','Laki-laki','Holland','-','Adult','-',1,'2024-01-26 04:14:34',NULL,'2024-01-26 04:14:34'),(23036,'NT/client/1/000000016','1111111','Mr.','First','Last','-','-','Laki-laki','Australia','-','Adult','-',1,'2024-01-30 10:27:40',NULL,'2024-01-30 10:27:40'),(23037,'NT/client/1/000000017','555','Mr.','Adam','Lasting','-','-','Laki-laki','UK','-','Adult','-',1,'2024-02-01 05:12:23',NULL,'2024-02-01 05:12:23'),(23038,'NT/client/1/000000018','343433','Mrs','Firstly','Last','-','-','Perempuan','Australia','-','Adult','-',1,'2024-02-02 04:24:11',NULL,'2024-02-02 04:24:11'),(23039,'NT/client/1/000000019','999','Mr','PUTU','Darmadi test','098','Jalan Buluh indah No.12','Laki-laki','Indonesia','09890198','Adult','test@gmail.com',1,'2024-03-07 07:34:41',NULL,'2024-03-07 07:34:41'),(23040,'NT/client/1/000000020','-','Mr.','Alexandros','Tsatsaris','-','Denmark','Laki-laki','Denmark','-','Adult','-',1,'2024-05-07 03:58:19',NULL,'2024-05-07 03:58:19'),(23041,'NT/client/1/000000021','','Ms','Katharina','Bech','-','Denmark','Perempuan','Denmark','-','Adult','-',1,'2024-05-07 04:00:15',NULL,'2024-05-07 04:00:15'),(23042,'NT/client/1/000000022','0000','Mr','TESTINGONE','Nameone','-','Denmark','Laki-laki','Denmark','-','Adult','-',1,'2024-08-14 07:16:26',NULL,'2024-08-14 07:16:26'),(23043,'NT/client/1/000000023','111','Ms','TESTINGONE','Nameone','-','Denmark','Perempuan','Denmark','-','Adult','-',1,'2024-08-14 07:18:27',NULL,'2024-08-14 07:18:27'),(23044,'NT/client/1/000000024','222','Mr','Testingtwo','Nametwo','1','Denmark','Laki-laki','Denmark','1','Adult','1',1,'2024-08-14 07:19:17',NULL,'2024-08-14 07:19:17'),(23045,'NT/client/1/000000025','32','Ms','Testingtwo','Nametwo','-','Denmark','Perempuan','Denmark','-','Adult','-',1,'2024-08-14 07:19:59',NULL,'2024-08-14 07:19:59'),(23046,'NT/client/1/000000026','','Mr','Morten Dam','Hansen','-','-','Laki-laki','Denmark','-','Adult','-',1,'2024-08-27 08:31:10',NULL,'2024-08-27 08:31:10'),(23047,'NT/client/1/000000027','','Ms','Charlotte','Skov','-','-','Perempuan','Denmark','-','Adult','-',1,'2024-08-27 08:32:50',NULL,'2024-08-27 08:32:50'),(23048,'NT/client/1/000000028','','Mss','Freja Emilie','Skov','-','-','Perempuan','Denmark','-','Child','-',1,'2024-08-27 08:33:22',1,'2024-08-27 08:34:41');
/*!40000 ALTER TABLE `client` ENABLE KEYS */;

--
-- Table structure for table `departemen_pegawai`
--

DROP TABLE IF EXISTS `departemen_pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departemen_pegawai` (
  `idDepartemenPegawai` bigint(20) NOT NULL AUTO_INCREMENT,
  `namaDepartemenPegawai` varchar(255) DEFAULT NULL,
  `statusDepartemen` varchar(50) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idDepartemenPegawai`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departemen_pegawai`
--

/*!40000 ALTER TABLE `departemen_pegawai` DISABLE KEYS */;
INSERT INTO `departemen_pegawai` VALUES (1,'Direktur','Aktif',0,'2024-01-07 02:56:42',0,'2024-01-07 02:56:42'),(2,'Admin','Aktif',0,'2024-01-07 02:56:42',1,'2024-09-07 00:31:59'),(3,'Finance','Aktif',0,'2024-01-07 02:56:42',0,'2024-01-07 02:56:42'),(4,'HRD','Aktif',0,'2024-01-07 02:56:42',1,'2024-01-07 02:59:32'),(5,'coba2','batal',1,'2024-01-07 02:57:54',1,'2024-01-07 02:58:27');
/*!40000 ALTER TABLE `departemen_pegawai` ENABLE KEYS */;

--
-- Table structure for table `informasi`
--

DROP TABLE IF EXISTS `informasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `informasi` (
  `idInformasi` int(11) NOT NULL AUTO_INCREMENT,
  `noTelp1` varchar(100) NOT NULL,
  `noTelp2` varchar(100) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `namaBank` varchar(255) NOT NULL,
  `noRek` varchar(255) NOT NULL,
  `atasNamaNoRek` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `emailInfo` varchar(255) NOT NULL,
  `namaFinance1` varchar(255) NOT NULL,
  `namaFinance2` varchar(255) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `noteInvoice` text NOT NULL,
  `logo` varchar(255) NOT NULL,
  `header` varchar(100) NOT NULL,
  `footer` varchar(100) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `informasi` text NOT NULL,
  `notePembayaran` text NOT NULL,
  PRIMARY KEY (`idInformasi`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `informasi`
--

/*!40000 ALTER TABLE `informasi` DISABLE KEYS */;
INSERT INTO `informasi` VALUES (1,'(0361) 4747 650','0818311355','OMSA Medic','Bank Mandiri','175-000-097-8261','CV. dr. Romy Associates','finance@drromyasc.com','info@drromyasc.com','Ida Ayu Savitri, SE','Eriza Natalia','Jl. Tukad Musi','By signing this payment details, i agreed to sum payable mentioned above.','logo2.png','logoatas.png','logobawah.png','icon.png','By signing this payment details, I agreed to the sum payable mentioned above','Apabila sudah melakukan pembayaran melalui transfer, mohon konfirmasi melalui telpon call center kami di (0361) 4747650 / 0881037194214 (whatsapp) atau bisa mengirimkan bukti transfer ke email ardrromyasc@gmail.com');
/*!40000 ALTER TABLE `informasi` ENABLE KEYS */;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_history` (
  `idUserActive` bigint(20) NOT NULL AUTO_INCREMENT,
  `idUser` bigint(20) DEFAULT NULL,
  `tokenCSRF` varchar(255) DEFAULT NULL,
  `idCabang` int(11) DEFAULT NULL,
  `IP_ADDR` varchar(30) NOT NULL,
  `attempt` int(11) NOT NULL,
  `timeResetAttempt` datetime DEFAULT NULL,
  `statusUser` varchar(25) DEFAULT NULL,
  `timeStampLogin` datetime DEFAULT NULL,
  `timeStampLogout` datetime DEFAULT NULL,
  PRIMARY KEY (`idUserActive`)
) ENGINE=InnoDB AUTO_INCREMENT=536 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (4,NULL,NULL,NULL,'2001:448a:500a:1e11:c0e6:2ec3:',5,NULL,'Process',NULL,NULL),(6,1,'ec8256b04fa51a4fa2e7363f623147ae6f9847cac464c4f6cbb23c6a02f33284',1,'180.249.185.43',5,NULL,'Logged In','2023-12-08 15:09:17',NULL),(7,1,'bea6a1dca97740f0445a12e9ea43e983ce7e12ea26a32111a1d37d174e46f458',1,'103.165.203.18',5,NULL,'Logged In','2024-01-25 15:07:45',NULL),(8,1,'5ee85c1b2b3e06c52f418a2e1b8aac1236a3b19a8514f7121f80244b02542d38',1,'114.122.138.226',5,NULL,'Logged In','2023-12-08 14:42:40',NULL),(9,NULL,NULL,NULL,'180.249.185.43',5,NULL,'Process',NULL,NULL),(10,1,'afb725d807204c53e5efd82f24cad5147f2552efd6d5b57193ad8ddef8f51ce9',1,'140.213.127.193',5,NULL,'Logged In','2023-12-08 17:58:05',NULL),(11,1,'847b38b2e30ac255e72244bd79236adee50c45cace3bc2c09ffb72c0d398ca21',1,'180.249.185.184',5,NULL,'Logged In','2023-12-09 18:25:39',NULL),(12,NULL,NULL,NULL,'180.249.185.184',5,NULL,'Process',NULL,NULL),(13,1,'9910a0e692790ab6c73e81ddb3b76c5d5007f688df06707f66b64cf38e6e18d4',1,'180.254.226.118',5,NULL,'Logged In','2023-12-09 19:57:35',NULL),(14,1,'98d0d9b5a7f0db5174c3dd476733b1e15937f033eb0aec0cdc31bfee92b5e4cd',1,'180.249.185.3',5,NULL,'Logged In','2023-12-14 17:04:56',NULL),(15,NULL,NULL,NULL,'103.165.203.18',5,NULL,'Process',NULL,NULL),(17,1,'1dd7382ace65f2396dec9348ae1f055d295206a8c4cdb3307d2068d57401ab34',1,'223.255.228.106',5,NULL,'Logged In','2023-12-11 13:03:19',NULL),(21,1,'1be816113b888ca9a554e93d7186bcab5f42bd32d3897208f655731a1fe18315',1,'203.78.114.143',5,NULL,'Logged In','2023-12-11 17:31:13',NULL),(28,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(29,1,'9783d561029485def435a826cdb49b99ef7f17004218d2693348ad2ed147dd22',1,'114.122.181.210',5,NULL,'Logged In','2023-12-12 10:01:05',NULL),(37,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(38,1,'a5fb87b0d8aad1b6e76a28b4743f0c1fc8715e80892f8fc8e0c1c8a1141fdb7b',1,'180.254.226.191',5,NULL,'Logged In','2023-12-14 08:23:28',NULL),(41,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(42,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(44,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(45,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(47,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(48,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(58,1,'e02eef39bd2d76fcd866a4613744c60aec380157aa084abaef0e7f5413fbfc4e',1,'114.122.139.181',5,NULL,'Logged In','2023-12-13 11:13:35',NULL),(59,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(62,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(63,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(64,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(65,1,'badee746ee2eb6d2ac9d41905a2cd40d2927cd4c2693802899b026cb60c52efc',1,'114.122.180.153',5,NULL,'Logged In','2023-12-13 17:30:59',NULL),(67,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(70,NULL,NULL,NULL,'180.254.226.191',5,NULL,'Process',NULL,NULL),(72,91,'15b1d8cc8be818b103248197c1704dfe31e4e4e7337307d8fc8123e54ccccecf',1,'101.128.64.85',5,NULL,'Logged In','2023-12-25 19:02:49',NULL),(73,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(74,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(75,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(77,1,'51a93ea769e13a189f39bf73b6fab1c70e37e4935e717fb9c3d8e65f9644b5fb',1,'180.254.226.177',5,NULL,'Logged In','2023-12-16 12:45:00',NULL),(78,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(79,1,'c0f9ec5acf32e2262fe69fb50d82998fc8a8d5d670c00a09938b5cbfb4be6768',1,'114.122.139.126',5,NULL,'Logged In','2023-12-14 13:33:26',NULL),(80,NULL,NULL,NULL,'114.122.139.126',5,NULL,'Process',NULL,NULL),(81,NULL,NULL,NULL,'114.122.139.126',5,NULL,'Process',NULL,NULL),(82,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(83,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(84,1,'529c6a4f8daec372874d9361559c6eac84720b9ec500034b7017b0a59fe1cb53',1,'114.122.143.228',5,NULL,'Logged In','2023-12-14 14:42:45',NULL),(85,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(86,NULL,NULL,NULL,'180.249.185.3',5,NULL,'Process',NULL,NULL),(87,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(88,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(89,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(90,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(91,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(92,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(93,88,'90d98f0d3ff2d425e498e2851cb2107347dd70e38ca612549df38d9ffc9f705d',1,'223.255.228.81',5,NULL,'Logged In','2023-12-14 20:38:01',NULL),(94,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(95,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(96,NULL,NULL,NULL,'103.165.203.18',5,NULL,'Process',NULL,NULL),(97,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(98,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(99,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(100,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(101,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(102,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(103,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(104,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(105,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(106,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(107,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(108,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(110,90,'7fbd9a5d332fc173c134c09393a1464645aced4ea3135e8f6d92d472bec9a6a6',1,'114.122.141.19',5,NULL,'Logged In','2023-12-15 15:41:43',NULL),(111,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(112,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(113,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(114,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(115,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(116,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(118,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(119,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(120,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(121,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(122,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(123,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(124,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(125,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(126,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(127,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(128,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(129,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(130,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(131,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(132,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(133,1,'5c9de3c011ccb3b8063d29d369e295a4990cbba2a92857b2f2cb0c5b4979e33e',1,'180.249.185.128',5,NULL,'Logged In','2023-12-16 20:10:04',NULL),(134,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(135,1,'272545d05866a5888233a7362439f7a1ae972f8d527d4ff850ebb11acd62485f',1,'180.254.226.56',5,NULL,'Logged In','2023-12-17 19:20:10',NULL),(136,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(137,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(138,NULL,NULL,NULL,'180.254.226.177',5,NULL,'Process',NULL,NULL),(139,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(140,NULL,NULL,NULL,'180.249.185.128',5,NULL,'Process',NULL,NULL),(141,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(142,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(143,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(144,NULL,NULL,NULL,'180.249.185.128',5,NULL,'Process',NULL,NULL),(145,NULL,NULL,NULL,'180.249.185.128',5,NULL,'Process',NULL,NULL),(146,NULL,NULL,NULL,'180.249.185.128',5,NULL,'Process',NULL,NULL),(147,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(148,1,'f0aa3025ca09a74b799440188da021656fcab2299da32f86a15f18aa2e972b24',1,'114.122.136.42',5,NULL,'Logged In','2023-12-16 16:08:04',NULL),(149,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(150,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(151,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(152,1,'52e897589e7a033a742507847f718fc1c7c9223640fd1fcc437d629ae7f05e43',1,'101.128.98.224',5,NULL,'Logged In','2023-12-16 20:13:26',NULL),(153,NULL,NULL,NULL,'180.254.226.56',5,NULL,'Process',NULL,NULL),(154,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(155,NULL,NULL,NULL,'180.254.226.56',5,NULL,'Process',NULL,NULL),(156,NULL,NULL,NULL,'180.249.185.128',5,NULL,'Process',NULL,NULL),(157,NULL,NULL,NULL,'101.128.98.224',5,NULL,'Process',NULL,NULL),(158,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(159,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(160,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(161,1,'5b1a67f5b399ede329842ce9a59c5873509e59a9f29fcfd99c0e530e1b730ec9',1,'101.128.98.90',5,NULL,'Logged In','2023-12-17 11:08:04',NULL),(162,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(163,NULL,NULL,NULL,'180.254.226.56',5,NULL,'Process',NULL,NULL),(164,NULL,NULL,NULL,'101.128.98.90',5,NULL,'Process',NULL,NULL),(165,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(167,NULL,NULL,NULL,'180.254.226.56',5,NULL,'Process',NULL,NULL),(168,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(169,NULL,NULL,NULL,'101.128.98.90',5,NULL,'Process',NULL,NULL),(170,NULL,NULL,NULL,'180.254.226.56',5,NULL,'Process',NULL,NULL),(172,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(173,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(174,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(175,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(176,1,'935c0a621576cc203342e6a9662b76a669964e645d85f340575b724ae278e5a9',1,'180.249.185.227',5,NULL,'Logged In','2023-12-19 22:56:31',NULL),(177,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(178,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(179,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(180,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(181,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(182,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(183,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(184,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(185,NULL,NULL,NULL,'180.254.226.56',5,NULL,'Process',NULL,NULL),(186,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(187,90,'216fe03854a606b48d6f18c6c1f6290792b9310b24363d827c092a2fe2097ede',1,'114.122.132.131',5,NULL,'Logged In','2023-12-17 21:05:54',NULL),(188,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(189,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(191,1,'b108d5e19e6578283b6c91b3e3a6aa3c93e026baf8fd7e62632afd3546ad3ddc',1,'180.254.226.224',5,NULL,'Logged In','2023-12-18 19:16:13',NULL),(192,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(193,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(194,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(195,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(196,1,'a0b35c118f60534ee072dcfd747450178231cb6016227a22aac60b355a436f53',1,'114.122.133.241',5,NULL,'Logged In','2023-12-18 10:17:05',NULL),(197,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(198,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(199,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(200,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(201,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(202,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(203,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(204,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(205,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(206,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(207,1,'e4d1e05c2d1e40ddab35369f577e25a18a28e9adf381d58727b6ee9b1af1e6d4',1,'114.122.143.249',5,NULL,'Logged In','2023-12-18 15:52:17',NULL),(208,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(209,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(210,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(211,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(212,90,'c589e0b83c3b3d4d8f8bd531280aa3fef7145eb88e2ebfffb217ad85c06c6236',1,'114.122.143.185',5,NULL,'Logged In','2023-12-18 16:10:29',NULL),(213,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(214,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(215,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(216,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(217,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(218,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(219,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(220,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(221,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(222,NULL,NULL,NULL,'180.254.226.224',5,NULL,'Process',NULL,NULL),(223,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(224,1,'16774ac6d56eba21895baec213a7a149fb8bb36be596864f92a00305ee1eba59',1,'180.254.225.56',5,NULL,'Logged In','2023-12-19 22:48:18',NULL),(225,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(226,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(227,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(228,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(229,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(230,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(231,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(232,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(233,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(234,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(235,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(236,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(237,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(238,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(239,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(240,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(241,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(242,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(243,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(244,NULL,NULL,NULL,'180.254.225.56',5,NULL,'Process',NULL,NULL),(245,NULL,NULL,NULL,'180.249.185.227',5,NULL,'Process',NULL,NULL),(246,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(247,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(248,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(249,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(250,1,'92eca1d6b80b70abe6c29bbfcac998850ded362b7144fbe6f837a6c7a0274cfb',1,'180.249.185.95',5,NULL,'Logged In','2023-12-21 12:53:39',NULL),(251,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(252,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(253,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(254,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(255,NULL,NULL,NULL,'180.249.185.95',5,NULL,'Process',NULL,NULL),(256,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(257,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(258,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(259,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(260,1,'2e925db803cd7e2e225722516dee15684a2aca22b8a3f5f89a74982efddd7317',1,'101.128.98.87',5,NULL,'Logged In','2023-12-20 15:17:07',NULL),(261,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(262,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(263,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(264,NULL,NULL,NULL,'180.249.185.95',5,NULL,'Process',NULL,NULL),(265,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(266,1,'b15c995b47fffa7eb6d5bf0ac691ffac43693378cb646ab95f7c891b641e4e63',1,'180.254.225.91',5,NULL,'Logged In','2023-12-22 04:36:56',NULL),(267,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(268,NULL,NULL,NULL,'180.254.225.91',5,NULL,'Process',NULL,NULL),(269,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(270,NULL,NULL,NULL,'180.254.225.91',5,NULL,'Process',NULL,NULL),(271,NULL,NULL,NULL,'180.249.185.95',5,NULL,'Process',NULL,NULL),(272,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(273,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(274,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(275,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(276,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(277,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(278,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(279,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(280,NULL,NULL,NULL,'180.254.225.91',5,NULL,'Process',NULL,NULL),(281,NULL,NULL,NULL,'180.254.225.91',5,NULL,'Process',NULL,NULL),(282,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(283,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(284,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(285,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(286,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(287,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(288,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(289,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(290,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(291,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(292,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(293,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(294,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(295,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(296,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(297,1,'5c27ae4d825a461b6d60cbbf0be6c63a5a235748ba002f6d2b53fcede8830db8',1,'180.249.184.38',5,NULL,'Logged In','2023-12-23 09:16:18',NULL),(298,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(299,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(300,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(301,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(302,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(303,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(304,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(305,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(306,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(307,1,'8f5f3a5d0f0954d7d4652277c2682ce53d6c58d6a744d5d11e338c3bdf154e39',1,'180.254.225.211',5,NULL,'Logged In','2023-12-24 08:53:49',NULL),(308,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(309,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(310,NULL,NULL,NULL,'180.254.225.211',5,NULL,'Process',NULL,NULL),(311,1,'ed89ffb56a4451501043b65da4db046227416cec713db051c8c428c6084475b1',1,'114.122.137.97',5,NULL,'Logged In','2023-12-24 11:16:44',NULL),(312,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(313,1,'997626c0e83c8a321aa31b4e7fd57ae7e720bc3b213bdf8f64ef6011002f1d2d',1,'125.162.173.70',5,NULL,'Logged In','2023-12-24 16:22:45',NULL),(314,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(315,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(316,NULL,NULL,NULL,'125.162.173.70',5,NULL,'Process',NULL,NULL),(317,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(318,1,'002d3a537d4567baf646bd0a2138753b59a66d7ada37824d5a833bb8975a75fd',1,'180.249.186.166',5,NULL,'Logged In','2023-12-24 19:32:07',NULL),(319,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(320,NULL,NULL,NULL,'180.249.186.166',5,NULL,'Process',NULL,NULL),(321,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(322,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(323,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(324,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(325,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(326,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(327,1,'aecc7b04d7ac11f27ce5b019f11c5be8b3475d38ed88c07762129774f36be737',1,'101.128.98.78',5,NULL,'Logged In','2023-12-25 16:45:12',NULL),(328,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(329,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(330,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(331,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(332,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(333,NULL,NULL,NULL,'101.128.98.78',5,NULL,'Process',NULL,NULL),(334,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(335,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(336,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(337,NULL,NULL,NULL,'101.128.64.85',5,NULL,'Process',NULL,NULL),(362,1,'dfe57f5030a8fefb204d4139478c910fb00d3128ba15fd348e0576b65311ecaf',1,'125.162.186.215',5,NULL,'Logged In','2024-01-25 17:29:49',NULL),(363,1,'d200a7053fe9e4bff70740c046c22765e9d706061e098cc9dfbafbdf61a97443',1,'180.254.227.47',5,NULL,'Logged In','2024-01-13 15:38:07',NULL),(364,1,'b4266c13e96e92cdfec77c61d28324911507ef74307245d152c9282a66f61d28',1,'101.128.98.162',5,NULL,'Logged In','2024-01-13 15:59:25',NULL),(365,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(366,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(367,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(368,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(373,NULL,NULL,NULL,'103.165.203.18',5,NULL,'Process',NULL,NULL),(375,1,'b724dc9412ce8c51ecd3bcbb9da9bc1374a4042284fe34c6c00d59d527d4fc38',1,'101.128.99.222',5,NULL,'Logged In','2024-02-23 12:21:30',NULL),(376,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(377,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(378,1,'1d7d9078a5c1f8d2538cf483c5504369fcdacd3bbb287454e03e98d3f8f848d3',1,'203.78.121.199',5,NULL,'Logged In','2024-01-16 11:17:18',NULL),(379,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(380,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(381,NULL,NULL,NULL,'103.165.203.18',5,NULL,'Process',NULL,NULL),(382,NULL,NULL,NULL,'103.165.203.18',5,NULL,'Process',NULL,NULL),(383,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(384,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(385,1,'cf13c45153a4fc1b11b474e7b887d4224a3601df5d7e09e8d926d16d1dc19ffd',1,'103.171.31.17',5,NULL,'Logged In','2024-06-13 09:08:15',NULL),(386,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(387,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(388,NULL,NULL,NULL,'159.203.53.95',5,NULL,'Process',NULL,NULL),(389,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(390,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(391,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(392,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(393,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(394,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(395,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(396,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(397,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(398,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(399,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(400,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(401,1,'63563d10ca5f674bd6974db61d9f57cad4ff9257450795b93c7d8cd4c96e76ec',1,'140.213.151.29',5,NULL,'Logged In','2024-01-22 11:12:13',NULL),(402,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(403,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(404,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(405,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(406,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(407,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(408,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(409,1,'f5bf00a6bcb6ae5bdccefba82fb6f204a97ba6b824675ef52b375ad278208fa9',1,'114.122.135.96',5,NULL,'Logged In','2024-01-23 13:44:36',NULL),(410,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(411,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(412,1,'5217d5ef314cfa48ccf7b43b938278491ded0da7c435730fb72eaf9bd42cf659',1,'125.162.174.49',5,NULL,'Logged In','2024-01-24 08:37:56',NULL),(413,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(414,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(415,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(416,1,'aef60d6484b66e58975b3b0f4a42ef0d089d668e628b38fc30e4c0f008e1beb0',1,'140.213.150.93',5,NULL,'Logged In','2024-01-25 12:32:11',NULL),(417,NULL,NULL,NULL,'140.213.150.93',5,NULL,'Process',NULL,NULL),(418,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(419,1,'5709bd77d9a26522f0776a6cfe27f59625e5a0af1ae1a8925bf5da90d6634175',1,'140.213.150.91',5,NULL,'Logged In','2024-01-25 13:59:55',NULL),(420,NULL,NULL,NULL,'103.165.203.18',5,NULL,'Process',NULL,NULL),(421,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(422,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(423,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(424,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(425,NULL,NULL,NULL,'125.162.186.215',5,NULL,'Process',NULL,NULL),(426,NULL,NULL,NULL,'103.171.31.17',5,NULL,'Process',NULL,NULL),(427,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(428,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(429,1,'4037e8b8f104cab3171dbaf46520a37e6de55133be419a4125b929f5a34c4419',1,'180.249.187.226',5,NULL,'Logged In','2024-01-26 15:31:56',NULL),(430,NULL,NULL,NULL,'103.171.31.17',5,NULL,'Process',NULL,NULL),(431,1,'7a32c4951fd0ad4d207950fa87236dc762adebc8ef181247db92cb0f2d97cc9e',1,'180.249.187.205',5,NULL,'Logged In','2024-01-31 22:19:51',NULL),(432,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(433,NULL,NULL,NULL,'180.249.187.205',5,NULL,'Process',NULL,NULL),(434,1,'1147e356784ddf48e1f0cea85c054f3d5df2f49e75c6c948efce3fc3e8496551',1,'140.213.127.212',5,NULL,'Logged In','2024-01-31 19:42:25',NULL),(435,NULL,NULL,NULL,'180.249.187.205',5,NULL,'Process',NULL,NULL),(436,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(437,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(438,NULL,NULL,NULL,'103.171.31.17',5,NULL,'Process',NULL,NULL),(439,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(440,1,'98eb14d989f89bec525f6297cdab42677059674ed424133639dd0b229c2ead3e',1,'180.249.187.11',5,NULL,'Logged In','2024-02-03 16:51:22',NULL),(441,NULL,NULL,NULL,'180.249.187.11',5,NULL,'Process',NULL,NULL),(442,NULL,NULL,NULL,'180.249.187.11',5,NULL,'Process',NULL,NULL),(443,NULL,NULL,NULL,'180.249.187.11',5,NULL,'Process',NULL,NULL),(444,1,'4fb8f6c2bcf59a962db84ad3bf062fb7ead872e14ce8eb15cddfec00a17f8ac4',1,'112.215.219.235',5,NULL,'Logged In','2024-02-05 11:08:35',NULL),(445,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(446,1,'2f7ff2a30584bc28ea0353003246eeac7d8a80e36a912aee79606fdc2018bc1b',1,'106.0.50.130',5,NULL,'Logged In','2024-07-15 10:27:49',NULL),(447,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(448,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(449,1,'24981226716c490c036b1b5623beedd0c66d5059a7317c7cc1d03a7228df9be4',1,'180.249.184.24',5,NULL,'Logged In','2024-02-10 22:01:07',NULL),(450,NULL,NULL,NULL,'180.249.184.24',5,NULL,'Process',NULL,NULL),(451,NULL,NULL,NULL,'180.249.184.24',5,NULL,'Process',NULL,NULL),(452,NULL,NULL,NULL,'180.249.184.24',5,NULL,'Process',NULL,NULL),(453,NULL,NULL,NULL,'106.0.50.130',5,NULL,'Process',NULL,NULL),(454,1,'a01eaa6ede938245df817d838a15f0d567bc5fad211f5ad9f1f921a52e1948f4',1,'180.249.184.181',5,NULL,'Logged In','2024-02-13 22:09:20',NULL),(455,NULL,NULL,NULL,'180.249.184.181',5,NULL,'Process',NULL,NULL),(456,NULL,NULL,NULL,'180.249.184.181',5,NULL,'Process',NULL,NULL),(457,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(458,1,'a61514112d43525cba1b49d5fe40bfa51bca29afa6b9338fa9eae158a1daf5fb',1,'140.213.151.118',5,NULL,'Logged In','2024-02-15 14:26:21',NULL),(459,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(460,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(461,NULL,NULL,NULL,'101.128.99.222',5,NULL,'Process',NULL,NULL),(462,1,'0e6dedd9edc487847b8fb1e0f1f8f86027536b2d89d4aa31efe7b8a34db277ad',1,'180.249.185.14',5,NULL,'Logged In','2024-02-23 14:33:19',NULL),(463,1,'988725b2041f1dbbaba756f26eee7095475caec8c937f9a073ca4376429d6b55',1,'101.128.98.151',5,NULL,'Logged In','2024-03-15 15:00:46',NULL),(464,NULL,NULL,NULL,'101.128.98.151',5,NULL,'Process',NULL,NULL),(465,1,'cddb4c78297b6e78c2bfdfb7cb5ce5fd2f64fb019b1398e5643ebecbd1989ce0',1,'112.215.220.91',5,NULL,'Logged In','2024-03-07 15:31:08',NULL),(466,NULL,NULL,NULL,'101.128.98.151',5,NULL,'Process',NULL,NULL),(467,1,'df9fa18497a86e49eaca75095b049f84bad2869d672fd69d1a463409942b1848',1,'180.249.187.237',5,NULL,'Logged In','2024-03-13 22:40:11',NULL),(468,NULL,NULL,NULL,'180.249.187.237',5,NULL,'Process',NULL,NULL),(469,NULL,NULL,NULL,'106.0.50.130',5,NULL,'Process',NULL,NULL),(470,1,'2ca6e72798978a65365426c5154912f9e415acd82381b9135de8b638b368f67f',1,'180.249.184.41',5,NULL,'Logged In','2024-03-15 14:42:21',NULL),(471,NULL,NULL,NULL,'101.128.98.151',5,NULL,'Process',NULL,NULL),(472,1,'b1c8faf6c6facf4f2dfcc0ce5271f675e0194af7890e1155691f64bada2ccc2b',1,'101.128.98.130',5,NULL,'Logged In','2024-03-26 15:10:01',NULL),(473,1,'34134157f1c69be8680169a178881675769fade88bb755b1ff73aff4d99bab35',1,'180.249.185.224',5,NULL,'Logged In','2024-03-18 18:25:58',NULL),(474,1,'7709b5cdf48027b6e7317bfc2af8d1c4999c3df1310f03caf09165f85e2eb1d5',1,'114.122.143.0',5,NULL,'Logged In','2024-03-25 16:48:32',NULL),(475,1,'23ee5c981ab80dc91868932908c4a30616ac198f90ccab261a4f97e173d450fe',1,'140.213.127.173',5,NULL,'Logged In','2024-03-26 08:40:57',NULL),(476,NULL,NULL,NULL,'101.128.98.130',5,NULL,'Process',NULL,NULL),(477,1,'63fe84967f571417c659afb13a1e000f8b5530abc4ddcbf7f84aed11625fa451',1,'180.249.184.78',5,NULL,'Logged In','2024-04-12 09:15:44',NULL),(478,NULL,NULL,NULL,'103.171.31.17',5,NULL,'Process',NULL,NULL),(479,1,'58b5bb43c5e9e883e16c8270354de9c715f2d11d2d96b591c5ca6416a73e9e7d',1,'101.128.99.152',5,NULL,'Logged In','2024-05-07 11:54:43',NULL),(480,NULL,NULL,NULL,'106.0.50.130',5,NULL,'Process',NULL,NULL),(481,1,'6798788b033b4e91df61402d14b12e4da5860bd3ff6df55b8068ed3487d513ee',1,'180.249.187.21',5,NULL,'Logged In','2024-05-14 21:44:21',NULL),(482,NULL,NULL,NULL,'103.171.31.17',5,NULL,'Process',NULL,NULL),(483,1,'b7c7e0f65df438f6111a7366d4af17eb2d4137854575ef3abb5a623cae18a8bb',1,'180.249.186.104',5,NULL,'Logged In','2024-06-13 09:31:14',NULL),(484,1,'3e28a585f607a40c3b022ca7fa7f7734812e42f770408f565a68bb2ea70421a9',1,'180.249.186.111',5,NULL,'Logged In','2024-06-18 09:30:33',NULL),(485,NULL,NULL,NULL,'106.0.50.130',5,NULL,'Process',NULL,NULL),(486,1,'73501260413f526f94dd80979c1c2ffdcb58881e7bb0b99321b412d614f1956b',1,'101.128.99.57',5,NULL,'Logged In','2024-07-20 16:54:38',NULL),(487,1,'1649ac7aaf5283ac4a468d1cda1528316f56df9c7e47599fa88f3a57ea0747c9',1,'180.249.184.184',5,NULL,'Logged In','2024-08-13 20:42:23',NULL),(488,NULL,NULL,NULL,'180.249.184.184',5,NULL,'Process',NULL,NULL),(489,1,'ea8ce05c3d935ec4df917547900b3ef65381a7aea796effdfc18091dcb0aa1d2',1,'101.128.98.32',5,NULL,'Logged In','2024-08-21 14:49:29',NULL),(490,NULL,NULL,NULL,'101.128.98.32',5,NULL,'Process',NULL,NULL),(491,NULL,NULL,NULL,'101.128.98.32',5,NULL,'Process',NULL,NULL),(492,NULL,NULL,NULL,'101.128.98.32',5,NULL,'Process',NULL,NULL),(493,1,'15e2564035962cbcc01675e44947e9e378f7882410b9171f99c37ae1b54cb3b0',1,'180.249.187.227',5,NULL,'Logged In','2024-08-22 17:41:05',NULL),(494,NULL,NULL,NULL,'180.249.187.227',5,NULL,'Process',NULL,NULL),(495,NULL,NULL,NULL,'101.128.98.32',5,NULL,'Process',NULL,NULL),(496,1,'185e534f89cfd8bbdd3eb03428cf2fd21ff03e28bcae564751a65b4395081789',1,'101.128.96.107',5,NULL,'Logged In','2024-08-22 12:19:57',NULL),(497,NULL,NULL,NULL,'180.249.187.227',5,NULL,'Process',NULL,NULL),(498,1,'8d6292ac0cd9bf7be138ca7c65a7aa4b126b21da99a4a86f3cfa29d3355cd07f',1,'101.128.98.196',5,NULL,'Logged In','2024-08-27 16:19:13',NULL),(504,1,'3c95e1b8c07c6802d2b20b6051d23270acae26c14a03ae4fa8de59ed5d408f91',1,'::1',5,NULL,'Logged In','2024-09-19 10:02:58',NULL),(505,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(506,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(507,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(508,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(509,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(510,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(511,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(512,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(513,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(514,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(515,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(516,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(517,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(518,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(519,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(520,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(521,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(522,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(523,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(524,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(525,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(526,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(527,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(528,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(529,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(530,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(531,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(532,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(533,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(534,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL),(535,NULL,NULL,NULL,'::1',5,NULL,'Process',NULL,NULL);
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu` (
  `idMenu` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indexSort` int(10) unsigned NOT NULL,
  `namaMenu` varchar(255) NOT NULL,
  `iconClass` varchar(100) NOT NULL,
  `statusMenu` varchar(30) NOT NULL,
  `timeStampInput` timestamp NOT NULL DEFAULT current_timestamp(),
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `idUser` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`idMenu`),
  UNIQUE KEY `namaMenu` (`namaMenu`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu`
--

/*!40000 ALTER TABLE `menu` DISABLE KEYS */;
INSERT INTO `menu` VALUES (1,5,'Laporan','fas fa-book','Aktif','2023-09-20 05:04:20','2024-09-03 00:50:31',1),(3,3,'Operational System','fas fa-handshake','Aktif','2023-09-19 11:08:38','2024-01-29 01:52:32',1),(17,1,'Master Data',' fas fa-database','Aktif','2024-01-25 05:53:24','2024-09-01 23:48:39',1),(19,1,'Purchasing','fas fa-shopping-basket','Aktif','2024-09-20 05:21:07','2024-09-20 05:21:20',1);
/*!40000 ALTER TABLE `menu` ENABLE KEYS */;

--
-- Table structure for table `menu_sub`
--

DROP TABLE IF EXISTS `menu_sub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_sub` (
  `idSubMenu` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indexSort` int(11) NOT NULL,
  `idMenu` bigint(20) unsigned NOT NULL,
  `namaSubMenu` varchar(255) NOT NULL,
  `namaKelompok` varchar(255) NOT NULL,
  `namaFolder` varchar(255) NOT NULL,
  `formatAksi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["Edit","Delete"]',
  `idUser` bigint(20) unsigned NOT NULL,
  `timeStampInput` timestamp NOT NULL DEFAULT current_timestamp(),
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idSubMenu`),
  UNIQUE KEY `idMenu` (`idMenu`,`namaSubMenu`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_sub`
--

/*!40000 ALTER TABLE `menu_sub` DISABLE KEYS */;
INSERT INTO `menu_sub` VALUES (6,10,2,'Hotel','master_data','hotel','[\"Edit\",\"Delete\"]',1,'2023-10-03 06:03:26','2024-01-26 07:19:42'),(7,9,2,'Client','master_data','client','[\"Edit\",\"Delete\"]',1,'2023-10-06 05:11:33','2024-01-26 07:19:50'),(8,7,2,'Region','master_data','region','[\"Edit\",\"Delete\"]',1,'2024-01-09 14:16:36','2024-01-17 00:38:06'),(9,6,2,'Agent','master_data','agent','[\"Edit\",\"Delete\"]',1,'2024-01-10 00:23:06','2024-01-17 00:34:39'),(10,8,2,'Supplier','master_data','supplier','[\"Edit\",\"Delete\"]',1,'2024-01-10 02:18:21','2024-01-17 00:38:18'),(11,11,2,'Activity','master_data','activity','[\"Edit\",\"Delete\"]',1,'2024-01-10 06:53:47','2024-01-17 00:38:46'),(21,1,17,'Menu','master_data','menu','[\"Edit\",\"Delete\"]',1,'2024-01-25 05:53:45','2024-09-09 02:20:10'),(22,3,17,'Pegawai','master_data','pegawai','[\"Edit\",\"Delete\"]',1,'2024-01-25 06:04:15','2024-01-25 06:04:32'),(23,2,17,'Departemen/Jabatan','master_data','departemen_pegawai','[\"Edit\",\"Delete\"]',1,'2024-01-25 06:04:59','2024-09-01 23:49:13'),(24,4,17,'User','master_data','user','[\"Edit\",\"Delete\"]',1,'2024-01-25 06:05:59','2024-01-25 06:05:59'),(25,5,17,'Cabang','master_data','cabang','[\"Edit\",\"Delete\"]',1,'2024-01-25 06:24:12','2024-01-25 06:24:12'),(26,12,2,'Note','master_data','note','[\"Edit\",\"Delete\"]',1,'2024-08-21 04:46:14','2024-08-21 04:46:14'),(27,1,18,'menu 1','master_data','test','[\"Edit\",\"Delete\"]',1,'2024-09-03 01:16:48','2024-09-03 01:16:48'),(28,1,17,'Account','master_data','account','[\"Edit\",\"Delete\"]',1,'2024-09-03 03:24:03','2024-09-05 01:17:28'),(29,6,17,'Bank','master_data','bank','[\"Edit\",\"Delete\"]',1,'2024-09-05 02:09:30','2024-09-05 02:09:30'),(30,1,3,'Pemasukan/Pengeluaran','proses_bisnis','pemasukan_pengeluaran_lain','[\"Edit\",\"Delete\"]',1,'2024-09-06 01:31:24','2024-09-06 01:35:19'),(31,1,1,'Kas Bank','laporan','kas_bank','[\"Edit\",\"Delete\"]',1,'2024-09-06 07:30:09','2024-09-10 05:51:11'),(32,2,3,'Biaya','proses_bisnis','biaya','[\"Edit\",\"Delete\"]',1,'2024-09-07 00:40:44','2024-09-07 00:50:26'),(33,7,17,'Petty Cash','master_data','petty_cash','[\"Edit\",\"Delete\"]',1,'2024-09-09 01:20:25','2024-09-09 01:20:25'),(34,3,3,'Budgeting Project','master_data','budgeting_project','[\"Edit\",\"Delete\"]',1,'2024-09-09 02:20:45','2024-09-09 02:20:45'),(35,8,17,'Vendor','master_data','vendor','[\"Edit\",\"Delete\"]',1,'2024-09-09 03:02:07','2024-09-09 03:02:07'),(36,1,19,'Input Purchasing','purchasing','input_purchasing','[\"Edit\",\"Delete\"]',1,'2024-09-20 05:25:48','2024-09-20 06:58:00'),(37,9,17,'Barang','master_data','barang','[\"Edit\",\"Delete\"]',1,'2024-09-20 05:38:36','2024-09-20 05:38:36');
/*!40000 ALTER TABLE `menu_sub` ENABLE KEYS */;

--
-- Table structure for table `nomor_urut`
--

DROP TABLE IF EXISTS `nomor_urut`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomor_urut` (
  `idNomor` bigint(20) NOT NULL AUTO_INCREMENT,
  `jenisNomor` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `nomorUrut` bigint(20) NOT NULL,
  PRIMARY KEY (`idNomor`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nomor_urut`
--

/*!40000 ALTER TABLE `nomor_urut` DISABLE KEYS */;
INSERT INTO `nomor_urut` VALUES (1,'pegawai',1,6),(2,'hotel',1,13),(3,'ID',1,3),(4,'cabang',1,4),(5,'client',1,29),(65,'rekam_medis',1,1),(66,'agent',1,3),(67,'supplier',1,13),(68,'activity',1,23),(69,'pelayanan_infusion',1,2),(70,'daftar',1,33),(71,'tujuan_transfer',1,1),(72,'account',1,74),(73,'bank',1,4),(74,'pemasukan_pengeluaran_lain',1,24),(75,'biaya_klinik',1,1),(76,'biaya',1,3),(77,'vendor',1,3),(78,'budgeting_project',1,4),(79,'budgeting_project_progres',1,12),(80,'inventory_obat',1,1),(81,'inventory_Barang',1,3),(82,'antrian_klinik',1,1),(83,'purchasing',1,1);
/*!40000 ALTER TABLE `nomor_urut` ENABLE KEYS */;

--
-- Table structure for table `nomor_voucher`
--

DROP TABLE IF EXISTS `nomor_voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomor_voucher` (
  `idNomorVoucher` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kodeAgent` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `kodeVoucher` varchar(50) NOT NULL,
  `nomorVoucher` int(15) DEFAULT NULL,
  `idUser` bigint(20) unsigned NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idNomorVoucher`),
  UNIQUE KEY `kodeAgent` (`kodeAgent`,`kodeVoucher`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nomor_voucher`
--

/*!40000 ALTER TABLE `nomor_voucher` DISABLE KEYS */;
INSERT INTO `nomor_voucher` VALUES (1,'NT/agent/1/000000001','WT',11,1,'2024-03-14 01:18:13'),(3,'NT','NT',121,1,'2024-01-14 09:29:17'),(4,'NT/agent/1/000000002','JT',11,1,'2024-08-21 04:42:32'),(5,'VOUCHER','VC',8,1,'2024-03-07 07:50:13'),(6,'INV','INV',18,1,'2024-03-07 07:42:46'),(7,'REF','REF',22,1,'2024-08-27 08:33:54');
/*!40000 ALTER TABLE `nomor_voucher` ENABLE KEYS */;

--
-- Table structure for table `pegawai`
--

DROP TABLE IF EXISTS `pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pegawai` (
  `idPegawai` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kodePegawai` varchar(100) NOT NULL,
  `idDepartemenPegawai` bigint(20) NOT NULL,
  `namaPegawai` varchar(255) NOT NULL,
  `NIKPegawai` varchar(255) NOT NULL DEFAULT '-',
  `jenisKelamin` varchar(50) NOT NULL DEFAULT 'Laki-laki',
  `noRekening` varchar(255) NOT NULL DEFAULT '-',
  `npwp` varchar(100) NOT NULL DEFAULT '-',
  `ttlPegawai` date NOT NULL,
  `tempatLahir` varchar(255) NOT NULL DEFAULT '-',
  `agama` varchar(50) NOT NULL DEFAULT '-',
  `pendidikan` varchar(255) NOT NULL DEFAULT '-',
  `email` varchar(255) NOT NULL,
  `alamatPegawai` text NOT NULL DEFAULT '-',
  `ktpPegawai` varchar(255) NOT NULL DEFAULT '-',
  `hpPegawai` varchar(255) NOT NULL DEFAULT '-',
  `tglMulaiKerja` date NOT NULL,
  `keterangan` text NOT NULL,
  `fileTTD` longtext DEFAULT NULL,
  `statusPegawai` varchar(30) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL DEFAULT 1,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPegawai`),
  UNIQUE KEY `kodePegawai` (`kodePegawai`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pegawai`
--

/*!40000 ALTER TABLE `pegawai` DISABLE KEYS */;
INSERT INTO `pegawai` VALUES (1,'NT/pegawai/1/000000003',1,'Nikolaj Jensen','1235432341','Laki-laki','1789754456','0990987','1978-01-03','Jerman','Lain-lain','s1','test@gmail.com','test','-','0816889','0000-00-00','',NULL,'Aktif',1,'2024-01-07 03:48:38',1,'2024-01-16 04:24:16'),(2,'NT/pegawai/1/000000004',4,'Dennish','9078654','Laki-laki','0989765','098901','1985-01-05','DENPASAR','Lain-lain','s1','test@gmail.com','-','-','098','0000-00-00','',NULL,'Aktif',1,'2024-01-09 05:59:41',NULL,'2024-01-09 05:59:45');
/*!40000 ALTER TABLE `pegawai` ENABLE KEYS */;

--
-- Table structure for table `pegawai_penghasilan`
--

DROP TABLE IF EXISTS `pegawai_penghasilan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pegawai_penghasilan` (
  `idPenghasilanPegawai` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kodePegawai` varchar(255) NOT NULL,
  `jenisPenghasilan` varchar(255) NOT NULL,
  `namaPenghasilan` varchar(255) NOT NULL,
  `nominal` int(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) unsigned NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPenghasilanPegawai`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pegawai_penghasilan`
--

/*!40000 ALTER TABLE `pegawai_penghasilan` DISABLE KEYS */;
INSERT INTO `pegawai_penghasilan` VALUES (1,'NT/pegawai/1/000000003','Gaji Pokok','Gaji Pokok',15000000,'Aktif',1,'2024-01-07 03:52:37'),(2,'NT/pegawai/1/000000003','Tunjangan','Tunjangan 1',3000000,'Aktif',1,'2024-01-07 03:52:42'),(3,'NT/pegawai/1/000000003','Potongan','potongan 1',500000,'Aktif',1,'2024-01-07 03:53:28');
/*!40000 ALTER TABLE `pegawai_penghasilan` ENABLE KEYS */;

--
-- Table structure for table `pemasukan_pengeluaran_lain`
--

DROP TABLE IF EXISTS `pemasukan_pengeluaran_lain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pemasukan_pengeluaran_lain` (
  `idPemasukanPengeluaranLain` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodePemasukanPengeluaranLain` varchar(255) NOT NULL,
  `idSubAccount` varchar(255) NOT NULL,
  `tipe` varchar(255) NOT NULL,
  `jenisRekening` varchar(255) NOT NULL,
  `idRekening` bigint(20) DEFAULT NULL,
  `nominal` bigint(20) DEFAULT NULL,
  `keterangan` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `statusPemasukanPengeluaranLain` varchar(255) DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPemasukanPengeluaranLain`),
  UNIQUE KEY `kodePemasukanPengeluaranLain` (`kodePemasukanPengeluaranLain`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemasukan_pengeluaran_lain`
--

/*!40000 ALTER TABLE `pemasukan_pengeluaran_lain` DISABLE KEYS */;
INSERT INTO `pemasukan_pengeluaran_lain` VALUES (1,'MUM/pemasukan_pengeluaran_lain/1/000000001','02.1','Pemasukan Lain','Bank',3,10000,'test','2024-09-06','Aktif',1,'2024-09-06 07:22:42',NULL,'2024-09-06 07:22:42'),(2,'MUM/pemasukan_pengeluaran_lain/1/000000002','02.1','Pemasukan Lain','Bank',3,1000000000,'test','2024-09-06','Aktif',1,'2024-09-06 07:24:01',NULL,'2024-09-06 07:24:01'),(3,'MUM/pemasukan_pengeluaran_lain/1/000000003','02.1','Pengeluaran Lain','Bank',3,20000,'test','2024-09-06','Aktif',1,'2024-09-06 08:20:57',NULL,'2024-09-06 08:20:57'),(7,'MUM/pemasukan_pengeluaran_lain/1/000000007','1','Pemasukan Lain','Bank',3,1111111,'pemasukan','2024-09-09','Aktif',1,'2024-09-09 01:17:42',NULL,'2024-09-09 01:17:42'),(8,'MUM/pemasukan_pengeluaran_lain/1/000000008','1','Pengeluaran Lain','Bank',3,11111,'pengeluaran','2024-09-09','Aktif',1,'2024-09-09 01:18:24',NULL,'2024-09-09 01:18:24'),(19,'MUM/pemasukan_pengeluaran_lain/1/000000019','1','Pemasukan Lain','Petty Cash',1,231421231,'pewamsukan','2024-09-09','Aktif',1,'2024-09-09 01:26:54',NULL,'2024-09-09 01:26:54'),(20,'MUM/pemasukan_pengeluaran_lain/1/000000020','1','Pengeluaran Lain','Petty Cash',1,100000,'pengeluaran','2024-09-09','Aktif',1,'2024-09-09 01:27:14',NULL,'2024-09-09 01:27:14'),(21,'MUM/pemasukan_pengeluaran_lain/1/000000021','1','Pemasukan Lain','Bank',3,34543,'fsegsf','2024-09-10','Aktif',1,'2024-09-10 05:48:31',NULL,'2024-09-10 05:48:31'),(22,'MUM/pemasukan_pengeluaran_lain/1/000000022','1','Pengeluaran Lain','Bank',4,345435,'dafgaw','2024-09-10','Aktif',1,'2024-09-10 05:48:46',NULL,'2024-09-10 05:48:46'),(23,'MUM/pemasukan_pengeluaran_lain/1/000000023','1','Pemasukan Lain','Bank',3,3243245,'sfaf','2024-09-13','Aktif',1,'2024-09-13 07:01:34',NULL,'2024-09-13 07:01:34');
/*!40000 ALTER TABLE `pemasukan_pengeluaran_lain` ENABLE KEYS */;

--
-- Table structure for table `petty_cash`
--

DROP TABLE IF EXISTS `petty_cash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `petty_cash` (
  `idPettyCash` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodePettyCash` varchar(255) NOT NULL,
  `tipe` varchar(100) NOT NULL,
  `namaPettyCash` varchar(255) NOT NULL,
  `statusPettyCash` varchar(100) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPettyCash`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `petty_cash`
--

/*!40000 ALTER TABLE `petty_cash` DISABLE KEYS */;
INSERT INTO `petty_cash` VALUES (1,'MUM/tujuan_transfer/1/000000001','','PETTY CASH KANTOR','Aktif',1,'2024-09-09 01:20:50',NULL,'2024-09-09 01:20:52');
/*!40000 ALTER TABLE `petty_cash` ENABLE KEYS */;

--
-- Table structure for table `purchasing`
--

DROP TABLE IF EXISTS `purchasing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchasing` (
  `idPurchasing` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodePurchasing` varchar(100) NOT NULL,
  `kodeVendor` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `statusPersetujuan` varchar(50) NOT NULL DEFAULT 'Pending',
  `statusPurchasing` varchar(50) NOT NULL DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPurchasing`),
  UNIQUE KEY `kodePurchasing` (`kodePurchasing`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchasing`
--

/*!40000 ALTER TABLE `purchasing` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchasing` ENABLE KEYS */;

--
-- Table structure for table `purchasing_detail`
--

DROP TABLE IF EXISTS `purchasing_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchasing_detail` (
  `idPurchasingDetail` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodePurchasing` varchar(100) NOT NULL,
  `idBarang` bigint(20) NOT NULL,
  `qty` int(11) NOT NULL,
  `statusPurchasingDetail` varchar(100) NOT NULL,
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idPurchasingDetail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchasing_detail`
--

/*!40000 ALTER TABLE `purchasing_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchasing_detail` ENABLE KEYS */;

--
-- Table structure for table `sub_account`
--

DROP TABLE IF EXISTS `sub_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_account` (
  `idSubAccount` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeAccount` varchar(255) NOT NULL,
  `kodeSub` varchar(255) NOT NULL,
  `namaSubAccount` varchar(255) NOT NULL,
  `statusSubAccount` varchar(255) DEFAULT 'Aktif',
  `idUser` bigint(20) NOT NULL,
  `timeStamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idSubAccount`),
  UNIQUE KEY `kodeSub` (`kodeSub`),
  KEY `kodeAccount` (`kodeAccount`),
  CONSTRAINT `sub_account_ibfk_1` FOREIGN KEY (`kodeAccount`) REFERENCES `account` (`kodeAccount`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_account`
--

/*!40000 ALTER TABLE `sub_account` DISABLE KEYS */;
INSERT INTO `sub_account` VALUES (1,'MUM/account/1/000000020','02.1','BPD Cabang Utama Denpasar','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 08:12:34'),(2,'MUM/account/1/000000020','02.2','Bank Mandiri Cabang Denpasar - Gatot Subroto','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(3,'MUM/account/1/000000020','02.3','Bank Danamon','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(4,'MUM/account/1/000000020','02.4','Bank BRI','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(5,'MUM/account/1/000000020','02.5','Bank BTPN','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(6,'MUM/account/1/000000020','02.6','Bank Mandiri (Gaji)','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(7,'MUM/account/1/000000021','03.1','Piutang Dagang / Proyek','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(8,'MUM/account/1/000000021','03.2','Piutang Umum Jangka Panjang','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(9,'MUM/account/1/000000021','03.3','Piutang Umum Jangka Pendek','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(10,'MUM/account/1/000000021','03.4','Piutang Lainnya','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(11,'MUM/account/1/000000023','05.1','Kantor / Gudang','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(12,'MUM/account/1/000000023','05.2','Kendaraan Bermotor','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(13,'MUM/account/1/000000023','05.3','Alat / Peralatan Kantor','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-06 05:14:28'),(14,'MUM/account/1/000000023','05.4','Alat / Peralatan Kerja','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(15,'MUM/account/1/000000026','08.1','Utang Dagang / Proyek','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(16,'MUM/account/1/000000026','08.2','Utang Umum Jangka Pendek','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(17,'MUM/account/1/000000026','08.3','Utang Bank','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(18,'MUM/account/1/000000026','08.4','Utang Lainnya','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(19,'MUM/account/1/000000028','10.1','PPh Pasal 22','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(20,'MUM/account/1/000000028','10.2','PPh Pasal 23','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(21,'MUM/account/1/000000028','10.3','PPh Pasal 25','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(22,'MUM/account/1/000000035','17.1','Biaya Gaji','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(23,'MUM/account/1/000000035','17.2','Biaya Administrasi Kantor','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(24,'MUM/account/1/000000035','17.3','Biaya Listrik, Telepon & Air','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(25,'MUM/account/1/000000035','17.4','Angkutan dan BBM','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(26,'MUM/account/1/000000035','17.5','Biaya Pemasaran / Marketing','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(27,'MUM/account/1/000000035','17.6.1','Spare Part & Bahan','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(28,'MUM/account/1/000000035','17.6.2','Upah','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(29,'MUM/account/1/000000035','17.7','Lain - Lain','Aktif',1,'2024-09-04 07:38:46',NULL,'2024-09-04 07:38:46'),(30,'MUM/account/1/000000037','111.1','test','Aktif',1,'2024-09-04 07:46:01',NULL,'2024-09-04 07:46:01'),(31,'MUM/account/1/000000037','111.2','test','Aktif',1,'2024-09-04 07:46:25',NULL,'2024-09-04 07:46:25'),(32,'MUM/account/1/000000019','1.1000','test','Non Aktif',1,'2024-09-04 08:14:43',NULL,'2024-09-05 01:15:29'),(34,'MUM/account/1/000000019','1.10000','test','Non Aktif',1,'2024-09-05 01:16:51',NULL,'2024-09-05 01:16:57');
/*!40000 ALTER TABLE `sub_account` ENABLE KEYS */;

--
-- Table structure for table `uploaded_file`
--

DROP TABLE IF EXISTS `uploaded_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uploaded_file` (
  `idUploadedFile` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kodeFile` varchar(100) NOT NULL,
  `noForm` varchar(100) NOT NULL,
  `fileName` text NOT NULL,
  `htmlName` varchar(255) NOT NULL,
  `folder` varchar(255) NOT NULL,
  `sizeFile` bigint(20) unsigned NOT NULL,
  `ekstensi` varchar(50) NOT NULL,
  `idUserInput` bigint(20) unsigned NOT NULL,
  `timeStampInput` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) unsigned DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idUploadedFile`),
  UNIQUE KEY `kodeFile` (`kodeFile`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploaded_file`
--

/*!40000 ALTER TABLE `uploaded_file` DISABLE KEYS */;
INSERT INTO `uploaded_file` VALUES (24,'3cff713dd3fd91e78d6f0a46bf490616','NT/agent/1/000000001','winberg.jpeg','imgKopAgent','agent',47181,'jpeg',1,'2024-01-13 04:34:07',NULL,'2024-01-13 04:34:07'),(26,'5f9fb72c6ef4d2fe0251cef0f0b14967','kNT/cabang/1/000000001','nicktours logo without travel.png','imgKopCabang','cabang',636342,'png',1,'2024-01-15 07:05:42',NULL,'2024-01-15 07:05:42'),(27,'b6d33f770150d281bc77d78c862ff280','NT/agent/1/000000002','winberg.jpg','imgKopAgent','agent',26493,'jpg',1,'2024-02-03 02:36:20',NULL,'2024-02-03 02:36:20'),(55,'22caeeb5fb74ba296fd04422f2be6bfb','MUM/budgeting_project_progres/1/000000006','22caeeb5fb74ba296fd04422f2be6bfb_login-wallpaper.jpg','imgNota','progres_budgeting',66515,'jpg',1,'2024-09-13 05:36:15',NULL,'2024-09-13 05:36:15'),(56,'2555d8c600db620fdbcba1035c365ea2','MUM/budgeting_project_progres/1/000000007','2555d8c600db620fdbcba1035c365ea2_login-icon3.png','imgNota','progres_budgeting',38238,'png',1,'2024-09-13 05:36:51',NULL,'2024-09-13 05:36:51'),(57,'54388e618320a5217ad5b1d1a0b72da5','MUM/budgeting_project_progres/1/000000008','54388e618320a5217ad5b1d1a0b72da5_login-icon.png','imgNota','progres_budgeting',37317,'png',1,'2024-09-13 06:56:59',NULL,'2024-09-13 06:56:59'),(58,'c9970862a1de11fcb66856d261313594','MUM/budgeting_project_progres/1/000000009','c9970862a1de11fcb66856d261313594_login-wallpaper.jpg','imgNota','progres_budgeting',66515,'jpg',1,'2024-09-13 06:57:43',NULL,'2024-09-13 06:57:43'),(59,'2184cf8bc80a871351f213c34eacc820','MUM/budgeting_project_progres/1/000000010','2184cf8bc80a871351f213c34eacc820_login-icon.png','imgNota','progres_budgeting',37317,'png',1,'2024-09-13 06:58:02',NULL,'2024-09-13 06:58:02'),(60,'ecaf8ac1ce2ff9887d470c997e2a41c3','MUM/budgeting_project_progres/1/000000011','ecaf8ac1ce2ff9887d470c997e2a41c3_base-icon2.png','imgNota','progres_budgeting',361372,'png',1,'2024-09-13 06:59:29',NULL,'2024-09-13 06:59:29');
/*!40000 ALTER TABLE `uploaded_file` ENABLE KEYS */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `idUser` bigint(20) NOT NULL AUTO_INCREMENT,
  `idPegawai` bigint(20) NOT NULL,
  `userName` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `aksesEditable` varchar(20) NOT NULL DEFAULT 'Non Aktif',
  `autoLogOut` varchar(20) NOT NULL DEFAULT 'Non Aktif',
  `listAksesKlinik` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["__ALL__"]',
  `statusUser` varchar(25) NOT NULL,
  `idUserInput` bigint(20) unsigned NOT NULL,
  `timeStampInput` timestamp NOT NULL DEFAULT current_timestamp(),
  `idUserEdit` bigint(20) unsigned DEFAULT NULL,
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idUser`),
  UNIQUE KEY `userName` (`userName`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,1,'admin','$2y$10$zsZnO/rCjRsw9rfmp7/ggOJo/dn1l0RyOHI.wQ53rYfB2mJpl3Un.','Aktif','Non Aktif','[\"__ALL__\"]','Aktif',1,'2021-09-19 03:33:55',NULL,'2023-12-06 04:28:07'),(2,2,'dennish','$2y$10$rAjg9foLwTjYbpZaUwvsBuoUz0Pcy0p8mTsQjpBrpsIEvIhmWHr/2','Non Aktif','Non Aktif','[\"__ALL__\"]','Aktif',1,'2024-01-09 06:00:16',NULL,'2024-09-01 23:52:12');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;

--
-- Table structure for table `user_dashboard`
--

DROP TABLE IF EXISTS `user_dashboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_dashboard` (
  `idUserDashboard` bigint(20) NOT NULL AUTO_INCREMENT,
  `idUser` bigint(20) NOT NULL,
  `widget` varchar(255) NOT NULL,
  `hakAkses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"Edit":"Active","Delete":"Active"}',
  `timeStampInput` timestamp NOT NULL DEFAULT current_timestamp(),
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idUserDashboard`),
  UNIQUE KEY `idUser` (`idUser`,`widget`)
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_dashboard`
--

/*!40000 ALTER TABLE `user_dashboard` DISABLE KEYS */;
INSERT INTO `user_dashboard` VALUES (235,2,'__WIDGET_PENJUALAN__','{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 08:07:30','2024-09-03 08:07:30'),(236,2,'__WIDGET_UANG_MASUK__','{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 08:07:30','2024-09-03 08:07:30');
/*!40000 ALTER TABLE `user_dashboard` ENABLE KEYS */;

--
-- Table structure for table `user_detail`
--

DROP TABLE IF EXISTS `user_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_detail` (
  `idUserDetail` bigint(20) NOT NULL AUTO_INCREMENT,
  `idUser` bigint(20) NOT NULL,
  `idSubMenu` bigint(20) NOT NULL,
  `hakAkses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"Edit":"Active","Delete":"Active"}',
  `timeStampInput` timestamp NOT NULL DEFAULT current_timestamp(),
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idUserDetail`),
  UNIQUE KEY `idUser` (`idUser`,`idSubMenu`)
) ENGINE=InnoDB AUTO_INCREMENT=1078 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_detail`
--

/*!40000 ALTER TABLE `user_detail` DISABLE KEYS */;
INSERT INTO `user_detail` VALUES (74,1,6,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2023-09-20 04:56:24','2023-09-20 04:56:24'),(75,1,7,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2023-09-20 04:56:25','2023-09-28 09:51:00'),(1002,1,8,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-09 14:16:56','2024-01-09 14:16:56'),(1003,1,9,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-10 00:23:41','2024-01-10 00:23:41'),(1004,1,10,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-10 02:18:35','2024-01-10 02:18:35'),(1005,1,11,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-10 06:54:01','2024-01-10 06:54:01'),(1007,1,12,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-15 03:31:34','2024-01-15 03:31:34'),(1008,1,13,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-15 03:45:17','2024-01-15 03:45:17'),(1009,1,15,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-15 04:05:13','2024-01-15 04:05:13'),(1010,1,14,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-15 04:05:13','2024-01-15 04:05:13'),(1013,2,13,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-15 06:20:28','2024-09-01 23:52:21'),(1016,2,7,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-18 08:09:12','2024-09-01 23:52:26'),(1017,2,8,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-18 08:09:13','2024-09-01 23:52:30'),(1018,2,9,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-18 08:09:15','2024-09-01 23:52:34'),(1019,2,10,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-18 08:09:16','2024-09-01 23:52:37'),(1020,2,11,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-18 08:09:17','2024-09-01 23:52:41'),(1021,2,6,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-18 08:09:28','2024-09-01 23:52:46'),(1023,1,16,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-25 05:22:29','2024-01-25 05:22:29'),(1024,1,17,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-25 05:28:01','2024-01-25 05:28:01'),(1025,1,18,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-25 05:30:28','2024-01-25 05:30:28'),(1033,1,25,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-01-25 06:24:53','2024-01-25 06:24:53'),(1034,1,26,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-08-21 04:46:28','2024-08-21 04:46:28'),(1035,2,26,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-08-21 04:46:47','2024-09-01 23:52:55'),(1045,1,27,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 01:17:16','2024-09-03 01:17:16'),(1046,1,28,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 03:24:13','2024-09-03 03:24:13'),(1047,1,21,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 08:06:20','2024-09-03 08:06:20'),(1048,1,24,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 08:06:35','2024-09-03 08:06:35'),(1049,1,22,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 08:06:51','2024-09-03 08:06:51'),(1050,1,23,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-03 08:06:52','2024-09-03 08:06:52'),(1057,1,29,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-05 02:09:38','2024-09-05 02:09:38'),(1059,1,30,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-06 01:33:51','2024-09-06 01:33:51'),(1061,1,31,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-06 07:30:51','2024-09-06 07:30:51'),(1069,1,32,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-07 00:41:26','2024-09-07 00:41:26'),(1070,1,33,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-09 01:20:34','2024-09-09 01:20:34'),(1071,1,34,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-09 02:21:56','2024-09-09 02:21:56'),(1072,1,35,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-09 03:02:15','2024-09-09 03:02:15'),(1075,1,36,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-20 05:26:15','2024-09-20 05:26:15'),(1076,1,37,'{\"Edit\":\"Active\",\"Delete\":\"Active\"}','2024-09-20 05:39:11','2024-09-20 05:39:11');
/*!40000 ALTER TABLE `user_detail` ENABLE KEYS */;

--
-- Table structure for table `vendor`
--

DROP TABLE IF EXISTS `vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor` (
  `idVendor` bigint(20) NOT NULL AUTO_INCREMENT,
  `kodeVendor` varchar(100) NOT NULL,
  `jenisVendor` varchar(50) NOT NULL DEFAULT 'Customer',
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
  `timeStampEdit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idVendor`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor`
--

/*!40000 ALTER TABLE `vendor` DISABLE KEYS */;
INSERT INTO `vendor` VALUES (1,'MUM/vendor/1/000000001','Customer','ceo vendor','123','Vendor A','alamat Vendor','123','Mandiri','123123123','ceo vendor','vendor@email','Vendor 1','Aktif',1,'2024-09-09 03:10:41',NULL,'2024-09-20 08:01:01'),(2,'MUM/vendor/1/000000002','Supplier','ceo vendor','234','Vendor B','alamat Vendor','2341','Mandiri','123','test','mrb.haqee@gmail.com','wafdwaf','Aktif',1,'2024-09-20 08:03:07',NULL,'2024-09-20 08:03:09');
/*!40000 ALTER TABLE `vendor` ENABLE KEYS */;

--
-- Dumping routines for database 'masterdb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-09-20 16:38:23
