-- Monitor v0.0.8 upgrade from v0.0.7. Take backup. Apply once, then update files.
-- The first active legacy admin is promoted only when no active Super Admin exists.
ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER role;
ALTER TABLE users ADD INDEX idx_users_role_status (role,status);
SET @monitor_active_super_admins := (SELECT COUNT(*) FROM users WHERE role='super_admin' AND status='active');
UPDATE users SET role='super_admin',updated_at=UTC_TIMESTAMP()
WHERE @monitor_active_super_admins = 0 AND status='active' AND role='admin'
ORDER BY id ASC LIMIT 1;
CREATE TABLE IF NOT EXISTS maintenance_logs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL,
 action VARCHAR(60) NOT NULL, status VARCHAR(20) NOT NULL,summary VARCHAR(240) NOT NULL,
 created_at DATETIME NOT NULL,INDEX idx_maintenance_created(created_at),
 INDEX idx_maintenance_user_created(user_id,created_at),
 CONSTRAINT fk_maintenance_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO system_meta(meta_key,meta_value,updated_at) VALUES ('installed_version','v0.0.8',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
