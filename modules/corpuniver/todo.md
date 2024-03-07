## Развитие модуля

* Фиксация даты начала/окончания прохождения Курса, Лекции, Материала, Задания [ + ]

* Отображение курса
    - Отображение прогресса изучения Курса, Лекции [ + ]
    - После прохождения Курса дать просмотреть результаты [ - ]
    - Добавить статус курса Активен/Не активен [ - ]
    - Добавить ограничение доступа к курсам ( указывает автор по ролям/сотрудникам ) [ - ]

* Показ лекций [ - ]
    - показывать последовательно, после прохождения предыдущего
    - при открытии открывать последнюю лекцию и последний не завершенный материал [ + ]
    - не давать переходить на новые лекции/материалы, пока не будет завершен предыдущий [ + ]
    - возможность открыть конкретный Материал [ - ]
    - выделить усвоенный Материал, выполненные Тесты [ + ]

* Изменить навигацию по слайдам
    - Переход к следующему как завершение изучения текущего [ + ]
    - Отключить переход к предыдущему [ + ]
    - Отключить выбор тестов [ + ]

* Материалы
    - Разделение материалов на шаги [ - ]
    - Возможность просмотреть материал повторно (из общего списка, без фиксаций) [ + ]

* Прохождение Тестов
    - Не давать проходить Тесты/задачи, пока прогресс меньше 100% [ + ]
    - Вывести количество тестов как информацию + прогресс прохождения тестов [ + ]
    - Заменить Radio на Checkbox для возможности указания нескольких правильных ответов [ - ]

* Добавить представление со списком сотрудников
    - Выводить пройденные и проходимые курсы ( с указанием прогресса ) [ - ]

* Конвертация загруженных файлов
    - конвертировать файлы "docx", "doc", "rtf", "pptx", "ppt" в PDF [ + ]

* Уведомления
    - уведомление о прохождении курса [ - ]
    - отправка приглашения на прохождение курса по email [ - ]

* Импорт/Экспорт курсов
    - выяснить, есть ли какой-то стандартный формат для выгрузки лекций [ - ]
    
* Практические задания
    - https://support.stepik.org/hc/ru/articles/360000159673

## Добавленные данные в БД

```mysql
ALTER TABLE `app_corpuniver_answers` ADD COLUMN `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'дата и время ответа' AFTER `id`
```

```mysql
CREATE TABLE `app_corpuniver_coursebyusers` (
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
```

```mysql
ALTER TABLE `app_corpuniver_coursebyusers` CHANGE COLUMN `idcource` `idcourse` INT(20) NOT NULL DEFAULT '0' COMMENT 'id курса' AFTER `datum_end`;
ALTER TABLE `app_corpuniver_coursebyusers` ADD COLUMN `idtask` INT(20) NOT NULL DEFAULT '0' COMMENT 'id теста' AFTER `idmaterial`;
```

```mysql
CREATE TABLE `app_corpuniver_useranswers` (
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
```