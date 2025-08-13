ALTER TABLE project_member_log MODIFY join_time DATE NOT NULL;
ALTER TABLE project_member_log MODIFY exit_time DATE DEFAULT NULL;
ALTER TABLE projects ADD COLUMN bg_color VARCHAR(20) DEFAULT NULL;
ALTER TABLE research_directions ADD COLUMN bg_color VARCHAR(20) DEFAULT NULL;
