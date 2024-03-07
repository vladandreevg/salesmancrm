ALTER TABLE `app_ymail_messages`
CHANGE COLUMN `messageid` `messageid` VARCHAR(255) NULL DEFAULT NULL AFTER `subbolder`,
CHANGE COLUMN `uid` `uid` INT(10) NULL DEFAULT NULL AFTER `messageid`,
CHANGE COLUMN `fromm` `fromm` MEDIUMTEXT NULL DEFAULT NULL AFTER `parentmid`,
CHANGE COLUMN `fromname` `fromname` MEDIUMTEXT NULL DEFAULT NULL AFTER `fromm`,
CHANGE COLUMN `theme` `theme` VARCHAR(255) NULL DEFAULT NULL AFTER `fromname`,
CHANGE COLUMN `content` `content` LONGTEXT NULL DEFAULT NULL AFTER `theme`,
CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `content`,
CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `fid`;

ALTER TABLE `app_history`
CHANGE COLUMN `uid` `uid` VARCHAR(100) NULL AFTER `fid`;

UPDATE `app_dogovor`
SET pid = 0
	WHERE pid = '';
ALTER TABLE `app_dogovor`
CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL AFTER `payer`;

UPDATE `app_clientcat`
SET pid = NULL
	WHERE pid = '' OR pid = 0;
ALTER TABLE `app_clientcat`
CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL COMMENT 'основной контакт (pid в таблице _personcat.pid)' AFTER `fav`;

ALTER TABLE `app_smtp`
CHANGE COLUMN `smtp_host` `smtp_host` VARCHAR(255) NULL AFTER `active`,
CHANGE COLUMN `smtp_port` `smtp_port` INT(10) NULL AFTER `smtp_host`,
CHANGE COLUMN `smtp_auth` `smtp_auth` VARCHAR(5) NULL AFTER `smtp_port`,
CHANGE COLUMN `smtp_secure` `smtp_secure` VARCHAR(5) NULL AFTER `smtp_auth`,
CHANGE COLUMN `smtp_user` `smtp_user` VARCHAR(100) NULL AFTER `smtp_secure`,
CHANGE COLUMN `smtp_pass` `smtp_pass` VARCHAR(200) NULL AFTER `smtp_user`,
CHANGE COLUMN `smtp_from` `smtp_from` VARCHAR(255) NULL AFTER `smtp_pass`,
CHANGE COLUMN `smtp_protocol` `smtp_protocol` VARCHAR(5) NULL AFTER `smtp_from`,
CHANGE COLUMN `tip` `tip` VARCHAR(10) NULL AFTER `smtp_protocol`,
CHANGE COLUMN `name` `name` VARCHAR(255) NULL AFTER `tip`,
CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'id пользователя user.iduser' AFTER `name`;

