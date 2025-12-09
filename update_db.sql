-- Update script for Collect feature
CREATE TABLE IF NOT EXISTS collect_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  status ENUM('open','paused','ended','void') NOT NULL DEFAULT 'open',
  deadline DATE NULL,
  fields_json LONGTEXT NOT NULL,
  target_member_ids LONGTEXT,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collect_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  member_id INT NOT NULL,
  data_json LONGTEXT,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_collect_template (template_id),
  INDEX idx_collect_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
