
CREATE USER 'gitphp' IDENTIFIED WITH mysql_native_password BY 'gitphp';

CREATE DATABASE gitphp;

GRANT ALL ON gitphp.* TO 'gitphp';

CREATE TABLE gitphp.Review (
  id int UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
);

CREATE TABLE gitphp.session (
  `id` char(32) NOT NULL,
  `k` varchar(255) NOT NULL DEFAULT '',
  `v` varchar(255) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE gitphp.`user` (
  `user` varchar(32) NOT NULL,
  `pubkey` text,
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
