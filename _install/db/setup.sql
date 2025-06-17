DROP TABLE IF EXISTS `app_activities`;#%%
CREATE TABLE `app_activities` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(20) NOT NULL COMMENT 'название',
    `color` VARCHAR(7) NULL  DEFAULT  NULL COMMENT 'цвет в RGB',
    `icon` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'иконка',
    `resultat` TEXT NULL  COMMENT 'список готовых реультатов, разделенный ;',
    `isDefault` VARCHAR(6) NULL  DEFAULT  NULL COMMENT 'признак дефолтности',
    `aorder` INT NULL  COMMENT 'порядок вывода',
    `filter` VARCHAR(255) NULL  DEFAULT 'all' COMMENT 'признак применимости (all - универсальный, task - только для задач, history - только для активностей',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `title` (`title`),
   INDEX `identity` (`identity`)
)  COMMENT='Типы активностей'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_activities` VALUES ('1','Первичный звонок','#009900',NULL,'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП','','8','all','1');#%%
INSERT INTO `app_activities` VALUES ('2','Факс','#cc00cc',NULL,'Отправлен и получен;Отправлен;Не отвечает;Не принимают','','16','activ','1');#%%
INSERT INTO `app_activities` VALUES ('3','Встреча','#ffcc00',NULL,'Состоялась;Перенос сроков;Отменена;Отпала необходимость','','5','all','1');#%%
INSERT INTO `app_activities` VALUES ('4','Задача','#ff6600',NULL,'Не выполнено;Перенос сроков;Отложено;Выполнено','yes','6','all','1');#%%
INSERT INTO `app_activities` VALUES ('5','Предложение','#66ccff',NULL,'Перенос;Отправлено КП;Отменено','','14','activ','1');#%%
INSERT INTO `app_activities` VALUES ('6','Событие','#666699',NULL,'Выполнено;Перенос;Отложено','','15','activ','1');#%%
INSERT INTO `app_activities` VALUES ('7','исх.Почта','#cccc00',NULL,'Отправлено КП;Отправлен Договор;Отправлена Презентация;Отправлена информация','','11','all','1');#%%
INSERT INTO `app_activities` VALUES ('8','вх.Звонок','#99cc00',NULL,'Новое обращение;Запрос счета;Запрос КП;Приглашение;Договорились о встрече','','7','all','1');#%%
INSERT INTO `app_activities` VALUES ('9','вх.Почта','#cc3300',NULL,'Отправлено;Не верный адрес;Отложено;Отменено','','10','all','1');#%%
INSERT INTO `app_activities` VALUES ('10','Поздравление','#009999',NULL,'Новый год;День Рождения;Праздник','','13','task','1');#%%
INSERT INTO `app_activities` VALUES ('11','исх.2.Звонок','#339966',NULL,'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП','','9','all','1');#%%
INSERT INTO `app_activities` VALUES ('12','Отправка КП','#ff0000',NULL,'Отправлено;Перенесено;Отложено;Отменено','','12','all','1');#%%


DROP TABLE IF EXISTS `app_budjet`;#%%
CREATE TABLE `app_budjet` (
    `id` INT NOT NULL auto_increment,
    `cat` INT NULL  COMMENT 'категория записи, ссылается на id в таблице _budjet_cat',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название расхода-дохода',
    `des` TEXT NULL  COMMENT 'описание',
    `year` INT NULL  COMMENT 'год',
    `mon` INT NULL  COMMENT 'месяц',
    `summa` DOUBLE(20,2) NULL  COMMENT 'сумма',
    `datum` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP,
    `iduser` INT NULL  COMMENT 'id пользователя _user.iduser',
    `do` VARCHAR(3) NULL  DEFAULT  NULL COMMENT 'признак того, что расход проведен',
    `rs` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'id расчетного счета из таблицы _mycomps_recv.id',
    `rs2` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'id расчетного счета (используется при перемещении средств)',
    `fid` TEXT NULL  COMMENT 'id файлов из таблицы _files.fid разделенного запятой',
    `did` INT NULL  COMMENT 'id сделки из таблицы _dogovor.did',
    `conid` INT NULL  COMMENT '_clientcat.clid для поставщиков',
    `partid` INT NULL  COMMENT '_clientcat.clid для партнеров',
    `date_plan` DATE NULL  COMMENT 'плановая дата',
    `invoice` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'номер счета',
    `invoice_date` DATE NULL  COMMENT 'дата счета',
    `invoice_paydate` DATE NULL  COMMENT 'дата оплаты счета',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Бюджет. Журнал расходов'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_budjet_bank`;#%%
