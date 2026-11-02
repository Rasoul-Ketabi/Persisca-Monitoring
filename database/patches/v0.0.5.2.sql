-- Monitor v0.0.5.2 notification page hotfix.
-- No schema changes. Optional metadata-only update for an installed v0.0.5.1 database.
-- Safe to run again. It does not change monitors, credentials, notification settings, or history.
INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version', 'v0.0.5.2', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at);
