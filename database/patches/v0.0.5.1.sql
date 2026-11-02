-- Monitor v0.0.5.1 hotfix: multiple Telegram destinations on an existing v0.0.5 database.
-- Apply ONCE after a database backup, with cron paused. Existing data is preserved.
-- The current saved single Chat ID is used to label historical delivery records.
ALTER TABLE notification_logs
  ADD COLUMN destination_chat_id VARCHAR(128) NOT NULL DEFAULT '' AFTER channel;

UPDATE notification_logs
SET destination_chat_id = COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'telegram_chat_id' LIMIT 1), '')
WHERE channel = 'telegram' AND destination_chat_id = '';

ALTER TABLE notification_logs
  DROP INDEX uniq_notification_incident_event,
  ADD UNIQUE KEY uniq_notification_target_event (channel, incident_id, event_type, destination_chat_id);

INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version', 'v0.0.5.1', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at);
