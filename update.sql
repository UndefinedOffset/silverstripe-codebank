ALTER TABLE languages ADD COLUMN user_language INT(1) NOT NULL DEFAULT 0;

INSERT INTO languages(language,file_extension,shjs_code) VALUES('Yaml', 'yml', 'yml');