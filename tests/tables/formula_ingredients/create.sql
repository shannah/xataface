CREATE TABLE IF NOT EXISTS `formula_ingredients` (
  `formula_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `concentration` decimal(10,3) NOT NULL,
  `concentration_units` int(11) NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `amount_units` int(11) NOT NULL,
  PRIMARY KEY (`formula_id`,`ingredient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
