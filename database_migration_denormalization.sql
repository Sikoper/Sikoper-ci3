-- ============================================================================
-- SIKOPER DATABASE DENORMALIZATION MIGRATION
-- Version: 1.0
-- Date: 2025-12-30
-- Database: sikoper2 (MariaDB/MySQL via XAMPP)
-- ============================================================================
-- 
-- INSTRUCTIONS:
-- 1. BACKUP YOUR DATABASE FIRST: mysqldump -u root sikoper2 > sikoper2_backup.sql
-- 2. Run this script in phpMyAdmin or MySQL CLI
-- 3. After migration, run the data population script
-- 4. Test the application thoroughly
--
-- ============================================================================

-- Disable foreign key checks during migration
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- ============================================================================
-- PART 1: ALTER TABLE - Add Denormalized Columns
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1.1 Modify tbsimpanan (Savings Account)
-- ----------------------------------------------------------------------------
ALTER TABLE `tbsimpanan`
    ADD COLUMN IF NOT EXISTS `nama_nasabah` VARCHAR(100) DEFAULT NULL AFTER `jenistabungan_id`,
    ADD COLUMN IF NOT EXISTS `nama_pegawai` VARCHAR(100) DEFAULT NULL AFTER `nama_nasabah`,
    ADD COLUMN IF NOT EXISTS `jenis_tabungan` VARCHAR(50) DEFAULT NULL AFTER `nama_pegawai`,
    ADD COLUMN IF NOT EXISTS `bunga_rate` DECIMAL(10,2) DEFAULT 0.00 AFTER `jenis_tabungan`,
    ADD COLUMN IF NOT EXISTS `total_setoran` DECIMAL(15,2) DEFAULT 0.00 AFTER `jumlah_bunga`,
    ADD COLUMN IF NOT EXISTS `total_penarikan` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_setoran`,
    ADD COLUMN IF NOT EXISTS `total_bunga_akumulasi` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_penarikan`;

-- ----------------------------------------------------------------------------
-- 1.2 Modify tbdeposito (Time Deposit)
-- ----------------------------------------------------------------------------
ALTER TABLE `tbdeposito`
    ADD COLUMN IF NOT EXISTS `nama_nasabah` VARCHAR(100) DEFAULT NULL AFTER `jenistabungan_id`,
    ADD COLUMN IF NOT EXISTS `telp_nasabah` VARCHAR(20) DEFAULT NULL AFTER `nama_nasabah`,
    ADD COLUMN IF NOT EXISTS `nama_pegawai` VARCHAR(100) DEFAULT NULL AFTER `telp_nasabah`,
    ADD COLUMN IF NOT EXISTS `jenis_tabungan` VARCHAR(50) DEFAULT NULL AFTER `nama_pegawai`,
    ADD COLUMN IF NOT EXISTS `total_bunga_akumulasi` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_bunga`,
    ADD COLUMN IF NOT EXISTS `bunga_belum_ditarik` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_bunga_akumulasi`,
    ADD COLUMN IF NOT EXISTS `tanggal_jatuh_tempo` DATE DEFAULT NULL AFTER `durasi`;

-- ----------------------------------------------------------------------------
-- 1.3 Modify tbdetail_simpanan (Deposit Transaction Details)
-- ----------------------------------------------------------------------------
ALTER TABLE `tbdetail_simpanan`
    ADD COLUMN IF NOT EXISTS `running_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `jumlah_setoran`,
    ADD COLUMN IF NOT EXISTS `nama_pegawai` VARCHAR(100) DEFAULT NULL AFTER `pegawai_id`;

-- ----------------------------------------------------------------------------
-- 1.4 Modify tbdetail_penarikan (Withdrawal Transaction Details)
-- ----------------------------------------------------------------------------
ALTER TABLE `tbdetail_penarikan`
    ADD COLUMN IF NOT EXISTS `running_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `jumlah_penarikan`,
    ADD COLUMN IF NOT EXISTS `nama_pegawai` VARCHAR(100) DEFAULT NULL AFTER `pegawai_id`;

-- ----------------------------------------------------------------------------
-- 1.5 Modify tbpenarikan (Withdrawal Header)
-- ----------------------------------------------------------------------------
ALTER TABLE `tbpenarikan`
    ADD COLUMN IF NOT EXISTS `nama_pegawai` VARCHAR(100) DEFAULT NULL AFTER `pegawai_id`;

-- ----------------------------------------------------------------------------
-- 1.6 Modify tbtransaksi (Interest Transaction Log for Savings)
-- ----------------------------------------------------------------------------
ALTER TABLE `tbtransaksi`
    ADD COLUMN IF NOT EXISTS `no_rekening` VARCHAR(20) DEFAULT NULL AFTER `simpanan_id`,
    ADD COLUMN IF NOT EXISTS `nama_nasabah` VARCHAR(100) DEFAULT NULL AFTER `no_rekening`;

