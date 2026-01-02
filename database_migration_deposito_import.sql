-- =====================================================
-- Migration: Add Deposito Import Support
-- Date: 2025-12-31
-- Description: Add columns to support Excel import feature
-- =====================================================

-- Add import-related columns to tbdeposito
ALTER TABLE `tbdeposito` 
ADD COLUMN `no_seri` INT(11) DEFAULT NULL COMMENT 'Original serial number from Excel import' AFTER `no_rekening`,
ADD COLUMN `import_batch_id` VARCHAR(50) DEFAULT NULL COMMENT 'Batch ID for tracking import sessions',
ADD COLUMN `import_notes` TEXT DEFAULT NULL COMMENT 'Notes/KET from Excel import',
ADD INDEX `idx_deposito_no_seri` (`no_seri`),
ADD INDEX `idx_deposito_import_batch` (`import_batch_id`);

-- Add import tracking to bunga log table  
ALTER TABLE `tb_bunga_deposito_log`
ADD COLUMN `import_batch_id` VARCHAR(50) DEFAULT NULL COMMENT 'Batch ID for tracking import sessions' AFTER `keterangan`,
ADD INDEX `idx_bunga_log_import_batch` (`import_batch_id`);
