CREATE  TABLE `preferences` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(200) NOT NULL,
  `value` VARCHAR(200) NOT NULL,
  `fkUser` INT(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `User_UNIQUE`(`fkUser` ASC, `code` ASC),
  KEY `FK_preference_owner` (`fkUser`),
  CONSTRAINT `FK_preference_owner` FOREIGN KEY (`fkUser`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1$

INSERT INTO preferences (fkUser,`code`,`value`) SELECT id, 'heartbeat', '0' FROM users$

DROP TRIGGER IF EXISTS snippit_insert_trigger$
DROP TRIGGER IF EXISTS snippit_update_trigger$
DROP TRIGGER IF EXISTS snippit_delete_trigger$