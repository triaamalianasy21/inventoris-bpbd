CREATE TABLE IF NOT EXISTS delete_requests (
  id_request INT AUTO_INCREMENT PRIMARY KEY,
  module ENUM('peminjaman','pengembalian','pakta','barang') NOT NULL,
  target_id INT NOT NULL,
  requested_by INT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_by INT NULL,
  verified_at DATETIME NULL,
  KEY idx_module_target (module, target_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
