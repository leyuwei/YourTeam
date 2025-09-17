USE team_management;

CREATE TABLE IF NOT EXISTS offices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sort_order INT DEFAULT 0,
  name VARCHAR(100) NOT NULL,
  location_description VARCHAR(255),
  region VARCHAR(100),
  layout_image VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS office_seats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  office_id INT NOT NULL,
  label VARCHAR(100) NOT NULL,
  pos_x DECIMAL(10,6) NOT NULL,
  pos_y DECIMAL(10,6) NOT NULL,
  member_id INT DEFAULT NULL,
  FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
);
