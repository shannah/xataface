DROP TABLE IF EXISTS `__fields__`;
CREATE TABLE IF NOT EXISTS `__fields__` (
	`fields_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
	`table` VARCHAR(64) NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`widget:type` VARCHAR(64),
	`widget:label` VARCHAR(128),
	`widget:description` TEXT,
	`visibility:list` VARCHAR(15),
	`vocabulary` VARCHAR(64),
	`atts:style` VARCHAR(255),
	`file` VARCHAR(128)
	);
	
DROP TABLE IF EXISTS `__properties__`;
CREATE TABLE IF NOT EXISTS `__properties__` (
	`parent_id` INT(11) NOT NULL,
	`parent_type` ENUM('fields','actions','relationships','valuelists','table'),
	`property_name` VARCHAR(128) NOT NULL,
	`property_value` TEXT,
	PRIMARY KEY (`parent_id`,`parent_type`,`property_name`)
	);

DROP TABLE IF EXISTS `__valuelists__`;	
CREATE TABLE IF NOT EXISTS `__valuelists__` (
	`valuelists_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
	`table` VARCHAR(64) NOT NULL,
	`name`	VARCHAR(64) NOT NULL,
	`__sql__` TEXT,
	`file` VARCHAR(128)
	);

DROP TABLE IF EXISTS `__relationships__`;	
CREATE TABLE IF NOT EXISTS `__relationships__` (
	`relationships_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
	`table`	VARCHAR(64) NOT NULL,
	`name`	VARCHAR(64) NOT NULL,
	`__sql__`	TEXT,
	`file` VARCHAR(128)
	);
	

DROP TABLE IF EXISTS `__actions__`;	
CREATE TABLE IF NOT EXISTS `__actions__` (
	`actions_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
	`table`	VARCHAR(64) NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`label` VARCHAR(128),
	`description` TEXT,
	`icon`	VARCHAR(128),
	`category` VARCHAR(64),
	`accessKey` VARCHAR(2),
	`condition` VARCHAR(255),
	`permission` VARCHAR(64),
	`file`		VARCHAR(128)
	);

DROP TABLE IF EXISTS `__table__`;	
CREATE TABLE IF NOT EXISTS `__table__` (

	`table_id`	INT(11) AUTO_INCREMENT PRIMARY KEY,
	`name` VARCHAR(128),
	`label` VARCHAR(128),
	`description` TEXT);
	




	