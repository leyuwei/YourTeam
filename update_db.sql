CREATE TABLE IF NOT EXISTS member_extra_attributes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  name_en VARCHAR(100) NOT NULL DEFAULT '',
  name_zh VARCHAR(100) NOT NULL DEFAULT '',
  default_value TEXT
);

CREATE TABLE IF NOT EXISTS member_extra_values (
  member_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value TEXT,
  PRIMARY KEY (member_id, attribute_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  FOREIGN KEY (attribute_id) REFERENCES member_extra_attributes(id) ON DELETE CASCADE
);
