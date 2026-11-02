-- Monitor Stage 10A: upgrade accepted v0.1.0 to v0.2.0. Apply once after backup.
-- Existing monitors remain platform-owned, and existing admin users are unchanged.
CREATE TABLE IF NOT EXISTS portal_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  email VARCHAR(190) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  monitor_limit INT UNSIGNED NOT NULL DEFAULT 0,
  session_version INT UNSIGNED NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_portal_email (email),
  KEY idx_portal_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_otp_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  portal_user_id BIGINT UNSIGNED NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  consumed_at DATETIME NULL,
  requested_ip_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_portal_otp_user_date (portal_user_id,created_at),
  KEY idx_portal_otp_expiry (expires_at),
  CONSTRAINT fk_portal_otp_user FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_otp_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email_hash CHAR(64) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  requested_at DATETIME NOT NULL,
  KEY idx_portal_requests_email_date (email_hash,requested_at),
  KEY idx_portal_requests_ip_date (ip_hash,requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE monitors ADD COLUMN portal_user_id BIGINT UNSIGNED NULL DEFAULT NULL,
  ADD KEY idx_monitors_portal_owner (portal_user_id),
  ADD CONSTRAINT fk_monitors_portal_owner FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE SET NULL;
INSERT INTO settings (setting_key,setting_value,updated_at) VALUES
  ('mail_from_email','',UTC_TIMESTAMP()),('mail_from_name','',UTC_TIMESTAMP())
  ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
INSERT INTO system_meta (meta_key,meta_value,updated_at) VALUES ('installed_version','v0.2.0',UTC_TIMESTAMP())
  ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
