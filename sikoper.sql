-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 31, 2025 at 03:41 AM
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
-- Database: `sikoper3`
--

-- --------------------------------------------------------

--
-- Table structure for table `systems_log`
--

CREATE TABLE `systems_log` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbdeposito`
--

CREATE TABLE `tbdeposito` (
  `id` int(11) NOT NULL,
  `no_rekening` varchar(20) NOT NULL,
  `no_seri` int(11) DEFAULT NULL,
  `nasabah_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `jenistabungan_id` int(11) NOT NULL,
  `nama_nasabah` varchar(100) DEFAULT NULL,
  `telp_nasabah` varchar(20) DEFAULT NULL,
  `nama_pegawai` varchar(100) DEFAULT NULL,
  `jenis_tabungan` varchar(50) DEFAULT NULL,
  `jumlah_deposito` decimal(15,2) NOT NULL,
  `rate_bunga` decimal(15,2) NOT NULL,
  `total_bunga` decimal(15,2) DEFAULT 0.00,
  `total_bunga_akumulasi` decimal(15,2) DEFAULT 0.00,
  `bunga_belum_ditarik` decimal(15,2) DEFAULT 0.00,
  `tanggal_deposito` date NOT NULL,
  `durasi` tinyint(4) DEFAULT NULL,
  `tanggal_jatuh_tempo` date DEFAULT NULL,
  `nama_ahli_waris` varchar(100) DEFAULT '-',
  `telp_ahli_waris` varchar(20) DEFAULT '-',
  `hubungan_ahli_waris` varchar(20) DEFAULT '-',
  `status` enum('aktif','jatuh tempo','nonaktif','ditutup','') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `import_batch_id` varchar(50) DEFAULT NULL COMMENT 'Batch ID for tracking import sessions',
  `import_notes` text DEFAULT NULL COMMENT 'Notes/KET from Excel import'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbdeposito_bunga_log`
--

CREATE TABLE `tbdeposito_bunga_log` (
  `id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `tanggal_bunga` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbdetail_penarikan`
--

CREATE TABLE `tbdetail_penarikan` (
  `id` int(11) NOT NULL,
  `penarikan_id` int(11) NOT NULL,
  `simpanan_id` int(11) NOT NULL,
  `tanggal_penarikan` datetime NOT NULL,
  `jumlah_penarikan` decimal(15,2) NOT NULL,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `pegawai_id` int(11) NOT NULL,
  `nama_pegawai` varchar(100) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak','batal','') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbdetail_simpanan`
--

