USE team_management;
CREATE TABLE IF NOT EXISTS reimbursement_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  in_charge_member_id INT DEFAULT NULL,
  deadline DATE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (in_charge_member_id) REFERENCES members(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reimbursement_receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  member_id INT NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) DEFAULT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES reimbursement_batches(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
