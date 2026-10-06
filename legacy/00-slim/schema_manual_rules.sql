-- Manual Rules Platform schema (auto-created by prop_manual_api.php?action=install)
-- You normally do NOT need to run this manually.

CREATE TABLE IF NOT EXISTS `prop_race_fingerprint` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `racingdate` DATE NOT NULL,
  `venue` VARCHAR(10) NOT NULL,
  `venue_bucket` ENUM('ST','HV','Sx') NOT NULL,
  `raceno` INT NOT NULL,
  `distance` INT DEFAULT NULL,
  `go_ch` VARCHAR(50) DEFAULT NULL,
  `n_horses` TINYINT NOT NULL,
  `prop_string` VARCHAR(255) NOT NULL,
  `bands_json` LONGTEXT NOT NULL,
  `cells_json` LONGTEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_race` (`racingdate`, `venue`, `raceno`),
  KEY `idx_bucket_date` (`venue_bucket`, `racingdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prop_manual_rule` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(32) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `venue_scope` ENUM('ST','HV','Sx','ALL') NOT NULL DEFAULT 'Sx',
  `priority` INT NOT NULL DEFAULT 100,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `mark_weight` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `condition_json` LONGTEXT NOT NULL,
  `pick_json` LONGTEXT NOT NULL,
  `note_zh` TEXT DEFAULT NULL,
  `created_by` VARCHAR(40) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_scope_en` (`venue_scope`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prop_manual_rule_evidence` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `rule_id` BIGINT NOT NULL,
  `racingdate` DATE NOT NULL,
  `venue` VARCHAR(10) NOT NULL,
  `raceno` INT NOT NULL,
  `prop_string` VARCHAR(255) NOT NULL,
  `result_json` LONGTEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rule_race` (`rule_id`, `racingdate`, `venue`, `raceno`),
  KEY `idx_race` (`racingdate`, `venue`, `raceno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prop_manual_rule_stat` (
  `rule_id` BIGINT NOT NULL,
  `venue_scope` VARCHAR(10) NOT NULL,
  `date_from` DATE NOT NULL,
  `date_to` DATE NOT NULL,
  `fp_max` TINYINT NOT NULL DEFAULT 4,
  `races_matched` INT NOT NULL,
  `picks_total` INT NOT NULL,
  `picks_placed` INT NOT NULL,
  `race_hit` INT NOT NULL,
  `hit_rate` DECIMAL(6,4) NOT NULL,
  `pick_hit_rate` DECIMAL(6,4) NOT NULL,
  `computed_at` DATETIME NOT NULL,
  PRIMARY KEY (`rule_id`, `venue_scope`, `date_from`, `date_to`, `fp_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