-- ----------------------------------------------------------------------------
-- 1.7 Modify tb_bunga_deposito_log (Interest Log for Deposits)
-- ----------------------------------------------------------------------------
ALTER TABLE `tb_bunga_deposito_log`
    ADD COLUMN IF NOT EXISTS `no_rekening` VARCHAR(20) DEFAULT NULL AFTER `deposito_id`,
    ADD COLUMN IF NOT EXISTS `nama_nasabah` VARCHAR(100) DEFAULT NULL AFTER `no_rekening`;


-- ============================================================================
-- PART 2: CREATE INDEXES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 2.1 Indexes for tbsimpanan
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_simpanan_nasabah_status` ON `tbsimpanan`(`nasabah_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_simpanan_created_at` ON `tbsimpanan`(`created_at` DESC);
CREATE INDEX IF NOT EXISTS `idx_simpanan_norek` ON `tbsimpanan`(`no_rekening`);
CREATE INDEX IF NOT EXISTS `idx_simpanan_nama_nasabah` ON `tbsimpanan`(`nama_nasabah`);

-- ----------------------------------------------------------------------------
-- 2.2 Indexes for tbdeposito
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_deposito_nasabah_status` ON `tbdeposito`(`nasabah_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_deposito_tanggal_status` ON `tbdeposito`(`tanggal_deposito`, `status`);
CREATE INDEX IF NOT EXISTS `idx_deposito_jatuh_tempo` ON `tbdeposito`(`status`, `tanggal_jatuh_tempo`);
CREATE INDEX IF NOT EXISTS `idx_deposito_nama_nasabah` ON `tbdeposito`(`nama_nasabah`);

-- ----------------------------------------------------------------------------
-- 2.3 Indexes for tbdetail_simpanan
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_detail_simpanan_tanggal` ON `tbdetail_simpanan`(`simpanan_id`, `tanggal_setoran`);

-- ----------------------------------------------------------------------------
-- 2.4 Indexes for tbdetail_penarikan
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_detail_penarikan_tanggal` ON `tbdetail_penarikan`(`simpanan_id`, `tanggal_penarikan`);
CREATE INDEX IF NOT EXISTS `idx_detail_penarikan_status` ON `tbdetail_penarikan`(`status`);

-- ----------------------------------------------------------------------------
-- 2.5 Indexes for tbtransaksi
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_transaksi_simpanan_tanggal` ON `tbtransaksi`(`simpanan_id`, `tanggal_transaksi`);

-- ----------------------------------------------------------------------------
-- 2.6 Indexes for tb_bunga_deposito_log
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_bunga_log_deposito_status` ON `tb_bunga_deposito_log`(`deposito_id`, `status_penarikan`);
CREATE INDEX IF NOT EXISTS `idx_bunga_log_tanggal` ON `tb_bunga_deposito_log`(`tanggal_perhitungan`, `deposito_id`);

-- ----------------------------------------------------------------------------
-- 2.7 Indexes for tbnasabah
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_nasabah_nama` ON `tbnasabah`(`nama_lengkap`);


-- ============================================================================
-- NOTE: Triggers are NOT used in this migration.
-- Denormalized data is maintained by PHP application code in the models.
-- This approach gives more control and visibility over data synchronization.
-- ============================================================================

-- ============================================================================
-- PART 5: POPULATE DENORMALIZED DATA FROM EXISTING RECORDS
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 5.1 Update tbsimpanan with denormalized nasabah/pegawai/jenis names
-- ----------------------------------------------------------------------------
UPDATE tbsimpanan s
JOIN tbnasabah n ON n.id = s.nasabah_id
SET s.nama_nasabah = n.nama_lengkap
WHERE s.nama_nasabah IS NULL OR s.nama_nasabah = '';

UPDATE tbsimpanan s
JOIN tbpegawai p ON p.id = s.pegawai_id
SET s.nama_pegawai = p.nama_lengkap
WHERE s.nama_pegawai IS NULL OR s.nama_pegawai = '';

UPDATE tbsimpanan s
JOIN tbjenistabungan j ON j.id = s.jenistabungan_id
SET s.jenis_tabungan = j.nama, s.bunga_rate = j.bunga
WHERE s.jenis_tabungan IS NULL OR s.jenis_tabungan = '';

-- ----------------------------------------------------------------------------
-- 5.2 Update tbsimpanan with calculated totals
-- ----------------------------------------------------------------------------
UPDATE tbsimpanan s
SET s.total_setoran = (
    SELECT COALESCE(SUM(ds.jumlah_setoran), 0)
    FROM tbdetail_simpanan ds
    WHERE ds.simpanan_id = s.id
);

UPDATE tbsimpanan s
SET s.total_penarikan = (
    SELECT COALESCE(SUM(dp.jumlah_penarikan), 0)
    FROM tbdetail_penarikan dp
    WHERE dp.simpanan_id = s.id AND dp.status = 'disetujui'
);

UPDATE tbsimpanan s
SET s.total_bunga_akumulasi = (
    SELECT COALESCE(SUM(t.jumlah_transaksi), 0)
    FROM tbtransaksi t
    WHERE t.simpanan_id = s.id
);

-- ----------------------------------------------------------------------------
-- 5.3 Update tbdeposito with denormalized names
-- ----------------------------------------------------------------------------
UPDATE tbdeposito d
JOIN tbnasabah n ON n.id = d.nasabah_id
SET d.nama_nasabah = n.nama_lengkap, d.telp_nasabah = n.telp
WHERE d.nama_nasabah IS NULL OR d.nama_nasabah = '';

UPDATE tbdeposito d
JOIN tbpegawai p ON p.id = d.pegawai_id
SET d.nama_pegawai = p.nama_lengkap
WHERE d.nama_pegawai IS NULL OR d.nama_pegawai = '';

UPDATE tbdeposito d
JOIN tbjenistabungan j ON j.id = d.jenistabungan_id
SET d.jenis_tabungan = j.nama
WHERE d.jenis_tabungan IS NULL OR d.jenis_tabungan = '';

-- ----------------------------------------------------------------------------
-- 5.4 Update tbdeposito with calculated totals and maturity date
-- ----------------------------------------------------------------------------
UPDATE tbdeposito d
SET d.tanggal_jatuh_tempo = DATE_ADD(d.tanggal_deposito, INTERVAL d.durasi MONTH)
WHERE d.tanggal_jatuh_tempo IS NULL;

UPDATE tbdeposito d
SET d.total_bunga_akumulasi = (
    SELECT COALESCE(SUM(bl.jumlah_bunga), 0)
    FROM tb_bunga_deposito_log bl
    WHERE bl.deposito_id = d.id
);

UPDATE tbdeposito d
SET d.bunga_belum_ditarik = (
    SELECT COALESCE(SUM(bl.jumlah_bunga), 0)
    FROM tb_bunga_deposito_log bl
    WHERE bl.deposito_id = d.id AND bl.status_penarikan = 'belum_ditarik'
);

-- ----------------------------------------------------------------------------
-- 5.5 Update tbdetail_simpanan with pegawai names
-- ----------------------------------------------------------------------------
UPDATE tbdetail_simpanan ds
JOIN tbpegawai p ON p.id = ds.pegawai_id
SET ds.nama_pegawai = p.nama_lengkap
WHERE ds.nama_pegawai IS NULL OR ds.nama_pegawai = '';

-- ----------------------------------------------------------------------------
-- 5.6 Update tbdetail_penarikan with pegawai names
-- ----------------------------------------------------------------------------
UPDATE tbdetail_penarikan dp
JOIN tbpegawai p ON p.id = dp.pegawai_id
SET dp.nama_pegawai = p.nama_lengkap
WHERE dp.nama_pegawai IS NULL OR dp.nama_pegawai = '';

-- ----------------------------------------------------------------------------
-- 5.7 Update tbpenarikan with pegawai names
-- ----------------------------------------------------------------------------
UPDATE tbpenarikan tp
JOIN tbpegawai p ON p.id = tp.pegawai_id
SET tp.nama_pegawai = p.nama_lengkap
WHERE tp.nama_pegawai IS NULL OR tp.nama_pegawai = '';

-- ----------------------------------------------------------------------------
-- 5.8 Update tbtransaksi with rekening and nasabah names
-- ----------------------------------------------------------------------------
UPDATE tbtransaksi t
JOIN tbsimpanan s ON s.id = t.simpanan_id
JOIN tbnasabah n ON n.id = s.nasabah_id
SET t.no_rekening = s.no_rekening, t.nama_nasabah = n.nama_lengkap
WHERE t.no_rekening IS NULL OR t.nama_nasabah IS NULL;

-- ----------------------------------------------------------------------------
-- 5.9 Update tb_bunga_deposito_log with rekening and nasabah names
-- ----------------------------------------------------------------------------
UPDATE tb_bunga_deposito_log bl
JOIN tbdeposito d ON d.id = bl.deposito_id
JOIN tbnasabah n ON n.id = d.nasabah_id
SET bl.no_rekening = d.no_rekening, bl.nama_nasabah = n.nama_lengkap
WHERE bl.no_rekening IS NULL OR bl.nama_nasabah IS NULL;


-- ============================================================================
-- FINALIZE
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- VERIFICATION QUERIES - Run these to verify migration success
-- ============================================================================
-- 
-- Check if denormalized names match original:
-- SELECT s.id, s.nama_nasabah, n.nama_lengkap, 
--        IF(s.nama_nasabah = n.nama_lengkap, 'OK', 'MISMATCH') as status
-- FROM tbsimpanan s JOIN tbnasabah n ON n.id = s.nasabah_id
-- WHERE s.nama_nasabah != n.nama_lengkap;
--
-- Check if totals match calculated:
-- SELECT s.id, s.total_setoran, 
--        (SELECT COALESCE(SUM(jumlah_setoran),0) FROM tbdetail_simpanan WHERE simpanan_id = s.id) as calc,
--        IF(s.total_setoran = calc, 'OK', 'MISMATCH') as status
-- FROM tbsimpanan s
-- HAVING status = 'MISMATCH';
--
-- ============================================================================
