CREATE DATABASE IF NOT EXISTS team_management DEFAULT CHARACTER SET utf8mb4;
USE team_management;

CREATE TABLE managers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL
);

INSERT INTO managers (username,password) VALUES
('manager1','$2y$12$sCJV4OxoRqqaBtDDLnd07OlvqIoO9L4/zSO9PwzgeZaFXqxoU5fcy'),
('manager2','$2y$12$sCJV4OxoRqqaBtDDLnd07OlvqIoO9L4/zSO9PwzgeZaFXqxoU5fcy');

CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  campus_id VARCHAR(20) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  identity_number VARCHAR(50),
  year_of_join INT,
  current_degree VARCHAR(50),
  degree_pursuing VARCHAR(50),
  phone VARCHAR(20),
  wechat VARCHAR(50),
  department VARCHAR(100),
  workplace VARCHAR(100),
  homeplace VARCHAR(100),
  status ENUM('in_work','exited') DEFAULT 'in_work',
  login_method ENUM('identity','password') NOT NULL DEFAULT 'identity',
  password_hash VARCHAR(255) DEFAULT NULL
);

CREATE TABLE member_extra_attributes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  name_en VARCHAR(100) NOT NULL DEFAULT '',
  name_zh VARCHAR(100) NOT NULL DEFAULT '',
  attribute_type ENUM('text','media') NOT NULL DEFAULT 'text',
  default_value TEXT
);

CREATE TABLE member_extra_values (
  member_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value TEXT,
  PRIMARY KEY (member_id, attribute_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  FOREIGN KEY (attribute_id) REFERENCES member_extra_attributes(id) ON DELETE CASCADE
);

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  bg_color VARCHAR(20),
  begin_date DATE,
  end_date DATE,
  status ENUM('todo','ongoing','paused','finished') DEFAULT 'todo'
);

CREATE TABLE project_member_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  member_id INT NOT NULL,
  sort_order INT DEFAULT 0,
  join_time DATE NOT NULL,
  exit_time DATE DEFAULT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  start_date DATE,
  status ENUM('active','paused','finished') DEFAULT 'active'
);

CREATE TABLE task_affairs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  description TEXT,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  status ENUM('pending','confirmed') DEFAULT 'pending',
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE task_affair_members (
  affair_id INT NOT NULL,
  member_id INT NOT NULL,
  PRIMARY KEY (affair_id, member_id),
  FOREIGN KEY (affair_id) REFERENCES task_affairs(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE research_directions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  bg_color VARCHAR(20)
);

CREATE TABLE direction_members (
  direction_id INT NOT NULL,
  member_id INT NOT NULL,
  sort_order INT DEFAULT 0,
  PRIMARY KEY (direction_id, member_id),
  FOREIGN KEY (direction_id) REFERENCES research_directions(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE todolist_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_role ENUM('manager','member') NOT NULL,
  week_start DATE NOT NULL,
  category ENUM('work','personal','longterm') NOT NULL,
  day ENUM('mon','tue','wed','thu','fri','sat','sun') NULL,
  content VARCHAR(255) NOT NULL,
  is_done TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0
);

CREATE TABLE todolist_common_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_role ENUM('manager','member') NOT NULL,
  content VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  INDEX idx_todolist_common_user (user_id, user_role)
);
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content TEXT NOT NULL,
  valid_begin_date DATE NOT NULL,
  valid_end_date DATE NOT NULL,
  is_revoked TINYINT(1) DEFAULT 0
);

CREATE TABLE notification_targets (
  notification_id INT NOT NULL,
  member_id INT NOT NULL,
  status ENUM('sent','seen','checked') DEFAULT 'sent',
  PRIMARY KEY (notification_id, member_id),
  FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE reimbursement_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  in_charge_member_id INT DEFAULT NULL,
  deadline DATE NOT NULL,
  price_limit DECIMAL(10,2) DEFAULT NULL,
  allowed_types VARCHAR(255) DEFAULT NULL,
  status ENUM('open','locked','completed') DEFAULT 'open',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (in_charge_member_id) REFERENCES members(id) ON DELETE SET NULL
);

CREATE TABLE reimbursement_receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  member_id INT NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  category ENUM('office','electronic','membership','book','trip') NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  status ENUM('submitted','locked','complete','refused') DEFAULT 'submitted',
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES reimbursement_batches(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE reimbursement_prohibited_keywords (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE reimbursement_announcement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content_en TEXT NOT NULL,
  content_zh TEXT NOT NULL
);
INSERT INTO reimbursement_announcement (id, content_en, content_zh) VALUES (1, '', '');

CREATE TABLE reimbursement_batch_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  operator_name VARCHAR(100) NOT NULL,
  action VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES reimbursement_batches(id) ON DELETE CASCADE
);

CREATE TABLE regulations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  updated_at DATE NOT NULL
);

CREATE TABLE regulation_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  regulation_id INT NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  FOREIGN KEY (regulation_id) REFERENCES regulations(id) ON DELETE CASCADE
);

CREATE TABLE offices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  name VARCHAR(100) NOT NULL,
  location_description VARCHAR(255),
  region VARCHAR(100),
  open_for_selection TINYINT(1) NOT NULL DEFAULT 1,
  layout_image VARCHAR(255) NOT NULL
);

CREATE TABLE office_seats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  office_id INT NOT NULL,
  label VARCHAR(100) NOT NULL,
  pos_x DECIMAL(10,6) NOT NULL,
  pos_y DECIMAL(10,6) NOT NULL,
  member_id INT DEFAULT NULL,
  FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
);

CREATE TABLE office_selection_whitelist (
  office_id INT NOT NULL,
  member_id INT NOT NULL,
  PRIMARY KEY (office_id, member_id),
  FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE asset_inbound_orders (
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

CREATE TABLE assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inbound_order_id INT NOT NULL,
  asset_code VARCHAR(120) NOT NULL UNIQUE,
  category VARCHAR(150) DEFAULT '',
  model VARCHAR(255) DEFAULT '',
  organization VARCHAR(255) DEFAULT '',
  remarks TEXT,
  current_office_id INT DEFAULT NULL,
  current_seat_id INT DEFAULT NULL,
  owner_member_id INT DEFAULT NULL,
  owner_external_name VARCHAR(150) DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  status ENUM('in_use','maintenance','pending','lost','retired') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (inbound_order_id) REFERENCES asset_inbound_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (current_office_id) REFERENCES offices(id) ON DELETE SET NULL,
  FOREIGN KEY (current_seat_id) REFERENCES office_seats(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_member_id) REFERENCES members(id) ON DELETE SET NULL
);

CREATE TABLE asset_operation_logs (
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

CREATE TABLE asset_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  code_prefix VARCHAR(60) NOT NULL DEFAULT 'ASSET-',
  link_prefix VARCHAR(255) NOT NULL DEFAULT '',
  sync_api_prefix VARCHAR(255) NOT NULL DEFAULT '',
  sync_mapping MEDIUMTEXT DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO asset_settings (id, code_prefix, link_prefix, sync_api_prefix, sync_mapping)
VALUES (1, 'ASSET-', '', '', NULL)
ON DUPLICATE KEY UPDATE code_prefix = code_prefix, link_prefix = link_prefix, sync_api_prefix = sync_api_prefix, sync_mapping = sync_mapping;

