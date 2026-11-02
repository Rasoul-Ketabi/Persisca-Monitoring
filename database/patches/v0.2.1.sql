-- Stage 10B: v0.2.0 (including admin sidebar hotfix) -> v0.2.1.
-- Run ONCE after backing up. Existing accounts, monitors and settings remain unchanged.
-- Personal publication is disabled for every existing account by default.
ALTER TABLE portal_users
  ADD COLUMN public_slug VARCHAR(120) NULL,
  ADD COLUMN public_status_enabled TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN status_page_title VARCHAR(190) NULL;

-- Stable unique IDs avoid collisions and do not unexpectedly expose customer names.
UPDATE portal_users SET public_slug=CONCAT('user-',id) WHERE public_slug IS NULL OR public_slug='';
ALTER TABLE portal_users
  MODIFY COLUMN public_slug VARCHAR(120) NOT NULL,
  ADD UNIQUE KEY uniq_portal_public_slug (public_slug);

INSERT INTO system_meta (meta_key,meta_value,updated_at)
VALUES ('installed_version','v0.2.1',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
