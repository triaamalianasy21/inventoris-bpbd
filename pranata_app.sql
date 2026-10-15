-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2026 at 06:40 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pranata_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `id_lokasi` int(11) NOT NULL,
  `kode_barang` varchar(50) DEFAULT NULL,
  `nomor_register` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `tahun_perolehan` year(4) DEFAULT NULL,
  `merk_tipe` varchar(100) DEFAULT NULL,
  `ukuran_cc` varchar(50) DEFAULT NULL,
  `bahan` varchar(80) DEFAULT NULL,
  `pabrik` varchar(80) DEFAULT NULL,
  `nomor_rangka` varchar(80) DEFAULT NULL,
  `nomor_mesin` varchar(80) DEFAULT NULL,
  `nomor_polisi` varchar(30) DEFAULT NULL,
  `nomor_bpkb` varchar(80) DEFAULT NULL,
  `asal_usul` varchar(120) DEFAULT NULL,
  `harga_perolehan` decimal(15,2) DEFAULT NULL,
  `kondisi` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `stok_barang` int(11) NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `status_brg` varchar(30) DEFAULT NULL,
  `tgl_masuk` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `id_lokasi`, `kode_barang`, `nomor_register`, `nama_barang`, `tahun_perolehan`, `merk_tipe`, `ukuran_cc`, `bahan`, `pabrik`, `nomor_rangka`, `nomor_mesin`, `nomor_polisi`, `nomor_bpkb`, `asal_usul`, `harga_perolehan`, `kondisi`, `keterangan`, `stok_barang`, `foto`, `status_brg`, `tgl_masuk`) VALUES
