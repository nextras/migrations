CREATE TABLE `languages` (
  `code` char(2) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `languages` (`code`, `name`) VALUES
('cs',	'Czech'),
('en',	'English');

CREATE TABLE `m` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(100) NOT NULL,
  `file` varchar(100) NOT NULL,
  `checksum` char(32) NOT NULL,
  `executed` datetime NOT NULL,
  `ready` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_file` (`group`,`file`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO `m` (`id`, `group`, `file`, `checksum`, `executed`, `ready`) VALUES
(1,	'structures',	'001.sql',	'0d0487793f672fc02900c015af2fa79a',	'2015-03-22 07:10:35',	1),
(2,	'structures',	'002.sql',	'c76f5e440cff34432101f93505fa0b55',	'2015-03-22 07:10:35',	1),
(3,	'basic-data',	'003.sql',	'44f34a6d029e9aca3abc5e9e33933083',	'2015-03-22 07:10:35',	1);

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `password_hash` char(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

