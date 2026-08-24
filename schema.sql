-- Tuhimunatanga v3.1.2 database schema.
-- Import this file into the database named by TUHIMUNATANGA_V3_DB_NAME.

CREATE TABLE IF NOT EXISTS `nga_taaurunga_v3` (
    `haatepe` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `rarangi_huna` MEDIUMTEXT CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `mukua_hash` BINARY(32) NULL,
    `waitohuwaa_hanga` BIGINT UNSIGNED NOT NULL,
    `waitohuwaa_mutu` BIGINT UNSIGNED NULL,
    `panui_kotahi` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`haatepe`),
    KEY `idx_taaurunga_waitohuwaa_mutu` (`waitohuwaa_mutu`),
    KEY `idx_taaurunga_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARACTER SET ascii COLLATE ascii_bin;

CREATE TABLE IF NOT EXISTS `nga_ngana_v3` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `momo` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `matua_hash` BINARY(32) NOT NULL,
    `wa_ip_hash` BINARY(32) NOT NULL,
    `waitohuwaa` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ngana_momo_waitohuwaa` (`momo`, `waitohuwaa`),
    KEY `idx_ngana_waitohuwaa` (`waitohuwaa`)
) ENGINE=InnoDB DEFAULT CHARACTER SET ascii COLLATE ascii_bin;
