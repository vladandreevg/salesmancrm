SET FOREIGN_KEY_CHECKS=0;

SET sql_mode='NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES';

ALTER TABLE `app_file_cat`
	ADD INDEX `subid` (`subid`);

ALTER TABLE `app_file`
	CHANGE COLUMN `folder` `folder` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Папка _file_cat.idcategory' AFTER `coid`,
	ADD INDEX `ftitle` (`ftitle`),
	ADD INDEX `folder` (`folder`);

ALTER TABLE `app_modcatalog`
	ADD INDEX `prid` (`prid`);

ALTER TABLE `app_price_cat`
	ADD INDEX `sub` (`sub`);

ALTER TABLE `app_price`
    CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT (CURRENT_TIMESTAMP) AFTER `price_5`;

UPDATE `app_price` SET datum = NULL WHERE datum = '0000-00-00 00:00:00';

ALTER TABLE `app_price`
	ADD INDEX `pr_cat` (`pr_cat`);

SET FOREIGN_KEY_CHECKS=1;