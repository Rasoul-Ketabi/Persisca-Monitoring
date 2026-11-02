-- Stage 05: apply ONCE after Stage 04 (v0.0.4). Back up the database first.
-- Does not change existing monitor, incident, authentication or branding records.
ALTER TABLE monitors ADD COLUMN telegram_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS notification_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  monitor_id BIGINT UNSIGNED NOT NULL,
  incident_id BIGINT UNSIGNED NOT NULL,
  channel VARCHAR(24) NOT NULL DEFAULT 'telegram',
  event_type VARCHAR(24) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  provider_message_id VARCHAR(100) NULL,
  error_code VARCHAR(24) NULL,
  error_message VARCHAR(220) NULL,
  next_attempt_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_notification_incident_event (channel, incident_id, event_type),
  INDEX idx_notification_due (status, next_attempt_at),
  INDEX idx_notification_monitor_date (monitor_id, created_at),
  INDEX idx_notification_created (created_at),
  CONSTRAINT fk_notification_monitor FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE,
  CONSTRAINT fk_notification_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Telegram stays disabled until explicitly configured in admin.
INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
('telegram_enabled','0',UTC_TIMESTAMP()),
('telegram_notify_down','1',UTC_TIMESTAMP()),
('telegram_notify_recovery','1',UTC_TIMESTAMP()),
('telegram_chat_id','',UTC_TIMESTAMP()),
('telegram_bot_token','',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version', 'v0.0.5', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at);
