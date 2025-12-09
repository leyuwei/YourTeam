USE team_management;

CREATE TABLE IF NOT EXISTS collect_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  deadline DATE NOT NULL,
  status ENUM('open','paused','ended','void') NOT NULL DEFAULT 'open',
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collect_fields (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  sort_order INT DEFAULT 0,
  label_en VARCHAR(150) DEFAULT '',
  label_zh VARCHAR(150) DEFAULT '',
  field_type ENUM('number','text','select','file') NOT NULL DEFAULT 'text',
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  options TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES collect_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collect_template_targets (
  template_id INT NOT NULL,
  member_id INT NOT NULL,
  status ENUM('pending','submitted') NOT NULL DEFAULT 'pending',
  submitted_at DATETIME DEFAULT NULL,
  PRIMARY KEY (template_id, member_id),
  FOREIGN KEY (template_id) REFERENCES collect_templates(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
