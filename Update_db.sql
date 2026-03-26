USE team_management;

SET @has_is_must := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'notifications'
    AND COLUMN_NAME = 'is_must'
);

SET @ddl := IF(
  @has_is_must = 0,
  'ALTER TABLE notifications ADD COLUMN is_must TINYINT(1) NOT NULL DEFAULT 0 AFTER valid_end_date;',
  'SELECT "Column notifications.is_must already exists";'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