ALTER TABLE `app_mycomps`
CHANGE COLUMN `name_ur` `name_ur` TEXT NULL DEFAULT NULL COMMENT 'полное наименование' AFTER `id`,
CHANGE COLUMN `name_shot` `name_shot` TEXT NULL DEFAULT NULL COMMENT 'сокращенное наименование' AFTER `name_ur`,
CHANGE COLUMN `address_yur` `address_yur` TEXT NULL DEFAULT NULL COMMENT 'юридические адрес' AFTER `name_shot`,
CHANGE COLUMN `address_post` `address_post` TEXT NULL DEFAULT NULL COMMENT 'почтовый адрес' AFTER `address_yur`,
CHANGE COLUMN `dir_name` `dir_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'в лице руководителя' AFTER `address_post`,
CHANGE COLUMN `dir_signature` `dir_signature` VARCHAR(255) NULL DEFAULT NULL COMMENT 'подпись руководителя' AFTER `dir_name`,
CHANGE COLUMN `dir_status` `dir_status` TEXT NULL DEFAULT NULL COMMENT 'должность руководителя' AFTER `dir_signature`,
CHANGE COLUMN `dir_osnovanie` `dir_osnovanie` TEXT NULL DEFAULT NULL COMMENT 'действующего на основаии' AFTER `dir_status`,
CHANGE COLUMN `innkpp` `innkpp` VARCHAR(255) NULL DEFAULT NULL COMMENT 'инн-кпп' AFTER `dir_osnovanie`,
CHANGE COLUMN `okog` `okog` VARCHAR(255) NULL DEFAULT NULL COMMENT 'окпо-огрн' AFTER `innkpp`,
CHANGE COLUMN `stamp` `stamp` VARCHAR(255) NULL DEFAULT NULL COMMENT 'файл с факсимилией' AFTER `okog`,
CHANGE COLUMN `logo` `logo` VARCHAR(255) NULL DEFAULT NULL COMMENT 'файл с логотипом' AFTER `stamp`;

ALTER TABLE `app_comments`
CHANGE COLUMN `mid` `mid` INT(10) NULL COMMENT 'DEPRECATED' AFTER `idparent`,
CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clientcat.clid' AFTER `datum`,
CHANGE COLUMN `pid` `pid` INT(10) NULL COMMENT 'personcat.pid' AFTER `clid`,
CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'dogovor.did' AFTER `pid`,
CHANGE COLUMN `prid` `prid` INT(10) NULL COMMENT 'price.n_id' AFTER `did`,
CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'user.iduser' AFTER `project`,
CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'заголовок' AFTER `iduser`,
CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'текст' AFTER `title`,
CHANGE COLUMN `fid` `fid` TEXT NULL COMMENT '_files.fid в виде списка с разделением ;' AFTER `content`,
CHANGE COLUMN `lastCommentDate` `lastCommentDate` DATETIME NULL COMMENT 'дата последнего коментария' AFTER `fid`,
CHANGE COLUMN `isClose` `isClose` VARCHAR(10) NULL DEFAULT 'no' COMMENT 'закрыто или открыты обсуждение' AFTER `lastCommentDate`,
CHANGE COLUMN `dateClose` `dateClose` DATETIME NULL COMMENT 'дата закрытия обсуждения' AFTER `isClose`;

ALTER TABLE `app_comments`
ADD INDEX `clid` (`clid`),
ADD INDEX `pid` (`pid`),
ADD INDEX `did` (`did`),
ADD INDEX `project` (`project`),
ADD INDEX `iduser` (`iduser`);

UPDATE app_comments SET lastCommentDate = NULL WHERE lastCommentDate = '0000-00-00 00:00:00';
UPDATE app_comments SET dateClose = NULL WHERE dateClose = '0000-00-00 00:00:00';

ALTER TABLE `app_settings`
CHANGE COLUMN `acs_view` `acs_view` VARCHAR(3) NULL DEFAULT 'on' AFTER `logo`,
CHANGE COLUMN `complect_on` `complect_on` VARCHAR(3) NULL DEFAULT 'no' AFTER `acs_view`,
CHANGE COLUMN `zayavka_on` `zayavka_on` VARCHAR(3) NULL DEFAULT 'no' AFTER `complect_on`,
CHANGE COLUMN `contract_format` `contract_format` VARCHAR(255) NULL AFTER `zayavka_on`,
CHANGE COLUMN `contract_num` `contract_num` INT(10) NULL AFTER `contract_format`,
CHANGE COLUMN `inum` `inum` INT(10) NULL AFTER `contract_num`,
CHANGE COLUMN `iformat` `iformat` VARCHAR(255) NULL AFTER `inum`,
CHANGE COLUMN `akt_num` `akt_num` VARCHAR(20) NULL DEFAULT '0' AFTER `iformat`,
CHANGE COLUMN `akt_step` `akt_step` INT(10) NULL AFTER `akt_num`,
CHANGE COLUMN `api_key` `api_key` VARCHAR(255) NULL AFTER `akt_step`,
CHANGE COLUMN `coordinator` `coordinator` INT(10) NULL AFTER `api_key`,
CHANGE COLUMN `timezone` `timezone` VARCHAR(255) NULL DEFAULT 'Asia/Yekaterinburg' COMMENT 'Временная зона' AFTER `coordinator`,
CHANGE COLUMN `ivc` `ivc` VARCHAR(255) NULL AFTER `timezone`,
CHANGE COLUMN `dFormat` `dFormat` VARCHAR(255) NULL AFTER `ivc`,
CHANGE COLUMN `dNum` `dNum` VARCHAR(255) NULL AFTER `dFormat`;

ALTER TABLE `app_mycomps_recv`
CHANGE COLUMN `title` `title` TEXT NULL COMMENT 'назваине р.с' AFTER `cid`,
CHANGE COLUMN `rs` `rs` VARCHAR(50) NULL COMMENT 'р.с' AFTER `title`,
CHANGE COLUMN `bankr` `bankr` TEXT NULL COMMENT 'бик, кур. счет и название банка' AFTER `rs`,
CHANGE COLUMN `tip` `tip` VARCHAR(6) NULL DEFAULT 'bank' COMMENT 'bank-kassa' AFTER `bankr`,
CHANGE COLUMN `ostatok` `ostatok` DOUBLE(20,2) NULL COMMENT 'остаток средств' AFTER `tip`,
CHANGE COLUMN `bloc` `bloc` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'заблокирован или нет счет' AFTER `ostatok`,
CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL DEFAULT 'no' COMMENT 'использутся по умолчанию или нет' AFTER `bloc`,
CHANGE COLUMN `ndsDefault` `ndsDefault` VARCHAR(5) NULL DEFAULT '0' COMMENT 'размер ндс по умолчанию' AFTER `isDefault`;

ALTER TABLE `app_callhistory`
CHANGE COLUMN `res` `res` VARCHAR(100) NULL AFTER `iduser`;

ALTER TABLE `app_ymail_messages`
CHANGE COLUMN `state` `state` VARCHAR(50) NULL DEFAULT 'unread' COMMENT 'deleted - удаленные, read - прочитанные, unread - не прочинанны' AFTER `priority`;

ALTER TABLE `app_file_cat`
CHANGE COLUMN `subid` `subid` INT(10) NULL DEFAULT NULL AFTER `idcategory`,
CHANGE COLUMN `title` `title` VARCHAR(250) NULL DEFAULT NULL AFTER `subid`,
CHANGE COLUMN `shared` `shared` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'общая папка (yes)' AFTER `title`;

ALTER TABLE `app_budjet_bank`
CHANGE COLUMN `fromINN` `fromINN` VARCHAR(12) NULL DEFAULT NULL COMMENT 'инн плательщика' AFTER `fromRS`,
CHANGE COLUMN `toINN` `toINN` VARCHAR(12) NULL DEFAULT NULL COMMENT 'инн получателя' AFTER `toRS`;

ALTER TABLE `app_ymail_messages`
CHANGE COLUMN `datum` `datum` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата сообщения' AFTER `id`;