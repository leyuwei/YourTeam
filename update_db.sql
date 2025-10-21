CREATE TABLE IF NOT EXISTS asset_inbound_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(100) NOT NULL UNIQUE,
  supplier VARCHAR(255) DEFAULT '',
  supplier_lead VARCHAR(255) DEFAULT '',
  receiver_lead VARCHAR(255) DEFAULT '',
  arrival_location VARCHAR(255) DEFAULT '',
  arrival_date DATE DEFAULT NULL,
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inbound_order_id INT NOT NULL,
  asset_code VARCHAR(120) NOT NULL UNIQUE,
  category VARCHAR(150) DEFAULT '',
  model VARCHAR(255) DEFAULT '',
  current_office_id INT DEFAULT NULL,
  current_seat_id INT DEFAULT NULL,
  owner_member_id INT DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  status ENUM('in_use','maintenance','pending','lost','retired') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (inbound_order_id) REFERENCES asset_inbound_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (current_office_id) REFERENCES offices(id) ON DELETE SET NULL,
  FOREIGN KEY (current_seat_id) REFERENCES office_seats(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_member_id) REFERENCES members(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS asset_operation_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  target_type ENUM('asset','inbound_order') NOT NULL,
  target_id INT NOT NULL,
  operator_name VARCHAR(100) NOT NULL,
  operator_role ENUM('manager','member') NOT NULL,
  action VARCHAR(255) NOT NULL,
  details TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_asset_logs_target (target_type, target_id)
);
