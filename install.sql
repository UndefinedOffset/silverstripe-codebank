DROP TABLE IF EXISTS `languages`$
CREATE TABLE `languages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(100) NOT NULL,
  `file_extension` varchar(45) NOT NULL,
  `shjs_code` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_language` (`language`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=latin1$

INSERT INTO `languages` VALUES (1,'Flex 3','mxml','Flex'),(2,'ActionScript 3','as','AS3'),(3,'PHP','php','Php'),(4,'Bison','bison','bison'),(5,'C','c','Cpp'),(6,'C++','cpp','Cpp'),(7,'C#','csharp','CSharp'),(8,'ChangeLog','log','Plain'),(9,'CSS','css','Css'),(10,'Diff','diff','Diff'),(11,'Flex','mxml','Flex'),(12,'GLSL','glsl','Plain'),(13,'Haxe','haxe','Plain'),(14,'HTML','html','Xml'),(15,'Java','java','Java'),(16,'Java properties','properties','properties'),(17,'JavaScript','js','JScript'),(18,'JavaScript with DOM','js','JScript'),(19,'LaTeX','latax','Latex'),(20,'LDAP','ldap','Plain'),(21,'Log','log','Plain'),(22,'LSM (Linux Software Map)','lsm','Plain'),(23,'M4','m4','Plain'),(24,'Makefile','makefile','Plain'),(25,'Oracle SQL','sql','Sql'),(26,'Pascal','pascal','Delphi'),(27,'Perl','pl','Perl'),(28,'Prolog','prolog','Plain'),(29,'Python','python','Python'),(30,'RPM spec','spec','Plain'),(31,'Ruby','ruby','Ruby'),(32,'S-Lang','slang','Plain'),(33,'Scala','scala','Scala'),(34,'Shell','sh','Bash'),(35,'SQL','sql','Sql'),(36,'Standard ML','sml','Plain'),(37,'Tcl','tcl','Plain'),(38,'XML','xml','Xml'),(39,'Xorg configuration','conf','Plain'),(40,'Objective Caml','caml','Plain'),(41,'AppleScript','applescript','AppleScript'),(42,'Assembler','asm','Asm'),(43,'Ada','ada','Ada'),(44,'ColdFusion','cf','ColdFusion'),(45,'Batch','bat','Bat'),(46,'Bash','bash','Bash'),(47,'Delphi','delphi','Delphi'),(48,'Erlang','el','Erlang'),(49,'F#','fsharp','FSharp'),(50,'Groovy','groovy','Groovy'),(51,'Visual Basic','vb','Vb'),(52,'PowerShell','ps','PowerShell'),(53,'Other','txt','Plain'),(54,'SilverStripe Template','ss','SilverStripe')$


DROP TABLE IF EXISTS `settings`$
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(300) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Index_2` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1$

INSERT INTO `settings` VALUES (1,'ipMessage',''),(2,'version','@@VERSION@@ @@BUILD_DATE@@')$


DROP TABLE IF EXISTS `users`$
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL,
  `loginKey` varchar(32) DEFAULT NULL,
  `lastLogin` varchar(18) DEFAULT NULL,
  `lastLoginIP` varchar(40) DEFAULT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1$


DROP TABLE IF EXISTS `snippits`$
CREATE TABLE `snippits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `description` varchar(600) NOT NULL,
  `tags` varchar(400) NOT NULL,
  `fkLanguage` int(10) unsigned NOT NULL,
  `fkCreatorUser` int(10) unsigned DEFAULT NULL,
  `fkLastEditUser` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_snippits_languages` (`fkLanguage`),
  KEY `FK_snippits_editor` (`fkLastEditUser`),
  KEY `FK_snippits_creator` (`fkCreatorUser`),
  CONSTRAINT `FK_snippits_creator` FOREIGN KEY (`fkCreatorUser`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_snippits_editor` FOREIGN KEY (`fkLastEditUser`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_snippits_languages` FOREIGN KEY (`fkLanguage`) REFERENCES `languages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1$


DROP TABLE IF EXISTS `snippit_search`$
CREATE TABLE `snippit_search` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(300) NOT NULL ,
  `description` VARCHAR(600) NOT NULL ,
  `tags` VARCHAR(400) NOT NULL ,
  `SnippitID` INT NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM$


DROP TABLE IF EXISTS `snippit_history`$
CREATE TABLE `snippit_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkSnippit` int(10) unsigned NOT NULL,
  `text` blob NOT NULL,
  `date` varchar(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_snippit_history_snippits` (`fkSnippit`),
  CONSTRAINT `FK_snippit_history_snippits` FOREIGN KEY (`fkSnippit`) REFERENCES `snippits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1$


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