(2, 2, '132020101003', '1', 'Station Wagon', 2011, 'Isuzu', '2500 cc', 'Besi', 'Mitsubishi', '6B311503', 'E311503', 'R 1249 XA', 'L04926549', 'APBD Kab.', '219976311.00', 'B', 'Ex R 9506 NS', 1, NULL, 'aktif', '2026-01-09'),
(3, 1, '132020101003', '1', 'Mobil', 2013, 'TOYOTA / NEW AVANZA 1,3 M/T', '1329', 'Besi', 'Toyota', 'MHKM1BA3JDK174830', 'MC48409', 'R 1478 XA', 'K-03108470', 'APBD Kab.', '171216129.00', 'Baik', 'R 103 A hanti menjadi R 1478 XA', 10, 'barang_foto_1769670247.jfif', 'menunggu_verifikasi', '2026-01-09'),
(4, 1, '132020101003', '2', 'Station Wagon', 2016, 'Toyota Kijang / Innova G 2.0', '1998 ', 'Besi', 'Toyota', 'MHFJW8EM9G2316075', '1TR-A169155 ', 'R 1708 XA', 'N-01944739', 'APBD Kab.', '298420000.00', 'B', 'Sekretariat Daerah/Ass I', 2, 'barang_foto_1768205038.jpg', 'terverifikasi', '2026-01-09'),
(19, 1, '132010304002', '1', 'Portable Generating Set ', 2016, 'Honda Tropic', '5,5 KVA', 'Besi', '', '', '', '', '', 'Hibah', '15950000.00', 'Baik', 'semua baik', 1, 'barang_foto_1768739035.jpg', 'menunggu_verifikasi', '2016-01-18'),
(20, 2, '132010312008', '2-5', 'Veld Bed Aluminium', 2024, 'BNPB/ Aluminium', '', 'Aluminium + Kain', '', '', '', '', '', 'Hibah', '4828000.00', 'Baik', 'Lengkap', 2, 'barang_foto_1768821761.jpg', 'terverifikasi', '2024-01-19'),
(21, 1, '132020101033', '2', 'Mobil Pick Up', 2013, 'Isuzu', '2499', 'Besi', '', 'MHCTBR54FDK166583', 'E166583', 'R 8063 XA', 'L-04701868', 'APBD Kab', '240834000.00', 'Baik', 'Mutasi dari DPPKAD', 1, 'barang_foto_1768900952.jpg', 'terverifikasi', '2026-01-22'),
(22, 2, '132010305010', '2', 'Pompa Air', 2023, 'DELUX/ WMP 800', NULL, 'Besi', NULL, NULL, NULL, NULL, NULL, 'APBD Kab', '6300000.00', 'Baik', 'Lengkap', 2, 'barang_foto_1768901944.jpg', 'menunggu_verifikasi', '2023-02-09'),
(24, 2, '11426389', '4', 'Tenda', 2023, 'Isuzu', NULL, 'Kain', NULL, NULL, NULL, NULL, NULL, NULL, '100000000.00', 'Baik', 'lengkap', 5, 'barang_foto_1768964478.jpg', 'terverifikasi', '2024-01-24'),
(26, 2, '3904u93843', '8', 'Sepeda motor Trail', 2023, 'Suzuki', '1329', 'Besi M', 'Toyota', '94uiuhfuiehif', 'm84875', 'R 1890 TU', '89375918', 'APBD Kab', '219976311.00', 'Baik', 'Bersih', 5, 'barang_foto_1776998914.png', 'menunggu_verifikasi', '2023-02-24'),
(27, 2, '990225', '1', 'pelampung', 2026, 'laut', '1329', 'karet', 'Toyota', 'MHKM1BA3JDK174830', 'MC48409', 'R 1478 XA', 'N-01944739', 'APBD Kab.', '171216129.00', 'Baik', 'lwngkzp', 6, 'barang_foto_1777002496.png', 'menunggu_verifikasi', '2026-04-24');

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id_detail_pinjam` int(11) NOT NULL,
  `id_pinjam` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `detail_peminjaman`
--

INSERT INTO `detail_peminjaman` (`id_detail_pinjam`, `id_pinjam`, `id_barang`, `jumlah`) VALUES
(3, 3, 3, 1),
(7, 5, 3, 1),
(8, 6, 3, 1),
(9, 7, 2, 1),
(18, 11, 3, 2),
(19, 11, 4, 1),
(32, 14, 19, 1),
(39, 18, 3, 1),
(63, 20, 3, 1),
(78, 52, 24, 2),
(79, 51, 21, 1),
(80, 19, 20, 1),
(81, 15, 24, 1),
(82, 54, 24, 1),
(83, 55, 4, 1),
(84, 55, 24, 1),
(85, 56, 24, 1);

-- --------------------------------------------------------

--
-- Table structure for table `detail_pengembalian`
--

CREATE TABLE `detail_pengembalian` (
  `id_detail_kembali` int(11) NOT NULL,
  `id_kembali` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `tgl_kembali` date NOT NULL,
  `foto` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `detail_pengembalian`
--

INSERT INTO `detail_pengembalian` (`id_detail_kembali`, `id_kembali`, `id_barang`, `tgl_kembali`, `foto`) VALUES
(4, 4, 3, '2026-01-30', 'uploads/pengembalian/kembali_4_3_20260129_003045_3bb8aeab.jpg'),
(5, 4, 4, '2026-01-30', 'uploads/pengembalian/kembali_4_4_20260129_003045_4db6e123.jpg'),
(6, 5, 3, '2026-01-19', 'uploads/pengembalian/kembali_6_3_20260119_153754_17f875f5.jpg'),
(8, 7, 2, '2026-01-29', 'uploads/pengembalian/kembali_7_2_20260120_113519_24c59a6e.jpg'),
(11, 10, 19, '2026-01-23', 'uploads/pengembalian/kembali_14_19_20260120_124859_f82eb134.jpg'),
(15, 14, 3, '2026-01-23', 'uploads/pengembalian/kembali_18_3_20260120_134902_e46a8df7.jpg'),
(22, 21, 24, '2026-02-05', 'uploads/pengembalian/kembali_52_24_20260129_074447_22675688.jpg'),
(23, 22, 3, '2026-02-05', 'uploads/pengembalian/kembali_5_3_20260129_081712_f19581a3.jpg'),
(24, 23, 3, '2026-02-06', 'uploads/pengembalian/kembali_3_3_20260129_081924_e3f6d823.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `lokasi_gudang`
--

CREATE TABLE `lokasi_gudang` (
  `id_lokasi` int(11) NOT NULL,
  `nama_lokasi` varchar(100) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lokasi_gudang`
--

INSERT INTO `lokasi_gudang` (`id_lokasi`, `nama_lokasi`, `alamat`) VALUES
(1, 'Gudang Utama BPBD', 'BPBD Kabupaten Banyumas'),
(2, 'Gudang Kedua BPBD', 'jalan Damkar');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(64) NOT NULL,
  `payload` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `payload`, `is_read`, `created_at`) VALUES