CREATE TABLE `app_budjet_bank` (
    `id` INT NOT NULL auto_increment,
    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'метка времени',
    `number` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'номер документа',
    `datum` DATE NULL  COMMENT 'дата проводки',
    `mon` VARCHAR(2) NULL  DEFAULT  NULL COMMENT 'месяц',
    `year` VARCHAR(4) NULL  DEFAULT  NULL COMMENT 'год',
    `tip` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'направление расхода - dohod, rashod',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название расхода',
    `content` TEXT NULL  COMMENT 'описание расхода',
    `rs` INT NULL  COMMENT 'id расчетного счета',
    `from` TEXT NULL  COMMENT 'название плательщика',
    `fromRS` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'р.с. плательщика',
    `fromINN` VARCHAR(12) NULL  DEFAULT  NULL COMMENT 'инн плательщика',
    `to` TEXT NULL  COMMENT 'название получателя',
    `toRS` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'р.с. получателя',
    `toINN` VARCHAR(12) NULL  DEFAULT  NULL COMMENT 'инн получателя',
    `summa` FLOAT(20,2) NULL  COMMENT 'сумма расхода',
    `clid` INT NULL  COMMENT 'id связанного клиента',
    `bid` INT NULL  COMMENT 'id связанной записи в бюджете',
    `category` INT NULL  COMMENT 'id статьи расхода',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Журнал банковской выписки'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_budjet_cat`;#%%
CREATE TABLE `app_budjet_cat` (
    `id` INT NOT NULL auto_increment,
    `subid` INT NULL  COMMENT 'ид основной записи budjet_cat.id',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название',
    `tip` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'тип (расход-доход)',
    `clientpath` INT NULL  COMMENT 'id канала',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Статьи расхода-дохода Бюджета'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_budjet_cat` VALUES ('1','0','Расходы на офис','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('2','1','Аренда офиса','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('3','1','Телефония','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('4','0','Прочие поступления','dohod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('5','4','Инвестиции','dohod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('7','1','Продукты питания','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('8','1','Оборудование','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('9','0','Сотрудники','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('10','9','Зарплата','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('11','9','Премия','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('12','9','Командировочные','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('13','4','Наличка','dohod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('14','0','Реклама','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('15','14','Интернет-реклама','rashod','177','1');#%%
INSERT INTO `app_budjet_cat` VALUES ('16','14','Вебинары','rashod','86','1');#%%
INSERT INTO `app_budjet_cat` VALUES ('17','14','Direct Mail','rashod','160','1');#%%
INSERT INTO `app_budjet_cat` VALUES ('18','0','Расчеты с контрагентами','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('19','18','Поставщики','rashod',NULL,'1');#%%
INSERT INTO `app_budjet_cat` VALUES ('20','18','Партнеры','rashod',NULL,'1');#%%


DROP TABLE IF EXISTS `app_budjetlog`;#%%
CREATE TABLE `app_budjetlog` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
    `status` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'статус расхода',
    `bjid` INT NULL  COMMENT 'id расхода',
    `iduser` INT NULL  COMMENT 'id пользователя user.iduser внес изменение',
    `comment` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'комментарий',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `status` (`status`),
   INDEX `bjid` (`bjid`)
)  COMMENT='Лог изменений статуса расходов'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_callhistory`;#%%
CREATE TABLE `app_callhistory` (
    `id` INT NOT NULL auto_increment,
    `uid` VARCHAR(255) NOT NULL COMMENT 'UID звонка из Астериска',
    `did` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'номер телефона наш если в src добавочный',
    `phone` VARCHAR(20) NOT NULL COMMENT 'телефон клиента всегда',
    `direct` VARCHAR(10) NOT NULL COMMENT 'направление вызова',
    `datum` DATETIME NOT NULL COMMENT 'дата-время вызова',
    `clid` INT NULL  COMMENT 'clid в таблице _clientcat.clid',
    `pid` INT NULL  COMMENT 'pid в таблице _personcat.pid',
    `iduser` INT NULL  COMMENT 'id пользователя user.iduser',
    `res` VARCHAR(100) NOT NULL COMMENT 'результат вызова',
    `sec` INT NOT NULL COMMENT 'продолжительность',
    `file` TEXT NULL  COMMENT 'имя файла',
    `src` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'источник звонка',
    `dst` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'назначение звонка',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `statistica` (`phone`, `datum`, `iduser`, `res`, `direct`, `identity`),
   INDEX `statistica2` (`direct`, `datum`, `iduser`, `identity`)
)  COMMENT='История звонков'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_capacity_client`;#%%
CREATE TABLE `app_capacity_client` (
    `id` INT NOT NULL auto_increment,
    `capid` INT NULL  COMMENT 'не используется',
    `clid` INT NULL  COMMENT 'clid в таблице _clientcat.clid',
    `direction` INT NULL  COMMENT 'направление деятельности из таблицы _direction.id',
    `year` INT NULL  COMMENT 'план на какой год',
    `mon` INT NULL  COMMENT 'план на какой месяц',
    `sumplan` DOUBLE(20,2) NULL  COMMENT 'план продаж в указанном периоде данному клиенту по данному направлению',
    `sumfact` DOUBLE(20,2) NULL  COMMENT 'факт продаж, при закрытии сделки суммируются',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Потенциал клиента'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_category`;#%%
CREATE TABLE `app_category` (
    `idcategory` INT NOT NULL auto_increment,
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'название отрасли',
    `tip` VARCHAR(10) NOT NULL DEFAULT 'client' COMMENT 'к какой записи относится (client,person,contractor,partner,concurent)',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`)
)  COMMENT='Отрасли'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_category` VALUES ('1','Физические лица','client','1');#%%
INSERT INTO `app_category` VALUES ('2','Розница','client','1');#%%


DROP TABLE IF EXISTS `app_changepass`;#%%
CREATE TABLE `app_changepass` (
    `id` INT NOT NULL auto_increment,
    `useremail` VARCHAR(255) NOT NULL,
    `code` VARCHAR(255) NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Хранение данных для смены пароля пользователя'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_clientcat`;#%%
CREATE TABLE `app_clientcat` (
    `clid` INT NOT NULL auto_increment,
    `uid` VARCHAR(30) NULL  DEFAULT  NULL,
    `title` VARCHAR(250) NULL  DEFAULT  NULL,
    `idcategory` INT NULL  DEFAULT '0',
    `iduser` VARCHAR(10) NULL  DEFAULT '0',
    `clientpath` INT NULL  DEFAULT '0',
    `des` TEXT NULL ,
    `address` TEXT NULL ,
    `phone` VARCHAR(250) NULL  DEFAULT  NULL,
    `fax` VARCHAR(250) NULL  DEFAULT  NULL,
    `site_url` VARCHAR(250) NULL  DEFAULT  NULL,
    `mail_url` VARCHAR(250) NULL  DEFAULT  NULL,
    `trash` VARCHAR(10) NULL  DEFAULT 'no',
    `fav` VARCHAR(20) NULL  DEFAULT 'no',
    `pid` INT NULL  DEFAULT '0',
    `head_clid` INT NULL  DEFAULT '0',
    `scheme` TEXT NULL ,
    `tip_cmr` VARCHAR(255) NULL  DEFAULT  NULL,
    `territory` INT NULL  DEFAULT '0',
    `input1` VARCHAR(255) NULL  DEFAULT  NULL,
    `input2` VARCHAR(255) NULL  DEFAULT  NULL,
    `input3` VARCHAR(255) NULL  DEFAULT  NULL,
    `input4` VARCHAR(255) NULL  DEFAULT  NULL,
    `input5` VARCHAR(255) NULL  DEFAULT  NULL,
    `input6` VARCHAR(255) NULL  DEFAULT  NULL,
    `input7` VARCHAR(255) NULL  DEFAULT  NULL,
    `input8` VARCHAR(255) NULL  DEFAULT  NULL,
    `input9` VARCHAR(255) NULL  DEFAULT  NULL,
    `input10` VARCHAR(255) NULL  DEFAULT  NULL,
    `date_create` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP,
    `date_edit` TIMESTAMP NULL ,
    `creator` INT NULL  DEFAULT '0',
    `editor` INT NULL  DEFAULT '0',
    `recv` TEXT NULL ,
    `dostup` VARCHAR(255) NULL  DEFAULT  NULL,
    `last_dog` DATE NULL ,
    `last_hist` DATETIME NULL ,
    `type` VARCHAR(100) NULL  DEFAULT 'client',
    `priceLevel` VARCHAR(255) NULL  DEFAULT 'price_1',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`clid`),
   INDEX `iduser` (`iduser`),
   INDEX `identity` (`identity`),
   INDEX `trash` (`trash`),
   INDEX `uid` (`uid`),
   INDEX `phone` (`phone`),
   INDEX `fax` (`fax`),
   INDEX `mail_url` (`mail_url`),
   INDEX `type` (`type`),
   FULLTEXT INDEX `title` (`title`)
)  COMMENT='Клиенты'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_clientpath`;#%%
CREATE TABLE `app_clientpath` (
    `id` INT NOT NULL auto_increment,
    `name` VARCHAR(255) NOT NULL COMMENT 'Название источника',
    `isDefault` VARCHAR(6) NULL  DEFAULT  NULL COMMENT 'Дефолтный признак',
    `utm_source` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Связка с источником',
    `destination` VARCHAR(12) NULL  DEFAULT  NULL COMMENT 'Связка с номером телефона',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Источник клиента'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_clientpath` VALUES ('1','Личные связи','','','','1');#%%
INSERT INTO `app_clientpath` VALUES ('2','Маркетинг','','','','1');#%%
INSERT INTO `app_clientpath` VALUES ('3','Справочник','','','','1');#%%
INSERT INTO `app_clientpath` VALUES ('4','Заказ с сайта','yes','','','1');#%%
INSERT INTO `app_clientpath` VALUES ('5','Рекомендации клиентов','','fromfriend','','1');#%%


DROP TABLE IF EXISTS `app_comments`;#%%
CREATE TABLE `app_comments` (
    `id` INT NOT NULL auto_increment,
    `idparent` INT NULL  DEFAULT '0' COMMENT 'comments.id -- ссылка на тему обсуждения',
    `mid` INT NULL  COMMENT 'DEPRECATED',
    `datum` TIMESTAMP NULL ,
    `clid` INT NULL  COMMENT 'clientcat.clid',
    `pid` INT NULL  COMMENT 'personcat.pid',
    `did` INT NULL  COMMENT 'dogovor.did',
    `prid` INT NULL  COMMENT 'price.n_id',
    `project` INT NULL  COMMENT 'id проекта',
    `iduser` INT NULL  COMMENT 'user.iduser',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'заголовок',
    `content` TEXT NULL  COMMENT 'текст',
    `fid` TEXT NULL  COMMENT '_files.fid в виде списка с разделением ;',
    `lastCommentDate` DATETIME NULL  COMMENT 'дата последнего коментария',
    `isClose` VARCHAR(10) NULL  DEFAULT 'no' COMMENT 'закрыто или открыты обсуждение',
    `dateClose` DATETIME NULL  COMMENT 'дата закрытия обсуждения',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `mid` (`mid`),
   INDEX `idparent` (`idparent`),
   INDEX `isClose` (`isClose`),
   INDEX `clid` (`clid`),
   INDEX `pid` (`pid`),
   INDEX `did` (`did`),
   INDEX `project` (`project`),
   INDEX `iduser` (`iduser`)
)  COMMENT='Модуль Обсуждения'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_comments_subscribe`;#%%
CREATE TABLE `app_comments_subscribe` (
    `id` INT NOT NULL auto_increment,
    `idcomment` INT NULL  COMMENT 'тема обсуждения _comments.id',
    `iduser` INT NULL  COMMENT 'пользователь _user.iduser',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `idcomment` (`idcomment`),
   INDEX `iduser` (`iduser`)
)  COMMENT='модуль Обсуждения - участники обсуждения'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_complect`;#%%
CREATE TABLE `app_complect` (
    `id` INT NOT NULL auto_increment,
    `did` INT NULL  COMMENT 'сделка _dogovor.id',
    `ccid` INT NULL  COMMENT 'тип контрольной точки _complect_cat.ccid',
    `data_plan` DATE NULL  COMMENT 'плановая дата',
    `data_fact` DATE NULL  COMMENT 'факт. дата выполнения',
    `doit` VARCHAR(5) NOT NULL DEFAULT 'no' COMMENT 'признак выполнения',
    `iduser` INT NULL  COMMENT 'пользователь, выполнивший КТ _user.iduser',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Контрольные точки'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_complect_cat`;#%%
CREATE TABLE `app_complect_cat` (
    `ccid` INT NOT NULL auto_increment,
    `title` VARCHAR(200) NULL  DEFAULT  NULL,
    `corder` INT NULL ,
    `dstep` INT NULL ,
    `role` TEXT NULL  COMMENT 'список должностей, которым доступно изменение контр.точки в виде списка с разделением ,',
    `users` TEXT NULL  COMMENT 'список сотрудников, которым доступно изменение контр.точки usser.iduser в виде списка с разделением ,',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`ccid`)
)  COMMENT='Модуль Контрольные точки. База'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_complect_cat` VALUES ('1','Получение оплаты','4','7','','','1');#%%
INSERT INTO `app_complect_cat` VALUES ('2','Подписать договор, Выставить счет','3','6','','1','1');#%%
INSERT INTO `app_complect_cat` VALUES ('3','Согласование спецификации','2','5','','1','1');#%%
INSERT INTO `app_complect_cat` VALUES ('4','Начать работы','5','0','','','1');#%%
INSERT INTO `app_complect_cat` VALUES ('5','Получить документы','6','8','Руководитель организации,Руководитель подразделения,Менеджер продаж','','1');#%%
INSERT INTO `app_complect_cat` VALUES ('6','Подготовка и отправка КП','1','11','Руководитель подразделения','','1');#%%
INSERT INTO `app_complect_cat` VALUES ('7','Работы выполнены','7','0','Руководитель организации,Руководитель подразделения,Руководитель отдела','','1');#%%


DROP TABLE IF EXISTS `app_contract`;#%%
CREATE TABLE `app_contract` (
    `deid` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP,
    `number` VARCHAR(255) NULL  DEFAULT  NULL,
    `datum_start` DATE NULL ,
    `datum_end` DATE NULL ,
    `des` TEXT NULL ,
    `clid` INT NULL  DEFAULT '0',
    `payer` INT NULL ,
    `pid` INT NULL  DEFAULT '0',
    `did` INT NULL ,
    `ftitle` VARCHAR(255) NULL  DEFAULT  NULL,
    `fname` VARCHAR(250) NULL  DEFAULT  NULL,
    `ftype` VARCHAR(250) NULL  DEFAULT  NULL,
    `iduser` INT NULL ,
    `title` TEXT NULL ,
    `idtype` INT NULL  DEFAULT '0',
    `crid` INT NULL  DEFAULT '0',
    `mcid` INT NULL  DEFAULT '0',
    `signer` INT NULL  DEFAULT '0',
    `status` INT NULL  DEFAULT '0',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`deid`),
   INDEX `did_iduser` (`did`, `iduser`)
)  COMMENT='Документы'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_contract_poz`;#%%
CREATE TABLE `app_contract_poz` (
    `id` INT NOT NULL auto_increment,
    `deid` INT NOT NULL DEFAULT '0' COMMENT 'id документа (_contract.deid)',
    `did` INT NULL  DEFAULT '0' COMMENT 'id сделки',
    `spid` INT NOT NULL DEFAULT '0' COMMENT 'id позиции спецификации',
    `prid` INT NULL  DEFAULT '0' COMMENT 'id позиции прайса',
    `kol` DOUBLE(20,4) NULL  DEFAULT '0.0000' COMMENT 'количество товара',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Позиции спецификации для Актов'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_contract_status`;#%%
CREATE TABLE `app_contract_status` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
    `tip` TEXT NULL  COMMENT 'типы документов',
    `title` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'название статуса',
    `color` VARCHAR(7) NULL  DEFAULT  NULL COMMENT 'цвет статуса',
    `ord` INT NULL  COMMENT 'порядок вывода статуса',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Статусы документов по типам'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_contract_statuslog`;#%%
CREATE TABLE `app_contract_statuslog` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deid` INT NULL  COMMENT 'id документа',
    `status` INT NULL  COMMENT 'новый статус',
    `oldstatus` INT NULL  COMMENT 'старый статус',
    `iduser` INT NULL  COMMENT 'id сотрудника',
    `des` TEXT NULL  COMMENT 'комментарий',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Лог изменения статуса документов'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_contract_temp`;#%%
CREATE TABLE `app_contract_temp` (
    `id` INT NOT NULL auto_increment,
    `typeid` INT NULL ,
    `title` VARCHAR(255) NULL  DEFAULT  NULL,
    `file` VARCHAR(255) NULL  DEFAULT  NULL,
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Шаблоны документов (кроме счетов и актов)'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_contract_temp` VALUES ('1','1','Квитанция на оплату','kvitancia_sberbank_pd4.docx','1');#%%
INSERT INTO `app_contract_temp` VALUES ('2','4','Базовый шаблон','invoice.tpl','1');#%%
INSERT INTO `app_contract_temp` VALUES ('3','2','Приёма-передачи. Права','akt_prava.tpl','1');#%%
INSERT INTO `app_contract_temp` VALUES ('4','2','Приёма-передачи. Услуги','akt_simple.tpl','1');#%%
INSERT INTO `app_contract_temp` VALUES ('5','2','Приёма-передачи. Услуги (расширенный)','akt_full.tpl','1');#%%
INSERT INTO `app_contract_temp` VALUES ('6','3','Счет-фактура (XLCX)','schet_faktura.xlsx','1');#%%
INSERT INTO `app_contract_temp` VALUES ('7','4','Счет с QRcode','invoice_qr.tpl','1');#%%


DROP TABLE IF EXISTS `app_contract_type`;#%%
CREATE TABLE `app_contract_type` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название документа',
    `type` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'внутренний тип get_akt, get_aktper, get_dogovor, invoice',
    `role` TEXT NULL  COMMENT 'список ролей, которым доступно изменение',
    `users` TEXT NULL  COMMENT 'список пользователей, которые могут добавлять такие документы -- user.iduser с разделением ,',
    `num` INT NULL  COMMENT 'счетчик нумерации',
    `format` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'шаблон формата номера',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Типы документа'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_contract_type` VALUES ('1','Квитанция в банк','','','','0','{cnum}','1');#%%
INSERT INTO `app_contract_type` VALUES ('2','Акт приема-передачи','get_akt','','','0','{cnum}','1');#%%
INSERT INTO `app_contract_type` VALUES ('3','Счет-фактура','','','','0','{cnum}','1');#%%
INSERT INTO `app_contract_type` VALUES ('4','Счет','invoice','','','0','','1');#%%
INSERT INTO `app_contract_type` VALUES ('5','Договор','get_dogovor','','','0','','1');#%%


DROP TABLE IF EXISTS `app_credit`;#%%
CREATE TABLE `app_credit` (
    `crid` INT NOT NULL auto_increment,
    `did` INT NULL  DEFAULT '0',
    `clid` INT NULL  DEFAULT '0',
    `pid` INT NULL  DEFAULT '0',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `datum_credit` DATE NULL ,
    `summa_credit` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `nds_credit` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `iduser` INT NULL  DEFAULT '0',
    `idowner` INT NULL  DEFAULT '0',
    `do` VARCHAR(5) NULL  DEFAULT 'no',
    `invoice` VARCHAR(20) NULL  DEFAULT  NULL,
    `invoice_chek` VARCHAR(40) NULL  DEFAULT  NULL,
    `invoice_date` DATE NULL ,
    `rs` INT NULL  DEFAULT '0',
    `tip` VARCHAR(255) NULL  DEFAULT  NULL,
    `template` INT NULL  DEFAULT '0',
    `suffix` TEXT NULL ,
    `signer` INT NULL  DEFAULT '0',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`crid`),
   INDEX `do` (`do`),
   INDEX `did` (`did`),
   INDEX `clid` (`clid`),
   INDEX `iduser` (`iduser`),
   INDEX `datum_credit` (`datum_credit`)
)  COMMENT='Счета'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_currency`;#%%
CREATE TABLE `app_currency` (
    `id` INT NOT NULL auto_increment,
    `datum` DATE NULL  COMMENT 'дата добавления',
    `name` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'название валюты',
    `view` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'отображаемое название валюты',
    `code` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'код валюты',
    `course` DOUBLE(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'текущий курс',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `id` (`id`)
)  COMMENT='Таблица курсов валют'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_currency_log`;#%%
CREATE TABLE `app_currency_log` (
    `id` INT NOT NULL auto_increment,
    `idcurrency` INT NULL  COMMENT 'id записи валюты',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
    `course` DOUBLE(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'курс на дату',
    `iduser` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'сотрудник, который выполнил действие',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `id` (`id`)
)  COMMENT='Таблица изменения курсов валют'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_customsettings`;#%%
CREATE TABLE `app_customsettings` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время добавления-изменения',
    `tip` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'тип параметра',
    `params` TEXT NULL  COMMENT 'параметры',
    `iduser` INT NULL  COMMENT 'id сотрудника',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Хранилище различных настроек'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_customsettings` VALUES ('1','2021-10-28 22:41:02','eform','{\"client\":{\"title\":{\"active\":\"yes\",\"requered\":\"yes\",\"more\":\"no\"},\"head_clid\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"phone\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"idcategory\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mail_url\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"site_url\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"address\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"fax\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"tip_cmr\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"clientpath\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"territory\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input1\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input3\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input4\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"des\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input2\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input5\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input6\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input7\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input9\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input8\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input10\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"}},\"person\":{\"ptitle\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"person\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"rol\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"tel\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input7\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mob\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mail\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"loyalty\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input1\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input3\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input4\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input5\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input6\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input12\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"}}}',NULL,'1');#%%
INSERT INTO `app_customsettings` VALUES ('2','2020-08-13 10:14:27','settingsMore','{\"timecheck\":\"yes\",\"budjetEnableVijets\":\"no\"}','1','1');#%%


DROP TABLE IF EXISTS `app_deal_anketa`;#%%
CREATE TABLE `app_deal_anketa` (
    `id` INT NOT NULL auto_increment,
    `idbase` INT NOT NULL DEFAULT '0' COMMENT 'id поля анкеты',
    `ida` INT NOT NULL COMMENT 'id анкеты',
    `did` INT NULL  COMMENT 'id сделки',
    `clid` INT NULL  COMMENT 'id клиента',
    `value` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'варианты значений',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Значения для анкет по сделкам'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_deal_anketa_base`;#%%
CREATE TABLE `app_deal_anketa_base` (
    `id` INT NOT NULL auto_increment,
    `block` INT NOT NULL DEFAULT '0' COMMENT 'id блока',
    `ida` INT NOT NULL COMMENT 'id анкеты',
    `name` VARCHAR(255) NOT NULL COMMENT 'Название поля',
    `tip` VARCHAR(10) NOT NULL COMMENT 'Тип поля',
    `value` TEXT NULL  COMMENT 'Возможные значения',
    `ord` INT NULL  COMMENT 'Порядок вывода',
    `pole` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'id поля',
    `pwidth` INT NULL  DEFAULT '50' COMMENT 'ширина поля',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='База полей для анкеты'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_deal_anketa_list`;#%%
CREATE TABLE `app_deal_anketa_list` (
    `id` INT NOT NULL auto_increment,
    `active` INT NOT NULL DEFAULT '1' COMMENT 'Активность анкеты',
    `datum` DATETIME NOT NULL COMMENT 'Дата создания',
    `datum_edit` DATETIME NOT NULL COMMENT 'Дата изменения',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Название анкеты',
    `content` TEXT NULL  COMMENT 'Описание анкеты',
    `iduser` INT NULL  COMMENT 'id Сотрудника-автора',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Список базовых анкет для сделок'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_direction`;#%%
CREATE TABLE `app_direction` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NOT NULL COMMENT 'название',
    `isDefault` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'признак дефолтности',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Направления деятельности'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_direction` VALUES ('1','Основное','yes','1');#%%


DROP TABLE IF EXISTS `app_dogcategory`;#%%
CREATE TABLE `app_dogcategory` (
    `idcategory` BIGINT NOT NULL auto_increment,
    `title` INT NULL  COMMENT 'название типа',
    `content` TEXT NULL  COMMENT 'описание',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`),
   INDEX `identity` (`identity`),
   INDEX `title` (`title`),
   INDEX `identity_2` (`identity`),
   INDEX `title_2` (`title`)
)  COMMENT='Этапы сделок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_dogcategory` VALUES ('2','20','Подтвержден интерес','1');#%%
INSERT INTO `app_dogcategory` VALUES ('5','60','Обсуждение деталей - продукты, услуги, оплата','1');#%%
INSERT INTO `app_dogcategory` VALUES ('6','80','Согласован договор, Выставлен счет','1');#%%
INSERT INTO `app_dogcategory` VALUES ('7','90','Получена предоплата, Выполнение договора','1');#%%
INSERT INTO `app_dogcategory` VALUES ('8','100','Закрытие сделки, Подписание документов','1');#%%
INSERT INTO `app_dogcategory` VALUES ('10','0','Проявлен/Выявлен интерес','1');#%%
INSERT INTO `app_dogcategory` VALUES ('11','40','Отправлено КП','1');#%%


DROP TABLE IF EXISTS `app_dogovor`;#%%
CREATE TABLE `app_dogovor` (
    `did` INT NOT NULL auto_increment,
    `uid` VARCHAR(30) NULL  DEFAULT  NULL,
    `idcategory` INT NULL  DEFAULT '0',
    `clid` INT NULL  DEFAULT '0',
    `payer` INT NULL  DEFAULT '0',
    `pid` INT NULL  DEFAULT '0',
    `datum` DATE NULL ,
    `autor` INT NULL  DEFAULT '0',
    `datum_plan` DATE NULL ,
    `title` TEXT NULL ,
    `content` TEXT NULL ,
    `tip` VARCHAR(100) NULL  DEFAULT '0',
    `kol` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `close` VARCHAR(5) NULL  DEFAULT 'no',
    `lat` FLOAT(10,6) NULL ,
    `lan` FLOAT(10,6) NULL ,
    `adres` TEXT NULL ,
    `iduser` INT NULL  DEFAULT '0',
    `datum_izm` DATE NULL ,
    `datum_close` DATE NULL ,
    `sid` INT NULL  DEFAULT '0',
    `kol_fact` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `des_fact` TEXT NULL ,
    `coid` VARCHAR(12) NULL  DEFAULT  NULL,
    `co_kol` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `coid1` TEXT NULL ,
    `coid2` VARCHAR(12) NULL  DEFAULT  NULL,
    `dog_num` TEXT NULL ,
    `marga` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `calculate` VARCHAR(4) NULL  DEFAULT  NULL,
    `isFrozen` INT NULL  DEFAULT '0',
    `datum_start` DATE NULL ,
    `datum_end` DATE NULL ,
    `pid_list` VARCHAR(255) NULL  DEFAULT  NULL,
    `partner` VARCHAR(100) NULL  DEFAULT  NULL,
    `zayavka` VARCHAR(200) NULL  DEFAULT  NULL,
    `ztitle` VARCHAR(255) NULL  DEFAULT  NULL,
    `mcid` INT NULL  DEFAULT '0',
    `direction` INT NULL  DEFAULT '0',
    `idcurrency` INT NULL  DEFAULT '0',
    `idcourse` INT NULL  DEFAULT '0',
    `akt_date` DATE NULL ,
    `akt_temp` VARCHAR(200) NULL  DEFAULT  NULL,
    `lid` INT NULL  DEFAULT '0',
    `input1` VARCHAR(512) NULL  DEFAULT  NULL,
    `input2` VARCHAR(512) NULL  DEFAULT  NULL,
    `input3` VARCHAR(512) NULL  DEFAULT  NULL,
    `input4` VARCHAR(512) NULL  DEFAULT  NULL,
    `input5` VARCHAR(512) NULL  DEFAULT  NULL,
    `input6` VARCHAR(512) NULL  DEFAULT  NULL,
    `input7` VARCHAR(512) NULL  DEFAULT  NULL,
    `input8` VARCHAR(512) NULL  DEFAULT  NULL,
    `input9` VARCHAR(512) NULL  DEFAULT  NULL,
    `input10` VARCHAR(512) NULL  DEFAULT  NULL,
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`did`),
   INDEX `identity` (`identity`),
   INDEX `iduser` (`iduser`),
   INDEX `idcategory` (`idcategory`),
   INDEX `tip` (`tip`),
   INDEX `direction` (`direction`),
   INDEX `datum_plan` (`datum_plan`),
   INDEX `clid` (`clid`),
   INDEX `note` (`iduser`, `identity`),
   INDEX `sid` (`sid`),
   INDEX `close` (`close`),
   INDEX `datum` (`datum`),
   FULLTEXT INDEX `content` (`content`)
)  COMMENT='Сделки'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_dogprovider`;#%%
CREATE TABLE `app_dogprovider` (
    `id` INT NOT NULL auto_increment,
    `did` INT NULL  DEFAULT '0',
    `conid` INT NULL  DEFAULT '0',
    `partid` INT NULL  DEFAULT '0',
    `summa` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `status` VARCHAR(20) NULL  DEFAULT  NULL,
    `bid` INT NULL  DEFAULT '0',
    `recal` INT NULL  DEFAULT '0',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Расходы по сделке на партнеров и поставщиков'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_dogstatus`;#%%
CREATE TABLE `app_dogstatus` (
    `sid` BIGINT NOT NULL auto_increment,
    `title` TEXT NULL  COMMENT 'название',
    `result_close` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Результат закрытия: lose - Проигрыш; win - Победа',
    `content` TEXT NULL  COMMENT 'описание',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`sid`)
)  COMMENT='Статусы закрытия сделок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_dogstatus` VALUES ('1','Победа полная','win','Обозначает выигрыш, Договор выполнен и получена прибыль','1');#%%
INSERT INTO `app_dogstatus` VALUES ('2','Победа, договорились с конкурентами','win','Сделка выиграна, заключен и исполнен договор, получена прибыль','1');#%%
INSERT INTO `app_dogstatus` VALUES ('3','Проигрыш по цене','lose','Договор не заключен, проиграли по цене','1');#%%
INSERT INTO `app_dogstatus` VALUES ('4','Проигрыш, договорились с конкурентами','lose','Сделка проиграна, но удалось договориться с конкурентами.','1');#%%
INSERT INTO `app_dogstatus` VALUES ('5','Отменена Заказчиком','lose','Сделка отменена Заказчиком','1');#%%
INSERT INTO `app_dogstatus` VALUES ('6','Отказ от участия','lose','Мы отказались от участия в сделке','1');#%%
INSERT INTO `app_dogstatus` VALUES ('7','Закрыл менеджер. Отказ','lose','Проигрыш','1');#%%


DROP TABLE IF EXISTS `app_dogtips`;#%%
CREATE TABLE `app_dogtips` (
    `tid` INT NOT NULL auto_increment,
    `title` TEXT NULL  COMMENT 'название',
    `isDefault` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'признак дефолтности',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`tid`)
)  COMMENT='Типы сделок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_dogtips` VALUES ('1','Продажа простая','','1');#%%
INSERT INTO `app_dogtips` VALUES ('2','Продажа с разработкой','','1');#%%
INSERT INTO `app_dogtips` VALUES ('3','Услуги','','1');#%%
INSERT INTO `app_dogtips` VALUES ('4','Продажа услуг','','1');#%%
INSERT INTO `app_dogtips` VALUES ('5','Тендер','','1');#%%
INSERT INTO `app_dogtips` VALUES ('6','Продажа быстрая','yes','1');#%%


DROP TABLE IF EXISTS `app_dostup`;#%%
CREATE TABLE `app_dostup` (
    `id` INT NOT NULL auto_increment,
    `clid` INT NULL  COMMENT 'Запись клиента _clientcat.clid',
    `pid` INT NULL  COMMENT 'Запись контакта _personcat.pid',
    `did` INT NULL  COMMENT 'Запись сделки _dogovor.did',
    `iduser` INT NULL  COMMENT 'Сотрудник, которому дан доступ _user.iduser',
    `subscribe` VARCHAR(3) NULL  DEFAULT 'off' COMMENT 'отправлять уведомления (on-off) по сделкам',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `yindex` (`clid`, `pid`, `did`, `iduser`),
   INDEX `clid` (`clid`),
   INDEX `did` (`did`),
   INDEX `iduser` (`iduser`)
)  COMMENT='Доступы к карточкам клиентов, сделок '  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_doubles`;#%%
CREATE TABLE `app_doubles` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
    `tip` TEXT NULL  COMMENT 'типы дубля',
    `idmain` INT NULL  COMMENT 'id проверяемой записи',
    `list` VARCHAR(500) NULL  DEFAULT  NULL COMMENT 'json-массив найденных дублей',
    `ids` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'список всех id, упомятутых в list',
    `status` VARCHAR(3) NULL  DEFAULT 'no' COMMENT 'статус',
    `datumdo` TIMESTAMP NULL  COMMENT 'дата обработки',
    `des` TEXT NULL  COMMENT 'комментарий',
    `iduser` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'сотрудник, который выполнил действие',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `filter` (`id`, `tip`(10), `idmain`, `ids`)
)  COMMENT='Лог поиска дублей'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_entry`;#%%
CREATE TABLE `app_entry` (
    `ide` INT NOT NULL auto_increment,
    `uid` INT NULL ,
    `clid` INT NULL  COMMENT 'Клиент _clientcat.clid',
    `pid` INT NULL  COMMENT 'Контакт _personcat.pid',
    `did` INT NULL  COMMENT 'Созданная сделка _dogovor.did',
    `datum` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания',
    `datum_do` TIMESTAMP NULL  COMMENT 'дата обработки обращения',
    `iduser` INT NULL  COMMENT 'ответственный user.iduser',
    `autor` INT NULL  COMMENT 'автор user.iduser',
    `content` TEXT NULL  COMMENT 'коментарий',
    `status` INT NULL  DEFAULT '0' COMMENT 'Статус обработки: 0-новое, 1-обработано, 2 - отмена',
    `identity` INT NOT NULL,
   PRIMARY KEY (`ide`)
)  COMMENT='Обращения'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_entry_poz`;#%%
CREATE TABLE `app_entry_poz` (
    `idp` INT NOT NULL auto_increment,
    `ide` INT NULL  COMMENT 'Обращение _entry.ide',
    `prid` INT NULL  COMMENT 'Связь с прайсом _price.n_id, не обязательный',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название позиции',
    `kol` INT NULL  COMMENT 'количество',
    `price` DOUBLE NOT NULL DEFAULT '0' COMMENT 'цена',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idp`)
)  COMMENT='Обращения. Позиции в обращении'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_field`;#%%
CREATE TABLE `app_field` (
    `fld_id` INT NOT NULL auto_increment,
    `fld_tip` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'тип поля - client, person, price, dogovor',
    `fld_name` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'имя поля в БД',
    `fld_title` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'название поля для интерфейса',
    `fld_required` VARCHAR(10) NULL  DEFAULT 'required' COMMENT 'признак обязательности',
    `fld_on` VARCHAR(255) NULL  DEFAULT 'yes' COMMENT 'признак активности поля',
    `fld_order` INT NULL  COMMENT 'порядок вывода',
    `fld_stat` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'можно ли поле выключить',
    `fld_temp` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'тип поля - input, select...',
    `fld_var` TEXT NULL  COMMENT 'вариант готовых ответов',
    `fld_sub` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'доп.разделение для карточек клиентов - клиент, поставщик, партнер..',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`fld_id`)
)  COMMENT='Поля форм'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_field` VALUES ('15','client','input1','доп.поле',NULL,NULL,'14','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('16','client','input2','доп.поле',NULL,NULL,'20','no','--Обычное--','до 3х,св.3 до 10,св.10 до 50,св.50',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('17','client','input3','доп.поле',NULL,NULL,'15','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('1','client','title','Название','required','yes','1','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('2','client','iduser','Ответственный','required','yes','3','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('3','client','idcategory','Категория','','','5','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('4','client','head_clid','Головн. орг-ия','','','2','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('5','client','pid','Осн. контакт','','','9','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('6','client','address','Адрес','','yes','8','yes','adres','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('7','client','phone','Телефон','','yes','4','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('8','client','fax','Факс','','yes','10','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('9','client','site_url','Сайт',NULL,'yes','7','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('10','client','mail_url','Почта',NULL,'yes','6','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('11','client','territory','Территория','','','13','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('12','client','des','Описание',NULL,'','18','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('13','client','scheme','Принятие решений','','','17','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('14','client','tip_cmr','Тип отношений','','','11','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('25','person','clid','Клиент','','yes','5','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('26','person','ptitle','Должность','required','yes','2','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('27','person','person','Ф.И.О.','required','yes','1','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('28','person','tel','Тел.','','yes','6','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('29','person','fax','Факс',NULL,'yes','8','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('30','person','mob','Моб.','','yes','9','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('31','person','mail','Почта','','yes','11','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('32','person','rol','Роль','','','3','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('33','person','social','Прочее',NULL,'','14','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('34','person','iduser','Куратор','required','yes','4','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('35','person','loyalty','Лояльность','','','12','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('36','person','input1','Дата рождения','','','13','no','datum','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('37','person','input2','доп.поле',NULL,NULL,'15','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('38','person','input3','доп.поле',NULL,NULL,'16','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('39','person','input4','доп.поле',NULL,NULL,'17','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('40','person','input5','доп.поле',NULL,NULL,'18','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('41','person','input6','доп.поле',NULL,NULL,'19','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('42','person','input7','Добавочный',NULL,'yes','7','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('43','person','input8','доп.поле','','','20','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('44','person','input9','доп.поле','','','21','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('18','client','input4','доп.поле',NULL,NULL,'16','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('19','client','input5','доп.поле',NULL,NULL,'21','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('20','client','input6','доп.поле',NULL,NULL,'22','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('21','client','input7','доп.поле',NULL,NULL,'23','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('22','client','input8','доп.поле',NULL,NULL,'25','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('23','client','input9','доп.поле',NULL,NULL,'24','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('24','client','input10','доп.поле',NULL,NULL,'26','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('45','client','recv','Реквизиты','','yes','19','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('46','client','clientpath','Источник клиента','','','12','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('47','person','clientpath','Канал привлечения','','','10','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('48','dogovor','zayavka','Номер заявки','','','1','no','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('49','dogovor','ztitle','Основание','','','1','no','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('50','dogovor','mcid','Компания','required','yes','2','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('51','dogovor','iduser','Куратор','required','yes','3','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('52','dogovor','datum_plan','Дата план.','required','yes','4','yes','datum','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('53','dogovor','period','Период действия',NULL,'','5','no','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('54','dogovor','idcategory','Этап','','yes','6','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('55','dogovor','dog_num','Договор','','yes','7','no','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('56','dogovor','tip','Тип сделки','required','yes','8','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('57','dogovor','direction','Направление','required','yes','9','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('58','dogovor','adres','Адрес','','','10','no','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('59','dogovor','money','Деньги',NULL,'yes','11','yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('60','dogovor','content','Описание','','','12','no','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('61','dogovor','pid_list','Персоны','','yes','13','no','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('62','dogovor','payer','Плательщик','','yes','14','yes','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('64','dogovor','kol','Сумма план.','','yes',NULL,'yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('65','dogovor','kol_fact','Сумма факт.','','yes',NULL,'yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('66','dogovor','marg','Прибыль','','yes',NULL,'yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('67','dogovor','oborot','Сумма','','yes',NULL,'yes','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('68','price','price_in','Закуп','required','yes',NULL,'','','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('69','price','price_1','Розница','required','yes',NULL,'','','35',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('70','price','price_2','Уровень 1','','yes',NULL,'','','25',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('71','price','price_3','Уровень 2','required','yes',NULL,'','','20',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('72','price','price_4','Уровень 3','','',NULL,'','','15',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('73','price','price_5','Уровень 4','','',NULL,'','','10',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('880','dogovor','input1','доп.поле',NULL,NULL,'19','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('881','dogovor','input2','доп.поле',NULL,NULL,'20','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('882','dogovor','input3','доп.поле',NULL,NULL,'21','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('883','dogovor','input4','доп.поле',NULL,NULL,'22','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('884','dogovor','input5','доп.поле',NULL,NULL,'23','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('885','dogovor','input6','доп.поле',NULL,NULL,'24','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('970','dogovor','input7','доп.поле',NULL,NULL,'25','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('971','dogovor','input8','доп.поле',NULL,NULL,'26','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('972','dogovor','input9','доп.поле',NULL,NULL,'27','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('973','dogovor','input10','доп.поле',NULL,NULL,'28','','--Обычное--','',NULL,'1');#%%
INSERT INTO `app_field` VALUES ('1064','person','input10','доп.поле','','','22','no','','',NULL,'1');#%%


DROP TABLE IF EXISTS `app_file`;#%%
CREATE TABLE `app_file` (
    `fid` INT NOT NULL auto_increment,
    `ftitle` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'оригинальное название файла',
    `fname` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'имя, хранимое в системе',
    `ftype` TEXT NULL  COMMENT 'тип файла',
    `fver` INT NULL  COMMENT 'версия',
    `ftag` TEXT NULL  COMMENT 'описание файла',
    `iduser` INT NULL  COMMENT 'Автор _user.iduser',
    `clid` INT NULL  COMMENT 'Клиент _clientcat.clid',
    `pid` INT NULL  COMMENT 'Контакт _personcat.pid',
    `did` INT NULL  COMMENT 'Сдекла _dogovor.did',
    `tskid` INT NULL  COMMENT 'DEPRECATED',
    `coid` INT NULL  COMMENT 'DEPRECATED',
    `folder` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Папка _file_cat.idcategory',
    `datum` DATETIME NULL ,
    `size` INT NULL ,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`fid`),
   INDEX `ftitle` (`ftitle`),
   INDEX `folder` (`folder`)
)  COMMENT='Файлы'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_file_cat`;#%%
CREATE TABLE `app_file_cat` (
    `idcategory` INT NOT NULL auto_increment,
    `subid` INT NULL  DEFAULT '0',
    `title` VARCHAR(250) NULL  DEFAULT  NULL,
    `shared` VARCHAR(3) NULL  DEFAULT 'no' COMMENT 'общая папка (yes)',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`),
   INDEX `subid` (`subid`)
)  COMMENT='Папки файлов'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_file_cat` VALUES ('1','0','Коммерческие предложения клиентам','','1');#%%
INSERT INTO `app_file_cat` VALUES ('2','10','Спецификации','','1');#%%
INSERT INTO `app_file_cat` VALUES ('3','0','Презентации','yes','1');#%%
INSERT INTO `app_file_cat` VALUES ('4','8','Прочее','','1');#%%
INSERT INTO `app_file_cat` VALUES ('5','0','Изображения','yes','1');#%%
INSERT INTO `app_file_cat` VALUES ('6','1','КП','','1');#%%
INSERT INTO `app_file_cat` VALUES ('7','8','Прайс конкурента','','1');#%%
INSERT INTO `app_file_cat` VALUES ('8','0','Разное','no','1');#%%
INSERT INTO `app_file_cat` VALUES ('9','0','Рассылки','no','1');#%%
INSERT INTO `app_file_cat` VALUES ('10','0','Документы','yes','1');#%%


DROP TABLE IF EXISTS `app_group`;#%%
CREATE TABLE `app_group` (
    `id` INT NOT NULL auto_increment,
    `name` TEXT NOT NULL COMMENT 'имя группы',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления группы',
    `type` INT NULL  COMMENT 'DEPRECATED',
    `service` VARCHAR(60) NULL  DEFAULT  NULL COMMENT 'Связка с сервисом _services.name',
    `idservice` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'id группы во внешнем сервисе',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Группы. список групп'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_grouplist`;#%%
CREATE TABLE `app_grouplist` (
    `id` INT NOT NULL auto_increment,
    `gid` INT NOT NULL COMMENT 'Группа _group.id',
    `clid` INT(10) UNSIGNED ZEROFILL NULL  COMMENT 'Клиент _clientcat.clid',
    `pid` INT(10) UNSIGNED ZEROFILL NULL  COMMENT 'Контакт _personcat.pid',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подписки',
    `person_id` INT(10) UNSIGNED ZEROFILL NULL  COMMENT 'не используется',
    `service` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Имя сервиса _services.name',
    `user_name` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'имя подписчика',
    `user_email` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'email подписчика',
    `user_phone` VARCHAR(15) NULL  DEFAULT  NULL COMMENT 'телефон подписчика',
    `tags` TEXT NULL  COMMENT 'тэги',
    `status` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'статус подписчика',
    `availability` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'доступность подписчика',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `gid_clid_identity` (`gid`, `clid`, `identity`),
   INDEX `clid` (`clid`),
   INDEX `pid` (`pid`)
)  COMMENT='Модуль Группы. список подписчиков в группах'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_history`;#%%
CREATE TABLE `app_history` (
    `cid` INT NOT NULL auto_increment,
    `clid` INT NULL  DEFAULT '0',
    `pid` VARCHAR(100) NULL  DEFAULT  NULL,
    `did` INT NULL  DEFAULT '0',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `datum_izm` DATETIME NULL ,
    `des` TEXT NULL ,
    `iduser` INT NULL  DEFAULT '0',
    `iduser_izm` INT NULL  DEFAULT '0',
    `tip` VARCHAR(50) NULL  DEFAULT  NULL,
    `fid` VARCHAR(255) NULL  DEFAULT  NULL,
    `uid` VARCHAR(100) NULL  DEFAULT  NULL,
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`cid`),
   INDEX `clid` (`clid`),
   INDEX `pid` (`pid`),
   INDEX `did` (`did`),
   INDEX `iduser` (`iduser`),
   INDEX `identity` (`identity`),
   INDEX `tip` (`tip`)
)  COMMENT='История активностей'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_incoming`;#%%
CREATE TABLE `app_incoming` (
    `p_identity` INT NOT NULL,
    `p_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `p_text` TEXT NOT NULL,
   UNIQUE INDEX `p_identity` (`p_identity`)
)  COMMENT='кэширующая таблица для запросов из астериска'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_incoming` VALUES ('1','2017-12-12 15:35:24','{\"Response\":\"Success\",\"Message\":\"Channel status will follow\",\"data\":{\"1\":{\"Event\":\"StatusComplete\",\"Items\":\"0\"}}}');#%%


DROP TABLE IF EXISTS `app_incoming_channels`;#%%
CREATE TABLE `app_incoming_channels` (
    `p_identity` INT NOT NULL,
    `p_time` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP,
    `p_text` TEXT NOT NULL,
   UNIQUE INDEX `p_identity` (`p_identity`)
)  COMMENT='кэширующая таблица для запросов из астериска'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_incoming_channels` VALUES ('1','2017-11-21 12:11:40','{\"Response\":\"Success\",\"data\":[{\"EventList\":\"start\"},{\"Event\":\"CoreShowChannelsComplete\",\"EventList\":\"Complete\",\"ListItems\":\"0\"}],\"Message\":\"Channels will follow\"}');#%%


DROP TABLE IF EXISTS `app_kb`;#%%
CREATE TABLE `app_kb` (
    `idcat` INT NOT NULL auto_increment,
    `subid` INT NULL  COMMENT 'ссылка на головную папку',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название папки',
    `share` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'DEPRECATED',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcat`)
)  COMMENT='Модуль  База знаний. Список папок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_kbtags`;#%%
CREATE TABLE `app_kbtags` (
    `id` INT NOT NULL auto_increment,
    `name` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'тэг',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль База знаний. список тэгов'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_knowledgebase`;#%%
CREATE TABLE `app_knowledgebase` (
    `id` INT NOT NULL auto_increment,
    `idcat` INT NULL  COMMENT 'Папка _kb.idcat',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата публикации',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название статьи',
    `content` MEDIUMTEXT NULL  COMMENT 'содержание статьи',
    `count` INT NULL  COMMENT 'число просмотров',
    `active` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'признак черновика',
    `pin` VARCHAR(5) NULL  DEFAULT 'no' COMMENT 'Закрепление статьи',
    `pindate` DATETIME NULL  COMMENT 'Дата закрепления статьи',
    `keywords` TEXT NULL  COMMENT 'тэги',
    `author` INT NULL  COMMENT 'Автор _user.iduser',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   FULLTEXT INDEX `content` (`content`)
)  COMMENT='Модуль База знаний. Статьи'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_kpi`;#%%
CREATE TABLE `app_kpi` (
    `id` INT NOT NULL auto_increment,
    `kpi` INT NULL  COMMENT 'ID показателя',
    `year` INT NULL  COMMENT 'Год',
    `period` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'Период расчета',
    `iduser` INT NULL  COMMENT 'ID сотрудника (iduser)',
    `val` INT NULL  COMMENT 'Значение показателя',
    `isPersonal` TINYINT(1) NULL  DEFAULT '0' COMMENT 'Признок персонального показателя',
    `identity` INT NULL  COMMENT 'ID аккаунта',
   PRIMARY KEY (`id`)
)  COMMENT='База KPI сотрудников'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_kpibase`;#%%
CREATE TABLE `app_kpibase` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Название показателя',
    `tip` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'Тип показателя',
    `values` TEXT NULL  COMMENT 'Список значений показателя для расчетов',
    `subvalues` TEXT NULL  COMMENT 'Список дополнительных значений',
    `identity` INT NULL  COMMENT 'ID аккаунта',
   PRIMARY KEY (`id`)
)  COMMENT='Базовые показатели KPI'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_kpiseason`;#%%
CREATE TABLE `app_kpiseason` (
    `id` INT NOT NULL auto_increment,
    `year` INT NULL ,
    `rate` MEDIUMTEXT NULL  COMMENT 'значения сезонного коэффициента в json',
    `kpi` TEXT NULL  COMMENT 'id показателя',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Сезонные коэффициенты для показателей KPI'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_leads`;#%%
CREATE TABLE `app_leads` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `datum_do` DATETIME NULL  COMMENT 'дата обработки',
    `status` INT NULL  COMMENT 'статус 0 => Открыт, 1 => В работе, 2 => Обработан, 3 => Закрыт',
    `rezult` INT NULL  COMMENT 'результат обработки 1 => Спам, 2 => Дубль, 3 => Другое, 4 => Не целевой',
    `title` VARCHAR(255) NULL  DEFAULT  NULL,
    `email` VARCHAR(255) NULL  DEFAULT  NULL,
    `phone` VARCHAR(255) NULL  DEFAULT  NULL,
    `site` VARCHAR(255) NULL  DEFAULT  NULL,
    `company` VARCHAR(255) NULL  DEFAULT  NULL,
    `description` TEXT NULL  COMMENT 'описание заявки',
    `ip` VARCHAR(16) NULL  DEFAULT  NULL,
    `city` VARCHAR(100) NULL  DEFAULT  NULL,
    `country` VARCHAR(255) NULL  DEFAULT  NULL,
    `timezone` VARCHAR(5) NULL  DEFAULT  NULL,
    `iduser` INT NULL  DEFAULT '0' COMMENT '_user.iduser',
    `clientpath` INT NULL  COMMENT '_clientpath.id',
    `pid` INT NULL  COMMENT '_personcat.pid',
    `clid` INT NULL  COMMENT '_clientcat.clid',
    `did` INT NULL  COMMENT '_dogovor.did',
    `partner` INT NULL  COMMENT '_clientcat.clid',
    `muid` VARCHAR(255) NULL  DEFAULT  NULL,
    `rezz` TEXT NULL  COMMENT 'комментарий при дисквалификации заявки',
    `utm_source` VARCHAR(255) NULL  DEFAULT  NULL,
    `utm_medium` VARCHAR(255) NULL  DEFAULT  NULL,
    `utm_campaign` VARCHAR(255) NULL  DEFAULT  NULL,
    `utm_term` VARCHAR(255) NULL  DEFAULT  NULL,
    `utm_content` VARCHAR(255) NULL  DEFAULT  NULL,
    `utm_referrer` VARCHAR(255) NULL  DEFAULT  NULL,
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Сборщик заявок. Заявки'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_leads_utm`;#%%
CREATE TABLE `app_leads_utm` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `clientpath` INT NOT NULL DEFAULT '0' COMMENT 'id Источника из _clientpath',
    `utm_source` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Название источника',
    `utm_url` VARCHAR(500) NULL  DEFAULT  NULL COMMENT 'Адрес целевой страницы',
    `utm_medium` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Канал кампании',
    `utm_campaign` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Название кампании',
    `utm_term` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Ключевые слова, фраза',
    `utm_content` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Доп.описание кампании',
    `site` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Адрес сайта',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Сборщик заявок. Каталог UTM-ссылок'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_logapi`;#%%
CREATE TABLE `app_logapi` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `content` MEDIUMTEXT NOT NULL,
    `rez` TEXT NOT NULL,
    `ip` VARCHAR(20) NOT NULL,
    `remoteaddr` TEXT NOT NULL,
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Логи API'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_logs`;#%%
CREATE TABLE `app_logs` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `type` VARCHAR(100) NOT NULL,
    `iduser` INT NOT NULL COMMENT 'id пользователя user.iduser',
    `content` TEXT NOT NULL,
    `identity` INT NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
   PRIMARY KEY (`id`)
)  COMMENT='Логи авторизаций и др.действий'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%


DROP TABLE IF EXISTS `app_loyal_cat`;#%%
CREATE TABLE `app_loyal_cat` (
    `idcategory` INT NOT NULL auto_increment,
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'название',
    `color` VARCHAR(7) NOT NULL DEFAULT '#CCCCCC' COMMENT 'цвет',
    `isDefault` VARCHAR(6) NULL  DEFAULT  NULL COMMENT 'признак дефолтности',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`)
)  COMMENT='Типы лояльности'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_loyal_cat` VALUES ('2','0 - Не лояльный','#333333','','1');#%%
INSERT INTO `app_loyal_cat` VALUES ('3','4 - Очень Лояльный','#ff0000','','1');#%%
INSERT INTO `app_loyal_cat` VALUES ('4','2 - Нейтральный','#99ccff','','1');#%%
INSERT INTO `app_loyal_cat` VALUES ('1','3 - Лояльный','#ff00ff','','1');#%%
INSERT INTO `app_loyal_cat` VALUES ('5','1 - Не понятно','#CCCCCC','yes','1');#%%
INSERT INTO `app_loyal_cat` VALUES ('6','5 - ВиП','#cedb9c','','1');#%%


DROP TABLE IF EXISTS `app_mail`;#%%
CREATE TABLE `app_mail` (
    `mid` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата рассылки',
    `title` TEXT NULL  COMMENT 'название',
    `descr` TEXT NULL  COMMENT 'описание',
    `theme` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'тема сообщения',
    `tip` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'тип рассылки (от пользователя или от компании)',
    `iduser` INT NULL  COMMENT 'автор user.iduser',
    `tpl_id` INT NULL  COMMENT 'храним ид шаблона mail_tpl.tpl_id',
    `client_list` TEXT NULL  COMMENT 'список clientcat.clid, разделенный ;',
    `person_list` TEXT NULL  COMMENT 'список personcat.pid, разделенный ;',
    `file` TEXT NULL  COMMENT 'file.fid - прикрепленные файлы с разделением ;',
    `do` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'признак проведения рассылки',
    `template` MEDIUMTEXT NOT NULL COMMENT 'текст сообщения',
    `clist_do` TEXT NOT NULL COMMENT 'список clientcat.clid, разделенный ; которым отправлено сообщение',
    `plist_do` TEXT NOT NULL COMMENT 'список personcat.pid, разделенный ; которым отправлено сообщение',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`mid`)
)  COMMENT='Модуль рассылок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_mail_tpl`;#%%
CREATE TABLE `app_mail_tpl` (
    `tpl_id` INT NOT NULL auto_increment,
    `name_tpl` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'имя шаблона',
    `content_tpl` MEDIUMTEXT NULL  COMMENT 'содержание шаблона',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`tpl_id`)
)  COMMENT='Модуль рассылок. шаблоны писем'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog`;#%%
CREATE TABLE `app_modcatalog` (
    `id` INT NOT NULL auto_increment,
    `prid` INT NOT NULL COMMENT 'price.n_id',
    `idz` INT NULL  COMMENT 'modcatalog_zayavka.id',
    `content` MEDIUMTEXT NULL  COMMENT 'описание позиции',
    `datum` DATETIME NOT NULL COMMENT 'дата',
    `price_plus` DOUBLE NULL ,
    `status` INT NULL  DEFAULT '0' COMMENT 'статус (в наличии и тд.)',
    `kol` DOUBLE NULL  DEFAULT '0' COMMENT 'количество',
    `files` TEXT NULL  COMMENT 'прикрепленные файлы в формате json',
    `sklad` INT NOT NULL COMMENT 'modcatalog_sklad.id',
    `iduser` INT NOT NULL COMMENT 'user.iduser',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `prid` (`prid`)
)  COMMENT='Модуль Каталог-склад. Список позиций'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_akt`;#%%
CREATE TABLE `app_modcatalog_akt` (
    `id` INT NOT NULL auto_increment,
    `did` INT NULL  DEFAULT '0',
    `tip` VARCHAR(100) NULL  DEFAULT  NULL,
    `number` INT NULL  DEFAULT '0',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `clid` INT NULL  DEFAULT '0',
    `posid` INT NULL  DEFAULT '0',
    `man1` VARCHAR(255) NULL  DEFAULT  NULL,
    `man2` VARCHAR(255) NULL  DEFAULT  NULL,
    `isdo` VARCHAR(5) NULL  DEFAULT  NULL,
    `cFactura` VARCHAR(20) NULL  DEFAULT  NULL,
    `cDate` DATE NULL ,
    `sklad` INT NULL  DEFAULT '0',
    `idz` INT NULL  DEFAULT '0',
    `identity` INT NULL ,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Ордера'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_aktpoz`;#%%
CREATE TABLE `app_modcatalog_aktpoz` (
    `id` INT NOT NULL auto_increment,
    `ida` INT NOT NULL COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)',
    `prid` INT NOT NULL COMMENT 'price.n_id',
    `price_in` DOUBLE NOT NULL DEFAULT '0' COMMENT 'цена по ордеру',
    `kol` DOUBLE(20,2) NULL  DEFAULT '0.00' COMMENT 'количество по приходному-расходному ордеру',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Позиции ордеров'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_dop`;#%%
CREATE TABLE `app_modcatalog_dop` (
    `id` INT NOT NULL auto_increment,
    `prid` INT NOT NULL COMMENT 'price.n_id',
    `bid` INT NOT NULL COMMENT 'по-моему не используется',
    `datum` DATE NOT NULL,
    `content` TEXT NOT NULL COMMENT 'наименование доп. затрат',
    `summa` DOUBLE NOT NULL COMMENT 'стоимость доп. затрат',
    `clid` INT NOT NULL COMMENT 'clientcat.clid',
    `iduser` INT NOT NULL COMMENT 'user.iduser',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Доп.затараты по позициям каталога'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_field`;#%%
CREATE TABLE `app_modcatalog_field` (
    `id` INT NOT NULL auto_increment,
    `pfid` INT NOT NULL COMMENT 'modcatalog_fieldcat.id',
    `n_id` INT NOT NULL COMMENT 'price.n_id',
    `value` VARCHAR(255) NOT NULL COMMENT 'значение доп поля для данной продукции',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `value` (`value`)
)  COMMENT='Модуль Каталог-склад. Доп.поля к позициям'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_fieldcat`;#%%
CREATE TABLE `app_modcatalog_fieldcat` (
    `id` INT NOT NULL auto_increment,
    `name` VARCHAR(255) NOT NULL COMMENT 'название доп поля',
    `tip` VARCHAR(10) NOT NULL COMMENT 'тип вывода: поле ввода, поле текста, список выбора, чекбоксы, радиокнопки и разделитель',
    `value` TEXT NOT NULL COMMENT 'выбираемы значения',
    `ord` INT NOT NULL COMMENT 'порядковый номер поля в списке доп. полей',
    `pole` VARCHAR(10) NOT NULL,
    `pwidth` INT NOT NULL DEFAULT '50' COMMENT 'ширина поля',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Каталог доп.полей к позициям каталога'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_log`;#%%
CREATE TABLE `app_modcatalog_log` (
    `id` INT NOT NULL auto_increment,
    `dopzid` INT NOT NULL COMMENT 'modcatalog_dop.id',
    `datum` DATETIME NOT NULL COMMENT 'дата изменения',
    `tip` VARCHAR(255) NOT NULL COMMENT 'где происходит измениение: catalog, dop, kol, price, status',
    `new` TEXT NOT NULL COMMENT 'было ',
    `old` TEXT NOT NULL COMMENT 'стало',
    `prid` INT NOT NULL COMMENT 'price.n_id',
    `iduser` INT NOT NULL COMMENT 'id пользователя user.iduser',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. История изменений по позициям'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_offer`;#%%
CREATE TABLE `app_modcatalog_offer` (
    `id` INT NOT NULL auto_increment,
    `datum` DATETIME NOT NULL COMMENT 'дата предложения',
    `datum_end` DATETIME NOT NULL,
    `status` INT NOT NULL DEFAULT '0' COMMENT 'статус 0-актуальная, 1-закрытая',
    `iduser` INT NOT NULL COMMENT 'user.iduser',
    `content` TEXT NOT NULL COMMENT 'коментарий предложения',
    `des` TEXT NOT NULL COMMENT 'данные по НДС, названию предложения, сумме',
    `users` TEXT NOT NULL COMMENT 'user.iduser с разделением ; принявшие предложение снабжения (голосование)',
    `prid` INT NOT NULL COMMENT 'id созданной позиции (price.n_id)',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Предложения от снабжения'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_reserv`;#%%
CREATE TABLE `app_modcatalog_reserv` (
    `id` INT NOT NULL auto_increment,
    `did` INT NOT NULL COMMENT 'dogovor.did',
    `prid` INT NOT NULL COMMENT 'price.n_id',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата резерва',
    `kol` DOUBLE(20,2) NULL  DEFAULT '0.00' COMMENT 'кол-во резерва',
    `status` VARCHAR(30) NOT NULL COMMENT 'статус резерва (действует-снят)',
    `idz` INT NOT NULL DEFAULT '0' COMMENT 'id заявки, по которой ставили резерв (modcatalog_zayavka.id)',
    `ida` INT NOT NULL DEFAULT '0' COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)',
    `sklad` INT NOT NULL DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Резерв'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_set`;#%%
CREATE TABLE `app_modcatalog_set` (
    `id` INT NOT NULL auto_increment,
    `settings` TEXT NOT NULL COMMENT 'настройки',
    `ftp` TEXT NOT NULL COMMENT 'настройки ftp',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Настройки'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_modcatalog_set` VALUES ('1','{\"mcArtikul\":\"yes\",\"mcStep\":\"6\",\"mcStepPers\":\"80\",\"mcKolEdit\":null,\"mcStatusEdit\":null,\"mcUseOrder\":\"yes\",\"mcCoordinator\":[\"1\",\"20\",\"22\",\"14\",\"13\",\"18\"],\"mcSpecialist\":[\"1\",\"23\",\"22\",\"3\"],\"mcAutoRezerv\":\"yes\",\"mcAutoWork\":\"yes\",\"mcAutoStatus\":null,\"mcSklad\":\"yes\",\"mcSkladPoz\":null,\"mcAutoProvider\":\"yes\",\"mcAutoPricein\":\"yes\",\"mcDBoardSkladName\":\"Наличие\",\"mcDBoardSklad\":\"yes\",\"mcDBoardZayavkaName\":\"Заявки\",\"mcDBoardZayavka\":\"yes\",\"mcDBoardOfferName\":\"Предложения\",\"mcDBoardOffer\":\"yes\",\"mcMenuTip\":\"inMain\",\"mcMenuPlace\":\"\",\"mcOfferName1\":\"\",\"mcOfferName2\":\"\",\"mcPriceCat\":[\"245\",\"247\",\"246\",\"1\",\"156\",\"154\",\"4\",\"158\",\"153\",\"180\",\"177\",\"176\",\"173\",\"172\",\"171\",\"170\",\"174\",\"175\",\"178\"]}','{\"mcFtpServer\":\"\",\"mcFtpUser\":\"\",\"mcFtpPass\":\"\",\"mcFtpPath\":\"\"}','1');#%%


DROP TABLE IF EXISTS `app_modcatalog_sklad`;#%%
CREATE TABLE `app_modcatalog_sklad` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NOT NULL COMMENT 'название склада',
    `mcid` INT NOT NULL COMMENT 'привязка к компании (mycomps.id)',
    `isDefault` VARCHAR(5) NOT NULL DEFAULT 'no' COMMENT 'склад по умолчанию для каждой компании',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE INDEX `id` (`id`)
)  COMMENT='Модуль Каталог-склад. список складов'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_skladmove`;#%%
CREATE TABLE `app_modcatalog_skladmove` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата перемещения',
    `skladfrom` INT NULL  DEFAULT '0' COMMENT 'id склада с которого перемещаем',
    `skladto` INT NULL  DEFAULT '0' COMMENT 'id склада на который перемещаем',
    `iduser` INT NULL  DEFAULT '0' COMMENT 'id сотрудника, сделавшего перемещение',
    `identity` INT NULL  DEFAULT '0',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Лог перемещения позиций между склдами'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_skladmovepoz`;#%%
CREATE TABLE `app_modcatalog_skladmovepoz` (
    `id` INT NOT NULL auto_increment,
    `idm` INT NOT NULL DEFAULT '0' COMMENT 'id группы перемещения (modcatalog_skladmove.id)',
    `idp` INT NOT NULL DEFAULT '0' COMMENT 'id позиции из таблицы modcatalog_skladpoz',
    `prid` INT NOT NULL DEFAULT '0' COMMENT 'id позиции прайса (price.n_id)',
    `kol` DOUBLE(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'количество для общего учета',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Позиции перемещения между складами'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_skladpoz`;#%%
CREATE TABLE `app_modcatalog_skladpoz` (
    `id` INT NOT NULL auto_increment,
    `prid` INT NOT NULL DEFAULT '0' COMMENT 'id товара (price.n_id)',
    `sklad` INT NOT NULL DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
    `status` VARCHAR(5) NOT NULL DEFAULT 'out',
    `date_in` DATE NULL  COMMENT 'дата поступления',
    `date_out` DATE NULL  COMMENT 'дата выбытия',
    `serial` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'серийный номер',
    `date_create` DATE NULL  COMMENT 'дата производства',
    `date_period` DATE NULL  COMMENT 'дата (например поверки)',
    `kol` DOUBLE(20,2) NULL  DEFAULT '0.00' COMMENT 'кол-во',
    `did` INT NULL  COMMENT 'id сделки, на которую позиция списана (поштучный учет) (dogovor.did)',
    `idorder_in` INT NULL  DEFAULT '0' COMMENT 'id приходного ордера (modcatalog_akt.id)',
    `idorder_out` INT NULL  DEFAULT '0' COMMENT 'id расходного ордера (modcatalog_akt.id)',
    `summa` DOUBLE(20,2) NULL  DEFAULT '0.00' COMMENT 'стоимость для расх.ордера',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `prid` (`prid`),
   INDEX `sklad` (`sklad`),
   INDEX `did` (`did`),
   INDEX `identity` (`identity`)
)  COMMENT='Модуль Каталог-склад. Позиции на складах'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_zayavka`;#%%
CREATE TABLE `app_modcatalog_zayavka` (
    `id` INT NOT NULL auto_increment,
    `number` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'номер заявки',
    `did` INT NOT NULL COMMENT 'dogovor.did',
    `datum` DATETIME NOT NULL COMMENT 'дата заявки',
    `datum_priority` DATE NULL  COMMENT 'желаемая дата (срочность)',
    `datum_start` DATETIME NOT NULL COMMENT 'дата начало выполнения заявки',
    `datum_end` DATETIME NOT NULL COMMENT 'дата окончания выполнения заявки',
    `status` INT NOT NULL DEFAULT '0' COMMENT '0 - создана, 1-в работе, 2- выполнено, 3-отмена',
    `iduser` INT NOT NULL COMMENT 'автор user.iduser',
    `sotrudnik` INT NOT NULL COMMENT 'ответственный user.iduser',
    `content` TEXT NOT NULL COMMENT 'коментарий заявки',
    `rezult` TEXT NOT NULL,
    `des` TEXT NOT NULL COMMENT 'заполнение доп полей',
    `isHight` VARCHAR(3) NULL  DEFAULT 'no',
    `cInvoice` VARCHAR(20) NULL  DEFAULT  NULL,
    `cDate` DATE NULL  COMMENT 'Дата счета поставщика',
    `cSumma` DOUBLE(20,2) NULL  DEFAULT '0.00' COMMENT 'сумма счета поставщика',
    `bid` INT NULL  DEFAULT '0' COMMENT 'Связка с записью в Расходах',
    `providerid` INT NULL  DEFAULT '0' COMMENT 'id записи в таблице dogprovider',
    `conid` INT NULL  DEFAULT '0' COMMENT 'id поставщика (clientcat.clid)',
    `sklad` INT NULL  DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Список заявок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modcatalog_zayavkapoz`;#%%
CREATE TABLE `app_modcatalog_zayavkapoz` (
    `id` INT NOT NULL auto_increment,
    `idz` INT NOT NULL COMMENT 'id заявки (odcatalog_zayavka.id)',
    `prid` INT NOT NULL COMMENT 'price.n_id',
    `kol` DOUBLE(20,2) NULL  DEFAULT '0.00' COMMENT 'кол-во на складе',
    `identity` INT NOT NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Каталог-склад. Позиции заявок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_modules`;#%%
CREATE TABLE `app_modules` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'название модуля',
    `content` TEXT NULL  COMMENT 'какие сделаны настройки модуля',
    `mpath` VARCHAR(255) NULL  DEFAULT  NULL,
    `icon` VARCHAR(20) NOT NULL DEFAULT 'icon-publish' COMMENT 'иконка из фонтелло для меню',
    `active` VARCHAR(5) NOT NULL DEFAULT 'on' COMMENT 'включен-отключен',
    `activateDate` VARCHAR(20) NULL  DEFAULT  NULL,
    `secret` VARCHAR(255) NULL  DEFAULT  NULL,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Подключенные модули'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_modules` VALUES ('1','Каталог-склад','','modcatalog','icon-archive','off','2020-08-13 12:12:48','','1');#%%
INSERT INTO `app_modules` VALUES ('2','Обращения','{\"enShowButtonLeft\":\"yes\",\"enShowButtonCall\":\"yes\"}','entry','icon-phone-squared','off','2021-10-28 22:44:45','','1');#%%


DROP TABLE IF EXISTS `app_multisteps`;#%%
CREATE TABLE `app_multisteps` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Название цепочки',
    `direction` INT NULL  COMMENT 'id from _direction Направление',
    `tip` INT NULL  COMMENT 'tid from _dogtips Тип сделки',
    `steps` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Набор этапов',
    `isdefault` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'id этапа по умолчанию',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Настройка мультиворонки'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_mycomps`;#%%
CREATE TABLE `app_mycomps` (
    `id` INT NOT NULL auto_increment,
    `name_ur` TEXT NULL  COMMENT 'полное наименование',
    `name_shot` TEXT NULL  COMMENT 'сокращенное наименование',
    `address_yur` TEXT NULL  COMMENT 'юридические адрес',
    `address_post` TEXT NULL  COMMENT 'почтовый адрес',
    `dir_name` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'в лице руководителя',
    `dir_signature` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'подпись руководителя',
    `dir_status` TEXT NULL  COMMENT 'должность руководителя',
    `dir_osnovanie` TEXT NULL  COMMENT 'действующего на основаии',
    `innkpp` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'инн-кпп',
    `okog` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'окпо-огрн',
    `stamp` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'файл с факсимилией',
    `logo` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'файл с логотипом',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Список собственных компаний'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_mycomps` VALUES ('1','Общество с ограниченной ответственностью ”Брикет Солюшн”','ООО ”Брикет Солюшн”','614007, г. Пермь, ул. Народовольческая, 60','614007, г. Пермь, ул. Народовольческая, 60','Директора Андреева Владислава Германовича','Андреев В.Г.','Директор','Устава','590402247104;590401001',';312590427000020','stamp1675529125.png','logo.png','1');#%%


DROP TABLE IF EXISTS `app_mycomps_recv`;#%%
CREATE TABLE `app_mycomps_recv` (
    `id` INT NOT NULL auto_increment,
    `cid` INT NULL  DEFAULT '0',
    `title` TEXT NULL  COMMENT 'назваине р.с',
    `rs` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'р.с',
    `bankr` TEXT NULL  COMMENT 'бик, кур. счет и название банка',
    `tip` VARCHAR(6) NULL  DEFAULT 'bank' COMMENT 'bank-kassa',
    `ostatok` DOUBLE(20,2) NULL  COMMENT 'остаток средств',
    `bloc` VARCHAR(3) NULL  DEFAULT 'no' COMMENT 'заблокирован или нет счет',
    `isDefault` VARCHAR(5) NULL  DEFAULT 'no' COMMENT 'использутся по умолчанию или нет',
    `ndsDefault` VARCHAR(5) NULL  DEFAULT '0' COMMENT 'размер ндс по умолчанию',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Расчетные счета к компаниям'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_mycomps_recv` VALUES ('1','1','Основной расчетный счет','1234567890000000000000000','045744863;30101810300000000863;Филиал ОАО «УРАЛСИБ» в г. Пермь','bank','0.00','','yes','20','1');#%%
INSERT INTO `app_mycomps_recv` VALUES ('2','1','Касса','0',';;','kassa','0.00','','','0','1');#%%


DROP TABLE IF EXISTS `app_mycomps_signer`;#%%
CREATE TABLE `app_mycomps_signer` (
    `id` INT NOT NULL auto_increment,
    `mcid` INT NULL  COMMENT 'Привязка к компании',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Имя подписанта',
    `status` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Должность',
    `signature` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Подпись',
    `osnovanie` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Действующий на основании',
    `stamp` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Файл факсимилье',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `mcid` (`mcid`)
)  COMMENT='Дополнительные подписанты для документов'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_notes`;#%%
CREATE TABLE `app_notes` (
    `id` INT NOT NULL auto_increment,
    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания заметки',
    `author` INT NOT NULL DEFAULT '0' COMMENT 'id пользователя, создавшего заметку',
    `pin` INT NOT NULL COMMENT 'признак важности заметки',
    `text` VARCHAR(180) NOT NULL COMMENT 'Текст заметки',
    `identity` INT NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
   PRIMARY KEY (`id`),
   UNIQUE INDEX `id` (`id`)
)  COMMENT='База заметок пользователей'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_notify`;#%%
CREATE TABLE `app_notify` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'время уведомления',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'заголовок уведомления',
    `content` TEXT NULL  COMMENT 'содержимое уведомления',
    `url` TEXT NULL  COMMENT 'ссылка на сущность',
    `tip` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'тип связанной записи',
    `uid` INT NULL  COMMENT 'id связанной записи',
    `status` VARCHAR(2) NULL  DEFAULT '0' COMMENT 'Статус прочтения - 0 Не прочитано, 1 Прочитано',
    `autor` INT NULL  COMMENT 'автор события',
    `iduser` INT NULL  COMMENT 'цель - сотрудник',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='База уведомлений'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_office_cat`;#%%
CREATE TABLE `app_office_cat` (
    `idcategory` INT NOT NULL auto_increment,
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'адрес офиса',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`)
)  COMMENT='Офисы'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_office_cat` VALUES ('1',' г. Пермь, ул. Ленина, 60 оф. 100','1');#%%


DROP TABLE IF EXISTS `app_otdel_cat`;#%%
CREATE TABLE `app_otdel_cat` (
    `idcategory` INT NOT NULL auto_increment,
    `uid` VARCHAR(30) NOT NULL COMMENT 'идентификатор для внешних систем',
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'название отдела',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`)
)  COMMENT='Отделы'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_otdel_cat` VALUES ('1','OAP','Отдел активных продаж','1');#%%
INSERT INTO `app_otdel_cat` VALUES ('2','OPP','Отдел пассивных продаж','1');#%%


DROP TABLE IF EXISTS `app_personcat`;#%%
CREATE TABLE `app_personcat` (
    `pid` INT NOT NULL auto_increment,
    `clid` INT NULL  DEFAULT '0',
    `ptitle` VARCHAR(250) NULL  DEFAULT  NULL,
    `person` VARCHAR(250) NULL  DEFAULT  NULL,
    `tel` VARCHAR(250) NULL  DEFAULT  NULL,
    `fax` VARCHAR(250) NULL  DEFAULT  NULL,
    `mob` VARCHAR(250) NULL  DEFAULT  NULL,
    `mail` VARCHAR(250) NULL  DEFAULT  NULL,
    `rol` TEXT NULL ,
    `social` TEXT NULL ,
    `iduser` VARCHAR(12) NULL  DEFAULT '0',
    `clientpath` INT NULL  DEFAULT '0',
    `loyalty` INT NULL  DEFAULT '0',
    `input1` VARCHAR(255) NULL  DEFAULT  NULL,
    `input2` VARCHAR(255) NULL  DEFAULT  NULL,
    `input3` VARCHAR(255) NULL  DEFAULT  NULL,
    `input4` VARCHAR(255) NULL  DEFAULT  NULL,
    `input5` VARCHAR(255) NULL  DEFAULT  NULL,
    `input6` VARCHAR(255) NULL  DEFAULT  NULL,
    `input7` VARCHAR(255) NULL  DEFAULT  NULL,
    `input8` VARCHAR(255) NULL  DEFAULT  NULL,
    `input9` VARCHAR(255) NULL  DEFAULT  NULL,
    `input10` VARCHAR(512) NULL  DEFAULT  NULL,
    `date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_edit` TIMESTAMP NULL ,
    `creator` INT NULL  DEFAULT '0',
    `editor` INT NULL  DEFAULT '0',
    `uid` INT NULL  DEFAULT '0',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`pid`),
   INDEX `person` (`person`),
   INDEX `tel` (`tel`),
   INDEX `mob` (`mob`),
   INDEX `fax` (`fax`),
   INDEX `mail` (`mail`)
)  COMMENT='Контакты'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_plan`;#%%
CREATE TABLE `app_plan` (
    `plid` INT NOT NULL auto_increment,
    `year` INT NULL  COMMENT 'год',
    `mon` INT NULL  COMMENT 'месяц',
    `iduser` INT NULL  COMMENT 'план для кокого сотрудника user.iduser',
    `kol_plan` TEXT NULL  COMMENT 'план',
    `marga` TEXT NULL  COMMENT 'прибыль',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`plid`)
)  COMMENT='План продаж'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_plugins`;#%%
CREATE TABLE `app_plugins` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подключения',
    `name` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'название ',
    `version` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'Установленная версия плагина',
    `active` VARCHAR(5) NOT NULL DEFAULT 'off' COMMENT 'статус активности - on-off',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Подключенные плагины'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_price`;#%%
CREATE TABLE `app_price` (
    `n_id` INT NOT NULL auto_increment,
    `artikul` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'артикул',
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название позиции',
    `descr` TEXT NULL  COMMENT 'описание',
    `edizm` VARCHAR(10) NULL  DEFAULT  NULL,
    `price_in` DOUBLE(20,2) NOT NULL DEFAULT '0.00',
    `price_1` DOUBLE(20,2) NOT NULL DEFAULT '0.00',
    `price_2` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `price_3` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `price_4` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `price_5` DOUBLE(20,2) NULL ,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `pr_cat` INT NOT NULL COMMENT 'категория price_cat.idcategory',
    `nds` DOUBLE(20,2) NOT NULL DEFAULT '0.00' COMMENT 'ндс',
    `archive` VARCHAR(3) NOT NULL DEFAULT 'no',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`n_id`),
   INDEX `pr_cat` (`pr_cat`),
   FULLTEXT INDEX `title` (`title`)
)  COMMENT='Прайс-лист'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_price_cat`;#%%
CREATE TABLE `app_price_cat` (
    `idcategory` INT NOT NULL auto_increment,
    `sub` INT NULL  COMMENT 'Головная категория - _price_cat.idcategory',
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'название прайса',
    `type` TINYINT(1) NULL  COMMENT 'тип: 0 - товар, 1 - услуга, 2 - материал',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`),
   INDEX `sub` (`sub`)
)  COMMENT='Прайс-лист. Категории'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_price_cat` VALUES ('1','0','Тест',NULL,'1');#%%


DROP TABLE IF EXISTS `app_profile`;#%%
CREATE TABLE `app_profile` (
    `pfid` INT NOT NULL auto_increment,
    `id` INT NULL  COMMENT 'profile_cat.id',
    `clid` INT NULL  COMMENT 'clientcat.clid',
    `value` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'начение поля',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`pfid`),
   INDEX `value` (`value`)
)  COMMENT='Модуль Профиль. Данные'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_profile_cat`;#%%
CREATE TABLE `app_profile_cat` (
    `id` INT NOT NULL auto_increment,
    `name` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название поля',
    `tip` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'тип вывода поля',
    `value` TEXT NULL  COMMENT 'значение поля',
    `ord` INT NULL  COMMENT 'порядок вывода',
    `pole` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'название поля для идентификации',
    `pwidth` INT NOT NULL DEFAULT '50' COMMENT 'ширина поля',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Профиль. Настройки профилей'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_profile_cat` VALUES ('1','Количество сотрудников в отделе снабжения','select','1-3;3-5;больше 5','15','pole1','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('2','Как часто проводят закупки','select','1 раз в мес.; 2 раза в мес.;больше 2-х раз в мес.','3','pole2','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('3','Тендерный отдел','radio','Нет;Есть','13','pole3','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('4','Проводят тендеры','radio','Электронные площадки;Самостоятельно;Оба варианта;Не проводят','14','pole4','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('5','Примечание','text','','17','pole5','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('8','Какие продукты можем предложить?','checkbox','Зап.части;Шины;Диски;Элементы кузова;Внедрение телефонии;Внедрение серверов;1С в облаке;Настройка VPN','4','pole8','100','1');#%%
INSERT INTO `app_profile_cat` VALUES ('9','Объем закупок в месяц','radio','<100т.р.;100-200 т.р.;200-300 т.р.;300-500 т.р.;>500 т.р.','5','pole9','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('10','Тип клиента для нас','radio','Не работаем;Ведем переговоры;С нами не будут работать;Работают только с нами','12','pole10','100','1');#%%
INSERT INTO `app_profile_cat` VALUES ('11','Что покупают постоянно','checkbox','ГСМ;Автохимия;Зап.части;Диски','8','pole11','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('12','Годовой оборот','radio','до 1млн.;свыше 1млн. до 20млн.;свыше 20млн. до 100млн.','11','pole12','50','1');#%%
INSERT INTO `app_profile_cat` VALUES ('19','Специализация','input','','16','pole19','100','1');#%%
INSERT INTO `app_profile_cat` VALUES ('15','Возможности по продаже','divider','','1','pole15','100','1');#%%
INSERT INTO `app_profile_cat` VALUES ('16','Интересы клиента','divider','','7','pole16','100','1');#%%


DROP TABLE IF EXISTS `app_projects_templates`;#%%
CREATE TABLE `app_projects_templates` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NULL  DEFAULT 'untitled' COMMENT 'Название шаблона',
    `autor` INT NULL  COMMENT 'iduser автора',
    `datum` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP,
    `content` TEXT NULL  COMMENT 'Содержание работ в json',
    `state` INT NULL  DEFAULT '1' COMMENT 'Статус: 1 - активен, 0 - не активен',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Шаблоны проектов'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_relations`;#%%
CREATE TABLE `app_relations` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название',
    `color` VARCHAR(10) NULL  DEFAULT  NULL COMMENT 'цвет',
    `isDefault` VARCHAR(6) NULL  DEFAULT  NULL COMMENT 'признак по умолчанию',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `title` (`title`),
   INDEX `title_2` (`title`)
)  COMMENT='Типы отношений'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_relations` VALUES ('1','0 - Не работаем','#333333','','1');#%%
INSERT INTO `app_relations` VALUES ('2','1 - Холодный клиент','#99ccff','yes','1');#%%
INSERT INTO `app_relations` VALUES ('3','3 - Текущий клиент','#3366ff','','1');#%%
INSERT INTO `app_relations` VALUES ('5','4 - Постоянный клиент','#ff9900','no','1');#%%
INSERT INTO `app_relations` VALUES ('4','2 - Потенциальный клиент','#99ff66','','1');#%%
INSERT INTO `app_relations` VALUES ('6','5 - Перспективный клиент','#ff0033','no','1');#%%


DROP TABLE IF EXISTS `app_reports`;#%%
CREATE TABLE `app_reports` (
    `rid` INT NOT NULL auto_increment,
    `title` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'название отчета',
    `file` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'файл отчета',
    `ron` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'активность отчета',
    `category` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'раздел',
    `roles` TEXT NULL  COMMENT 'Роли сотрудников с доступом к отчету',
    `users` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'id сотрудников, у которых есть доступ к отчету',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`rid`)
)  COMMENT='Подключенные файлы отчетов'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_reports` VALUES ('1','Активности по сделкам','work.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('2','Сделки по сотрудникам','effect_total.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('5','Анализ конкурентов','effect_concurent.php','yes','Связи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('73','Ent. Эффективность каналов','entClientpathToMoney.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('7','Топ клиентов','top_clients.php','yes','Рейтинг',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('8','Топ сотрудников','top_managers.php','yes','Рейтинг',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('9','Активность по клиентам','week.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('10','Действия по сделкам','newdogs.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('11','По отделам','effect_otdel.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('12','Сделки по типам','effect_dogovor.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('13','По реализ. сделкам','effect_closed.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('14','Анализ поставщиков','effect_contractor.php','yes','Связи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('15','Анализ партнеров','effect_partner.php','yes','Связи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('16','Активности. Сводная','pipeline_activities.php','no','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('19','Pipeline Продажи Сотрудников','pipelineUsersNew.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('21','Эффективность сотрудников','effect.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('20','Pipeline Ожидаемый приход','pipeline_prognoz.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('22','Pipeline Продажи по этапам','pipeline_dogs.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('29','Здоровье сделок','dogs_health.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('30','Здоровье сделок [большой]','dogs_health_big.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('31','Выполнение дел','activities_results.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('38','Воронка по марже','voronka_marg.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('39','Здоровье сделок (дни)','dogs_health_big_day.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('40','Сделки по направлениям','effect_direction.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('74','Ent. Анализ клиентов по типам отношений','entRelationsToMoney.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('44','Новые клиенты','effect_newclients.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('45','Сделки. Анализ','dogs_monitor.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('46','Сделки. В работе','dogs_inwork.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('47','Сделки. Зависшие','dogs_inhold.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('48','Сделки. Утвержденные','dogs_approved.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('49','Сделки. Отказные','dogs_disapproved.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('50','Сделки. Здоровье (все сделки)','dogs_health_all.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('51','Контроль сделок (по КТ)','dogs_complect.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('61','Ent. ABC анализ клиентов','ent-ABC-clients.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('62','Ent. ABC анализ продуктов','ent-ABC-products.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('63','Ent. RFM анализ клиентов','ent-RFM-clients.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('56','Прогноз по продуктам','dogs_productprognoz.php','yes','Планирование',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('57','Прогноз по продуктам (большой)','dogs_productprognoz_hor.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('58','Выполнение планов','planfact2015.php','yes','Планирование',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('59','Воронка по активностям','voronka_classic.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('72','Ent. Сделки в работе. По дням по этапам','ent-dealsPerDayPerStep.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('64','Ent. RFM анализ продуктов','ent-RFM-products.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('65','Анализ Сборщика заявок (Лидов)','leads2014.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('66','Анализ звонков (телефония)','call_history.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('67','Закрытые успешные сделки','dealResultReport.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('68','Закрытые сделки по этапам','ent-ClosedDealAnalyseByStep.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('69','Выставленные счета по сотрудникам','ent-InvoiceStateByUser.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('70','Ent. Супер Воронка продаж','ent-SalesFunnel.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('71','Ent. Комплексная воронка','ent-voronkaComplex.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('75','Ent. Новые клиенты','ent-newClients.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('76','Ent. Новые сделки','ent-newDeals.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('77','Ent. Оплаты по сотрудникам','ent-PaymentsByUser.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('80','Ent. Активности по времени','ent-activitiesByTime.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('81','Активности пользователей по сделкам','ent-ActivitiesByUserByDeals.php','yes','Активности',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('82','Анализ направлений','entDirectionAnaliseChart.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('83','Эффективность каналов продаж','effect_clientpath.php','yes','Эффективность',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('84','Выполнение планов по оплатам','ent-planDoByPayment.php','yes','Планирование',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('85','Рейтинг выполнения плана','raiting_plan.php','yes','Планирование',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('86','Ent. Антиворонка','ent-antiSalesFunnel.php','yes','Продажи',NULL,NULL,'1');#%%
INSERT INTO `app_reports` VALUES ('87','Ent. Сделки в работе. По дням','ent-dealsPerDay.php','yes','Продажи',NULL,NULL,'1');#%%


DROP TABLE IF EXISTS `app_search`;#%%
CREATE TABLE `app_search` (
    `seid` INT NOT NULL auto_increment,
    `tip` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'Привязка к person, client, dog',
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'Название представления',
    `squery` TEXT NULL  COMMENT 'Поисковой запрос',
    `sorder` INT NULL  COMMENT 'Порядок вывода',
    `iduser` INT NULL  COMMENT 'user.iduser',
    `share` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Общий доступ',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`seid`)
)  COMMENT='Поисковые представления'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_services`;#%%
CREATE TABLE `app_services` (
    `id` INT NOT NULL auto_increment,
    `name` VARCHAR(255) NOT NULL COMMENT 'название',
    `folder` VARCHAR(60) NOT NULL COMMENT 'название для системы',
    `tip` VARCHAR(200) NOT NULL COMMENT 'тип sip-mail',
    `user_id` VARCHAR(255) NOT NULL COMMENT 'пользователь',
    `user_key` VARCHAR(255) NOT NULL COMMENT 'ключ пользователя',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Данные различных внешних систем для интеграции'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_settings`;#%%
CREATE TABLE `app_settings` (
    `id` INT NOT NULL auto_increment,
    `company` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'Название компании. Краткое',
    `company_full` MEDIUMTEXT NULL  COMMENT 'Название компании. Полное',
    `company_site` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'Сайт компании',
    `company_mail` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'Email компании',
    `company_phone` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'Телефон компании',
    `company_fax` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'факс',
    `outClientUrl` VARCHAR(255) NULL  DEFAULT  NULL,
    `outDealUrl` VARCHAR(255) NULL  DEFAULT  NULL,
    `defaultDealName` VARCHAR(255) NULL  DEFAULT  NULL,
    `dir_prava` VARCHAR(255) NULL  DEFAULT  NULL,
    `recv` MEDIUMTEXT NULL ,
    `gkey` VARCHAR(250) NULL  DEFAULT  NULL,
    `num_client` INT NULL  DEFAULT '30',
    `num_con` INT NULL  DEFAULT '30',
    `num_person` INT NULL  DEFAULT '30',
    `num_dogs` INT NULL  DEFAULT '30',
    `format_phone` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'Формат телефона',
    `format_fax` VARCHAR(250) NULL  DEFAULT  NULL,
    `format_tel` VARCHAR(250) NULL  DEFAULT  NULL,
    `format_mob` VARCHAR(250) NULL  DEFAULT  NULL,
    `format_dogs` VARCHAR(250) NULL  DEFAULT  NULL,
    `session` VARCHAR(3) NOT NULL,
    `export_lock` VARCHAR(255) NULL  DEFAULT  NULL,
    `valuta` VARCHAR(10) NULL  DEFAULT  NULL,
    `ipaccesse` VARCHAR(5) NULL  DEFAULT  NULL,
    `ipstart` VARCHAR(15) NULL  DEFAULT  NULL,
    `ipend` VARCHAR(15) NULL  DEFAULT  NULL,
    `iplist` MEDIUMTEXT NULL ,
    `maxupload` VARCHAR(3) NULL  DEFAULT  NULL COMMENT 'Максимальный размер файла для загрузки',
    `ipmask` VARCHAR(20) NULL  DEFAULT  NULL,
    `ext_allow` MEDIUMTEXT NOT NULL COMMENT 'Разрешенные типы файлов',
    `mailme` VARCHAR(5) NULL  DEFAULT  NULL,
    `mailout` VARCHAR(10) NULL  DEFAULT  NULL,
    `other` MEDIUMTEXT NULL  COMMENT 'Прочие настройке в формате json',
    `logo` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'Логотип компании',
    `acs_view` VARCHAR(3) NULL  DEFAULT 'on',
    `complect_on` VARCHAR(3) NULL  DEFAULT 'no',
    `zayavka_on` VARCHAR(3) NULL  DEFAULT 'no',
    `contract_format` VARCHAR(255) NULL  DEFAULT  NULL,
    `contract_num` INT NULL ,
    `inum` INT NULL ,
    `iformat` VARCHAR(255) NULL  DEFAULT  NULL,
    `akt_num` VARCHAR(20) NULL  DEFAULT '0',
    `akt_step` INT NULL ,
    `api_key` VARCHAR(255) NULL  DEFAULT  NULL,
    `coordinator` INT NULL ,
    `timezone` VARCHAR(255) NULL  DEFAULT 'Asia/Yekaterinburg' COMMENT 'Временная зона',
    `ivc` VARCHAR(255) NULL  DEFAULT  NULL,
    `dFormat` VARCHAR(255) NULL  DEFAULT  NULL,
    `dNum` VARCHAR(255) NULL  DEFAULT  NULL,
   PRIMARY KEY (`id`)
)  COMMENT='Основные настройки'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_settings` VALUES ('1','Наша','','http://nasha.ru','info@nasha.ru','+7(342)2067201','','','','{ClientName}','','','','30','30','30','30','9(999)999-99-99',NULL,'8(342)254-55-77',NULL,'99,999 999 999 999','14','','р.',NULL,'','','','20','','gif,jpg,jpeg,png,txt,doc,docx,xls,xlsx,ppt,pptx,rtf,pdf,7z,tar,zip,rar,gz,exe','yes',NULL,'no;no;no;yes;yes;yes;25;25;no;yes;no;yes;yes;yes;no;Дней;no;no;no;no;no;no;yes;yes;invoicedo;2;14;yes;yes;no;no;no;no;no;yes;yes;no;no;no;akt_full.tpl;invoice_qr.tpl;akt_full.tpl;invoice.tpl;no;no;no;no;no;no;no;no','logo.png',NULL,NULL,NULL,'{cnum}-{MM}{YY}/{YYYY}','0','2','{cnum}','1','7','VaSeZvkTfh5HMjJpNnge1W7Bloim0S',NULL,'Europe/Moscow','ifyb8VTNF4hf8kE7QclT9w==','СД{cnum}','1');#%%


DROP TABLE IF EXISTS `app_sip`;#%%
CREATE TABLE `app_sip` (
    `id` INT NOT NULL auto_increment,
    `active` VARCHAR(3) NOT NULL DEFAULT 'no',
    `tip` VARCHAR(20) NULL  DEFAULT  NULL,
    `sip_host` VARCHAR(255) NULL  DEFAULT  NULL,
    `sip_port` INT NULL ,
    `sip_channel` VARCHAR(30) NULL  DEFAULT  NULL,
    `sip_context` VARCHAR(255) NULL  DEFAULT  NULL,
    `sip_user` VARCHAR(100) NULL  DEFAULT  NULL,
    `sip_secret` VARCHAR(200) NULL  DEFAULT  NULL,
    `sip_numout` VARCHAR(3) NULL  DEFAULT  NULL,
    `sip_pfchange` VARCHAR(3) NULL  DEFAULT  NULL,
    `sip_path` VARCHAR(255) NULL  DEFAULT  NULL,
    `sip_cdr` VARCHAR(255) NULL  DEFAULT  NULL,
    `sip_secure` VARCHAR(5) NULL  DEFAULT  NULL,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Настройки подключения к астериску'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_sip` VALUES ('1','no','','','8089','SIP','from-internal','','','','','','','','1');#%%


DROP TABLE IF EXISTS `app_smtp`;#%%
CREATE TABLE `app_smtp` (
    `id` INT NOT NULL auto_increment,
    `active` VARCHAR(3) NOT NULL DEFAULT 'no',
    `smtp_host` VARCHAR(255) NULL  DEFAULT  NULL,
    `smtp_port` INT NULL ,
    `smtp_auth` VARCHAR(5) NULL  DEFAULT  NULL,
    `smtp_secure` VARCHAR(5) NULL  DEFAULT  NULL,
    `smtp_user` VARCHAR(100) NULL  DEFAULT  NULL,
    `smtp_pass` VARCHAR(200) NULL  DEFAULT  NULL,
    `smtp_from` VARCHAR(255) NULL  DEFAULT  NULL,
    `smtp_protocol` VARCHAR(5) NULL  DEFAULT  NULL,
    `tip` VARCHAR(10) NULL  DEFAULT  NULL,
    `name` VARCHAR(255) NULL  DEFAULT  NULL,
    `iduser` INT NULL  COMMENT 'id пользователя user.iduser',
    `divider` VARCHAR(3) NOT NULL DEFAULT ':',
    `filter` VARCHAR(255) NOT NULL DEFAULT 'заявка',
    `deletemess` VARCHAR(5) NOT NULL DEFAULT 'false',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Настройки подключения к почтовым службам'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_smtp` VALUES ('1','no','smtp.yandex.ru','587','true','tls','','','','','send','','0',':','','false','1');#%%


DROP TABLE IF EXISTS `app_speca`;#%%
CREATE TABLE `app_speca` (
    `spid` INT NOT NULL auto_increment,
    `prid` INT NULL  DEFAULT '0',
    `did` INT NULL  DEFAULT '0',
    `artikul` VARCHAR(100) NULL  DEFAULT  NULL,
    `title` VARCHAR(255) NULL  DEFAULT  NULL,
    `tip` INT NULL  DEFAULT '0',
    `price` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `price_in` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `kol` DOUBLE(20,2) NULL  DEFAULT '0.00',
    `edizm` VARCHAR(10) NULL  DEFAULT  NULL,
    `datum` TIMESTAMP NULL  DEFAULT CURRENT_TIMESTAMP,
    `nds` FLOAT(20,2) NULL ,
    `dop` INT NULL  DEFAULT '1',
    `comments` VARCHAR(250) NULL  DEFAULT  NULL,
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`spid`)
)  COMMENT='Позиции спецификаций к сделкам'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_steplog`;#%%
CREATE TABLE `app_steplog` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `step` INT NULL  COMMENT 'id этапа dogcategory.idcategory',
    `did` INT NULL  COMMENT 'id сделки dogovor.did',
    `iduser` INT NULL  COMMENT 'id пользователя user.iduser внес изменение',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `step` (`step`),
   INDEX `did` (`did`)
)  COMMENT='Лог изменений этапов сделок'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_tasks`;#%%
CREATE TABLE `app_tasks` (
    `tid` INT NOT NULL auto_increment,
    `maintid` INT NULL  DEFAULT '0',
    `iduser` INT NULL  DEFAULT '0',
    `clid` INT NULL  DEFAULT '0',
    `pid` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'personcat.pid (может быть несколько с разделением ;)',
    `did` INT NULL  DEFAULT '0',
    `cid` INT NULL  DEFAULT '0',
    `datum` DATE NULL ,
    `totime` TIME NULL  DEFAULT '09:00:00',
    `title` VARCHAR(250) NULL  DEFAULT  NULL,
    `des` TEXT NULL ,
    `tip` VARCHAR(100) NULL  DEFAULT 'Звонок',
    `active` VARCHAR(255) NULL  DEFAULT 'yes',
    `autor` INT NULL  DEFAULT '0',
    `priority` INT NULL  DEFAULT '0',
    `speed` INT NULL  DEFAULT '0',
    `created` DATETIME NULL ,
    `readonly` VARCHAR(3) NULL  DEFAULT 'no',
    `alert` VARCHAR(3) NULL  DEFAULT 'yes',
    `day` VARCHAR(3) NULL  DEFAULT  NULL,
    `status` INT NULL  DEFAULT '0',
    `alertTime` INT NULL  DEFAULT '0',
    `identity` VARCHAR(30) NULL  DEFAULT '1',
   PRIMARY KEY (`tid`),
   INDEX `iduser` (`iduser`),
   INDEX `tip` (`tip`),
   INDEX `clid` (`clid`),
   INDEX `did` (`did`),
   INDEX `identity` (`identity`),
   INDEX `autor` (`autor`),
   INDEX `cid` (`cid`)
)  COMMENT='Напоминания'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_territory_cat`;#%%
CREATE TABLE `app_territory_cat` (
    `idcategory` INT NOT NULL auto_increment,
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'наименование',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`idcategory`)
)  COMMENT='Территории'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_territory_cat` VALUES ('1','Пермь','1');#%%
INSERT INTO `app_territory_cat` VALUES ('3','Тюмень','1');#%%
INSERT INTO `app_territory_cat` VALUES ('4','Челябинск','1');#%%
INSERT INTO `app_territory_cat` VALUES ('5','Москва','1');#%%


DROP TABLE IF EXISTS `app_tpl`;#%%
CREATE TABLE `app_tpl` (
    `tid` INT NOT NULL auto_increment,
    `tip` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'тип',
    `name` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'название',
    `content` MEDIUMTEXT NULL  COMMENT 'сообщение',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`tid`)
)  COMMENT='Шаблоны для email-уведомлений'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%

INSERT INTO `app_tpl` VALUES ('1','new_client','Новая организация','Создана новая Организация - <strong>{link}</strong>','1');#%%
INSERT INTO `app_tpl` VALUES ('2','new_person','Новая персона','Создана новая персона - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('3','new_dog','Новая сделка','Я создал сделку&nbsp;{link}','1');#%%
INSERT INTO `app_tpl` VALUES ('4','edit_dog','Изменение в сделке','Я изменил статус сделки&nbsp;{link}','1');#%%
INSERT INTO `app_tpl` VALUES ('5','close_dog','Закрытие сделки','Я закрыл сделку -&nbsp;{link}','1');#%%
INSERT INTO `app_tpl` VALUES ('6','send_client','Вам назначена организация','Вы назначены ответственным за Организацию - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('7','send_person','Вам назначена персона','Вы назначены ответственным за Персону - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('8','trash_client','Изменение Ответственного','Ваша Организация перемещена в корзину - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('9','lead_add','Новый интерес','Новый входящий интерес - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('10','lead_setuser','Назначенный интерес','Вы назначены Ответственным за обработку входящего интереса - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('11','lead_do','Обработанный интерес','Я обработал интерес - {link}','1');#%%
INSERT INTO `app_tpl` VALUES ('12','leadClientNotifyTemp','Уведомление','&lt;div style=&quot;width:98%; max-width:600px; margin: 0 auto&quot;&gt;
&lt;div class=&quot;blok&quot; style=&quot;font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;
&lt;div style=&quot;color:black; font-size:14px; margin-top: 5px;&quot;&gt;&lt;strong&gt;Уважаемый {castomerName}!&lt;/strong&gt;&lt;br /&gt;
&lt;br /&gt;
&lt;br /&gt;
Благодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником &lt;strong&gt;{UserName}&lt;/strong&gt;.&lt;br /&gt;
&lt;br /&gt;
&lt;br /&gt;
Контакты сотрудника:&lt;br /&gt;
&amp;nbsp;
&lt;ul&gt;
	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Телефон:&lt;strong&gt; {UserPhone}&lt;/strong&gt;&lt;/li&gt;
	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Мобильный:&lt;strong&gt; {UserMob}&lt;/strong&gt;&lt;/li&gt;
	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Почта: &lt;strong&gt;{UserEmail}&lt;/strong&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;br /&gt;
В ближайшее время мы с вами свяжемся по указанному телефону или email.&lt;br /&gt;
&amp;nbsp;
&lt;hr /&gt;&lt;br /&gt;
С уважением, {compName}&lt;/div&gt;
&lt;/div&gt;

&lt;div align=&quot;right&quot; style=&quot;font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;Обработано в SalesMan CRM&lt;/div&gt;
&lt;/div&gt;
','1');#%%
INSERT INTO `app_tpl` VALUES ('13','leadSendWellcomeTemp','Уведомление','&lt;div style=&quot;width:98%; max-width:600px; margin: 0 auto&quot;&gt;
&lt;div class=&quot;blok&quot; style=&quot;font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;
&lt;div style=&quot;color:black; font-size:14px; margin-top: 5px;&quot;&gt;&lt;strong&gt;Уважаемый {castomerName}!&lt;/strong&gt;&lt;br /&gt;
&lt;br /&gt;
&lt;br /&gt;
Благодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником &lt;strong&gt;{UserName}&lt;/strong&gt;.&lt;br /&gt;
&lt;br /&gt;
&lt;br /&gt;
Контакты сотрудника:&lt;br /&gt;
&amp;nbsp;
&lt;ul&gt;
	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Телефон:&lt;strong&gt; {UserPhone}&lt;/strong&gt;&lt;/li&gt;
	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Мобильный:&lt;strong&gt; {UserMob}&lt;/strong&gt;&lt;/li&gt;
	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Почта: &lt;strong&gt;{UserEmail}&lt;/strong&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;br /&gt;
В ближайшее время мы с вами свяжемся по указанному телефону или email.&lt;br /&gt;
&amp;nbsp;
&lt;hr /&gt;&lt;br /&gt;
С уважением, {compName}&lt;/div&gt;
&lt;/div&gt;

&lt;div align=&quot;right&quot; style=&quot;font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;Обработано в SalesMan CRM&lt;/div&gt;
&lt;/div&gt;
','1');#%%


DROP TABLE IF EXISTS `app_uids`;#%%
CREATE TABLE `app_uids` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `name` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'название параметра',
    `value` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'знаение параметра',
    `lid` INT NULL  DEFAULT '0' COMMENT 'id заявки',
    `eid` INT NULL  DEFAULT '0' COMMENT 'id обращения',
    `clid` INT NULL  DEFAULT '0' COMMENT 'id записи клиента',
    `did` INT NULL  DEFAULT '0' COMMENT 'id записи сделки',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='База связки id сторонних систем с записями CRM'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_user`;#%%
CREATE TABLE `app_user` (
    `iduser` INT NOT NULL auto_increment,
    `login` VARCHAR(250) NOT NULL COMMENT 'Логин',
    `pwd` VARCHAR(250) NOT NULL COMMENT 'хеш пароля',
    `ses` TEXT NULL  COMMENT 'Сессия',
    `title` VARCHAR(250) NULL  DEFAULT  NULL COMMENT 'ФИО',
    `tip` VARCHAR(250) NULL  DEFAULT 'Менеджер продаж',
    `user_post` VARCHAR(255) NULL  DEFAULT  NULL,
    `mid` INT NULL  DEFAULT '0',
    `bid` INT NULL  DEFAULT '0',
    `otdel` TEXT NULL ,
    `email` TEXT NULL  COMMENT 'Email',
    `gcalendar` TEXT NULL ,
    `territory` INT NOT NULL DEFAULT '0',
    `office` INT NOT NULL DEFAULT '0',
    `phone` TEXT NULL  COMMENT 'Телефон',
    `phone_in` VARCHAR(20) NULL  DEFAULT  NULL COMMENT 'Добавочный номер',
    `fax` TEXT NULL ,
    `mob` TEXT NULL  COMMENT 'Мобильный',
    `bday` DATE NULL ,
    `acs_analitics` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Доступ к отчетам',
    `acs_maillist` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Доступ к рассылкам',
    `acs_files` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Доступ к разделу Файлы',
    `acs_price` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Доступ к разделу Прайс',
    `acs_credit` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Может ставить оплаты',
    `acs_prava` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Может просматривать чужие записи',
    `tzone` VARCHAR(5) NULL  DEFAULT  NULL COMMENT 'Временная зона',
    `viget_on` VARCHAR(500) NULL  DEFAULT 'on;on;on;on;on;on;on;on;on;on;on',
    `viget_order` VARCHAR(500) NULL  DEFAULT 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11',
    `secrty` VARCHAR(5) NOT NULL DEFAULT 'yes' COMMENT 'доступ в систему',
    `isadmin` VARCHAR(3) NOT NULL DEFAULT 'off' COMMENT 'признак администратора',
    `acs_import` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'разные права',
    `show_marga` VARCHAR(3) NOT NULL DEFAULT 'yes' COMMENT 'видит маржу',
    `acs_plan` VARCHAR(60) NOT NULL DEFAULT 'on' COMMENT 'имеет план продаж',
    `zam` INT NULL  DEFAULT '0',
    `CompStart` DATE NULL ,
    `CompEnd` DATE NULL ,
    `subscription` TEXT NULL  COMMENT 'подписки на email-уведомления',
    `avatar` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'аватар',
    `sole` VARCHAR(250) NULL  DEFAULT  NULL,
    `adate` DATE NULL ,
    `usersettings` TEXT NULL ,
    `uid` VARCHAR(30) NULL  DEFAULT  NULL,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`iduser`),
   INDEX `title` (`title`),
   INDEX `mid` (`mid`),
   INDEX `secrty` (`secrty`)
)  COMMENT='Сотрудники'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%


DROP TABLE IF EXISTS `app_ver`;#%%
CREATE TABLE `app_ver` (
    `id` INT NOT NULL auto_increment,
    `current` VARCHAR(10) NOT NULL,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`)
)   ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%


DROP TABLE IF EXISTS `app_webhook`;#%%
CREATE TABLE `app_webhook` (
    `id` INT NOT NULL auto_increment,
    `title` VARCHAR(255) NULL  DEFAULT 'event' COMMENT 'название ',
    `event` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'событие',
    `url` TINYTEXT NULL ,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Webhook'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_webhooklog`;#%%
CREATE TABLE `app_webhooklog` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `event` VARCHAR(50) NOT NULL,
    `query` TEXT NOT NULL,
    `response` TEXT NOT NULL,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Webhook. Лог работы'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_ymail_blacklist`;#%%
CREATE TABLE `app_ymail_blacklist` (
    `id` INT NOT NULL auto_increment COMMENT 'id записи',
    `email` VARCHAR(50) NULL  DEFAULT  NULL COMMENT 'e-mail ',
    `identity` INT NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Почтовик. Черный список email'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_ymail_files`;#%%
CREATE TABLE `app_ymail_files` (
    `id` INT NOT NULL auto_increment,
    `mid` INT NULL  COMMENT 'mail.id',
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата',
    `name` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'оригинальное имя файла',
    `file` VARCHAR(255) NULL  DEFAULT  NULL COMMENT 'переименнованое имя файла для системы',
    `identity` INT UNSIGNED NULL  DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Почтовик. Файлы полученные или отправленные почтой'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_ymail_messages`;#%%
CREATE TABLE `app_ymail_messages` (
    `id` INT NOT NULL auto_increment,
    `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата сообщения',
    `folder` VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT 'тип сообщения inbox => Входящее, outbox => Исходящее, draft => Черновик, trash => Корзина, sended => Отправлено',
    `trash` VARCHAR(30) NOT NULL DEFAULT 'no' COMMENT 'в корзине или нет сообщение (yes - в корзине)',
    `priority` INT NOT NULL DEFAULT '3',
    `state` VARCHAR(50) NULL  DEFAULT 'unread' COMMENT 'deleted - удаленные, read - прочитанные, unread - не прочинанны',
    `subbolder` VARCHAR(255) NULL  DEFAULT  NULL,
    `messageid` VARCHAR(255) NULL  DEFAULT  NULL,
    `uid` INT NULL ,
    `hid` INT NULL  COMMENT 'привязска с историей активности (history.cid)',
    `parentmid` VARCHAR(255) NULL  DEFAULT  NULL,
    `fromm` MEDIUMTEXT NULL ,
    `fromname` MEDIUMTEXT NULL ,
    `theme` VARCHAR(255) NULL  DEFAULT  NULL,
    `content` LONGTEXT NULL ,
    `iduser` INT NULL ,
    `fid` TEXT NULL ,
    `did` INT NULL ,
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `uid` (`uid`),
   INDEX `theme` (`theme`),
   INDEX `iduser` (`iduser`),
   INDEX `did` (`did`),
   INDEX `messageid` (`messageid`),
   INDEX `complex` (`folder`, `state`, `iduser`, `identity`),
   FULLTEXT INDEX `content` (`content`)
)  COMMENT='Модуль Почтовик. Список сообщений'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_ymail_messagesrec`;#%%
CREATE TABLE `app_ymail_messagesrec` (
    `id` INT NOT NULL auto_increment,
    `mid` INT NULL  COMMENT 'ymail_messages.id',
    `tip` VARCHAR(100) NULL  DEFAULT 'to' COMMENT 'полученно-отправленное письмо',
    `email` VARCHAR(100) NULL  DEFAULT  NULL COMMENT 'email',
    `name` VARCHAR(200) NULL  DEFAULT  NULL COMMENT 'имя отправителя-получателя',
    `clid` INT NULL  COMMENT 'clid в таблице clientcat.clid',
    `pid` INT NULL  COMMENT 'pid в таблице personcat.pid',
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `mid` (`mid`),
   INDEX `email` (`email`),
   INDEX `clid` (`clid`),
   INDEX `pid` (`pid`)
)  COMMENT='Модуль Почтовик. Для свзяи с карточками клиента и контакта'  ENGINE=MyISAM DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_ymail_settings`;#%%
CREATE TABLE `app_ymail_settings` (
    `id` INT NOT NULL auto_increment,
    `iduser` INT NOT NULL DEFAULT '0' COMMENT 'id пользователя user.iduser',
    `settings` TEXT NULL  COMMENT 'настройки',
    `lasttime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата и время последнего события',
    `identity` INT NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
)  COMMENT='Модуль Почтовик. Настройки почтовика'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%



DROP TABLE IF EXISTS `app_ymail_tpl`;#%%
CREATE TABLE `app_ymail_tpl` (
    `id` INT NOT NULL auto_increment,
    `name` VARCHAR(255) NULL  DEFAULT 'Шаблон',
    `content` TEXT NULL ,
    `share` VARCHAR(5) NULL  DEFAULT 'no',
    `iduser` INT NULL ,
    `identity` INT NULL  DEFAULT '1',
   PRIMARY KEY (`id`),
   INDEX `identity` (`identity`)
)  COMMENT='Модуль Почтовик. Шаблоны писем'  ENGINE=InnoDB DEFAULT CHARSET='utf8';#%%