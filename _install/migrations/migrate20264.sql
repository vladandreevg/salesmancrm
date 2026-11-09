SET FOREIGN_KEY_CHECKS=0;

SET sql_mode='NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES';

ALTER TABLE `app_settings` ADD COLUMN `api_key_login` VARCHAR(255) NULL DEFAULT NULL COMMENT 'логин, от имени которого действует ключ API (пусто — любой сотрудник)' AFTER `api_key`;

ALTER TABLE `app_speca` ADD COLUMN `sort` INT(20) NULL DEFAULT NULL COMMENT 'Порядок вывода' AFTER `tip`;
UPDATE `app_speca` SET `sort` = `spid` WHERE `sort` IS NULL;
ALTER TABLE `app_speca` ADD INDEX `did_sort` (`did`, `sort`);

SET FOREIGN_KEY_CHECKS=1;