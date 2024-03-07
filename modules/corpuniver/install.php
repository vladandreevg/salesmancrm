<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.х         */
/* ============================ */
/*   Developer: Ivan Drachyov   */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

// Проверяем наличие главной таблицы модуля. Если таблицы нет, то создаем её
$da = $db -> getCol("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}corpuniver_course'");
if ($da[0] == 0) {

	// Таблица "Курсы"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_course (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`cat` INT(20) NOT NULL DEFAULT '0' COMMENT 'id категории',
			`name` VARCHAR(250) NULL DEFAULT NULL COMMENT 'название', 
			`date_create` DATE NOT NULL COMMENT 'дата создания',
			`author` INT(20) NULL DEFAULT NULL COMMENT 'автор', 
			`des` TEXT NULL COMMENT 'описание', 
			`date_edit` DATETIME NULL COMMENT 'дата изменения',
			`editor` INT(20) NULL DEFAULT NULL COMMENT 'редактор', 
			`moderator` VARCHAR(255) NULL COMMENT 'модераторы', 
			`fid` TEXT COMMENT 'список файлов',
			`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Список курсов'
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	// Таблица "Категории курсов"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_course_cat (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`subid` INT(20) NOT NULL DEFAULT '0' COMMENT 'родительская категория',
			`title` VARCHAR(250) NULL DEFAULT '0' COMMENT 'название',
			`identity` INT(30) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Список категорий курсов'
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	// Добавляем начальную категорию
	$db -> query("INSERT INTO ".$sqlname."corpuniver_course_cat SET subid=0, title='Общее', identity='$identity'");

	// Таблица "Лекции"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_lecture (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`course` INT(20) NOT NULL COMMENT 'курс', 
			`name` VARCHAR(250) NULL DEFAULT NULL COMMENT 'название лекции', 
			`ord` INT(20) NOT NULL DEFAULT '0' COMMENT 'порядок вывода', 
			`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Лекции' 
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	// Таблица "Материалы лекции"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_material (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`lecture` INT(20) NULL DEFAULT NULL COMMENT 'лекция', 
			`type` VARCHAR(20) NOT NULL COMMENT 'тип материала', 
			`name` VARCHAR(250) NULL DEFAULT NULL COMMENT 'название материала',
			`text` TEXT NULL COMMENT 'текст материала', 
			`fid` TEXT COMMENT 'список файлов',
			`source` TEXT COMMENT 'ссылка на сторонний ресурс',
			`ord` INT(20) NOT NULL DEFAULT '0' COMMENT 'порядок вывода', 
			`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Материалы лекции' 
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	// Таблица "Задания лекции"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_task (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`lecture` INT(20) NULL DEFAULT NULL COMMENT 'лекция',
			`type` VARCHAR(20) NOT NULL COMMENT 'тип задания', 
			`name` VARCHAR(250) NULL DEFAULT NULL COMMENT 'название задания',
			`fid` TEXT COMMENT 'список файлов(материалы к заданию)',
			`ord` INT(20) NOT NULL DEFAULT '0' COMMENT 'порядок вывода', 
			`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Задания по лекции' 
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	// Таблица "Вопросы заданий лекции"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_questions (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`task` INT(20) NULL DEFAULT NULL COMMENT 'задание',
			`text` VARCHAR(250) NULL DEFAULT NULL COMMENT 'текст вопроса', 
			`ord` INT(20) NULL DEFAULT '0' COMMENT 'порядок вывода', 
			`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Вопросы заданий' 
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	// Таблица "Ответы на вопросы заданий"
	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_answers (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата и время ответа',
			`question` INT(20) NULL DEFAULT NULL COMMENT 'вопрос',
			`text` VARCHAR(250) NULL DEFAULT NULL COMMENT 'текст ответа', 
			`status` INT(1) NOT NULL DEFAULT '0' COMMENT 'правильность (0-неверный, 2-верный ответ)', 
			`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`)
		) 
		COMMENT='Модуль Корпоративный университет. Ответы на вопросы' 
		COLLATE='utf8_general_ci' 
		ENGINE=InnoDB
	");

	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_coursebyusers (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` DATETIME NULL DEFAULT NULL COMMENT 'дата старта',
			`datum_end` DATETIME NULL DEFAULT NULL COMMENT 'дата завершения',
			`idcourse` INT(20) NOT NULL DEFAULT '0' COMMENT 'id курса',
			`idlecture` INT(20) NOT NULL DEFAULT '0' COMMENT 'id лекции',
			`idmaterial` INT(20) NOT NULL DEFAULT '0' COMMENT 'id материала',
			`idtask` INT(20) NOT NULL DEFAULT '0' COMMENT 'id теста',
			`iduser` INT(20) NOT NULL DEFAULT '0' COMMENT 'id сотрудника',
			`identity` INT(20) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`)
		)
		COMMENT='Таблица фиксации прохождения курсов сотрудниками'
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB
	");

	$db -> query("
		CREATE TABLE {$sqlname}corpuniver_useranswers (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`type` CHAR(10) NULL DEFAULT NULL COMMENT 'тип ответа - task или quest',
			`datum` DATETIME NULL DEFAULT NULL COMMENT 'дата ответа',
			`iduser` INT(20) NULL DEFAULT NULL COMMENT 'id сотрудника',
			`parent` INT(20) NULL DEFAULT NULL COMMENT 'id вопроса или теста',
			`answer` VARCHAR(50) NULL DEFAULT NULL COMMENT 'содержимое ответа',
			`identity` INT(20) NULL DEFAULT '1',
			PRIMARY KEY (`id`)
		)
		COMMENT='Ответы сотрудников на вопросы тестов и вопросов'
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB
	");

	print 'Модуль "Корпоративный университет" установлен <br>';

}


//добавим запись в таблицу модулей для текущего аккаунта
$isModule = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}modules WHERE mpath = 'corpuniver' AND identity = '$identity'") + 0;
if ($isModule == 0) {

	$db -> query("INSERT INTO {$sqlname}modules SET ?u", [
		'title'        => 'Корпоративный университет',
		'mpath'        => 'corpuniver',
		'icon'         => 'icon-town-hall',
		'active'       => 'on',
		'activateDate' => current_datumtime(),
		'identity'     => $identity
	]);

	print 'Модуль "Корпоративный университет" активирован<br>';

}

exit();