(1, 1, 'peminjaman:new', '{\"id\":1,\"by\":2}', 0, '2026-01-09 17:59:36'),
(2, 1, 'peminjaman:new', '{\"id\":2,\"by\":2}', 0, '2026-01-09 19:14:52'),
(3, 4, 'peminjaman:approved', '\"Pengajuan peminjaman atas nama Supriyadi sudah disetujui.\"', 1, '2026-01-09 19:15:32'),
(4, 1, 'peminjaman:new', '{\"id\":3,\"by\":6}', 0, '2026-01-09 20:18:21'),
(5, 4, 'peminjaman:approved', '\"Pengajuan peminjaman atas nama Panggah sudah disetujui.\"', 1, '2026-01-09 20:19:36'),
(6, 1, 'peminjaman:new', '{\"id\":4,\"by\":6}', 0, '2026-01-12 07:27:47'),
(7, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Nita Devi sudah disetujui.\",\"id\":4}', 1, '2026-01-12 07:34:15'),
(8, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Nita Devi sudah disetujui.\",\"id\":4}', 1, '2026-01-12 07:34:15'),
(9, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Nita Devi ditolak.\"', 1, '2026-01-12 08:05:36'),
(10, 1, 'peminjaman:new', '{\"id\":5,\"by\":7}', 0, '2026-01-12 11:19:11'),
(11, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama siti Maimunah sudah disetujui.\",\"id\":5}', 1, '2026-01-12 11:21:15'),
(12, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama siti Maimunah sudah disetujui.\",\"id\":5}', 1, '2026-01-12 11:21:15'),
(13, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama siti Maimunah sudah disetujui.\",\"id\":5}', 0, '2026-01-12 14:23:31'),
(14, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama siti Maimunah sudah disetujui.\",\"id\":5}', 1, '2026-01-12 14:23:31'),
(15, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama panggah sudah disetujui.\",\"id\":5}', 0, '2026-01-12 14:50:58'),
(16, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama panggah sudah disetujui.\",\"id\":5}', 1, '2026-01-12 14:50:58'),
(17, 1, 'peminjaman:new', '{\"id\":6,\"by\":8}', 0, '2026-01-14 10:59:53'),
(18, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Grace Sarah sudah disetujui.\",\"id\":6}', 0, '2026-01-14 11:00:54'),
(19, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Grace Sarah sudah disetujui.\",\"id\":6}', 1, '2026-01-14 11:00:54'),
(20, 1, 'peminjaman:new', '{\"id\":7,\"by\":10}', 0, '2026-01-14 18:24:58'),
(21, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Winka Audhyka sudah disetujui.\",\"id\":7}', 0, '2026-01-14 19:56:05'),
(22, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Winka Audhyka sudah disetujui.\",\"id\":7}', 1, '2026-01-14 19:56:05'),
(23, 4, 'delete:approved', '{\"message\":\"Permintaan penghapusan data peminjaman disetujui.\",\"link\":\"index.php?page=pengurus_peminjaman\"}', 0, '2026-01-15 08:42:46'),
(24, 4, 'delete:rejected', '{\"message\":\"Permintaan penghapusan data peminjaman ditolak.\",\"link\":\"index.php?page=pengurus_peminjaman\"}', 0, '2026-01-15 08:42:49'),
(25, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Grace Sarah sudah disetujui.\",\"id\":11}', 1, '2026-01-18 21:36:09'),
(26, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Grace Sarah sudah disetujui.\",\"id\":11}', 1, '2026-01-18 21:36:10'),
(27, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Supriyadi sudah disetujui.\",\"id\":14}', 1, '2026-01-19 22:20:06'),
(28, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Supriyadi sudah disetujui.\",\"id\":14}', 1, '2026-01-19 22:20:06'),
(29, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":11}', 1, '2026-01-19 22:20:13'),
(30, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":11}', 1, '2026-01-19 22:20:13'),
(31, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":6}', 1, '2026-01-19 22:20:17'),
(32, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":6}', 1, '2026-01-19 22:20:17'),
(33, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Sigit Sutrisno sudah disetujui.\",\"id\":15}', 1, '2026-01-20 02:50:31'),
(34, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Sigit Sutrisno sudah disetujui.\",\"id\":15}', 1, '2026-01-20 02:50:31'),
(35, 1, 'peminjaman:new', '{\"id\":16,\"by\":13}', 0, '2026-01-20 15:09:08'),
(36, 5, 'barang:verified', '{\"message\":\"Barang masuk Pompa Air sudah diverifikasi.\",\"id\":22}', 1, '2026-01-20 16:39:35'),
(37, 1, 'peminjaman:new', '{\"id\":18,\"by\":2}', 0, '2026-01-20 17:05:14'),
(38, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":7}', 0, '2026-01-20 17:37:36'),
(39, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":7}', 1, '2026-01-20 17:37:36'),
(40, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Siti Mukaromah sudah disetujui.\",\"id\":18}', 0, '2026-01-20 17:37:52'),
(41, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Siti Mukaromah sudah disetujui.\",\"id\":18}', 1, '2026-01-20 17:37:52'),
(42, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Nafiza Eka Gustiwana sudah disetujui.\",\"id\":19}', 0, '2026-01-20 17:50:15'),
(43, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Nafiza Eka Gustiwana sudah disetujui.\",\"id\":19}', 1, '2026-01-20 17:50:15'),
(44, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Nafiza Eka Gustiwana sudah disetujui.\",\"id\":8}', 0, '2026-01-20 17:54:03'),
(45, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Nafiza Eka Gustiwana sudah disetujui.\",\"id\":8}', 1, '2026-01-20 17:54:03'),
(46, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Faradita Gabriela sudah disetujui.\",\"id\":20}', 0, '2026-01-20 18:07:09'),
(47, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Faradita Gabriela sudah disetujui.\",\"id\":20}', 1, '2026-01-20 18:07:09'),
(48, 4, 'pengembalian:processing', '{\"message\":\"Pengembalian atas nama Faradita Gabriela perlu diperbaiki.\",\"id\":9}', 0, '2026-01-20 18:09:47'),
(49, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Tria Amalia sudah disetujui.\",\"id\":10}', 0, '2026-01-20 19:04:42'),
(50, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Tria Amalia sudah disetujui.\",\"id\":10}', 1, '2026-01-20 19:04:42'),
(51, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Faradita Gabriela ditolak.\",\"id\":11}', 0, '2026-01-20 19:08:41'),
(52, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Sigit Sutrisno ditolak.\",\"id\":12}', 0, '2026-01-20 19:12:36'),
(53, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama apa ditolak.\"', 1, '2026-01-20 19:31:26'),
(54, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Siti Mukaromah sudah disetujui.\",\"id\":14}', 1, '2026-01-20 19:50:00'),
(55, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Siti Mukaromah sudah disetujui.\",\"id\":14}', 1, '2026-01-20 19:50:00'),
(56, 1, 'peminjaman:new', '{\"id\":22,\"by\":9}', 0, '2026-01-20 20:48:25'),
(57, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Tria Amalia ditolak.\"', 1, '2026-01-20 20:57:50'),
(58, 1, 'peminjaman:new', '{\"id\":23,\"by\":4}', 0, '2026-01-20 21:35:04'),
(59, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Raisa Dwi sudah disetujui.\",\"id\":23}', 1, '2026-01-20 21:44:24'),
(60, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Raisa Dwi sudah disetujui.\",\"id\":23}', 1, '2026-01-20 21:44:24'),
(61, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Raisa Dwi ditolak.\",\"id\":16}', 1, '2026-01-20 21:46:48'),
(62, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Faradita Gabriela ditolak.\",\"id\":17}', 1, '2026-01-21 05:04:52'),
(63, 1, 'peminjaman:new', '{\"id\":46,\"by\":4}', 0, '2026-01-27 10:48:35'),
(64, 1, 'peminjaman:new', '{\"id\":47,\"by\":4}', 0, '2026-01-27 10:50:06'),
(65, 5, 'barang:verified', '{\"message\":\"Barang masuk Pompa Air sudah diverifikasi.\",\"id\":22}', 0, '2026-01-28 11:03:22'),
(66, 5, 'barang:verified', '{\"message\":\"Barang masuk Tenda sudah diverifikasi.\",\"id\":24}', 0, '2026-01-28 11:03:25'),
(67, 1, 'peminjaman:new', '{\"id\":48,\"by\":4}', 0, '2026-01-28 18:36:09'),
(68, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Siti Julaeha sudah disetujui.\",\"id\":48}', 0, '2026-01-28 18:37:01'),
(69, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Siti Julaeha sudah disetujui.\",\"id\":48}', 0, '2026-01-28 18:37:01'),
(70, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Raisa Dwi ditolak.\"', 0, '2026-01-28 18:39:30'),
(71, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Raisa Dwi ditolak.\"', 0, '2026-01-28 18:40:45'),
(72, 1, 'peminjaman:new', '{\"id\":49,\"by\":4}', 0, '2026-01-29 04:49:00'),
(73, 1, 'peminjaman:new', '{\"id\":50,\"by\":4}', 0, '2026-01-29 05:00:50'),
(74, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Faradita Gabriela sudah disetujui.\",\"id\":20}', 0, '2026-01-29 05:03:17'),
(75, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Faradita Gabriela sudah disetujui.\",\"id\":20}', 0, '2026-01-29 05:03:17'),
(76, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Siti Julaeha ditolak.\"', 0, '2026-01-29 05:03:20'),
(77, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Siti Julaeha sudah disetujui.\",\"id\":49}', 0, '2026-01-29 05:05:58'),
(78, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Siti Julaeha sudah disetujui.\",\"id\":49}', 0, '2026-01-29 05:05:58'),
(79, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Siti Julaeha ditolak.\"', 0, '2026-01-29 05:09:23'),
(80, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Sigit Sutrisno ditolak.\",\"id\":19}', 0, '2026-01-29 07:23:19'),
(81, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Faradita Gabriela sudah disetujui.\",\"id\":18}', 0, '2026-01-29 07:23:21'),
(82, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Faradita Gabriela sudah disetujui.\",\"id\":18}', 1, '2026-01-29 07:23:21'),
(83, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":4}', 1, '2026-01-29 07:23:23'),
(84, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Panggah sudah disetujui.\",\"id\":4}', 1, '2026-01-29 07:23:23'),
(85, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Sigit Sutrisno ditolak.\",\"id\":19}', 1, '2026-01-29 07:30:01'),
(86, 1, 'peminjaman:new', '{\"id\":51,\"by\":4}', 0, '2026-01-29 13:13:55'),
(87, 1, 'peminjaman:new', '{\"id\":52,\"by\":9}', 0, '2026-01-29 13:24:26'),
(88, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Tria Amalia sudah disetujui.\",\"id\":52}', 0, '2026-01-29 13:38:16'),
(89, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Tria Amalia sudah disetujui.\",\"id\":52}', 0, '2026-01-29 13:38:16'),
(90, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Siti Maimunah sudah disetujui.\",\"id\":22}', 0, '2026-01-29 14:17:42'),
(91, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Siti Maimunah sudah disetujui.\",\"id\":22}', 0, '2026-01-29 14:17:42'),
(92, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama nita devi sudah disetujui.\",\"id\":23}', 0, '2026-01-29 14:19:47'),
(93, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama nita devi sudah disetujui.\",\"id\":23}', 0, '2026-01-29 14:19:47'),
(94, 4, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Grace Sarah sudah disetujui.\",\"id\":4}', 0, '2026-01-29 14:39:11'),
(95, 5, 'pengembalian:completed', '{\"message\":\"Pengembalian atas nama Grace Sarah sudah disetujui.\",\"id\":4}', 0, '2026-01-29 14:39:11'),
(96, 1, 'peminjaman:new', '{\"id\":53,\"by\":4}', 0, '2026-01-29 14:44:49'),
(97, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Fernando sudah disetujui.\",\"id\":51}', 0, '2026-01-29 14:46:17'),
(98, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Fernando sudah disetujui.\",\"id\":51}', 0, '2026-01-29 14:46:17'),
(99, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Fatah ditolak.\"', 0, '2026-01-29 14:46:19'),
(100, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Fernando sudah disetujui.\",\"id\":51}', 1, '2026-01-29 14:48:40'),
(101, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Fernando sudah disetujui.\",\"id\":51}', 1, '2026-01-29 14:48:40'),
(102, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Tria Amalia sudah disetujui.\",\"id\":52}', 1, '2026-01-30 08:30:08'),
(103, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Tria Amalia sudah disetujui.\",\"id\":52}', 1, '2026-01-30 08:30:08'),
(104, 4, 'peminjaman:rejected', '\"Pengajuan peminjaman atas nama Fernando ditolak.\"', 1, '2026-01-30 08:30:14'),
(105, 4, 'peminjaman:rejected', '{\"message\":\"Pengajuan peminjaman atas nama Fernando ditolak.\",\"id\":51}', 0, '2026-01-30 10:54:35'),
(106, 4, 'pengembalian:rejected', '{\"message\":\"Pengembalian atas nama Tria Amalia ditolak.\",\"id\":21}', 0, '2026-01-30 10:55:29'),
(107, 4, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Tria Amalia sudah disetujui.\",\"id\":52}', 0, '2026-04-24 09:01:45'),
(108, 5, 'peminjaman:approved', '{\"message\":\"Pengajuan peminjaman atas nama Tria Amalia sudah disetujui.\",\"id\":52}', 1, '2026-04-24 09:01:45'),
(109, 1, 'peminjaman:new', '{\"message\":\"Pengajuan peminjaman baru atas nama Siti Mukaromah.\",\"id\":54,\"by\":2}', 0, '2026-04-24 09:12:52'),
(110, 1, 'peminjaman:new', '{\"message\":\"Pengajuan peminjaman baru atas nama nadia.\",\"id\":55,\"by\":4}', 0, '2026-04-24 10:54:23'),
(111, 1, 'peminjaman:new', '{\"message\":\"Pengajuan peminjaman baru atas nama Winka Audhyka.\",\"id\":56,\"by\":10}', 0, '2026-09-17 09:41:15'),
(112, 4, 'peminjaman:rejected', '{\"message\":\"Pengajuan peminjaman atas nama Winka Audhyka ditolak.\",\"id\":56}', 0, '2026-09-17 09:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `pakta_integritas`
--

CREATE TABLE `pakta_integritas` (
  `id_pakta` int(11) NOT NULL,
  `id_pinjam` int(11) NOT NULL,
  `tgl_dibuat` date NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `nip_nik` varchar(50) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `barang_digunakan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pakta_integritas`
--

INSERT INTO `pakta_integritas` (`id_pakta`, `id_pinjam`, `tgl_dibuat`, `user_id`, `nama`, `nip_nik`, `jabatan`, `barang_digunakan`) VALUES
(4, 5, '2026-01-12', 7, 'panggah', '37834583754', 'staff logistik', 'Mobil'),
(6, 7, '2026-01-20', 10, 'Panggah', '37834583754', 'Staff logistik', 'Station Wagon'),
(16, 14, '2026-01-19', 4, 'Tria Amalia', '123456', 'Mahasiswa', 'Portable Generating Set '),
(37, 20, '2026-01-31', NULL, 'Faradita Gabriela', '-', 'Mahasiswa', 'Mobil'),
(39, 3, '2026-02-05', NULL, 'nita devi', '4839545784', 'Mahasiswa', 'Mobil'),
(40, 6, '2026-01-27', NULL, 'Panggah', '37834583754', 'Staff logistik', 'Mobil'),
(108, 52, '2026-01-29', 9, 'Tria Amalia', '-', 'Mahasiswa', 'Tenda'),
(109, 18, '2026-04-24', NULL, 'Siti Mukaromah', '-', 'Mahasiswa', 'Mobil');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_pinjam` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_pihak1` varchar(120) DEFAULT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` varchar(80) DEFAULT NULL,
  `nama_pihak2` varchar(120) DEFAULT NULL,
  `nip1` varchar(30) DEFAULT NULL,
  `jabatan1` varchar(80) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `tujuan` varchar(200) DEFAULT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_jatuh_tempo` date DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('pending','waiting_verification','approved','completed','ditolak','rejected') NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_pinjam`, `id_user`, `nama_pihak1`, `nip`, `jabatan`, `nama_pihak2`, `nip1`, `jabatan1`, `alamat`, `no_hp`, `tujuan`, `tgl_pinjam`, `tgl_jatuh_tempo`, `spesifikasi`, `keterangan`, `status`, `requested_by`, `verified_by`, `verified_at`) VALUES
(3, 6, 'Panggah', '5478754389', 'logistik', 'nita devi', '4839545784', 'Warga setempat', 'sumpiuh', '086584657823', 'Evakuasi bencana', '2026-01-09', '2026-01-10', 'jangan rusak', 'lengkap', 'completed', 6, 3, '2026-01-09 20:19:36'),
(5, 7, 'Panggah', 'y7438758347', 'Mahasiswa', 'Siti Maimunah', '37834583754', 'staff logistik', 'bogor', '743758943275837', 'kemah', '2026-01-12', '2026-01-19', 'lengkap', 'lengkap', 'completed', 7, 3, '2026-01-12 14:23:31'),
(6, 8, 'Panggah', '7779202664', 'Mahasiswa', 'Grace Sarah', '37834583754', 'Staff logistik', 'Karang Klesem', '08647453223', 'Kemah Mahasiswa', '2026-01-16', '2026-01-21', 'Lengkap', 'Lengkap', 'completed', 8, 3, '2026-01-14 11:00:54'),
(7, 10, 'Winka Audhyka', '7457475755', 'Mahasiswa', 'Panggah', '37834583754', 'Staff logistik', 'Pejogol RT 06 RW 01', '086346832743', 'Kemah Mahasiswa', '2026-01-16', '2026-01-23', 'Lengkap', 'Lengkap', 'completed', 10, 3, '2026-01-14 19:56:05'),
(11, 8, 'Panggah', '537291046', 'Mahasiswa', 'Grace Sarah', '25361946', 'Staff Logistik', 'Perumahan Puri Indah Karangklesem', '0863451857', 'Kemah KMKP', '2026-01-18', '2026-01-26', 'lengkap', 'Lengkap', 'completed', 8, 3, '2026-01-18 21:36:09'),
(14, 4, 'Supriyadi', '202301014', 'Pengurus Barang', 'Tria Amalia', '123456', 'Mahasiswa', 'Pejogol', '01234567890', 'Camp', '2026-01-12', '2026-01-20', 'Lengkap', 'Lengkap', 'completed', 4, 3, '2026-01-19 22:20:06'),
(15, 12, 'Supriyadi', '10263517184', 'Pengurus Barang', 'Sigit Sutrisno', '-', 'Kepala Desa', 'Cilongok', '085252564391', 'Evakuasi bencana Puting Beliung', '2026-01-22', '2026-01-26', 'Baik', 'Lengkap', 'waiting_verification', 12, NULL, NULL),
(18, 2, 'Supriyadi', '7457475755', 'Staff logistik', 'Siti Mukaromah', '-', 'Mahasiswa', 'KarangKemiri', '08548758423', 'Camp', '2026-01-22', '2026-01-29', 'Lengkap', 'Lengkap', 'completed', 2, 3, '2026-01-20 17:37:52'),
(19, 14, 'Panggah', '73827151893', 'Staff Logistik', 'Nafiza Eka Gustiwana', '-', 'Sekertariat desa', 'Panusupan', '08635816829', 'Cadangan untuk evakuasi bencana', '2026-01-24', '2026-01-29', 'lengkap', 'lengkap', 'waiting_verification', 14, NULL, NULL),
(20, 15, 'Panggah', 'y7438758347', 'Staff logistik', 'Faradita Gabriela', '-', 'Mahasiswa', 'Grendeng', '082154679564', 'Kemah KMK', '2026-01-24', '2026-01-28', 'lengkap', 'untuk kepentingan kemah', 'completed', 15, 3, '2026-01-29 05:03:17'),
(51, 4, 'Panggah', '16352819', 'Staff Logistik', 'Fernando', '-', 'Warga', 'Karanggude', '08972516173', 'Evakuasi bencana alam longsor', '2026-01-31', '2026-02-07', 'Lengkap', 'Lengkap', 'waiting_verification', 4, NULL, NULL),
(52, 9, 'Supriyadi', 'y7438758347', 'Sekretariat', 'Tria Amalia', '-', 'Pegawai BPBD Purbalingga', 'Kutasari', '08647453223', 'Kekurangan tenda karena evakuasi warga bencana', '2026-01-31', '2026-02-07', 'lengkap', 'lengkap', 'approved', 9, 3, '2026-04-24 09:01:45'),
(54, 2, 'Supriyadi', '7457475755', 'Sekretariat', 'Siti Mukaromah', '37834583754', 'Warga', 'Karangklesem', '08548758423', 'Evakuasi bencana', '2026-04-24', '2026-04-24', 'lengkap', 'lengkap', 'waiting_verification', 2, NULL, NULL),
(55, 4, 'Supriyadi', '7457475755', 'Sekretariat', 'nadia', '9876', 'Mahasiswa', 'karangklesem', '08972516173', 'bencana longsor', '2026-04-24', '2026-04-30', 'legkap', 'lengkap', 'waiting_verification', 4, NULL, NULL),
(56, 10, 'Panggah', '73827151893', 'Pengurus Barang', 'Winka Audhyka', '-', 'Mahasiswa', 'oo', '743758943275837', 'Camp', '2026-09-17', '2026-10-09', 'lengkap', 'aman', 'ditolak', 10, 3, '2026-09-17 09:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_kembali` int(11) NOT NULL,
  `id_pinjam` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `kondisi_kembali` varchar(80) DEFAULT NULL,
  `status` enum('pending','processing','completed','ditolak','rejected') NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id_kembali`, `id_pinjam`, `id_user`, `kondisi_kembali`, `status`, `requested_by`, `verified_by`, `verified_at`) VALUES
(4, 11, 8, 'Baik', 'completed', 8, 3, '2026-01-29 14:39:11'),
(5, 6, 4, 'Baik', 'completed', 4, 3, '2026-01-19 22:20:17'),
(7, 7, 10, 'Baik', 'completed', 4, 3, '2026-01-20 17:37:36'),
(10, 14, 4, 'Baik', 'completed', 4, 3, '2026-01-20 19:04:42'),
(14, 18, 4, 'baik', 'completed', 4, 3, '2026-01-20 19:50:00'),
(21, 52, 9, 'baik', 'ditolak', 9, 3, '2026-01-30 10:55:29'),
(22, 5, 7, 'baik', 'completed', 4, 3, '2026-01-29 14:17:42'),
(23, 3, 6, 'baik', 'completed', 4, 3, '2026-01-29 14:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL,
  `no_kontak` varchar(25) DEFAULT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `username`, `password`, `role`, `no_kontak`, `status`) VALUES
(1, 'Admin BPBD', 'admin', '$2y$10$OzUOwUVT81IsBxDuyK1TW.5rOYD9V6ys08Et2Ye4Z/aGGrpB3iDC2', 'admin', '081234567890', 'active'),
(2, 'Siti Mukaromah', 'muka_romah', '$2y$10$W8aCGZV.Q2LL4P1c7A5sne4cuincp2QEdmBeJZxKXEVkC9DM2qJXO', 'pemohon', NULL, 'active'),
(3, 'Kepala Pelaksana BPBD', 'kalaksa', '$2y$10$GFoJOdOCZzDkjBpEPVG26eDq96mva7i1RUdEyaGDIt5bRBwyldTGC', 'kalaksa', '083xxxxxxxxx', 'active'),
(4, 'Pengurus Barang BPBD', 'pengurus_barang', '$2y$10$4hEsq1Eu3Yvak7gpyQwfpeQNdmD3xsFxFygnrRi.nTx486Uiv6E2G', 'pengurus_barang', '08xxxxxxxxxx', 'active'),
(5, 'Petugas Gudang BPBD', 'petugas_gudang', '$2y$10$qVv3cR9Tbr06cSl6KI0LbOMPNP2dcvGf1Vy3Hie7sLsJigpuC06ay', 'petugas_gudang', '08xxxxxxxxxx', 'active'),
(6, 'Nita Devi', 'viel_chan', '$2y$10$Kn6Z9F1Ni87TCT.f9PjWIuOcAn0GK974bJceW7ZHr2O2nmX1oT73S', 'pemohon', NULL, 'active'),
(7, 'siti Maimunah', 'simai', '$2y$10$rWL5RcvFEaUeIM21grEOl.SCQHgurqpwBNz0MYm/A5om54Hjz/YhS', 'pemohon', NULL, 'active'),
(8, 'Grace Sarah', 'grace_sarah', '$2y$10$dGz8iupBjznpst3S1DsmZe6exKud3tasfXdJCaz5xOIYBDt6m8k7O', 'pemohon', NULL, 'active'),
(9, 'Tria Amalia', 'tria_amalia', '$2y$10$4kJkps4t1r/83A7svpY2nOd1RD9A0BnAlmko5wlTHzI9WjBKsuQuS', 'pemohon', 'null', 'active'),
(10, 'Winka Audhyka', 'winka_audhyka', '$2y$10$eEI2HXtNyW71swY3.jIJFeECms/g8XxkaLwNSiBPdLWkhjSJONP4S', 'pemohon', NULL, 'active'),
(11, 'Vanessa Angel', 'van_angel', '$2y$10$CQRHVXNDbzUxx/7MAeZAUODshrdBRCjZXJYUkUgN83gxPGPCJnh9O', 'pemohon', NULL, 'rejected'),
(12, 'Sigit Sutrisno', 'trisno', '$2y$10$.Qqkjt/kDQrNOOQR5R6FkeAOI2UxA9DJ1lVyOc3PINOZ.51FZ7kGO', 'pemohon', NULL, 'active'),
(13, 'Faransa Aprilia', 'faransa_aprilia', '$2y$10$tjZbK.TOmKYggC/CW7FfbuedCzmeJQwTOMam1EtNt55UvRBII3pb2', 'pemohon', NULL, 'active'),
(14, 'Nafiza Eka Gustiwana', 'nafiza_', '$2y$10$Lj94zr2joe1lDVLE/Rs9veLPLtETCI3uY5kCsFbGLNY7pcz7Y.kOe', 'pemohon', '08265173911', 'active'),
(15, 'Faradita Gabriela', 'faradita', '$2y$10$IfFWFvZK2jm78uSKN53K7On.RA3ew.hqd7gEAWai9VzlckAsOhAtO', 'pemohon', '0847452859', 'active'),
(21, 'Dwi Safitri', 'dwi_safitri', '$2y$10$7GH5ssMsOf3m.4iHiOdOrutV3BLbDeciJUOZCTBXRubQBcgdXF1nm', 'pemohon', NULL, 'pending'),
(22, 'Vincenzio Leonard W', 'Zio ganteng', '$2y$10$MmTmlRT/6CBVJ.YHTWDmIu0rNc4PIMVlPOlVhoJhv8ZRUU6FzWgs6', 'pemohon', NULL, 'active'),
(23, 'jannis', 'janniswwws', '$2y$10$tmd03KB6cXaEl6ie4qox0.8pEswJZMoKplktmdL4hG2Qav3f20x.i', 'pemohon', NULL, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`),
  ADD KEY `fk_barang_lokasi` (`id_lokasi`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id_detail_pinjam`),
  ADD KEY `fk_detailpinjam_pinjam` (`id_pinjam`),
  ADD KEY `fk_detailpinjam_barang` (`id_barang`);

--
-- Indexes for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  ADD PRIMARY KEY (`id_detail_kembali`),
  ADD KEY `fk_detailkembali_kembali` (`id_kembali`),
  ADD KEY `fk_detailkembali_barang` (`id_barang`);

--
-- Indexes for table `lokasi_gudang`
--
ALTER TABLE `lokasi_gudang`
  ADD PRIMARY KEY (`id_lokasi`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pakta_integritas`
--
ALTER TABLE `pakta_integritas`
  ADD PRIMARY KEY (`id_pakta`),
  ADD UNIQUE KEY `id_pinjam` (`id_pinjam`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_pinjam`),
  ADD KEY `fk_pinjam_user` (`id_user`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_kembali`),
  ADD UNIQUE KEY `id_pinjam` (`id_pinjam`),
  ADD KEY `fk_kembali_user` (`id_user`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id_detail_pinjam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  MODIFY `id_detail_kembali` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `lokasi_gudang`
--
ALTER TABLE `lokasi_gudang`
  MODIFY `id_lokasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `pakta_integritas`
--
ALTER TABLE `pakta_integritas`
  MODIFY `id_pakta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_pinjam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_kembali` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_lokasi` FOREIGN KEY (`id_lokasi`) REFERENCES `lokasi_gudang` (`id_lokasi`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `fk_detailpinjam_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detailpinjam_pinjam` FOREIGN KEY (`id_pinjam`) REFERENCES `peminjaman` (`id_pinjam`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  ADD CONSTRAINT `fk_detailkembali_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detailkembali_kembali` FOREIGN KEY (`id_kembali`) REFERENCES `pengembalian` (`id_kembali`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pakta_integritas`
--
ALTER TABLE `pakta_integritas`
  ADD CONSTRAINT `fk_pakta_pinjam` FOREIGN KEY (`id_pinjam`) REFERENCES `peminjaman` (`id_pinjam`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_pinjam_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `fk_kembali_pinjam` FOREIGN KEY (`id_pinjam`) REFERENCES `peminjaman` (`id_pinjam`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kembali_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