CREATE TABLE `tbdetail_simpanan` (
  `id` int(11) NOT NULL,
  `simpanan_id` int(11) NOT NULL,
  `tanggal_setoran` datetime NOT NULL DEFAULT current_timestamp(),
  `jumlah_setoran` decimal(15,2) NOT NULL,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `pegawai_id` int(11) NOT NULL,
  `nama_pegawai` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbjenistabungan`
--

CREATE TABLE `tbjenistabungan` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `bunga` decimal(10,2) NOT NULL,
  `biaya_registrasi` decimal(10,2) NOT NULL,
  `simpanan_awal` decimal(10,2) NOT NULL,
  `pengendapan` decimal(10,2) NOT NULL,
  `tanggal_bunga` int(11) NOT NULL,
  `jenis_denda` enum('Rp','%','-') DEFAULT '-',
  `jumlah_denda` decimal(10,2) DEFAULT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbnasabah`
--

CREATE TABLE `tbnasabah` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan','?','') NOT NULL,
  `tempat_lahir` varchar(10) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `agama` varchar(15) NOT NULL,
  `alamat` varchar(30) NOT NULL,
  `pekerjaan` varchar(50) NOT NULL,
  `telp` varchar(20) NOT NULL,
  `nama_ibu_kandung` varchar(100) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbpegawai`
--

CREATE TABLE `tbpegawai` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan','?') NOT NULL,
  `tempat_lahir` varchar(20) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `agama` varchar(20) NOT NULL,
  `alamat` varchar(50) NOT NULL,
  `telp` varchar(20) NOT NULL,
  `jabatan` varchar(40) NOT NULL,
  `user_token` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbpenarikan`
--

CREATE TABLE `tbpenarikan` (
  `id` int(11) NOT NULL,
  `simpanan_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `nama_pegawai` varchar(100) DEFAULT NULL,
  `tanggal_penarikan` datetime NOT NULL,
  `jumlah_denda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_penarikan` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbpenarikan_deposito`
--

CREATE TABLE `tbpenarikan_deposito` (
  `id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal_penarikan` datetime NOT NULL,
  `jumlah_penarikan` decimal(15,2) NOT NULL,
  `jumlah_penarikan_pokok` decimal(15,2) NOT NULL DEFAULT 0.00,
  `jumlah_penarikan_bunga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `jumlah_denda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_penarikan` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbsimpanan`
--

CREATE TABLE `tbsimpanan` (
  `id` int(11) NOT NULL,
  `no_rekening` varchar(20) NOT NULL,
  `nasabah_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `jenistabungan_id` int(11) NOT NULL,
  `nama_nasabah` varchar(100) DEFAULT NULL,
  `nama_pegawai` varchar(100) DEFAULT NULL,
  `jenis_tabungan` varchar(50) DEFAULT NULL,
  `bunga_rate` decimal(10,2) DEFAULT 0.00,
  `jumlah_simpanan` decimal(15,2) NOT NULL,
  `jumlah_bunga` decimal(15,2) NOT NULL,
  `total_setoran` decimal(15,2) DEFAULT 0.00,
  `total_penarikan` decimal(15,2) DEFAULT 0.00,
  `total_bunga_akumulasi` decimal(15,2) DEFAULT 0.00,
  `tanggal_simpanan` date NOT NULL,
  `status` enum('aktif','ditutup','diblokir') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbtransaksi`
--

CREATE TABLE `tbtransaksi` (
  `id` int(11) NOT NULL,
  `simpanan_id` int(11) NOT NULL,
  `no_rekening` varchar(20) DEFAULT NULL,
  `nama_nasabah` varchar(100) DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `jumlah_transaksi` decimal(10,2) NOT NULL,
  `rate_bunga` decimal(10,2) NOT NULL,
  `penarikan_id` int(11) DEFAULT NULL,
  `bunga_riil` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbuser`
--

CREATE TABLE `tbuser` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `level` enum('Admin','Direktur','Pegawai') NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_bunga_deposito_log`
--

CREATE TABLE `tb_bunga_deposito_log` (
  `id` int(11) NOT NULL,
  `deposito_id` int(11) NOT NULL,
  `no_rekening` varchar(20) DEFAULT NULL,
  `nama_nasabah` varchar(100) DEFAULT NULL,
  `jumlah_bunga` decimal(15,2) NOT NULL,
  `rate_bunga` decimal(10,2) DEFAULT NULL,
  `tanggal_perhitungan` date NOT NULL,
  `status_penarikan` enum('belum_ditarik','sudah_ditarik') NOT NULL DEFAULT 'belum_ditarik',
  `input_method` enum('auto','manual','import') NOT NULL DEFAULT 'auto',
  `pegawai_id` int(11) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `import_batch_id` varchar(50) DEFAULT NULL COMMENT 'Batch ID for tracking import sessions',
  `penarikan_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `penarikan_deposito_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `systems_log`
--
ALTER TABLE `systems_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbdeposito`
--
ALTER TABLE `tbdeposito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`) USING BTREE,
  ADD KEY `jenistabungan_id` (`jenistabungan_id`),
  ADD KEY `idx_deposito_nasabah_status` (`nasabah_id`,`status`),
  ADD KEY `idx_deposito_tanggal_status` (`tanggal_deposito`,`status`),
  ADD KEY `idx_deposito_jatuh_tempo` (`status`,`tanggal_jatuh_tempo`),
  ADD KEY `idx_deposito_nama_nasabah` (`nama_nasabah`),
  ADD KEY `idx_deposito_no_seri` (`no_seri`),
  ADD KEY `idx_deposito_import_batch` (`import_batch_id`);

--
-- Indexes for table `tbdeposito_bunga_log`
--
ALTER TABLE `tbdeposito_bunga_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbdetail_penarikan`
--
ALTER TABLE `tbdetail_penarikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`simpanan_id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `idx_penarikan_id` (`penarikan_id`),
  ADD KEY `idx_detail_penarikan_tanggal` (`simpanan_id`,`tanggal_penarikan`),
  ADD KEY `idx_detail_penarikan_status` (`status`);

--
-- Indexes for table `tbdetail_simpanan`
--
ALTER TABLE `tbdetail_simpanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`simpanan_id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `idx_detail_simpanan_tanggal` (`simpanan_id`,`tanggal_setoran`);

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
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `idx_nasabah_nama` (`nama_lengkap`);

--
-- Indexes for table `tbpegawai`
--
ALTER TABLE `tbpegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- Indexes for table `tbpenarikan`
--
ALTER TABLE `tbpenarikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`simpanan_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `tbpenarikan_deposito`
--
ALTER TABLE `tbpenarikan_deposito`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbsimpanan`
--
ALTER TABLE `tbsimpanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasabah_id` (`nasabah_id`),
  ADD KEY `pegawai_id` (`pegawai_id`) USING BTREE,
  ADD KEY `jenistabungan_id` (`jenistabungan_id`),
  ADD KEY `idx_simpanan_nasabah_status` (`nasabah_id`,`status`),
  ADD KEY `idx_simpanan_created_at` (`created_at`),
  ADD KEY `idx_simpanan_norek` (`no_rekening`),
  ADD KEY `idx_simpanan_nama_nasabah` (`nama_nasabah`);

--
-- Indexes for table `tbtransaksi`
--
ALTER TABLE `tbtransaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `simpanan_id` (`simpanan_id`) USING BTREE,
  ADD KEY `idx_penarikan_id` (`penarikan_id`),
  ADD KEY `idx_transaksi_simpanan_tanggal` (`simpanan_id`,`tanggal_transaksi`);

--
-- Indexes for table `tbuser`
--
ALTER TABLE `tbuser`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `tb_bunga_deposito_log`
--
ALTER TABLE `tb_bunga_deposito_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deposito_id` (`deposito_id`),
  ADD KEY `idx_bunga_log_deposito_status` (`deposito_id`,`status_penarikan`),
  ADD KEY `idx_bunga_log_tanggal` (`tanggal_perhitungan`,`deposito_id`),
  ADD KEY `fk_bunga_log_pegawai` (`pegawai_id`),
  ADD KEY `idx_bunga_log_input_method` (`input_method`),
  ADD KEY `idx_bunga_log_import_batch` (`import_batch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `systems_log`
--
ALTER TABLE `systems_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbdeposito`
--
ALTER TABLE `tbdeposito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbdeposito_bunga_log`
--
ALTER TABLE `tbdeposito_bunga_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `tbpenarikan_deposito`
--
ALTER TABLE `tbpenarikan_deposito`
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
-- AUTO_INCREMENT for table `tb_bunga_deposito_log`
--
ALTER TABLE `tb_bunga_deposito_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbdeposito`
--
ALTER TABLE `tbdeposito`
  ADD CONSTRAINT `tbdeposito_ibfk_1` FOREIGN KEY (`jenistabungan_id`) REFERENCES `tbjenistabungan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdeposito_ibfk_2` FOREIGN KEY (`nasabah_id`) REFERENCES `tbnasabah` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdeposito_ibfk_3` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbdetail_penarikan`
--
ALTER TABLE `tbdetail_penarikan`
  ADD CONSTRAINT `fk_penarikan_detail_header` FOREIGN KEY (`penarikan_id`) REFERENCES `tbpenarikan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbdetail_penarikan_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdetail_penarikan_ibfk_2` FOREIGN KEY (`simpanan_id`) REFERENCES `tbsimpanan` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbdetail_simpanan`
--
ALTER TABLE `tbdetail_simpanan`
  ADD CONSTRAINT `tbdetail_simpanan_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdetail_simpanan_ibfk_2` FOREIGN KEY (`simpanan_id`) REFERENCES `tbsimpanan` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbnasabah`
--
ALTER TABLE `tbnasabah`
  ADD CONSTRAINT `tbnasabah_ibfk_2` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbpenarikan`
--
ALTER TABLE `tbpenarikan`
  ADD CONSTRAINT `tbpenarikan_ibfk_2` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbpenarikan_ibfk_3` FOREIGN KEY (`simpanan_id`) REFERENCES `tbsimpanan` (`id`) ON UPDATE CASCADE;

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
  ADD CONSTRAINT `tbtransaksi_ibfk_4` FOREIGN KEY (`simpanan_id`) REFERENCES `tbsimpanan` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbuser`
--
ALTER TABLE `tbuser`
  ADD CONSTRAINT `tbuser_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tb_bunga_deposito_log`
--
ALTER TABLE `tb_bunga_deposito_log`
  ADD CONSTRAINT `fk_bunga_log_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_log_bunga_ke_deposito` FOREIGN KEY (`deposito_id`) REFERENCES `tbdeposito` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
