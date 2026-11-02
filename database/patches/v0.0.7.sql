-- Monitor v0.0.7 upgrade from v0.0.6. No structural schema changes.
-- Existing users, monitors, check/incident history, notifications, secrets and settings are preserved.
-- Apply once after backup. Do not rerun the installer.
INSERT INTO settings (setting_key, setting_value, updated_at)
VALUES
('status_page_enabled','1',UTC_TIMESTAMP()),
('status_show_uptime','1',UTC_TIMESTAMP()),
('status_show_response_time','1',UTC_TIMESTAMP()),
('status_show_incidents','1',UTC_TIMESTAMP()),
('status_show_footer','1',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version','v0.0.7',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value), updated_at=VALUES(updated_at);
