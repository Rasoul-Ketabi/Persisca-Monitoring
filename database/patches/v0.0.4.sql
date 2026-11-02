-- Monitor Stage 04: apply ONCE to an existing v0.0.3 installation.
-- Backup your database first. Preserve existing data; do not reinstall.
ALTER TABLE monitors
  ADD COLUMN operational_status VARCHAR(12) NOT NULL DEFAULT 'unknown',
  ADD COLUMN failure_threshold TINYINT UNSIGNED NOT NULL DEFAULT 2,
  ADD COLUMN consecutive_failures INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN failure_sequence_started_at DATETIME NULL,
  ADD COLUMN last_success_at DATETIME NULL,
  ADD COLUMN last_failure_at DATETIME NULL,
  ADD COLUMN current_incident_id BIGINT UNSIGNED NULL,
  ADD INDEX idx_monitors_operational (operational_status),
  ADD INDEX idx_monitors_active_incident (current_incident_id);

CREATE TABLE IF NOT EXISTS incidents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  monitor_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'open',
  started_at DATETIME NOT NULL,
  detected_at DATETIME NOT NULL,
  recovered_at DATETIME NULL,
  duration_seconds BIGINT UNSIGNED NULL,
  opening_check_id BIGINT UNSIGNED NULL,
  closing_check_id BIGINT UNSIGNED NULL,
  failure_count INT UNSIGNED NOT NULL DEFAULT 1,
  last_error_type VARCHAR(100) NULL,
  last_error_message TEXT NULL,
  last_http_status SMALLINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_incident_monitor_status (monitor_id, status),
  INDEX idx_incident_status_start (status, started_at),
  INDEX idx_incident_monitor_start (monitor_id, started_at),
  INDEX idx_incident_recovered (recovered_at),
  CONSTRAINT fk_incidents_monitor FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE monitor_checks
  ADD COLUMN incident_id BIGINT UNSIGNED NULL,
  ADD INDEX idx_checks_incident_date (incident_id, checked_at);

INSERT INTO system_meta (meta_key, meta_value, updated_at)
VALUES ('installed_version', 'v0.0.4', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at);
