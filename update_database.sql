ALTER TABLE `collect_templates`
  ADD COLUMN `allow_user_download` TINYINT(1) NOT NULL DEFAULT 0 AFTER `deadline`;
