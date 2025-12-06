USE team_management;

CREATE TABLE IF NOT EXISTS askme_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content_zh LONGTEXT NOT NULL,
  content_en LONGTEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS askme_keywords (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entry_id INT NOT NULL,
  keyword VARCHAR(255) NOT NULL,
  locale ENUM('zh','en') NOT NULL DEFAULT 'zh',
  INDEX idx_askme_keyword_lookup (keyword(120)),
  INDEX idx_askme_keyword_locale (locale),
  FOREIGN KEY (entry_id) REFERENCES askme_entries(id) ON DELETE CASCADE
);
