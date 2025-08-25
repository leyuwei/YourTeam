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
