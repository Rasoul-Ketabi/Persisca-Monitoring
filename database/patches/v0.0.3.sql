-- Monitor v0.0.3 migration; run once after uploading the Stage 03 patch.
-- Requires an installed v0.0.2 schema. Non-destructive: preserves users, settings, monitors, and branding.
-- BACK UP YOUR DATABASE FIRST.

ALTER TABLE monitors
  ADD COLUMN last_checked_at DATETIME NULL,
  ADD COLUMN last_check_success TINYINT(1) NULL,
  ADD COLUMN last_http_status SMALLINT UNSIGNED NULL,
  ADD COLUMN last_response_time_ms INT UNSIGNED NULL,
  ADD COLUMN last_error_type VARCHAR(100) NULL,
  ADD COLUMN last_error_message TEXT NULL,
  ADD COLUMN last_attempt_count SMALLINT UNSIGNED NULL,
  ADD COLUMN next_check_at DATETIME NULL,
  ADD INDEX idx_monitors_due (is_active, next_check_at);


CREATE TABLE IF NOT EXISTS monitor_checks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  monitor_id BIGINT UNSIGNED NOT NULL,
  check_type VARCHAR(12) NOT NULL DEFAULT 'scheduled',
  success TINYINT(1) NOT NULL,
  http_status SMALLINT UNSIGNED NULL,
  response_time_ms INT UNSIGNED NULL,
  error_type VARCHAR(100) NULL,
  error_message TEXT NULL,
  attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  effective_url TEXT NULL,
  checked_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_checks_monitor_date (monitor_id, checked_at),
  INDEX idx_checks_date (checked_at),
  INDEX idx_checks_success_date (success, checked_at),
  CONSTRAINT fk_monitor_checks_monitor FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version', 'v0.0.3', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at);

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
('check_retention_days', '90', UTC_TIMESTAMP()),
('cron_stale_after_minutes', '10', UTC_TIMESTAMP()),
('cron_batch_size', '25', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_value = setting_value;
