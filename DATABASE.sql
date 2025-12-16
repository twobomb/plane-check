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
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`addr` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`parent_id` Int( 0 ) NULL DEFAULT NULL,
	`point_id` Int( 0 ) NULL DEFAULT NULL,
	`sort_id` Int( 0 ) NOT NULL DEFAULT 0,
	`lat` Decimal( 9, 6 ) NULL DEFAULT NULL,
	`lng` Decimal( 9, 6 ) NULL DEFAULT NULL,
	PRIMARY KEY ( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 497;
-- -------------------------------------------------------------


-- CREATE TABLE "department_to_plan" ---------------------------
CREATE TABLE `department_to_plan`( 
	`department_id` Int( 0 ) NOT NULL,
	`plan_id` Int( 0 ) NOT NULL )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "file_to_plan" ---------------------------------
CREATE TABLE `file_to_plan`( 
	`file_id` Int( 0 ) NOT NULL,
	`plan_id` Int( 0 ) NOT NULL )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "files" ----------------------------------------
CREATE TABLE `files`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`url` Text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`date` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 20;
-- -------------------------------------------------------------


-- CREATE TABLE "history" --------------------------------------
CREATE TABLE `history`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`user_id` Int( 0 ) NOT NULL,
	`plan_id` Int( 0 ) NOT NULL,
	`date` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`type` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`value` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 73;
-- -------------------------------------------------------------


-- CREATE TABLE "layer" ----------------------------------------
CREATE TABLE `layer`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`description` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
	`visible` TinyInt( 0 ) NOT NULL DEFAULT 1,
	`project_id` Int( 0 ) NOT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 31;
-- -------------------------------------------------------------


-- CREATE TABLE "plan" -----------------------------------------
CREATE TABLE `plan`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`content` MediumText CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`user_id` Int( 0 ) NOT NULL,
	`create_at` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`date_type` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`date_value` Date NULL DEFAULT NULL,
	`status` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`parent_id` Int( 0 ) NULL DEFAULT NULL,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 28;
-- -------------------------------------------------------------


-- CREATE TABLE "point" ----------------------------------------
CREATE TABLE `point`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`layer_id` Int( 0 ) NULL DEFAULT NULL,
	`type` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
	`lng` Decimal( 9, 6 ) NOT NULL,
	`lat` Decimal( 9, 6 ) NOT NULL,
	`addr` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`description` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
	`color` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#3498db',
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 101;
-- -------------------------------------------------------------


-- CREATE TABLE "point_to_plan" --------------------------------
CREATE TABLE `point_to_plan`( 
	`plan_id` Int( 0 ) NOT NULL,
	`point_id` Int( 0 ) NOT NULL )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "project" --------------------------------------
CREATE TABLE `project`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`user_id` Int( 0 ) NOT NULL,
	`created_at` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`access` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'public',
	`center_lat` Decimal( 9, 6 ) NOT NULL,
	`center_lng` Decimal( 9, 6 ) NOT NULL,
	`scheme` VarChar( 255 ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
	`zoom` Int( 0 ) NOT NULL,
	`showLabels` TinyInt( 0 ) NOT NULL,
	`description` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`updated_at` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 14;
-- -------------------------------------------------------------


-- CREATE TABLE "role" -----------------------------------------
CREATE TABLE `role`( 
	`name` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`user_id` Int( 0 ) NOT NULL )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB;
-- -------------------------------------------------------------


-- CREATE TABLE "user" -----------------------------------------
CREATE TABLE `user`( 
	`id` Int( 0 ) AUTO_INCREMENT NOT NULL,
	`username` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`login` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`pwd_hash` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`create_at` DateTime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`remember_token` VarChar( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	`is_blocked` TinyInt( 0 ) NOT NULL DEFAULT 0,
	PRIMARY KEY ( `id` ),
	CONSTRAINT `login` UNIQUE( `login` ),
	CONSTRAINT `unique_id` UNIQUE( `id` ) )
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci
ENGINE = InnoDB
AUTO_INCREMENT = 7;
-- -------------------------------------------------------------


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


-- CREATE INDEX "lnk_project_layer" ----------------------------
CREATE INDEX `lnk_project_layer` USING BTREE ON `layer`( `project_id` );
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


-- CREATE INDEX "lnk_user_project" -----------------------------
CREATE INDEX `lnk_user_project` USING BTREE ON `project`( `user_id` );
-- -------------------------------------------------------------


-- CREATE INDEX "lnk_user_role" --------------------------------
CREATE INDEX `lnk_user_role` USING BTREE ON `role`( `user_id` );
-- -------------------------------------------------------------


-- CREATE LINK "lnk_department_department_to_plan" -------------
ALTER TABLE `department_to_plan`
	ADD CONSTRAINT `lnk_department_department_to_plan` FOREIGN KEY ( `department_id` )
	REFERENCES `department`( `id` )
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


-- CREATE LINK "lnk_layer_point" -------------------------------
ALTER TABLE `point`
	ADD CONSTRAINT `lnk_layer_point` FOREIGN KEY ( `layer_id` )
	REFERENCES `layer`( `id` )
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


-- CREATE LINK "lnk_plan_point_to_plan" ------------------------
ALTER TABLE `point_to_plan`
	ADD CONSTRAINT `lnk_plan_point_to_plan` FOREIGN KEY ( `plan_id` )
	REFERENCES `plan`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_point_point_to_plan_2" ---------------------
ALTER TABLE `point_to_plan`
	ADD CONSTRAINT `lnk_point_point_to_plan_2` FOREIGN KEY ( `point_id` )
	REFERENCES `point`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_project_layer" -----------------------------
ALTER TABLE `layer`
	ADD CONSTRAINT `lnk_project_layer` FOREIGN KEY ( `project_id` )
	REFERENCES `project`( `id` )
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


-- CREATE LINK "lnk_user_project" ------------------------------
ALTER TABLE `project`
	ADD CONSTRAINT `lnk_user_project` FOREIGN KEY ( `user_id` )
	REFERENCES `user`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


-- CREATE LINK "lnk_user_role" ---------------------------------
ALTER TABLE `role`
	ADD CONSTRAINT `lnk_user_role` FOREIGN KEY ( `user_id` )
	REFERENCES `user`( `id` )
	ON DELETE Cascade
	ON UPDATE Cascade;
-- -------------------------------------------------------------


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- ---------------------------------------------------------


