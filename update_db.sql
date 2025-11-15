ALTER TABLE members
    ADD COLUMN login_method ENUM('identity','password') NOT NULL DEFAULT 'identity' AFTER status,
    ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER login_method;
