--Authentication and panel management
CREATE DATABASE authentication;

USE authentication;

CREATE TABLE `users` (
	`userId` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`uuid` CHAR(36) NOT NULL,
	`username` VARCHAR(64) NOT NULL,
	`email` VARCHAR(64) NOT NULL,
	`password` VARCHAR(64) NOT NULL,
	`session` VARCHAR(64) NULL DEFAULT NULL,
	`verified` TINYINT(1) NOT NULL DEFAULT '0',
	`approved` TINYINT(1) NOT NULL DEFAULT '0',
	`data` VARCHAR(64) NULL DEFAULT NULL,
	`lastaction` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`userId`),
	UNIQUE INDEX `unique_email` (`email`),
	UNIQUE INDEX `unique_username` (`username`),
	UNIQUE INDEX `unique_uuid` (`uuid`),
	INDEX `verified` (`verified`),
	INDEX `approved` (`approved`),
	INDEX `username` (`username`),
	INDEX `email` (`email`),
	INDEX `uuid` (`uuid`)
)
COLLATE='utf8_general_ci';

CREATE TABLE `passwordreset` (
	`userUUID` CHAR(36) NOT NULL,
	`resetkey` VARCHAR(64) NOT NULL,
	PRIMARY KEY (`userUUID`),
	CONSTRAINT `FK_passwordreset_users` FOREIGN KEY (`userUUID`) REFERENCES `users` (`uuid`)
)
COLLATE='utf8_general_ci';

CREATE TABLE `permissions` (
	`perm` VARCHAR(64) NOT NULL,
	`grantedby` VARCHAR(64) NOT NULL,
	PRIMARY KEY (`perm`),
	INDEX `grant_to_parent` (`grantedby`),
	CONSTRAINT `grant_to_parent` FOREIGN KEY (`grantedby`) REFERENCES `permissions` (`perm`)
)
COLLATE='utf8_general_ci';

CREATE TABLE `perms_user` (
	`userUUID` CHAR(36) NOT NULL,
	`perm` VARCHAR(64) NOT NULL,
	UNIQUE INDEX `perm_to_user` (`userUUID`, `perm`),
	INDEX `FK_perms_user_permissions` (`perm`),
	CONSTRAINT `FK_perms_user_permissions` FOREIGN KEY (`perm`) REFERENCES `permissions` (`perm`),
	CONSTRAINT `FK_perms_user_users` FOREIGN KEY (`userUUID`) REFERENCES `users` (`uuid`)
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
COLLATE='utf8_general_ci';

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
COLLATE='utf8_general_ci';

--Create users

CREATE USER 'panel'@'localhost' IDENTIFIED BY '';

GRANT ALL ON authentication.* TO 'panel'@'localhost';
GRANT ALL ON factoid.* TO 'panel'@'localhost';
