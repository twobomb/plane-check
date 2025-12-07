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
	`content` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`parent_id` Int( 255 ) NOT NULL,
	`point_id` Int( 255 ) NOT NULL,
	PRIMARY KEY ( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 3;
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
	`date` DateTime NOT NULL,
	`type` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`value` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8
COLLATE = utf8_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 1;
-- -------------------------------------------------------------


-- CREATE TABLE "layer" ----------------------------------------
CREATE TABLE `layer`( 
	`id` Int( 255 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`description` VarChar( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
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

INSERT INTO `department`(`id`,`name`,`addr`,`content`,`parent_id`,`point_id`) VALUES 
( '1', '1 ПСЧ 1 ПСО г.Луганск', '', '', '0', '0' ),
( '2', '10 ПСЧ 10 ПСО г.Ровеньки', '', '', '0', '0' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "department_to_plan" -----------------------
BEGIN;

INSERT INTO `department_to_plan`(`department_id`,`plan_id`) VALUES 
( '1', '7' ),
( '1', '6' ),
( '2', '6' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "file_to_plan" -----------------------------
BEGIN;

INSERT INTO `file_to_plan`(`file_id`,`plan_id`) VALUES 
( '6', '6' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "files" ------------------------------------
BEGIN;

INSERT INTO `files`(`id`,`url`,`date`,`name`) VALUES 
( '6', 'uploaded/planfiles/e2991493fe9194957f3329fc1f5da3d3.js', '2025-12-07 21:12:56', 'select2.min.js' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "history" ----------------------------------
-- ---------------------------------------------------------


-- Dump data of "layer" ------------------------------------
-- ---------------------------------------------------------


-- Dump data of "plan" -------------------------------------
BEGIN;

INSERT INTO `plan`(`id`,`name`,`content`,`user_id`,`create_at`,`date_type`,`date_value`,`status`,`parent_id`) VALUES 
( '6', 'Тестаплан123', '<p>ferrege</p><p>reer</p><p><img src="/uploaded/images/1765135448_6935d4589271e.png"></p><p><img src="/uploaded/images/1765135595_6935d4eb2a63d.png" alt="" class="quill-image"><img src="/uploaded/images/1765135595_6935d4eb2c3ae.jpeg" alt="" class="quill-image"><img src="/uploaded/images/1765135595_6935d4eb330d1.png" alt="" class="quill-image"></p><p><br></p>', '1', '2025-12-07 21:12:56', 'month', '2025-11-30', 'pending', '0' ),
( '7', 'ewrew2', '<p>fewfew</p>', '1', '2025-12-07 21:57:28', 'month', '2025-11-30', 'pending', '0' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "point" ------------------------------------
BEGIN;

INSERT INTO `point`(`id`,`layer_id`,`type`,`lon`,`lat`,`addr`,`name`,`description`) VALUES 
( '1', NULL, '', '0', '0', '', 'Геоточка #1', '' ),
( '2', NULL, '', '0', '0', '', 'Еще одна геоточка с длинным название ', '' );
COMMIT;
-- ---------------------------------------------------------


-- Dump data of "point_to_plan" ----------------------------
BEGIN;

INSERT INTO `point_to_plan`(`plan_id`,`point_id`) VALUES 
( '7', '1' ),
( '6', '2' );
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


