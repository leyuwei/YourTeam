ALTER TABLE offices
  ADD COLUMN open_for_selection TINYINT(1) NOT NULL DEFAULT 1 AFTER region;

CREATE TABLE IF NOT EXISTS office_selection_whitelist (
  office_id INT NOT NULL,
  member_id INT NOT NULL,
  PRIMARY KEY (office_id, member_id),
  FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

INSERT INTO office_selection_whitelist (office_id, member_id)
SELECT o.id, m.id
FROM offices o
JOIN members m ON m.status != 'exited'
ON DUPLICATE KEY UPDATE member_id = member_id;
