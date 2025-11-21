ALTER TABLE members
    ADD COLUMN login_method ENUM('identity','password') NOT NULL DEFAULT 'identity' AFTER status,
    ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER login_method;

ALTER TABLE member_extra_attributes
    ADD COLUMN attribute_type ENUM('text','media') NOT NULL DEFAULT 'text' AFTER name_zh;
