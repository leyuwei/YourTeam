CREATE TABLE IF NOT EXISTS publish_settings (
  id INT PRIMARY KEY,
  allow_member_view_all TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO publish_settings (id, allow_member_view_all)
VALUES (1, 0)
ON DUPLICATE KEY UPDATE id = id;
