-- Monitor SMTP Stage 10A.1, cumulatively applied to actual v0.2.2 baseline.
-- Upgrade v0.2.2 -> v0.2.3. Back up the database before applying once.
-- No schema DDL is required: settings is already a key/value table.
-- Existing OTP, Telegram, portal, branding and monitoring data are retained.
INSERT INTO settings(setting_key,setting_value,updated_at) VALUES
('mail_transport','php_mail',UTC_TIMESTAMP()),
('smtp_host','',UTC_TIMESTAMP()),
('smtp_port','587',UTC_TIMESTAMP()),
('smtp_encryption','tls',UTC_TIMESTAMP()),
('smtp_auth_enabled','1',UTC_TIMESTAMP()),
('smtp_username','',UTC_TIMESTAMP()),
('smtp_password','',UTC_TIMESTAMP()),
('mail_reply_to','',UTC_TIMESTAMP()),
('smtp_timeout','15',UTC_TIMESTAMP()),
('mail_last_test_at','',UTC_TIMESTAMP()),
('mail_last_test_status','never',UTC_TIMESTAMP()),
('mail_last_test_error','',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key=setting_key;

INSERT INTO system_meta(meta_key,meta_value,updated_at)
VALUES ('installed_version','v0.2.3',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
