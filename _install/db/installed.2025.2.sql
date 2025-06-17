-- MariaDB dump 10.17  Distrib 10.4.12-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: salesman
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `app_activities`
--

DROP TABLE IF EXISTS `app_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL COMMENT 'название',
  `color` varchar(7) DEFAULT NULL COMMENT 'цвет в RGB',
  `icon` varchar(100) DEFAULT NULL COMMENT 'иконка',
  `resultat` text COMMENT 'список готовых реультатов, разделенный ;',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'признак дефолтности',
  `aorder` int DEFAULT NULL COMMENT 'порядок вывода',
  `filter` varchar(255) DEFAULT 'all' COMMENT 'признак применимости (all - универсальный, task - только для задач, history - только для активностей',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `identity` (`identity`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COMMENT='Типы активностей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_activities`
--

LOCK TABLES `app_activities` WRITE;
/*!40000 ALTER TABLE `app_activities` DISABLE KEYS */;
INSERT INTO `app_activities` VALUES (1,'Первичный звонок','#009900',NULL,'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП','',8,'all',1),(2,'Факс','#cc00cc',NULL,'Отправлен и получен;Отправлен;Не отвечает;Не принимают','',16,'activ',1),(3,'Встреча','#ffcc00',NULL,'Состоялась;Перенос сроков;Отменена;Отпала необходимость','',5,'all',1),(4,'Задача','#ff6600',NULL,'Не выполнено;Перенос сроков;Отложено;Выполнено','yes',6,'all',1),(5,'Предложение','#66ccff',NULL,'Перенос;Отправлено КП;Отменено','',14,'activ',1),(6,'Событие','#666699',NULL,'Выполнено;Перенос;Отложено','',15,'activ',1),(7,'исх.Почта','#cccc00',NULL,'Отправлено КП;Отправлен Договор;Отправлена Презентация;Отправлена информация','',11,'all',1),(8,'вх.Звонок','#99cc00',NULL,'Новое обращение;Запрос счета;Запрос КП;Приглашение;Договорились о встрече','',7,'all',1),(9,'вх.Почта','#cc3300',NULL,'Отправлено;Не верный адрес;Отложено;Отменено','',10,'all',1),(10,'Поздравление','#009999',NULL,'Новый год;День Рождения;Праздник','',13,'task',1),(11,'исх.2.Звонок','#339966',NULL,'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП','',9,'all',1),(12,'Отправка КП','#ff0000',NULL,'Отправлено;Перенесено;Отложено;Отменено','',12,'all',1);
/*!40000 ALTER TABLE `app_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_budjet`
--

DROP TABLE IF EXISTS `app_budjet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_budjet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cat` int DEFAULT NULL COMMENT 'категория записи, ссылается на id в таблице _budjet_cat',
  `title` varchar(255) DEFAULT NULL COMMENT 'название расхода-дохода',
  `des` text COMMENT 'описание',
  `year` int DEFAULT NULL COMMENT 'год',
  `mon` int DEFAULT NULL COMMENT 'месяц',
  `summa` double(20,2) DEFAULT NULL COMMENT 'сумма',
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `iduser` int DEFAULT NULL COMMENT 'id пользователя _user.iduser',
  `do` varchar(3) DEFAULT NULL COMMENT 'признак того, что расход проведен',
  `rs` varchar(20) DEFAULT NULL COMMENT 'id расчетного счета из таблицы _mycomps_recv.id',
  `rs2` varchar(20) DEFAULT NULL COMMENT 'id расчетного счета (используется при перемещении средств)',
  `fid` text COMMENT 'id файлов из таблицы _files.fid разделенного запятой',
  `did` int DEFAULT NULL COMMENT 'id сделки из таблицы _dogovor.did',
  `conid` int DEFAULT NULL COMMENT '_clientcat.clid для поставщиков',
  `partid` int DEFAULT NULL COMMENT '_clientcat.clid для партнеров',
  `date_plan` date DEFAULT NULL COMMENT 'плановая дата',
  `invoice` varchar(255) DEFAULT NULL COMMENT 'номер счета',
  `invoice_date` date DEFAULT NULL COMMENT 'дата счета',
  `invoice_paydate` date DEFAULT NULL COMMENT 'дата оплаты счета',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Бюджет. Журнал расходов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_budjet`
--

LOCK TABLES `app_budjet` WRITE;
/*!40000 ALTER TABLE `app_budjet` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_budjet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_budjet_bank`
--

DROP TABLE IF EXISTS `app_budjet_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_budjet_bank` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'метка времени',
  `number` varchar(50) DEFAULT NULL COMMENT 'номер документа',
  `datum` date DEFAULT NULL COMMENT 'дата проводки',
  `mon` varchar(2) DEFAULT NULL COMMENT 'месяц',
  `year` varchar(4) DEFAULT NULL COMMENT 'год',
  `tip` varchar(10) DEFAULT NULL COMMENT 'направление расхода - dohod, rashod',
  `title` varchar(255) DEFAULT NULL COMMENT 'название расхода',
  `content` text COMMENT 'описание расхода',
  `rs` int DEFAULT NULL COMMENT 'id расчетного счета',
  `from` text COMMENT 'название плательщика',
  `fromRS` varchar(20) DEFAULT NULL COMMENT 'р.с. плательщика',
  `fromINN` varchar(12) DEFAULT NULL COMMENT 'инн плательщика',
  `to` text COMMENT 'название получателя',
  `toRS` varchar(20) DEFAULT NULL COMMENT 'р.с. получателя',
  `toINN` varchar(12) DEFAULT NULL COMMENT 'инн получателя',
  `summa` float(20,2) DEFAULT NULL COMMENT 'сумма расхода',
  `clid` int DEFAULT NULL COMMENT 'id связанного клиента',
  `bid` int DEFAULT NULL COMMENT 'id связанной записи в бюджете',
  `category` int DEFAULT NULL COMMENT 'id статьи расхода',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Журнал банковской выписки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_budjet_bank`
--

LOCK TABLES `app_budjet_bank` WRITE;
/*!40000 ALTER TABLE `app_budjet_bank` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_budjet_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_budjet_cat`
--

DROP TABLE IF EXISTS `app_budjet_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_budjet_cat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subid` int DEFAULT NULL COMMENT 'ид основной записи budjet_cat.id',
  `title` varchar(255) DEFAULT NULL COMMENT 'название',
  `tip` varchar(10) DEFAULT NULL COMMENT 'тип (расход-доход)',
  `clientpath` int DEFAULT NULL COMMENT 'id канала',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COMMENT='Статьи расхода-дохода Бюджета';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_budjet_cat`
--

LOCK TABLES `app_budjet_cat` WRITE;
/*!40000 ALTER TABLE `app_budjet_cat` DISABLE KEYS */;
INSERT INTO `app_budjet_cat` VALUES (1,0,'Расходы на офис','rashod',NULL,1),(2,1,'Аренда офиса','rashod',NULL,1),(3,1,'Телефония','rashod',NULL,1),(4,0,'Прочие поступления','dohod',NULL,1),(5,4,'Инвестиции','dohod',NULL,1),(7,1,'Продукты питания','rashod',NULL,1),(8,1,'Оборудование','rashod',NULL,1),(9,0,'Сотрудники','rashod',NULL,1),(10,9,'Зарплата','rashod',NULL,1),(11,9,'Премия','rashod',NULL,1),(12,9,'Командировочные','rashod',NULL,1),(13,4,'Наличка','dohod',NULL,1),(14,0,'Реклама','rashod',NULL,1),(15,14,'Интернет-реклама','rashod',177,1),(16,14,'Вебинары','rashod',86,1),(17,14,'Direct Mail','rashod',160,1),(18,0,'Расчеты с контрагентами','rashod',NULL,1),(19,18,'Поставщики','rashod',NULL,1),(20,18,'Партнеры','rashod',NULL,1);
/*!40000 ALTER TABLE `app_budjet_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_budjetlog`
--

DROP TABLE IF EXISTS `app_budjetlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_budjetlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
  `status` varchar(10) DEFAULT NULL COMMENT 'статус расхода',
  `bjid` int DEFAULT NULL COMMENT 'id расхода',
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser внес изменение',
  `comment` varchar(255) DEFAULT NULL COMMENT 'комментарий',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `bjid` (`bjid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Лог изменений статуса расходов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_budjetlog`
--

LOCK TABLES `app_budjetlog` WRITE;
/*!40000 ALTER TABLE `app_budjetlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_budjetlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_callhistory`
--

DROP TABLE IF EXISTS `app_callhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_callhistory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) NOT NULL COMMENT 'UID звонка из Астериска',
  `did` varchar(50) DEFAULT NULL COMMENT 'номер телефона наш если в src добавочный',
  `phone` varchar(20) NOT NULL COMMENT 'телефон клиента всегда',
  `direct` varchar(10) NOT NULL COMMENT 'направление вызова',
  `datum` datetime NOT NULL COMMENT 'дата-время вызова',
  `clid` int DEFAULT NULL COMMENT 'clid в таблице _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'pid в таблице _personcat.pid',
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser',
  `res` varchar(100) NOT NULL COMMENT 'результат вызова',
  `sec` int NOT NULL COMMENT 'продолжительность',
  `file` text COMMENT 'имя файла',
  `src` varchar(20) DEFAULT NULL COMMENT 'источник звонка',
  `dst` varchar(20) DEFAULT NULL COMMENT 'назначение звонка',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `statistica` (`phone`,`datum`,`iduser`,`res`,`direct`,`identity`),
  KEY `statistica2` (`direct`,`datum`,`iduser`,`identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='История звонков';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_callhistory`
--

LOCK TABLES `app_callhistory` WRITE;
/*!40000 ALTER TABLE `app_callhistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_callhistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_capacity_client`
--

DROP TABLE IF EXISTS `app_capacity_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_capacity_client` (
  `id` int NOT NULL AUTO_INCREMENT,
  `capid` int DEFAULT NULL COMMENT 'не используется',
  `clid` int DEFAULT NULL COMMENT 'clid в таблице _clientcat.clid',
  `direction` int DEFAULT NULL COMMENT 'направление деятельности из таблицы _direction.id',
  `year` int DEFAULT NULL COMMENT 'план на какой год',
  `mon` int DEFAULT NULL COMMENT 'план на какой месяц',
  `sumplan` double(20,2) DEFAULT NULL COMMENT 'план продаж в указанном периоде данному клиенту по данному направлению',
  `sumfact` double(20,2) DEFAULT NULL COMMENT 'факт продаж, при закрытии сделки суммируются',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Потенциал клиента';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_capacity_client`
--

LOCK TABLES `app_capacity_client` WRITE;
/*!40000 ALTER TABLE `app_capacity_client` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_capacity_client` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_category`
--

DROP TABLE IF EXISTS `app_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_category` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'название отрасли',
  `tip` varchar(10) NOT NULL DEFAULT 'client' COMMENT 'к какой записи относится (client,person,contractor,partner,concurent)',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Отрасли';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_category`
--

LOCK TABLES `app_category` WRITE;
/*!40000 ALTER TABLE `app_category` DISABLE KEYS */;
INSERT INTO `app_category` VALUES (1,'Физические лица','client',1),(2,'Розница','client',1);
/*!40000 ALTER TABLE `app_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_changepass`
--

DROP TABLE IF EXISTS `app_changepass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_changepass` (
  `id` int NOT NULL AUTO_INCREMENT,
  `useremail` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Хранение данных для смены пароля пользователя';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_changepass`
--

LOCK TABLES `app_changepass` WRITE;
/*!40000 ALTER TABLE `app_changepass` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_changepass` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_clientcat`
--

DROP TABLE IF EXISTS `app_clientcat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_clientcat` (
  `clid` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(30) DEFAULT NULL,
  `title` varchar(250) DEFAULT NULL,
  `idcategory` int DEFAULT '0',
  `iduser` varchar(10) DEFAULT '0',
  `clientpath` int DEFAULT '0',
  `des` text,
  `address` text,
  `phone` varchar(250) DEFAULT NULL,
  `fax` varchar(250) DEFAULT NULL,
  `site_url` varchar(250) DEFAULT NULL,
  `mail_url` varchar(250) DEFAULT NULL,
  `trash` varchar(10) DEFAULT 'no',
  `fav` varchar(20) DEFAULT 'no',
  `pid` int DEFAULT '0',
  `head_clid` int DEFAULT '0',
  `scheme` text,
  `tip_cmr` varchar(255) DEFAULT NULL,
  `territory` int DEFAULT '0',
  `input1` varchar(255) DEFAULT NULL,
  `input2` varchar(255) DEFAULT NULL,
  `input3` varchar(255) DEFAULT NULL,
  `input4` varchar(255) DEFAULT NULL,
  `input5` varchar(255) DEFAULT NULL,
  `input6` varchar(255) DEFAULT NULL,
  `input7` varchar(255) DEFAULT NULL,
  `input8` varchar(255) DEFAULT NULL,
  `input9` varchar(255) DEFAULT NULL,
  `input10` varchar(255) DEFAULT NULL,
  `date_create` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_edit` timestamp NULL DEFAULT NULL,
  `creator` int DEFAULT '0',
  `editor` int DEFAULT '0',
  `recv` text,
  `dostup` varchar(255) DEFAULT NULL,
  `last_dog` date DEFAULT NULL,
  `last_hist` datetime DEFAULT NULL,
  `type` varchar(100) DEFAULT 'client',
  `priceLevel` varchar(255) DEFAULT 'price_1',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`clid`),
  KEY `iduser` (`iduser`),
  KEY `identity` (`identity`),
  KEY `trash` (`trash`),
  KEY `uid` (`uid`),
  KEY `phone` (`phone`),
  KEY `fax` (`fax`),
  KEY `mail_url` (`mail_url`),
  KEY `type` (`type`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Клиенты';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_clientcat`
--

LOCK TABLES `app_clientcat` WRITE;
/*!40000 ALTER TABLE `app_clientcat` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_clientcat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_clientpath`
--

DROP TABLE IF EXISTS `app_clientpath`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_clientpath` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название источника',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'Дефолтный признак',
  `utm_source` varchar(255) DEFAULT NULL COMMENT 'Связка с источником',
  `destination` varchar(12) DEFAULT NULL COMMENT 'Связка с номером телефона',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COMMENT='Источник клиента';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_clientpath`
--

LOCK TABLES `app_clientpath` WRITE;
/*!40000 ALTER TABLE `app_clientpath` DISABLE KEYS */;
INSERT INTO `app_clientpath` VALUES (1,'Личные связи','','','',1),(2,'Маркетинг','','','',1),(3,'Справочник','','','',1),(4,'Заказ с сайта','yes','','',1),(5,'Рекомендации клиентов','','fromfriend','',1);
/*!40000 ALTER TABLE `app_clientpath` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_comments`
--

DROP TABLE IF EXISTS `app_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idparent` int DEFAULT '0' COMMENT 'comments.id -- ссылка на тему обсуждения',
  `mid` int DEFAULT NULL COMMENT 'DEPRECATED',
  `datum` timestamp NULL DEFAULT NULL,
  `clid` int DEFAULT NULL COMMENT 'clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'personcat.pid',
  `did` int DEFAULT NULL COMMENT 'dogovor.did',
  `prid` int DEFAULT NULL COMMENT 'price.n_id',
  `project` int DEFAULT NULL COMMENT 'id проекта',
  `iduser` int DEFAULT NULL COMMENT 'user.iduser',
  `title` varchar(255) DEFAULT NULL COMMENT 'заголовок',
  `content` text COMMENT 'текст',
  `fid` text COMMENT '_files.fid в виде списка с разделением ;',
  `lastCommentDate` datetime DEFAULT NULL COMMENT 'дата последнего коментария',
  `isClose` varchar(10) DEFAULT 'no' COMMENT 'закрыто или открыты обсуждение',
  `dateClose` datetime DEFAULT NULL COMMENT 'дата закрытия обсуждения',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `idparent` (`idparent`),
  KEY `isClose` (`isClose`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`),
  KEY `did` (`did`),
  KEY `project` (`project`),
  KEY `iduser` (`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Обсуждения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_comments`
--

LOCK TABLES `app_comments` WRITE;
/*!40000 ALTER TABLE `app_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_comments_subscribe`
--

DROP TABLE IF EXISTS `app_comments_subscribe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_comments_subscribe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcomment` int DEFAULT NULL COMMENT 'тема обсуждения _comments.id',
  `iduser` int DEFAULT NULL COMMENT 'пользователь _user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idcomment` (`idcomment`),
  KEY `iduser` (`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='модуль Обсуждения - участники обсуждения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_comments_subscribe`
--

LOCK TABLES `app_comments_subscribe` WRITE;
/*!40000 ALTER TABLE `app_comments_subscribe` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_comments_subscribe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_complect`
--

DROP TABLE IF EXISTS `app_complect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_complect` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT NULL COMMENT 'сделка _dogovor.id',
  `ccid` int DEFAULT NULL COMMENT 'тип контрольной точки _complect_cat.ccid',
  `data_plan` date DEFAULT NULL COMMENT 'плановая дата',
  `data_fact` date DEFAULT NULL COMMENT 'факт. дата выполнения',
  `doit` varchar(5) NOT NULL DEFAULT 'no' COMMENT 'признак выполнения',
  `iduser` int DEFAULT NULL COMMENT 'пользователь, выполнивший КТ _user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Контрольные точки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_complect`
--

LOCK TABLES `app_complect` WRITE;
/*!40000 ALTER TABLE `app_complect` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_complect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_complect_cat`
--

DROP TABLE IF EXISTS `app_complect_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_complect_cat` (
  `ccid` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `corder` int DEFAULT NULL,
  `dstep` int DEFAULT NULL,
  `role` text COMMENT 'список должностей, которым доступно изменение контр.точки в виде списка с разделением ,',
  `users` text COMMENT 'список сотрудников, которым доступно изменение контр.точки usser.iduser в виде списка с разделением ,',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ccid`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Контрольные точки. База';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_complect_cat`
--

LOCK TABLES `app_complect_cat` WRITE;
/*!40000 ALTER TABLE `app_complect_cat` DISABLE KEYS */;
INSERT INTO `app_complect_cat` VALUES (1,'Получение оплаты',4,7,'','',1),(2,'Подписать договор, Выставить счет',3,6,'','1',1),(3,'Согласование спецификации',2,5,'','1',1),(4,'Начать работы',5,0,'','',1),(5,'Получить документы',6,8,'Руководитель организации,Руководитель подразделения,Менеджер продаж','',1),(6,'Подготовка и отправка КП',1,11,'Руководитель подразделения','',1),(7,'Работы выполнены',7,0,'Руководитель организации,Руководитель подразделения,Руководитель отдела','',1);
/*!40000 ALTER TABLE `app_complect_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_contract`
--

DROP TABLE IF EXISTS `app_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contract` (
  `deid` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `number` varchar(255) DEFAULT NULL,
  `datum_start` date DEFAULT NULL,
  `datum_end` date DEFAULT NULL,
  `des` text,
  `clid` int DEFAULT '0',
  `payer` int DEFAULT NULL,
  `pid` int DEFAULT '0',
  `did` int DEFAULT NULL,
  `ftitle` varchar(255) DEFAULT NULL,
  `fname` varchar(250) DEFAULT NULL,
  `ftype` varchar(250) DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  `title` text,
  `idtype` int DEFAULT '0',
  `crid` int DEFAULT '0',
  `mcid` int DEFAULT '0',
  `signer` int DEFAULT '0',
  `status` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`deid`),
  KEY `did_iduser` (`did`,`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Документы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_contract`
--

LOCK TABLES `app_contract` WRITE;
/*!40000 ALTER TABLE `app_contract` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_contract` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_contract_poz`
--

DROP TABLE IF EXISTS `app_contract_poz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contract_poz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deid` int NOT NULL DEFAULT '0' COMMENT 'id документа (_contract.deid)',
  `did` int DEFAULT '0' COMMENT 'id сделки',
  `spid` int NOT NULL DEFAULT '0' COMMENT 'id позиции спецификации',
  `prid` int DEFAULT '0' COMMENT 'id позиции прайса',
  `kol` double(20,4) DEFAULT '0.0000' COMMENT 'количество товара',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Позиции спецификации для Актов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_contract_poz`
--

LOCK TABLES `app_contract_poz` WRITE;
/*!40000 ALTER TABLE `app_contract_poz` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_contract_poz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_contract_status`
--

DROP TABLE IF EXISTS `app_contract_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contract_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
  `tip` text COMMENT 'типы документов',
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'название статуса',
  `color` varchar(7) DEFAULT NULL COMMENT 'цвет статуса',
  `ord` int DEFAULT NULL COMMENT 'порядок вывода статуса',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Статусы документов по типам';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_contract_status`
--

LOCK TABLES `app_contract_status` WRITE;
/*!40000 ALTER TABLE `app_contract_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_contract_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_contract_statuslog`
--

DROP TABLE IF EXISTS `app_contract_statuslog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contract_statuslog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deid` int DEFAULT NULL COMMENT 'id документа',
  `status` int DEFAULT NULL COMMENT 'новый статус',
  `oldstatus` int DEFAULT NULL COMMENT 'старый статус',
  `iduser` int DEFAULT NULL COMMENT 'id сотрудника',
  `des` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT 'комментарий',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Лог изменения статуса документов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_contract_statuslog`
--

LOCK TABLES `app_contract_statuslog` WRITE;
/*!40000 ALTER TABLE `app_contract_statuslog` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_contract_statuslog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_contract_temp`
--

DROP TABLE IF EXISTS `app_contract_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contract_temp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `typeid` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COMMENT='Шаблоны документов (кроме счетов и актов)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_contract_temp`
--

LOCK TABLES `app_contract_temp` WRITE;
/*!40000 ALTER TABLE `app_contract_temp` DISABLE KEYS */;
INSERT INTO `app_contract_temp` VALUES (1,1,'Квитанция на оплату','kvitancia_sberbank_pd4.docx',1),(2,4,'Базовый шаблон','invoice.tpl',1),(3,2,'Приёма-передачи. Права','akt_prava.tpl',1),(4,2,'Приёма-передачи. Услуги','akt_simple.tpl',1),(5,2,'Приёма-передачи. Услуги (расширенный)','akt_full.tpl',1),(6,3,'Счет-фактура (XLCX)','schet_faktura.xlsx',1),(7,4,'Счет с QRcode','invoice_qr.tpl',1);
/*!40000 ALTER TABLE `app_contract_temp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_contract_type`
--

DROP TABLE IF EXISTS `app_contract_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_contract_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'название документа',
  `type` varchar(255) DEFAULT NULL COMMENT 'внутренний тип get_akt, get_aktper, get_dogovor, invoice',
  `role` text COMMENT 'список ролей, которым доступно изменение',
  `users` text COMMENT 'список пользователей, которые могут добавлять такие документы -- user.iduser с разделением ,',
  `num` int DEFAULT NULL COMMENT 'счетчик нумерации',
  `format` varchar(255) DEFAULT NULL COMMENT 'шаблон формата номера',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COMMENT='Типы документа';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_contract_type`
--

LOCK TABLES `app_contract_type` WRITE;
/*!40000 ALTER TABLE `app_contract_type` DISABLE KEYS */;
INSERT INTO `app_contract_type` VALUES (1,'Квитанция в банк','','','',0,'{cnum}',1),(2,'Акт приема-передачи','get_akt','','',0,'{cnum}',1),(3,'Счет-фактура','','','',0,'{cnum}',1),(4,'Счет','invoice','','',0,'',1),(5,'Договор','get_dogovor','','',0,'',1);
/*!40000 ALTER TABLE `app_contract_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_credit`
--

DROP TABLE IF EXISTS `app_credit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_credit` (
  `crid` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT '0',
  `clid` int DEFAULT '0',
  `pid` int DEFAULT '0',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datum_credit` date DEFAULT NULL,
  `summa_credit` double(20,2) DEFAULT '0.00',
  `nds_credit` double(20,2) DEFAULT '0.00',
  `iduser` int DEFAULT '0',
  `idowner` int DEFAULT '0',
  `do` varchar(5) DEFAULT 'no',
  `invoice` varchar(20) DEFAULT NULL,
  `invoice_chek` varchar(40) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `rs` int DEFAULT '0',
  `tip` varchar(255) DEFAULT NULL,
  `template` int DEFAULT '0',
  `suffix` text,
  `signer` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`crid`),
  KEY `do` (`do`),
  KEY `did` (`did`),
  KEY `clid` (`clid`),
  KEY `iduser` (`iduser`),
  KEY `datum_credit` (`datum_credit`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Счета';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_credit`
--

LOCK TABLES `app_credit` WRITE;
/*!40000 ALTER TABLE `app_credit` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_credit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_currency`
--

DROP TABLE IF EXISTS `app_currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_currency` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` date DEFAULT NULL COMMENT 'дата добавления',
  `name` varchar(50) DEFAULT NULL COMMENT 'название валюты',
  `view` varchar(10) DEFAULT NULL COMMENT 'отображаемое название валюты',
  `code` varchar(10) DEFAULT NULL COMMENT 'код валюты',
  `course` double(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'текущий курс',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Таблица курсов валют';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_currency`
--

LOCK TABLES `app_currency` WRITE;
/*!40000 ALTER TABLE `app_currency` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_currency_log`
--

DROP TABLE IF EXISTS `app_currency_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_currency_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcurrency` int DEFAULT NULL COMMENT 'id записи валюты',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
  `course` double(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'курс на дату',
  `iduser` varchar(10) DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Таблица изменения курсов валют';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_currency_log`
--

LOCK TABLES `app_currency_log` WRITE;
/*!40000 ALTER TABLE `app_currency_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_currency_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_customsettings`
--

DROP TABLE IF EXISTS `app_customsettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_customsettings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время добавления-изменения',
  `tip` varchar(50) DEFAULT NULL COMMENT 'тип параметра',
  `params` text COMMENT 'параметры',
  `iduser` int DEFAULT NULL COMMENT 'id сотрудника',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Хранилище различных настроек';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_customsettings`
--

LOCK TABLES `app_customsettings` WRITE;
/*!40000 ALTER TABLE `app_customsettings` DISABLE KEYS */;
INSERT INTO `app_customsettings` VALUES (1,'2021-10-28 19:41:02','eform','{\"client\":{\"title\":{\"active\":\"yes\",\"requered\":\"yes\",\"more\":\"no\"},\"head_clid\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"phone\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"idcategory\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mail_url\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"site_url\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"address\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"fax\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"tip_cmr\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"clientpath\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"territory\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input1\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input3\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input4\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"des\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input2\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input5\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input6\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input7\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input9\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input8\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input10\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"}},\"person\":{\"ptitle\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"person\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"rol\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"tel\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input7\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mob\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mail\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"loyalty\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input1\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input3\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input4\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input5\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input6\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input12\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"}}}',NULL,1),(2,'2020-08-13 07:14:27','settingsMore','{\"timecheck\":\"yes\",\"budjetEnableVijets\":\"no\"}',1,1);
/*!40000 ALTER TABLE `app_customsettings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_deal_anketa`
--

DROP TABLE IF EXISTS `app_deal_anketa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_deal_anketa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idbase` int NOT NULL DEFAULT '0' COMMENT 'id поля анкеты',
  `ida` int NOT NULL COMMENT 'id анкеты',
  `did` int DEFAULT NULL COMMENT 'id сделки',
  `clid` int DEFAULT NULL COMMENT 'id клиента',
  `value` varchar(255) DEFAULT NULL COMMENT 'варианты значений',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Значения для анкет по сделкам';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_deal_anketa`
--

LOCK TABLES `app_deal_anketa` WRITE;
/*!40000 ALTER TABLE `app_deal_anketa` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_deal_anketa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_deal_anketa_base`
--

DROP TABLE IF EXISTS `app_deal_anketa_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_deal_anketa_base` (
  `id` int NOT NULL AUTO_INCREMENT,
  `block` int NOT NULL DEFAULT '0' COMMENT 'id блока',
  `ida` int NOT NULL COMMENT 'id анкеты',
  `name` varchar(255) NOT NULL COMMENT 'Название поля',
  `tip` varchar(10) NOT NULL COMMENT 'Тип поля',
  `value` text COMMENT 'Возможные значения',
  `ord` int DEFAULT NULL COMMENT 'Порядок вывода',
  `pole` varchar(10) DEFAULT NULL COMMENT 'id поля',
  `pwidth` int DEFAULT '50' COMMENT 'ширина поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='База полей для анкеты';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_deal_anketa_base`
--

LOCK TABLES `app_deal_anketa_base` WRITE;
/*!40000 ALTER TABLE `app_deal_anketa_base` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_deal_anketa_base` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_deal_anketa_list`
--

DROP TABLE IF EXISTS `app_deal_anketa_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_deal_anketa_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` int NOT NULL DEFAULT '1' COMMENT 'Активность анкеты',
  `datum` datetime NOT NULL COMMENT 'Дата создания',
  `datum_edit` datetime NOT NULL COMMENT 'Дата изменения',
  `title` varchar(255) DEFAULT NULL COMMENT 'Название анкеты',
  `content` text COMMENT 'Описание анкеты',
  `iduser` int DEFAULT NULL COMMENT 'id Сотрудника-автора',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Список базовых анкет для сделок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_deal_anketa_list`
--

LOCK TABLES `app_deal_anketa_list` WRITE;
/*!40000 ALTER TABLE `app_deal_anketa_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_deal_anketa_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_direction`
--

DROP TABLE IF EXISTS `app_direction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_direction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'название',
  `isDefault` varchar(5) DEFAULT NULL COMMENT 'признак дефолтности',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Направления деятельности';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_direction`
--

LOCK TABLES `app_direction` WRITE;
/*!40000 ALTER TABLE `app_direction` DISABLE KEYS */;
INSERT INTO `app_direction` VALUES (1,'Основное','yes',1);
/*!40000 ALTER TABLE `app_direction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_dogcategory`
--

DROP TABLE IF EXISTS `app_dogcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_dogcategory` (
  `idcategory` bigint NOT NULL AUTO_INCREMENT,
  `title` int DEFAULT NULL COMMENT 'название типа',
  `content` text COMMENT 'описание',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`),
  KEY `identity` (`identity`),
  KEY `title` (`title`),
  KEY `identity_2` (`identity`),
  KEY `title_2` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COMMENT='Этапы сделок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_dogcategory`
--

LOCK TABLES `app_dogcategory` WRITE;
/*!40000 ALTER TABLE `app_dogcategory` DISABLE KEYS */;
INSERT INTO `app_dogcategory` VALUES (2,20,'Подтвержден интерес',1),(5,60,'Обсуждение деталей - продукты, услуги, оплата',1),(6,80,'Согласован договор, Выставлен счет',1),(7,90,'Получена предоплата, Выполнение договора',1),(8,100,'Закрытие сделки, Подписание документов',1),(10,0,'Проявлен/Выявлен интерес',1),(11,40,'Отправлено КП',1);
/*!40000 ALTER TABLE `app_dogcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_dogovor`
--

DROP TABLE IF EXISTS `app_dogovor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_dogovor` (
  `did` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(30) DEFAULT NULL,
  `idcategory` int DEFAULT '0',
  `clid` int DEFAULT '0',
  `payer` int DEFAULT '0',
  `pid` int DEFAULT '0',
  `datum` date DEFAULT NULL,
  `autor` int DEFAULT '0',
  `datum_plan` date DEFAULT NULL,
  `title` text,
  `content` text,
  `tip` varchar(100) DEFAULT '0',
  `kol` double(20,2) DEFAULT '0.00',
  `close` varchar(5) DEFAULT 'no',
  `lat` float(10,6) DEFAULT NULL,
  `lan` float(10,6) DEFAULT NULL,
  `adres` text,
  `iduser` int DEFAULT '0',
  `datum_izm` date DEFAULT NULL,
  `datum_close` date DEFAULT NULL,
  `sid` int DEFAULT '0',
  `kol_fact` double(20,2) DEFAULT '0.00',
  `des_fact` text,
  `coid` varchar(12) DEFAULT NULL,
  `co_kol` double(20,2) DEFAULT '0.00',
  `coid1` text,
  `coid2` varchar(12) DEFAULT NULL,
  `dog_num` text,
  `marga` double(20,2) DEFAULT '0.00',
  `calculate` varchar(4) DEFAULT NULL,
  `isFrozen` int DEFAULT '0',
  `datum_start` date DEFAULT NULL,
  `datum_end` date DEFAULT NULL,
  `pid_list` varchar(255) DEFAULT NULL,
  `partner` varchar(100) DEFAULT NULL,
  `zayavka` varchar(200) DEFAULT NULL,
  `ztitle` varchar(255) DEFAULT NULL,
  `mcid` int DEFAULT '0',
  `direction` int DEFAULT '0',
  `idcurrency` int DEFAULT '0',
  `idcourse` int DEFAULT '0',
  `akt_date` date DEFAULT NULL,
  `akt_temp` varchar(200) DEFAULT NULL,
  `lid` int DEFAULT '0',
  `input1` varchar(512) DEFAULT NULL,
  `input2` varchar(512) DEFAULT NULL,
  `input3` varchar(512) DEFAULT NULL,
  `input4` varchar(512) DEFAULT NULL,
  `input5` varchar(512) DEFAULT NULL,
  `input6` varchar(512) DEFAULT NULL,
  `input7` varchar(512) DEFAULT NULL,
  `input8` varchar(512) DEFAULT NULL,
  `input9` varchar(512) DEFAULT NULL,
  `input10` varchar(512) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`did`),
  KEY `identity` (`identity`),
  KEY `iduser` (`iduser`),
  KEY `idcategory` (`idcategory`),
  KEY `tip` (`tip`),
  KEY `direction` (`direction`),
  KEY `datum_plan` (`datum_plan`),
  KEY `clid` (`clid`),
  KEY `note` (`iduser`,`identity`),
  KEY `sid` (`sid`),
  KEY `close` (`close`),
  KEY `datum` (`datum`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Сделки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_dogovor`
--

LOCK TABLES `app_dogovor` WRITE;
/*!40000 ALTER TABLE `app_dogovor` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_dogovor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_dogprovider`
--

DROP TABLE IF EXISTS `app_dogprovider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_dogprovider` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT '0',
  `conid` int DEFAULT '0',
  `partid` int DEFAULT '0',
  `summa` double(20,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT NULL,
  `bid` int DEFAULT '0',
  `recal` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Расходы по сделке на партнеров и поставщиков';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_dogprovider`
--

LOCK TABLES `app_dogprovider` WRITE;
/*!40000 ALTER TABLE `app_dogprovider` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_dogprovider` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_dogstatus`
--

DROP TABLE IF EXISTS `app_dogstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_dogstatus` (
  `sid` bigint NOT NULL AUTO_INCREMENT,
  `title` text COMMENT 'название',
  `result_close` varchar(5) DEFAULT NULL COMMENT 'Результат закрытия: lose - Проигрыш; win - Победа',
  `content` text COMMENT 'описание',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COMMENT='Статусы закрытия сделок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_dogstatus`
--

LOCK TABLES `app_dogstatus` WRITE;
/*!40000 ALTER TABLE `app_dogstatus` DISABLE KEYS */;
INSERT INTO `app_dogstatus` VALUES (1,'Победа полная','win','Обозначает выигрыш, Договор выполнен и получена прибыль',1),(2,'Победа, договорились с конкурентами','win','Сделка выиграна, заключен и исполнен договор, получена прибыль',1),(3,'Проигрыш по цене','lose','Договор не заключен, проиграли по цене',1),(4,'Проигрыш, договорились с конкурентами','lose','Сделка проиграна, но удалось договориться с конкурентами.',1),(5,'Отменена Заказчиком','lose','Сделка отменена Заказчиком',1),(6,'Отказ от участия','lose','Мы отказались от участия в сделке',1),(7,'Закрыл менеджер. Отказ','lose','Проигрыш',1);
/*!40000 ALTER TABLE `app_dogstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_dogtips`
--

DROP TABLE IF EXISTS `app_dogtips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_dogtips` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `title` text COMMENT 'название',
  `isDefault` varchar(5) DEFAULT NULL COMMENT 'признак дефолтности',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COMMENT='Типы сделок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_dogtips`
--

LOCK TABLES `app_dogtips` WRITE;
/*!40000 ALTER TABLE `app_dogtips` DISABLE KEYS */;
INSERT INTO `app_dogtips` VALUES (1,'Продажа простая','',1),(2,'Продажа с разработкой','',1),(3,'Услуги','',1),(4,'Продажа услуг','',1),(5,'Тендер','',1),(6,'Продажа быстрая','yes',1);
/*!40000 ALTER TABLE `app_dogtips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_dostup`
--

DROP TABLE IF EXISTS `app_dostup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_dostup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clid` int DEFAULT NULL COMMENT 'Запись клиента _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'Запись контакта _personcat.pid',
  `did` int DEFAULT NULL COMMENT 'Запись сделки _dogovor.did',
  `iduser` int DEFAULT NULL COMMENT 'Сотрудник, которому дан доступ _user.iduser',
  `subscribe` varchar(3) DEFAULT 'off' COMMENT 'отправлять уведомления (on-off) по сделкам',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `yindex` (`clid`,`pid`,`did`,`iduser`),
  KEY `clid` (`clid`),
  KEY `did` (`did`),
  KEY `iduser` (`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Доступы к карточкам клиентов, сделок ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_dostup`
--

LOCK TABLES `app_dostup` WRITE;
/*!40000 ALTER TABLE `app_dostup` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_dostup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_doubles`
--

DROP TABLE IF EXISTS `app_doubles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_doubles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
  `tip` text COMMENT 'типы дубля',
  `idmain` int DEFAULT NULL COMMENT 'id проверяемой записи',
  `list` varchar(500) DEFAULT NULL COMMENT 'json-массив найденных дублей',
  `ids` varchar(100) DEFAULT NULL COMMENT 'список всех id, упомятутых в list',
  `status` varchar(3) DEFAULT 'no' COMMENT 'статус',
  `datumdo` timestamp NULL DEFAULT NULL COMMENT 'дата обработки',
  `des` text COMMENT 'комментарий',
  `iduser` varchar(10) DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `filter` (`id`,`tip`(10),`idmain`,`ids`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Лог поиска дублей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_doubles`
--

LOCK TABLES `app_doubles` WRITE;
/*!40000 ALTER TABLE `app_doubles` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_doubles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_entry`
--

DROP TABLE IF EXISTS `app_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_entry` (
  `ide` int NOT NULL AUTO_INCREMENT,
  `uid` int DEFAULT NULL,
  `clid` int DEFAULT NULL COMMENT 'Клиент _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'Контакт _personcat.pid',
  `did` int DEFAULT NULL COMMENT 'Созданная сделка _dogovor.did',
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания',
  `datum_do` timestamp NULL DEFAULT NULL COMMENT 'дата обработки обращения',
  `iduser` int DEFAULT NULL COMMENT 'ответственный user.iduser',
  `autor` int DEFAULT NULL COMMENT 'автор user.iduser',
  `content` text COMMENT 'коментарий',
  `status` int DEFAULT '0' COMMENT 'Статус обработки: 0-новое, 1-обработано, 2 - отмена',
  `identity` int NOT NULL,
  PRIMARY KEY (`ide`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Обращения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_entry`
--

LOCK TABLES `app_entry` WRITE;
/*!40000 ALTER TABLE `app_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_entry_poz`
--

DROP TABLE IF EXISTS `app_entry_poz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_entry_poz` (
  `idp` int NOT NULL AUTO_INCREMENT,
  `ide` int DEFAULT NULL COMMENT 'Обращение _entry.ide',
  `prid` int DEFAULT NULL COMMENT 'Связь с прайсом _price.n_id, не обязательный',
  `title` varchar(255) DEFAULT NULL COMMENT 'название позиции',
  `kol` int DEFAULT NULL COMMENT 'количество',
  `price` double NOT NULL DEFAULT '0' COMMENT 'цена',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Обращения. Позиции в обращении';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_entry_poz`
--

LOCK TABLES `app_entry_poz` WRITE;
/*!40000 ALTER TABLE `app_entry_poz` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_entry_poz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_field`
--

DROP TABLE IF EXISTS `app_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_field` (
  `fld_id` int NOT NULL AUTO_INCREMENT,
  `fld_tip` varchar(10) DEFAULT NULL COMMENT 'тип поля - client, person, price, dogovor',
  `fld_name` varchar(10) DEFAULT NULL COMMENT 'имя поля в БД',
  `fld_title` varchar(100) DEFAULT NULL COMMENT 'название поля для интерфейса',
  `fld_required` varchar(10) DEFAULT 'required' COMMENT 'признак обязательности',
  `fld_on` varchar(255) DEFAULT 'yes' COMMENT 'признак активности поля',
  `fld_order` int DEFAULT NULL COMMENT 'порядок вывода',
  `fld_stat` varchar(10) DEFAULT NULL COMMENT 'можно ли поле выключить',
  `fld_temp` varchar(255) DEFAULT NULL COMMENT 'тип поля - input, select...',
  `fld_var` text COMMENT 'вариант готовых ответов',
  `fld_sub` varchar(10) DEFAULT NULL COMMENT 'доп.разделение для карточек клиентов - клиент, поставщик, партнер..',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`fld_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1065 DEFAULT CHARSET=utf8mb3 COMMENT='Поля форм';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_field`
--

LOCK TABLES `app_field` WRITE;
/*!40000 ALTER TABLE `app_field` DISABLE KEYS */;
INSERT INTO `app_field` VALUES (15,'client','input1','доп.поле',NULL,NULL,14,'no','--Обычное--','',NULL,1),(16,'client','input2','доп.поле',NULL,NULL,20,'no','--Обычное--','до 3х,св.3 до 10,св.10 до 50,св.50',NULL,1),(17,'client','input3','доп.поле',NULL,NULL,15,'no','--Обычное--','',NULL,1),(1,'client','title','Название','required','yes',1,'yes','--Обычное--','',NULL,1),(2,'client','iduser','Ответственный','required','yes',3,'yes','','',NULL,1),(3,'client','idcategory','Категория','','',5,'yes','--Обычное--','',NULL,1),(4,'client','head_clid','Головн. орг-ия','','',2,'yes','--Обычное--','',NULL,1),(5,'client','pid','Осн. контакт','','',9,'yes','','',NULL,1),(6,'client','address','Адрес','','yes',8,'yes','adres','',NULL,1),(7,'client','phone','Телефон','','yes',4,'yes','','',NULL,1),(8,'client','fax','Факс','','yes',10,'yes','','',NULL,1),(9,'client','site_url','Сайт',NULL,'yes',7,'yes','','',NULL,1),(10,'client','mail_url','Почта',NULL,'yes',6,'yes','','',NULL,1),(11,'client','territory','Территория','','',13,'yes','','',NULL,1),(12,'client','des','Описание',NULL,'',18,'yes','','',NULL,1),(13,'client','scheme','Принятие решений','','',17,'yes','--Обычное--','',NULL,1),(14,'client','tip_cmr','Тип отношений','','',11,'yes','--Обычное--','',NULL,1),(25,'person','clid','Клиент','','yes',5,'yes','--Обычное--','',NULL,1),(26,'person','ptitle','Должность','required','yes',2,'yes','','',NULL,1),(27,'person','person','Ф.И.О.','required','yes',1,'yes','','',NULL,1),(28,'person','tel','Тел.','','yes',6,'yes','','',NULL,1),(29,'person','fax','Факс',NULL,'yes',8,'yes','','',NULL,1),(30,'person','mob','Моб.','','yes',9,'yes','','',NULL,1),(31,'person','mail','Почта','','yes',11,'yes','','',NULL,1),(32,'person','rol','Роль','','',3,'yes','','',NULL,1),(33,'person','social','Прочее',NULL,'',14,'yes','','',NULL,1),(34,'person','iduser','Куратор','required','yes',4,'yes','--Обычное--','',NULL,1),(35,'person','loyalty','Лояльность','','',12,'yes','--Обычное--','',NULL,1),(36,'person','input1','Дата рождения','','',13,'no','datum','',NULL,1),(37,'person','input2','доп.поле',NULL,NULL,15,'no','--Обычное--','',NULL,1),(38,'person','input3','доп.поле',NULL,NULL,16,'no','--Обычное--','',NULL,1),(39,'person','input4','доп.поле',NULL,NULL,17,'no','--Обычное--','',NULL,1),(40,'person','input5','доп.поле',NULL,NULL,18,'no','--Обычное--','',NULL,1),(41,'person','input6','доп.поле',NULL,NULL,19,'no','--Обычное--','',NULL,1),(42,'person','input7','Добавочный',NULL,'yes',7,'no','--Обычное--','',NULL,1),(43,'person','input8','доп.поле','','',20,'no','--Обычное--','',NULL,1),(44,'person','input9','доп.поле','','',21,'no','--Обычное--','',NULL,1),(18,'client','input4','доп.поле',NULL,NULL,16,'no','--Обычное--','',NULL,1),(19,'client','input5','доп.поле',NULL,NULL,21,'no','--Обычное--','',NULL,1),(20,'client','input6','доп.поле',NULL,NULL,22,'no','--Обычное--','',NULL,1),(21,'client','input7','доп.поле',NULL,NULL,23,'no','--Обычное--','',NULL,1),(22,'client','input8','доп.поле',NULL,NULL,25,'no','--Обычное--','',NULL,1),(23,'client','input9','доп.поле',NULL,NULL,24,'no','--Обычное--','',NULL,1),(24,'client','input10','доп.поле',NULL,NULL,26,'no','--Обычное--','',NULL,1),(45,'client','recv','Реквизиты','','yes',19,'yes','--Обычное--','',NULL,1),(46,'client','clientpath','Источник клиента','','',12,'yes','','',NULL,1),(47,'person','clientpath','Канал привлечения','','',10,'yes','--Обычное--','',NULL,1),(48,'dogovor','zayavka','Номер заявки','','',1,'no','','',NULL,1),(49,'dogovor','ztitle','Основание','','',1,'no','','',NULL,1),(50,'dogovor','mcid','Компания','required','yes',2,'yes','--Обычное--','',NULL,1),(51,'dogovor','iduser','Куратор','required','yes',3,'yes','','',NULL,1),(52,'dogovor','datum_plan','Дата план.','required','yes',4,'yes','datum','',NULL,1),(53,'dogovor','period','Период действия',NULL,'',5,'no','','',NULL,1),(54,'dogovor','idcategory','Этап','','yes',6,'yes','','',NULL,1),(55,'dogovor','dog_num','Договор','','yes',7,'no','','',NULL,1),(56,'dogovor','tip','Тип сделки','required','yes',8,'no','--Обычное--','',NULL,1),(57,'dogovor','direction','Направление','required','yes',9,'yes','','',NULL,1),(58,'dogovor','adres','Адрес','','',10,'no','','',NULL,1),(59,'dogovor','money','Деньги',NULL,'yes',11,'yes','','',NULL,1),(60,'dogovor','content','Описание','','',12,'no','','',NULL,1),(61,'dogovor','pid_list','Персоны','','yes',13,'no','--Обычное--','',NULL,1),(62,'dogovor','payer','Плательщик','','yes',14,'yes','--Обычное--','',NULL,1),(64,'dogovor','kol','Сумма план.','','yes',NULL,'yes','','',NULL,1),(65,'dogovor','kol_fact','Сумма факт.','','yes',NULL,'yes','','',NULL,1),(66,'dogovor','marg','Прибыль','','yes',NULL,'yes','','',NULL,1),(67,'dogovor','oborot','Сумма','','yes',NULL,'yes','','',NULL,1),(68,'price','price_in','Закуп','required','yes',NULL,'','','',NULL,1),(69,'price','price_1','Розница','required','yes',NULL,'','','35',NULL,1),(70,'price','price_2','Уровень 1','','yes',NULL,'','','25',NULL,1),(71,'price','price_3','Уровень 2','required','yes',NULL,'','','20',NULL,1),(72,'price','price_4','Уровень 3','','',NULL,'','','15',NULL,1),(73,'price','price_5','Уровень 4','','',NULL,'','','10',NULL,1),(880,'dogovor','input1','доп.поле',NULL,NULL,19,'','--Обычное--','',NULL,1),(881,'dogovor','input2','доп.поле',NULL,NULL,20,'','--Обычное--','',NULL,1),(882,'dogovor','input3','доп.поле',NULL,NULL,21,'','--Обычное--','',NULL,1),(883,'dogovor','input4','доп.поле',NULL,NULL,22,'','--Обычное--','',NULL,1),(884,'dogovor','input5','доп.поле',NULL,NULL,23,'','--Обычное--','',NULL,1),(885,'dogovor','input6','доп.поле',NULL,NULL,24,'','--Обычное--','',NULL,1),(970,'dogovor','input7','доп.поле',NULL,NULL,25,'','--Обычное--','',NULL,1),(971,'dogovor','input8','доп.поле',NULL,NULL,26,'','--Обычное--','',NULL,1),(972,'dogovor','input9','доп.поле',NULL,NULL,27,'','--Обычное--','',NULL,1),(973,'dogovor','input10','доп.поле',NULL,NULL,28,'','--Обычное--','',NULL,1),(1064,'person','input10','доп.поле','','',22,'no','','',NULL,1);
/*!40000 ALTER TABLE `app_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_file`
--

DROP TABLE IF EXISTS `app_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_file` (
  `fid` int NOT NULL AUTO_INCREMENT,
  `ftitle` varchar(255) DEFAULT NULL COMMENT 'оригинальное название файла',
  `fname` varchar(255) DEFAULT NULL COMMENT 'имя, хранимое в системе',
  `ftype` text COMMENT 'тип файла',
  `fver` int DEFAULT NULL COMMENT 'версия',
  `ftag` text COMMENT 'описание файла',
  `iduser` int DEFAULT NULL COMMENT 'Автор _user.iduser',
  `clid` int DEFAULT NULL COMMENT 'Клиент _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'Контакт _personcat.pid',
  `did` int DEFAULT NULL COMMENT 'Сдекла _dogovor.did',
  `tskid` int DEFAULT NULL COMMENT 'DEPRECATED',
  `coid` int DEFAULT NULL COMMENT 'DEPRECATED',
  `folder` varchar(255) DEFAULT NULL COMMENT 'Папка _file_cat.idcategory',
  `datum` datetime DEFAULT NULL,
  `size` int DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`fid`),
  KEY `ftitle` (`ftitle`),
  KEY `folder` (`folder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Файлы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_file`
--

LOCK TABLES `app_file` WRITE;
/*!40000 ALTER TABLE `app_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_file_cat`
--

DROP TABLE IF EXISTS `app_file_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_file_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `subid` int DEFAULT '0',
  `title` varchar(250) DEFAULT NULL,
  `shared` varchar(3) DEFAULT 'no' COMMENT 'общая папка (yes)',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`),
  KEY `subid` (`subid`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COMMENT='Папки файлов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_file_cat`
--

LOCK TABLES `app_file_cat` WRITE;
/*!40000 ALTER TABLE `app_file_cat` DISABLE KEYS */;
INSERT INTO `app_file_cat` VALUES (1,0,'Коммерческие предложения клиентам','',1),(2,10,'Спецификации','',1),(3,0,'Презентации','yes',1),(4,8,'Прочее','',1),(5,0,'Изображения','yes',1),(6,1,'КП','',1),(7,8,'Прайс конкурента','',1),(8,0,'Разное','no',1),(9,0,'Рассылки','no',1),(10,0,'Документы','yes',1);
/*!40000 ALTER TABLE `app_file_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_group`
--

DROP TABLE IF EXISTS `app_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT 'имя группы',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления группы',
  `type` int DEFAULT NULL COMMENT 'DEPRECATED',
  `service` varchar(60) DEFAULT NULL COMMENT 'Связка с сервисом _services.name',
  `idservice` varchar(100) DEFAULT NULL COMMENT 'id группы во внешнем сервисе',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Группы. список групп';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_group`
--

LOCK TABLES `app_group` WRITE;
/*!40000 ALTER TABLE `app_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_grouplist`
--

DROP TABLE IF EXISTS `app_grouplist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_grouplist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gid` int NOT NULL COMMENT 'Группа _group.id',
  `clid` int(10) unsigned zerofill DEFAULT NULL COMMENT 'Клиент _clientcat.clid',
  `pid` int(10) unsigned zerofill DEFAULT NULL COMMENT 'Контакт _personcat.pid',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подписки',
  `person_id` int(10) unsigned zerofill DEFAULT NULL COMMENT 'не используется',
  `service` varchar(255) DEFAULT NULL COMMENT 'Имя сервиса _services.name',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'имя подписчика',
  `user_email` varchar(255) DEFAULT NULL COMMENT 'email подписчика',
  `user_phone` varchar(15) DEFAULT NULL COMMENT 'телефон подписчика',
  `tags` text COMMENT 'тэги',
  `status` varchar(100) DEFAULT NULL COMMENT 'статус подписчика',
  `availability` varchar(100) DEFAULT NULL COMMENT 'доступность подписчика',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `gid_clid_identity` (`gid`,`clid`,`identity`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Группы. список подписчиков в группах';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_grouplist`
--

LOCK TABLES `app_grouplist` WRITE;
/*!40000 ALTER TABLE `app_grouplist` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_grouplist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_history`
--

DROP TABLE IF EXISTS `app_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_history` (
  `cid` int NOT NULL AUTO_INCREMENT,
  `clid` int DEFAULT '0',
  `pid` varchar(100) DEFAULT NULL,
  `did` int DEFAULT '0',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datum_izm` datetime DEFAULT NULL,
  `des` text,
  `iduser` int DEFAULT '0',
  `iduser_izm` int DEFAULT '0',
  `tip` varchar(50) DEFAULT NULL,
  `fid` varchar(255) DEFAULT NULL,
  `uid` varchar(100) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`cid`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`),
  KEY `did` (`did`),
  KEY `iduser` (`iduser`),
  KEY `identity` (`identity`),
  KEY `tip` (`tip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='История активностей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_history`
--

LOCK TABLES `app_history` WRITE;
/*!40000 ALTER TABLE `app_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_incoming`
--

DROP TABLE IF EXISTS `app_incoming`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_incoming` (
  `p_identity` int NOT NULL,
  `p_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `p_text` text NOT NULL,
  UNIQUE KEY `p_identity` (`p_identity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='кэширующая таблица для запросов из астериска';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_incoming`
--

LOCK TABLES `app_incoming` WRITE;
/*!40000 ALTER TABLE `app_incoming` DISABLE KEYS */;
INSERT INTO `app_incoming` VALUES (1,'2017-12-12 12:35:24','{\"Response\":\"Success\",\"Message\":\"Channel status will follow\",\"data\":{\"1\":{\"Event\":\"StatusComplete\",\"Items\":\"0\"}}}');
/*!40000 ALTER TABLE `app_incoming` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_incoming_channels`
--

DROP TABLE IF EXISTS `app_incoming_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_incoming_channels` (
  `p_identity` int NOT NULL,
  `p_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `p_text` text NOT NULL,
  UNIQUE KEY `p_identity` (`p_identity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='кэширующая таблица для запросов из астериска';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_incoming_channels`
--

LOCK TABLES `app_incoming_channels` WRITE;
/*!40000 ALTER TABLE `app_incoming_channels` DISABLE KEYS */;
INSERT INTO `app_incoming_channels` VALUES (1,'2017-11-21 09:11:40','{\"Response\":\"Success\",\"data\":[{\"EventList\":\"start\"},{\"Event\":\"CoreShowChannelsComplete\",\"EventList\":\"Complete\",\"ListItems\":\"0\"}],\"Message\":\"Channels will follow\"}');
/*!40000 ALTER TABLE `app_incoming_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kb`
--

DROP TABLE IF EXISTS `app_kb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kb` (
  `idcat` int NOT NULL AUTO_INCREMENT,
  `subid` int DEFAULT NULL COMMENT 'ссылка на головную папку',
  `title` varchar(255) DEFAULT NULL COMMENT 'название папки',
  `share` varchar(5) DEFAULT NULL COMMENT 'DEPRECATED',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль  База знаний. Список папок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kb`
--

LOCK TABLES `app_kb` WRITE;
/*!40000 ALTER TABLE `app_kb` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_kb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kbtags`
--

DROP TABLE IF EXISTS `app_kbtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kbtags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT 'тэг',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль База знаний. список тэгов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kbtags`
--

LOCK TABLES `app_kbtags` WRITE;
/*!40000 ALTER TABLE `app_kbtags` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_kbtags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_knowledgebase`
--

DROP TABLE IF EXISTS `app_knowledgebase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_knowledgebase` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcat` int DEFAULT NULL COMMENT 'Папка _kb.idcat',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата публикации',
  `title` varchar(255) DEFAULT NULL COMMENT 'название статьи',
  `content` mediumtext COMMENT 'содержание статьи',
  `count` int DEFAULT NULL COMMENT 'число просмотров',
  `active` varchar(5) DEFAULT NULL COMMENT 'признак черновика',
  `pin` varchar(5) DEFAULT 'no' COMMENT 'Закрепление статьи',
  `pindate` datetime DEFAULT NULL COMMENT 'Дата закрепления статьи',
  `keywords` text COMMENT 'тэги',
  `author` int DEFAULT NULL COMMENT 'Автор _user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль База знаний. Статьи';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_knowledgebase`
--

LOCK TABLES `app_knowledgebase` WRITE;
/*!40000 ALTER TABLE `app_knowledgebase` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_knowledgebase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kpi`
--

DROP TABLE IF EXISTS `app_kpi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kpi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kpi` int DEFAULT NULL COMMENT 'ID показателя',
  `year` int DEFAULT NULL COMMENT 'Год',
  `period` varchar(10) DEFAULT NULL COMMENT 'Период расчета',
  `iduser` int DEFAULT NULL COMMENT 'ID сотрудника (iduser)',
  `val` int DEFAULT NULL COMMENT 'Значение показателя',
  `isPersonal` tinyint(1) DEFAULT '0' COMMENT 'Признок персонального показателя',
  `identity` int DEFAULT NULL COMMENT 'ID аккаунта',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='База KPI сотрудников';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kpi`
--

LOCK TABLES `app_kpi` WRITE;
/*!40000 ALTER TABLE `app_kpi` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_kpi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kpibase`
--

DROP TABLE IF EXISTS `app_kpibase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kpibase` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'Название показателя',
  `tip` varchar(20) DEFAULT NULL COMMENT 'Тип показателя',
  `values` text COMMENT 'Список значений показателя для расчетов',
  `subvalues` text COMMENT 'Список дополнительных значений',
  `identity` int DEFAULT NULL COMMENT 'ID аккаунта',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Базовые показатели KPI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kpibase`
--

LOCK TABLES `app_kpibase` WRITE;
/*!40000 ALTER TABLE `app_kpibase` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_kpibase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kpiseason`
--

DROP TABLE IF EXISTS `app_kpiseason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kpiseason` (
  `id` int NOT NULL AUTO_INCREMENT,
  `year` int DEFAULT NULL,
  `rate` mediumtext COMMENT 'значения сезонного коэффициента в json',
  `kpi` text COMMENT 'id показателя',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Сезонные коэффициенты для показателей KPI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kpiseason`
--

LOCK TABLES `app_kpiseason` WRITE;
/*!40000 ALTER TABLE `app_kpiseason` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_kpiseason` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_leads`
--

DROP TABLE IF EXISTS `app_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datum_do` datetime DEFAULT NULL COMMENT 'дата обработки',
  `status` int DEFAULT NULL COMMENT 'статус 0 => Открыт, 1 => В работе, 2 => Обработан, 3 => Закрыт',
  `rezult` int DEFAULT NULL COMMENT 'результат обработки 1 => Спам, 2 => Дубль, 3 => Другое, 4 => Не целевой',
  `title` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `description` text COMMENT 'описание заявки',
  `ip` varchar(16) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `timezone` varchar(5) DEFAULT NULL,
  `iduser` int DEFAULT '0' COMMENT '_user.iduser',
  `clientpath` int DEFAULT NULL COMMENT '_clientpath.id',
  `pid` int DEFAULT NULL COMMENT '_personcat.pid',
  `clid` int DEFAULT NULL COMMENT '_clientcat.clid',
  `did` int DEFAULT NULL COMMENT '_dogovor.did',
  `partner` int DEFAULT NULL COMMENT '_clientcat.clid',
  `muid` varchar(255) DEFAULT NULL,
  `rezz` text COMMENT 'комментарий при дисквалификации заявки',
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_referrer` varchar(255) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Сборщик заявок. Заявки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_leads`
--

LOCK TABLES `app_leads` WRITE;
/*!40000 ALTER TABLE `app_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_leads_utm`
--

DROP TABLE IF EXISTS `app_leads_utm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_leads_utm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clientpath` int NOT NULL DEFAULT '0' COMMENT 'id Источника из _clientpath',
  `utm_source` varchar(255) DEFAULT NULL COMMENT 'Название источника',
  `utm_url` varchar(500) DEFAULT NULL COMMENT 'Адрес целевой страницы',
  `utm_medium` varchar(255) DEFAULT NULL COMMENT 'Канал кампании',
  `utm_campaign` varchar(255) DEFAULT NULL COMMENT 'Название кампании',
  `utm_term` varchar(255) DEFAULT NULL COMMENT 'Ключевые слова, фраза',
  `utm_content` varchar(255) DEFAULT NULL COMMENT 'Доп.описание кампании',
  `site` varchar(255) DEFAULT NULL COMMENT 'Адрес сайта',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Сборщик заявок. Каталог UTM-ссылок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_leads_utm`
--

LOCK TABLES `app_leads_utm` WRITE;
/*!40000 ALTER TABLE `app_leads_utm` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_leads_utm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_logapi`
--

DROP TABLE IF EXISTS `app_logapi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_logapi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content` mediumtext NOT NULL,
  `rez` text NOT NULL,
  `ip` varchar(20) NOT NULL,
  `remoteaddr` text NOT NULL,
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Логи API';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_logapi`
--

LOCK TABLES `app_logapi` WRITE;
/*!40000 ALTER TABLE `app_logapi` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_logapi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_logs`
--

DROP TABLE IF EXISTS `app_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(100) NOT NULL,
  `iduser` int NOT NULL COMMENT 'id пользователя user.iduser',
  `content` text NOT NULL,
  `identity` int NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COMMENT='Логи авторизаций и др.действий';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_logs`
--

LOCK TABLES `app_logs` WRITE;
/*!40000 ALTER TABLE `app_logs` DISABLE KEYS */;
INSERT INTO `app_logs` VALUES (1,'2024-03-14 11:44:04','Авторизация',1,'Пользователь авторизовался в системе',1),(2,'2024-03-14 11:44:06','Начало дня',1,'Первый запуск за день',1),(3,'2024-03-14 11:44:27','Администрирование',1,'Пользователь вошел в панель администратора',1),(4,'2024-05-16 06:01:08','Авторизация',1,'Пользователь авторизовался в системе',1),(5,'2024-05-16 06:01:10','Начало дня',1,'Первый запуск за день',1),(6,'2024-07-03 07:31:58','Авторизация',1,'Пользователь авторизовался в системе',1),(7,'2024-07-03 07:32:01','Начало дня',1,'Первый запуск за день',1),(8,'2025-06-16 12:50:26','Авторизация',1,'Пользователь авторизовался в системе',1),(9,'2025-06-16 12:50:28','Начало дня',1,'Первый запуск за день',1),(10,'2025-06-16 12:51:12','Администрирование',1,'Пользователь вошел в панель администратора',1);
/*!40000 ALTER TABLE `app_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_loyal_cat`
--

DROP TABLE IF EXISTS `app_loyal_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_loyal_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'название',
  `color` varchar(7) NOT NULL DEFAULT '#CCCCCC' COMMENT 'цвет',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'признак дефолтности',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COMMENT='Типы лояльности';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_loyal_cat`
--

LOCK TABLES `app_loyal_cat` WRITE;
/*!40000 ALTER TABLE `app_loyal_cat` DISABLE KEYS */;
INSERT INTO `app_loyal_cat` VALUES (2,'0 - Не лояльный','#333333','',1),(3,'4 - Очень Лояльный','#ff0000','',1),(4,'2 - Нейтральный','#99ccff','',1),(1,'3 - Лояльный','#ff00ff','',1),(5,'1 - Не понятно','#CCCCCC','yes',1),(6,'5 - ВиП','#cedb9c','',1);
/*!40000 ALTER TABLE `app_loyal_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_mail`
--

DROP TABLE IF EXISTS `app_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_mail` (
  `mid` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата рассылки',
  `title` text COMMENT 'название',
  `descr` text COMMENT 'описание',
  `theme` varchar(255) DEFAULT NULL COMMENT 'тема сообщения',
  `tip` varchar(20) DEFAULT NULL COMMENT 'тип рассылки (от пользователя или от компании)',
  `iduser` int DEFAULT NULL COMMENT 'автор user.iduser',
  `tpl_id` int DEFAULT NULL COMMENT 'храним ид шаблона mail_tpl.tpl_id',
  `client_list` text COMMENT 'список clientcat.clid, разделенный ;',
  `person_list` text COMMENT 'список personcat.pid, разделенный ;',
  `file` text COMMENT 'file.fid - прикрепленные файлы с разделением ;',
  `do` varchar(5) DEFAULT NULL COMMENT 'признак проведения рассылки',
  `template` mediumtext NOT NULL COMMENT 'текст сообщения',
  `clist_do` text NOT NULL COMMENT 'список clientcat.clid, разделенный ; которым отправлено сообщение',
  `plist_do` text NOT NULL COMMENT 'список personcat.pid, разделенный ; которым отправлено сообщение',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль рассылок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_mail`
--

LOCK TABLES `app_mail` WRITE;
/*!40000 ALTER TABLE `app_mail` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_mail_tpl`
--

DROP TABLE IF EXISTS `app_mail_tpl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_mail_tpl` (
  `tpl_id` int NOT NULL AUTO_INCREMENT,
  `name_tpl` varchar(250) DEFAULT NULL COMMENT 'имя шаблона',
  `content_tpl` mediumtext COMMENT 'содержание шаблона',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tpl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль рассылок. шаблоны писем';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_mail_tpl`
--

LOCK TABLES `app_mail_tpl` WRITE;
/*!40000 ALTER TABLE `app_mail_tpl` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_mail_tpl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog`
--

DROP TABLE IF EXISTS `app_modcatalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prid` int NOT NULL COMMENT 'price.n_id',
  `idz` int DEFAULT NULL COMMENT 'modcatalog_zayavka.id',
  `content` mediumtext COMMENT 'описание позиции',
  `datum` datetime NOT NULL COMMENT 'дата',
  `price_plus` double DEFAULT NULL,
  `status` int DEFAULT '0' COMMENT 'статус (в наличии и тд.)',
  `kol` double DEFAULT '0' COMMENT 'количество',
  `files` text COMMENT 'прикрепленные файлы в формате json',
  `sklad` int NOT NULL COMMENT 'modcatalog_sklad.id',
  `iduser` int NOT NULL COMMENT 'user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `prid` (`prid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Список позиций';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog`
--

LOCK TABLES `app_modcatalog` WRITE;
/*!40000 ALTER TABLE `app_modcatalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_akt`
--

DROP TABLE IF EXISTS `app_modcatalog_akt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_akt` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT '0',
  `tip` varchar(100) DEFAULT NULL,
  `number` int DEFAULT '0',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clid` int DEFAULT '0',
  `posid` int DEFAULT '0',
  `man1` varchar(255) DEFAULT NULL,
  `man2` varchar(255) DEFAULT NULL,
  `isdo` varchar(5) DEFAULT NULL,
  `cFactura` varchar(20) DEFAULT NULL,
  `cDate` date DEFAULT NULL,
  `sklad` int DEFAULT '0',
  `idz` int DEFAULT '0',
  `identity` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Ордера';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_akt`
--

LOCK TABLES `app_modcatalog_akt` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_akt` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_akt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_aktpoz`
--

DROP TABLE IF EXISTS `app_modcatalog_aktpoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_aktpoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ida` int NOT NULL COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `price_in` double NOT NULL DEFAULT '0' COMMENT 'цена по ордеру',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'количество по приходному-расходному ордеру',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Позиции ордеров';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_aktpoz`
--

LOCK TABLES `app_modcatalog_aktpoz` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_aktpoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_aktpoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_dop`
--

DROP TABLE IF EXISTS `app_modcatalog_dop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_dop` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prid` int NOT NULL COMMENT 'price.n_id',
  `bid` int NOT NULL COMMENT 'по-моему не используется',
  `datum` date NOT NULL,
  `content` text NOT NULL COMMENT 'наименование доп. затрат',
  `summa` double NOT NULL COMMENT 'стоимость доп. затрат',
  `clid` int NOT NULL COMMENT 'clientcat.clid',
  `iduser` int NOT NULL COMMENT 'user.iduser',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Доп.затараты по позициям каталога';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_dop`
--

LOCK TABLES `app_modcatalog_dop` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_dop` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_dop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_field`
--

DROP TABLE IF EXISTS `app_modcatalog_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pfid` int NOT NULL COMMENT 'modcatalog_fieldcat.id',
  `n_id` int NOT NULL COMMENT 'price.n_id',
  `value` varchar(255) NOT NULL COMMENT 'значение доп поля для данной продукции',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Доп.поля к позициям';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_field`
--

LOCK TABLES `app_modcatalog_field` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_fieldcat`
--

DROP TABLE IF EXISTS `app_modcatalog_fieldcat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_fieldcat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'название доп поля',
  `tip` varchar(10) NOT NULL COMMENT 'тип вывода: поле ввода, поле текста, список выбора, чекбоксы, радиокнопки и разделитель',
  `value` text NOT NULL COMMENT 'выбираемы значения',
  `ord` int NOT NULL COMMENT 'порядковый номер поля в списке доп. полей',
  `pole` varchar(10) NOT NULL,
  `pwidth` int NOT NULL DEFAULT '50' COMMENT 'ширина поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Каталог доп.полей к позициям каталога';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_fieldcat`
--

LOCK TABLES `app_modcatalog_fieldcat` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_fieldcat` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_fieldcat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_log`
--

DROP TABLE IF EXISTS `app_modcatalog_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dopzid` int NOT NULL COMMENT 'modcatalog_dop.id',
  `datum` datetime NOT NULL COMMENT 'дата изменения',
  `tip` varchar(255) NOT NULL COMMENT 'где происходит измениение: catalog, dop, kol, price, status',
  `new` text NOT NULL COMMENT 'было ',
  `old` text NOT NULL COMMENT 'стало',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `iduser` int NOT NULL COMMENT 'id пользователя user.iduser',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. История изменений по позициям';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_log`
--

LOCK TABLES `app_modcatalog_log` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_offer`
--

DROP TABLE IF EXISTS `app_modcatalog_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_offer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` datetime NOT NULL COMMENT 'дата предложения',
  `datum_end` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT 'статус 0-актуальная, 1-закрытая',
  `iduser` int NOT NULL COMMENT 'user.iduser',
  `content` text NOT NULL COMMENT 'коментарий предложения',
  `des` text NOT NULL COMMENT 'данные по НДС, названию предложения, сумме',
  `users` text NOT NULL COMMENT 'user.iduser с разделением ; принявшие предложение снабжения (голосование)',
  `prid` int NOT NULL COMMENT 'id созданной позиции (price.n_id)',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Предложения от снабжения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_offer`
--

LOCK TABLES `app_modcatalog_offer` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_offer` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_offer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_reserv`
--

DROP TABLE IF EXISTS `app_modcatalog_reserv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_reserv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int NOT NULL COMMENT 'dogovor.did',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата резерва',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'кол-во резерва',
  `status` varchar(30) NOT NULL COMMENT 'статус резерва (действует-снят)',
  `idz` int NOT NULL DEFAULT '0' COMMENT 'id заявки, по которой ставили резерв (modcatalog_zayavka.id)',
  `ida` int NOT NULL DEFAULT '0' COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)',
  `sklad` int NOT NULL DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Резерв';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_reserv`
--

LOCK TABLES `app_modcatalog_reserv` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_reserv` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_reserv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_set`
--

DROP TABLE IF EXISTS `app_modcatalog_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_set` (
  `id` int NOT NULL AUTO_INCREMENT,
  `settings` text NOT NULL COMMENT 'настройки',
  `ftp` text NOT NULL COMMENT 'настройки ftp',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Настройки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_set`
--

LOCK TABLES `app_modcatalog_set` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_set` DISABLE KEYS */;
INSERT INTO `app_modcatalog_set` VALUES (1,'{\"mcArtikul\":\"yes\",\"mcStep\":\"6\",\"mcStepPers\":\"80\",\"mcKolEdit\":null,\"mcStatusEdit\":null,\"mcUseOrder\":\"yes\",\"mcCoordinator\":[\"1\",\"20\",\"22\",\"14\",\"13\",\"18\"],\"mcSpecialist\":[\"1\",\"23\",\"22\",\"3\"],\"mcAutoRezerv\":\"yes\",\"mcAutoWork\":\"yes\",\"mcAutoStatus\":null,\"mcSklad\":\"yes\",\"mcSkladPoz\":null,\"mcAutoProvider\":\"yes\",\"mcAutoPricein\":\"yes\",\"mcDBoardSkladName\":\"Наличие\",\"mcDBoardSklad\":\"yes\",\"mcDBoardZayavkaName\":\"Заявки\",\"mcDBoardZayavka\":\"yes\",\"mcDBoardOfferName\":\"Предложения\",\"mcDBoardOffer\":\"yes\",\"mcMenuTip\":\"inMain\",\"mcMenuPlace\":\"\",\"mcOfferName1\":\"\",\"mcOfferName2\":\"\",\"mcPriceCat\":[\"245\",\"247\",\"246\",\"1\",\"156\",\"154\",\"4\",\"158\",\"153\",\"180\",\"177\",\"176\",\"173\",\"172\",\"171\",\"170\",\"174\",\"175\",\"178\"]}','{\"mcFtpServer\":\"\",\"mcFtpUser\":\"\",\"mcFtpPass\":\"\",\"mcFtpPath\":\"\"}',1);
/*!40000 ALTER TABLE `app_modcatalog_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_sklad`
--

DROP TABLE IF EXISTS `app_modcatalog_sklad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_sklad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'название склада',
  `mcid` int NOT NULL COMMENT 'привязка к компании (mycomps.id)',
  `isDefault` varchar(5) NOT NULL DEFAULT 'no' COMMENT 'склад по умолчанию для каждой компании',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. список складов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_sklad`
--

LOCK TABLES `app_modcatalog_sklad` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_sklad` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_sklad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_skladmove`
--

DROP TABLE IF EXISTS `app_modcatalog_skladmove`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_skladmove` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата перемещения',
  `skladfrom` int DEFAULT '0' COMMENT 'id склада с которого перемещаем',
  `skladto` int DEFAULT '0' COMMENT 'id склада на который перемещаем',
  `iduser` int DEFAULT '0' COMMENT 'id сотрудника, сделавшего перемещение',
  `identity` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Лог перемещения позиций между склдами';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_skladmove`
--

LOCK TABLES `app_modcatalog_skladmove` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_skladmove` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_skladmove` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_skladmovepoz`
--

DROP TABLE IF EXISTS `app_modcatalog_skladmovepoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_skladmovepoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idm` int NOT NULL DEFAULT '0' COMMENT 'id группы перемещения (modcatalog_skladmove.id)',
  `idp` int NOT NULL DEFAULT '0' COMMENT 'id позиции из таблицы modcatalog_skladpoz',
  `prid` int NOT NULL DEFAULT '0' COMMENT 'id позиции прайса (price.n_id)',
  `kol` double(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'количество для общего учета',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Позиции перемещения между складами';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_skladmovepoz`
--

LOCK TABLES `app_modcatalog_skladmovepoz` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_skladmovepoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_skladmovepoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_skladpoz`
--

DROP TABLE IF EXISTS `app_modcatalog_skladpoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_skladpoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prid` int NOT NULL DEFAULT '0' COMMENT 'id товара (price.n_id)',
  `sklad` int NOT NULL DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
  `status` varchar(5) NOT NULL DEFAULT 'out',
  `date_in` date DEFAULT NULL COMMENT 'дата поступления',
  `date_out` date DEFAULT NULL COMMENT 'дата выбытия',
  `serial` varchar(255) DEFAULT NULL COMMENT 'серийный номер',
  `date_create` date DEFAULT NULL COMMENT 'дата производства',
  `date_period` date DEFAULT NULL COMMENT 'дата (например поверки)',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'кол-во',
  `did` int DEFAULT NULL COMMENT 'id сделки, на которую позиция списана (поштучный учет) (dogovor.did)',
  `idorder_in` int DEFAULT '0' COMMENT 'id приходного ордера (modcatalog_akt.id)',
  `idorder_out` int DEFAULT '0' COMMENT 'id расходного ордера (modcatalog_akt.id)',
  `summa` double(20,2) DEFAULT '0.00' COMMENT 'стоимость для расх.ордера',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `prid` (`prid`),
  KEY `sklad` (`sklad`),
  KEY `did` (`did`),
  KEY `identity` (`identity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Позиции на складах';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_skladpoz`
--

LOCK TABLES `app_modcatalog_skladpoz` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_skladpoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_skladpoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_zayavka`
--

DROP TABLE IF EXISTS `app_modcatalog_zayavka`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_zayavka` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number` varchar(50) NOT NULL DEFAULT '0' COMMENT 'номер заявки',
  `did` int NOT NULL COMMENT 'dogovor.did',
  `datum` datetime NOT NULL COMMENT 'дата заявки',
  `datum_priority` date DEFAULT NULL COMMENT 'желаемая дата (срочность)',
  `datum_start` datetime NOT NULL COMMENT 'дата начало выполнения заявки',
  `datum_end` datetime NOT NULL COMMENT 'дата окончания выполнения заявки',
  `status` int NOT NULL DEFAULT '0' COMMENT '0 - создана, 1-в работе, 2- выполнено, 3-отмена',
  `iduser` int NOT NULL COMMENT 'автор user.iduser',
  `sotrudnik` int NOT NULL COMMENT 'ответственный user.iduser',
  `content` text NOT NULL COMMENT 'коментарий заявки',
  `rezult` text NOT NULL,
  `des` text NOT NULL COMMENT 'заполнение доп полей',
  `isHight` varchar(3) DEFAULT 'no',
  `cInvoice` varchar(20) DEFAULT NULL,
  `cDate` date DEFAULT NULL COMMENT 'Дата счета поставщика',
  `cSumma` double(20,2) DEFAULT '0.00' COMMENT 'сумма счета поставщика',
  `bid` int DEFAULT '0' COMMENT 'Связка с записью в Расходах',
  `providerid` int DEFAULT '0' COMMENT 'id записи в таблице dogprovider',
  `conid` int DEFAULT '0' COMMENT 'id поставщика (clientcat.clid)',
  `sklad` int DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Список заявок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_zayavka`
--

LOCK TABLES `app_modcatalog_zayavka` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_zayavka` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_zayavka` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modcatalog_zayavkapoz`
--

DROP TABLE IF EXISTS `app_modcatalog_zayavkapoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modcatalog_zayavkapoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idz` int NOT NULL COMMENT 'id заявки (odcatalog_zayavka.id)',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'кол-во на складе',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Каталог-склад. Позиции заявок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modcatalog_zayavkapoz`
--

LOCK TABLES `app_modcatalog_zayavkapoz` WRITE;
/*!40000 ALTER TABLE `app_modcatalog_zayavkapoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_modcatalog_zayavkapoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_modules`
--

DROP TABLE IF EXISTS `app_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL COMMENT 'название модуля',
  `content` text COMMENT 'какие сделаны настройки модуля',
  `mpath` varchar(255) DEFAULT NULL,
  `icon` varchar(20) NOT NULL DEFAULT 'icon-publish' COMMENT 'иконка из фонтелло для меню',
  `active` varchar(5) NOT NULL DEFAULT 'on' COMMENT 'включен-отключен',
  `activateDate` varchar(20) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Подключенные модули';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_modules`
--

LOCK TABLES `app_modules` WRITE;
/*!40000 ALTER TABLE `app_modules` DISABLE KEYS */;
INSERT INTO `app_modules` VALUES (1,'Каталог-склад','','modcatalog','icon-archive','off','2020-08-13 12:12:48','',1),(2,'Обращения','{\"enShowButtonLeft\":\"yes\",\"enShowButtonCall\":\"yes\"}','entry','icon-phone-squared','off','2021-10-28 22:44:45','',1);
/*!40000 ALTER TABLE `app_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_multisteps`
--

DROP TABLE IF EXISTS `app_multisteps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_multisteps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'Название цепочки',
  `direction` int DEFAULT NULL COMMENT 'id from _direction Направление',
  `tip` int DEFAULT NULL COMMENT 'tid from _dogtips Тип сделки',
  `steps` varchar(255) DEFAULT NULL COMMENT 'Набор этапов',
  `isdefault` varchar(5) DEFAULT NULL COMMENT 'id этапа по умолчанию',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Настройка мультиворонки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_multisteps`
--

LOCK TABLES `app_multisteps` WRITE;
/*!40000 ALTER TABLE `app_multisteps` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_multisteps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_mycomps`
--

DROP TABLE IF EXISTS `app_mycomps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_mycomps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_ur` text COMMENT 'полное наименование',
  `name_shot` text COMMENT 'сокращенное наименование',
  `address_yur` text COMMENT 'юридические адрес',
  `address_post` text COMMENT 'почтовый адрес',
  `dir_name` varchar(255) DEFAULT NULL COMMENT 'в лице руководителя',
  `dir_signature` varchar(255) DEFAULT NULL COMMENT 'подпись руководителя',
  `dir_status` text COMMENT 'должность руководителя',
  `dir_osnovanie` text COMMENT 'действующего на основаии',
  `innkpp` varchar(255) DEFAULT NULL COMMENT 'инн-кпп',
  `okog` varchar(255) DEFAULT NULL COMMENT 'окпо-огрн',
  `stamp` varchar(255) DEFAULT NULL COMMENT 'файл с факсимилией',
  `logo` varchar(255) DEFAULT NULL COMMENT 'файл с логотипом',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Список собственных компаний';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_mycomps`
--

LOCK TABLES `app_mycomps` WRITE;
/*!40000 ALTER TABLE `app_mycomps` DISABLE KEYS */;
INSERT INTO `app_mycomps` VALUES (1,'Общество с ограниченной ответственностью ”Брикет Солюшн”','ООО ”Брикет Солюшн”','614007, г. Пермь, ул. Народовольческая, 60','614007, г. Пермь, ул. Народовольческая, 60','Директора Андреева Владислава Германовича','Андреев В.Г.','Директор','Устава','590402247104;590401001',';312590427000020','stamp1675529125.png','logo.png',1);
/*!40000 ALTER TABLE `app_mycomps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_mycomps_recv`
--

DROP TABLE IF EXISTS `app_mycomps_recv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_mycomps_recv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cid` int DEFAULT '0',
  `title` text COMMENT 'назваине р.с',
  `rs` varchar(50) DEFAULT NULL COMMENT 'р.с',
  `bankr` text COMMENT 'бик, кур. счет и название банка',
  `tip` varchar(6) DEFAULT 'bank' COMMENT 'bank-kassa',
  `ostatok` double(20,2) DEFAULT NULL COMMENT 'остаток средств',
  `bloc` varchar(3) DEFAULT 'no' COMMENT 'заблокирован или нет счет',
  `isDefault` varchar(5) DEFAULT 'no' COMMENT 'использутся по умолчанию или нет',
  `ndsDefault` varchar(5) DEFAULT '0' COMMENT 'размер ндс по умолчанию',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Расчетные счета к компаниям';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_mycomps_recv`
--

LOCK TABLES `app_mycomps_recv` WRITE;
/*!40000 ALTER TABLE `app_mycomps_recv` DISABLE KEYS */;
INSERT INTO `app_mycomps_recv` VALUES (1,1,'Основной расчетный счет','1234567890000000000000000','045744863;30101810300000000863;Филиал ОАО «УРАЛСИБ» в г. Пермь','bank',0.00,'','yes','20',1),(2,1,'Касса','0',';;','kassa',0.00,'','','0',1);
/*!40000 ALTER TABLE `app_mycomps_recv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_mycomps_signer`
--

DROP TABLE IF EXISTS `app_mycomps_signer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_mycomps_signer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mcid` int DEFAULT NULL COMMENT 'Привязка к компании',
  `title` varchar(255) DEFAULT NULL COMMENT 'Имя подписанта',
  `status` varchar(255) DEFAULT NULL COMMENT 'Должность',
  `signature` varchar(255) DEFAULT NULL COMMENT 'Подпись',
  `osnovanie` varchar(255) DEFAULT NULL COMMENT 'Действующий на основании',
  `stamp` varchar(255) DEFAULT NULL COMMENT 'Файл факсимилье',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `mcid` (`mcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Дополнительные подписанты для документов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_mycomps_signer`
--

LOCK TABLES `app_mycomps_signer` WRITE;
/*!40000 ALTER TABLE `app_mycomps_signer` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_mycomps_signer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_notes`
--

DROP TABLE IF EXISTS `app_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания заметки',
  `author` int NOT NULL DEFAULT '0' COMMENT 'id пользователя, создавшего заметку',
  `pin` int NOT NULL COMMENT 'признак важности заметки',
  `text` varchar(180) NOT NULL COMMENT 'Текст заметки',
  `identity` int NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='База заметок пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_notes`
--

LOCK TABLES `app_notes` WRITE;
/*!40000 ALTER TABLE `app_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_notify`
--

DROP TABLE IF EXISTS `app_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_notify` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время уведомления',
  `title` varchar(255) DEFAULT NULL COMMENT 'заголовок уведомления',
  `content` text COMMENT 'содержимое уведомления',
  `url` text COMMENT 'ссылка на сущность',
  `tip` varchar(50) DEFAULT NULL COMMENT 'тип связанной записи',
  `uid` int DEFAULT NULL COMMENT 'id связанной записи',
  `status` varchar(2) DEFAULT '0' COMMENT 'Статус прочтения - 0 Не прочитано, 1 Прочитано',
  `autor` int DEFAULT NULL COMMENT 'автор события',
  `iduser` int DEFAULT NULL COMMENT 'цель - сотрудник',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='База уведомлений';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_notify`
--

LOCK TABLES `app_notify` WRITE;
/*!40000 ALTER TABLE `app_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_office_cat`
--

DROP TABLE IF EXISTS `app_office_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_office_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'адрес офиса',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Офисы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_office_cat`
--

LOCK TABLES `app_office_cat` WRITE;
/*!40000 ALTER TABLE `app_office_cat` DISABLE KEYS */;
INSERT INTO `app_office_cat` VALUES (1,' г. Пермь, ул. Ленина, 60 оф. 100',1);
/*!40000 ALTER TABLE `app_office_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_otdel_cat`
--

DROP TABLE IF EXISTS `app_otdel_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_otdel_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(30) NOT NULL COMMENT 'идентификатор для внешних систем',
  `title` varchar(250) DEFAULT NULL COMMENT 'название отдела',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Отделы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_otdel_cat`
--

LOCK TABLES `app_otdel_cat` WRITE;
/*!40000 ALTER TABLE `app_otdel_cat` DISABLE KEYS */;
INSERT INTO `app_otdel_cat` VALUES (1,'OAP','Отдел активных продаж',1),(2,'OPP','Отдел пассивных продаж',1);
/*!40000 ALTER TABLE `app_otdel_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_personcat`
--

DROP TABLE IF EXISTS `app_personcat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_personcat` (
  `pid` int NOT NULL AUTO_INCREMENT,
  `clid` int DEFAULT '0',
  `ptitle` varchar(250) DEFAULT NULL,
  `person` varchar(250) DEFAULT NULL,
  `tel` varchar(250) DEFAULT NULL,
  `fax` varchar(250) DEFAULT NULL,
  `mob` varchar(250) DEFAULT NULL,
  `mail` varchar(250) DEFAULT NULL,
  `rol` text,
  `social` text,
  `iduser` varchar(12) DEFAULT '0',
  `clientpath` int DEFAULT '0',
  `loyalty` int DEFAULT '0',
  `input1` varchar(255) DEFAULT NULL,
  `input2` varchar(255) DEFAULT NULL,
  `input3` varchar(255) DEFAULT NULL,
  `input4` varchar(255) DEFAULT NULL,
  `input5` varchar(255) DEFAULT NULL,
  `input6` varchar(255) DEFAULT NULL,
  `input7` varchar(255) DEFAULT NULL,
  `input8` varchar(255) DEFAULT NULL,
  `input9` varchar(255) DEFAULT NULL,
  `input10` varchar(512) DEFAULT NULL,
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_edit` timestamp NULL DEFAULT NULL,
  `creator` int DEFAULT '0',
  `editor` int DEFAULT '0',
  `uid` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`pid`),
  KEY `person` (`person`),
  KEY `tel` (`tel`),
  KEY `mob` (`mob`),
  KEY `fax` (`fax`),
  KEY `mail` (`mail`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Контакты';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_personcat`
--

LOCK TABLES `app_personcat` WRITE;
/*!40000 ALTER TABLE `app_personcat` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_personcat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_plan`
--

DROP TABLE IF EXISTS `app_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_plan` (
  `plid` int NOT NULL AUTO_INCREMENT,
  `year` int DEFAULT NULL COMMENT 'год',
  `mon` int DEFAULT NULL COMMENT 'месяц',
  `iduser` int DEFAULT NULL COMMENT 'план для кокого сотрудника user.iduser',
  `kol_plan` text COMMENT 'план',
  `marga` text COMMENT 'прибыль',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`plid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='План продаж';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_plan`
--

LOCK TABLES `app_plan` WRITE;
/*!40000 ALTER TABLE `app_plan` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_plugins`
--

DROP TABLE IF EXISTS `app_plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_plugins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подключения',
  `name` varchar(50) NOT NULL DEFAULT '0' COMMENT 'название ',
  `version` varchar(10) DEFAULT NULL COMMENT 'Установленная версия плагина',
  `active` varchar(5) NOT NULL DEFAULT 'off' COMMENT 'статус активности - on-off',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Подключенные плагины';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_plugins`
--

LOCK TABLES `app_plugins` WRITE;
/*!40000 ALTER TABLE `app_plugins` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_price`
--

DROP TABLE IF EXISTS `app_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_price` (
  `n_id` int NOT NULL AUTO_INCREMENT,
  `artikul` varchar(255) DEFAULT NULL COMMENT 'артикул',
  `title` varchar(255) DEFAULT NULL COMMENT 'название позиции',
  `descr` text COMMENT 'описание',
  `edizm` varchar(10) DEFAULT NULL,
  `price_in` double(20,2) NOT NULL DEFAULT '0.00',
  `price_1` double(20,2) NOT NULL DEFAULT '0.00',
  `price_2` double(20,2) DEFAULT '0.00',
  `price_3` double(20,2) DEFAULT '0.00',
  `price_4` double(20,2) DEFAULT '0.00',
  `price_5` double(20,2) DEFAULT NULL,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pr_cat` int NOT NULL COMMENT 'категория price_cat.idcategory',
  `nds` double(20,2) NOT NULL DEFAULT '0.00' COMMENT 'ндс',
  `archive` varchar(3) NOT NULL DEFAULT 'no',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`n_id`),
  KEY `pr_cat` (`pr_cat`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Прайс-лист';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_price`
--

LOCK TABLES `app_price` WRITE;
/*!40000 ALTER TABLE `app_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_price_cat`
--

DROP TABLE IF EXISTS `app_price_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_price_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `sub` int DEFAULT NULL COMMENT 'Головная категория - _price_cat.idcategory',
  `title` varchar(250) DEFAULT NULL COMMENT 'название прайса',
  `type` tinyint(1) DEFAULT NULL COMMENT 'тип: 0 - товар, 1 - услуга, 2 - материал',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`),
  KEY `sub` (`sub`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Прайс-лист. Категории';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_price_cat`
--

LOCK TABLES `app_price_cat` WRITE;
/*!40000 ALTER TABLE `app_price_cat` DISABLE KEYS */;
INSERT INTO `app_price_cat` VALUES (1,0,'Тест',NULL,1);
/*!40000 ALTER TABLE `app_price_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_profile`
--

DROP TABLE IF EXISTS `app_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_profile` (
  `pfid` int NOT NULL AUTO_INCREMENT,
  `id` int DEFAULT NULL COMMENT 'profile_cat.id',
  `clid` int DEFAULT NULL COMMENT 'clientcat.clid',
  `value` varchar(255) DEFAULT NULL COMMENT 'начение поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`pfid`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Профиль. Данные';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_profile`
--

LOCK TABLES `app_profile` WRITE;
/*!40000 ALTER TABLE `app_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_profile_cat`
--

DROP TABLE IF EXISTS `app_profile_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_profile_cat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT 'название поля',
  `tip` varchar(10) DEFAULT NULL COMMENT 'тип вывода поля',
  `value` text COMMENT 'значение поля',
  `ord` int DEFAULT NULL COMMENT 'порядок вывода',
  `pole` varchar(10) DEFAULT NULL COMMENT 'название поля для идентификации',
  `pwidth` int NOT NULL DEFAULT '50' COMMENT 'ширина поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Профиль. Настройки профилей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_profile_cat`
--

LOCK TABLES `app_profile_cat` WRITE;
/*!40000 ALTER TABLE `app_profile_cat` DISABLE KEYS */;
INSERT INTO `app_profile_cat` VALUES (1,'Количество сотрудников в отделе снабжения','select','1-3;3-5;больше 5',15,'pole1',50,1),(2,'Как часто проводят закупки','select','1 раз в мес.; 2 раза в мес.;больше 2-х раз в мес.',3,'pole2',50,1),(3,'Тендерный отдел','radio','Нет;Есть',13,'pole3',50,1),(4,'Проводят тендеры','radio','Электронные площадки;Самостоятельно;Оба варианта;Не проводят',14,'pole4',50,1),(5,'Примечание','text','',17,'pole5',50,1),(8,'Какие продукты можем предложить?','checkbox','Зап.части;Шины;Диски;Элементы кузова;Внедрение телефонии;Внедрение серверов;1С в облаке;Настройка VPN',4,'pole8',100,1),(9,'Объем закупок в месяц','radio','<100т.р.;100-200 т.р.;200-300 т.р.;300-500 т.р.;>500 т.р.',5,'pole9',50,1),(10,'Тип клиента для нас','radio','Не работаем;Ведем переговоры;С нами не будут работать;Работают только с нами',12,'pole10',100,1),(11,'Что покупают постоянно','checkbox','ГСМ;Автохимия;Зап.части;Диски',8,'pole11',50,1),(12,'Годовой оборот','radio','до 1млн.;свыше 1млн. до 20млн.;свыше 20млн. до 100млн.',11,'pole12',50,1),(19,'Специализация','input','',16,'pole19',100,1),(15,'Возможности по продаже','divider','',1,'pole15',100,1),(16,'Интересы клиента','divider','',7,'pole16',100,1);
/*!40000 ALTER TABLE `app_profile_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_projects_templates`
--

DROP TABLE IF EXISTS `app_projects_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_projects_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT 'untitled' COMMENT 'Название шаблона',
  `autor` int DEFAULT NULL COMMENT 'iduser автора',
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `content` text COMMENT 'Содержание работ в json',
  `state` int DEFAULT '1' COMMENT 'Статус: 1 - активен, 0 - не активен',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Шаблоны проектов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_projects_templates`
--

LOCK TABLES `app_projects_templates` WRITE;
/*!40000 ALTER TABLE `app_projects_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_projects_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_relations`
--

DROP TABLE IF EXISTS `app_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'название',
  `color` varchar(10) DEFAULT NULL COMMENT 'цвет',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'признак по умолчанию',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `title_2` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COMMENT='Типы отношений';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_relations`
--

LOCK TABLES `app_relations` WRITE;
/*!40000 ALTER TABLE `app_relations` DISABLE KEYS */;
INSERT INTO `app_relations` VALUES (1,'0 - Не работаем','#333333','',1),(2,'1 - Холодный клиент','#99ccff','yes',1),(3,'3 - Текущий клиент','#3366ff','',1),(5,'4 - Постоянный клиент','#ff9900','no',1),(4,'2 - Потенциальный клиент','#99ff66','',1),(6,'5 - Перспективный клиент','#ff0033','no',1);
/*!40000 ALTER TABLE `app_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_reports`
--

DROP TABLE IF EXISTS `app_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_reports` (
  `rid` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL COMMENT 'название отчета',
  `file` varchar(100) DEFAULT NULL COMMENT 'файл отчета',
  `ron` varchar(5) DEFAULT NULL COMMENT 'активность отчета',
  `category` varchar(20) DEFAULT NULL COMMENT 'раздел',
  `roles` text COMMENT 'Роли сотрудников с доступом к отчету',
  `users` varchar(255) DEFAULT NULL COMMENT 'id сотрудников, у которых есть доступ к отчету',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`rid`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb3 COMMENT='Подключенные файлы отчетов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_reports`
--

LOCK TABLES `app_reports` WRITE;
/*!40000 ALTER TABLE `app_reports` DISABLE KEYS */;
INSERT INTO `app_reports` VALUES (1,'Активности по сделкам','work.php','yes','Активности',NULL,NULL,1),(2,'Сделки по сотрудникам','effect_total.php','yes','Эффективность',NULL,NULL,1),(5,'Анализ конкурентов','effect_concurent.php','yes','Связи',NULL,NULL,1),(73,'Ent. Эффективность каналов','entClientpathToMoney.php','yes','Эффективность',NULL,NULL,1),(7,'Топ клиентов','top_clients.php','yes','Рейтинг',NULL,NULL,1),(8,'Топ сотрудников','top_managers.php','yes','Рейтинг',NULL,NULL,1),(9,'Активность по клиентам','week.php','yes','Активности',NULL,NULL,1),(10,'Действия по сделкам','newdogs.php','yes','Активности',NULL,NULL,1),(11,'По отделам','effect_otdel.php','yes','Эффективность',NULL,NULL,1),(12,'Сделки по типам','effect_dogovor.php','yes','Эффективность',NULL,NULL,1),(13,'По реализ. сделкам','effect_closed.php','yes','Эффективность',NULL,NULL,1),(14,'Анализ поставщиков','effect_contractor.php','yes','Связи',NULL,NULL,1),(15,'Анализ партнеров','effect_partner.php','yes','Связи',NULL,NULL,1),(16,'Активности. Сводная','pipeline_activities.php','no','Активности',NULL,NULL,1),(19,'Pipeline Продажи Сотрудников','pipelineUsersNew.php','yes','Продажи',NULL,NULL,1),(21,'Эффективность сотрудников','effect.php','yes','Эффективность',NULL,NULL,1),(20,'Pipeline Ожидаемый приход','pipeline_prognoz.php','yes','Продажи',NULL,NULL,1),(22,'Pipeline Продажи по этапам','pipeline_dogs.php','yes','Продажи',NULL,NULL,1),(29,'Здоровье сделок','dogs_health.php','yes','Продажи',NULL,NULL,1),(30,'Здоровье сделок [большой]','dogs_health_big.php','yes','Продажи',NULL,NULL,1),(31,'Выполнение дел','activities_results.php','yes','Активности',NULL,NULL,1),(38,'Воронка по марже','voronka_marg.php','yes','Продажи',NULL,NULL,1),(39,'Здоровье сделок (дни)','dogs_health_big_day.php','yes','Продажи',NULL,NULL,1),(40,'Сделки по направлениям','effect_direction.php','yes','Эффективность',NULL,NULL,1),(74,'Ent. Анализ клиентов по типам отношений','entRelationsToMoney.php','yes','Эффективность',NULL,NULL,1),(44,'Новые клиенты','effect_newclients.php','yes','Активности',NULL,NULL,1),(45,'Сделки. Анализ','dogs_monitor.php','yes','Продажи',NULL,NULL,1),(46,'Сделки. В работе','dogs_inwork.php','yes','Продажи',NULL,NULL,1),(47,'Сделки. Зависшие','dogs_inhold.php','yes','Продажи',NULL,NULL,1),(48,'Сделки. Утвержденные','dogs_approved.php','yes','Продажи',NULL,NULL,1),(49,'Сделки. Отказные','dogs_disapproved.php','yes','Продажи',NULL,NULL,1),(50,'Сделки. Здоровье (все сделки)','dogs_health_all.php','yes','Продажи',NULL,NULL,1),(51,'Контроль сделок (по КТ)','dogs_complect.php','yes','Продажи',NULL,NULL,1),(61,'Ent. ABC анализ клиентов','ent-ABC-clients.php','yes','Продажи',NULL,NULL,1),(62,'Ent. ABC анализ продуктов','ent-ABC-products.php','yes','Продажи',NULL,NULL,1),(63,'Ent. RFM анализ клиентов','ent-RFM-clients.php','yes','Продажи',NULL,NULL,1),(56,'Прогноз по продуктам','dogs_productprognoz.php','yes','Планирование',NULL,NULL,1),(57,'Прогноз по продуктам (большой)','dogs_productprognoz_hor.php','yes','Продажи',NULL,NULL,1),(58,'Выполнение планов','planfact2015.php','yes','Планирование',NULL,NULL,1),(59,'Воронка по активностям','voronka_classic.php','yes','Активности',NULL,NULL,1),(72,'Ent. Сделки в работе. По дням по этапам','ent-dealsPerDayPerStep.php','yes','Продажи',NULL,NULL,1),(64,'Ent. RFM анализ продуктов','ent-RFM-products.php','yes','Продажи',NULL,NULL,1),(65,'Анализ Сборщика заявок (Лидов)','leads2014.php','yes','Активности',NULL,NULL,1),(66,'Анализ звонков (телефония)','call_history.php','yes','Активности',NULL,NULL,1),(67,'Закрытые успешные сделки','dealResultReport.php','yes','Продажи',NULL,NULL,1),(68,'Закрытые сделки по этапам','ent-ClosedDealAnalyseByStep.php','yes','Продажи',NULL,NULL,1),(69,'Выставленные счета по сотрудникам','ent-InvoiceStateByUser.php','yes','Продажи',NULL,NULL,1),(70,'Ent. Супер Воронка продаж','ent-SalesFunnel.php','yes','Продажи',NULL,NULL,1),(71,'Ent. Комплексная воронка','ent-voronkaComplex.php','yes','Продажи',NULL,NULL,1),(75,'Ent. Новые клиенты','ent-newClients.php','yes','Активности',NULL,NULL,1),(76,'Ent. Новые сделки','ent-newDeals.php','yes','Активности',NULL,NULL,1),(77,'Ent. Оплаты по сотрудникам','ent-PaymentsByUser.php','yes','Продажи',NULL,NULL,1),(80,'Ent. Активности по времени','ent-activitiesByTime.php','yes','Активности',NULL,NULL,1),(81,'Активности пользователей по сделкам','ent-ActivitiesByUserByDeals.php','yes','Активности',NULL,NULL,1),(82,'Анализ направлений','entDirectionAnaliseChart.php','yes','Эффективность',NULL,NULL,1),(83,'Эффективность каналов продаж','effect_clientpath.php','yes','Эффективность',NULL,NULL,1),(84,'Выполнение планов по оплатам','ent-planDoByPayment.php','yes','Планирование',NULL,NULL,1),(85,'Рейтинг выполнения плана','raiting_plan.php','yes','Планирование',NULL,NULL,1),(86,'Ent. Антиворонка','ent-antiSalesFunnel.php','yes','Продажи',NULL,NULL,1),(87,'Ent. Сделки в работе. По дням','ent-dealsPerDay.php','yes','Продажи',NULL,NULL,1);
/*!40000 ALTER TABLE `app_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_search`
--

DROP TABLE IF EXISTS `app_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_search` (
  `seid` int NOT NULL AUTO_INCREMENT,
  `tip` varchar(100) DEFAULT NULL COMMENT 'Привязка к person, client, dog',
  `title` varchar(250) DEFAULT NULL COMMENT 'Название представления',
  `squery` text COMMENT 'Поисковой запрос',
  `sorder` int DEFAULT NULL COMMENT 'Порядок вывода',
  `iduser` int DEFAULT NULL COMMENT 'user.iduser',
  `share` varchar(5) DEFAULT NULL COMMENT 'Общий доступ',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`seid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Поисковые представления';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_search`
--

LOCK TABLES `app_search` WRITE;
/*!40000 ALTER TABLE `app_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_services`
--

DROP TABLE IF EXISTS `app_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'название',
  `folder` varchar(60) NOT NULL COMMENT 'название для системы',
  `tip` varchar(200) NOT NULL COMMENT 'тип sip-mail',
  `user_id` varchar(255) NOT NULL COMMENT 'пользователь',
  `user_key` varchar(255) NOT NULL COMMENT 'ключ пользователя',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Данные различных внешних систем для интеграции';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_services`
--

LOCK TABLES `app_services` WRITE;
/*!40000 ALTER TABLE `app_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company` varchar(250) DEFAULT NULL COMMENT 'Название компании. Краткое',
  `company_full` mediumtext COMMENT 'Название компании. Полное',
  `company_site` varchar(250) DEFAULT NULL COMMENT 'Сайт компании',
  `company_mail` varchar(250) DEFAULT NULL COMMENT 'Email компании',
  `company_phone` varchar(255) DEFAULT NULL COMMENT 'Телефон компании',
  `company_fax` varchar(255) DEFAULT NULL COMMENT 'факс',
  `outClientUrl` varchar(255) DEFAULT NULL,
  `outDealUrl` varchar(255) DEFAULT NULL,
  `defaultDealName` varchar(255) DEFAULT NULL,
  `dir_prava` varchar(255) DEFAULT NULL,
  `recv` mediumtext,
  `gkey` varchar(250) DEFAULT NULL,
  `num_client` int DEFAULT '30',
  `num_con` int DEFAULT '30',
  `num_person` int DEFAULT '30',
  `num_dogs` int DEFAULT '30',
  `format_phone` varchar(250) DEFAULT NULL COMMENT 'Формат телефона',
  `format_fax` varchar(250) DEFAULT NULL,
  `format_tel` varchar(250) DEFAULT NULL,
  `format_mob` varchar(250) DEFAULT NULL,
  `format_dogs` varchar(250) DEFAULT NULL,
  `session` varchar(3) NOT NULL,
  `export_lock` varchar(255) DEFAULT NULL,
  `valuta` varchar(10) DEFAULT NULL,
  `ipaccesse` varchar(5) DEFAULT NULL,
  `ipstart` varchar(15) DEFAULT NULL,
  `ipend` varchar(15) DEFAULT NULL,
  `iplist` mediumtext,
  `maxupload` varchar(3) DEFAULT NULL COMMENT 'Максимальный размер файла для загрузки',
  `ipmask` varchar(20) DEFAULT NULL,
  `ext_allow` mediumtext NOT NULL COMMENT 'Разрешенные типы файлов',
  `mailme` varchar(5) DEFAULT NULL,
  `mailout` varchar(10) DEFAULT NULL,
  `other` mediumtext COMMENT 'Прочие настройке в формате json',
  `logo` varchar(100) DEFAULT NULL COMMENT 'Логотип компании',
  `acs_view` varchar(3) DEFAULT 'on',
  `complect_on` varchar(3) DEFAULT 'no',
  `zayavka_on` varchar(3) DEFAULT 'no',
  `contract_format` varchar(255) DEFAULT NULL,
  `contract_num` int DEFAULT NULL,
  `inum` int DEFAULT NULL,
  `iformat` varchar(255) DEFAULT NULL,
  `akt_num` varchar(20) DEFAULT '0',
  `akt_step` int DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `coordinator` int DEFAULT NULL,
  `timezone` varchar(255) DEFAULT 'Asia/Yekaterinburg' COMMENT 'Временная зона',
  `ivc` varchar(255) DEFAULT NULL,
  `dFormat` varchar(255) DEFAULT NULL,
  `dNum` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Основные настройки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (1,'Наша','','http://nasha.ru','info@nasha.ru','+7(342)2067201','','','','{ClientName}','','','',30,30,30,30,'9(999)999-99-99',NULL,'8(342)254-55-77',NULL,'99,999 999 999 999','14','','р.',NULL,'','','','20','','gif,jpg,jpeg,png,txt,doc,docx,xls,xlsx,ppt,pptx,rtf,pdf,7z,tar,zip,rar,gz,exe','yes',NULL,'no;no;no;yes;yes;yes;25;25;no;yes;no;yes;yes;yes;no;Дней;no;no;no;no;no;no;yes;yes;invoicedo;2;14;yes;yes;no;no;no;no;no;yes;yes;no;no;no;akt_full.tpl;invoice_qr.tpl;akt_full.tpl;invoice.tpl;no;no;no;no;no;no;no;no','logo.png',NULL,NULL,NULL,'{cnum}-{MM}{YY}/{YYYY}',0,2,'{cnum}','1',7,'VaSeZvkTfh5HMjJpNnge1W7Bloim0S',NULL,'Europe/Moscow','ifyb8VTNF4hf8kE7QclT9w==','СД{cnum}','1');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_sip`
--

DROP TABLE IF EXISTS `app_sip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_sip` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` varchar(3) NOT NULL DEFAULT 'no',
  `tip` varchar(20) DEFAULT NULL,
  `sip_host` varchar(255) DEFAULT NULL,
  `sip_port` int DEFAULT NULL,
  `sip_channel` varchar(30) DEFAULT NULL,
  `sip_context` varchar(255) DEFAULT NULL,
  `sip_user` varchar(100) DEFAULT NULL,
  `sip_secret` varchar(200) DEFAULT NULL,
  `sip_numout` varchar(3) DEFAULT NULL,
  `sip_pfchange` varchar(3) DEFAULT NULL,
  `sip_path` varchar(255) DEFAULT NULL,
  `sip_cdr` varchar(255) DEFAULT NULL,
  `sip_secure` varchar(5) DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Настройки подключения к астериску';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_sip`
--

LOCK TABLES `app_sip` WRITE;
/*!40000 ALTER TABLE `app_sip` DISABLE KEYS */;
INSERT INTO `app_sip` VALUES (1,'no','','',8089,'SIP','from-internal','','','','','','','',1);
/*!40000 ALTER TABLE `app_sip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_smtp`
--

DROP TABLE IF EXISTS `app_smtp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_smtp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` varchar(3) NOT NULL DEFAULT 'no',
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int DEFAULT NULL,
  `smtp_auth` varchar(5) DEFAULT NULL,
  `smtp_secure` varchar(5) DEFAULT NULL,
  `smtp_user` varchar(100) DEFAULT NULL,
  `smtp_pass` varchar(200) DEFAULT NULL,
  `smtp_from` varchar(255) DEFAULT NULL,
  `smtp_protocol` varchar(5) DEFAULT NULL,
  `tip` varchar(10) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser',
  `divider` varchar(3) NOT NULL DEFAULT ':',
  `filter` varchar(255) NOT NULL DEFAULT 'заявка',
  `deletemess` varchar(5) NOT NULL DEFAULT 'false',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Настройки подключения к почтовым службам';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_smtp`
--

LOCK TABLES `app_smtp` WRITE;
/*!40000 ALTER TABLE `app_smtp` DISABLE KEYS */;
INSERT INTO `app_smtp` VALUES (1,'no','smtp.yandex.ru',587,'true','tls','','','','','send','',0,':','','false',1);
/*!40000 ALTER TABLE `app_smtp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_speca`
--

DROP TABLE IF EXISTS `app_speca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_speca` (
  `spid` int NOT NULL AUTO_INCREMENT,
  `prid` int DEFAULT '0',
  `did` int DEFAULT '0',
  `artikul` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `tip` int DEFAULT '0',
  `price` double(20,2) DEFAULT '0.00',
  `price_in` double(20,2) DEFAULT '0.00',
  `kol` double(20,2) DEFAULT '0.00',
  `edizm` varchar(10) DEFAULT NULL,
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nds` float(20,2) DEFAULT NULL,
  `dop` int DEFAULT '1',
  `comments` varchar(250) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`spid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Позиции спецификаций к сделкам';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_speca`
--

LOCK TABLES `app_speca` WRITE;
/*!40000 ALTER TABLE `app_speca` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_speca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_steplog`
--

DROP TABLE IF EXISTS `app_steplog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_steplog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step` int DEFAULT NULL COMMENT 'id этапа dogcategory.idcategory',
  `did` int DEFAULT NULL COMMENT 'id сделки dogovor.did',
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser внес изменение',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `step` (`step`),
  KEY `did` (`did`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Лог изменений этапов сделок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_steplog`
--

LOCK TABLES `app_steplog` WRITE;
/*!40000 ALTER TABLE `app_steplog` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_steplog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_tasks`
--

DROP TABLE IF EXISTS `app_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_tasks` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `maintid` int DEFAULT '0',
  `iduser` int DEFAULT '0',
  `clid` int DEFAULT '0',
  `pid` varchar(255) DEFAULT NULL COMMENT 'personcat.pid (может быть несколько с разделением ;)',
  `did` int DEFAULT '0',
  `cid` int DEFAULT '0',
  `datum` date DEFAULT NULL,
  `totime` time DEFAULT '09:00:00',
  `title` varchar(250) DEFAULT NULL,
  `des` text,
  `tip` varchar(100) DEFAULT 'Звонок',
  `active` varchar(255) DEFAULT 'yes',
  `autor` int DEFAULT '0',
  `priority` int DEFAULT '0',
  `speed` int DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `readonly` varchar(3) DEFAULT 'no',
  `alert` varchar(3) DEFAULT 'yes',
  `day` varchar(3) DEFAULT NULL,
  `status` int DEFAULT '0',
  `alertTime` int DEFAULT '0',
  `identity` varchar(30) DEFAULT '1',
  PRIMARY KEY (`tid`),
  KEY `iduser` (`iduser`),
  KEY `tip` (`tip`),
  KEY `clid` (`clid`),
  KEY `did` (`did`),
  KEY `identity` (`identity`),
  KEY `autor` (`autor`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Напоминания';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_tasks`
--

LOCK TABLES `app_tasks` WRITE;
/*!40000 ALTER TABLE `app_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_territory_cat`
--

DROP TABLE IF EXISTS `app_territory_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_territory_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'наименование',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COMMENT='Территории';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_territory_cat`
--

LOCK TABLES `app_territory_cat` WRITE;
/*!40000 ALTER TABLE `app_territory_cat` DISABLE KEYS */;
INSERT INTO `app_territory_cat` VALUES (1,'Пермь',1),(3,'Тюмень',1),(4,'Челябинск',1),(5,'Москва',1);
/*!40000 ALTER TABLE `app_territory_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_tpl`
--

DROP TABLE IF EXISTS `app_tpl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_tpl` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `tip` varchar(20) DEFAULT NULL COMMENT 'тип',
  `name` varchar(255) DEFAULT NULL COMMENT 'название',
  `content` mediumtext COMMENT 'сообщение',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COMMENT='Шаблоны для email-уведомлений';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_tpl`
--

LOCK TABLES `app_tpl` WRITE;
/*!40000 ALTER TABLE `app_tpl` DISABLE KEYS */;
INSERT INTO `app_tpl` VALUES (1,'new_client','Новая организация','Создана новая Организация - <strong>{link}</strong>',1),(2,'new_person','Новая персона','Создана новая персона - {link}',1),(3,'new_dog','Новая сделка','Я создал сделку&nbsp;{link}',1),(4,'edit_dog','Изменение в сделке','Я изменил статус сделки&nbsp;{link}',1),(5,'close_dog','Закрытие сделки','Я закрыл сделку -&nbsp;{link}',1),(6,'send_client','Вам назначена организация','Вы назначены ответственным за Организацию - {link}',1),(7,'send_person','Вам назначена персона','Вы назначены ответственным за Персону - {link}',1),(8,'trash_client','Изменение Ответственного','Ваша Организация перемещена в корзину - {link}',1),(9,'lead_add','Новый интерес','Новый входящий интерес - {link}',1),(10,'lead_setuser','Назначенный интерес','Вы назначены Ответственным за обработку входящего интереса - {link}',1),(11,'lead_do','Обработанный интерес','Я обработал интерес - {link}',1),(12,'leadClientNotifyTemp','Уведомление','&lt;div style=&quot;width:98%; max-width:600px; margin: 0 auto&quot;&gt;\r\n&lt;div class=&quot;blok&quot; style=&quot;font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;\r\n&lt;div style=&quot;color:black; font-size:14px; margin-top: 5px;&quot;&gt;&lt;strong&gt;Уважаемый {castomerName}!&lt;/strong&gt;&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nБлагодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником &lt;strong&gt;{UserName}&lt;/strong&gt;.&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nКонтакты сотрудника:&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;ul&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Телефон:&lt;strong&gt; {UserPhone}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Мобильный:&lt;strong&gt; {UserMob}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Почта: &lt;strong&gt;{UserEmail}&lt;/strong&gt;&lt;/li&gt;\r\n&lt;/ul&gt;\r\n&lt;br /&gt;\r\nВ ближайшее время мы с вами свяжемся по указанному телефону или email.&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;hr /&gt;&lt;br /&gt;\r\nС уважением, {compName}&lt;/div&gt;\r\n&lt;/div&gt;\r\n\r\n&lt;div align=&quot;right&quot; style=&quot;font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;Обработано в SalesMan CRM&lt;/div&gt;\r\n&lt;/div&gt;\r\n',1),(13,'leadSendWellcomeTemp','Уведомление','&lt;div style=&quot;width:98%; max-width:600px; margin: 0 auto&quot;&gt;\r\n&lt;div class=&quot;blok&quot; style=&quot;font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;\r\n&lt;div style=&quot;color:black; font-size:14px; margin-top: 5px;&quot;&gt;&lt;strong&gt;Уважаемый {castomerName}!&lt;/strong&gt;&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nБлагодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником &lt;strong&gt;{UserName}&lt;/strong&gt;.&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nКонтакты сотрудника:&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;ul&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Телефон:&lt;strong&gt; {UserPhone}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Мобильный:&lt;strong&gt; {UserMob}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Почта: &lt;strong&gt;{UserEmail}&lt;/strong&gt;&lt;/li&gt;\r\n&lt;/ul&gt;\r\n&lt;br /&gt;\r\nВ ближайшее время мы с вами свяжемся по указанному телефону или email.&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;hr /&gt;&lt;br /&gt;\r\nС уважением, {compName}&lt;/div&gt;\r\n&lt;/div&gt;\r\n\r\n&lt;div align=&quot;right&quot; style=&quot;font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;Обработано в SalesMan CRM&lt;/div&gt;\r\n&lt;/div&gt;\r\n',1);
/*!40000 ALTER TABLE `app_tpl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_uids`
--

DROP TABLE IF EXISTS `app_uids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_uids` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) DEFAULT NULL COMMENT 'название параметра',
  `value` varchar(100) DEFAULT NULL COMMENT 'знаение параметра',
  `lid` int DEFAULT '0' COMMENT 'id заявки',
  `eid` int DEFAULT '0' COMMENT 'id обращения',
  `clid` int DEFAULT '0' COMMENT 'id записи клиента',
  `did` int DEFAULT '0' COMMENT 'id записи сделки',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='База связки id сторонних систем с записями CRM';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_uids`
--

LOCK TABLES `app_uids` WRITE;
/*!40000 ALTER TABLE `app_uids` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_uids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_user`
--

DROP TABLE IF EXISTS `app_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_user` (
  `iduser` int NOT NULL AUTO_INCREMENT,
  `login` varchar(250) NOT NULL COMMENT 'Логин',
  `pwd` varchar(250) NOT NULL COMMENT 'хеш пароля',
  `ses` text COMMENT 'Сессия',
  `title` varchar(250) DEFAULT NULL COMMENT 'ФИО',
  `tip` varchar(250) DEFAULT 'Менеджер продаж',
  `user_post` varchar(255) DEFAULT NULL,
  `mid` int DEFAULT '0',
  `bid` int DEFAULT '0',
  `otdel` text,
  `email` text COMMENT 'Email',
  `gcalendar` text,
  `territory` int NOT NULL DEFAULT '0',
  `office` int NOT NULL DEFAULT '0',
  `phone` text COMMENT 'Телефон',
  `phone_in` varchar(20) DEFAULT NULL COMMENT 'Добавочный номер',
  `fax` text,
  `mob` text COMMENT 'Мобильный',
  `bday` date DEFAULT NULL,
  `acs_analitics` varchar(5) DEFAULT NULL COMMENT 'Доступ к отчетам',
  `acs_maillist` varchar(5) DEFAULT NULL COMMENT 'Доступ к рассылкам',
  `acs_files` varchar(5) DEFAULT NULL COMMENT 'Доступ к разделу Файлы',
  `acs_price` varchar(5) DEFAULT NULL COMMENT 'Доступ к разделу Прайс',
  `acs_credit` varchar(5) DEFAULT NULL COMMENT 'Может ставить оплаты',
  `acs_prava` varchar(5) DEFAULT NULL COMMENT 'Может просматривать чужие записи',
  `tzone` varchar(5) DEFAULT NULL COMMENT 'Временная зона',
  `viget_on` varchar(500) DEFAULT 'on;on;on;on;on;on;on;on;on;on;on',
  `viget_order` varchar(500) DEFAULT 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11',
  `secrty` varchar(5) NOT NULL DEFAULT 'yes' COMMENT 'доступ в систему',
  `isadmin` varchar(3) NOT NULL DEFAULT 'off' COMMENT 'признак администратора',
  `acs_import` varchar(255) DEFAULT NULL COMMENT 'разные права',
  `show_marga` varchar(3) NOT NULL DEFAULT 'yes' COMMENT 'видит маржу',
  `acs_plan` varchar(60) NOT NULL DEFAULT 'on' COMMENT 'имеет план продаж',
  `zam` int DEFAULT '0',
  `CompStart` date DEFAULT NULL,
  `CompEnd` date DEFAULT NULL,
  `subscription` text COMMENT 'подписки на email-уведомления',
  `avatar` varchar(100) DEFAULT NULL COMMENT 'аватар',
  `sole` varchar(250) DEFAULT NULL,
  `adate` date DEFAULT NULL,
  `usersettings` text,
  `uid` varchar(30) DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`iduser`),
  KEY `title` (`title`),
  KEY `mid` (`mid`),
  KEY `secrty` (`secrty`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Сотрудники';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_user`
--

LOCK TABLES `app_user` WRITE;
/*!40000 ALTER TABLE `app_user` DISABLE KEYS */;
INSERT INTO `app_user` VALUES (1,'admin','8b519a20059be3c01d7ff24aaab744bbb9dccc92501aa055ba51828b3c346799172b4740b3fd66b58dca4e022e500294970c0c528bd17c68f0614da3abd75ed7ae17fccf4e1972aa1458482887018ba7cca45b2e42','s2ys05spwv012lLcUmI2D2f0MfmgegnZgvRc0k6rswXs60KyGAKz7lS2Vjs2','Administrator','Руководитель организации','Руководитель',0,0,NULL,'admin',NULL,0,0,NULL,NULL,NULL,NULL,NULL,'on','on','on','on','on','on','0','on;on;on;on;on;on;on;on;on;on;on;on','d1;d2;d5;d7;d4;d6;d3;d8;d9;d10;d11;d12','yes','on','on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on','yes','on',0,NULL,NULL,'on;off;off;on;off;on;on;on;on;on;on;on;off;off;off;off;off;off',NULL,'pwv012lLcUmI2D2f0MfmgiuNQGXgQLR3',NULL,'{\\\"vigets\\\":{\\\"parameters\\\":\\\"on\\\",\\\"voronka\\\":\\\"on\\\",\\\"analitic\\\":\\\"on\\\",\\\"dogs_renew\\\":\\\"on\\\",\\\"credit\\\":\\\"on\\\",\\\"stat\\\":\\\"on\\\"},\\\"taskAlarm\\\":null,\\\"userTheme\\\":\\\"\\\",\\\"userThemeRound\\\":null,\\\"startTab\\\":\\\"vigets\\\",\\\"menuClient\\\":\\\"my\\\",\\\"menuPerson\\\":\\\"my\\\",\\\"menuDeal\\\":\\\"my\\\",\\\"notify\\\":[\\\"client.add\\\",\\\"client.edit\\\",\\\"client.userchange\\\",\\\"client.delete\\\",\\\"client.double\\\",\\\"person.send\\\",\\\"deal.add\\\",\\\"deal.edit\\\",\\\"deal.userchange\\\",\\\"deal.step\\\",\\\"deal.close\\\",\\\"invoice.doit\\\",\\\"lead.add\\\",\\\"lead.setuser\\\",\\\"lead.do\\\",\\\"comment.new\\\",\\\"comment.close\\\",\\\"task.add\\\",\\\"task.edit\\\",\\\"task.doit\\\",\\\"self\\\"],\\\"filterAllBy\\\":null,\\\"subscribs\\\":null}',NULL,1);
/*!40000 ALTER TABLE `app_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ver`
--

DROP TABLE IF EXISTS `app_ver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ver` (
  `id` int NOT NULL AUTO_INCREMENT,
  `current` varchar(10) NOT NULL,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ver`
--

LOCK TABLES `app_ver` WRITE;
/*!40000 ALTER TABLE `app_ver` DISABLE KEYS */;
INSERT INTO `app_ver` VALUES (1,'2024.1','2024-03-14 11:43:42'),(2,'2024.2','2024-05-16 06:04:42'),(3,'2024.3','2024-07-03 07:32:24'),(4,'2025.2','2025-06-16 12:50:53');
/*!40000 ALTER TABLE `app_ver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_webhook`
--

DROP TABLE IF EXISTS `app_webhook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_webhook` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT 'event' COMMENT 'название ',
  `event` varchar(255) DEFAULT NULL COMMENT 'событие',
  `url` tinytext,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Webhook';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_webhook`
--

LOCK TABLES `app_webhook` WRITE;
/*!40000 ALTER TABLE `app_webhook` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_webhook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_webhooklog`
--

DROP TABLE IF EXISTS `app_webhooklog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_webhooklog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event` varchar(50) NOT NULL,
  `query` text NOT NULL,
  `response` text NOT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Webhook. Лог работы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_webhooklog`
--

LOCK TABLES `app_webhooklog` WRITE;
/*!40000 ALTER TABLE `app_webhooklog` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_webhooklog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ymail_blacklist`
--

DROP TABLE IF EXISTS `app_ymail_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ymail_blacklist` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id записи',
  `email` varchar(50) DEFAULT NULL COMMENT 'e-mail ',
  `identity` int NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Почтовик. Черный список email';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ymail_blacklist`
--

LOCK TABLES `app_ymail_blacklist` WRITE;
/*!40000 ALTER TABLE `app_ymail_blacklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ymail_blacklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ymail_files`
--

DROP TABLE IF EXISTS `app_ymail_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ymail_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int DEFAULT NULL COMMENT 'mail.id',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата',
  `name` varchar(255) DEFAULT NULL COMMENT 'оригинальное имя файла',
  `file` varchar(255) DEFAULT NULL COMMENT 'переименнованое имя файла для системы',
  `identity` int unsigned DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Почтовик. Файлы полученные или отправленные почтой';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ymail_files`
--

LOCK TABLES `app_ymail_files` WRITE;
/*!40000 ALTER TABLE `app_ymail_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ymail_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ymail_messages`
--

DROP TABLE IF EXISTS `app_ymail_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ymail_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата сообщения',
  `folder` varchar(30) NOT NULL DEFAULT 'draft' COMMENT 'тип сообщения inbox => Входящее, outbox => Исходящее, draft => Черновик, trash => Корзина, sended => Отправлено',
  `trash` varchar(30) NOT NULL DEFAULT 'no' COMMENT 'в корзине или нет сообщение (yes - в корзине)',
  `priority` int NOT NULL DEFAULT '3',
  `state` varchar(50) DEFAULT 'unread' COMMENT 'deleted - удаленные, read - прочитанные, unread - не прочинанны',
  `subbolder` varchar(255) DEFAULT NULL,
  `messageid` varchar(255) DEFAULT NULL,
  `uid` int DEFAULT NULL,
  `hid` int DEFAULT NULL COMMENT 'привязска с историей активности (history.cid)',
  `parentmid` varchar(255) DEFAULT NULL,
  `fromm` mediumtext,
  `fromname` mediumtext,
  `theme` varchar(255) DEFAULT NULL,
  `content` longtext,
  `iduser` int DEFAULT NULL,
  `fid` text,
  `did` int DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `theme` (`theme`),
  KEY `iduser` (`iduser`),
  KEY `did` (`did`),
  KEY `messageid` (`messageid`),
  KEY `complex` (`folder`,`state`,`iduser`,`identity`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Почтовик. Список сообщений';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ymail_messages`
--

LOCK TABLES `app_ymail_messages` WRITE;
/*!40000 ALTER TABLE `app_ymail_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ymail_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ymail_messagesrec`
--

DROP TABLE IF EXISTS `app_ymail_messagesrec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ymail_messagesrec` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int DEFAULT NULL COMMENT 'ymail_messages.id',
  `tip` varchar(100) DEFAULT 'to' COMMENT 'полученно-отправленное письмо',
  `email` varchar(100) DEFAULT NULL COMMENT 'email',
  `name` varchar(200) DEFAULT NULL COMMENT 'имя отправителя-получателя',
  `clid` int DEFAULT NULL COMMENT 'clid в таблице clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'pid в таблице personcat.pid',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `email` (`email`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Почтовик. Для свзяи с карточками клиента и контакта';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ymail_messagesrec`
--

LOCK TABLES `app_ymail_messagesrec` WRITE;
/*!40000 ALTER TABLE `app_ymail_messagesrec` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ymail_messagesrec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ymail_settings`
--

DROP TABLE IF EXISTS `app_ymail_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ymail_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `iduser` int NOT NULL DEFAULT '0' COMMENT 'id пользователя user.iduser',
  `settings` text COMMENT 'настройки',
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата и время последнего события',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Почтовик. Настройки почтовика';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ymail_settings`
--

LOCK TABLES `app_ymail_settings` WRITE;
/*!40000 ALTER TABLE `app_ymail_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ymail_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_ymail_tpl`
--

DROP TABLE IF EXISTS `app_ymail_tpl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_ymail_tpl` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT 'Шаблон',
  `content` text,
  `share` varchar(5) DEFAULT 'no',
  `iduser` int DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `identity` (`identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Модуль Почтовик. Шаблоны писем';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ymail_tpl`
--

LOCK TABLES `app_ymail_tpl` WRITE;
/*!40000 ALTER TABLE `app_ymail_tpl` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ymail_tpl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_activities`
--

DROP TABLE IF EXISTS `salesman_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL COMMENT 'название',
  `color` varchar(7) DEFAULT NULL COMMENT 'цвет в RGB',
  `icon` varchar(100) DEFAULT NULL COMMENT 'иконка',
  `resultat` text COMMENT 'список готовых реультатов, разделенный ;',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'признак дефолтности',
  `aorder` int DEFAULT NULL COMMENT 'порядок вывода',
  `filter` varchar(255) DEFAULT 'all' COMMENT 'признак применимости (all - универсальный, task - только для задач, history - только для активностей',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `identity` (`identity`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_activities`
--

LOCK TABLES `salesman_activities` WRITE;
/*!40000 ALTER TABLE `salesman_activities` DISABLE KEYS */;
INSERT INTO `salesman_activities` VALUES (1,'Первичный звонок','#009900',NULL,'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП','',8,'all',1),(2,'Факс','#cc00cc',NULL,'Отправлен и получен;Отправлен;Не отвечает;Не принимают','',16,'activ',1),(3,'Встреча','#ffcc00',NULL,'Состоялась;Перенос сроков;Отменена;Отпала необходимость','',5,'all',1),(4,'Задача','#ff6600',NULL,'Не выполнено;Перенос сроков;Отложено;Выполнено','yes',6,'all',1),(5,'Предложение','#66ccff',NULL,'Перенос;Отправлено КП;Отменено','',14,'activ',1),(6,'Событие','#666699',NULL,'Выполнено;Перенос;Отложено','',15,'activ',1),(7,'исх.Почта','#cccc00',NULL,'Отправлено КП;Отправлен Договор;Отправлена Презентация;Отправлена информация','',11,'all',1),(8,'вх.Звонок','#99cc00',NULL,'Новое обращение;Запрос счета;Запрос КП;Приглашение;Договорились о встрече','',7,'all',1),(9,'вх.Почта','#cc3300',NULL,'Отправлено;Не верный адрес;Отложено;Отменено','',10,'all',1),(10,'Поздравление','#009999',NULL,'Новый год;День Рождения;Праздник','',13,'task',1),(11,'исх.2.Звонок','#339966',NULL,'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП','',9,'all',1),(12,'Отправка КП','#ff0000',NULL,'Отправлено;Перенесено;Отложено;Отменено','',12,'all',1);
/*!40000 ALTER TABLE `salesman_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_budjet`
--

DROP TABLE IF EXISTS `salesman_budjet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_budjet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cat` int DEFAULT NULL COMMENT 'категория записи, ссылается на id в таблице _budjet_cat',
  `title` varchar(255) DEFAULT NULL COMMENT 'название расхода-дохода',
  `des` text COMMENT 'описание',
  `year` int DEFAULT NULL COMMENT 'год',
  `mon` int DEFAULT NULL COMMENT 'месяц',
  `summa` double(20,2) DEFAULT NULL COMMENT 'сумма',
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `iduser` int DEFAULT NULL COMMENT 'id пользователя _user.iduser',
  `do` varchar(3) DEFAULT NULL COMMENT 'признак того, что расход проведен',
  `rs` varchar(20) DEFAULT NULL COMMENT 'id расчетного счета из таблицы _mycomps_recv.id',
  `rs2` varchar(20) DEFAULT NULL COMMENT 'id расчетного счета (используется при перемещении средств)',
  `fid` text COMMENT 'id файлов из таблицы _files.fid разделенного запятой',
  `did` int DEFAULT NULL COMMENT 'id сделки из таблицы _dogovor.did',
  `conid` int DEFAULT NULL COMMENT '_clientcat.clid для поставщиков',
  `partid` int DEFAULT NULL COMMENT '_clientcat.clid для партнеров',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_budjet`
--

LOCK TABLES `salesman_budjet` WRITE;
/*!40000 ALTER TABLE `salesman_budjet` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_budjet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_budjet_bank`
--

DROP TABLE IF EXISTS `salesman_budjet_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_budjet_bank` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'метка времени',
  `number` varchar(50) DEFAULT NULL COMMENT 'номер документа',
  `datum` date DEFAULT NULL COMMENT 'дата проводки',
  `mon` varchar(2) DEFAULT NULL COMMENT 'месяц',
  `year` varchar(4) DEFAULT NULL COMMENT 'год',
  `tip` varchar(10) DEFAULT NULL COMMENT 'направление расхода - dohod, rashod',
  `title` varchar(255) DEFAULT NULL COMMENT 'название расхода',
  `content` text COMMENT 'описание расхода',
  `rs` int DEFAULT NULL COMMENT 'id расчетного счета',
  `from` text COMMENT 'название плательщика',
  `fromRS` varchar(20) DEFAULT NULL COMMENT 'р.с. плательщика',
  `fromINN` varchar(10) DEFAULT NULL COMMENT 'инн плательщика',
  `to` text COMMENT 'название получателя',
  `toRS` varchar(20) DEFAULT NULL COMMENT 'р.с. получателя',
  `toINN` varchar(10) DEFAULT NULL COMMENT 'инн получателя',
  `summa` float(20,2) DEFAULT NULL COMMENT 'сумма расхода',
  `clid` int DEFAULT NULL COMMENT 'id связанного клиента',
  `bid` int DEFAULT NULL COMMENT 'id связанной записи в бюджете',
  `category` int DEFAULT NULL COMMENT 'id статьи расхода',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Журнал банковской выписки';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_budjet_bank`
--

LOCK TABLES `salesman_budjet_bank` WRITE;
/*!40000 ALTER TABLE `salesman_budjet_bank` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_budjet_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_budjet_cat`
--

DROP TABLE IF EXISTS `salesman_budjet_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_budjet_cat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subid` int DEFAULT NULL COMMENT 'ид основной записи budjet_cat.id',
  `title` varchar(255) DEFAULT NULL COMMENT 'название',
  `tip` varchar(10) DEFAULT NULL COMMENT 'тип (расход-доход)',
  `clientpath` int DEFAULT NULL COMMENT 'id канала',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_budjet_cat`
--

LOCK TABLES `salesman_budjet_cat` WRITE;
/*!40000 ALTER TABLE `salesman_budjet_cat` DISABLE KEYS */;
INSERT INTO `salesman_budjet_cat` VALUES (1,0,'Расходы на офис','rashod',NULL,1),(2,1,'Аренда офиса','rashod',NULL,1),(3,1,'Телефония','rashod',NULL,1),(4,0,'Прочие поступления','dohod',NULL,1),(5,4,'Инвестиции','dohod',NULL,1),(7,1,'Продукты питания','rashod',NULL,1),(8,1,'Оборудование','rashod',NULL,1),(9,0,'Сотрудники','rashod',NULL,1),(10,9,'Зарплата','rashod',NULL,1),(11,9,'Премия','rashod',NULL,1),(12,9,'Командировочные','rashod',NULL,1),(13,4,'Наличка','dohod',NULL,1),(14,0,'Реклама','rashod',NULL,1),(15,14,'Интернет-реклама','rashod',177,1),(16,14,'Вебинары','rashod',86,1),(17,14,'Direct Mail','rashod',160,1),(18,0,'Расчеты с контрагентами','rashod',NULL,1),(19,18,'Поставщики','rashod',NULL,1),(20,18,'Партнеры','rashod',NULL,1);
/*!40000 ALTER TABLE `salesman_budjet_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_callhistory`
--

DROP TABLE IF EXISTS `salesman_callhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_callhistory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) NOT NULL COMMENT 'UID звонка из Астериска',
  `did` varchar(50) DEFAULT NULL COMMENT 'номер телефона наш если в src добавочный',
  `phone` varchar(20) NOT NULL COMMENT 'телефон клиента всегда',
  `direct` varchar(10) NOT NULL COMMENT 'направление вызова',
  `datum` datetime NOT NULL COMMENT 'дата-время вызова',
  `clid` int DEFAULT NULL COMMENT 'clid в таблице _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'pid в таблице _personcat.pid',
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser',
  `res` varchar(100) NOT NULL COMMENT 'результат вызова',
  `sec` int NOT NULL COMMENT 'продолжительность',
  `file` text COMMENT 'имя файла',
  `src` varchar(20) DEFAULT NULL COMMENT 'источник звонка',
  `dst` varchar(20) DEFAULT NULL COMMENT 'назначение звонка',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `statistica` (`phone`,`datum`,`iduser`,`res`,`direct`,`identity`),
  KEY `statistica2` (`direct`,`datum`,`iduser`,`identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_callhistory`
--

LOCK TABLES `salesman_callhistory` WRITE;
/*!40000 ALTER TABLE `salesman_callhistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_callhistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_capacity_client`
--

DROP TABLE IF EXISTS `salesman_capacity_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_capacity_client` (
  `id` int NOT NULL AUTO_INCREMENT,
  `capid` int DEFAULT NULL COMMENT 'не используется',
  `clid` int DEFAULT NULL COMMENT 'clid в таблице _clientcat.clid',
  `direction` int DEFAULT NULL COMMENT 'направление деятельности из таблицы _direction.id',
  `year` int DEFAULT NULL COMMENT 'план на какой год',
  `mon` int DEFAULT NULL COMMENT 'план на какой месяц',
  `sumplan` double(20,2) DEFAULT NULL COMMENT 'план продаж в указанном периоде данному клиенту по данному направлению',
  `sumfact` double(20,2) DEFAULT NULL COMMENT 'факт продаж, при закрытии сделки суммируются',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_capacity_client`
--

LOCK TABLES `salesman_capacity_client` WRITE;
/*!40000 ALTER TABLE `salesman_capacity_client` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_capacity_client` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_category`
--

DROP TABLE IF EXISTS `salesman_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_category` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'название отрасли',
  `tip` varchar(10) NOT NULL DEFAULT 'client' COMMENT 'к какой записи относится (client,person,contractor,partner,concurent)',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_category`
--

LOCK TABLES `salesman_category` WRITE;
/*!40000 ALTER TABLE `salesman_category` DISABLE KEYS */;
INSERT INTO `salesman_category` VALUES (1,'Физические лица','client',1),(2,'Розница','client',1);
/*!40000 ALTER TABLE `salesman_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_changepass`
--

DROP TABLE IF EXISTS `salesman_changepass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_changepass` (
  `id` int NOT NULL AUTO_INCREMENT,
  `useremail` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_changepass`
--

LOCK TABLES `salesman_changepass` WRITE;
/*!40000 ALTER TABLE `salesman_changepass` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_changepass` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_clientcat`
--

DROP TABLE IF EXISTS `salesman_clientcat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_clientcat` (
  `clid` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(30) DEFAULT NULL,
  `title` varchar(250) DEFAULT NULL,
  `idcategory` int DEFAULT '0',
  `iduser` varchar(10) DEFAULT '0',
  `clientpath` int DEFAULT '0',
  `des` text,
  `address` text,
  `phone` varchar(250) DEFAULT NULL,
  `fax` varchar(250) DEFAULT NULL,
  `site_url` varchar(250) DEFAULT NULL,
  `mail_url` varchar(250) DEFAULT NULL,
  `trash` varchar(10) DEFAULT 'no',
  `fav` varchar(20) DEFAULT 'no',
  `pid` int DEFAULT '0',
  `head_clid` int DEFAULT '0',
  `scheme` text,
  `tip_cmr` varchar(255) DEFAULT NULL,
  `territory` int DEFAULT '0',
  `input1` varchar(255) DEFAULT NULL,
  `input2` varchar(255) DEFAULT NULL,
  `input3` varchar(255) DEFAULT NULL,
  `input4` varchar(255) DEFAULT NULL,
  `input5` varchar(255) DEFAULT NULL,
  `input6` varchar(255) DEFAULT NULL,
  `input7` varchar(255) DEFAULT NULL,
  `input8` varchar(255) DEFAULT NULL,
  `input9` varchar(255) DEFAULT NULL,
  `input10` varchar(255) DEFAULT NULL,
  `date_create` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_edit` timestamp NULL DEFAULT NULL,
  `creator` int DEFAULT '0',
  `editor` int DEFAULT '0',
  `recv` text,
  `dostup` varchar(255) DEFAULT NULL,
  `last_dog` date DEFAULT NULL,
  `last_hist` datetime DEFAULT NULL,
  `type` varchar(100) DEFAULT 'client',
  `priceLevel` varchar(255) DEFAULT 'price_1',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`clid`),
  KEY `iduser` (`iduser`),
  KEY `identity` (`identity`),
  KEY `trash` (`trash`),
  KEY `uid` (`uid`),
  KEY `phone` (`phone`),
  KEY `fax` (`fax`),
  KEY `mail_url` (`mail_url`),
  KEY `type` (`type`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_clientcat`
--

LOCK TABLES `salesman_clientcat` WRITE;
/*!40000 ALTER TABLE `salesman_clientcat` DISABLE KEYS */;
INSERT INTO `salesman_clientcat` VALUES (1,NULL,'Тест 001',0,'1',2,NULL,NULL,'7 (922) 328-94-66',NULL,NULL,NULL,'no','no',0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2023-02-04 16:28:10',NULL,1,0,';;;;;;;;;;;;;;;',NULL,NULL,'2023-02-04 19:28:31','client','price_1',1);
/*!40000 ALTER TABLE `salesman_clientcat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_clientpath`
--

DROP TABLE IF EXISTS `salesman_clientpath`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_clientpath` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название источника',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'Дефолтный признак',
  `utm_source` varchar(255) DEFAULT NULL COMMENT 'Связка с источником',
  `destination` varchar(12) DEFAULT NULL COMMENT 'Связка с номером телефона',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_clientpath`
--

LOCK TABLES `salesman_clientpath` WRITE;
/*!40000 ALTER TABLE `salesman_clientpath` DISABLE KEYS */;
INSERT INTO `salesman_clientpath` VALUES (1,'Личные связи','','','',1),(2,'Маркетинг','','','',1),(3,'Справочник','','','',1),(4,'Заказ с сайта','yes','','',1),(5,'Рекомендации клиентов','','fromfriend','',1);
/*!40000 ALTER TABLE `salesman_clientpath` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_comments`
--

DROP TABLE IF EXISTS `salesman_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idparent` int DEFAULT '0' COMMENT 'comments.id -- ссылка на тему обсуждения',
  `mid` int DEFAULT NULL COMMENT 'DEPRECATED',
  `datum` timestamp NULL DEFAULT NULL,
  `clid` int DEFAULT NULL COMMENT 'clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'personcat.pid',
  `did` int DEFAULT NULL COMMENT 'dogovor.did',
  `prid` int DEFAULT NULL COMMENT 'price.n_id',
  `project` int DEFAULT NULL COMMENT 'id проекта',
  `iduser` int DEFAULT NULL COMMENT 'user.iduser',
  `title` varchar(255) DEFAULT NULL COMMENT 'заголовок',
  `content` text COMMENT 'текст',
  `fid` text COMMENT '_files.fid в виде списка с разделением ;',
  `lastCommentDate` datetime DEFAULT NULL COMMENT 'дата последнего коментария',
  `isClose` varchar(10) DEFAULT 'no' COMMENT 'закрыто или открыты обсуждение',
  `dateClose` datetime DEFAULT NULL COMMENT 'дата закрытия обсуждения',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `idparent` (`idparent`),
  KEY `isClose` (`isClose`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`),
  KEY `did` (`did`),
  KEY `project` (`project`),
  KEY `iduser` (`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_comments`
--

LOCK TABLES `salesman_comments` WRITE;
/*!40000 ALTER TABLE `salesman_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_comments_subscribe`
--

DROP TABLE IF EXISTS `salesman_comments_subscribe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_comments_subscribe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcomment` int DEFAULT NULL COMMENT 'тема обсуждения _comments.id',
  `iduser` int DEFAULT NULL COMMENT 'пользователь _user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idcomment` (`idcomment`),
  KEY `iduser` (`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_comments_subscribe`
--

LOCK TABLES `salesman_comments_subscribe` WRITE;
/*!40000 ALTER TABLE `salesman_comments_subscribe` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_comments_subscribe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_complect`
--

DROP TABLE IF EXISTS `salesman_complect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_complect` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT NULL COMMENT 'сделка _dogovor.id',
  `ccid` int DEFAULT NULL COMMENT 'тип контрольной точки _complect_cat.ccid',
  `data_plan` date DEFAULT NULL COMMENT 'плановая дата',
  `data_fact` date DEFAULT NULL COMMENT 'факт. дата выполнения',
  `doit` varchar(5) NOT NULL DEFAULT 'no' COMMENT 'признак выполнения',
  `iduser` int DEFAULT NULL COMMENT 'пользователь, выполнивший КТ _user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_complect`
--

LOCK TABLES `salesman_complect` WRITE;
/*!40000 ALTER TABLE `salesman_complect` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_complect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_complect_cat`
--

DROP TABLE IF EXISTS `salesman_complect_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_complect_cat` (
  `ccid` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL COMMENT 'название контрольной точки',
  `corder` int DEFAULT NULL COMMENT 'порядок вывода',
  `dstep` int DEFAULT NULL COMMENT 'привязка к этапу сделки _dogcategory.idcategory',
  `role` text COMMENT 'список должностей, которым доступно изменение контр.точки в виде списка с разделением ,',
  `users` text COMMENT 'список сотрудников, которым доступно изменение контр.точки usser.iduser в виде списка с разделением ,',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ccid`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_complect_cat`
--

LOCK TABLES `salesman_complect_cat` WRITE;
/*!40000 ALTER TABLE `salesman_complect_cat` DISABLE KEYS */;
INSERT INTO `salesman_complect_cat` VALUES (1,'Получение оплаты',4,7,'','',1),(2,'Подписать договор, Выставить счет',3,6,'','1',1),(3,'Согласование спецификации',2,5,'','1',1),(4,'Начать работы',5,0,'','',1),(5,'Получить документы',6,8,'Руководитель организации,Руководитель подразделения,Менеджер продаж','',1),(6,'Подготовка и отправка КП',1,11,'Руководитель подразделения','',1),(7,'Работы выполнены',7,0,'Руководитель организации,Руководитель подразделения,Руководитель отдела','',1);
/*!40000 ALTER TABLE `salesman_complect_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_contract`
--

DROP TABLE IF EXISTS `salesman_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_contract` (
  `deid` int NOT NULL AUTO_INCREMENT,
  `datum` datetime DEFAULT CURRENT_TIMESTAMP,
  `number` varchar(255) DEFAULT NULL,
  `datum_start` date DEFAULT NULL,
  `datum_end` date DEFAULT NULL,
  `des` text,
  `clid` int DEFAULT '0',
  `payer` int DEFAULT NULL,
  `pid` int DEFAULT '0',
  `did` int DEFAULT NULL,
  `ftitle` varchar(255) DEFAULT NULL,
  `fname` varchar(250) DEFAULT NULL,
  `ftype` varchar(250) DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  `title` text,
  `idtype` int DEFAULT '0',
  `crid` int DEFAULT '0',
  `mcid` int DEFAULT '0',
  `signer` int DEFAULT '0',
  `status` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`deid`),
  KEY `did_iduser` (`did`,`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_contract`
--

LOCK TABLES `salesman_contract` WRITE;
/*!40000 ALTER TABLE `salesman_contract` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_contract` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_contract_poz`
--

DROP TABLE IF EXISTS `salesman_contract_poz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_contract_poz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deid` int NOT NULL DEFAULT '0' COMMENT 'id документа (_contract.deid)',
  `did` int DEFAULT '0' COMMENT 'id сделки',
  `spid` int NOT NULL DEFAULT '0' COMMENT 'id позиции спецификации',
  `prid` int DEFAULT '0' COMMENT 'id позиции прайса',
  `kol` double(20,4) DEFAULT '0.0000' COMMENT 'количество товара',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Позиции спецификации для Актов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_contract_poz`
--

LOCK TABLES `salesman_contract_poz` WRITE;
/*!40000 ALTER TABLE `salesman_contract_poz` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_contract_poz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_contract_status`
--

DROP TABLE IF EXISTS `salesman_contract_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_contract_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
  `tip` text COMMENT 'типы документов',
  `title` varchar(100) DEFAULT NULL COMMENT 'название статуса',
  `color` varchar(7) DEFAULT NULL COMMENT 'цвет статуса',
  `ord` int DEFAULT NULL COMMENT 'порядок вывода статуса',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Статусы документов по типам';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_contract_status`
--

LOCK TABLES `salesman_contract_status` WRITE;
/*!40000 ALTER TABLE `salesman_contract_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_contract_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_contract_statuslog`
--

DROP TABLE IF EXISTS `salesman_contract_statuslog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_contract_statuslog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deid` int DEFAULT NULL COMMENT 'id документа',
  `status` int DEFAULT NULL COMMENT 'новый статус',
  `oldstatus` int DEFAULT NULL COMMENT 'старый статус',
  `iduser` int DEFAULT NULL COMMENT 'id сотрудника',
  `des` text COMMENT 'комментарий',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Лог изменения статуса документов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_contract_statuslog`
--

LOCK TABLES `salesman_contract_statuslog` WRITE;
/*!40000 ALTER TABLE `salesman_contract_statuslog` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_contract_statuslog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_contract_temp`
--

DROP TABLE IF EXISTS `salesman_contract_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_contract_temp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `typeid` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_contract_temp`
--

LOCK TABLES `salesman_contract_temp` WRITE;
/*!40000 ALTER TABLE `salesman_contract_temp` DISABLE KEYS */;
INSERT INTO `salesman_contract_temp` VALUES (1,1,'Квитанция на оплату','kvitancia_sberbank_pd4.docx',1),(2,4,'Базовый шаблон','invoice.tpl',1),(3,2,'Приёма-передачи. Права','akt_prava.tpl',1),(4,2,'Приёма-передачи. Услуги','akt_simple.tpl',1),(5,2,'Приёма-передачи. Услуги (расширенный)','akt_full.tpl',1),(6,3,'Счет-фактура (XLCX)','schet_faktura.xlsx',1),(7,4,'Счет с QRcode','invoice_qr.tpl',1);
/*!40000 ALTER TABLE `salesman_contract_temp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_contract_type`
--

DROP TABLE IF EXISTS `salesman_contract_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_contract_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'название документа',
  `type` varchar(255) DEFAULT NULL COMMENT 'внутренний тип get_akt, get_aktper, get_dogovor, invoice',
  `role` text COMMENT 'список ролей, которым доступно изменение',
  `users` text COMMENT 'список пользователей, которые могут добавлять такие документы -- user.iduser с разделением ,',
  `num` int DEFAULT NULL COMMENT 'счетчик нумерации',
  `format` varchar(255) DEFAULT NULL COMMENT 'шаблон формата номера',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_contract_type`
--

LOCK TABLES `salesman_contract_type` WRITE;
/*!40000 ALTER TABLE `salesman_contract_type` DISABLE KEYS */;
INSERT INTO `salesman_contract_type` VALUES (1,'Квитанция в банк','','','',0,'{cnum}',1),(2,'Акт приема-передачи','get_akt','','',0,'{cnum}',1),(3,'Счет-фактура','','','',0,'{cnum}',1),(4,'Счет','invoice','','',0,'',1),(5,'Договор','get_dogovor','','',0,'',1);
/*!40000 ALTER TABLE `salesman_contract_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_credit`
--

DROP TABLE IF EXISTS `salesman_credit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_credit` (
  `crid` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT '0',
  `clid` int DEFAULT '0',
  `pid` int DEFAULT '0',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datum_credit` date DEFAULT NULL,
  `summa_credit` double(20,2) DEFAULT '0.00',
  `nds_credit` double(20,2) DEFAULT '0.00',
  `iduser` int DEFAULT '0',
  `idowner` int DEFAULT '0',
  `do` varchar(5) DEFAULT 'no',
  `invoice` varchar(20) DEFAULT NULL,
  `invoice_chek` varchar(40) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `rs` int DEFAULT '0',
  `tip` varchar(255) DEFAULT NULL,
  `template` int DEFAULT '0',
  `suffix` text,
  `signer` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`crid`),
  KEY `do` (`do`),
  KEY `did` (`did`),
  KEY `clid` (`clid`),
  KEY `iduser` (`iduser`),
  KEY `datum_credit` (`datum_credit`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_credit`
--

LOCK TABLES `salesman_credit` WRITE;
/*!40000 ALTER TABLE `salesman_credit` DISABLE KEYS */;
INSERT INTO `salesman_credit` VALUES (1,1,1,0,'2023-02-04 18:29:00','2023-02-09',1350.00,0.00,1,1,'no','2',NULL,NULL,1,'Счет-договор',7,'&lt;p&gt;&amp;nbsp;&lt;/p&gt;\r\n\r\n&lt;p&gt;&lt;b&gt;ВНИМАНИЕ! СЧЕТ ДЕЙСТВИТЕЛЕН В ТЕЧЕНИЕ 5 ДНЕЙ С ДАТЫ СОЗДАНИЯ.&lt;/b&gt;&lt;/p&gt;\r\n\r\n&lt;p align=&quot;justify&quot;&gt;Стороны договорились, что стоимость услуг оплачивается Покупателем в размере 100 (сто) % от стоимости, указанной в вышеприведённой таблице. Датой оплаты счета считается дата списания денежных средств с расчетного счета Покупателя.&lt;/p&gt;\r\n\r\n&lt;p&gt;Настоящий счет является договором-офертой в соответствии со ст.435 ГК РФ. Настоящая оферта действительна и ее акцепт возможен &lt;b&gt;в течение 5 (пяти) &lt;/b&gt;рабочих дней с момента выставления счета. При осуществлении оплаты по окончании срока действия оферты, Поставщик вправе потребовать доплаты, при условии изменения текущей розничной стоимости товара, либо не принимать оплату. Оплата по настоящему счёту является заключением договора об оказании услуг, при условии принятия ее поставщиком, в том числе по истечении срока, установленного для акцепта данной оферты.&lt;/p&gt;\r\n\r\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;\r\n\r\n&lt;p&gt;&lt;b&gt;Датой окончания предоставления услуги считается дата подписания обеими Сторонами акта приёма-передачи работ. Настоящий счёт-договор вступает в силу с момента его оплаты Покупателем и действует до момента исполнения всех обязательств по нему.&lt;/b&gt;&lt;/p&gt;\r\n',0,1);
/*!40000 ALTER TABLE `salesman_credit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_currency`
--

DROP TABLE IF EXISTS `salesman_currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_currency` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` date DEFAULT NULL COMMENT 'дата добавления',
  `name` varchar(50) DEFAULT NULL COMMENT 'название валюты',
  `view` varchar(10) DEFAULT NULL COMMENT 'отображаемое название валюты',
  `code` varchar(10) DEFAULT NULL COMMENT 'код валюты',
  `course` double(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'текущий курс',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Таблица курсов валют';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_currency`
--

LOCK TABLES `salesman_currency` WRITE;
/*!40000 ALTER TABLE `salesman_currency` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_currency_log`
--

DROP TABLE IF EXISTS `salesman_currency_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_currency_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcurrency` int DEFAULT NULL COMMENT 'id записи валюты',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
  `course` double(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'курс на дату',
  `iduser` varchar(10) DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Таблица изменения курсов валют';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_currency_log`
--

LOCK TABLES `salesman_currency_log` WRITE;
/*!40000 ALTER TABLE `salesman_currency_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_currency_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_customsettings`
--

DROP TABLE IF EXISTS `salesman_customsettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_customsettings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время добавления-изменения',
  `tip` varchar(50) DEFAULT NULL COMMENT 'тип параметра',
  `params` text COMMENT 'параметры',
  `iduser` int DEFAULT NULL COMMENT 'id сотрудника',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Хранилище различных настроек';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_customsettings`
--

LOCK TABLES `salesman_customsettings` WRITE;
/*!40000 ALTER TABLE `salesman_customsettings` DISABLE KEYS */;
INSERT INTO `salesman_customsettings` VALUES (1,'2021-10-28 19:41:02','eform','{\"client\":{\"title\":{\"active\":\"yes\",\"requered\":\"yes\",\"more\":\"no\"},\"head_clid\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"phone\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"idcategory\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mail_url\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"site_url\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"address\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"yes\"},\"fax\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"tip_cmr\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"clientpath\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"territory\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input1\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input3\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input4\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"des\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input2\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input5\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input6\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input7\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input9\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input8\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input10\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"}},\"person\":{\"ptitle\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"person\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"rol\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"tel\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input7\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mob\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"mail\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"loyalty\":{\"active\":\"yes\",\"requered\":\"no\",\"more\":\"no\"},\"input1\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input3\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input4\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input5\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input6\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"},\"input12\":{\"active\":\"no\",\"requered\":\"no\",\"more\":\"no\"}}}',NULL,1),(2,'2020-08-13 07:14:27','settingsMore','{\"timecheck\":\"yes\",\"budjetEnableVijets\":\"no\"}',1,1);
/*!40000 ALTER TABLE `salesman_customsettings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_deal_anketa`
--

DROP TABLE IF EXISTS `salesman_deal_anketa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_deal_anketa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idbase` int NOT NULL DEFAULT '0' COMMENT 'id поля анкеты',
  `ida` int NOT NULL COMMENT 'id анкеты',
  `did` int DEFAULT NULL COMMENT 'id сделки',
  `clid` int DEFAULT NULL COMMENT 'id клиента',
  `value` varchar(255) DEFAULT NULL COMMENT 'варианты значений',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Значения для анкет по сделкам';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_deal_anketa`
--

LOCK TABLES `salesman_deal_anketa` WRITE;
/*!40000 ALTER TABLE `salesman_deal_anketa` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_deal_anketa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_deal_anketa_base`
--

DROP TABLE IF EXISTS `salesman_deal_anketa_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_deal_anketa_base` (
  `id` int NOT NULL AUTO_INCREMENT,
  `block` int NOT NULL DEFAULT '0' COMMENT 'id блока',
  `ida` int NOT NULL COMMENT 'id анкеты',
  `name` varchar(255) NOT NULL COMMENT 'Название поля',
  `tip` varchar(10) NOT NULL COMMENT 'Тип поля',
  `value` text COMMENT 'Возможные значения',
  `ord` int DEFAULT NULL COMMENT 'Порядок вывода',
  `pole` varchar(10) DEFAULT NULL COMMENT 'id поля',
  `pwidth` int DEFAULT '50' COMMENT 'ширина поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='База полей для анкеты';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_deal_anketa_base`
--

LOCK TABLES `salesman_deal_anketa_base` WRITE;
/*!40000 ALTER TABLE `salesman_deal_anketa_base` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_deal_anketa_base` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_deal_anketa_list`
--

DROP TABLE IF EXISTS `salesman_deal_anketa_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_deal_anketa_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` int NOT NULL DEFAULT '1' COMMENT 'Активность анкеты',
  `datum` datetime NOT NULL COMMENT 'Дата создания',
  `datum_edit` datetime NOT NULL COMMENT 'Дата изменения',
  `title` varchar(255) DEFAULT NULL COMMENT 'Название анкеты',
  `content` text COMMENT 'Описание анкеты',
  `iduser` int DEFAULT NULL COMMENT 'id Сотрудника-автора',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Список базовых анкет для сделок';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_deal_anketa_list`
--

LOCK TABLES `salesman_deal_anketa_list` WRITE;
/*!40000 ALTER TABLE `salesman_deal_anketa_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_deal_anketa_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_direction`
--

DROP TABLE IF EXISTS `salesman_direction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_direction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'название',
  `isDefault` varchar(5) DEFAULT NULL COMMENT 'признак дефолтности',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_direction`
--

LOCK TABLES `salesman_direction` WRITE;
/*!40000 ALTER TABLE `salesman_direction` DISABLE KEYS */;
INSERT INTO `salesman_direction` VALUES (1,'Основное','yes',1);
/*!40000 ALTER TABLE `salesman_direction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_dogcategory`
--

DROP TABLE IF EXISTS `salesman_dogcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_dogcategory` (
  `idcategory` bigint NOT NULL AUTO_INCREMENT,
  `title` int DEFAULT NULL COMMENT 'название типа',
  `content` text COMMENT 'описание',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`),
  KEY `identity` (`identity`),
  KEY `title` (`title`),
  KEY `identity_2` (`identity`),
  KEY `title_2` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_dogcategory`
--

LOCK TABLES `salesman_dogcategory` WRITE;
/*!40000 ALTER TABLE `salesman_dogcategory` DISABLE KEYS */;
INSERT INTO `salesman_dogcategory` VALUES (2,20,'Подтвержден интерес',1),(5,60,'Обсуждение деталей - продукты, услуги, оплата',1),(6,80,'Согласован договор, Выставлен счет',1),(7,90,'Получена предоплата, Выполнение договора',1),(8,100,'Закрытие сделки, Подписание документов',1),(10,0,'Проявлен/Выявлен интерес',1),(11,40,'Отправлено КП',1);
/*!40000 ALTER TABLE `salesman_dogcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_dogovor`
--

DROP TABLE IF EXISTS `salesman_dogovor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_dogovor` (
  `did` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(30) DEFAULT NULL,
  `idcategory` int DEFAULT '0',
  `clid` int DEFAULT '0',
  `payer` int DEFAULT '0',
  `pid` int DEFAULT '0',
  `datum` date DEFAULT NULL,
  `autor` int DEFAULT '0',
  `datum_plan` date DEFAULT NULL,
  `title` text,
  `content` text,
  `tip` varchar(100) DEFAULT '0',
  `kol` double(20,2) DEFAULT '0.00',
  `close` varchar(5) DEFAULT 'no',
  `lat` float(10,6) DEFAULT NULL,
  `lan` float(10,6) DEFAULT NULL,
  `adres` text,
  `iduser` int DEFAULT '0',
  `datum_izm` date DEFAULT NULL,
  `datum_close` date DEFAULT NULL,
  `sid` int DEFAULT '0',
  `kol_fact` double(20,2) DEFAULT '0.00',
  `des_fact` text,
  `coid` varchar(12) DEFAULT NULL,
  `co_kol` double(20,2) DEFAULT '0.00',
  `coid1` text,
  `coid2` varchar(12) DEFAULT NULL,
  `dog_num` text,
  `marga` double(20,2) DEFAULT '0.00',
  `calculate` varchar(4) DEFAULT NULL,
  `isFrozen` int DEFAULT '0',
  `datum_start` date DEFAULT NULL,
  `datum_end` date DEFAULT NULL,
  `pid_list` varchar(255) DEFAULT NULL,
  `partner` varchar(100) DEFAULT NULL,
  `zayavka` varchar(200) DEFAULT NULL,
  `ztitle` varchar(255) DEFAULT NULL,
  `mcid` int DEFAULT '0',
  `direction` int DEFAULT '0',
  `idcurrency` int DEFAULT '0',
  `idcourse` int DEFAULT '0',
  `akt_date` date DEFAULT NULL,
  `akt_temp` varchar(200) DEFAULT NULL,
  `lid` int DEFAULT '0',
  `input1` varchar(512) DEFAULT NULL,
  `input2` varchar(512) DEFAULT NULL,
  `input3` varchar(512) DEFAULT NULL,
  `input4` varchar(512) DEFAULT NULL,
  `input5` varchar(512) DEFAULT NULL,
  `input6` varchar(512) DEFAULT NULL,
  `input7` varchar(512) DEFAULT NULL,
  `input8` varchar(512) DEFAULT NULL,
  `input9` varchar(512) DEFAULT NULL,
  `input10` varchar(512) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`did`),
  KEY `identity` (`identity`),
  KEY `iduser` (`iduser`),
  KEY `idcategory` (`idcategory`),
  KEY `tip` (`tip`),
  KEY `direction` (`direction`),
  KEY `datum_plan` (`datum_plan`),
  KEY `clid` (`clid`),
  KEY `note` (`iduser`,`identity`),
  KEY `sid` (`sid`),
  KEY `close` (`close`),
  KEY `datum` (`datum`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_dogovor`
--

LOCK TABLES `salesman_dogovor` WRITE;
/*!40000 ALTER TABLE `salesman_dogovor` DISABLE KEYS */;
INSERT INTO `salesman_dogovor` VALUES (1,NULL,10,1,1,0,'2023-02-04',1,'2023-02-18','СД1: Тест 001',NULL,'6',1350.00,'no',NULL,NULL,NULL,1,NULL,NULL,0,0.00,NULL,NULL,0.00,NULL,NULL,NULL,350.00,'yes',0,NULL,NULL,NULL,NULL,NULL,NULL,1,1,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `salesman_dogovor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_dogprovider`
--

DROP TABLE IF EXISTS `salesman_dogprovider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_dogprovider` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT '0',
  `conid` int DEFAULT '0',
  `partid` int DEFAULT '0',
  `summa` double(20,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT NULL,
  `bid` int DEFAULT '0',
  `recal` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_dogprovider`
--

LOCK TABLES `salesman_dogprovider` WRITE;
/*!40000 ALTER TABLE `salesman_dogprovider` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_dogprovider` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_dogstatus`
--

DROP TABLE IF EXISTS `salesman_dogstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_dogstatus` (
  `sid` bigint NOT NULL AUTO_INCREMENT,
  `title` text COMMENT 'название',
  `result_close` varchar(5) DEFAULT NULL COMMENT 'Результат закрытия: lose - Проигрыш; win - Победа',
  `content` text COMMENT 'описание',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_dogstatus`
--

LOCK TABLES `salesman_dogstatus` WRITE;
/*!40000 ALTER TABLE `salesman_dogstatus` DISABLE KEYS */;
INSERT INTO `salesman_dogstatus` VALUES (1,'Победа полная','win','Обозначает выигрыш, Договор выполнен и получена прибыль',1),(2,'Победа, договорились с конкурентами','win','Сделка выиграна, заключен и исполнен договор, получена прибыль',1),(3,'Проигрыш по цене','lose','Договор не заключен, проиграли по цене',1),(4,'Проигрыш, договорились с конкурентами','lose','Сделка проиграна, но удалось договориться с конкурентами.',1),(5,'Отменена Заказчиком','lose','Сделка отменена Заказчиком',1),(6,'Отказ от участия','lose','Мы отказались от участия в сделке',1),(7,'Закрыл менеджер. Отказ','lose','Проигрыш',1);
/*!40000 ALTER TABLE `salesman_dogstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_dogtips`
--

DROP TABLE IF EXISTS `salesman_dogtips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_dogtips` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `title` text COMMENT 'название',
  `isDefault` varchar(5) DEFAULT NULL COMMENT 'признак дефолтности',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_dogtips`
--

LOCK TABLES `salesman_dogtips` WRITE;
/*!40000 ALTER TABLE `salesman_dogtips` DISABLE KEYS */;
INSERT INTO `salesman_dogtips` VALUES (1,'Продажа простая','',1),(2,'Продажа с разработкой','',1),(3,'Услуги','',1),(4,'Продажа услуг','',1),(5,'Тендер','',1),(6,'Продажа быстрая','yes',1);
/*!40000 ALTER TABLE `salesman_dogtips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_dostup`
--

DROP TABLE IF EXISTS `salesman_dostup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_dostup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clid` int DEFAULT NULL COMMENT 'Запись клиента _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'Запись контакта _personcat.pid',
  `did` int DEFAULT NULL COMMENT 'Запись сделки _dogovor.did',
  `iduser` int DEFAULT NULL COMMENT 'Сотрудник, которому дан доступ _user.iduser',
  `subscribe` varchar(3) DEFAULT 'off' COMMENT 'отправлять уведомления (on-off) по сделкам',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `yindex` (`clid`,`pid`,`did`,`iduser`),
  KEY `clid` (`clid`),
  KEY `did` (`did`),
  KEY `iduser` (`iduser`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_dostup`
--

LOCK TABLES `salesman_dostup` WRITE;
/*!40000 ALTER TABLE `salesman_dostup` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_dostup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_doubles`
--

DROP TABLE IF EXISTS `salesman_doubles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_doubles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
  `tip` text COMMENT 'типы дубля',
  `idmain` int DEFAULT NULL COMMENT 'id проверяемой записи',
  `list` varchar(500) DEFAULT NULL COMMENT 'json-массив найденных дублей',
  `ids` varchar(100) DEFAULT NULL COMMENT 'список всех id, упомятутых в list',
  `status` varchar(3) DEFAULT 'no' COMMENT 'статус',
  `datumdo` timestamp NULL DEFAULT NULL COMMENT 'дата обработки',
  `des` text COMMENT 'комментарий',
  `iduser` varchar(10) DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `filter` (`id`,`tip`(10),`idmain`,`ids`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Лог поиска дублей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_doubles`
--

LOCK TABLES `salesman_doubles` WRITE;
/*!40000 ALTER TABLE `salesman_doubles` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_doubles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_entry`
--

DROP TABLE IF EXISTS `salesman_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_entry` (
  `ide` int NOT NULL AUTO_INCREMENT,
  `uid` int DEFAULT NULL,
  `clid` int DEFAULT NULL COMMENT 'Клиент _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'Контакт _personcat.pid',
  `did` int DEFAULT NULL COMMENT 'Созданная сделка _dogovor.did',
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания',
  `datum_do` timestamp NULL DEFAULT NULL COMMENT 'дата обработки обращения',
  `iduser` int DEFAULT NULL COMMENT 'ответственный user.iduser',
  `autor` int DEFAULT NULL COMMENT 'автор user.iduser',
  `content` text COMMENT 'коментарий',
  `status` int DEFAULT '0' COMMENT 'Статус обработки: 0-новое, 1-обработано, 2 - отмена',
  `identity` int NOT NULL,
  PRIMARY KEY (`ide`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_entry`
--

LOCK TABLES `salesman_entry` WRITE;
/*!40000 ALTER TABLE `salesman_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_entry_poz`
--

DROP TABLE IF EXISTS `salesman_entry_poz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_entry_poz` (
  `idp` int NOT NULL AUTO_INCREMENT,
  `ide` int DEFAULT NULL COMMENT 'Обращение _entry.ide',
  `prid` int DEFAULT NULL COMMENT 'Связь с прайсом _price.n_id, не обязательный',
  `title` varchar(255) DEFAULT NULL COMMENT 'название позиции',
  `kol` int DEFAULT NULL COMMENT 'количество',
  `price` double NOT NULL DEFAULT '0' COMMENT 'цена',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_entry_poz`
--

LOCK TABLES `salesman_entry_poz` WRITE;
/*!40000 ALTER TABLE `salesman_entry_poz` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_entry_poz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_field`
--

DROP TABLE IF EXISTS `salesman_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_field` (
  `fld_id` int NOT NULL AUTO_INCREMENT,
  `fld_tip` varchar(10) DEFAULT NULL COMMENT 'тип поля - client, person, price, dogovor',
  `fld_name` varchar(10) DEFAULT NULL COMMENT 'имя поля в БД',
  `fld_title` varchar(100) DEFAULT NULL COMMENT 'название поля для интерфейса',
  `fld_required` varchar(10) DEFAULT 'required' COMMENT 'признак обязательности',
  `fld_on` varchar(255) DEFAULT 'yes' COMMENT 'признак активности поля',
  `fld_order` int DEFAULT NULL COMMENT 'порядок вывода',
  `fld_stat` varchar(10) DEFAULT NULL COMMENT 'можно ли поле выключить',
  `fld_temp` varchar(255) DEFAULT NULL COMMENT 'тип поля - input, select...',
  `fld_var` text COMMENT 'вариант готовых ответов',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`fld_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1065 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_field`
--

LOCK TABLES `salesman_field` WRITE;
/*!40000 ALTER TABLE `salesman_field` DISABLE KEYS */;
INSERT INTO `salesman_field` VALUES (15,'client','input1','доп.поле',NULL,NULL,14,'no','--Обычное--','',1),(16,'client','input2','доп.поле',NULL,NULL,20,'no','--Обычное--','до 3х,св.3 до 10,св.10 до 50,св.50',1),(17,'client','input3','доп.поле',NULL,NULL,15,'no','--Обычное--','',1),(1,'client','title','Название','required','yes',1,'yes','--Обычное--','',1),(2,'client','iduser','Ответственный','required','yes',3,'yes','','',1),(3,'client','idcategory','Категория','','',5,'yes','--Обычное--','',1),(4,'client','head_clid','Головн. орг-ия','','',2,'yes','--Обычное--','',1),(5,'client','pid','Осн. контакт','','',9,'yes','','',1),(6,'client','address','Адрес','','yes',8,'yes','adres','',1),(7,'client','phone','Телефон','','yes',4,'yes','','',1),(8,'client','fax','Факс','','yes',10,'yes','','',1),(9,'client','site_url','Сайт',NULL,'yes',7,'yes','','',1),(10,'client','mail_url','Почта',NULL,'yes',6,'yes','','',1),(11,'client','territory','Территория','','',13,'yes','','',1),(12,'client','des','Описание',NULL,'',18,'yes','','',1),(13,'client','scheme','Принятие решений','','',17,'yes','--Обычное--','',1),(14,'client','tip_cmr','Тип отношений','','',11,'yes','--Обычное--','',1),(25,'person','clid','Клиент','','yes',5,'yes','--Обычное--','',1),(26,'person','ptitle','Должность','required','yes',2,'yes','','',1),(27,'person','person','Ф.И.О.','required','yes',1,'yes','','',1),(28,'person','tel','Тел.','','yes',6,'yes','','',1),(29,'person','fax','Факс',NULL,'yes',8,'yes','','',1),(30,'person','mob','Моб.','','yes',9,'yes','','',1),(31,'person','mail','Почта','','yes',11,'yes','','',1),(32,'person','rol','Роль','','',3,'yes','','',1),(33,'person','social','Прочее',NULL,'',14,'yes','','',1),(34,'person','iduser','Куратор','required','yes',4,'yes','--Обычное--','',1),(35,'person','loyalty','Лояльность','','',12,'yes','--Обычное--','',1),(36,'person','input1','Дата рождения','','',13,'no','datum','',1),(37,'person','input2','доп.поле',NULL,NULL,15,'no','--Обычное--','',1),(38,'person','input3','доп.поле',NULL,NULL,16,'no','--Обычное--','',1),(39,'person','input4','доп.поле',NULL,NULL,17,'no','--Обычное--','',1),(40,'person','input5','доп.поле',NULL,NULL,18,'no','--Обычное--','',1),(41,'person','input6','доп.поле',NULL,NULL,19,'no','--Обычное--','',1),(42,'person','input7','Добавочный',NULL,'yes',7,'no','--Обычное--','',1),(43,'person','input8','доп.поле','','',20,'no','--Обычное--','',1),(44,'person','input9','доп.поле','','',21,'no','--Обычное--','',1),(18,'client','input4','доп.поле',NULL,NULL,16,'no','--Обычное--','',1),(19,'client','input5','доп.поле',NULL,NULL,21,'no','--Обычное--','',1),(20,'client','input6','доп.поле',NULL,NULL,22,'no','--Обычное--','',1),(21,'client','input7','доп.поле',NULL,NULL,23,'no','--Обычное--','',1),(22,'client','input8','доп.поле',NULL,NULL,25,'no','--Обычное--','',1),(23,'client','input9','доп.поле',NULL,NULL,24,'no','--Обычное--','',1),(24,'client','input10','доп.поле',NULL,NULL,26,'no','--Обычное--','',1),(45,'client','recv','Реквизиты','','yes',19,'yes','--Обычное--','',1),(46,'client','clientpath','Источник клиента','','',12,'yes','','',1),(47,'person','clientpath','Канал привлечения','','',10,'yes','--Обычное--','',1),(48,'dogovor','zayavka','Номер заявки','','',1,'no','','',1),(49,'dogovor','ztitle','Основание','','',1,'no','','',1),(50,'dogovor','mcid','Компания','required','yes',2,'yes','--Обычное--','',1),(51,'dogovor','iduser','Куратор','required','yes',3,'yes','','',1),(52,'dogovor','datum_plan','Дата план.','required','yes',4,'yes','datum','',1),(53,'dogovor','period','Период действия',NULL,'',5,'no','','',1),(54,'dogovor','idcategory','Этап','','yes',6,'yes','','',1),(55,'dogovor','dog_num','Договор','','yes',7,'no','','',1),(56,'dogovor','tip','Тип сделки','required','yes',8,'no','--Обычное--','',1),(57,'dogovor','direction','Направление','required','yes',9,'yes','','',1),(58,'dogovor','adres','Адрес','','',10,'no','','',1),(59,'dogovor','money','Деньги',NULL,'yes',11,'yes','','',1),(60,'dogovor','content','Описание','','',12,'no','','',1),(61,'dogovor','pid_list','Персоны','','yes',13,'no','--Обычное--','',1),(62,'dogovor','payer','Плательщик','','yes',14,'yes','--Обычное--','',1),(64,'dogovor','kol','Сумма план.','','yes',NULL,'yes','','',1),(65,'dogovor','kol_fact','Сумма факт.','','yes',NULL,'yes','','',1),(66,'dogovor','marg','Прибыль','','yes',NULL,'yes','','',1),(67,'dogovor','oborot','Сумма','','yes',NULL,'yes','','',1),(68,'price','price_in','Закуп','required','yes',NULL,'','','',1),(69,'price','price_1','Розница','required','yes',NULL,'','','35',1),(70,'price','price_2','Уровень 1','','yes',NULL,'','','25',1),(71,'price','price_3','Уровень 2','required','yes',NULL,'','','20',1),(72,'price','price_4','Уровень 3','','',NULL,'','','15',1),(73,'price','price_5','Уровень 4','','',NULL,'','','10',1),(880,'dogovor','input1','доп.поле',NULL,NULL,19,'','--Обычное--','',1),(881,'dogovor','input2','доп.поле',NULL,NULL,20,'','--Обычное--','',1),(882,'dogovor','input3','доп.поле',NULL,NULL,21,'','--Обычное--','',1),(883,'dogovor','input4','доп.поле',NULL,NULL,22,'','--Обычное--','',1),(884,'dogovor','input5','доп.поле',NULL,NULL,23,'','--Обычное--','',1),(885,'dogovor','input6','доп.поле',NULL,NULL,24,'','--Обычное--','',1),(970,'dogovor','input7','доп.поле',NULL,NULL,25,'','--Обычное--','',1),(971,'dogovor','input8','доп.поле',NULL,NULL,26,'','--Обычное--','',1),(972,'dogovor','input9','доп.поле',NULL,NULL,27,'','--Обычное--','',1),(973,'dogovor','input10','доп.поле',NULL,NULL,28,'','--Обычное--','',1),(1064,'person','input10','доп.поле','','',22,'no','','',1);
/*!40000 ALTER TABLE `salesman_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_file`
--

DROP TABLE IF EXISTS `salesman_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_file` (
  `fid` int NOT NULL AUTO_INCREMENT,
  `ftitle` varchar(255) DEFAULT NULL COMMENT 'оригинальное название файла',
  `fname` varchar(255) DEFAULT NULL COMMENT 'имя, хранимое в системе',
  `ftype` text COMMENT 'тип файла',
  `fver` int DEFAULT NULL COMMENT 'версия',
  `ftag` text COMMENT 'описание файла',
  `iduser` int DEFAULT NULL COMMENT 'Автор _user.iduser',
  `clid` int DEFAULT NULL COMMENT 'Клиент _clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'Контакт _personcat.pid',
  `did` int DEFAULT NULL COMMENT 'Сдекла _dogovor.did',
  `tskid` int DEFAULT NULL COMMENT 'DEPRECATED',
  `coid` int DEFAULT NULL COMMENT 'DEPRECATED',
  `folder` text COMMENT 'Папка _file_cat.idcategory',
  `datum` datetime DEFAULT NULL,
  `size` int DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_file`
--

LOCK TABLES `salesman_file` WRITE;
/*!40000 ALTER TABLE `salesman_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_file_cat`
--

DROP TABLE IF EXISTS `salesman_file_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_file_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `subid` int DEFAULT '0' COMMENT 'родительская папка idcategory',
  `title` varchar(250) DEFAULT NULL COMMENT 'название категории',
  `shared` varchar(3) NOT NULL DEFAULT 'no' COMMENT 'общая папка (yes)',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_file_cat`
--

LOCK TABLES `salesman_file_cat` WRITE;
/*!40000 ALTER TABLE `salesman_file_cat` DISABLE KEYS */;
INSERT INTO `salesman_file_cat` VALUES (1,0,'Коммерческие предложения клиентам','',1),(2,10,'Спецификации','',1),(3,0,'Презентации','yes',1),(4,8,'Прочее','',1),(5,0,'Изображения','yes',1),(6,1,'КП','',1),(7,8,'Прайс конкурента','',1),(8,0,'Разное','no',1),(9,0,'Рассылки','no',1),(10,0,'Документы','yes',1);
/*!40000 ALTER TABLE `salesman_file_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_group`
--

DROP TABLE IF EXISTS `salesman_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL COMMENT 'имя группы',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления группы',
  `type` int DEFAULT NULL COMMENT 'DEPRECATED',
  `service` varchar(60) DEFAULT NULL COMMENT 'Связка с сервисом _services.name',
  `idservice` varchar(100) DEFAULT NULL COMMENT 'id группы во внешнем сервисе',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_group`
--

LOCK TABLES `salesman_group` WRITE;
/*!40000 ALTER TABLE `salesman_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_grouplist`
--

DROP TABLE IF EXISTS `salesman_grouplist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_grouplist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gid` int NOT NULL COMMENT 'Группа _group.id',
  `clid` int(10) unsigned zerofill DEFAULT NULL COMMENT 'Клиент _clientcat.clid',
  `pid` int(10) unsigned zerofill DEFAULT NULL COMMENT 'Контакт _personcat.pid',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подписки',
  `person_id` int(10) unsigned zerofill DEFAULT NULL COMMENT 'не используется',
  `service` varchar(255) DEFAULT NULL COMMENT 'Имя сервиса _services.name',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'имя подписчика',
  `user_email` varchar(255) DEFAULT NULL COMMENT 'email подписчика',
  `user_phone` varchar(15) DEFAULT NULL COMMENT 'телефон подписчика',
  `tags` text COMMENT 'тэги',
  `status` varchar(100) DEFAULT NULL COMMENT 'статус подписчика',
  `availability` varchar(100) DEFAULT NULL COMMENT 'доступность подписчика',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `gid_clid_identity` (`gid`,`clid`,`identity`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_grouplist`
--

LOCK TABLES `salesman_grouplist` WRITE;
/*!40000 ALTER TABLE `salesman_grouplist` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_grouplist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_history`
--

DROP TABLE IF EXISTS `salesman_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_history` (
  `cid` int NOT NULL AUTO_INCREMENT,
  `clid` int DEFAULT '0',
  `pid` varchar(100) DEFAULT NULL,
  `did` int DEFAULT '0',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datum_izm` datetime DEFAULT NULL,
  `des` text,
  `iduser` int DEFAULT '0',
  `iduser_izm` int DEFAULT '0',
  `tip` varchar(50) DEFAULT NULL,
  `fid` varchar(255) DEFAULT NULL,
  `uid` varchar(100) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`cid`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`),
  KEY `did` (`did`),
  KEY `iduser` (`iduser`),
  KEY `identity` (`identity`),
  KEY `tip` (`tip`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_history`
--

LOCK TABLES `salesman_history` WRITE;
/*!40000 ALTER TABLE `salesman_history` DISABLE KEYS */;
INSERT INTO `salesman_history` VALUES (1,1,NULL,0,'2023-02-04 18:28:10',NULL,'Добавлен клиент',1,0,'СобытиеCRM',NULL,NULL,1),(2,1,NULL,0,'2023-02-04 18:28:30',NULL,'Добавлена сделка',1,0,'СобытиеCRM',NULL,NULL,1),(3,0,NULL,1,'2023-02-04 18:29:16',NULL,'Счет добавлен в платежи',1,0,'СобытиеCRM',NULL,NULL,1);
/*!40000 ALTER TABLE `salesman_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_incoming`
--

DROP TABLE IF EXISTS `salesman_incoming`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_incoming` (
  `p_identity` int NOT NULL,
  `p_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `p_text` text NOT NULL,
  UNIQUE KEY `p_identity` (`p_identity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_incoming`
--

LOCK TABLES `salesman_incoming` WRITE;
/*!40000 ALTER TABLE `salesman_incoming` DISABLE KEYS */;
INSERT INTO `salesman_incoming` VALUES (1,'2017-12-12 12:35:24','{\"Response\":\"Success\",\"Message\":\"Channel status will follow\",\"data\":{\"1\":{\"Event\":\"StatusComplete\",\"Items\":\"0\"}}}');
/*!40000 ALTER TABLE `salesman_incoming` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_incoming_channels`
--

DROP TABLE IF EXISTS `salesman_incoming_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_incoming_channels` (
  `p_identity` int NOT NULL,
  `p_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `p_text` text NOT NULL,
  UNIQUE KEY `p_identity` (`p_identity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_incoming_channels`
--

LOCK TABLES `salesman_incoming_channels` WRITE;
/*!40000 ALTER TABLE `salesman_incoming_channels` DISABLE KEYS */;
INSERT INTO `salesman_incoming_channels` VALUES (1,'2017-11-21 09:11:40','{\"Response\":\"Success\",\"data\":[{\"EventList\":\"start\"},{\"Event\":\"CoreShowChannelsComplete\",\"EventList\":\"Complete\",\"ListItems\":\"0\"}],\"Message\":\"Channels will follow\"}');
/*!40000 ALTER TABLE `salesman_incoming_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_kb`
--

DROP TABLE IF EXISTS `salesman_kb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_kb` (
  `idcat` int NOT NULL AUTO_INCREMENT,
  `subid` int DEFAULT NULL COMMENT 'ссылка на головную папку',
  `title` varchar(255) DEFAULT NULL COMMENT 'название папки',
  `share` varchar(5) DEFAULT NULL COMMENT 'DEPRECATED',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_kb`
--

LOCK TABLES `salesman_kb` WRITE;
/*!40000 ALTER TABLE `salesman_kb` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_kb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_kbtags`
--

DROP TABLE IF EXISTS `salesman_kbtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_kbtags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT 'тэг',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_kbtags`
--

LOCK TABLES `salesman_kbtags` WRITE;
/*!40000 ALTER TABLE `salesman_kbtags` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_kbtags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_knowledgebase`
--

DROP TABLE IF EXISTS `salesman_knowledgebase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_knowledgebase` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcat` int DEFAULT NULL COMMENT 'Папка _kb.idcat',
  `datum` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата публикации',
  `title` varchar(255) DEFAULT NULL COMMENT 'название статьи',
  `content` mediumtext COMMENT 'содержание статьи',
  `count` int DEFAULT NULL COMMENT 'число просмотров',
  `active` varchar(5) DEFAULT NULL COMMENT 'признак черновика',
  `pin` varchar(5) DEFAULT 'no' COMMENT 'Закрепление статьи',
  `pindate` datetime DEFAULT NULL COMMENT 'Дата закрепления статьи',
  `keywords` text COMMENT 'тэги',
  `author` int DEFAULT NULL COMMENT 'Автор _user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_knowledgebase`
--

LOCK TABLES `salesman_knowledgebase` WRITE;
/*!40000 ALTER TABLE `salesman_knowledgebase` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_knowledgebase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_kpi`
--

DROP TABLE IF EXISTS `salesman_kpi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_kpi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kpi` int DEFAULT NULL COMMENT 'ID показателя',
  `year` int DEFAULT NULL COMMENT 'Год',
  `period` varchar(10) DEFAULT NULL COMMENT 'Период расчета',
  `iduser` int DEFAULT NULL COMMENT 'ID сотрудника (iduser)',
  `val` int DEFAULT NULL COMMENT 'Значение показателя',
  `isPersonal` tinyint(1) DEFAULT '0' COMMENT 'Признок персонального показателя',
  `identity` int DEFAULT NULL COMMENT 'ID аккаунта',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='База KPI сотрудников';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_kpi`
--

LOCK TABLES `salesman_kpi` WRITE;
/*!40000 ALTER TABLE `salesman_kpi` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_kpi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_kpibase`
--

DROP TABLE IF EXISTS `salesman_kpibase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_kpibase` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'Название показателя',
  `tip` varchar(20) DEFAULT NULL COMMENT 'Тип показателя',
  `values` text COMMENT 'Список значений показателя для расчетов',
  `subvalues` text COMMENT 'Список дополнительных значений',
  `identity` int DEFAULT NULL COMMENT 'ID аккаунта',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Базовые показатели KPI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_kpibase`
--

LOCK TABLES `salesman_kpibase` WRITE;
/*!40000 ALTER TABLE `salesman_kpibase` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_kpibase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_kpiseason`
--

DROP TABLE IF EXISTS `salesman_kpiseason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_kpiseason` (
  `id` int NOT NULL AUTO_INCREMENT,
  `year` int DEFAULT NULL,
  `rate` mediumtext COMMENT 'значения сезонного коэффициента в json',
  `kpi` text COMMENT 'id показателя',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Сезонные коэффициенты для показателей KPI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_kpiseason`
--

LOCK TABLES `salesman_kpiseason` WRITE;
/*!40000 ALTER TABLE `salesman_kpiseason` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_kpiseason` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_leads`
--

DROP TABLE IF EXISTS `salesman_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datum_do` datetime DEFAULT NULL COMMENT 'дата обработки',
  `status` int DEFAULT NULL COMMENT 'статус 0 => Открыт, 1 => В работе, 2 => Обработан, 3 => Закрыт',
  `rezult` int DEFAULT NULL COMMENT 'результат обработки 1 => Спам, 2 => Дубль, 3 => Другое, 4 => Не целевой',
  `title` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `description` text COMMENT 'описание заявки',
  `ip` varchar(16) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `timezone` varchar(5) DEFAULT NULL,
  `iduser` int DEFAULT '0' COMMENT '_user.iduser',
  `clientpath` int DEFAULT NULL COMMENT '_clientpath.id',
  `pid` int DEFAULT NULL COMMENT '_personcat.pid',
  `clid` int DEFAULT NULL COMMENT '_clientcat.clid',
  `did` int DEFAULT NULL COMMENT '_dogovor.did',
  `partner` int DEFAULT NULL COMMENT '_clientcat.clid',
  `muid` varchar(255) DEFAULT NULL,
  `rezz` text COMMENT 'комментарий при дисквалификации заявки',
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_referrer` varchar(255) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_leads`
--

LOCK TABLES `salesman_leads` WRITE;
/*!40000 ALTER TABLE `salesman_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_leads_utm`
--

DROP TABLE IF EXISTS `salesman_leads_utm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_leads_utm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clientpath` int NOT NULL DEFAULT '0' COMMENT 'id Источника из _clientpath',
  `utm_source` varchar(255) DEFAULT NULL COMMENT 'Название источника',
  `utm_url` varchar(500) DEFAULT NULL COMMENT 'Адрес целевой страницы',
  `utm_medium` varchar(255) DEFAULT NULL COMMENT 'Канал кампании',
  `utm_campaign` varchar(255) DEFAULT NULL COMMENT 'Название кампании',
  `utm_term` varchar(255) DEFAULT NULL COMMENT 'Ключевые слова, фраза',
  `utm_content` varchar(255) DEFAULT NULL COMMENT 'Доп.описание кампании',
  `site` varchar(255) DEFAULT NULL COMMENT 'Адрес сайта',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_leads_utm`
--

LOCK TABLES `salesman_leads_utm` WRITE;
/*!40000 ALTER TABLE `salesman_leads_utm` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_leads_utm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_logapi`
--

DROP TABLE IF EXISTS `salesman_logapi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_logapi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content` mediumtext NOT NULL,
  `rez` text NOT NULL,
  `ip` varchar(20) NOT NULL,
  `remoteaddr` text NOT NULL,
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_logapi`
--

LOCK TABLES `salesman_logapi` WRITE;
/*!40000 ALTER TABLE `salesman_logapi` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_logapi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_logs`
--

DROP TABLE IF EXISTS `salesman_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(100) NOT NULL,
  `iduser` int NOT NULL COMMENT 'id пользователя user.iduser',
  `content` text NOT NULL,
  `identity` int NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_logs`
--

LOCK TABLES `salesman_logs` WRITE;
/*!40000 ALTER TABLE `salesman_logs` DISABLE KEYS */;
INSERT INTO `salesman_logs` VALUES (1,'2022-04-20 05:37:38','Авторизация',1,'Пользователь авторизовался в системе',1),(2,'2022-04-20 05:37:40','Начало дня',1,'Первый запуск за день',1),(3,'2022-04-20 05:58:06','Администрирование',1,'Пользователь вошел в панель администратора',1),(4,'2022-08-31 12:12:29','Авторизация',1,'Пользователь авторизовался в системе',1),(5,'2022-08-31 12:12:29','Начало дня',1,'Первый запуск за день',1),(6,'2022-08-31 12:12:59','Администрирование',1,'Пользователь вошел в панель администратора',1),(7,'2023-01-16 05:15:30','Авторизация',1,'Пользователь авторизовался в системе',1),(8,'2023-01-16 05:15:30','Начало дня',1,'Первый запуск за день',1),(9,'2023-01-16 05:15:38','Администрирование',1,'Пользователь вошел в панель администратора',1),(10,'2023-01-16 05:18:14','Авторизация',1,'Пользователь авторизовался в системе',1),(11,'2023-01-16 05:18:18','Администрирование',1,'Пользователь вошел в панель администратора',1),(12,'2023-01-16 05:24:08','Администрирование',1,'Пользователь вошел в панель администратора',1),(13,'2023-01-16 05:33:34','Администрирование',1,'Пользователь вошел в панель администратора',1),(14,'2023-01-16 05:33:43','Администрирование',1,'Пользователь вошел в панель администратора',1),(15,'2023-02-04 15:45:10','Авторизация',1,'Пользователь авторизовался в системе',1),(16,'2023-02-04 15:45:10','Начало дня',1,'Первый запуск за день',1),(17,'2023-02-04 16:44:06','Администрирование',1,'Пользователь вошел в панель администратора',1),(18,'2023-02-04 17:15:21','Администрирование',1,'Пользователь вошел в панель администратора',1),(19,'2023-02-04 17:16:06','Администрирование',1,'Пользователь вошел в панель администратора',1);
/*!40000 ALTER TABLE `salesman_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_loyal_cat`
--

DROP TABLE IF EXISTS `salesman_loyal_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_loyal_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'название',
  `color` varchar(7) NOT NULL DEFAULT '#CCCCCC' COMMENT 'цвет',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'признак дефолтности',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_loyal_cat`
--

LOCK TABLES `salesman_loyal_cat` WRITE;
/*!40000 ALTER TABLE `salesman_loyal_cat` DISABLE KEYS */;
INSERT INTO `salesman_loyal_cat` VALUES (2,'0 - Не лояльный','#333333','',1),(3,'4 - Очень Лояльный','#ff0000','',1),(4,'2 - Нейтральный','#99ccff','',1),(1,'3 - Лояльный','#ff00ff','',1),(5,'1 - Не понятно','#CCCCCC','yes',1),(6,'5 - ВиП','#cedb9c','',1);
/*!40000 ALTER TABLE `salesman_loyal_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_mail`
--

DROP TABLE IF EXISTS `salesman_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_mail` (
  `mid` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата рассылки',
  `title` text COMMENT 'название',
  `descr` text COMMENT 'описание',
  `theme` varchar(255) DEFAULT NULL COMMENT 'тема сообщения',
  `tip` varchar(20) DEFAULT NULL COMMENT 'тип рассылки (от пользователя или от компании)',
  `iduser` int DEFAULT NULL COMMENT 'автор user.iduser',
  `tpl_id` int DEFAULT NULL COMMENT 'храним ид шаблона mail_tpl.tpl_id',
  `client_list` text COMMENT 'список clientcat.clid, разделенный ;',
  `person_list` text COMMENT 'список personcat.pid, разделенный ;',
  `file` text COMMENT 'file.fid - прикрепленные файлы с разделением ;',
  `do` varchar(5) DEFAULT NULL COMMENT 'признак проведения рассылки',
  `template` mediumtext NOT NULL COMMENT 'текст сообщения',
  `clist_do` text NOT NULL COMMENT 'список clientcat.clid, разделенный ; которым отправлено сообщение',
  `plist_do` text NOT NULL COMMENT 'список personcat.pid, разделенный ; которым отправлено сообщение',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_mail`
--

LOCK TABLES `salesman_mail` WRITE;
/*!40000 ALTER TABLE `salesman_mail` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_mail_tpl`
--

DROP TABLE IF EXISTS `salesman_mail_tpl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_mail_tpl` (
  `tpl_id` int NOT NULL AUTO_INCREMENT,
  `name_tpl` varchar(250) DEFAULT NULL COMMENT 'имя шаблона',
  `content_tpl` mediumtext COMMENT 'содержание шаблона',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tpl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_mail_tpl`
--

LOCK TABLES `salesman_mail_tpl` WRITE;
/*!40000 ALTER TABLE `salesman_mail_tpl` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_mail_tpl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog`
--

DROP TABLE IF EXISTS `salesman_modcatalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prid` int NOT NULL COMMENT 'price.n_id',
  `idz` int DEFAULT NULL COMMENT 'modcatalog_zayavka.id',
  `content` mediumtext COMMENT 'описание позиции',
  `datum` datetime NOT NULL COMMENT 'дата',
  `price_plus` double DEFAULT NULL,
  `status` int DEFAULT '0' COMMENT 'статус (в наличии и тд.)',
  `kol` double DEFAULT '0' COMMENT 'количество',
  `files` text COMMENT 'прикрепленные файлы в формате json',
  `sklad` int NOT NULL COMMENT 'modcatalog_sklad.id',
  `iduser` int NOT NULL COMMENT 'user.iduser',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog`
--

LOCK TABLES `salesman_modcatalog` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_akt`
--

DROP TABLE IF EXISTS `salesman_modcatalog_akt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_akt` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int DEFAULT '0',
  `tip` varchar(100) DEFAULT NULL,
  `number` int DEFAULT '0',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clid` int DEFAULT '0',
  `posid` int DEFAULT '0',
  `man1` varchar(255) DEFAULT NULL,
  `man2` varchar(255) DEFAULT NULL,
  `isdo` varchar(5) DEFAULT NULL,
  `cFactura` varchar(20) DEFAULT NULL,
  `cDate` date DEFAULT NULL,
  `sklad` int DEFAULT '0',
  `idz` int DEFAULT '0',
  `identity` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_akt`
--

LOCK TABLES `salesman_modcatalog_akt` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_akt` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_akt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_aktpoz`
--

DROP TABLE IF EXISTS `salesman_modcatalog_aktpoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_aktpoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ida` int NOT NULL COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `price_in` double NOT NULL DEFAULT '0' COMMENT 'цена по ордеру',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'количество по приходному-расходному ордеру',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_aktpoz`
--

LOCK TABLES `salesman_modcatalog_aktpoz` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_aktpoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_aktpoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_dop`
--

DROP TABLE IF EXISTS `salesman_modcatalog_dop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_dop` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prid` int NOT NULL COMMENT 'price.n_id',
  `bid` int NOT NULL COMMENT 'по-моему не используется',
  `datum` date NOT NULL,
  `content` text NOT NULL COMMENT 'наименование доп. затрат',
  `summa` double NOT NULL COMMENT 'стоимость доп. затрат',
  `clid` int NOT NULL COMMENT 'clientcat.clid',
  `iduser` int NOT NULL COMMENT 'user.iduser',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_dop`
--

LOCK TABLES `salesman_modcatalog_dop` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_dop` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_dop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_field`
--

DROP TABLE IF EXISTS `salesman_modcatalog_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pfid` int NOT NULL COMMENT 'modcatalog_fieldcat.id',
  `n_id` int NOT NULL COMMENT 'price.n_id',
  `value` varchar(255) NOT NULL COMMENT 'значение доп поля для данной продукции',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_field`
--

LOCK TABLES `salesman_modcatalog_field` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_fieldcat`
--

DROP TABLE IF EXISTS `salesman_modcatalog_fieldcat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_fieldcat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'название доп поля',
  `tip` varchar(10) NOT NULL COMMENT 'тип вывода: поле ввода, поле текста, список выбора, чекбоксы, радиокнопки и разделитель',
  `value` text NOT NULL COMMENT 'выбираемы значения',
  `ord` int NOT NULL COMMENT 'порядковый номер поля в списке доп. полей',
  `pole` varchar(10) NOT NULL,
  `pwidth` int NOT NULL DEFAULT '50' COMMENT 'ширина поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_fieldcat`
--

LOCK TABLES `salesman_modcatalog_fieldcat` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_fieldcat` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_fieldcat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_log`
--

DROP TABLE IF EXISTS `salesman_modcatalog_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dopzid` int NOT NULL COMMENT 'modcatalog_dop.id',
  `datum` datetime NOT NULL COMMENT 'дата изменения',
  `tip` varchar(255) NOT NULL COMMENT 'где происходит измениение: catalog, dop, kol, price, status',
  `new` text NOT NULL COMMENT 'было ',
  `old` text NOT NULL COMMENT 'стало',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `iduser` int NOT NULL COMMENT 'id пользователя user.iduser',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_log`
--

LOCK TABLES `salesman_modcatalog_log` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_offer`
--

DROP TABLE IF EXISTS `salesman_modcatalog_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_offer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` datetime NOT NULL COMMENT 'дата предложения',
  `datum_end` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT 'статус 0-актуальная, 1-закрытая',
  `iduser` int NOT NULL COMMENT 'user.iduser',
  `content` text NOT NULL COMMENT 'коментарий предложения',
  `des` text NOT NULL COMMENT 'данные по НДС, названию предложения, сумме',
  `users` text NOT NULL COMMENT 'user.iduser с разделением ; принявшие предложение снабжения (голосование)',
  `prid` int NOT NULL COMMENT 'id созданной позиции (price.n_id)',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_offer`
--

LOCK TABLES `salesman_modcatalog_offer` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_offer` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_offer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_reserv`
--

DROP TABLE IF EXISTS `salesman_modcatalog_reserv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_reserv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `did` int NOT NULL COMMENT 'dogovor.did',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата резерва',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'кол-во резерва',
  `status` varchar(30) NOT NULL COMMENT 'статус резерва (действует-снят)',
  `idz` int NOT NULL DEFAULT '0' COMMENT 'id заявки, по которой ставили резерв (modcatalog_zayavka.id)',
  `ida` int NOT NULL DEFAULT '0' COMMENT 'id акта в таблице modcatalog_akt (modcatalog_akt.id)',
  `sklad` int NOT NULL DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_reserv`
--

LOCK TABLES `salesman_modcatalog_reserv` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_reserv` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_reserv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_set`
--

DROP TABLE IF EXISTS `salesman_modcatalog_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_set` (
  `id` int NOT NULL AUTO_INCREMENT,
  `settings` text NOT NULL COMMENT 'настройки',
  `ftp` text NOT NULL COMMENT 'настройки ftp',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_set`
--

LOCK TABLES `salesman_modcatalog_set` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_set` DISABLE KEYS */;
INSERT INTO `salesman_modcatalog_set` VALUES (1,'{\"mcArtikul\":\"yes\",\"mcStep\":\"6\",\"mcStepPers\":\"80\",\"mcKolEdit\":null,\"mcStatusEdit\":null,\"mcUseOrder\":\"yes\",\"mcCoordinator\":[\"1\",\"20\",\"22\",\"14\",\"13\",\"18\"],\"mcSpecialist\":[\"1\",\"23\",\"22\",\"3\"],\"mcAutoRezerv\":\"yes\",\"mcAutoWork\":\"yes\",\"mcAutoStatus\":null,\"mcSklad\":\"yes\",\"mcSkladPoz\":null,\"mcAutoProvider\":\"yes\",\"mcAutoPricein\":\"yes\",\"mcDBoardSkladName\":\"Наличие\",\"mcDBoardSklad\":\"yes\",\"mcDBoardZayavkaName\":\"Заявки\",\"mcDBoardZayavka\":\"yes\",\"mcDBoardOfferName\":\"Предложения\",\"mcDBoardOffer\":\"yes\",\"mcMenuTip\":\"inMain\",\"mcMenuPlace\":\"\",\"mcOfferName1\":\"\",\"mcOfferName2\":\"\",\"mcPriceCat\":[\"245\",\"247\",\"246\",\"1\",\"156\",\"154\",\"4\",\"158\",\"153\",\"180\",\"177\",\"176\",\"173\",\"172\",\"171\",\"170\",\"174\",\"175\",\"178\"]}','{\"mcFtpServer\":\"\",\"mcFtpUser\":\"\",\"mcFtpPass\":\"\",\"mcFtpPath\":\"\"}',1);
/*!40000 ALTER TABLE `salesman_modcatalog_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_sklad`
--

DROP TABLE IF EXISTS `salesman_modcatalog_sklad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_sklad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'название склада',
  `mcid` int NOT NULL COMMENT 'привязка к компании (mycomps.id)',
  `isDefault` varchar(5) NOT NULL DEFAULT 'no' COMMENT 'склад по умолчанию для каждой компании',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_sklad`
--

LOCK TABLES `salesman_modcatalog_sklad` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_sklad` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_sklad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_skladmove`
--

DROP TABLE IF EXISTS `salesman_modcatalog_skladmove`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_skladmove` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата перемещения',
  `skladfrom` int DEFAULT '0' COMMENT 'id склада с которого перемещаем',
  `skladto` int DEFAULT '0' COMMENT 'id склада на который перемещаем',
  `iduser` int DEFAULT '0' COMMENT 'id сотрудника, сделавшего перемещение',
  `identity` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_skladmove`
--

LOCK TABLES `salesman_modcatalog_skladmove` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_skladmove` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_skladmove` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_skladmovepoz`
--

DROP TABLE IF EXISTS `salesman_modcatalog_skladmovepoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_skladmovepoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idm` int NOT NULL DEFAULT '0' COMMENT 'id группы перемещения (modcatalog_skladmove.id)',
  `idp` int NOT NULL DEFAULT '0' COMMENT 'id позиции из таблицы modcatalog_skladpoz',
  `prid` int NOT NULL DEFAULT '0' COMMENT 'id позиции прайса (price.n_id)',
  `kol` double(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'количество для общего учета',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_skladmovepoz`
--

LOCK TABLES `salesman_modcatalog_skladmovepoz` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_skladmovepoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_skladmovepoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_skladpoz`
--

DROP TABLE IF EXISTS `salesman_modcatalog_skladpoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_skladpoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prid` int NOT NULL DEFAULT '0' COMMENT 'id товара (price.n_id)',
  `sklad` int NOT NULL DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
  `status` varchar(5) NOT NULL DEFAULT 'out',
  `date_in` date DEFAULT NULL COMMENT 'дата поступления',
  `date_out` date DEFAULT NULL COMMENT 'дата выбытия',
  `serial` varchar(255) DEFAULT NULL COMMENT 'серийный номер',
  `date_create` date DEFAULT NULL COMMENT 'дата производства',
  `date_period` date DEFAULT NULL COMMENT 'дата (например поверки)',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'кол-во',
  `did` int DEFAULT NULL COMMENT 'id сделки, на которую позиция списана (поштучный учет) (dogovor.did)',
  `idorder_in` int DEFAULT '0' COMMENT 'id приходного ордера (modcatalog_akt.id)',
  `idorder_out` int DEFAULT '0' COMMENT 'id расходного ордера (modcatalog_akt.id)',
  `summa` double(20,2) DEFAULT '0.00' COMMENT 'стоимость для расх.ордера',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `prid` (`prid`),
  KEY `sklad` (`sklad`),
  KEY `did` (`did`),
  KEY `identity` (`identity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_skladpoz`
--

LOCK TABLES `salesman_modcatalog_skladpoz` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_skladpoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_skladpoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_zayavka`
--

DROP TABLE IF EXISTS `salesman_modcatalog_zayavka`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_zayavka` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number` varchar(50) NOT NULL DEFAULT '0' COMMENT 'номер заявки',
  `did` int NOT NULL COMMENT 'dogovor.did',
  `datum` datetime NOT NULL COMMENT 'дата заявки',
  `datum_priority` date DEFAULT NULL COMMENT 'желаемая дата (срочность)',
  `datum_start` datetime NOT NULL COMMENT 'дата начало выполнения заявки',
  `datum_end` datetime NOT NULL COMMENT 'дата окончания выполнения заявки',
  `status` int NOT NULL DEFAULT '0' COMMENT '0 - создана, 1-в работе, 2- выполнено, 3-отмена',
  `iduser` int NOT NULL COMMENT 'автор user.iduser',
  `sotrudnik` int NOT NULL COMMENT 'ответственный user.iduser',
  `content` text NOT NULL COMMENT 'коментарий заявки',
  `rezult` text NOT NULL,
  `des` text NOT NULL COMMENT 'заполнение доп полей',
  `isHight` varchar(3) DEFAULT 'no',
  `cInvoice` varchar(20) DEFAULT NULL,
  `cDate` date DEFAULT NULL COMMENT 'Дата счета поставщика',
  `cSumma` double(20,2) DEFAULT '0.00' COMMENT 'сумма счета поставщика',
  `bid` int DEFAULT '0' COMMENT 'Связка с записью в Расходах',
  `providerid` int DEFAULT '0' COMMENT 'id записи в таблице dogprovider',
  `conid` int DEFAULT '0' COMMENT 'id поставщика (clientcat.clid)',
  `sklad` int DEFAULT '0' COMMENT 'id склада (modcatalog_sklad.id)',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_zayavka`
--

LOCK TABLES `salesman_modcatalog_zayavka` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_zayavka` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_zayavka` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modcatalog_zayavkapoz`
--

DROP TABLE IF EXISTS `salesman_modcatalog_zayavkapoz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modcatalog_zayavkapoz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idz` int NOT NULL COMMENT 'id заявки (odcatalog_zayavka.id)',
  `prid` int NOT NULL COMMENT 'price.n_id',
  `kol` double(20,2) DEFAULT '0.00' COMMENT 'кол-во на складе',
  `identity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modcatalog_zayavkapoz`
--

LOCK TABLES `salesman_modcatalog_zayavkapoz` WRITE;
/*!40000 ALTER TABLE `salesman_modcatalog_zayavkapoz` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_modcatalog_zayavkapoz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_modules`
--

DROP TABLE IF EXISTS `salesman_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL COMMENT 'название модуля',
  `content` text COMMENT 'какие сделаны настройки модуля',
  `mpath` varchar(255) DEFAULT NULL,
  `icon` varchar(20) NOT NULL DEFAULT 'icon-publish' COMMENT 'иконка из фонтелло для меню',
  `active` varchar(5) NOT NULL DEFAULT 'on' COMMENT 'включен-отключен',
  `activateDate` varchar(20) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_modules`
--

LOCK TABLES `salesman_modules` WRITE;
/*!40000 ALTER TABLE `salesman_modules` DISABLE KEYS */;
INSERT INTO `salesman_modules` VALUES (1,'Каталог-склад','','modcatalog','icon-archive','off','2020-08-13 12:12:48','',1),(2,'Обращения','{\"enShowButtonLeft\":\"yes\",\"enShowButtonCall\":\"yes\"}','entry','icon-phone-squared','off','2021-10-28 22:44:45','',1);
/*!40000 ALTER TABLE `salesman_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_multisteps`
--

DROP TABLE IF EXISTS `salesman_multisteps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_multisteps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'Название цепочки',
  `direction` int DEFAULT NULL COMMENT 'id from _direction Направление',
  `tip` int DEFAULT NULL COMMENT 'tid from _dogtips Тип сделки',
  `steps` varchar(255) DEFAULT NULL COMMENT 'Набор этапов',
  `isdefault` varchar(5) DEFAULT NULL COMMENT 'id этапа по умолчанию',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_multisteps`
--

LOCK TABLES `salesman_multisteps` WRITE;
/*!40000 ALTER TABLE `salesman_multisteps` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_multisteps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_mycomps`
--

DROP TABLE IF EXISTS `salesman_mycomps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_mycomps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_ur` text COMMENT 'полное наименование',
  `name_shot` text COMMENT 'сокращенное наименование',
  `address_yur` text COMMENT 'юридические адрес',
  `address_post` text COMMENT 'почтовый адрес',
  `dir_name` varchar(255) DEFAULT NULL COMMENT 'в лице руководителя',
  `dir_signature` varchar(255) DEFAULT NULL COMMENT 'подпись руководителя',
  `dir_status` text COMMENT 'должность руководителя',
  `dir_osnovanie` text COMMENT 'действующего на основаии',
  `innkpp` varchar(255) DEFAULT NULL COMMENT 'инн-кпп',
  `okog` varchar(255) DEFAULT NULL COMMENT 'окпо-огрн',
  `stamp` varchar(255) DEFAULT NULL COMMENT 'файл с факсимилией',
  `logo` varchar(255) DEFAULT NULL COMMENT 'файл с логотипом',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_mycomps`
--

LOCK TABLES `salesman_mycomps` WRITE;
/*!40000 ALTER TABLE `salesman_mycomps` DISABLE KEYS */;
INSERT INTO `salesman_mycomps` VALUES (1,'Общество с ограниченной ответственностью ”Брикет Солюшн”','ООО ”Брикет Солюшн”','614007, г. Пермь, ул. Народовольческая, 60','614007, г. Пермь, ул. Народовольческая, 60','Директора Андреева Владислава Германовича','Андреев В.Г.','Директор','Устава','590402247104;590401001',';312590427000020','stamp1675529125.png','logo.png',1);
/*!40000 ALTER TABLE `salesman_mycomps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_mycomps_recv`
--

DROP TABLE IF EXISTS `salesman_mycomps_recv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_mycomps_recv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cid` int DEFAULT '0',
  `title` text COMMENT 'назваине р.с',
  `rs` varchar(50) DEFAULT NULL COMMENT 'р.с',
  `bankr` text COMMENT 'бик, кур. счет и название банка',
  `tip` varchar(6) DEFAULT 'bank' COMMENT 'bank-kassa',
  `ostatok` double(20,2) DEFAULT NULL COMMENT 'остаток средств',
  `bloc` varchar(3) DEFAULT 'no' COMMENT 'заблокирован или нет счет',
  `isDefault` varchar(5) DEFAULT 'no' COMMENT 'использутся по умолчанию или нет',
  `ndsDefault` varchar(5) DEFAULT '0' COMMENT 'размер ндс по умолчанию',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_mycomps_recv`
--

LOCK TABLES `salesman_mycomps_recv` WRITE;
/*!40000 ALTER TABLE `salesman_mycomps_recv` DISABLE KEYS */;
INSERT INTO `salesman_mycomps_recv` VALUES (1,1,'Основной расчетный счет','1234567890000000000000000','045744863;30101810300000000863;Филиал ОАО «УРАЛСИБ» в г. Пермь','bank',0.00,'','yes','20',1),(2,1,'Касса','0',';;','kassa',0.00,'','','0',1);
/*!40000 ALTER TABLE `salesman_mycomps_recv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_mycomps_signer`
--

DROP TABLE IF EXISTS `salesman_mycomps_signer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_mycomps_signer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mcid` int DEFAULT NULL COMMENT 'Привязка к компании',
  `title` varchar(255) DEFAULT NULL COMMENT 'Имя подписанта',
  `status` varchar(255) DEFAULT NULL COMMENT 'Должность',
  `signature` varchar(255) DEFAULT NULL COMMENT 'Подпись',
  `osnovanie` varchar(255) DEFAULT NULL COMMENT 'Действующий на основании',
  `stamp` varchar(255) DEFAULT NULL COMMENT 'Файл факсимилье',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `mcid` (`mcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Дополнительные подписанты для документов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_mycomps_signer`
--

LOCK TABLES `salesman_mycomps_signer` WRITE;
/*!40000 ALTER TABLE `salesman_mycomps_signer` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_mycomps_signer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_notes`
--

DROP TABLE IF EXISTS `salesman_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания заметки',
  `author` int NOT NULL DEFAULT '0' COMMENT 'id пользователя, создавшего заметку',
  `pin` int NOT NULL COMMENT 'признак важности заметки',
  `text` varchar(180) NOT NULL COMMENT 'Текст заметки',
  `identity` int NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='База заметок пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_notes`
--

LOCK TABLES `salesman_notes` WRITE;
/*!40000 ALTER TABLE `salesman_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_notify`
--

DROP TABLE IF EXISTS `salesman_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_notify` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время уведомления',
  `title` varchar(255) DEFAULT NULL COMMENT 'заголовок уведомления',
  `content` text COMMENT 'содержимое уведомления',
  `url` text COMMENT 'ссылка на сущность',
  `tip` varchar(50) DEFAULT NULL COMMENT 'тип связанной записи',
  `uid` int DEFAULT NULL COMMENT 'id связанной записи',
  `status` varchar(2) DEFAULT '0' COMMENT 'Статус прочтения - 0 Не прочитано, 1 Прочитано',
  `autor` int DEFAULT NULL COMMENT 'автор события',
  `iduser` int DEFAULT NULL COMMENT 'цель - сотрудник',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COMMENT='База уведомлений';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_notify`
--

LOCK TABLES `salesman_notify` WRITE;
/*!40000 ALTER TABLE `salesman_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_office_cat`
--

DROP TABLE IF EXISTS `salesman_office_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_office_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'адрес офиса',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_office_cat`
--

LOCK TABLES `salesman_office_cat` WRITE;
/*!40000 ALTER TABLE `salesman_office_cat` DISABLE KEYS */;
INSERT INTO `salesman_office_cat` VALUES (1,' г. Пермь, ул. Ленина, 60 оф. 100',1);
/*!40000 ALTER TABLE `salesman_office_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_otdel_cat`
--

DROP TABLE IF EXISTS `salesman_otdel_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_otdel_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(30) NOT NULL COMMENT 'идентификатор для внешних систем',
  `title` varchar(250) DEFAULT NULL COMMENT 'название отдела',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_otdel_cat`
--

LOCK TABLES `salesman_otdel_cat` WRITE;
/*!40000 ALTER TABLE `salesman_otdel_cat` DISABLE KEYS */;
INSERT INTO `salesman_otdel_cat` VALUES (1,'OAP','Отдел активных продаж',1),(2,'OPP','Отдел пассивных продаж',1);
/*!40000 ALTER TABLE `salesman_otdel_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_personcat`
--

DROP TABLE IF EXISTS `salesman_personcat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_personcat` (
  `pid` int NOT NULL AUTO_INCREMENT,
  `clid` int DEFAULT '0',
  `ptitle` varchar(250) DEFAULT NULL,
  `person` varchar(250) DEFAULT NULL,
  `tel` varchar(250) DEFAULT NULL,
  `fax` varchar(250) DEFAULT NULL,
  `mob` varchar(250) DEFAULT NULL,
  `mail` varchar(250) DEFAULT NULL,
  `rol` text,
  `social` text,
  `iduser` varchar(12) DEFAULT '0',
  `clientpath` int DEFAULT '0',
  `loyalty` int DEFAULT '0',
  `input1` varchar(255) DEFAULT NULL,
  `input2` varchar(255) DEFAULT NULL,
  `input3` varchar(255) DEFAULT NULL,
  `input4` varchar(255) DEFAULT NULL,
  `input5` varchar(255) DEFAULT NULL,
  `input6` varchar(255) DEFAULT NULL,
  `input7` varchar(255) DEFAULT NULL,
  `input8` varchar(255) DEFAULT NULL,
  `input9` varchar(255) DEFAULT NULL,
  `input10` varchar(512) DEFAULT NULL,
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_edit` timestamp NULL DEFAULT NULL,
  `creator` int DEFAULT '0',
  `editor` int DEFAULT '0',
  `uid` int DEFAULT '0',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`pid`),
  KEY `person` (`person`),
  KEY `tel` (`tel`),
  KEY `mob` (`mob`),
  KEY `fax` (`fax`),
  KEY `mail` (`mail`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_personcat`
--

LOCK TABLES `salesman_personcat` WRITE;
/*!40000 ALTER TABLE `salesman_personcat` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_personcat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_plan`
--

DROP TABLE IF EXISTS `salesman_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_plan` (
  `plid` int NOT NULL AUTO_INCREMENT,
  `year` int DEFAULT NULL COMMENT 'год',
  `mon` int DEFAULT NULL COMMENT 'месяц',
  `iduser` int DEFAULT NULL COMMENT 'план для кокого сотрудника user.iduser',
  `kol_plan` text COMMENT 'план',
  `marga` text COMMENT 'прибыль',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`plid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_plan`
--

LOCK TABLES `salesman_plan` WRITE;
/*!40000 ALTER TABLE `salesman_plan` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_plugins`
--

DROP TABLE IF EXISTS `salesman_plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_plugins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подключения',
  `name` varchar(50) NOT NULL DEFAULT '0' COMMENT 'название ',
  `version` varchar(10) DEFAULT NULL COMMENT 'Установленная версия плагина',
  `active` varchar(5) NOT NULL DEFAULT 'off' COMMENT 'статус активности - on-off',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_plugins`
--

LOCK TABLES `salesman_plugins` WRITE;
/*!40000 ALTER TABLE `salesman_plugins` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_price`
--

DROP TABLE IF EXISTS `salesman_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_price` (
  `n_id` int NOT NULL AUTO_INCREMENT,
  `artikul` varchar(255) DEFAULT NULL COMMENT 'артикул',
  `title` varchar(255) DEFAULT NULL COMMENT 'название позиции',
  `descr` text COMMENT 'описание',
  `edizm` varchar(10) DEFAULT NULL,
  `price_in` double(20,2) NOT NULL DEFAULT '0.00',
  `price_1` double(20,2) NOT NULL DEFAULT '0.00',
  `price_2` double(20,2) DEFAULT '0.00',
  `price_3` double(20,2) DEFAULT '0.00',
  `price_4` double(20,2) DEFAULT '0.00',
  `price_5` double(20,2) DEFAULT NULL,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pr_cat` int NOT NULL COMMENT 'категория price_cat.idcategory',
  `nds` double(20,2) NOT NULL DEFAULT '0.00' COMMENT 'ндс',
  `archive` varchar(3) NOT NULL DEFAULT 'no',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`n_id`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_price`
--

LOCK TABLES `salesman_price` WRITE;
/*!40000 ALTER TABLE `salesman_price` DISABLE KEYS */;
INSERT INTO `salesman_price` VALUES (1,'00000','Тест 001','тест','шт',1000.00,1350.00,1250.00,1200.00,0.00,NULL,'2023-02-04 15:45:51',0,0.00,'no',1);
/*!40000 ALTER TABLE `salesman_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_price_cat`
--

DROP TABLE IF EXISTS `salesman_price_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_price_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `sub` int DEFAULT '0' COMMENT 'Головная категория - _price_cat.idcategory',
  `title` varchar(250) DEFAULT NULL COMMENT 'название прайса',
  `type` tinyint(1) DEFAULT NULL COMMENT 'тип: 0 - товар, 1 - услуга, 2 - материал',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_price_cat`
--

LOCK TABLES `salesman_price_cat` WRITE;
/*!40000 ALTER TABLE `salesman_price_cat` DISABLE KEYS */;
INSERT INTO `salesman_price_cat` VALUES (1,0,'Тест',NULL,1);
/*!40000 ALTER TABLE `salesman_price_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_profile`
--

DROP TABLE IF EXISTS `salesman_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_profile` (
  `pfid` int NOT NULL AUTO_INCREMENT,
  `id` int DEFAULT NULL COMMENT 'profile_cat.id',
  `clid` int DEFAULT NULL COMMENT 'clientcat.clid',
  `value` varchar(255) DEFAULT NULL COMMENT 'начение поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`pfid`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_profile`
--

LOCK TABLES `salesman_profile` WRITE;
/*!40000 ALTER TABLE `salesman_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_profile_cat`
--

DROP TABLE IF EXISTS `salesman_profile_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_profile_cat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT 'название поля',
  `tip` varchar(10) DEFAULT NULL COMMENT 'тип вывода поля',
  `value` text COMMENT 'значение поля',
  `ord` int DEFAULT NULL COMMENT 'порядок вывода',
  `pole` varchar(10) DEFAULT NULL COMMENT 'название поля для идентификации',
  `pwidth` int NOT NULL DEFAULT '50' COMMENT 'ширина поля',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_profile_cat`
--

LOCK TABLES `salesman_profile_cat` WRITE;
/*!40000 ALTER TABLE `salesman_profile_cat` DISABLE KEYS */;
INSERT INTO `salesman_profile_cat` VALUES (1,'Количество сотрудников в отделе снабжения','select','1-3;3-5;больше 5',15,'pole1',50,1),(2,'Как часто проводят закупки','select','1 раз в мес.; 2 раза в мес.;больше 2-х раз в мес.',3,'pole2',50,1),(3,'Тендерный отдел','radio','Нет;Есть',13,'pole3',50,1),(4,'Проводят тендеры','radio','Электронные площадки;Самостоятельно;Оба варианта;Не проводят',14,'pole4',50,1),(5,'Примечание','text','',17,'pole5',50,1),(8,'Какие продукты можем предложить?','checkbox','Зап.части;Шины;Диски;Элементы кузова;Внедрение телефонии;Внедрение серверов;1С в облаке;Настройка VPN',4,'pole8',100,1),(9,'Объем закупок в месяц','radio','<100т.р.;100-200 т.р.;200-300 т.р.;300-500 т.р.;>500 т.р.',5,'pole9',50,1),(10,'Тип клиента для нас','radio','Не работаем;Ведем переговоры;С нами не будут работать;Работают только с нами',12,'pole10',100,1),(11,'Что покупают постоянно','checkbox','ГСМ;Автохимия;Зап.части;Диски',8,'pole11',50,1),(12,'Годовой оборот','radio','до 1млн.;свыше 1млн. до 20млн.;свыше 20млн. до 100млн.',11,'pole12',50,1),(19,'Специализация','input','',16,'pole19',100,1),(15,'Возможности по продаже','divider','',1,'pole15',100,1),(16,'Интересы клиента','divider','',7,'pole16',100,1);
/*!40000 ALTER TABLE `salesman_profile_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_relations`
--

DROP TABLE IF EXISTS `salesman_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'название',
  `color` varchar(10) DEFAULT NULL COMMENT 'цвет',
  `isDefault` varchar(6) DEFAULT NULL COMMENT 'признак по умолчанию',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `title_2` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_relations`
--

LOCK TABLES `salesman_relations` WRITE;
/*!40000 ALTER TABLE `salesman_relations` DISABLE KEYS */;
INSERT INTO `salesman_relations` VALUES (1,'0 - Не работаем','#333333','',1),(2,'1 - Холодный клиент','#99ccff','yes',1),(3,'3 - Текущий клиент','#3366ff','',1),(5,'4 - Постоянный клиент','#ff9900','no',1),(4,'2 - Потенциальный клиент','#99ff66','',1),(6,'5 - Перспективный клиент','#ff0033','no',1);
/*!40000 ALTER TABLE `salesman_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_reports`
--

DROP TABLE IF EXISTS `salesman_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_reports` (
  `rid` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL COMMENT 'название отчета',
  `file` varchar(100) DEFAULT NULL COMMENT 'файл отчета',
  `ron` varchar(5) DEFAULT NULL COMMENT 'активность отчета',
  `category` varchar(20) DEFAULT NULL COMMENT 'раздел',
  `roles` text COMMENT 'Роли сотрудников с доступом к отчету',
  `users` varchar(255) DEFAULT NULL COMMENT 'id сотрудников, у которых есть доступ к отчету',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`rid`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_reports`
--

LOCK TABLES `salesman_reports` WRITE;
/*!40000 ALTER TABLE `salesman_reports` DISABLE KEYS */;
INSERT INTO `salesman_reports` VALUES (1,'Активности по сделкам','work.php','yes','Активности',NULL,NULL,1),(2,'Сделки по сотрудникам','effect_total.php','yes','Эффективность',NULL,NULL,1),(5,'Анализ конкурентов','effect_concurent.php','yes','Связи',NULL,NULL,1),(73,'Ent. Эффективность каналов','entClientpathToMoney.php','yes','Эффективность',NULL,NULL,1),(7,'Топ клиентов','top_clients.php','yes','Рейтинг',NULL,NULL,1),(8,'Топ сотрудников','top_managers.php','yes','Рейтинг',NULL,NULL,1),(9,'Активность по клиентам','week.php','yes','Активности',NULL,NULL,1),(10,'Действия по сделкам','newdogs.php','yes','Активности',NULL,NULL,1),(11,'По отделам','effect_otdel.php','yes','Эффективность',NULL,NULL,1),(12,'Сделки по типам','effect_dogovor.php','yes','Эффективность',NULL,NULL,1),(13,'По реализ. сделкам','effect_closed.php','yes','Эффективность',NULL,NULL,1),(14,'Анализ поставщиков','effect_contractor.php','yes','Связи',NULL,NULL,1),(15,'Анализ партнеров','effect_partner.php','yes','Связи',NULL,NULL,1),(16,'Активности. Сводная','pipeline_activities.php','no','Активности',NULL,NULL,1),(19,'Pipeline Продажи Сотрудников','pipelineUsersNew.php','yes','Продажи',NULL,NULL,1),(21,'Эффективность сотрудников','effect.php','yes','Эффективность',NULL,NULL,1),(20,'Pipeline Ожидаемый приход','pipeline_prognoz.php','yes','Продажи',NULL,NULL,1),(22,'Pipeline Продажи по этапам','pipeline_dogs.php','yes','Продажи',NULL,NULL,1),(29,'Здоровье сделок','dogs_health.php','yes','Продажи',NULL,NULL,1),(30,'Здоровье сделок [большой]','dogs_health_big.php','yes','Продажи',NULL,NULL,1),(31,'Выполнение дел','activities_results.php','yes','Активности',NULL,NULL,1),(38,'Воронка по марже','voronka_marg.php','yes','Продажи',NULL,NULL,1),(39,'Здоровье сделок (дни)','dogs_health_big_day.php','yes','Продажи',NULL,NULL,1),(40,'Сделки по направлениям','effect_direction.php','yes','Эффективность',NULL,NULL,1),(74,'Ent. Анализ клиентов по типам отношений','entRelationsToMoney.php','yes','Эффективность',NULL,NULL,1),(44,'Новые клиенты','effect_newclients.php','yes','Активности',NULL,NULL,1),(45,'Сделки. Анализ','dogs_monitor.php','yes','Продажи',NULL,NULL,1),(46,'Сделки. В работе','dogs_inwork.php','yes','Продажи',NULL,NULL,1),(47,'Сделки. Зависшие','dogs_inhold.php','yes','Продажи',NULL,NULL,1),(48,'Сделки. Утвержденные','dogs_approved.php','yes','Продажи',NULL,NULL,1),(49,'Сделки. Отказные','dogs_disapproved.php','yes','Продажи',NULL,NULL,1),(50,'Сделки. Здоровье (все сделки)','dogs_health_all.php','yes','Продажи',NULL,NULL,1),(51,'Контроль сделок (по КТ)','dogs_complect.php','yes','Продажи',NULL,NULL,1),(61,'Ent. ABC анализ клиентов','ent-ABC-clients.php','yes','Продажи',NULL,NULL,1),(62,'Ent. ABC анализ продуктов','ent-ABC-products.php','yes','Продажи',NULL,NULL,1),(63,'Ent. RFM анализ клиентов','ent-RFM-clients.php','yes','Продажи',NULL,NULL,1),(56,'Прогноз по продуктам','dogs_productprognoz.php','yes','Планирование',NULL,NULL,1),(57,'Прогноз по продуктам (большой)','dogs_productprognoz_hor.php','yes','Продажи',NULL,NULL,1),(58,'Выполнение планов','planfact2015.php','yes','Планирование',NULL,NULL,1),(59,'Воронка по активностям','voronka_classic.php','yes','Активности',NULL,NULL,1),(72,'Ent. Сделки в работе. По дням по этапам','ent-dealsPerDayPerStep.php','yes','Продажи',NULL,NULL,1),(64,'Ent. RFM анализ продуктов','ent-RFM-products.php','yes','Продажи',NULL,NULL,1),(65,'Анализ Сборщика заявок (Лидов)','leads2014.php','yes','Активности',NULL,NULL,1),(66,'Анализ звонков (телефония)','call_history.php','yes','Активности',NULL,NULL,1),(67,'Закрытые успешные сделки','dealResultReport.php','yes','Продажи',NULL,NULL,1),(68,'Закрытые сделки по этапам','ent-ClosedDealAnalyseByStep.php','yes','Продажи',NULL,NULL,1),(69,'Выставленные счета по сотрудникам','ent-InvoiceStateByUser.php','yes','Продажи',NULL,NULL,1),(70,'Ent. Супер Воронка продаж','ent-SalesFunnel.php','yes','Продажи',NULL,NULL,1),(71,'Ent. Комплексная воронка','ent-voronkaComplex.php','yes','Продажи',NULL,NULL,1),(75,'Ent. Новые клиенты','ent-newClients.php','yes','Активности',NULL,NULL,1),(76,'Ent. Новые сделки','ent-newDeals.php','yes','Активности',NULL,NULL,1),(77,'Ent. Оплаты по сотрудникам','ent-PaymentsByUser.php','yes','Продажи',NULL,NULL,1),(80,'Ent. Активности по времени','ent-activitiesByTime.php','yes','Активности',NULL,NULL,1),(81,'Активности пользователей по сделкам','ent-ActivitiesByUserByDeals.php','yes','Активности',NULL,NULL,1),(82,'Анализ направлений','entDirectionAnaliseChart.php','yes','Эффективность',NULL,NULL,1),(83,'Эффективность каналов продаж','effect_clientpath.php','yes','Эффективность',NULL,NULL,1),(84,'Выполнение планов по оплатам','ent-planDoByPayment.php','yes','Планирование',NULL,NULL,1),(85,'Рейтинг выполнения плана','raiting_plan.php','yes','Планирование',NULL,NULL,1),(86,'Ent. Антиворонка','ent-antiSalesFunnel.php','yes','Продажи',NULL,NULL,1),(87,'Ent. Сделки в работе. По дням','ent-dealsPerDay.php','yes','Продажи',NULL,NULL,1);
/*!40000 ALTER TABLE `salesman_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_search`
--

DROP TABLE IF EXISTS `salesman_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_search` (
  `seid` int NOT NULL AUTO_INCREMENT,
  `tip` varchar(100) DEFAULT NULL COMMENT 'Привязка к person, client, dog',
  `title` varchar(250) DEFAULT NULL COMMENT 'Название представления',
  `squery` text COMMENT 'Поисковой запрос',
  `sorder` int DEFAULT NULL COMMENT 'Порядок вывода',
  `iduser` int DEFAULT NULL COMMENT 'user.iduser',
  `share` varchar(5) DEFAULT NULL COMMENT 'Общий доступ',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`seid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_search`
--

LOCK TABLES `salesman_search` WRITE;
/*!40000 ALTER TABLE `salesman_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_services`
--

DROP TABLE IF EXISTS `salesman_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'название',
  `folder` varchar(60) NOT NULL COMMENT 'название для системы',
  `tip` varchar(200) NOT NULL COMMENT 'тип sip-mail',
  `user_id` varchar(255) NOT NULL COMMENT 'пользователь',
  `user_key` varchar(255) NOT NULL COMMENT 'ключ пользователя',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_services`
--

LOCK TABLES `salesman_services` WRITE;
/*!40000 ALTER TABLE `salesman_services` DISABLE KEYS */;
INSERT INTO `salesman_services` VALUES (1,'JastClick','jastclick','mail','','',1),(2,'Unisender','unisender','mail','','',1);
/*!40000 ALTER TABLE `salesman_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_settings`
--

DROP TABLE IF EXISTS `salesman_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company` varchar(250) DEFAULT NULL COMMENT 'Название компании. Краткое',
  `company_full` mediumtext COMMENT 'Название компании. Полное',
  `company_site` varchar(250) DEFAULT NULL COMMENT 'Сайт компании',
  `company_mail` varchar(250) DEFAULT NULL COMMENT 'Email компании',
  `company_phone` varchar(255) DEFAULT NULL COMMENT 'Телефон компании',
  `company_fax` varchar(255) DEFAULT NULL COMMENT 'факс',
  `outClientUrl` varchar(255) DEFAULT NULL,
  `outDealUrl` varchar(255) DEFAULT NULL,
  `defaultDealName` varchar(255) DEFAULT NULL,
  `dir_prava` varchar(255) DEFAULT NULL,
  `recv` mediumtext,
  `gkey` varchar(250) DEFAULT NULL,
  `num_client` int DEFAULT '30',
  `num_con` int DEFAULT '30',
  `num_person` int DEFAULT '30',
  `num_dogs` int DEFAULT '30',
  `format_phone` varchar(250) DEFAULT NULL COMMENT 'Формат телефона',
  `format_fax` varchar(250) DEFAULT NULL,
  `format_tel` varchar(250) DEFAULT NULL,
  `format_mob` varchar(250) DEFAULT NULL,
  `format_dogs` varchar(250) DEFAULT NULL,
  `session` varchar(3) NOT NULL,
  `export_lock` varchar(255) DEFAULT NULL,
  `valuta` varchar(10) DEFAULT NULL,
  `ipaccesse` varchar(5) DEFAULT NULL,
  `ipstart` varchar(15) DEFAULT NULL,
  `ipend` varchar(15) DEFAULT NULL,
  `iplist` mediumtext,
  `maxupload` varchar(3) DEFAULT NULL COMMENT 'Максимальный размер файла для загрузки',
  `ipmask` varchar(20) DEFAULT NULL,
  `ext_allow` mediumtext NOT NULL COMMENT 'Разрешенные типы файлов',
  `mailme` varchar(5) DEFAULT NULL,
  `mailout` varchar(10) DEFAULT NULL,
  `other` mediumtext COMMENT 'Прочие настройке в формате json',
  `logo` varchar(100) DEFAULT NULL COMMENT 'Логотип компании',
  `acs_view` varchar(3) DEFAULT 'on',
  `complect_on` varchar(3) DEFAULT 'no',
  `zayavka_on` varchar(3) DEFAULT 'no',
  `contract_format` varchar(255) DEFAULT NULL,
  `contract_num` int DEFAULT NULL,
  `inum` int DEFAULT NULL,
  `iformat` varchar(255) DEFAULT NULL,
  `akt_num` varchar(20) DEFAULT '0',
  `akt_step` int DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `coordinator` int DEFAULT NULL,
  `timezone` varchar(255) DEFAULT 'Asia/Yekaterinburg' COMMENT 'Временная зона',
  `ivc` varchar(255) DEFAULT NULL,
  `dFormat` varchar(255) DEFAULT NULL,
  `dNum` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_settings`
--

LOCK TABLES `salesman_settings` WRITE;
/*!40000 ALTER TABLE `salesman_settings` DISABLE KEYS */;
INSERT INTO `salesman_settings` VALUES (1,'Наша','','http://nasha.ru','info@nasha.ru','+7(342)2067201','','','','{ClientName}','','','',30,30,30,30,'9(999)999-99-99',NULL,'8(342)254-55-77',NULL,'99,999 999 999 999','14','','р.',NULL,'','','','20','','gif,jpg,jpeg,png,txt,doc,docx,xls,xlsx,ppt,pptx,rtf,pdf,7z,tar,zip,rar,gz,exe','yes',NULL,'no;no;no;yes;yes;yes;25;25;no;yes;no;yes;yes;yes;no;Дней;no;no;no;no;no;no;yes;yes;invoicedo;2;14;yes;yes;no;no;no;no;no;yes;yes;no;no;no;akt_full.tpl;invoice_qr.tpl;akt_full.tpl;invoice.tpl;no;no;no;no;no;no;no;no','logo.png',NULL,NULL,NULL,'{cnum}-{MM}{YY}/{YYYY}',0,2,'{cnum}','1',7,'VaSeZvkTfh5HMjJpNnge1W7Bloim0S',NULL,'Europe/Moscow','ifyb8VTNF4hf8kE7QclT9w==','СД{cnum}','1');
/*!40000 ALTER TABLE `salesman_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_sip`
--

DROP TABLE IF EXISTS `salesman_sip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_sip` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` varchar(3) NOT NULL DEFAULT 'no',
  `tip` varchar(20) DEFAULT NULL,
  `sip_host` varchar(255) DEFAULT NULL,
  `sip_port` int DEFAULT NULL,
  `sip_channel` varchar(30) DEFAULT NULL,
  `sip_context` varchar(255) DEFAULT NULL,
  `sip_user` varchar(100) DEFAULT NULL,
  `sip_secret` varchar(200) DEFAULT NULL,
  `sip_numout` varchar(3) DEFAULT NULL,
  `sip_pfchange` varchar(3) DEFAULT NULL,
  `sip_path` varchar(255) DEFAULT NULL,
  `sip_cdr` varchar(255) DEFAULT NULL,
  `sip_secure` varchar(5) DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_sip`
--

LOCK TABLES `salesman_sip` WRITE;
/*!40000 ALTER TABLE `salesman_sip` DISABLE KEYS */;
INSERT INTO `salesman_sip` VALUES (1,'no','','',8089,'SIP','from-internal','','','','','','','',1);
/*!40000 ALTER TABLE `salesman_sip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_smtp`
--

DROP TABLE IF EXISTS `salesman_smtp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_smtp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` varchar(3) NOT NULL DEFAULT 'no',
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int DEFAULT NULL,
  `smtp_auth` varchar(5) DEFAULT NULL,
  `smtp_secure` varchar(5) DEFAULT NULL,
  `smtp_user` varchar(100) DEFAULT NULL,
  `smtp_pass` varchar(200) DEFAULT NULL,
  `smtp_from` varchar(255) DEFAULT NULL,
  `smtp_protocol` varchar(5) DEFAULT NULL,
  `tip` varchar(10) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser',
  `divider` varchar(3) NOT NULL DEFAULT ':',
  `filter` varchar(255) NOT NULL DEFAULT 'заявка',
  `deletemess` varchar(5) NOT NULL DEFAULT 'false',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_smtp`
--

LOCK TABLES `salesman_smtp` WRITE;
/*!40000 ALTER TABLE `salesman_smtp` DISABLE KEYS */;
INSERT INTO `salesman_smtp` VALUES (1,'no','smtp.yandex.ru',587,'true','tls','','','','','send','',0,':','','false',1);
/*!40000 ALTER TABLE `salesman_smtp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_speca`
--

DROP TABLE IF EXISTS `salesman_speca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_speca` (
  `spid` int NOT NULL AUTO_INCREMENT,
  `prid` int DEFAULT '0',
  `did` int DEFAULT '0',
  `artikul` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `tip` int DEFAULT '0',
  `price` double(20,2) DEFAULT '0.00',
  `price_in` double(20,2) DEFAULT '0.00',
  `kol` double(20,2) DEFAULT '0.00',
  `edizm` varchar(10) DEFAULT NULL,
  `datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nds` float(20,2) DEFAULT NULL,
  `dop` int DEFAULT '1',
  `comments` varchar(250) DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`spid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_speca`
--

LOCK TABLES `salesman_speca` WRITE;
/*!40000 ALTER TABLE `salesman_speca` DISABLE KEYS */;
INSERT INTO `salesman_speca` VALUES (1,1,1,'00000','Тест 001',0,1350.00,1000.00,1.00,'шт','2023-02-04 16:29:05',0.00,1,' ',1);
/*!40000 ALTER TABLE `salesman_speca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_steplog`
--

DROP TABLE IF EXISTS `salesman_steplog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_steplog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step` int DEFAULT NULL COMMENT 'id этапа dogcategory.idcategory',
  `did` int DEFAULT NULL COMMENT 'id сделки dogovor.did',
  `iduser` int DEFAULT NULL COMMENT 'id пользователя user.iduser внес изменение',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `step` (`step`),
  KEY `did` (`did`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_steplog`
--

LOCK TABLES `salesman_steplog` WRITE;
/*!40000 ALTER TABLE `salesman_steplog` DISABLE KEYS */;
INSERT INTO `salesman_steplog` VALUES (1,'2023-02-04 16:28:30',10,1,1,1);
/*!40000 ALTER TABLE `salesman_steplog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_tasks`
--

DROP TABLE IF EXISTS `salesman_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_tasks` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `maintid` int DEFAULT '0',
  `iduser` int DEFAULT '0',
  `clid` int DEFAULT '0',
  `pid` varchar(255) DEFAULT NULL COMMENT 'personcat.pid (может быть несколько с разделением ;)',
  `did` int DEFAULT '0',
  `cid` int DEFAULT '0',
  `datum` date DEFAULT NULL,
  `totime` time DEFAULT '09:00:00',
  `title` varchar(250) DEFAULT NULL,
  `des` text,
  `tip` varchar(100) DEFAULT 'Звонок',
  `active` varchar(255) DEFAULT 'yes',
  `autor` int DEFAULT '0',
  `priority` int DEFAULT '0',
  `speed` int DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `readonly` varchar(3) DEFAULT 'no',
  `alert` varchar(3) DEFAULT 'yes',
  `day` varchar(3) DEFAULT NULL,
  `status` int DEFAULT '0',
  `alertTime` int DEFAULT '0',
  `identity` varchar(30) DEFAULT '1',
  PRIMARY KEY (`tid`),
  KEY `iduser` (`iduser`),
  KEY `tip` (`tip`),
  KEY `clid` (`clid`),
  KEY `did` (`did`),
  KEY `identity` (`identity`),
  KEY `autor` (`autor`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_tasks`
--

LOCK TABLES `salesman_tasks` WRITE;
/*!40000 ALTER TABLE `salesman_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_territory_cat`
--

DROP TABLE IF EXISTS `salesman_territory_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_territory_cat` (
  `idcategory` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT 'наименование',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idcategory`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_territory_cat`
--

LOCK TABLES `salesman_territory_cat` WRITE;
/*!40000 ALTER TABLE `salesman_territory_cat` DISABLE KEYS */;
INSERT INTO `salesman_territory_cat` VALUES (1,'Пермь',1),(3,'Тюмень',1),(4,'Челябинск',1),(5,'Москва',1);
/*!40000 ALTER TABLE `salesman_territory_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_tpl`
--

DROP TABLE IF EXISTS `salesman_tpl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_tpl` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `tip` varchar(20) DEFAULT NULL COMMENT 'тип',
  `name` varchar(255) DEFAULT NULL COMMENT 'название',
  `content` mediumtext COMMENT 'сообщение',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_tpl`
--

LOCK TABLES `salesman_tpl` WRITE;
/*!40000 ALTER TABLE `salesman_tpl` DISABLE KEYS */;
INSERT INTO `salesman_tpl` VALUES (1,'new_client','Новая организация','Создана новая Организация - <strong>{link}</strong>',1),(2,'new_person','Новая персона','Создана новая персона - {link}',1),(3,'new_dog','Новая сделка','Я создал сделку&nbsp;{link}',1),(4,'edit_dog','Изменение в сделке','Я изменил статус сделки&nbsp;{link}',1),(5,'close_dog','Закрытие сделки','Я закрыл сделку -&nbsp;{link}',1),(6,'send_client','Вам назначена организация','Вы назначены ответственным за Организацию - {link}',1),(7,'send_person','Вам назначена персона','Вы назначены ответственным за Персону - {link}',1),(8,'trash_client','Изменение Ответственного','Ваша Организация перемещена в корзину - {link}',1),(9,'lead_add','Новый интерес','Новый входящий интерес - {link}',1),(10,'lead_setuser','Назначенный интерес','Вы назначены Ответственным за обработку входящего интереса - {link}',1),(11,'lead_do','Обработанный интерес','Я обработал интерес - {link}',1),(12,'leadClientNotifyTemp','Уведомление','&lt;div style=&quot;width:98%; max-width:600px; margin: 0 auto&quot;&gt;\r\n&lt;div class=&quot;blok&quot; style=&quot;font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;\r\n&lt;div style=&quot;color:black; font-size:14px; margin-top: 5px;&quot;&gt;&lt;strong&gt;Уважаемый {castomerName}!&lt;/strong&gt;&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nБлагодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником &lt;strong&gt;{UserName}&lt;/strong&gt;.&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nКонтакты сотрудника:&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;ul&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Телефон:&lt;strong&gt; {UserPhone}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Мобильный:&lt;strong&gt; {UserMob}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Почта: &lt;strong&gt;{UserEmail}&lt;/strong&gt;&lt;/li&gt;\r\n&lt;/ul&gt;\r\n&lt;br /&gt;\r\nВ ближайшее время мы с вами свяжемся по указанному телефону или email.&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;hr /&gt;&lt;br /&gt;\r\nС уважением, {compName}&lt;/div&gt;\r\n&lt;/div&gt;\r\n\r\n&lt;div align=&quot;right&quot; style=&quot;font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;Обработано в SalesMan CRM&lt;/div&gt;\r\n&lt;/div&gt;\r\n',1),(13,'leadSendWellcomeTemp','Уведомление','&lt;div style=&quot;width:98%; max-width:600px; margin: 0 auto&quot;&gt;\r\n&lt;div class=&quot;blok&quot; style=&quot;font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;\r\n&lt;div style=&quot;color:black; font-size:14px; margin-top: 5px;&quot;&gt;&lt;strong&gt;Уважаемый {castomerName}!&lt;/strong&gt;&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nБлагодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником &lt;strong&gt;{UserName}&lt;/strong&gt;.&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;br /&gt;\r\nКонтакты сотрудника:&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;ul&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Телефон:&lt;strong&gt; {UserPhone}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Мобильный:&lt;strong&gt; {UserMob}&lt;/strong&gt;&lt;/li&gt;\r\n	&lt;li style=&quot;color: black; font-size: 12px; margin-top: 5px;&quot;&gt;Почта: &lt;strong&gt;{UserEmail}&lt;/strong&gt;&lt;/li&gt;\r\n&lt;/ul&gt;\r\n&lt;br /&gt;\r\nВ ближайшее время мы с вами свяжемся по указанному телефону или email.&lt;br /&gt;\r\n&amp;nbsp;\r\n&lt;hr /&gt;&lt;br /&gt;\r\nС уважением, {compName}&lt;/div&gt;\r\n&lt;/div&gt;\r\n\r\n&lt;div align=&quot;right&quot; style=&quot;font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;&quot;&gt;Обработано в SalesMan CRM&lt;/div&gt;\r\n&lt;/div&gt;\r\n',1);
/*!40000 ALTER TABLE `salesman_tpl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_uids`
--

DROP TABLE IF EXISTS `salesman_uids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_uids` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) DEFAULT NULL COMMENT 'название параметра',
  `value` varchar(100) DEFAULT NULL COMMENT 'знаение параметра',
  `lid` int DEFAULT '0' COMMENT 'id заявки',
  `eid` int DEFAULT '0' COMMENT 'id обращения',
  `clid` int DEFAULT '0' COMMENT 'id записи клиента',
  `did` int DEFAULT '0' COMMENT 'id записи сделки',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='База связки id сторонних систем с записями CRM';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_uids`
--

LOCK TABLES `salesman_uids` WRITE;
/*!40000 ALTER TABLE `salesman_uids` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_uids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_user`
--

DROP TABLE IF EXISTS `salesman_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_user` (
  `iduser` int NOT NULL AUTO_INCREMENT,
  `login` varchar(250) NOT NULL COMMENT 'Логин',
  `pwd` varchar(250) NOT NULL COMMENT 'хеш пароля',
  `ses` text COMMENT 'Сессия',
  `title` varchar(250) DEFAULT NULL COMMENT 'ФИО',
  `tip` varchar(250) DEFAULT 'Менеджер продаж',
  `user_post` varchar(255) DEFAULT NULL,
  `mid` int DEFAULT '0',
  `bid` int DEFAULT '0',
  `otdel` text,
  `email` text COMMENT 'Email',
  `gcalendar` text,
  `territory` int NOT NULL DEFAULT '0',
  `office` int NOT NULL DEFAULT '0',
  `phone` text COMMENT 'Телефон',
  `phone_in` varchar(20) DEFAULT NULL COMMENT 'Добавочный номер',
  `fax` text,
  `mob` text COMMENT 'Мобильный',
  `bday` date DEFAULT NULL,
  `acs_analitics` varchar(5) DEFAULT NULL COMMENT 'Доступ к отчетам',
  `acs_maillist` varchar(5) DEFAULT NULL COMMENT 'Доступ к рассылкам',
  `acs_files` varchar(5) DEFAULT NULL COMMENT 'Доступ к разделу Файлы',
  `acs_price` varchar(5) DEFAULT NULL COMMENT 'Доступ к разделу Прайс',
  `acs_credit` varchar(5) DEFAULT NULL COMMENT 'Может ставить оплаты',
  `acs_prava` varchar(5) DEFAULT NULL COMMENT 'Может просматривать чужие записи',
  `tzone` varchar(5) DEFAULT NULL COMMENT 'Временная зона',
  `viget_on` varchar(500) DEFAULT 'on;on;on;on;on;on;on;on;on;on;on',
  `viget_order` varchar(500) DEFAULT 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11',
  `secrty` varchar(5) NOT NULL DEFAULT 'yes' COMMENT 'доступ в систему',
  `isadmin` varchar(3) NOT NULL DEFAULT 'off' COMMENT 'признак администратора',
  `acs_import` varchar(255) DEFAULT NULL COMMENT 'разные права',
  `show_marga` varchar(3) NOT NULL DEFAULT 'yes' COMMENT 'видит маржу',
  `acs_plan` varchar(60) NOT NULL DEFAULT 'on' COMMENT 'имеет план продаж',
  `zam` int DEFAULT '0',
  `CompStart` date DEFAULT NULL,
  `CompEnd` date DEFAULT NULL,
  `subscription` text COMMENT 'подписки на email-уведомления',
  `avatar` varchar(100) DEFAULT NULL COMMENT 'аватар',
  `sole` varchar(250) DEFAULT NULL,
  `adate` date DEFAULT NULL,
  `usersettings` text,
  `uid` varchar(30) DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`iduser`),
  KEY `title` (`title`),
  KEY `mid` (`mid`),
  KEY `secrty` (`secrty`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_user`
--

LOCK TABLES `salesman_user` WRITE;
/*!40000 ALTER TABLE `salesman_user` DISABLE KEYS */;
INSERT INTO `salesman_user` VALUES (1,'admin','3e94331b2291acf795b7ba7dae5abcc755bf6b372a7be21c93fd4c8c0da9fd5b9879fe73889cc82e2bef8457c003ac1610e3cd5dfc0701492d65dc09db5e36b7a9c1f826e2e603076db730eb60f8b5c50e8fb48c5b','s2ys05sznxSd0gueqCRiv40sn59ouCzkTOGY0Nk7IiPhDJacnqBSNVDvFmsi','Директор','Руководитель организации','Руководитель',0,0,NULL,'admin',NULL,0,0,NULL,NULL,NULL,NULL,NULL,'on','on','on','on','on','on','+0','on;on;on;on;on;on;on;on;on;on;on;on','d1;d2;d5;d7;d4;d6;d3;d8;d9;d10;d11;d12','yes','on','on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on','yes','on',0,NULL,NULL,'on;off;off;on;off;on;on;on;on;on;on;on;off;off;off;off;off;off',NULL,'znxSd0gueqCRiv40sn59o31Tocc6dEGW',NULL,'{\"vigets\":{\"voronka\":\"on\",\"analitic\":\"on\",\"dogs_renew\":\"on\",\"credit\":\"on\",\"stat\":\"on\",\"dogsclosed\":\"on\",\"history\":\"on\",\"parameters\":\"on\",\"middleMetric\":\"on\"},\"taskAlarm\":null,\"userTheme\":\"\",\"userThemeRound\":null,\"startTab\":\"vigets\",\"menuClient\":\"all\",\"menuPerson\":\"all\",\"menuDeal\":\"all\",\"notify\":[\"client.add\",\"client.edit\",\"client.userchange\",\"client.delete\",\"client.double\",\"person.send\",\"deal.add\",\"deal.edit\",\"deal.userchange\",\"deal.step\",\"deal.close\",\"invoice.doit\",\"lead.add\",\"lead.setuser\",\"lead.do\",\"comment.new\",\"comment.close\",\"task.add\",\"task.edit\",\"task.doit\",\"self\"],\"filterAllBy\":null,\"subscribs\":null}',NULL,1);
/*!40000 ALTER TABLE `salesman_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_useronline`
--

DROP TABLE IF EXISTS `salesman_useronline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_useronline` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` datetime NOT NULL COMMENT 'время последней активности',
  `iduser` int NOT NULL DEFAULT '0' COMMENT 'id сотрудника',
  `status` varchar(10) NOT NULL DEFAULT 'offline' COMMENT 'статус сотрудника',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Хранилище статусов пользователей: online/offline';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_useronline`
--

LOCK TABLES `salesman_useronline` WRITE;
/*!40000 ALTER TABLE `salesman_useronline` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_useronline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ver`
--

DROP TABLE IF EXISTS `salesman_ver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ver` (
  `id` int NOT NULL AUTO_INCREMENT,
  `current` varchar(10) NOT NULL,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ver`
--

LOCK TABLES `salesman_ver` WRITE;
/*!40000 ALTER TABLE `salesman_ver` DISABLE KEYS */;
INSERT INTO `salesman_ver` VALUES (1,'2023.1','2023-02-04 15:44:54');
/*!40000 ALTER TABLE `salesman_ver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_webhook`
--

DROP TABLE IF EXISTS `salesman_webhook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_webhook` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT 'event' COMMENT 'название ',
  `event` varchar(255) DEFAULT NULL COMMENT 'событие',
  `url` tinytext,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_webhook`
--

LOCK TABLES `salesman_webhook` WRITE;
/*!40000 ALTER TABLE `salesman_webhook` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_webhook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_webhooklog`
--

DROP TABLE IF EXISTS `salesman_webhooklog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_webhooklog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event` varchar(50) NOT NULL,
  `query` text NOT NULL,
  `response` text NOT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_webhooklog`
--

LOCK TABLES `salesman_webhooklog` WRITE;
/*!40000 ALTER TABLE `salesman_webhooklog` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_webhooklog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ymail_blacklist`
--

DROP TABLE IF EXISTS `salesman_ymail_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ymail_blacklist` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id записи',
  `email` varchar(50) DEFAULT NULL COMMENT 'e-mail ',
  `identity` int NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ymail_blacklist`
--

LOCK TABLES `salesman_ymail_blacklist` WRITE;
/*!40000 ALTER TABLE `salesman_ymail_blacklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_ymail_blacklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ymail_files`
--

DROP TABLE IF EXISTS `salesman_ymail_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ymail_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int DEFAULT NULL COMMENT 'mail.id',
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата',
  `name` varchar(255) DEFAULT NULL COMMENT 'оригинальное имя файла',
  `file` varchar(255) DEFAULT NULL COMMENT 'переименнованое имя файла для системы',
  `identity` int unsigned DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ymail_files`
--

LOCK TABLES `salesman_ymail_files` WRITE;
/*!40000 ALTER TABLE `salesman_ymail_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_ymail_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ymail_messages`
--

DROP TABLE IF EXISTS `salesman_ymail_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ymail_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата сообщения',
  `folder` varchar(30) NOT NULL DEFAULT 'draft' COMMENT 'тип сообщения inbox => Входящее, outbox => Исходящее, draft => Черновик, trash => Корзина, sended => Отправлено',
  `trash` varchar(30) NOT NULL DEFAULT 'no' COMMENT 'в корзине или нет сообщение (yes - в корзине)',
  `priority` int NOT NULL DEFAULT '3',
  `state` varchar(50) DEFAULT NULL COMMENT 'deleted - удаленные, read - прочитанные, unread - не прочинанны',
  `subbolder` varchar(255) DEFAULT NULL,
  `messageid` varchar(255) DEFAULT NULL,
  `uid` int DEFAULT NULL,
  `hid` int DEFAULT NULL COMMENT 'привязска с историей активности (history.cid)',
  `parentmid` varchar(255) DEFAULT NULL,
  `fromm` mediumtext,
  `fromname` mediumtext,
  `theme` varchar(255) DEFAULT NULL,
  `content` longtext,
  `iduser` int DEFAULT NULL,
  `fid` text,
  `did` int DEFAULT NULL,
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `theme` (`theme`),
  KEY `iduser` (`iduser`),
  KEY `did` (`did`),
  KEY `messageid` (`messageid`),
  KEY `complex` (`folder`,`state`,`iduser`,`identity`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ymail_messages`
--

LOCK TABLES `salesman_ymail_messages` WRITE;
/*!40000 ALTER TABLE `salesman_ymail_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_ymail_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ymail_messagesrec`
--

DROP TABLE IF EXISTS `salesman_ymail_messagesrec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ymail_messagesrec` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int DEFAULT NULL COMMENT 'ymail_messages.id',
  `tip` varchar(100) DEFAULT 'to' COMMENT 'полученно-отправленное письмо',
  `email` varchar(100) DEFAULT NULL COMMENT 'email',
  `name` varchar(200) DEFAULT NULL COMMENT 'имя отправителя-получателя',
  `clid` int DEFAULT NULL COMMENT 'clid в таблице clientcat.clid',
  `pid` int DEFAULT NULL COMMENT 'pid в таблице personcat.pid',
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `email` (`email`),
  KEY `clid` (`clid`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ymail_messagesrec`
--

LOCK TABLES `salesman_ymail_messagesrec` WRITE;
/*!40000 ALTER TABLE `salesman_ymail_messagesrec` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_ymail_messagesrec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ymail_settings`
--

DROP TABLE IF EXISTS `salesman_ymail_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ymail_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `iduser` int NOT NULL DEFAULT '0' COMMENT 'id пользователя user.iduser',
  `settings` text COMMENT 'настройки',
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата и время последнего события',
  `identity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ymail_settings`
--

LOCK TABLES `salesman_ymail_settings` WRITE;
/*!40000 ALTER TABLE `salesman_ymail_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_ymail_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_ymail_tpl`
--

DROP TABLE IF EXISTS `salesman_ymail_tpl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salesman_ymail_tpl` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT 'Шаблон',
  `content` text,
  `share` varchar(5) DEFAULT 'no',
  `iduser` int DEFAULT NULL,
  `identity` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `identity` (`identity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_ymail_tpl`
--

LOCK TABLES `salesman_ymail_tpl` WRITE;
/*!40000 ALTER TABLE `salesman_ymail_tpl` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_ymail_tpl` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-16 17:51:27
