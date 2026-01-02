-- Migration: Add rate_bunga column to tb_bunga_deposito_log
-- This stores the interest rate at the time of calculation for historical accuracy

ALTER TABLE `tb_bunga_deposito_log` 
ADD COLUMN `rate_bunga` DECIMAL(10,2) DEFAULT NULL AFTER `jumlah_bunga`;

-- Update existing records with rate from deposito table (one-time migration)
UPDATE `tb_bunga_deposito_log` log
INNER JOIN `tbdeposito` d ON log.deposito_id = d.id
SET log.rate_bunga = d.rate_bunga
WHERE log.rate_bunga IS NULL;
