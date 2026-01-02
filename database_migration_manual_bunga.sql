-- Migration: Add manual entry support to tb_bunga_deposito_log
-- Run this migration to enable manual bunga entry and data source tracking

-- Add new columns for tracking data source and audit
ALTER TABLE `tb_bunga_deposito_log` 
ADD COLUMN `input_method` ENUM('auto', 'manual', 'import') NOT NULL DEFAULT 'auto' AFTER `status_penarikan`,
ADD COLUMN `pegawai_id` INT(11) NULL AFTER `input_method`,
ADD COLUMN `keterangan` VARCHAR(255) NULL AFTER `pegawai_id`;

-- Add foreign key for pegawai_id (optional, for data integrity)
ALTER TABLE `tb_bunga_deposito_log`
ADD CONSTRAINT `fk_bunga_log_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `tbpegawai` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- Add index for faster filtering by input_method
ALTER TABLE `tb_bunga_deposito_log`
ADD INDEX `idx_bunga_log_input_method` (`input_method`);
