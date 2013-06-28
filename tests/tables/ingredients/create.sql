CREATE TABLE IF NOT EXISTS `ingredients` (
  `ingredient_id` int(11) NOT NULL AUTO_INCREMENT,
  `ingredient_name` varchar(100) NOT NULL,
  `default_concentration` float DEFAULT NULL,
  `default_concentration_units` int(11) DEFAULT NULL,
  `default_units` int(11) DEFAULT NULL,
  PRIMARY KEY (`ingredient_id`),
  UNIQUE KEY `ingredient_name` (`ingredient_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;
