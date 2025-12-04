ALTER TABLE reimbursement_batches
  ADD COLUMN notice_en TEXT NULL AFTER allowed_types,
  ADD COLUMN notice_zh TEXT NULL AFTER notice_en;
