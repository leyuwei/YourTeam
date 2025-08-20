CREATE TABLE IF NOT EXISTS reimbursement_announcement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content TEXT NOT NULL
);
INSERT INTO reimbursement_announcement (id, content) VALUES (1, '') ON DUPLICATE KEY UPDATE content=content;
