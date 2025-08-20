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
  status ENUM('in_work','exited') DEFAULT 'in_work'
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
