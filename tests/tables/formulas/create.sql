CREATE TABLE IF NOT EXISTS `formulas` (
  `formula_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `formula_name` varchar(100) NOT NULL,
  `fragrance_id` int(11) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `formula_description` text,
  PRIMARY KEY (`formula_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;