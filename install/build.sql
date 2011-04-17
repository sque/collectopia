DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `places`;
DROP TABLE IF EXISTS `cities`;
DROP TABLE IF EXISTS `countries`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `places_cats`;
DROP TABLE IF EXISTS `actions_log`;
DROP TABLE IF EXISTS `marker_packs`;

-- Create users
create table `users` (
    `username` varchar(50) not null,
    `password` varchar(40) not null,
    `enabled` int(1) not null,
    primary key(`username`)
) DEFAULT CHARSET='UTF8';

CREATE TABLE `marker_packs` (
	`hash` char(32) NOT NULL,
	`total` integer NOT NULL,
	`center_lng` float NOT NULL,
	`center_lat` float NOT NULL,
	PRIMARY KEY(`hash`)
) ENGINE=InnoDB DEFAULT CHARSET UTF8;

CREATE TABLE `places` (
	`id` integer auto_increment,
	`slug` char(255) NOT NULL,
	`short_name` CHAR(12),
	`name` varchar(80) not null,
	`loc_lng` FLOAT not null,
	`loc_lat` FLOAT not null,
	`markerpack_hash` CHAR(32) not null,
	`markerpack_index` INTEGER not null,
	`area_level_1` varchar(80) not null,
	`area_level_2` varchar(80) not null,
	`country` varchar(80) not null,
	`city` varchar(80) not null,
	`street` varchar(255) not null,
	`street_number` varchar(255) not null,
	`address` varchar(200) not null,
	`email` varchar(80) not null,
	`web` varchar(255) not null,
	`video` varchar(255) not null,
	`tel` varchar(20) not null,
	`description` TEXT not null,
	`rate_current` FLOAT not null,
	`rate_total` INTEGER not null,	
	`created_at` DATETIME not null,
	PRIMARY KEY (`id`),
	KEY (`slug`)
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
	`secret` char(32) NOT NULL,
	`place_id` integer,
	`name` varchar(255) NOT NULL,
	`width` integer NOT NULL,
	`height` integer NOT NULL,
	`size` integer NOT NULL,
	`data_hash` char(32) NOT NULL,
	`created_at` DATETIME,
	PRIMARY KEY(`id`),
	KEY(`secret`),
	FOREIGN KEY (`place_id`) REFERENCES `places` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET UTF8; 


INSERT INTO `users` (`username`, `password`, `enabled`) values ('root', sha1('root'), 1);

INSERT INTO `categories` (`tag`, `color`, `title`) VALUES
	('food', 'f12324', 'Food'),
	('freestuff', 'a34b93', 'Stuff'),
	('fun', '49a729', 'Entertainment'),
	('house', '6c6c6c', 'Accommodation'),
	('learning', '5196d2', 'Learning/Library'),
	('talk', 'ffd70c', 'Discussion');