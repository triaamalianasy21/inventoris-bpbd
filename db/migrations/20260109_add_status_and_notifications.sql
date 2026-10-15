-- Migrasi: tambah/ubah kolom status dan kolom verifier secara idempotent
-- Tempatkan file ini di db/migrations dan jalankan via phpMyAdmin atau mysql client

-- 1) Peminjaman: tambah/ubah kolom status, requested_by, verified_by, verified_at
SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME='status');
SET @sql := IF(@cnt=0,
  "ALTER TABLE `peminjaman` ADD COLUMN `status` ENUM('pending','waiting_verification','approved','rejected','completed') NOT NULL DEFAULT 'pending'",
  "ALTER TABLE `peminjaman` MODIFY COLUMN `status` ENUM('pending','waiting_verification','approved','rejected','completed') NOT NULL DEFAULT 'pending'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME='requested_by');
SET @sql := IF(@cnt=0, "ALTER TABLE `peminjaman` ADD COLUMN `requested_by` INT NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME='verified_by');
SET @sql := IF(@cnt=0, "ALTER TABLE `peminjaman` ADD COLUMN `verified_by` INT NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME='verified_at');
SET @sql := IF(@cnt=0, "ALTER TABLE `peminjaman` ADD COLUMN `verified_at` DATETIME NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Pengembalian: tambah/ubah kolom status dan verifier
SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengembalian' AND COLUMN_NAME='status');
SET @sql := IF(@cnt=0,
  "ALTER TABLE `pengembalian` ADD COLUMN `status` ENUM('pending','processing','completed') NOT NULL DEFAULT 'pending'",
  "ALTER TABLE `pengembalian` MODIFY COLUMN `status` ENUM('pending','processing','completed') NOT NULL DEFAULT 'pending'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengembalian' AND COLUMN_NAME='requested_by');
SET @sql := IF(@cnt=0, "ALTER TABLE `pengembalian` ADD COLUMN `requested_by` INT NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengembalian' AND COLUMN_NAME='verified_by');
SET @sql := IF(@cnt=0, "ALTER TABLE `pengembalian` ADD COLUMN `verified_by` INT NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengembalian' AND COLUMN_NAME='verified_at');
SET @sql := IF(@cnt=0, "ALTER TABLE `pengembalian` ADD COLUMN `verified_at` DATETIME NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Pakta integritas: tambahkan kolom status, user_id dan tgl_dibuat jika belum ada
SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pakta_integritas' AND COLUMN_NAME='status');
SET @sql := IF(@cnt=0,
  "ALTER TABLE `pakta_integritas` ADD COLUMN `status` ENUM('not_filled','filled') NOT NULL DEFAULT 'not_filled'",
  "ALTER TABLE `pakta_integritas` MODIFY COLUMN `status` ENUM('not_filled','filled') NOT NULL DEFAULT 'not_filled'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pakta_integritas' AND COLUMN_NAME='user_id');
SET @sql := IF(@cnt=0, "ALTER TABLE `pakta_integritas` ADD COLUMN `user_id` INT NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pakta_integritas' AND COLUMN_NAME='tgl_dibuat');
SET @sql := IF(@cnt=0, "ALTER TABLE `pakta_integritas` ADD COLUMN `tgl_dibuat` DATETIME NULL", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Notifications table (buat jika belum ada)
SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications');
SET @sql := IF(@cnt=0, "CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(64) NOT NULL,
    `payload` TEXT,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (`user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Peminjaman items: kalau Anda sudah punya tabel detail (mis. `detail_peminjam`), kita akan gunakan tabel itu; hanya buat `peminjaman_items` jika tidak ada tabel sejenis
SET @cnt := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('peminjaman_items','peminjaman_item','peminjaman_detail','peminjamanitems','detail_peminjaman','detail_pinjaman','detail_peminjam'));
SET @sql := IF(@cnt=0, "CREATE TABLE `peminjaman_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `peminjaman_id` INT NOT NULL,
    `barang_id` INT NOT NULL,
    `qty` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (`peminjaman_id`),
    INDEX (`barang_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "SELECT 0");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catatan: Beberapa client MySQL/phpMyAdmin menolak blok IF ... END IF di SQL tunggal.
-- Jika Anda mengalami error ketika menjalankan file ini, jalankan bagian-bagian yang relevan satu per satu.
-- Alternatif cepat untuk kasus duplikat kolom: gunakan ALTER TABLE ... MODIFY COLUMN untuk menyesuaikan definisi kolom yang sudah ada.
