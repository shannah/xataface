CREATE TABLE IF NOT EXISTS `test_versions` (
  `test_versions_id` int(11) NOT NULL AUTO_INCREMENT,
  `varchar_field` varchar(100) NOT NULL,
  `version` int(11) DEFAULT 0,
  PRIMARY KEY (`test_versions_id`)
)