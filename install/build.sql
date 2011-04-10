DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `places`;
DROP TABLE IF EXISTS `cities`;
DROP TABLE IF EXISTS `countries`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `places_cats`;
DROP TABLE IF EXISTS `actions_log`;

-- Countries
CREATE TABLE `countries` (
	`id` integer auto_increment,
	`name` varchar(80) not null,
	`iso3166` char(2) not null,
	`continent_id` integer not null,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`name`)	
) ENGINE = InnoDB DEFAULT CHARSET UTF8;

-- Create users
create table `users` (
    `username` varchar(50) not null,
    `password` varchar(40) not null,
    `enabled` int(1) not null,
    primary key(`username`)
) DEFAULT CHARSET='UTF8';

CREATE TABLE `cities` (
	`id` integer auto_increment,
	`name` varchar(80) not null,
	`country_id` integer not null,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`name`),
	FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET UTF8;

CREATE TABLE `places` (
	`id` integer auto_increment,
	`name` varchar(80) not null,
	`loc_lng` FLOAT not null,
	`loc_lat` FLOAT not null,
	`area_level_1` varchar(80) not null,
	`area_level_2` varchar(80) not null,
	`country` varchar(80) not null,
	`city` varchar(80) not null,
	`street` varchar(255) not null,
	`street_number` varchar(255) not null,
	`address` varchar(200) not null,
	`email` varchar(80) not null,
	`web` varchar(255) not null,
	`tel` varchar(20) not null,
	`description` TEXT not null,
	`rate_current` FLOAT not null,
	`rate_total` INTEGER not null,	
	`created_at` DATETIME not null,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET UTF8;

CREATE TABLE `categories` (
	`tag` char(10) not null,
	`color` char(6) not null,
	`title` varchar(50),
	PRIMARY KEY (`tag`),
	UNIQUE KEY (`title`)
) ENGINE = InnoDB DEFAULT CHARSET UTF8;

CREATE TABLE `places_cats` (
	`cat_tag` char(10) not null,
	`place_id` integer not null,
	PRIMARY KEY (`cat_tag`, `place_id`),
	FOREIGN KEY (`cat_tag`) REFERENCES `categories` (`tag`),
	FOREIGN KEY (`place_id`) REFERENCES `places` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET UTF8;

CREATE TABLE `actions_log` (
	`id` integer auto_increment,
	`ip` varchar(34) not null,
	`when` DATETIME not null,
	`what` TEXT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET UTF8;

CREATE TABLE `photos` (
	`id` integer auto_increment,
	`place_id` integer NOT NULL,
	`name` varchar(255) NOT NULL,
	`width` integer NOT NULL,
	`height` integer NOT NULL,
	`data_hash` char(32) NOT NULL,	
	PRIMARY KEY(`id`),
	FOREIGN KEY (`place_id`) REFERENCES `places` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET UTF8; 

CREATE TABLE `tmp_photos` (
	`key` char(32) NOT NULL,
	`name` varchar(255) NOT NULL,
	`data_hash` char(32) NOT NULL,	
	`created_at` DATETIME not null,
	PRIMARY KEY(`key`)
) ENGINE=InnoDB DEFAULT CHARSET UTF8; 

INSERT INTO `users` (`username`, `password`, `enabled`) values ('root', sha1('root'), 1);

INSERT INTO `categories` (`tag`, `color`, `title`) VALUES
	('food', 'ff0000', 'Food'),
	('freestuff', '993399', 'FreeStuff'),
	('fun', '00cc00', 'Fun'),
	('house', 'ff9933', 'House'),
	('learning', '3333ff', 'Learning'),
	('talk', '000000', 'Black');