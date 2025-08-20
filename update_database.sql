ALTER TABLE reimbursement_receipts MODIFY status ENUM('submitted','locked','complete','refused') DEFAULT 'submitted';
