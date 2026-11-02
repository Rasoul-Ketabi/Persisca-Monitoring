-- Monitor v0.1.0 release upgrade from accepted v0.0.8.
-- Back up the database first. Upload the v0.1.0 Patch, then apply this file once.
-- No tables/columns are dropped; user, monitor, incident, analytics and Telegram data remain unchanged.
-- The current schema from v0.0.8 remains valid for the first production release.
INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version', 'v0.1.0', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at);
