SET sql_mode='NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES';

UPDATE `app_budjet` SET datum = NULL WHERE datum = '0000-00-00 00:00:00';

ALTER TABLE `app_budjet`
    ADD COLUMN `date_plan` DATE NULL DEFAULT NULL COMMENT 'плановая дата' AFTER `partid`,
    ADD COLUMN `invoice` VARCHAR(255) NULL DEFAULT NULL COMMENT 'номер счета' AFTER `date_plan`,
    ADD COLUMN `invoice_date` DATE NULL DEFAULT NULL COMMENT 'дата счета' AFTER `invoice`;

ALTER TABLE `app_budjet`
    ADD COLUMN `invoice_paydate` DATE NULL DEFAULT NULL COMMENT 'дата оплаты счета' AFTER `invoice_date`;

CREATE TABLE `app_budjetlog` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
    `status` VARCHAR(10) NULL DEFAULT NULL COMMENT 'статус расхода',
    `bjid` INT(10) NULL DEFAULT NULL COMMENT 'id расхода',
    `iduser` INT(10) NULL DEFAULT NULL COMMENT 'id пользователя user.iduser внес изменение',
    `comment` VARCHAR(255) NULL DEFAULT NULL COMMENT 'комментарий',
    `identity` INT(10) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `status` (`status`) USING BTREE,
    INDEX `bjid` (`bjid`) USING BTREE
)
    COMMENT='Лог изменений статуса расходов'
    ENGINE=InnoDB;