ALTER TABLE members ADD COLUMN sort_order INT DEFAULT 0;
UPDATE members SET sort_order=id;
ALTER TABLE projects ADD COLUMN sort_order INT DEFAULT 0;
UPDATE projects SET sort_order=id;
ALTER TABLE research_directions ADD COLUMN sort_order INT DEFAULT 0;
UPDATE research_directions SET sort_order=id;
