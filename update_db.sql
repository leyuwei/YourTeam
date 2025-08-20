USE team_management;

CREATE TABLE IF NOT EXISTS reimbursement_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  in_charge_member_id INT DEFAULT NULL,
  deadline DATE NOT NULL,
  price_limit DECIMAL(10,2) DEFAULT NULL,
  status ENUM('open','locked','completed') DEFAULT 'open',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (in_charge_member_id) REFERENCES members(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reimbursement_receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  member_id INT NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  category ENUM('office','electronic','membership','book','trip') NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  status ENUM('submitted','locked','complete') DEFAULT 'submitted',
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES reimbursement_batches(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

ALTER TABLE reimbursement_batches ADD COLUMN IF NOT EXISTS price_limit DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE reimbursement_batches ADD COLUMN IF NOT EXISTS status ENUM('open','locked','completed') DEFAULT 'open';
ALTER TABLE reimbursement_receipts CHANGE COLUMN amount price DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE reimbursement_receipts ADD COLUMN IF NOT EXISTS category ENUM('office','electronic','membership','book','trip') NOT NULL AFTER stored_filename;
ALTER TABLE reimbursement_receipts ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT NULL AFTER category;
ALTER TABLE reimbursement_receipts ADD COLUMN IF NOT EXISTS status ENUM('submitted','locked','complete') DEFAULT 'submitted' AFTER price;
