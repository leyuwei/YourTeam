USE team_management;

CREATE TABLE IF NOT EXISTS collect_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  member_id INT NOT NULL,
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES collect_templates(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collect_response_values (
  id INT AUTO_INCREMENT PRIMARY KEY,
  response_id INT NOT NULL,
  field_id INT NOT NULL,
  value_text TEXT,
  file_path VARCHAR(255),
  FOREIGN KEY (response_id) REFERENCES collect_responses(id) ON DELETE CASCADE,
  FOREIGN KEY (field_id) REFERENCES collect_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
