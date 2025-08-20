USE team_management;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content TEXT NOT NULL,
  valid_begin_date DATE NOT NULL,
  valid_end_date DATE NOT NULL,
  is_revoked TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS notification_targets (
  notification_id INT NOT NULL,
  member_id INT NOT NULL,
  status ENUM('sent','seen','checked') DEFAULT 'sent',
  PRIMARY KEY (notification_id, member_id),
  FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
