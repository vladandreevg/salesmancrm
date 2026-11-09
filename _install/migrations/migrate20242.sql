ALTER TABLE `app_modcatalog`
    CHANGE COLUMN `sklad` `sklad` INT(10) NULL DEFAULT NULL COMMENT 'modcatalog_sklad.id' AFTER `files`,
    CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL COMMENT 'user.iduser' AFTER `sklad`;

ALTER TABLE `app_modcatalog_aktpoz`
    CHANGE COLUMN `ida` `ida` INT(10) NULL DEFAULT NULL COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)' AFTER `id`,
    CHANGE COLUMN `prid` `prid` INT(10) NULL DEFAULT NULL COMMENT 'price.n_id' AFTER `ida`,
    CHANGE COLUMN `identity` `identity` INT(10) NOT NULL DEFAULT '1' AFTER `kol`;

ALTER TABLE `app_modcatalog_dop`
    CHANGE COLUMN `prid` `prid` INT(10) NULL DEFAULT NULL COMMENT 'price.n_id' AFTER `id`,
    CHANGE COLUMN `bid` `bid` INT(10) NULL DEFAULT NULL COMMENT 'по-моему не используется' AFTER `prid`,
    CHANGE COLUMN `datum` `datum` DATE NULL DEFAULT NULL AFTER `bid`,
    CHANGE COLUMN `content` `content` TEXT NULL DEFAULT NULL COMMENT 'наименование доп. затрат' COLLATE 'utf8_general_ci' AFTER `datum`,
    CHANGE COLUMN `summa` `summa` DOUBLE NULL DEFAULT '0' COMMENT 'стоимость доп. затрат' AFTER `content`,
    CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL COMMENT 'clientcat.clid' AFTER `summa`,
    CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL COMMENT 'user.iduser' AFTER `clid`,
    CHANGE COLUMN `identity` `identity` INT(10) NOT NULL DEFAULT '1' AFTER `iduser`;

ALTER TABLE `app_modcatalog_field`
    CHANGE COLUMN `pfid` `pfid` INT(10) NULL DEFAULT NULL COMMENT 'modcatalog_fieldcat.id' AFTER `id`,
    CHANGE COLUMN `n_id` `n_id` INT(10) NULL DEFAULT NULL COMMENT 'price.n_id' AFTER `pfid`,
    CHANGE COLUMN `value` `value` VARCHAR(255) NULL DEFAULT NULL COMMENT 'значение доп поля для данной продукции' COLLATE 'utf8_general_ci' AFTER `n_id`;

ALTER TABLE `app_modcatalog_fieldcat`
    CHANGE COLUMN `name` `name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'название доп поля' COLLATE 'utf8_general_ci' AFTER `id`,
    CHANGE COLUMN `tip` `tip` VARCHAR(10) NULL DEFAULT NULL COMMENT 'тип вывода: поле ввода, поле текста, список выбора, чекбоксы, радиокнопки и разделитель' COLLATE 'utf8_general_ci' AFTER `name`,
    CHANGE COLUMN `value` `value` TEXT NULL DEFAULT NULL COMMENT 'выбираемы значения' COLLATE 'utf8_general_ci' AFTER `tip`,
    CHANGE COLUMN `ord` `ord` INT(10) NULL DEFAULT NULL COMMENT 'порядковый номер поля в списке доп. полей' AFTER `value`,
    CHANGE COLUMN `pole` `pole` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `ord`,
    CHANGE COLUMN `pwidth` `pwidth` INT(10) NULL DEFAULT '50' COMMENT 'ширина поля' AFTER `pole`;

ALTER TABLE `app_moddaycontrol_poz`
    CHANGE COLUMN `date_create` `date_create` DATE NULL DEFAULT NULL COMMENT 'дата производства' AFTER `serial`,
    CHANGE COLUMN `date_last` `date_last` DATE NULL DEFAULT NULL COMMENT 'дата последней проверки' AFTER `date_create`,
    CHANGE COLUMN `period` `period` INT(10) NULL COMMENT 'период контроля' AFTER `date_last`,
    CHANGE COLUMN `active` `active` VARCHAR(5) NULL DEFAULT 'yes' COMMENT 'статус: в учете, списано-выбыло' COLLATE 'utf8_general_ci' AFTER `period`;

ALTER TABLE `app_logapi`
    CHANGE COLUMN `datum` `datum` DATETIME NOT NULL DEFAULT (CURRENT_TIMESTAMP()) AFTER `id`,
    CHANGE COLUMN `content` `content` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci' AFTER `datum`,
    CHANGE COLUMN `rez` `rez` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci' AFTER `content`,
    CHANGE COLUMN `ip` `ip` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci' AFTER `rez`,
    CHANGE COLUMN `remoteaddr` `remoteaddr` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci' AFTER `ip`,
    CHANGE COLUMN `identity` `identity` INT(10) NOT NULL DEFAULT '1' AFTER `remoteaddr`;

ALTER TABLE `app_logapi`
    ENGINE=InnoDB;