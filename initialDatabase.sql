--Authentication and panel management
CREATE DATABASE authentication;

USE authentication;

CREATE TABLE `groups` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(32) NOT NULL DEFAULT '0',
	`displayname` VARCHAR(32) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`),
	UNIQUE INDEX `displayname` (`displayname`)
)
COLLATE='utf8_general_ci';

CREATE TABLE `users` (
	`authkey` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(64) NOT NULL,
	`email` VARCHAR(64) NOT NULL,
	`password` VARCHAR(64) NOT NULL,
	`session` VARCHAR(64) NULL DEFAULT NULL,
	`verified` TINYINT(1) NOT NULL DEFAULT '0',
	`approved` TINYINT(1) NOT NULL DEFAULT '0',
	`data` VARCHAR(64) NULL DEFAULT NULL,
	`lastaction` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`authkey`),
	UNIQUE INDEX `unique_email` (`email`),
	UNIQUE INDEX `unique_username` (`username`),
	INDEX `verified` (`verified`),
	INDEX `approved` (`approved`),
	INDEX `username` (`username`),
	INDEX `email` (`email`)
)
COLLATE='utf8_general_ci'
AUTO_INCREMENT=1;

CREATE TABLE `passwordreset` (
	`authkey` INT(10) UNSIGNED NOT NULL,
	`resetkey` VARCHAR(64) NOT NULL,
	UNIQUE INDEX `authkey` (`authkey`),
	CONSTRAINT `FK_passwordreset_users` FOREIGN KEY (`authkey`) REFERENCES `users` (`authkey`)
)
COLLATE='utf8_general_ci';

CREATE TABLE `permissions` (
	`perm` VARCHAR(64) NOT NULL,
	`userId` INT(10) UNSIGNED NULL DEFAULT NULL,
	`groupId` INT(10) UNSIGNED NULL DEFAULT NULL,
	INDEX `FK_permissions_users` (`userId`),
	INDEX `FK_permissions_groups` (`groupId`),
	CONSTRAINT `FK_permissions_users` FOREIGN KEY (`userId`) REFERENCES `users` (`authkey`),
	CONSTRAINT `FK_permissions_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`id`)
)
COLLATE='utf8_general_ci';

--Factoid management
CREATE DATABASE factoid;

USE factoid;

CREATE TABLE `games` (
	`id` SMALLINT(6) UNSIGNED NOT NULL AUTO_INCREMENT,
	`idname` VARCHAR(255) NOT NULL,
	`displayname` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `idname` (`idname`),
	UNIQUE INDEX `displayname` (`displayname`)
)
COLLATE='utf8_general_ci'
AUTO_INCREMENT=1;

CREATE TABLE `factoids` (
	`id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(128) NOT NULL,
	`game` SMALLINT(6) UNSIGNED NOT NULL DEFAULT '0',
	`content` MEDIUMTEXT NOT NULL,
	PRIMARY KEY (`name`, `game`),
	UNIQUE INDEX `unique_id` (`id`),
	INDEX `FK_factoids_games` (`game`),
	INDEX `key_id` (`id`),
	CONSTRAINT `FK_factoids_games` FOREIGN KEY (`game`) REFERENCES `games` (`id`)
)
COLLATE='utf8_general_ci'
AUTO_INCREMENT=1;

--Create users

CREATE USER 'panel'@'localhost' IDENTIFIED BY '';

GRANT ALL ON authentication.* TO 'panel'@'localhost';
GRANT ALL ON factoid.* TO 'panel'@'localhost';
