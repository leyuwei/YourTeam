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
  homeplace VARCHAR(100)
);

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  begin_date DATE,
  end_date DATE,
  status ENUM('todo','ongoing','paused','finished') DEFAULT 'todo'
);

CREATE TABLE project_member_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  member_id INT NOT NULL,
  sort_order INT DEFAULT 0,
  join_time DATETIME NOT NULL,
  exit_time DATETIME DEFAULT NULL,
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
  description TEXT
);

CREATE TABLE direction_members (
  direction_id INT NOT NULL,
  member_id INT NOT NULL,
  sort_order INT DEFAULT 0,
  PRIMARY KEY (direction_id, member_id),
  FOREIGN KEY (direction_id) REFERENCES research_directions(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
