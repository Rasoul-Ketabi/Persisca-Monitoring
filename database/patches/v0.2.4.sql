-- Monitor v0.2.4: strict exclusive owner-based Telegram notification routing.
-- Upgrade ONLY an accepted v0.2.3 (including Portal Add/Edit Monitor hotfix).
-- Back up database and stop cron before applying. Re-running this file is safe.
-- No schema DDL or credential changes; existing data and historical sent logs preserved.
-- Cancel older queued cross-owner notifications so they cannot be delivered after upgrade.
UPDATE notification_logs AS l
JOIN monitors AS m ON m.id = l.monitor_id
SET l.status='failed',
    l.error_code='wrong_owner',
    l.error_message='Superseded by owner-based Telegram routing.',
    l.next_attempt_at=NULL,
    l.updated_at=UTC_TIMESTAMP()
WHERE l.channel='telegram' AND l.status IN ('pending','processing')
  AND (
    (m.portal_user_id IS NULL AND (l.recipient_type <> 'admin_global' OR l.recipient_id <> 0))
    OR
    (m.portal_user_id IS NOT NULL AND (l.recipient_type <> 'portal_user' OR l.recipient_id <> m.portal_user_id))
  );

-- Exact installed version after code deployment must match VERSION.
INSERT INTO system_meta(meta_key,meta_value,updated_at)
VALUES ('installed_version','v0.2.4',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
