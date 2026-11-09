UPDATE `app_entry` SET datum_do = NULL WHERE datum_do = '0000-00-00 00:00:00';

ALTER TABLE `app_entry`
    CHANGE COLUMN `datum` `datum` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP) COMMENT 'дата создания' AFTER `did`,
    CHANGE COLUMN `datum_do` `datum_do` DATETIME NULL DEFAULT NULL COMMENT 'дата обработки обращения' AFTER `datum`;

ALTER TABLE `app_entry_poz`
    CHANGE COLUMN `kol` `kol` DOUBLE NULL DEFAULT NULL COMMENT 'количество' AFTER `title`