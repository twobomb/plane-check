-- Valentina Studio --
-- MySQL dump --
-- ---------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
-- ---------------------------------------------------------


-- CREATE TABLE "department" -----------------------------------
CREATE TABLE `department`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`addr` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`parent_id` Int( 255 ) NULL DEFAULT NULL,
	`point_id` Int( 255 ) NULL DEFAULT NULL,
	`sort_id` Int( 255 ) NOT NULL DEFAULT 0,
	PRIMARY KEY ( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 497;
-- -------------------------------------------------------------


-- CREATE TABLE "department_to_plan" ---------------------------
CREATE TABLE `department_to_plan`( 
	`department_id` Int( 255 ) NOT NULL,
	`plan_id` Int( 255 ) NOT NULL )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "file_to_plan" ---------------------------------
CREATE TABLE `file_to_plan`( 
	`file_id` Int( 255 ) NOT NULL,
	`plan_id` Int( 255 ) NOT NULL )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "files" ----------------------------------------
CREATE TABLE `files`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`url` Text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`date` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`name` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 7;
-- -------------------------------------------------------------


-- CREATE TABLE "history" --------------------------------------
CREATE TABLE `history`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`user_id` Int( 255 ) NOT NULL,
	`plan_id` Int( 255 ) NOT NULL,
	`date` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`type` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`value` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 20;
-- -------------------------------------------------------------


-- CREATE TABLE "layer" ----------------------------------------
CREATE TABLE `layer`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`description` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`can_edit` TinyInt( 255 ) NOT NULL DEFAULT 1,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 1;
-- -------------------------------------------------------------


-- CREATE TABLE "plan" -----------------------------------------
CREATE TABLE `plan`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`content` MediumText CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`user_id` Int( 255 ) NOT NULL,
	`create_at` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`date_type` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`date_value` Date NULL DEFAULT NULL,
	`status` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`parent_id` Int( 255 ) NULL DEFAULT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 8;
-- -------------------------------------------------------------


-- CREATE TABLE "point" ----------------------------------------
CREATE TABLE `point`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`layer_id` Int( 255 ) NULL DEFAULT NULL,
	`type` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`lon` Float( 12, 0 ) NOT NULL,
	`lat` Float( 12, 0 ) NOT NULL,
	`addr` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`description` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 3;
-- -------------------------------------------------------------


-- CREATE TABLE "point_to_plan" --------------------------------
CREATE TABLE `point_to_plan`( 
	`plan_id` Int( 255 ) NOT NULL,
	`point_id` Int( 255 ) NOT NULL )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "user" -----------------------------------------
CREATE TABLE `user`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`username` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`login` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`pwd_hash` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`role` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`create_at` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 2;
-- -------------------------------------------------------------


-- Dump data of "department" -------------------------------
BEGIN;

INSERT INTO `department`(`id`,`name`,`addr`,`parent_id`,`point_id`,`sort_id`) VALUES 
( '55', 'УЧЕБНЫЙ ПУНКТ ФПС ГПС', '291045, 50 лет Образования СССР, 63', '64', NULL, '3' ),
( '59', 'ЦЕНТР ГИДРОМЕТЕОРОЛОГИИ', '91008, г.Луганск, Городок ЛНАУ, 5-б', '180', NULL, '54' ),
( '62', 'СПЕЦИАЛИЗИРОВАННЫЙ ОТРЯД ', '291042, г.Луганск, ул.30-лет Победы, 51б', NULL, NULL, '21' ),
( '64', '1 ПСО (г. Луганск)', '291047, г. Луганск, ул.Фабричная, 25', NULL, NULL, '22' ),
( '66', '5 ПСЧ ', '291011, г. Луганск, ул.Фабричная, 25', NULL, NULL, '29' ),
( '67', 'СПСЧ', '291042, г. Луганск, ул.30-лет Победы, 51 А', NULL, NULL, '30' ),
( '68', '1 ПСЧ (Г. ЛУГАНСК) ', ' (291011, кв.Алексеева, 12)', NULL, NULL, '23' ),
( '69', '2 ПСЧ  (Г. ЛУГАНСК)', ' (291005, г. Луганск, ул. Демина, 1 а)', NULL, NULL, '24' ),
( '70', '3 ПСЧ (Г. ЛУГАНСК) ', '291029, ул.2-я Краснознаменная, 49', NULL, NULL, '25' ),
( '71', '4 ПСЧ (Г. ЛУГАНСК)', '291014, г. Луганск, ул. Эстонская, 5', NULL, NULL, '27' ),
( '72', 'ОП 3 ПСЧ (ПО ОХРАНЕ ПОС. ЮБИЛЕЙНЫЙ)', '291493 г.Луганск,пгт.Юбилейный ул.Бондаренко, 1-Б', NULL, NULL, '26' ),
( '73', '6 ПСО (Г. КРАСНОДОН)', '294405, г. Краснодон, пр-т 60 лет СССР, 5', NULL, NULL, '35' ),
( '74', '6 ПСЧ (Г. КРАСНОДОН)', '294405, г. Краснодон, пр-т 60 лет СССР, 5', '73', NULL, '32' ),
( '75', 'ОП (ПО ОХРАНЕ ПГТ КРАСНОДОН)', ' (п.Широкий, ул. Клубная, 29)', '73', NULL, '34' ),
( '76', '41 ПСЧ (Г. СУХОДОЛЬСК)', '294420, г. Суходольск, ул. Шоссейная, 103', '73', NULL, '35' ),
( '77', '42 ПСЧ (Г. МОЛОДОГВАРДЕЙСК) ', '294415, г. Молодогвардейск, ул. Калинина, 15', '73', NULL, '36' ),
( '78', '43 ПСЧ (ПГТ ИЗВАРИНО)', 'пгт Изварино ул. Клубная, 2 б', '73', NULL, '37' ),
( '80', '11 ПСО (Г. СВЕРДЛОВСК) ', '294801, г. Свердловск ул. Октябрьская, 33', NULL, NULL, '40' ),
( '81', '7 ПСЧ (Г. СВЕРДЛОВСК)', 'г. Свердловск ул. Октябрьская, 33', '80', NULL, '34' ),
( '82', '8 ПСЧ (ПГТ ШАХТЁРСКИЙ)', 'пгт Шахтёрский, ул.Пролетарская, 5', '80', NULL, '35' ),
( '83', '9 ПСЧ (Г. ЧЕРВОНОПАРТИЗАНСК) ', 'г. Червонопартизанск, ул.Некрасова, 1 а', '80', NULL, '36' ),
( '84', '10 ПСО (Г. РОВЕНЬКИ) ', '294700, г. Ровеньки, ул. Ленина, 11', NULL, NULL, '39' ),
( '85', '10 ПСЧ (Г. РОВЕНЬКИ)', ' (г. Ровеньки, ул.Ленина, 11)', '84', NULL, '35' ),
( '86', 'ОП  10 ПО ОХРАНЕ Г. РОВЕНЬКИ', '294706, г. Ровеньки, ул. Есенина, 3', '84', NULL, '36' ),
( '87', '44 ПСЧ (ПГТ ЯСЕНОВСКИЙ)', ' (г. Ровеньки, пгт Ясеновский, пер. Тюкова, 14)', '84', NULL, '37' ),
( '88', '45 ПСЧ (ПГТ ДЗЕРЖИНСКИЙ)', ' (г. Ровеньки, пгт Дзержинский, ул. Волкова, 63)', '84', NULL, '38' ),
( '89', '3 ПСО (Г. АНТРАЦИТ)', '94613, г. Антрацит, ул.Заводская, 1', NULL, NULL, '32' ),
( '90', 'ОП (ПО ОХРАНЕ ПГТ  ДУБОВСКИЙ)', 'пгт Дубовский (294636, г.Антрацит, пгт Дубовский, ул. Гагарина, 17)', '89', NULL, '6' ),
( '91', '11 ПСЧ  (Г. АНТРАЦИТ)', '294613, г. Антрацит, ул. Заводская, 1', '89', NULL, '5' ),
( '92', 'ОП ПО ОХРАНЕ (ПГТ ИВАНОВКА )', 'пгт Ивановка (94643, Антрацитовский р-н, пгт Ивановка, ул. Пушкина, 23)', '89', NULL, '7' ),
( '93', 'ОП ПО ОХРАНЕ (ПГТ КРЕПЕНСКИЙ)', ' (294632, г. Антрацит, ул. Зеленая, 4)', '89', NULL, '8' ),
( '94', '46 ПСЧ (ПГТ ЩЕТОВО)', ' (94620, г. Антрацит, пгт Щетово ул. Базарная, 8)', '89', NULL, '8' ),
( '96', '5 ПСО (Г. КРАСНЫЙ ЛУЧ)', '294513, г. Красный Луч, ул. МОПРа, 2', NULL, NULL, '34' ),
( '97', '12 ПСЧ (Г. КРАСНЫЙ ЛУЧ)', ' (294513, г. Красный Луч, ул. МОПРа, 2)', '96', NULL, '6' ),
( '98', '13 ПСЧ (Г. ПЕТРОВСКОЕ)', 'г. Петровское, ул. Первомайская, 7', '96', NULL, '7' ),
( '99', '14 ПСЧ (Г. ВАХРУШЕВО)', 'Антрацитовский район, пгт Красный Кут, ул.Свердлова, 138', '96', NULL, '8' ),
( '100', '15 ПСЧ (Г. КРАСНЫЙ ЛУЧ)', 'г. Красный Луч, ул. Луганское Шоссе, 52', '96', NULL, '9' ),
( '101', 'ОП (ПО ОХРАНЕ Г. МИУСИНСК)', 'г. Миусинск, ул.Вокзальная, 29', '96', NULL, '5' ),
( '102', 'Краснолучское ГИМС', 'г. Миусинск, ул. Школьная, 1', '384', NULL, '4' ),
( '103', '9 ПСО (Г. ПЕРЕВАЛЬСК)', '294300, г.Перевальск, ул.Мира, 16', NULL, NULL, '38' ),
( '104', '16 ПСЧ (Г. ПЕРЕВАЛЬСК)', 'г.Перевальск, ул.Мира, 16', '103', NULL, '1' ),
( '105', '17 ПСЧ (Г. АРТЁМОВСК)', ' (г. Артёмовск, ул.Стаханова, 10)', '103', NULL, '2' ),
( '107', '53 ПСЧ (ПГТ ЧЕРНУХИНО)', 'пгт Чернухино, ул.Папанина, 1', '103', NULL, '3' ),
( '108', '2 ПСО (Г. АЛЧЕВСК)', '294201, г.Алчевск, ул.Репина, 69', NULL, NULL, '31' ),
( '109', '18 ПСЧ (Г. АЛЧЕВСК)', ' (294201, г. Алчевск, ул. Репина, 69) ', '108', NULL, '1' ),
( '110', '19 ПСЧ (Г. АЛЧЕВСК)', ' (294201, ул. Ленинградская, 1а) ', '108', NULL, '1' ),
( '113', '20 ПСЧ (Г. КИРОВСК)', '293805, г. Кировск, ул. Неделина, 2', '120', NULL, '6' ),
( '115', '47 ПСЧ (ПГТ ДОНЕЦКИЙ)', ' (293890, пгт Донецкий, ул. Московская, 2)', '120', NULL, '7' ),
( '116', '48 ПСЧ (ПГТ ЧЕРВОНОГВАРДЕЙСКОЕ) ', ' (293892, пгт Червоногвардейское, ул. Депутатская, 3)', '120', NULL, '8' ),
( '117', '21 ПСЧ (Г. БРЯНКА)', '294100, г.Брянка, ул.Дворцовая, 11-а', '120', NULL, '3' ),
( '119', '49 ПСЧ (ПГТ АННЕНКА)', ' (294104, пгт Анненка, ул. Литвинова, 27)', '120', NULL, '9' ),
( '120', '4 ПСО (Г. СТАХАНОВ)', '294013, г. Стаханов, ул. Бурбело, 8', NULL, NULL, '33' ),
( '121', '22 ПСЧ (Г. СТАХАНОВ) ', ' (294013, г.Стаханов, ул.Бурбело,8)', '120', NULL, '10' ),
( '122', '50 ПСЧ (Г. ИРМИНО)', ' (294092, г.Ирмино, ул.Станюкевича,4)', '120', NULL, '11' ),
( '123', '51 ПСЧ (Г. АЛМАЗНАЯ) ', '294095, г. Алмазная, ул.Теплая, 5', '120', NULL, '44' ),
( '124', '8 ПСО (Г. ПЕРВОМАЙСК)', '293200, г. Первомайск, ул. Лейтенанта Правика, 3', NULL, NULL, '37' ),
( '125', '23 ПСЧ (Г. ПЕРВОМАЙСК) ', ' (293200 г. Первомайск, ул. Лейтенанта Правика, 3)', '124', NULL, '3' ),
( '126', '12 ПСО (ПГТ СЛАВЯНОСЕРБСК)', '293701, п. Славяносербск, пер. Пионерский, 2', NULL, NULL, '41' ),
( '127', '24  ПСЧ (ПГТ СЛАВЯНОСЕРБСК)', '293701, пгт Славяносербск, пер.Пионерский, 2', '126', NULL, '4' ),
( '128', '25 ПСЧ (Г. ЗИМОГОРЬЕ) ', ' (г. Зимогорье, ул. Некрасова, 1)', '126', NULL, '5' ),
( '129', 'ОП  (ПО ОХРАНЕ ПГТ РОДАКОВО)', ' (пгт Родаково, ул.Ворошилова, 4 а)', '126', NULL, '6' ),
( '130', 'ОП (ПО ОХРАНЕ ПГТ ФРУНЗЕ)', 'пгт Фрунзе, ул. Энгельса, 3-а', '126', NULL, '8' ),
( '131', 'ОП  (ПО ОХРАНЕ ПГТ ЛОТИКОВО)', ' (пгт Лотиково, ул.Папанина, 2)', '126', NULL, '7' ),
( '132', '7 ПСО (Г. ЛУТУГИНО)', '292000, г. Лутугино, ул.Марии Буцкой, 1', NULL, NULL, '36' ),
( '133', '26 ПСЧ (Г. ЛУТУГИНО)', ' (292000, г.Лутугино, ул.Марии Буцкой, 1)', '132', NULL, '3' ),
( '134', '27 ПСЧ (ПГТ БЕЛОЕ)', ' (292015, пгт Белое, ул.Труда, 1)', '132', NULL, '5' ),
( '135', 'ОП ПО ОХРАНЕ ПГТ БЕЛОРЕЧЕНСКИЙ ', '292016, пгт Белореченский,ул. Пионеров, 9', '132', NULL, '6' ),
( '136', 'ОП ПО ОХРАНЕ ПГТ УСПЕНКА', '292007, пгт Успенка-1, ул.Гагарина, 13', '132', NULL, '4' ),
( '139', 'ЦУ ВГСО', ' (91493, ул. Артема, 4, п. Юбилейный, г. Луганск)', '180', NULL, '56' ),
( '141', 'ВГСЧ №1 Стаханов', ' (94007, г. Стаханов ул. Мичурина, 2)', '139', NULL, '46' ),
( '146', 'ВГСЧ №2 Краснодон', '94400, г. Краснодон, ул. Ленина, 1', '139', NULL, '48' ),
( '150', 'ВГСЧ №3 Красный Луч', '94520, г. Красный Луч, ул. Орджоникидзе, 20', '139', NULL, '50' ),
( '200', 'СОТ ВГСО ЦУ', '91493, г. Луганск, п.Юбилейный ул. Артема, 4', '139', NULL, '0' ),
( '221', '17 ПСО (ПГТ СТАНИЦА ЛУГАНСКАЯ)', '293600, пгт Станица Луганская ул. 5-я Линия, 24', NULL, NULL, '46' ),
( '222', '29 ПСЧ (ПГТ СТАНИЦА  ЛУГАНСКАЯ)', 'пгт Станица Луганская, ул. 5-я Линия, 24', '221', NULL, '3' ),
( '224', '78 ПСЧ Г.СЧАСТЬЕ', '291480, г. Луганск, г. Счастье, ул. Республиканская, 1а', NULL, NULL, '28' ),
( '225', '18 ПСО (ПГТ МАРКОВКА)', '292400, пгт Марковка, переулок Южный , д.7', NULL, NULL, '47' ),
( '226', '65 ПСЧ (Г. СВАТОВО)', '292603, г. Сватово,  пл. Советская, 45', '242', NULL, '5' ),
( '228', '66 ПСЧ (ПГТ НИЖНЯЯ ДУВАНКА)', 'пгт Нижняя Дуванка, ул. Почтовая, 10', '242', NULL, '6' ),
( '229', '54 ПСЧ (ПГТ БЕЛОВОДСК)', '292800, пгт Беловодск, ул. Заречная, 1', '221', NULL, '60' ),
( '230', '58 ПСЧ (ПГТ ТРОИЦКОЕ)', '292100, пгт Троицкое, ул. Комсомольская, 40', '238', NULL, '6' ),
( '231', '57 ПСЧ (ПГТ НОВОАЙДАР)', '293500, пгт Новоайдар, кв. Мира, 36', '232', NULL, '4' ),
( '232', '14 ПСО (Г. СТАРОБЕЛЬСК)', '292700, г. Старобельск, ул. Старотаганрогская, 113', NULL, NULL, '43' ),
( '233', '64 ПСЧ (Г. СТАРОБЕЛЬСК)', 'г. Старобельск, ул. Старотаганрогская, 113', '232', NULL, '5' ),
( '234', '56 ПСЧ (ПГТ МЕЛОВОЕ)', '292500, пгт Меловое, ул. Луначарского, 80', '225', NULL, '51' ),
( '235', '69 ПСЧ (ПГТ БЕЛОКУРАКИНО)', '292200, пгт Белокуракино, ул. Чапаева, 115', '238', NULL, '4' ),
( '237', 'ОП (ПО ОХРАНЕ ПГТ ЛОЗНО-АЛЕКСАНДРОВКА)', '292211, пгт Лозно-Александровка, ул. Вишнёвая, 54', '238', NULL, '5' ),
( '238', '16 ПСО (ПГТ НОВОПСКОВ)', '292303, пгт Новопсков, ул. Школьная, 7', NULL, NULL, '45' ),
( '239', '67 ПСЧ (ПГТ НОВОПСКОВ)', 'пгт Новопсков, ул. Школьная, 7', '238', NULL, '2' ),
( '240', 'ОП (ПО ОХРАНЕ ПГТ БЕЛОЛУЦК)', '292322, пгт Белолуцк, ул. Кольцова, 1', '238', NULL, '3' ),
( '242', '15 ПСО (Г. КРЕМЕННАЯ)', '292900 г. Кременная, ул. Чайковского, 5', NULL, NULL, '44' ),
( '243', '13 ПСО (Г. СЕВЕРОДОНЕЦК)', '293401, г. Северодонецк, ул. Новикова, 1 б', NULL, NULL, '42' ),
( '244', '76 ПСЧ (Г. КРЕМЕННАЯ)', '292900 г. Кременная, Чайковского, 5', '242', NULL, '3' ),
( '245', '62 ПСЧ (Г. РУБЕЖНОЕ)', 'г. Рубежное, ул. Восточная, 5', '243', NULL, '5' ),
( '246', 'ОП ПО ОХРАНЕ ПГТ КРАСНОРЕЧЕНСК', 'пгт Краснореченск, ул. Станкостроителей, 8 а', '242', NULL, '4' ),
( '247', '59 ПСЧ (Г. ЛИСИЧАНСК)', 'г. Лисичанск, ул. Свердлова, 324', '243', NULL, '4' ),
( '250', '63 ПСЧ (Г. СЕВЕРОДОНЕЦК)', 'г. Северодонецк, ул. Новикова, 1-Б', '243', NULL, '8' ),
( '279', 'ОП ПО ОХРАНЕ ПГТ ПЕТРОВКА', ' (293605, пгт Петровка, ул. Буденного д. 4)', '221', NULL, '4' ),
( '326', 'СПТ', '', NULL, NULL, '20' ),
( '331', '80 ПСЧ(Г. ГОРСКОЕ) ', '293292, г. Горское, ул. Ивана Данько, 3', '124', NULL, '4' ),
( '360', '55 ПСЧ (ПГТ МАРКОВКА)', '292400, пгт Марковка, переулок Южный, 7', '225', NULL, '3' ),
( '384', 'Центр ГИМС', 'индекс 91031, г. Луганск, 50 лет Образования СССР, 63', NULL, NULL, '100' ),
( '496', '30 ПСЧ', ' ', '243', NULL, '3' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "department_to_plan" -----------------------
BEGIN;

INSERT INTO `department_to_plan`(`department_id`,`plan_id`) VALUES 
( '222', '25' ),
( '82', '26' ),
( '83', '26' ),
( '64', '27' ),
( '108', '27' ),
( '110', '27' ),
( '117', '27' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "file_to_plan" -----------------------------
BEGIN;

INSERT INTO `file_to_plan`(`file_id`,`plan_id`) VALUES 
( '17', '27' ),
( '18', '27' ),
( '19', '27' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "files" ------------------------------------
BEGIN;

INSERT INTO `files`(`id`,`url`,`date`,`name`) VALUES 
( '17', 'uploaded/planfiles/4b8867734ec372b2ead60182160d6b56.png', '2025-12-09 23:05:26', '1384551.png' ),
( '18', 'uploaded/planfiles/c93eb28f7008286e4641a35936a41ecf.js', '2025-12-09 23:05:26', 'instuction-app.js' ),
( '19', 'uploaded/planfiles/30fae9d2a78c7d318c61f91f8d5d9ffc.jpg', '2025-12-09 23:05:26', 'c3.jpg' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "history" ----------------------------------
BEGIN;

INSERT INTO `history`(`id`,`user_id`,`plan_id`,`date`,`type`,`value`) VALUES 
( '62', '1', '24', '2025-12-09 20:03:50', 'add', 'План ооздан!' ),
( '63', '1', '25', '2025-12-09 20:59:40', 'add', 'План ооздан!' ),
( '64', '1', '26', '2025-12-09 21:01:13', 'add', 'План ооздан!' ),
( '65', '1', '26', '2025-12-09 22:44:45', 'edit', 'Изменен родительский план' ),
( '66', '1', '25', '2025-12-09 23:00:42', 'changestatus', 'Статус изменен на \'В работе\'' ),
( '67', '1', '25', '2025-12-09 23:00:53', 'changestatus', 'Статус изменен на \'В работе\'' ),
( '68', '1', '25', '2025-12-09 23:01:21', 'changestatus', 'Статус изменен на \'Ожидание\'' ),
( '69', '1', '25', '2025-12-09 23:01:49', 'changestatus', 'Статус изменен на \'В работе\'' ),
( '70', '1', '25', '2025-12-09 23:01:52', 'changestatus', 'Статус изменен на \'Выполнен\'' ),
( '71', '1', '27', '2025-12-09 23:05:26', 'add', 'План создан!' ),
( '72', '1', '27', '2025-12-09 23:26:49', 'changestatus', 'Статус изменен на \'В работе\'' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "layer" ------------------------------------
-- ---------------------------------------------------------


-- Dump data of "plan" -------------------------------------
BEGIN;

INSERT INTO `plan`(`id`,`name`,`content`,`user_id`,`create_at`,`date_type`,`date_value`,`status`,`parent_id`) VALUES 
( '24', 'Развертывание АТС в подразделениях', '<p>Установка и настройка АТС во всех подразделениях</p>', '1', '2025-12-09 20:03:50', 'year', '2026-12-31', 'inprogress', NULL ),
( '25', 'АТС настройка 29 ПСЧ Станица', '<p>АТС настройка 29 ПСЧ Станица</p>', '1', '2025-12-09 20:59:40', 'exact', '2025-12-09', 'completed', '24' ),
( '26', 'АТС настройка ПСЧ 8 ПСЧ 9', '<p>АТС настройка ПСЧ 8 ПСЧ 9</p>', '1', '2025-12-09 21:01:13', 'month', '2025-12-31', 'pending', '24' ),
( '27', 'Тестовый план', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure <strong>dolor in reprehenderit in voluptate velit esse</strong> cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit </p><blockquote>anim id est laborum.</blockquote><p class="ql-align-center"><img src="/uploaded/images/1765310726_6938810698a51.png" alt="" class="quill-image"></p>', '1', '2025-12-09 23:05:26', 'year', '2025-12-31', 'inprogress', NULL );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "point" ------------------------------------
BEGIN;

INSERT INTO `point`(`id`,`layer_id`,`type`,`lon`,`lat`,`addr`,`name`,`description`) VALUES 
( '1', NULL, '', '0', '0', 'Адрес', 'Геоточка #1', 'Описание..' ),
( '2', NULL, '', '0', '0', 'Адрес2', 'Еще одна геоточка с длинным название ', 'Описание..' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "point_to_plan" ----------------------------
BEGIN;

INSERT INTO `point_to_plan`(`plan_id`,`point_id`) VALUES 
( '27', '1' ),
( '27', '2' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "user" -------------------------------------
BEGIN;

INSERT INTO `user`(`id`,`username`,`login`,`pwd_hash`,`role`,`create_at`) VALUES 
( '1', 'admin', 'admin', 'ekwopkegowp', 'admin', '2025-12-07 16:10:49' );
COMMIT;
-- ---------------------------------------------------------


-- CREATE INDEX "lnk_department_department_to_plan" ------------
CREATE INDEX `lnk_department_department_to_plan` USING BTREE ON `department_to_plan`( `department_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_plan_department_to_plan" ------------------
CREATE INDEX `lnk_plan_department_to_plan` USING BTREE ON `department_to_plan`( `plan_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_files_file_to_plan" -----------------------
CREATE INDEX `lnk_files_file_to_plan` USING BTREE ON `file_to_plan`( `file_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_plan_file_to_plan" ------------------------
CREATE INDEX `lnk_plan_file_to_plan` USING BTREE ON `file_to_plan`( `plan_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_plan_history" -----------------------------
CREATE INDEX `lnk_plan_history` USING BTREE ON `history`( `plan_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_user_history" -----------------------------
CREATE INDEX `lnk_user_history` USING BTREE ON `history`( `user_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_user_plan_2" ------------------------------
CREATE INDEX `lnk_user_plan_2` USING BTREE ON `plan`( `user_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_layer_point" ------------------------------
CREATE INDEX `lnk_layer_point` USING BTREE ON `point`( `layer_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_plan_point_to_plan" -----------------------
CREATE INDEX `lnk_plan_point_to_plan` USING BTREE ON `point_to_plan`( `plan_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_point_point_to_plan" ----------------------
CREATE INDEX `lnk_point_point_to_plan` USING BTREE ON `point_to_plan`( `point_id` );
-- -------------------------------------------------------------


-- CREATE LINK "lnk_department_department_to_plan" -------------
ALTER TABLE `department_to_plan`
	ADD CONSTRAINT `lnk_department_department_to_plan` FOREIGN KEY ( `department_id` )
	REFERENCES `department`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_plan_department_to_plan" -------------------
ALTER TABLE `department_to_plan`
	ADD CONSTRAINT `lnk_plan_department_to_plan` FOREIGN KEY ( `plan_id` )
	REFERENCES `plan`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_files_file_to_plan" ------------------------
ALTER TABLE `file_to_plan`
	ADD CONSTRAINT `lnk_files_file_to_plan` FOREIGN KEY ( `file_id` )
	REFERENCES `files`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_plan_file_to_plan" -------------------------
ALTER TABLE `file_to_plan`
	ADD CONSTRAINT `lnk_plan_file_to_plan` FOREIGN KEY ( `plan_id` )
	REFERENCES `plan`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_plan_history" ------------------------------
ALTER TABLE `history`
	ADD CONSTRAINT `lnk_plan_history` FOREIGN KEY ( `plan_id` )
	REFERENCES `plan`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_user_history" ------------------------------
ALTER TABLE `history`
	ADD CONSTRAINT `lnk_user_history` FOREIGN KEY ( `user_id` )
	REFERENCES `user`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_user_plan" ---------------------------------
ALTER TABLE `plan`
	ADD CONSTRAINT `lnk_user_plan` FOREIGN KEY ( `user_id` )
	REFERENCES `user`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_user_plan_2" -------------------------------
ALTER TABLE `plan`
	ADD CONSTRAINT `lnk_user_plan_2` FOREIGN KEY ( `user_id` )
	REFERENCES `user`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_layer_point" -------------------------------
ALTER TABLE `point`
	ADD CONSTRAINT `lnk_layer_point` FOREIGN KEY ( `layer_id` )
	REFERENCES `layer`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_plan_point_to_plan" ------------------------
ALTER TABLE `point_to_plan`
	ADD CONSTRAINT `lnk_plan_point_to_plan` FOREIGN KEY ( `plan_id` )
	REFERENCES `plan`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_point_point_to_plan" -----------------------
ALTER TABLE `point_to_plan`
	ADD CONSTRAINT `lnk_point_point_to_plan` FOREIGN KEY ( `point_id` )
	REFERENCES `point`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- ---------------------------------------------------------


