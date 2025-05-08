-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8111
-- Generation Time: May 06, 2025 at 07:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sikoper`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbdetail_penarikan`
--

CREATE TABLE `tbdetail_penarikan` (
  `id` int(11) NOT NULL,
  `nasabah_id` int(11) NOT NULL,
  `tgl_penarikan` datetime NOT NULL,
  `jumlah_penarikan` decimal(10,2) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `status` enum('pending','disetujui','ditolak','batal','') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbdetail_simpanan`
--

CREATE TABLE `tbdetail_simpanan` (
  `id` int(11) NOT NULL,
  `nasabah_id` int(11) NOT NULL,
  `tgl_setoran` datetime NOT NULL,
  `jumlah_setoran` decimal(10,2) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `status` enum('aktif','nonaktif','ditutup','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbjenistabungan`
--

CREATE TABLE `tbjenistabungan` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `bunga` decimal(10,2) NOT NULL,
  `simpanana_awal` decimal(10,2) NOT NULL,
  `pengendapan` decimal(10,2) NOT NULL,
  `keterangan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbnasabah`
--

CREATE TABLE `tbnasabah` (
  `id` int(11) NOT NULL,
  `no_rekening` bigint(20) NOT NULL,
  `nik` bigint(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan','?','') NOT NULL,
  `tempat_lahir` varchar(10) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `agama` varchar(15) NOT NULL,
  `alamat` varchar(30) NOT NULL,
  `desa` varchar(20) NOT NULL,
  `kecamatan` varchar(20) NOT NULL,
  `kabupaten` varchar(20) NOT NULL,
  `pekerjaan` varchar(50) NOT NULL,
  `telp` varchar(20) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `nama_ibu_kandung` varchar(100) NOT NULL,
  `jenistabungan_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbpegawai`
--

CREATE TABLE `tbpegawai` (
  `id` int(11) NOT NULL,
  `foto_ktp` varchar(100) DEFAULT NULL,
  `nik` bigint(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan','?','') NOT NULL,
  `tempat_lahir` varchar(20) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `agama` varchar(20) NOT NULL,
  `alamat` varchar(50) NOT NULL,
  `desa` varchar(30) NOT NULL,
  `kecamatan` varchar(30) NOT NULL,
  `kabupaten` varchar(30) NOT NULL,
  `telp` varchar(20) NOT NULL,
  `jabatan` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbpenarikan`
--

CREATE TABLE `tbpenarikan` (
  `id` int(11) NOT NULL,
  `nasabah_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `jenistabungan_id` int(11) NOT NULL,
  `tgl_penarikan` datetime NOT NULL,
  `total_penarikan` decimal(10,2) NOT NULL,
  `jumlah_penarikan` int(11) NOT NULL,
  `status` enum('aktif','nonaktif','ditutup','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbsimpanan`
--

CREATE TABLE `tbsimpanan` (
  `id` int(11) NOT NULL,
  `nasabah_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `jenistabungan_id` int(11) NOT NULL,
  `tgl_simpanan` date NOT NULL,
  `jumlah_simpanan` decimal(10,2) NOT NULL,
  `status` enum('aktif','nonaktif','ditutup','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbtransaksi`
--

CREATE TABLE `tbtransaksi` (
  `id` int(11) NOT NULL,
  `nasabah_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tgl_transaksi` datetime NOT NULL,
  `jumlah_transaksi` decimal(10,2) NOT NULL,
  `detailsimpan_id` int(11) DEFAULT NULL,
  `detailpenarikan_id` int(11) DEFAULT NULL,
  `keterangan` varchar(40) NOT NULL,
  `status` enum('pending','disetujui','ditolak','batal') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbuser`
--

CREATE TABLE `tbuser` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(100) NOT NULL,
  `level` enum('Admin','Pegawai','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbdetail_penarikan`
--
ALTER TABLE `tbdetail_penarikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `tbdetail_simpanan`
--
ALTER TABLE `tbdetail_simpanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `tbjenistabungan`
--
ALTER TABLE `tbjenistabungan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbnasabah`
--
ALTER TABLE `tbnasabah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jenistabungan_id` (`jenistabungan_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `tbpegawai`
--
ALTER TABLE `tbpegawai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbpenarikan`
--
ALTER TABLE `tbpenarikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `jenistabungan_id` (`jenistabungan_id`);

--
-- Indexes for table `tbsimpanan`
--
ALTER TABLE `tbsimpanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`) USING BTREE,
  ADD KEY `jenistabungan_id` (`jenistabungan_id`);

--
-- Indexes for table `tbtransaksi`
--
ALTER TABLE `tbtransaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detailsimpan_id` (`detailsimpan_id`),
  ADD KEY `detailpenarikan_id` (`detailpenarikan_id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `tbuser`
--
ALTER TABLE `tbuser`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbdetail_penarikan`
--
ALTER TABLE `tbdetail_penarikan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbdetail_simpanan`
--
ALTER TABLE `tbdetail_simpanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbjenistabungan`
--
ALTER TABLE `tbjenistabungan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbnasabah`
--
ALTER TABLE `tbnasabah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbpegawai`
--
ALTER TABLE `tbpegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbpenarikan`
--
ALTER TABLE `tbpenarikan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbsimpanan`
--
ALTER TABLE `tbsimpanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbtransaksi`
--
ALTER TABLE `tbtransaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbuser`
--
ALTER TABLE `tbuser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbdetail_penarikan`
--
ALTER TABLE `tbdetail_penarikan`
  ADD CONSTRAINT `tbdetail_penarikan_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdetail_penarikan_ibfk_2` FOREIGN KEY (`nasabah_id`) REFERENCES `tbnasabah` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbdetail_simpanan`
--
ALTER TABLE `tbdetail_simpanan`
  ADD CONSTRAINT `tbdetail_simpanan_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdetail_simpanan_ibfk_2` FOREIGN KEY (`nasabah_id`) REFERENCES `tbnasabah` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbnasabah`
--
ALTER TABLE `tbnasabah`
  ADD CONSTRAINT `tbnasabah_ibfk_1` FOREIGN KEY (`jenistabungan_id`) REFERENCES `tbjenistabungan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbnasabah_ibfk_2` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbpenarikan`
--
ALTER TABLE `tbpenarikan`
  ADD CONSTRAINT `tbpenarikan_ibfk_1` FOREIGN KEY (`nasabah_id`) REFERENCES `tbnasabah` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbpenarikan_ibfk_2` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbsimpanan`
--
ALTER TABLE `tbsimpanan`
  ADD CONSTRAINT `tbsimpanan_ibfk_1` FOREIGN KEY (`jenistabungan_id`) REFERENCES `tbjenistabungan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbsimpanan_ibfk_2` FOREIGN KEY (`nasabah_id`) REFERENCES `tbnasabah` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbsimpanan_ibfk_3` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbtransaksi`
--
ALTER TABLE `tbtransaksi`
  ADD CONSTRAINT `tbtransaksi_ibfk_1` FOREIGN KEY (`detailpenarikan_id`) REFERENCES `tbdetail_penarikan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbtransaksi_ibfk_2` FOREIGN KEY (`detailsimpan_id`) REFERENCES `tbdetail_simpanan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbtransaksi_ibfk_3` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbtransaksi_ibfk_4` FOREIGN KEY (`nasabah_id`) REFERENCES `tbnasabah` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
