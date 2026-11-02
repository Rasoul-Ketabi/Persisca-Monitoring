-- Stage 10C: v0.2.1 -> v0.2.2. Back up first; apply ONCE before the ZIP patch.
-- Existing admin Telegram notification history stays admin_global (recipient_id=0).
-- Do not rerun installer or import database/schema.sql over an installed application.
ALTER TABLE portal_users
  ADD COLUMN telegram_chat_id VARCHAR(128) NULL,
  ADD COLUMN telegram_user_id VARCHAR(32) NULL,
  ADD COLUMN telegram_username VARCHAR(64) NULL,
  ADD COLUMN telegram_connected_at DATETIME NULL,
  ADD COLUMN telegram_notifications_enabled TINYINT(1) NOT NULL DEFAULT 0,
  ADD UNIQUE KEY uniq_portal_telegram_user (telegram_user_id);

ALTER TABLE notification_logs
  ADD COLUMN recipient_type VARCHAR(20) NOT NULL DEFAULT 'admin_global' AFTER destination_chat_id,
  ADD COLUMN recipient_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER recipient_type,
  DROP INDEX uniq_notification_target_event,
  ADD UNIQUE KEY uniq_notification_recipient_event (channel,incident_id,event_type,recipient_type,recipient_id,destination_chat_id),
  ADD INDEX idx_notification_recipient_history (recipient_type,recipient_id,created_at);

CREATE TABLE IF NOT EXISTS telegram_link_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  portal_user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_telegram_link_token_hash (token_hash),
  KEY idx_telegram_link_user_expiry (portal_user_id,expires_at),
  CONSTRAINT fk_telegram_link_portal FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key,setting_value,updated_at) VALUES
('telegram_bot_username','',UTC_TIMESTAMP()),
('telegram_webhook_secret','',UTC_TIMESTAMP()),
('telegram_webhook_configured','0',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key=setting_key;

INSERT INTO system_meta(meta_key,meta_value,updated_at)
VALUES ('installed_version','v0.2.2',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
